@php
    $validEffects = ['fade', 'zoom', 'rotate', 'slide', 'none'];
    $validNavStyles = ['bg', 'arrow', 'bg-arrow', 'simple'];
    $validDotsStyles = ['default', 'inner', 'white'];
    $validHeights = ['auto', 'sm', 'md', 'lg', 'full'];

    $effect = in_array($attrs['effect'] ?? 'fade', $validEffects) ? ($attrs['effect'] ?? 'fade') : 'fade';
    $autoplay = filter_var($attrs['autoplay'] ?? false, FILTER_VALIDATE_BOOLEAN);
    $autoplayTimeout = max(1000, (int) ($attrs['autoplay-timeout'] ?? 5000));
    $loop = filter_var($attrs['loop'] ?? false, FILTER_VALIDATE_BOOLEAN);
    $nav = filter_var($attrs['nav'] ?? true, FILTER_VALIDATE_BOOLEAN);
    $dots = filter_var($attrs['dots'] ?? false, FILTER_VALIDATE_BOOLEAN);
    $navStyle = in_array($attrs['nav-style'] ?? 'bg', $validNavStyles) ? ($attrs['nav-style'] ?? 'bg') : 'bg';
    $dotsStyle = in_array($attrs['dots-style'] ?? 'default', $validDotsStyles) ? ($attrs['dots-style'] ?? 'default') : 'default';
    $height = in_array($attrs['height'] ?? 'md', $validHeights) ? ($attrs['height'] ?? 'md') : 'md';

    [$animIn, $animOut] = match ($effect) {
        'fade'   => ['fadeIn', 'fadeOut'],
        'zoom'   => ['zoomIn', 'zoomOut'],
        'rotate' => ['rotateInUpLeft', 'rotateOutUpLeft'],
        'slide'  => ['slideInRight', 'slideOutLeft'],
        default  => [null, null],
    };

    $owlOptions = [
        'items'           => 1,
        'autoplay'        => $autoplay,
        'autoplayTimeout' => $autoplayTimeout,
        'loop'            => $loop,
        'nav'             => $nav,
        'dots'            => $dots,
    ];

    if ($animIn) {
        $owlOptions['animateIn'] = $animIn;
        $owlOptions['animateOut'] = $animOut;
    }

    $classes = collect([
        'big-slider',
        'owl-carousel',
        'owl-theme',
        'row',
        'cols-1',
        'gutter-no',
        $effect !== 'none' ? 'animation-slider' : null,
        $nav && str_contains($navStyle, 'bg') ? 'owl-nav-bg' : null,
        $nav && str_contains($navStyle, 'arrow') ? 'owl-nav-arrow' : null,
        $dots && $dotsStyle === 'inner' ? 'owl-dot-inner' : null,
        $dots && $dotsStyle === 'white' ? 'owl-dot-white' : null,
        $height !== 'auto' ? 'slider-height-' . $height : null,
        ! empty($attrs['class']) ? htmlspecialchars($attrs['class']) : null,
    ])->filter()->implode(' ');

    $sliderId = 'slider-' . substr(md5(uniqid()), 0, 8);
    $owlJson = json_encode($owlOptions, JSON_UNESCAPED_SLASHES);
@endphp

@once
    @push('styles')
    <link rel="stylesheet" href="{{ asset('modules/Theme/theme/libs/owl.carousel/dist/owl.carousel.min.css') }}">
    <link rel="stylesheet" href="{{ asset('modules/Theme/theme/libs/owl.carousel/dist/assets/owl.theme.default.min.css') }}">
    @endpush
@endonce

<div id="{{ $sliderId }}" class="{{ $classes }}" data-owl-options='{!! $owlJson !!}'>
    {!! $content !!}
</div>

@once
    @push('scripts')
    <script src="{{ asset('modules/Theme/theme/libs/owl.carousel/dist/owl.carousel.min.js') }}"></script>
    <script>
    $(function () {
        // Inicializar todos los sliders shortcode que aún no estén iniciados
        $('.big-slider.owl-carousel').each(function () {
            if ($(this).hasClass('owl-loaded')) return;
            var opts = $(this).data('owl-options') || {};
            $(this).owlCarousel(opts);
        });

        // Aplicar color de fondo de slides via data-bg-color
        $('.slide-item[data-bg-color]').each(function () {
            $(this).css('background-color', $(this).data('bg-color'));
        });

        // Disparar animaciones de los elementos al cambiar de slide
        $('.big-slider').on('changed.owl.carousel', function (event) {
            var $items = $(event.target).find('.owl-item');
            var $current = $items.eq(event.item.index);
            $current.find('[data-animation-options]').each(function () {
                var opts = $(this).data('animation-options');
                if (opts && opts.name) {
                    var $el = $(this);
                    setTimeout(function () {
                        $el.addClass('animated ' + opts.name);
                    }, opts.delay ? parseFloat(opts.delay) * 1000 : 0);
                }
            });
        });
    });
    </script>
    @endpush
@endonce
