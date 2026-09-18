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
    {{-- JS extraido a public/vendor/helpdesk/modals/: se cachea en el navegador
         en vez de re-descargarse en cada render del inbox. --}}
    <script src="{{ asset('vendor/helpdesk/modals/save-view.js') }}?v={{ @filemtime(public_path('vendor/helpdesk/modals/save-view.js')) }}" defer></script>
@endpush
@endonce
