<?php

namespace App\Services\Imports;

use App\DataTransferObjects\Imports\SocioActivoRowData;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class SellerUserResolver
{
    private const FALLBACK_EMAIL = 'import.fallback@local.test';

    /** @var Collection<int, User>|null */
    private static ?Collection $usersForNameMatchCache = null;

    /**
     * @param  list<SocioActivoRowData>  $rows
     * @return array<string, mixed>
     */
    public function syncUsers(array $rows, bool $execute = false, ?int $sucursalId = null): array
    {
        $identities = $this->collectIdentities($rows);
        $report = [
            'phase' => 'users',
            'dry_run' => ! $execute,
            'detected_users' => count($identities),
            'created' => 0,
            'existing' => 0,
            'ignored' => 0,
            'users' => [],
        ];

        $report['users'][] = [
            'name' => 'Importación Excel',
            'action' => $execute ? (User::query()->where('email', self::FALLBACK_EMAIL)->exists() ? 'existing_fallback' : 'created_fallback') : 'would_create_fallback',
        ];

        if ($execute) {
            $this->fallbackUser();
        }

        foreach ($identities as $normalized => $displayName) {
            if ($this->isIgnoredName($normalized)) {
                $report['ignored']++;

                continue;
            }

            $existing = $this->findExistingUser($normalized);
            if ($existing) {
                if ($execute) {
                    $this->ensureConfiguredSellerRole($existing);
                    $this->attachSucursal($existing, $sucursalId);
                }
                $report['existing']++;
                $report['users'][] = ['name' => $displayName, 'action' => 'existing'];

                continue;
            }

            if ($execute) {
                $user = User::firstOrCreate(
                    ['email' => $this->syntheticEmail($normalized)],
                    [
                        'name' => $displayName,
                        'password' => $this->importedSellerPlainPassword(),
                        'estado' => 'activo',
                        'email_verified_at' => now(),
                    ]
                );
                $this->ensureConfiguredSellerRole($user);
                $this->attachSucursal($user, $sucursalId);
                self::$usersForNameMatchCache = null;
            }

            $report['created']++;
            $report['users'][] = ['name' => $displayName, 'action' => $execute ? 'created' : 'would_create'];
        }

        return $report;
    }

    /**
     * @param  list<array{fila: int, nombre: string}>  $entries
     * @return array{created: bool, existing: bool, user_id: ?int}
     */
    public function syncSingleVendedorEntry(string $displayName, int $sucursalId, bool $execute): array
    {
        $normalized = SocioActivoRowData::normalizeComparable($displayName);
        if ($this->isIgnoredName($normalized)) {
            return ['created' => false, 'existing' => false, 'user_id' => null];
        }

        $existing = $this->findExistingUser($normalized);
        if ($existing) {
            if ($execute) {
                $this->ensureConfiguredSellerRole($existing);
                $this->attachSucursal($existing, $sucursalId);
                self::$usersForNameMatchCache = null;
            }

            return ['created' => false, 'existing' => true, 'user_id' => $existing->id];
        }

        if (! $execute) {
            return ['created' => false, 'existing' => false, 'user_id' => null];
        }

        $user = User::query()->create([
            'name' => trim($displayName),
            'email' => $this->syntheticEmail($normalized),
            'password' => $this->importedSellerPlainPassword(),
            'estado' => 'activo',
            'email_verified_at' => now(),
            'default_sucursal_id' => $sucursalId > 0 ? $sucursalId : null,
        ]);
        $this->ensureConfiguredSellerRole($user);
        $this->attachSucursal($user, $sucursalId);
        self::$usersForNameMatchCache = null;

        return ['created' => true, 'existing' => false, 'user_id' => $user->id];
    }

    public function resolveOrFallback(?string $displayName): User
    {
        $normalized = SocioActivoRowData::normalizeComparable($displayName);
        if ($this->isIgnoredName($normalized)) {
            return $this->fallbackUser();
        }

        return $this->findExistingUser($normalized) ?? $this->createUser($displayName ?: 'Usuario importado');
    }

    public function fallbackUser(): User
    {
        $user = User::firstOrCreate(
            ['email' => self::FALLBACK_EMAIL],
            [
                'name' => 'Importación Excel',
                'password' => Str::random(32),
                'estado' => 'activo',
                'email_verified_at' => now(),
            ]
        );

        $this->ensureConfiguredSellerRole($user);
        self::$usersForNameMatchCache = null;

        return $user;
    }

    /**
     * @param  list<SocioActivoRowData>  $rows
     * @return array<string, string>
     */
    private function collectIdentities(array $rows): array
    {
        $frequency = [];

        foreach ($rows as $row) {
            foreach ([$row->vendedor, $row->repartido] as $candidate) {
                $normalized = SocioActivoRowData::normalizeComparable($candidate);
                if ($this->isIgnoredName($normalized)) {
                    continue;
                }

                $displayName = trim((string) $candidate);
                $frequency[$normalized][$displayName] = ($frequency[$normalized][$displayName] ?? 0) + 1;
            }
        }

        $result = [];
        foreach ($frequency as $normalized => $variants) {
            arsort($variants);
            $result[$normalized] = array_key_first($variants) ?: $normalized;
        }

        ksort($result);

        return $result;
    }

    private function findExistingUser(string $normalizedName): ?User
    {
        if ($normalizedName === '') {
            return null;
        }

        $bySyntheticEmail = User::query()->where('email', $this->syntheticEmail($normalizedName))->first();
        if ($bySyntheticEmail) {
            return $bySyntheticEmail;
        }

        return $this->usersForNameMatch()
            ->first(fn (User $user) => SocioActivoRowData::normalizeComparable($user->name) === $normalizedName);
    }

    /**
     * @return Collection<int, User>
     */
    private function usersForNameMatch(): Collection
    {
        return self::$usersForNameMatchCache ??= User::query()
            ->select(['id', 'name', 'email'])
            ->get();
    }

    private function createUser(string $displayName): User
    {
        $normalized = SocioActivoRowData::normalizeComparable($displayName);
        $user = User::firstOrCreate(
            ['email' => $this->syntheticEmail($normalized)],
            [
                'name' => trim($displayName),
                'password' => $this->importedSellerPlainPassword(),
                'estado' => 'activo',
                'email_verified_at' => now(),
            ]
        );

        $this->ensureConfiguredSellerRole($user);
        self::$usersForNameMatchCache = null;

        return $user;
    }

    private function syntheticEmail(string $normalizedName): string
    {
        $slug = Str::slug($normalizedName, '.');
        if ($slug === '') {
            $slug = 'usuario';
        }

        $domain = (string) config('importacion.seller_email_domain', 'empresa.test');

        return sprintf('%s.%s@%s', $slug, substr(md5($normalizedName), 0, 8), $domain);
    }

    private function importedSellerPlainPassword(): string
    {
        return (string) config('importacion.default_import_user_password', 'user123');
    }

    private function attachSucursal(User $user, ?int $sucursalId): void
    {
        if ($sucursalId === null || $sucursalId <= 0) {
            return;
        }

        $user->sucursales()->syncWithoutDetaching([$sucursalId]);
        if (! $user->default_sucursal_id) {
            $user->forceFill(['default_sucursal_id' => $sucursalId])->save();
        }
    }

    private function ensureConfiguredSellerRole(User $user): void
    {
        $roleName = (string) config('importacion.seller_role', 'vendedor');
        $role = Role::firstOrCreate([
            'name' => $roleName,
            'guard_name' => config('auth.defaults.guard'),
        ]);

        if (! $user->hasRole($role->name)) {
            $user->assignRole($role);
        }
    }

    private function isIgnoredName(string $normalized): bool
    {
        return $normalized === '' || in_array($normalized, ['sin vendedor', 'n/a', '-', '--'], true);
    }
}
