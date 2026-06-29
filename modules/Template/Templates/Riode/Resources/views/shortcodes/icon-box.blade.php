@php
    $icon        = $attrs['icon'] ?? null;
    $title       = $attrs['title'] ?? null;
    $description = $attrs['description'] ?? null;
    $layout      = in_array($attrs['layout'] ?? '', ['top','side']) ? $attrs['layout'] : 'top';
    $style       = in_array($attrs['style'] ?? '', ['default','border','inversed','solid','tiny']) ? $attrs['style'] : 'default';
    $color       = in_array($attrs['color'] ?? '', ['default','primary','secondary','dark']) ? $attrs['color'] : 'default';
    $href        = $attrs['href'] ?? null;
    $align       = in_array($attrs['align'] ?? '', ['left','center','right']) ? $attrs['align'] : 'center';
    $extraClass  = $attrs['class'] ?? null;

    if (! $icon || ! $title) {
        return;
    }

    $classes = collect([
        'icon-box',
        $layout === 'side'     ? 'icon-box-side' : null,
        $style === 'border'    ? 'icon-border' : null,
        $style === 'inversed'  ? 'icon-inversed' : null,
        $style === 'solid'     ? 'icon-solid' : null,
        $style === 'tiny'      ? 'icon-tiny' : null,
        $color !== 'default'   ? 'icon-color-' . $color : null,
        'text-' . $align,
        $extraClass,
    ])->filter()->implode(' ');

    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }} class="{{ $classes }}"@if ($href) href="{{ $href }}"@endif>
    <span class="icon-box-icon">
        <i class="{{ $icon }}" aria-hidden="true"></i>
    </span>
    <div class="icon-box-content">
        <h4 class="icon-box-title">{{ $title }}</h4>
        @if ($description)
            <p>{{ $description }}</p>
        @endif
    </div>
</{{ $tag }}>
