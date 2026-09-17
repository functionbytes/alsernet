{{-- Modal: Visualizar conversación anterior (#30 conversation-viewer).
     Cabecera y pie usan el sistema de modales v2 (.modal/.modal-head/
     .modal-icon/.modal-label/.modal-title/.modal-foot), el mismo que
     "Escalar a ticket" — antes tenía su propia piel vieja
     (.bv-modal-dialog--cv) con la etiqueta "CHAT" en ámbar. El cuerpo
     (.bv-cv-*) se queda con sus clases propias: necesita la barra de
     contexto a sangre y el hilo de mensajes con scroll independiente,
     algo que el .modal-body genérico (padding fijo, sin scroll propio)
     no da. --}}
<div class="bv-modal" data-bv-modal-name="conversation-viewer">
    <div class="modal w-lg">
        <div class="modal-head">
            <div class="modal-icon"><i class="far fa-comments"></i></div>
            <div class="modal-title-wrap">
                <div class="modal-label">{{ __('helpdesk::helpdesk.inbox.modals.conversation_viewer_label_chat') }} {{ __('helpdesk::helpdesk.inbox.modals.conversation_viewer_label_suffix') }}</div>
                <div class="modal-title" id="cvModalTitle">{{ __('helpdesk::helpdesk.inbox.modals.conversation_viewer_title') }}</div>
            </div>
            <button class="modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>

        <div class="bv-cv-wrap" id="cvWrap">
            {{-- Loading --}}
            <div class="bv-cv-loading-msg" id="cvLoading">
                <i class="fas fa-spinner fa-spin"></i> {{ __('helpdesk::helpdesk.inbox.modals.conversation_viewer_loading') }}
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

        <div class="modal-foot">
            <button class="btn btn-primary" id="cvBtnOpen">
                {{ __('helpdesk::helpdesk.inbox.modals.conversation_viewer_open') }}
            </button>
            <button class="btn btn-outline" id="cvBtnBack" data-bv-close data-bv-open="history">
                {{ __('helpdesk::helpdesk.inbox.modals.conversation_viewer_back_to_history') }}
            </button>
            <button class="btn btn-outline" data-bv-close>{{ __('helpdesk::helpdesk.inbox.modals.conversation_viewer_close') }}</button>
        </div>
    </div>
</div>
