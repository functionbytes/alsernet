{{--
    Modal "CONTACTOS · ALTA / Buscar en ERP o tienda" (mockup piezas 01 y 02) — mismo framework visual .bv-modal que
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
{{-- Estilos de este modal: modules/HelpdeskContacts/resources/css/contacts.css
     (cargado desde index.blade.php y show.blade.php, las dos vistas que
     incluyen este partial — ver comentario de cabecera de ese fichero). --}}
<div class="bv-modal" id="external-search-modal" data-bv-modal-name="external-search"
     data-platforms-url="{{ route('contacts.external-platforms') }}"
     data-search-url="{{ route('contacts.external-search') }}"
     data-preview-url="{{ route('contacts.external-preview') }}"
     data-create-url="{{ route('contacts.external-create') }}"
     data-link-url-base="{{ url('panel/helpdesk/contacts') }}"
     data-show-url-base="{{ url('panel/helpdesk/contacts') }}"
     data-hsm-templates-url="{{ route('contacts.hsm-templates') }}"
     data-inbox-url="{{ route('manager.helpdesk.conversations.index') }}">
    <div class="bv-modal-dialog ext-dialog">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box primary" id="extModalIcon"><i class="fas fa-magnifying-glass"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label" id="extModalLabel">Contactos · Alta</span>
                <div class="bv-modal-title"><span id="extModalTitle">Buscar en ERP o tienda</span></div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>

        <div class="bv-modal-body">

            {{-- Vista 1: búsqueda (mockup pieza 01) --}}
            <div id="extSearchView" class="ext-view-stack">
                {{-- Pestañas segmentadas Todas/ERP/PrestaShop; el <select> oculto
                     sigue siendo la fuente de verdad que lee runSearch(). --}}
                <div class="psc-seg" id="ext-seg"><button type="button" class="is-on" data-ext-seg="">Todas</button></div>
                <select class="d-none" id="ext-search-platform"><option value="">Todas</option></select>
                <div class="search-field">
                    <i class="fas fa-magnifying-glass"></i>
                    <input type="text" class="finput" id="ext-search-query" placeholder="Email, teléfono, NIF o nombre…">
                </div>
                <div id="ext-search-results">
                    <div class="bv-oc-empty">
                        <i class="fas fa-magnifying-glass"></i>
                        <div class="title">Buscar cliente</div>
                        <div>Escribe al menos 2 caracteres y pulsa Intro.</div>
                    </div>
                </div>
                <div class="minfo ext-audit-note"><i class="fas fa-shield-halved"></i>
                    <div>Búsqueda auditada y limitada por tasa: cada consulta queda registrada con el agente.</div>
                </div>
            </div>

            {{-- Vista 2: vista previa antes de importar (mockup pieza 02) --}}
            <div id="extPreviewView" class="ext-view-stack d-none">
                <div class="info-table ext-kv" id="ext-preview-customer"></div>
                <div id="ext-preview-orders"></div>
                <div class="minfo ext-audit-note"><i class="fas fa-eye"></i>
                    <div>Consulta de solo lectura: no crea nada en el Helpdesk hasta que se pulsa importar, y queda auditada por datos personales.</div>
                </div>
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

        {{-- Pie de la búsqueda: las acciones sobre el resultado seleccionado las
             pinta renderSearchFoot() (external-search-modal.js). --}}
        <div class="bv-modal-foot" id="extSearchFoot">
            <div id="ext-search-actions" class="ext-foot-stack"></div>
            <button class="btn-secondary" data-bv-close type="button">Cerrar</button>
        </div>

        <div class="bv-modal-foot bv-step-hidden" id="extPreviewFoot">
            <div id="ext-preview-actions" class="ext-foot-stack"></div>
            <button class="btn-secondary" id="ext-preview-back" type="button">Volver a los resultados</button>
        </div>

        <div class="bv-modal-foot bv-step-hidden" id="extHsmFoot">
            <button class="btn-primary" id="ext-hsm-send" type="button">Enviar plantilla</button>
            <button class="btn-secondary" id="ext-hsm-back" type="button">Volver a la ficha</button>
        </div>
    </div>
</div>
