{{--
    Modal compartido para enviar una plantilla HSM de WhatsApp, tanto a un
    contacto individual (data-mode="single") como a varios seleccionados en
    el listado (data-mode="bulk"). El JS (send-hsm-modal.js) decide el modo
    leyendo los atributos data-* que la vista que incluye este partial setea
    antes de mostrar el modal.
--}}
<div class="modal fade ct-mdl" id="send-hsm-modal" tabindex="-1" aria-labelledby="sendHsmModalLabel" aria-hidden="true"
     data-templates-url="{{ route('contacts.hsm-templates') }}"
     data-single-url-base="{{ url('panel/helpdesk/contacts') }}"
     data-bulk-url="{{ route('contacts.bulk-send-hsm') }}">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <span class="ct-modal-icon me-2"><i class="fab fa-whatsapp"></i></span>
                <div class="flex-grow-1">
                    <div class="ct-modal-eyebrow" id="send-hsm-eyebrow">Contacto · WhatsApp</div>
                    <h5 class="modal-title" id="sendHsmModalLabel">Enviar plantilla</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body d-flex flex-column gap-2">
                {{-- Individual: fila "Destinatario" con el teléfono (mockup pieza 04). --}}
                <div class="ct-kv d-none" id="send-hsm-recipient">
                    <span class="k">Destinatario</span><span class="v mono" id="send-hsm-recipient-val"></span>
                </div>

                {{-- Solo en modo masivo (pieza 05): desglose de destinatarios válidos/omitidos,
                     calculado en el cliente a partir de las filas marcadas en el listado
                     (sin teléfono → no puede recibir WhatsApp; bloqueado → omitido).
                     SendBulkHsmTemplateJob no filtra por su cuenta. --}}
                <div id="send-hsm-bulk-info" class="d-none d-flex flex-column gap-2">
                    <div class="ct-kv is-good"><span class="k">Con teléfono válido</span><span class="v" id="send-hsm-valid-count">0 de 0</span></div>
                    <div class="ct-kv d-none" id="send-hsm-omitted-box"><span class="k">Se omiten</span><span class="v" id="send-hsm-omitted-val"></span></div>
                </div>

                <div>
                    <label class="ct-flabel" for="send-hsm-template">Plantilla aprobada</label>
                    <select class="ct-fselect" id="send-hsm-template">
                        <option value="">Cargando plantillas...</option>
                    </select>
                </div>

                <div class="ct-preview-card" id="send-hsm-preview-card">
                    <div class="ct-preview-card-hd">Vista previa</div>
                    <div class="ct-preview-card-body" id="send-hsm-preview"></div>
                </div>

                <div class="row g-2" id="send-hsm-vars"></div>

                <div class="psc-note psc-note--good" id="send-hsm-note-single">
                    <i class="fas fa-circle-info mt-1"></i>
                    <span>Se enviará en la conversación existente del contacto; si no tiene, se crea una nueva.</span>
                </div>
                <div class="psc-note psc-note--warn d-none" id="send-hsm-note-bulk">
                    <i class="fas fa-triangle-exclamation mt-1"></i>
                    <span>El envío se encola y no se puede deshacer. Se registra quién lo lanzó y a cuántos contactos.</span>
                </div>

                {{-- Solo en modo masivo. Siempre activo: SendBulkHsmTemplateJob crea o
                     reutiliza la conversación de cada contacto — no se finge la opción. --}}
                <label class="ct-fcheck d-none" id="send-hsm-bulk-conv-check">
                    <input type="checkbox" checked disabled>
                    <span class="ct-fcheck-grow">Crear conversación por contacto para seguir las respuestas</span>
                </label>
            </div>
            <div class="modal-footer">
                <button type="button" class="psc-btn psc-btn--primary" id="send-hsm-submit">
                    <span id="send-hsm-submit-label">Enviar plantilla</span>
                </button>
                <button type="button" class="psc-btn psc-btn--outline" data-bs-dismiss="modal">Cancelar</button>
            </div>
        </div>
    </div>
</div>
