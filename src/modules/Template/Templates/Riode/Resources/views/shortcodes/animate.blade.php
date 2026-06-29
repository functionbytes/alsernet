@php
    $allowed = [
        'fadeIn', 'fadeInDownShorter', 'fadeInLeftShorter', 'fadeInRightShorter', 'fadeInUpShorter',
        'zoomInShorter', 'zoomInDown', 'zoomInLeft', 'zoomInRight', 'zoomInUp',
        'bounceIn', 'bounceInDown', 'bounceInLeft', 'bounceInRight', 'bounceInUp', 'bounce',
        'maskUp', 'maskDown', 'maskLeft', 'maskRight',
        'flipInX', 'flipInY', 'flipOutY',
        'rotateIn', 'rotateInDownLeft', 'rotateInDownRight', 'rotateInUpLeft', 'rotateInUpRight',
        'slideInUp', 'slideInDown', 'slideInLeft', 'slideInRight', 'slideZoomIn',
        'blurIn', 'brightIn', 'grayOut', 'dotPulse',
        'shake', 'headShake', 'swing', 'tada', 'wobble', 'jello', 'rubberBand',
        'lightSpeedIn', 'rollIn',
    ];

    $name      = in_array($attrs['name'] ?? '', $allowed, true) ? $attrs['name'] : 'fadeIn';
    $duration  = $attrs['duration'] ?? '1s';
    $delay     = $attrs['delay'] ?? '0s';
    $slideMode = filter_var($attrs['slide-mode'] ?? false, FILTER_VALIDATE_BOOLEAN);
    $wrapper   = $slideMode ? 'slide-animate' : 'appear-animate';
    $options   = json_encode([
        'name'     => $name,
        'duration' => $duration,
        'delay'    => $delay,
    ], JSON_UNESCAPED_UNICODE);
@endphp

<div class="{{ $wrapper }}" data-animation-options='{!! $options !!}'>
    {!! $content !!}
</div>

@once
@push('scripts')
<script>
(function () {
    'use strict';

    var prefersReduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function applyAnimation(el) {
        var opts = {};
        try { opts = JSON.parse(el.getAttribute('data-animation-options') || '{}'); } catch (e) {}

        var name     = opts.name     || 'fadeIn';
        var duration = opts.duration || '1s';
        var delay    = opts.delay    || '0s';

        if (prefersReduced) {
            // Show without animation — do not leave opacity:0
            el.style.opacity = '';
            return;
        }

        el.style.animationDuration = duration;
        el.style.animationDelay    = delay;
        el.classList.add('animated', name);
    }

    if (typeof IntersectionObserver !== 'undefined') {
        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    applyAnimation(entry.target);
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.15 });

        document.querySelectorAll('.appear-animate').forEach(function (el) {
            observer.observe(el);
        });
    } else {
        // Fallback: apply immediately
        document.querySelectorAll('.appear-animate').forEach(applyAnimation);
    }
}());
</script>
@endpush
@endonce
