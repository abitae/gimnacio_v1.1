<?php

namespace App\Services\Imports;

use App\DataTransferObjects\Imports\SocioActivoRowData;
use App\Models\Core\Membresia;

class MembershipCatalogBuilder
{
    /**
     * @param  list<SocioActivoRowData>  $rows
     * @return array<string, mixed>
     */
    public function sync(array $rows, bool $execute = false): array
    {
        $packages = $this->analyzePackages($rows);
        $report = [
            'phase' => 'membresias',
            'dry_run' => ! $execute,
            'detected_packages' => count($packages),
            'created' => 0,
            'existing' => 0,
            'rejected' => 0,
            'conflicts' => [],
            'packages' => [],
            'errors' => [],
        ];

        foreach ($packages as $packageName => $meta) {
            if ($packageName === '' || $meta['precio_base'] === null || $meta['duracion_dias'] === null || $meta['duracion_dias'] <= 0 || $meta['precio_base'] < 0) {
                $report['rejected']++;
                $report['errors'][] = [
                    'package' => $packageName,
                    'reason' => 'El paquete no tiene nombre, duración o precio válidos.',
                ];
                continue;
            }

            if ($meta['price_conflict'] || $meta['duration_conflict']) {
                $report['conflicts'][] = [
                    'package' => $packageName,
                    'prices' => array_values(array_keys($meta['price_distribution'])),
                    'durations' => array_values(array_keys($meta['duration_distribution'])),
                ];
            }

            $existing = Membresia::query()->where('nombre', $packageName)->first();
            if ($existing) {
                $report['existing']++;
                $report['packages'][] = [
                    'package' => $packageName,
                    'action' => 'existing',
                    'precio_base' => $meta['precio_base'],
                    'duracion_dias' => $meta['duracion_dias'],
                ];
                continue;
            }

            if ($execute) {
                Membresia::create([
                    'nombre' => $packageName,
                    'descripcion' => 'Migrado desde Excel de socios activos',
                    'duracion_dias' => $meta['duracion_dias'],
                    'precio_base' => $meta['precio_base'],
                    'permite_cuotas' => false,
                    'numero_cuotas_default' => null,
                    'frecuencia_cuotas_default' => null,
                    'cuota_inicial_monto' => null,
                    'cuota_inicial_porcentaje' => null,
                    'tipo_acceso' => 'ilimitado',
                    'max_visitas_dia' => null,
                    'permite_congelacion' => false,
                    'max_dias_congelacion' => null,
                    'estado' => 'activa',
                ]);
            }

            $report['created']++;
            $report['packages'][] = [
                'package' => $packageName,
                'action' => $execute ? 'created' : 'would_create',
                'precio_base' => $meta['precio_base'],
                'duracion_dias' => $meta['duracion_dias'],
            ];
        }

        return $report;
    }

    /**
     * @param  list<SocioActivoRowData>  $rows
     * @return array<string, array<string, mixed>>
     */
    private function analyzePackages(array $rows): array
    {
        $grouped = [];

        foreach ($rows as $row) {
            if (! $row->paquete) {
                continue;
            }

            $grouped[$row->paquete]['prices'][] = $row->costo;
            $grouped[$row->paquete]['durations'][] = $row->duracionDias();
        }

        $result = [];
        foreach ($grouped as $package => $meta) {
            $priceDistribution = $this->frequencyDistribution(array_filter($meta['prices'], static fn ($value) => $value !== null));
            $durationDistribution = $this->frequencyDistribution(array_filter($meta['durations'], static fn ($value) => $value !== null));

            $result[$package] = [
                'precio_base' => $this->modalValue($priceDistribution, true),
                'duracion_dias' => $this->modalValue($durationDistribution, true),
                'price_distribution' => $priceDistribution,
                'duration_distribution' => $durationDistribution,
                'price_conflict' => count($priceDistribution) > 1,
                'duration_conflict' => count($durationDistribution) > 1,
            ];
        }

        ksort($result);

        return $result;
    }

    /**
     * @param  list<int|float>  $values
     * @return array<string, int>
     */
    private function frequencyDistribution(array $values): array
    {
        $distribution = [];
        foreach ($values as $value) {
            $key = (string) $value;
            $distribution[$key] = ($distribution[$key] ?? 0) + 1;
        }

        return $distribution;
    }

    private function modalValue(array $distribution, bool $preferHigherOnTie = false): int|float|null
    {
        if ($distribution === []) {
            return null;
        }

        $bestKey = null;
        $bestCount = -1;
        foreach ($distribution as $key => $count) {
            $numericKey = (float) $key;
            if ($count > $bestCount || ($preferHigherOnTie && $count === $bestCount && $numericKey > (float) $bestKey)) {
                $bestKey = $key;
                $bestCount = $count;
            }
        }

        if ($bestKey === null) {
            return null;
        }

        return str_contains((string) $bestKey, '.') ? (float) $bestKey : (int) $bestKey;
    }
}
