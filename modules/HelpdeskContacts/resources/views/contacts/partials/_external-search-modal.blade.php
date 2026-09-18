{{--
    Modal "CLIENTE · BÚSQUEDA EXTERNA" — mismo framework visual .bv-modal que
    usa HelpdeskIntegration (customer-integrations.blade.php), reconstruido
    aquí porque ese modal depende de HDCommerce/conversations.js (acoplado al
    estado del inbox). Este modal tiene su propio JS de apertura/cierre
    (external-search-modal.js), sin depender de conversations.js.

    Tres vistas dentro de #external-search-modal, alternadas por JS:
      1. extSearchView   — buscar en ERP/PrestaShop (todas a la vez por defecto)
      2. extPreviewView  — ficha completa del resultado elegido (info-table +
         pedidos/facturas), con la acción contextual (ver ficha / vincular /
         crear) en el foot.
      3. extHsmView      — enviar plantilla WhatsApp inline, una vez resuelto
         un customer_id (recién creado, vinculado, o ya existente).

    data-mode="index" (listado, sin contacto aún) / "link" (ficha 360, vincula
    al contacto ya abierto) — seteado por el trigger antes de abrir el modal.
--}}
@once
    @push('css')
        <style>
            /* Las 3 vistas son un nivel de anidado extra dentro de
               .bv-modal-body, que solo da su gap:12px a hijos directos — sin
               esto, los campos internos de cada vista (fila plataforma/tipo,
               búsqueda, resultados) quedaban pegados entre sí. Clase (no
               inline) a propósito: JS solo alterna .d-none, así que
               display:flex sigue viniendo del stylesheet en todo momento. */
            .ext-view-stack { display: flex; flex-direction: column; gap: 12px; }

            /* select2 asume una caja de ~28px (su alto por defecto) y coloca
               la flecha con un "top" fijo para esa altura — dentro del
               .bv-modal la caja sale más alta (mismo padding que .fselect),
               así que la flecha quedaba pegada arriba en vez de centrada.
               Se iguala la caja al look de .fselect y se centra la flecha
               con top:50% en vez de un valor fijo, para que no importe la
               altura real que termine teniendo. */
            #external-search-modal .select2-container .select2-selection--single {
                height: auto;
                padding: 8px 10px;
                border: 1px solid var(--bv-border, #e4e4e7);
                border-radius: 8px;
            }
            #external-search-modal .select2-container .select2-selection--single .select2-selection__rendered {
                padding: 0;
                line-height: 1.4;
                font-size: 13px;
                color: var(--bv-text, #18181b);
            }
            #external-search-modal .select2-container .select2-selection--single .select2-selection__arrow {
                height: auto;
                top: 50%;
                right: 8px;
                transform: translateY(-50%);
            }

            .nc-platform-group { padding: 10px; }
            .nc-platform-group .bv-modal-label { padding: 10px; }
        </style>
    @endpush
@endonce
<div class="bv-modal" id="external-search-modal" data-bv-modal-name="external-search"
     data-platforms-url="{{ route('contacts.external-platforms') }}"
     data-search-url="{{ route('contacts.external-search') }}"
     data-preview-url="{{ route('contacts.external-preview') }}"
     data-create-url="{{ route('contacts.external-create') }}"
     data-link-url-base="{{ url('panel/helpdesk/contacts') }}"
     data-show-url-base="{{ url('panel/helpdesk/contacts') }}"
     data-hsm-templates-url="{{ route('contacts.hsm-templates') }}"
     data-inbox-url="{{ route('manager.helpdesk.conversations.index') }}">
    <div class="bv-modal-dialog lg">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box primary" id="extModalIcon"><i class="fas fa-magnifying-glass"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label" id="extModalLabel">CLIENTE · BÚSQUEDA EXTERNA</span>
                <div class="bv-modal-title"><span id="extModalTitle">Buscar en ERP/PrestaShop</span></div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>

        <div class="bv-modal-body">

            {{-- Vista 1: búsqueda --}}
            <div id="extSearchView" class="ext-view-stack">
                <div class="field">
                    <div class="flabel">Plataforma</div>
                    <select class="fselect" id="ext-search-platform">
                        <option value="">Cargando...</option>
                    </select>
                </div>
                <div class="field">
                    <div class="flabel">Búsqueda</div>
                    <div class="search-field">
                        <i class="fas fa-magnifying-glass"></i>
                        <input type="text" class="finput" id="ext-search-query" placeholder="Nombre, email, teléfono, NIF o ID...">
                    </div>
                </div>
                <div id="ext-search-results">
                    <div class="bv-oc-empty">
                        <i class="fas fa-magnifying-glass"></i>
                        <div class="title">Buscar cliente</div>
                        <div>Escribe al menos 2 caracteres para buscar en ERP y PrestaShop a la vez.</div>
                    </div>
                </div>
            </div>

            {{-- Vista 2: ficha completa del resultado elegido --}}
            <div id="extPreviewView" class="ext-view-stack d-none">
                <div class="info-table" id="ext-preview-customer"></div>
                <div id="ext-preview-orders"></div>
            </div>

            {{-- Vista 3: enviar plantilla WhatsApp --}}
            <div id="extHsmView" class="ext-view-stack d-none">
                <div class="minfo" id="ext-hsm-target"></div>
                <div class="field">
                    <div class="flabel">Plantilla</div>
                    <select class="fselect" id="ext-hsm-template">
                        <option value="">Cargando plantillas...</option>
                    </select>
                </div>
                <div id="ext-hsm-vars"></div>
                <div class="field">
                    <div class="flabel">Vista previa</div>
                    <div class="info-table" id="ext-hsm-preview"></div>
                </div>
            </div>

        </div>

        <div class="bv-modal-foot" id="extSearchFoot">
            <button class="btn-secondary" data-bv-close type="button">Cerrar</button>
        </div>

        <div class="bv-modal-foot" id="extPreviewFoot" style="display:none">
            <div id="ext-preview-actions"></div>
            <button class="btn-secondary" id="ext-preview-back" type="button">Volver a la búsqueda</button>
        </div>

        <div class="bv-modal-foot" id="extHsmFoot" style="display:none">
            <button class="btn-primary" id="ext-hsm-send" type="button">Enviar plantilla</button>
            <button class="btn-secondary" id="ext-hsm-back" type="button">Volver a la ficha</button>
        </div>
    </div>
</div>
