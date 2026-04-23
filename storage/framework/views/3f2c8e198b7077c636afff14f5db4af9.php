<div
    class="g-recaptcha"
    id="<?php echo e($name); ?>"
    data-sitekey="<?php echo e($siteKey); ?>"
    <?php if (isset($attributes['aria-label'])) { ?>
        aria-label="<?php echo e($attributes['aria-label']); ?>"
    <?php } else { ?>
        aria-label="<?php echo e(trans('captcha::captcha.settings.security_verification')); ?>"
    <?php } ?>
    role="img"
></div>
<?php /**PATH /Users/developerts/Herd/system/modules/Captcha/resources/views/v2/html.blade.php ENDPATH**/ ?>