@php
    $image = $attrs['image'] ?? null;
    $alt = $attrs['alt'] ?? 'hotspot';
    $width = max(1, (int) ($attrs['width'] ?? 1180));
    $height = max(1, (int) ($attrs['height'] ?? 600));
    $animation = $attrs['animation'] ?? null;

    $classes = collect([
        'banner',
        'banner-hotspot',
        ! empty($animation) ? 'appear-animate' : null,
        ! empty($attrs['class']) ? htmlspecialchars($attrs['class']) : null,
    ])->filter()->implode(' ');

    $hotspotId = 'hotspot-' . substr(md5(uniqid()), 0, 8);
@endphp

<div id="{{ $hotspotId }}"
     class="{{ $classes }}"
     @if(! empty($animation))
         data-animation-options='{"name":"{{ htmlspecialchars($animation) }}"}'
     @endif>

    @if(! empty($image))
        <figure>
            <img src="{{ htmlspecialchars($image) }}"
                 alt="{{ htmlspecialchars($alt) }}"
                 width="{{ $width }}"
                 height="{{ $height }}"
                 loading="lazy">
        </figure>
    @endif

    <div class="hotspot-container">
        {!! $content !!}
    </div>
</div>

@once
    @push('scripts')
    <script>
    $(function () {
        // Hover: mostrar/ocultar tooltip
        $(document).on('mouseenter', '.banner-hotspot .hotspot', function () {
            $(this).addClass('hover');
        });
        $(document).on('mouseleave', '.banner-hotspot .hotspot', function () {
            $(this).removeClass('hover');
        });

        // Mobile touch: toggle on click, cerrar el resto
        $(document).on('click', '.banner-hotspot .hotspot > a', function (e) {
            e.preventDefault();
            var $hot = $(this).closest('.hotspot');
            $('.hotspot').not($hot).removeClass('hover');
            $hot.toggleClass('hover');
        });
    });
    </script>
    @endpush
@endonce
