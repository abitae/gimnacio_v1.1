<?php

namespace App\Console\Commands;

use App\Support\PermissionCatalog;
use App\Support\RolePermissionSynchronizer;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionsAuditCommand extends Command
{
    protected $signature = 'permissions:audit
                            {--roles : Verificar tambien roles y asignaciones del catalogo}
                            {--sync : Crear roles y permisos faltantes sin modificar asignaciones existentes}
                            {--reset : Con --sync, alinea roles existentes al catalogo (puede cambiar produccion)}
                            {--json : Salida en JSON}';

    protected $description = 'Sincroniza y verifica permisos y roles del catalogo en la base de datos';

    public function handle(): int
    {
        $guard = (string) config('auth.defaults.guard');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        if ($this->option('sync')) {
            $this->syncCatalog($guard, (bool) $this->option('reset'));
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }

        $expected = collect(PermissionCatalog::permissionNames())->sort()->values();
        $dbPermissions = Permission::query()
            ->where('guard_name', $guard)
            ->pluck('name')
            ->sort()
            ->values();

        $missing = $expected->diff($dbPermissions)->values();
        $legacyNames = collect(PermissionCatalog::legacyPermissionNames())
            ->merge(['manage-users', 'manage-roles'])
            ->unique()
            ->values();
        $orphan = $dbPermissions
            ->diff($expected)
            ->diff($legacyNames)
            ->values();

        $roleReport = $this->option('roles') ? $this->auditRoles($guard) : null;
        $roleGaps = collect($roleReport['role_permission_gaps'] ?? []);
        $missingRoles = collect($roleReport['missing_roles'] ?? []);

        $hasIssues = $missing->isNotEmpty()
            || ($roleReport !== null && (! $missingRoles->isEmpty() || $roleGaps->isNotEmpty()));

        if ($this->option('json')) {
            $this->line(json_encode([
                'guard' => $guard,
                'expected_count' => $expected->count(),
                'database_count' => $dbPermissions->count(),
                'missing' => $missing->all(),
                'orphan' => $orphan->all(),
                'roles' => $roleReport,
                'ok' => ! $hasIssues,
                'warnings' => $orphan->isNotEmpty(),
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return $hasIssues ? self::FAILURE : self::SUCCESS;
        }

        $this->info('Auditoria de permisos — guard: '.$guard);
        $this->line('Catalogo: '.$expected->count().' | Base de datos: '.$dbPermissions->count());

        if ($missing->isEmpty()) {
            $this->components->info('Todos los permisos del catalogo existen en la BD.');
        } else {
            $this->components->error('Permisos faltantes en la BD ('.$missing->count().'):');
            foreach ($missing as $name) {
                $this->line('  - '.$name);
            }
        }

        if ($orphan->isEmpty()) {
            $this->components->info('No hay permisos huerfanos fuera del catalogo (excl. legacy).');
        } else {
            $this->components->warn('Permisos en BD no definidos en PermissionCatalog ('.$orphan->count().'):');
            foreach ($orphan as $name) {
                $this->line('  - '.$name);
            }
        }

        if ($roleReport !== null) {
            $this->newLine();
            $this->info('Roles del catalogo');

            if ($missingRoles->isEmpty()) {
                $this->components->info('Todos los roles del catalogo existen en la BD.');
            } else {
                $this->components->error('Roles faltantes ('.$missingRoles->count().'):');
                foreach ($missingRoles as $roleName) {
                    $this->line('  - '.$roleName);
                }
            }

            if ($roleGaps->isEmpty()) {
                $this->components->info('Las asignaciones de permisos por rol coinciden con el catalogo.');
            } else {
                $this->components->warn('Roles con permisos distintos al catalogo:');
                foreach ($roleGaps as $gap) {
                    $gapMissing = collect($gap['missing']);
                    $gapExtra = collect($gap['extra']);
                    $this->line('  - '.$gap['role']);
                    if ($gapMissing->isNotEmpty()) {
                        $this->line('      faltan: '.$gapMissing->implode(', '));
                    }
                    if ($gapExtra->isNotEmpty()) {
                        $this->line('      sobran: '.$gapExtra->implode(', '));
                    }
                }
            }
        }

        if ($hasIssues && ! $this->option('sync')) {
            $this->newLine();
            $this->comment('Sugerencia: php artisan permissions:sync crea lo faltante sin cambiar roles existentes.');
        }

        return $hasIssues ? self::FAILURE : self::SUCCESS;
    }

    private function syncCatalog(string $guard, bool $reset = false): void
    {
        $this->info($reset
            ? 'Sincronizando desde PermissionCatalog (reset de roles existentes)...'
            : 'Creando roles y permisos faltantes (sin modificar asignaciones actuales)...');

        $result = RolePermissionSynchronizer::sync($guard, reset: $reset);

        $this->components->info(
            'Revisados '.$result['permissions'].' permisos y '.$result['roles'].' roles del catalogo.'
        );

        if ($result['created_roles'] !== []) {
            $this->components->info('Roles creados: '.implode(', ', $result['created_roles']));
        }
    }

    /**
     * @return array{
     *     missing_roles: \Illuminate\Support\Collection<int, string>,
     *     role_permission_gaps: list<array{role: string, missing: \Illuminate\Support\Collection, extra: \Illuminate\Support\Collection}>
     * }
     */
    private function auditRoles(string $guard): array
    {
        $definitions = PermissionCatalog::roleDefinitions();
        $missingRoles = collect();
        $gaps = [];

        foreach ($definitions as $roleName => $definition) {
            $role = Role::query()
                ->where('name', $roleName)
                ->where('guard_name', $guard)
                ->first();

            if ($role === null) {
                $missingRoles->push($roleName);

                continue;
            }

            $expected = collect($definition['permissions'])->sort()->values();
            $assigned = $role->permissions()->pluck('name')->sort()->values();

            $missing = $expected->diff($assigned)->values();
            $extra = $assigned->diff($expected)->values();

            if ($missing->isNotEmpty() || $extra->isNotEmpty()) {
                $gaps[] = [
                    'role' => $roleName,
                    'missing' => $missing,
                    'extra' => $extra,
                ];
            }
        }

        return [
            'missing_roles' => $missingRoles->values()->all(),
            'role_permission_gaps' => $gaps,
        ];
    }
}
