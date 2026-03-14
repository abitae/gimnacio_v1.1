<?php

namespace App\Services;

use App\Models\Core\ClienteMembresia;
use App\Models\Core\ClienteMatricula;
use Illuminate\Support\Collection;

class ClientEnrollmentService
{
    public function __construct(
        protected ClienteMatriculaService $clienteMatriculaService,
        protected ClienteMembresiaService $clienteMembresiaService,
    ) {
    }

    public function resolveActiveEnrollmentModel(int $clienteId): ClienteMatricula|ClienteMembresia|null
    {
        $today = today();

        $matricula = ClienteMatricula::query()
            ->where('cliente_id', $clienteId)
            ->where('tipo', 'membresia')
            ->where('estado', 'activa')
            ->where('fecha_inicio', '<=', $today)
            ->where(function ($query) use ($today) {
                $query->whereNull('fecha_fin')
                    ->orWhere('fecha_fin', '>=', $today);
            })
            ->with(['membresia', 'cliente'])
            ->orderBy('fecha_inicio', 'desc')
            ->first();

        if ($matricula) {
            return $matricula;
        }

        return ClienteMembresia::query()
            ->where('cliente_id', $clienteId)
            ->where('estado', 'activa')
            ->where('fecha_inicio', '<=', $today)
            ->where(function ($query) use ($today) {
                $query->whereNull('fecha_fin')
                    ->orWhere('fecha_fin', '>=', $today);
            })
            ->with(['membresia', 'cliente'])
            ->orderBy('fecha_inicio', 'desc')
            ->first();
    }

    public function resolveActiveEnrollment(int $clienteId): ?array
    {
        $record = $this->resolveActiveEnrollmentModel($clienteId);

        if (! $record) {
            return null;
        }

        $sourceType = $record instanceof ClienteMatricula ? 'cliente_matricula' : 'cliente_membresia';

        return [
            'source_type' => $sourceType,
            'source_id' => $record->id,
            'cliente_id' => $record->cliente_id,
            'tipo' => $record instanceof ClienteMatricula ? $record->tipo : 'membresia',
            'estado' => $record->estado,
            'fecha_inicio' => $record->fecha_inicio,
            'fecha_fin' => $record->fecha_fin,
            'nombre_plan' => $record->membresia->nombre ?? $record->nombre ?? 'N/A',
            'saldo_pendiente' => $this->resolveBalanceForRecord($record),
            'source_model' => $record,
        ];
    }

    public function resolveBalanceForRecord(ClienteMatricula|ClienteMembresia $record): float
    {
        if ($record instanceof ClienteMatricula) {
            return $this->clienteMatriculaService->obtenerSaldoPendiente($record->id);
        }

        return $this->clienteMembresiaService->obtenerSaldoPendiente($record->id);
    }

    public function resolveCommercialHistory(int $clienteId, int $membershipLimit = 10, int $classLimit = 10): array
    {
        $memberships = ClienteMatricula::with(['membresia', 'pagos', 'asesor'])
            ->where('cliente_id', $clienteId)
            ->where('tipo', 'membresia')
            ->orderBy('fecha_inicio', 'desc')
            ->limit($membershipLimit)
            ->get()
            ->values();

        if ($memberships->isEmpty()) {
            $memberships = ClienteMembresia::with(['membresia', 'pagos', 'asesor'])
                ->where('cliente_id', $clienteId)
                ->orderBy('fecha_inicio', 'desc')
                ->limit($membershipLimit)
                ->get()
                ->values();
        }

        $classes = ClienteMatricula::with(['clase', 'pagos', 'asesor'])
            ->where('cliente_id', $clienteId)
            ->where('tipo', 'clase')
            ->orderBy('fecha_inicio', 'desc')
            ->limit($classLimit)
            ->get()
            ->values();

        return [
            'memberships' => $memberships,
            'classes' => $classes,
        ];
    }

    public function resolveLatestActiveEnrollmentFromHistory(Collection $history): ClienteMatricula|ClienteMembresia|null
    {
        $today = today();

        return $history->first(function ($record) use ($today) {
            return $record->estado === 'activa'
                && $record->fecha_inicio <= $today
                && ($record->fecha_fin === null || $record->fecha_fin >= $today);
        });
    }
}
