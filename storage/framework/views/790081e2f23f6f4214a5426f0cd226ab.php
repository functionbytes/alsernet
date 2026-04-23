<?php
use Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys;
use Livewire\Mechanisms\ExtendBlade\ExtendBlade;

if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (! empty($images)) { ?>
<?php $gid = 'gi-'.substr(md5(implode(',', $images)), 0, 8); ?>

<section class="gi-section sp1">
    <div class="container">
        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (! empty($title) || ! empty($subtitle)) { ?>
            <div class="row justify-content-center heading6 mb-5 wow fadeInUp">
                <div class="col-lg-7 text-center">
                    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (! empty($title)) { ?>
                        <h2 class="wow fadeInUp" data-wow-delay="0.1s"><?php echo e($title); ?></h2>
                    <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (! empty($subtitle)) { ?>
                        <p class="wow fadeInUp" data-wow-delay="0.2s"><?php echo e($subtitle); ?></p>
                    <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                </div>
            </div>
        <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>

        <div class="gi-grid">
            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__currentLoopData = $images;
    $__env->addLoop($__currentLoopData);
    foreach ($__currentLoopData as $src) {
        $__env->incrementLoopIndices();
        $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
                <a href="<?php echo e($src); ?>"
                   class="gi-item wow fadeInUp"
                   data-gallery="<?php echo e($gid); ?>"
                   data-wow-delay="<?php echo e(number_format(0.05 * $loop->index, 2)); ?>s">
                    <div class="gi-img-wrap">
                        <img src="<?php echo e($src); ?>"
                             alt="Instalación <?php echo e($loop->iteration); ?>"
                             loading="<?php echo e($loop->first ? 'eager' : 'lazy'); ?>">
                    </div>
                    <div class="gi-overlay">
                        <span class="gi-zoom-icon">
                            <i class="fa-solid fa-magnifying-glass-plus"></i>
                        </span>
                        <div class="gi-label">
                            <span class="gi-counter"><?php echo e(str_pad($loop->iteration, 2, '0', STR_PAD_LEFT)); ?> / <?php echo e(str_pad(count($images), 2, '0', STR_PAD_LEFT)); ?></span>
                            <span class="gi-view-text">Ver proyecto <i class="fa-solid fa-arrow-right ms-1"></i></span>
                        </div>
                    </div>
                </a>
            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } $__env->popLoop();
    $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>
        </div>

    </div>
</section>



<script>
(function () {
    var gid = '<?php echo e($gid); ?>';
    var attempts = 0;

    function initGallery() {
        attempts++;
        if (typeof window.jQuery === 'undefined' || typeof window.jQuery.fn.magnificPopup === 'undefined') {
            if (attempts < 50) {
                return setTimeout(initGallery, 100);
            }
            console.warn('[galeria-instalaciones] magnific-popup not available after 5s');
            return;
        }

        var $items = window.jQuery('.gi-item[data-gallery="' + gid + '"]');
        if (!$items.length || $items.data('mfp-initialized')) return;
        $items.data('mfp-initialized', true);

        var total = $items.length;
        $items.magnificPopup({
            type: 'image',
            gallery: { enabled: true, navigateByImgClick: true, preload: [0, 1] },
            image: {
                titleSrc: function (item) {
                    var idx = (item.index || 0) + 1;
                    return String(idx).padStart(2, '0') + ' / ' + String(total).padStart(2, '0');
                }
            },
            removalDelay: 300,
            mainClass: 'mfp-fade'
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initGallery);
    } else {
        initGallery();
    }
    window.addEventListener('load', initGallery);
}());
</script>
<?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
<?php /**PATH /Users/developerts/Herd/system/platform/themes/caixilhariablanco/partials/shortcodes/installation-gallery.blade.php ENDPATH**/ ?>