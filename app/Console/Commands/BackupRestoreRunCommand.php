<?php

namespace App\Console\Commands;

use App\Services\System\DatabaseRestoreService;
use Illuminate\Console\Command;
use Throwable;

class BackupRestoreRunCommand extends Command
{
    protected $signature = 'backup:restore-run {--id= : Identificador interno de restauracion}';

    protected $description = 'Ejecuta una restauracion de base de datos en segundo plano.';

    public function handle(DatabaseRestoreService $restoreService): int
    {
        $id = (string) $this->option('id');
        if ($id === '') {
            $this->error('Debes indicar un id de restauracion.');

            return self::FAILURE;
        }

        try {
            $restoreService->runRestore($id);
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
