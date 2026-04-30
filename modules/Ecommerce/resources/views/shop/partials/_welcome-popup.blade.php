@php
    $couponCode    = \Modules\Core\Models\Setting::get('ecommerce.welcome_coupon_code', '');
    $couponPercent = \Modules\Core\Models\Setting::get('ecommerce.welcome_coupon_percent', '10');
@endphp

@if($couponCode)
<div class="modal fade" id="welcomePopup" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0">
            <div class="modal-body p-0 position-relative">
                <button type="button" class="btn-close position-absolute top-0 end-0 m-3" style="z-index:10" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                <div class="row g-0">
                    <div class="col-md-5 d-none d-md-flex align-items-center justify-content-center welcome-popup-sidebar">
                        <i class="fas fa-gift fa-4x text-white opacity-75"></i>
                    </div>
                    <div class="col-md-7 p-4 text-center">
                        <i class="fas fa-gift fa-4x text-warning mb-3 d-md-none"></i>
                        <h2 class="fw-bold mb-2">¡Bienvenido!</h2>
                        <p class="text-muted mb-3">Como nuevo visitante, te regalamos:</p>
                        <div class="display-3 fw-bold text-brand mb-3">{{ $couponPercent }}% OFF</div>
                        <p class="mb-3">en tu primera compra. Usa el código:</p>
                        <div class="alert alert-warning fs-5 fw-bold py-2 mb-3 welcome-coupon-code">{{ $couponCode }}</div>
                        <button class="btn btn-primary w-100 mb-2" id="copyWelcomeCoupon" data-code="{{ $couponCode }}">
                            <i class="fas fa-copy me-1"></i> Copiar código
                        </button>
                        <button class="btn btn-link text-muted small w-100" data-bs-dismiss="modal">No, gracias</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.welcome-popup-sidebar {
    background: linear-gradient(135deg, #b10100, #7da210);
    min-height: 360px;
    border-radius: 4px 0 0 4px;
}
.welcome-coupon-code { letter-spacing: 2px; }
</style>

<script>
(function() {
    if (localStorage.getItem('welcome_popup_seen')) return;

    setTimeout(function() {
        var el = document.getElementById('welcomePopup');
        if (!el) return;
        var modal = new bootstrap.Modal(el);
        modal.show();
        localStorage.setItem('welcome_popup_seen', '1');
    }, 5000);

    document.addEventListener('click', function(e) {
        if (e.target && e.target.id === 'copyWelcomeCoupon') {
            var code = e.target.getAttribute('data-code');
            navigator.clipboard.writeText(code).then(function() {
                if (typeof toastr !== 'undefined') {
                    toastr.success('Código copiado al portapapeles');
                }
            });
        }
    });
})();
</script>
@endif
