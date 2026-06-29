@php
    // TODO: Acoplar a módulo Vendor cuando esté disponible en el proyecto.
    // El proyecto NO tiene módulo Marketplace/Multi-vendor. Este shortcode renderiza
    // exclusivamente con los datos pasados como atributos (sin consulta a BD).

    $layoutClass = match (in_array($attrs['layout'] ?? '', ['style2', 'style3'], true) ? $attrs['layout'] : 'default') {
        'style2' => ' vendor-type2',
        'style3' => ' vendor-type3',
        default  => '',
    };

    $name          = $attrs['name'] ?? '';
    $href          = $attrs['href'] ?? '#';
    $logo          = $attrs['logo'] ?? '';
    $productsCount = isset($attrs['products-count']) ? (int) $attrs['products-count'] : null;
    $hasRating     = isset($attrs['rating']);
    $rating        = $hasRating ? max(0, min(100, (int) $attrs['rating'])) : null;
@endphp

<div class="vendor-widget{{ $layoutClass }}">
    <div class="vendor-details">
        <figure class="vendor-logo">
            <a href="{{ $href }}">
                <img src="{{ $logo }}"
                     alt="{{ $name }} logo"
                     width="70" height="70"
                     loading="lazy">
            </a>
        </figure>

        <div class="vendor-personal">
            <h4 class="vendor-name">
                <a href="{{ $href }}">{{ $name }}</a>
                @if ($productsCount !== null)
                    <span class="vendor-products-count">
                        @if ($productsCount > 0)
                            ( {{ $productsCount }} Productos )
                        @else
                            (Sin productos)
                        @endif
                    </span>
                @endif
            </h4>

            @if ($hasRating)
                <div class="ratings-container mb-0">
                    <div class="ratings-full">
                        <span class="ratings" data-rating="{{ $rating }}"></span>
                        <span class="tooltiptext tooltip-top">{{ number_format($rating / 20, 1) }} / 5</span>
                    </div>
                </div>
            @endif
        </div>
    </div>

    @if (!empty($productImages))
        <div class="vendor-products grid-type gutter-xs">
            @foreach ($productImages as $img)
                <div class="vendor-product">
                    <figure class="product-media">
                        <a href="{{ $img['href'] ?? '#' }}">
                            <img src="{{ $img['url'] }}"
                                 alt="{{ $img['alt'] ?? 'producto' }}"
                                 loading="lazy">
                        </a>
                    </figure>
                </div>
            @endforeach
        </div>
    @endif
</div>

@once
@push('scripts')
<script>
(function () {
    'use strict';
    // Apply rating bar width via JS to avoid inline style on the span
    document.querySelectorAll('.ratings[data-rating]').forEach(function (el) {
        var pct = parseInt(el.getAttribute('data-rating'), 10) || 0;
        el.style.width = Math.max(0, Math.min(100, pct)) + '%';
    });
}());
</script>
@endpush
@endonce
