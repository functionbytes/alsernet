{{-- HelpdeskDocument — inyecta "Solicitar documento" en el composer del
     thread de Helpdesk. Mismo patrón que
     helpdeskhelpcenter::partials.thread-extension: modal + script deben
     renderizarse SIEMPRE (no solo cuando hay conversación seleccionada) para
     que sobrevivan al primer swap AJAX de pane.blade.php (ver comentario en
     thread.blade.php junto a @stack('hd-thread-modals')). El botón, en
     cambio, SÍ va condicionado a $convo porque necesita sus URLs — y se
     regenera fresco en cada render (full load o AJAX). --}}
@php($convo = $selectedConversation ?? null)

@if($convo)
@push('hd-composer-toolbar-buttons')
<button class="btn-ico" type="button" data-bv-tip="Solicitar documento" aria-label="Solicitar documento"
    data-doc-req-search-url="{{ route('manager.helpdesk.conversations.documents.search', $convo->id) }}"
    data-doc-req-message-url-template="{{ route('manager.helpdesk.conversations.documents.request-message', [$convo->id, '_DOCID_']) }}"
    onclick="openDocumentRequestModal(this)">
    <i class="fa-solid fa-file-arrow-up" aria-hidden="true"></i>
</button>
@endpush
@endif

@push('hd-thread-modals')
<style>
/* .hd-modal .list-item .body es text-align:center por defecto (pensado para
   #hdCannedList, que no siempre lleva .kbd); en esta lista de resultados de
   búsqueda el contenido va alineado a la izquierda. */
#hdDocReqList .list-item .body { text-align: left; }

/* .hd-overlay comparte align-items:flex-start + padding-top:10vh con
   #hdCannedOverlay/#hdArticleOverlay (no tocar esa regla, la comparten los
   tres); este modal en concreto va centrado verticalmente. overflow-y:auto
   por si el contenido (lista + idioma + vista previa a la vez) supera el
   alto de la ventana. */
#hdDocReqOverlay { align-items: center; padding-top: 0; overflow-y: auto; }
</style>
<div class="hd-overlay" id="hdDocReqOverlay">
    <div class="hd-modal w-md">
        <div class="modal-head">
            <div class="modal-icon"><i class="fa-solid fa-file-arrow-up"></i></div>
            <div class="modal-title-wrap">
                <span class="modal-label">HELPDESK · DOCUMENTOS</span>
                <span class="modal-title">Solicitar documento</span>
            </div>
            <button class="modal-close" onclick="closeDocumentRequestModal()"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <div class="search-field">
                <i class="fa-solid fa-magnifying-glass sf-ic"></i>
                <input class="finput" id="hdDocReqSearch" placeholder="Nº pedido, DNI, email o nombre…" autocomplete="off">
            </div>
            <div class="hd-canned-list" id="hdDocReqList">
                <div class="bv-list-state">Cargando expedientes del cliente…</div>
            </div>
            <div class="field bv-hidden" id="hdDocReqLocaleField">
                <label class="flabel" for="hdDocReqLocale">Idioma del mensaje</label>
                <select class="fselect select2" id="hdDocReqLocale">
                    <option value="es">Español</option>
                    <option value="en">English</option>
                    <option value="it">Italiano</option>
                    <option value="de">Deutsch</option>
                    <option value="pt">Português</option>
                </select>
            </div>
            <div class="field bv-hidden" id="hdDocReqPreviewField">
                <label class="flabel">Vista previa <span class="hint">Editable antes de insertar</span></label>
                <textarea class="finput" id="hdDocReqPreview" rows="4" placeholder="Selecciona un expediente…"></textarea>
            </div>
        </div>
        <div class="modal-foot modal-foot--stack">
            <button class="btn btn-primary w-100" onclick="hdInsertDocumentRequest()">Insertar en el chat</button>
            <button class="btn btn-secondary w-100" onclick="closeDocumentRequestModal()">Cancelar</button>
        </div>
    </div>
</div>
@endpush

@push('hd-thread-scripts')
    <script src="{{ asset('modules/helpdeskdocument/js/thread-extension.js') }}?v={{ @filemtime(public_path('modules/helpdeskdocument/js/thread-extension.js')) }}"></script>
@endpush
