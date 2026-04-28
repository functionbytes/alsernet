<?php

use Livewire\Mechanisms\ExtendBlade\ExtendBlade;

    $until = $attrs['until'] ?? null;
$layout = in_array($attrs['layout'] ?? '', ['inline-banner', 'block', '2-cols', '2-cols-split'])
    ? $attrs['layout']
    : 'block';
$format = $attrs['format'] ?? 'DHMS';
$border = filter_var($attrs['border'] ?? false, FILTER_VALIDATE_BOOLEAN);
$background = in_array($attrs['background'] ?? '', ['light', 'dark', 'primary']) ? $attrs['background'] : 'none';
$textColor = in_array($attrs['text-color'] ?? '', ['light', 'dark']) ? $attrs['text-color'] : 'dark';
$dealImage = $attrs['deal-image'] ?? null;
$title = $attrs['title'] ?? null;
$subtitle = $attrs['subtitle'] ?? null;
$buttonText = $attrs['button-text'] ?? null;
$buttonHref = $attrs['button-href'] ?? '#';
$labelsRaw = $attrs['labels'] ?? null;
$extraClass = $attrs['class'] ?? null;

// Parse until date — skip render if expired or missing
if (! $until) {
    return;
}

$untilTs = is_numeric($until) ? (int) $until : strtotime($until);

if ($untilTs === false || $untilTs <= time()) {
    return;
}

$secondsRemaining = $untilTs - time();

$defaultLabels = [
    'days' => __('shortcode::shortcode.countdown_days'),
    'hours' => __('shortcode::shortcode.countdown_hours'),
    'minutes' => __('shortcode::shortcode.countdown_minutes'),
    'seconds' => __('shortcode::shortcode.countdown_seconds'),
];

$labelsJson = $labelsRaw ?: json_encode($defaultLabels, JSON_UNESCAPED_UNICODE);

$wrapperClasses = collect([
    $layout === 'inline-banner' ? 'countdown-type1 d-flex align-items-center justify-content-between flex-wrap gap-3' : null,
    $layout === 'block' ? 'countdown-type2 text-center' : null,
    $layout === '2-cols' ? 'ct-2-grid countdown-1' : null,
    $layout === '2-cols-split' ? 'ct-2-grid border-split countdown-2' : null,
    $background !== 'none' ? 'bg-'.$background : null,
    'text-'.$textColor,
    $extraClass,
])->filter()->implode(' ');

$countdownClasses = collect([
    'countdown',
    'countdown-default',
    $border ? 'cd-section-border' : null,
])->filter()->implode(' ');

$countdownId = 'countdown-'.uniqid();
?>

<div class="<?php echo e($wrapperClasses); ?>">

    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($layout === 'inline-banner') { ?>
        <div class="countdown-content-left d-flex align-items-center gap-3">
            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($dealImage) { ?>
                <figure class="deal-image mb-0">
                    <img src="<?php echo e($dealImage); ?>" alt="" width="37" height="46" loading="lazy">
                </figure>
            <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
            <div>
                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($title) { ?>
                    <h2 class="banner-title mb-0 fw-bold ls-m"><?php echo e($title); ?></h2>
                <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($subtitle) { ?>
                    <h3 class="banner-subtitle mb-0 fw-normal text-muted"><?php echo e($subtitle); ?></h3>
                <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
            </div>
        </div>

        <div class="countdown-content-center">
            <div id="<?php echo e($countdownId); ?>"
                 class="<?php echo e($countdownClasses); ?>"
                 data-seconds="<?php echo e($secondsRemaining); ?>"
                 data-format="<?php echo e($format); ?>"
                 data-labels="<?php echo e($labelsJson); ?>"></div>
        </div>

        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($buttonText) { ?>
            <div class="countdown-content-right">
                <a href="<?php echo e($buttonHref); ?>" class="btn btn-link btn-icon-right text-uppercase">
                    <?php echo e($buttonText); ?><i class="fas fa-arrow-right ms-1" aria-hidden="true"></i>
                </a>
            </div>
        <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>

    <?php } else { ?>
        <div id="<?php echo e($countdownId); ?>"
             class="<?php echo e($countdownClasses); ?>"
             data-seconds="<?php echo e($secondsRemaining); ?>"
             data-format="<?php echo e($format); ?>"
             data-labels="<?php echo e($labelsJson); ?>"></div>
    <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>

    <noscript>
        <p class="text-muted small">
            <?php echo e(__('shortcode::shortcode.countdown_expires')); ?>:
            <?php echo e(date('d/m/Y H:i', $untilTs)); ?>

        </p>
    </noscript>
</div>

<?php if (! $__env->hasRenderedOnce('a6c902ae-d5e7-409e-823a-419aa9b52b29')) {
    $__env->markAsRenderedOnce('a6c902ae-d5e7-409e-823a-419aa9b52b29'); ?>
<?php $__env->startPush('scripts'); ?>
<script>
(function () {
    'use strict';

    function formatNumber(n) {
        return n < 10 ? '0' + n : '' + n;
    }

    function buildCountdownHtml(remaining, format, labels) {
        var parts = [];
        var d = Math.floor(remaining / 86400);
        var h = Math.floor((remaining % 86400) / 3600);
        var m = Math.floor((remaining % 3600) / 60);
        var s = remaining % 60;

        var units = [];
        if (format.indexOf('D') !== -1) { units.push({ val: d, label: labels.days || 'Días' }); }
        if (format.indexOf('H') !== -1) { units.push({ val: h, label: labels.hours || 'Horas' }); }
        if (format.indexOf('M') !== -1) { units.push({ val: m, label: labels.minutes || 'Min' }); }
        if (format.indexOf('S') !== -1) { units.push({ val: s, label: labels.seconds || 'Seg' }); }

        units.forEach(function (u) {
            parts.push(
                '<span class="countdown-unit">' +
                    '<span class="countdown-value">' + formatNumber(u.val) + '</span>' +
                    '<span class="countdown-label">' + u.label + '</span>' +
                '</span>'
            );
        });

        return parts.join('<span class="countdown-sep">:</span>');
    }

    function initCountdown(el) {
        var $el = $(el);
        var seconds = parseInt($el.data('seconds'), 10);
        var format  = $el.data('format') || 'DHMS';
        var labels  = {};

        try { labels = JSON.parse($el.attr('data-labels') || '{}'); } catch (e) {}

        if (isNaN(seconds) || seconds <= 0) {
            $el.html('<span class="countdown-expired"><?php echo e(__('shortcode::shortcode.countdown_expired')); ?></span>');
            return;
        }

        var remaining = seconds;

        function tick() {
            if (remaining < 0) {
                clearInterval(timer);
                $el.html('<span class="countdown-expired"><?php echo e(__('shortcode::shortcode.countdown_expired')); ?></span>');
                return;
            }
            $el.html(buildCountdownHtml(remaining, format, labels));
            remaining--;
        }

        tick();
        var timer = setInterval(tick, 1000);
    }

    // Respect prefers-reduced-motion: show final state without animation
    var prefersReduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    $('.countdown').each(function () {
        if (prefersReduced) {
            var $el = $(this);
            var seconds = parseInt($el.data('seconds'), 10);
            var format  = $el.data('format') || 'DHMS';
            var labels  = {};
            try { labels = JSON.parse($el.attr('data-labels') || '{}'); } catch (e) {}
            $el.html(buildCountdownHtml(Math.max(seconds, 0), format, labels));
        } else {
            initCountdown(this);
        }
    });
}());
</script>
<?php $__env->stopPush(); ?>
<?php } ?>
<?php /**PATH /Users/developerts/Herd/system/modules/Template/Templates/Riode/Resources/views/shortcodes/countdown.blade.php ENDPATH**/ ?>