<?php

use Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys;
use Livewire\Mechanisms\ExtendBlade\ExtendBlade;

    $layout = in_array($attrs['layout'] ?? '', ['grid', 'carousel']) ? $attrs['layout'] : 'grid';
$cols = min(4, max(1, (int) ($attrs['cols'] ?? 3)));
$colsTablet = min(4, max(1, (int) ($attrs['cols-tablet'] ?? 2)));
$colsMobile = min(2, max(1, (int) ($attrs['cols-mobile'] ?? 1)));
$gutter = in_array($attrs['gutter'] ?? '', ['none', 'sm', 'md', 'lg']) ? $attrs['gutter'] : 'md';
$background = in_array($attrs['background'] ?? '', ['light', 'grey', 'dark']) ? $attrs['background'] : 'none';
$autoplay = filter_var($attrs['autoplay'] ?? false, FILTER_VALIDATE_BOOLEAN);
$autoplaySpeed = (int) ($attrs['autoplay-speed'] ?? 5000);
$showArrows = filter_var($attrs['show-arrows'] ?? true, FILTER_VALIDATE_BOOLEAN);
$showDots = filter_var($attrs['show-dots'] ?? false, FILTER_VALIDATE_BOOLEAN);
$align = in_array($attrs['align'] ?? '', ['left', 'center', 'right']) ? $attrs['align'] : 'center';
$extraClass = $attrs['class'] ?? null;

// Only valid Bootstrap divisors
$validCols = [1, 2, 3, 4, 6, 12];
$cols = in_array($cols, $validCols) ? $cols : 3;
$colsTablet = in_array($colsTablet, $validCols) ? $colsTablet : 2;
$colsMobile = in_array($colsMobile, $validCols) ? $colsMobile : 1;

$colDesktop = (int) (12 / $cols);
$colTablet = (int) (12 / $colsTablet);
$colMobile = (int) (12 / $colsMobile);

$gutterClass = match ($gutter) {
    'none' => 'g-0',
    'sm' => 'g-2',
    'lg' => 'g-4',
    default => 'g-3',
};

$wrapperClasses = collect([
    'icon-box-grid',
    $background !== 'none' ? 'bg-'.$background : null,
    'text-'.$align,
    $extraClass,
])->filter()->implode(' ');

// Parse child [icon-box] items from rendered content
$children = array_filter(
    array_map('trim', preg_split('/(?=<(?:div|a) class="icon-box)/', $content) ?: []),
    fn ($c) => $c !== ''
);

$childCount = count($children);

// Degrade carousel to grid if only 1 item
if ($layout === 'carousel' && $childCount <= 1) {
    $layout = 'grid';
}

$carouselId = 'ibg-'.uniqid();
?>

<?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (empty($children)) { ?>
    
<?php } elseif ($layout === 'carousel') { ?>
    <div class="<?php echo e($wrapperClasses); ?>">
        <div id="<?php echo e($carouselId); ?>"
             class="owl-carousel owl-theme"
             data-owl-options="<?php echo e(json_encode([
                 'items' => $cols,
                 'loop' => true,
                 'autoplay' => $autoplay,
                 'autoplayTimeout' => $autoplaySpeed,
                 'nav' => $showArrows,
                 'dots' => $showDots,
                 'responsive' => [
                     '0' => ['items' => $colsMobile],
                     '768' => ['items' => $colsTablet],
                     '992' => ['items' => $cols],
                 ],
             ])); ?>"
             role="region"
             aria-label="<?php echo e(__('shortcode::shortcode.icon_box_carousel_label')); ?>">
            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__currentLoopData = $children;
    $__env->addLoop($__currentLoopData);
    foreach ($__currentLoopData as $iconBox) {
        $__env->incrementLoopIndices();
        $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
                <?php echo $iconBox; ?>

            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } $__env->popLoop();
    $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>
        </div>
    </div>

    <?php if (! $__env->hasRenderedOnce('18b56117-ba59-4b1d-8834-5f137a98f3a7')) {
        $__env->markAsRenderedOnce('18b56117-ba59-4b1d-8834-5f137a98f3a7'); ?>
    <?php $__env->startPush('scripts'); ?>
    <script>
    $(document).ready(function () {
        $('.icon-box-grid .owl-carousel').each(function () {
            var opts = {};
            try { opts = JSON.parse($(this).attr('data-owl-options') || '{}'); } catch (e) {}
            $(this).owlCarousel(opts);
        });
    });
    </script>
    <?php $__env->stopPush(); ?>
    <?php } ?>
<?php } else { ?>
    <div class="<?php echo e($wrapperClasses); ?>">
        <div class="row <?php echo e($gutterClass); ?>">
            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__currentLoopData = $children;
        $__env->addLoop($__currentLoopData);
        foreach ($__currentLoopData as $iconBox) {
            $__env->incrementLoopIndices();
            $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
                <div class="col-<?php echo e($colMobile); ?> col-md-<?php echo e($colTablet); ?> col-lg-<?php echo e($colDesktop); ?>">
                    <?php echo $iconBox; ?>

                </div>
            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } $__env->popLoop();
        $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>
        </div>
    </div>
<?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
<?php /**PATH /Users/developerts/Herd/system/modules/Template/Templates/Riode/Resources/views/shortcodes/icon-box-grid.blade.php ENDPATH**/ ?>