<div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
        <div class="newsletter-popup-content">
            <button type="button" class="btn-close position-absolute" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            <div class="modal-header flex-column align-items-start border-0 p-0">
                <span class="modal-subtitle fw-semibold text-uppercase mb-2">{{ __('newsletter_popup_subtitle') }}</span>
                <h5 class="modal-title mb-2">{{ __('newsletter_popup_title') }}</h5>
                <p class="modal-text text-muted">{{ __('newsletter_popup_description') }}</p>
            </div>
            <div class="modal-body p-0 mt-3">
                <form method="POST" action="{{ route('newsletter.subscribe') }}" class="bb-newsletter-popup-form">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label" for="popup-name">{{ __('newsletter_field_name') }}</label>
                        <input class="form-control" placeholder="{{ __('newsletter_placeholder_name') }}" name="name" type="text" id="popup-name" autocomplete="name">
                    </div>
                    <div class="mb-3">
                        <label class="form-label required" for="popup-email">{{ __('newsletter_field_email') }}</label>
                        <input class="form-control" placeholder="{{ __('newsletter_placeholder_email') }}" required name="email" type="email" id="popup-email" autocomplete="email">
                    </div>
                    <div class="newsletter-message newsletter-success-message mb-3" class="d-none"></div>
                    <div class="newsletter-message newsletter-error-message mb-3" class="d-none"></div>
                    @if(\Modules\Core\Models\Setting::get('newsletter.recaptcha_enabled') === '1' && \Modules\Captcha\Facades\Captcha::isEnabled())
                    <div class="mb-3" id="newsletter-popup-captcha-wrapper">
                        <div class="g-recaptcha" id="newsletter-popup-captcha"
                             data-sitekey="{{ setting('captcha_site_key') }}"></div>
                    </div>
                    @endif
                    <button class="btn btn-info" type="submit">{{ __('newsletter_btn_subscribe') }}</button>
                    <div class="form-check mt-2">
                        <input type="checkbox" id="dont_show_again" name="dont_show_again" class="form-check-input" value="1">
                        <label class="form-check-label text-muted" for="dont_show_again">{{ __('newsletter_dont_show_again') }}</label>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
