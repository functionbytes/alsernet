@php
    use App\Features\EcommerceFeatures;
@endphp
@if(\Laravel\Pennant\Feature::active(EcommerceFeatures::EXIT_INTENT))
@php
    $exitCoupon = \Modules\Core\Models\Setting::get('ecommerce.exit_intent_coupon', 'NOTEVAYAS');
    $exitDiscount = \Modules\Core\Models\Setting::get('ecommerce.exit_intent_percent', '15');
@endphp

<div class="modal fade" id="exitIntentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0">
            <div class="modal-body p-0 position-relative">
                <button type="button" class="btn-close position-absolute top-0 end-0 m-3" style="z-index:10" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                <div class="row g-0">
                    <div class="col-md-5 d-none d-md-flex align-items-center justify-content-center" style="background:linear-gradient(135deg,#1a2030,#2d3447);min-height:380px">
                        <i class="fas fa-hand-paper" style="color:#ffc107;font-size:7rem"></i>
                    </div>
                    <div class="col-md-7 p-4 text-center d-flex flex-column justify-content-center">
                        <h2 class="fw-bold mb-3">¡Espera!</h2>
                        <p class="text-muted mb-3">Antes de irte, te dejamos un descuento exclusivo:</p>
                        <div class="display-3 fw-bold text-brand mb-2">{{ $exitDiscount }}% OFF</div>
                        <p class="mb-3">en tu próxima compra</p>
                        <div class="alert alert-warning fs-5 fw-bold py-2 mb-3" style="letter-spacing:2px">{{ $exitCoupon }}</div>
                        <button class="btn btn-primary w-100 mb-2" id="copyExitCoupon" data-coupon="{{ $exitCoupon }}">
                            <i class="fas fa-copy me-2"></i>Copiar y seguir comprando
                        </button>
                        <button class="btn btn-link text-muted small" data-bs-dismiss="modal">Salir igualmente</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    if (sessionStorage.getItem('exit_intent_shown')) { return; }
    if (localStorage.getItem('exit_intent_seen_today') === new Date().toDateString()) { return; }

    var triggered = false;

    document.addEventListener('mouseout', function (e) {
        if (triggered || e.relatedTarget !== null || e.clientY >= 10) { return; }
        triggered = true;
        var modal = new bootstrap.Modal(document.getElementById('exitIntentModal'));
        modal.show();
        sessionStorage.setItem('exit_intent_shown', '1');
        localStorage.setItem('exit_intent_seen_today', new Date().toDateString());
    });

    document.getElementById('copyExitCoupon').addEventListener('click', function () {
        var coupon = this.dataset.coupon;
        navigator.clipboard.writeText(coupon).then(function () {
            if (typeof toastr !== 'undefined') { toastr.success('Código copiado'); }
            bootstrap.Modal.getInstance(document.getElementById('exitIntentModal')).hide();
        });
    });
}());
</script>
@endif
