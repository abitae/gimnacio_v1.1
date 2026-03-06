<?php

namespace Database\Seeders;

use App\Models\Core\Cita;
use App\Models\Core\Cliente;
use App\Models\Core\SeguimientoNutricion;
use App\Models\User;
use Illuminate\Database\Seeder;

class SeguimientoNutricionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Depende de: Cliente, Cita (opcional), User (nutricionista).
     */
    public function run(): void
    {
        $clientes = Cliente::limit(5)->get();
        if ($clientes->isEmpty()) {
            $this->command->warn('No hay clientes. Ejecuta ClienteSeeder primero.');
            return;
        }

        $nutricionista = User::where('email', 'nutricionista@gimnasio.com')->first();
        $citas = Cita::where('estado', 'completada')->limit(3)->get();

        // Plan inicial para el primer cliente
        SeguimientoNutricion::firstOrCreate(
            [
                'cliente_id' => $clientes[0]->id,
                'tipo' => 'plan_inicial',
                'fecha' => now()->subDays(30),
            ],
            [
                'nutricionista_id' => $nutricionista?->id,
                'cita_id' => null,
                'objetivo' => 'Pérdida de peso y tonificación',
                'calorias_objetivo' => 1800,
                'macros' => ['proteina' => 120, 'grasa' => 60, 'carbohidratos' => 180],
                'contenido' => 'Plan inicial: 5 comidas al día, hidratación 2L, evitar azúcares refinados. Revisión en 15 días.',
                'estado' => 'activo',
            ]
        );

        // Seguimiento vinculado a cita (si hay citas completadas)
        if ($citas->isNotEmpty() && $clientes->count() > 0) {
            SeguimientoNutricion::firstOrCreate(
                [
                    'cliente_id' => $citas[0]->cliente_id,
                    'tipo' => 'seguimiento',
                    'fecha' => $citas[0]->fecha_hora->toDateString(),
                ],
                [
                    'nutricionista_id' => $citas[0]->nutricionista_id,
                    'cita_id' => $citas[0]->id,
                    'objetivo' => 'Seguimiento mensual',
                    'calorias_objetivo' => null,
                    'macros' => null,
                    'contenido' => 'Cliente en buen progreso. Ajustar porciones según apetito. Mantener proteína alta.',
                    'estado' => 'activo',
                ]
            );
        }

        // Recomendación para segundo cliente
        if ($clientes->count() > 1) {
            SeguimientoNutricion::firstOrCreate(
                [
                    'cliente_id' => $clientes[1]->id,
                    'tipo' => 'recomendacion',
                    'fecha' => now()->subDays(7),
                ],
                [
                    'nutricionista_id' => $nutricionista?->id,
                    'cita_id' => null,
                    'objetivo' => null,
                    'calorias_objetivo' => null,
                    'macros' => null,
                    'contenido' => 'Aumentar consumo de verduras en almuerzo y cena. Incluir fruta en desayuno.',
                    'estado' => 'activo',
                ]
            );
        }

        $this->command->info('Seguimientos de nutrición creados.');
    }
}
