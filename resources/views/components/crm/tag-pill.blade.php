@props([
    'color' => null,
    'label',
])

@php
    $hex = ltrim($color ?: '#71717a', '#');
    if (strlen($hex) === 3) {
        $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
    }
    if (strlen($hex) !== 6 || ! ctype_xdigit($hex)) {
        $hex = '71717a';
    }

    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));

    // Luminancia relativa aproximada (sRGB) para decidir si el texto necesita oscurecerse u aclararse.
    $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;

    $lightText = $luminance > 0.55
        ? sprintf('rgb(%d, %d, %d)', (int) round($r * 0.55), (int) round($g * 0.55), (int) round($b * 0.55))
        : sprintf('rgb(%d, %d, %d)', $r, $g, $b);

    $darkText = $luminance < 0.45
        ? sprintf('rgb(%d, %d, %d)', (int) round($r + (255 - $r) * 0.55), (int) round($g + (255 - $g) * 0.55), (int) round($b + (255 - $b) * 0.55))
        : sprintf('rgb(%d, %d, %d)', $r, $g, $b);
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-[var(--tag-bg)] text-[var(--tag-fg-light)] dark:text-[var(--tag-fg-dark)]']) }}
    style="--tag-bg: rgba({{ $r }}, {{ $g }}, {{ $b }}, 0.18); --tag-fg-light: {{ $lightText }}; --tag-fg-dark: {{ $darkText }};">
    {{ $label }}
</span>
