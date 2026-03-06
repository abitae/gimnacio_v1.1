@props([
    'message' => 'No hay registros.',
    'actionLabel' => null,
    'actionHref' => null,
    'actionClick' => null,
])

<div class="flex flex-col items-center justify-center py-12 text-center">
    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $message }}</p>
    @if ($actionLabel && ($actionHref || $actionClick))
        <div class="mt-4">
            @if ($actionHref)
                <a href="{{ $actionHref }}" wire:navigate
                    class="inline-flex items-center justify-center rounded-lg bg-purple-600 px-4 py-2 text-sm font-medium text-white hover:bg-purple-500">
                    {{ $actionLabel }}
                </a>
            @else
                <button type="button" wire:click="{{ $actionClick }}"
                    class="inline-flex items-center justify-center rounded-lg bg-purple-600 px-4 py-2 text-sm font-medium text-white hover:bg-purple-500">
                    {{ $actionLabel }}
                </button>
            @endif
        </div>
    @endif
</div>
