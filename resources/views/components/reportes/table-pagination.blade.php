@props([
    'paginator',
    'model',
    'options' => [10, 15, 25, 50],
])

@if ($paginator->total() > 0)
    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-zinc-200 px-4 py-3 text-xs text-zinc-600 dark:border-zinc-700 dark:text-zinc-400 print:hidden">
        <label class="flex items-center gap-2">
            Filas
            <select wire:model.live="{{ $model }}"
                class="rounded-lg border border-zinc-300 bg-white px-2 py-1 text-xs dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                @foreach ($options as $option)
                    <option value="{{ $option }}">{{ $option }}</option>
                @endforeach
            </select>
        </label>

        <div class="text-zinc-500 dark:text-zinc-400">
            {{ $paginator->firstItem() }}-{{ $paginator->lastItem() }} de {{ $paginator->total() }}
        </div>

        @if ($paginator->hasPages())
            <div class="w-full sm:w-auto">
                {{ $paginator->links() }}
            </div>
        @endif
    </div>
@endif
