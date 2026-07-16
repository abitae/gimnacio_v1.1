<?php

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (\Illuminate\Console\Scheduling\Schedule $schedule): void {
        $minutes = max(1, (int) config('biotime.access_reconcile_minutes', 60));

        $event = $schedule->call(function (): void {
            \App\Models\BioTime\BioTimeSucursalSetting::query()
                ->where('enabled', true)
                ->pluck('sucursal_id')
                ->each(function ($sucursalId): void {
                    \App\Jobs\BioTime\ReconcileBioTimeAccessForSucursal::dispatch((int) $sucursalId);
                });
        })
            ->name('biotime-access-reconcile')
            ->withoutOverlapping();

        if ($minutes === 60) {
            $event->hourly();
        } else {
            $event->everyMinutes(min(59, $minutes));
        }
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'sucursal.context' => \App\Http\Middleware\EnsureSucursalContext::class,
            'biotime.sync' => \App\Http\Middleware\VerifyBioTimeSyncToken::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (NotFoundHttpException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Página no encontrada'], 404);
            }

            return response()->view('errors.404', [], 404);
        });

        $exceptions->render(function (HttpException $e, $request) {
            if ($e->getStatusCode() === 403) {
                if ($request->expectsJson()) {
                    return response()->json(['message' => 'Acceso denegado'], 403);
                }

                return response()->view('errors.403', [], 403);
            }

            return null;
        });

        $exceptions->render(function (AuthorizationException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage() ?: 'Acceso denegado'], 403);
            }

            return response()->view('errors.403', [], 403);
        });
    })->create();
