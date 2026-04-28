<?php

use Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys;
use Livewire\Mechanisms\ExtendBlade\ExtendBlade;

    // This view is a child emitter: it outputs a self-contained HTML block
// that the parent [testimonials] view collects and wraps.
$author = $attrs['author'] ?? '';
$role = $attrs['role'] ?? null;
$avatar = $attrs['avatar'] ?? null;
$rating = isset($attrs['rating']) ? min(5, max(0, (float) $attrs['rating'])) : null;
$quote = trim($content);
$extraClass = $attrs['class'] ?? null;

$fullStars = $rating !== null ? (int) $rating : 0;
$halfStar = $rating !== null && ($rating - $fullStars) >= 0.5;
$emptyStars = $rating !== null ? max(0, 5 - $fullStars - ($halfStar ? 1 : 0)) : 0;
?>

<div class="testimonial-child<?php echo e($extraClass ? ' '.$extraClass : ''); ?>"
     data-testimonial-item="1">
    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($quote) { ?>
        <blockquote><?php echo e($quote); ?></blockquote>
    <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>

    <div class="testimonial-info">
        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($avatar) { ?>
            <figure class="testimonial-author-thumbnail">
                <img src="<?php echo e(htmlspecialchars($avatar)); ?>"
                     alt="<?php echo e(e($author)); ?>"
                     width="50" height="50"
                     loading="lazy">
            </figure>
        <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>

        <cite>
            <?php echo e($author); ?>

            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($role) { ?>
                <span><?php echo e($role); ?></span>
            <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
        </cite>

        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($rating !== null) { ?>
            <div class="testimonial-rating" aria-label="<?php echo e(__('shortcode::shortcode.testimonial.rating', ['n' => $rating])); ?>">
                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php for ($i = 0; $i < $fullStars; $i++) { ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
                    <i class="fas fa-star" aria-hidden="true"></i>
                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>
                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($halfStar) { ?>
                    <i class="fas fa-star-half-alt" aria-hidden="true"></i>
                <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php for ($i = 0; $i < $emptyStars; $i++) { ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
                    <i class="far fa-star" aria-hidden="true"></i>
                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>
            </div>
        <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
    </div>
</div>
<?php /**PATH /Users/developerts/Herd/system/modules/Template/Templates/Riode/Resources/views/shortcodes/testimonial.blade.php ENDPATH**/ ?>