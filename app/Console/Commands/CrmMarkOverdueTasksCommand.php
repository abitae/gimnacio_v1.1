<?php

namespace App\Console\Commands;

use App\Services\Crm\TaskSchedulerService;
use Illuminate\Console\Command;

class CrmMarkOverdueTasksCommand extends Command
{
    protected $signature = 'crm:mark-overdue-tasks';

    protected $description = 'Marca las tareas CRM pendientes con fecha pasada como vencidas';

    public function handle(TaskSchedulerService $service): int
    {
        $count = $service->markOverdueTasks();
        $this->info("Tareas marcadas como vencidas: {$count}");
        return self::SUCCESS;
    }
}
