<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component {
    public string $sidebar_bg = 'default';
    public string $header_bg = 'default';

    public function mount(): void
    {
        if (Auth::check()) {
            $this->sidebar_bg = Auth::user()->sidebar_bg ?? 'default';
            $this->header_bg = Auth::user()->header_bg ?? 'default';
        }
    }

    public function setSidebarBg(string $value): void
    {
        if (! in_array($value, ['default', 'blue', 'green', 'red'], true) || ! Auth::check()) {
            return;
        }
        Auth::user()->update(['sidebar_bg' => $value]);
        $this->sidebar_bg = $value;
        $this->dispatchAppearanceUpdated();
    }

    public function setHeaderBg(string $value): void
    {
        if (! in_array($value, ['default', 'blue', 'green', 'red'], true) || ! Auth::check()) {
            return;
        }
        Auth::user()->update(['header_bg' => $value]);
        $this->header_bg = $value;
        $this->dispatchAppearanceUpdated();
    }

    private function dispatchAppearanceUpdated(): void
    {
        $user = Auth::user();
        $this->dispatch('appearance-updated',
            appearance: $user->appearance ?? 'system',
            appearance_sidebar: $user->appearance_sidebar ?? 'system',
            appearance_header: $user->appearance_header ?? 'system',
            accent: $user->accent ?? 'neutral',
            sidebar_bg: $this->sidebar_bg,
            header_bg: $this->header_bg,
            body_bg: $user->body_bg ?? 'default',
            font_size: $user->font_size ?? 'base',
        );
    }
}; ?>

<div class="w-full space-y-3 px-2 py-2" wire:key="sidebar-header-colors">
    <div>
        <flux:text class="mb-1.5 block text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('Sidebar') }}</flux:text>
        <div class="flex w-full gap-1 rounded-lg border border-zinc-200 bg-zinc-50 p-1 dark:border-zinc-600 dark:bg-zinc-800">
            <button type="button" wire:click="setSidebarBg('default')" class="min-w-0 flex-1 rounded-md px-2 py-1.5 text-xs transition @if($sidebar_bg === 'default') bg-white shadow dark:bg-zinc-700 @else hover:bg-zinc-200 dark:hover:bg-zinc-700 @endif" title="{{ __('Default') }}">
                <span class="inline-block size-3 rounded-full border border-zinc-300 bg-zinc-100 dark:border-zinc-600 dark:bg-zinc-600"></span>
            </button>
            <button type="button" wire:click="setSidebarBg('blue')" class="min-w-0 flex-1 rounded-md px-2 py-1.5 text-xs transition @if($sidebar_bg === 'blue') bg-white shadow dark:bg-zinc-700 @else hover:bg-zinc-200 dark:hover:bg-zinc-700 @endif" title="{{ __('Blue') }}">
                <span class="inline-block size-3 rounded-full bg-blue-500"></span>
            </button>
            <button type="button" wire:click="setSidebarBg('green')" class="min-w-0 flex-1 rounded-md px-2 py-1.5 text-xs transition @if($sidebar_bg === 'green') bg-white shadow dark:bg-zinc-700 @else hover:bg-zinc-200 dark:hover:bg-zinc-700 @endif" title="{{ __('Green') }}">
                <span class="inline-block size-3 rounded-full bg-green-500"></span>
            </button>
            <button type="button" wire:click="setSidebarBg('red')" class="min-w-0 flex-1 rounded-md px-2 py-1.5 text-xs transition @if($sidebar_bg === 'red') bg-white shadow dark:bg-zinc-700 @else hover:bg-zinc-200 dark:hover:bg-zinc-700 @endif" title="{{ __('Red') }}">
                <span class="inline-block size-3 rounded-full bg-red-500"></span>
            </button>
        </div>
    </div>
    <div>
        <flux:text class="mb-1.5 block text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('Header') }}</flux:text>
        <div class="flex w-full gap-1 rounded-lg border border-zinc-200 bg-zinc-50 p-1 dark:border-zinc-600 dark:bg-zinc-800">
            <button type="button" wire:click="setHeaderBg('default')" class="min-w-0 flex-1 rounded-md px-2 py-1.5 text-xs transition @if($header_bg === 'default') bg-white shadow dark:bg-zinc-700 @else hover:bg-zinc-200 dark:hover:bg-zinc-700 @endif" title="{{ __('Default') }}">
                <span class="inline-block size-3 rounded-full border border-zinc-300 bg-zinc-100 dark:border-zinc-600 dark:bg-zinc-600"></span>
            </button>
            <button type="button" wire:click="setHeaderBg('blue')" class="min-w-0 flex-1 rounded-md px-2 py-1.5 text-xs transition @if($header_bg === 'blue') bg-white shadow dark:bg-zinc-700 @else hover:bg-zinc-200 dark:hover:bg-zinc-700 @endif" title="{{ __('Blue') }}">
                <span class="inline-block size-3 rounded-full bg-blue-500"></span>
            </button>
            <button type="button" wire:click="setHeaderBg('green')" class="min-w-0 flex-1 rounded-md px-2 py-1.5 text-xs transition @if($header_bg === 'green') bg-white shadow dark:bg-zinc-700 @else hover:bg-zinc-200 dark:hover:bg-zinc-700 @endif" title="{{ __('Green') }}">
                <span class="inline-block size-3 rounded-full bg-green-500"></span>
            </button>
            <button type="button" wire:click="setHeaderBg('red')" class="min-w-0 flex-1 rounded-md px-2 py-1.5 text-xs transition @if($header_bg === 'red') bg-white shadow dark:bg-zinc-700 @else hover:bg-zinc-200 dark:hover:bg-zinc-700 @endif" title="{{ __('Red') }}">
                <span class="inline-block size-3 rounded-full bg-red-500"></span>
            </button>
        </div>
    </div>
</div>
