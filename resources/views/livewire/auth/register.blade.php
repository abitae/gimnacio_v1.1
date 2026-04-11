<x-layouts.auth>
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('auth.register.title')" :description="__('auth.register.description')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('register.store') }}" class="flex flex-col gap-6">
            @csrf
            <!-- Name -->
            <flux:input
                name="name"
                :label="__('auth.register.name')"
                :value="old('name')"
                type="text"
                required
                autofocus
                autocomplete="name"
                :placeholder="__('auth.register.full_name_placeholder')"
            />

            <!-- Email Address -->
            <flux:input
                name="email"
                :label="__('auth.register.email_label')"
                :value="old('email')"
                type="email"
                required
                autocomplete="email"
                :placeholder="__('auth.login.placeholder_email')"
            />

            <!-- Password -->
            <flux:input
                name="password"
                :label="__('auth.register.password_label')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('auth.register.password_label')"
                viewable
            />

            <!-- Confirm Password -->
            <flux:input
                name="password_confirmation"
                :label="__('auth.register.confirm_password_label')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('auth.register.confirm_password_label')"
                viewable
            />

            <div class="flex items-center justify-end">
                <flux:button type="submit" variant="primary" class="w-full" data-test="register-user-button">
                    {{ __('auth.register.submit') }}
                </flux:button>
            </div>
        </form>

        <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-600 dark:text-zinc-400">
            <span>{{ __('auth.register.have_account') }}</span>
            <flux:link :href="route('login')" wire:navigate>{{ __('auth.register.log_in') }}</flux:link>
        </div>
    </div>
</x-layouts.auth>
