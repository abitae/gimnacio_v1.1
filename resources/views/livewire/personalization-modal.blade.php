<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component {
    public bool $showModal = false;
    /** Tema del cuerpo / contenido principal */
    public string $appearance = 'system';
    /** Tema del sidebar */
    public string $appearance_sidebar = 'system';
    /** Tema del header */
    public string $appearance_header = 'system';
    public string $accent = 'neutral';
    public string $sidebar_bg = 'default';
    public string $header_bg = 'default';
    public string $body_bg = 'default';
    public string $font_size = 'base';

    public function mount(): void
    {
        if (Auth::check()) {
            $this->syncFromUser();
        }
    }

    public function openModal(): void
    {
        if (Auth::check()) {
            $this->syncFromUser();
        }
        $this->showModal = true;
    }

    private function syncFromUser(): void
    {
        $user = Auth::user();
        $this->appearance = $user->appearance ?? 'system';
        $this->appearance_sidebar = $user->appearance_sidebar ?? 'system';
        $this->appearance_header = $user->appearance_header ?? 'system';
        $this->accent = $user->accent ?? 'neutral';
        $this->sidebar_bg = $user->sidebar_bg ?? 'default';
        $this->header_bg = $user->header_bg ?? 'default';
        $this->body_bg = $user->body_bg ?? 'default';
        $this->font_size = $user->font_size ?? 'base';
    }

    /** Tema del cuerpo (contenido principal) */
    public function setAppearance(string $value): void
    {
        if (! in_array($value, ['light', 'dark', 'system'], true) || ! Auth::check()) return;
        Auth::user()->update(['appearance' => $value]);
        $this->appearance = $value;
        $this->dispatchAppearanceUpdated();
    }

    /** Tema del sidebar */
    public function setAppearanceSidebar(string $value): void
    {
        if (! in_array($value, ['light', 'dark', 'system'], true) || ! Auth::check()) return;
        Auth::user()->update(['appearance_sidebar' => $value]);
        $this->appearance_sidebar = $value;
        $this->dispatchAppearanceUpdated();
    }

    /** Tema del header */
    public function setAppearanceHeader(string $value): void
    {
        if (! in_array($value, ['light', 'dark', 'system'], true) || ! Auth::check()) return;
        Auth::user()->update(['appearance_header' => $value]);
        $this->appearance_header = $value;
        $this->dispatchAppearanceUpdated();
    }

    public function setAccent(string $value): void
    {
        if (! in_array($value, ['neutral', 'blue', 'green', 'red', 'violet', 'indigo', 'amber'], true) || ! Auth::check()) return;
        Auth::user()->update(['accent' => $value]);
        $this->accent = $value;
        $this->dispatchAppearanceUpdated();
    }

    public function setSidebarBg(string $value): void
    {
        if (! in_array($value, ['default', 'slate', 'blue', 'green', 'amber', 'red', 'violet', 'indigo'], true) || ! Auth::check()) return;
        Auth::user()->update(['sidebar_bg' => $value]);
        $this->sidebar_bg = $value;
        $this->dispatchAppearanceUpdated();
    }

    public function setHeaderBg(string $value): void
    {
        if (! in_array($value, ['default', 'slate', 'blue', 'green', 'amber', 'red', 'violet', 'indigo'], true) || ! Auth::check()) return;
        Auth::user()->update(['header_bg' => $value]);
        $this->header_bg = $value;
        $this->dispatchAppearanceUpdated();
    }

    public function setBodyBg(string $value): void
    {
        if (! in_array($value, ['default', 'slate', 'blue', 'green', 'amber', 'red', 'violet', 'indigo'], true) || ! Auth::check()) return;
        Auth::user()->update(['body_bg' => $value]);
        $this->body_bg = $value;
        $this->dispatchAppearanceUpdated();
    }

    public function setFontSize(string $value): void
    {
        if (! in_array($value, ['sm', 'base', 'lg'], true) || ! Auth::check()) return;
        Auth::user()->update(['font_size' => $value]);
        $this->font_size = $value;
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
            body_bg: $this->body_bg,
            font_size: $this->font_size
        );
    }
}; ?>

<div wire:key="personalization-modal">
    <flux:sidebar.item icon="paint-brush" class="w-full justify-start gap-2" wire:click="openModal" as="button" type="button" title="{{ __('Personalize') }}">
        <span class="truncate">{{ __('Personalize') }}</span>
    </flux:sidebar.item>

    <flux:modal name="personalization-modal" wire:model="showModal" focusable class="md:max-w-md">
        {{-- Contenedor en modo claro fijo para que el modal siempre se vea claro --}}
        <div class="light bg-white text-zinc-900 -m-4 rounded-lg p-4" style="color-scheme: light;">
            <flux:heading size="lg" class="text-zinc-900">{{ __('Personalize') }}</flux:heading>
            <flux:subheading class="text-zinc-600">{{ __('Theme, sidebar and header colors. They adapt to light/dark mode.') }}</flux:subheading>

            <div class="mt-4 space-y-4">
                <div>
                    <flux:text class="mb-2 block text-sm font-medium text-zinc-900">{{ __('Theme mode') }} — {{ __('Sidebar') }}</flux:text>
                    <div class="flex gap-1 rounded-lg border border-zinc-200 bg-zinc-50 p-1">
                        <button type="button" wire:click="setAppearanceSidebar('light')" class="min-w-0 flex-1 rounded-md px-2 py-2 text-xs transition @if($appearance_sidebar === 'light') bg-white shadow @else hover:bg-zinc-200 @endif text-zinc-900" title="{{ __('Light') }}">
                            <flux:icon name="sun" class="mx-auto size-4" />
                        </button>
                        <button type="button" wire:click="setAppearanceSidebar('dark')" class="min-w-0 flex-1 rounded-md px-2 py-2 text-xs transition @if($appearance_sidebar === 'dark') bg-white shadow @else hover:bg-zinc-200 @endif text-zinc-900" title="{{ __('Dark') }}">
                            <flux:icon name="moon" class="mx-auto size-4" />
                        </button>
                        <button type="button" wire:click="setAppearanceSidebar('system')" class="min-w-0 flex-1 rounded-md px-2 py-2 text-xs transition @if($appearance_sidebar === 'system') bg-white shadow @else hover:bg-zinc-200 @endif text-zinc-900" title="{{ __('System') }}">
                            <flux:icon name="computer-desktop" class="mx-auto size-4" />
                        </button>
                    </div>
                </div>
                <div>
                    <flux:text class="mb-2 block text-sm font-medium text-zinc-900">{{ __('Theme mode') }} — {{ __('Header') }}</flux:text>
                    <div class="flex gap-1 rounded-lg border border-zinc-200 bg-zinc-50 p-1">
                        <button type="button" wire:click="setAppearanceHeader('light')" class="min-w-0 flex-1 rounded-md px-2 py-2 text-xs transition @if($appearance_header === 'light') bg-white shadow @else hover:bg-zinc-200 @endif text-zinc-900" title="{{ __('Light') }}">
                            <flux:icon name="sun" class="mx-auto size-4" />
                        </button>
                        <button type="button" wire:click="setAppearanceHeader('dark')" class="min-w-0 flex-1 rounded-md px-2 py-2 text-xs transition @if($appearance_header === 'dark') bg-white shadow @else hover:bg-zinc-200 @endif text-zinc-900" title="{{ __('Dark') }}">
                            <flux:icon name="moon" class="mx-auto size-4" />
                        </button>
                        <button type="button" wire:click="setAppearanceHeader('system')" class="min-w-0 flex-1 rounded-md px-2 py-2 text-xs transition @if($appearance_header === 'system') bg-white shadow @else hover:bg-zinc-200 @endif text-zinc-900" title="{{ __('System') }}">
                            <flux:icon name="computer-desktop" class="mx-auto size-4" />
                        </button>
                    </div>
                </div>
                <div>
                    <flux:text class="mb-2 block text-sm font-medium text-zinc-900">{{ __('Theme mode') }} — {{ __('Body') }}</flux:text>
                    <div class="flex gap-1 rounded-lg border border-zinc-200 bg-zinc-50 p-1">
                        <button type="button" wire:click="setAppearance('light')" class="min-w-0 flex-1 rounded-md px-2 py-2 text-xs transition @if($appearance === 'light') bg-white shadow @else hover:bg-zinc-200 @endif text-zinc-900" title="{{ __('Light') }}">
                            <flux:icon name="sun" class="mx-auto size-4" />
                        </button>
                        <button type="button" wire:click="setAppearance('dark')" class="min-w-0 flex-1 rounded-md px-2 py-2 text-xs transition @if($appearance === 'dark') bg-white shadow @else hover:bg-zinc-200 @endif text-zinc-900" title="{{ __('Dark') }}">
                            <flux:icon name="moon" class="mx-auto size-4" />
                        </button>
                        <button type="button" wire:click="setAppearance('system')" class="min-w-0 flex-1 rounded-md px-2 py-2 text-xs transition @if($appearance === 'system') bg-white shadow @else hover:bg-zinc-200 @endif text-zinc-900" title="{{ __('System') }}">
                            <flux:icon name="computer-desktop" class="mx-auto size-4" />
                        </button>
                    </div>
                </div>

                <div>
                    <flux:text class="mb-2 block text-sm font-medium text-zinc-900">{{ __('Font size') }}</flux:text>
                    <div class="flex gap-2">
                        @foreach(['sm' => 'Pequeño', 'base' => 'Mediano', 'lg' => 'Grande'] as $val => $label)
                            <button type="button" wire:click="setFontSize('{{ $val }}')" class="flex-1 rounded-lg border border-zinc-200 px-3 py-2 text-sm transition hover:border-zinc-300 text-zinc-900 @if($font_size === $val) border-accent ring-2 ring-accent @endif" title="{{ $label }}">
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <div>
                    <flux:text class="mb-2 block text-sm font-medium text-zinc-900">{{ __('Accent color') }}</flux:text>
                    <div class="flex flex-wrap gap-2">
                        @foreach(['neutral' => 'bg-zinc-400', 'blue' => 'bg-blue-500', 'green' => 'bg-green-500', 'red' => 'bg-red-500', 'violet' => 'bg-violet-500', 'indigo' => 'bg-indigo-500', 'amber' => 'bg-amber-500'] as $val => $dotClass)
                            <button type="button" wire:click="setAccent('{{ $val }}')" class="flex items-center gap-2 rounded-lg border border-zinc-200 px-3 py-2 text-sm transition hover:border-zinc-300 text-zinc-900 @if($accent === $val) border-accent ring-2 ring-accent @endif" title="{{ __(ucfirst($val)) }}">
                                <span class="size-4 rounded-full {{ $dotClass }}"></span>
                                <span>{{ __(ucfirst($val)) }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>

                <div>
                    <flux:text class="mb-2 block text-sm font-medium text-zinc-900">{{ __('Sidebar background') }}</flux:text>
                    <div class="flex flex-wrap gap-2">
                        @foreach(['default' => 'border-zinc-300 bg-zinc-100', 'slate' => 'bg-slate-500', 'blue' => 'bg-blue-500', 'green' => 'bg-green-500', 'amber' => 'bg-amber-500', 'red' => 'bg-red-500', 'violet' => 'bg-violet-500', 'indigo' => 'bg-indigo-500'] as $val => $swatchClass)
                            <button type="button" wire:click="setSidebarBg('{{ $val }}')" class="rounded-lg border-2 p-1.5 transition border-transparent hover:border-zinc-300 @if($sidebar_bg === $val) border-accent ring-2 ring-accent @endif" title="{{ __(ucfirst($val)) }}">
                                <span class="block size-6 rounded-full {{ $swatchClass }}"></span>
                            </button>
                        @endforeach
                    </div>
                </div>

                <div>
                    <flux:text class="mb-2 block text-sm font-medium text-zinc-900">{{ __('Header background') }}</flux:text>
                    <div class="flex flex-wrap gap-2">
                        @foreach(['default' => 'border-zinc-300 bg-zinc-100', 'slate' => 'bg-slate-500', 'blue' => 'bg-blue-500', 'green' => 'bg-green-500', 'amber' => 'bg-amber-500', 'red' => 'bg-red-500', 'violet' => 'bg-violet-500', 'indigo' => 'bg-indigo-500'] as $val => $swatchClass)
                            <button type="button" wire:click="setHeaderBg('{{ $val }}')" class="rounded-lg border-2 p-1.5 transition border-transparent hover:border-zinc-300 @if($header_bg === $val) border-accent ring-2 ring-accent @endif" title="{{ __(ucfirst($val)) }}">
                                <span class="block size-6 rounded-full {{ $swatchClass }}"></span>
                            </button>
                        @endforeach
                    </div>
                </div>

                <div>
                    <flux:text class="mb-2 block text-sm font-medium text-zinc-900">{{ __('Body background') }}</flux:text>
                    <div class="flex flex-wrap gap-2">
                        @foreach(['default' => 'border-zinc-300 bg-zinc-100', 'slate' => 'bg-slate-500', 'blue' => 'bg-blue-500', 'green' => 'bg-green-500', 'amber' => 'bg-amber-500', 'red' => 'bg-red-500', 'violet' => 'bg-violet-500', 'indigo' => 'bg-indigo-500'] as $val => $swatchClass)
                            <button type="button" wire:click="setBodyBg('{{ $val }}')" class="rounded-lg border-2 p-1.5 transition border-transparent hover:border-zinc-300 @if($body_bg === $val) border-accent ring-2 ring-accent @endif" title="{{ __(ucfirst($val)) }}">
                                <span class="block size-6 rounded-full {{ $swatchClass }}"></span>
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>

            <flux:modal.close class="mt-4">
                <flux:button variant="primary">{{ __('Close') }}</flux:button>
            </flux:modal.close>
        </div>
    </flux:modal>
</div>
