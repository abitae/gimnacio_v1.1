<?php

namespace App\Console\Commands;

use App\Support\RolePermissionSynchronizer;
use Illuminate\Console\Command;

class PermissionsSyncCommand extends Command
{
    protected $signature = 'permissions:sync
                            {--reset : Sobrescribe permisos de roles existentes con el catálogo. No usar en producción si hay ajustes manuales.}';

    protected $description = 'Crea roles y permisos faltantes del catálogo sin modificar la configuración actual';

    public function handle(): int
    {
        $guard = (string) config('auth.defaults.guard');
        $reset = (bool) $this->option('reset');

        if ($reset) {
            $this->warn('Modo --reset: se alinearán los roles existentes al catálogo (puede cambiar permisos actuales).');
        } else {
            $this->info('Modo seguro: no se modifican roles ni permisos ya configurados. Guard: '.$guard);
        }

        $result = RolePermissionSynchronizer::sync($guard, reset: $reset);

        $this->components->info(
            'Listo: '.$result['permissions'].' permisos del catálogo y '.$result['roles'].' roles revisados.'
        );

        if ($result['created_roles'] !== []) {
            $this->components->info('Roles creados: '.implode(', ', $result['created_roles']));
        } else {
            $this->line('No se creó ningún rol nuevo.');
        }

        return self::SUCCESS;
    }
}
