{{-- Modal: Adjuntar ubicación --}}
<div class="bv-modal" data-bv-modal-name="attach-location">
    <div class="bv-modal-dialog">
        <div class="bv-modal-head">
            <div class="bv-modal-icon-box"><i class="fas fa-location-dot"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">{{ __('helpdesk::helpdesk.inbox.modals.attach_location_label') }}</span>
                <div class="bv-modal-title">{{ __('helpdesk::helpdesk.inbox.modals.attach_location_title') }}</div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            <div class="bv-minfo">
                <i class="fas fa-circle-info"></i>
                <div>{{ __('helpdesk::helpdesk.inbox.modals.attach_location_info') }}</div>
            </div>

            {{-- Tabs de tipo --}}
            <div class="bv-loc-tabs" id="attach-location-type">
                <button class="bv-loc-tab" data-loc-type="current">
                    <i class="fas fa-location-crosshairs"></i> {{ __('helpdesk::helpdesk.inbox.modals.attach_location_tab_current') }}
                </button>
                <button class="bv-loc-tab" data-loc-type="search">
                    <i class="fas fa-magnifying-glass"></i> {{ __('helpdesk::helpdesk.inbox.modals.attach_location_tab_search') }}
                </button>
                <button class="bv-loc-tab on" data-loc-type="saved">
                    <i class="far fa-bookmark"></i> {{ __('helpdesk::helpdesk.inbox.modals.attach_location_tab_saved') }}
                </button>
            </div>

            {{-- Buscar dirección (oculto por defecto) --}}
            <div class="bv-modal-search bv-hidden" id="attach-location-search-wrap">
                <i class="fas fa-magnifying-glass"></i>
                <input id="attach-location-search" type="text" placeholder="{{ __('helpdesk::helpdesk.inbox.modals.attach_location_search_placeholder') }}" autocomplete="off">
            </div>

            {{-- Ubicaciones guardadas --}}
            <div class="bv-loc-opts" id="attach-location-saved">
                <button class="bv-loc-opt on" data-loc-id="hq">
                    <div class="ic"><i class="fas fa-building"></i></div>
                    <div class="body">
                        <span class="t">{{ __('helpdesk::helpdesk.inbox.modals.attach_location_hq_name') }}</span>
                        <span class="s">{{ __('helpdesk::helpdesk.inbox.modals.attach_location_hq_address') }}</span>
                    </div>
                    <div class="radio"></div>
                </button>
                <button class="bv-loc-opt" data-loc-id="warehouse">
                    <div class="ic"><i class="fas fa-warehouse"></i></div>
                    <div class="body">
                        <span class="t">{{ __('helpdesk::helpdesk.inbox.modals.attach_location_warehouse_name') }}</span>
                        <span class="s">{{ __('helpdesk::helpdesk.inbox.modals.attach_location_warehouse_address') }}</span>
                    </div>
                    <div class="radio"></div>
                </button>
                <button class="bv-loc-opt" data-loc-id="store">
                    <div class="ic"><i class="fas fa-store"></i></div>
                    <div class="body">
                        <span class="t">{{ __('helpdesk::helpdesk.inbox.modals.attach_location_store_name') }}</span>
                        <span class="s">{{ __('helpdesk::helpdesk.inbox.modals.attach_location_store_address') }}</span>
                    </div>
                    <div class="radio"></div>
                </button>
            </div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="attach-location-send">{{ __('helpdesk::helpdesk.inbox.modals.attach_location_send') }}</button>
            <button class="btn-secondary" data-bv-close>{{ __('helpdesk::helpdesk.inbox.modals.cancel') }}</button>
        </div>
    </div>
</div>

@once
@push('scripts')
    {{-- JS extraido a public/vendor/helpdesk/modals/: se cachea en el navegador
         en vez de re-descargarse en cada render del inbox. --}}
    <script src="{{ asset('vendor/helpdesk/modals/attach-location.js') }}?v={{ @filemtime(public_path('vendor/helpdesk/modals/attach-location.js')) }}"></script>
@endpush
@endonce
