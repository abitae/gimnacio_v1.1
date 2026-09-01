<?php

namespace App\Console\Commands;

use App\Models\Core\Cliente;
use Illuminate\Console\Command;

class CrmBackfillAsesorCommand extends Command
{
    protected $signature = 'crm:backfill-asesor {--dry-run : No guarda cambios, solo muestra cuántos clientes se actualizarían}';

    protected $description = 'Backfillea clientes.asesor_crm_id desde la matrícula/membresía más reciente, con fallback a created_by';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $count = 0;

        Cliente::query()
            ->whereNull('asesor_crm_id')
            ->with(['matriculaMembresiaReciente', 'ultimaMatricula'])
            ->chunkById(200, function ($clientes) use (&$count, $dryRun) {
                foreach ($clientes as $cliente) {
                    $asesorId = $this->resolveAsesorId($cliente);
                    if ($asesorId === null) {
                        continue;
                    }

                    if (! $dryRun) {
                        $cliente->forceFill(['asesor_crm_id' => $asesorId])->saveQuietly();
                    }
                    $count++;
                }
            });

        $prefix = $dryRun ? '[dry-run] ' : '';
        $this->info("{$prefix}Clientes actualizados: {$count}");

        return self::SUCCESS;
    }

    private function resolveAsesorId(Cliente $cliente): ?int
    {
        return $cliente->matriculaMembresiaReciente?->asesor_id
            ?? $cliente->ultimaMatricula?->asesor_id
            ?? $cliente->created_by;
    }
}
