<?php

namespace App\Providers;

use App\Services\SucursalContext;
use App\Services\WhatsApp\MockWhatsAppService;
use App\Services\WhatsApp\WhatsAppServiceInterface;
use App\Support\PermissionCatalog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(WhatsAppServiceInterface::class, MockWhatsAppService::class);
        $this->app->singleton(SucursalContext::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Opcion B (Spatie): super_administrador pasa cualquier comprobacion de autorizaciÃ³n sin listar todos los permisos en BD.
        Gate::before(function ($user, $ability) {
            if ($user === null || ! is_string($ability)) {
                return null;
            }

            return method_exists($user, 'hasRole') && $user->hasRole(PermissionCatalog::SUPER_ADMIN_ROLE_NAME)
                ? true
                : null;
        });

        Gate::policy(\App\Models\Crm\Lead::class, \App\Policies\Crm\LeadPolicy::class);

        $slowMs = (int) env('DB_SLOW_QUERY_LOG_MS', 0);
        if ($slowMs > 0 && (bool) config('app.debug')) {
            DB::listen(function ($query) use ($slowMs): void {
                if ($query->time < $slowMs) {
                    return;
                }
                Log::warning('Consulta lenta', [
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'ms' => $query->time,
                    'connection' => $query->connectionName,
                ]);
            });
        }

        $this->app->booted(function () {
            Schedule::command('crm:mark-overdue-tasks')->hourly();
            Schedule::command('crm:renewal-tasks --days=7')->dailyAt('08:00');
        });

        $sidebarBgClasses = [
            'default' => 'bg-zinc-50 dark:bg-zinc-900 border-r border-zinc-200 dark:border-zinc-700',
            'slate' => 'bg-slate-50 dark:bg-slate-950 border-r border-slate-200 dark:border-slate-800',
            'blue' => 'bg-blue-50 dark:bg-blue-950 border-r border-blue-200 dark:border-blue-800',
            'green' => 'bg-green-50 dark:bg-green-950 border-r border-green-200 dark:border-green-800',
            'amber' => 'bg-amber-50 dark:bg-amber-950 border-r border-amber-200 dark:border-amber-800',
            'red' => 'bg-red-50 dark:bg-red-950 border-r border-red-200 dark:border-red-800',
            'violet' => 'bg-violet-50 dark:bg-violet-950 border-r border-violet-200 dark:border-violet-800',
            'indigo' => 'bg-indigo-50 dark:bg-indigo-950 border-r border-indigo-200 dark:border-indigo-800',
        ];
        /** Barra superior (#app-header): tinte visible alineado con header_bg */
        $headerBgClasses = [
            'default' => 'bg-white lg:bg-zinc-100 dark:bg-zinc-900 border-b-2 border-zinc-300 dark:border-zinc-600',
            'slate' => 'bg-white lg:bg-slate-100 dark:bg-slate-950 border-b-2 border-slate-300 dark:border-slate-700',
            'blue' => 'bg-white lg:bg-blue-100 dark:bg-blue-950 border-b-2 border-blue-300 dark:border-blue-700',
            'green' => 'bg-white lg:bg-green-100 dark:bg-green-950 border-b-2 border-green-300 dark:border-green-700',
            'amber' => 'bg-white lg:bg-amber-100 dark:bg-amber-950 border-b-2 border-amber-300 dark:border-amber-700',
            'red' => 'bg-white lg:bg-red-100 dark:bg-red-950 border-b-2 border-red-300 dark:border-red-700',
            'violet' => 'bg-white lg:bg-violet-100 dark:bg-violet-950 border-b-2 border-violet-300 dark:border-violet-700',
            'indigo' => 'bg-white lg:bg-indigo-100 dark:bg-indigo-950 border-b-2 border-indigo-300 dark:border-indigo-700',
        ];
        /** Gradientes de cabeceras de módulo (misma clave que users.header_bg) */
        $pageHeaderGradientClasses = [
            'default' => 'bg-gradient-to-r from-zinc-600 to-zinc-700 dark:from-zinc-700 dark:to-zinc-800',
            'slate' => 'bg-gradient-to-r from-slate-600 to-slate-700 dark:from-slate-700 dark:to-slate-800',
            'blue' => 'bg-gradient-to-r from-blue-600 to-blue-700 dark:from-blue-700 dark:to-blue-800',
            'green' => 'bg-gradient-to-r from-green-600 to-green-700 dark:from-green-700 dark:to-green-800',
            'amber' => 'bg-gradient-to-r from-amber-600 to-amber-700 dark:from-amber-700 dark:to-amber-800',
            'red' => 'bg-gradient-to-r from-red-600 to-red-700 dark:from-red-700 dark:to-red-800',
            'violet' => 'bg-gradient-to-r from-violet-600 to-violet-700 dark:from-violet-700 dark:to-violet-800',
            'indigo' => 'bg-gradient-to-r from-indigo-600 to-indigo-700 dark:from-indigo-700 dark:to-indigo-800',
        ];

        View::composer('*', function ($view) use ($pageHeaderGradientClasses) {
            $key = Auth::check() ? (Auth::user()->header_bg ?? 'red') : 'red';
            $view->with(
                'pageHeaderGradientClass',
                $pageHeaderGradientClasses[$key] ?? $pageHeaderGradientClasses['default']
            );
        });
        $bodyBgClasses = [
            'default' => 'bg-white dark:bg-zinc-800',
            'slate' => 'bg-slate-50 dark:bg-slate-900',
            'blue' => 'bg-blue-50/50 dark:bg-blue-950/50',
            'green' => 'bg-green-50/50 dark:bg-green-950/50',
            'amber' => 'bg-amber-50/50 dark:bg-amber-950/50',
            'red' => 'bg-red-50/50 dark:bg-red-950/50',
            'violet' => 'bg-violet-50/50 dark:bg-violet-950/50',
            'indigo' => 'bg-indigo-50/50 dark:bg-indigo-950/50',
        ];

        View::composer('components.layouts.app.sidebar', function ($view) use ($sidebarBgClasses, $headerBgClasses, $bodyBgClasses) {
            $bodyAppearanceClass = 'dark';
            $appearanceValue = 'system';
            $sidebarAppearanceClass = 'dark';
            $appearanceSidebarValue = 'dark';
            $headerAppearanceClass = 'dark';
            $appearanceHeaderValue = 'dark';
            $accentClass = 'accent-red';
            $sidebarBgClass = $sidebarBgClasses['red'];
            $headerBgClass = $headerBgClasses['red'];
            $bodyBgClass = $bodyBgClasses['default'];
            $accentValue = 'red';
            $sidebarBgValue = 'red';
            $headerBgValue = 'red';
            $bodyBgValue = 'default';
            $fontSizeValue = 'base';
            $fontSizeClass = 'text-base';

            if (Auth::check()) {
                $user = Auth::user();
                $appearanceValue = $user->appearance ?? 'system';
                $bodyAppearanceClass = $appearanceValue === 'system' ? 'dark' : $appearanceValue;
                $appearanceSidebarValue = $user->appearance_sidebar ?? 'dark';
                $sidebarAppearanceClass = $appearanceSidebarValue === 'system' ? 'dark' : $appearanceSidebarValue;
                $appearanceHeaderValue = $user->appearance_header ?? 'dark';
                $headerAppearanceClass = $appearanceHeaderValue === 'system' ? 'dark' : $appearanceHeaderValue;
                $accentValue = $user->accent ?? 'red';
                $accentClass = 'accent-'.$accentValue;
                $sidebarBgValue = $user->sidebar_bg ?? 'red';
                $headerBgValue = $user->header_bg ?? 'red';
                $bodyBgValue = $user->body_bg ?? 'default';
                $fontSizeValue = $user->font_size ?? 'base';
                $sidebarBgClass = $sidebarBgClasses[$sidebarBgValue] ?? $sidebarBgClasses['default'];
                $headerBgClass = $headerBgClasses[$headerBgValue] ?? $headerBgClasses['default'];
                $bodyBgClass = $bodyBgClasses[$bodyBgValue] ?? $bodyBgClasses['default'];
                $fontSizeClass = match ($fontSizeValue) {
                    'sm' => 'text-sm',
                    'lg' => 'text-lg',
                    default => 'text-base',
                };
            }

            $sucursalContext = app(SucursalContext::class);
            $activeSucursal = Auth::check() ? $sucursalContext->sucursal() : null;
            $availableSucursales = Auth::check() ? $sucursalContext->availableForUser(Auth::user()) : collect();

            $view->with('bodyAppearanceClass', $bodyAppearanceClass);
            $view->with('appearanceValue', $appearanceValue);
            $view->with('sidebarAppearanceClass', $sidebarAppearanceClass);
            $view->with('appearanceSidebarValue', $appearanceSidebarValue);
            $view->with('headerAppearanceClass', $headerAppearanceClass);
            $view->with('appearanceHeaderValue', $appearanceHeaderValue);
            $view->with('accentClass', $accentClass);
            $view->with('accentValue', $accentValue);
            $view->with('sidebarBgClass', $sidebarBgClass);
            $view->with('headerBgClass', $headerBgClass);
            $view->with('bodyBgClass', $bodyBgClass);
            $view->with('sidebarBgValue', $sidebarBgValue);
            $view->with('headerBgValue', $headerBgValue);
            $view->with('bodyBgValue', $bodyBgValue);
            $view->with('fontSizeValue', $fontSizeValue);
            $view->with('fontSizeClass', $fontSizeClass);
            $view->with('activeSucursal', $activeSucursal);
            $view->with('availableSucursales', $availableSucursales);
        });
    }
}
