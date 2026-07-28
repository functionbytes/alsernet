{{-- Modal: Buscar globalmente (⌘K) --}}
{{-- data-url-search: la ruta viaja por atributo para que el JS no necesite Blade. --}}
<div class="bv-modal" data-bv-modal-name="search-customer" data-url-search="{{ route('manager.helpdesk.search.global') }}">
    <div class="bv-modal-dialog">
        <div class="bv-modal-head">
            <div class="bv-modal-title"><i class="fas fa-magnifying-glass bv-modal-title-icon"></i> {{ __('helpdesk::helpdesk.inbox.modals.customer_search_title') }}</div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            <div class="mv4-search bv-search-mb0">
                <i class="fas fa-magnifying-glass"></i>
                <input type="text" id="csSearch" placeholder="{{ __('helpdesk::helpdesk.inbox.modals.customer_search_placeholder') }}" autocomplete="off">
                <button class="x bv-hidden" id="csClear"><i class="fas fa-xmark"></i></button>
            </div>

            <div class="mv4-search-meta">
                <span id="csCount">{{ __('helpdesk::helpdesk.inbox.modals.customer_search_type_to_search') }}</span>
                <span class="bv-spacer"></span>
            </div>

            <div class="mv4-search-list" id="csResults">
                {{-- Placeholder inicial --}}
                <div class="bv-gs-placeholder" id="csPlaceholder">
                    <i class="fas fa-magnifying-glass"></i>
                    <p>{{ __('helpdesk::helpdesk.inbox.modals.customer_search_min_chars') }}</p>
                </div>

                {{-- Sección: Clientes --}}
                <div class="bv-gs-section bv-hidden" id="gs-sec-customers">
                    <div class="bv-gs-section-title">
                        <i class="fas fa-users"></i> {{ __('helpdesk::helpdesk.inbox.modals.customer_search_section_customers') }}
                    </div>
                    <div id="gs-list-customers"></div>
                </div>

                {{-- Sección: Conversaciones --}}
                <div class="bv-gs-section bv-hidden" id="gs-sec-conversations">
                    <div class="bv-gs-section-title">
                        <i class="far fa-comments"></i> {{ __('helpdesk::helpdesk.inbox.modals.customer_search_section_conversations') }}
                    </div>
                    <div id="gs-list-conversations"></div>
                </div>

                {{-- Sección: Etiquetas --}}
                <div class="bv-gs-section bv-hidden" id="gs-sec-tags">
                    <div class="bv-gs-section-title">
                        <i class="fas fa-tag"></i> {{ __('helpdesk::helpdesk.inbox.modals.customer_search_section_tags') }}
                    </div>
                    <div id="gs-list-tags"></div>
                </div>

                <div class="mv4-empty bv-hidden" id="csEmpty">
                    <i class="far fa-circle-question"></i>
                    <p>{{ __('helpdesk::helpdesk.inbox.modals.customer_search_no_results') }}</p>
                    <a href="{{ route('manager.helpdesk.customers.create') }}" class="btn-primary bv-empty-create-btn">
                        <i class="fas fa-user-plus"></i> {{ __('helpdesk::helpdesk.inbox.modals.customer_search_create_customer') }}
                    </a>
                </div>
            </div>

        </div>
        <div class="bv-modal-foot">
            <a href="{{ route('manager.helpdesk.customers.create') }}" class="btn-secondary">{{ __('helpdesk::helpdesk.inbox.modals.customer_search_create_customer_footer') }}</a>
            <button class="btn-secondary" data-bv-close>{{ __('helpdesk::helpdesk.inbox.modals.cancel') }}</button>
        </div>
    </div>
</div>

@once
@push('scripts')
    {{-- JS extraido a public/vendor/helpdesk/modals/. --}}
    <script src="{{ asset('vendor/helpdesk/modals/customer-search.js') }}?v={{ @filemtime(public_path('vendor/helpdesk/modals/customer-search.js')) }}"></script>
@endpush
@endonce
