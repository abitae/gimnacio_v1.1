<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\PermissionCatalog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public const ADMIN_EMAIL = 'abel.arana@hotmail.com';
    public const ADMIN_NAME = 'Administrador';
    public const ADMIN_PASSWORD = 'lobomalo123';

    public function run(): void
    {
        $this->upsertAdmin();
    }

    public function upsertAdmin(): User
    {
        $user = User::query()->firstOrNew(['email' => self::ADMIN_EMAIL]);

        $user->forceFill([
            'name' => self::ADMIN_NAME,
            'email' => self::ADMIN_EMAIL,
            'password' => Hash::make(self::ADMIN_PASSWORD),
            'email_verified_at' => $user->email_verified_at ?? now(),
            'estado' => 'activo',
        ]);
        $user->save();

        if (! $user->hasRole(PermissionCatalog::SUPER_ADMIN_ROLE_NAME)) {
            $user->assignRole(PermissionCatalog::SUPER_ADMIN_ROLE_NAME);
        }

        return $user->fresh();
    }
}
