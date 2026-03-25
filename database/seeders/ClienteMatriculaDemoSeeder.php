<?php

namespace Database\Seeders;

use App\Models\Core\Caja;
use App\Models\Core\Cliente;
use App\Models\Core\ClienteMatricula;
use App\Models\Core\Membresia;
use App\Models\User;
use App\Services\ClienteMatriculaService;
use App\Services\ClientWellnessService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;

/**
 * Matrículas demo alineadas con ClienteMatriculaService (contado, pago a cuenta, cuotas, congelación por días).
 * Ejecutar tras MembresiaSeeder, ClienteSeeder, CajaSeeder y un usuario.
 */
class ClienteMatriculaDemoSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->first();
        if ($user === null) {
            $this->command?->warn('ClienteMatriculaDemoSeeder: no hay usuarios.');

            return;
        }

        Auth::login($user);

        Caja::query()->firstOrCreate(
            ['usuario_id' => $user->id, 'estado' => 'abierta'],
            [
                'saldo_inicial' => 100,
                'fecha_apertura' => now(),
                'observaciones_apertura' => 'Demo matrículas',
            ]
        );

        $memContado = Membresia::query()->where('nombre', 'Mensual Básica')->first()
            ?? Membresia::query()->first();
        $memCuotas = Membresia::query()->where('nombre', 'Trimestral')->first()
            ?? Membresia::query()->first();
        $memFreeze = Membresia::query()->where('permite_congelacion', true)->first()
            ?? Membresia::query()->first();

        if ($memContado === null || $memCuotas === null) {
            $this->command?->warn('ClienteMatriculaDemoSeeder: faltan membresías en catálogo.');

            return;
        }

        $service = app(ClienteMatriculaService::class);
        $wellness = app(ClientWellnessService::class);

        $c1 = Cliente::factory()->create(['created_by' => $user->id]);
        $service->create([
            'cliente_id' => $c1->id,
            'tipo' => 'membresia',
            'membresia_id' => $memContado->id,
            'fecha_matricula' => now()->toDateString(),
            'fecha_inicio' => now()->toDateString(),
            'estado' => 'activa',
            'precio_lista' => $memContado->precio_base,
            'descuento_monto' => 0,
            'precio_final' => (float) $memContado->precio_base,
            'asesor_id' => $user->id,
            'canal_venta' => 'demo',
            'modalidad_pago' => 'contado',
        ]);

        $c2 = Cliente::factory()->create(['created_by' => $user->id]);
        $service->create([
            'cliente_id' => $c2->id,
            'tipo' => 'membresia',
            'membresia_id' => $memContado->id,
            'fecha_matricula' => now()->toDateString(),
            'fecha_inicio' => now()->toDateString(),
            'estado' => 'activa',
            'precio_lista' => $memContado->precio_base,
            'descuento_monto' => 0,
            'precio_final' => (float) $memContado->precio_base,
            'asesor_id' => $user->id,
            'canal_venta' => 'demo',
            'modalidad_pago' => 'contado',
            'monto_pago_inicial' => round((float) $memContado->precio_base * 0.25, 2),
        ]);

        $c3 = Cliente::factory()->create(['created_by' => $user->id]);
        $service->create([
            'cliente_id' => $c3->id,
            'tipo' => 'membresia',
            'membresia_id' => $memCuotas->id,
            'fecha_matricula' => now()->toDateString(),
            'fecha_inicio' => now()->toDateString(),
            'estado' => 'activa',
            'precio_lista' => $memCuotas->precio_base,
            'descuento_monto' => 0,
            'precio_final' => (float) $memCuotas->precio_base,
            'asesor_id' => $user->id,
            'canal_venta' => 'demo',
            'modalidad_pago' => 'cuotas',
            'numero_cuotas' => 4,
            'frecuencia_cuotas' => 'mensual',
            'cuota_inicial_monto' => min(80, (float) $memCuotas->precio_base / 4),
        ]);

        if ($memFreeze !== null && $memFreeze->permite_congelacion) {
            $c4 = Cliente::factory()->create(['created_by' => $user->id]);
            $m = $service->create([
                'cliente_id' => $c4->id,
                'tipo' => 'membresia',
                'membresia_id' => $memFreeze->id,
                'fecha_matricula' => now()->toDateString(),
                'fecha_inicio' => now()->toDateString(),
                'estado' => 'activa',
                'precio_lista' => $memFreeze->precio_base,
                'descuento_monto' => 0,
                'precio_final' => (float) $memFreeze->precio_base,
                'asesor_id' => $user->id,
                'canal_venta' => 'demo',
                'modalidad_pago' => 'contado',
            ]);
            if ($m instanceof ClienteMatricula) {
                $dias = max(1, min(3, (int) ($memFreeze->max_dias_congelacion ?? 3)));
                $wellness->freezePlanByDays(
                    $c4->id,
                    'cliente_matricula',
                    $m->id,
                    $dias,
                    'Demo seeder',
                    $user->id
                );
            }
        }

        $this->command?->info('ClienteMatriculaDemoSeeder: matrículas demo creadas.');
    }
}
