{{--
   Inbox slot del módulo HelpdeskPrestashop.
   Aporta los tabs de PrestaShop (Tienda/Devoluciones/Cupones/Direcciones) al
   panel derecho del inbox. Si el módulo se desactiva, estos tabs desaparecen.
   Datos reales vía PrestashopContextService (por email del cliente).
   Recibe: $rpCust
   El CSS del módulo (prestashop-inbox.css) se carga desde modals/cart-build.blade.php,
   que siempre está presente cuando el módulo está activo (cubre modales + este tab).
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
                                <div class="rp3-order" data-ps-order-open
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

{{-- Tab: Carritos (pedidos + carrito abandonado, vía PrestashopContextService) --}}
<div class="bv-right-tab-content bv-tab-hidden" data-bv-tab-content="carts" id="bv-carts-tab">
    @php
        $rawAttrs = $rpCust?->custom_attributes ?? [];
        // Support nested custom_attributes (PrestaShop widget sends custom_attributes inside custom_attributes)
        $nestedAttrs = is_array($rawAttrs['custom_attributes'] ?? null) ? $rawAttrs['custom_attributes'] : [];
        $cartsExternalOrders = (array) ($nestedAttrs['orders'] ?? $rawAttrs['orders'] ?? []);
        $cartData = $nestedAttrs['cart'] ?? $rawAttrs['cart'] ?? null;

        // Pedidos reales de PrestaShop (vía contexto API). Este slot solo se
        // incluye cuando $rpHasPs es true, así que el cliente ya está vinculado.
        if ($rpCust?->email) {
            try {
                $psCtxCarts = app(\Modules\HelpdeskPrestashop\Services\PrestashopContextService::class)->getCustomerContext($rpCust->email);
                foreach (($psCtxCarts['orders'] ?? []) as $pso) {
                    $cartsExternalOrders[] = [
                        'id' => $pso['id'] ?? null,
                        'reference' => $pso['reference'] ?? null,
                        'status' => $pso['state']['name'] ?? 'Pendiente',
                        'date' => $pso['placed_at'] ?? null,
                        'total' => $pso['totals']['total'] ?? 0,
                    ];
                }
            } catch (\Throwable $e) {
                // best-effort
            }
        }

        $cartsAllOrders = ! empty($cartsExternalOrders);
        $cartsTotalOrders = count($cartsExternalOrders);
    @endphp
    <div class="bv-source-actions bv-hidden" id="bv-orders-source-actions">
        <button class="btn btn-sm btn-link bv-refresh-source" data-bv-refresh-source="carts">
            <i class="fas fa-arrows-rotate"></i> {{ __('helpdesk::helpdesk.inbox.right.refresh_action') }}
        </button>
        <span class="bv-source-meta" data-bv-source-meta="carts"></span>
    </div>
    @if(!$cartsAllOrders && empty($cartData))
        <div class="bv-tab-empty">
            <i class="far fa-cart-shopping"></i>
            <div class="bv-tab-empty-title">{{ __('helpdesk::helpdesk.inbox.right.no_orders_title') }}</div>
            <div class="bv-tab-empty-sub">{{ __('helpdesk::helpdesk.inbox.right.no_orders_sub') }}</div>
        </div>
    @else
        <div class="rp3-scroll">
            @if($cartsAllOrders)
            <div class="rp3-section">
                <div class="rp3-sec-head">
                    {{ __('helpdesk::helpdesk.inbox.right.order_history') }}
                    <span class="count">· {{ $cartsTotalOrders }}</span>
                    <span class="spacer"></span>
                </div>
                @foreach($cartsExternalOrders as $extOrder)
                    @php
                        $extStatus = $extOrder['status'] ?? 'Pendiente';
                        $extStatusColor = match(strtolower($extStatus)) {
                            'entregado', 'completed', 'complete' => 'var(--success)',
                            'enviado', 'shipped' => 'var(--info)',
                            'cancelado', 'cancelled', 'canceled' => 'var(--danger)',
                            default => 'var(--warning)',
                        };
                        $extStatusClass = match(strtolower($extStatus)) {
                            'entregado', 'completed', 'complete' => 'is-completed',
                            'enviado', 'shipped' => 'is-shipped',
                            'cancelado', 'cancelled', 'canceled' => 'is-cancelled',
                            default => 'is-pending',
                        };
                        $extDateRaw = $extOrder['date'] ?? null;
                        try {
                            $extDate = $extDateRaw ? \Carbon\Carbon::parse($extDateRaw)->translatedFormat('d M') : '—';
                            $extDateFull = $extDateRaw ? \Carbon\Carbon::parse($extDateRaw)->translatedFormat('d M Y') : '—';
                        } catch (\Throwable $e) {
                            $extDate = '—';
                            $extDateFull = '—';
                        }
                        $extTotal = (float) ($extOrder['total'] ?? 0);
                        $extRef = $extOrder['reference'] ?? $extOrder['id'] ?? '—';
                        $extUrl = $extOrder['url'] ?? null;
                        $extProducts = [];
                        if (!empty($extOrder['products']) && is_array($extOrder['products'])) {
                            foreach ($extOrder['products'] as $p) {
                                $extProducts[] = ['name' => $p['name'] ?? 'Producto', 'qty' => $p['quantity'] ?? 1, 'price' => $p['price'] ?? 0];
                            }
                        }
                    @endphp
                    <div class="rp3-order" data-bv-modal="order" data-order-type="external"
                         data-order-id="{{ $extOrder['id'] ?? '' }}"
                         data-order-ref="#{{ $extRef }}"
                         data-order-status="{{ $extStatus }}"
                         data-order-status-color="{{ $extStatusColor }}"
                         data-order-date="{{ $extDateFull }}"
                         data-order-total="{{ number_format($extTotal, 2, ',', '.') }}"
                         data-order-products="{{ json_encode($extProducts) }}"
                         data-order-url="{{ $extUrl }}"
                         data-order-platform="prestashop"
                        >
                        <div class="thumb"><i class="fas fa-box"></i></div>
                        <div class="body">
                            <div class="head">
                                <span class="id">#{{ $extRef }}</span>
                                <span class="st {{ $extStatusClass }}">{{ $extStatus }}</span>
                            </div>
                            @if(!empty($extProducts))
                                <div class="t">{{ $extProducts[0]['name'] }}@if(count($extProducts) > 1) +{{ count($extProducts) - 1 }}@endif</div>
                            @endif
                            <div class="meta">
                                <b>{{ number_format($extTotal, 2, ',', '.') }} €</b>
                                <span>· {{ $extDate }}</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            @endif

            {{-- Carrito abandonado --}}
            @if(!empty($cartData) && is_array($cartData))
            @php
                $cartItemCount = count($cartData['products'] ?? []);
                $cartTotal     = (float) ($cartData['total'] ?? 0);
                $cartId        = $cartData['id'] ?? null;
                $cartAdminUrl  = $cartId && ($_rpPsStoreUrl ?? null)
                    ? rtrim($_rpPsStoreUrl, '/') . '/index.php?controller=AdminCarts&id_cart=' . (int) $cartId . '&viewcart=1'
                    : null;
            @endphp
            <div class="rp3-section">
                <div class="rp3-sec-head">
                    {{ __('helpdesk::helpdesk.inbox.right.abandoned_cart_heading') }}
                    @if($cartAdminUrl)
                        <a href="{{ $cartAdminUrl }}" target="_blank" rel="noopener"
                           class="rp3-cart-ext-link" title="{{ __('helpdesk::helpdesk.inbox.right.view_cart_prestashop_title') }}">
                            <i class="fas fa-arrow-up-right-from-square"></i>
                        </a>
                    @endif
                </div>
                <div class="rp3-cart" @if($cartId) data-cart-id="{{ $cartId }}" @endif>
                    <div class="hd">
                        <span class="dot"></span>
                        <i class="fas fa-cart-shopping"></i>
                        {{ $cartItemCount }} item{{ $cartItemCount === 1 ? '' : 's' }}
                    </div>
                    <div class="rp3-cart-items">
                        @foreach($cartData['products'] ?? [] as $product)
                        <div class="rp3-cart-item">
                            <div class="th"></div>
                            <div class="n">{{ $product['name'] ?? 'Producto' }}</div>
                            <div class="p">{{ number_format((float) ($product['price'] ?? 0), 2, ',', '.') }} €</div>
                        </div>
                        @endforeach
                    </div>
                    <div class="rp3-cart-total">
                        <span>Total</span>
                        <span>{{ number_format($cartTotal, 2, ',', '.') }} €</span>
                    </div>
                    <div class="rp3-cart-acts">
                        <button type="button"><i class="fas fa-tag"></i> Cupón 10%</button>
                        @if($cartAdminUrl)
                        <button type="button" class="rp3-cart-act-primary"
                            onclick="window.open('{{ $cartAdminUrl }}', '_blank')">
                            <i class="fas fa-link"></i> Recuperar
                        </button>
                        @else
                        <button type="button" class="rp3-cart-act-primary" disabled>
                            <i class="fas fa-link"></i> Recuperar
                        </button>
                        @endif
                    </div>
                </div>
            </div>
            @endif
        </div>
    @endif
</div>

@once

@push('scripts')
<script>
(function () {
    window.openPsOrdersModal = function () {
        var modal = new bootstrap.Modal(document.getElementById('psOrdersModal'));
        modal.show();
    };

    // Abrir el workspace de pedido PrestaShop (detalle real vía el bridge) al
    // pulsar un pedido de la lista. Cierra el modal-lista de bootstrap antes.
    $(document).on('click', '.rp3-order[data-ps-order-open]', function () {
        var id = $(this).data('order-id');
        if (!id) { return; }
        var lm = bootstrap.Modal.getInstance(document.getElementById('psOrdersModal'));
        if (lm) { lm.hide(); }
        if (typeof window.openPsOrderWorkspace === 'function') {
            window.openPsOrderWorkspace(id);
        }
    });

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
