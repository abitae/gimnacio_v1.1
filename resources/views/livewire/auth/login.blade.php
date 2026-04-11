<x-layouts.auth>
    <div class="flex flex-col gap-8 border border-gray-500 p-4 rounded-lg">
        <x-auth-header
            title="Iniciar sesión"
            description="Introduce tu correo y contraseña para continuar"
        />

        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-6">
            @csrf

            <div class="flex flex-col gap-2">
                <label for="sucursal_id" class="text-sm font-medium text-zinc-800 dark:text-zinc-100">Sucursal</label>
                <select
                    id="sucursal_id"
                    name="sucursal_id"
                    class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"
                    required
                >
                    <option value="">Seleccionar sucursal</option>
                    @foreach (($sucursales ?? collect()) as $sucursal)
                        <option value="{{ $sucursal->id }}" @selected(old('sucursal_id') == $sucursal->id)>
                            {{ $sucursal->empresa?->nombre }} - {{ $sucursal->nombre }}
                        </option>
                    @endforeach
                </select>
                @error('sucursal_id')
                    <p class="text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <flux:input
                name="email"
                :label="__('auth.login.email_label')"
                :value="old('email')"
                type="email"
                required
                autofocus
                autocomplete="email"
                :placeholder="__('auth.login.placeholder_email')"
            />

            <div class="flex flex-col gap-1.5">
                <flux:input
                    name="password"
                    :label="__('auth.login.password_label')"
                    type="password"
                    required
                    autocomplete="current-password"
                    :placeholder="__('auth.login.password_label')"
                    viewable
                />
                @if (Route::has('password.request'))
                    <div class="flex justify-end">
                        <flux:link
                            class="text-sm text-zinc-600 hover:text-zinc-900"
                            :href="route('password.request')"
                            wire:navigate
                        >
                            {{ __('auth.login.forgot_password') }}
                        </flux:link>
                    </div>
                @endif
            </div>

            <flux:checkbox name="remember" :label="__('auth.login.remember_me')" :checked="old('remember')" />

            <flux:button variant="primary" type="submit" class="w-full" data-test="login-button">
                {{ __('auth.login.submit') }}
            </flux:button>
        </form>

        @if (Route::has('register'))
            <p class="text-center text-sm text-zinc-600">
                <span>{{ __('auth.login.no_account') }}</span>
                <flux:link :href="route('register')" class="font-medium text-zinc-800 hover:underline" wire:navigate>
                    {{ __('auth.login.sign_up') }}
                </flux:link>
            </p>
        @endif
    </div>
</x-layouts.auth>
