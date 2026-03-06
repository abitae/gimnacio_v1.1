@props(['label', 'value'])

@if ($value)
    <div>
        <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $label }}</p>
        <p class="text-xs text-zinc-900 dark:text-zinc-100 line-clamp-3">{{ $value }}</p>
    </div>
@endif
