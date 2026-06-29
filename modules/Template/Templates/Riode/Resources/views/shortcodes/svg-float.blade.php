@php
    $svgPath    = $attrs['svg-path'] ?? '';
    $delta      = is_numeric($attrs['delta'] ?? null) ? max(0, min(3, (float) $attrs['delta'])) : 1;
    $speed      = is_numeric($attrs['speed'] ?? null) ? max(0.5, min(5, (float) $attrs['speed'])) : 2;
    $size       = is_numeric($attrs['size'] ?? null) ? max(1, min(50, (int) $attrs['size'])) : 14;
    $viewbox    = $attrs['viewbox'] ?? '0 -1 70 72';
    $width      = is_numeric($attrs['width'] ?? null) ? (int) $attrs['width'] : 980;
    $height     = is_numeric($attrs['height'] ?? null) ? (int) $attrs['height'] : 980;
    $fill       = preg_match('/^(#[0-9A-Fa-f]{3,6}|currentColor)$/', $attrs['fill'] ?? '') ? $attrs['fill'] : 'currentColor';
    $image      = $attrs['image'] ?? null;
    $imageAlt   = $attrs['image-alt'] ?? '';
    $background = $attrs['background'] ?? null;
    $bgAlt      = $attrs['background-alt'] ?? '';
    $notice     = $attrs['notice-image'] ?? null;

    $options = json_encode([
        'delta' => $delta,
        'speed' => $speed,
        'size'  => $size,
    ]);
@endphp

@if ($svgPath)
<div class="svg-template">
    @if ($background)
        <figure class="svg-background">
            <img src="{{ $background }}" alt="{{ $bgAlt }}" loading="lazy">
        </figure>
    @endif

    <svg xmlns="http://www.w3.org/2000/svg"
         x="0px" y="0px"
         width="{{ $width }}px" height="{{ $height }}px"
         viewBox="{{ $viewbox }}"
         class="float-svg"
         data-float-options='{!! $options !!}'
         xml:space="preserve"
         aria-hidden="true">
        <path fill-rule="evenodd" d="{{ $svgPath }}" fill="{{ $fill }}"></path>
    </svg>

    @if ($image)
        <figure>
            <img src="{{ $image }}" alt="{{ $imageAlt }}" loading="lazy">
        </figure>
    @endif

    @if ($notice)
        <figure class="svg-notice">
            <img src="{{ $notice }}" alt="" loading="lazy">
        </figure>
    @endif
</div>
@endif

@once
@push('scripts')
<script>
(function () {
    'use strict';

    var prefersReduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    document.querySelectorAll('.float-svg').forEach(function (svgEl) {
        var opts = {};
        try { opts = JSON.parse(svgEl.getAttribute('data-float-options') || '{}'); } catch (e) {}

        var delta = parseFloat(opts.delta) || 1;
        var speed = parseFloat(opts.speed) || 2;
        var path  = svgEl.querySelector('path');

        if (!path) { return; }

        if (prefersReduced) { return; }

        var points = [];
        var d      = path.getAttribute('d') || '';
        // Collect numeric pairs for morphing (simplified: perturb M/L/C control points)
        var nums   = d.match(/[-+]?\d*\.?\d+/g) || [];
        nums = nums.map(Number);

        var origNums = nums.slice();
        var t = 0;
        var rAF = window.requestAnimationFrame;

        function animate() {
            t += 0.016 * speed;
            var morphed = origNums.map(function (v, i) {
                return v + Math.sin(t + i * 0.5) * delta;
            });

            // Rebuild path string substituting numeric values
            var idx = 0;
            var newD = d.replace(/[-+]?\d*\.?\d+/g, function () {
                return morphed[idx++] !== undefined ? morphed[idx - 1].toFixed(2) : '0';
            });

            path.setAttribute('d', newD);
            rAF(animate);
        }

        rAF(animate);
    });
}());
</script>
@endpush
@endonce
