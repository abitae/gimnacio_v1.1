<?php

namespace Database\Seeders;

use App\Models\Core\Membresia;
use Illuminate\Database\Seeder;

class MembresiaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $membresias = [
            [
                'nombre' => 'Mensual Básica',
                'descripcion' => 'Acceso ilimitado al gimnasio durante 30 días',
                'duracion_dias' => 30,
                'precio_base' => 150.00,
                'permite_cuotas' => true,
                'numero_cuotas_default' => 12,
                'frecuencia_cuotas_default' => 'mensual',
                'cuota_inicial_monto' => null,
                'cuota_inicial_porcentaje' => 20.00,
                'tipo_acceso' => 'ilimitado',
                'max_visitas_dia' => null,
                'permite_congelacion' => false,
                'max_dias_congelacion' => null,
                'estado' => 'activa',
            ],
            [
                'nombre' => 'Mensual Premium',
                'descripcion' => 'Acceso ilimitado + clases grupales durante 30 días',
                'duracion_dias' => 30,
                'precio_base' => 200.00,
                'permite_cuotas' => true,
                'numero_cuotas_default' => 12,
                'frecuencia_cuotas_default' => 'mensual',
                'cuota_inicial_monto' => null,
                'cuota_inicial_porcentaje' => 15.00,
                'tipo_acceso' => 'ilimitado',
                'max_visitas_dia' => null,
                'permite_congelacion' => true,
                'max_dias_congelacion' => 7,
                'estado' => 'activa',
            ],
            [
                'nombre' => 'Trimestral',
                'descripcion' => 'Acceso ilimitado durante 90 días con descuento',
                'duracion_dias' => 90,
                'precio_base' => 400.00,
                'permite_cuotas' => true,
                'numero_cuotas_default' => 6,
                'frecuencia_cuotas_default' => 'mensual',
                'cuota_inicial_monto' => 50.00,
                'cuota_inicial_porcentaje' => null,
                'tipo_acceso' => 'ilimitado',
                'max_visitas_dia' => null,
                'permite_congelacion' => true,
                'max_dias_congelacion' => 15,
                'estado' => 'activa',
            ],
            [
                'nombre' => 'Anual',
                'descripcion' => 'Acceso ilimitado durante 365 días con máximo descuento',
                'duracion_dias' => 365,
                'precio_base' => 1200.00,
                'permite_cuotas' => true,
                'numero_cuotas_default' => 12,
                'frecuencia_cuotas_default' => 'mensual',
                'cuota_inicial_monto' => null,
                'cuota_inicial_porcentaje' => 10.00,
                'tipo_acceso' => 'ilimitado',
                'max_visitas_dia' => null,
                'permite_congelacion' => true,
                'max_dias_congelacion' => 30,
                'estado' => 'activa',
            ],
            [
                'nombre' => 'Por Visitas (10)',
                'descripcion' => 'Paquete de 10 visitas sin fecha de vencimiento',
                'duracion_dias' => 365,
                'precio_base' => 80.00,
                'permite_cuotas' => false,
                'numero_cuotas_default' => null,
                'frecuencia_cuotas_default' => null,
                'cuota_inicial_monto' => null,
                'cuota_inicial_porcentaje' => null,
                'tipo_acceso' => 'por_visitas',
                'max_visitas_dia' => 1,
                'permite_congelacion' => false,
                'max_dias_congelacion' => null,
                'estado' => 'activa',
            ],
        ];

        foreach ($membresias as $membresia) {
            Membresia::create($membresia);
        }
    }
}
