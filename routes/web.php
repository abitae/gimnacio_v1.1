<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('dashboard', \App\Livewire\Dashboard\DashboardLive::class)->middleware(['auth', 'verified'])->name('dashboard');

// Descarga de reporte con URL firmada (para enlace enviado por WhatsApp al cliente; sin auth, válida 48 h)
Route::get('reportes/evaluacion/descargar/{evaluacionId}', [\App\Http\Controllers\ReporteController::class, 'descargarEvaluacion'])
    ->middleware(['signed'])
    ->name('reportes.evaluacion.descargar.signed');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    // Ejercicios y Rutinas (módulo entrenamiento)
    Route::prefix('ejercicios')->name('ejercicios.')->middleware('permission:ejercicios-rutinas.view')->group(function () {
        Route::get('/', \App\Livewire\Exercises\Index::class)->name('index');
        Route::get('{exercise}', \App\Livewire\Exercises\Show::class)->name('show');
    });
    Route::prefix('rutinas-base')->name('rutinas-base.')->middleware('permission:ejercicios-rutinas.view')->group(function () {
        Route::get('/', \App\Livewire\Routines\Templates\Index::class)->name('index');
        Route::get('{template}', \App\Livewire\Routines\Templates\Show::class)->name('show');
        Route::get('{template}/builder', \App\Livewire\Routines\Templates\Builder::class)->name('builder');
    });
    Route::prefix('ejercicios-rutinas')->name('ejercicios-rutinas.')->middleware('permission:ejercicios-rutinas.view')->group(function () {
        Route::get('progreso', \App\Livewire\Reports\ProgressByExercise::class)->name('progreso');
        Route::get('cumplimiento', \App\Livewire\Reports\Compliance::class)->name('cumplimiento');
    });

    // Clientes
    Route::get('clientes', \App\Livewire\Clientes\ClienteLive::class)->middleware('permission:clientes.view')->name('clientes.index');
    Route::get('clientes/perfil', \App\Livewire\Clientes\ClientePerfilLive::class)->middleware('permission:clientes.view')->name('clientes.perfil.index');
    Route::get('clientes/{cliente}/perfil', \App\Livewire\Clientes\ClientePerfilLive::class)->middleware('permission:clientes.view')->name('clientes.perfil');
    Route::get('clientes/rutinas/asignar', \App\Livewire\Clients\Routines\Assign::class)->middleware('permission:ejercicios-rutinas.view')->name('clientes.rutinas.asignar');
    Route::prefix('clientes/{cliente}')->name('clientes.rutinas.')->middleware('permission:ejercicios-rutinas.view')->group(function () {
        Route::get('rutinas', \App\Livewire\Clients\Routines\Index::class)->name('index');
        Route::get('rutinas/{clientRoutine}', \App\Livewire\Clients\Routines\Show::class)->name('show');
        Route::get('rutinas/{clientRoutine}/sesiones', \App\Livewire\Clients\Workouts\Index::class)->name('sesiones.index');
        Route::get('rutinas/{clientRoutine}/sesiones/crear', \App\Livewire\Clients\Workouts\Form::class)->name('sesiones.create');
    });
    Route::get('clientes/{cliente}/sesiones/{workoutSession}', \App\Livewire\Clients\Workouts\Show::class)->middleware('permission:ejercicios-rutinas.view')->name('clientes.sesiones.show');

    // Membresías
    Route::get('membresias', \App\Livewire\Membresias\MembresiaLive::class)->middleware('permission:membresias.view')->name('membresias.index');

    // Matrículas de Clientes (Membresías y Clases)
    Route::get('cliente-matriculas', \App\Livewire\ClienteMatriculas\ClienteMatriculaLive::class)->middleware('permission:cliente-matriculas.view')->name('cliente-matriculas.index');
    Route::get('cliente-matriculas/{clienteMatricula}/cuotas', \App\Livewire\Enrollments\Installments\Schedule::class)->middleware('permission:cliente-matriculas.view')->name('cliente-matriculas.cuotas');
    Route::get('cliente-matriculas/{clienteMatricula}/cuotas/crear', \App\Livewire\Enrollments\Installments\PlanForm::class)->middleware('permission:cliente-matriculas.create')->name('cliente-matriculas.cuotas.crear');
    Route::get('cuotas/{installment}/pagar', \App\Livewire\Enrollments\Installments\PaymentForm::class)->middleware('permission:cliente-matriculas.update')->name('cuotas.pagar');

    // Cajas
    Route::get('cajas', \App\Livewire\Cajas\CajaLive::class)->middleware('permission:cajas.view')->name('cajas.index');

    // Checking - Registro de Ingreso
    Route::get('checking', \App\Livewire\Checking\CheckingLive::class)->middleware('permission:checking.view')->name('checking.index');

    // Punto de Venta
    Route::get('pos', \App\Livewire\POS\POSLive::class)->middleware('permission:pos.view')->name('pos.index');
    Route::get('pos/ventas-credito', \App\Livewire\POS\CreditSales::class)->middleware('permission:pos.view')->name('pos.ventas-credito');
    Route::get('pos/cuentas-por-cobrar', \App\Livewire\POS\CustomerDebts::class)->middleware('permission:pos.view')->name('pos.cuentas-por-cobrar');

    // Comprobante de venta (HTML y PDF para modal)
    Route::get('ventas/{venta}/comprobante', [\App\Http\Controllers\ComprobanteVentaController::class, 'show'])
        ->middleware('permission:pos.view')
        ->name('ventas.comprobante');
    Route::get('ventas/{venta}/comprobante.pdf', [\App\Http\Controllers\ComprobanteVentaController::class, 'pdf'])
        ->middleware('permission:pos.view')
        ->name('ventas.comprobante.pdf');

    // Cupones de descuento
    Route::prefix('cupones')->name('cupones.')->middleware('permission:cupones.view')->group(function () {
        Route::get('/', \App\Livewire\Coupons\Index::class)->name('index');
        Route::get('create', \App\Livewire\Coupons\Form::class)->name('create')->middleware('permission:cupones.create');
        Route::get('{coupon}/edit', \App\Livewire\Coupons\Form::class)->name('edit')->middleware('permission:cupones.update');
        Route::get('{coupon}', \App\Livewire\Coupons\Show::class)->name('show');
    });

    // Catálogos
    Route::get('categorias-productos', \App\Livewire\Categorias\CategoriaProductoLive::class)->middleware('permission:categorias-productos.view')->name('categorias-productos.index');
    Route::get('productos', \App\Livewire\Productos\ProductoLive::class)->middleware('permission:productos.view')->name('productos.index');
    Route::get('servicios', \App\Livewire\Servicios\ServicioExternoLive::class)->middleware('permission:servicios.view')->name('servicios.index');
    Route::get('clases', \App\Livewire\Clases\ClaseLive::class)->middleware('permission:clases.view')->name('clases.index');

    // Alquileres
    Route::prefix('alquileres')->name('rentals.')->middleware('permission:rentals.view')->group(function () {
        Route::get('espacios', \App\Livewire\Rentals\Spaces\Index::class)->name('spaces.index');
        Route::get('calendario', \App\Livewire\Rentals\Calendar\Index::class)->name('calendar.index');
        Route::get('reservas/crear', \App\Livewire\Rentals\Bookings\Form::class)->name('bookings.create')->middleware('permission:rentals.create');
        Route::get('reservas/{rental}/editar', \App\Livewire\Rentals\Bookings\Form::class)->name('bookings.edit')->middleware('permission:rentals.update');
        Route::get('reservas/{rental}', \App\Livewire\Rentals\Bookings\Show::class)->name('bookings.show');
        Route::get('reporte-ingresos', \App\Livewire\Rentals\Report::class)->name('report');
    });

    // Personal / Empleados
    Route::prefix('empleados')->name('employees.')->middleware('permission:employees.view')->group(function () {
        Route::get('/', \App\Livewire\Employees\Index::class)->name('index');
        Route::get('crear', \App\Livewire\Employees\Form::class)->name('create')->middleware('permission:employees.create');
        Route::get('{employee}/editar', \App\Livewire\Employees\Form::class)->name('edit')->middleware('permission:employees.update');
        Route::get('{employee}', \App\Livewire\Employees\Show::class)->name('show');
        Route::get('asistencia/listado', \App\Livewire\Employees\Attendances\Index::class)->name('attendances.index');
        Route::get('asistencia/registrar', \App\Livewire\Employees\Attendances\Form::class)->name('attendances.create')->middleware('permission:attendance.create');
        Route::get('asistencia/reporte', \App\Livewire\Employees\Attendances\Report::class)->name('attendances.report');
    });

    // Módulo de Reportes (índice, reportes por tipo y exportación PDF/Excel)
    Route::prefix('reportes')->name('reportes.')->middleware('permission:reportes.view')->group(function () {
        Route::get('/', \App\Livewire\Reportes\ReporteIndexLive::class)->name('index');
        Route::get('ventas', \App\Livewire\Reportes\ReporteVentasLive::class)->name('ventas');
        Route::get('matriculas', \App\Livewire\Reportes\ReporteMatriculasLive::class)->name('matriculas');
        Route::get('financiero', \App\Livewire\Reportes\ReporteFinancieroLive::class)->name('financiero');
        Route::get('clientes', \App\Livewire\Reportes\ReporteClientesLive::class)->name('clientes');
        Route::get('clientes-membresia-clases', \App\Livewire\Reportes\ReporteClientesMembresiaClasesLive::class)->name('clientes-membresia-clases');
        Route::get('usuarios', \App\Livewire\Reportes\ReporteUsuariosLive::class)->name('usuarios');
        Route::get('cajas', \App\Livewire\Reportes\ReporteCajasLive::class)->name('cajas');
        Route::get('productos-servicios', \App\Livewire\Reportes\ReporteProductosServiciosLive::class)->name('productos-servicios');
        Route::get('gimnasio', \App\Livewire\Reportes\ReporteGimnasioLive::class)->name('gimnasio');
        Route::get('cuentas-por-cobrar', \App\Livewire\POS\CustomerDebts::class)->name('cuentas-por-cobrar');
        Route::get('cuotas-vencidas', \App\Livewire\Reportes\ReporteCuotasVencidasLive::class)->name('cuotas-vencidas');
        // Exportación PDF
        Route::get('ventas/exportar-pdf', [\App\Http\Controllers\ReporteModuloController::class, 'exportarPdfVentas'])->name('ventas.exportar.pdf');
        Route::get('matriculas/exportar-pdf', [\App\Http\Controllers\ReporteModuloController::class, 'exportarPdfMatriculas'])->name('matriculas.exportar.pdf');
        Route::get('financiero/exportar-pdf', [\App\Http\Controllers\ReporteModuloController::class, 'exportarPdfFinanciero'])->name('financiero.exportar.pdf');
        Route::get('clientes/exportar-pdf', [\App\Http\Controllers\ReporteModuloController::class, 'exportarPdfClientes'])->name('clientes.exportar.pdf');
        Route::get('clientes-membresia-clases/exportar-pdf', [\App\Http\Controllers\ReporteModuloController::class, 'exportarPdfClientesMembresiaClases'])->name('clientes-membresia-clases.exportar.pdf');
        Route::get('usuarios/exportar-pdf', [\App\Http\Controllers\ReporteModuloController::class, 'exportarPdfUsuarios'])->name('usuarios.exportar.pdf');
        Route::get('cajas/exportar-pdf', [\App\Http\Controllers\ReporteModuloController::class, 'exportarPdfCajas'])->name('cajas.exportar.pdf');
        Route::get('productos-servicios/exportar-pdf', [\App\Http\Controllers\ReporteModuloController::class, 'exportarPdfProductosServicios'])->name('productos-servicios.exportar.pdf');
        Route::get('gimnasio/exportar-pdf', [\App\Http\Controllers\ReporteModuloController::class, 'exportarPdfGimnasio'])->name('gimnasio.exportar.pdf');
        // Exportación Excel
        Route::get('ventas/exportar-excel', [\App\Http\Controllers\ReporteModuloController::class, 'exportarExcelVentas'])->name('ventas.exportar.excel');
        Route::get('matriculas/exportar-excel', [\App\Http\Controllers\ReporteModuloController::class, 'exportarExcelMatriculas'])->name('matriculas.exportar.excel');
        Route::get('financiero/exportar-excel', [\App\Http\Controllers\ReporteModuloController::class, 'exportarExcelFinanciero'])->name('financiero.exportar.excel');
        Route::get('clientes/exportar-excel', [\App\Http\Controllers\ReporteModuloController::class, 'exportarExcelClientes'])->name('clientes.exportar.excel');
        Route::get('clientes-membresia-clases/exportar-excel', [\App\Http\Controllers\ReporteModuloController::class, 'exportarExcelClientesMembresiaClases'])->name('clientes-membresia-clases.exportar.excel');
        Route::get('usuarios/exportar-excel', [\App\Http\Controllers\ReporteModuloController::class, 'exportarExcelUsuarios'])->name('usuarios.exportar.excel');
        Route::get('cajas/exportar-excel', [\App\Http\Controllers\ReporteModuloController::class, 'exportarExcelCajas'])->name('cajas.exportar.excel');
        Route::get('productos-servicios/exportar-excel', [\App\Http\Controllers\ReporteModuloController::class, 'exportarExcelProductosServicios'])->name('productos-servicios.exportar.excel');
        Route::get('gimnasio/exportar-excel', [\App\Http\Controllers\ReporteModuloController::class, 'exportarExcelGimnasio'])->name('gimnasio.exportar.excel');
    });

    // Reportes (previsualización e impresión/descarga) - evaluaciones y cliente
    Route::prefix('reportes')->name('reportes.')->middleware('permission:reportes.view')->group(function () {
        Route::get('evaluacion/{evaluacionId}/preview', [\App\Http\Controllers\ReporteController::class, 'previewEvaluacion'])->name('evaluacion.preview');
        Route::get('evaluacion/{evaluacionId}/descargar', [\App\Http\Controllers\ReporteController::class, 'descargarEvaluacion'])->name('evaluacion.descargar');
        Route::get('historial-cliente/{clienteId}/preview', [\App\Http\Controllers\ReporteController::class, 'previewHistorialCliente'])->name('historial-cliente.preview');
        Route::get('historial-cliente/{clienteId}/descargar', [\App\Http\Controllers\ReporteController::class, 'descargarHistorialCliente'])->name('historial-cliente.descargar');
        Route::get('composicion-corporal/{clienteId}/preview', [\App\Http\Controllers\ReporteController::class, 'previewComposicionCorporal'])->name('composicion-corporal.preview');
        Route::get('composicion-corporal/{clienteId}/descargar', [\App\Http\Controllers\ReporteController::class, 'descargarComposicionCorporal'])->name('composicion-corporal.descargar');
    });

    // Gestión Nutricional (módulo unificado: Medidas, Nutrición, Citas, Calendario)
    Route::middleware('permission:gestion-nutricional.view')->group(function () {
        Route::get('gestion-nutricional', \App\Livewire\GestionNutricional\GestionNutricionalUnificadoLive::class)->name('gestion-nutricional.index');
        Route::get('gestion-nutricional/calendario', \App\Livewire\GestionNutricional\CalendarioCitasLive::class)->name('gestion-nutricional.calendario');
        Route::get('gestion-nutricional/calendario/eventos', function (\Illuminate\Http\Request $request) {
            $start = $request->get('start', now()->startOfMonth()->toIso8601String());
            $end = $request->get('end', now()->endOfMonth()->toIso8601String());
            $service = app(\App\Services\CitaService::class);
            return response()->json($service->getEventosParaCalendario($start, $end)->values());
        })->name('gestion-nutricional.calendario.eventos');
        Route::redirect('gestion-nutricional/medidas', 'gestion-nutricional', 301);
        Route::redirect('gestion-nutricional/nutricion', 'gestion-nutricional', 301);
        Route::redirect('gestion-nutricional/citas', 'gestion-nutricional', 301);
        Route::redirect('medidas-nutricion', 'gestion-nutricional', 301)->name('medidas-nutricion.index');
        Route::get('gestion-nutricional/objetivos', \App\Livewire\Nutrition\Goals\Index::class)->name('gestion-nutricional.objetivos.index');
        Route::get('gestion-nutricional/objetivos/crear', \App\Livewire\Nutrition\Goals\Form::class)->name('gestion-nutricional.objetivos.create');
        Route::get('gestion-nutricional/objetivos/{goal}/seguimiento/crear', \App\Livewire\Nutrition\Progress\Form::class)->name('gestion-nutricional.objetivos.seguimiento.create');
        Route::get('gestion-nutricional/objetivos/{goal}/editar', \App\Livewire\Nutrition\Goals\Form::class)->name('gestion-nutricional.objetivos.edit');
        Route::get('gestion-nutricional/objetivos/{goal}', \App\Livewire\Nutrition\Goals\Show::class)->name('gestion-nutricional.objetivos.show');
        Route::get('gestion-nutricional/salud/{cliente}', function (App\Models\Core\Cliente $cliente) {
            return redirect()->route('gestion-nutricional.index', ['salud' => $cliente->id]);
        })->name('gestion-nutricional.salud');
    });

    // CRM
    Route::prefix('crm')->name('crm.')->middleware('permission:crm.view')->group(function () {
        Route::get('pipeline', \App\Livewire\Crm\CrmPipelineLive::class)->name('pipeline');
        Route::get('leads', \App\Livewire\Crm\LeadsListLive::class)->name('leads.index');
        Route::get('leads/{lead}', \App\Livewire\Crm\LeadDetailLive::class)->name('leads.show');
        Route::get('tareas', \App\Livewire\Crm\CrmTasksLive::class)->name('tareas');
        Route::get('ofertas', \App\Livewire\Crm\CrmDealsLive::class)->name('deals');
        Route::get('reportes', \App\Livewire\Crm\CrmReportesLive::class)->name('reportes');
        Route::get('etiquetas', \App\Livewire\Crm\CrmTagsLive::class)->name('etiquetas');
        Route::get('renovacion-reactivacion', \App\Livewire\Crm\RenewalReactivacionLive::class)->name('renovacion-reactivacion');
        Route::get('campanias', \App\Livewire\Crm\CrmCampaignsLive::class)->name('campaigns');
        Route::get('campanias/{campaign}', \App\Livewire\Crm\CampaignDetailLive::class)->name('campaigns.show');
        Route::get('clientes/{cliente}/etiquetas', \App\Livewire\Crm\ClienteTagsLive::class)->name('clientes.etiquetas');
    });
    Route::get('crm/mensajes', \App\Livewire\Crm\MensajesLive::class)->middleware('permission:crm-mensajes.view')->name('crm.mensajes');

    // Configuración: Métodos de pago
    Route::get('configuracion/metodos-pago', \App\Livewire\Settings\PaymentMethods\Index::class)->middleware('permission:payment-methods.view')->name('payment-methods.index');

    // Administración (por permiso)
    Route::get('usuarios', \App\Livewire\Usuarios\UsuarioLive::class)->middleware('permission:usuarios.view')->name('usuarios.index');
    Route::get('roles', \App\Livewire\Roles\RolLive::class)->middleware('permission:roles.view')->name('roles.index');

    // Integración BioTime (ZKTeco)
    Route::middleware('permission:biotime.view')->group(function () {
        Route::get('biotime', \App\Livewire\biotime\BiotimeIndexLive::class)->name('biotime.index');
        Route::get('biotime/config', \App\Livewire\biotime\BiotimeConfigLive::class)->name('biotime.config');
        Route::get('biotime/sync', \App\Livewire\biotime\BiotimeSyncLive::class)->name('biotime.sync');
        Route::get('biotime/areas', \App\Livewire\biotime\area\AreaIndexLive::class)->name('biotime.areas');
        Route::get('biotime/departments', \App\Livewire\biotime\department\DepartmentIndexLive::class)->name('biotime.departments');
        Route::get('biotime/employees', \App\Livewire\biotime\employees\EmployeesIndexLive::class)->name('biotime.employees');
    });

    Volt::route('settings/profile', 'settings.profile')->name('profile.edit');
    Volt::route('settings/password', 'settings.password')->name('user-password.edit');
    Volt::route('settings/appearance', 'settings.appearance')->name('appearance.edit');

    Volt::route('settings/two-factor', 'settings.two-factor')
        ->middleware(
            when(
                Features::canManageTwoFactorAuthentication()
                    && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
                ['password.confirm'],
                [],
            ),
        )
        ->name('two-factor.show');
});
