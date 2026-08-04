{{-- Tab "Documentacion" del right-panel del inbox — LISTA de expedientes del cliente.
     Propietario: HelpdeskDocument · Renderizado por Helpdesk via slot.
     Espera $rpDocuments (array de items ligeros, DocumentPanelPresenter::list) y
     $rpConvo (Conversation). El detalle de cada expediente se carga via AJAX en el
     host (#docs-detail-host) desde la ruta manager.helpdesk.conversations.documents.panel. --}}
<div class="bv-right-tab-content bv-tab-hidden" data-bv-tab-content="document">
    @include('helpdeskdocument::modals._assets')


    {{-- Backdrop + modal persistente del detalle de expediente --}}
    <div class="docs-backdrop" data-docs-modal-backdrop-for="docsDetailPanel"></div>
    <div class="docs-modal docs-modal-xl" id="docsDetailPanel" data-docs-persistent
         aria-labelledby="docsDetailPanelLabel"
         data-url-manage-tpl="{{ route('documents.manage', '__UID__') }}"
         data-url-summary-tpl="{{ route('documents.summary', '__UID__') }}"
         data-url-media-tpl="{{ route('manager.helpdesk.customers.media', ['customer' => '__ID__']) }}"
         data-url-import-chat-tpl="{{ route('manager.helpdesk.conversations.documents.import-from-chat', ['conversation' => '__ID__']) }}"
         data-url-import-device-tpl="{{ route('manager.helpdesk.conversations.documents.import-from-device', ['conversation' => '__ID__']) }}">
        <div class="docs-head">
            <div class="docs-head-icon"><i class="fa-regular fa-folder-open"></i></div>
            <div class="docs-head-text">
                <div class="docs-head-label" id="docsDetailPanelLabel">Expediente</div>
                <div class="docs-head-order" id="docsDetailPanelOrder"></div>
            </div>
            <button type="button" class="docs-close" aria-label="Cerrar"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="docs-body pad-0" id="docsDetailPanelBody">
            {{-- Relleno via AJAX al abrir --}}
        </div>
        <div class="docs-foot">
            <button type="button" class="btn btn-outline-secondary w-100" data-docs-manage-link>
                Ver en gestor de documentos
            </button>
            <button type="button" class="btn btn-outline-secondary w-100 docs-close">
                Cerrar
            </button>
        </div>
    </div>

    {{-- Modal: crear expediente nuevo para el cliente --}}
    <div class="docs-backdrop" data-docs-modal-backdrop-for="docsCreatePanel"></div>
    <div class="docs-modal docs-modal-md" id="docsCreatePanel" aria-labelledby="docsCreatePanelLabel">
        <div class="docs-head">
            <div class="docs-head-icon"><i class="fa-regular fa-folder-open"></i></div>
            <div class="docs-head-text">
                <div class="docs-head-label">Documentos</div>
                <div class="docs-head-title" id="docsCreatePanelLabel">Asignar expediente</div>
            </div>
            <button type="button" class="docs-close" aria-label="Cerrar"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="docs-body">
            <div class="docs-minfo">
                <i class="fa-solid fa-circle-info"></i>
                <div class="docs-create-info">Se creará un expediente para <b class="docs-create-customer">el cliente</b> y quedará vinculado a esta conversación.</div>
            </div>

            {{-- Modo: crear un expediente nuevo o asignar uno ya existente --}}
            {{-- Toggle oculto: el modal opera solo en modo "Asignar existente". Para
                 reactivar "Crear nuevo", quitar la clase d-none. --}}
            <div class="btn-group w-100 mt-2 mb-2 docs-create-modes d-none" role="group" aria-label="Modo">
                <button type="button" class="btn btn-sm btn-primary" data-docs-create-mode="new">Crear nuevo</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-docs-create-mode="assign">Asignar existente</button>
            </div>

            {{-- Crear nuevo --}}
            <div data-docs-create-pane="new">
                <div class="docs-field">
                    <label class="flabel">Tipo de documento</label>
                    <select class="fselect docs-create-type"><option value="">Cargando…</option></select>
                </div>
                <div class="docs-field mt-2">
                    <label class="flabel">Referencia de orden <span class="hint">opcional</span></label>
                    <input type="text" class="finput docs-create-ref" maxlength="64" placeholder="#829575">
                </div>
            </div>

            {{-- Asignar existente --}}
            <div class="d-none" data-docs-create-pane="assign">
                <div class="docs-field">
                    <label class="flabel">Buscar expediente</label>
                    <div class="docs-search-field">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" class="docs-assign-search" autocomplete="off" placeholder="Nº de orden, DNI, correo, nombre…">
                    </div>
                    <div class="hint mt-1">Por nº de orden, documento (DNI/uid), correo o nombre. Mín. 2 caracteres.</div>
                </div>
                <div class="docs-opt-list mt-2" id="docsAssignResults"></div>
            </div>
        </div>
        <div class="docs-foot">
            <button type="button" class="btn btn-primary docs-create-submit" data-docs-mode-btn="new">Crear expediente</button>
            <button type="button" class="btn btn-primary docs-assign-submit d-none" data-docs-mode-btn="assign">Asignar expediente</button>
            <button type="button" class="btn btn-outline-secondary docs-close">Cancelar</button>
        </div>
    </div>

    {{-- Vista lista de expedientes (partial re-renderizable vía AJAX) --}}
    <div class="docs-list-view" data-docs-list-view
         @if($rpConvo)
         data-url-list="{{ route('manager.helpdesk.conversations.documents.list', $rpConvo->id) }}"
         data-url-create-options="{{ route('manager.helpdesk.conversations.documents.create-options', $rpConvo->id) }}"
         data-url-create="{{ route('manager.helpdesk.conversations.documents.store', $rpConvo->id) }}"
         data-url-search="{{ route('manager.helpdesk.conversations.documents.search', $rpConvo->id) }}"
         data-url-link="{{ route('manager.helpdesk.conversations.documents.link', [$rpConvo->id, '_DOCID_']) }}"
         @endif>
        @include('helpdeskdocument::inbox-slots._document-list', ['rpDocuments' => $rpDocuments ?? [], 'rpConvo' => $rpConvo])
    </div>
</div>

@once
@push('scripts')
    {{-- El JS de este panel (~1.800 lineas) vive en un fichero propio y no inline:
         asi se cachea en el navegador, se versiona por mtime y es editable sin
         tocar el Blade. Fuente en modules/HelpdeskDocument/public/js/ — recuerda
         copiarlo a public/modules/helpdeskdocument/js/ tras editarlo. --}}
    <script src="{{ asset('modules/helpdeskdocument/js/document-panel.js') }}?v={{ @filemtime(base_path('modules/HelpdeskDocument/public/js/document-panel.js')) }}" defer></script>
@endpush
@endonce
