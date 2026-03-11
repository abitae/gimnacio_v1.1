<?php

namespace Database\Seeders;

use App\Models\Core\DiscountCoupon;
use Illuminate\Database\Seeder;

class DiscountCouponSeeder extends Seeder
{
    public function run(): void
    {
        $coupons = [
            [
                'codigo' => 'BIENVENIDA',
                'nombre' => 'Descuento bienvenida',
                'descripcion' => 'S/ 20 de descuento para nuevos clientes',
                'tipo_descuento' => 'monto_fijo',
                'valor_descuento' => 20.00,
                'fecha_inicio' => now()->toDateString(),
                'fecha_vencimiento' => now()->addYear()->toDateString(),
                'cantidad_max_usos' => null,
                'cantidad_usada' => 0,
                'aplica_a' => 'todos',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'VERANO2026',
                'nombre' => 'Promo verano',
                'descripcion' => 'S/ 50 de descuento en matrícula o membresía',
                'tipo_descuento' => 'monto_fijo',
                'valor_descuento' => 50.00,
                'fecha_inicio' => now()->toDateString(),
                'fecha_vencimiento' => now()->addMonths(3)->toDateString(),
                'cantidad_max_usos' => 100,
                'cantidad_usada' => 0,
                'aplica_a' => 'todos',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'POS10',
                'nombre' => 'Descuento POS',
                'descripcion' => 'S/ 10 en compras en tienda',
                'tipo_descuento' => 'monto_fijo',
                'valor_descuento' => 10.00,
                'fecha_inicio' => now()->toDateString(),
                'fecha_vencimiento' => now()->addMonths(6)->toDateString(),
                'cantidad_max_usos' => null,
                'cantidad_usada' => 0,
                'aplica_a' => 'pos',
                'estado' => 'activo',
            ],
        ];

        foreach ($coupons as $data) {
            DiscountCoupon::firstOrCreate(
                ['codigo' => $data['codigo']],
                $data
            );
        }
    }
}
