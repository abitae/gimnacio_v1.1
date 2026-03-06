<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class TrainerSeeder extends Seeder
{
    /**
     * Crea usuarios con rol "trainer" (ya no existe la tabla trainers).
     */
    public function run(): void
    {
        // El rol 'trainer' se crea en RoleSeeder
        $users = [
            ['email' => 'trainer1@gimnasio.com', 'name' => 'Carlos Trainer'],
            ['email' => 'trainer2@gimnasio.com', 'name' => 'María Entrenadora'],
            ['email' => 'trainer3@gimnasio.com', 'name' => 'Juan Fitness'],
        ];

        foreach ($users as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => bcrypt('password'),
                    'estado' => 'activo',
                ]
            );
            if (! $user->hasRole('trainer')) {
                $user->assignRole('trainer');
            }
        }
    }
}
