@props([
    'src',
    'alt' => '',
    'width' => null,
    'height' => null,
    'sizes' => null,
    'srcset' => null,
    'class' => null,
])

{{--
    Hero/LCP image — eager load, high fetch priority, optimized for LCP
    in PageSpeed. Use this for the largest image above the fold.

    Usage:
        <x-seo-hero-image src="/img/hero.jpg" alt="Hero" width="1200" height="630" />
--}}
<img
    src="{{ $src }}"
    alt="{{ $alt }}"
    @if($width !== null) width="{{ $width }}" @endif
    @if($height !== null) height="{{ $height }}" @endif
    loading="eager"
    decoding="async"
    fetchpriority="high"
    @if($srcset) srcset="{{ $srcset }}" @endif
    @if($sizes) sizes="{{ $sizes }}" @endif
    @if($class) class="{{ $class }}" @endif
    {{ $attributes->except(['src', 'alt', 'width', 'height', 'sizes', 'srcset', 'class']) }}
>
