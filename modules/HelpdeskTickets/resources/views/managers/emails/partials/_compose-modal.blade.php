{{-- Redactar / responder / reenviar — mismo componente bv-modal que el resto
     del módulo (mismo patrón data-bv-modal-name/data-htk-close documentado
     en el proyecto). --}}
<div class="bv-modal" data-bv-modal-name="eml-compose">
    <div class="bv-modal-dialog md">
        <div class="bv-modal-head">
            <div>
                <span class="bv-modal-label">Emails · Redactar</span>
                <div class="bv-modal-title">Nuevo email <span class="chip" id="eml-compose-ticket-chip"></span></div>
            </div>
            <button type="button" class="bv-modal-close" data-htk-close><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form id="eml-compose-form" class="bv-modal-body" enctype="multipart/form-data">
            <input type="hidden" name="ticket_id" id="eml-compose-ticket-id">

            <div class="eml-field">
                <label class="eml-flabel">Para <span class="eml-req">*</span></label>
                <input type="email" name="to" id="eml-compose-to" class="eml-finput" required>
            </div>

            <div class="eml-frow">
                <div class="eml-field">
                    <label class="eml-flabel">CC</label>
                    <input type="text" name="cc_raw" id="eml-compose-cc" class="eml-finput" placeholder="separados por coma">
                </div>
                <div class="eml-field">
                    <label class="eml-flabel">CCO</label>
                    <input type="text" name="bcc_raw" id="eml-compose-bcc" class="eml-finput" placeholder="separados por coma">
                </div>
            </div>

            <div class="eml-field">
                <label class="eml-flabel">Asunto <span class="eml-req">*</span></label>
                <input type="text" name="subject" id="eml-compose-subject" class="eml-finput" required maxlength="255">
            </div>

            <div class="eml-frow">
                <div class="eml-field">
                    <label class="eml-flabel">Categoría</label>
                    <select name="category_id" id="eml-compose-category" class="eml-fselect">
                        <option value="">Sin categoría</option>
                    </select>
                </div>
                <div class="eml-field">
                    <label class="eml-flabel">Programar envío</label>
                    <input type="datetime-local" name="scheduled_at" id="eml-compose-scheduled" class="eml-finput">
                </div>
            </div>

            <div class="eml-field">
                <label class="eml-flabel">Mensaje <span class="eml-req">*</span></label>
                <textarea name="body" id="eml-compose-body" class="eml-finput" required></textarea>
            </div>

            <div class="eml-field">
                <label class="eml-flabel">Adjuntos</label>
                <input type="file" name="attachments[]" id="eml-compose-attachments" multiple>
            </div>

            <label class="eml-check">
                <input type="checkbox" name="is_internal" id="eml-compose-internal" value="1">
                Aviso interno (no llega al cliente)
            </label>

            <p id="eml-compose-error" class="text-danger small mb-0" style="display:none"></p>
        </form>
        <div class="bv-modal-foot">
            <button type="submit" form="eml-compose-form" class="btn-primary" id="eml-compose-submit">Enviar</button>
            <button type="button" class="btn-secondary" data-htk-close>Cancelar</button>
        </div>
    </div>
</div>
