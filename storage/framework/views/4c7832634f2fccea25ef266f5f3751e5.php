<?php

use Livewire\Mechanisms\ExtendBlade\ExtendBlade;

    $layout = in_array($attrs['layout'] ?? '', ['promo', 'subscribe', 'one-col', '2-cols', '3-cols', 'parallax'])
        ? $attrs['layout']
        : 'promo';
$background = $attrs['background'] ?? 'white';
$backgroundImage = $attrs['background-image'] ?? null;
$align = in_array($attrs['align'] ?? '', ['left', 'center', 'right']) ? $attrs['align'] : 'left';
$title = $attrs['title'] ?? null;
$subtitle = $attrs['subtitle'] ?? null;
$description = $attrs['description'] ?? null;
$buttonText = $attrs['button-text'] ?? null;
$buttonHref = $attrs['button-href'] ?? '#';
$buttonStyle = in_array($attrs['button-style'] ?? '', ['primary', 'dark', 'white-outline']) ? $attrs['button-style'] : 'primary';
$formAction = $attrs['form-action'] ?? null;
$formPlaceholder = $attrs['form-placeholder'] ?? __('shortcode::shortcode.cta_form_placeholder');
$formButton = $attrs['form-button'] ?? __('shortcode::shortcode.cta_form_button');
$showSocial = filter_var($attrs['show-social'] ?? false, FILTER_VALIDATE_BOOLEAN);
$extraClass = $attrs['class'] ?? null;

$bgClass = match ($background) {
    'grey' => 'bg-grey',
    'dark' => 'bg-dark text-white',
    'image', 'parallax' => '',
    default => 'bg-white',
};

$wrapperClasses = collect([
    'banner',
    $layout === 'promo' ? 'cta-simple' : null,
    $layout === 'subscribe' ? 'banner-newsletter' : null,
    $layout === 'one-col' ? 'banner-one-col' : null,
    $layout === '2-cols' ? 'banner-2 banner-fixed content-center content-middle' : null,
    $layout === '3-cols' ? 'banner-group' : null,
    $layout === 'parallax' ? 'banner-background parallax' : null,
    'text-'.$align,
    $extraClass,
])->filter()->implode(' ');
?>

<div class="<?php echo e($wrapperClasses); ?>"
     <?php if ($backgroundImage) { ?> data-image-src="<?php echo e($backgroundImage); ?>" data-parallax='{"speed":1.5,"horizontalPosition":"50%"}' <?php } ?>>

    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($layout === '3-cols') { ?>
        <div class="row g-0">
            <?php echo $content; ?>

        </div>
    <?php } else { ?>
        <div class="banner-content <?php echo e($bgClass); ?> <?php if ($layout === 'promo') { ?> d-lg-flex align-items-center <?php } ?>">

            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($title || $subtitle) { ?>
                <div class="banner-header">
                    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($title) { ?>
                        <h3 class="banner-title font-weight-bold ls-s text-uppercase"><?php echo e($title); ?></h3>
                    <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($subtitle) { ?>
                        <h4 class="banner-subtitle font-weight-normal ls-s"><?php echo e($subtitle); ?></h4>
                    <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                </div>
            <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>

            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($description) { ?>
                <div class="banner-text">
                    <p class="ls-m mb-0"><?php echo e($description); ?></p>
                </div>
            <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>

            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (in_array($layout, ['subscribe', 'parallax']) && $formAction) { ?>
                <form action="<?php echo e($formAction); ?>" method="POST" class="input-wrapper input-wrapper-round form-solid">
                    <?php echo csrf_field(); ?>
                    <input type="email" name="email" class="form-control" placeholder="<?php echo e($formPlaceholder); ?>" required>
                    <button type="submit" class="btn btn-primary btn-rounded"><?php echo e($formButton); ?></button>
                </form>
            <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>

            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($buttonText) { ?>
                <a href="<?php echo e($buttonHref); ?>" class="btn btn-<?php echo e($buttonStyle); ?> btn-ellipse">
                    <?php echo e($buttonText); ?><i class="fas fa-arrow-right ms-2" aria-hidden="true"></i>
                </a>
            <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>

            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($showSocial && $layout === 'parallax') { ?>
                
            <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>

            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (in_array($layout, ['promo', 'one-col', '2-cols']) && $content) { ?>
                <?php echo $content; ?>

            <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
        </div>
    <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>

    <noscript>
        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($buttonText) { ?>
            <a href="<?php echo e($buttonHref); ?>"><?php echo e($buttonText); ?></a>
        <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
    </noscript>
</div>

<?php if (! $__env->hasRenderedOnce('b4eedbc5-ffe5-429b-91bc-6c6d256384c2')) {
    $__env->markAsRenderedOnce('b4eedbc5-ffe5-429b-91bc-6c6d256384c2'); ?>
<?php $__env->startPush('scripts'); ?>
<script>
$(document).on('submit', '.banner-newsletter form, .banner-background form', function (e) {
    e.preventDefault();
    var $form = $(this);
    $.post($form.attr('action'), $form.serialize() + '&_token=' + $('meta[name="csrf-token"]').attr('content'))
        .done(function () {
            toastr.success('<?php echo e(__('shortcode::shortcode.cta_subscribe_success')); ?>');
            $form[0].reset();
        })
        .fail(function (xhr) {
            var msg = xhr.responseJSON?.message ?? '<?php echo e(__('shortcode::shortcode.cta_subscribe_error')); ?>';
            toastr.error(msg);
        });
});
</script>
<?php $__env->stopPush(); ?>
<?php } ?>
<?php /**PATH /Users/developerts/Herd/system/modules/Template/Templates/Riode/Resources/views/shortcodes/cta.blade.php ENDPATH**/ ?>