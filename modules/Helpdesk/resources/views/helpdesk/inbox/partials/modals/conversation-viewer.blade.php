{{-- Modal: Visualizar conversación anterior (#30 conversation-viewer) --}}
<div class="bv-modal" data-bv-modal-name="conversation-viewer">
    <div class="bv-modal-dialog lg bv-modal-dialog--cv">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box"><i class="far fa-comments"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label"><span class="bv-cv-label-chat">{{ __('helpdesk::helpdesk.inbox.modals.conversation_viewer_label_chat') }}</span> {{ __('helpdesk::helpdesk.inbox.modals.conversation_viewer_label_suffix') }}</span>
                <div class="bv-modal-title" id="cvModalTitle">{{ __('helpdesk::helpdesk.inbox.modals.conversation_viewer_title') }}</div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
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

        <div class="bv-modal-foot">
            <button class="btn-primary" id="cvBtnOpen">
                {{ __('helpdesk::helpdesk.inbox.modals.conversation_viewer_open') }}
            </button>
            <button class="btn-secondary" id="cvBtnBack" data-bv-close data-bv-open="history">
                {{ __('helpdesk::helpdesk.inbox.modals.conversation_viewer_back_to_history') }}
            </button>
            <button class="btn-secondary" data-bv-close>{{ __('helpdesk::helpdesk.inbox.modals.conversation_viewer_close') }}</button>
        </div>
    </div>
</div>
