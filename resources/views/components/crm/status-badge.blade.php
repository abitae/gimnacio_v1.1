@props([
    'status',
    'type' => 'default',
    'label' => null,
])

@php
    $palettes = [
        'deal' => [
            'open' => 'bg-sky-100 text-sky-800 dark:bg-sky-900/40 dark:text-sky-200',
            'won' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200',
            'lost' => 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-200',
        ],
        'task' => [
            'pending' => 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300',
            'done' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200',
            'overdue' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200',
        ],
        'campaign-target' => [
            'pending' => 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300',
            'contacted' => 'bg-sky-100 text-sky-800 dark:bg-sky-900/40 dark:text-sky-200',
            'won' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200',
            'lost' => 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-200',
        ],
        'mensaje' => [
            'enviado' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
            'fallido' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
            'pendiente' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
        ],
        'campaign' => [
            'draft' => 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300',
            'active' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200',
            'done' => 'bg-sky-100 text-sky-800 dark:bg-sky-900/40 dark:text-sky-200',
        ],
    ];

    $classes = $palettes[$type][$status]
        ?? 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300';
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium whitespace-nowrap {$classes}"]) }}>
    {{ $label ?? $status }}
</span>
