<?php
use Livewire\Mechanisms\ExtendBlade\ExtendBlade;

if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (! $isRendered || request()->ajax()) { ?>
    <script>
        'use strict';

        window.recaptchaInputs = window.recaptchaInputs || [];

        var refreshRecaptcha = function() {
            window.recaptchaInputs.forEach(function(item, index) {
                grecaptcha.reset(index);
            });
        };

        var onloadCallback = function() {
            window.recaptchaInputs.forEach(function(item) {
                var el = document.getElementById(item);
                if (!el) { return; }
                try {
                    grecaptcha.render(item);
                } catch (e) {
                    // Already rendered — safe to ignore in preview/refresh contexts
                }
            });
        };

        (function() {
            var loaded = false;
            function loadRecaptcha() {
                if (loaded) return;
                loaded = true;
                var script = document.createElement('script');
                script.src = '<?php echo e($url); ?>';
                script.async = true;
                script.defer = true;
                document.head.appendChild(script);
            }
            document.addEventListener('focusin', loadRecaptcha, {once: true});
            document.addEventListener('scroll', loadRecaptcha, {once: true});
            document.addEventListener('mousemove', loadRecaptcha, {once: true});
            document.addEventListener('touchstart', loadRecaptcha, {once: true});
        })();
    </script>
<?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>

<script>
    window.recaptchaInputs.push('<?php echo e($name); ?>');
</script>
<?php /**PATH /Users/developerts/Herd/system/modules/Captcha/resources/views/v2/script.blade.php ENDPATH**/ ?>