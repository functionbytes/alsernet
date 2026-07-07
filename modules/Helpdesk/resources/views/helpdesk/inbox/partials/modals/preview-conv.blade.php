{{-- Modal: Vista previa / historial de conversaciones (contenido cargado dinámicamente por JS) --}}
<div class="bv-modal" data-bv-modal-name="preview-conv">
    <div class="modal w-lg modal--history">
        <div class="modal-head">
            <div class="modal-icon"><i class="fa-solid fa-clock-rotate-left"></i></div>
            <div class="modal-title-wrap">
                <div class="modal-label">{{ __('helpdesk::helpdesk.inbox.modals.preview_conv_label') }}</div>
                <div class="modal-title" id="prev-conv-subject">{{ __('helpdesk::helpdesk.inbox.modals.preview_conv_title') }}</div>
            </div>
            <button class="modal-close" data-bv-close><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body" id="prev-conv-body">
            <div class="bv-tab-loading"><i class="fas fa-spinner fa-spin"></i> {{ __('helpdesk::helpdesk.inbox.thread.loading') }}</div>
        </div>
        <div class="modal-foot">
            <a href="#" id="prev-conv-open-btn" class="btn btn-primary">
                {{ __('helpdesk::helpdesk.inbox.modals.preview_conv_open') }}
            </a>
            <button class="btn btn-outline" data-bv-close>{{ __('helpdesk::helpdesk.inbox.modals.order_close') }}</button>
        </div>
    </div>
</div>
