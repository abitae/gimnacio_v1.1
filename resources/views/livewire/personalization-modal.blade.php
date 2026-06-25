<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component {
    public bool $showModal = false;

    public string $accent = 'red';

    public string $menu_color = 'red';

    public function mount(): void
    {
        if (Auth::check()) {
            $this->syncFromUser();
            $this->ensureLightMode();
        }
    }

    public function openModal(): void
    {
        if (Auth::check()) {
            $this->syncFromUser();
            $this->ensureLightMode();
        }
        $this->showModal = true;
    }

    private function syncFromUser(): void
    {
        $user = Auth::user();
        $this->accent = $user->accent ?? 'red';
        $this->menu_color = $user->sidebar_bg ?? $user->header_bg ?? 'red';
    }

    private function ensureLightMode(): void
    {
        $user = Auth::user();
        $needsUpdate = ($user->appearance ?? 'system') !== 'light'
            || ($user->appearance_sidebar ?? 'dark') !== 'light'
            || ($user->appearance_header ?? 'dark') !== 'light';

        if ($needsUpdate) {
            $user->update([
                'appearance' => 'light',
                'appearance_sidebar' => 'light',
                'appearance_header' => 'light',
            ]);
        }
    }

    public function setAccent(string $value): void
    {
        if (! in_array($value, ['neutral', 'blue', 'green', 'red', 'violet', 'indigo', 'amber'], true) || ! Auth::check()) {
            return;
        }

        Auth::user()->update(['accent' => $value]);
        $this->accent = $value;
        $this->dispatchAppearanceUpdated();
    }

    public function setMenuColor(string $value): void
    {
        if (! in_array($value, ['default', 'slate', 'blue', 'green', 'amber', 'red', 'violet', 'indigo'], true) || ! Auth::check()) {
            return;
        }

        Auth::user()->update([
            'sidebar_bg' => $value,
            'header_bg' => $value,
        ]);
        $this->menu_color = $value;
        $this->dispatchAppearanceUpdated();
    }

    private function dispatchAppearanceUpdated(): void
    {
        $user = Auth::user();

        $this->dispatch('appearance-updated',
            appearance: 'light',
            appearance_sidebar: 'light',
            appearance_header: 'light',
            accent: $user->accent ?? 'red',
            sidebar_bg: $this->menu_color,
            header_bg: $this->menu_color,
            body_bg: $user->body_bg ?? 'default',
            font_size: $user->font_size ?? 'base',
        );
    }
}; ?>

<div wire:key="personalization-modal">
    <flux:sidebar.item icon="paint-brush" class="w-full justify-start gap-2" wire:click="openModal" as="button" type="button" :tooltip="__('Personalize')">
        {{ __('Personalize') }}
    </flux:sidebar.item>

    <flux:modal name="personalization-modal" wire:model="showModal" focusable class="md:max-w-sm">
        <div class="light bg-white text-zinc-900 -m-4 rounded-lg p-4" style="color-scheme: light;">
            <flux:heading size="lg" class="text-zinc-900">{{ __('Personalize') }}</flux:heading>
            <flux:subheading class="text-zinc-600">{{ __('Accent and menu colors (light mode).') }}</flux:subheading>

            <div class="mt-4 space-y-5">
                <div>
                    <flux:text class="mb-2 block text-sm font-medium text-zinc-900">{{ __('Accent color') }}</flux:text>
                    <div class="flex flex-wrap gap-2">
                        @foreach(['neutral' => 'bg-zinc-400', 'blue' => 'bg-blue-500', 'green' => 'bg-green-500', 'red' => 'bg-red-500', 'violet' => 'bg-violet-500', 'indigo' => 'bg-indigo-500', 'amber' => 'bg-amber-500'] as $val => $dotClass)
                            <button type="button" wire:click="setAccent('{{ $val }}')" class="flex items-center gap-2 rounded-lg border border-zinc-200 px-2.5 py-1.5 text-sm transition hover:border-zinc-300 text-zinc-900 @if($accent === $val) border-accent ring-2 ring-accent @endif" title="{{ __(ucfirst($val)) }}">
                                <span class="size-4 rounded-full {{ $dotClass }}"></span>
                                <span class="hidden sm:inline">{{ __(ucfirst($val)) }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>

                <div>
                    <flux:text class="mb-2 block text-sm font-medium text-zinc-900">{{ __('Menu and header') }}</flux:text>
                    <div class="flex flex-wrap gap-2">
                        @foreach(['default' => 'border-zinc-300 bg-zinc-100', 'slate' => 'bg-slate-500', 'blue' => 'bg-blue-500', 'green' => 'bg-green-500', 'amber' => 'bg-amber-500', 'red' => 'bg-red-500', 'violet' => 'bg-violet-500', 'indigo' => 'bg-indigo-500'] as $val => $swatchClass)
                            <button type="button" wire:click="setMenuColor('{{ $val }}')" class="rounded-lg border-2 p-1.5 transition border-transparent hover:border-zinc-300 @if($menu_color === $val) border-accent ring-2 ring-accent @endif" title="{{ __(ucfirst($val)) }}">
                                <span class="block size-7 rounded-full {{ $swatchClass }}"></span>
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>

            <flux:modal.close class="mt-5">
                <flux:button variant="primary">{{ __('Close') }}</flux:button>
            </flux:modal.close>
        </div>
    </flux:modal>
</div>
