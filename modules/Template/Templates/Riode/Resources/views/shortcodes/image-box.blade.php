@php
    $validLayouts = ['default', 'classic', 'overlay'];

    $image         = htmlspecialchars($attrs['image'] ?? '', ENT_QUOTES);
    $alt           = htmlspecialchars($attrs['alt'] ?? ($attrs['name'] ?? ''), ENT_QUOTES);
    $name          = $attrs['name'] ?? null;
    $job           = $attrs['job'] ?? null;
    $email         = $attrs['email'] ?? null;
    $phone         = $attrs['phone'] ?? null;
    $layout        = in_array($attrs['layout'] ?? 'default', $validLayouts) ? ($attrs['layout'] ?? 'default') : 'default';
    $radius        = filter_var($attrs['radius'] ?? true, FILTER_VALIDATE_BOOLEAN);
    $animation     = $attrs['animation'] ?? null;
    $animationDelay = $attrs['animation-delay'] ?? '0s';
    $width         = (int) ($attrs['width'] ?? 280);
    $height        = (int) ($attrs['height'] ?? 280);
    $extraClass    = $attrs['class'] ?? null;

    // Social links mapeados a FA6
    $socialMap = [
        'facebook'  => ['key' => 'social-facebook',  'icon' => 'fab fa-facebook-f'],
        'twitter'   => ['key' => 'social-twitter',   'icon' => 'fab fa-twitter'],
        'linkedin'  => ['key' => 'social-linkedin',  'icon' => 'fab fa-linkedin-in'],
        'instagram' => ['key' => 'social-instagram', 'icon' => 'fab fa-instagram'],
        'youtube'   => ['key' => 'social-youtube',   'icon' => 'fab fa-youtube'],
    ];

    $socials = collect($socialMap)->filter(fn ($s) => ! empty($attrs[$s['key']]));

    // layout=overlay sin datos de contacto → degradar a default
    if ($layout === 'overlay' && $socials->isEmpty() && ! $email && ! $phone) {
        $layout = 'default';
    }

    $wrapperClasses = collect([
        'image-box',
        $layout === 'overlay' ? 'image-overlay' : null,
        $animation ? 'appear-animate' : null,
        $extraClass,
    ])->filter()->implode(' ');

    $figureClass = $radius ? 'banner-radius' : null;
@endphp

<div class="{{ $wrapperClasses }}"
     @if ($animation)
         data-animation-options='{"name":"{{ htmlspecialchars($animation, ENT_QUOTES) }}","delay":"{{ htmlspecialchars($animationDelay, ENT_QUOTES) }}"}'
     @endif>

    @if ($layout === 'overlay')

        <figure @if ($figureClass) class="{{ $figureClass }}" @endif>
            <img src="{{ $image }}"
                 alt="{{ $alt }}"
                 width="{{ $width }}"
                 height="{{ $height }}"
                 loading="lazy">
            @if ($name)
                <h4 class="overlay-visible">{{ $name }}</h4>
            @endif
            <div class="overlay overlay-transparent">
                @if ($email)
                    <a href="mailto:{{ htmlspecialchars($email, ENT_QUOTES) }}">{{ $email }}</a>
                @endif
                @if ($phone)
                    <a href="tel:{{ preg_replace('/[^+\d]/', '', $phone) }}">{{ $phone }}</a>
                @endif
                @if ($socials->isNotEmpty())
                    <div class="social-links mt-1">
                        @foreach ($socials as $network => $s)
                            <a href="{{ htmlspecialchars($attrs[$s['key']], ENT_QUOTES) }}"
                               class="social-link social-{{ $network }}"
                               aria-label="{{ ucfirst($network) }}"
                               target="_blank"
                               rel="noopener noreferrer">
                                <i class="{{ $s['icon'] }}" aria-hidden="true"></i>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </figure>

    @elseif ($layout === 'classic')

        <figure @if ($figureClass) class="{{ $figureClass }}" @endif>
            <img src="{{ $image }}"
                 alt="{{ $alt }}"
                 width="{{ $width }}"
                 height="{{ $height }}"
                 loading="lazy">
            <div class="overlay social-links">
                @if ($name)
                    <h4>{{ $name }}</h4>
                @endif
                @if ($job)
                    <h5>{{ $job }}</h5>
                @endif
                @foreach ($socials as $network => $s)
                    <a href="{{ htmlspecialchars($attrs[$s['key']], ENT_QUOTES) }}"
                       class="social-link social-{{ $network }}"
                       aria-label="{{ ucfirst($network) }}"
                       target="_blank"
                       rel="noopener noreferrer">
                        <i class="{{ $s['icon'] }}" aria-hidden="true"></i>
                    </a>
                @endforeach
            </div>
        </figure>

    @else

        {{-- layout=default: info siempre visible debajo de la imagen --}}
        <figure @if ($figureClass) class="{{ $figureClass }}" @endif>
            <img src="{{ $image }}"
                 alt="{{ $alt }}"
                 width="{{ $width }}"
                 height="{{ $height }}"
                 loading="lazy">
        </figure>
        @if ($name)
            <h4 class="image-box-name">{{ $name }}</h4>
        @endif
        @if ($job)
            <h5 class="image-box-job">{{ $job }}</h5>
        @endif
        @if ($socials->isNotEmpty())
            <div class="social-links pt-2 pb-2">
                @foreach ($socials as $network => $s)
                    <a href="{{ htmlspecialchars($attrs[$s['key']], ENT_QUOTES) }}"
                       class="social-link social-{{ $network }}"
                       aria-label="{{ ucfirst($network) }}"
                       target="_blank"
                       rel="noopener noreferrer">
                        <i class="{{ $s['icon'] }}" aria-hidden="true"></i>
                    </a>
                @endforeach
            </div>
        @endif

    @endif

</div>

@if ($animation)
    @once
    @push('scripts')
    <script>
    (function () {
        'use strict';

        if (typeof IntersectionObserver === 'undefined') {
            return;
        }

        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (! entry.isIntersecting) {
                    return;
                }

                var el = entry.target;
                var opts = {};

                try {
                    opts = JSON.parse(el.dataset.animationOptions || '{}');
                } catch (e) {}

                el.style.animationDelay = opts.delay || '0s';
                el.classList.add('animated', opts.name || 'fadeIn');
                observer.unobserve(el);
            });
        }, { threshold: 0.15 });

        document.querySelectorAll('.appear-animate').forEach(function (el) {
            observer.observe(el);
        });
    }());
    </script>
    @endpush
    @endonce
@endif
