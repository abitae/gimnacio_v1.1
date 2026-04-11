<x-layouts.auth>
    <div class="flex flex-col gap-6">
        <x-auth-header
            :title="__('auth.confirm_password.title')"
            :description="__('auth.confirm_password.description')"
        />

        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('password.confirm.store') }}" class="flex flex-col gap-6">
            @csrf

            <flux:input
                name="password"
                :label="__('auth.confirm_password.password_label')"
                type="password"
                required
                autocomplete="current-password"
                :placeholder="__('auth.confirm_password.password_label')"
                viewable
            />

            <flux:button variant="primary" type="submit" class="w-full" data-test="confirm-password-button">
                {{ __('auth.confirm_password.submit') }}
            </flux:button>
        </form>
    </div>
</x-layouts.auth>
