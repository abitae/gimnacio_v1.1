<?php

namespace App\Console\Commands;

use App\Services\Imports\DeudasClientesImportService;
use Illuminate\Console\Command;
use Throwable;

class ImportDeudasClientesCommand extends Command
{
    protected $signature = 'import:deudas-clientes
        {--file= : Ruta absoluta del archivo Excel}
        {--dry-run : Ejecuta solo validacion y resumen}
        {--execute : Ejecuta cambios persistentes}';

    protected $description = 'Importa deudas de clientes desde Excel ajustando matriculas y pagos.';

    public function handle(DeudasClientesImportService $service): int
    {
        $file = (string) $this->option('file');
        $execute = (bool) $this->option('execute');
        $dryRun = (bool) $this->option('dry-run');

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
            $report = $service->run($file, $execute);
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf('Fase: deudas-clientes | Modo: %s', $execute ? 'execute' : 'dry-run'));
        foreach ($report as $key => $value) {
            if (is_scalar($value)) {
                $this->line(sprintf('%s: %s', $key, (string) $value));
            }
        }

        if (! empty($report['warnings'])) {
            $this->warn('Advertencias:');
            $this->table(array_keys($report['warnings'][0]), array_slice($report['warnings'], 0, 20));
        }

        if (! empty($report['report_paths'])) {
            $this->warn('Reportes generados:');
            foreach ($report['report_paths'] as $type => $path) {
                $this->line(sprintf('%s: %s', $type, $path));
            }
        }

        return self::SUCCESS;
    }
}
