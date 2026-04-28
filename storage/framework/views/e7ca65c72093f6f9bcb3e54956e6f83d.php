<?php
$invertX = filter_var($attrs['invert-x'] ?? true, FILTER_VALIDATE_BOOLEAN);
$invertY = filter_var($attrs['invert-y'] ?? true, FILTER_VALIDATE_BOOLEAN);
$depth = is_numeric($attrs['depth'] ?? null) ? max(0.1, min(5, (float) $attrs['depth'])) : 1;
$options = json_encode(['invertX' => $invertX, 'invertY' => $invertY]);
?>

<div class="floating"
     data-options='<?php echo $options; ?>'
     data-floating-depth="<?php echo e($depth); ?>">
    <figure class="layer">
        <?php echo $content; ?>

    </figure>
</div>

<?php if (! $__env->hasRenderedOnce('1d74b885-e732-4e4f-9cc6-18c8367c4929')) {
    $__env->markAsRenderedOnce('1d74b885-e732-4e4f-9cc6-18c8367c4929'); ?>
<?php $__env->startPush('scripts'); ?>
<script>
(function () {
    'use strict';

    // Disabled for touch devices (no cursor) and prefers-reduced-motion
    var noPointer = window.matchMedia && window.matchMedia('(hover: none)').matches;
    var prefersReduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (noPointer || prefersReduced) {
        return;
    }

    var rAF = window.requestAnimationFrame || function (cb) { setTimeout(cb, 16); };

    document.querySelectorAll('.floating').forEach(function (wrapper) {
        var layer = wrapper.querySelector('.layer');
        if (!layer) { return; }

        var opts  = {};
        try { opts = JSON.parse(wrapper.getAttribute('data-options') || '{}'); } catch (e) {}

        var depth   = parseFloat(wrapper.getAttribute('data-floating-depth') || '1');
        var invertX = opts.invertX !== false;
        var invertY = opts.invertY !== false;

        var cx = 0, cy = 0;
        var tx = 0, ty = 0;

        function onMouseMove(e) {
            var rect = wrapper.getBoundingClientRect();
            cx = ((e.clientX - rect.left) / rect.width  - 0.5) * depth * (invertX ? -1 : 1);
            cy = ((e.clientY - rect.top)  / rect.height - 0.5) * depth * (invertY ? -1 : 1);
        }

        function animate() {
            tx += (cx - tx) * 0.08;
            ty += (cy - ty) * 0.08;
            layer.style.transform = 'translate3d(' + (tx * 30) + 'px, ' + (ty * 30) + 'px, 0)';
            rAF(animate);
        }

        document.addEventListener('mousemove', onMouseMove);
        rAF(animate);
    });
}());
</script>
<?php $__env->stopPush(); ?>
<?php } ?>
<?php /**PATH /Users/developerts/Herd/system/modules/Template/Templates/Riode/Resources/views/shortcodes/floating.blade.php ENDPATH**/ ?>