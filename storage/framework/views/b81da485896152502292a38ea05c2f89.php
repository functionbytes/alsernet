<?php

use Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys;
use Livewire\Mechanisms\ExtendBlade\ExtendBlade;

    $validLayouts = ['default', 'classic', 'overlay'];

$image = htmlspecialchars($attrs['image'] ?? '', ENT_QUOTES);
$alt = htmlspecialchars($attrs['alt'] ?? ($attrs['name'] ?? ''), ENT_QUOTES);
$name = $attrs['name'] ?? null;
$job = $attrs['job'] ?? null;
$email = $attrs['email'] ?? null;
$phone = $attrs['phone'] ?? null;
$layout = in_array($attrs['layout'] ?? 'default', $validLayouts) ? ($attrs['layout'] ?? 'default') : 'default';
$radius = filter_var($attrs['radius'] ?? true, FILTER_VALIDATE_BOOLEAN);
$animation = $attrs['animation'] ?? null;
$animationDelay = $attrs['animation-delay'] ?? '0s';
$width = (int) ($attrs['width'] ?? 280);
$height = (int) ($attrs['height'] ?? 280);
$extraClass = $attrs['class'] ?? null;

// Social links mapeados a FA6
$socialMap = [
    'facebook' => ['key' => 'social-facebook',  'icon' => 'fab fa-facebook-f'],
    'twitter' => ['key' => 'social-twitter',   'icon' => 'fab fa-twitter'],
    'linkedin' => ['key' => 'social-linkedin',  'icon' => 'fab fa-linkedin-in'],
    'instagram' => ['key' => 'social-instagram', 'icon' => 'fab fa-instagram'],
    'youtube' => ['key' => 'social-youtube',   'icon' => 'fab fa-youtube'],
];

$socials = collect($socialMap)->filter(fn ($s) => ! empty($attrs[$s['key']]));

// layout=overlay sin datos de contacto → degradar a default
if ($layout === 'overlay' && $socials->isEmpty() && ! $email && ! $phone) {
    $layout = 'default';
}

$wrapperClasses = collect([
    'image-box',
    $layout === 'overlay' ? 'image-overlay' : null,
    $animation ? 'appear-animate' : null,
    $extraClass,
])->filter()->implode(' ');

$figureClass = $radius ? 'banner-radius' : null;
?>

<div class="<?php echo e($wrapperClasses); ?>"
     <?php if ($animation) { ?>
         data-animation-options='{"name":"<?php echo e(htmlspecialchars($animation, ENT_QUOTES)); ?>","delay":"<?php echo e(htmlspecialchars($animationDelay, ENT_QUOTES)); ?>"}'
     <?php } ?>>

    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($layout === 'overlay') { ?>

        <figure <?php if ($figureClass) { ?> class="<?php echo e($figureClass); ?>" <?php } ?>>
            <img src="<?php echo e($image); ?>"
                 alt="<?php echo e($alt); ?>"
                 width="<?php echo e($width); ?>"
                 height="<?php echo e($height); ?>"
                 loading="lazy">
            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($name) { ?>
                <h4 class="overlay-visible"><?php echo e($name); ?></h4>
            <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
            <div class="overlay overlay-transparent">
                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($email) { ?>
                    <a href="mailto:<?php echo e(htmlspecialchars($email, ENT_QUOTES)); ?>"><?php echo e($email); ?></a>
                <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($phone) { ?>
                    <a href="tel:<?php echo e(preg_replace('/[^+\d]/', '', $phone)); ?>"><?php echo e($phone); ?></a>
                <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($socials->isNotEmpty()) { ?>
                    <div class="social-links mt-1">
                        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__currentLoopData = $socials;
                    $__env->addLoop($__currentLoopData);
                    foreach ($__currentLoopData as $network => $s) {
                        $__env->incrementLoopIndices();
                        $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
                            <a href="<?php echo e(htmlspecialchars($attrs[$s['key']], ENT_QUOTES)); ?>"
                               class="social-link social-<?php echo e($network); ?>"
                               aria-label="<?php echo e(ucfirst($network)); ?>"
                               target="_blank"
                               rel="noopener noreferrer">
                                <i class="<?php echo e($s['icon']); ?>" aria-hidden="true"></i>
                            </a>
                        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } $__env->popLoop();
                    $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>
                    </div>
                <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
            </div>
        </figure>

    <?php } elseif ($layout === 'classic') { ?>

        <figure <?php if ($figureClass) { ?> class="<?php echo e($figureClass); ?>" <?php } ?>>
            <img src="<?php echo e($image); ?>"
                 alt="<?php echo e($alt); ?>"
                 width="<?php echo e($width); ?>"
                 height="<?php echo e($height); ?>"
                 loading="lazy">
            <div class="overlay social-links">
                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($name) { ?>
                    <h4><?php echo e($name); ?></h4>
                <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($job) { ?>
                    <h5><?php echo e($job); ?></h5>
                <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__currentLoopData = $socials;
        $__env->addLoop($__currentLoopData);
        foreach ($__currentLoopData as $network => $s) {
            $__env->incrementLoopIndices();
            $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
                    <a href="<?php echo e(htmlspecialchars($attrs[$s['key']], ENT_QUOTES)); ?>"
                       class="social-link social-<?php echo e($network); ?>"
                       aria-label="<?php echo e(ucfirst($network)); ?>"
                       target="_blank"
                       rel="noopener noreferrer">
                        <i class="<?php echo e($s['icon']); ?>" aria-hidden="true"></i>
                    </a>
                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } $__env->popLoop();
        $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>
            </div>
        </figure>

    <?php } else { ?>

        
        <figure <?php if ($figureClass) { ?> class="<?php echo e($figureClass); ?>" <?php } ?>>
            <img src="<?php echo e($image); ?>"
                 alt="<?php echo e($alt); ?>"
                 width="<?php echo e($width); ?>"
                 height="<?php echo e($height); ?>"
                 loading="lazy">
        </figure>
        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($name) { ?>
            <h4 class="image-box-name"><?php echo e($name); ?></h4>
        <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($job) { ?>
            <h5 class="image-box-job"><?php echo e($job); ?></h5>
        <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($socials->isNotEmpty()) { ?>
            <div class="social-links pt-2 pb-2">
                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__currentLoopData = $socials;
            $__env->addLoop($__currentLoopData);
            foreach ($__currentLoopData as $network => $s) {
                $__env->incrementLoopIndices();
                $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
                    <a href="<?php echo e(htmlspecialchars($attrs[$s['key']], ENT_QUOTES)); ?>"
                       class="social-link social-<?php echo e($network); ?>"
                       aria-label="<?php echo e(ucfirst($network)); ?>"
                       target="_blank"
                       rel="noopener noreferrer">
                        <i class="<?php echo e($s['icon']); ?>" aria-hidden="true"></i>
                    </a>
                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } $__env->popLoop();
            $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>
            </div>
        <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>

    <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>

</div>

<?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($animation) { ?>
    <?php if (! $__env->hasRenderedOnce('0f0ed166-5a64-4676-bf69-b356d5551633')) {
        $__env->markAsRenderedOnce('0f0ed166-5a64-4676-bf69-b356d5551633'); ?>
    <?php $__env->startPush('scripts'); ?>
    <script>
    (function () {
        'use strict';

        if (typeof IntersectionObserver === 'undefined') {
            return;
        }

        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (! entry.isIntersecting) {
                    return;
                }

                var el = entry.target;
                var opts = {};

                try {
                    opts = JSON.parse(el.dataset.animationOptions || '{}');
                } catch (e) {}

                el.style.animationDelay = opts.delay || '0s';
                el.classList.add('animated', opts.name || 'fadeIn');
                observer.unobserve(el);
            });
        }, { threshold: 0.15 });

        document.querySelectorAll('.appear-animate').forEach(function (el) {
            observer.observe(el);
        });
    }());
    </script>
    <?php $__env->stopPush(); ?>
    <?php } ?>
<?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
<?php /**PATH /Users/developerts/Herd/system/modules/Template/Templates/Riode/Resources/views/shortcodes/image-box.blade.php ENDPATH**/ ?>