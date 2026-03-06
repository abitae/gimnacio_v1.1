<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component {
    public string $appearance = 'system';

    public function mount(): void
    {
        if (Auth::check()) {
            $this->appearance = Auth::user()->appearance ?? 'system';
        }
    }

    public function setAppearance(string $value): void
    {
        if (! in_array($value, ['light', 'dark', 'system'], true)) {
            return;
        }
        if (! Auth::check()) {
            return;
        }

        Auth::user()->update(['appearance' => $value]);
        $this->appearance = $value;

        $user = Auth::user();
        $this->dispatch('appearance-updated',
            appearance: $value,
            accent: $user->accent ?? 'neutral',
            sidebar_bg: $user->sidebar_bg ?? 'default',
            header_bg: $user->header_bg ?? 'default'
        );
    }
}; ?>

<div class="flex w-full items-center gap-1 rounded-lg border border-zinc-200 bg-zinc-50 p-1 dark:border-zinc-600 dark:bg-zinc-800" wire:key="theme-switcher">
    <flux:button size="sm" variant="ghost" class="min-w-0 flex-1 px-2 @if($appearance === 'light') bg-white shadow dark:bg-zinc-700 @endif" wire:click="setAppearance('light')" title="{{ __('Light') }}">
        <flux:icon name="sun" class="size-4" />
    </flux:button>
    <flux:button size="sm" variant="ghost" class="min-w-0 flex-1 px-2 @if($appearance === 'dark') bg-white shadow dark:bg-zinc-700 @endif" wire:click="setAppearance('dark')" title="{{ __('Dark') }}">
        <flux:icon name="moon" class="size-4" />
    </flux:button>
    <flux:button size="sm" variant="ghost" class="min-w-0 flex-1 px-2 @if($appearance === 'system') bg-white shadow dark:bg-zinc-700 @endif" wire:click="setAppearance('system')" title="{{ __('System') }}">
        <flux:icon name="computer-desktop" class="size-4" />
    </flux:button>
</div>
