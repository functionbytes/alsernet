{{-- Modal: Editar información de contacto --}}
<div class="bv-modal" data-bv-modal-name="edit-contact">
    <div class="bv-modal-dialog">
        <div class="bv-modal-head">
            <div class="bv-modal-title">Editar contacto</div>
            <button class="bv-modal-close" data-bv-close>
                <i class="fas fa-xmark"></i>
            </button>
        </div>
        <div class="bv-modal-body">
            <div style="display:flex;flex-direction:column;gap:14px">
                <div>
                    <label style="font-size:11px;font-weight:600;color:var(--bv-text-muted);text-transform:uppercase;letter-spacing:.08em">Nombre</label>
                    <input type="text" value="Carmen Pérez" style="width:100%;margin-top:4px;padding:8px 12px;border:1px solid var(--bv-border);border-radius:8px;font-size:13px;font-family:inherit">
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                    <div>
                        <label style="font-size:11px;font-weight:600;color:var(--bv-text-muted);text-transform:uppercase;letter-spacing:.08em">Email</label>
                        <input type="email" value="carmen.perez@email.com" style="width:100%;margin-top:4px;padding:8px 12px;border:1px solid var(--bv-border);border-radius:8px;font-size:13px;font-family:inherit">
                    </div>
                    <div>
                        <label style="font-size:11px;font-weight:600;color:var(--bv-text-muted);text-transform:uppercase;letter-spacing:.08em">Teléfono</label>
                        <input type="tel" value="+34 612 345 678" style="width:100%;margin-top:4px;padding:8px 12px;border:1px solid var(--bv-border);border-radius:8px;font-size:13px;font-family:inherit">
                    </div>
                </div>
                <div>
                    <label style="font-size:11px;font-weight:600;color:var(--bv-text-muted);text-transform:uppercase;letter-spacing:.08em">Empresa</label>
                    <input type="text" value="Boutique Rosa" style="width:100%;margin-top:4px;padding:8px 12px;border:1px solid var(--bv-border);border-radius:8px;font-size:13px;font-family:inherit">
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                    <div>
                        <label style="font-size:11px;font-weight:600;color:var(--bv-text-muted);text-transform:uppercase;letter-spacing:.08em">Idioma</label>
                        <select style="width:100%;margin-top:4px;padding:8px 12px;border:1px solid var(--bv-border);border-radius:8px;font-size:13px;font-family:inherit">
                            <option>Español</option>
                            <option>English</option>
                            <option>Français</option>
                        </select>
                    </div>
                    <div>
                        <label style="font-size:11px;font-weight:600;color:var(--bv-text-muted);text-transform:uppercase;letter-spacing:.08em">Zona horaria</label>
                        <select style="width:100%;margin-top:4px;padding:8px 12px;border:1px solid var(--bv-border);border-radius:8px;font-size:13px;font-family:inherit">
                            <option>Europe/Madrid</option>
                            <option>America/New_York</option>
                            <option>America/Bogota</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label style="font-size:11px;font-weight:600;color:var(--bv-text-muted);text-transform:uppercase;letter-spacing:.08em">Notas internas</label>
                    <textarea rows="3" placeholder="Notas privadas sobre este contacto…" style="width:100%;margin-top:4px;padding:8px 12px;border:1px solid var(--bv-border);border-radius:8px;font-size:13px;font-family:inherit;resize:vertical"></textarea>
                </div>
            </div>
        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary">Guardar cambios</button>
            <button class="btn-secondary" data-bv-close>Cancelar</button>
        </div>
    </div>
</div>
