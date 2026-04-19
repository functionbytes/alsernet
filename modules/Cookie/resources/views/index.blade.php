@php
    $geoEnabled = cookie_option('geo_targeting_enabled') === '1';
    $allowedRegion = true;

    if ($geoEnabled && function_exists('geoip')) {
        try {
            $geo = @geoip(request()->ip());
            $euCountries = ['AT','BE','BG','HR','CY','CZ','DK','EE','FI','FR','DE','GR','HU','IE','IT','LV','LT','LU','MT','NL','PL','PT','RO','SK','SI','ES','SE','GB','IS','LI','NO'];
            $allowedRegion = in_array($geo->iso_code ?? '', $euCountries);
        } catch (\Throwable $e) {
            $allowedRegion = true;
        }
    }

    $enabled        = cookie_option('enabled') === '1';
    $bgColor        = cookie_option('bg_color', '#ffffff');
    $textColor      = cookie_option('text_color', '#212529');
    $btnColor       = cookie_option('btn_color', '#90bb13');
    $position       = cookie_option('position', 'bottom');
    $style          = cookie_option('style', config('Cookie.general.style', 'full-width'));
    $styleClass     = 'cookie-consent-' . $style;
    $maxWidth       = cookie_option('max_width', config('Cookie.general.max_width', 1170));
    $message        = cookie_option('message',          __('cookie::messages.banner.message'));
    $acceptText     = cookie_option('accept_btn_text',  __('cookie::messages.banner.accept'));
    $rejectText     = cookie_option('reject_btn_text',  __('cookie::messages.banner.reject'));
    $customText     = cookie_option('customize_btn_text', __('cookie::messages.banner.customize'));
    $saveText       = cookie_option('save_btn_text',    __('cookie::messages.modal.save'));
    $learnMoreUrl   = cookie_option('more_info_url',    config('Cookie.general.learn_more_url', ''));
    $learnMoreText  = cookie_option('more_info_text',   __('cookie::messages.banner.learn_more'));
    $categories     = config('Cookie.general.cookie_categories', []);
    $direction      = app()->getLocale() === 'ar' ? 'rtl' : 'ltr';
    $gaEnabled      = cookie_option('google_analytics_enabled') === '1';
    $gaId           = cookie_option('google_analytics_id');
    $fbPixelEnabled = cookie_option('facebook_pixel_enabled') === '1';
    $fbPixelId      = cookie_option('facebook_pixel_id');
    $gtmEnabled          = cookie_option('google_tag_manager_enabled') === '1';
    $gtmId               = cookie_option('google_tag_manager_id');
    $linkedinEnabled     = cookie_option('linkedin_insight_enabled') === '1';
    $linkedinPartnerId   = cookie_option('linkedin_partner_id');
    $tiktokEnabled       = cookie_option('tiktok_pixel_enabled') === '1';
    $tiktokPixelId       = cookie_option('tiktok_pixel_id');
    $twitterEnabled      = cookie_option('twitter_pixel_enabled') === '1';
    $twitterPixelId      = cookie_option('twitter_pixel_id');
    $msUetEnabled        = cookie_option('microsoft_uet_enabled') === '1';
    $msUetId             = cookie_option('microsoft_uet_id');
    $cookieName     = config('Cookie.general.cookie_name', 'cookie_for_consent');
    $cookieLifetime = config('Cookie.general.cookie_lifetime', 7300);
    $consentVersion = cookie_option('consent_version', '1.0');

    $positionClass = match($position) {
        'top'    => 'cookie-consent--position-top',
        'center' => 'cookie-consent--position-center',
        default  => '',
    };
@endphp

@if ($enabled && $allowedRegion)
<div id="cookie-banner"
     class="js-cookie-consent cookie-consent {{ $styleClass }} {{ $positionClass }} d-none"
     style="--cookie-bg: {{ $bgColor }}; --cookie-text: {{ $textColor }}; --cookie-btn: {{ $btnColor }};"
     dir="{{ $direction }}"
     data-cookie-name="{{ $cookieName }}"
     data-cookie-lifetime="{{ $cookieLifetime }}"
     data-consent-version="{{ $consentVersion }}"
     data-cookie-domain="{{ config('session.domain') ?? request()->getHost() }}"
     data-cookie-secure="{{ config('session.secure') ? '1' : '0' }}"
     role="dialog"
     aria-live="polite"
     aria-label="Aviso de cookies"
     aria-modal="false"
>
    <div class="cookie-consent-body" style="--cookie-max-width: {{ $maxWidth }}px;">
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

{{-- JSON-LD: indica a Google que la página usa consentimiento de cookies (GDPR) --}}
<script type="application/ld+json">
{
  "@@context": "https://schema.org",
  "@@type": "WebSite",
  "url": "{{ url('/') }}",
  "potentialAction": {
    "@@type": "AgreementAction",
    "actionStatus": "https://schema.org/PotentialActionStatus",
    "object": {
      "@@type": "DigitalDocument",
      "name": "Cookie Policy",
      "url": "{{ $learnMoreUrl ?: url('/') }}"
    }
  }
}
</script>
@endif

@if ($enabled && $gaEnabled && $gaId)
{{-- Google Consent Mode v2 — must run BEFORE gtag.js loads --}}
<script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}

    gtag('consent', 'default', {
        ad_storage:             'denied',
        analytics_storage:      'denied',
        ad_user_data:           'denied',
        ad_personalization:     'denied',
        functionality_storage:  'denied',
        personalization_storage:'denied',
        security_storage:       'granted',
        wait_for_update:        500
    });

    gtag('set', 'ads_data_redaction', true);
    gtag('set', 'url_passthrough', true);

    window._cookieGaId = {!! \Illuminate\Support\Js::from($gaId) !!};
</script>
<script async src="https://www.googletagmanager.com/gtag/js?id={{ $gaId }}"></script>
<script>gtag('js', new Date()); gtag('config', window._cookieGaId, { send_page_view: false });</script>
@endif

@if ($enabled && $fbPixelEnabled && $fbPixelId)
<script>window._cookieFbPixelId = {!! \Illuminate\Support\Js::from($fbPixelId) !!};</script>
@endif

@if ($enabled && $gtmEnabled && $gtmId)
{{-- GTM: consent-aware, se carga en <head> via dataLayer --}}
<script>
window.dataLayer = window.dataLayer || [];
(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer',{!! \Illuminate\Support\Js::from($gtmId) !!});
window._cookieGtmId = {!! \Illuminate\Support\Js::from($gtmId) !!};
</script>
@endif

@if ($enabled && $msUetEnabled && $msUetId)
<script>window._cookieMsUetId = {!! \Illuminate\Support\Js::from($msUetId) !!};</script>
@endif

@if ($enabled && $linkedinEnabled && $linkedinPartnerId)
<script>window._cookieLinkedInPartnerId = {!! \Illuminate\Support\Js::from($linkedinPartnerId) !!};</script>
@endif

@if ($enabled && $tiktokEnabled && $tiktokPixelId)
<script>window._cookieTikTokPixelId = {!! \Illuminate\Support\Js::from($tiktokPixelId) !!};</script>
@endif

@if ($enabled && $twitterEnabled && $twitterPixelId)
<script>window._cookieTwitterPixelId = {!! \Illuminate\Support\Js::from($twitterPixelId) !!};</script>
@endif
