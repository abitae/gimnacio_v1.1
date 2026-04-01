<?php

namespace App\Livewire\Concerns;

use App\Models\Core\ClienteMatricula;
use App\Services\ClientWellnessService;
use Carbon\Carbon;

/**
 * Formulario de matrícula (membresía/clase) reutilizable en listados y perfil de cliente.
 *
 * @property-read \App\Services\ClienteMatriculaService $matriculaService
 * @property-read \App\Services\ClienteService $clienteService
 */
trait ManagesClienteMatriculaForm
{
    public array $matriculaModalState = [
        'create' => false,
        'delete' => false,
    ];

    public ?int $clienteMatriculaId = null;

    public ?int $renovandoMatriculaId = null;

    public array $matriculaForm = [
        'tipo' => 'membresia',
        'membresia_id' => '',
        'clase_id' => '',
        'fecha_matricula' => '',
        'fecha_inicio' => '',
        'fecha_fin' => '',
        'estado' => 'activa',
        'precio_lista' => 0.00,
        'descuento_monto' => 0.00,
        'precio_final' => 0.00,
        'modalidad_pago' => 'contado',
        'monto_pago_inicial' => 0.00,
        'cuota_inicial_monto' => 0.00,
        'numero_cuotas' => null,
        'frecuencia_cuotas' => 'mensual',
        'personalizado_por' => 'numero_cuotas',
        'monto_cuota_personalizado' => '',
        'fecha_inicio_plan_cuotas' => '',
        'asesor_id' => null,
        'canal_venta' => 'presencial',
        'fechas_congelacion' => [],
        'motivo_cancelacion' => '',
        'sesiones_totales' => null,
        'sesiones_usadas' => 0,
    ];

    public bool $membresiaPermiteCuotas = false;

    /** Si true, no se puede cambiar nº de cuotas / frecuencia / inicio del plan (cronograma ya generado). */
    public bool $matriculaBloqueaNumeroCuotas = false;

    public bool $matriculaCongelarModalOpen = false;

    /**
     * Si true (p. ej. perfil de cliente), en alta de membresía en contado no se ofrece pago a cuenta:
     * queda deuda total o se usa plan de cuotas.
     */
    public bool $matriculaFormSinPagoInicialEnAlta = false;

    public ?int $matriculaCongelarId = null;

    public int $matriculaCongelarDias = 7;

    public string $matriculaCongelarMotivo = '';

    public ?int $matriculaCongelarMaxDias = null;

    /** Membresías vs clases según pestaña del componente host. */
    abstract protected function matriculaTabIsMembresias(): bool;

    protected function afterClienteMatriculaMutation(): void
    {
        if (method_exists($this, 'resetPage')) {
            $this->resetPage();
        }
    }

    public function openMatriculaCreateModal(): void
    {
        $this->authorize('cliente-matriculas.create');
        if (! $this->selectedClienteId) {
            $this->flashToast('error', 'Debes seleccionar un cliente primero');

            return;
        }

        $this->resetMatriculaForm();
        $this->matriculaForm['tipo'] = $this->matriculaTabIsMembresias() ? 'membresia' : 'clase';
        if ($this->matriculaForm['tipo'] === 'membresia') {
            $this->matriculaForm['fecha_matricula'] = now()->format('Y-m-d');
            $this->matriculaForm['fecha_inicio'] = now()->format('Y-m-d');
            $this->matriculaForm['fecha_inicio_plan_cuotas'] = now()->format('Y-m-d');
            $this->updatedMatriculaFormFechaInicio();
        } else {
            $this->matriculaForm['fecha_matricula'] = now()->format('Y-m-d');
            $this->matriculaForm['fecha_fin'] = '';
        }
        $this->matriculaModalState['create'] = true;
    }

    public function openMatriculaEditModal(int $id): void
    {
        $this->authorize('cliente-matriculas.update');
        $clienteMatricula = $this->matriculaService->find($id);

        if (! $clienteMatricula) {
            $this->flashToast('error', 'Matrícula no encontrada');

            return;
        }

        $this->clienteMatriculaId = $clienteMatricula->id;
        $this->mapClienteMatriculaToMatriculaForm($clienteMatricula);
        $this->matriculaModalState['create'] = true;
    }

    public function openMatriculaDeleteModal(int $id): void
    {
        $this->authorize('cliente-matriculas.delete');
        $this->clienteMatriculaId = $id;
        $this->matriculaModalState['delete'] = true;
    }

    public function openRenovarMembresia(int $clienteId, int $matriculaId): void
    {
        $this->authorize('cliente-matriculas.create');
        $matricula = $this->matriculaService->find($matriculaId);
        if (! $matricula || $matricula->tipo !== 'membresia' || (int) $matricula->cliente_id !== $clienteId) {
            $this->flashToast('error', 'Matrícula no encontrada o no es una membresía');

            return;
        }

        $this->selectedClienteId = $clienteId;
        $this->selectedCliente = $this->clienteService->find($clienteId);
        $this->clienteSearch = $this->selectedCliente ? ($this->selectedCliente->nombres.' '.$this->selectedCliente->apellidos) : '';
        $this->clientes = collect([]);

        if (property_exists($this, 'activeTab')) {
            $this->activeTab = 'membresias';
        }
        if (method_exists($this, 'setTab')) {
            $this->setTab('membresias');
        }

        $this->resetMatriculaForm();
        $this->renovandoMatriculaId = $matriculaId;
        $this->matriculaForm['tipo'] = 'membresia';
        $this->matriculaForm['membresia_id'] = (string) $matricula->membresia_id;
        $this->matriculaForm['fecha_matricula'] = now()->format('Y-m-d');
        $this->matriculaForm['fecha_inicio'] = Carbon::parse($matricula->fecha_fin)->addDay()->format('Y-m-d');
        $this->updatedMatriculaFormMembresiaId();
        $this->matriculaForm['estado'] = $matricula->fecha_fin && Carbon::parse($matricula->fecha_fin)->gte(Carbon::today())
            ? 'congelada'
            : 'activa';

        $this->matriculaModalState['create'] = true;
    }

    public function closeMatriculaModal(): void
    {
        $this->matriculaModalState = [
            'create' => false,
            'delete' => false,
        ];
        $this->resetMatriculaForm();
    }

    public function updatedMatriculaFormTipo(): void
    {
        $this->matriculaForm['membresia_id'] = '';
        $this->matriculaForm['clase_id'] = '';
        $this->matriculaForm['precio_lista'] = 0.00;
        $this->matriculaForm['precio_final'] = 0.00;
        $this->matriculaForm['sesiones_totales'] = null;
        $this->membresiaPermiteCuotas = false;
        $this->resetMatriculaQuotaFormData();
        if ($this->matriculaForm['tipo'] === 'clase') {
            $this->matriculaForm['fecha_fin'] = '';
        }
    }

    public function updatedMatriculaFormMembresiaId(): void
    {
        $this->membresiaPermiteCuotas = false;

        if ($this->matriculaForm['membresia_id']) {
            $membresia = \App\Models\Core\Membresia::find($this->matriculaForm['membresia_id']);
            if ($membresia) {
                $this->matriculaForm['precio_lista'] = $membresia->precio_base;
                $this->calculateMatriculaPrecioFinal();
                $this->membresiaPermiteCuotas = true;
                $this->matriculaForm['frecuencia_cuotas'] = $membresia->frecuencia_cuotas_default ?: 'mensual';
                $this->syncMatriculaNumeroCuotasFromFrequency($membresia);
                $this->matriculaForm['personalizado_por'] = 'numero_cuotas';
                $this->matriculaForm['monto_cuota_personalizado'] = '';
                if (($this->matriculaForm['modalidad_pago'] ?? 'contado') === 'cuotas') {
                    $this->matriculaForm['cuota_inicial_monto'] = $this->resolverCuotaInicialDefaultForMatricula($membresia, (float) $this->matriculaForm['precio_final']);
                } else {
                    $this->matriculaForm['cuota_inicial_monto'] = 0.0;
                }
                $this->matriculaForm['fecha_inicio_plan_cuotas'] = $this->matriculaForm['fecha_inicio'] ?: now()->format('Y-m-d');

                if ($this->matriculaForm['fecha_inicio']) {
                    $fechaInicio = Carbon::parse($this->matriculaForm['fecha_inicio']);
                    $this->matriculaForm['fecha_fin'] = $fechaInicio->copy()->addDays($membresia->duracion_dias ?? 30)->format('Y-m-d');
                }
            }
        } else {
            $this->resetMatriculaQuotaFormData();
        }
    }

    public function updatedMatriculaFormClaseId(): void
    {
        if ($this->matriculaForm['clase_id']) {
            $clase = \App\Models\Core\Clase::find($this->matriculaForm['clase_id']);
            if ($clase) {
                $this->matriculaForm['precio_lista'] = $clase->obtenerPrecio();
                if ($clase->tipo === 'paquete' && $clase->sesiones_paquete) {
                    $this->matriculaForm['sesiones_totales'] = $clase->sesiones_paquete;
                }
                $this->calculateMatriculaPrecioFinal();
            }
        }
    }

    public function updatedMatriculaFormPrecioLista(): void
    {
        $this->calculateMatriculaPrecioFinal();
    }

    public function updatedMatriculaFormDescuentoMonto(): void
    {
        $this->calculateMatriculaPrecioFinal();
    }

    protected function calculateMatriculaPrecioFinal(): void
    {
        $precioLista = (float) ($this->matriculaForm['precio_lista'] ?? 0);
        $descuento = (float) ($this->matriculaForm['descuento_monto'] ?? 0);
        $this->matriculaForm['precio_final'] = max(0, $precioLista - $descuento);
    }

    public function updatedMatriculaFormModalidadPago(): void
    {
        if ($this->matriculaForm['modalidad_pago'] !== 'cuotas') {
            $this->resetMatriculaQuotaFormData(keepModalidad: true);

            return;
        }

        if ($this->matriculaForm['tipo'] === 'membresia' && filled($this->matriculaForm['membresia_id'] ?? null)) {
            $membresia = \App\Models\Core\Membresia::find($this->matriculaForm['membresia_id']);
            if ($membresia) {
                $this->matriculaForm['frecuencia_cuotas'] = $membresia->frecuencia_cuotas_default ?: 'mensual';
                $this->syncMatriculaNumeroCuotasFromFrequency($membresia);
                $this->matriculaForm['cuota_inicial_monto'] = $this->resolverCuotaInicialDefaultForMatricula($membresia, (float) $this->matriculaForm['precio_final']);
                $this->matriculaForm['fecha_inicio_plan_cuotas'] = $this->matriculaForm['fecha_inicio'] ?: now()->format('Y-m-d');
            }
        }
    }

    public function updatedMatriculaFormFrecuenciaCuotas(): void
    {
        if (($this->matriculaForm['frecuencia_cuotas'] ?? '') !== 'personalizado') {
            $this->matriculaForm['personalizado_por'] = 'numero_cuotas';
            $this->matriculaForm['monto_cuota_personalizado'] = '';
        }

        if (
            $this->matriculaBloqueaNumeroCuotas
            || ($this->matriculaForm['modalidad_pago'] ?? 'contado') !== 'cuotas'
            || ($this->matriculaForm['personalizado_por'] ?? 'numero_cuotas') === 'monto_cuota'
            || ! filled($this->matriculaForm['membresia_id'] ?? null)
        ) {
            return;
        }

        $membresia = \App\Models\Core\Membresia::find($this->matriculaForm['membresia_id']);
        if ($membresia) {
            $this->syncMatriculaNumeroCuotasFromFrequency($membresia);
        }
    }

    public function updatedMatriculaFormFechaInicio(): void
    {
        if ($this->matriculaForm['fecha_inicio']) {
            if (! $this->matriculaForm['fecha_inicio_plan_cuotas']) {
                $this->matriculaForm['fecha_inicio_plan_cuotas'] = $this->matriculaForm['fecha_inicio'];
            }

            if ($this->matriculaForm['tipo'] === 'membresia' && $this->matriculaForm['membresia_id']) {
                $membresia = \App\Models\Core\Membresia::find($this->matriculaForm['membresia_id']);
                if ($membresia) {
                    $fechaInicio = Carbon::parse($this->matriculaForm['fecha_inicio']);
                    $this->matriculaForm['fecha_fin'] = $fechaInicio->copy()->addDays($membresia->duracion_dias)->format('Y-m-d');
                }
            } elseif ($this->matriculaForm['tipo'] === 'clase') {
                $this->matriculaForm['fecha_fin'] = '';
            }
        }
    }

    public function saveMatricula(): void
    {
        $this->authorize($this->clienteMatriculaId ? 'cliente-matriculas.update' : 'cliente-matriculas.create');
        try {
            if (! $this->selectedClienteId) {
                $this->flashToast('error', 'Debes seleccionar un cliente primero');

                return;
            }

            $data = $this->mapMatriculaFormToData();
            $data['cliente_id'] = $this->selectedClienteId;

            if ($this->clienteMatriculaId) {
                $matriculaActual = $this->matriculaService->find($this->clienteMatriculaId);
                $eraCongeladaYAhoraActiva = $matriculaActual
                    && $matriculaActual->tipo === 'membresia'
                    && $matriculaActual->estado === 'congelada'
                    && ($this->matriculaForm['estado'] ?? '') === 'activa';

                $this->matriculaService->update($this->clienteMatriculaId, $data);

                if ($eraCongeladaYAhoraActiva) {
                    $this->flashToast('success', 'Membresía activada correctamente. La fecha de inicio se actualizó a hoy. Si el cliente tenía otra membresía activa, esta ha pasado a estado congelada.');
                } else {
                    $this->flashToast('success', 'Matrícula actualizada correctamente');
                }
            } else {
                if ($this->renovandoMatriculaId) {
                    $matriculaVigente = $this->matriculaService->find($this->renovandoMatriculaId);
                    if ($matriculaVigente && $matriculaVigente->fecha_fin && Carbon::parse($matriculaVigente->fecha_fin)->gte(Carbon::today())) {
                        $this->matriculaForm['estado'] = 'congelada';
                    } else {
                        $this->matriculaForm['estado'] = 'activa';
                    }
                    $data = $this->mapMatriculaFormToData();
                    $data['cliente_id'] = $this->selectedClienteId;
                }
                $this->matriculaService->create($data);
                if ($this->renovandoMatriculaId) {
                    $this->flashToast('success', $this->matriculaForm['estado'] === 'congelada'
                        ? 'Membresía renovada. Quedará congelada hasta que termine la membresía actual.'
                        : 'Membresía renovada y activa correctamente.');
                    $this->renovandoMatriculaId = null;
                } else {
                    $this->flashToast('success', 'Matrícula creada correctamente');
                }
            }

            \App\Models\Core\Cliente::where('id', $this->selectedClienteId)->update(['estado_cliente' => 'activo']);

            $this->closeMatriculaModal();
            $this->afterClienteMatriculaMutation();
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->handleMatriculaValidationErrors($e);
        } catch (\Exception $e) {
            $this->flashToast('error', $e->getMessage());
        }
    }

    public function deleteMatricula(): void
    {
        $this->authorize('cliente-matriculas.delete');
        try {
            $this->matriculaService->delete($this->clienteMatriculaId);
            $this->flashToast('success', 'Matrícula eliminada correctamente');
            $this->closeMatriculaModal();
            $this->afterClienteMatriculaMutation();
        } catch (\Exception $e) {
            $this->flashToast('error', $e->getMessage());
        }
    }

    protected function mapClienteMatriculaToMatriculaForm(ClienteMatricula $clienteMatricula): void
    {
        $this->matriculaForm = [
            'tipo' => $clienteMatricula->tipo,
            'membresia_id' => $clienteMatricula->membresia_id,
            'clase_id' => $clienteMatricula->clase_id,
            'fecha_matricula' => $clienteMatricula->fecha_matricula?->format('Y-m-d') ?? '',
            'fecha_inicio' => $clienteMatricula->fecha_inicio->format('Y-m-d'),
            'fecha_fin' => $clienteMatricula->fecha_fin ? $clienteMatricula->fecha_fin->format('Y-m-d') : '',
            'estado' => $clienteMatricula->estado,
            'precio_lista' => $clienteMatricula->precio_lista,
            'descuento_monto' => $clienteMatricula->descuento_monto,
            'precio_final' => $clienteMatricula->precio_final,
            'modalidad_pago' => $clienteMatricula->modalidad_pago ?? 'contado',
            'monto_pago_inicial' => 0.00,
            'cuota_inicial_monto' => (float) ($clienteMatricula->cuota_inicial_monto ?? 0),
            'numero_cuotas' => ($nCuotas = $clienteMatricula->enrollmentInstallments()->count()) > 0
                ? $nCuotas
                : max(2, (int) ($clienteMatricula->membresia?->numero_cuotas_default ?? 12)),
            'frecuencia_cuotas' => $clienteMatricula->installmentPlan?->frecuencia ?? 'mensual',
            'personalizado_por' => 'numero_cuotas',
            'monto_cuota_personalizado' => '',
            'fecha_inicio_plan_cuotas' => ($m = $clienteMatricula->enrollmentInstallments()->min('fecha_vencimiento'))
                ? Carbon::parse($m)->format('Y-m-d')
                : ($clienteMatricula->installmentPlan?->fecha_inicio?->format('Y-m-d') ?? ''),
            'asesor_id' => $clienteMatricula->asesor_id,
            'canal_venta' => $clienteMatricula->canal_venta ?? 'presencial',
            'fechas_congelacion' => $clienteMatricula->fechas_congelacion ?? [],
            'motivo_cancelacion' => $clienteMatricula->motivo_cancelacion ?? '',
            'sesiones_totales' => $clienteMatricula->sesiones_totales,
            'sesiones_usadas' => $clienteMatricula->sesiones_usadas ?? 0,
        ];

        $this->membresiaPermiteCuotas = $clienteMatricula->tipo === 'membresia';
        $this->matriculaBloqueaNumeroCuotas = $clienteMatricula->enrollmentInstallments()->exists();
    }

    protected function mapMatriculaFormToData(): array
    {
        $data = [
            'tipo' => $this->matriculaForm['tipo'],
            'fecha_matricula' => $this->matriculaForm['fecha_matricula'],
            'fecha_inicio' => $this->matriculaForm['fecha_inicio'],
            'fecha_fin' => ($this->matriculaForm['tipo'] === 'clase') ? null : ($this->matriculaForm['fecha_fin'] ?: null),
            'estado' => $this->matriculaForm['estado'],
            'precio_lista' => $this->matriculaForm['precio_lista'],
            'descuento_monto' => $this->matriculaForm['descuento_monto'] ?? 0,
            'precio_final' => $this->matriculaForm['precio_final'],
            'asesor_id' => $this->matriculaForm['asesor_id'] ?: auth()->id(),
            'canal_venta' => $this->matriculaForm['canal_venta'] ?: null,
            'fechas_congelacion' => $this->matriculaForm['fechas_congelacion'] ?: null,
            'motivo_cancelacion' => $this->matriculaForm['motivo_cancelacion'] ?: null,
        ];

        if ($this->matriculaForm['tipo'] === 'membresia') {
            $data['membresia_id'] = $this->matriculaForm['membresia_id'];
            $data['clase_id'] = null;

            if (! $this->clienteMatriculaId) {
                $data['modalidad_pago'] = $this->matriculaForm['modalidad_pago'] ?? 'contado';

                if (($this->matriculaForm['modalidad_pago'] ?? 'contado') === 'cuotas') {
                    $data['cuota_inicial_monto'] = (float) ($this->matriculaForm['cuota_inicial_monto'] ?? 0);
                    $data['numero_cuotas'] = $this->matriculaForm['numero_cuotas'] ?: null;
                    $data['frecuencia_cuotas'] = $this->matriculaForm['frecuencia_cuotas'] ?: null;
                    $data['fecha_inicio_plan_cuotas'] = $this->matriculaForm['fecha_inicio_plan_cuotas'] ?: $this->matriculaForm['fecha_inicio'];
                    $data['personalizado_por'] = $this->matriculaForm['personalizado_por'] ?? 'numero_cuotas';
                    $data['monto_cuota_personalizado'] = $this->matriculaForm['monto_cuota_personalizado'] !== ''
                        ? $this->matriculaForm['monto_cuota_personalizado']
                        : null;
                }
            }
        } else {
            $data['clase_id'] = $this->matriculaForm['clase_id'];
            $data['membresia_id'] = null;
            $data['sesiones_totales'] = $this->matriculaForm['sesiones_totales'] ?? null;
            $data['sesiones_usadas'] = $this->matriculaForm['sesiones_usadas'] ?? 0;
        }

        return $data;
    }

    protected function resetMatriculaForm(): void
    {
        $this->clienteMatriculaId = null;
        $this->renovandoMatriculaId = null;
        $this->membresiaPermiteCuotas = false;
        $this->matriculaBloqueaNumeroCuotas = false;
        $this->matriculaForm = [
            'tipo' => $this->matriculaTabIsMembresias() ? 'membresia' : 'clase',
            'membresia_id' => '',
            'clase_id' => '',
            'fecha_matricula' => now()->format('Y-m-d'),
            'fecha_inicio' => '',
            'fecha_fin' => '',
            'estado' => 'activa',
            'precio_lista' => 0.00,
            'descuento_monto' => 0.00,
            'precio_final' => 0.00,
            'modalidad_pago' => 'contado',
            'monto_pago_inicial' => 0.00,
            'cuota_inicial_monto' => 0.00,
            'numero_cuotas' => null,
            'frecuencia_cuotas' => 'mensual',
            'personalizado_por' => 'numero_cuotas',
            'monto_cuota_personalizado' => '',
            'fecha_inicio_plan_cuotas' => '',
            'asesor_id' => auth()->id(),
            'canal_venta' => 'presencial',
            'fechas_congelacion' => [],
            'motivo_cancelacion' => '',
            'sesiones_totales' => null,
            'sesiones_usadas' => 0,
        ];
    }

    protected function resetMatriculaQuotaFormData(bool $keepModalidad = false): void
    {
        if (! $keepModalidad) {
            $this->matriculaForm['modalidad_pago'] = 'contado';
        }

        $this->matriculaForm['monto_pago_inicial'] = 0.00;
        $this->matriculaForm['cuota_inicial_monto'] = 0.00;
        $this->matriculaForm['numero_cuotas'] = null;
        $this->matriculaForm['frecuencia_cuotas'] = 'mensual';
        $this->matriculaForm['personalizado_por'] = 'numero_cuotas';
        $this->matriculaForm['monto_cuota_personalizado'] = '';
        $this->matriculaForm['fecha_inicio_plan_cuotas'] = $this->matriculaForm['fecha_inicio'] ?: now()->format('Y-m-d');
    }

    protected function resolverCuotaInicialDefaultForMatricula(\App\Models\Core\Membresia $membresia, float $precioFinal): float
    {
        if ($membresia->cuota_inicial_monto !== null) {
            return (float) $membresia->cuota_inicial_monto;
        }

        if ($membresia->cuota_inicial_porcentaje !== null) {
            return round($precioFinal * ((float) $membresia->cuota_inicial_porcentaje / 100), 2);
        }

        return 0.0;
    }

    protected function syncMatriculaNumeroCuotasFromFrequency(\App\Models\Core\Membresia $membresia): void
    {
        if ($this->matriculaBloqueaNumeroCuotas) {
            return;
        }

        $frecuencia = $this->matriculaForm['frecuencia_cuotas'] ?? 'mensual';
        if ($frecuencia === 'personalizado' && ($this->matriculaForm['personalizado_por'] ?? 'numero_cuotas') === 'monto_cuota') {
            return;
        }

        $duracionDias = max(1, (int) ($membresia->duracion_dias ?? 30));
        $numeroCuotas = match ($frecuencia) {
            'semanal' => (int) ceil($duracionDias / 7),
            'quincenal' => (int) ceil($duracionDias / 15),
            'mensual' => (int) ceil($duracionDias / 30),
            'anual' => (int) ceil($duracionDias / 360),
            default => (int) ($membresia->numero_cuotas_default ?? 12),
        };

        $this->matriculaForm['numero_cuotas'] = max(2, min(60, $numeroCuotas));
    }

    public function getMatriculaSaldoFinanciadoProperty(): float
    {
        $cuotaInicial = (float) ($this->matriculaForm['cuota_inicial_monto'] ?? 0);

        return max(0, round((float) ($this->matriculaForm['precio_final'] ?? 0) - $cuotaInicial, 2));
    }

    public function getMatriculaNumeroCuotasEstimadoProperty(): int
    {
        $saldoFinanciado = $this->matriculaSaldoFinanciado;
        $esFrecPersonalizada = ($this->matriculaForm['frecuencia_cuotas'] ?? '') === 'personalizado';
        $porMontoCuota = ($this->matriculaForm['personalizado_por'] ?? 'numero_cuotas') === 'monto_cuota';
        $montoObjPer = (float) ($this->matriculaForm['monto_cuota_personalizado'] ?? 0);
        $numeroCuotasCalc = max(1, (int) ($this->matriculaForm['numero_cuotas'] ?: 1));

        if ($esFrecPersonalizada && $porMontoCuota && $montoObjPer > 0 && $saldoFinanciado > 0) {
            $numeroCuotasCalc = max(2, min(60, (int) ceil($saldoFinanciado / $montoObjPer)));
        }

        return $numeroCuotasCalc;
    }

    public function getMatriculaCuotaEstimadaProperty(): float
    {
        $numeroCuotasCalc = $this->matriculaNumeroCuotasEstimado;

        return $numeroCuotasCalc > 0
            ? round($this->matriculaSaldoFinanciado / $numeroCuotasCalc, 2)
            : 0.0;
    }

    protected function handleMatriculaValidationErrors(\Illuminate\Validation\ValidationException $e): void
    {
        foreach ($e->errors() as $messages) {
            foreach ($messages as $message) {
                $this->flashToast('error', $message);
            }
        }
    }

    public function openCongelarMatriculaModal(int $matriculaId): void
    {
        $this->authorize('cliente-matriculas.update');
        if (! $this->selectedClienteId) {
            $this->flashToast('error', __('Debes seleccionar un cliente primero'));

            return;
        }

        $m = $this->matriculaService->find($matriculaId);
        if (! $m || $m->tipo !== 'membresia' || $m->estado !== 'activa') {
            $this->flashToast('error', __('Solo se pueden congelar membresías activas.'));

            return;
        }
        if ((int) $m->cliente_id !== (int) $this->selectedClienteId) {
            $this->flashToast('error', __('La matrícula no corresponde al cliente seleccionado.'));

            return;
        }
        if (! $m->membresia?->permite_congelacion) {
            $this->flashToast('error', __('Esta membresía no permite congelación.'));

            return;
        }

        $this->matriculaCongelarId = $matriculaId;
        $max = $m->membresia->max_dias_congelacion !== null ? (int) $m->membresia->max_dias_congelacion : null;
        $this->matriculaCongelarMaxDias = $max;
        $this->matriculaCongelarDias = $max !== null ? max(1, min(7, $max)) : 7;
        $this->matriculaCongelarMotivo = '';
        $this->matriculaCongelarModalOpen = true;
    }

    public function closeCongelarMatriculaModal(): void
    {
        $this->matriculaCongelarModalOpen = false;
        $this->matriculaCongelarId = null;
        $this->matriculaCongelarMaxDias = null;
    }

    public function saveCongelarMatricula(): void
    {
        $this->authorize('cliente-matriculas.update');
        if (! $this->selectedClienteId || ! $this->matriculaCongelarId) {
            $this->flashToast('error', __('Datos incompletos.'));

            return;
        }

        $dias = max(1, (int) $this->matriculaCongelarDias);
        if ($this->matriculaCongelarMaxDias !== null && $dias > $this->matriculaCongelarMaxDias) {
            $this->flashToast('error', __('Los días no pueden superar el máximo permitido por el plan (:max).', ['max' => $this->matriculaCongelarMaxDias]));

            return;
        }

        try {
            app(ClientWellnessService::class)->freezePlanByDays(
                (int) $this->selectedClienteId,
                'cliente_matricula',
                (int) $this->matriculaCongelarId,
                $dias,
                $this->matriculaCongelarMotivo !== '' ? $this->matriculaCongelarMotivo : null,
                (int) auth()->id()
            );
            $this->flashToast('success', __('Membresía congelada correctamente.'));
            $this->closeCongelarMatriculaModal();
            $this->afterClienteMatriculaMutation();
        } catch (\InvalidArgumentException|\RuntimeException $e) {
            $this->flashToast('error', $e->getMessage());
        }
    }
}
