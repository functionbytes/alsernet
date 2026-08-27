<div id="preview-pdf-modal" class="modal fade">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Previsualizacion del PDF</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted">
                    Hoja generada con los cambios que tienes ahora en el editor, aunque no los hayas guardado.
                </p>
                <div id="preview-pdf-status" class="small mb-2"></div>
                <iframe id="preview-pdf-frame" class="pricelabels-preview-frame" title="Previsualizacion del PDF"></iframe>
            </div>
            <div class="modal-footer">
                <div class="d-grid gap-2 w-100">
                    <a id="preview-pdf-open" class="btn btn-primary" target="_blank" rel="noopener">Abrir en una pestana nueva</a>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
</div>
