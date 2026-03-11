<?php

namespace Database\Seeders;

use App\Models\Core\Cliente;
use App\Models\Core\HealthRecord;
use App\Models\User;
use Illuminate\Database\Seeder;

class HealthRecordSeeder extends Seeder
{
    public function run(): void
    {
        $clientes = Cliente::limit(5)->get();
        if ($clientes->isEmpty()) {
            $this->command->warn('No hay clientes. HealthRecordSeeder se omite.');
            return;
        }

        $user = User::first();
        $actualizadoPor = $user?->id;

        $ejemplos = [
            [
                'enfermedades' => 'Ninguna',
                'alergias' => 'Polen',
                'medicacion' => 'Ninguna',
                'restricciones_medicas' => null,
                'lesiones' => 'Rodilla derecha (recuperación)',
                'observaciones' => 'Evitar impacto fuerte en rodilla.',
            ],
            [
                'enfermedades' => null,
                'alergias' => 'Ninguna',
                'medicacion' => null,
                'restricciones_medicas' => 'Sin restricciones',
                'lesiones' => null,
                'observaciones' => null,
            ],
            [
                'enfermedades' => 'Asma leve',
                'alergias' => 'Ácaros',
                'medicacion' => 'Inhalador de rescate',
                'restricciones_medicas' => 'Evitar ejercicio intenso en ambientes cerrados sin ventilación.',
                'lesiones' => null,
                'observaciones' => 'Controlado con medicación.',
            ],
        ];

        foreach ($clientes as $i => $cliente) {
            if (HealthRecord::where('cliente_id', $cliente->id)->exists()) {
                continue;
            }
            $datos = $ejemplos[$i % count($ejemplos)];
            HealthRecord::create([
                'cliente_id' => $cliente->id,
                'enfermedades' => $datos['enfermedades'],
                'alergias' => $datos['alergias'],
                'medicacion' => $datos['medicacion'],
                'restricciones_medicas' => $datos['restricciones_medicas'],
                'lesiones' => $datos['lesiones'],
                'observaciones' => $datos['observaciones'],
                'actualizado_por' => $actualizadoPor,
            ]);
        }
    }
}
