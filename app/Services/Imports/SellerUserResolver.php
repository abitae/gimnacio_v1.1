<?php

namespace App\Services\Imports;

use App\DataTransferObjects\Imports\SocioActivoRowData;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class SellerUserResolver
{
    private const FALLBACK_EMAIL = 'import.fallback@local.test';

    /**
     * @param  list<SocioActivoRowData>  $rows
     * @return array<string, mixed>
     */
    public function syncUsers(array $rows, bool $execute = false): array
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
                    $this->ensureVendedorRole($existing);
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
                        'password' => Hash::make(Str::random(32)),
                        'estado' => 'activo',
                        'email_verified_at' => now(),
                    ]
                );
                $this->ensureVendedorRole($user);
            }

            $report['created']++;
            $report['users'][] = ['name' => $displayName, 'action' => $execute ? 'created' : 'would_create'];
        }

        return $report;
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
                'password' => Hash::make(Str::random(32)),
                'estado' => 'activo',
                'email_verified_at' => now(),
            ]
        );

        $this->ensureVendedorRole($user);

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

        return User::query()
            ->get()
            ->first(fn (User $user) => SocioActivoRowData::normalizeComparable($user->name) === $normalizedName);
    }

    private function createUser(string $displayName): User
    {
        $normalized = SocioActivoRowData::normalizeComparable($displayName);
        $user = User::firstOrCreate(
            ['email' => $this->syntheticEmail($normalized)],
            [
                'name' => trim($displayName),
                'password' => Hash::make(Str::random(32)),
                'estado' => 'activo',
                'email_verified_at' => now(),
            ]
        );

        $this->ensureVendedorRole($user);

        return $user;
    }

    private function syntheticEmail(string $normalizedName): string
    {
        $slug = Str::slug($normalizedName, '.');
        if ($slug === '') {
            $slug = 'usuario';
        }

        return sprintf('import.%s.%s@local.test', $slug, substr(md5($normalizedName), 0, 8));
    }

    private function ensureVendedorRole(User $user): void
    {
        $role = Role::firstOrCreate([
            'name' => 'vendedor',
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
