@php
    $to          = $attrs['to'] ?? null;
    $title       = $attrs['title'] ?? '';
    $from        = $attrs['from'] ?? 0;
    $duration    = (int) ($attrs['duration'] ?? 2000);
    $decimals    = min(3, max(0, (int) ($attrs['decimals'] ?? 0)));
    $delimiter   = $attrs['delimiter'] ?? ',';
    $prefix      = $attrs['prefix'] ?? '';
    $suffix      = $attrs['suffix'] ?? '';
    $description = $attrs['description'] ?? null;
    $iconImage   = $attrs['icon-image'] ?? null;
    $icon        = $attrs['icon'] ?? null;
    $extraClass  = $attrs['class'] ?? null;

    if ($to === null || $to === '') {
        return;
    }

    $symbolClass = match (true) {
        $prefix === '$'  => 'symbol-dollar',
        $suffix === '%'  => 'symbol-percent',
        default          => null,
    };

    $countClasses = collect(['count-to', $symbolClass])->filter()->implode(' ');

    // Aria label for accessibility
    $ariaLabel = trim($prefix . $to . $suffix . ' ' . $title);
@endphp

<div class="counter-part {{ $extraClass }}">
    <div class="counter text-center" aria-label="{{ $ariaLabel }}">

        @if ($iconImage)
            <figure class="counter-icon mb-2">
                <img src="{{ $iconImage }}" alt="" loading="lazy">
            </figure>
        @elseif ($icon)
            <i class="{{ $icon }} counter-icon mb-2" aria-hidden="true"></i>
        @endif

        <span class="{{ $countClasses }}"
              data-fromvalue="{{ $from }}"
              data-tovalue="{{ $to }}"
              data-duration="{{ $duration }}"
              data-delimiter="{{ $delimiter }}"
              data-round="{{ $decimals }}"
              aria-hidden="true">{{ $prefix }}{{ $from }}{{ $suffix }}</span>

        <div class="counter-content">
            <h3 class="count-title">{{ $title }}</h3>
            @if ($description)
                <p class="counter-descri">{{ $description }}</p>
            @endif
        </div>

    </div>
</div>

@once
@push('scripts')
<script>
(function () {
    'use strict';

    var prefersReduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function formatValue(value, round, delimiter) {
        var fixed = value.toFixed(round);
        if (delimiter && value >= 1000) {
            var parts = fixed.split('.');
            parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, delimiter);
            return parts.join('.');
        }
        return fixed;
    }

    function animateCounter($el) {
        var from      = parseFloat($el.data('fromvalue')) || 0;
        var to        = parseFloat($el.data('tovalue'));
        var duration  = parseInt($el.data('duration'), 10) || 2000;
        var round     = parseInt($el.data('round'), 10) || 0;
        var delimiter = $el.data('delimiter') || ',';
        var text      = $el.text();
        var prefix    = text.match(/^[^0-9]*/)?.[0] ?? '';
        var suffix    = text.match(/[^0-9]*$/)?.[0] ?? '';

        if (prefersReduced) {
            $el.text(prefix + formatValue(to, round, delimiter) + suffix);
            return;
        }

        $({ value: from }).animate({ value: to }, {
            duration: duration,
            easing: 'swing',
            step: function () {
                $el.text(prefix + formatValue(this.value, round, delimiter) + suffix);
            },
            complete: function () {
                $el.text(prefix + formatValue(to, round, delimiter) + suffix);
            },
        });
    }

    if (typeof IntersectionObserver !== 'undefined') {
        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    animateCounter($(entry.target));
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.3 });

        $('.count-to').each(function () {
            observer.observe(this);
        });
    } else {
        // Fallback: animate immediately
        $('.count-to').each(function () { animateCounter($(this)); });
    }
}());
</script>
@endpush
@endonce
