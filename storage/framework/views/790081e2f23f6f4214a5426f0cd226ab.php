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
                    <h5 class="wow fadeInUp">Proyectos reales</h5>
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

        <div class="gi-footer text-center wow fadeInUp">
            <p class="gi-footer-text">
                <i class="fa-solid fa-location-dot"></i>
                <span><?php echo e(count($images)); ?> instalaciones reales en Alcobaça y toda la región Centro de Portugal</span>
            </p>
        </div>
    </div>
</section>

<style>
.gi-section {
    background: var(--white-color);
    position: relative;
}

.gi-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 24px;
}

@media (max-width: 991px) {
    .gi-grid { grid-template-columns: repeat(2, 1fr); gap: 16px; }
}

@media (max-width: 575px) {
    .gi-grid { grid-template-columns: repeat(1, 1fr); gap: 14px; }
}

.gi-item {
    position: relative;
    display: block;
    overflow: hidden;
    border-radius: 12px;
    cursor: pointer;
    background: #f9f9f9;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
    transition: transform 0.4s cubic-bezier(0.22, 1, 0.36, 1), box-shadow 0.4s ease;
    text-decoration: none;
}

.gi-item:hover {
    transform: translateY(-6px);
    box-shadow: 0 16px 36px rgba(0, 0, 0, 0.14);
}

.gi-img-wrap {
    position: relative;
    aspect-ratio: 4 / 3;
    overflow: hidden;
}

.gi-img-wrap::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(180deg, rgba(0,0,0,0) 60%, rgba(0,0,0,0.35) 100%);
    z-index: 1;
    opacity: 0.6;
    transition: opacity 0.4s ease;
    pointer-events: none;
}

.gi-item:hover .gi-img-wrap::before {
    opacity: 0;
}

.gi-img-wrap img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.8s cubic-bezier(0.22, 1, 0.36, 1);
}

.gi-item:hover .gi-img-wrap img {
    transform: scale(1.08);
}

.gi-overlay {
    position: absolute;
    inset: 0;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    padding: 18px;
    background: linear-gradient(180deg, rgba(177, 1, 0, 0.15) 0%, rgba(0, 0, 0, 0.75) 100%);
    opacity: 0;
    transition: opacity 0.4s ease;
    z-index: 2;
    pointer-events: none;
}

.gi-item:hover .gi-overlay {
    opacity: 1;
}

.gi-zoom-icon {
    align-self: flex-end;
    width: 42px;
    height: 42px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--white-color);
    color: var(--accent-color);
    border-radius: 50%;
    font-size: 14px;
    transform: translateY(-14px) scale(0.8);
    opacity: 0;
    transition: transform 0.4s cubic-bezier(0.22, 1, 0.36, 1), opacity 0.3s ease;
}

.gi-item:hover .gi-zoom-icon {
    transform: translateY(0) scale(1);
    opacity: 1;
}

.gi-label {
    color: var(--white-color);
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    transform: translateY(14px);
    opacity: 0;
    transition: transform 0.4s cubic-bezier(0.22, 1, 0.36, 1), opacity 0.3s ease;
}

.gi-item:hover .gi-label {
    transform: translateY(0);
    opacity: 1;
}

.gi-counter {
    font-size: 11px;
    font-weight: 700;
    opacity: 0.85;
    letter-spacing: 1.5px;
}

.gi-view-text {
    font-size: 13px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
}

.gi-footer {
    margin-top: 50px;
    padding-top: 30px;
    border-top: 1px solid rgba(0, 0, 0, 0.08);
}

.gi-footer-text {
    color: var(--text-color);
    font-weight: 500;
    margin: 0;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    font-size: 15px;
}

.gi-footer-text i {
    color: var(--accent-color);
    font-size: 18px;
}

/* Lightbox styling */
.mfp-fade.mfp-bg {
    opacity: 0;
    transition: opacity 0.3s ease-out;
}

.mfp-fade.mfp-bg.mfp-ready {
    opacity: 0.92;
}
</style>

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