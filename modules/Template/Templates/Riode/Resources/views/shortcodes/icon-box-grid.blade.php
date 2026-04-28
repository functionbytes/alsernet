@php
    $layout       = in_array($attrs['layout'] ?? '', ['grid','carousel']) ? $attrs['layout'] : 'grid';
    $cols         = min(4, max(1, (int) ($attrs['cols'] ?? 3)));
    $colsTablet   = min(4, max(1, (int) ($attrs['cols-tablet'] ?? 2)));
    $colsMobile   = min(2, max(1, (int) ($attrs['cols-mobile'] ?? 1)));
    $gutter       = in_array($attrs['gutter'] ?? '', ['none','sm','md','lg']) ? $attrs['gutter'] : 'md';
    $background   = in_array($attrs['background'] ?? '', ['light','grey','dark']) ? $attrs['background'] : 'none';
    $autoplay     = filter_var($attrs['autoplay'] ?? false, FILTER_VALIDATE_BOOLEAN);
    $autoplaySpeed = (int) ($attrs['autoplay-speed'] ?? 5000);
    $showArrows   = filter_var($attrs['show-arrows'] ?? true, FILTER_VALIDATE_BOOLEAN);
    $showDots     = filter_var($attrs['show-dots'] ?? false, FILTER_VALIDATE_BOOLEAN);
    $align        = in_array($attrs['align'] ?? '', ['left','center','right']) ? $attrs['align'] : 'center';
    $extraClass   = $attrs['class'] ?? null;

    // Only valid Bootstrap divisors
    $validCols  = [1, 2, 3, 4, 6, 12];
    $cols       = in_array($cols, $validCols) ? $cols : 3;
    $colsTablet = in_array($colsTablet, $validCols) ? $colsTablet : 2;
    $colsMobile = in_array($colsMobile, $validCols) ? $colsMobile : 1;

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
        'icon-box-grid',
        $background !== 'none' ? 'bg-' . $background : null,
        'text-' . $align,
        $extraClass,
    ])->filter()->implode(' ');

    // Parse rendered icon-box items (content is already compiled by the shortcode callback)
    $children = array_filter(
        array_map('trim', preg_split('/(?=<(?:div|a) class="icon-box)/', $content) ?: []),
        fn ($c) => str_contains($c, 'icon-box')
    );

    $childCount = count($children);

    // Degrade carousel to grid if only 1 item
    if ($layout === 'carousel' && $childCount <= 1) {
        $layout = 'grid';
    }

    $carouselId = 'ibg-' . uniqid();
@endphp

@if (empty($children))
    {{-- Empty: nothing to render --}}
@elseif ($layout === 'carousel')
    <div class="{{ $wrapperClasses }}">
        <div id="{{ $carouselId }}"
             class="owl-carousel owl-theme"
             data-owl-options="{{ json_encode([
                 'items'           => $cols,
                 'loop'            => true,
                 'autoplay'        => $autoplay,
                 'autoplayTimeout' => $autoplaySpeed,
                 'nav'             => $showArrows,
                 'dots'            => $showDots,
                 'responsive'      => [
                     '0'   => ['items' => $colsMobile],
                     '768' => ['items' => $colsTablet],
                     '992' => ['items' => $cols],
                 ],
             ]) }}"
             role="region"
             aria-label="{{ __('shortcode::shortcode.icon_box_carousel_label') }}">
            @foreach ($children as $iconBox)
                {!! $iconBox !!}
            @endforeach
        </div>
    </div>

    @once
    @push('scripts')
    <script>
    $(document).ready(function () {
        $('.icon-box-grid .owl-carousel').each(function () {
            var opts = {};
            try { opts = JSON.parse($(this).attr('data-owl-options') || '{}'); } catch (e) {}
            $(this).owlCarousel(opts);
        });
    });
    </script>
    @endpush
    @endonce
@else
    <div class="{{ $wrapperClasses }}">
        <div class="row {{ $gutterClass }}">
            @foreach ($children as $iconBox)
                <div class="col-{{ $colMobile }} col-md-{{ $colTablet }} col-lg-{{ $colDesktop }}">
                    {!! $iconBox !!}
                </div>
            @endforeach
        </div>
    </div>
@endif
