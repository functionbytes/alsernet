{{-- Modal: Visualizar conversación anterior (#30 conversation-viewer) --}}
<div class="bv-modal" data-bv-modal-name="conversation-viewer">
    <div class="bv-modal-dialog lg bv-modal-dialog--cv">
        <div class="bv-modal-head">
            <div class="bv-modal-icon-box"><i class="far fa-comments"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label"><span class="bv-cv-label-chat">CHAT</span> · HISTORIAL</span>
                <div class="bv-modal-title" id="cvModalTitle">Visualizar conversación</div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>

        <div class="bv-cv-wrap" id="cvWrap">
            {{-- Loading --}}
            <div class="bv-cv-loading-msg" id="cvLoading">
                <i class="fas fa-spinner fa-spin"></i> Cargando conversación…
            </div>

            {{-- Context bar --}}
            <div class="bv-cv-ctx-bar bv-hidden" id="cvCtxBar">
                <div class="bv-cv-ctx-av" id="cvCtxAv">??</div>
                <div class="bv-cv-ctx-meta">
                    <div class="bv-cv-ctx-nm" id="cvCtxNm">—</div>
                    <div class="bv-cv-ctx-sub" id="cvCtxSub">—</div>
                </div>
                <span class="bv-cv-ctx-status" id="cvCtxStatus">—</span>
            </div>

            {{-- Messages --}}
            <div class="bv-cv-messages bv-hidden" id="cvMessages"></div>
        </div>

        <div class="bv-modal-foot">
            <button class="btn btn-primary" id="cvBtnOpen">
                Abrir conversación
            </button>
            <button class="btn btn-secondary" id="cvBtnBack" data-bv-close data-bv-open="history">
                Volver al historial
            </button>
            <button class="btn btn-secondary" data-bv-close>Cerrar</button>
        </div>
    </div>
</div>
