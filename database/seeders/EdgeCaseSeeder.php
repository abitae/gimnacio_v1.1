<?php

namespace Database\Seeders;

use App\Models\Core\Caja;
use App\Models\Core\Cliente;
use App\Models\Core\ClienteMatricula;
use App\Models\Core\DiscountCoupon;
use App\Models\Core\HealthRecord;
use App\Models\Core\Membresia;
use App\Models\Core\Producto;
use App\Models\Core\RentableSpace;
use App\Models\Crm\CrmStage;
use App\Models\Crm\Lead;
use App\Models\User;
use App\Services\ClienteMatriculaService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;

class EdgeCaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([BaseCatalogSeeder::class]);

        $user = User::first() ?? User::factory()->create();
        Auth::login($user);

        Caja::query()->firstOrCreate(
            ['usuario_id' => $user->id, 'estado' => 'abierta'],
            ['saldo_inicial' => 150, 'fecha_apertura' => now(), 'observaciones_apertura' => 'Caja abierta edge case']
        );

        Caja::query()->firstOrCreate(
            ['usuario_id' => $user->id, 'estado' => 'cerrada'],
            ['saldo_inicial' => 100, 'saldo_final' => 100, 'fecha_apertura' => now()->subDay(), 'fecha_cierre' => now()->subHours(12)]
        );

        Producto::factory()->sinStock()->create([
            'nombre' => 'Producto sin stock',
            'stock_minimo' => 3,
        ]);

        Producto::factory()->create([
            'nombre' => 'Producto en stock exacto',
            'stock_actual' => 5,
            'stock_minimo' => 5,
        ]);

        DiscountCoupon::factory()->expirado()->create(['codigo' => 'EDGEEXP']);
        DiscountCoupon::factory()->create([
            'codigo' => 'EDGEFULL',
            'cantidad_max_usos' => 10,
            'cantidad_usada' => 10,
        ]);

        $clienteSalud = Cliente::factory()->create([
            'created_by' => $user->id,
            'datos_salud' => [
                'enfermedades' => 'Ninguna',
                'alergias' => 'Polen',
                'medicacion' => 'Ninguna',
                'lesiones' => 'Ninguna',
            ],
        ]);

        HealthRecord::updateOrCreate(
            ['cliente_id' => $clienteSalud->id],
            [
                'enfermedades' => 'Asma',
                'alergias' => 'Penicilina',
                'medicacion' => 'Inhalador',
                'lesiones' => 'Hombro derecho',
                'observaciones' => 'Registro moderno prevalece sobre legacy',
            ]
        );

        RentableSpace::factory()->create([
            'nombre' => 'Espacio inactivo edge',
            'estado' => 'inactivo',
        ]);

        $stage = CrmStage::query()->where('is_default', true)->first() ?? CrmStage::query()->first() ?? CrmStage::factory()->create(['is_default' => true]);
        Lead::factory()->create([
            'stage_id' => $stage->id,
            'created_by' => $user->id,
            'estado' => 'no_responde',
        ]);
        Lead::factory()->convertido()->create([
            'stage_id' => $stage->id,
            'created_by' => $user->id,
        ]);

        $membresia = Membresia::query()->first() ?? Membresia::factory()->create();
        $matriculaService = app(ClienteMatriculaService::class);

        $clienteActivo = Cliente::factory()->create(['created_by' => $user->id]);
        $clienteCongelado = Cliente::factory()->create(['created_by' => $user->id]);
        $clienteCancelado = Cliente::factory()->create(['created_by' => $user->id]);
        $clienteVencido = Cliente::factory()->create(['created_by' => $user->id]);
        $clienteCuotas = Cliente::factory()->create(['created_by' => $user->id]);

        $activa = $this->crearMatriculaBase($matriculaService, $clienteActivo->id, $membresia->id, $user->id, now(), 'activa');
        $congelada = $this->crearMatriculaBase($matriculaService, $clienteCongelado->id, $membresia->id, $user->id, now()->subDays(10), 'activa');
        $cancelada = $this->crearMatriculaBase($matriculaService, $clienteCancelado->id, $membresia->id, $user->id, now()->subDays(20), 'activa');
        $vencida = $this->crearMatriculaBase($matriculaService, $clienteVencido->id, $membresia->id, $user->id, now()->subMonths(3), 'activa');

        $congelada->update([
            'estado' => 'congelada',
            'fechas_congelacion' => [[
                'desde' => now()->subDays(5)->toDateString(),
                'hasta' => now()->addDays(5)->toDateString(),
                'motivo' => 'Viaje',
                'registrado_por' => $user->id,
            ]],
        ]);

        $cancelada->update([
            'estado' => 'cancelada',
            'motivo_cancelacion' => 'Solicitud del cliente',
        ]);

        $vencida->update([
            'estado' => 'vencida',
            'fecha_inicio' => now()->subMonths(3)->toDateString(),
            'fecha_fin' => now()->subMonth()->toDateString(),
        ]);

        $cuotas = $matriculaService->create([
            'cliente_id' => $clienteCuotas->id,
            'tipo' => 'membresia',
            'membresia_id' => $membresia->id,
            'fecha_matricula' => now()->subMonths(2)->toDateString(),
            'fecha_inicio' => now()->subMonths(2)->toDateString(),
            'estado' => 'activa',
            'precio_lista' => $membresia->precio_base,
            'descuento_monto' => 0,
            'precio_final' => $membresia->precio_base,
            'asesor_id' => $user->id,
            'canal_venta' => 'edge-case',
            'modalidad_pago' => 'cuotas',
            'numero_cuotas' => 3,
            'frecuencia_cuotas' => 'mensual',
            'cuota_inicial_monto' => min(80, (float) $membresia->precio_base / 2),
        ]);

        $plan = $cuotas->fresh('installmentPlan.installments')->installmentPlan;
        if ($plan) {
            $installments = $plan->installments->sortBy('numero_cuota')->values();
            $installments->get(0)?->update([
                'estado' => 'pagada',
                'fecha_pago' => now()->subMonth()->toDateString(),
            ]);
            $installments->get(1)?->update([
                'estado' => 'vencida',
                'fecha_vencimiento' => now()->subDays(10)->toDateString(),
            ]);
            $installments->get(2)?->update([
                'estado' => 'pendiente',
                'fecha_vencimiento' => now()->addDays(15)->toDateString(),
            ]);
        }

        $this->command?->info(sprintf(
            'EdgeCaseSeeder listo: activa #%d, congelada #%d, cancelada #%d, vencida #%d.',
            $activa->id,
            $congelada->id,
            $cancelada->id,
            $vencida->id,
        ));
    }

    protected function crearMatriculaBase(
        ClienteMatriculaService $service,
        int $clienteId,
        int $membresiaId,
        int $asesorId,
        \Carbon\CarbonInterface $fechaInicio,
        string $estado
    ): ClienteMatricula {
        $membresia = Membresia::query()->findOrFail($membresiaId);

        return $service->create([
            'cliente_id' => $clienteId,
            'tipo' => 'membresia',
            'membresia_id' => $membresiaId,
            'fecha_matricula' => $fechaInicio->toDateString(),
            'fecha_inicio' => $fechaInicio->toDateString(),
            'estado' => $estado,
            'precio_lista' => $membresia->precio_base,
            'descuento_monto' => 0,
            'precio_final' => $membresia->precio_base,
            'asesor_id' => $asesorId,
            'canal_venta' => 'edge-case',
            'modalidad_pago' => 'contado',
        ]);
    }
}
