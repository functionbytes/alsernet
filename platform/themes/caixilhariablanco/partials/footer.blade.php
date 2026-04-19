
{!! dynamic_sidebar('top_footer_sidebar') !!}
<footer class="main-footer">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <!-- Footer Header Start -->
                <div class="footer-header">
                    <div class="row align-items-center">
                        <div class="col-lg-6 col-md-8">
                            <div class="footer-header-title">
                                <!-- Section Title Start -->
                                <div class="section-title dark-section">
                                    <h2 class="text-anime-style-3" data-cursor="-opaque">{{ __('footer_cta') }}</h2>
                                </div>
                                <!-- Section Title End -->
                            </div>
                        </div>

                        <div class="col-lg-6 col-md-4">
                            <!-- Footer Contact Button Start -->
                            <div class="footer-contact-btn">
                                <a href="{{ url('/contacto') }}" class="btn-default btn-highlighted">{{ __('footer_contact_us_today') }}</a>
                            </div>
                            <!-- Footer Contact Button End -->
                        </div>
                    </div>
                </div>
                <!-- Footer Header End -->
            </div>

            <div class="col-lg-3 col-md-6">
                <!-- About Footer Start -->
                <div class="about-footer">
                    <!-- Footer Logo Start -->

                    @if (theme_option('logo_light'))
                        <div class="footer-logo">
                            <img src="{{ RvMedia::getImageUrl(theme_option('logo_light')) }}" alt="{{ theme_option('site_title') }}">
                        </div>
                    @endif

                    <div class="about-footer-content">
                        <p>{{ __('footer_description') }}</p>
                    </div>

                    @if (($socialLinks = theme_option('social_links')) && ($socialLinks = json_decode($socialLinks, true)))
                        <div class="footer-social-links">
                            <ul>
                                @foreach ($socialLinks as $link)
                                    @if (!empty($link['url']) && !empty($link['icon']))
                                        <li>
                                            <a href="{{ $link['url'] }}" title="{{ $link['name'] ?? '' }}" target="_blank" rel="noopener">
                                                {!! BaseHelper::renderIcon($link['icon']) !!}
                                            </a>
                                        </li>
                                    @endif
                                @endforeach
                            </ul>
                        </div>
                    @endif

                </div>
                <!-- About Footer End -->
            </div>

            <div class="col-lg-3 col-md-6">

                <!-- Footer Services Menu Start -->
                <div class="footer-links">
                    <h3>{{ __('footer_services') }}</h3>
                    {!! Menu::renderMenuLocation('footer-services', ['view' => 'footer-menu']) !!}
                </div>
                <!-- Footer Services Menu End -->
            </div>

            <div class="col-lg-3 col-md-6">
                <!-- Footer Links Menu Start -->
                <div class="footer-links">
                    <h3>{{ __('footer_quick_links') }}</h3>
                    {!! Menu::renderMenuLocation('footer-links', ['view' => 'footer-menu']) !!}
                </div>
                <!-- Footer Links Menu End -->
            </div>

            <div class="col-lg-3 col-md-6">
                <!-- Footer Contact Box Start -->
                <div class="footer-contact-box footer-links">
                    <h3>{{ __('footer_contact_information') }}</h3>

                    @if (theme_option('email'))
                        <div class="footer-contact-item">
                            <div class="icon-box">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div class="footer-contact-content">
                                <p><a href="mailto:{{ Theme::getSiteEmail() }}" style="color:inherit;">{{ Theme::getSiteEmail() }}</a></p>
                            </div>
                        </div>
                    @endif

                    @if (theme_option('phone'))
                        <div class="footer-contact-item">
                            <div class="icon-box">
                                <i class="fas fa-phone"></i>
                            </div>
                            <div class="footer-contact-content">
                                <p><a href="tel:{{ Theme::getSiteCellphone() }}" style="color:inherit;">{{ Theme::getSiteCellphone() }}</a></p>
                            </div>
                        </div>
                    @endif

                    @if (theme_option('address'))
                        <div class="footer-contact-item">
                            <div class="icon-box">
                                <i class="fas fa-location-dot"></i>
                            </div>
                            <div class="footer-contact-content">
                                <p>{!! Theme::getSiteAddress() !!}</p>
                            </div>
                        </div>
                    @endif

                    @if (theme_option('opening_hours'))
                        <div class="footer-contact-item">
                            <div class="icon-box">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="footer-contact-content">
                                <p>{!! Theme::getSiteOpen() !!}</p>
                            </div>
                        </div>
                    @endif

                </div>

            </div>

        </div>

        <!-- Footer Copyright Section Start -->
        <div class="footer-copyright">
            <div class="row align-items-center">
                <div class="col-lg-12">
                    <!-- Footer Copyright Start -->
                    <div class="footer-copyright-text">
                        <p>{!! Theme::getSiteCopyright() !!}</p>
                    </div>
                    <!-- Footer Copyright End -->
                </div>
            </div>
        </div>
        <!-- Footer Copyright Section End -->
    </div>
</footer>



{!! Theme::footer() !!}

{!! Theme::asset()->container('footer')->scripts() !!}

<script>
    window.trans = {
        "Views": "{{ __('Views') }}",
        "Read more": "{{ __('Read more') }}",
        "days": "{{ __('days') }}",
        "hours": "{{ __('hours') }}",
        "mins": "{{ __('mins') }}",
        "sec": "{{ __('sec') }}",
        "No reviews!": "{{ __('No reviews!') }}"
    };

    window.trackedStartCheckout = '{{ session("tracked_start_checkout") }}';
    window.siteUrl = '{{ url("/") }}';
</script>

{!! Theme::place('footer') !!}

@if (session()->has('success_msg') || session()->has('error_msg') || (isset($errors) && $errors->count() > 0) || isset($error_msg))
    <script type="text/javascript">
        window.onload = function () {
            @if (session()->has('success_msg'))
            window.showAlert('alert-success', '{{ session('success_msg') }}');
            @endif

            @if (session()->has('error_msg'))
            window.showAlert('alert-danger', '{{ session('error_msg') }}');
            @endif

            @if (isset($error_msg))
            window.showAlert('alert-danger', '{{ $error_msg }}');
            @endif

            @if (isset($errors))
            @foreach ($errors->all() as $error)
            window.showAlert('alert-danger', '{!! BaseHelper::clean($error) !!}');
            @endforeach
            @endif
        };
    </script>
@endif

<!-- Progress Scroll To Top -->
<div class="progress-wrap cursor-inner" id="progress-wrap">
    <svg class="progress-circle svg-content" width="100%" height="100%" viewBox="-1 -1 102 102">
        <path d="M50,1 a49,49 0 0,1 0,98 a49,49 0 0,1 0,-98"/>
    </svg>
</div>

@stack('scripts')

{{-- Disable form submit buttons until reCAPTCHA is solved --}}
<script>
(function () {
    function patchCaptchaButtons() {
        (window.recaptchaInputs || []).forEach(function (id) {
            var el = document.getElementById(id);
            if (!el || el.dataset.rcPatched) { return; }
            el.dataset.rcPatched = '1';

            var form = el.closest('form');
            if (!form) { return; }

            var btn = form.querySelector('button[type="submit"]');
            if (!btn) { return; }

            btn.disabled = true;

            var enableKey  = 'rcEnable_'  + id.replace(/[^a-z0-9]/gi, '_');
            var expiredKey = 'rcExpired_' + id.replace(/[^a-z0-9]/gi, '_');

            window[enableKey]  = function () { btn.disabled = false; };
            window[expiredKey] = function () { btn.disabled = true; };

            el.setAttribute('data-callback', enableKey);
            el.setAttribute('data-expired-callback', expiredKey);
        });
    }

    // Runs synchronously before the async reCAPTCHA API fires onloadCallback
    patchCaptchaButtons();
}());
</script>

<link rel="stylesheet" href="{{ url('modules/Newsletter/css/newsletter.css') }}">
@if(\Modules\Core\Models\Setting::get('newsletter.popup_enabled') === '1')
    <div id="newsletter-popup" class="modal fade newsletter-popup" tabindex="-1"
         data-delay="{{ \Modules\Core\Models\Setting::get('newsletter.popup_delay', '5') }}"
         data-url="{{ route('newsletter.popup') }}">
    </div>
@endif
@if(\Modules\Core\Models\Setting::get('newsletter.recaptcha_enabled') === '1' && \Modules\Captcha\Facades\Captcha::isEnabled())
    @include('captcha::header-meta')
    <script>
        window.recaptchaInputs = window.recaptchaInputs || [];
        var refreshRecaptcha = function() {
            window.recaptchaInputs.forEach(function(item, index) {
                grecaptcha.reset(index);
            });
        };
        var onloadCallback = function() {
            window.recaptchaInputs.forEach(function(id) {
                var el = document.getElementById(id);
                if (el) grecaptcha.render(id, { sitekey: el.getAttribute('data-sitekey') });
            });
        };
    </script>
    <script src="https://www.google.com/recaptcha/api.js?onload=onloadCallback&render=explicit&hl={{ app()->getLocale() }}" async defer></script>
@endif
<script src="{{ url('modules/Newsletter/js/newsletter.js') }}"></script>
</body>
</html>
