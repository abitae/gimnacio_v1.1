<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component {
    public string $appearance = 'system';
    public string $accent = 'neutral';
    public string $sidebar_bg = 'default';
    public string $header_bg = 'default';
    public string $body_bg = 'default';

    public function mount(): void
    {
        $user = Auth::user();
        $this->appearance = $user->appearance ?? 'system';
        $this->accent = $user->accent ?? 'neutral';
        $this->sidebar_bg = $user->sidebar_bg ?? 'default';
        $this->header_bg = $user->header_bg ?? 'default';
        $this->body_bg = $user->body_bg ?? 'default';
    }

    public function save(): void
    {
        $validated = $this->validate([
            'appearance' => ['required', 'string', 'in:light,dark,system'],
            'accent' => ['required', 'string', 'in:neutral,blue,green,red,violet,indigo,amber'],
            'sidebar_bg' => ['required', 'string', 'in:default,slate,blue,green,amber,red,violet,indigo'],
            'header_bg' => ['required', 'string', 'in:default,slate,blue,green,amber,red,violet,indigo'],
            'body_bg' => ['required', 'string', 'in:default,slate,blue,green,amber,red,violet,indigo'],
        ]);

        Auth::user()->update($validated);

        $this->dispatch('appearance-updated', appearance: $this->appearance, accent: $this->accent, sidebar_bg: $this->sidebar_bg, header_bg: $this->header_bg, body_bg: $this->body_bg);
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Appearance')" :subheading="__('Update the appearance settings for your account')">
        <form wire:submit="save" class="space-y-6">
            <div>
                <flux:text class="mb-2 block font-medium">{{ __('Theme mode') }}</flux:text>
                <flux:radio.group wire:model="appearance" variant="segmented">
                    <flux:radio value="light" icon="sun">{{ __('Light') }}</flux:radio>
                    <flux:radio value="dark" icon="moon">{{ __('Dark') }}</flux:radio>
                    <flux:radio value="system" icon="computer-desktop">{{ __('System') }}</flux:radio>
                </flux:radio.group>
            </div>

            <div>
                <flux:text class="mb-2 block font-medium">{{ __('Sidebar background') }}</flux:text>
                <div class="flex flex-wrap gap-2">
                    @foreach(['default' => 'border-zinc-300 bg-zinc-100 dark:bg-zinc-600', 'slate' => 'bg-slate-500', 'blue' => 'bg-blue-500', 'green' => 'bg-green-500', 'amber' => 'bg-amber-500', 'red' => 'bg-red-500', 'violet' => 'bg-violet-500', 'indigo' => 'bg-indigo-500'] as $value => $dotClass)
                        <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-zinc-200 px-3 py-2 has-[:checked]:border-accent has-[:checked]:ring-2 has-[:checked]:ring-accent dark:border-zinc-600 dark:has-[:checked]:border-accent">
                            <input type="radio" wire:model="sidebar_bg" value="{{ $value }}" class="size-4 border-zinc-300 text-accent focus:ring-accent dark:border-zinc-600">
                            <span class="size-4 rounded-full {{ $dotClass }} shrink-0"></span>
                            <span class="text-sm">{{ __($value === 'default' ? 'Default' : ucfirst($value)) }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div>
                <flux:text class="mb-2 block font-medium">{{ __('Header background') }}</flux:text>
                <div class="flex flex-wrap gap-2">
                    @foreach(['default' => 'border-zinc-300 bg-zinc-100 dark:bg-zinc-600', 'slate' => 'bg-slate-500', 'blue' => 'bg-blue-500', 'green' => 'bg-green-500', 'amber' => 'bg-amber-500', 'red' => 'bg-red-500', 'violet' => 'bg-violet-500', 'indigo' => 'bg-indigo-500'] as $value => $dotClass)
                        <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-zinc-200 px-3 py-2 has-[:checked]:border-accent has-[:checked]:ring-2 has-[:checked]:ring-accent dark:border-zinc-600 dark:has-[:checked]:border-accent">
                            <input type="radio" wire:model="header_bg" value="{{ $value }}" class="size-4 border-zinc-300 text-accent focus:ring-accent dark:border-zinc-600">
                            <span class="size-4 rounded-full {{ $dotClass }} shrink-0"></span>
                            <span class="text-sm">{{ __($value === 'default' ? 'Default' : ucfirst($value)) }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div>
                <flux:text class="mb-2 block font-medium">{{ __('Accent color') }}</flux:text>
                <div class="flex flex-wrap gap-2">
                    @foreach(['neutral' => __('Neutral'), 'blue' => __('Blue'), 'green' => __('Green'), 'red' => __('Red'), 'violet' => __('Violet'), 'indigo' => __('Indigo'), 'amber' => __('Amber')] as $value => $label)
                        <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-zinc-200 px-3 py-2 has-[:checked]:border-accent has-[:checked]:ring-2 has-[:checked]:ring-accent dark:border-zinc-600 dark:has-[:checked]:border-accent">
                            <input type="radio" wire:model="accent" value="{{ $value }}" class="size-4 border-zinc-300 text-accent focus:ring-accent dark:border-zinc-600">
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
                <flux:text class="mb-2 block font-medium">{{ __('Body background') }}</flux:text>
                <div class="flex flex-wrap gap-2">
                    @foreach(['default' => 'border-zinc-300 bg-zinc-100 dark:bg-zinc-600', 'slate' => 'bg-slate-500', 'blue' => 'bg-blue-500', 'green' => 'bg-green-500', 'amber' => 'bg-amber-500', 'red' => 'bg-red-500', 'violet' => 'bg-violet-500', 'indigo' => 'bg-indigo-500'] as $value => $dotClass)
                        <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-zinc-200 px-3 py-2 has-[:checked]:border-accent has-[:checked]:ring-2 has-[:checked]:ring-accent dark:border-zinc-600 dark:has-[:checked]:border-accent">
                            <input type="radio" wire:model="body_bg" value="{{ $value }}" class="size-4 border-zinc-300 text-accent focus:ring-accent dark:border-zinc-600">
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
