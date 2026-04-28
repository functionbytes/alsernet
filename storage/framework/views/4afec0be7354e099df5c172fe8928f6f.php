<?php

use Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys;
use Livewire\Mechanisms\ExtendBlade\ExtendBlade;

    // TODO: Acoplar a módulo Vendor cuando esté disponible en el proyecto.
// El proyecto NO tiene módulo Marketplace/Multi-vendor. Este shortcode renderiza
// exclusivamente con los datos pasados como atributos (sin consulta a BD).

$layoutClass = match (in_array($attrs['layout'] ?? '', ['style2', 'style3'], true) ? $attrs['layout'] : 'default') {
    'style2' => ' vendor-type2',
    'style3' => ' vendor-type3',
    default => '',
};

$name = $attrs['name'] ?? '';
$href = $attrs['href'] ?? '#';
$logo = $attrs['logo'] ?? '';
$productsCount = isset($attrs['products-count']) ? (int) $attrs['products-count'] : null;
$hasRating = isset($attrs['rating']);
$rating = $hasRating ? max(0, min(100, (int) $attrs['rating'])) : null;
?>

<div class="vendor-widget<?php echo e($layoutClass); ?>">
    <div class="vendor-details">
        <figure class="vendor-logo">
            <a href="<?php echo e($href); ?>">
                <img src="<?php echo e($logo); ?>"
                     alt="<?php echo e($name); ?> logo"
                     width="70" height="70"
                     loading="lazy">
            </a>
        </figure>

        <div class="vendor-personal">
            <h4 class="vendor-name">
                <a href="<?php echo e($href); ?>"><?php echo e($name); ?></a>
                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($productsCount !== null) { ?>
                    <span class="vendor-products-count">
                        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($productsCount > 0) { ?>
                            ( <?php echo e($productsCount); ?> Productos )
                        <?php } else { ?>
                            (Sin productos)
                        <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                    </span>
                <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
            </h4>

            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($hasRating) { ?>
                <div class="ratings-container mb-0">
                    <div class="ratings-full">
                        <span class="ratings" data-rating="<?php echo e($rating); ?>"></span>
                        <span class="tooltiptext tooltip-top"><?php echo e(number_format($rating / 20, 1)); ?> / 5</span>
                    </div>
                </div>
            <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
        </div>
    </div>

    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (! empty($productImages)) { ?>
        <div class="vendor-products grid-type gutter-xs">
            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__currentLoopData = $productImages;
        $__env->addLoop($__currentLoopData);
        foreach ($__currentLoopData as $img) {
            $__env->incrementLoopIndices();
            $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
                <div class="vendor-product">
                    <figure class="product-media">
                        <a href="<?php echo e($img['href'] ?? '#'); ?>">
                            <img src="<?php echo e($img['url']); ?>"
                                 alt="<?php echo e($img['alt'] ?? 'producto'); ?>"
                                 loading="lazy">
                        </a>
                    </figure>
                </div>
            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } $__env->popLoop();
        $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>
        </div>
    <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
</div>

<?php if (! $__env->hasRenderedOnce('02c6315b-f267-4678-bb25-f8005b7e0d80')) {
    $__env->markAsRenderedOnce('02c6315b-f267-4678-bb25-f8005b7e0d80'); ?>
<?php $__env->startPush('scripts'); ?>
<script>
(function () {
    'use strict';
    // Apply rating bar width via JS to avoid inline style on the span
    document.querySelectorAll('.ratings[data-rating]').forEach(function (el) {
        var pct = parseInt(el.getAttribute('data-rating'), 10) || 0;
        el.style.width = Math.max(0, Math.min(100, pct)) + '%';
    });
}());
</script>
<?php $__env->stopPush(); ?>
<?php } ?>
<?php /**PATH /Users/developerts/Herd/system/modules/Template/Templates/Riode/Resources/views/shortcodes/vendor-card.blade.php ENDPATH**/ ?>