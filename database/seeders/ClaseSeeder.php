<?php

namespace Database\Seeders;

use App\Models\Core\Clase;
use App\Models\User;
use Illuminate\Database\Seeder;

class ClaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener usuarios para asignar como instructores
        $instructores = User::limit(3)->get();
        $instructor1 = $instructores->get(0);
        $instructor2 = $instructores->get(1);
        $instructor3 = $instructores->get(2);

        $clases = [
            // Clases por sesión
            [
                'codigo' => 'CLA-001',
                'nombre' => 'Yoga',
                'descripcion' => 'Clase de yoga para flexibilidad y relajación',
                'tipo' => 'sesion',
                'precio_sesion' => 25.00,
                'precio_paquete' => null,
                'sesiones_paquete' => null,
                'instructor_id' => $instructor1?->id,
                'estado' => 'activo',
            ],
            [
                'codigo' => 'CLA-002',
                'nombre' => 'Spinning',
                'descripcion' => 'Clase de ciclismo indoor de alta intensidad',
                'tipo' => 'sesion',
                'precio_sesion' => 30.00,
                'precio_paquete' => null,
                'sesiones_paquete' => null,
                'instructor_id' => $instructor2?->id,
                'estado' => 'activo',
            ],
            [
                'codigo' => 'CLA-003',
                'nombre' => 'Zumba',
                'descripcion' => 'Clase de baile fitness',
                'tipo' => 'sesion',
                'precio_sesion' => 20.00,
                'precio_paquete' => null,
                'sesiones_paquete' => null,
                'instructor_id' => $instructor3?->id,
                'estado' => 'activo',
            ],
            [
                'codigo' => 'CLA-004',
                'nombre' => 'CrossFit',
                'descripcion' => 'Entrenamiento funcional de alta intensidad',
                'tipo' => 'sesion',
                'precio_sesion' => 35.00,
                'precio_paquete' => null,
                'sesiones_paquete' => null,
                'instructor_id' => $instructor1?->id,
                'estado' => 'activo',
            ],
            [
                'codigo' => 'CLA-005',
                'nombre' => 'Pilates',
                'descripcion' => 'Clase de pilates para fortalecimiento y flexibilidad',
                'tipo' => 'sesion',
                'precio_sesion' => 28.00,
                'precio_paquete' => null,
                'sesiones_paquete' => null,
                'instructor_id' => $instructor2?->id,
                'estado' => 'activo',
            ],
            // Clases por paquete
            [
                'codigo' => 'CLA-006',
                'nombre' => 'Yoga - Paquete 10 Sesiones',
                'descripcion' => 'Paquete de 10 sesiones de yoga con descuento',
                'tipo' => 'paquete',
                'precio_sesion' => null,
                'precio_paquete' => 200.00,
                'sesiones_paquete' => 10,
                'instructor_id' => $instructor1?->id,
                'estado' => 'activo',
            ],
            [
                'codigo' => 'CLA-007',
                'nombre' => 'Spinning - Paquete 8 Sesiones',
                'descripcion' => 'Paquete de 8 sesiones de spinning',
                'tipo' => 'paquete',
                'precio_sesion' => null,
                'precio_paquete' => 200.00,
                'sesiones_paquete' => 8,
                'instructor_id' => $instructor2?->id,
                'estado' => 'activo',
            ],
            [
                'codigo' => 'CLA-008',
                'nombre' => 'CrossFit - Paquete 12 Sesiones',
                'descripcion' => 'Paquete de 12 sesiones de CrossFit',
                'tipo' => 'paquete',
                'precio_sesion' => null,
                'precio_paquete' => 360.00,
                'sesiones_paquete' => 12,
                'instructor_id' => $instructor1?->id,
                'estado' => 'activo',
            ],
        ];

        foreach ($clases as $clase) {
            Clase::create($clase);
        }
    }
}
