<?php

namespace Database\Seeders;

use App\Models\Core\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        $methods = [
            [
                'nombre' => 'Efectivo',
                'descripcion' => 'Pago en efectivo',
                'requiere_numero_operacion' => false,
                'requiere_entidad' => false,
                'estado' => 'activo',
            ],
            [
                'nombre' => 'Transferencia',
                'descripcion' => 'Transferencia bancaria',
                'requiere_numero_operacion' => true,
                'requiere_entidad' => true,
                'estado' => 'activo',
            ],
            [
                'nombre' => 'Depósito',
                'descripcion' => 'Depósito en cuenta',
                'requiere_numero_operacion' => true,
                'requiere_entidad' => true,
                'estado' => 'activo',
            ],
            [
                'nombre' => 'Tarjeta',
                'descripcion' => 'Tarjeta de débito o crédito',
                'requiere_numero_operacion' => true,
                'requiere_entidad' => false,
                'estado' => 'activo',
            ],
            [
                'nombre' => 'Yape',
                'descripcion' => 'Pago por Yape',
                'requiere_numero_operacion' => true,
                'requiere_entidad' => false,
                'estado' => 'activo',
            ],
            [
                'nombre' => 'Plin',
                'descripcion' => 'Pago por Plin',
                'requiere_numero_operacion' => true,
                'requiere_entidad' => false,
                'estado' => 'activo',
            ],
        ];

        foreach ($methods as $data) {
            PaymentMethod::firstOrCreate(
                ['nombre' => $data['nombre']],
                $data
            );
        }
    }
}
