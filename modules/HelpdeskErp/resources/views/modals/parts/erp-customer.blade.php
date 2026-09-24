{{-- Ficha de cliente de Gestión (ERP) — bv-modal "erp-customer-workspace".

     Mismo esquema que el workspace de cliente de PrestaShop: menú de
     secciones a la izquierda (arriba y compacto en móvil) y el panel de la
     sección a la derecha. Cada panel carga su sección con ErpChat.section()
     y pinta su estado (bloqueado, sin conexión, no disponible…).

     Se abre con [data-erp-open="customer"] (+ data-erp-pane opcional) o con
     window.ErpCustomerWorkspace.open(pane). SOLO LECTURA.

     data-erc-cw-sensitive: el agente puede ver tarjetas y cuentas
     (helpdeskerp.sensitive.view). El backend responde 403 igualmente si no.

     Fuente del JS/CSS en modules/HelpdeskErp/public/ — copiar a
     public/modules/helpdeskerp/ tras editar. --}}
@once
<link rel="stylesheet" href="{{ asset('modules/helpdeskerp/css/erp-customer.css') }}?v={{ @filemtime(public_path('modules/helpdeskerp/css/erp-customer.css')) }}"/>

<div class="bv-modal" data-bv-modal-name="erp-customer-workspace"
     data-erc-cw-sensitive="{{ auth()->user()?->can('helpdeskerp.sensitive.view') ? '1' : '0' }}">
    <div class="bv-modal-dialog xxl erc-cw-dialog" role="dialog" aria-modal="true" aria-labelledby="ercCwTitle">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box primary"><i class="fas fa-address-card"></i></div>
            <div class="bv-modal-title-wrap erc-cw-headwrap">
                <span class="bv-modal-label"><i class="fas fa-database"></i> Gestión · Cliente</span>
                <div class="bv-modal-title" id="ercCwTitle">Cliente</div>
                <div class="erc-cw-headmeta" id="ercCwMeta"></div>
            </div>
            <button type="button" class="bv-modal-close" data-bv-close aria-label="Cerrar"><i class="fas fa-xmark"></i></button>
        </div>

        <div class="bv-modal-body erc-cw-body">
            <div class="erc-cw">
                <nav class="erc-cw-nav" id="ercCwNav" aria-label="Secciones del cliente"></nav>
                <div class="erc-cw-pane" id="ercCwPane"></div>
            </div>
        </div>

        <div class="bv-modal-foot">
            <button type="button" class="btn-primary w-100" id="ercCwRefresh">Actualizar datos</button>
            <button type="button" class="btn-secondary w-100" data-bv-close>Cerrar</button>
        </div>
    </div>
</div>

@push('scripts')
    <script src="{{ asset('modules/helpdeskerp/js/erp-customer.js') }}?v={{ @filemtime(public_path('modules/helpdeskerp/js/erp-customer.js')) }}" defer></script>
@endpush
@endonce
