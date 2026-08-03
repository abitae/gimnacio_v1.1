<?php

use App\Livewire\Employees\Attendances\Index;
use App\Livewire\Employees\Attendances\Report;
use App\Models\Core\Employee;
use App\Models\Core\EmployeeAttendance;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $guard = config('auth.defaults.guard');
    foreach (['empleado.ver', 'asistencia_empleado.ver', 'asistencia_empleado.crear'] as $perm) {
        Permission::firstOrCreate(['name' => $perm, 'guard_name' => $guard]);
    }
});

it('muestra búsqueda de empleados en el listado de asistencia', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['asistencia_empleado.ver', 'asistencia_empleado.crear']);
    $this->actingAs($user);

    $employee = Employee::factory()->create([
        'nombres' => 'Pedro',
        'apellidos' => 'Asistencia Busqueda',
        'documento' => '99887766',
        'estado' => 'activo',
    ]);

    Livewire::test(Index::class)
        ->set('employeeSearch', 'Pedro Asist')
        ->assertSee('Pedro Asistencia Busqueda')
        ->assertSee('Registrar ingreso');
});

it('registra ingreso y salida desde el listado de asistencia', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['asistencia_empleado.ver', 'asistencia_empleado.crear']);
    $this->actingAs($user);

    $employee = Employee::factory()->create(['estado' => 'activo']);
    $fecha = now()->format('Y-m-d');

    Livewire::test(Index::class)
        ->set('fecha', $fecha)
        ->call('registrarIngreso', $employee->id)
        ->assertHasNoErrors();

    expect(EmployeeAttendance::query()->where('employee_id', $employee->id)->whereDate('fecha', $fecha)->exists())->toBeTrue();

    Livewire::test(Index::class)
        ->set('fecha', $fecha)
        ->call('registrarSalida', $employee->id)
        ->assertHasNoErrors();

    $attendance = EmployeeAttendance::query()->where('employee_id', $employee->id)->whereDate('fecha', $fecha)->first();
    expect($attendance->hora_salida)->not->toBeNull();
});

it('renderiza el reporte mensual de asistencia con totales', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['asistencia_empleado.ver']);
    $this->actingAs($user);

    $employee = Employee::factory()->create([
        'nombres' => 'Ana',
        'apellidos' => 'Reporte Test',
        'estado' => 'activo',
    ]);

    EmployeeAttendance::create([
        'employee_id' => $employee->id,
        'fecha' => now()->toDateString(),
        'hora_ingreso' => '08:00',
        'hora_salida' => '17:00',
        'tardanza_minutos' => 10,
        'registrado_por' => $user->id,
    ]);

    Livewire::test(Report::class)
        ->assertSee('Reporte de asistencia')
        ->assertSee('Ana Reporte Test')
        ->assertSee('Exportar Excel');
});

it('exporta excel del reporte de asistencia', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['asistencia_empleado.ver']);
    $this->actingAs($user);

    $response = $this->get(route('employees.attendances.report.export', [
        'mes' => now()->month,
        'anio' => now()->year,
    ]));

    $response->assertOk();
});
