{{-- Extensión "orderdocs" (piezas 20 y 22 de "PrestaShop en el chat").

     - Notas del pedido: el JS pinta la lista y el formulario en la pestaña
       Notas del workspace de pedido (#powPanelNotas) al recibir
       psc:order-rendered; no necesita marcado aquí.
     - Documentos del pedido: hoja interna del workspace. Se define aquí y el
       JS la mueve UNA vez dentro de .bv-po-body del modal ps-order-workspace
       (nunca un modal encima de otro). --}}
<link rel="stylesheet" href="{{ asset('modules/helpdeskprestashop/css/ext/orderdocs.css') }}?v={{ @filemtime(public_path('modules/helpdeskprestashop/css/ext/orderdocs.css')) }}">

<div class="ps-sheet bv-hidden" id="psOrderdocsSheet">
    <div class="ps-sheet-head">
        <span class="ic"><i class="fas fa-file-lines"></i></span>
        <span>
            <span class="lbl">Pedido · <span id="psOrderdocsRef"></span></span>
            <span class="ttl">Documentos</span>
        </span>
        <button type="button" class="bv-modal-close" id="psOrderdocsClose" aria-label="Cerrar"><i class="fas fa-xmark"></i></button>
    </div>
    <div class="ps-sheet-body">
        <div class="psc-note psc-note--warn bv-hidden" id="psOrderdocsError"><span class="psc-note-txt"></span></div>
        <div class="psc-note psc-note--lock bv-hidden" id="psOrderdocsDenied">
            <span class="psc-note-txt">Puedes ver qué documentos hay, pero no tienes permiso para descargarlos ni enviarlos.</span>
        </div>
        <div class="psc-orderdocs-list" id="psOrderdocsList"></div>
        <div class="psc-note psc-note--info" id="psOrderdocsHint">
            <span class="psc-note-txt">Enviar por el chat manda el PDF al cliente en esta conversación sin descargarlo a tu equipo.</span>
        </div>
        <div class="psc-note psc-note--lock bv-hidden" id="psOrderdocsConfirm">
            <span class="psc-note-txt"></span>
        </div>
    </div>
    <div class="ps-sheet-foot">
        <button type="button" class="psc-btn psc-btn--primary is-disabled" id="psOrderdocsSend" disabled>Enviar por el chat</button>
        <button type="button" class="psc-btn psc-btn--outline is-disabled" id="psOrderdocsDownload" disabled>Descargar</button>
        <button type="button" class="psc-btn psc-btn--outline" id="psOrderdocsCancel">Cerrar</button>
    </div>
</div>

@once
@push('scripts')
    <script src="{{ asset('modules/helpdeskprestashop/js/ext/orderdocs.js') }}?v={{ @filemtime(public_path('modules/helpdeskprestashop/js/ext/orderdocs.js')) }}" defer></script>
@endpush
@endonce
