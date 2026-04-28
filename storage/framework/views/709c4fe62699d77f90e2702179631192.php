<?php
$speed = in_array($attrs['speed'] ?? '', ['slow', 'normal', 'fast', 'entire'], true) ? $attrs['speed'] : 'normal';
$direction = in_array($attrs['direction'] ?? '', ['up', 'down', 'left', 'right'], true) ? $attrs['direction'] : 'up';

$magnitudes = ['slow' => 50, 'normal' => 100, 'fast' => 150, 'entire' => 100];
$axis = in_array($direction, ['up', 'down'], true) ? 'Y' : 'X';
$sign = in_array($direction, ['up', 'left'], true) ? 1 : -1;
$magnitude = $magnitudes[$speed];

$fromValue = $sign * $magnitude;
$toValue = -1 * $fromValue;

$fromDefault = $axis === 'Y'
    ? "translateY({$fromValue}%)"
    : "translateX({$fromValue}%)";
$toDefault = $axis === 'Y'
    ? "translateY({$toValue}%)"
    : "translateX({$toValue}%)";

// Allow full CSS override via attributes
$fromTransform = $attrs['from'] ?? $fromDefault;
$toTransform = $attrs['to'] ?? $toDefault;

$extraClass = match ($speed) {
    'slow' => ' scroll-slow',
    'entire' => ' scroll-entire',
    default => '',
};

$options = json_encode([
    'from' => $fromTransform,
    'to' => $toTransform,
]);
?>

<div class="scroll-reveal-el<?php echo e($extraClass); ?>"
     data-scroll-options='<?php echo $options; ?>'>
    <?php echo $content; ?>

</div>

<?php if (! $__env->hasRenderedOnce('18c0f51c-88a9-46d1-9d42-d476679a5b01')) {
    $__env->markAsRenderedOnce('18c0f51c-88a9-46d1-9d42-d476679a5b01'); ?>
<?php $__env->startPush('scripts'); ?>
<script>
(function () {
    'use strict';

    var prefersReduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (prefersReduced) {
        return;
    }

    var rAF = window.requestAnimationFrame || function (cb) { setTimeout(cb, 16); };

    function lerp(a, b, t) { return a + (b - a) * t; }

    var elements = [];

    document.querySelectorAll('.scroll-reveal-el').forEach(function (el) {
        var opts = {};
        try { opts = JSON.parse(el.getAttribute('data-scroll-options') || '{}'); } catch (e) {}
        elements.push({ el: el, from: opts.from || 'translateY(100%)', to: opts.to || 'translateY(-100%)', progress: 0 });
    });

    if (elements.length === 0) { return; }

    var ticking = false;

    function updateProgress() {
        var wh = window.innerHeight;
        elements.forEach(function (item) {
            var rect = item.el.getBoundingClientRect();
            // progress: 0 when bottom enters viewport top, 1 when top reaches viewport bottom
            var progress = 1 - (rect.bottom / (wh + rect.height));
            item.progress = Math.max(0, Math.min(1, progress));
        });
        applyTransforms();
        ticking = false;
    }

    function applyTransforms() {
        elements.forEach(function (item) {
            // Interpolate between from/to CSS transform strings by progress
            // Simple approach: apply from at 0%, to at 100% via CSS variable
            item.el.style.setProperty('--scroll-progress', item.progress.toFixed(4));

            // Extract numeric values from simple translateX/Y patterns for direct lerp
            var fromMatch = item.from.match(/translate[XY]?\(([^,)]+)/);
            var toMatch   = item.to.match(/translate[XY]?\(([^,)]+)/);

            if (fromMatch && toMatch) {
                var fromNum = parseFloat(fromMatch[1]);
                var toNum   = parseFloat(toMatch[1]);
                var unit    = item.from.includes('px') ? 'px' : '%';
                var isX     = item.from.includes('translateX');
                var val     = lerp(fromNum, toNum, item.progress);
                item.el.style.transform = (isX ? 'translateX(' : 'translateY(') + val + unit + ')';
            }
        });
    }

    function onScroll() {
        if (!ticking) {
            rAF(updateProgress);
            ticking = true;
        }
    }

    window.addEventListener('scroll', onScroll, { passive: true });
    updateProgress();
}());
</script>
<?php $__env->stopPush(); ?>
<?php } ?>
<?php /**PATH /Users/developerts/Herd/system/modules/Template/Templates/Riode/Resources/views/shortcodes/scroll-reveal.blade.php ENDPATH**/ ?>