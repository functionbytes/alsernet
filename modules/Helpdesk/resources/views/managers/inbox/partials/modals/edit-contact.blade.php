{{-- Modal: Editar información de contacto --}}
<div class="bv-modal" data-bv-modal-name="edit-contact">
    <div class="bv-modal-dialog">
        <div class="bv-modal-head">
            <div class="bv-modal-title"><i class="fas fa-user-pen bv-modal-title-icon"></i> Editar contacto</div>
            <button class="bv-modal-close" data-bv-close>
                <i class="fas fa-xmark"></i>
            </button>
        </div>
        <div class="bv-modal-body">
            <div class="bv-contact-form">
                <div>
                    <label class="bv-field-label">Nombre</label>
                    <input type="text" value="Carmen Pérez" class="bv-field-input">
                </div>
                <div class="bv-contact-grid-2">
                    <div>
                        <label class="bv-field-label">Email</label>
                        <input type="email" value="carmen.perez@email.com" class="bv-field-input">
                    </div>
                    <div>
                        <label class="bv-field-label">Teléfono</label>
                        <input type="tel" value="+34 612 345 678" class="bv-field-input">
                    </div>
                </div>
                <div>
                    <label class="bv-field-label">Empresa</label>
                    <input type="text" value="Boutique Rosa" class="bv-field-input">
                </div>
                <div class="bv-contact-grid-2">
                    <div>
                        <label class="bv-field-label">Idioma</label>
                        <select class="bv-field-input">
                            <option>Español</option>
                            <option>English</option>
                            <option>Français</option>
                        </select>
                    </div>
                    <div>
                        <label class="bv-field-label">Zona horaria</label>
                        <select class="bv-field-input">
                            <option>Europe/Madrid</option>
                            <option>America/New_York</option>
                            <option>America/Bogota</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="bv-field-label">Notas internas</label>
                    <textarea rows="3" placeholder="Notas privadas sobre este contacto…" class="bv-field-input bv-textarea-resizable"></textarea>
                </div>
            </div>
        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary">Guardar cambios</button>
            <button class="btn-secondary" data-bv-close>Cancelar</button>
        </div>
    </div>
</div>
