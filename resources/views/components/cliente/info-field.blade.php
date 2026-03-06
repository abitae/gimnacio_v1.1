@props(['label', 'value'])

@if ($value)
    <div>
        <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $label }}</p>
        <p class="text-xs text-zinc-900 dark:text-zinc-100 @if($label === 'Email') truncate @elseif($label === 'Dirección') line-clamp-2 @endif">{{ $value }}</p>
    </div>
@endif

