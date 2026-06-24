<?php

namespace App\Services\Cliente;

use App\Models\Core\ClienteMatricula;
use App\Models\Core\ClienteMembresia;
use App\Services\EnrollmentInstallmentService;
use Carbon\Carbon;

/**
 * Congelamiento comercial de planes (matrícula o membresía legacy).
 */
class PlanFreezeService
{
    public function __construct(
        protected EnrollmentInstallmentService $enrollmentInstallmentService,
    ) {}

    public function freezePlan(int $clienteId, array $data, int $userId): void
    {
        $model = $this->resolvePlanModelForFreeze($clienteId, $data);

        $desde = Carbon::parse($data['fecha_desde'])->startOfDay();
        $hasta = Carbon::parse($data['fecha_hasta'])->startOfDay();
        if ($hasta->lt($desde)) {
            throw new \InvalidArgumentException('La fecha hasta debe ser posterior o igual a la fecha desde.');
        }

        $this->applyFreezePeriod(
            $model,
            $desde->toDateString(),
            $hasta->toDateString(),
            $data['motivo'] ?? null,
            $userId
        );
    }

    /**
     * @param  'cliente_matricula'|'cliente_membresia'  $origenTipo
     */
    public function freezePlanByDays(int $clienteId, string $origenTipo, int $registroId, int $dias, ?string $motivo, int $userId): void
    {
        if ($dias < 1) {
            throw new \InvalidArgumentException('El número de días debe ser al menos 1.');
        }

        $model = $this->resolvePlanModelForFreeze($clienteId, [
            'origen_tipo' => $origenTipo,
            'registro_id' => $registroId,
        ]);

        $membresia = $model->membresia;
        if ($membresia) {
            if (! $membresia->permite_congelacion) {
                throw new \InvalidArgumentException('Esta membresía no permite congelación.');
            }
            if ($membresia->max_dias_congelacion !== null && $dias > (int) $membresia->max_dias_congelacion) {
                throw new \InvalidArgumentException('El periodo supera los días máximos de congelación permitidos.');
            }
        }

        $desde = Carbon::today()->startOfDay();
        $hasta = $desde->copy()->addDays($dias - 1)->startOfDay();

        $this->applyFreezePeriod(
            $model,
            $desde->toDateString(),
            $hasta->toDateString(),
            $motivo,
            $userId,
            $dias
        );
    }

    protected function applyFreezePeriod(
        ClienteMatricula|ClienteMembresia $model,
        string $fechaDesde,
        string $fechaHasta,
        ?string $motivo,
        int $userId,
        ?int $diasSolicitados = null
    ): void {
        $desde = Carbon::parse($fechaDesde)->startOfDay();
        $hasta = Carbon::parse($fechaHasta)->startOfDay();
        if ($hasta->lt($desde)) {
            throw new \InvalidArgumentException('La fecha hasta debe ser posterior o igual a la fecha desde.');
        }

        $dias = (int) $desde->diffInDays($hasta) + 1;

        $membresia = $model->membresia;
        if ($membresia) {
            if (! $membresia->permite_congelacion) {
                throw new \InvalidArgumentException('Esta membresía no permite congelación.');
            }
            if ($membresia->max_dias_congelacion !== null && $dias > (int) $membresia->max_dias_congelacion) {
                throw new \InvalidArgumentException('El periodo supera los días máximos de congelación permitidos.');
            }
        }

        $entry = [
            'desde' => $fechaDesde,
            'hasta' => $fechaHasta,
            'motivo' => $motivo ?: null,
            'registrado_por' => $userId,
            'registrado_en' => now()->toDateTimeString(),
        ];
        if ($diasSolicitados !== null) {
            $entry['dias'] = $diasSolicitados;
        }

        $fechas = collect($model->fechas_congelacion ?? [])
            ->push($entry)
            ->values()
            ->all();

        $attrs = [
            'estado' => 'congelada',
            'fechas_congelacion' => $fechas,
        ];

        if ($model->fecha_fin) {
            $attrs['fecha_fin'] = Carbon::parse($model->fecha_fin)->addDays($dias)->toDateString();
        }

        $model->update($attrs);

        if ($model instanceof ClienteMatricula) {
            $this->enrollmentInstallmentService->shiftPendingInstallmentsForMatricula($model->fresh(), $dias);
        }
    }

    /**
     * @param  array{origen_tipo: string, registro_id: int}  $data
     */
    protected function resolvePlanModelForFreeze(int $clienteId, array $data): ClienteMatricula|ClienteMembresia
    {
        $model = $data['origen_tipo'] === 'cliente_membresia'
            ? ClienteMembresia::with('membresia')->where('cliente_id', $clienteId)->find($data['registro_id'])
            : ClienteMatricula::with('membresia')->where('cliente_id', $clienteId)->find($data['registro_id']);

        if (! $model) {
            throw new \RuntimeException('No se encontró el plan seleccionado.');
        }

        return $model;
    }
}
