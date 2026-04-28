@php
    $validGutters = ['none', 'sm', 'md', 'lg'];

    $gutter     = in_array($attrs['gutter'] ?? 'md', $validGutters) ? ($attrs['gutter'] ?? 'md') : 'md';
    $extraClass = $attrs['class'] ?? null;

    $classes = collect([
        'row',
        'grid',
        $gutter !== 'none' ? 'gutter-' . $gutter : 'g-0',
        $extraClass,
    ])->filter()->implode(' ');
@endphp

@if (trim($content) !== '')
    <div class="{{ $classes }}">
        {!! $content !!}
        {{-- Grid space helper for masonry/dense CSS grid layout --}}
        <div class="grid-space col-1" aria-hidden="true"></div>
    </div>
@endif
