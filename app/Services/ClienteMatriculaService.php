<?php

namespace App\Services;

use App\Models\Core\ClienteMatricula;
use App\Models\Core\CajaMovimiento;
use App\Models\Core\ClientePlanTraspaso;
use App\Models\Core\Membresia;
use App\Models\Core\Clase;
use App\Models\Core\Pago;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
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
            ->with(['membresia', 'clase', 'asesor', 'pagos.registradoPor', 'installmentPlan.installments'])
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
        return ClienteMatricula::with(['cliente', 'membresia', 'clase', 'asesor', 'pagos', 'installmentPlan.installments'])->find($id);
    }

    /**
     * Crear una nueva matrícula para un cliente
     */
    public function create(array $data): ClienteMatricula
    {
        $validated = $this->validate($data);

        return DB::transaction(function () use ($validated) {
            $membresia = null;
            $installmentConfig = null;

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

            $validated['modalidad_pago'] = $validated['tipo'] === 'membresia'
                ? ($validated['modalidad_pago'] ?? 'contado')
                : 'contado';
            $validated['requiere_plan_cuotas'] = false;

            if ($validated['tipo'] === 'membresia' && $validated['modalidad_pago'] === 'cuotas') {
                $installmentConfig = $this->resolverConfiguracionCuotas($membresia, $validated);
                $validated['requiere_plan_cuotas'] = true;
                $validated['cuota_inicial_monto'] = $installmentConfig['cuota_inicial_monto'];
            } else {
                $validated['cuota_inicial_monto'] = 0;
            }

            $clienteMatricula = ClienteMatricula::create(
                Arr::except($validated, ['numero_cuotas', 'frecuencia_cuotas', 'fecha_inicio_plan_cuotas'])
            );

            // El cliente solo puede tener una membresía activa: las demás pasan a congelada
            if ($validated['tipo'] === 'membresia' && ($validated['estado'] ?? '') === 'activa') {
                ClienteMatricula::where('cliente_id', $clienteMatricula->cliente_id)
                    ->where('tipo', 'membresia')
                    ->where('estado', 'activa')
                    ->where('id', '!=', $clienteMatricula->id)
                    ->update(['estado' => 'congelada']);
            }

            if ($clienteMatricula->usaPlanCuotas() && $installmentConfig) {
                $this->registrarPagoInicialCuotas(
                    $clienteMatricula,
                    $installmentConfig['cuota_inicial_monto'],
                    $installmentConfig['saldo_financiado']
                );

                app(EnrollmentInstallmentService::class)->createPlan($clienteMatricula, [
                    'monto_total' => $installmentConfig['saldo_financiado'],
                    'numero_cuotas' => $installmentConfig['numero_cuotas'],
                    'frecuencia' => $installmentConfig['frecuencia'],
                    'fecha_inicio' => $validated['fecha_inicio_plan_cuotas'] ?? $validated['fecha_matricula'],
                    'observaciones' => 'Plan generado automáticamente al registrar la membresía.',
                ]);
            } else {
                // Crear deuda inicial automáticamente
                $this->crearDeudaInicial($clienteMatricula);
            }

            return $clienteMatricula->fresh(['pagos', 'installmentPlan.installments']);
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

    protected function registrarPagoInicialCuotas(
        ClienteMatricula $clienteMatricula,
        float $montoPagoInicial,
        float $saldoFinanciado
    ): ?Pago {
        if ($montoPagoInicial <= 0) {
            return null;
        }

        return Pago::create([
            'cliente_id' => $clienteMatricula->cliente_id,
            'cliente_matricula_id' => $clienteMatricula->id,
            'monto' => $montoPagoInicial,
            'moneda' => 'PEN',
            'metodo_pago' => 'cuota_inicial',
            'fecha_pago' => $clienteMatricula->fecha_matricula ?? $clienteMatricula->fecha_inicio,
            'es_pago_parcial' => $saldoFinanciado > 0,
            'saldo_pendiente' => $saldoFinanciado,
            'comprobante_tipo' => null,
            'comprobante_numero' => null,
            'registrado_por' => Auth::id(),
        ]);
    }

    protected function resolverConfiguracionCuotas(?Membresia $membresia, array $validated): array
    {
        if (! $membresia) {
            throw new \InvalidArgumentException('No se pudo resolver la membresía para configurar las cuotas.');
        }

        $numeroCuotas = (int) ($validated['numero_cuotas'] ?? $membresia->numero_cuotas_default);
        $frecuencia = $validated['frecuencia_cuotas'] ?? $membresia->frecuencia_cuotas_default ?? 'mensual';

        if (array_key_exists('cuota_inicial_monto', $validated) && $validated['cuota_inicial_monto'] !== null) {
            $cuotaInicialMonto = round((float) $validated['cuota_inicial_monto'], 2);
        } elseif ($membresia->cuota_inicial_monto !== null) {
            $cuotaInicialMonto = round((float) $membresia->cuota_inicial_monto, 2);
        } elseif ($membresia->cuota_inicial_porcentaje !== null) {
            $cuotaInicialMonto = round(((float) $validated['precio_final'] * (float) $membresia->cuota_inicial_porcentaje) / 100, 2);
        } else {
            $cuotaInicialMonto = 0.0;
        }

        $saldoFinanciado = round((float) $validated['precio_final'] - $cuotaInicialMonto, 2);

        if ($saldoFinanciado <= 0) {
            throw new \InvalidArgumentException('El saldo financiado debe ser mayor a cero para generar cuotas.');
        }

        return [
            'numero_cuotas' => $numeroCuotas,
            'frecuencia' => $frecuencia,
            'cuota_inicial_monto' => $cuotaInicialMonto,
            'saldo_financiado' => $saldoFinanciado,
        ];
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

        if ($clienteMatricula->usaPlanCuotas()) {
            throw new \Exception('Esta matrícula se cobra por cronograma de cuotas. Registre el pago desde el módulo de cuotas.');
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

            $cajaService = app(CajaService::class);
            $concepto = 'Cobro de ' . strtolower($clienteMatricula->tipo) . ' - ' . $clienteMatricula->nombre;
            $observaciones = 'Metodo de pago: ' . $metodoPago;
            if (! empty($data['comprobante_tipo']) || ! empty($data['comprobante_numero'])) {
                $observaciones .= ', Comprobante: ' . strtoupper((string) ($data['comprobante_tipo'] ?? '')) . ' ' . ($data['comprobante_numero'] ?? '');
            }
            $cajaService->registrarIngresoPorPago(
                $pago,
                $concepto,
                $clienteMatricula->esClase() ? CajaMovimiento::CATEGORIA_CLASE : CajaMovimiento::CATEGORIA_MEMBRESIA,
                CajaMovimiento::ORIGEN_CLIENTE_MATRICULAS,
                ClienteMatricula::class,
                $clienteMatricula->id,
                trim($observaciones, ', ')
            );

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

        if ($clienteMatricula->usaPlanCuotas()) {
            if ($clienteMatricula->installmentPlan) {
                return round((float) $clienteMatricula->installmentPlan->installments()
                    ->whereIn('estado', ['pendiente', 'vencida'])
                    ->sum('monto'), 2);
            }

            return round($clienteMatricula->monto_financiado, 2);
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

        if (
            $clienteMatricula->usaPlanCuotas()
            && (
                (array_key_exists('precio_lista', $data) && (float) $data['precio_lista'] !== (float) $clienteMatricula->precio_lista)
                || (array_key_exists('descuento_monto', $data) && (float) ($data['descuento_monto'] ?? 0) !== (float) $clienteMatricula->descuento_monto)
                || (array_key_exists('precio_final', $data) && (float) $data['precio_final'] !== (float) $clienteMatricula->precio_final)
                || (array_key_exists('modalidad_pago', $data) && ($data['modalidad_pago'] ?? 'contado') !== $clienteMatricula->modalidad_pago)
                || (array_key_exists('cuota_inicial_monto', $data) && (float) ($data['cuota_inicial_monto'] ?? 0) !== (float) ($clienteMatricula->cuota_inicial_monto ?? 0))
            )
        ) {
            throw new \Exception('No se puede modificar el precio o modalidad de una matrícula que ya tiene plan de cuotas.');
        }

        $validated = $this->validate($data, $id);

        return DB::transaction(function () use ($clienteMatricula, $validated) {
            $planAnteriorTipo = $clienteMatricula->tipo === 'membresia' ? 'membresia' : 'clase';
            $planAnteriorId = $clienteMatricula->tipo === 'membresia'
                ? $clienteMatricula->membresia_id
                : $clienteMatricula->clase_id;
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

            if (isset($validated['fecha_matricula'])) {
                $validated['fecha_matricula'] = Carbon::parse($validated['fecha_matricula'])->toDateString();
            }

            $clienteMatricula->update(
                Arr::except($validated, ['numero_cuotas', 'frecuencia_cuotas', 'fecha_inicio_plan_cuotas'])
            );

            // El cliente solo puede tener una membresía activa: las demás pasan a congelada (al crear o activar otra)
            if ($clienteMatricula->tipo === 'membresia' && $nuevoEstado === 'activa') {
                ClienteMatricula::where('cliente_id', $clienteMatricula->cliente_id)
                    ->where('tipo', 'membresia')
                    ->where('estado', 'activa')
                    ->where('id', '!=', $clienteMatricula->id)
                    ->update(['estado' => 'congelada']);
            }

            $planNuevoTipo = $clienteMatricula->tipo === 'membresia' ? 'membresia' : 'clase';
            $planNuevoId = $clienteMatricula->tipo === 'membresia'
                ? $clienteMatricula->membresia_id
                : $clienteMatricula->clase_id;

            if ($planAnteriorTipo !== $planNuevoTipo || (int) $planAnteriorId !== (int) $planNuevoId) {
                ClientePlanTraspaso::create([
                    'cliente_id' => $clienteMatricula->cliente_id,
                    'origen_tipo' => ClienteMatricula::class,
                    'origen_id' => $clienteMatricula->id,
                    'plan_anterior_tipo' => $planAnteriorTipo,
                    'plan_anterior_id' => $planAnteriorId,
                    'plan_nuevo_tipo' => $planNuevoTipo,
                    'plan_nuevo_id' => $planNuevoId,
                    'motivo' => $validated['motivo_cancelacion'] ?? null,
                    'registrado_por' => Auth::id(),
                ]);
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
                $isUpdate ? 'sometimes' : 'nullable',
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
            'modalidad_pago' => ['nullable', 'string', 'in:contado,cuotas'],
            'cuota_inicial_monto' => ['nullable', 'numeric', 'min:0', 'regex:/^\d+(\.\d{1,2})?$/'],
            'numero_cuotas' => ['nullable', 'integer', 'min:2', 'max:60'],
            'frecuencia_cuotas' => ['nullable', 'string', 'in:semanal,quincenal,mensual'],
            'fecha_inicio_plan_cuotas' => ['nullable', 'date'],
        ];

        if ($tipo === 'membresia') {
            $rules['membresia_id'] = [$isUpdate ? 'sometimes' : 'required', 'exists:membresias,id'];
            $rules['clase_id'] = ['nullable'];
        } else {
            $rules['clase_id'] = [$isUpdate ? 'sometimes' : 'required', 'exists:clases,id'];
            $rules['membresia_id'] = ['nullable'];
        }

        $validator = Validator::make($data, $rules);
        $validator->after(function ($validator) use ($data, $tipo) {
            if ($tipo !== 'membresia') {
                return;
            }

            $modalidadPago = $data['modalidad_pago'] ?? 'contado';

            if ($modalidadPago !== 'cuotas') {
                return;
            }

            $membresiaId = $data['membresia_id'] ?? null;
            $membresia = $membresiaId ? Membresia::find($membresiaId) : null;
            $precioFinal = (float) ($data['precio_final'] ?? (($data['precio_lista'] ?? 0) - ($data['descuento_monto'] ?? 0)));
            $cuotaInicialMonto = array_key_exists('cuota_inicial_monto', $data) && $data['cuota_inicial_monto'] !== null
                ? (float) $data['cuota_inicial_monto']
                : null;

            if (! $membresia || ! $membresia->permite_cuotas) {
                $validator->errors()->add('modalidad_pago', 'La membresía seleccionada no permite pago en cuotas.');
                return;
            }

            if ($precioFinal <= 0) {
                $validator->errors()->add('precio_final', 'El precio final debe ser mayor a cero para generar cuotas.');
            }

            if (($data['numero_cuotas'] ?? null) === null && $membresia->numero_cuotas_default === null) {
                $validator->errors()->add('numero_cuotas', 'Debes indicar el número de cuotas o configurarlo en la membresía.');
            }

            if (($data['frecuencia_cuotas'] ?? null) === null && $membresia->frecuencia_cuotas_default === null) {
                $validator->errors()->add('frecuencia_cuotas', 'Debes indicar la frecuencia de cuotas o configurarla en la membresía.');
            }

            if ($cuotaInicialMonto !== null && $cuotaInicialMonto >= $precioFinal && $precioFinal > 0) {
                $validator->errors()->add('cuota_inicial_monto', 'La cuota inicial debe ser menor al precio final.');
            }
        });

        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }

        $validated = $validator->validated();

        if (($validated['modalidad_pago'] ?? 'contado') !== 'cuotas') {
            $validated['cuota_inicial_monto'] = 0;
        }

        return $validated;
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
