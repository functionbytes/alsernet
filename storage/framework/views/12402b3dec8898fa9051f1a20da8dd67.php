<?php

use Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys;
use Livewire\Mechanisms\ExtendBlade\ExtendBlade;

    $layout = in_array($attrs['layout'] ?? '', ['carousel', 'grid', 'masonry'], true) ? $attrs['layout'] : 'carousel';
$cols = is_numeric($attrs['cols'] ?? null) ? max(2, min(6, (int) $attrs['cols'])) : 5;
$autoplay = filter_var($attrs['autoplay'] ?? true, FILTER_VALIDATE_BOOLEAN);
$showInfo = filter_var($attrs['show-info'] ?? false, FILTER_VALIDATE_BOOLEAN);
$gutter = in_array($attrs['gutter'] ?? '', ['none', 'xs', 'sm', 'default'], true) ? $attrs['gutter'] : 'default';
$figClass = 'instagram'.($showInfo ? ' instagram-info' : '');

$gutterClass = match ($gutter) {
    'none' => 'gutter-no',
    'xs' => 'gutter-xs',
    'sm' => 'gutter-sm',
    default => '',
};
?>

<?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($placeholder ?? false) { ?>
    
    <div class="instagram-feed-placeholder py-4 text-center text-muted">
        <i class="fab fa-instagram fa-2x mb-2" aria-hidden="true"></i>
        <p class="mb-0">
            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (($attrs['source'] ?? 'manual') === 'api') { ?>
                Configura <code>INSTAGRAM_ACCESS_TOKEN</code> en el archivo <code>.env</code> para activar el feed en tiempo real.
            <?php } else { ?>
                Añade el atributo <code>images</code> con un JSON array o usa <code>source="api"</code>.
            <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
        </p>
    </div>
<?php } elseif (! empty($items)) { ?>

    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($layout === 'carousel') { ?>
        <?php
            $owlOptions = json_encode([
                'autoplay' => $autoplay,
                'autoplayTimeout' => 5000,
                'loop' => true,
                'margin' => 0,
                'responsive' => [
                    '0' => ['items' => 2],
                    '576' => ['items' => 3],
                    '768' => ['items' => 4],
                    '992' => ['items' => $cols],
                ],
            ]);
        ?>
        <div class="owl-carousel owl-theme <?php echo e($gutterClass); ?>"
             data-owl-options='<?php echo $owlOptions; ?>'>
            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__currentLoopData = $items;
        $__env->addLoop($__currentLoopData);
        foreach ($__currentLoopData as $item) {
            $__env->incrementLoopIndices();
            $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
                <figure class="<?php echo e($figClass); ?>">
                    <a href="<?php echo e($item['link'] ?? '#'); ?>" target="_blank" rel="noopener noreferrer">
                        <img src="<?php echo e($item['url']); ?>"
                             alt="<?php echo e($item['alt'] ?? 'Instagram'); ?>"
                             width="220" height="220"
                             loading="lazy">
                    </a>
                </figure>
            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } $__env->popLoop();
        $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>
        </div>

    <?php } elseif ($layout === 'grid') { ?>
        <div class="row cols-<?php echo e($cols); ?> <?php echo e($gutterClass); ?>">
            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__currentLoopData = $items;
        $__env->addLoop($__currentLoopData);
        foreach ($__currentLoopData as $item) {
            $__env->incrementLoopIndices();
            $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
                <figure class="<?php echo e($figClass); ?>">
                    <a href="<?php echo e($item['link'] ?? '#'); ?>" target="_blank" rel="noopener noreferrer">
                        <img src="<?php echo e($item['url']); ?>"
                             alt="<?php echo e($item['alt'] ?? 'Instagram'); ?>"
                             width="220" height="220"
                             loading="lazy">
                    </a>
                </figure>
            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } $__env->popLoop();
        $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>
        </div>

    <?php } else { ?>
        
        <div class="row grid instagram-masonry <?php echo e($gutterClass); ?>">
            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__currentLoopData = $items;
        $__env->addLoop($__currentLoopData);
        foreach ($__currentLoopData as $item) {
            $__env->incrementLoopIndices();
            $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
                <?php
                    $size = in_array($item['size'] ?? '', ['x1', 'x15', 'x2', 'x25'], true) ? $item['size'] : 'x1';
            $colMd = $size === 'x2' ? 'col-md-6' : 'col-md-3 col-6';
            ?>
                <div class="grid-item <?php echo e($colMd); ?> height-<?php echo e($size); ?>">
                    <figure class="<?php echo e($figClass); ?>">
                        <a href="<?php echo e($item['link'] ?? '#'); ?>" target="_blank" rel="noopener noreferrer">
                            <img src="<?php echo e($item['url']); ?>"
                                 alt="<?php echo e($item['alt'] ?? 'Instagram'); ?>"
                                 loading="lazy">
                        </a>
                    </figure>
                </div>
            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } $__env->popLoop();
        $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>
            <div class="col-1 grid-space"></div>
        </div>
    <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>

<?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
<?php /**PATH /Users/developerts/Herd/system/modules/Template/Templates/Riode/Resources/views/shortcodes/instagram-feed.blade.php ENDPATH**/ ?>