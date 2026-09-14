{{-- Modal: Respuesta rápida / plantillas (#63 ve-quick-reply) --}}
<div class="bv-modal" data-bv-modal-name="quick-reply">
    <div class="bv-modal-dialog md">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box"><i class="fas fa-bolt"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">{{ __('helpdesk::helpdesk.inbox.modals.snooze_eyebrow') }}</span>
                <div class="bv-modal-title">{{ __('helpdesk::helpdesk.inbox.modals.quick_reply_title') }}</div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            <div class="bv-modal-search bv-modal-search--hint">
                <i class="fas fa-magnifying-glass"></i>
                <input id="qrSearch" type="text" placeholder="{{ __('helpdesk::helpdesk.inbox.modals.quick_reply_search_placeholder') }}" autocomplete="off">
                <span class="bv-kbd">↑↓</span>
            </div>

            {{-- Category pills --}}
            <div class="bv-media-pills bv-x47" id="qrCategoryPills">
                <span class="bv-media-pill on" data-qr-cat="">{{ __('helpdesk::helpdesk.inbox.thread.all_label') }}</span>
            </div>

            {{-- List --}}
            <div class="bv-x48" id="qrList">
                <div class="bv-cv-loading-msg"><i class="fas fa-spinner fa-spin"></i></div>
            </div>

            {{-- Preview --}}
            <div id="qrPreview" style="display:none;margin-top:10px;padding:10px 12px;background:var(--bv-bg-subtle,#f9fafb);border-radius:6px;font-size:12.5px;color:var(--bv-text,#111827);white-space:pre-wrap"></div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="bv-qr-insert" disabled>{{ __('helpdesk::helpdesk.inbox.thread.insert_template') }}</button>
            <button class="btn-secondary" data-bv-close>{{ __('helpdesk::helpdesk.inbox.modals.order_close') }}</button>
        </div>
    </div>
</div>

@once
@push('scripts')
    {{-- JS extraido a public/vendor/helpdesk/modals/: se cachea en el navegador
         en vez de re-descargarse en cada render del inbox. --}}
    <script src="{{ asset('vendor/helpdesk/modals/quick-reply.js') }}?v={{ @filemtime(public_path('vendor/helpdesk/modals/quick-reply.js')) }}" defer></script>
@endpush
@endonce
