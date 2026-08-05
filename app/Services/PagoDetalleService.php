<?php

namespace App\Services;

use App\Models\Core\Caja;
use App\Models\Core\Pago;
use App\Models\Core\PagoDetalle;
use App\Models\Core\PaymentMethod;
use Illuminate\Support\Collection;

class PagoDetalleService
{
    /**
     * @return list<array{payment_method_id: int|null, monto: float, metodo_pago: string, numero_operacion: string|null, entidad_financiera: string|null}>
     */
    public function normalizar(array $data, float $montoTotal, int $sucursalId): array
    {
        $usaDistribucion = array_key_exists('pagos', $data);
        $lineas = $usaDistribucion ? $data['pagos'] : [[
            'payment_method_id' => $data['payment_method_id'] ?? null,
            'monto' => $montoTotal,
            'metodo_pago' => $data['metodo_pago'] ?? 'efectivo',
            'numero_operacion' => $data['numero_operacion'] ?? null,
            'entidad_financiera' => $data['entidad_financiera'] ?? null,
        ]];

        if (! is_array($lineas) || $lineas === [] || count($lineas) > 2) {
            throw new \InvalidArgumentException('El pago debe tener una o dos formas de pago.');
        }

        $normalizadas = [];
        $metodosUsados = [];

        foreach (array_values($lineas) as $linea) {
            if (! is_array($linea)) {
                throw new \InvalidArgumentException('La distribución del pago no es válida.');
            }

            $paymentMethodId = filled($linea['payment_method_id'] ?? null)
                ? (int) $linea['payment_method_id']
                : null;
            $monto = round((float) ($linea['monto'] ?? 0), 2);
            $numeroOperacion = trim((string) ($linea['numero_operacion'] ?? '')) ?: null;
            $entidadFinanciera = trim((string) ($linea['entidad_financiera'] ?? '')) ?: null;

            if ($monto <= 0) {
                throw new \InvalidArgumentException('Cada forma de pago debe tener un monto mayor a cero.');
            }

            $paymentMethod = $paymentMethodId ? PaymentMethod::find($paymentMethodId) : null;
            if ($usaDistribucion && ! $paymentMethod) {
                throw new \InvalidArgumentException('Selecciona un método de pago válido en cada línea.');
            }

            if ($paymentMethod) {
                if ((int) $paymentMethod->sucursal_id !== $sucursalId || $paymentMethod->estado !== 'activo') {
                    throw new \InvalidArgumentException('El método de pago no está activo en la sucursal actual.');
                }
                if (isset($metodosUsados[$paymentMethod->id])) {
                    throw new \InvalidArgumentException('No se puede repetir el mismo método de pago.');
                }
                if ($paymentMethod->requiere_numero_operacion && ! $numeroOperacion) {
                    throw new \InvalidArgumentException("El método {$paymentMethod->nombre} requiere número de operación.");
                }
                if ($paymentMethod->requiere_entidad && ! $entidadFinanciera) {
                    throw new \InvalidArgumentException("El método {$paymentMethod->nombre} requiere entidad financiera.");
                }
                $metodosUsados[$paymentMethod->id] = true;
            }

            $normalizadas[] = [
                'payment_method_id' => $paymentMethod?->id,
                'monto' => $monto,
                'metodo_pago' => $paymentMethod?->nombre
                    ?? trim((string) ($linea['metodo_pago'] ?? $data['metodo_pago'] ?? 'efectivo')),
                'numero_operacion' => $numeroOperacion,
                'entidad_financiera' => $entidadFinanciera,
            ];
        }

        $suma = round((float) collect($normalizadas)->sum('monto'), 2);
        if (abs($suma - round($montoTotal, 2)) > 0.009) {
            throw new \InvalidArgumentException('La suma de las formas de pago debe ser igual al monto a pagar.');
        }

        return $normalizadas;
    }

    /**
     * @param  list<array{payment_method_id: int|null, monto: float, metodo_pago: string, numero_operacion: string|null, entidad_financiera: string|null}>  $lineas
     * @return array{metodo_pago: string, payment_method_id: int|null, numero_operacion: string|null, entidad_financiera: string|null}
     */
    public function datosCabecera(array $lineas): array
    {
        if (count($lineas) > 1) {
            return [
                'metodo_pago' => 'Mixto',
                'payment_method_id' => null,
                'numero_operacion' => null,
                'entidad_financiera' => null,
            ];
        }

        $linea = $lineas[0];

        return [
            'metodo_pago' => $linea['metodo_pago'],
            'payment_method_id' => $linea['payment_method_id'],
            'numero_operacion' => $linea['numero_operacion'],
            'entidad_financiera' => $linea['entidad_financiera'],
        ];
    }

    /**
     * @param  list<array{payment_method_id: int|null, monto: float, metodo_pago: string, numero_operacion: string|null, entidad_financiera: string|null}>  $lineas
     * @return Collection<int, PagoDetalle>
     */
    public function crearDetalles(Pago $pago, Caja $caja, array $lineas): Collection
    {
        return collect($lineas)->map(fn (array $linea) => PagoDetalle::create([
            ...$linea,
            'pago_id' => $pago->id,
            'caja_id' => $caja->id,
            'sucursal_id' => $pago->sucursal_id,
        ]));
    }
}
