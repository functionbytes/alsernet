<div id="cookie-banner" class="cookie-banner" style="display:none">
    <div class="container">
        <div class="row align-items-center g-2">
            <div class="col-md-8">
                <p class="mb-0 small text-white">
                    <i class="fas fa-cookie-bite me-2"></i>
                    Usamos cookies propias y de terceros para mejorar tu experiencia, mostrar publicidad personalizada y analizar el tráfico. Al aceptar, consientes el uso de todas las cookies.
                    <a href="{{ route('shop.legal.show', 'privacy') }}" class="text-warning text-decoration-underline">Más información</a>
                </p>
            </div>
            <div class="col-md-4 text-md-end">
                <button class="btn btn-sm btn-outline-light me-2" id="cookie-banner-essential">Solo necesarias</button>
                <button class="btn btn-sm btn-warning" id="cookie-banner-accept">Aceptar todas</button>
            </div>
        </div>
    </div>
</div>

<style>
    .cookie-banner {
        position: fixed; bottom: 0; left: 0; right: 0;
        background: rgba(20,20,30,0.97);
        padding: 16px 0;
        z-index: 9998;
        box-shadow: 0 -4px 20px rgba(0,0,0,0.3);
    }
</style>

<script>
(function () {
    const KEY = 'cookie_consent';
    const banner = document.getElementById('cookie-banner');
    if (! localStorage.getItem(KEY)) {
        setTimeout(() => banner.style.display = 'block', 1200);
    }
    document.getElementById('cookie-banner-accept').addEventListener('click', () => {
        localStorage.setItem(KEY, 'all');
        banner.style.display = 'none';
        document.dispatchEvent(new Event('cookieConsentAll'));
    });
    document.getElementById('cookie-banner-essential').addEventListener('click', () => {
        localStorage.setItem(KEY, 'essential');
        banner.style.display = 'none';
        document.dispatchEvent(new Event('cookieConsentEssential'));
    });
})();
</script>
