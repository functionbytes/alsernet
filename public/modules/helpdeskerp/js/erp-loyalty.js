/*!
 * HelpdeskErp · modal Fidelización (bv-modal "erp-loyalty") — SOLO LECTURA
 *
 * Abre con [data-erp-open="loyalty"][data-erp-pane="points|vouchers|bonuses"]
 * desde cualquier sitio (delegación en document). Pestañas:
 *   - Puntos: saldo, tarjeta principal y movimientos (paginado en cliente,
 *     filtro ganados / canjeados). El albarán de un movimiento se abre en una
 *     hoja interna .erc-sheet (nunca un modal encima de otro).
 *   - Vales: importe, tipo, validez, anulado; chips vigente / caducado / anulado.
 *   - Bonos: importe, compra mínima, validez, consumido y canales de envío;
 *     "Insertar bono en el chat" (importe, compra mínima y validez: el ERP no
 *     da un código canjeable en la web, así que no se inventa ninguno).
 *
 * Vales y bonos llegan hoy "blocked" (sin GRANT en Oracle): bloque ámbar
 * estándar y el render listo para el día que el manager responda ok.
 *
 * API: window.ErpLoyalty = { open(pane), render: {points, vouchers, bonuses},
 *      voucherStatus(v), bonusStatus(b), bonusText(b) }
 *
 * Fuente: modules/HelpdeskErp/public/js/ — copiar a public/modules/helpdeskerp/js/ tras editar.
 */
(function ($) {
    'use strict';

    if (!$ || window.ErpLoyalty) { return; }

    function boot(C) {
        if (window.ErpLoyalty) { return; }

        var NAME = 'erp-loyalty';
        var MOV_PAGE = 10;
        var PANES = ['points', 'vouchers', 'bonuses'];
        var SECTION = { points: 'loyalty-points', vouchers: 'vouchers', bonuses: 'bonuses' };
        var LABELS = { points: 'Puntos', vouchers: 'Vales', bonuses: 'Bonos' };

        var esc = C.esc;
        var escAttr = C.escAttr;
        var money = C.money;
        var num = C.num;

        // filters: pane -> clave de chip; shown: nº de movimientos visibles.
        var st = { pane: 'points', cid: null, seq: 0, panes: {}, filters: { points: 'all', vouchers: 'all', bonuses: 'all' }, shown: MOV_PAGE };

        function $modal() { return $('[data-bv-modal-name="' + NAME + '"]'); }
        function $body() { return $('#ercLoyBody'); }
        function blank(v) { return v == null || (typeof v === 'string' && v.trim() === ''); }
        function isOff(v) { return v === false || v === 0 || v === '0'; }

        /* ── Apertura / cierre ──────────────────────────────────────── */

        function openModal() {
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
            return String($('.bv-right').first().data('customer-name') || '') || 'Cliente';
        }

        function renderTitle() {
            var erpId = C.erpId();
            $('#ercLoyTitle').html(esc(customerName()) + (erpId ? ' <span class="erc-fin-id">· ERP ' + esc(erpId) + '</span>' : ''));
        }

        function renderTabs() {
            $('#ercLoyTabs').html(PANES.map(function (p) {
                var ps = st.panes[p];
                var cls = 'erc-fin-tab' + (p === st.pane ? ' is-on' : '');
                var n = '';
                if (ps && ps.resp) {
                    if (ps.resp.state === 'blocked' || ps.resp.state === 'down') { cls += ' is-blocked'; }
                    if (ps.resp.state === 'ok') {
                        if (p === 'points') {
                            var bal = num((ps.resp.data || {}).balance);
                            if (bal !== null) { n = '<span class="n">' + esc(bal) + '</span>'; }
                        } else {
                            var live = listOf(p, ps.resp.data).filter(function (x) {
                                return (p === 'vouchers' ? voucherStatus(x) : bonusStatus(x)).key === 'active';
                            }).length;
                            if (live) { cls += ' is-good'; n = '<span class="n">' + live + '</span>'; }
                        }
                    }
                }
                return '<button type="button" role="tab" class="' + cls + '" data-erc-loy-tab="' + escAttr(p) + '" aria-selected="' + (p === st.pane) + '">' +
                    '<span class="dot"></span>' + esc(LABELS[p]) + n +
                '</button>';
            }).join(''));
        }

        function open(pane) {
            var cid = C.customerId();
            if (cid !== st.cid) {
                st.cid = cid;
                st.panes = {};
                st.filters = { points: 'all', vouchers: 'all', bonuses: 'all' };
                st.shown = MOV_PAGE;
            }
            st.pane = PANES.indexOf(pane) !== -1 ? pane : 'points';

            openModal();
            C.sheet.close($body());
            renderTitle();
            renderTabs();

            if (!cid) { $body().html(C.stateHtml('nocustomer')); return; }
            if (!C.can('loyalty')) { $body().html(C.stateHtml('forbidden', 'Fidelización')); return; }
            if (!C.cachedOverview()) { C.overview().then(function () { if (st.cid === C.customerId()) { renderTitle(); } }); }

            show(st.pane, false);
        }

        function show(pane, force) {
            st.pane = pane;
            C.sheet.close($body());
            renderTabs();
            var ps = st.panes[pane];
            if (ps && ps.resp && !force) { renderPane(pane); return; }
            load(pane, !!force);
        }

        function load(pane, force) {
            var cid = st.cid;
            var ps = { resp: null, token: ++st.seq };
            st.panes[pane] = ps;
            if (pane === 'points') { st.shown = MOV_PAGE; }
            if (st.pane === pane) { $body().html(C.skeleton(3, pane === 'points' ? 'card' : null)); }

            return C.section(SECTION[pane], force ? { force: 1 } : {}).then(function (resp) {
                if (st.panes[pane] !== ps || st.cid !== cid) { return; }
                ps.resp = resp;
                renderTabs();
                if (st.pane === pane && $modal().hasClass('on')) { renderPane(pane); }
            });
        }

        /* ── Estados de vales y bonos ───────────────────────────────── */

        function startOfToday() { var d = new Date(); return new Date(d.getFullYear(), d.getMonth(), d.getDate()); }

        function isPast(iso) {
            var d = C.parseDate(iso);
            return !!d && new Date(d.getFullYear(), d.getMonth(), d.getDate()) < startOfToday();
        }

        function isFuture(iso) {
            var d = C.parseDate(iso);
            return !!d && new Date(d.getFullYear(), d.getMonth(), d.getDate()) > startOfToday();
        }

        // {key: active|expired|void, label, tag}
        function voucherStatus(v) {
            var x = v || {};
            if (!blank(x.cancelled_at)) { return { key: 'void', label: 'Anulado', tag: 'blocked' }; }
            if (isPast(x.valid_until)) { return { key: 'expired', label: 'Caducado', tag: 'closed' }; }
            if (isOff(x.available)) { return { key: 'void', label: 'No disponible', tag: 'blocked' }; }
            return { key: 'active', label: 'Vigente', tag: 'done' };
        }

        // {key: active|upcoming|consumed|expired|void, label, tag}
        function bonusStatus(b) {
            var x = b || {};
            if (!blank(x.consumed_at)) { return { key: 'consumed', label: 'Consumido', tag: 'closed' }; }
            if (isPast(x.valid_until)) { return { key: 'expired', label: 'Caducado', tag: 'closed' }; }
            if (isOff(x.available)) { return { key: 'void', label: 'Anulado', tag: 'blocked' }; }
            if (isFuture(x.valid_from)) { return { key: 'upcoming', label: 'Aún no válido', tag: 'pending' }; }
            return { key: 'active', label: 'Vigente', tag: 'done' };
        }

        function listOf(pane, data) {
            var d = data || {};
            if (Array.isArray(d)) { return d; }
            var arr = pane === 'vouchers' ? d.vouchers : (pane === 'bonuses' ? d.bonuses : d.movements);
            return Array.isArray(arr) ? arr : [];
        }

        /* ── Pintado ────────────────────────────────────────────────── */

        function renderPane(pane) {
            var ps = st.panes[pane];
            if (!ps || !ps.resp) { $body().html(C.skeleton(3)); return; }
            var resp = ps.resp;
            var html;
            if (resp.state !== 'ok') {
                var r = $.extend({}, resp);
                r.retry = 'erp-loyalty:' + pane;
                html = C.stateHtml(r, LABELS[pane]);
                if (resp.state === 'blocked') {
                    html += '<div class="erc-note erc-note--info"><span class="txt">Los ' + esc(LABELS[pane].toLowerCase()) +
                        ' se mostrarán aquí en cuanto el DBA conceda el permiso de lectura en Oracle. No hace falta hacer nada más.</span></div>';
                }
            } else if (pane === 'points') {
                html = renderPoints(resp.data, st.filters.points, st.shown);
            } else if (pane === 'vouchers') {
                html = renderVouchers(resp.data, st.filters.vouchers);
            } else {
                html = renderBonuses(resp.data, st.filters.bonuses);
            }
            var at = resp.fetched_at ? '<div class="erc-loading">Datos de Gestión · ' + esc(C.relative(resp.fetched_at)) + '</div>' : '';
            $body().html('<div class="erc-stack">' + html + at + '</div>');
        }

        function chips(pane, defs, current, counts) {
            return '<div class="erc-chips" role="group">' + defs.map(function (d) {
                var n = counts && counts[d[0]] != null ? '<span class="n">' + counts[d[0]] + '</span>' : '';
                return '<button type="button" class="erc-chip' + (current === d[0] ? ' is-on' : '') + '" data-erc-loy-filter="' + escAttr(pane + ':' + d[0]) + '">' + esc(d[1]) + n + '</button>';
            }).join('') + '</div>';
        }

        function empty(title, msg, icon) {
            return C.stateHtml({ state: 'empty', message: msg || '', icon: icon || 'fas fa-inbox' }, title);
        }

        /* Puntos */

        function signed(n) {
            var v = num(n) || 0;
            return (v > 0 ? '+' : (v < 0 ? '−' : '')) + Math.abs(v);
        }

        function renderPoints(data, filter, shown) {
            var d = data || {};
            var bal = num(d.balance);
            var movs = listOf('points', d);
            var earned = 0;
            var spent = 0;
            movs.forEach(function (m) { var p = num(m.points) || 0; if (p > 0) { earned += p; } else { spent += -p; } });

            var html = '<div class="erc-points"><span class="n">' + esc(bal === null ? '—' : bal) + '</span><span class="l">puntos disponibles</span></div>';

            if (!blank(d.main_card)) {
                html += '<div class="erc-card"><div class="erc-card-body">' +
                    '<div class="erc-fin-cardno"><span class="ic"><i class="fas fa-id-card"></i></span>' +
                        '<span class="tt"><span class="k">Tarjeta principal</span><span class="v">' + esc(d.main_card) + '</span></span>' +
                        '<button type="button" class="erc-icon-btn" data-erc-loy-copy="' + escAttr(d.main_card) + '" aria-label="Copiar número de tarjeta"><i class="far fa-copy"></i></button>' +
                    '</div>' +
                '</div></div>';
            }

            html += '<div class="erc-stats">' +
                '<div class="erc-stat"><span class="n">' + movs.length + '</span><span class="l">Movimientos</span></div>' +
                '<div class="erc-stat"><span class="n is-good">+' + earned + '</span><span class="l">Ganados</span></div>' +
                '<div class="erc-stat"><span class="n is-muted">−' + spent + '</span><span class="l">Canjeados</span></div>' +
            '</div>';

            if (bal !== null) {
                html += '<div class="erc-fin-actions"><button type="button" class="erc-btn erc-btn--outline erc-btn--sm" data-erc-loy-insert-points>Insertar saldo de puntos en el chat</button></div>';
            }

            if (!movs.length) {
                return html + empty('Sin movimientos', 'Todavía no hay movimientos de puntos.', 'fas fa-star');
            }

            var filtered = movs.filter(function (m) {
                var p = num(m.points) || 0;
                return filter === 'earned' ? p > 0 : (filter === 'spent' ? p < 0 : true);
            });
            var counts = { all: movs.length, earned: movs.filter(function (m) { return (num(m.points) || 0) > 0; }).length };
            counts.spent = movs.filter(function (m) { return (num(m.points) || 0) < 0; }).length;

            html += '<div class="erc-fin-pane-head"><span class="erc-sec-label">Movimientos</span></div>' +
                chips('points', [['all', 'Todos'], ['earned', 'Ganados'], ['spent', 'Canjeados']], filter, counts);

            if (!filtered.length) { return html + empty('Sin movimientos', 'No hay movimientos con este filtro.', 'fas fa-star'); }

            var canDn = C.can('orders') || C.can('finance');
            html += '<div class="erc-list">' + filtered.slice(0, shown).map(function (m) {
                var p = num(m.points) || 0;
                var meta = [C.codeLabel(m.warehouse_description, m.warehouse, 'Almacén'), m.delivery_id ? 'Albarán ' + m.delivery_id : '',
                    m.liquidation ? 'Liquidación ' + m.liquidation : ''].filter(Boolean).join(' · ');
                return '<div class="erc-item' + (isOff(m.available) ? ' erc-item--muted' : '') + '">' +
                    '<span class="ic"><i class="fas ' + (p >= 0 ? 'fa-plus' : 'fa-minus') + '"></i></span>' +
                    '<span class="info">' +
                        '<span class="top"><span class="ref">' + esc(C.date(m.date, true)) + '</span>' +
                            (isOff(m.available) ? '<span class="erc-tag erc-tag--blocked">Anulado</span>' : '') +
                        '</span>' +
                        '<span class="t">' + esc(p >= 0 ? 'Puntos ganados' : 'Puntos canjeados') + '</span>' +
                        (meta ? '<span class="m">' + esc(meta) + '</span>' : '') +
                    '</span>' +
                    '<span class="end">' +
                        '<span class="amt ' + (p >= 0 ? 'is-good' : 'is-muted') + '">' + esc(signed(p)) + '</span>' +
                        (m.delivery_id && canDn ? '<button type="button" class="erc-link" data-erc-loy-dn="' + escAttr(m.delivery_id) + '">Ver albarán</button>' : '') +
                    '</span>' +
                '</div>';
            }).join('') + '</div>';

            if (filtered.length > shown) {
                html += '<div class="erc-more"><button type="button" class="erc-btn erc-btn--outline erc-fin-more" data-erc-loy-more>Ver más movimientos (' + (filtered.length - shown) + ')</button></div>';
            }
            return html;
        }

        function pointsText(data) {
            var bal = num((data || {}).balance);
            return 'Tienes ' + (bal === null ? 0 : bal) + ' puntos de fidelización disponibles.';
        }

        /* Vales */

        function renderVouchers(data, filter) {
            var d = data || {};
            var list = listOf('vouchers', d);
            var stats = (d.statistics && d.statistics.vouchers) || {};
            if (!list.length) { return empty('Sin vales', 'Este cliente no tiene vales en Gestión.', 'fas fa-ticket'); }

            var counts = { all: list.length, active: 0, expired: 0, void: 0 };
            var activeAmount = 0;
            list.forEach(function (v) {
                var s = voucherStatus(v);
                counts[s.key] = (counts[s.key] || 0) + 1;
                if (s.key === 'active') { activeAmount += num(v.amount) || 0; }
            });

            var html = '<div class="erc-stats">' +
                '<div class="erc-stat"><span class="n">' + esc(stats.total != null ? stats.total : list.length) + '</span><span class="l">Vales</span></div>' +
                '<div class="erc-stat"><span class="n is-good">' + counts.active + '</span><span class="l">Vigentes</span></div>' +
                '<div class="erc-stat"><span class="n">' + esc(money(activeAmount)) + '</span><span class="l">Disponible</span></div>' +
            '</div>' +
            chips('vouchers', [['all', 'Todos'], ['active', 'Vigentes'], ['expired', 'Caducados'], ['void', 'Anulados']], filter, counts);

            var filtered = list.filter(function (v) { return filter === 'all' || voucherStatus(v).key === filter; });
            if (!filtered.length) { return html + empty('Sin vales', 'No hay vales con este filtro.', 'fas fa-ticket'); }

            html += '<div class="erc-fin-vch-list">' + filtered.map(function (v) {
                var s = voucherStatus(v);
                var validity = s.key === 'void' && !blank(v.cancelled_at)
                    ? 'Anulado el ' + C.date(v.cancelled_at, true)
                    : (v.valid_until ? (s.key === 'expired' ? 'Caducó el ' : 'Válido hasta el ') + C.date(v.valid_until, true) +
                        (s.key === 'active' ? ' (' + C.relative(v.valid_until) + ')' : '') : 'Sin fecha de caducidad');
                var extra = [C.codeLabel(v.warehouse_description, v.warehouse, 'Almacén'), v.has_check_code ? 'Con código de control' : '',
                    v.original_voucher_id ? 'Viene del vale ' + v.original_voucher_id : ''].filter(Boolean).join(' · ');
                return '<div class="erc-vch ' + (s.key === 'active' ? 'erc-vch--available' : 'erc-vch--spent') + '">' +
                    '<div class="erc-vch-hd"><span class="erc-vch-code">Vale ' + esc(v.voucher_id || v.id || '') + '</span>' +
                        '<span class="erc-tag erc-tag--' + s.tag + '">' + esc(s.label) + '</span></div>' +
                    '<div class="erc-vch-value"><b>' + esc(money(v.amount)) + '</b>' + (blank(v.type) ? '' : '<span>' + esc(v.type) + '</span>') + '</div>' +
                    '<div class="erc-vch-min">' + esc(validity) + (extra ? ' · ' + esc(extra) : '') + '</div>' +
                    (blank(v.observations) ? '' : '<div class="erc-fin-vch-obs">' + esc(v.observations) + '</div>') +
                '</div>';
            }).join('') + '</div>';
            return html;
        }

        /* Bonos */

        var SENT = [['sms', 'SMS'], ['email', 'Email'], ['mail', 'Correo postal'], ['printed', 'Impreso']];

        function renderBonuses(data, filter) {
            var d = data || {};
            var list = listOf('bonuses', d);
            var stats = (d.statistics && d.statistics.bonuses) || {};
            if (!list.length) { return empty('Sin bonos', 'Este cliente no tiene bonos promocionales en Gestión.', 'fas fa-gift'); }

            var counts = { all: list.length, active: 0, consumed: 0, expired: 0 };
            list.forEach(function (b) {
                var k = bonusStatus(b).key;
                if (k === 'upcoming') { k = 'active'; }
                if (k === 'void') { k = 'expired'; }
                counts[k] = (counts[k] || 0) + 1;
            });

            var html = '<div class="erc-stats">' +
                '<div class="erc-stat"><span class="n">' + esc(stats.total != null ? stats.total : list.length) + '</span><span class="l">Bonos</span></div>' +
                '<div class="erc-stat"><span class="n is-good">' + counts.active + '</span><span class="l">Vigentes</span></div>' +
                '<div class="erc-stat"><span class="n is-muted">' + counts.consumed + '</span><span class="l">Consumidos</span></div>' +
            '</div>' +
            chips('bonuses', [['all', 'Todos'], ['active', 'Vigentes'], ['consumed', 'Consumidos'], ['expired', 'Caducados']], filter, counts);

            var filtered = list.map(function (b, idx) { return { b: b, idx: idx }; }).filter(function (x) {
                if (filter === 'all') { return true; }
                var k = bonusStatus(x.b).key;
                if (filter === 'active') { return k === 'active' || k === 'upcoming'; }
                if (filter === 'expired') { return k === 'expired' || k === 'void'; }
                return k === filter;
            });
            if (!filtered.length) { return html + empty('Sin bonos', 'No hay bonos con este filtro.', 'fas fa-gift'); }

            html += '<div class="erc-fin-vch-list">' + filtered.map(function (x) {
                var b = x.b;
                var s = bonusStatus(b);
                var min = num(b.minimum_purchase);
                var live = s.key === 'active' || s.key === 'upcoming';
                var validity = validityText(b);
                var sent = b.sent || {};
                var anySent = SENT.some(function (c) { return !!sent[c[0]]; });
                var dates = [!blank(b.consumed_at) ? 'Consumido el ' + C.date(b.consumed_at, true) : '',
                    !blank(b.sent_at) ? 'Enviado el ' + C.date(b.sent_at, true) : '',
                    b.delivery_id ? 'Albarán ' + b.delivery_id : ''].filter(Boolean).join(' · ');
                return '<div class="erc-vch ' + (live ? 'erc-vch--available' : 'erc-vch--spent') + '">' +
                    '<div class="erc-vch-hd"><span class="erc-vch-code">Bono' + (blank(b.type) ? '' : ' · ' + esc(b.type)) + '</span>' +
                        '<span class="erc-tag erc-tag--' + s.tag + '">' + esc(s.label) + '</span></div>' +
                    '<div class="erc-vch-value"><b>' + esc(money(b.amount)) + '</b>' +
                        '<span>' + (min ? 'Compra mínima ' + esc(money(min)) : 'Sin compra mínima') + '</span></div>' +
                    '<div class="erc-vch-min">' + esc(validity) + (dates ? ' · ' + esc(dates) : '') + '</div>' +
                    '<div class="erc-fin-sent">' + (anySent
                        ? SENT.map(function (c) { return '<span class="' + (sent[c[0]] ? 'is-on' : '') + '">' + esc(c[1]) + '</span>'; }).join('')
                        : '<span>' + esc(blank(b.send_status) ? 'Sin enviar' : b.send_status) + '</span>') +
                    '</div>' +
                    (live ? '<div class="erc-fin-vch-foot"><button type="button" class="erc-btn erc-btn--outline erc-btn--sm" data-erc-loy-insert-bonus="' + x.idx + '">Insertar bono en el chat</button></div>' : '') +
                '</div>';
            }).join('') + '</div>';
            return html;
        }

        function validityText(b) {
            if (b.valid_from && b.valid_until) { return 'Válido del ' + C.date(b.valid_from, true) + ' al ' + C.date(b.valid_until, true); }
            if (b.valid_until) { return 'Válido hasta el ' + C.date(b.valid_until, true); }
            if (b.valid_from) { return 'Válido desde el ' + C.date(b.valid_from, true); }
            return 'Sin fecha de validez';
        }

        // Sin código: el ERP no expone un código canjeable en la web.
        function bonusText(b) {
            var x = b || {};
            var min = num(x.minimum_purchase);
            var out = 'Tienes un bono de ' + money(x.amount) + (min ? ' para compras a partir de ' + money(min) : '') + '.';
            if (x.valid_from && x.valid_until) { out += ' Es válido del ' + C.date(x.valid_from, true) + ' al ' + C.date(x.valid_until, true) + '.'; }
            else if (x.valid_until) { out += ' Es válido hasta el ' + C.date(x.valid_until, true) + '.'; }
            return out;
        }

        /* Hoja de albarán (movimientos de puntos) */

        // Usa la del modal Finanzas si está cargada; si no, una versión mínima.
        function deliveryNoteSheet(id) {
            if (window.ErpFinance && typeof window.ErpFinance.deliveryNoteSheet === 'function') {
                return window.ErpFinance.deliveryNoteSheet($body(), { id: id });
            }
            var $sheet = C.sheet.open($body(), {
                id: 'delivery-note', label: 'Gestión · Albarán', title: 'Albarán', icon: 'fas fa-truck',
                html: C.skeleton(3, 'card'),
                foot: '<button type="button" class="erc-btn erc-btn--outline" data-erc-sheet-close>Volver</button>',
            });
            var s = String(id || '');
            C.deliveryNote(s).then(function (resp) {
                if (!$.contains(document, $sheet[0])) { return; }
                if (resp && resp.state === 'ok' && resp.data) {
                    C.sheet.update($sheet, { title: 'Albarán ' + (resp.data.number || resp.data.id), html: C.render.deliveryNote(resp.data) });
                } else {
                    var r = $.extend({}, resp || { state: 'down' });
                    if (r.state === 'down') { r.state = 'unavailable'; }
                    C.sheet.update($sheet, { html: C.stateHtml(r, 'Detalle del albarán') });
                }
            });
            return $sheet;
        }

        /* ── Eventos ────────────────────────────────────────────────── */

        $(document).on('click', '[data-erp-open="loyalty"]', function (e) {
            e.preventDefault();
            open(String($(this).attr('data-erp-pane') || ''));
        });

        $(document).on('click', '[data-erc-loy-tab]', function () {
            var pane = String($(this).attr('data-erc-loy-tab'));
            if (PANES.indexOf(pane) === -1 || !C.can('loyalty')) { return; }
            show(pane, false);
        });

        $(document).on('click', '#ercLoyRefresh', function () {
            if (!st.cid || !C.can('loyalty')) { return; }
            C.sheet.close($body());
            load(st.pane, true);
            renderTabs();
        });

        $(document).on('click', '#ercLoyBody [data-erc-loy-filter]', function () {
            var parts = String($(this).attr('data-erc-loy-filter') || '').split(':');
            if (PANES.indexOf(parts[0]) === -1) { return; }
            st.filters[parts[0]] = parts[1] || 'all';
            if (parts[0] === 'points') { st.shown = MOV_PAGE; }
            renderPane(parts[0]);
        });

        $(document).on('click', '#ercLoyBody [data-erc-loy-more]', function () {
            st.shown += MOV_PAGE;
            renderPane('points');
        });

        $(document).on('click', '#ercLoyBody [data-erc-loy-dn]', function () {
            deliveryNoteSheet(String($(this).attr('data-erc-loy-dn') || ''));
        });

        $(document).on('click', '#ercLoyBody [data-erc-loy-copy]', function () {
            C.copy(String($(this).attr('data-erc-loy-copy') || ''));
        });

        $(document).on('click', '#ercLoyBody [data-erc-loy-insert-points]', function () {
            var ps = st.panes.points;
            if (!ps || !ps.resp || ps.resp.state !== 'ok') { return; }
            if (C.insert(pointsText(ps.resp.data))) { closeModal(); }
        });

        $(document).on('click', '#ercLoyBody [data-erc-loy-insert-bonus]', function () {
            var ps = st.panes.bonuses;
            var b = ps && ps.resp && ps.resp.state === 'ok' ? listOf('bonuses', ps.resp.data)[parseInt($(this).attr('data-erc-loy-insert-bonus'), 10)] : null;
            if (!b) { return; }
            if (C.insert(bonusText(b))) { closeModal(); }
        });

        // Botones de la hoja de albarán (insertar / ver factura) cuando la hoja
        // la pinta ErpFinance dentro de este modal.
        $(document).on('click', '#ercLoyBody [data-erc-fin-insert-doc]', function () {
            var text = $(this).closest('.erc-sheet').data('ercInsert');
            if (text && C.insert(text)) { closeModal(); }
        });

        $(document).on('click', '#ercLoyBody [data-erc-fin-dn-invoice]', function () {
            if (window.ErpFinance && typeof window.ErpFinance.invoiceSheet === 'function') {
                window.ErpFinance.invoiceSheet($body(), { id: String($(this).attr('data-erc-fin-dn-invoice') || '') });
            }
        });

        // Abrir un pedido (otro modal) desde aquí: primero se cierra este.
        $(document).on('click', '[data-bv-modal-name="' + NAME + '"] [data-erp-order-open]', function () {
            closeModal();
        });

        $(document).on('erp:retry', function (e, target) {
            var m = /^erp-loyalty:(.+)$/.exec(String(target || ''));
            if (m && PANES.indexOf(m[1]) !== -1 && $modal().hasClass('on')) { show(m[1], true); }
        });

        $(document).on('erp:overview-loaded', function (e, resp, cid) {
            if ($modal().hasClass('on') && String(cid) === String(st.cid)) { renderTitle(); }
        });

        window.ErpLoyalty = {
            open: open,
            voucherStatus: voucherStatus,
            bonusStatus: bonusStatus,
            bonusText: bonusText,
            render: { points: renderPoints, vouchers: renderVouchers, bonuses: renderBonuses },
        };
    }

    if (window.ErpChat) { boot(window.ErpChat); }
    else { $(document).one('erp:ready', function (e, C) { boot(C || window.ErpChat); }); }
})(window.jQuery);
