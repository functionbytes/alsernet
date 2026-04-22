<?php

use Illuminate\Support\Js;
use Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys;
use Livewire\Mechanisms\ExtendBlade\ExtendBlade;
use Modules\Page\Models\Page;

    $geoEnabled = cookie_option('geo_targeting_enabled') === '1';
$allowedRegion = true;

if ($geoEnabled && function_exists('geoip')) {
    try {
        $geo = @geoip(request()->ip());
        $euCountries = ['AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR', 'DE', 'GR', 'HU', 'IE', 'IT', 'LV', 'LT', 'LU', 'MT', 'NL', 'PL', 'PT', 'RO', 'SK', 'SI', 'ES', 'SE', 'GB', 'IS', 'LI', 'NO'];
        $allowedRegion = in_array($geo->iso_code ?? '', $euCountries);
    } catch (Throwable $e) {
        $allowedRegion = true;
    }
}

$enabled = cookie_option('enabled') === '1';
$bgColor = '#ffffff';
$textColor = '#212529';
$btnColor = '#b10100';
$position = cookie_option('position', 'bottom');
$style = cookie_option('style', config('Cookie.general.style', 'full-width'));
$styleClass = 'cookie-consent-'.$style;
$maxWidth = config('Cookie.general.max_width', 1170);
$message = __('cookie::messages.banner.message');
$acceptText = __('cookie::messages.banner.accept');
$rejectText = __('cookie::messages.banner.reject');
$customText = __('cookie::messages.banner.customize');
$saveText = __('cookie::messages.modal.save');
$policyPageId = cookie_option('policy_page_id');
$policyPage = $policyPageId ? Page::find($policyPageId) : null;
$learnMoreUrl = $policyPage && $policyPage->status === 'published'
    ? url($policyPage->slug)
    : config('Cookie.general.learn_more_url', '');
$learnMoreText = __('cookie::messages.banner.learn_more');
$categories = config('Cookie.general.cookie_categories', []);
$direction = app()->getLocale() === 'ar' ? 'rtl' : 'ltr';
$gaEnabled = cookie_option('google_analytics_enabled') === '1';
$gaId = cookie_option('google_analytics_id');
$fbPixelEnabled = cookie_option('facebook_pixel_enabled') === '1';
$fbPixelId = cookie_option('facebook_pixel_id');
$gtmEnabled = cookie_option('google_tag_manager_enabled') === '1';
$gtmId = cookie_option('google_tag_manager_id');
$linkedinEnabled = cookie_option('linkedin_insight_enabled') === '1';
$linkedinPartnerId = cookie_option('linkedin_partner_id');
$tiktokEnabled = cookie_option('tiktok_pixel_enabled') === '1';
$tiktokPixelId = cookie_option('tiktok_pixel_id');
$twitterEnabled = cookie_option('twitter_pixel_enabled') === '1';
$twitterPixelId = cookie_option('twitter_pixel_id');
$msUetEnabled = cookie_option('microsoft_uet_enabled') === '1';
$msUetId = cookie_option('microsoft_uet_id');
$cookieName = config('Cookie.general.cookie_name', 'cookie_for_consent');
$cookieLifetime = config('Cookie.general.cookie_lifetime', 7300);
$consentVersion = cookie_option('consent_version', '1.0');

$positionClass = match ($position) {
    'top' => 'cookie-consent--position-top',
    'center' => 'cookie-consent--position-center',
    default => '',
};
?>

<?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($enabled && $allowedRegion) { ?>
<div id="cookie-banner"
     class="js-cookie-consent cookie-consent <?php echo e($styleClass); ?> <?php echo e($positionClass); ?> d-none"
     style="--cookie-bg: <?php echo e($bgColor); ?>; --cookie-text: <?php echo e($textColor); ?>; --cookie-btn: <?php echo e($btnColor); ?>;"
     dir="<?php echo e($direction); ?>"
     data-cookie-name="<?php echo e($cookieName); ?>"
     data-cookie-lifetime="<?php echo e($cookieLifetime); ?>"
     data-consent-version="<?php echo e($consentVersion); ?>"
     data-cookie-domain="<?php echo e(config('session.domain') ?? request()->getHost()); ?>"
     data-cookie-secure="<?php echo e(config('session.secure') ? '1' : '0'); ?>"
     role="dialog"
     aria-live="polite"
     aria-label="Aviso de cookies"
     aria-modal="false"
>
    <div class="cookie-consent-body" style="--cookie-max-width: <?php echo e($maxWidth); ?>px;">
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


<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "WebSite",
  "url": "<?php echo e(url('/')); ?>",
  "potentialAction": {
    "@type": "AgreementAction",
    "actionStatus": "https://schema.org/PotentialActionStatus",
    "object": {
      "@type": "DigitalDocument",
      "name": "Cookie Policy",
      "url": "<?php echo e($learnMoreUrl ?: url('/')); ?>"
    }
  }
}
</script>
<?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>

<?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($enabled && $gaEnabled && $gaId) { ?>

<script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}

    gtag('consent', 'default', {
        ad_storage:             'denied',
        analytics_storage:      'denied',
        ad_user_data:           'denied',
        ad_personalization:     'denied',
        functionality_storage:  'denied',
        personalization_storage:'denied',
        security_storage:       'granted',
        wait_for_update:        500
    });

    gtag('set', 'ads_data_redaction', true);
    gtag('set', 'url_passthrough', true);

    window._cookieGaId = <?php echo Js::from($gaId); ?>;
</script>
<script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo e($gaId); ?>"></script>
<script>gtag('js', new Date()); gtag('config', window._cookieGaId, { send_page_view: false });</script>
<?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>

<?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($enabled && $fbPixelEnabled && $fbPixelId) { ?>
<script>window._cookieFbPixelId = <?php echo Js::from($fbPixelId); ?>;</script>
<?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>

<?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($enabled && $gtmEnabled && $gtmId) { ?>

<script>
window.dataLayer = window.dataLayer || [];
(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer',<?php echo Js::from($gtmId); ?>);
window._cookieGtmId = <?php echo Js::from($gtmId); ?>;
</script>
<?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>

<?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($enabled && $msUetEnabled && $msUetId) { ?>
<script>window._cookieMsUetId = <?php echo Js::from($msUetId); ?>;</script>
<?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>

<?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($enabled && $linkedinEnabled && $linkedinPartnerId) { ?>
<script>window._cookieLinkedInPartnerId = <?php echo Js::from($linkedinPartnerId); ?>;</script>
<?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>

<?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($enabled && $tiktokEnabled && $tiktokPixelId) { ?>
<script>window._cookieTikTokPixelId = <?php echo Js::from($tiktokPixelId); ?>;</script>
<?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>

<?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($enabled && $twitterEnabled && $twitterPixelId) { ?>
<script>window._cookieTwitterPixelId = <?php echo Js::from($twitterPixelId); ?>;</script>
<?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
<?php /**PATH /Users/developerts/Herd/system/modules/Cookie/resources/views/index.blade.php ENDPATH**/ ?>