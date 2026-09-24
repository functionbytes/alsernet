{{-- Gestión (ERP) · modal Finanzas. SOLO LECTURA.

     Se abre con [data-erp-open="finance"][data-erp-pane="balance|invoices|
     payments|debts|delivery-notes|returns"] (delegación en document, ver
     erp-finance.js). Los detalles (factura, albarán) se pintan en una hoja
     interna .erc-sheet dentro del cuerpo: nunca un modal sobre otro.

     Hoy casi todas las pestañas llegan "blocked" (falta GRANT en Oracle):
     se pintan en ámbar y funcionarán solas el día que el DBA dé el permiso.

     Lo incluye modals/erp-index.blade.php (glob de modals/parts). --}}
@once('erc-finance-css')
<link rel="stylesheet" href="{{ asset('modules/helpdeskerp/css/erp-finance.css') }}?v={{ @filemtime(public_path('modules/helpdeskerp/css/erp-finance.css')) }}"/>
@endonce

@once
<div class="bv-modal" data-bv-modal-name="erp-finance">
    <div class="bv-modal-dialog lg erc-fin-dialog">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box primary"><i class="fas fa-file-invoice-dollar"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">Gestión · Finanzas</span>
                <div class="bv-modal-title" id="ercFinTitle">Cliente</div>
            </div>
            <button type="button" class="bv-modal-close" data-bv-close aria-label="Cerrar"><i class="fas fa-xmark"></i></button>
        </div>

        <div class="erc-fin-tabs" id="ercFinTabs" role="tablist" aria-label="Secciones de finanzas"></div>

        <div class="bv-modal-body erc-fin-body" id="ercFinBody" role="tabpanel"></div>

        <div class="bv-modal-foot">
            <button type="button" class="btn-primary w-100" id="ercFinRefresh">Actualizar</button>
            <button type="button" class="btn-secondary w-100" data-bv-close>Cerrar</button>
        </div>
    </div>
</div>

@push('scripts')
    <script src="{{ asset('modules/helpdeskerp/js/erp-finance.js') }}?v={{ @filemtime(public_path('modules/helpdeskerp/js/erp-finance.js')) }}" defer></script>
@endpush
@endonce
