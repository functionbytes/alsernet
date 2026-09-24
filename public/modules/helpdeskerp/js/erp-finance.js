/*!
 * HelpdeskErp · modal Finanzas (bv-modal "erp-finance") — SOLO LECTURA
 *
 * Abre con [data-erp-open="finance"][data-erp-pane="balance|invoices|
 * payments|debts|delivery-notes|returns"] desde cualquier sitio (delegación
 * en document). Pestañas:
 *   - Balance: facturado / cobrado / pendiente + barra de riesgo.
 *   - Balance: además, mini-gráfico facturado vs cobrado por mes (barras
 *     CSS) desde …/erp/invoices/monthly, si hay datos.
 *   - Facturas: lista paginada con filtro por año y búsqueda por número →
 *     hoja con la factura, "Descargar copia (PDF)" (…/invoices/{id}/pdf; 409
 *     si falta el GRANT: botón deshabilitado con explicación) e "Insertar
 *     datos de la factura en el chat".
 *   - Albaranes: lista paginada → hoja con el albarán (y su pedido si llega).
 *   - Cobros, Deudas (con su albarán), Devoluciones y abonos.
 *
 * Hoy la mayoría llega "blocked" (sin GRANT en Oracle): se pinta el bloque
 * ámbar estándar (ErpChat.stateHtml) y el render de datos queda listo para
 * el día que el manager responda ok. Los detalles van en hojas internas
 * (ErpChat.sheet), nunca en un modal encima de otro.
 *
 * API: window.ErpFinance = { open(pane), deliveryNoteSheet($body, opts),
 *      render: {balance, invoices, deliveryNotes, payments, debts, returns} }
 *   (los render son puros: útiles para probar en consola con datos simulados)
 *
 * Fuente: modules/HelpdeskErp/public/js/ — copiar a public/modules/helpdeskerp/js/ tras editar.
 */
(function ($) {
    'use strict';

    if (!$ || window.ErpFinance) { return; }

    function boot(C) {
        if (window.ErpFinance) { return; }

        var NAME = 'erp-finance';
        var PAGE = 20;
        var PANES = ['balance', 'invoices', 'delivery-notes', 'payments', 'debts', 'returns'];
        var LABELS = {
            balance: 'Balance',
            invoices: 'Facturas',
            'delivery-notes': 'Albaranes',
            payments: 'Cobros',
            debts: 'Deudas',
            returns: 'Devoluciones y abonos',
        };
        var PERMS = {
            balance: ['finance'],
            invoices: ['finance'],
            'delivery-notes': ['orders', 'finance'],
            payments: ['finance'],
            debts: ['finance'],
            returns: ['orders', 'finance'],
        };
        var LISTS = { invoices: true, 'delivery-notes': true, payments: true, returns: true };

        var esc = C.esc;
        var escAttr = C.escAttr;
        var money = C.money;
        var num = C.num;

        var st = { pane: 'balance', cid: null, seq: 0, panes: {} };

        function $modal() { return $('[data-bv-modal-name="' + NAME + '"]'); }
        function $body() { return $('#ercFinBody'); }

        function allowed(pane) {
            return (PERMS[pane] || []).some(function (k) { return C.can(k); });
        }

        function allowedPanes() { return PANES.filter(allowed); }

        // El manager da `status` de facturas, cobros y deudas como bandera
        // (activo / anulado), no como código de estado de pedido: solo se
        // marca el anulado.
        function voidFlag(v) { return v === false || v === 0 || v === '0'; }

        function blank(v) { return v == null || (typeof v === 'string' && v.trim() === ''); }

        /* ── Apertura / cierre del modal ────────────────────────────── */

        function openModal() {
            // Nunca un modal encima de otro: se cierran los que estén abiertos.
            $('.bv-modal.on').not($modal()).removeClass('on');
            if (window.HDCommerce && typeof window.HDCommerce.open === 'function') {
                window.HDCommerce.open(NAME);
            } else {
                $modal().addClass('on');
                $('body').css('overflow', 'hidden');
                $(document).trigger('bv:modal:open', [NAME]);
            }
        }

        function closeModal() {
            C.sheet.close($body());
            if (window.HDCommerce && typeof window.HDCommerce.close === 'function') {
                window.HDCommerce.close(NAME);
            } else {
                $modal().removeClass('on');
                if (!$('.bv-modal.on').length) { $('body').css('overflow', ''); }
            }
        }

        function customerName() {
            var ov = C.cachedOverview();
            var sum = ov && ov.data && ov.data.sections && ov.data.sections.summary ? ov.data.sections.summary.data : null;
            if (sum) {
                var n = [sum.label, sum.surnames].filter(function (v) { return !blank(v); }).join(' ');
                if (n) { return n; }
            }
            var $r = $('.bv-right').first();
            return String($r.data('customer-name') || '') || 'Cliente';
        }

        function renderTitle() {
            var erpId = C.erpId();
            $('#ercFinTitle').html(esc(customerName()) + (erpId ? ' <span class="erc-fin-id">· ERP ' + esc(erpId) + '</span>' : ''));
        }

        function renderTabs() {
            var html = allowedPanes().map(function (p) {
                var ps = st.panes[p];
                var cls = 'erc-fin-tab' + (p === st.pane ? ' is-on' : '');
                var n = '';
                if (ps && ps.resp) {
                    if (ps.resp.state === 'blocked' || ps.resp.state === 'down') { cls += ' is-blocked'; }
                    if (LISTS[p] && ps.resp.state === 'ok' && ps.items.length) {
                        n = '<span class="n">' + ps.items.length + (ps.hasMore ? '+' : '') + '</span>';
                    }
                    if (p === 'debts' && ps.resp.state === 'ok') {
                        var cnt = debtList(ps.resp.data).length;
                        if (cnt) { n = '<span class="n">' + cnt + '</span>'; }
                    }
                }
                return '<button type="button" role="tab" class="' + cls + '" data-erc-fin-tab="' + escAttr(p) + '" aria-selected="' + (p === st.pane) + '">' +
                    '<span class="dot"></span>' + esc(LABELS[p]) + n +
                '</button>';
            }).join('');
            $('#ercFinTabs').html(html);
        }

        function open(pane) {
            var cid = C.customerId();
            if (cid !== st.cid) { st.cid = cid; st.panes = {}; }
            var panes = allowedPanes();
            st.pane = panes.indexOf(pane) !== -1 ? pane : (panes[0] || 'balance');

            openModal();
            C.sheet.close($body());
            renderTitle();
            renderTabs();

            if (!cid) { $body().html(C.stateHtml('nocustomer')); return; }
            if (!panes.length) { $body().html(C.stateHtml('forbidden', 'Finanzas')); return; }

            // El título usa el resumen (nombre + id ERP): si aún no está, se pide.
            if (!C.cachedOverview()) { C.overview().then(function () { if (st.cid === C.customerId()) { renderTitle(); } }); }

            show(st.pane, false);
        }

        function show(pane, force) {
            st.pane = pane;
            C.sheet.close($body());
            renderTabs();
            var ps = st.panes[pane];
            if (ps && ps.resp && !force) { renderPane(pane); return; }
            load(pane, { reset: true, force: !!force });
        }

        /* ── Datos ──────────────────────────────────────────────────── */

        function asList(data) {
            if (Array.isArray(data)) { return data; }
            if (data && Array.isArray(data.items)) { return data.items; }
            return [];
        }

        function load(pane, opts) {
            var o = opts || {};
            var cid = st.cid;
            var prev = st.panes[pane];
            var ps = (!o.reset && prev) ? prev : { resp: null, items: [], offset: 0, hasMore: false, year: prev ? prev.year : '', q: prev ? (prev.q || '') : '', loadingMore: false };
            var token = ++st.seq;
            ps.token = token;
            st.panes[pane] = ps;

            var params = {};
            if (LISTS[pane]) {
                params.limit = PAGE;
                params.offset = ps.offset;
                if (pane === 'invoices' && ps.year) { params.year = ps.year; }
            }
            if (o.force) { params.force = 1; }

            if (o.reset) {
                ps.resp = null;
                if (st.pane === pane) { $body().html(C.skeleton(LISTS[pane] ? 4 : 2, LISTS[pane] ? null : 'card')); }
            } else {
                ps.loadingMore = true;
                if (st.pane === pane) { renderPane(pane); }
            }

            return C.section(pane, params).then(function (resp) {
                if (ps.token !== token || st.cid !== cid || st.panes[pane] !== ps) { return; }
                ps.loadingMore = false;

                if (!o.reset && (!resp || resp.state !== 'ok')) {
                    C.toast('warning', (resp && resp.message) || 'No se pudieron cargar más resultados.');
                } else {
                    ps.resp = resp;
                    if (LISTS[pane] && resp.state === 'ok') {
                        var arr = asList(resp.data);
                        ps.items = ps.items.concat(arr);
                        ps.offset += arr.length;
                        ps.hasMore = resp.pagination ? !!resp.pagination.has_more : arr.length >= PAGE;
                        if (!arr.length) { ps.hasMore = false; }
                    }
                }

                renderTabs();
                if (st.pane === pane && $modal().hasClass('on')) { renderPane(pane); }
            });
        }

        /* ── Pintado de paneles ─────────────────────────────────────── */

        function paneState(pane, resp) {
            var r = $.extend({}, resp || { state: 'down' });
            r.retry = 'erp-finance:' + pane;
            if (pane === 'returns' && r.state === 'unavailable') {
                r = { state: 'unavailable', message: 'Gestión aún no expone devoluciones ni abonos.' };
            }
            return C.stateHtml(r, LABELS[pane]);
        }

        function renderPane(pane) {
            var ps = st.panes[pane];
            if (!ps || !ps.resp) { $body().html(C.skeleton(3)); return; }
            var resp = ps.resp;
            var html;

            if (resp.state !== 'ok') {
                html = paneState(pane, resp);
                if (resp.state === 'blocked') {
                    html += '<div class="erc-note erc-note--info"><span class="txt">' +
                        esc(LABELS[pane]) + ' se mostrará aquí en cuanto el DBA conceda el permiso de lectura en Oracle. No hace falta hacer nada más.' +
                    '</span></div>';
                }
                if (pane === 'invoices' && resp.state === 'invalid') { html = yearChips(ps.year) + html; }
                $body().html('<div class="erc-stack">' + html + '</div>');
                return;
            }

            switch (pane) {
                case 'balance': html = renderBalance(resp.data) + chartHost(); break;
                case 'invoices': html = yearChips(ps.year) + invoiceSearch(ps.q) + '<div id="ercFinInvList">' + renderInvoiceList(ps) + '</div>'; break;
                case 'delivery-notes': html = renderDeliveryNotes(ps.items) + moreBtn(ps); break;
                case 'payments': html = renderPayments(ps.items) + moreBtn(ps); break;
                case 'debts': html = renderDebts(resp.data); break;
                case 'returns': html = renderReturns(ps.items) + moreBtn(ps); break;
                default: html = '';
            }
            $body().html('<div class="erc-stack">' + html + fetchedAt(resp) + '</div>');
            if (pane === 'balance') { loadChart(false); }
        }

        function fetchedAt(resp) {
            if (!resp || !resp.fetched_at) { return ''; }
            return '<div class="erc-loading">Datos de Gestión · ' + esc(C.relative(resp.fetched_at)) + '</div>';
        }

        function moreBtn(ps) {
            if (!ps.hasMore) { return ''; }
            return '<div class="erc-more"><button type="button" class="erc-btn erc-btn--outline erc-fin-more" data-erc-fin-more' +
                (ps.loadingMore ? ' disabled' : '') + '>' + (ps.loadingMore ? 'Cargando…' : 'Cargar más') + '</button></div>';
        }

        function empty(title, msg, icon) {
            return C.stateHtml({ state: 'empty', message: msg || '', icon: icon || 'fas fa-inbox' }, title);
        }

        /* Balance */

        function riskHtml(risk) {
            var r = risk || {};
            var cur = num(r.current);
            var max = num(r.max_allowed);
            if (cur === null && max === null) { return ''; }
            if (!max || max <= 0) {
                return '<div class="erc-row"><span class="k">Riesgo actual</span><span class="v mono">' + esc(money(cur)) + '</span></div>';
            }
            var pct = Math.max(0, ((cur || 0) / max) * 100);
            var cls = 'erc-risk' + (pct > 90 ? ' erc-risk--over' : (pct > 60 ? ' erc-risk--mid' : ''));
            var cap = pct > 100
                ? 'Supera el máximo en ' + money((cur || 0) - max) + ' · ' + Math.round(pct) + '%'
                : 'Disponible ' + money(max - (cur || 0)) + ' · ' + Math.round(pct) + '% usado';
            return '<div class="' + cls + '">' +
                '<div class="erc-risk-head"><span>Riesgo</span><b>' + esc(money(cur)) + ' de ' + esc(money(max)) + '</b></div>' +
                '<span class="erc-risk-track"><span class="' + C.widthClass(pct) + '"></span></span>' +
                '<span class="erc-risk-cap">' + esc(cap) + '</span>' +
            '</div>';
        }

        function renderBalance(data) {
            var d = data || {};
            var b = d.balance || {};
            var pending = num(b.pending);
            var html = '<div class="erc-kpis">' +
                '<div class="erc-kpi"><span class="l">Facturado</span><span class="n">' + esc(money(b.invoiced)) + '</span></div>' +
                '<div class="erc-kpi"><span class="l">Cobrado</span><span class="n">' + esc(money(b.collected)) + '</span>' +
                    '<span class="d is-good">Pagos registrados</span></div>' +
                '<div class="erc-kpi"><span class="l">Pendiente</span><span class="n">' + esc(money(b.pending)) + '</span>' +
                    '<span class="d' + (pending !== null && pending <= 0 ? ' is-good' : '') + '">' +
                    (pending !== null && pending <= 0 ? 'Sin deuda pendiente' : 'Por cobrar') + '</span></div>' +
            '</div>';

            var risk = riskHtml(d.risk);
            if (risk) {
                html += '<div class="erc-card"><div class="erc-card-head">Riesgo comercial</div><div class="erc-card-body">' + risk + '</div></div>';
            }

            var pts = num(d.loyalty_points);
            if (pts !== null) {
                html += '<div class="erc-row erc-row--good"><span class="k">Puntos de fidelización</span><span class="v mono">' + esc(pts) + '</span></div>' +
                    (C.can('loyalty') ? '<div class="erc-fin-actions"><button type="button" class="erc-link" data-erp-open="loyalty" data-erp-pane="points">Ver movimientos de puntos</button></div>' : '');
            }

            html += '<div class="erc-fin-actions"><button type="button" class="erc-btn erc-btn--outline erc-btn--sm" data-erc-fin-insert-balance>Insertar saldo en el chat</button></div>';
            return html;
        }

        /* Mini-gráfico facturado vs cobrado por mes (barras CSS). La serie
           la arma el servidor (ErpInvoiceMonthlyService); si facturas y
           cobros están bloqueados, no se pinta nada. */

        function chartHost() { return '<div id="ercFinChart" class="erc-fin-chart-host"></div>'; }

        function loadChart(force) {
            var cid = st.cid;
            var url = C.base() ? C.base() + '/invoices/monthly' : null;
            if (!url) { return; }
            if (st.chart && st.chart.cid === cid && st.chart.resp && !force) { paintChart(st.chart.resp); return; }
            var token = ++st.seq;
            st.chart = { cid: cid, token: token, resp: null };
            $('#ercFinChart').html(C.skeleton(1, 'card'));
            C.request(url, force ? { force: 1 } : {}).then(function (resp) {
                if (!st.chart || st.chart.token !== token || st.cid !== cid) { return; }
                st.chart.resp = resp;
                paintChart(resp);
            });
        }

        function paintChart(resp) {
            var $host = $('#ercFinChart');
            if (!$host.length) { return; }
            $host.html(renderChart(resp));
        }

        function renderChart(resp) {
            var d = resp && resp.state === 'ok' ? resp.data : null;
            var months = d && Array.isArray(d.months) ? d.months : [];
            var hasInv = months.some(function (m) { return num(m.invoiced) !== null; });
            var hasCol = months.some(function (m) { return num(m.collected) !== null; });
            var max = months.reduce(function (acc, m) { return Math.max(acc, num(m.invoiced) || 0, num(m.collected) || 0); }, 0);
            if (!months.length || (!hasInv && !hasCol) || max <= 0) { return ''; }

            var h = function (v) {
                var n = num(v);
                if (n === null || n <= 0) { return 'erc-h-0'; }
                return 'erc-h-' + Math.max(5, Math.round((n / max) * 20) * 5);
            };
            var bars = months.map(function (m) {
                var title = m.label + ' ' + m.year + ' · Facturado ' + money(m.invoiced) + ' · Cobrado ' + money(m.collected);
                return '<div class="erc-fin-bar-col" title="' + escAttr(title) + '">' +
                    '<div class="erc-fin-bars">' +
                        (hasInv ? '<span class="erc-fin-bar erc-fin-bar--inv ' + h(m.invoiced) + '"></span>' : '') +
                        (hasCol ? '<span class="erc-fin-bar erc-fin-bar--col ' + h(m.collected) + '"></span>' : '') +
                    '</div>' +
                    '<span class="erc-fin-bar-lbl">' + esc(m.label) + '</span>' +
                '</div>';
            }).join('');

            var notes = [];
            if (!hasInv && d.invoices && d.invoices.state === 'blocked') { notes.push('Facturado: pendiente de permiso en Oracle.'); }
            if (!hasCol && d.payments && d.payments.state === 'blocked') { notes.push('Cobrado: pendiente de permiso en Oracle.'); }
            if (d.partial) { notes.push('Serie aproximada: solo se suman las facturas y cobros más recientes.'); }

            return '<div class="erc-card erc-fin-chart">' +
                '<div class="erc-card-head">Facturado y cobrado por mes<span class="erc-meta">Últimos ' + months.length + ' meses</span></div>' +
                '<div class="erc-card-body">' +
                    '<div class="erc-fin-legend">' +
                        (hasInv ? '<span class="erc-fin-key erc-fin-key--inv">Facturado</span>' : '') +
                        (hasCol ? '<span class="erc-fin-key erc-fin-key--col">Cobrado</span>' : '') +
                    '</div>' +
                    '<div class="erc-fin-bars-row" role="img" aria-label="Facturado y cobrado por mes">' + bars + '</div>' +
                    (notes.length ? '<div class="erc-fin-chart-note">' + esc(notes.join(' ')) + '</div>' : '') +
                '</div>' +
            '</div>';
        }

        function balanceText(data) {
            var b = (data || {}).balance || {};
            return 'Estado de tu cuenta:\n' +
                '• Facturado: ' + money(b.invoiced) + '\n' +
                '• Cobrado: ' + money(b.collected) + '\n' +
                '• Pendiente: ' + money(b.pending);
        }

        /* Facturas */

        function yearChips(current) {
            var y = new Date().getFullYear();
            var years = [y, y - 1, y - 2, y - 3];
            var cur = String(current || '');
            return '<div class="erc-fin-pane-head"><span class="erc-sec-label">Año</span></div>' +
                '<div class="erc-chips" role="group" aria-label="Filtrar facturas por año">' +
                    '<button type="button" class="erc-chip' + (cur === '' ? ' is-on' : '') + '" data-erc-fin-year="">Todos</button>' +
                    years.map(function (yr) {
                        return '<button type="button" class="erc-chip' + (cur === String(yr) ? ' is-on' : '') + '" data-erc-fin-year="' + yr + '">' + yr + '</button>';
                    }).join('') +
                '</div>';
        }

        function invoiceRef(inv) {
            var i = inv || {};
            var ref = [i.series, i.number].filter(function (v) { return !blank(v); }).join('-');
            if (i.year) { ref += (ref ? '/' : '') + i.year; }
            return ref || String(i.id || '');
        }

        function invoiceSearch(q) {
            return '<div class="erc-search erc-fin-search">' +
                '<i class="fas fa-magnifying-glass"></i>' +
                '<input type="search" id="ercFinInvQ" value="' + escAttr(q || '') + '" placeholder="Buscar por número de factura" aria-label="Buscar por número de factura" autocomplete="off">' +
            '</div>';
        }

        function normRef(v) { return String(v == null ? '' : v).toLowerCase().replace(/[\s\-\/.]/g, ''); }

        // Filtra sobre lo ya cargado (el manager no busca por número): con
        // "Cargar más" se amplía el conjunto sobre el que se busca.
        function matchInvoice(inv, q) {
            var n = normRef(q);
            if (!n) { return true; }
            return [inv.number, invoiceRef(inv), String(inv.series || '') + String(inv.number || ''), inv.id]
                .some(function (v) { return normRef(v).indexOf(n) !== -1; });
        }

        function renderInvoiceList(ps) {
            var q = String(ps.q || '').trim();
            var idx = [];
            ps.items.forEach(function (inv, i) { if (matchInvoice(inv, q)) { idx.push(i); } });
            if (q && !idx.length) {
                return empty('Sin coincidencias', 'Ninguna factura cargada contiene «' + q + '».' + (ps.hasMore ? ' Carga más para buscar en las anteriores.' : ''), 'fas fa-magnifying-glass') + moreBtn(ps);
            }
            return renderInvoices(ps.items, idx) +
                (q ? '<div class="erc-loading">' + idx.length + ' de ' + ps.items.length + ' facturas cargadas</div>' : '') +
                moreBtn(ps);
        }

        function renderInvoices(items, only) {
            if (!items.length) { return empty('Sin facturas', 'No hay facturas para este filtro.', 'fas fa-file-invoice'); }
            var list = Array.isArray(only) ? only : items.map(function (x, i) { return i; });
            return '<div class="erc-list">' + list.map(function (idx) {
                var inv = items[idx];
                var meta = [inv.payment_method, C.codeLabel(inv.warehouse_description, inv.warehouse, 'Almacén')].filter(Boolean).join(' · ');
                return '<button type="button" class="erc-item is-link" data-erc-fin-invoice="' + idx + '">' +
                    '<span class="ic"><i class="fas fa-file-invoice"></i></span>' +
                    '<span class="info">' +
                        '<span class="top"><span class="ref">' + esc(invoiceRef(inv)) + '</span>' +
                            (inv.simplified ? '<span class="erc-tag erc-tag--closed">Simplificada</span>' : '') +
                            (voidFlag(inv.status) ? '<span class="erc-tag erc-tag--blocked">Anulada</span>' : '') +
                        '</span>' +
                        '<span class="t">' + esc(C.date(inv.date, true)) + '</span>' +
                        (meta ? '<span class="m">' + esc(meta) + '</span>' : '') +
                    '</span>' +
                    '<span class="end"><span class="act">Ver</span></span>' +
                '</button>';
            }).join('') + '</div>';
        }

        function invoiceText(inv, detail) {
            var d = detail || {};
            var i = $.extend({}, inv || {}, d);
            var ref = invoiceRef(i);
            var t = d.totals || {};
            var total = num(t.lines_total_with_taxes != null ? t.lines_total_with_taxes : t.total);
            var base = num(t.lines_total_bi);
            var dt = d.date || (inv || {}).date;
            return [(i.simplified ? 'Factura simplificada: ' : 'Factura: ') + ref,
                dt ? 'Fecha: ' + C.date(dt, true) : '',
                base !== null && total !== null ? 'Base imponible: ' + money(base) + ' · IVA: ' + money(total - base) : '',
                total !== null ? 'Total: ' + money(total) : '',
                !blank(i.payment_method) ? 'Forma de pago: ' + i.payment_method : '',
                voidFlag(i.status) ? 'Estado: anulada' : ''].filter(Boolean).join('\n');
        }

        var PDF_WHY = {
            loading: '',
            blocked: 'La copia en PDF estará disponible cuando Oracle conceda el permiso de lectura de facturas.',
            unavailable: 'Gestión no tiene el detalle de esta factura, así que no se puede generar la copia.',
            down: 'Gestión no responde ahora mismo. Inténtalo de nuevo en un momento.',
        };

        // pdf: 'ok' | 'loading' | 'blocked' | 'unavailable' | 'down'
        function invoiceFoot(pdf, why) {
            var ok = pdf === 'ok';
            var msg = ok ? '' : (why || PDF_WHY[pdf] || '');
            return '<button type="button" class="erc-btn erc-btn--primary" data-erc-fin-insert-doc>Insertar datos de la factura en el chat</button>' +
                '<button type="button" class="erc-btn erc-btn--outline" data-erc-fin-pdf' + (ok ? '' : ' disabled aria-disabled="true"') + '>Descargar copia (PDF)</button>' +
                (msg ? '<span class="erc-fin-pdf-why' + (pdf === 'blocked' || pdf === 'down' ? ' is-warn' : '') + '">' + esc(msg) + '</span>' : '') +
                '<button type="button" class="erc-btn erc-btn--outline" data-erc-sheet-close>Volver</button>';
        }

        function pdfUrl(id) {
            return C.base() ? C.base() + '/invoices/' + encodeURIComponent(id) + '/pdf' : null;
        }

        // Descarga por XHR (no un enlace): si el servidor responde JSON (409
        // sin GRANT, 404, 503…) se explica en la hoja en vez de abrir una
        // pestaña con el error.
        function downloadPdf($btn) {
            var $sheet = $btn.closest('.erc-sheet');
            var id = $sheet.data('ercInvoiceId');
            var url = pdfUrl(id);
            if (!url || $btn.prop('disabled')) { return; }

            $btn.prop('disabled', true).text('Generando PDF…');
            var xhr = new XMLHttpRequest();
            xhr.open('GET', url, true);
            xhr.responseType = 'blob';
            xhr.setRequestHeader('Accept', 'application/pdf, application/json');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.timeout = 60000;

            var fail = function (state, message) {
                if (!$.contains(document, $sheet[0])) { return; }
                var keep = state === 'down';
                C.sheet.update($sheet, { foot: invoiceFoot(keep ? 'ok' : state, message) });
                if (keep) { C.toast('warning', message || PDF_WHY.down); }
            };

            xhr.onload = function () {
                var type = String(xhr.getResponseHeader('Content-Type') || '');
                if (xhr.status === 200 && type.indexOf('application/pdf') !== -1) {
                    var name = 'copia-factura.pdf';
                    var cd = String(xhr.getResponseHeader('Content-Disposition') || '');
                    var m = /filename="?([^";]+)"?/i.exec(cd);
                    if (m) { name = m[1]; }
                    var href = URL.createObjectURL(xhr.response);
                    var a = document.createElement('a');
                    a.href = href;
                    a.download = name;
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    setTimeout(function () { URL.revokeObjectURL(href); }, 30000);
                    $btn.prop('disabled', false).text('Descargar copia (PDF)');
                    C.toast('success', 'Copia de la factura descargada');
                    return;
                }
                // Respuesta JSON de error: se lee el blob como texto.
                var reader = new FileReader();
                reader.onload = function () {
                    var j = {};
                    try { j = JSON.parse(String(reader.result || '{}')); } catch (e) { j = {}; }
                    var st2 = xhr.status === 409 ? (j.state === 'blocked' ? 'blocked' : 'unavailable')
                        : (xhr.status === 404 ? 'unavailable'
                        : (xhr.status === 403 ? 'unavailable' : 'down'));
                    var msg = j.message || (xhr.status === 403 ? 'Sin permiso para descargar facturas de Gestión.' : (xhr.status === 429 ? 'Demasiadas descargas seguidas, espera un momento.' : ''));
                    fail(st2, msg);
                };
                reader.onerror = function () { fail('down', ''); };
                reader.readAsText(xhr.response || new Blob());
            };
            xhr.onerror = function () { fail('down', ''); };
            xhr.ontimeout = function () { fail('down', 'Gestión tardó demasiado en generar la copia.'); };
            xhr.send();
        }

        function openInvoiceSheet($host, inv) {
            var i = inv || {};
            var $sheet = C.sheet.open($host, {
                id: 'invoice',
                label: 'Gestión · Factura',
                title: 'Factura ' + invoiceRef(i),
                icon: 'fas fa-file-invoice',
                html: C.skeleton(3, 'card'),
                foot: invoiceFoot('loading'),
            });
            $sheet.data('ercInsert', invoiceText(i, null));
            $sheet.data('ercInvoiceId', i.id);

            C.invoice(i.id).then(function (resp) {
                if (!$.contains(document, $sheet[0])) { return; }
                if (resp && resp.state === 'ok' && resp.data) {
                    $sheet.data('ercInsert', invoiceText(i, resp.data));
                    C.sheet.update($sheet, {
                        title: 'Factura ' + invoiceRef($.extend({}, i, resp.data)),
                        html: C.render.invoice(resp.data) +
                            '<div class="erc-note erc-note--info"><span class="txt">La copia en PDF es informativa y no vale como factura: la factura fiscal la emite Gestión.</span></div>',
                        foot: invoiceFoot('ok'),
                    });
                    return;
                }
                var pdfState = resp && resp.state === 'blocked' ? 'blocked'
                    : (resp && (resp.state === 'down' || resp.state === 'loading') ? 'down' : 'unavailable');
                // Sin detalle (bloqueado, no disponible…): se muestran los datos de la lista.
                var r = $.extend({}, resp || {});
                delete r.retry;
                if (r.state === 'down') { r.state = 'unavailable'; r.message = r.message || 'Gestión no responde ahora mismo.'; }
                C.sheet.update($sheet, {
                    foot: invoiceFoot(pdfState),
                    html: C.stateHtml(r, 'Detalle de la factura') +
                        '<div class="erc-card"><div class="erc-card-body"><div class="erc-kv">' +
                            C.render.kv('Factura', invoiceRef(i), true) +
                            C.render.kv('Fecha', i.date ? C.date(i.date, true) : null) +
                            C.render.kv('Tipo', i.simplified ? 'Simplificada' : 'Completa') +
                            C.render.kv('Forma de pago', i.payment_method) +
                            C.render.kv('Almacén', C.codeLabel(i.warehouse_description, i.warehouse)) +
                        '</div></div></div>' +
                        C.render.obs(i.observations),
                });
            });
            return $sheet;
        }

        /* Albaranes */

        function dnIsVoid(dn) { return dn.status === false || dn.status === 0 || dn.status === '0'; }

        function renderDeliveryNotes(items) {
            if (!items.length) { return empty('Sin albaranes', 'Este cliente no tiene albaranes en Gestión.', 'fas fa-truck'); }
            return '<div class="erc-list">' + items.map(function (dn, idx) {
                var tags = dnIsVoid(dn) ? '<span class="erc-tag erc-tag--blocked">Anulado</span>'
                    : (dn.invoice_id ? '<span class="erc-tag erc-tag--done">Facturado</span>' : '<span class="erc-tag erc-tag--closed">Sin facturar</span>');
                var meta = [C.codeLabel(dn.warehouse_description, dn.warehouse, 'Almacén'), num(dn.loyalty_points) ? dn.loyalty_points + ' puntos' : ''].filter(Boolean).join(' · ');
                return '<button type="button" class="erc-item is-link' + (dnIsVoid(dn) ? ' erc-item--muted' : '') + '" data-erc-fin-dn="' + idx + '">' +
                    '<span class="ic"><i class="fas fa-truck"></i></span>' +
                    '<span class="info">' +
                        '<span class="top"><span class="ref">Albarán ' + esc(dn.number || dn.delivery_id || dn.id) + '</span>' + tags + '</span>' +
                        '<span class="t">' + esc(C.date(dn.date, true)) + '</span>' +
                        (meta ? '<span class="m">' + esc(meta) + '</span>' : '') +
                    '</span>' +
                    '<span class="end"><span class="act">Ver</span></span>' +
                '</button>';
            }).join('') + '</div>';
        }

        // El detalle de albarán usa el id central (10101961890); algunos
        // orígenes (deudas, puntos) dan el corto (101961890). Se busca en la
        // lista cargada y, si no está, se prueba el corto y luego con "10".
        function centralDnId(id) {
            var s = String(id || '');
            var ps = st.panes['delivery-notes'];
            var hit = ps ? ps.items.filter(function (d) { return String(d.delivery_id) === s || String(d.id) === s; })[0] : null;
            return hit ? String(hit.id) : s;
        }

        function fetchDeliveryNote(id) {
            var first = centralDnId(id);
            // El backend ya resuelve el id corto (resolve/{ref}): nada de adivinar el prefijo.
            return C.deliveryNote(first);
        }

        function dnText(d) {
            var total = d.totals ? num(d.totals.lines_total_with_taxes) : null;
            return ['Albarán: ' + (d.number || d.id),
                d.date ? 'Fecha: ' + C.date(d.date, true) : '',
                total !== null ? 'Total: ' + money(total) : ''].filter(Boolean).join('\n');
        }

        // opts: {id, number, date, orderId}. Reutilizable desde otros modales
        // (window.ErpFinance.deliveryNoteSheet).
        function deliveryNoteSheet($host, opts) {
            var o = opts || {};
            var foot = function (withInsert) {
                return (withInsert ? '<button type="button" class="erc-btn erc-btn--primary" data-erc-fin-insert-doc>Insertar datos del albarán en el chat</button>' : '') +
                    '<button type="button" class="erc-btn erc-btn--outline" data-erc-sheet-close>Volver</button>';
            };
            var $sheet = C.sheet.open($host, {
                id: 'delivery-note',
                label: 'Gestión · Albarán',
                title: 'Albarán ' + (o.number || o.id || ''),
                icon: 'fas fa-truck',
                html: C.skeleton(3, 'card'),
                foot: foot(false),
            });

            fetchDeliveryNote(o.id).then(function (resp) {
                if (!$.contains(document, $sheet[0])) { return; }
                if (!resp || resp.state !== 'ok' || !resp.data) {
                    var r = $.extend({}, resp || { state: 'down' });
                    if (r.state === 'down') { r.state = 'unavailable'; }
                    C.sheet.update($sheet, { html: C.stateHtml(r, 'Detalle del albarán') });
                    return;
                }
                var d = resp.data;
                var orderId = d.order_id || (d.order && d.order.id) || o.orderId || null;
                var links = '';
                if (orderId && C.can('orders')) {
                    links += '<button type="button" class="erc-btn erc-btn--outline erc-btn--sm" data-erp-order-open="' + escAttr(orderId) + '">Ver pedido</button>';
                }
                if (d.invoice_id && C.can('finance')) {
                    links += '<button type="button" class="erc-btn erc-btn--outline erc-btn--sm" data-erc-fin-dn-invoice="' + escAttr(d.invoice_id) + '">Ver factura</button>';
                }
                $sheet.data('ercInsert', dnText(d));
                C.sheet.update($sheet, {
                    title: 'Albarán ' + (d.number || o.number || d.id),
                    html: C.render.deliveryNote(d) + (links ? '<div class="erc-fin-actions">' + links + '</div>' : ''),
                    foot: foot(true),
                });
            });
            return $sheet;
        }

        /* Cobros */

        function renderPayments(items) {
            if (!items.length) { return empty('Sin cobros', 'No hay cobros registrados en Gestión.', 'fas fa-money-bill'); }
            return '<div class="erc-list">' + items.map(function (p) {
                var free = num(p.amount_free);
                var meta = [p.voucher ? 'Vale ' + p.voucher : '', p.cash_register ? 'Caja ' + p.cash_register : '',
                    p.transporter ? 'Transportista ' + p.transporter : '', free ? 'Sin aplicar ' + money(free) : ''].filter(Boolean).join(' · ');
                return '<div class="erc-item">' +
                    '<span class="ic"><i class="fas fa-money-bill"></i></span>' +
                    '<span class="info">' +
                        '<span class="top"><span class="ref">' + esc(C.date(p.date, true)) + '</span>' +
                            (voidFlag(p.status) ? '<span class="erc-tag erc-tag--blocked">Anulado</span>' : '') +
                        '</span>' +
                        '<span class="t">' + esc(p.method || 'Cobro') + '</span>' +
                        (meta ? '<span class="m">' + esc(meta) + '</span>' : '') +
                    '</span>' +
                    '<span class="end"><span class="amt is-good">' + esc(money(p.amount_collected)) + '</span></span>' +
                '</div>';
            }).join('') + '</div>';
        }

        /* Deudas */

        function debtList(data) {
            if (Array.isArray(data)) { return data; }
            return data && Array.isArray(data.debts) ? data.debts : [];
        }

        function renderDebts(data) {
            var d = data && !Array.isArray(data) ? data : {};
            var list = debtList(data);
            var stats = (d.statistics && d.statistics.debts) || {};
            var total = num(stats.amount_total);
            if (total === null) { total = list.reduce(function (acc, x) { return acc + (num(x.amount) || 0); }, 0); }

            var html = '';
            var risk = riskHtml(d.risk);
            if (risk) {
                html += '<div class="erc-card"><div class="erc-card-head">Riesgo comercial</div><div class="erc-card-body">' + risk + '</div></div>';
            }
            if (!list.length) {
                return html + '<div class="erc-note erc-note--good"><span class="txt">Sin deudas pendientes en Gestión.</span></div>';
            }
            html += '<div class="erc-stats erc-stats--2">' +
                '<div class="erc-stat"><span class="n">' + esc(stats.total != null ? stats.total : list.length) + '</span><span class="l">Deudas</span></div>' +
                '<div class="erc-stat"><span class="n">' + esc(money(total)) + '</span><span class="l">Importe</span></div>' +
            '</div>';
            html += '<div class="erc-list">' + list.map(function (x, idx) {
                var meta = [x.delivery_date ? 'Albarán del ' + C.date(x.delivery_date, true) : '', x.payment_method].filter(Boolean).join(' · ');
                var hasDn = !!(x.delivery_id || x.delivery_number) && allowed('delivery-notes');
                return '<' + (hasDn ? 'button type="button"' : 'div') + ' class="erc-item' + (hasDn ? ' is-link' : '') + '"' + (hasDn ? ' data-erc-fin-debt="' + idx + '"' : '') + '>' +
                    '<span class="ic"><i class="fas fa-receipt"></i></span>' +
                    '<span class="info">' +
                        '<span class="top"><span class="ref">' + esc(x.delivery_number ? 'Albarán ' + x.delivery_number : 'Deuda ' + (x.debt_id || x.id || '')) + '</span>' +
                            (voidFlag(x.status) ? '<span class="erc-tag erc-tag--blocked">Anulada</span>' : '') +
                        '</span>' +
                        '<span class="t">' + esc(money(x.amount)) + '</span>' +
                        (meta ? '<span class="m">' + esc(meta) + '</span>' : '') +
                    '</span>' +
                    (hasDn ? '<span class="end"><span class="act">Ver albarán</span></span>' : '') +
                '</' + (hasDn ? 'button' : 'div') + '>';
            }).join('') + '</div>';
            return html;
        }

        /* Devoluciones y abonos */

        function renderReturns(items) {
            if (!items.length) { return empty('Sin devoluciones', 'No hay devoluciones ni abonos en Gestión.', 'fas fa-rotate-left'); }
            return '<div class="erc-list">' + items.map(function (r, idx) {
                var isCredit = String(r.kind || '').toLowerCase() === 'abono';
                var links = '';
                if (r.delivery_note_id && allowed('delivery-notes')) {
                    links += '<button type="button" class="erc-link" data-erc-fin-return-dn="' + idx + '">Ver albarán</button>';
                }
                if (r.invoice_id && C.can('finance')) {
                    links += '<button type="button" class="erc-link" data-erc-fin-dn-invoice="' + escAttr(r.invoice_id) + '">Ver factura</button>';
                }
                if (r.order_id && C.can('orders')) {
                    links += '<button type="button" class="erc-link" data-erp-order-open="' + escAttr(r.order_id) + '">Ver pedido</button>';
                }
                var amount = num(r.amount);
                return '<div class="erc-item">' +
                    '<span class="ic"><i class="fas ' + (isCredit ? 'fa-file-circle-minus' : 'fa-rotate-left') + '"></i></span>' +
                    '<span class="info">' +
                        '<span class="top"><span class="ref">' + esc(r.number || r.id || '') + '</span>' +
                            '<span class="erc-tag ' + (isCredit ? 'erc-tag--progress' : 'erc-tag--closed') + '">' + (isCredit ? 'Abono' : 'Devolución') + '</span>' +
                        '</span>' +
                        '<span class="t">' + esc(C.date(r.date, true)) + '</span>' +
                    '</span>' +
                    '<span class="end">' +
                        (amount !== null ? '<span class="amt' + (amount > 0 ? ' is-good' : '') + '">' + esc(money(amount)) + '</span>' : '') +
                        (links ? '<span class="erc-fin-links">' + links + '</span>' : '') +
                    '</span>' +
                '</div>';
            }).join('') + '</div>';
        }

        /* ── Eventos ────────────────────────────────────────────────── */

        $(document).on('click', '[data-erp-open="finance"]', function (e) {
            e.preventDefault();
            open(String($(this).attr('data-erp-pane') || ''));
        });

        $(document).on('click', '[data-erc-fin-tab]', function () {
            var pane = String($(this).attr('data-erc-fin-tab'));
            if (!allowed(pane)) { return; }
            show(pane, false);
        });

        $(document).on('click', '#ercFinRefresh', function () {
            C.sheet.close($body());
            if (st.pane === 'balance') { st.chart = null; }
            load(st.pane, { reset: true, force: true }).then(function () { renderTabs(); });
            renderTabs();
        });

        $(document).on('click', '#ercFinBody [data-erc-fin-more]', function () {
            var ps = st.panes[st.pane];
            if (!ps || ps.loadingMore || !ps.hasMore) { return; }
            load(st.pane, { reset: false });
        });

        $(document).on('click', '#ercFinBody [data-erc-fin-year]', function () {
            var ps = st.panes.invoices || { year: '' };
            var y = String($(this).attr('data-erc-fin-year') || '');
            if (String(ps.year || '') === y && ps.resp) { return; }
            st.panes.invoices = $.extend(ps, { year: y });
            load('invoices', { reset: true });
        });

        $(document).on('input', '#ercFinInvQ', function () {
            var ps = st.panes.invoices;
            if (!ps) { return; }
            ps.q = String($(this).val() || '');
            $('#ercFinInvList').html(renderInvoiceList(ps));
        });

        $(document).on('keydown', '#ercFinInvQ', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); }
        });

        $(document).on('click', '#ercFinBody [data-erc-fin-pdf]', function () {
            downloadPdf($(this));
        });

        $(document).on('click', '#ercFinBody [data-erc-fin-invoice]', function () {
            var ps = st.panes.invoices;
            var inv = ps ? ps.items[parseInt($(this).attr('data-erc-fin-invoice'), 10)] : null;
            if (inv) { openInvoiceSheet($body(), inv); }
        });

        $(document).on('click', '#ercFinBody [data-erc-fin-dn]', function () {
            var ps = st.panes['delivery-notes'];
            var dn = ps ? ps.items[parseInt($(this).attr('data-erc-fin-dn'), 10)] : null;
            if (dn) { deliveryNoteSheet($body(), { id: dn.id, number: dn.number, orderId: dn.order_id || null }); }
        });

        $(document).on('click', '#ercFinBody [data-erc-fin-debt]', function () {
            var ps = st.panes.debts;
            var x = ps && ps.resp ? debtList(ps.resp.data)[parseInt($(this).attr('data-erc-fin-debt'), 10)] : null;
            if (x) { deliveryNoteSheet($body(), { id: x.delivery_id || x.delivery_number, number: x.delivery_number }); }
        });

        $(document).on('click', '#ercFinBody [data-erc-fin-return-dn]', function () {
            var ps = st.panes.returns;
            var r = ps ? ps.items[parseInt($(this).attr('data-erc-fin-return-dn'), 10)] : null;
            if (r) { deliveryNoteSheet($body(), { id: r.delivery_note_id, orderId: r.order_id || null }); }
        });

        // Factura enlazada desde un albarán o una devolución (id sin datos de lista).
        $(document).on('click', '#ercFinBody [data-erc-fin-dn-invoice]', function () {
            var id = String($(this).attr('data-erc-fin-dn-invoice') || '');
            var ps = st.panes.invoices;
            var hit = ps ? ps.items.filter(function (i) { return String(i.id) === id; })[0] : null;
            openInvoiceSheet($body(), hit || { id: id });
        });

        $(document).on('click', '#ercFinBody [data-erc-fin-insert-doc]', function () {
            var text = $(this).closest('.erc-sheet').data('ercInsert');
            if (!text) { return; }
            if (C.insert(text)) { closeModal(); }
        });

        $(document).on('click', '#ercFinBody [data-erc-fin-insert-balance]', function () {
            var ps = st.panes.balance;
            if (!ps || !ps.resp || ps.resp.state !== 'ok') { return; }
            if (C.insert(balanceText(ps.resp.data))) { closeModal(); }
        });

        // Abrir el pedido (otro modal) desde aquí: primero se cierra este,
        // nunca uno encima de otro. El handler de erp-chat.js abre el pedido.
        $(document).on('click', '[data-bv-modal-name="' + NAME + '"] [data-erp-order-open]', function () {
            closeModal();
        });

        $(document).on('erp:retry', function (e, target) {
            var m = /^erp-finance:(.+)$/.exec(String(target || ''));
            if (m && PANES.indexOf(m[1]) !== -1 && $modal().hasClass('on')) {
                st.pane = m[1];
                show(m[1], true);
            }
        });

        // Llegó el resumen: el título puede completar nombre e id ERP.
        $(document).on('erp:overview-loaded', function (e, resp, cid) {
            if ($modal().hasClass('on') && String(cid) === String(st.cid)) { renderTitle(); }
        });

        window.ErpFinance = {
            open: open,
            deliveryNoteSheet: deliveryNoteSheet,
            invoiceSheet: openInvoiceSheet,
            pdfUrl: pdfUrl,
            render: {
                balance: renderBalance,
                invoices: renderInvoices,
                deliveryNotes: renderDeliveryNotes,
                payments: renderPayments,
                debts: renderDebts,
                returns: renderReturns,
                risk: riskHtml,
                chart: renderChart,
                invoiceList: renderInvoiceList,
            },
            text: { balance: balanceText, invoice: invoiceText, deliveryNote: dnText },
        };
    }

    if (window.ErpChat) { boot(window.ErpChat); }
    else { $(document).one('erp:ready', function (e, C) { boot(C || window.ErpChat); }); }
})(window.jQuery);
