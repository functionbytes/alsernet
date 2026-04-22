<?php

use Livewire\Mechanisms\ExtendBlade\ExtendBlade;

$btnClass = 'btn btn-'.($form->button_variant ?? 'primary');
$btnSize = match ($form->button_size ?? 'md') {
    'sm' => 'btn-sm', 'lg' => 'btn-lg', default => ''
};
$btnWidth = ($form->button_position ?? 'left') === 'full' ? 'w-100' : '';
$customStyle = $buttonColor ? "background-color:{$buttonColor};border-color:{$buttonColor}" : '';
?>
<button type="submit" class="<?php echo e($btnClass); ?> <?php echo e($btnSize); ?> <?php echo e($btnWidth); ?> forms-submit-btn" style="<?php echo e($customStyle); ?>">
    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($form->button_icon) { ?><i class="<?php echo e($form->button_icon); ?> me-1"></i><?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
    <span class="forms-submit-text"><?php echo e($buttonText); ?></span>
    <span class="forms-submit-spinner d-none">
        <span class="spinner-border spinner-border-sm me-1"></span>
        <?php echo e(['es' => 'Enviando...', 'pt' => 'A enviar...', 'en' => 'Sending...', 'fr' => 'Envoi...'][app()->getLocale()] ?? 'Enviando...'); ?>

    </span>
</button>
<?php /**PATH /Users/developerts/Herd/system/modules/Forms/resources/views/public/partials/submit-button.blade.php ENDPATH**/ ?>