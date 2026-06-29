{{-- Modal: Historial de conversaciones (#29 history-modal) --}}
<div class="bv-modal" data-bv-modal-name="history">
    <div class="bv-modal-dialog bv-modal-dialog--history">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box primary"><i class="fas fa-clock-rotate-left"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">CHAT · HISTORIAL</span>
                <div class="bv-modal-title">
                    Conversaciones <span class="bv-chip" id="histModalCount"></span>
                </div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>

        <div class="bv-modal-body">
            <div class="bv-hist-search">
                <i class="fas fa-magnifying-glass"></i>
                <input type="text" id="histSearchInput" placeholder="Buscar en historial…">
            </div>
            <div class="bv-hist-filter-row">
                <span class="bv-media-pill bv-hist-pill on" data-bv-hist-filter="all">
                    Todas <span class="c" id="histCountAll">0</span>
                </span>
                <span class="bv-media-pill bv-hist-pill" data-bv-hist-filter="open">
                    Abiertas <span class="c" id="histCountOpen">0</span>
                </span>
                <span class="bv-media-pill bv-hist-pill" data-bv-hist-filter="closed">
                    Cerradas <span class="c" id="histCountClosed">0</span>
                </span>
            </div>
            <div class="bv-hist-list" id="histList">
                <div class="bv-cv-loading-msg"><i class="fas fa-spinner fa-spin"></i></div>
            </div>
        </div>

        <div class="bv-modal-foot">
            <button class="btn btn-secondary" data-bv-close>Cerrar</button>
        </div>
    </div>
</div>
