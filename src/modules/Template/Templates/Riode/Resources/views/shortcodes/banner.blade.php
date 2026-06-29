@php
    $validTypes = ['image', 'video', 'parallax'];
    $validHeights = ['auto', 'sm', 'md', 'lg', 'full'];
    $validTextAligns = ['left', 'center', 'right'];
    $validTextColors = ['light', 'dark'];
    $validVerticalPositions = ['top', 'middle', 'bottom'];
    $validHorizontalPositions = ['left', 'center', 'right'];
    $validButtonStyles = ['primary', 'dark', 'white', 'outline-white', 'outline-dark'];
    $validButtonShapes = ['rounded', 'ellipse', 'square'];
    $validHoverEffects = ['none', 'effect1', 'effect2', 'effect3', 'effect4', 'zoom', 'light', 'dark', 'zoom-light', 'zoom-dark'];
    $validFilters = ['none', 'blur', 'brightness', 'contrast', 'grayscale', 'hue', 'opacity', 'saturate', 'sepia'];
    $validKenburns = ['none', 'left', 'right', 'top', 'bottom'];

    $type = in_array($attrs['type'] ?? 'image', $validTypes) ? ($attrs['type'] ?? 'image') : 'image';
    $image = $attrs['image'] ?? null;
    $imageAlt = $attrs['image-alt'] ?? ($attrs['title'] ?? 'banner');
    $bgColor = $attrs['bg-color'] ?? null;
    $height = in_array($attrs['height'] ?? 'auto', $validHeights) ? ($attrs['height'] ?? 'auto') : 'auto';
    $parallaxOffset = (int) ($attrs['parallax-offset'] ?? -60);
    $videoSrc = $attrs['video-src'] ?? null;
    $title = $attrs['title'] ?? null;
    $subtitle = $attrs['subtitle'] ?? null;
    $description = $attrs['description'] ?? null;
    $textAlign = in_array($attrs['text-align'] ?? 'left', $validTextAligns) ? ($attrs['text-align'] ?? 'left') : 'left';
    $textColor = in_array($attrs['text-color'] ?? 'light', $validTextColors) ? ($attrs['text-color'] ?? 'light') : 'light';
    $verticalPosition = in_array($attrs['vertical-position'] ?? 'middle', $validVerticalPositions) ? ($attrs['vertical-position'] ?? 'middle') : 'middle';
    $horizontalPosition = in_array($attrs['horizontal-position'] ?? 'left', $validHorizontalPositions) ? ($attrs['horizontal-position'] ?? 'left') : 'left';
    $buttonText = $attrs['button-text'] ?? null;
    $buttonHref = $attrs['button-href'] ?? null;
    $buttonStyle = in_array($attrs['button-style'] ?? 'primary', $validButtonStyles) ? ($attrs['button-style'] ?? 'primary') : 'primary';
    $buttonShape = in_array($attrs['button-shape'] ?? 'rounded', $validButtonShapes) ? ($attrs['button-shape'] ?? 'rounded') : 'rounded';
    $animation = $attrs['animation'] ?? null;
    $hoverEffect = in_array($attrs['hover-effect'] ?? 'none', $validHoverEffects) ? ($attrs['hover-effect'] ?? 'none') : 'none';
    $filter = in_array($attrs['filter'] ?? 'none', $validFilters) ? ($attrs['filter'] ?? 'none') : 'none';
    $kenburns = in_array($attrs['kenburns'] ?? 'none', $validKenburns) ? ($attrs['kenburns'] ?? 'none') : 'none';
    $kenburnsDuration = $attrs['kenburns-duration'] ?? '20s';

    // Si type=video sin video-src, degradar a image
    if ($type === 'video' && empty($videoSrc)) {
        $type = 'image';
    }

    $effectMap = [
        'effect1'   => 'overlay-effect1',
        'effect2'   => 'overlay-effect2',
        'effect3'   => 'overlay-effect3',
        'effect4'   => 'overlay-effect4',
        'zoom'      => 'overlay-zoom',
        'light'     => 'overlay-light',
        'dark'      => 'overlay-dark',
        'zoom-light' => 'overlay-zoom overlay-light',
        'zoom-dark'  => 'overlay-zoom overlay-dark',
    ];

    $classes = collect([
        'banner',
        'banner-fixed',
        $type === 'image' ? 'banner-image' : null,
        $type === 'parallax' ? 'banner-background parallax' : null,
        $type === 'video' ? 'video-banner' : null,
        $hoverEffect !== 'none' ? ($effectMap[$hoverEffect] ?? null) : null,
        $filter !== 'none' ? 'overlay-filter overlay-' . $filter : null,
        $height !== 'auto' ? 'banner-h-' . $height : null,
        ! empty($attrs['class']) ? htmlspecialchars($attrs['class']) : null,
    ])->filter()->implode(' ');

    $contentClasses = collect([
        'banner-content',
        $verticalPosition === 'middle' ? 'y-50' : null,
        $verticalPosition === 'bottom' ? 'y-bottom' : null,
        $horizontalPosition === 'center' ? 'x-50' : null,
        $horizontalPosition === 'right' ? 'x-right' : null,
        $textAlign === 'center' ? 'text-center' : null,
        $textAlign === 'right' ? 'text-right' : null,
        $textColor === 'light' ? 'text-white' : null,
    ])->filter()->implode(' ');

    $btnClasses = collect([
        'btn',
        $buttonStyle === 'primary' ? 'btn-primary' : null,
        $buttonStyle === 'dark' ? 'btn-dark' : null,
        $buttonStyle === 'white' ? 'btn-white' : null,
        $buttonStyle === 'outline-white' ? 'btn-outline btn-white' : null,
        $buttonStyle === 'outline-dark' ? 'btn-outline btn-dark' : null,
        $buttonShape === 'rounded' ? 'btn-rounded' : null,
        $buttonShape === 'ellipse' ? 'btn-ellipse' : null,
    ])->filter()->implode(' ');

    $kenburnsClass = $kenburns !== 'none' ? 'kenBurnsTo' . ucfirst($kenburns) : '';
    $bannerId = 'banner-' . substr(md5(uniqid()), 0, 8);
@endphp

<div id="{{ $bannerId }}"
     class="{{ $classes }}"
     @if($type === 'parallax' && ! empty($image))
         data-image-src="{{ htmlspecialchars($image) }}"
         data-option='{"offset":{{ $parallaxOffset }}}'
     @endif
     @if(! empty($bgColor)) data-bg-color="{{ htmlspecialchars($bgColor) }}" @endif>

    @if($type !== 'parallax' && ! empty($image))
        <figure class="{{ $kenburnsClass }}"
                @if($kenburnsClass) data-kenburns-duration="{{ htmlspecialchars($kenburnsDuration) }}" @endif>
            <img src="{{ htmlspecialchars($image) }}"
                 alt="{{ htmlspecialchars($imageAlt) }}"
                 loading="lazy">
        </figure>
    @endif

    <div class="container">
        <div class="{{ $contentClasses }}"
             @if(! empty($animation))
                 data-animation-options='{"name":"{{ $animation }}","duration":"1s"}'
             @endif>

            @if(! empty($subtitle))
                <h4 class="banner-subtitle">{{ $subtitle }}</h4>
            @endif

            @if(! empty($title))
                <h3 class="banner-title">{{ $title }}</h3>
            @endif

            @if(! empty($description))
                <p>{{ $description }}</p>
            @endif

            @if($type === 'video' && ! empty($videoSrc))
                <a href="{{ htmlspecialchars($videoSrc) }}"
                   class="btn-dark btn-iframe btn-play"
                   aria-label="{{ __('shortcode::shortcode.banner.play_video') }}">
                    <i class="fas fa-play" aria-hidden="true"></i>
                </a>
            @endif

            @if(! empty($buttonText) && ! empty($buttonHref))
                <a href="{{ htmlspecialchars($buttonHref) }}" class="{{ $btnClasses }}">
                    {{ $buttonText }}<i class="fas fa-arrow-right ms-2" aria-hidden="true"></i>
                </a>
            @endif

        </div>
    </div>
</div>

@push('scripts')
<script>
$(function () {
    // Aplicar color de fondo via data-bg-color (evita inline style en template)
    $('#{{ $bannerId }}[data-bg-color]').each(function () {
        $(this).css('background-color', $(this).data('bg-color'));
    });

    // Aplicar duración kenburns via JS (evita inline style en template)
    $('#{{ $bannerId }} [data-kenburns-duration]').each(function () {
        this.style.animationDuration = $(this).data('kenburns-duration');
    });
});
</script>
@endpush
