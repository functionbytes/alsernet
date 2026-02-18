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
    $cookieLifetime = config('Cookie.general.cookie_lifetime', 36000);

    $positionStyle = match($position) {
        'top'    => 'top:0; bottom:auto; transform:translateY(-100%);',
        'center' => 'top:50%; bottom:auto; transform:translateY(-50%); left:50%; right:auto; transform:translate(-50%,-50%);',
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
<div class="js-cookie-consent cookie-consent {{ $styleClass }}"
     style="color: {{ $textColor }}; {{ $positionStyle }}"
     dir="{{ $direction }}"
     data-cookie-name="{{ $cookieName }}"
     data-cookie-lifetime="{{ $cookieLifetime }}"
     data-cookie-domain="{{ config('session.domain') ?? request()->getHost() }}"
     data-session-secure="{{ config('session.secure') ? ';secure' : '' }}"
>
    <div class="cookie-consent-body" style="max-width: {{ $maxWidth }}px;">
        <div class="cookie-consent__inner">

            <div class="cookie-consent__message">
                {!! $message !!}
                @if ($learnMoreUrl && $learnMoreText)
                    <a href="{{ $learnMoreUrl }}">{{ $learnMoreText }}</a>
                @endif
            </div>

            <div class="cookie-consent__actions">
                <button class="js-cookie-consent-reject cookie-consent__reject">{{ $rejectText }}</button>

                @if (!empty($categories) && count($categories) > 1)
                    <button class="js-cookie-consent-customize cookie-consent__customize">{{ $customText }}</button>
                @endif

                <button class="js-cookie-consent-agree cookie-consent__agree">{{ $acceptText }}</button>
            </div>

            @if (!empty($categories))
                <div class="cookie-consent__categories">
                    @foreach ($categories as $key => $category)
                        <div class="cookie-category">
                            <label class="cookie-category__label">
                                <input type="checkbox" class="js-cookie-category" value="{{ $key }}"
                                    {{ !empty($category['required']) ? 'checked disabled' : 'checked' }}>
                                <span class="cookie-category__name">{{ $category['name'] }}</span>
                            </label>
                            <p class="cookie-category__description">{{ $category['description'] }}</p>
                        </div>
                    @endforeach

                    <div class="cookie-consent__save">
                        <button class="js-cookie-consent-save cookie-consent__save-button">
                            {{ $saveText }}
                        </button>
                    </div>
                </div>
            @endif

        </div>
    </div>
</div>
@endif

@if ($gaEnabled && $gaId)
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

    gtag('config', '{{ $gaId }}');
</script>
@endif

@if ($fbPixelEnabled && $fbPixelId)
<!-- Facebook Pixel Code -->
<script>
    !function(f,b,e,v,n,t,s)
    {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
    n.callMethod.apply(n,arguments):n.queue.push(arguments)};
    if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
    n.queue=[];t=b.createElement(e);t.async=!0;
    t.src=v;s=b.getElementsByTagName(e)[0];
    s.parentNode.insertBefore(t,s)}(window, document,'script',
    'https://connect.facebook.net/en_US/fbevents.js');
    fbq('init', '{{ $fbPixelId }}');
    fbq('track', 'PageView');
</script>
@endif
