@if (! $isRendered || request()->ajax())
    <script
        src="{{ $url }}"
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
@endif

<script>
    window.recaptchaInputs.push('{{ $name }}');
</script>
