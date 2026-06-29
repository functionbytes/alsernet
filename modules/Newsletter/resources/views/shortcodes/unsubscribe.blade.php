<div class="newsletter-unsubscribe-shortcode default-style">
    <div class="newsletter-inline-container">
        <div class="newsletter-inline-content">
            <span class="newsletter-inline-subtitle">{{ __('newsletter_unsubscribe_subtitle') }}</span>
            <h3 class="newsletter-inline-title">{{ __('newsletter_unsubscribe_title') }}</h3>
            <p class="newsletter-inline-description">{{ __('newsletter_unsubscribe_description') }}</p>
            <div class="newsletter-inline-form">
                <form class="bb-newsletter-unsubscribe-form" novalidate>
                    @csrf
                    <div class="newsletter-form-fields">
                        <div class="form-group">
                            <input class="form-control mb-3"
                                   placeholder="{{ __('newsletter_unsubscribe_placeholder') }}"
                                   name="email" type="email" autocomplete="email" required aria-required="true">
                        </div>
                        <button class="btn btn-outline-danger w-100" type="submit">
                            {{ __('newsletter_btn_unsubscribe') }}
                        </button>
                    </div>
                    <div class="newsletter-message newsletter-success-message mt-3" class="d-none"></div>
                    <div class="newsletter-message newsletter-error-message mt-3" class="d-none"></div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    document.querySelectorAll('.bb-newsletter-unsubscribe-form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var email = form.querySelector('input[name="email"]').value;
            var csrf  = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
            var btn   = form.querySelector('button[type="submit"]');
            var ok    = form.querySelector('.newsletter-success-message');
            var err   = form.querySelector('.newsletter-error-message');

            btn.disabled = true;

            fetch('{{ route('newsletter.unsubscribe') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ email: email }),
            })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data.success) {
                    ok.style.display = 'block';
                    ok.innerHTML = '<div class="alert alert-success">' + data.message + '</div>';
                    err.style.display = 'none';
                    form.querySelector('input').value = '';
                } else {
                    err.style.display = 'block';
                    err.innerHTML = '<div class="alert alert-danger">' + (data.message || 'Ha ocurrido un error.') + '</div>';
                    ok.style.display = 'none';
                }
                btn.disabled = false;
            })
            .catch(function () {
                err.style.display = 'block';
                err.innerHTML = '<div class="alert alert-danger">Ha ocurrido un error. Inténtalo de nuevo.</div>';
                btn.disabled = false;
            });
        });
    });
}());
</script>
