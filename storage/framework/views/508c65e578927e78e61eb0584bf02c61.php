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

<style>
.theme-btn {
    background-color: #008bce;
    border-radius: 37px;
    bottom: 17px;
    color: #fff;
    display: table;
    height: 50px;
    left: 25px;
    min-width: 49px;
    position: fixed;
    text-align: center;
    z-index: 9999;
    text-decoration: none;
    overflow: hidden;
}
.theme-btn i {
    font-size: 20px;
    line-height: 52px;
    font-weight: 600;
    display: table-cell;
    vertical-align: middle;
    padding: 0 15px;
}
.theme-btn.bt-support-now {
    background: #b10100;
    bottom: 74px;
}
.theme-btn.bt-buy-now {
    background: #000000;
}
.theme-btn:hover {
    color: #fff;
    padding: 0 20px;
}
.theme-btn span {
    display: table-cell;
    vertical-align: middle;
    font-size: 16px;
    letter-spacing: -15px;
    opacity: 0;
    line-height: 50px;
    transition: all .5s ease;
    -webkit-transition: all .5s ease;
    -moz-transition: all .5s ease;
    text-transform: uppercase;
    white-space: nowrap;
    max-width: 0;
    overflow: hidden;
}
.theme-btn:hover span {
    opacity: 1;
    letter-spacing: 1px;
    padding-left: 10px;
    padding-right: 6px;
    max-width: 200px;
}
</style>
<?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
<?php /**PATH /Users/developerts/Herd/system/platform/themes/caixilhariablanco/partials/shortcodes/cta-floating.blade.php ENDPATH**/ ?>