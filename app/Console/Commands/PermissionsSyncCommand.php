<?php

namespace App\Console\Commands;

use App\Support\RolePermissionSynchronizer;
use Illuminate\Console\Command;

class PermissionsSyncCommand extends Command
{
    protected $signature = 'permissions:sync';

    protected $description = 'Sincroniza permisos y roles desde PermissionCatalog (Spatie)';

    public function handle(): int
    {
        $guard = (string) config('auth.defaults.guard');

        $this->info('Sincronizando permisos y roles — guard: '.$guard);

        $result = RolePermissionSynchronizer::sync($guard);

        $this->components->info(
            'Listo: '.$result['permissions'].' permisos y '.$result['roles'].' roles sincronizados.'
        );

        return self::SUCCESS;
    }
}
