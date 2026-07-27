/* ============================================================
   HelpdeskErp · Motor del panel ERP del inbox
   Movido de Helpdesk core (conversations.js): carga lazy de los
   tabs ERP (Gestión/Finanzas/Fidelización) y el detalle de pedido.
   Corre una vez en document ready (equivalente al init original).
   Deps del core re-obtenidas aquí; setAddressTab (compartido con el
   modal de pedido de PrestaShop) se consume vía window.HDInbox.
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
        var setAddressTab = function (shipping, billing) {
            return (window.HDInbox && window.HDInbox.setAddressTab)
                ? window.HDInbox.setAddressTab(shipping, billing) : null;
        };

            if (!$aside.data('has-erp')) { return; }

            var $erpTab    = $('#bv-erp-orders');
            var contextUrl = $erpTab.data('erp-context-url') || '';
            var detailBase = $erpTab.data('erp-order-detail-url-base') || '';
            if (!contextUrl) { return; }

            var erpCache         = null;
            var erpFetching      = false;
            var erpDeferreds     = [];
            var erpDetailCache   = {};
            var erpDetailDefers  = {};
            var erpDetailPrewarm = {};

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

            // Fetch ERP order detail (reutilizable para prefetch y click)
            function fetchErpOrderDetail(orderId, custId, onDone) {
                if (!orderId || !detailBase || !custId) { if (onDone) { onDone(null); } return; }
                if (erpDetailCache[orderId]) { if (onDone) { onDone(erpDetailCache[orderId]); } return; }
                if (erpDetailPrewarm[orderId]) { if (onDone) { erpDetailPrewarm[orderId].push(onDone); } return; }
                erpDetailPrewarm[orderId] = onDone ? [onDone] : [];
                $.ajax({
                    url: detailBase + custId + '/' + orderId,
                    method: 'GET',
                    headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                    success: function (resp) {
                        if (resp.success && resp.data) { erpDetailCache[orderId] = resp.data; }
                        var cbs = (erpDetailPrewarm[orderId] || []).splice(0);
                        delete erpDetailPrewarm[orderId];
                        cbs.forEach(function (cb) { cb(resp.success ? resp.data : null); });
                    },
                    error: function () {
                        var cbs = (erpDetailPrewarm[orderId] || []).splice(0);
                        delete erpDetailPrewarm[orderId];
                        cbs.forEach(function (cb) { cb(null); });
                    },
                });
            }

            // Prefetch en hover — igual que PS
            $(document).on('mouseenter', '.rp3-erp-order', function () {
                var orderId = String($(this).data('order-id') || '');
                var custId  = String($(this).data('erp-customer-id') || $erpTab.data('erp-customer-id') || '');
                fetchErpOrderDetail(orderId, custId, null);
            });

            // ERP order card click → modal con datos ya cacheados o skeleton mientras carga
            document.addEventListener('click', function (e) {
                var $el = $(e.target).closest('.rp3-erp-order');
                if (!$el.length) { return; }

                var orderId = String($el.data('order-id') || '');
                var custId  = String($el.data('erp-customer-id') || $erpTab.data('erp-customer-id') || '');
                var ref     = $el.data('order-ref')    || '#—';
                var status  = $el.data('order-status') || '—';
                var date    = $el.data('order-date')   || '—';

                // Si ya está en caché: abrir con datos inmediatamente
                if (erpDetailCache[orderId]) {
                    showErpBasicModal(ref, status, date);
                    populateErpDetail(erpDetailCache[orderId]);
                    openOrderModal();
                    return;
                }

                // Si no: mostrar skeleton, abrir modal y cargar datos
                showErpBasicModal(ref, status, date);
                openOrderModal();
                fetchErpOrderDetail(orderId, custId, function (data) {
                    populateErpDetail(data);
                });
            }, true);

            function showErpBasicModal(ref, status, date) {
                var skLine = '<div class="bv-om-prod-skeleton"><div class="bv-sk-thumb"></div><div class="bv-sk-body"><div class="bv-sk-line w70"></div><div class="bv-sk-line w40"></div></div></div>';
                $('#bv-order-modal-chip').text(ref);
                $('#bv-order-modal-cust-name').text(($('.bv-cp-name-btn').first().text() || '—').trim());
                $('#bv-order-modal-order-id').text(ref);
                $('#bv-order-modal-reference').text(ref);
                $('#bv-order-modal-date').text(date || '—');
                $('#bv-order-modal-status').text(status || '—').attr('class', 'bv-ov-st ' + window.bvOrderStatusClass(status || ''));
                $('#bv-order-modal-payment').text('—');
                setAddressTab(null, null);
                $('#bv-order-modal-subtotal').text('—');
                $('#bv-order-modal-shipping-val').text('—');
                $('#bv-order-modal-total').text('—');
                $('#bv-order-modal-tax-row').addClass('bv-hidden');
                $('#bv-order-modal-discount-row').addClass('bv-hidden');
                $('#bv-order-modal-tracking-field').addClass('bv-hidden');
                $('#bv-order-modal-tracking-empty').removeClass('bv-hidden');
                $('#bv-order-modal-history-field').addClass('bv-hidden');
                $('#bv-order-modal-states-empty').removeClass('bv-hidden');
                $('#bv-order-modal-external-link').addClass('bv-hidden');
                $('[data-bv-modal-name="order"] [data-bv-om-tab]').removeClass('is-active');
                $('[data-bv-modal-name="order"] [data-bv-om-tab="info"]').addClass('is-active');
                $('[data-bv-modal-name="order"] [data-bv-om-panel]').removeClass('is-active');
                $('[data-bv-modal-name="order"] [data-bv-om-panel="info"]').addClass('is-active');
                $('#bv-order-modal-products').html(skLine + skLine);
                $('#bv-order-modal-products-count').text('');
            }

            function populateErpDetail(detail) {
                if (!detail) {
                    $('#bv-order-modal-products').html('<div class="bv-oc-empty"><i class="fas fa-box-open"></i><div class="title">Sin detalle</div></div>');
                    return;
                }
                var lines   = detail.lines || detail.lineas || detail.products || [];
                var total   = detail.total != null ? detail.total : (detail.importe || null);
                var payment = detail.payment_method || detail.formadepago || null;
                // Construir objetos de dirección desde los campos del ERP
                var rawAddr = detail.shipping_address || detail.address || detail.direccion || null;
                var erpShipping = rawAddr && typeof rawAddr === 'object'
                    ? rawAddr
                    : (rawAddr ? { address1: String(rawAddr), phone: detail.phone || detail.telefono || null } : null);
                var erpBilling = detail.billing_address || null;

                if (lines.length) {
                    var lHtml = '';
                    lines.forEach(function (l) {
                        var name  = l.name || l.descripcion || l.articulo || 'Artículo';
                        var qty   = parseInt(l.qty  || l.cantidad  || l.quantity || 1, 10);
                        var price = parseFloat(l.price || l.precio || l.importe  || 0);
                        var dto   = parseFloat(l.discount || l.dto || 0);
                        var linePrice = dto > 0 ? price * (1 - dto / 100) : price;
                        lHtml +=
                            '<div class="bv-om-prod-card">' +
                                '<div class="bv-om-pc-thumb"><i class="fas fa-box"></i></div>' +
                                '<div class="bv-om-pc-info">' +
                                    '<div class="bv-om-pc-name">' + esc(name) + '</div>' +
                                    '<div class="bv-om-pc-meta">\xd7' + qty +
                                        (price > 0 ? ' \xb7 ' + linePrice.toFixed(2) + ' €' : '') +
                                        (dto > 0 ? ' <span class="text-success">-' + dto + '%</span>' : '') +
                                    '</div>' +
                                '</div>' +
                                '<div class="bv-om-pc-price">' + (linePrice * qty).toFixed(2) + ' €</div>' +
                            '</div>';
                    });
                    $('#bv-order-modal-products').html(lHtml);
                    $('#bv-order-modal-products-count').text(lines.length + (lines.length === 1 ? ' artículo' : ' artículos'));
                } else {
                    $('#bv-order-modal-products').html('<div class="bv-oc-empty"><i class="fas fa-box-open"></i><div class="title">Sin productos registrados</div></div>');
                }
                if (total != null)   { $('#bv-order-modal-total').text(parseFloat(total).toFixed(2) + ' €'); }
                setAddressTab(erpShipping, erpBilling);
                if (payment)         { $('#bv-order-modal-payment').text(String(payment)); }
            }
    });
})();
