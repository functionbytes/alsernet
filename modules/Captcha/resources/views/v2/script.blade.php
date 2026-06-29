@if (! $isRendered || request()->ajax())
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
                script.src = '{{ $url }}';
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
@endif

<script>
    window.recaptchaInputs.push('{{ $name }}');
</script>
