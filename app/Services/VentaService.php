<?php

namespace App\Services;

use App\Models\Core\Caja;
use App\Models\Core\CajaMovimiento;
use App\Models\Core\Clase;
use App\Models\Core\Cliente;
use App\Models\Core\ClientDebt;
use App\Models\Core\CouponUsage;
use App\Models\Core\Employee;
use App\Models\Core\EmployeeDebt;
use App\Models\Core\PaymentMethod;
use App\Models\Core\Producto;
use App\Models\Core\RentableSpace;
use App\Models\Core\ServicioExterno;
use App\Models\Core\Venta;
use App\Models\Core\VentaItem;
use App\Models\System\ComprobanteConfig;
use Illuminate\Support\Facades\DB;

class VentaService
{
    private const CLIENTE_SOLO_VENTA_NOMBRE_DEFAULT = 'ninguno';

    private const CLIENTE_SOLO_VENTA_DOCUMENTO_DEFAULT = '00000000';

    protected CajaService $cajaService;

    protected InventarioService $inventarioService;

    public function __construct(CajaService $cajaService, InventarioService $inventarioService)
    {
        $this->cajaService = $cajaService;
        $this->inventarioService = $inventarioService;
    }

    /**
     * Procesar una venta
     */
    public function procesarVenta(array $data): Venta
    {
        $caja = $this->cajaService->obtenerCajaAbiertaPorUsuario(auth()->id());
        if (! $caja) {
            throw new \Exception('No hay una caja abierta. Por favor, abra una caja antes de procesar ventas.');
        }

        $items = $data['items'] ?? [];
        if (empty($items)) {
            throw new \Exception('La venta debe tener al menos un item.');
        }

        return DB::transaction(function () use ($data, $caja, $items) {
            $sucursalId = (int) $caja->sucursal_id;
            $itemsValidados = $this->validarItems($items, $sucursalId);

            $subtotal = $this->calcularSubtotal($itemsValidados);
            $descuento = $data['descuento'] ?? 0;
            $montoDescuentoCupon = (float) ($data['monto_descuento_cupon'] ?? 0);
            $baseConIgv = $subtotal - $descuento - $montoDescuentoCupon;
            $igv = round($baseConIgv * 18 / 118, 2);
            $total = max(0, $baseConIgv);

            $numeroVenta = $this->generarNumeroVenta();
            $comprobante = $this->generarComprobante($data['tipo_comprobante'] ?? 'ticket');

            $paymentMethodId = $data['payment_method_id'] ?? null;
            $metodoPago = $data['metodo_pago'] ?? 'efectivo';
            if ($paymentMethodId) {
                $pm = $this->resolvePaymentMethod((int) $paymentMethodId, $sucursalId);
                $metodoPago = $pm->nombre;
            }

            $tipoComprador = $data['tipo_comprador'] ?? 'cliente';
            $clienteId = $tipoComprador === 'cliente' ? ($data['cliente_id'] ?? null) : null;
            $employeeId = $tipoComprador === 'empleado' ? ($data['employee_id'] ?? null) : null;
            $this->assertCompradorSucursal($tipoComprador, $clienteId, $employeeId, $sucursalId);

            $clienteVentaNombre = $tipoComprador === 'cliente_solo_venta'
                ? $this->valueOrDefault($data['cliente_venta_nombre'] ?? null, self::CLIENTE_SOLO_VENTA_NOMBRE_DEFAULT)
                : null;
            $clienteVentaDocumento = $tipoComprador === 'cliente_solo_venta'
                ? $this->valueOrDefault($data['cliente_venta_documento'] ?? null, self::CLIENTE_SOLO_VENTA_DOCUMENTO_DEFAULT)
                : null;
            $clienteVentaTelefono = $tipoComprador === 'cliente_solo_venta'
                ? (trim((string) ($data['cliente_venta_telefono'] ?? '')) ?: null)
                : null;

            $esCredito = ! empty($data['es_credito']) && ($clienteId || $employeeId);
            $montoInicial = $esCredito ? (float) ($data['monto_inicial'] ?? 0) : 0;
            $fechaVencimientoDeuda = $esCredito && ! empty($data['fecha_vencimiento_deuda'])
                ? $data['fecha_vencimiento_deuda']
                : null;

            $venta = Venta::create([
                'numero_venta' => $numeroVenta,
                'cliente_id' => $clienteId,
                'employee_id' => $employeeId,
                'cliente_venta_nombre' => $clienteVentaNombre,
                'cliente_venta_documento' => $clienteVentaDocumento,
                'cliente_venta_telefono' => $clienteVentaTelefono,
                'caja_id' => $caja->id,
                'usuario_id' => auth()->id(),
                'tipo_comprobante' => $data['tipo_comprobante'] ?? 'ticket',
                'numero_comprobante' => $comprobante['numero'],
                'serie_comprobante' => $comprobante['serie'],
                'subtotal' => $subtotal,
                'descuento' => $descuento,
                'igv' => $igv,
                'total' => $total,
                'metodo_pago' => $metodoPago,
                'payment_method_id' => $paymentMethodId,
                'numero_operacion' => $data['numero_operacion'] ?? null,
                'entidad_financiera' => $data['entidad_financiera'] ?? null,
                'discount_coupon_id' => $data['discount_coupon_id'] ?? null,
                'monto_descuento_cupon' => $montoDescuentoCupon,
                'es_credito' => $esCredito,
                'monto_inicial' => $esCredito ? $montoInicial : null,
                'fecha_vencimiento_deuda' => $fechaVencimientoDeuda,
                'estado' => 'completada',
                'fecha_venta' => now(),
                'observaciones' => $data['observaciones'] ?? null,
                'sucursal_id' => $sucursalId,
            ]);

            foreach ($itemsValidados as $item) {
                VentaItem::create([
                    'venta_id' => $venta->id,
                    'tipo_item' => $item['tipo'],
                    'item_id' => $item['id'],
                    'nombre_item' => $item['nombre'],
                    'cantidad' => $item['cantidad'],
                    'precio_unitario' => $item['precio'],
                    'descuento' => $item['descuento'] ?? 0,
                    'subtotal' => $item['subtotal'],
                    'sucursal_id' => $sucursalId,
                ]);

                if ($item['tipo'] === 'producto') {
                    $this->inventarioService->registrarSalidaVenta($item['id'], $item['cantidad'], $venta->id);
                }
            }

            if (($data['discount_coupon_id'] ?? null) && $montoDescuentoCupon > 0) {
                $coupon = \App\Models\Core\DiscountCoupon::find($data['discount_coupon_id']);
                if ($coupon) {
                    CouponUsage::create([
                        'discount_coupon_id' => $coupon->id,
                        'usable_type' => Venta::class,
                        'usable_id' => $venta->id,
                        'monto_descuento_aplicado' => $montoDescuentoCupon,
                        'usado_por' => auth()->id(),
                    ]);
                    $coupon->increment('cantidad_usada');
                }
            }

            if ($esCredito && $venta->cliente_id) {
                $saldoPendiente = $total - $montoInicial;
                $estado = $saldoPendiente <= 0 ? 'pagado' : ($montoInicial > 0 ? 'parcial' : 'pendiente');
                ClientDebt::create([
                    'cliente_id' => $venta->cliente_id,
                    'sucursal_id' => $sucursalId,
                    'venta_id' => $venta->id,
                    'origen_tipo' => 'Pos',
                    'origen_id' => $venta->id,
                    'monto_total' => $total,
                    'monto_pagado' => $montoInicial,
                    'saldo_pendiente' => $saldoPendiente,
                    'fecha_registro' => now()->toDateString(),
                    'fecha_vencimiento' => $fechaVencimientoDeuda,
                    'estado' => $estado,
                    'observaciones' => 'Venta a credito POS - '.$venta->numero_venta,
                ]);
                $montoARegistrar = $montoInicial;
            } elseif ($esCredito && $venta->employee_id) {
                $saldoPendiente = $total - $montoInicial;
                $estado = $saldoPendiente <= 0 ? 'pagado' : ($montoInicial > 0 ? 'parcial' : 'pendiente');
                EmployeeDebt::create([
                    'employee_id' => $venta->employee_id,
                    'venta_id' => $venta->id,
                    'monto_total' => $total,
                    'monto_abonado' => $montoInicial,
                    'saldo_pendiente' => $saldoPendiente,
                    'fecha_vencimiento' => $fechaVencimientoDeuda,
                    'estado' => $estado,
                    'observaciones' => 'Venta a credito POS - '.$venta->numero_venta,
                ]);
                $montoARegistrar = $montoInicial;
            } else {
                $montoARegistrar = $total;
            }

            $this->registrarPagoEnCaja($venta, $caja, $montoARegistrar);

            return $venta->fresh(['cliente', 'caja', 'usuario', 'items']);
        });
    }

    /**
     * Validar items de venta
     */
    protected function validarItems(array $items, int $sucursalId): array
    {
        $itemsValidados = [];

        foreach ($items as $item) {
            $tipo = $item['tipo'] ?? null;
            $id = $item['id'] ?? null;
            $cantidad = (int) ($item['cantidad'] ?? 1);

            if (! $tipo || ! $id || $cantidad <= 0) {
                throw new \Exception('Item invalido en la venta.');
            }

            if ($tipo === 'producto') {
                $producto = Producto::find($id);
                if (! $producto) {
                    throw new \Exception("Producto con ID {$id} no encontrado.");
                }
                $this->assertSucursalMatch((int) $producto->sucursal_id, $sucursalId, "El producto {$producto->nombre} no pertenece a la sucursal activa.");
                if ($producto->estado !== 'activo') {
                    throw new \Exception("El producto {$producto->nombre} no esta activo.");
                }
                if (! $producto->tieneStockSuficiente($cantidad)) {
                    throw new \Exception("Stock insuficiente para el producto {$producto->nombre}. Stock disponible: {$producto->stock_actual}");
                }

                $precio = (float) $producto->precio_venta;
                $descuento = (float) ($item['descuento'] ?? 0);
                $subtotal = ($precio * $cantidad) - $descuento;

                $itemsValidados[] = [
                    'tipo' => 'producto',
                    'id' => $id,
                    'nombre' => $producto->nombre,
                    'cantidad' => $cantidad,
                    'precio' => $precio,
                    'descuento' => $descuento,
                    'subtotal' => $subtotal,
                ];
            } elseif ($tipo === 'servicio') {
                $servicio = ServicioExterno::find($id);
                if (! $servicio) {
                    throw new \Exception("Servicio con ID {$id} no encontrado.");
                }
                $this->assertSucursalMatch((int) $servicio->sucursal_id, $sucursalId, "El servicio {$servicio->nombre} no pertenece a la sucursal activa.");
                if ($servicio->estado !== 'activo') {
                    throw new \Exception("El servicio {$servicio->nombre} no esta activo.");
                }

                $precio = (float) $servicio->precio;
                $descuento = (float) ($item['descuento'] ?? 0);
                $subtotal = ($precio * $cantidad) - $descuento;

                $itemsValidados[] = [
                    'tipo' => 'servicio',
                    'id' => $id,
                    'nombre' => $servicio->nombre,
                    'cantidad' => $cantidad,
                    'precio' => $precio,
                    'descuento' => $descuento,
                    'subtotal' => $subtotal,
                ];
            } elseif ($tipo === 'clase') {
                $clase = Clase::find($id);
                if (! $clase) {
                    throw new \Exception("Clase con ID {$id} no encontrada.");
                }
                $this->assertSucursalMatch((int) $clase->sucursal_id, $sucursalId, "La clase {$clase->nombre} no pertenece a la sucursal activa.");
                if ($clase->estado !== 'activo') {
                    throw new \Exception("La clase {$clase->nombre} no esta activa.");
                }

                $precio = (float) $clase->obtenerPrecio();
                $descuento = (float) ($item['descuento'] ?? 0);
                $subtotal = ($precio * $cantidad) - $descuento;

                $itemsValidados[] = [
                    'tipo' => 'clase',
                    'id' => $id,
                    'nombre' => $clase->nombre,
                    'cantidad' => $cantidad,
                    'precio' => $precio,
                    'descuento' => $descuento,
                    'subtotal' => $subtotal,
                ];
            } elseif ($tipo === 'alquiler') {
                $espacio = RentableSpace::find($id);
                if (! $espacio) {
                    throw new \Exception("Espacio con ID {$id} no encontrado.");
                }
                $this->assertSucursalMatch((int) $espacio->sucursal_id, $sucursalId, "El espacio {$espacio->nombre} no pertenece a la sucursal activa.");
                if ($espacio->estado !== 'activo') {
                    throw new \Exception("El espacio {$espacio->nombre} no esta activo.");
                }

                $precio = $espacio->precioPos();
                $descuento = (float) ($item['descuento'] ?? 0);
                $subtotal = ($precio * $cantidad) - $descuento;

                $itemsValidados[] = [
                    'tipo' => 'alquiler',
                    'id' => $id,
                    'nombre' => 'Alquiler: '.$espacio->nombre,
                    'cantidad' => $cantidad,
                    'precio' => $precio,
                    'descuento' => $descuento,
                    'subtotal' => $subtotal,
                ];
            } else {
                throw new \Exception("Tipo de item invalido: {$tipo}");
            }
        }

        return $itemsValidados;
    }

    protected function calcularSubtotal(array $items): float
    {
        return array_sum(array_column($items, 'subtotal'));
    }

    protected function calcularIGV(float $base): float
    {
        return round($base * 0.18, 2);
    }

    protected function generarNumeroVenta(): string
    {
        $fecha = now()->format('Ymd');
        $ultimaVenta = Venta::whereDate('created_at', today())
            ->orderBy('id', 'desc')
            ->first();

        $numero = $ultimaVenta ? ((int) substr($ultimaVenta->numero_venta, -4)) + 1 : 1;

        return 'V-'.$fecha.'-'.str_pad($numero, 4, '0', STR_PAD_LEFT);
    }

    protected function generarComprobante(string $tipo): array
    {
        $config = ComprobanteConfig::where('tipo', $tipo)
            ->where('estado', 'activo')
            ->first();

        if (! $config) {
            $serie = match ($tipo) {
                'factura' => 'F001',
                'boleta' => 'B001',
                'ticket' => 'T001',
                default => 'T001',
            };

            return [
                'serie' => $serie,
                'numero' => '000001',
            ];
        }

        $numero = $config->obtenerSiguienteNumero();
        $config->incrementarNumero();

        return [
            'serie' => $config->serie,
            'numero' => str_pad($numero, 6, '0', STR_PAD_LEFT),
        ];
    }

    protected function registrarPagoEnCaja(Venta $venta, Caja $caja, ?float $monto = null): void
    {
        $monto = $monto ?? $venta->total;
        if ($monto <= 0) {
            return;
        }

        $venta->loadMissing('items');
        $subtotalItems = (float) $venta->items->sum('subtotal');
        $subtotalAlquiler = (float) $venta->items->where('tipo_item', 'alquiler')->sum('subtotal');
        $montoAlquiler = $subtotalItems > 0
            ? round($monto * ($subtotalAlquiler / $subtotalItems), 2)
            : 0.0;
        $monto = round($monto - $montoAlquiler, 2);

        if ($monto <= 0) {
            return;
        }

        $concepto = "Venta POS - {$venta->numero_venta}";
        if ($venta->es_credito && $monto < $venta->total) {
            $concepto .= ' (anticipo a credito)';
        }

        $observaciones = "Metodo de pago: {$venta->metodo_pago}, Comprobante: ".strtoupper($venta->tipo_comprobante)." {$venta->serie_comprobante}-{$venta->numero_comprobante}";

        $this->cajaService->registrarMovimientoClasificado(
            cajaId: $caja->id,
            tipo: 'entrada',
            categoria: CajaMovimiento::CATEGORIA_POS,
            origenModulo: CajaMovimiento::ORIGEN_VENTAS,
            monto: $monto,
            concepto: $concepto,
            referenciaTipo: Venta::class,
            referenciaId: $venta->id,
            observaciones: $observaciones
        );
    }

    protected function resolvePaymentMethod(int $paymentMethodId, int $sucursalId): PaymentMethod
    {
        $paymentMethod = PaymentMethod::find($paymentMethodId);
        if (! $paymentMethod) {
            throw new \Exception('El metodo de pago seleccionado no existe en la sucursal activa.');
        }

        $this->assertSucursalMatch(
            (int) $paymentMethod->sucursal_id,
            $sucursalId,
            'El metodo de pago seleccionado no pertenece a la sucursal activa.'
        );

        return $paymentMethod;
    }

    protected function assertCompradorSucursal(string $tipoComprador, ?int $clienteId, ?int $employeeId, int $sucursalId): void
    {
        if ($tipoComprador === 'cliente' && $clienteId) {
            $cliente = Cliente::find($clienteId);
            if (! $cliente) {
                throw new \Exception('El cliente seleccionado no existe en la sucursal activa.');
            }

            $this->assertSucursalMatch(
                (int) $cliente->sucursal_id,
                $sucursalId,
                'El cliente seleccionado no pertenece a la sucursal activa.'
            );
        }

        if ($tipoComprador === 'empleado' && $employeeId) {
            $employee = Employee::find($employeeId);
            if (! $employee) {
                throw new \Exception('El empleado seleccionado no existe en la sucursal activa.');
            }

            $this->assertSucursalMatch(
                (int) $employee->sucursal_id,
                $sucursalId,
                'El empleado seleccionado no pertenece a la sucursal activa.'
            );
        }
    }

    protected function assertSucursalMatch(?int $resourceSucursalId, int $expectedSucursalId, string $message): void
    {
        if ($resourceSucursalId !== $expectedSucursalId) {
            throw new \Exception($message);
        }
    }

    private function valueOrDefault(mixed $value, string $default): string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : $default;
    }
}
