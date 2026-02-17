@php
    $style = config('Cookie.general.style', 'full-width');
    $bgColor = config('Cookie.general.background_color', '#000000');
    $textColor = config('Cookie.general.text_color', '#ffffff');
    $maxWidth = config('Cookie.general.max_width', 1170);
    $message = config('Cookie.general.message');
    $buttonText = config('Cookie.general.button_text');
    $learnMoreUrl = config('Cookie.general.learn_more_url');
    $learnMoreText = config('Cookie.general.learn_more_text');
    $showRejectButton = config('Cookie.general.show_reject_button', false);
    $showCustomizeButton = config('Cookie.general.show_customize_button', false);
    $cookieCategories = config('Cookie.general.cookie_categories', []);

    // Allow theme options to override if available
    if (function_exists('theme_option')) {
        $style = theme_option('cookie_consent_style', $style);
        $bgColor = theme_option('cookie_consent_background_color', $bgColor);
        $textColor = theme_option('cookie_consent_text_color', $textColor);
        $maxWidth = theme_option('cookie_consent_max_width', $maxWidth);
        $message = theme_option('cookie_consent_message', $message);
        $buttonText = theme_option('cookie_consent_button_text', $buttonText);
        $learnMoreUrl = theme_option('cookie_consent_learn_more_url', $learnMoreUrl);
        $learnMoreText = theme_option('cookie_consent_learn_more_text', $learnMoreText);
        $showRejectButton = theme_option('cookie_consent_show_reject_button', 'no') === 'yes';
        $showCustomizeButton = theme_option('cookie_consent_show_customize_button', 'no') === 'yes';
    }

    $direction = app()->getLocale() === 'ar' ? 'rtl' : 'ltr';
@endphp

<div
    class="js-cookie-consent cookie-consent cookie-consent-{{ $style }}"
    style="background-color: {{ $bgColor }}; color: {{ $textColor }};"
    dir="{{ $direction }}"
>
    <div
        class="cookie-consent-body"
        style="max-width: {{ $maxWidth }}px;"
    >
        <div class="cookie-consent__inner">
            <div class="cookie-consent__message">
                {!! $message !!}
                @if ($learnMoreUrl && $learnMoreText)
                    <a href="{{ $learnMoreUrl }}">{{ $learnMoreText }}</a>
                @endif
            </div>

            <div class="cookie-consent__actions">
                @if ($showRejectButton)
                    <button
                        class="js-cookie-consent-reject cookie-consent__reject"
                        style="background-color: {{ $textColor }}; color: {{ $bgColor }}; border: 1px solid {{ $textColor }};"
                    >
                        {{ trans('Cookie::cookie-consent.reject_text') }}
                    </button>
                @endif

                @if (!empty($cookieCategories) && $showCustomizeButton)
                    <button
                        class="js-cookie-consent-customize cookie-consent__customize"
                        style="background-color: {{ $textColor }}; color: {{ $bgColor }}; border: 1px solid {{ $textColor }};"
                    >
                        {{ trans('Cookie::cookie-consent.customize_text') }}
                    </button>
                @endif

                <button
                    class="js-cookie-consent-agree cookie-consent__agree"
                    style="background-color: {{ $bgColor }}; color: {{ $textColor }}; border: 1px solid {{ $textColor }};"
                >
                    {{ $buttonText }}
                </button>
            </div>
        </div>

        @if (!empty($cookieCategories))
            <div class="cookie-consent__categories">
                @foreach ($cookieCategories as $key => $category)
                    <div class="cookie-category">
                        <label class="cookie-category__label">
                            <input
                                type="checkbox"
                                name="cookie_category[]"
                                value="{{ $key }}"
                                class="js-cookie-category"
                                @if ($category['required'] ?? false) checked disabled @endif
                            >
                            <span class="cookie-category__name">
                                {{ trans('Cookie::cookie-consent.cookie_categories.' . $key . '.name') }}
                            </span>
                        </label>
                        <p class="cookie-category__description">
                            {{ trans('Cookie::cookie-consent.cookie_categories.' . $key . '.description') }}
                        </p>
                    </div>
                @endforeach

                <div class="cookie-consent__save">
                    <button
                        class="js-cookie-consent-save cookie-consent__save-button"
                        style="background-color: {{ $bgColor }}; color: {{ $textColor }}; border: 1px solid {{ $textColor }};"
                    >
                        {{ trans('Cookie::cookie-consent.save_text') }}
                    </button>
                </div>
            </div>
        @endif
    </div>
</div>

<div data-site-cookie-name="{{ $CookieConfig['cookie_name'] ?? 'cookie_for_consent' }}"></div>
<div data-site-cookie-lifetime="{{ $CookieConfig['cookie_lifetime'] ?? 36000 }}"></div>
<div data-site-cookie-domain="{{ config('session.domain') ?? request()->getHost() }}"></div>
<div data-site-session-secure="{{ config('session.secure') ? ';secure' : '' }}"></div>

@if (config('Cookie.general.google_analytics.enabled'))
<script>
    window.addEventListener('load', function () {
        if (typeof gtag !== 'undefined') {
            gtag('consent', 'default', {
                'ad_storage': 'denied',
                'analytics_storage': 'denied'
            });

            document.addEventListener('click', function(event) {
                if (event.target.classList.contains('js-cookie-consent-agree')) {
                    const categories = document.querySelectorAll('.js-cookie-category:checked');
                    const consents = {
                        'ad_storage': 'denied',
                        'analytics_storage': 'denied'
                    };

                    categories.forEach(function(category) {
                        if (category.value === 'marketing') {
                            consents.ad_storage = 'granted';
                        }
                        if (category.value === 'analytics') {
                            consents.analytics_storage = 'granted';
                        }
                    });

                    gtag('consent', 'update', consents);
                }
            });
        }
    });
</script>
@endif
