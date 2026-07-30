{{-- Modal: Compartir tienda --}}
@php
    $stores = collect();
    if (class_exists(\Modules\Ecommerce\Models\StoreLocator::class)) {
        try {
            $stores = \Modules\Ecommerce\Models\StoreLocator::query()
                ->orderByDesc('is_primary')
                ->orderBy('name')
                ->limit(50)
                ->get();
        } catch (\Throwable $e) {
            $stores = collect();
        }
    }
@endphp

<div class="bv-modal" data-bv-modal-name="store-picker">
    <div class="bv-modal-dialog">
        <div class="bv-modal-head">
            <div class="bv-modal-title">
                <i class="fas fa-store"></i> {{ __('helpdesk::helpdesk.inbox.modals.store_picker_title') }}
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">
            @if($stores->isEmpty())
                <div class="bv-tab-empty">
                    <i class="fas fa-store-slash"></i>
                    <div class="bv-tab-empty-title">{{ __('helpdesk::helpdesk.inbox.modals.store_picker_empty_title') }}</div>
                    <div class="bv-tab-empty-sub">
                        {{ __('helpdesk::helpdesk.inbox.modals.store_picker_empty_sub') }}
                        <a href="{{ url('/panel/ecommerce/settings/store-locators') }}">{{ __('helpdesk::helpdesk.inbox.modals.store_picker_empty_link') }}</a>
                    </div>
                </div>
            @else
                <div class="bv-store-search">
                    <i class="fas fa-magnifying-glass"></i>
                    <input type="text" id="bv-store-search-input" placeholder="{{ __('helpdesk::helpdesk.inbox.modals.store_picker_search_placeholder') }}" autocomplete="off">
                </div>
                <div class="bv-store-list" id="bv-store-list">
                    @foreach($stores as $store)
                        <button class="bv-store-item"
                                data-bv-store-id="{{ $store->id }}"
                                data-bv-store-name="{{ $store->name }}"
                                data-bv-store-address="{{ trim(implode(', ', array_filter([$store->address, $store->city, $store->state, $store->country]))) }}"
                                data-bv-store-phone="{{ $store->phone }}"
                                data-bv-store-email="{{ $store->email }}">
                            <div class="bv-store-icon">
                                <i class="fas fa-store"></i>
                                @if($store->is_primary)
                                    <span class="bv-store-primary-badge" title="{{ __('helpdesk::helpdesk.inbox.modals.store_picker_primary') }}">
                                        <i class="fas fa-star"></i>
                                    </span>
                                @endif
                            </div>
                            <div class="bv-store-body">
                                <div class="bv-store-name">{{ $store->name }}</div>
                                <div class="bv-store-meta">
                                    @if($store->address)
                                        <span><i class="fas fa-location-dot"></i> {{ $store->city ?: $store->address }}</span>
                                    @endif
                                    @if($store->phone)
                                        <span><i class="fas fa-phone"></i> {{ $store->phone }}</span>
                                    @endif
                                </div>
                            </div>
                            <i class="fas fa-paper-plane bv-store-send-icon"></i>
                        </button>
                    @endforeach
                </div>
            @endif
        </div>
        <div class="bv-modal-foot">
            <button class="btn-secondary" data-bv-close>{{ __('helpdesk::helpdesk.inbox.modals.cancel') }}</button>
        </div>
    </div>
</div>
