@props([
    'align' => 'right',
])

<div {{ $attributes->merge([
    'class' => 'table-actions inline-flex flex-wrap gap-0.5 ' . ($align === 'right' ? 'justify-end' : 'justify-start'),
]) }}>
    {{ $slot }}
</div>
