@php
    $cols = $attrs['cols'] ?? 'auto';
    $gap  = in_array($attrs['gap'] ?? '', ['none', 'sm', 'default', 'lg'], true) ? $attrs['gap'] : 'default';

    $gapClass = match ($gap) {
        'none'    => ' gap-0',
        'sm'      => ' gap-2',
        'lg'      => ' gap-4',
        default   => '',
    };

    // --columns CSS variable is acceptable per project conventions (CSS custom property, not cosmetic inline style)
    $colsStyle = ($cols !== 'auto' && is_numeric($cols)) ? ' style="--columns: ' . (int) $cols . ';"' : '';
@endphp

<div class="category-column{{ $gapClass }}"{!! $colsStyle !!}>
    {!! $content !!}
</div>
