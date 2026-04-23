
<?php
use Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys;
use Livewire\Mechanisms\ExtendBlade\ExtendBlade;
use Modules\Captcha\Facades\Captcha;
use Modules\Core\Models\Setting;

echo dynamic_sidebar('top_footer_sidebar'); ?>

<footer class="main-footer">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <!-- Footer Header Start -->
                <div class="footer-header">
                    <div class="row align-items-center">
                        <div class="col-lg-6 col-md-8">
                            <div class="footer-header-title">
                                <!-- Section Title Start -->
                                <div class="section-title dark-section">
                                    <h2 class="text-anime-style-3" data-cursor="-opaque"><?php echo e(__('footer_cta')); ?></h2>
                                </div>
                                <!-- Section Title End -->
                            </div>
                        </div>

                        <div class="col-lg-6 col-md-4">
                            <!-- Footer Contact Button Start -->
                            <div class="footer-contact-btn">
                                <a href="<?php echo e(url('/contacto')); ?>" class="btn-default btn-highlighted"><?php echo e(__('footer_contact_us_today')); ?></a>
                            </div>
                            <!-- Footer Contact Button End -->
                        </div>
                    </div>
                </div>
                <!-- Footer Header End -->
            </div>

            <div class="col-lg-3 col-md-6">
                <!-- About Footer Start -->
                <div class="about-footer">
                    <!-- Footer Logo Start -->

                    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (theme_option('logo_light')) { ?>
                        <div class="footer-logo">
                            <img src="<?php echo e(RvMedia::getImageUrl(theme_option('logo_light'))); ?>" alt="<?php echo e(theme_option('site_title')); ?>" width="242" height="76">
                        </div>
                    <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>

                    <div class="about-footer-content">
                        <p><?php echo e(__('footer_description')); ?></p>
                    </div>

                    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (($socialLinks = theme_option('social_links')) && ($socialLinks = json_decode($socialLinks, true))) { ?>
                        <div class="footer-social-links">
                            <ul>
                                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__currentLoopData = $socialLinks;
                        $__env->addLoop($__currentLoopData);
                        foreach ($__currentLoopData as $link) {
                            $__env->incrementLoopIndices();
                            $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
                                    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (! empty($link['url']) && ! empty($link['icon'])) { ?>
                                        <li>
                                            <a href="<?php echo e($link['url']); ?>" title="<?php echo e($link['name'] ?? ''); ?>" target="_blank" rel="noopener">
                                                <?php echo BaseHelper::renderIcon($link['icon']); ?>

                                            </a>
                                        </li>
                                    <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } $__env->popLoop();
                        $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>
                            </ul>
                        </div>
                    <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>

                </div>
                <!-- About Footer End -->
            </div>

            <div class="col-lg-3 col-md-6">

                <!-- Footer Services Menu Start -->
                <div class="footer-links">
                    <h3><?php echo e(__('footer_services')); ?></h3>
                    <?php echo Menu::renderMenuLocation('footer-services', ['view' => 'footer-menu']); ?>

                </div>
                <!-- Footer Services Menu End -->
            </div>

            <div class="col-lg-3 col-md-6">
                <!-- Footer Links Menu Start -->
                <div class="footer-links">
                    <h3><?php echo e(__('footer_quick_links')); ?></h3>
                    <?php echo Menu::renderMenuLocation('footer-links', ['view' => 'footer-menu']); ?>

                </div>
                <!-- Footer Links Menu End -->
            </div>

            <div class="col-lg-3 col-md-6">
                <!-- Footer Contact Box Start -->
                <div class="footer-contact-box footer-links">
                    <h3><?php echo e(__('footer_contact_information')); ?></h3>

                    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (theme_option('email')) { ?>
                        <div class="footer-contact-item">
                            <div class="icon-box">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div class="footer-contact-content">
                                <p><a href="mailto:<?php echo e(Theme::getSiteEmail()); ?>" class="color-inherit"><?php echo e(Theme::getSiteEmail()); ?></a></p>
                            </div>
                        </div>
                    <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>

                    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (theme_option('phone')) { ?>
                        <div class="footer-contact-item">
                            <div class="icon-box">
                                <i class="fas fa-phone"></i>
                            </div>
                            <div class="footer-contact-content">
                                <p><a href="tel:<?php echo e(Theme::getSiteCellphone()); ?>" class="color-inherit"><?php echo e(Theme::getSiteCellphone()); ?></a></p>
                            </div>
                        </div>
                    <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>

                    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (theme_option('address')) { ?>
                        <div class="footer-contact-item">
                            <div class="icon-box">
                                <i class="fas fa-location-dot"></i>
                            </div>
                            <div class="footer-contact-content">
                                <p><?php echo Theme::getSiteAddress(); ?></p>
                            </div>
                        </div>
                    <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>

                    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (theme_option('opening_hours')) { ?>
                        <div class="footer-contact-item">
                            <div class="icon-box">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="footer-contact-content">
                                <p><?php echo Theme::getSiteOpen(); ?></p>
                            </div>
                        </div>
                    <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>

                </div>

            </div>

        </div>

        <!-- Footer Copyright Section Start -->
        <div class="footer-copyright">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <div class="footer-copyright-text">
                        <p><?php echo Theme::getSiteCopyright(); ?></p>
                    </div>
                </div>
                <div class="col-lg-4 text-lg-end mt-2 mt-lg-0">
                    <a href="<?php echo e(url('cookie/policy')); ?>" class="text-white small me-3">Política de cookies</a>
                    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (Setting::get('cookie.enabled') === '1') { ?>
                        <button type="button" class="btn btn-link text-white small p-0 border-0 text-decoration-none"
                                data-bs-toggle="modal" data-bs-target="#cookie-preferences-modal">
                            Gestionar cookies
                        </button>
                    <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                </div>
            </div>
        </div>
        <!-- Footer Copyright Section End -->
    </div>
</footer>



<?php echo Theme::footer(); ?>


<?php echo Theme::asset()->container('footer')->scripts(); ?>


<script>
    window.trans = {
        "Views": "<?php echo e(__('Views')); ?>",
        "Read more": "<?php echo e(__('Read more')); ?>",
        "days": "<?php echo e(__('days')); ?>",
        "hours": "<?php echo e(__('hours')); ?>",
        "mins": "<?php echo e(__('mins')); ?>",
        "sec": "<?php echo e(__('sec')); ?>",
        "No reviews!": "<?php echo e(__('No reviews!')); ?>"
    };

    window.trackedStartCheckout = '<?php echo e(session('tracked_start_checkout')); ?>';
    window.siteUrl = '<?php echo e(url('/')); ?>';
</script>

<?php echo Theme::place('footer'); ?>


<?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (session()->has('success_msg') || session()->has('error_msg') || (isset($errors) && $errors->count() > 0) || isset($error_msg)) { ?>
    <script type="text/javascript">
        window.onload = function () {
            <?php if (session()->has('success_msg')) { ?>
            window.showAlert('alert-success', '<?php echo e(session('success_msg')); ?>');
            <?php } ?>

            <?php if (session()->has('error_msg')) { ?>
            window.showAlert('alert-danger', '<?php echo e(session('error_msg')); ?>');
            <?php } ?>

            <?php if (isset($error_msg)) { ?>
            window.showAlert('alert-danger', '<?php echo e($error_msg); ?>');
            <?php } ?>

            <?php if (isset($errors)) { ?>
            <?php $__currentLoopData = $errors->all();
                $__env->addLoop($__currentLoopData);
                foreach ($__currentLoopData as $error) {
                    $__env->incrementLoopIndices();
                    $loop = $__env->getLastLoop(); ?>
            window.showAlert('alert-danger', '<?php echo BaseHelper::clean($error); ?>');
            <?php } $__env->popLoop();
                $loop = $__env->getLastLoop(); ?>
            <?php } ?>
        };
    </script>
<?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>

<!-- Progress Scroll To Top -->
<div class="progress-wrap cursor-inner" id="progress-wrap">
    <svg class="progress-circle svg-content" width="100%" height="100%" viewBox="-1 -1 102 102">
        <path d="M50,1 a49,49 0 0,1 0,98 a49,49 0 0,1 0,-98"/>
    </svg>
</div>

<?php echo $__env->yieldPushContent('scripts'); ?>


<script>
(function () {
    function patchCaptchaButtons() {
        (window.recaptchaInputs || []).forEach(function (id) {
            var el = document.getElementById(id);
            if (!el || el.dataset.rcPatched) { return; }
            el.dataset.rcPatched = '1';

            var form = el.closest('form');
            if (!form) { return; }

            var btn = form.querySelector('button[type="submit"]');
            if (!btn) { return; }

            btn.disabled = true;

            var enableKey  = 'rcEnable_'  + id.replace(/[^a-z0-9]/gi, '_');
            var expiredKey = 'rcExpired_' + id.replace(/[^a-z0-9]/gi, '_');

            window[enableKey]  = function () { btn.disabled = false; };
            window[expiredKey] = function () { btn.disabled = true; };

            el.setAttribute('data-callback', enableKey);
            el.setAttribute('data-expired-callback', expiredKey);
        });
    }

    // Runs synchronously before the async reCAPTCHA API fires onloadCallback
    patchCaptchaButtons();
}());
</script>

<link rel="stylesheet" href="<?php echo e(url('modules/Newsletter/css/newsletter.css')); ?>">
<?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (Setting::get('newsletter.popup_enabled') === '1') { ?>
    <div id="newsletter-popup" class="modal fade newsletter-popup" tabindex="-1"
         data-delay="<?php echo e(Setting::get('newsletter.popup_delay', '5')); ?>"
         data-url="<?php echo e(route('newsletter.popup')); ?>">
    </div>
<?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
<?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (Setting::get('newsletter.recaptcha_enabled') === '1' && Captcha::isEnabled()) { ?>
    <?php echo $__env->make('captcha::header-meta', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <script>
        window.recaptchaInputs = window.recaptchaInputs || [];
        var refreshRecaptcha = function() {
            window.recaptchaInputs.forEach(function(item, index) {
                grecaptcha.reset(index);
            });
        };
        var onloadCallback = function() {
            window.recaptchaInputs.forEach(function(id) {
                var el = document.getElementById(id);
                if (el) grecaptcha.render(id, { sitekey: el.getAttribute('data-sitekey') });
            });
        };
    </script>
    <script src="https://www.google.com/recaptcha/api.js?onload=onloadCallback&render=explicit&hl=<?php echo e(app()->getLocale()); ?>" async defer></script>
<?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
<script src="<?php echo e(url('modules/Newsletter/js/newsletter.js')); ?>"></script>
<script src="<?php echo e(url('modules/Reviews/js/reviews-cards.js')); ?>"></script>

<?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (Setting::get('cookie.enabled') === '1') { ?>
    <?php echo $__env->make('cookie::index', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <link rel="stylesheet" href="<?php echo e(url('modules/Cookie/css/cookie-consent.css')); ?>">
    <script src="<?php echo e(url('modules/Cookie/js/cookie-consent.js')); ?>"></script>
<?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>

<?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (theme_option('cta_floating_enabled', '1') === '1') { ?>
    <?php echo Theme::partial('shortcodes.cta-floating'); ?>

<?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
</body>
</html>
<?php /**PATH /Users/developerts/Herd/system/platform/themes/caixilhariablanco/partials/footer.blade.php ENDPATH**/ ?>