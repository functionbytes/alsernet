<?php

use Livewire\Mechanisms\ExtendBlade\ExtendBlade;

    $ctaPhone = theme_option('phone', '+351913893833');
$ctaPhoneDigits = preg_replace('/[^0-9+]/', '', $ctaPhone);
$ctaPhoneIntl = preg_replace('/[^0-9]/', '', $ctaPhone);
$ctaWhatsappUrl = 'https://api.whatsapp.com/send?phone='.$ctaPhoneIntl;
$ctaCallUrl = 'tel:'.$ctaPhoneDigits;
?>

<?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (! empty($ctaPhone)) { ?>
<a href="<?php echo e($ctaWhatsappUrl); ?>" target="_blank" rel="noopener"
   class="bt-buy-now theme-btn"
   aria-label="<?php echo e(__('cta_whatsapp_aria')); ?>">
    <i class="fab fa-whatsapp" aria-hidden="true"></i>
    <span><?php echo e(__('cta_whatsapp')); ?></span>
</a>
<a href="<?php echo e($ctaCallUrl); ?>"
   class="bt-support-now theme-btn"
   aria-label="<?php echo e(__('cta_call_aria')); ?>">
    <i class="fa fa-phone" aria-hidden="true"></i>
    <span><?php echo e(__('cta_call')); ?></span>
</a>


<?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
<?php /**PATH /Users/developerts/Herd/system/platform/themes/caixilhariablanco/partials/shortcodes/cta-floating.blade.php ENDPATH**/ ?>