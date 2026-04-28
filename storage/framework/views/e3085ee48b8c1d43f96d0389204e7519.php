<?php

use Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys;
use Livewire\Mechanisms\ExtendBlade\ExtendBlade;

    $validLayouts = ['grid', 'carousel'];
$validGutters = ['none', 'sm', 'md', 'lg'];
$validAligns = ['left', 'center', 'right'];

$layout = in_array($attrs['layout'] ?? 'grid', $validLayouts) ? ($attrs['layout'] ?? 'grid') : 'grid';
$cols = min(5, max(1, (int) ($attrs['cols'] ?? 4)));
$colsTablet = min(4, max(1, (int) ($attrs['cols-tablet'] ?? 2)));
$colsMobile = min(3, max(1, (int) ($attrs['cols-mobile'] ?? 1)));
$gutter = in_array($attrs['gutter'] ?? 'md', $validGutters) ? ($attrs['gutter'] ?? 'md') : 'md';
$autoplay = filter_var($attrs['autoplay'] ?? false, FILTER_VALIDATE_BOOLEAN);
$autoplaySpeed = (int) ($attrs['autoplay-speed'] ?? 5000);
$showArrows = filter_var($attrs['show-arrows'] ?? true, FILTER_VALIDATE_BOOLEAN);
$showDots = filter_var($attrs['show-dots'] ?? false, FILTER_VALIDATE_BOOLEAN);
$align = in_array($attrs['align'] ?? 'center', $validAligns) ? ($attrs['align'] ?? 'center') : 'center';
$extraClass = $attrs['class'] ?? null;

// Bootstrap divisors for grid; for cols=5 use col-lg with flex 20%
$validBsCols = [1, 2, 3, 4, 6, 12];
$colDesktop = in_array((int) (12 / $cols), $validBsCols) ? (int) (12 / $cols) : null;
$colTablet = in_array((int) (12 / $colsTablet), $validBsCols) ? (int) (12 / $colsTablet) : 6;
$colMobile = in_array((int) (12 / $colsMobile), $validBsCols) ? (int) (12 / $colsMobile) : 12;

$gutterClass = match ($gutter) {
    'none' => 'g-0',
    'sm' => 'g-2',
    'lg' => 'g-4',
    default => 'g-3',
};

$wrapperClasses = collect([
    'category-grid',
    'text-'.$align,
    $extraClass,
])->filter()->implode(' ');

// Parse child category-card items from rendered content
$children = array_filter(
    array_map('trim', preg_split('/(?=<div class="category)/', $content) ?: []),
    fn ($c) => $c !== ''
);

$childCount = count($children);
?>

<?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (! empty($children)) { ?>
    <div class="<?php echo e($wrapperClasses); ?>">
        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($layout === 'carousel') { ?>
            <div class="owl-carousel owl-theme"
                 data-owl-options="<?php echo e(json_encode([
                     'items' => $cols,
                     'loop' => $childCount > $cols,
                     'autoplay' => $autoplay,
                     'autoplayTimeout' => $autoplaySpeed,
                     'nav' => $showArrows,
                     'dots' => $showDots,
                     'responsive' => [
                         '0' => ['items' => $colsMobile],
                         '768' => ['items' => $colsTablet],
                         '992' => ['items' => $cols],
                     ],
                 ])); ?>">
                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__currentLoopData = $children;
            $__env->addLoop($__currentLoopData);
            foreach ($__currentLoopData as $card) {
                $__env->incrementLoopIndices();
                $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
                    <?php echo $card; ?>

                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } $__env->popLoop();
            $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>
            </div>

            <?php if (! $__env->hasRenderedOnce('56ee5dce-305f-40ca-af92-6fa1a1d51a48')) {
                $__env->markAsRenderedOnce('56ee5dce-305f-40ca-af92-6fa1a1d51a48'); ?>
            <?php $__env->startPush('scripts'); ?>
            <script>
            $(document).ready(function () {
                $('.category-grid .owl-carousel').each(function () {
                    var opts = {};
                    try { opts = JSON.parse($(this).attr('data-owl-options') || '{}'); } catch (e) {}
                    $(this).owlCarousel(opts);
                });
            });
            </script>
            <?php $__env->stopPush(); ?>
            <?php } ?>
        <?php } else { ?>
            <div class="row <?php echo e($gutterClass); ?>">
                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__currentLoopData = $children;
            $__env->addLoop($__currentLoopData);
            foreach ($__currentLoopData as $card) {
                $__env->incrementLoopIndices();
                $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
                    <div class="<?php echo e($colDesktop ? 'col-'.$colMobile.' col-md-'.$colTablet.' col-lg-'.$colDesktop : 'col-'.$colMobile.' col-md-'.$colTablet.' col-lg'); ?>">
                        <?php echo $card; ?>

                    </div>
                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } $__env->popLoop();
            $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>
            </div>
        <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
    </div>
<?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
<?php /**PATH /Users/developerts/Herd/system/modules/Template/Templates/Riode/Resources/views/shortcodes/category-grid.blade.php ENDPATH**/ ?>