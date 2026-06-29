{{-- Modal: Vista previa / historial de conversaciones (contenido cargado dinámicamente por JS) --}}
<div class="bv-modal" data-bv-modal-name="preview-conv">
    <div class="modal w-lg modal--history">
        <div class="modal-head">
            <div class="modal-icon"><i class="fa-solid fa-clock-rotate-left"></i></div>
            <div class="modal-title-wrap">
                <div class="modal-label">CHAT · HISTORIAL</div>
                <div class="modal-title" id="prev-conv-subject">Conversaciones</div>
            </div>
            <button class="modal-close" data-bv-close><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body" id="prev-conv-body">
            <div class="bv-tab-loading"><i class="fas fa-spinner fa-spin"></i> Cargando…</div>
        </div>
        <div class="modal-foot">
            <a href="#" id="prev-conv-open-btn" class="btn btn-primary">
                Abrir conversación
            </a>
            <button class="btn btn-outline" data-bv-close>Cerrar</button>
        </div>
    </div>
</div>
