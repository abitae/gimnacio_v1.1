<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Models\System\Sucursal;
use App\Models\User;
use App\Services\SucursalContext;
use App\Support\PermissionCatalog;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureActions();
        $this->configureViews();
        $this->configureAuthentication();
        $this->configureRateLimiting();
    }

    /**
     * Configure Fortify actions.
     */
    private function configureActions(): void
    {
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::createUsersUsing(CreateNewUser::class);
    }

    /**
     * Configure Fortify views.
     */
    private function configureViews(): void
    {
        Fortify::loginView(fn () => view('livewire.auth.login', [
            'sucursales' => Sucursal::query()
                ->with('empresa')
                ->where('estado', 'activa')
                ->whereHas('empresa', fn ($query) => $query->where('estado', 'activa'))
                ->orderByDesc('es_principal')
                ->orderBy('nombre')
                ->get(),
        ]));
        Fortify::verifyEmailView(fn () => view('livewire.auth.verify-email'));
        Fortify::twoFactorChallengeView(fn () => view('livewire.auth.two-factor-challenge'));
        Fortify::confirmPasswordView(fn () => view('livewire.auth.confirm-password'));
        Fortify::registerView(fn () => view('livewire.auth.register'));
        Fortify::resetPasswordView(fn () => view('livewire.auth.reset-password'));
        Fortify::requestPasswordResetLinkView(fn () => view('livewire.auth.forgot-password'));
    }

    private function configureAuthentication(): void
    {
        Fortify::authenticateUsing(function (Request $request) {
            $request->validate([
                'email' => ['required', 'email'],
                'password' => ['required', 'string'],
                'sucursal_id' => ['required', 'integer'],
            ]);

            /** @var User|null $user */
            $user = User::query()->where('email', $request->string('email'))->first();

            if (! $user || ! Hash::check($request->string('password'), $user->password)) {
                return null;
            }

            if (($user->estado ?? 'activo') !== 'activo') {
                throw ValidationException::withMessages([
                    Fortify::username() => 'Tu usuario está inactivo.',
                ]);
            }

            $sucursalId = (int) $request->integer('sucursal_id');
            $sucursalQuery = Sucursal::query()
                ->with('empresa')
                ->whereKey($sucursalId)
                ->where('sucursales.estado', 'activa')
                ->whereHas('empresa', fn ($query) => $query->where('estado', 'activa'));

            if (! $user->hasRole(PermissionCatalog::SUPER_ADMIN_ROLE_NAME)) {
                $sucursalQuery->whereHas('usuarios', fn ($query) => $query->whereKey($user->id));
            }

            $sucursal = $sucursalQuery->first();

            if (! $sucursal) {
                throw ValidationException::withMessages([
                    'sucursal_id' => 'La sucursal seleccionada no está asignada a tu usuario.',
                ]);
            }

            app(SucursalContext::class)->activate($sucursal);

            return $user;
        });
    }

    /**
     * Configure rate limiting.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });
    }
}
