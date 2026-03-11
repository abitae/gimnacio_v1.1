<?php

namespace App\Services;

use App\Models\Core\ClienteMatricula;
use App\Models\Core\Membresia;
use App\Models\Core\Clase;
use App\Models\Core\Pago;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class ClienteMatriculaService
{
    /**
     * Obtener todas las matrículas de un cliente con paginación
     */
    public function getByCliente(int $clienteId, array $filtros = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = ClienteMatricula::query()
            ->with(['membresia', 'clase', 'asesor', 'pagos.registradoPor'])
            ->where('cliente_id', $clienteId)
            ->orderBy('fecha_inicio', 'desc');

        if (isset($filtros['estado'])) {
            $query->where('estado', $filtros['estado']);
        }

        if (isset($filtros['tipo'])) {
            $query->where('tipo', $filtros['tipo']);
        }

        return $query->paginate($perPage);
    }

    /**
     * Matrículas de tipo membresía (activas) cuya fecha_fin está próxima.
     * Útil para mostrar avisos de renovación.
     *
     * @param int $dias Ventana en días (por defecto 30: vencen desde hoy hasta hoy + 30)
     * @param int $limit Cantidad máxima a devolver
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getMembresiasProximasAVencer(int $dias = 30, int $limit = 20): \Illuminate\Database\Eloquent\Collection
    {
        $hoy = Carbon::today();
        $limiteFecha = $hoy->copy()->addDays($dias);

        return ClienteMatricula::query()
            ->with(['cliente.registroPor', 'membresia', 'asesor'])
            ->where('tipo', 'membresia')
            ->where('estado', 'activa')
            ->whereNotNull('fecha_fin')
            ->where('fecha_fin', '>=', $hoy)
            ->where('fecha_fin', '<=', $limiteFecha)
            ->orderBy('fecha_fin', 'asc')
            ->limit($limit)
            ->get();
    }

    /**
     * Obtener una matrícula por ID
     */
    public function find(int $id): ?ClienteMatricula
    {
        return ClienteMatricula::with(['cliente', 'membresia', 'clase', 'asesor'])->find($id);
    }

    /**
     * Crear una nueva matrícula para un cliente
     */
    public function create(array $data): ClienteMatricula
    {
        $validated = $this->validate($data);

        return DB::transaction(function () use ($validated) {
            // Para membresía: fecha inicio por defecto hoy y fecha fin según duración de la membresía
            if ($validated['tipo'] === 'membresia') {
                $validated['fecha_inicio'] = isset($validated['fecha_inicio'])
                    ? Carbon::parse($validated['fecha_inicio'])->toDateString()
                    : Carbon::today()->toDateString();
                $membresia = Membresia::find($validated['membresia_id']);
                $dias = $membresia && $membresia->duracion_dias ? (int) $membresia->duracion_dias : 30;
                $validated['fecha_fin'] = Carbon::parse($validated['fecha_inicio'])->addDays($dias)->toDateString();
            }

            $validated['fecha_matricula'] = isset($validated['fecha_matricula'])
                ? Carbon::parse($validated['fecha_matricula'])->toDateString()
                : Carbon::today()->toDateString();

            // Calcular precio_final si no está presente
            if (!isset($validated['precio_final'])) {
                $precioLista = $validated['precio_lista'] ?? 0;
                $descuento = $validated['descuento_monto'] ?? 0;
                $validated['precio_final'] = $precioLista - $descuento;
            }

            $clienteMatricula = ClienteMatricula::create($validated);

            // El cliente solo puede tener una membresía activa: las demás pasan a congelada
            if ($validated['tipo'] === 'membresia' && ($validated['estado'] ?? '') === 'activa') {
                ClienteMatricula::where('cliente_id', $clienteMatricula->cliente_id)
                    ->where('tipo', 'membresia')
                    ->where('estado', 'activa')
                    ->where('id', '!=', $clienteMatricula->id)
                    ->update(['estado' => 'congelada']);
            }

            // Crear deuda inicial automáticamente
            $this->crearDeudaInicial($clienteMatricula);

            return $clienteMatricula->fresh(['pagos']);
        });
    }

    /**
     * Crear deuda inicial para una matrícula
     */
    protected function crearDeudaInicial(ClienteMatricula $clienteMatricula): Pago
    {
        return Pago::create([
            'cliente_id' => $clienteMatricula->cliente_id,
            'cliente_matricula_id' => $clienteMatricula->id,
            'monto' => $clienteMatricula->precio_final,
            'moneda' => 'PEN',
            'metodo_pago' => 'efectivo',
            'fecha_pago' => $clienteMatricula->fecha_inicio,
            'es_pago_parcial' => true,
            'saldo_pendiente' => $clienteMatricula->precio_final,
            'comprobante_tipo' => null,
            'comprobante_numero' => null,
            'registrado_por' => Auth::user()->id,
        ]);
    }

    /**
     * Procesar un pago para una matrícula
     */
    public function procesarPago(int $clienteMatriculaId, array $data): Pago
    {
        $clienteMatricula = $this->find($clienteMatriculaId);

        if (!$clienteMatricula) {
            throw new \Exception('Matrícula no encontrada');
        }

        // Validar que exista una caja abierta
        $cajaService = app(CajaService::class);
        if (!$cajaService->validarCajaAbierta(Auth::user()->id)) {
            throw new \Exception('No hay una caja abierta. Por favor, abra una caja antes de registrar pagos.');
        }

        // Obtener o crear caja abierta para el usuario actual
        $caja = $cajaService->obtenerOCrearCajaAbierta();

        $saldoPendiente = $this->obtenerSaldoPendiente($clienteMatriculaId);
        $montoPago = (float) ($data['monto_pago'] ?? 0);

        // Validaciones
        if ($montoPago <= 0) {
            throw new \Exception('El monto del pago debe ser mayor a cero.');
        }

        if ($montoPago > $saldoPendiente) {
            throw new \Exception('El monto del pago no puede ser mayor al saldo pendiente.');
        }

        return DB::transaction(function () use ($clienteMatricula, $montoPago, $data, $saldoPendiente, $caja) {
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
                'cliente_id' => $clienteMatricula->cliente_id,
                'cliente_matricula_id' => $clienteMatricula->id,
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

            return $pago;
        });
    }

    /**
     * Obtener el saldo pendiente de una matrícula
     */
    public function obtenerSaldoPendiente(int $clienteMatriculaId): float
    {
        $clienteMatricula = $this->find($clienteMatriculaId);

        if (!$clienteMatricula) {
            return 0;
        }

        // Obtener el último pago para ver el saldo pendiente actual
        $ultimoPago = Pago::where('cliente_matricula_id', $clienteMatriculaId)
            ->orderBy('created_at', 'desc')
            ->first();

        if ($ultimoPago) {
            return (float) $ultimoPago->saldo_pendiente;
        }

        // Si no hay pagos, el saldo pendiente es el precio final
        return (float) $clienteMatricula->precio_final;
    }

    /**
     * Actualizar una matrícula de cliente
     */
    public function update(int $id, array $data): ClienteMatricula
    {
        $clienteMatricula = $this->find($id);

        if (!$clienteMatricula) {
            throw new \Exception('Matrícula no encontrada');
        }

        if ($clienteMatricula->estado === 'completada') {
            throw new \Exception('No se puede editar una matrícula completada.');
        }

        $validated = $this->validate($data, $id);

        return DB::transaction(function () use ($clienteMatricula, $validated) {
            $nuevoEstado = $validated['estado'] ?? $clienteMatricula->estado;

            // Si pasa de congelada a activa (membresía), el cliente solo puede tener una activa
            if ($clienteMatricula->tipo === 'membresia' && $clienteMatricula->estado === 'congelada' && $nuevoEstado === 'activa') {
                $otraActiva = ClienteMatricula::where('cliente_id', $clienteMatricula->cliente_id)
                    ->where('tipo', 'membresia')
                    ->where('estado', 'activa')
                    ->where('id', '!=', $clienteMatricula->id)
                    ->exists();
                if ($otraActiva) {
                    throw new \Exception('El cliente ya tiene una membresía activa.');
                }
                $membresia = Membresia::find($clienteMatricula->membresia_id);
                $dias = $membresia && $membresia->duracion_dias ? (int) $membresia->duracion_dias : 30;
                $validated['fecha_inicio'] = Carbon::today()->toDateString();
                $validated['fecha_fin'] = Carbon::today()->addDays($dias)->toDateString();
            }

            // Recalcular precio_final si se actualizan precio_lista o descuento_monto
            if (isset($validated['precio_lista']) || isset($validated['descuento_monto'])) {
                $precioLista = $validated['precio_lista'] ?? $clienteMatricula->precio_lista;
                $descuento = $validated['descuento_monto'] ?? $clienteMatricula->descuento_monto;
                $validated['precio_final'] = $precioLista - $descuento;
            }

            $clienteMatricula->update($validated);

            // El cliente solo puede tener una membresía activa: las demás pasan a congelada (al crear o activar otra)
            if ($clienteMatricula->tipo === 'membresia' && $nuevoEstado === 'activa') {
                ClienteMatricula::where('cliente_id', $clienteMatricula->cliente_id)
                    ->where('tipo', 'membresia')
                    ->where('estado', 'activa')
                    ->where('id', '!=', $clienteMatricula->id)
                    ->update(['estado' => 'congelada']);
            }

            return $clienteMatricula->fresh();
        });
    }

    /**
     * Eliminar una matrícula de cliente
     */
    public function delete(int $id): bool
    {
        $clienteMatricula = $this->find($id);

        if (!$clienteMatricula) {
            throw new \Exception('Matrícula no encontrada');
        }

        if ($clienteMatricula->estado === 'completada') {
            throw new \Exception('No se puede eliminar una matrícula completada.');
        }

        // Verificar si tiene relaciones
        $this->checkRelations($clienteMatricula);

        return DB::transaction(function () use ($clienteMatricula) {
            return $clienteMatricula->delete();
        });
    }

    /**
     * Validar datos de la matrícula
     */
    protected function validate(array $data, ?int $id = null): array
    {
        $isUpdate = $id !== null;
        $tipo = $data['tipo'] ?? 'membresia';
        
        $rules = [
            'cliente_id' => [$isUpdate ? 'sometimes' : 'required', 'exists:clientes,id'],
            'tipo' => ['required', 'string', 'in:membresia,clase'],
            'fecha_matricula' => ['nullable', 'date'],
            'fecha_inicio' => [$isUpdate ? 'sometimes' : 'required', 'date'],
            'fecha_fin' => [
                $isUpdate ? 'sometimes' : ($tipo === 'membresia' ? 'required' : 'nullable'),
                'nullable',
                $tipo === 'membresia' && isset($data['fecha_inicio']) ? 'after:fecha_inicio' : 'nullable',
            ],
            'estado' => [$isUpdate ? 'sometimes' : 'required', 'string', 'in:activa,vencida,cancelada,congelada,completada'],
            'precio_lista' => [$isUpdate ? 'sometimes' : 'required', 'numeric', 'min:0', 'regex:/^\d+(\.\d{1,2})?$/'],
            'descuento_monto' => ['nullable', 'numeric', 'min:0', 'regex:/^\d+(\.\d{1,2})?$/'],
            'precio_final' => ['nullable', 'numeric', 'min:0', 'regex:/^\d+(\.\d{1,2})?$/'],
            'asesor_id' => ['nullable', 'exists:users,id'],
            'canal_venta' => ['nullable', 'string', 'max:100'],
            'fechas_congelacion' => ['nullable', 'array'],
            'motivo_cancelacion' => ['nullable', 'string'],
            'sesiones_totales' => ['nullable', 'integer', 'min:1'],
            'sesiones_usadas' => ['nullable', 'integer', 'min:0'],
        ];

        if ($tipo === 'membresia') {
            $rules['membresia_id'] = [$isUpdate ? 'sometimes' : 'required', 'exists:membresias,id'];
            $rules['clase_id'] = ['nullable'];
        } else {
            $rules['clase_id'] = [$isUpdate ? 'sometimes' : 'required', 'exists:clases,id'];
            $rules['membresia_id'] = ['nullable'];
        }

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * Verificar relaciones antes de eliminar
     */
    protected function checkRelations(ClienteMatricula $clienteMatricula): void
    {
        $hasPagos = $clienteMatricula->pagos()->exists();
        $hasAsistencias = $clienteMatricula->asistencias()->exists();

        if ($hasPagos || $hasAsistencias) {
            throw new \Exception('No se puede eliminar la matrícula porque tiene pagos o asistencias asociadas.');
        }
    }

    /**
     * Obtener todas las membresías activas
     */
    public function getMembresiasActivas(): Collection
    {
        return Membresia::where('estado', 'activa')->get();
    }

    /**
     * Obtener todas las clases activas
     */
    public function getClasesActivas(): Collection
    {
        return Clase::where('estado', 'activo')->get();
    }
}
