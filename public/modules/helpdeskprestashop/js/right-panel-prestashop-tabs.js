/*!
 * HelpdeskPrestashop · tab "Tienda" del panel derecho del inbox.
 *
 * Extraido de resources/views/inbox-slots/right-panel-prestashop-tabs.blade.php,
 * donde vivia como <script> inline que se re-descargaba en CADA carga del
 * inbox (el slot se incluye siempre desde
 * helpdesk/inbox/partials/right-panel.blade.php cuando el módulo está
 * activo). No tiene interpolacion Blade: la config llega por atributos
 * data-* y por window.HDCommerce, que define el core en
 * modals/_commerce-js.blade.php.
 *
 * OJO 1: depende de window.HDCommerce en tiempo de uso (dentro de
 * openPsAddressesModal), asi que debe cargarse DESPUES de _commerce-js — lo
 * garantiza el orden de @push('scripts') (_commerce-js se encola en
 * modals.blade.php, antes de que right-panel.blade.php incluya este slot).
 * OJO 2: la fuente es este fichero; asset() sirve desde
 * public/modules/helpdeskprestashop/js/ — hay que copiarlo alli tras editar.
 *
 * Pedidos PS (tile "Pedidos" + modal psOrdersModal): carga diferida al abrir
 * el tab "ps-orders", mismo patrón que erp-inbox.js — evita el bloqueo
 * síncrono del bridge de PrestaShop (hasta 12s) en el render del panel.
 */
(function () {
    var psOrdersCache = null;
    var psOrdersFetching = false;
    var psOrdersDeferreds = [];

    var PS_MONTHS = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sept', 'oct', 'nov', 'dic'];

    function esc(s) {
        return $('<span>').text(String(s || '')).html();
    }

    function escAttr(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function psMoney(n) {
        return (parseFloat(n) || 0).toLocaleString('es-ES', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function psFormatDate(iso, withYear) {
        if (!iso) { return '—'; }
        var d = new Date(iso);
        if (isNaN(d.getTime())) { return '—'; }
        var out = d.getDate() + ' ' + PS_MONTHS[d.getMonth()];
        return withYear ? out + ' ' + d.getFullYear() : out;
    }

    function ordersUrl() {
        return $('#bv-ps-orders').data('ps-orders-url') || '';
    }

    function fetchPsOrders(onDone) {
        if (psOrdersCache) { onDone(psOrdersCache); return; }

        var url = ordersUrl();
        if (!url) { onDone([]); return; }

        if (psOrdersFetching) { psOrdersDeferreds.push(onDone); return; }
        psOrdersFetching = true;
        psOrdersDeferreds.push(onDone);

        $.ajax({
            url: url,
            method: 'GET',
            dataType: 'json',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            },
        }).done(function (r) {
            psOrdersFetching = false;
            psOrdersCache = r.orders || [];
            var cbs = psOrdersDeferreds.splice(0);
            cbs.forEach(function (cb) { cb(psOrdersCache); });
        }).fail(function () {
            psOrdersFetching = false;
            var cbs = psOrdersDeferreds.splice(0);
            cbs.forEach(function (cb) { cb(null); });
        });
    }

    /* ── render: tile "Pedidos" del tab "Tienda" ── */
    function renderPsOrdersTile(orders) {
        var count = orders ? orders.length : 0;
        var html = count
            ? '<button class="ps-section-tile" type="button" onclick="openPsOrdersModal()">' +
                  '<span class="ps-st-icon"><i class="fas fa-box"></i></span>' +
                  '<span class="ps-st-body">' +
                      '<span class="ps-st-title">Pedidos</span>' +
                      '<span class="ps-st-sub">' + count + ' pedidos en PrestaShop</span>' +
                  '</span>' +
                  '<i class="fas fa-chevron-right ps-st-arrow"></i>' +
              '</button>'
            : '<div class="ps-section-tile ps-section-tile--empty">' +
                  '<span class="ps-st-icon"><i class="fas fa-box"></i></span>' +
                  '<span class="ps-st-body">' +
                      '<span class="ps-st-title">Pedidos</span>' +
                      '<span class="ps-st-sub">Sin pedidos registrados</span>' +
                  '</span>' +
              '</div>';
        $('#ps-orders-tile-wrap').html(html);
        if (window.bvSyncRightTabVisibility) { window.bvSyncRightTabVisibility(); }
    }

    /* ── render: fila de pedido dentro del modal psOrdersModal ── */
    function psOrderRowHtml(pso) {
        var status = (pso.state && pso.state.name) || pso.status || 'Pendiente';
        var statusClass = window.bvOrderStatusClass ? window.bvOrderStatusClass(status) : 'is-pending';
        var ref = pso.reference || pso.id || '—';

        // helpdesk_context devuelve 'lines'; customer.orders fallback devuelve 'products' (vacío)
        var rawLines = pso.lines || pso.products || [];
        var products = rawLines.map(function (pp) {
            return {
                name: pp.name || 'Producto',
                qty: parseInt(pp.quantity || 1, 10),
                price: parseFloat(pp.unit_price || pp.price || 0),
            };
        });

        // Usar totals.products cuando total=0 (pedidos con descuento completo)
        var total = parseFloat((pso.totals && pso.totals.total) || pso.total || 0);
        if (total <= 0 && pso.totals && pso.totals.products != null) {
            total = parseFloat(pso.totals.products);
        }
        var payment = pso.payment_method || (pso.payments && pso.payments[0] && pso.payments[0].payment_method) || '';
        var dateShort = psFormatDate(pso.placed_at, false);
        var dateFull = psFormatDate(pso.placed_at, true);

        var productsLine = products.length
            ? '<div class="t">' + esc(products[0].name) + (products.length > 1 ? ' +' + (products.length - 1) : '') + '</div>'
            : '';

        return '<div class="rp3-order" data-ps-order-open' +
            ' data-order-id="' + escAttr(pso.id || '') + '"' +
            ' data-order-ref="#' + escAttr(ref) + '"' +
            ' data-order-status="' + escAttr(status) + '"' +
            ' data-order-date="' + escAttr(dateFull) + '"' +
            ' data-order-total="' + escAttr(psMoney(total)) + '"' +
            ' data-order-products="' + escAttr(JSON.stringify(products)) + '"' +
            ' data-order-payment="' + escAttr(payment) + '"' +
            ' data-order-platform="prestashop"' +
            (pso.url ? ' data-order-url="' + escAttr(pso.url) + '"' : '') +
            '>' +
            '<div class="thumb"><i class="fas fa-box"></i></div>' +
            '<div class="body">' +
                '<div class="head">' +
                    '<span class="id">#' + esc(ref) + '</span>' +
                    '<span class="st ' + statusClass + '">' + esc(status) + '</span>' +
                '</div>' +
                productsLine +
                '<div class="meta">' +
                    '<b>' + psMoney(total) + ' €</b>' +
                    '<span>· ' + esc(dateShort) + '</span>' +
                '</div>' +
            '</div></div>';
    }

    function psOrdersModalHtml(orders) {
        if (!orders || !orders.length) {
            return '<div class="bv-tab-empty">' +
                '<i class="fas fa-store"></i>' +
                '<div class="bv-tab-empty-title">Sin pedidos en PrestaShop</div>' +
                '<div class="bv-tab-empty-sub">Este cliente no tiene pedidos en la tienda</div>' +
            '</div>';
        }
        return '<div class="rp3-scroll"><div class="rp3-section">' +
            orders.map(psOrderRowHtml).join('') +
        '</div></div>';
    }

    function psOrdersErrorHtml() {
        return '<div class="bv-tab-empty">' +
            '<i class="fas fa-triangle-exclamation"></i>' +
            '<div class="bv-tab-empty-title">Error al cargar</div>' +
            '<div class="bv-tab-empty-sub">No se pudieron obtener los pedidos de PrestaShop</div>' +
        '</div>';
    }

    window.openPsOrdersModal = function () {
        var modal = new bootstrap.Modal(document.getElementById('psOrdersModal'));
        var $body = $('#psOrdersModalBody');
        modal.show();

        if (psOrdersCache) {
            $body.html(psOrdersModalHtml(psOrdersCache));
            return;
        }

        $body.html('<div class="text-center py-4 text-muted"><i class="fas fa-spinner fa-spin fa-2x"></i></div>');
        fetchPsOrders(function (orders) {
            $body.html(orders === null ? psOrdersErrorHtml() : psOrdersModalHtml(orders));
        });
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

    // Lazy-load al abrir el tab "Tienda" — capture phase nativo, igual que
    // erp-inbox.js, porque el panel derecho se sustituye entero al cambiar
    // de conversación (SPA pane) y un listener delegado en document sobrevive
    // a ese reemplazo sin tener que re-registrarse.
    document.addEventListener('click', function (e) {
        if (!$(e.target).closest('.bv-right-tab[data-bv-tab="ps-orders"]').length) { return; }
        fetchPsOrders(renderPsOrdersTile);
    }, true);

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
})();
