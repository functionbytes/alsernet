{{-- Modal: Centro de ayuda (#84 ve-help-center) --}}
{{-- data-url-*: las rutas viajan por atributo para que el JS no necesite Blade. --}}
<div class="bv-modal" data-bv-modal-name="help-center"
     data-url-categories="{{ route('manager.helpcenter.api.categories') }}"
     data-url-public="{{ route('public.helpcenter.index') }}">
    <div class="bv-modal-dialog md">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box primary"><i class="far fa-circle-question"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">{{ __('helpdesk::helpdesk.inbox.modals.help_center_label') }}</span>
                <div class="bv-modal-title"><span>{{ __('helpdesk::helpdesk.inbox.modals.help_center_title') }}</span></div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>

        <div class="bv-modal-body">
            <div class="search-field">
                <i class="fas fa-magnifying-glass"></i>
                <input type="text" id="hcSearch" placeholder="{{ __('helpdesk::helpdesk.inbox.modals.help_center_search_placeholder') }}" autocomplete="off">
            </div>

            <div class="bv-hc-grid" id="hcGrid">
                <div class="bv-oc-loading"><i class="fas fa-spinner fa-spin"></i> {{ __('helpdesk::helpdesk.inbox.modals.help_center_loading') }}</div>
            </div>
        </div>

        <div class="bv-modal-foot">
            <a href="{{ route('public.helpcenter.index') }}" target="_blank" rel="noopener" class="btn-primary">
                {{ __('helpdesk::helpdesk.inbox.modals.help_center_open_kb') }}
            </a>
            <button class="btn-secondary" data-bv-close>{{ __('helpdesk::helpdesk.inbox.modals.help_center_close') }}</button>
        </div>
    </div>
</div>

@once
@push('scripts')
    {{-- JS extraido a public/vendor/helpdesk/modals/. --}}
    <script src="{{ asset('vendor/helpdesk/modals/help-center.js') }}?v={{ @filemtime(public_path('vendor/helpdesk/modals/help-center.js')) }}" defer></script>
@endpush
@endonce
