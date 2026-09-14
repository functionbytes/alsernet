{{--
    Modal compartido para enviar una plantilla HSM de WhatsApp, tanto a un
    contacto individual (data-mode="single") como a varios seleccionados en
    el listado (data-mode="bulk"). El JS (send-hsm-modal.js) decide el modo
    leyendo los atributos data-* que la vista que incluye este partial setea
    antes de mostrar el modal.
--}}
<div class="modal fade" id="send-hsm-modal" tabindex="-1" aria-labelledby="sendHsmModalLabel" aria-hidden="true"
     data-templates-url="{{ route('contacts.hsm-templates') }}"
     data-single-url-base="{{ url('panel/helpdesk/contacts') }}"
     data-bulk-url="{{ route('contacts.bulk-send-hsm') }}">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <span class="ct-modal-icon me-2"><i class="fab fa-whatsapp"></i></span>
                <div class="flex-grow-1">
                    <div class="ct-modal-eyebrow">Contacto · WhatsApp</div>
                    <h5 class="modal-title" id="sendHsmModalLabel">Enviar plantilla</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p class="ct-meta mb-3" id="send-hsm-target-label"></p>

                <div class="mb-3">
                    <label class="form-label" for="send-hsm-template">Plantilla</label>
                    <select class="form-select" id="send-hsm-template">
                        <option value="">Cargando plantillas...</option>
                    </select>
                </div>

                <div id="send-hsm-vars"></div>

                <div class="mb-0">
                    <label class="form-label">Vista previa</label>
                    <div class="ct-note-box" id="send-hsm-preview"></div>
                </div>
            </div>
            <div class="modal-footer flex-column">
                <div class="w-100 ct-meta text-center mb-1">El envío abre una nueva conversación en la bandeja.</div>
                <button type="button" class="btn ct-btn-dark w-100 mb-2" id="send-hsm-submit">
                    <i class="fas fa-paper-plane me-1"></i> Enviar plantilla
                </button>
                <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
            </div>
        </div>
    </div>
</div>
