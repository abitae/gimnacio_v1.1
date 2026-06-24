<?php

namespace App\Services\Wellness;

use App\Models\Core\NutritionGoal;
use App\Models\Core\SeguimientoNutricion;
use App\Services\SeguimientoNutricionService;
use Illuminate\Support\Collection;

class ClienteNutritionService
{
    public function __construct(
        protected SeguimientoNutricionService $seguimientoNutricionService,
    ) {}

    /**
     * @return array{seguimientos: Collection, objetivos_activos: Collection, ultimo_seguimiento: mixed}
     */
    public function getSummary(int $clienteId): array
    {
        $seguimientos = SeguimientoNutricion::query()
            ->where('cliente_id', $clienteId)
            ->orderByDesc('fecha')
            ->limit(10)
            ->get();

        $objetivos = NutritionGoal::query()
            ->where('cliente_id', $clienteId)
            ->whereIn('estado', ['activo', 'en_progreso'])
            ->orderByDesc('created_at')
            ->get();

        return [
            'seguimientos' => $seguimientos,
            'objetivos_activos' => $objetivos,
            'ultimo_seguimiento' => $seguimientos->first(),
        ];
    }
}
