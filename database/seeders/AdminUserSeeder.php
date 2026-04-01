<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\PermissionCatalog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public const ADMIN_EMAIL = 'abel.arana@hotmail.com';

    public function run(): void
    {
        $user = User::query()->firstOrCreate(
            ['email' => self::ADMIN_EMAIL],
            [
                'name' => 'Administrador',
                'password' => Hash::make("lobomalo123"),
                'email_verified_at' => now(),
                'estado' => 'activo',
            ]
        );

        if (! $user->hasRole(PermissionCatalog::SUPER_ADMIN_ROLE_NAME)) {
            $user->assignRole(PermissionCatalog::SUPER_ADMIN_ROLE_NAME);
        }
    }

    
}
