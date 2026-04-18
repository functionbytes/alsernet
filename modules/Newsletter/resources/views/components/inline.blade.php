<div class="newsletter-inline-shortcode default-style">
    <div class="newsletter-inline-container">
        <div class="newsletter-inline-content">
            <span class="newsletter-inline-subtitle">{{ __('newsletter_inline_subtitle') }}</span>
            <h3 class="newsletter-inline-title">{{ __('newsletter_inline_title') }}</h3>
            <p class="newsletter-inline-description">{{ __('newsletter_inline_description') }}</p>
            <div class="newsletter-inline-form">
                <form method="POST" action="{{ route('newsletter.subscribe') }}" class="bb-newsletter-inline-form" novalidate>
                    @csrf
                    <div class="newsletter-form-fields">
                        <div class="form-group">
                            <input class="form-control mb-3" placeholder="{{ __('newsletter_placeholder_name') }}" id="newsletter-inline-name" name="name" type="text" autocomplete="name">
                        </div>
                        <div class="form-group">
                            <input class="form-control mb-3" placeholder="{{ __('newsletter_placeholder_email') }}" id="newsletter-inline-email" required name="email" type="email" autocomplete="email" aria-required="true">
                        </div>
                        @if(\Modules\Core\Models\Setting::get('newsletter.recaptcha_enabled') === '1' && \Modules\Captcha\Facades\Captcha::isEnabled())
                        <div class="mb-3">
                            {!! \Modules\Captcha\Facades\Captcha::display() !!}
                        </div>
                        @endif
                        <button class="btn btn-primary w-100" type="submit">{{ __('newsletter_btn_subscribe') }}</button>
                    </div>
                    <div class="newsletter-message newsletter-success-message mt-3" style="display:none"></div>
                    <div class="newsletter-message newsletter-error-message mt-3" style="display:none"></div>
                </form>
            </div>
        </div>
    </div>
</div>
