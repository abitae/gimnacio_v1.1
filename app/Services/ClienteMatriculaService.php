<?php

namespace App\Services;

use App\Models\Core\CajaMovimiento;
use App\Models\Core\Clase;
use App\Models\Core\Cliente;
use App\Models\Core\ClienteMatricula;
use App\Models\Core\ClientePlanTraspaso;
use App\Models\Core\Membresia;
use App\Models\Core\Pago;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ClienteMatriculaService
{
    /**
     * Obtener todas las matrÃ­culas de un cliente con paginaciÃ³n
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
     * MatrÃ­culas de tipo membresÃ­a (activas) cuya fecha_fin estÃ¡ prÃ³xima.
     * Ãštil para mostrar avisos de renovaciÃ³n.
     *
     * @param  int  $dias  Ventana en dÃ­as (por defecto 30: vencen desde hoy hasta hoy + 30)
     * @param  int  $limit  Cantidad mÃ¡xima a devolver
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
     * Obtener una matrÃ­cula por ID
     */
    public function find(int $id): ?ClienteMatricula
    {
        return ClienteMatricula::with(['cliente', 'membresia', 'clase', 'asesor', 'pagos', 'installmentPlan.installments'])->find($id);
    }

    /**
     * Crear una nueva matrÃ­cula para un cliente
     */
    public function create(array $data): ClienteMatricula
    {
        $validated = $this->validate($data);

        return DB::transaction(function () use ($validated) {
            $membresia = null;
            $installmentConfig = null;

            // Para membresÃ­a: fecha inicio por defecto hoy y fecha fin segÃºn duraciÃ³n de la membresÃ­a
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

            // Calcular precio_final si no estÃ¡ presente
            if (! isset($validated['precio_final'])) {
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
                Arr::except($validated, [
                    'numero_cuotas',
                    'frecuencia_cuotas',
                    'fecha_inicio_plan_cuotas',
                    'monto_pago_inicial',
                    'installment_schedule',
                ])
            );

            // El cliente solo puede tener una membresÃ­a activa: las demÃ¡s pasan a congelada
            if ($validated['tipo'] === 'membresia' && ($validated['estado'] ?? '') === 'activa') {
                ClienteMatricula::where('cliente_id', $clienteMatricula->cliente_id)
                    ->where('tipo', 'membresia')
                    ->where('estado', 'activa')
                    ->where('id', '!=', $clienteMatricula->id)
                    ->update(['estado' => 'congelada']);
            }

            if ($clienteMatricula->usaPlanCuotas() && $installmentConfig) {
                $cliente = $clienteMatricula->cliente ?? Cliente::findOrFail($clienteMatricula->cliente_id);
                $payload = [
                    'monto_total' => (float) $clienteMatricula->precio_final,
                    'cuota_inicial_monto' => $installmentConfig['cuota_inicial_monto'],
                    'numero_cuotas' => $installmentConfig['numero_cuotas'],
                    'frecuencia' => $installmentConfig['frecuencia'],
                    'fecha_inicio' => $validated['fecha_inicio_plan_cuotas'] ?? $validated['fecha_matricula'],
                    'observaciones' => 'Plan generado automÃ¡ticamente al registrar la membresÃ­a (sin cobro en alta).',
                ];
                if (! empty($validated['installment_schedule']) && is_array($validated['installment_schedule'])) {
                    $payload['schedule'] = $validated['installment_schedule'];
                }
                app(EnrollmentInstallmentService::class)->addFinancing($cliente, $clienteMatricula, $payload);
            }

            return $clienteMatricula->fresh(['pagos', 'installmentPlan.installments']);
        });
    }

    protected function resolverConfiguracionCuotas(?Membresia $membresia, array $validated): array
    {
        $frecuencia = $validated['frecuencia_cuotas'] ?? 'mensual';

        $cuotaInicialMonto = array_key_exists('cuota_inicial_monto', $validated) && $validated['cuota_inicial_monto'] !== null
            ? round((float) $validated['cuota_inicial_monto'], 2)
            : 0.0;

        $saldoFinanciado = round((float) $validated['precio_final'] - $cuotaInicialMonto, 2);

        if ($saldoFinanciado <= 0) {
            throw new \InvalidArgumentException('El saldo financiado debe ser mayor a cero para generar cuotas.');
        }

        $numeroCuotas = (int) ($validated['numero_cuotas'] ?? 0);

        if ($numeroCuotas < 2) {
            throw new \InvalidArgumentException('Debes indicar un nÃºmero de cuotas vÃ¡lido (mÃ­nimo 2).');
        }

        return [
            'numero_cuotas' => $numeroCuotas,
            'frecuencia' => $frecuencia,
            'cuota_inicial_monto' => $cuotaInicialMonto,
            'saldo_financiado' => $saldoFinanciado,
        ];
    }

    /**
     * Procesar un pago para una matrÃ­cula
     */
    public function procesarPago(int $clienteMatriculaId, array $data): Pago
    {
        $clienteMatricula = $this->find($clienteMatriculaId);

        if (! $clienteMatricula) {
            throw new \Exception('MatrÃ­cula no encontrada');
        }

        if ($clienteMatricula->usaPlanCuotas()) {
            throw new \Exception('Esta matrÃ­cula se cobra por cronograma de cuotas. Registre el pago desde el mÃ³dulo de cuotas.');
        }

        // Validar que exista una caja abierta
        $cajaService = app(CajaService::class);
        if (! $cajaService->validarCajaAbierta(Auth::user()->id)) {
            throw new \Exception('No hay una caja abierta. Por favor, abra una caja antes de registrar pagos.');
        }

        // Obtener o crear caja abierta para el usuario actual
        $caja = $cajaService->obtenerOCrearCajaAbierta();
        $this->assertCajaSucursal($caja->id, (int) $clienteMatricula->sucursal_id);

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
                $this->assertPaymentMethodSucursal((int) $paymentMethodId, (int) $clienteMatricula->sucursal_id);
                $pm = \App\Models\Core\PaymentMethod::find($paymentMethodId);
                if ($pm) {
                    $metodoPago = $pm->nombre;
                }
            }

            $cobro = app(CobroTicketService::class)->resolverComprobantePago([
                'comprobante_tipo' => $data['comprobante_tipo'] ?? null,
                'comprobante_numero' => $data['comprobante_numero'] ?? null,
            ]);

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
                'comprobante_tipo' => $cobro['tipo'],
                'comprobante_numero' => $cobro['numero'],
                'registrado_por' => Auth::user()->id,
                'caja_id' => $caja->id,
                'sucursal_id' => $clienteMatricula->sucursal_id,
            ]);

            $cajaService = app(CajaService::class);
            $concepto = 'Cobro de '.strtolower($clienteMatricula->tipo).' - '.$clienteMatricula->nombre;
            $observaciones = 'Metodo de pago: '.$metodoPago;
            if ($pago->comprobante_tipo || $pago->comprobante_numero) {
                $observaciones .= ', Comprobante: '.strtoupper((string) $pago->comprobante_tipo).' '.$pago->comprobante_numero;
            }
            $cajaService->registrarIngresoPorPago(
                $pago,
                $concepto,
                $clienteMatricula->esClase() ? CajaMovimiento::CATEGORIA_CLASE : CajaMovimiento::CATEGORIA_MEMBRESIA,
                CajaMovimiento::ORIGEN_CLIENTE_MATRICULAS,
                null,
                null,
                trim($observaciones, ', ')
            );

            return $pago;
        });
    }

    /**
     * Obtener el saldo pendiente de una matrÃ­cula
     */
    public function obtenerSaldoPendiente(int $clienteMatriculaId): float
    {
        $clienteMatricula = $this->find($clienteMatriculaId);

        if (! $clienteMatricula) {
            return 0;
        }

        if ($clienteMatricula->usaPlanCuotas()) {
            if (! $clienteMatricula->enrollmentInstallments()->exists()) {
                return round($clienteMatricula->monto_financiado, 2);
            }

            $sum = (float) $clienteMatricula->enrollmentInstallments()
                ->whereIn('estado', ['pendiente', 'vencida', 'parcial'])
                ->get()
                ->sum(fn (\App\Models\Core\EnrollmentInstallment $installment) => $installment->saldo_pendiente);

            return round(max(0, $sum), 2);
        }

        // Obtener el Ãºltimo pago para ver el saldo pendiente actual
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
     * Actualizar una matrÃ­cula de cliente
     */
    public function update(int $id, array $data): ClienteMatricula
    {
        $clienteMatricula = $this->find($id);

        if (! $clienteMatricula) {
            throw new \Exception('MatrÃ­cula no encontrada');
        }

        if ($clienteMatricula->estado === 'completada') {
            throw new \Exception('No se puede editar una matrÃ­cula completada.');
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
            throw new \Exception('No se puede modificar el precio o modalidad de una matrÃ­cula que ya tiene plan de cuotas.');
        }

        $cambiaPrecio = array_key_exists('precio_lista', $data)
            || array_key_exists('descuento_monto', $data)
            || array_key_exists('precio_final', $data);

        if (
            $clienteMatricula->enrollmentInstallments()->exists()
            && ! $clienteMatricula->usaPlanCuotas()
            && $cambiaPrecio
        ) {
            throw new \Exception('No se puede modificar el precio: esta matrÃ­cula tiene cuotas registradas. Use la pantalla de cuotas del cliente.');
        }

        $validated = $this->validate($data, $id);

        $precioFinalAntes = (float) $clienteMatricula->precio_final;
        $cambioPrecioOFinanzas = array_key_exists('precio_lista', $data)
            || array_key_exists('descuento_monto', $data)
            || array_key_exists('precio_final', $data);

        $fechaInicioMatriculaAntes = $clienteMatricula->fecha_inicio?->format('Y-m-d');

        return DB::transaction(function () use ($clienteMatricula, $validated, $precioFinalAntes, $cambioPrecioOFinanzas, $fechaInicioMatriculaAntes) {
            $planAnteriorTipo = $clienteMatricula->tipo === 'membresia' ? 'membresia' : 'clase';
            $planAnteriorId = $clienteMatricula->tipo === 'membresia'
                ? $clienteMatricula->membresia_id
                : $clienteMatricula->clase_id;
            $nuevoEstado = $validated['estado'] ?? $clienteMatricula->estado;

            // Si pasa de congelada a activa (membresÃ­a), el cliente solo puede tener una activa
            if ($clienteMatricula->tipo === 'membresia' && $clienteMatricula->estado === 'congelada' && $nuevoEstado === 'activa') {
                $otraActiva = ClienteMatricula::where('cliente_id', $clienteMatricula->cliente_id)
                    ->where('tipo', 'membresia')
                    ->where('estado', 'activa')
                    ->where('id', '!=', $clienteMatricula->id)
                    ->exists();
                if ($otraActiva) {
                    throw new \Exception('El cliente ya tiene una membresÃ­a activa.');
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

            $clienteMatricula->refresh();

            if (
                $clienteMatricula->usaPlanCuotas()
                && $fechaInicioMatriculaAntes !== null
                && isset($validated['fecha_inicio'])
            ) {
                $nuevaInicio = Carbon::parse($validated['fecha_inicio'])->startOfDay();
                $antiguaInicio = Carbon::parse($fechaInicioMatriculaAntes)->startOfDay();
                if ($nuevaInicio->toDateString() !== $antiguaInicio->toDateString()) {
                    $dias = (int) floor(($nuevaInicio->timestamp - $antiguaInicio->timestamp) / 86400);
                    if ($dias !== 0) {
                        app(\App\Services\EnrollmentInstallmentService::class)
                            ->shiftPendingInstallmentsForMatricula($clienteMatricula->fresh(), $dias);
                    }
                }
            }

            if (
                $cambioPrecioOFinanzas
                && ! $clienteMatricula->usaPlanCuotas()
                && ! $clienteMatricula->enrollmentInstallments()->exists()
                && abs((float) $clienteMatricula->precio_final - $precioFinalAntes) > 0.004
            ) {
                $this->sincronizarPagoUnicoMatriculaContado($clienteMatricula, $precioFinalAntes);
            }

            // El cliente solo puede tener una membresÃ­a activa: las demÃ¡s pasan a congelada (al crear o activar otra)
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
     * Ajusta el Ãºnico pago de alta de una matrÃ­cula al contado (sin plan de cuotas) tras cambiar precio_final.
     *
     * @throws \Exception
     */
    protected function sincronizarPagoUnicoMatriculaContado(ClienteMatricula $clienteMatricula, float $precioFinalAnterior): void
    {
        $pagos = Pago::query()
            ->where('cliente_matricula_id', $clienteMatricula->id)
            ->orderBy('id')
            ->get();

        if ($pagos->isEmpty()) {
            return;
        }

        if ($pagos->count() > 1) {
            throw new \Exception('No se puede sincronizar el precio: existen varios pagos registrados para esta matrÃ­cula.');
        }

        $pago = $pagos->first();

        if ($pago->caja_id !== null) {
            throw new \Exception('No se puede modificar el precio: ya hay cobros asociados a caja en esta matrÃ­cula.');
        }

        $nuevoPrecio = (float) $clienteMatricula->precio_final;
        $montoRegistrado = (float) $pago->monto;
        $saldoAnterior = (float) $pago->saldo_pendiente;

        if (abs(($montoRegistrado + $saldoAnterior) - $precioFinalAnterior) > 0.02) {
            throw new \Exception('No se puede ajustar el precio automÃ¡ticamente: el pago registrado no coincide con el precio anterior.');
        }

        if (abs($saldoAnterior) < 0.004) {
            $pago->update([
                'monto' => $nuevoPrecio,
                'es_pago_parcial' => false,
                'saldo_pendiente' => 0,
            ]);

            return;
        }

        $nuevoSaldo = round($nuevoPrecio - $montoRegistrado, 2);

        if ($nuevoSaldo < -0.004) {
            throw new \Exception('El precio final no puede ser menor al monto ya pagado a cuenta.');
        }

        $pago->update([
            'es_pago_parcial' => $nuevoSaldo > 0.004,
            'saldo_pendiente' => max(0, $nuevoSaldo),
        ]);
    }

    /**
     * Eliminar una matrÃ­cula de cliente
     */
    public function delete(int $id): bool
    {
        $clienteMatricula = $this->find($id);

        if (! $clienteMatricula) {
            throw new \Exception('MatrÃ­cula no encontrada');
        }

        if ($clienteMatricula->estado === 'completada') {
            throw new \Exception('No se puede eliminar una matrÃ­cula completada.');
        }

        // Verificar si tiene relaciones
        $this->checkRelations($clienteMatricula);

        return DB::transaction(function () use ($clienteMatricula) {
            return $clienteMatricula->delete();
        });
    }

    /**
     * Validar datos de la matrÃ­cula
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
            'frecuencia_cuotas' => ['nullable', 'string', 'in:quincenal,mensual'],
            'fecha_inicio_plan_cuotas' => ['nullable', 'date'],
            'monto_pago_inicial' => ['nullable', 'numeric', 'min:0', 'regex:/^\d+(\.\d{1,2})?$/'],
            'installment_schedule' => ['nullable', 'array'],
            'installment_schedule.*.monto' => ['nullable', 'numeric', 'min:0'],
            'installment_schedule.*.fecha_vencimiento' => ['nullable', 'date'],
        ];

        if ($tipo === 'membresia') {
            $rules['membresia_id'] = [$isUpdate ? 'sometimes' : 'required', 'exists:membresias,id'];
            $rules['clase_id'] = ['nullable'];
        } else {
            $rules['clase_id'] = [$isUpdate ? 'sometimes' : 'required', 'exists:clases,id'];
            $rules['membresia_id'] = ['nullable'];
        }

        $validator = Validator::make($data, $rules);
        $validator->after(function ($validator) use ($data, $tipo, $isUpdate) {
            if ($tipo !== 'membresia') {
                return;
            }

            $modalidadPago = $data['modalidad_pago'] ?? 'contado';

            if (! $isUpdate && $modalidadPago === 'contado' && array_key_exists('monto_pago_inicial', $data) && $data['monto_pago_inicial'] !== null && $data['monto_pago_inicial'] !== '') {
                $precioFinal = (float) ($data['precio_final'] ?? (($data['precio_lista'] ?? 0) - ($data['descuento_monto'] ?? 0)));
                $monto = round((float) $data['monto_pago_inicial'], 2);
                if ($monto < 0) {
                    $validator->errors()->add('monto_pago_inicial', 'El pago a cuenta no puede ser negativo.');
                } elseif ($precioFinal > 0 && $monto > $precioFinal) {
                    $validator->errors()->add('monto_pago_inicial', 'El pago a cuenta no puede superar el precio final.');
                }
            }

            if ($modalidadPago !== 'cuotas') {
                return;
            }

            $precioFinal = (float) ($data['precio_final'] ?? (($data['precio_lista'] ?? 0) - ($data['descuento_monto'] ?? 0)));
            $cuotaInicialMonto = array_key_exists('cuota_inicial_monto', $data) && $data['cuota_inicial_monto'] !== null
                ? (float) $data['cuota_inicial_monto']
                : null;

            if ($precioFinal <= 0) {
                $validator->errors()->add('precio_final', 'El precio final debe ser mayor a cero para generar cuotas.');
            }

            if (($data['frecuencia_cuotas'] ?? null) === null) {
                $validator->errors()->add('frecuencia_cuotas', 'Debes indicar la frecuencia de cuotas.');
            }

            if (($data['numero_cuotas'] ?? null) === null) {
                $validator->errors()->add('numero_cuotas', 'Debes indicar el numero de cuotas.');
            }
            if ($cuotaInicialMonto !== null && $cuotaInicialMonto >= $precioFinal && $precioFinal > 0) {
                $validator->errors()->add('cuota_inicial_monto', 'La cuota inicial debe ser menor al precio final.');
            }

            $schedule = $data['installment_schedule'] ?? null;
            if (is_array($schedule) && $schedule !== []) {
                $suma = round((float) collect($schedule)->sum(fn ($row) => (float) ($row['monto'] ?? 0)), 2);
                if (abs($suma - round($precioFinal, 2)) > 0.02) {
                    $validator->errors()->add('installment_schedule', 'La suma de las cuotas del cronograma debe igualar el precio final.');
                }
                if (count($schedule) < 2) {
                    $validator->errors()->add('installment_schedule', 'El cronograma debe tener al menos dos cuotas.');
                }
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
            throw new \Exception('No se puede eliminar la matrÃ­cula porque tiene pagos o asistencias asociadas.');
        }
    }

    /**
     * Obtener todas las membresÃ­as activas
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

    private function assertCajaSucursal(int $cajaId, int $sucursalId): void
    {
        $caja = \App\Models\Core\Caja::query()->findOrFail($cajaId);
        if ((int) $caja->sucursal_id !== $sucursalId) {
            throw new \InvalidArgumentException('La caja seleccionada no pertenece a la misma sucursal de la matricula.');
        }
    }

    private function assertPaymentMethodSucursal(int $paymentMethodId, int $sucursalId): void
    {
        $paymentMethod = \App\Models\Core\PaymentMethod::query()->find($paymentMethodId);
        if (! $paymentMethod || (int) $paymentMethod->sucursal_id !== $sucursalId) {
            throw new \InvalidArgumentException('El metodo de pago seleccionado no pertenece a la misma sucursal.');
        }
    }
}
