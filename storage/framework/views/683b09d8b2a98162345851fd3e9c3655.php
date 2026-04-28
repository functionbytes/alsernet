<?php

use Livewire\Mechanisms\ExtendBlade\ExtendBlade;

    $image = $attrs['image'] ?? null;
$title = $attrs['title'] ?? null;
$subtitle = $attrs['subtitle'] ?? null;
$buttonText = $attrs['button-text'] ?? null;
$buttonHref = $attrs['button-href'] ?? '#';
$extraClass = $attrs['class'] ?? null;
?>

<div class="col banner banner-radius <?php echo e($extraClass); ?>">
    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($image) { ?>
        <img src="<?php echo e($image); ?>" alt="<?php echo e($title); ?>" class="banner-img img-fluid w-100" loading="lazy">
    <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
    <div class="banner-content">
        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($title) { ?>
            <h4 class="banner-title"><?php echo e($title); ?></h4>
        <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($subtitle) { ?>
            <p class="banner-subtitle mb-0"><?php echo e($subtitle); ?></p>
        <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($buttonText) { ?>
            <a href="<?php echo e($buttonHref); ?>" class="btn btn-primary btn-sm mt-2"><?php echo e($buttonText); ?></a>
        <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
    </div>
</div>
<?php /**PATH /Users/developerts/Herd/system/modules/Template/Templates/Riode/Resources/views/shortcodes/cta-column.blade.php ENDPATH**/ ?>