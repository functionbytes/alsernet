<?php

use Livewire\Mechanisms\ExtendBlade\ExtendBlade;

    $validStyles = ['default', 'light', 'icon', 'ellipse', 'semi-circle', 'classic', 'badge'];
$validShapes = ['default', 'rounded', 'ellipse', 'absolute'];
$validHovers = ['none', 'zoom', 'light', 'dark'];

$name = $attrs['name'] ?? '';
$href = $attrs['href'] ?? '#';
$image = $attrs['image'] ?? null;
$icon = $attrs['icon'] ?? null;
$description = $attrs['description'] ?? null;
$count = isset($attrs['count']) && (int) $attrs['count'] > 0 ? (int) $attrs['count'] : null;
$countLabel = $attrs['count-label'] ?? __('shortcode::shortcode.category_card.products');
$style = in_array($attrs['style'] ?? 'default', $validStyles) ? ($attrs['style'] ?? 'default') : 'default';
$shape = in_array($attrs['shape'] ?? 'rounded', $validShapes) ? ($attrs['shape'] ?? 'rounded') : 'rounded';
$hover = in_array($attrs['hover'] ?? 'zoom', $validHovers) ? ($attrs['hover'] ?? 'zoom') : 'zoom';
$bgColor = $attrs['bg-color'] ?? null;
$buttonText = $attrs['button-text'] ?? null;
$buttonHref = $attrs['button-href'] ?? $href;
$extraClass = $attrs['class'] ?? null;

// Validate hex color; reject if not valid
if ($bgColor && ! preg_match('/^#[0-9A-Fa-f]{3,6}$/', $bgColor)) {
    $bgColor = null;
}

$classes = collect([
    'category',
    $style === 'default' ? 'category-default' : null,
    $style === 'light' ? 'category-light' : null,
    $style === 'icon' ? 'category-icon' : null,
    $style === 'ellipse' ? 'category-ellipse' : null,
    $style === 'semi-circle' ? 'category-ellipse2' : null,
    $style === 'classic' ? 'category-classic' : null,
    $style === 'badge' ? 'category-badge' : null,
    $shape === 'rounded' ? 'category-rounded' : null,
    $shape === 'ellipse' ? 'category-ellipse-shape' : null,
    $shape === 'absolute' ? 'category-absolute' : null,
    $hover === 'zoom' ? 'overlay-zoom' : null,
    $hover === 'light' ? 'overlay-light' : null,
    $hover === 'dark' ? 'overlay-dark' : null,
    $extraClass,
])->filter()->implode(' ');
?>

<div class="<?php echo e($classes); ?>"
     <?php if ($bgColor) { ?> data-bg-color="<?php echo e(e($bgColor)); ?>" <?php } ?>>
    <a href="<?php echo e(htmlspecialchars($href)); ?>">
        <figure class="category-media">
            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (! empty($image)) { ?>
                <img src="<?php echo e(htmlspecialchars($image)); ?>"
                     alt="<?php echo e(e($name)); ?>"
                     width="280" height="280"
                     loading="lazy">
            <?php } elseif (! empty($icon)) { ?>
                <i class="<?php echo e(e($icon)); ?>" aria-hidden="true"></i>
            <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
        </figure>
    </a>

    <div class="category-content">
        <h4 class="category-name">
            <a href="<?php echo e(htmlspecialchars($href)); ?>"><?php echo e($name); ?></a>
        </h4>

        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($description) { ?>
            <p class="category-descri"><?php echo e($description); ?></p>
        <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>

        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($count !== null) { ?>
            <span class="category-count"><span><?php echo e($count); ?></span> <?php echo e($countLabel); ?></span>
        <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>

        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($buttonText) { ?>
            <a href="<?php echo e(htmlspecialchars($buttonHref)); ?>" class="btn btn-primary"><?php echo e($buttonText); ?></a>
        <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
    </div>
</div>

<?php if (! $__env->hasRenderedOnce('08d7cb4a-2a40-4911-98e5-99c9b834695b')) {
    $__env->markAsRenderedOnce('08d7cb4a-2a40-4911-98e5-99c9b834695b'); ?>
<?php $__env->startPush('scripts'); ?>
<script>
$(function () {
    // Apply bg-color via CSS custom property (avoids inline style)
    $('.category[data-bg-color]').each(function () {
        $(this).get(0).style.setProperty('--cat-bg', $(this).data('bg-color'));
    });
});
</script>
<?php $__env->stopPush(); ?>
<?php } ?>
<?php /**PATH /Users/developerts/Herd/system/modules/Template/Templates/Riode/Resources/views/shortcodes/category-card.blade.php ENDPATH**/ ?>