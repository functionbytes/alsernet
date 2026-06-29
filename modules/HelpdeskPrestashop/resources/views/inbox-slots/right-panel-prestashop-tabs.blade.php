{{--
   Inbox slot del módulo HelpdeskPrestashop.
   Aporta los tabs de PrestaShop (Tienda/Devoluciones/Cupones/Direcciones) al
   panel derecho del inbox. Si el módulo se desactiva, estos tabs desaparecen.
   Datos reales vía PrestashopContextService (por email del cliente).
   Recibe: $rpCust
--}}

@php
    $rpPsOrders = [];
    if ($rpCust?->email) {
        try {
            $psCtxTab = app(\Modules\HelpdeskPrestashop\Services\PrestashopContextService::class)->getCustomerContext($rpCust->email);
            $rpPsOrders = $psCtxTab['orders'] ?? [];
        } catch (\Throwable $e) {
            $rpPsOrders = [];
        }
    }
@endphp

{{-- Tab: Tienda (secciones tipo tile) --}}
<div class="bv-right-tab-content bv-tab-hidden" data-bv-tab-content="ps-orders" id="bv-ps-orders"
     data-ps-order-detail-url="{{ url('panel/helpdesk/ps/orders') }}/">

    <div class="ps-sections">

        {{-- Pedidos --}}
        @if(!empty($rpPsOrders))
        <button class="ps-section-tile" type="button" onclick="openPsOrdersModal()">
            <span class="ps-st-icon"><i class="fas fa-box"></i></span>
            <span class="ps-st-body">
                <span class="ps-st-title">Pedidos</span>
                <span class="ps-st-sub">{{ count($rpPsOrders) }} pedidos en PrestaShop</span>
            </span>
            <i class="fas fa-chevron-right ps-st-arrow"></i>
        </button>
        @else
        <div class="ps-section-tile ps-section-tile--empty">
            <span class="ps-st-icon"><i class="fas fa-box"></i></span>
            <span class="ps-st-body">
                <span class="ps-st-title">Pedidos</span>
                <span class="ps-st-sub">Sin pedidos registrados</span>
            </span>
        </div>
        @endif

        {{-- Carrito --}}
        <button class="ps-section-tile" type="button" onclick="openCartBuild()">
            <span class="ps-st-icon"><i class="fas fa-cart-shopping"></i></span>
            <span class="ps-st-body">
                <span class="ps-st-title">Carrito</span>
                <span class="ps-st-sub">Ver y editar carrito del cliente</span>
            </span>
            <i class="fas fa-chevron-right ps-st-arrow"></i>
        </button>

        {{-- Direcciones --}}
        <button class="ps-section-tile" type="button" onclick="openPsAddressesModal()">
            <span class="ps-st-icon"><i class="fas fa-location-dot"></i></span>
            <span class="ps-st-body">
                <span class="ps-st-title">Direcciones</span>
                <span class="ps-st-sub">Direcciones de envío guardadas</span>
            </span>
            <i class="fas fa-chevron-right ps-st-arrow"></i>
        </button>

        {{-- Recomendar producto --}}
        <button class="ps-section-tile" type="button" onclick="openProductRecommend()">
            <span class="ps-st-icon ps-st-icon--primary"><i class="fas fa-star"></i></span>
            <span class="ps-st-body">
                <span class="ps-st-title">Recomendar producto</span>
                <span class="ps-st-sub">Buscar y enviar al chat</span>
            </span>
            <i class="fas fa-chevron-right ps-st-arrow"></i>
        </button>

    </div>

</div>

{{-- Modal: lista de pedidos PS --}}
<div class="modal fade" id="psOrdersModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-box me-2"></i>Pedidos en PrestaShop</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                @if(!empty($rpPsOrders))
                    <div class="rp3-scroll">
                        <div class="rp3-section">
                            @foreach($rpPsOrders as $pso)
                                @php
                                    $psStatus = $pso['state']['name'] ?? ($pso['status'] ?? 'Pendiente');
                                    $psStatusClass = match(true) {
                                        str_contains(strtolower($psStatus), 'entregado'), str_contains(strtolower($psStatus), 'pago acept'), str_contains(strtolower($psStatus), 'complet') => 'is-completed',
                                        str_contains(strtolower($psStatus), 'envi'), str_contains(strtolower($psStatus), 'ship') => 'is-shipped',
                                        str_contains(strtolower($psStatus), 'cancel') => 'is-cancelled',
                                        default => 'is-pending',
                                    };
                                    $psRef = $pso['reference'] ?? $pso['id'] ?? '—';
                                    // helpdesk_context devuelve 'lines'; customer.orders fallback devuelve 'products' (vacío)
                                    $psRawLines = $pso['lines'] ?? $pso['products'] ?? [];
                                    $psProducts = [];
                                    foreach ($psRawLines as $pp) {
                                        $psProducts[] = [
                                            'name'  => $pp['name'] ?? 'Producto',
                                            'qty'   => (int) ($pp['quantity'] ?? 1),
                                            'price' => (float) ($pp['unit_price'] ?? $pp['price'] ?? 0),
                                        ];
                                    }
                                    // Usar totals.products cuando total=0 (pedidos con descuento completo)
                                    $psTotal = (float) ($pso['totals']['total'] ?? $pso['total'] ?? 0);
                                    if ($psTotal <= 0 && isset($pso['totals']['products'])) {
                                        $psTotal = (float) $pso['totals']['products'];
                                    }
                                    $psPayment = $pso['payment_method'] ?? $pso['payments'][0]['payment_method'] ?? '';
                                    try {
                                        $psDate = !empty($pso['placed_at']) ? \Carbon\Carbon::parse($pso['placed_at'])->translatedFormat('d M') : '—';
                                        $psDateFull = !empty($pso['placed_at']) ? \Carbon\Carbon::parse($pso['placed_at'])->translatedFormat('d M Y') : '—';
                                    } catch (\Throwable $e) { $psDate = '—'; $psDateFull = '—'; }
                                @endphp
                                <div class="rp3-order" data-bv-modal="order" data-order-type="external"
                                     data-order-id="{{ $pso['id'] ?? '' }}"
                                     data-order-ref="#{{ $psRef }}"
                                     data-order-status="{{ $psStatus }}"
                                     data-order-date="{{ $psDateFull }}"
                                     data-order-total="{{ number_format($psTotal, 2, ',', '.') }}"
                                     data-order-products="{{ json_encode($psProducts) }}"
                                     data-order-payment="{{ $psPayment }}"
                                     data-order-platform="prestashop"
                                     @if(!empty($pso['url'])) data-order-url="{{ $pso['url'] }}" @endif>
                                    <div class="thumb"><i class="fas fa-box"></i></div>
                                    <div class="body">
                                        <div class="head">
                                            <span class="id">#{{ $psRef }}</span>
                                            <span class="st {{ $psStatusClass }}">{{ $psStatus }}</span>
                                        </div>
                                        @if(!empty($psProducts))
                                            <div class="t">{{ $psProducts[0]['name'] }}@if(count($psProducts) > 1) +{{ count($psProducts) - 1 }}@endif</div>
                                        @endif
                                        <div class="meta">
                                            <b>{{ number_format($psTotal, 2, ',', '.') }} €</b>
                                            <span>· {{ $psDate }}</span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="bv-tab-empty">
                        <i class="fas fa-store"></i>
                        <div class="bv-tab-empty-title">Sin pedidos en PrestaShop</div>
                        <div class="bv-tab-empty-sub">Este cliente no tiene pedidos en la tienda</div>
                    </div>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

{{-- Modal: direcciones PS --}}
<div class="modal fade" id="psAddressesModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-location-dot me-2"></i>Direcciones de envío</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0" id="psAddressesBody">
                <div class="text-center py-4 text-muted">
                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

{{-- Tab: Devoluciones --}}
<div class="bv-right-tab-content bv-tab-hidden" data-bv-tab-content="ps-returns" id="bv-ps-returns">
    <div class="bv-tab-empty">
        <i class="fas fa-rotate-left"></i>
        <div class="bv-tab-empty-title">Sin devoluciones</div>
        <div class="bv-tab-empty-sub">No hay devoluciones registradas en PrestaShop</div>
    </div>
</div>

{{-- Tab: Cupones --}}
<div class="bv-right-tab-content bv-tab-hidden" data-bv-tab-content="ps-vouchers" id="bv-ps-vouchers">
    <div class="bv-tab-empty">
        <i class="fas fa-tag"></i>
        <div class="bv-tab-empty-title">Sin cupones</div>
        <div class="bv-tab-empty-sub">Este cliente no tiene cupones en PrestaShop</div>
    </div>
</div>

{{-- Tab: Direcciones --}}
<div class="bv-right-tab-content bv-tab-hidden" data-bv-tab-content="ps-addresses" id="bv-ps-addresses">
    <div class="bv-tab-empty">
        <i class="fas fa-location-dot"></i>
        <div class="bv-tab-empty-title">Sin direcciones</div>
        <div class="bv-tab-empty-sub">No hay direcciones registradas en PrestaShop</div>
    </div>
</div>

{{-- Tab: Carritos asistidos --}}
<div class="bv-right-tab-content bv-tab-hidden" data-bv-tab-content="carts" id="bv-carts-tab">
    <div class="bv-tab-loading"><i class="fas fa-spinner fa-spin"></i> Cargando carritos...</div>
</div>

@once
@push('styles')
<style>
.ps-sections { padding: 8px 0; display: flex; flex-direction: column; gap: 2px; }
.ps-section-tile {
    display: flex; align-items: center; gap: 12px;
    width: 100%; padding: 12px 14px;
    background: #fff; border: 1px solid #f0f0f0; border-radius: 8px;
    text-align: left; cursor: pointer; transition: background 0.15s, border-color 0.15s;
}
.ps-section-tile:hover { background: #f8fdf0; border-color: #90bb13; }
.ps-section-tile--empty { opacity: 0.5; cursor: default; }
.ps-section-tile--empty:hover { background: #fff; border-color: #f0f0f0; }
.ps-st-icon {
    width: 36px; height: 36px; flex-shrink: 0;
    background: #f5f5f5; border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.9rem; color: #6c757d;
}
.ps-st-icon--primary { background: #f0f7dc; color: #90bb13; }
.ps-st-body { flex: 1; min-width: 0; }
.ps-st-title { display: block; font-size: 0.85rem; font-weight: 600; color: #2d2d2d; }
.ps-st-sub { display: block; font-size: 0.73rem; color: #888; margin-top: 1px; }
.ps-st-arrow { color: #ccc; font-size: 0.75rem; flex-shrink: 0; }

/* Addresses modal */
.ps-addr-card { padding: 12px 16px; border-bottom: 1px solid #f5f5f5; }
.ps-addr-card:last-child { border-bottom: none; }
.ps-addr-alias { font-size: 0.7rem; font-weight: 700; text-transform: uppercase; color: #90bb13; margin-bottom: 3px; letter-spacing: 0.5px; }
.ps-addr-name { font-size: 0.85rem; font-weight: 600; }
.ps-addr-line, .ps-addr-city { font-size: 0.8rem; color: #555; }
.ps-addr-phone { font-size: 0.78rem; color: #888; margin-top: 3px; }
.ps-addr-phone .fas { margin-right: 4px; }
</style>
@endpush

@push('scripts')
<script>
(function () {
    window.openPsOrdersModal = function () {
        var modal = new bootstrap.Modal(document.getElementById('psOrdersModal'));
        modal.show();
    };

    window.openPsAddressesModal = function () {
        var $body = $('#psAddressesBody');
        $body.html('<div class="text-center py-4 text-muted"><i class="fas fa-spinner fa-spin fa-2x"></i></div>');

        var modal = new bootstrap.Modal(document.getElementById('psAddressesModal'));
        modal.show();

        var base = window.HDCommerce ? window.HDCommerce.base() : null;
        if (!base) {
            $body.html('<p class="text-center text-danger py-3">No hay cliente seleccionado.</p>');
            return;
        }

        $.ajax({
            url: base + '/ps/addresses',
            method: 'GET',
            dataType: 'json',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            },
        }).done(function (r) {
            var addresses = r.addresses || r.data || [];
            if (addresses.length) {
                var html = addresses.map(function (a) {
                    return '<div class="ps-addr-card">' +
                        '<div class="ps-addr-alias">' + esc(a.alias) + '</div>' +
                        '<div class="ps-addr-name">' + esc(a.full_name) + (a.company ? ' · ' + esc(a.company) : '') + '</div>' +
                        '<div class="ps-addr-line">' + esc(a.address1) + (a.address2 ? ', ' + esc(a.address2) : '') + '</div>' +
                        '<div class="ps-addr-city">' + esc(a.postcode) + ' ' + esc(a.city) + (a.country ? ', ' + esc(a.country) : '') + '</div>' +
                        (a.phone ? '<div class="ps-addr-phone"><i class="fas fa-phone"></i> ' + esc(a.phone) + '</div>' : '') +
                    '</div>';
                }).join('');
                $body.html(html);
            } else {
                $body.html('<p class="text-center text-muted py-3">No hay direcciones guardadas.</p>');
            }
        }).fail(function () {
            $body.html('<p class="text-center text-danger py-3">Error al cargar direcciones.</p>');
        });
    };

    function esc(s) {
        return $('<span>').text(String(s || '')).html();
    }
})();
</script>
@endpush
@endonce
