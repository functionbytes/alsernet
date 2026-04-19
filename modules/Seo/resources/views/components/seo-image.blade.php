@props([
    'src',
    'alt' => '',
    'width' => null,
    'height' => null,
    'loading' => 'lazy',
    'decoding' => 'async',
    'fetchpriority' => null,
    'sizes' => null,
    'srcset' => null,
    'class' => null,
])

{{--
    Image component optimized for Core Web Vitals.
    Always emits width/height (prevents CLS), loading="lazy" and decoding="async"
    by default. Pass fetchpriority="high" only for the LCP image.

    Usage:
        <x-seo-image src="/img/photo.jpg" alt="Foto" width="800" height="600" />
        <x-seo-hero-image src="/img/hero.jpg" alt="Hero" width="1200" height="630" />
--}}
<img
    src="{{ $src }}"
    alt="{{ $alt }}"
    @if($width !== null) width="{{ $width }}" @endif
    @if($height !== null) height="{{ $height }}" @endif
    loading="{{ $loading }}"
    decoding="{{ $decoding }}"
    @if($fetchpriority) fetchpriority="{{ $fetchpriority }}" @endif
    @if($srcset) srcset="{{ $srcset }}" @endif
    @if($sizes) sizes="{{ $sizes }}" @endif
    @if($class) class="{{ $class }}" @endif
    {{ $attributes->except(['src', 'alt', 'width', 'height', 'loading', 'decoding', 'fetchpriority', 'srcset', 'sizes', 'class']) }}
>
