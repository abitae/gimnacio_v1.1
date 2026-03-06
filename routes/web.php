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

    // Cajas
    Route::get('cajas', \App\Livewire\Cajas\CajaLive::class)->middleware('permission:cajas.view')->name('cajas.index');

    // Checking - Registro de Ingreso
    Route::get('checking', \App\Livewire\Checking\CheckingLive::class)->middleware('permission:checking.view')->name('checking.index');

    // Punto de Venta
    Route::get('pos', \App\Livewire\POS\POSLive::class)->middleware('permission:pos.view')->name('pos.index');

    // Catálogos
    Route::get('categorias-productos', \App\Livewire\Categorias\CategoriaProductoLive::class)->middleware('permission:categorias-productos.view')->name('categorias-productos.index');
    Route::get('productos', \App\Livewire\Productos\ProductoLive::class)->middleware('permission:productos.view')->name('productos.index');
    Route::get('servicios', \App\Livewire\Servicios\ServicioExternoLive::class)->middleware('permission:servicios.view')->name('servicios.index');
    Route::get('clases', \App\Livewire\Clases\ClaseLive::class)->middleware('permission:clases.view')->name('clases.index');

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
    Route::prefix('reportes')->name('reportes.')->group(function () {
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

    // Administración (por permiso)
    Route::get('usuarios', \App\Livewire\Usuarios\UsuarioLive::class)->middleware('permission:usuarios.view')->name('usuarios.index');
    Route::get('roles', \App\Livewire\Roles\RolLive::class)->middleware('permission:roles.view')->name('roles.index');

    // Integración BioTime (ZKTeco)
    Route::middleware('permission:biotime.view')->group(function () {
        Route::get('biotime', \App\Livewire\Biotime\BiotimeIndexLive::class)->name('biotime.index');
        Route::get('biotime/config', \App\Livewire\Biotime\BiotimeConfigLive::class)->name('biotime.config');
        Route::get('biotime/sync', \App\Livewire\Biotime\BiotimeSyncLive::class)->name('biotime.sync');
        Route::get('biotime/areas', \App\Livewire\Biotime\Area\AreaIndexLive::class)->name('biotime.areas');
        Route::get('biotime/departments', \App\Livewire\Biotime\Department\DepartmentIndexLive::class)->name('biotime.departments');
        Route::get('biotime/employees', \App\Livewire\Biotime\Employees\EmployeesIndexLive::class)->name('biotime.employees');
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
