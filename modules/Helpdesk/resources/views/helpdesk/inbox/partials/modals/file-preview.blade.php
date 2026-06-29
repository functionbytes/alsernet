{{-- Modal: Lightbox de imagen / archivos (estilo WhatsApp) --}}
<div class="bv-modal bv-lightbox" data-bv-modal-name="file-preview">
    <div class="bv-lightbox-toolbar">
        <div class="bv-lightbox-author">
            <div class="bv-lightbox-avatar"><i class="far fa-user"></i></div>
            <div class="bv-lightbox-meta">
                <div class="bv-lightbox-author-name" id="bv-lightbox-author"></div>
                <div class="bv-lightbox-author-sub" id="bv-lightbox-sub"></div>
            </div>
        </div>
        <div class="bv-lightbox-actions">
            <span class="bv-lightbox-counter" id="bv-lightbox-counter"></span>
            <button type="button" class="bv-lightbox-btn" id="bv-lightbox-zoom-out" title="Reducir"><i class="fas fa-magnifying-glass-minus"></i></button>
            <button type="button" class="bv-lightbox-btn" id="bv-lightbox-zoom-in" title="Ampliar"><i class="fas fa-magnifying-glass-plus"></i></button>
            <button type="button" class="bv-lightbox-btn" id="bv-lightbox-rotate" title="Girar"><i class="fas fa-rotate-right"></i></button>
            <button type="button" class="bv-lightbox-btn" id="bv-lightbox-open" title="Abrir en pestaña nueva"><i class="fas fa-arrow-up-right-from-square"></i></button>
            <button type="button" class="bv-lightbox-btn" id="bv-lightbox-download" title="Descargar"><i class="fas fa-download"></i></button>
            <button type="button" class="bv-lightbox-btn bv-lightbox-close" data-bv-close title="Cerrar"><i class="fas fa-xmark"></i></button>
        </div>
    </div>

    <button type="button" class="bv-lightbox-nav bv-lightbox-prev" id="bv-lightbox-prev" aria-label="Anterior"><i class="fas fa-chevron-left"></i></button>
    <button type="button" class="bv-lightbox-nav bv-lightbox-next" id="bv-lightbox-next" aria-label="Siguiente"><i class="fas fa-chevron-right"></i></button>

    <div class="bv-lightbox-stage" id="bv-lightbox-stage">
        <img class="bv-lightbox-img" id="bv-lightbox-img" src="" alt=""
             onerror="this.classList.add('bv-lb-error'); this.removeAttribute('src');">
        <div class="bv-lb-error-state" id="bv-lb-error-state">
            <i class="fa-regular fa-image"></i>
            <span>No se puede cargar la imagen</span>
        </div>
        <video class="bv-lightbox-video" id="bv-lightbox-video" controls></video>
        <audio class="bv-lightbox-audio" id="bv-lightbox-audio" controls></audio>
        <div class="bv-lightbox-doc" id="bv-lightbox-doc">
            <div class="bv-lb-doc-icon"><i class="fas fa-file"></i></div>
            <div class="bv-lb-doc-name"></div>
            <div class="bv-lb-doc-size"></div>
            <button type="button" class="bv-lb-doc-dl" id="bv-lb-doc-dl">
                <i class="fas fa-download"></i> Descargar archivo
            </button>
        </div>
    </div>

    <div class="bv-lightbox-strip" id="bv-lightbox-strip"></div>
</div>
