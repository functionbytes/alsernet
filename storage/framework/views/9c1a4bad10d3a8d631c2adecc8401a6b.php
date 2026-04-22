<?php
use Livewire\Mechanisms\ExtendBlade\ExtendBlade;

if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (! $isRendered || request()->ajax()) { ?>
    <script
        src="<?php echo e($url); ?>"
        async
        defer
    ></script>

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
    </script>
<?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>

<script>
    window.recaptchaInputs.push('<?php echo e($name); ?>');
</script>
<?php /**PATH /Users/developerts/Herd/system/modules/Captcha/resources/views/v2/script.blade.php ENDPATH**/ ?>