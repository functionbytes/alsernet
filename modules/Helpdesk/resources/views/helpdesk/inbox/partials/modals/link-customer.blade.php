{{-- Modal: Vincular a otro cliente --}}
{{-- data-empty-hint: texto traducido por atributo, para que el JS no necesite Blade. --}}
<div class="bv-modal" data-bv-modal-name="link-customer"
     data-empty-hint="{{ __('helpdesk::helpdesk.inbox.modals.link_customer_empty_hint') }}">
    <div class="modal w-md">
        <div class="modal-head">
            <div class="modal-icon"><i class="fa-solid fa-link"></i></div>
            <div class="modal-title-wrap">
                <div class="modal-label">{{ __('helpdesk::helpdesk.inbox.modals.link_customer_label') }}</div>
                <div class="modal-title">{{ __('helpdesk::helpdesk.inbox.modals.link_customer_title') }}</div>
            </div>
            <button class="modal-close" data-bv-close><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body">

            {{-- Paso 1: búsqueda --}}
            <div id="link-customer-step-search">
                <div class="minfo">
                    <i class="fa-solid fa-circle-info"></i>
                    <div>{{ __('helpdesk::helpdesk.inbox.modals.link_customer_info') }}</div>
                </div>

                <div class="search-field">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input id="link-customer-search" type="text" placeholder="{{ __('helpdesk::helpdesk.inbox.modals.link_customer_search_placeholder') }}" autocomplete="off">
                </div>

                <div class="bv-modal-list-label">{{ __('helpdesk::helpdesk.inbox.modals.link_customer_list_label') }}</div>
                <div id="link-customer-list">
                    <div class="nc-empty-hint">
                        <i class="fas fa-magnifying-glass"></i>
                        <span>{{ __('helpdesk::helpdesk.inbox.modals.link_customer_empty_hint') }}</span>
                    </div>
                </div>
            </div>

            {{-- Paso 2: confirmación --}}
            <div id="link-customer-step-confirm" class="bv-hidden">
                <div class="minfo">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <div>
                        {{ __('helpdesk::helpdesk.inbox.modals.link_customer_confirm_prefix') }}
                        <b id="link-customer-confirm-name"></b>{{ __('helpdesk::helpdesk.inbox.modals.link_customer_confirm_suffix') }}
                    </div>
                </div>
            </div>

        </div>
        <div class="modal-foot">
            <div id="link-customer-foot-search">
                <button class="btn btn-primary" id="link-customer-continue" disabled>{{ __('helpdesk::helpdesk.inbox.modals.link_customer_continue') }}</button>
                <button class="btn btn-outline" data-bv-close>{{ __('helpdesk::helpdesk.inbox.modals.cancel') }}</button>
            </div>
            <div id="link-customer-foot-confirm" class="bv-hidden">
                <button class="btn btn-primary" id="link-customer-submit">{{ __('helpdesk::helpdesk.inbox.modals.link_customer_confirm_button') }}</button>
                <button class="btn btn-outline" id="link-customer-back">{{ __('helpdesk::helpdesk.inbox.modals.cancel') }}</button>
            </div>
        </div>
    </div>
</div>

@once
@push('scripts')
    {{-- JS extraido a public/vendor/helpdesk/modals/. --}}
    <script src="{{ asset('vendor/helpdesk/modals/link-customer.js') }}?v={{ @filemtime(public_path('vendor/helpdesk/modals/link-customer.js')) }}" defer></script>
@endpush
@endonce
