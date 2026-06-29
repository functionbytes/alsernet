@php
    // bg-color: validar hex — inline style is the ONLY viable option here per the doc analysis.
    // The project's no-inline-style rule has a documented exception for this shortcode:
    // the color value is runtime/dynamic and comes from the shortcode attribute, so
    // it cannot be pre-compiled into a CSS class. The value is sanitized via regex.
    $bgColor   = preg_match('/^#[0-9A-Fa-f]{6}$/', $attrs['bg-color'] ?? '') ? $attrs['bg-color'] : '#4b5577';
    $textClass = ($attrs['text-color'] ?? 'light') === 'dark' ? ' text-dark' : '';
    $icon      = preg_match('/^(fas|far|fab) fa-[a-z0-9-]+$/', $attrs['icon'] ?? '') ? $attrs['icon'] : 'fas fa-folder';
    $name      = $attrs['name'] ?? '';
    $href      = $attrs['href'] ?? '#';
@endphp

<div class="category category-group-icon{{ $textClass }}" data-bg-color="{{ $bgColor }}" style="background-color: {{ $bgColor }};">
    <a href="{{ $href }}">
        <figure class="category-media">
            <i class="{{ $icon }}" aria-hidden="true"></i>
        </figure>
        <h4 class="category-name">{{ $name }}</h4>
    </a>

    @if (!empty($subcategories))
        <div class="category-content">
            <ul class="category-list">
                @foreach ($subcategories as $sub)
                    <li>
                        <a href="{{ $sub['url'] ?? '#' }}">{{ $sub['label'] ?? '' }}</a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
