<?php

use Livewire\Mechanisms\ExtendBlade\ExtendBlade;

    $to = $attrs['to'] ?? null;
$title = $attrs['title'] ?? '';
$from = $attrs['from'] ?? 0;
$duration = (int) ($attrs['duration'] ?? 2000);
$decimals = min(3, max(0, (int) ($attrs['decimals'] ?? 0)));
$delimiter = $attrs['delimiter'] ?? ',';
$prefix = $attrs['prefix'] ?? '';
$suffix = $attrs['suffix'] ?? '';
$description = $attrs['description'] ?? null;
$iconImage = $attrs['icon-image'] ?? null;
$icon = $attrs['icon'] ?? null;
$extraClass = $attrs['class'] ?? null;

if ($to === null || $to === '') {
    return;
}

$symbolClass = match (true) {
    $prefix === '$' => 'symbol-dollar',
    $suffix === '%' => 'symbol-percent',
    default => null,
};

$countClasses = collect(['count-to', $symbolClass])->filter()->implode(' ');

// Aria label for accessibility
$ariaLabel = trim($prefix.$to.$suffix.' '.$title);
?>

<div class="counter-part <?php echo e($extraClass); ?>">
    <div class="counter text-center" aria-label="<?php echo e($ariaLabel); ?>">

        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($iconImage) { ?>
            <figure class="counter-icon mb-2">
                <img src="<?php echo e($iconImage); ?>" alt="" loading="lazy">
            </figure>
        <?php } elseif ($icon) { ?>
            <i class="<?php echo e($icon); ?> counter-icon mb-2" aria-hidden="true"></i>
        <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>

        <span class="<?php echo e($countClasses); ?>"
              data-fromvalue="<?php echo e($from); ?>"
              data-tovalue="<?php echo e($to); ?>"
              data-duration="<?php echo e($duration); ?>"
              data-delimiter="<?php echo e($delimiter); ?>"
              data-round="<?php echo e($decimals); ?>"
              aria-hidden="true"><?php echo e($prefix); ?><?php echo e($from); ?><?php echo e($suffix); ?></span>

        <div class="counter-content">
            <h3 class="count-title"><?php echo e($title); ?></h3>
            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($description) { ?>
                <p class="counter-descri"><?php echo e($description); ?></p>
            <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
        </div>

    </div>
</div>

<?php if (! $__env->hasRenderedOnce('106618c8-8b78-4d94-8ca4-e9c471c86998')) {
    $__env->markAsRenderedOnce('106618c8-8b78-4d94-8ca4-e9c471c86998'); ?>
<?php $__env->startPush('scripts'); ?>
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
<?php $__env->stopPush(); ?>
<?php } ?>
<?php /**PATH /Users/developerts/Herd/system/modules/Template/Templates/Riode/Resources/views/shortcodes/counter.blade.php ENDPATH**/ ?>