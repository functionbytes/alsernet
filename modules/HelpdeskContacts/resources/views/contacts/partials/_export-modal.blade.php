{{--
    Modal "Exportar contactos" (pieza #9 del mockup). El link "Descargar CSV"
    reconstruye su href por JS a partir de la URL base ya generada aquí
    (route('contacts.export', request()->query()) — misma query de filtros
    que ya usaba el enlace directo) añadiéndole columns[] según los checks
    marcados.
--}}
<div class="modal fade ct-mdl" id="contact-export-modal" tabindex="-1" aria-labelledby="exportModalLabel" aria-hidden="true"
     data-export-base-url="{{ route('contacts.export', request()->query()) }}">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <span class="ct-modal-icon me-2"><i class="fas fa-file-export"></i></span>
                <div class="flex-grow-1">
                    <div class="ct-modal-eyebrow">Contactos · Exportar</div>
                    <h5 class="modal-title" id="exportModalLabel">Exportar contactos</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="ct-kv mb-3">
                    <span class="k">Se exportará</span>
                    <span class="v" id="export-scope-value"
                          data-default="la vista actual · {{ number_format($customers->total()) }} contacto{{ $customers->total() === 1 ? '' : 's' }}">la vista actual · {{ number_format($customers->total()) }} contacto{{ $customers->total() === 1 ? '' : 's' }}</span>
                </div>

                <div class="d-flex flex-column gap-2 mb-3">
                    <label class="ct-fcheck">
                        <input type="checkbox" id="export-col-contact" checked disabled>
                        <span class="ct-fcheck-grow">Datos de contacto y empresa</span>
                    </label>
                    <label class="ct-fcheck">
                        <input type="checkbox" id="export-col-health" value="health" checked>
                        <span class="ct-fcheck-grow">Métricas de salud y valor</span>
                    </label>
                    <label class="ct-fcheck">
                        <input type="checkbox" id="export-col-external" value="external">
                        <span class="ct-fcheck-grow">Identificadores de ERP y tienda</span>
                    </label>
                </div>

                <div class="ct-note-box">
                    <i class="fas fa-shield-halved mt-1"></i>
                    <span>Exportar datos personales queda auditado. Con más de 5.000 filas se envía por email en lugar de descargarse.</span>
                </div>
            </div>
            <div class="modal-footer">
                <a href="#" id="contact-export-download-btn" class="psc-btn psc-btn--primary">Descargar CSV</a>
                <button type="button" class="psc-btn psc-btn--outline" data-bs-dismiss="modal">Cancelar</button>
            </div>
        </div>
    </div>
</div>
