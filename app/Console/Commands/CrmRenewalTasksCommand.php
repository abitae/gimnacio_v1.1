<?php

namespace App\Console\Commands;

use App\Services\Crm\RenewalReactivationService;
use Illuminate\Console\Command;

class CrmRenewalTasksCommand extends Command
{
    protected $signature = 'crm:renewal-tasks {--days=7 : Días antes del vencimiento para generar tareas}';

    protected $description = 'Genera tareas CRM de renovación para clientes con membresía por vencer';

    public function handle(RenewalReactivationService $service): int
    {
        $days = (int) $this->option('days');
        if (!in_array($days, [1, 3, 7], true)) {
            $days = 7;
        }
        $count = $service->generateRenewalTasks($days);
        $this->info("Tareas de renovación generadas: {$count} (próximos {$days} días)");
        return self::SUCCESS;
    }
}
