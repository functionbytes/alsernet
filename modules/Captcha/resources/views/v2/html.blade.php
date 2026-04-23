<div
    class="g-recaptcha"
    id="{{ $name }}"
    data-sitekey="{{ $siteKey }}"
    @isset($attributes['aria-label'])
        aria-label="{{ $attributes['aria-label'] }}"
    @else
        aria-label="{{ trans('captcha::captcha.settings.security_verification') }}"
    @endisset
    role="img"
></div>
