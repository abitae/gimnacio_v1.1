@props([
    'title',
    'addLabel' => null,
    'addAction' => null,
    'permission' => 'crm.crear',
])

@php
    $addActionTarget = $addAction ? \Illuminate\Support\Str::before($addAction, '(') : null;
@endphp

<div class="rounded-xl border border-zinc-200 dark:border-zinc-700 p-4">
    <div class="flex items-center justify-between mb-3">
        <h2 class="font-medium text-zinc-800 dark:text-zinc-200">{{ $title }}</h2>
        @can($permission)
            @if ($addLabel && $addAction)
                <flux:button size="xs" variant="primary" wire:click="{{ $addAction }}"
                    wire:loading.attr="disabled" wire:target="{{ $addActionTarget }}">{{ $addLabel }}</flux:button>
            @endif
        @endcan
    </div>
    <ul class="space-y-2 text-sm">
        {{ $slot }}
    </ul>
</div>
