{{-- Modal estandalone: verificacion de identidad del cliente (OTP).
     Reutilizable desde cualquier punto de la app via
     window.openCustomerIdentityVerification(customerId, onVerified).
     Reemplaza el gate que antes vivia embebido dentro de
     modals/customer-integrations.blade.php — mismos endpoints, mismo
     servicio (CustomerIdentityVerificationService), UX enriquecida segun
     el mockup Alvarez #51 (intentos limitados, cronometro de reenvio,
     cuenta atras de caducidad, banner de "ya validada"). --}}
<div class="bv-modal" data-bv-modal-name="verify-customer-identity">
    <div class="bv-modal-dialog md">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box primary"><i class="fas fa-shield-halved"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">CLIENTE · IDENTIDAD</span>
                <div class="bv-modal-title"><span id="viTitle">Verificar identidad</span></div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>

        <div class="bv-modal-body" id="viBody"></div>

        <div class="bv-modal-foot" id="viFoot"></div>
    </div>
</div>

@once
@push('scripts')
<script>
    window.HelpdeskIntegrationLang = Object.assign(window.HelpdeskIntegrationLang || {}, @json(__('helpdeskintegration::messages.js')));
</script>
<script src="{{ asset('vendor/helpdeskintegration/verify-customer-identity.js') }}?v={{ @filemtime(public_path('vendor/helpdeskintegration/verify-customer-identity.js')) }}"></script>
@endpush
@endonce
