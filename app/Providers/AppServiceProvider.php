<?php

namespace App\Providers;

use App\Services\WhatsApp\MockWhatsAppService;
use App\Services\WhatsApp\WhatsAppServiceInterface;
use App\Support\PermissionCatalog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Opción B (Spatie): super-admin pasa cualquier comprobación de autorización sin listar todos los permisos en BD.
        Gate::before(function ($user, $ability) {
            if ($user === null || ! is_string($ability)) {
                return null;
            }

            return method_exists($user, 'hasRole') && $user->hasRole(PermissionCatalog::SUPER_ADMIN_ROLE_NAME)
                ? true
                : null;
        });

        Gate::policy(\App\Models\Crm\Lead::class, \App\Policies\Crm\LeadPolicy::class);

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
        $headerBgClasses = [
            'default' => 'bg-white lg:bg-zinc-50 dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-700',
            'slate' => 'bg-white lg:bg-slate-50 dark:bg-slate-950 border-b border-slate-200 dark:border-slate-800',
            'blue' => 'bg-white lg:bg-blue-50 dark:bg-blue-950 border-b border-blue-200 dark:border-blue-800',
            'green' => 'bg-white lg:bg-green-50 dark:bg-green-950 border-b border-green-200 dark:border-green-800',
            'amber' => 'bg-white lg:bg-amber-50 dark:bg-amber-950 border-b border-amber-200 dark:border-amber-800',
            'red' => 'bg-white lg:bg-red-50 dark:bg-red-950 border-b border-red-200 dark:border-red-800',
            'violet' => 'bg-white lg:bg-violet-50 dark:bg-violet-950 border-b border-violet-200 dark:border-violet-800',
            'indigo' => 'bg-white lg:bg-indigo-50 dark:bg-indigo-950 border-b border-indigo-200 dark:border-indigo-800',
        ];
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
            $appearanceSidebarValue = 'system';
            $headerAppearanceClass = 'dark';
            $appearanceHeaderValue = 'system';
            $accentClass = 'accent-neutral';
            $sidebarBgClass = $sidebarBgClasses['default'];
            $headerBgClass = $headerBgClasses['default'];
            $bodyBgClass = $bodyBgClasses['default'];
            $accentValue = 'neutral';
            $sidebarBgValue = 'default';
            $headerBgValue = 'default';
            $bodyBgValue = 'default';
            $fontSizeValue = 'base';
            $fontSizeClass = 'text-base';

            if (Auth::check()) {
                $user = Auth::user();
                $appearanceValue = $user->appearance ?? 'system';
                $bodyAppearanceClass = $appearanceValue === 'system' ? 'dark' : $appearanceValue;
                $appearanceSidebarValue = $user->appearance_sidebar ?? 'system';
                $sidebarAppearanceClass = $appearanceSidebarValue === 'system' ? 'dark' : $appearanceSidebarValue;
                $appearanceHeaderValue = $user->appearance_header ?? 'system';
                $headerAppearanceClass = $appearanceHeaderValue === 'system' ? 'dark' : $appearanceHeaderValue;
                $accentValue = $user->accent ?? 'neutral';
                $accentClass = 'accent-'.$accentValue;
                $sidebarBgValue = $user->sidebar_bg ?? 'default';
                $headerBgValue = $user->header_bg ?? 'default';
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
        });
    }
}
