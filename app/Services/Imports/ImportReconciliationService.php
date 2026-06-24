<?php

namespace App\Services\Imports;

use App\Models\Import;

class ImportReconciliationService
{
    /**
     * @return array<string, mixed>
     */
    public function summarize(Import $import): array
    {
        $rows = $import->rows();
        $total = (clone $rows)->count();
        $errores = (clone $rows)->where('estado', 'error')->count();
        $ok = (clone $rows)->where('estado', 'procesado')->count();
        $omitidos = (clone $rows)->where('estado', 'omitido')->count();

        return [
            'import_id' => $import->id,
            'tipo' => $import->tipo_importacion,
            'estado' => $import->estado,
            'total_filas' => $total,
            'procesadas' => $ok,
            'errores' => $errores,
            'omitidas' => $omitidos,
            'tasa_exito' => $total > 0 ? round(($ok / $total) * 100, 1) : 0.0,
            'finalizado_en' => $import->finished_at ?? $import->updated_at,
        ];
    }
}
