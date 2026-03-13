<?php

namespace App\Services;

use App\Models\Core\ClienteMembresia;
use App\Models\Core\Membresia;
use App\Models\Core\Pago;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ClienteMembresiaService
{
    /**
     * Obtener todas las membresías de un cliente con paginación
     */
    public function getByCliente(int $clienteId, ?string $estado = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = ClienteMembresia::query()
            ->with(['membresia', 'asesor', 'pagos.registradoPor'])
            ->where('cliente_id', $clienteId)
            ->orderBy('fecha_inicio', 'desc');

        if ($estado) {
            $query->where('estado', $estado);
        }

        return $query->paginate($perPage);
    }

    /**
     * Obtener una membresía de cliente por ID
     */
    public function find(int $id): ?ClienteMembresia
    {
        return ClienteMembresia::with(['cliente', 'membresia', 'asesor'])->find($id);
    }

    /**
     * Crear una nueva membresía para un cliente
     */
    public function create(array $data): ClienteMembresia
    {
        $validated = $this->validate($data);

        return DB::transaction(function () use ($validated) {
            $validated['fecha_matricula'] = isset($validated['fecha_matricula'])
                ? \Carbon\Carbon::parse($validated['fecha_matricula'])->toDateString()
                : now()->toDateString();

            // Calcular precio_final si no está presente
            if (!isset($validated['precio_final'])) {
                $precioLista = $validated['precio_lista'] ?? 0;
                $descuento = $validated['descuento_monto'] ?? 0;
                $validated['precio_final'] = $precioLista - $descuento;
            }

            $clienteMembresia = ClienteMembresia::create($validated);

            // Crear deuda inicial automáticamente
            $this->crearDeudaInicial($clienteMembresia);

            return $clienteMembresia->fresh(['pagos']);
        });
    }

    /**
     * Crear deuda inicial para una membresía
     */
    protected function crearDeudaInicial(ClienteMembresia $clienteMembresia): Pago
    {
        return Pago::create([
            'cliente_id' => $clienteMembresia->cliente_id,
            'cliente_membresia_id' => $clienteMembresia->id,
            'monto' => $clienteMembresia->precio_final,
            'moneda' => 'PEN',
            'metodo_pago' => 'efectivo',
            'fecha_pago' => $clienteMembresia->fecha_inicio,
            'es_pago_parcial' => true,
            'saldo_pendiente' => $clienteMembresia->precio_final,
            'comprobante_tipo' => null,
            'comprobante_numero' => null,
            'registrado_por' => Auth::user()->id,
        ]);
    }

    /**
     * Procesar un pago para una membresía
     */
    public function procesarPago(int $clienteMembresiaId, array $data): Pago
    {
        $clienteMembresia = $this->find($clienteMembresiaId);

        if (!$clienteMembresia) {
            throw new \Exception('Membresía de cliente no encontrada');
        }

        // Validar que exista una caja abierta
        $cajaService = app(CajaService::class);
        if (!$cajaService->validarCajaAbierta(Auth::user()->id)) {
            throw new \Exception('No hay una caja abierta. Por favor, abra una caja antes de registrar pagos.');
        }

        // Obtener o crear caja abierta para el usuario actual
        $caja = $cajaService->obtenerOCrearCajaAbierta();

        $saldoPendiente = $this->obtenerSaldoPendiente($clienteMembresiaId);
        $montoPago = (float) ($data['monto_pago'] ?? 0);

        // Validaciones
        if ($montoPago <= 0) {
            throw new \Exception('El monto del pago debe ser mayor a cero.');
        }

        if ($montoPago > $saldoPendiente) {
            throw new \Exception('El monto del pago no puede ser mayor al saldo pendiente.');
        }

        return DB::transaction(function () use ($clienteMembresia, $montoPago, $data, $saldoPendiente, $caja) {
            $nuevoSaldoPendiente = $saldoPendiente - $montoPago;
            $esPagoParcial = $nuevoSaldoPendiente > 0;

            $metodoPago = $data['metodo_pago'] ?? 'efectivo';
            $paymentMethodId = $data['payment_method_id'] ?? null;
            if ($paymentMethodId) {
                $pm = \App\Models\Core\PaymentMethod::find($paymentMethodId);
                if ($pm) {
                    $metodoPago = $pm->nombre;
                }
            }

            // Crear nuevo registro de pago asociado a la caja
            $pago = Pago::create([
                'cliente_id' => $clienteMembresia->cliente_id,
                'cliente_membresia_id' => $clienteMembresia->id,
                'monto' => $montoPago,
                'moneda' => $data['moneda'] ?? 'PEN',
                'metodo_pago' => $metodoPago,
                'payment_method_id' => $paymentMethodId,
                'numero_operacion' => $data['numero_operacion'] ?? null,
                'entidad_financiera' => $data['entidad_financiera'] ?? null,
                'fecha_pago' => $data['fecha_pago'] ?? now(),
                'es_pago_parcial' => $esPagoParcial,
                'saldo_pendiente' => $nuevoSaldoPendiente,
                'comprobante_tipo' => $data['comprobante_tipo'] ?? null,
                'comprobante_numero' => $data['comprobante_numero'] ?? null,
                'registrado_por' => Auth::user()->id,
                'caja_id' => $caja->id,
            ]);

            $cajaService = app(CajaService::class);
            $concepto = 'Cobro de membresia legacy - ' . ($clienteMembresia->membresia->nombre ?? 'N/A');
            $observaciones = 'Metodo de pago: ' . $metodoPago;
            if (! empty($data['comprobante_tipo']) || ! empty($data['comprobante_numero'])) {
                $observaciones .= ', Comprobante: ' . strtoupper((string) ($data['comprobante_tipo'] ?? '')) . ' ' . ($data['comprobante_numero'] ?? '');
            }
            $cajaService->registrarIngresoPorPago(
                $pago,
                $concepto,
                ClienteMembresia::class,
                $clienteMembresia->id,
                trim($observaciones, ', ')
            );

            return $pago;
        });
    }

    /**
     * Obtener el saldo pendiente de una membresía
     */
    public function obtenerSaldoPendiente(int $clienteMembresiaId): float
    {
        $clienteMembresia = $this->find($clienteMembresiaId);

        if (!$clienteMembresia) {
            return 0;
        }

        // Obtener el último pago para ver el saldo pendiente actual
        $ultimoPago = Pago::where('cliente_membresia_id', $clienteMembresiaId)
            ->orderBy('created_at', 'desc')
            ->first();

        if ($ultimoPago) {
            return (float) $ultimoPago->saldo_pendiente;
        }

        // Si no hay pagos, el saldo pendiente es el precio final
        return (float) $clienteMembresia->precio_final;
    }

    /**
     * Actualizar una membresía de cliente
     */
    public function update(int $id, array $data): ClienteMembresia
    {
        $clienteMembresia = $this->find($id);

        if (!$clienteMembresia) {
            throw new \Exception('Membresía de cliente no encontrada');
        }

        $validated = $this->validate($data, $id);

        return DB::transaction(function () use ($clienteMembresia, $validated) {
            // Recalcular precio_final si se actualizan precio_lista o descuento_monto
            if (isset($validated['precio_lista']) || isset($validated['descuento_monto'])) {
                $precioLista = $validated['precio_lista'] ?? $clienteMembresia->precio_lista;
                $descuento = $validated['descuento_monto'] ?? $clienteMembresia->descuento_monto;
                $validated['precio_final'] = $precioLista - $descuento;
            }

            $clienteMembresia->update($validated);
            return $clienteMembresia->fresh();
        });
    }

    /**
     * Eliminar una membresía de cliente
     */
    public function delete(int $id): bool
    {
        $clienteMembresia = $this->find($id);

        if (!$clienteMembresia) {
            throw new \Exception('Membresía de cliente no encontrada');
        }

        // Verificar si tiene relaciones
        $this->checkRelations($clienteMembresia);

        return DB::transaction(function () use ($clienteMembresia) {
            return $clienteMembresia->delete();
        });
    }

    /**
     * Validar datos de la membresía de cliente
     */
    protected function validate(array $data, ?int $id = null): array
    {
        $isUpdate = $id !== null;
        
        $rules = [
            'cliente_id' => [$isUpdate ? 'sometimes' : 'required', 'exists:clientes,id'],
            'membresia_id' => [$isUpdate ? 'sometimes' : 'required', 'exists:membresias,id'],
            'fecha_matricula' => ['nullable', 'date'],
            'fecha_inicio' => [$isUpdate ? 'sometimes' : 'required', 'date'],
            'fecha_fin' => [
                $isUpdate ? 'sometimes' : 'required',
                'date',
                'after:fecha_inicio',
            ],
            'estado' => [$isUpdate ? 'sometimes' : 'required', 'string', 'in:activa,vencida,cancelada,congelada'],
            'precio_lista' => [$isUpdate ? 'sometimes' : 'required', 'numeric', 'min:0', 'regex:/^\d+(\.\d{1,2})?$/'],
            'descuento_monto' => ['nullable', 'numeric', 'min:0', 'regex:/^\d+(\.\d{1,2})?$/'],
            'precio_final' => ['nullable', 'numeric', 'min:0', 'regex:/^\d+(\.\d{1,2})?$/'],
            'asesor_id' => ['nullable', 'exists:users,id'],
            'canal_venta' => ['nullable', 'string', 'max:100'],
            'fechas_congelacion' => ['nullable', 'array'],
            'motivo_cancelacion' => ['nullable', 'string'],
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * Verificar relaciones antes de eliminar
     */
    protected function checkRelations(ClienteMembresia $clienteMembresia): void
    {
        $hasPagos = $clienteMembresia->pagos()->exists();
        $hasAsistencias = $clienteMembresia->asistencias()->exists();

        if ($hasPagos || $hasAsistencias) {
            throw new \Exception('No se puede eliminar la membresía porque tiene pagos o asistencias asociadas.');
        }
    }

    /**
     * Obtener todas las membresías activas
     */
    public function getMembresiasActivas(): Collection
    {
        return Membresia::where('estado', 'activa')->get();
    }
}
