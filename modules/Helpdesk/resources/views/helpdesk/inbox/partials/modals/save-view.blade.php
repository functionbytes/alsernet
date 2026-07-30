{{-- Modal: Guardar vista actual (inline, no usa el sistema bv-modal) --}}
<div id="bv-save-view-modal">
    <div class="bv-sv-dialog">
        <div class="bv-sv-head">
            <span class="bv-sv-title">
                <i class="fas fa-star"></i>{{ __('helpdesk::helpdesk.inbox.modals.filter_save_view') }}
            </span>
            <button id="bv-save-view-cancel" class="bv-sv-close" aria-label="{{ __('helpdesk::helpdesk.inbox.modals.order_close') }}">
                <i class="fas fa-xmark"></i>
            </button>
        </div>
        <p class="bv-sv-desc">
            {{ __('helpdesk::helpdesk.inbox.modals.save_view_desc') }}
        </p>
        <input type="text"
               id="bv-save-view-name"
               class="bv-field-input bv-sv-input"
               placeholder="{{ __('helpdesk::helpdesk.inbox.modals.save_view_name_placeholder') }}"
               maxlength="100">
        <div class="bv-sv-foot">
            <button id="bv-save-view-cancel-2" class="btn-secondary">{{ __('helpdesk::helpdesk.inbox.modals.cancel') }}</button>
            <button id="bv-save-view-confirm" class="btn-primary">
                <i class="fas fa-check"></i> {{ __('helpdesk::helpdesk.inbox.modals.save_view_save') }}
            </button>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
(function () {
    // conversations.js handles: open (#bv-save-view-btn), primary cancel (#bv-save-view-cancel), confirm (#bv-save-view-confirm)
    // We handle: second cancel button, backdrop click, keyboard shortcuts

    $(document).on('click', '#bv-save-view-cancel-2', function () {
        $('#bv-save-view-modal').css('display', 'none');
    });

    $(document).on('click', '#bv-save-view-modal', function (e) {
        if (e.target === this) { $(this).css('display', 'none'); }
    });

    $(document).on('keydown', '#bv-save-view-name', function (e) {
        if (e.key === 'Enter') { $('#bv-save-view-confirm').trigger('click'); }
        if (e.key === 'Escape') { $('#bv-save-view-modal').css('display', 'none'); }
    });
}());
</script>
@endpush
@endonce
