<?php

use Illuminate\Support\Js;
use Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys;
use Livewire\Mechanisms\ExtendBlade\ExtendBlade;

    $enabled = cookie_option('enabled') === '1';
$bgColor = cookie_option('bg_color', '#ffffff');
$textColor = cookie_option('text_color', '#212529');
$btnColor = cookie_option('btn_color', '#90bb13');
$position = cookie_option('position', 'bottom');
$style = cookie_option('style', config('Cookie.general.style', 'full-width'));
$styleClass = 'cookie-consent-'.$style;
$maxWidth = cookie_option('max_width', config('Cookie.general.max_width', 1170));
$message = cookie_option('message', config('Cookie.general.message', 'We use cookies to improve your experience.'));
$acceptText = cookie_option('accept_btn_text', config('Cookie.general.button_text', 'Permitir cookies'));
$rejectText = cookie_option('reject_btn_text', config('Cookie.general.reject_text', 'Rechazar'));
$customText = cookie_option('customize_btn_text', config('Cookie.general.customize_text', 'Personalizar preferencias'));
$saveText = cookie_option('save_btn_text', config('Cookie.general.save_text', 'Guardar preferencias'));
$learnMoreUrl = cookie_option('more_info_url', config('Cookie.general.learn_more_url', ''));
$learnMoreText = cookie_option('more_info_text', config('Cookie.general.learn_more_text', ''));
$categories = config('Cookie.general.cookie_categories', []);
$direction = app()->getLocale() === 'ar' ? 'rtl' : 'ltr';
$gaEnabled = cookie_option('google_analytics_enabled') === '1';
$gaId = cookie_option('google_analytics_id');
$fbPixelEnabled = cookie_option('facebook_pixel_enabled') === '1';
$fbPixelId = cookie_option('facebook_pixel_id');
$cookieName = config('Cookie.general.cookie_name', 'cookie_for_consent');
$cookieLifetime = config('Cookie.general.cookie_lifetime', 7300);

$positionClass = match ($position) {
    'top' => 'cookie-consent--position-top',
    'center' => 'cookie-consent--position-center',
    default => '',
};
?>

<?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($enabled) { ?>
<style>
.cookie-consent--visible { background-color: <?php echo e($bgColor); ?>; }
.cookie-consent__agree, .cookie-consent__save-button {
    background-color: <?php echo e($btnColor); ?>;
    border-color: <?php echo e($btnColor); ?>;
    color: #fff;
}
</style>
<div id="cookie-banner"
     class="js-cookie-consent cookie-consent <?php echo e($styleClass); ?> <?php echo e($positionClass); ?> d-none"
     style="color: <?php echo e($textColor); ?>;"
     dir="<?php echo e($direction); ?>"
     data-cookie-name="<?php echo e($cookieName); ?>"
     data-cookie-lifetime="<?php echo e($cookieLifetime); ?>"
     data-cookie-domain="<?php echo e(config('session.domain') ?? request()->getHost()); ?>"
     data-cookie-secure="<?php echo e(config('session.secure') ? '1' : '0'); ?>"
     role="dialog"
     aria-live="polite"
     aria-label="Aviso de cookies"
     aria-modal="false"
>
    <div class="cookie-consent-body" style="max-width: <?php echo e($maxWidth); ?>px;">
        <div class="cookie-consent__inner">

            <div class="cookie-consent__message">
                <?php echo e($message); ?>

                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($learnMoreUrl && $learnMoreText) { ?>
                    <a href="<?php echo e($learnMoreUrl); ?>"><?php echo e($learnMoreText); ?></a>
                <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
            </div>

            <div class="cookie-consent__actions">
                <button class="js-cookie-reject cookie-consent__reject"><?php echo e($rejectText); ?></button>

                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (! empty($categories) && count($categories) > 1) { ?>
                    <button class="js-cookie-customize cookie-consent__customize"
                            data-bs-toggle="modal" data-bs-target="#cookie-preferences-modal"><?php echo e($customText); ?></button>
                <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>

                <button class="js-cookie-accept cookie-consent__agree"><?php echo e($acceptText); ?></button>
            </div>

            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (! empty($categories)) { ?>
                <div class="cookie-consent__categories">
                    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__currentLoopData = $categories;
                $__env->addLoop($__currentLoopData);
                foreach ($__currentLoopData as $key => $category) {
                    $__env->incrementLoopIndices();
                    $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
                        <div class="cookie-category">
                            <label class="cookie-category__label">
                                <input type="checkbox" class="cookie-category-toggle" data-category="<?php echo e($key); ?>" value="<?php echo e($key); ?>"
                                    <?php echo e(! empty($category['required']) ? 'checked disabled' : 'checked'); ?>>
                                <span class="cookie-category__name"><?php echo e($category['name']); ?></span>
                            </label>
                            <p class="cookie-category__description"><?php echo e($category['description']); ?></p>
                        </div>
                    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } $__env->popLoop();
                $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>

                    <div class="cookie-consent__save">
                        <button class="js-cookie-save-preferences cookie-consent__save-button">
                            <?php echo e($saveText); ?>

                        </button>
                    </div>
                </div>
            <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>

        </div>
    </div>
</div>

<?php echo $__env->make('cookie::components.preferences-modal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>

<?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($enabled && $gaEnabled && $gaId) { ?>
<!-- Google Analytics Global Site Tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo e($gaId); ?>"></script>
<script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());

    gtag('consent', 'default', {
        'ad_storage': 'denied',
        'analytics_storage': 'denied',
        'functionality_storage': 'denied',
        'personalization_storage': 'denied',
        'security_storage': 'granted'
    });

    window._cookieGaId = <?php echo Js::from($gaId); ?>;
</script>
<?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>

<?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($enabled && $fbPixelEnabled && $fbPixelId) { ?>
<script>window._cookieFbPixelId = <?php echo Js::from($fbPixelId); ?>;</script>
<?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
<?php /**PATH /Users/developerts/Herd/system/modules/Cookie/resources/views/index.blade.php ENDPATH**/ ?>