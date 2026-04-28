@php
    $validHeights = ['x1', 'x15', 'x2', 'x25', 'x3'];
    $validAnimations = [
        'fadeInLeft', 'fadeInRight', 'fadeInUp', 'fadeInDown',
        'fadeInLeftShorter', 'fadeInRightShorter', 'fadeInUpShorter', 'fadeInDownShorter',
        'none',
    ];

    $cols      = min(12, max(1, (int) ($attrs['cols'] ?? 12)));
    $colsSm    = isset($attrs['cols-sm']) ? min(12, max(1, (int) $attrs['cols-sm'])) : null;
    $colsMd    = isset($attrs['cols-md']) ? min(12, max(1, (int) $attrs['cols-md'])) : null;
    $height    = in_array($attrs['height'] ?? 'x1', $validHeights) ? ($attrs['height'] ?? 'x1') : 'x1';
    $animation = in_array($attrs['animation'] ?? 'none', $validAnimations)
        ? ($attrs['animation'] ?? 'none') : 'none';
    $delay     = $attrs['delay'] ?? '0s';
    $extraClass = $attrs['class'] ?? null;

    $classes = collect([
        'grid-item',
        'col-lg-' . $cols,
        $colsSm ? 'col-sm-' . $colsSm : null,
        $colsMd ? 'col-md-' . $colsMd : null,
        'height-' . $height,
        $animation !== 'none' ? 'appear-animate' : null,
        $extraClass,
    ])->filter()->implode(' ');
@endphp

<div class="{{ $classes }}"
     @if ($animation !== 'none')
         data-animation-options="{{ json_encode(['name' => $animation, 'delay' => $delay, 'duration' => '1s']) }}"
     @endif>
    {!! $content !!}
</div>
