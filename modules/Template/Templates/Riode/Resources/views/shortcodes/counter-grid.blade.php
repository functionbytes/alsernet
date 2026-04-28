@php
    $layout          = in_array($attrs['layout'] ?? '', ['boxed','block','simple','background','inline'])
        ? $attrs['layout']
        : 'simple';
    $cols            = min(4, max(1, (int) ($attrs['cols'] ?? 4)));
    $colsTablet      = min(4, max(1, (int) ($attrs['cols-tablet'] ?? 2)));
    $colsMobile      = min(2, max(1, (int) ($attrs['cols-mobile'] ?? 1)));
    $gutter          = in_array($attrs['gutter'] ?? '', ['none','sm','md','lg']) ? $attrs['gutter'] : 'md';
    $background      = in_array($attrs['background'] ?? '', ['light','dark','image']) ? $attrs['background'] : 'none';
    $backgroundImage = $attrs['background-image'] ?? null;
    $align           = in_array($attrs['align'] ?? '', ['left','center','right']) ? $attrs['align'] : 'center';
    $extraClass      = $attrs['class'] ?? null;

    // Only divisors of 12 are supported; clamp to nearest valid value
    $validCols   = [1, 2, 3, 4, 6, 12];
    $cols        = in_array($cols, $validCols) ? $cols : 4;
    $colsTablet  = in_array($colsTablet, $validCols) ? $colsTablet : 2;
    $colsMobile  = in_array($colsMobile, $validCols) ? $colsMobile : 1;

    $colDesktop = (int) (12 / $cols);
    $colTablet  = (int) (12 / $colsTablet);
    $colMobile  = (int) (12 / $colsMobile);

    $gutterClass = match ($gutter) {
        'none' => 'g-0',
        'sm'   => 'g-2',
        'lg'   => 'g-4',
        default => 'g-3',
    };

    $wrapperClasses = collect([
        'counter-grid',
        'counter-grid-' . $layout,
        $background !== 'none' ? 'counter-bg-' . $background : null,
        'text-' . $align,
        $extraClass,
    ])->filter()->implode(' ');

    $itemLayoutClass = match ($layout) {
        'boxed'      => 'counter-box',
        'block'      => 'counter-block',
        'background' => 'counter-background',
        'inline'     => 'counter-inline',
        default      => 'counter-simple',
    };

    // Parse child [counter] items from rendered content
    $children = array_filter(
        array_map('trim', preg_split('/(?=<div class="counter-part)/', $content) ?: []),
        fn ($c) => $c !== ''
    );
@endphp

@if (empty($children))
    {{-- Empty container: nothing to render --}}
@else
    <div class="{{ $wrapperClasses }}"
         @if ($backgroundImage) data-bg-image="{{ $backgroundImage }}" @endif>

        @if ($layout === 'inline')
            <div class="d-flex flex-wrap align-items-center justify-content-center gap-3">
                @foreach ($children as $counter)
                    <div class="{{ $itemLayoutClass }}">
                        {!! $counter !!}
                    </div>
                @endforeach
            </div>
        @else
            <div class="row {{ $gutterClass }}">
                @foreach ($children as $counter)
                    <div class="col-{{ $colMobile }} col-md-{{ $colTablet }} col-lg-{{ $colDesktop }}">
                        <div class="{{ $itemLayoutClass }}">
                            {!! $counter !!}
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    @once
    @push('scripts')
    <script>
    (function () {
        // Apply background-image via JS (data attribute → CSS) to avoid inline styles
        $('.counter-grid[data-bg-image]').each(function () {
            var src = $(this).data('bg-image');
            if (src) {
                $(this).css('background-image', 'url(' + src + ')');
                $(this).addClass('counter-bg-cover');
            }
        });
    }());
    </script>
    @endpush
    @endonce
@endif
