<?php

namespace Database\Seeders;

use App\Models\Core\RentableSpace;
use Illuminate\Database\Seeder;

class RentableSpaceSeeder extends Seeder
{
    public function run(): void
    {
        $spaces = [
            [
                'nombre' => 'Cancha de fútbol',
                'descripcion' => 'Cancha de fútbol 7 con césped sintético',
                'capacidad' => 14,
                'estado' => 'activo',
                'color_calendario' => '#10B981',
            ],
            [
                'nombre' => 'Salón funcional',
                'descripcion' => 'Espacio para entrenamiento funcional y clases grupales',
                'capacidad' => 20,
                'estado' => 'activo',
                'color_calendario' => '#3B82F6',
            ],
            [
                'nombre' => 'Cancha de vóley',
                'descripcion' => 'Cancha de vóley techada',
                'capacidad' => 12,
                'estado' => 'activo',
                'color_calendario' => '#F59E0B',
            ],
            [
                'nombre' => 'Sala de reuniones',
                'descripcion' => 'Sala para reuniones o talleres',
                'capacidad' => 15,
                'estado' => 'activo',
                'color_calendario' => '#8B5CF6',
            ],
        ];

        foreach ($spaces as $data) {
            RentableSpace::firstOrCreate(
                ['nombre' => $data['nombre']],
                $data
            );
        }
    }
}
