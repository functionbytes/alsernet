{{-- reCAPTCHA v2 Script --}}
@if (!$isRendered)
<script type="text/javascript">
    var onloadCallback = function() {
        @if (!empty($name))
        grecaptcha.render('{{ $name }}', {
            'sitekey': '{{ setting('captcha_site_key') }}'
        });
        @endif
    };
</script>
<script src="{{ $url }}" async defer></script>
@endif
