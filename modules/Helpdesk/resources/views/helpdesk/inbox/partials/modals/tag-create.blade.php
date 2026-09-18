{{-- Modal: Crear nueva etiqueta (#05 ve-tag-create) --}}
<div class="bv-modal" data-bv-modal-name="tag-create">
    <div class="bv-modal-dialog sm">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box"><i class="fas fa-tag"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">{{ __('helpdesk::helpdesk.inbox.modals.tag_create_label') }}</span>
                <div class="bv-modal-title">{{ __('helpdesk::helpdesk.inbox.modals.tag_create_title') }}</div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            <div class="bv-form-field">
                <label class="bv-form-label">{{ __('helpdesk::helpdesk.inbox.modals.tag_create_name') }} <span class="bv-req">*</span></label>
                <input id="tagCreateName" type="text" class="bv-form-input" placeholder="{{ __('helpdesk::helpdesk.inbox.modals.tag_create_name_placeholder') }}" autocomplete="off">
            </div>

            <div class="bv-form-field">
                <label class="bv-form-label">{{ __('helpdesk::helpdesk.inbox.modals.tag_create_color') }}</label>
                <div class="bv-color-picker" id="tagColorPicker">
                    @foreach([
                        '#dc2626','#ea580c','#d97706','#65a30d','#16a34a',
                        '#059669','#0891b2','#14b8a6','#7c3aed','#9333ea',
                        '#c026d3','#be185d','#78716c','#334155','#90bb13',
                    ] as $color)
                        <button type="button" class="bv-color-dot bv-dot-dyn {{ $loop->last ? 'on' : '' }}"
                                data-color="{{ $color }}"
                                style="--bv-dot-color: {{ $color }};">
                        </button>
                    @endforeach
                </div>
                <input type="hidden" id="tagCreateColor" value="#90bb13">
            </div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="bv-tag-create-confirm">{{ __('helpdesk::helpdesk.inbox.modals.tag_create_confirm') }}</button>
            <button class="btn-secondary" data-bv-close>{{ __('helpdesk::helpdesk.inbox.modals.cancel') }}</button>
        </div>
    </div>
</div>

@once
@push('scripts')
    {{-- JS extraido a public/vendor/helpdesk/modals/: se cachea en el navegador
         en vez de re-descargarse en cada render del inbox. --}}
    <script src="{{ asset('vendor/helpdesk/modals/tag-create.js') }}?v={{ @filemtime(public_path('vendor/helpdesk/modals/tag-create.js')) }}" defer></script>
@endpush
@endonce
