{{-- Gestión (ERP) · modal Fidelización. SOLO LECTURA.

     Se abre con [data-erp-open="loyalty"][data-erp-pane="points|vouchers|
     bonuses"] (delegación en document, ver erp-loyalty.js). El albarán de un
     movimiento de puntos se pinta en una hoja interna .erc-sheet dentro del
     cuerpo: nunca un modal sobre otro.

     Vales y bonos llegan hoy "blocked" (falta GRANT en Oracle): se pintan en
     ámbar y funcionarán solos el día que el DBA dé el permiso. El ERP no da
     código canjeable de los bonos: el texto que se inserta en el chat solo
     lleva importe, compra mínima y validez.

     Comparte erp-finance.css con el modal Finanzas.
     Lo incluye modals/erp-index.blade.php (glob de modals/parts). --}}
@once('erc-finance-css')
<link rel="stylesheet" href="{{ asset('modules/helpdeskerp/css/erp-finance.css') }}?v={{ @filemtime(public_path('modules/helpdeskerp/css/erp-finance.css')) }}"/>
@endonce

@once
<div class="bv-modal" data-bv-modal-name="erp-loyalty">
    <div class="bv-modal-dialog lg erc-fin-dialog">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box primary"><i class="fas fa-star"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">Gestión · Fidelización</span>
                <div class="bv-modal-title" id="ercLoyTitle">Cliente</div>
            </div>
            <button type="button" class="bv-modal-close" data-bv-close aria-label="Cerrar"><i class="fas fa-xmark"></i></button>
        </div>

        <div class="erc-fin-tabs" id="ercLoyTabs" role="tablist" aria-label="Secciones de fidelización"></div>

        <div class="bv-modal-body erc-fin-body" id="ercLoyBody" role="tabpanel"></div>

        <div class="bv-modal-foot">
            <button type="button" class="btn-primary w-100" id="ercLoyRefresh">Actualizar</button>
            <button type="button" class="btn-secondary w-100" data-bv-close>Cerrar</button>
        </div>
    </div>
</div>

@push('scripts')
    <script src="{{ asset('modules/helpdeskerp/js/erp-loyalty.js') }}?v={{ @filemtime(public_path('modules/helpdeskerp/js/erp-loyalty.js')) }}" defer></script>
@endpush
@endonce
