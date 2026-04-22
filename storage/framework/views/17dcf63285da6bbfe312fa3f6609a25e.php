<?php
use Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys;
use Livewire\Mechanisms\ExtendBlade\ExtendBlade;

if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (! empty($slides)) { ?>
<style>
    .slider-header-carousel .heading1 h1.main-heading,
    .slider-header-carousel .heading1 h5 {
        text-transform: none;
    }
    .slider-header-carousel .heading1 h1.main-heading::first-letter,
    .slider-header-carousel .heading1 h5::first-letter {
        text-transform: uppercase;
    }
</style>
<div class="slider-header-carousel owl-carousel">
    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__currentLoopData = $slides;
    $__env->addLoop($__currentLoopData);
    foreach ($__currentLoopData as $slide) {
        $__env->incrementLoopIndices();
        $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
    <div class="hero1-section-area">
        <img src="<?php echo e($slide['image']); ?>" alt="" class="header-img1"
             <?php echo e($loop->first ? 'fetchpriority="high"' : 'loading="lazy"'); ?>>
        <div class="container">
            <div class="row">
                <div class="col-lg-6">
                    <div class="hero-heading-area heading1">
                        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (! empty($slide['badge'])) { ?>
                            <h5><?php echo e($slide['badge']); ?></h5>
                        <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                        <h1 class="main-heading"><?php echo e($slide['title'] ?? ''); ?></h1>
                        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (! empty($slide['text'])) { ?>
                            <p class="pera"><?php echo e($slide['text']); ?></p>
                        <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                        <div class="btn-area">
                            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (! empty($slide['btn1_text'])) { ?>
                                <a href="<?php echo e($slide['btn1_url'] ?? '#'); ?>" class="header-btn1">
                                    <?php echo e($slide['btn1_text']); ?> <i class="fa-solid fa-arrow-right"></i>
                                </a>
                            <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (! empty($slide['btn2_text'])) { ?>
                                <a href="<?php echo e($slide['btn2_url'] ?? '#'); ?>" class="header-btn2">
                                    <?php echo e($slide['btn2_text']); ?> <i class="fa-solid fa-arrow-right"></i>
                                </a>
                            <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                        </div>
                        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (! empty($slide['stat'])) { ?>
                        <div class="header-bottom-images">
                            <div class="img1">
                                <img src="/pages/images/all-images/header-bottom.png" alt="" loading="lazy">
                            </div>
                            <div class="content">
                                <ul>
                                    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php for ($i = 0; $i < 5; $i++) { ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
                                        <li><i class="fa-solid fa-star"></i></li>
                                    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>
                                </ul>
                                <p><span><?php echo e($slide['stat']); ?></span> <?php echo e($slide['stat_label'] ?? ''); ?></p>
                            </div>
                        </div>
                        <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } $__env->popLoop();
    $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>
</div>
<?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
<?php /**PATH /Users/developerts/Herd/system/platform/themes/caixilhariablanco/partials/shortcodes/hero-slider.blade.php ENDPATH**/ ?>