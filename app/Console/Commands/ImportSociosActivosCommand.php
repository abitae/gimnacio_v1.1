<?php

namespace App\Console\Commands;

use App\Services\Imports\SociosActivosImportService;
use Illuminate\Console\Command;
use Throwable;

class ImportSociosActivosCommand extends Command
{
    protected $signature = 'import:socios-activos
        {--phase= : membresias|users|clients}
        {--file= : Ruta absoluta del archivo Excel}
        {--dry-run : Ejecuta solo validación y resumen}
        {--execute : Ejecuta cambios persistentes}';

    protected $description = 'Importa socios activos desde Excel en fases controladas.';

    public function handle(SociosActivosImportService $service): int
    {
        $phase = (string) $this->option('phase');
        $file = (string) $this->option('file');
        $execute = (bool) $this->option('execute');
        $dryRun = (bool) $this->option('dry-run');

        if ($phase === '' || ! in_array($phase, ['membresias', 'users', 'clients'], true)) {
            $this->error('Debes indicar una fase válida con --phase=membresias|users|clients.');

            return self::FAILURE;
        }

        if ($file === '') {
            $this->error('Debes indicar la ruta del archivo con --file=ABSOLUTE_PATH.');

            return self::FAILURE;
        }

        if ($execute && $dryRun) {
            $this->error('No puedes usar --dry-run y --execute al mismo tiempo.');

            return self::FAILURE;
        }

        $execute = $execute && ! $dryRun;

        try {
            $report = $service->run($phase, $file, $execute);
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf('Fase: %s | Modo: %s', $phase, $execute ? 'execute' : 'dry-run'));
        foreach ($report as $key => $value) {
            if (is_scalar($value)) {
                $this->line(sprintf('%s: %s', $key, (string) $value));
            }
        }

        if (! empty($report['conflicts'])) {
            $this->warn('Conflictos detectados:');
            $this->table(
                ['Paquete', 'Precios', 'Duraciones'],
                array_map(
                    fn (array $item) => [
                        $item['package'] ?? '',
                        implode(', ', $item['prices'] ?? []),
                        implode(', ', $item['durations'] ?? []),
                    ],
                    $report['conflicts']
                )
            );
        }

        if (! empty($report['errors'])) {
            $this->warn('Errores:');
            $this->table(array_keys($report['errors'][0]), $report['errors']);
        }

        if (! empty($report['rejected'])) {
            $this->warn('Registros omitidos/rechazados:');
            $this->table(array_keys($report['rejected'][0]), array_slice($report['rejected'], 0, 20));
        }

        return self::SUCCESS;
    }
}
