<?php

namespace Database\Seeders;

use App\Models\System\Empresa;
use App\Models\System\GymSetting;
use App\Models\System\Sucursal;
use App\Models\User;
use Illuminate\Database\Seeder;

class CompanyBranchSeeder extends Seeder
{
    public function run(): void
    {
        $gymSetting = GymSetting::query()->first();

        $empresa = Empresa::query()->firstOrCreate(
            ['nombre' => $gymSetting?->nombre_gimnasio ?? config('app.name', 'Open9')],
            [
                'razon_social' => $gymSetting?->nombre_gimnasio ?? config('app.name', 'Open9'),
                'ruc' => $gymSetting?->ruc,
                'direccion' => $gymSetting?->direccion,
                'telefono' => $gymSetting?->telefono,
                'email' => $gymSetting?->email,
                'logo' => $gymSetting?->logo,
                'estado' => 'activa',
            ]
        );

        $sucursal = Sucursal::query()->firstOrCreate(
            ['codigo' => 'principal'],
            [
                'empresa_id' => $empresa->id,
                'nombre' => $gymSetting?->nombre_gimnasio ?? 'Sucursal Principal',
                'direccion' => $gymSetting?->direccion,
                'telefono' => $gymSetting?->telefono,
                'email' => $gymSetting?->email,
                'logo' => $gymSetting?->logo,
                'estado' => 'activa',
                'es_principal' => true,
                'horarios_acceso' => $gymSetting?->horarios_acceso,
                'politicas_acceso' => $gymSetting?->politicas_acceso,
            ]
        );

        User::query()->each(function (User $user) use ($sucursal) {
            $user->sucursales()->syncWithoutDetaching([$sucursal->id]);

            if ($user->default_sucursal_id === null) {
                $user->forceFill(['default_sucursal_id' => $sucursal->id])->save();
            }
        });
    }
}
