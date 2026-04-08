@php
    $enabled        = cookie_option('enabled') === '1';
    $bgColor        = cookie_option('bg_color', '#ffffff');
    $textColor      = cookie_option('text_color', '#212529');
    $btnColor       = cookie_option('btn_color', '#90bb13');
    $position       = cookie_option('position', 'bottom');
    $style          = cookie_option('style', config('Cookie.general.style', 'full-width'));
    $styleClass     = 'cookie-consent-' . $style;
    $maxWidth       = cookie_option('max_width', config('Cookie.general.max_width', 1170));
    $message        = cookie_option('message', config('Cookie.general.message', 'We use cookies to improve your experience.'));
    $acceptText     = cookie_option('accept_btn_text', config('Cookie.general.button_text', 'Permitir cookies'));
    $rejectText     = cookie_option('reject_btn_text', config('Cookie.general.reject_text', 'Rechazar'));
    $customText     = cookie_option('customize_btn_text', config('Cookie.general.customize_text', 'Personalizar preferencias'));
    $saveText       = cookie_option('save_btn_text', config('Cookie.general.save_text', 'Guardar preferencias'));
    $learnMoreUrl   = cookie_option('more_info_url', config('Cookie.general.learn_more_url', ''));
    $learnMoreText  = cookie_option('more_info_text', config('Cookie.general.learn_more_text', ''));
    $categories     = config('Cookie.general.cookie_categories', []);
    $direction      = app()->getLocale() === 'ar' ? 'rtl' : 'ltr';
    $gaEnabled      = cookie_option('google_analytics_enabled') === '1';
    $gaId           = cookie_option('google_analytics_id');
    $fbPixelEnabled = cookie_option('facebook_pixel_enabled') === '1';
    $fbPixelId      = cookie_option('facebook_pixel_id');
    $cookieName     = config('Cookie.general.cookie_name', 'cookie_for_consent');
    $cookieLifetime = config('Cookie.general.cookie_lifetime', 7300);

    $positionClass = match($position) {
        'top'    => 'cookie-consent--position-top',
        'center' => 'cookie-consent--position-center',
        default  => '',
    };
@endphp

@if ($enabled)
<style>
.cookie-consent--visible { background-color: {{ $bgColor }}; }
.cookie-consent__agree, .cookie-consent__save-button {
    background-color: {{ $btnColor }};
    border-color: {{ $btnColor }};
    color: #fff;
}
</style>
<div id="cookie-banner"
     class="js-cookie-consent cookie-consent {{ $styleClass }} {{ $positionClass }} d-none"
     style="color: {{ $textColor }};"
     dir="{{ $direction }}"
     data-cookie-name="{{ $cookieName }}"
     data-cookie-lifetime="{{ $cookieLifetime }}"
     data-cookie-domain="{{ config('session.domain') ?? request()->getHost() }}"
     data-cookie-secure="{{ config('session.secure') ? '1' : '0' }}"
     role="dialog"
     aria-live="polite"
     aria-label="Aviso de cookies"
     aria-modal="false"
>
    <div class="cookie-consent-body" style="max-width: {{ $maxWidth }}px;">
        <div class="cookie-consent__inner">

            <div class="cookie-consent__message">
                {{ $message }}
                @if ($learnMoreUrl && $learnMoreText)
                    <a href="{{ $learnMoreUrl }}">{{ $learnMoreText }}</a>
                @endif
            </div>

            <div class="cookie-consent__actions">
                <button class="js-cookie-reject cookie-consent__reject">{{ $rejectText }}</button>

                @if (!empty($categories) && count($categories) > 1)
                    <button class="js-cookie-customize cookie-consent__customize"
                            data-bs-toggle="modal" data-bs-target="#cookie-preferences-modal">{{ $customText }}</button>
                @endif

                <button class="js-cookie-accept cookie-consent__agree">{{ $acceptText }}</button>
            </div>

            @if (!empty($categories))
                <div class="cookie-consent__categories">
                    @foreach ($categories as $key => $category)
                        <div class="cookie-category">
                            <label class="cookie-category__label">
                                <input type="checkbox" class="cookie-category-toggle" data-category="{{ $key }}" value="{{ $key }}"
                                    {{ !empty($category['required']) ? 'checked disabled' : 'checked' }}>
                                <span class="cookie-category__name">{{ $category['name'] }}</span>
                            </label>
                            <p class="cookie-category__description">{{ $category['description'] }}</p>
                        </div>
                    @endforeach

                    <div class="cookie-consent__save">
                        <button class="js-cookie-save-preferences cookie-consent__save-button">
                            {{ $saveText }}
                        </button>
                    </div>
                </div>
            @endif

        </div>
    </div>
</div>

@include('cookie::components.preferences-modal')
@endif

@if ($enabled && $gaEnabled && $gaId)
<!-- Google Analytics Global Site Tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id={{ $gaId }}"></script>
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

    window._cookieGaId = {!! \Illuminate\Support\Js::from($gaId) !!};
</script>
@endif

@if ($enabled && $fbPixelEnabled && $fbPixelId)
<script>window._cookieFbPixelId = {!! \Illuminate\Support\Js::from($fbPixelId) !!};</script>
@endif
