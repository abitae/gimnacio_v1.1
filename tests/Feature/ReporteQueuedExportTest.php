<?php

use App\Jobs\ExportReporteModuloJob;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Bus;

it('despacha el job de exportación cuando REPORTES_QUEUE_EXPORTS está activo', function () {
    Bus::fake();
    $this->seed(RoleSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('caja');

    config(['reportes.queue_exports' => true]);

    $response = $this->actingAs($user)
        ->from(route('reportes.ventas'))
        ->get(route('reportes.ventas.exportar.excel', [
            'fecha_desde' => '2024-01-01',
            'fecha_hasta' => '2024-12-31',
        ]));

    $response->assertRedirect();
    $response->assertSessionHas('success');
    Bus::assertDispatched(ExportReporteModuloJob::class, function (ExportReporteModuloJob $job) {
        return $job->modulo === 'ventas'
            && $job->format === 'excel';
    });
});
