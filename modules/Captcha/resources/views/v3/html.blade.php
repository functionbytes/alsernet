<input
    id="{{ $uniqueId }}"
    name="{{ $name }}"
    type="hidden"
    aria-hidden="true"
>

@if (setting('captcha_show_disclaimer'))
    <div class="captcha-disclaimer" style="display: block; background-color: rgb(232 233 235); border-radius: 4px; padding: 16px; margin-bottom: 16px; " role="note" aria-label="{{ trans('captcha::captcha.settings.recaptcha_disclaimer') }}">
        {!! __('This site is protected by reCAPTCHA and the Google :privacyLink and :termsLink apply.', [
            'privacyLink' => '<a href="https://www.google.com/intl/en/policies/privacy/" target="_blank">' . __('Privacy Policy') . '</a>',
            'termsLink' => '<a href="https://www.google.com/intl/en/policies/terms/" target="_blank">' . __('Terms of Service') . '</a>',
        ]) !!}
    </div>

    <style>
        body[data-bs-theme="dark"] .captcha-disclaimer {
            background-color: transparent !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
        }
    </style>
@endif
