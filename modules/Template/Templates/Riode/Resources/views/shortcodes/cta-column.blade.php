@php
    $image       = $attrs['image'] ?? null;
    $title       = $attrs['title'] ?? null;
    $subtitle    = $attrs['subtitle'] ?? null;
    $buttonText  = $attrs['button-text'] ?? null;
    $buttonHref  = $attrs['button-href'] ?? '#';
    $extraClass  = $attrs['class'] ?? null;
@endphp

<div class="col banner banner-radius {{ $extraClass }}">
    @if ($image)
        <img src="{{ $image }}" alt="{{ $title }}" class="banner-img img-fluid w-100" loading="lazy">
    @endif
    <div class="banner-content">
        @if ($title)
            <h4 class="banner-title">{{ $title }}</h4>
        @endif
        @if ($subtitle)
            <p class="banner-subtitle mb-0">{{ $subtitle }}</p>
        @endif
        @if ($buttonText)
            <a href="{{ $buttonHref }}" class="btn btn-primary btn-sm mt-2">{{ $buttonText }}</a>
        @endif
    </div>
</div>
