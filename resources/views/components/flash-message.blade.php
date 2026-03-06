@props(['type' => 'success'])

@php
    $classes = match($type) {
        'success' => 'rounded-lg bg-green-50 p-2.5 text-xs text-green-800 dark:bg-green-900/20 dark:text-green-400',
        'error' => 'rounded-lg bg-red-50 p-2.5 text-xs text-red-800 dark:bg-red-900/20 dark:text-red-400',
        'warning' => 'rounded-lg bg-yellow-50 p-2.5 text-xs text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400',
        'info' => 'rounded-lg bg-blue-50 p-2.5 text-xs text-blue-800 dark:bg-blue-900/20 dark:text-blue-400',
        default => 'rounded-lg bg-gray-50 p-2.5 text-xs text-gray-800 dark:bg-gray-900/20 dark:text-gray-400',
    };
@endphp

@if (session()->has($type))
    <div class="{{ $classes }}" role="alert" aria-live="polite">
        {{ session($type) }}
    </div>
@endif

