@php
    $validStyles = ['default', 'light', 'icon', 'ellipse', 'semi-circle', 'classic', 'badge'];
    $validShapes = ['default', 'rounded', 'ellipse', 'absolute'];
    $validHovers = ['none', 'zoom', 'light', 'dark'];

    $name       = $attrs['name'] ?? '';
    $href       = $attrs['href'] ?? '#';
    $image      = $attrs['image'] ?? null;
    $icon       = $attrs['icon'] ?? null;
    $description = $attrs['description'] ?? null;
    $count      = isset($attrs['count']) && (int) $attrs['count'] > 0 ? (int) $attrs['count'] : null;
    $countLabel = $attrs['count-label'] ?? __('shortcode::shortcode.category_card.products');
    $style      = in_array($attrs['style'] ?? 'default', $validStyles) ? ($attrs['style'] ?? 'default') : 'default';
    $shape      = in_array($attrs['shape'] ?? 'rounded', $validShapes) ? ($attrs['shape'] ?? 'rounded') : 'rounded';
    $hover      = in_array($attrs['hover'] ?? 'zoom', $validHovers) ? ($attrs['hover'] ?? 'zoom') : 'zoom';
    $bgColor    = $attrs['bg-color'] ?? null;
    $buttonText = $attrs['button-text'] ?? null;
    $buttonHref = $attrs['button-href'] ?? $href;
    $extraClass = $attrs['class'] ?? null;

    // Validate hex color; reject if not valid
    if ($bgColor && ! preg_match('/^#[0-9A-Fa-f]{3,6}$/', $bgColor)) {
        $bgColor = null;
    }

    $classes = collect([
        'category',
        $style === 'default'     ? 'category-default' : null,
        $style === 'light'       ? 'category-light' : null,
        $style === 'icon'        ? 'category-icon' : null,
        $style === 'ellipse'     ? 'category-ellipse' : null,
        $style === 'semi-circle' ? 'category-ellipse2' : null,
        $style === 'classic'     ? 'category-classic' : null,
        $style === 'badge'       ? 'category-badge' : null,
        $shape === 'rounded'  ? 'category-rounded' : null,
        $shape === 'ellipse'  ? 'category-ellipse-shape' : null,
        $shape === 'absolute' ? 'category-absolute' : null,
        $hover === 'zoom'  ? 'overlay-zoom' : null,
        $hover === 'light' ? 'overlay-light' : null,
        $hover === 'dark'  ? 'overlay-dark' : null,
        $extraClass,
    ])->filter()->implode(' ');
@endphp

<div class="{{ $classes }}"
     @if ($bgColor) data-bg-color="{{ e($bgColor) }}" @endif>
    <a href="{{ htmlspecialchars($href) }}">
        <figure class="category-media">
            @if (! empty($image))
                <img src="{{ htmlspecialchars($image) }}"
                     alt="{{ e($name) }}"
                     width="280" height="280"
                     loading="lazy">
            @elseif (! empty($icon))
                <i class="{{ e($icon) }}" aria-hidden="true"></i>
            @endif
        </figure>
    </a>

    <div class="category-content">
        <h4 class="category-name">
            <a href="{{ htmlspecialchars($href) }}">{{ $name }}</a>
        </h4>

        @if ($description)
            <p class="category-descri">{{ $description }}</p>
        @endif

        @if ($count !== null)
            <span class="category-count"><span>{{ $count }}</span> {{ $countLabel }}</span>
        @endif

        @if ($buttonText)
            <a href="{{ htmlspecialchars($buttonHref) }}" class="btn btn-primary">{{ $buttonText }}</a>
        @endif
    </div>
</div>

@once
@push('scripts')
<script>
$(function () {
    // Apply bg-color via CSS custom property (avoids inline style)
    $('.category[data-bg-color]').each(function () {
        $(this).get(0).style.setProperty('--cat-bg', $(this).data('bg-color'));
    });
});
</script>
@endpush
@endonce
