{{-- Cookie Consent Banner --}}
@if (setting('cookie_consent_enabled', true))
<div class="cookie-consent-banner" id="cookie-consent" style="display: none;">
    <div class="container">
        <div class="cookie-consent-content">
            <div class="cookie-consent-message">
                <i class="fa fa-cookie-bite me-2"></i>
                {{ setting('cookie_consent_message', trans('cookie-consent::cookie-consent.message')) }}
            </div>
            <div class="cookie-consent-actions">
                <button class="btn btn-light btn-sm" id="cookie-agree">
                    {{ trans('cookie-consent::cookie-consent.agree') }}
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.cookie-consent-banner {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: rgba(0, 0, 0, 0.9);
    color: #fff;
    padding: 1.5rem 0;
    z-index: 9999;
}
.cookie-consent-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 1rem;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const banner = document.getElementById('cookie-consent');
    const agreeBtn = document.getElementById('cookie-agree');

    if (!getCookie('cookie_consent')) {
        banner.style.display = 'block';
    }

    if (agreeBtn) {
        agreeBtn.addEventListener('click', function() {
            setCookie('cookie_consent', 'accepted', 365);
            banner.style.display = 'none';
        });
    }

    function setCookie(name, value, days) {
        const d = new Date();
        d.setTime(d.getTime() + (days * 24 * 60 * 60 * 1000));
        document.cookie = name + "=" + value + ";expires=" + d.toUTCString() + ";path=/";
    }

    function getCookie(name) {
        const nameEQ = name + "=";
        const ca = document.cookie.split(';');
        for (let i = 0; i < ca.length; i++) {
            let c = ca[i].trim();
            if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length);
        }
        return null;
    }
});
</script>
@endif
