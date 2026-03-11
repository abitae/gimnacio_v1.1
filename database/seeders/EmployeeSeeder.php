<?php

namespace Database\Seeders;

use App\Models\Core\Employee;
use App\Models\User;
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();
        if (! $user) {
            $this->command->warn('No hay usuarios. EmployeeSeeder se omite.');
            return;
        }

        $employees = [
            [
                'user_id' => $user->id,
                'nombres' => $user->name,
                'apellidos' => 'Admin',
                'documento' => '00000000',
                'cargo' => 'Administrador',
                'area' => 'General',
                'telefono' => null,
                'fecha_ingreso' => now()->subYears(2)->toDateString(),
                'estado' => 'activo',
            ],
            [
                'user_id' => null,
                'nombres' => 'María',
                'apellidos' => 'García López',
                'documento' => '12345678',
                'cargo' => 'Recepcionista',
                'area' => 'Atención al cliente',
                'telefono' => '987654321',
                'fecha_ingreso' => now()->subMonths(6)->toDateString(),
                'estado' => 'activo',
            ],
            [
                'user_id' => null,
                'nombres' => 'Carlos',
                'apellidos' => 'Rodríguez',
                'documento' => '87654321',
                'cargo' => 'Mantenimiento',
                'area' => 'Operaciones',
                'telefono' => null,
                'fecha_ingreso' => now()->subYear()->toDateString(),
                'estado' => 'activo',
            ],
        ];

        foreach ($employees as $index => $data) {
            if ($index === 0) {
                Employee::firstOrCreate(
                    ['user_id' => $user->id],
                    $data
                );
            } else {
                Employee::firstOrCreate(
                    ['documento' => $data['documento']],
                    $data
                );
            }
        }
    }
}
