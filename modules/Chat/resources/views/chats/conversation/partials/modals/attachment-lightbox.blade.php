{{-- No variables needed - content populated by openAttachmentViewer() JS function --}}
<div class="modal fade" id="attachmentLightboxModal" tabindex="-1" aria-hidden="true" style="background-color: rgba(0,0,0,0.5);">
    <div class="modal-dialog modal-dialog-centered" id="attachmentLightboxDialog" style="max-width: 95vw; width: 95vw;">
        <div class="modal-content border-0 shadow-lg" style="background-color: #ffffff; height: auto;">
            <div class="modal-header border-bottom bg-white py-2 px-4 d-flex align-items-center gap-2">
                <span id="attachmentLightboxIcon" class="fs-5"></span>
                <span id="attachmentLightboxName" class="fw-semibold text-dark text-truncate small flex-grow-1"></span>
                <button type="button" class="btn-close ms-2" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body p-0 bg-white d-flex align-items-center justify-content-center" id="attachmentLightboxBody" style="background-color: #ffffff; min-height: 500px;">
                {{-- Populated dynamically by openAttachmentViewer() --}}
            </div>
            <div class="modal-footer bg-white border-top d-flex flex-column gap-2 py-3 px-4">
                <a id="attachmentLightboxDownload" href="#" download class="btn btn-primary w-100">
                    Descargar
                </a>
                <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
