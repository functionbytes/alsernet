{{--
   Inbox slot del módulo HelpdeskPrestashop.
   Aporta los tabs de PrestaShop (Tienda/Devoluciones/Cupones) al panel
   derecho del inbox. Si el módulo se desactiva, estos tabs desaparecen.
   Recibe: $rpCust
   El CSS del módulo (prestashop-inbox.css + prestashop-chat.css) se carga
   desde modals/cart-build.blade.php, que siempre está presente cuando el
   módulo está activo (cubre modales + este tab).

   Diseño: "Alvarez PrestaShop en el Chat" — pieza Unificado (orden de
   bloques: cliente + métricas, direcciones, carrito en vivo, pedidos,
   movimientos, acciones de chat) + piezas 13 (salud) y 14 (alertas).

   NOTA: no se invoca PrestashopContextService server-side aquí — el bridge
   puede tardar hasta 12s en cache miss y bloquearía el render del panel.
   Todo el tab sale de UNA llamada diferida (/ps/orders devuelve el
   customer.helpdesk_context completo) desde right-panel-prestashop-tabs.js.
--}}
@php
    $psLinkSearchUrl = ($rpCust && \Illuminate\Support\Facades\Route::has('manager.helpdesk.customers.integrations.search'))
        ? route('manager.helpdesk.customers.integrations.search', $rpCust) : '';
    $psLinkUrl = ($rpCust && \Illuminate\Support\Facades\Route::has('manager.helpdesk.customers.integrations.link'))
        ? route('manager.helpdesk.customers.integrations.link', $rpCust) : '';
@endphp

{{-- Tab: Tienda --}}
<div class="bv-right-tab-content bv-tab-hidden psc-store" data-bv-tab-content="ps-orders" id="bv-ps-orders"
     data-ps-orders-url="{{ $rpCust ? route('manager.helpdesk.customers.ps.orders', $rpCust) : '' }}"
     data-ps-order-detail-url="{{ url('panel/helpdesk/ps/orders') }}/"
     data-ps-link-search-url="{{ $psLinkSearchUrl }}"
     data-ps-link-url="{{ $psLinkUrl }}"
     data-ps-admin-url="{{ config('helpdeskprestashop.admin_url') }}"
     data-ps-img-fallback="{{ config('helpdeskprestashop.image_fallback_host') }}">

    {{-- Sin cliente en PrestaShop (puente sano, found=false): todo el tab
         colapsa a esta caja. Reutiliza la búsqueda y el vínculo manual de
         HelpdeskIntegration (mismo registro y auditoría que Contactos 360). --}}
    <div class="psc-card psc-link-box">
        <div class="psc-card-body">
            <div class="psc-state psc-state--compact">
                <i class="fas fa-user-slash"></i>
                <span class="t">{{ __('helpdeskprestashop::chat.states.no_customer') }}</span>
                <span class="s" id="ps-link-intro"></span>
            </div>
            @if($psLinkSearchUrl)
                <div class="psc-fieldrow">
                    <div class="psc-field psc-field--grow">
                        <input type="text" class="finput" id="ps-link-query" placeholder="Email, teléfono, nombre o ID" autocomplete="off">
                    </div>
                    <div class="psc-field">
                        <select id="ps-link-type">
                            <option value="email">Email</option>
                            <option value="phone">Teléfono</option>
                            <option value="name">Nombre</option>
                            <option value="id">ID PrestaShop</option>
                            <option value="nif">NIF / DNI</option>
                        </select>
                    </div>
                </div>
                <button type="button" class="psc-btn psc-btn--outline" id="ps-link-search">{{ __('helpdeskprestashop::chat.states.link_customer') }}</button>
                <div class="psc-link-results" id="ps-link-results"></div>
                @if($psLinkUrl)
                    <button type="button" class="psc-btn psc-btn--primary is-disabled" id="ps-link-submit" disabled>Vincular cliente</button>
                @endif
            @else
                <div class="psc-note psc-note--info">La búsqueda de clientes necesita la integración activa en Ajustes → Integraciones.</div>
            @endif
        </div>
    </div>

    <div class="psc-store-main">
        {{-- Alertas del cliente (pieza 14): las inyecta el JS, máximo tres.
             Sin alertas, el bloque no existe. --}}
        <div id="ps-alerts-wrap"></div>

        {{-- Cliente + métricas: se pintan por JS con los datos del bridge;
             hasta entonces, fallback mínimo con los datos locales. --}}
        <div class="psc-card" id="ps-customer-card">
            <div class="psc-card-body" id="ps-customer-body">
                <div class="psc-cust-id">
                    <span class="psc-avatar">{{ $rpCust ? mb_strtoupper(mb_substr($rpCust->name ?? $rpCust->email ?? '?', 0, 2)) : '?' }}</span>
                    <span class="psc-cust-id-body">
                        <span class="nm">{{ $rpCust?->name ?: ($rpCust?->email ?? '—') }}</span>
                        <span class="s">{{ __('helpdeskprestashop::chat.customer.title') }}</span>
                    </span>
                </div>
                <div class="psc-skel"></div>
            </div>
        </div>

        {{-- Direcciones por defecto (plegable, abierta por defecto) --}}
        <div class="psc-card">
            <button type="button" class="psc-card-head psc-card-head--toggle" data-psc-toggle="dirs" aria-expanded="true">
                {{ __('helpdeskprestashop::chat.addresses.defaults') }}
                <span class="psc-meta" id="ps-addr-summary">—</span>
                <i class="fas fa-chevron-down psc-chevron"></i>
            </button>
            <div class="psc-card-body psc-collapse" data-psc-collapse="dirs" id="ps-addr-defaults">
                <div class="psc-skel"></div>
            </div>
        </div>

        {{-- Cupones del cliente (plegable, mismos datos que el tab "Cupones") --}}
        <div class="psc-card">
            <button type="button" class="psc-card-head psc-card-head--toggle" data-psc-toggle="cups" aria-expanded="true">
                Cupones del cliente
                <span class="psc-meta" id="ps-vch-summary">—</span>
                <i class="fas fa-chevron-down psc-chevron"></i>
            </button>
            <div class="psc-card-body psc-collapse" data-psc-collapse="cups" id="ps-vch-inline">
                <div class="psc-skel"></div>
            </div>
        </div>

        {{-- Carrito en vivo --}}
        <div id="ps-cart-live-wrap">
            <div class="psc-live-hint psc-live-hint--muted">
                <span class="dot"></span>
                <span class="txt">{{ __('helpdeskprestashop::chat.states.loading') }}</span>
            </div>
        </div>

        {{-- Pedidos --}}
        <div class="psc-card">
            <div class="psc-card-head">
                <span class="psc-card-head-tt">
                    <span>{{ __('helpdeskprestashop::chat.customer.orders') }}</span>
                    <span class="s" id="ps-orders-sub"></span>
                </span>
                <span class="psc-count" id="ps-orders-count">—</span>
            </div>
            <div class="psc-card-body" id="ps-orders-body">
                <div class="psc-skel"></div>
                <div class="psc-skel"></div>
            </div>
        </div>

        {{-- Reembolsos (pieza 7): solo lectura; vacío = la tarjeta no existe --}}
        <div id="ps-refunds-wrap"></div>

        {{-- Últimos movimientos: todo lo fechado del contexto (carrito,
             pedidos, devoluciones, reembolsos, mensajes de la tienda) --}}
        <div class="psc-card">
            <div class="psc-card-head">
                Últimos movimientos
                <span class="psc-meta" id="ps-activity-meta"></span>
            </div>
            <div class="psc-card-body" id="ps-activity-body">
                <div class="psc-skel"></div>
            </div>
        </div>

        {{-- Respuestas con datos reales (pieza 17) --}}
        <div id="ps-replies-wrap"></div>

        {{-- Bloques de extensiones (js/ext/*.js, evento psc:store-rendered) --}}
        <div id="ps-ext-wrap" class="psc-store-main"></div>
    </div>

    {{-- Acciones de chat: insertan en el composer sin enviar --}}
    <div class="psc-card psc-actions-card">
        <div class="psc-card-body">
            <button type="button" class="psc-btn psc-btn--outline" onclick="openProductRecommend()">
                {{ __('helpdeskprestashop::chat.actions.recommend') }}
            </button>
            <button type="button" class="psc-btn psc-btn--outline" onclick="HDCommerce.open('ps-wishlist-send')">
                {{ __('helpdeskprestashop::chat.actions.wishlist') }}
            </button>
            @if(config('helpdeskprestashop.admin_url'))
                <a class="psc-btn psc-btn--outline" target="_blank" rel="noopener"
                   href="{{ config('helpdeskprestashop.admin_url') }}">
                    {{ __('helpdeskprestashop::chat.actions.backoffice') }}
                </a>
            @endif
        </div>
    </div>

    {{-- Frescura del dato y estado del puente (pieza 13) --}}
    <div class="psc-health" id="ps-health">
        <span class="dot"></span>
        <span id="ps-health-txt">{{ __('helpdeskprestashop::chat.states.loading') }}</span>
        <button type="button" class="psc-meta" data-psc-retry="store">
            {{ __('helpdeskprestashop::chat.health.refresh') }}
        </button>
    </div>

</div>

{{-- Modal: pedidos del cliente (Bootstrap, como el resto de modales del
     panel). Lo pinta prestashop-chat.js: buscador, chips de estado con
     conteo que filtran en cliente, una fila por pedido y "Enviar resumen". --}}
<div class="modal fade" id="psOrdersModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header ps-cart-modal-head">
                <span class="ic"><i class="fas fa-box"></i></span>
                <span class="ps-cart-modal-titlewrap">
                    <span class="lbl">PrestaShop &middot; Cliente</span>
                    <h5 class="modal-title" id="psOrdersModalTitle">Pedidos</h5>
                </span>
                <span class="psc-count" id="psOrdersModalCount"></span>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="ps-ord-tools">
                <div class="search-field">
                    <i class="fas fa-magnifying-glass"></i>
                    <input type="text" class="finput" id="psOrdSearch" placeholder="Buscar por número, referencia o producto…" autocomplete="off">
                </div>
                <div class="psc-chips" id="psOrdChips"></div>
            </div>
            <div class="modal-body" id="psOrdersModalBody">
                <div class="psc-skel"></div>
                <div class="psc-skel"></div>
            </div>
            <div class="modal-footer ps-modal-foot">
                <button type="button" class="psc-btn psc-btn--primary" id="psOrdSummary">Enviar resumen al chat</button>
                <button type="button" class="psc-btn psc-btn--outline" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

{{-- Modal: carrito en vivo (pieza 5). Contenido pintado por
     right-panel-prestashop-tabs.js; cada operación repinta el bloque de
     cupones y los totales sin cerrar el modal. --}}
<div class="modal fade" id="psCartModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header ps-cart-modal-head">
                <span class="ic"><i class="fas fa-cart-shopping"></i></span>
                <span class="ps-cart-modal-titlewrap">
                    <span class="lbl">PrestaShop &middot; Carrito en vivo</span>
                    <h5 class="modal-title" id="psCartModalTitle">Carrito</h5>
                    <span class="ps-cart-modal-meta" id="psCartModalMeta"></span>
                </span>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0" id="psCartModalBody">
                <div class="psc-card-body"><div class="psc-skel"></div></div>
            </div>
            <div class="modal-footer ps-modal-foot">
                <button type="button" class="psc-btn psc-btn--primary bv-hidden" id="psCartSummaryBtn" data-psc-cart-summary>Enviar resumen al chat</button>
                <button type="button" class="psc-btn psc-btn--outline" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

{{-- Modal: selector de direcciones del carrito ("Cambiar") --}}
<div class="modal fade" id="psAddressesModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header ps-cart-modal-head">
                <span class="ic"><i class="fas fa-location-dot"></i></span>
                <span class="ps-cart-modal-titlewrap">
                    <span class="lbl">PrestaShop &middot; Carrito</span>
                    <h5 class="modal-title" id="psAddressesTitle">Direcciones de envío</h5>
                </span>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0" id="psAddressesBody"></div>
            <div class="modal-footer ps-modal-foot">
                <button type="button" class="psc-btn psc-btn--outline" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

{{-- Tab: Devoluciones (pieza 1) — lo pinta renderPsReturnsTab() --}}
<div class="bv-right-tab-content bv-tab-hidden" data-bv-tab-content="ps-returns" id="bv-ps-returns">
    <div class="psc-card-body"><div class="psc-skel"></div><div class="psc-skel"></div></div>
</div>

{{-- Tab: Cupones (pieza 2) — lo pinta renderPsVouchersTab() --}}
<div class="bv-right-tab-content bv-tab-hidden" data-bv-tab-content="ps-vouchers" id="bv-ps-vouchers">
    <div class="psc-card-body"><div class="psc-skel"></div><div class="psc-skel"></div></div>
</div>

{{-- Tab: Carritos (pedidos vía custom_attributes + carrito abandonado).
     Sin botón en la barra de pestañas (ningún data-bv-tab="carts" existe en
     right-panel.blade.php) — se retiró junto con AssistedCartController y
     quedó huérfano. No se restaura aquí (fuera de alcance); solo se retira la
     llamada síncrona al bridge de PrestaShop que corría en cada render pese
     a ser inalcanzable. Los pedidos reales de PrestaShop ahora solo se
     consultan bajo demanda desde el tab "Tienda" (ver ps-orders más arriba). --}}
<div class="bv-right-tab-content bv-tab-hidden" data-bv-tab-content="carts" id="bv-carts-tab">
    @php
        $rawAttrs = $rpCust?->custom_attributes ?? [];
        // Support nested custom_attributes (PrestaShop widget sends custom_attributes inside custom_attributes)
        $nestedAttrs = is_array($rawAttrs['custom_attributes'] ?? null) ? $rawAttrs['custom_attributes'] : [];
        $cartsExternalOrders = (array) ($nestedAttrs['orders'] ?? $rawAttrs['orders'] ?? []);
        $cartData = $nestedAttrs['cart'] ?? $rawAttrs['cart'] ?? null;

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
    {{-- JS extraido a fichero propio: se cachea en el navegador en vez de
         re-descargarse en cada render del inbox. Fuente en
         modules/HelpdeskPrestashop/public/js/ — copiar a public/modules/ tras editar. --}}
    <script src="{{ asset('modules/helpdeskprestashop/js/right-panel-prestashop-tabs.js') }}?v={{ @filemtime(public_path('modules/helpdeskprestashop/js/right-panel-prestashop-tabs.js')) }}" defer></script>
@endpush
@endonce
