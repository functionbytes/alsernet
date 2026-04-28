<?php

use Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys;
use Livewire\Mechanisms\ExtendBlade\ExtendBlade;

    $layout = in_array($attrs['layout'] ?? '', ['boxed', 'block', 'simple', 'background', 'inline'])
        ? $attrs['layout']
        : 'simple';
$cols = min(4, max(1, (int) ($attrs['cols'] ?? 4)));
$colsTablet = min(4, max(1, (int) ($attrs['cols-tablet'] ?? 2)));
$colsMobile = min(2, max(1, (int) ($attrs['cols-mobile'] ?? 1)));
$gutter = in_array($attrs['gutter'] ?? '', ['none', 'sm', 'md', 'lg']) ? $attrs['gutter'] : 'md';
$background = in_array($attrs['background'] ?? '', ['light', 'dark', 'image']) ? $attrs['background'] : 'none';
$backgroundImage = $attrs['background-image'] ?? null;
$align = in_array($attrs['align'] ?? '', ['left', 'center', 'right']) ? $attrs['align'] : 'center';
$extraClass = $attrs['class'] ?? null;

// Only divisors of 12 are supported; clamp to nearest valid value
$validCols = [1, 2, 3, 4, 6, 12];
$cols = in_array($cols, $validCols) ? $cols : 4;
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
    'counter-grid',
    'counter-grid-'.$layout,
    $background !== 'none' ? 'counter-bg-'.$background : null,
    'text-'.$align,
    $extraClass,
])->filter()->implode(' ');

$itemLayoutClass = match ($layout) {
    'boxed' => 'counter-box',
    'block' => 'counter-block',
    'background' => 'counter-background',
    'inline' => 'counter-inline',
    default => 'counter-simple',
};

// Parse child [counter] items from rendered content
$children = array_filter(
    array_map('trim', preg_split('/(?=<div class="counter-part)/', $content) ?: []),
    fn ($c) => $c !== ''
);
?>

<?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (empty($children)) { ?>
    
<?php } else { ?>
    <div class="<?php echo e($wrapperClasses); ?>"
         <?php if ($backgroundImage) { ?> data-bg-image="<?php echo e($backgroundImage); ?>" <?php } ?>>

        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($layout === 'inline') { ?>
            <div class="d-flex flex-wrap align-items-center justify-content-center gap-3">
                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__currentLoopData = $children;
            $__env->addLoop($__currentLoopData);
            foreach ($__currentLoopData as $counter) {
                $__env->incrementLoopIndices();
                $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
                    <div class="<?php echo e($itemLayoutClass); ?>">
                        <?php echo $counter; ?>

                    </div>
                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } $__env->popLoop();
            $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>
            </div>
        <?php } else { ?>
            <div class="row <?php echo e($gutterClass); ?>">
                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__currentLoopData = $children;
            $__env->addLoop($__currentLoopData);
            foreach ($__currentLoopData as $counter) {
                $__env->incrementLoopIndices();
                $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
                    <div class="col-<?php echo e($colMobile); ?> col-md-<?php echo e($colTablet); ?> col-lg-<?php echo e($colDesktop); ?>">
                        <div class="<?php echo e($itemLayoutClass); ?>">
                            <?php echo $counter; ?>

                        </div>
                    </div>
                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } $__env->popLoop();
            $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>
            </div>
        <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
    </div>

    <?php if (! $__env->hasRenderedOnce('f812ccf1-e2eb-4a90-b147-4a265a533df4')) {
        $__env->markAsRenderedOnce('f812ccf1-e2eb-4a90-b147-4a265a533df4'); ?>
    <?php $__env->startPush('scripts'); ?>
    <script>
    (function () {
        // Apply background-image via JS (data attribute → CSS) to avoid inline styles
        $('.counter-grid[data-bg-image]').each(function () {
            var src = $(this).data('bg-image');
            if (src) {
                $(this).css('background-image', 'url(' + src + ')');
                $(this).addClass('counter-bg-cover');
            }
        });
    }());
    </script>
    <?php $__env->stopPush(); ?>
    <?php } ?>
<?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
<?php /**PATH /Users/developerts/Herd/system/modules/Template/Templates/Riode/Resources/views/shortcodes/counter-grid.blade.php ENDPATH**/ ?>