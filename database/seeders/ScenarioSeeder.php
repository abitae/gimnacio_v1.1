<?php

namespace Database\Seeders;

use App\Models\Core\Caja;
use App\Models\Core\Clase;
use App\Models\Core\Cliente;
use App\Models\Core\HealthRecord;
use App\Models\Core\Membresia;
use App\Models\Core\PaymentMethod;
use App\Models\Crm\CrmStage;
use App\Models\Crm\Lead;
use App\Models\User;
use App\Services\ClientWellnessService;
use App\Services\ClienteMatriculaService;
use App\Services\Crm\ConvertLeadToClientService;
use App\Services\EnrollmentInstallmentService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;

class ScenarioSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([BaseCatalogSeeder::class]);

        $user = User::first() ?? User::factory()->create();
        Auth::login($user);

        $cashMethod = PaymentMethod::query()->firstOrCreate(
            ['nombre' => 'Efectivo'],
            ['descripcion' => 'Pago en efectivo', 'requiere_numero_operacion' => false, 'requiere_entidad' => false, 'estado' => 'activo']
        );

        Caja::query()->firstOrCreate(
            ['usuario_id' => $user->id, 'estado' => 'abierta'],
            ['saldo_inicial' => 200, 'fecha_apertura' => now(), 'observaciones_apertura' => 'Caja para seed transaccional']
        );

        $membresiaContado = Membresia::query()->first() ?? Membresia::factory()->create();
        $membresiaCuotas = Membresia::query()->where('permite_cuotas', true)->first() ?? Membresia::factory()->conCuotas()->create();
        $clase = Clase::query()->first() ?? Clase::factory()->create();

        $clienteContado = Cliente::factory()->create(['created_by' => $user->id]);
        $clienteCuotas = Cliente::factory()->create(['created_by' => $user->id]);
        $clienteClase = Cliente::factory()->create(['created_by' => $user->id]);

        HealthRecord::updateOrCreate(
            ['cliente_id' => $clienteContado->id],
            ['enfermedades' => 'Hipertensión controlada', 'alergias' => 'Ninguna', 'medicacion' => 'Losartán', 'lesiones' => 'Rodilla izquierda']
        );

        $matriculaService = app(ClienteMatriculaService::class);
        $installmentService = app(EnrollmentInstallmentService::class);

        $matriculaContado = $matriculaService->create([
            'cliente_id' => $clienteContado->id,
            'tipo' => 'membresia',
            'membresia_id' => $membresiaContado->id,
            'fecha_matricula' => now()->toDateString(),
            'fecha_inicio' => now()->toDateString(),
            'estado' => 'activa',
            'precio_lista' => $membresiaContado->precio_base,
            'descuento_monto' => 0,
            'precio_final' => $membresiaContado->precio_base,
            'asesor_id' => $user->id,
            'canal_venta' => 'mostrador',
            'modalidad_pago' => 'contado',
        ]);

        $matriculaService->procesarPago($matriculaContado->id, [
            'monto_pago' => round((float) $membresiaContado->precio_base / 2, 2),
            'metodo_pago' => 'efectivo',
            'payment_method_id' => $cashMethod->id,
            'fecha_pago' => now(),
        ]);

        $matriculaCuotas = $matriculaService->create([
            'cliente_id' => $clienteCuotas->id,
            'tipo' => 'membresia',
            'membresia_id' => $membresiaCuotas->id,
            'fecha_matricula' => now()->toDateString(),
            'fecha_inicio' => now()->toDateString(),
            'estado' => 'activa',
            'precio_lista' => $membresiaCuotas->precio_base,
            'descuento_monto' => 0,
            'precio_final' => $membresiaCuotas->precio_base,
            'asesor_id' => $user->id,
            'canal_venta' => 'web',
            'modalidad_pago' => 'cuotas',
            'numero_cuotas' => 4,
            'frecuencia_cuotas' => 'mensual',
            'cuota_inicial_monto' => min(100, (float) $membresiaCuotas->precio_base / 2),
        ]);

        $firstInstallment = $matriculaCuotas->fresh('installmentPlan.installments')->installmentPlan?->installments?->sortBy('numero_cuota')->first();
        if ($firstInstallment) {
            $installmentService->pagarCuota($firstInstallment, [
                'payment_method_id' => $cashMethod->id,
                'metodo_pago' => 'efectivo',
                'fecha_pago' => now(),
            ]);
        }

        $matriculaService->create([
            'cliente_id' => $clienteClase->id,
            'tipo' => 'clase',
            'clase_id' => $clase->id,
            'fecha_matricula' => now()->toDateString(),
            'fecha_inicio' => now()->toDateString(),
            'fecha_fin' => now()->addMonth()->toDateString(),
            'estado' => 'activa',
            'precio_lista' => $clase->obtenerPrecio(),
            'descuento_monto' => 0,
            'precio_final' => $clase->obtenerPrecio(),
            'asesor_id' => $user->id,
            'canal_venta' => 'mostrador',
            'sesiones_totales' => $clase->sesiones_paquete ?? 1,
            'sesiones_usadas' => 0,
        ]);

        $stage = CrmStage::query()->where('is_default', true)->first() ?? CrmStage::query()->first() ?? CrmStage::factory()->create(['is_default' => true]);
        $leadPendiente = Lead::factory()->create(['stage_id' => $stage->id, 'created_by' => $user->id]);
        $leadConvertido = Lead::factory()->create(['stage_id' => $stage->id, 'created_by' => $user->id]);

        app(ConvertLeadToClientService::class)->convert($leadConvertido, [
            'tipo_documento' => 'DNI',
            'numero_documento' => fake()->unique()->numerify('########'),
            'nombres' => 'Lead',
            'apellidos' => 'Convertido Demo',
            'telefono' => '9' . fake()->numerify('########'),
            'email' => fake()->unique()->safeEmail(),
            'activar_membresia' => false,
        ]);

        app(ClientWellnessService::class)->createReservation($clienteClase->id, [
            'rentable_space_id' => \App\Models\Core\RentableSpace::query()->first()?->id ?? \App\Models\Core\RentableSpace::factory()->create()->id,
            'fecha' => now()->addDays(2)->toDateString(),
            'hora_inicio' => '08:00',
            'hora_fin' => '09:00',
            'precio' => 50,
            'estado' => 'confirmado',
            'observaciones' => 'Reserva creada por escenario de prueba',
        ], $user->id);

        $this->command?->info(sprintf(
            'ScenarioSeeder listo: cliente contado #%d, cliente cuotas #%d, lead pendiente #%d.',
            $clienteContado->id,
            $clienteCuotas->id,
            $leadPendiente->id,
        ));
    }
}
