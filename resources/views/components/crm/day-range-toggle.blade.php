@props([
    'wireModel',
    'current',
    'options',
    'labelText' => null,
])

<div class="flex gap-2 items-center flex-wrap" role="group" @if($labelText) aria-label="{{ $labelText }}" @endif>
    @if ($labelText)
        <span class="text-sm text-zinc-500">{{ $labelText }}</span>
    @endif
    @foreach ($options as $value => $label)
        <flux:button size="xs" variant="{{ (string) $current === (string) $value ? 'primary' : 'ghost' }}"
            wire:click="$set('{{ $wireModel }}', '{{ $value }}')"
            aria-pressed="{{ (string) $current === (string) $value ? 'true' : 'false' }}">
            {{ $label }}
        </flux:button>
    @endforeach
</div>
