<x-layouts.auth>
    <div class="flex flex-col gap-8 border border-gray-500 p-4 rounded-lg">
        <x-auth-header
            :title="__('Log in to your account')"
            :description="__('Enter your email and password below to log in')"
        />

        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-6">
            @csrf

            <flux:input
                name="email"
                :label="__('Email address')"
                :value="old('email')"
                type="email"
                required
                autofocus
                autocomplete="email"
                placeholder="email@example.com"
            />

            <div class="flex flex-col gap-1.5">
                <flux:input
                    name="password"
                    :label="__('Password')"
                    type="password"
                    required
                    autocomplete="current-password"
                    :placeholder="__('Password')"
                    viewable
                />
                @if (Route::has('password.request'))
                    <div class="flex justify-end">
                        <flux:link
                            class="text-sm text-zinc-600 hover:text-zinc-900"
                            :href="route('password.request')"
                            wire:navigate
                        >
                            {{ __('Forgot your password?') }}
                        </flux:link>
                    </div>
                @endif
            </div>

            <flux:checkbox name="remember" :label="__('Remember me')" :checked="old('remember')" />

            <flux:button variant="primary" type="submit" class="w-full" data-test="login-button">
                {{ __('Log in') }}
            </flux:button>
        </form>

        @if (Route::has('register'))
            <p class="text-center text-sm text-zinc-600">
                <span>{{ __('Don\'t have an account?') }}</span>
                <flux:link :href="route('register')" class="font-medium text-zinc-800 hover:underline" wire:navigate>
                    {{ __('Sign up') }}
                </flux:link>
            </p>
        @endif
    </div>
</x-layouts.auth>
