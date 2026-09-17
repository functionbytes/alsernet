/* ============================================================
   HelpdeskErp · Motor del panel ERP del inbox
   Movido de Helpdesk core (conversations.js): carga lazy de los
   tabs ERP (Gestión/Finanzas/Fidelización) y el detalle de pedido.
   Corre una vez en document ready (equivalente al init original).

   El click en una fila de pedido ERP (.rp3-erp-order) abre el workspace
   de solo lectura definido en modals/order-workspace.blade.php
   (window.openErpOrderWorkspace) en vez de un modal propio: no hay
   detalle de pedido que pintar aquí, solo enrutar el click.
   ============================================================ */
(function () {
    'use strict';
    if (typeof jQuery === 'undefined') { return; }
    var $ = jQuery;
    // Idempotente: si el <script> se incluyera más de una vez, no re-registrar handlers.
    if (window.__hdErpInboxLoaded) { return; }
    window.__hdErpInboxLoaded = true;

    $(function () {
        var $aside = $('.bv-right');
        var csrf   = $('meta[name="csrf-token"]').attr('content');
        var esc    = function (s) { return $('<i>').text(s == null ? '' : String(s)).html(); };

            if (!$aside.data('has-erp')) { return; }

            var $erpTab    = $('#bv-erp-orders');
            var contextUrl = $erpTab.data('erp-context-url') || '';
            if (!contextUrl) { return; }

            var erpCache         = null;
            var erpFetching      = false;
            var erpDeferreds     = [];

            function showErpSkeleton(tabName) {
                var skRow = '<div class="bv-tab-sk-row"><div class="bv-sk-circle"></div><div class="bv-sk-body"><div class="bv-sk-line w60"></div><div class="bv-sk-line w40"></div></div></div>';
                $('[data-bv-tab-content="' + tabName + '"]').html(
                    '<div class="rp3-scroll"><div class="rp3-section">' + skRow + skRow + skRow + '</div></div>'
                );
            }

            function fetchErpContext(onDone) {
                if (erpCache) { if (onDone) { onDone(erpCache); } return; }
                if (erpFetching) { if (onDone) { erpDeferreds.push(onDone); } return; }
                erpFetching = true;
                if (onDone) { erpDeferreds.push(onDone); }

                var email      = $aside.data('lookup-email') || $aside.data('customer-email') || '';
                var custId     = $aside.data('customer-id') || '';
                var url        = contextUrl + '?email=' + encodeURIComponent(email) + (custId ? '&customer_id=' + custId : '');

                $.ajax({
                    url: url, method: 'GET',
                    headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                    success: function (resp) {
                        erpFetching = false;
                        if (resp.success && resp.data) { erpCache = resp.data; }
                        var cbs = erpDeferreds.splice(0);
                        cbs.forEach(function (cb) { cb(erpCache); });
                    },
                    error: function () {
                        erpFetching = false;
                        var cbs = erpDeferreds.splice(0);
                        cbs.forEach(function (cb) { cb(null); });
                    },
                });
            }

            function renderErpOrdersTab(data) {
                var $t    = $('#bv-erp-orders');
                var cust  = (data && data.customer) || {};
                var custId = cust.id || $t.data('erp-customer-id') || '';
                var orders = (data && Array.isArray(data.orders)) ? data.orders : [];
                var html  = '<div class="rp3-scroll">';
                if (orders.length) {
                    html += '<div class="rp3-section"><div class="rp3-sec-head">Pedidos ERP <span class="count">· ' + orders.length + '</span></div>';
                    var erpStatusLabel = { 0:'Pendiente', 1:'Confirmado', 2:'En preparación', 3:'Enviado', 5:'Entregado', 7:'Servido', 9:'Cancelado' };
                    orders.slice(0, 20).forEach(function (o) {
                        var oRef    = o.number ? ('#' + o.number) : (o.id ? ('#' + o.id) : '—');
                        var oStatus = (typeof o.status === 'number') ? (erpStatusLabel[o.status] || ('Estado ' + o.status)) : (o.status || 'Pedido');
                        var oDate   = o.date ? String(o.date).substring(0, 10) : '';
                        html += '<div class="rp3-order rp3-erp-order" data-order-platform="erp"' +
                            ' data-order-id="' + esc(String(o.id || '')) + '"' +
                            ' data-erp-customer-id="' + esc(String(custId)) + '"' +
                            ' data-order-ref="' + esc(oRef) + '"' +
                            ' data-order-status="' + esc(oStatus) + '"' +
                            ' data-order-date="' + esc(oDate) + '">' +
                            '<div class="thumb"><i class="fas fa-clipboard-list"></i></div>' +
                            '<div class="body">' +
                                '<div class="head"><span class="id">' + esc(oRef) + '</span>' +
                                    '<span class="st ' + window.bvOrderStatusClass(oStatus) + '">' + esc(oStatus) + '</span></div>' +
                                '<div class="meta">' + esc(oDate) +
                                    (o.observations ? ' · <span>' + esc(String(o.observations).substring(0, 40)) + '</span>' : '') +
                                '</div>' +
                            '</div></div>';
                    });
                    html += '</div>';
                } else {
                    html += '<div class="bv-tab-empty"><i class="fas fa-clipboard-list"></i>' +
                        '<div class="bv-tab-empty-title">Sin pedidos ERP</div>' +
                        '<div class="bv-tab-empty-sub">No hay pedidos en gestión</div></div>';
                }
                $t.html(html + '</div>');
                syncRightTabVisibility();
            }

            function renderErpFinanceTab(data) {
                var cust     = (data && data.customer) || {};
                var invoices = (data && Array.isArray(data.invoices)) ? data.invoices : [];
                var html     = '<div class="rp3-scroll">';
                if (cust.found && (cust.credit_limit != null || cust.balance != null || cust.payment_terms)) {
                    html += '<div class="rp3-section"><div class="rp3-sec-head">Balance</div><div class="rp3-stats">';
                    if (cust.credit_limit != null) {
                        html += '<div class="rp3-stat"><div class="lbl">Límite crédito</div><div class="val">' + esc(parseFloat(cust.credit_limit).toFixed(2)) + ' €</div></div>';
                    }
                    if (cust.balance != null) {
                        html += '<div class="rp3-stat"><div class="lbl">Saldo pendiente</div><div class="val">' + esc(parseFloat(cust.balance).toFixed(2)) + ' €</div></div>';
                    }
                    if (cust.payment_terms) {
                        html += '<div class="rp3-stat"><div class="lbl">Forma de pago</div><div class="val">' + esc(cust.payment_terms) + '</div></div>';
                    }
                    html += '</div></div>';
                }
                if (invoices.length) {
                    html += '<div class="rp3-section"><div class="rp3-sec-head">Facturas <span class="count">· ' + invoices.length + '</span></div>';
                    invoices.slice(0, 15).forEach(function (inv) {
                        var invRef  = inv.number ? ('#' + inv.number) : (inv.id ? ('#' + inv.id) : '—');
                        var invDate = inv.date ? String(inv.date).substring(0, 10) : '';
                        html += '<div class="rp3-order"><div class="thumb"><i class="fas fa-file-invoice"></i></div><div class="body">' +
                            '<div class="head"><span class="id">' + esc(invRef) + '</span>' +
                                (inv.status ? '<span class="st ' + window.bvOrderStatusClass(inv.status) + '">' + esc(inv.status) + '</span>' : '') +
                            '</div>' +
                            '<div class="meta">' + esc(invDate) + (inv.payment_method ? ' · ' + esc(inv.payment_method) : '') + '</div>' +
                        '</div></div>';
                    });
                    html += '</div>';
                }
                if (html === '<div class="rp3-scroll">') {
                    html += '<div class="bv-tab-empty"><i class="fas fa-coins"></i>' +
                        '<div class="bv-tab-empty-title">Sin datos financieros</div>' +
                        '<div class="bv-tab-empty-sub">No hay información financiera disponible</div></div>';
                }
                $('#bv-erp-finance').html(html + '</div>');
                syncRightTabVisibility();
            }

            function renderErpLoyaltyTab(data) {
                var cust = (data && data.customer) || {};
                var html = '<div class="rp3-scroll">';
                if (cust.found && cust.loyalty_points != null) {
                    html += '<div class="rp3-section"><div class="rp3-sec-head">Fidelización</div>' +
                        '<div class="rp3-stats"><div class="rp3-stat">' +
                            '<div class="lbl">Puntos acumulados</div>' +
                            '<div class="val">' + esc(String(cust.loyalty_points)) + '</div>' +
                        '</div></div></div>';
                } else {
                    html += '<div class="bv-tab-empty"><i class="fas fa-star"></i>' +
                        '<div class="bv-tab-empty-title">Sin fidelización</div>' +
                        '<div class="bv-tab-empty-sub">No hay puntos registrados</div></div>';
                }
                $('#bv-erp-loyalty').html(html + '</div>');
                syncRightTabVisibility();
            }

            function renderForTab(tabName, data) {
                if (!data) {
                    $('[data-bv-tab-content="' + tabName + '"]').html(
                        '<div class="bv-tab-empty"><i class="fas fa-triangle-exclamation"></i>' +
                        '<div class="bv-tab-empty-title">Error al cargar</div>' +
                        '<div class="bv-tab-empty-sub">No se pudo obtener el contexto ERP</div></div>'
                    );
                    syncRightTabVisibility();
                    return;
                }
                if (tabName === 'erp-orders') { renderErpOrdersTab(data); }
                else if (tabName === 'erp-finance') { renderErpFinanceTab(data); }
                else if (tabName === 'erp-loyalty') { renderErpLoyaltyTab(data); }
            }

            // Pre-warm context when panel loads — avoids wait on first tab click
            fetchErpContext(null);

            // Lazy-load on tab click
            // Usar capture phase nativo: el click en erp-orders/finance/loyalty siempre dispara primero
            document.addEventListener('click', function (e) {
                var $btn = $(e.target).closest('.bv-right-tab[data-bv-tab^="erp-"]');
                if (!$btn.length) { return; }
                var tabName = $btn.data('bv-tab');
                if (erpCache) {
                    renderForTab(tabName, erpCache);
                    return;
                }
                showErpSkeleton(tabName);
                fetchErpContext(function (data) { renderForTab(tabName, data); });
            }, true);

            // ERP order card click → abre el workspace de pedido ERP (solo lectura).
            // Captura para ganarle a cualquier otro handler de .rp3-erp-order que
            // pudiera registrarse más tarde (p.ej. el genérico de PrestaShop).
            document.addEventListener('click', function (e) {
                var $el = $(e.target).closest('.rp3-erp-order');
                if (!$el.length) { return; }
                if (typeof window.openErpOrderWorkspace !== 'function') { return; }

                var orderId = String($el.data('order-id') || '');
                var custId  = String($el.data('erp-customer-id') || $erpTab.data('erp-customer-id') || '');
                if (!orderId || !custId) { return; }

                e.stopImmediatePropagation();
                e.preventDefault();
                window.openErpOrderWorkspace(custId, orderId);
            }, true);
    });
})();

/**
 * Reintento manual de la búsqueda del cliente en gestión.
 *
 * El aviso "Este remitente no está en gestión" del panel derecho lo pinta
 * right-panel.blade.php cuando helpdesk_customers.erp_lookup_status dice que la
 * búsqueda automática ya corrió y falló. Aquí solo se relanza el trabajo
 * saltándose el enfriamiento.
 *
 * Delegación en document porque el panel derecho se sustituye entero al cambiar
 * de conversación (SPA pane).
 */
(function ($) {
    'use strict';

    if (!$) { return; }

    $(document).on('click', '[data-bv-erp-relink]', function () {
        var $btn = $(this);
        var $box = $btn.closest('.rsp-erp-missing');
        var url = $box.data('relink-url');

        if (!url || $btn.prop('disabled')) { return; }

        $btn.prop('disabled', true).text('Buscando…');

        $.ajax({
            url: url,
            method: 'POST',
            timeout: 15000,
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || ''
            }
        }).done(function (resp) {
            if (window.toastr) {
                window.toastr.success((resp && resp.message) || 'Buscando el cliente en gestión…');
            }

            // El trabajo es asíncrono: no se puede pintar el resultado aquí. Se
            // deja constancia de que se pidió y se invita a recargar, en vez de
            // fingir que ya está resuelto.
            $box.find('.rsp-erp-missing-text span').text('Búsqueda enviada. Recarga en unos segundos para ver el resultado.');
            $btn.text('Enviado');
        }).fail(function (xhr, textStatus) {
            var msg;

            if (textStatus === 'timeout') {
                msg = 'La petición tardó demasiado.';
            } else if (xhr && xhr.status === 429) {
                msg = 'Demasiados reintentos seguidos, espera un minuto.';
            } else {
                msg = (xhr && xhr.responseJSON && xhr.responseJSON.message) || 'No se pudo pedir la búsqueda.';
            }

            if (window.toastr) { window.toastr.error(msg); }

            $btn.prop('disabled', false).text('Reintentar');
        });
    });

}(window.jQuery));
