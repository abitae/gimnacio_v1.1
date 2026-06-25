<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component {
    public string $accent = 'red';

    public string $menu_color = 'red';

    public function mount(): void
    {
        $user = Auth::user();
        $this->accent = $user->accent ?? 'red';
        $this->menu_color = $user->sidebar_bg ?? $user->header_bg ?? 'red';
    }

    public function save(): void
    {
        $validated = $this->validate([
            'accent' => ['required', 'string', 'in:neutral,blue,green,red,violet,indigo,amber'],
            'menu_color' => ['required', 'string', 'in:default,slate,blue,green,amber,red,violet,indigo'],
        ]);

        Auth::user()->update([
            'accent' => $validated['accent'],
            'sidebar_bg' => $validated['menu_color'],
            'header_bg' => $validated['menu_color'],
            'appearance' => 'light',
            'appearance_sidebar' => 'light',
            'appearance_header' => 'light',
        ]);

        $user = Auth::user()->fresh();
        $this->dispatch('appearance-updated',
            appearance: 'light',
            appearance_sidebar: 'light',
            appearance_header: 'light',
            accent: $user->accent ?? 'red',
            sidebar_bg: $user->sidebar_bg ?? 'red',
            header_bg: $user->header_bg ?? 'red',
            body_bg: $user->body_bg ?? 'default',
            font_size: $user->font_size ?? 'base',
        );
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Appearance')" :subheading="__('Accent and menu colors. The application uses light mode.')">
        <form wire:submit="save" class="space-y-6">
            <div>
                <flux:text class="mb-2 block font-medium">{{ __('Accent color') }}</flux:text>
                <div class="flex flex-wrap gap-2">
                    @foreach(['neutral' => __('Neutral'), 'blue' => __('Blue'), 'green' => __('Green'), 'red' => __('Red'), 'violet' => __('Violet'), 'indigo' => __('Indigo'), 'amber' => __('Amber')] as $value => $label)
                        <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-zinc-200 px-3 py-2 has-[:checked]:border-accent has-[:checked]:ring-2 has-[:checked]:ring-accent">
                            <input type="radio" wire:model="accent" value="{{ $value }}" class="size-4 border-zinc-300 text-accent focus:ring-accent">
                            @if($value !== 'neutral')
                                <span class="size-4 rounded-full
                                    @if($value === 'blue') bg-blue-500
                                    @elseif($value === 'green') bg-green-500
                                    @elseif($value === 'red') bg-red-500
                                    @elseif($value === 'violet') bg-violet-500
                                    @elseif($value === 'indigo') bg-indigo-500
                                    @elseif($value === 'amber') bg-amber-500
                                    @endif
                                "></span>
                            @endif
                            <span class="text-sm">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div>
                <flux:text class="mb-2 block font-medium">{{ __('Menu and header') }}</flux:text>
                <div class="flex flex-wrap gap-2">
                    @foreach(['default' => 'border-zinc-300 bg-zinc-100', 'slate' => 'bg-slate-500', 'blue' => 'bg-blue-500', 'green' => 'bg-green-500', 'amber' => 'bg-amber-500', 'red' => 'bg-red-500', 'violet' => 'bg-violet-500', 'indigo' => 'bg-indigo-500'] as $value => $dotClass)
                        <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-zinc-200 px-3 py-2 has-[:checked]:border-accent has-[:checked]:ring-2 has-[:checked]:ring-accent">
                            <input type="radio" wire:model="menu_color" value="{{ $value }}" class="size-4 border-zinc-300 text-accent focus:ring-accent">
                            <span class="size-4 rounded-full {{ $dotClass }} shrink-0"></span>
                            <span class="text-sm">{{ __($value === 'default' ? 'Default' : ucfirst($value)) }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <flux:button variant="primary" type="submit">
                {{ __('Save') }}
            </flux:button>
        </form>
    </x-settings.layout>
</section>
