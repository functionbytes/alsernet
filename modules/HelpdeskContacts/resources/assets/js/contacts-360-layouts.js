/**
 * Contactos 360 — bloques propios de cada estilo de la ficha (24-sep-2026).
 *
 * El estilo se elige en Ajustes → Helpdesk · Contactos (#contact360
 * data-layout). Todo lo común (hero, métricas, avisos, historial, compras,
 * notas, pestañas) lo sigue pintando contacts-360.js; aquí solo va lo que
 * añade cada estilo, leyendo los datos que aquel ya pidió
 * (window.Contacts360.data) y repintando en cada evento 'c360:data'.
 *
 *   linea    → tarjeta de salud + historial desplegado
 *   acciones → conversación en curso + compromisos abiertos
 *   valor    → indicadores, gasto por mes, canales, productos, pedidos
 *   maestro  → lista lateral de contactos (J/K, /)
 *   todos    → buscador de acciones (tecla «.»; Ctrl/⌘ + K es el buscador
 *              global de la plataforma)
 */
(function ($) {
    'use strict';

    var $root = $('#contact360');
    var C = window.Contacts360;
    if (!$root.length || !C) {
        return;
    }

    var layout = String($root.data('layout') || 'clasica');
    var esc = C.esc;
    var MONTHS = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
    var MONTHS_LONG = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];

    // ───────────────────────────────────────────────────────── helpers ──

    function num(v) {
        var n = parseFloat(v);
        return isNaN(n) ? 0 : n;
    }

    // Tramos de 5 para las clases de alto/ancho (.is-hN / .c3l-w-N): sin
    // estilos en línea, igual que el anillo de salud del hero.
    function step5(pct) {
        var p = Math.max(0, Math.min(100, pct));
        var s = Math.round(p / 5) * 5;
        return (p > 0 && s === 0) ? 5 : s;
    }

    function emptyLine(text) {
        return '<div class="c3l-empty">' + esc(text) + '</div>';
    }

    var convPromise = null;

    // La pestaña Conversaciones no está entre las que la ficha precarga: se
    // pide una sola vez para los estilos que la usan.
    function conversations() {
        if (!convPromise) {
            convPromise = $.ajax({
                url: C.baseUrl + '/tab/conversaciones',
                method: 'GET',
                headers: { 'Accept': 'application/json' }
            }).then(function (resp) {
                var list = resp && resp.data && resp.data.conversations;
                return Array.isArray(list) ? list : [];
            }, function () {
                return [];
            });
        }
        return convPromise;
    }

    // Mismo origen que "Compras" de la ficha: tienda local si tiene pedidos,
    // si no PrestaShop. Normalizado a { ref, at, total, kind, lines, psId }.
    function orders() {
        var t = C.data.tienda;
        if (t && t.available && Array.isArray(t.orders) && t.orders.length) {
            return t.orders.map(function (o) {
                var st = String(o.status || '');
                return {
                    ref: o.number ? String(o.number) : '',
                    at: o.placedAt,
                    total: num(o.total),
                    currency: o.currency,
                    stateName: st,
                    kind: /cancel|anulad|reembols|devuel/i.test(st) ? 'blocked' : 'closed',
                    lines: (o.items || []).map(function (it) { return { name: it.name, qty: parseInt(it.qty, 10) || 1 }; }),
                    psId: null,
                    source: 'tienda'
                };
            });
        }

        var ps = C.data.prestashop;
        if (ps && ps.available && Array.isArray(ps.orders)) {
            return ps.orders.map(function (o) {
                return {
                    ref: o.reference ? ('#' + o.reference) : ('#' + o.id),
                    at: o.placed_at,
                    total: num(o.totals && o.totals.total),
                    currency: null,
                    stateName: (o.state && o.state.name) || '',
                    kind: C.orderKind(o),
                    lines: (o.lines || []).map(function (l) { return { name: l.name, qty: parseInt(l.quantity || l.qty, 10) || 1 }; }),
                    psId: o.id,
                    source: 'prestashop'
                };
            });
        }

        return [];
    }

    function ordersLoaded() {
        return !!(C.loaded.tienda || C.loaded.prestashop || C.loaded.erp);
    }

    // Pedidos de Gestión (ERP): sin importes (el contexto no los trae), así
    // que cuentan y se listan pero no suman gasto. Un pedido de la tienda
    // también acaba en Gestión: no se mezclan en los totales.
    function erpCustomer() {
        var erp = C.data.erp;
        return (erp && erp.available !== false && erp.customer && erp.customer.found) ? erp : null;
    }

    function erpOrders() {
        var erp = erpCustomer();
        if (!erp || !Array.isArray(erp.orders)) {
            return [];
        }
        return erp.orders.map(function (o) {
            var st = o.status != null ? parseInt(o.status, 10) : null;
            var label = C.erpStatusLabel ? C.erpStatusLabel(st) : '';
            return {
                ref: '#' + (o.number || o.id || '—'),
                at: o.date,
                total: null,
                currency: null,
                stateName: label,
                kind: st === 9 ? 'blocked' : ((st === 5 || st === 7 || st === 3) ? 'closed' : 'progress'),
                lines: [],
                psId: null,
                erpId: o.id,
                source: 'erp'
            };
        });
    }

    function openReturns() {
        var ps = C.data.prestashop;
        if (!(ps && ps.available && Array.isArray(ps.returns))) {
            return [];
        }
        return ps.returns.filter(function (r) {
            var s = String(r.state_name || '').toLowerCase();
            return s !== '' && !/denegad|cancelad|rechazad|complet|recibid/.test(s);
        });
    }

    function healthLabel(score) {
        if (score == null) {
            return 'sin datos';
        }
        return score >= 70 ? 'buena' : (score >= 40 ? 'regular' : 'baja');
    }

    // ─────────────────────────────────────────── estilo "Línea de vida" ──

    var historyExpanded = false;

    function renderHealth() {
        var $el = $('#c3l-health-body');
        if (!$el.length || !C.loaded.resumen) {
            return;
        }
        var stats = (C.data.resumen && C.data.resumen.stats) || {};
        var score = stats.healthScore != null ? Math.max(0, Math.min(100, parseInt(stats.healthScore, 10))) : null;
        var circ = 213.6;
        var dash = score == null ? 0 : (score / 100) * circ;

        var factors = (Array.isArray(stats.healthFactors) ? stats.healthFactors : [])
            .filter(function (f) { return f && f.points; });

        var html = '<div class="c3l-health-row">' +
            '<svg class="c3l-ring" width="72" height="72" viewBox="0 0 80 80" role="img" aria-label="Salud ' + esc(score == null ? 'sin datos' : score) + ' de 100">' +
            '<circle cx="40" cy="40" r="34" fill="none" class="c3l-ring-track" stroke-width="8"></circle>' +
            '<circle cx="40" cy="40" r="34" fill="none" class="c3l-ring-bar" stroke-width="8" stroke-linecap="round" stroke-dasharray="' + dash.toFixed(1) + ' ' + circ + '" transform="rotate(-90 40 40)"></circle>' +
            '<text x="40" y="46" text-anchor="middle" class="c3l-ring-text">' + esc(score == null ? '—' : score) + '</text>' +
            '</svg>' +
            '<div class="c3l-health-text"><div class="c3l-health-title">Salud ' + esc(healthLabel(score)) + '</div>' +
            '<div class="c3l-sub">' + (factors.length ? 'Lo que más pesa:' : 'Parte de 50: todavía nada la sube ni la baja.') + '</div></div>' +
            '</div>';

        if (factors.length) {
            html += '<ul class="c3l-factors">' + factors.map(function (f) {
                var pts = parseInt(f.points, 10) || 0;
                return '<li><span>' + esc(f.label) + '</span><span class="c3l-pts' + (pts < 0 ? ' is-neg' : '') + '">' + (pts > 0 ? '+' : '') + pts + '</span></li>';
            }).join('') + '</ul>';
        }

        $el.html(html);
    }

    function renderLinea(tab) {
        if (tab === 'resumen') {
            renderHealth();
        }
        if (tab === 'actividad' && !historyExpanded) {
            historyExpanded = true;
            C.expandHistory();
        }
    }

    // ───────────────────────────────────────── estilo "Qué hacer ahora" ──

    var liveRendered = false;

    function renderLive() {
        if (liveRendered) {
            return;
        }
        liveRendered = true;

        conversations().then(function (list) {
            var $body = $('#c3l-live-body');
            var open = list.filter(function (c) { return c.statusClass === 'success'; });
            var conv = open[0] || null;

            if (!conv) {
                var last = list[0];
                $body.html(emptyLine('No hay conversaciones abiertas.') + (last
                    ? '<div class="c3l-sub c3l-mt-sm">Última: ' + esc(last.channelLabel || '') + ' · ' + esc(C.when(last.lastAt, false)) + ' · ' + esc(last.subject || '') + '</div>'
                    : ''));
                renderCommitments();
                return;
            }

            if (conv.url) {
                $('#c3l-live-link').attr('href', conv.url).removeClass('d-none');
            }
            var meta = [conv.channelLabel, conv.agentName ? ('Asignada a ' + conv.agentName) : 'Sin asignar', conv.lastAt ? ('último mensaje ' + C.when(conv.lastAt, true)) : null]
                .filter(Boolean).join(' · ');

            $body.html(
                '<div class="c3l-sub">' + esc(meta) + '</div>' +
                '<div class="c3l-bubbles"><div class="c3l-bubble">' + esc(conv.preview || conv.subject || '') + '</div></div>' +
                (open.length > 1 ? '<div class="c3l-sub">' + (open.length - 1) + ' conversación' + (open.length > 2 ? 'es' : '') + ' abierta' + (open.length > 2 ? 's' : '') + ' más</div>' : '')
            );
            renderCommitments();
        });
    }

    function ticketDue(t) {
        var badge = t.slaBadge;
        if (badge && badge.label) {
            return { text: badge.label, warn: badge['class'] === 'danger' || badge['class'] === 'warning' };
        }
        return { text: C.when(t.createdAt, false), warn: false };
    }

    function renderCommitments() {
        var $body = $('#c3l-commit-body');
        if (!$body.length) {
            return;
        }
        var ticketsReady = !!C.loaded.tickets || !$root.find('[data-contact-tab="tickets"]').length;
        var shopReady = !!C.loaded.prestashop || !$root.find('[data-contact-tab="prestashop"]').length;
        if (!ticketsReady || !shopReady) {
            return;
        }

        var rows = [];

        var tickets = (C.data.tickets && Array.isArray(C.data.tickets.tickets)) ? C.data.tickets.tickets : [];
        tickets.filter(function (t) { return t.statusClass === 'success' && !t.closedAt; }).forEach(function (t) {
            var due = ticketDue(t);
            rows.push({
                when: due.text,
                warn: due.warn,
                title: 'Ticket ' + (t.number ? ('#' + t.number) : '') + ' · ' + (t.subject || 'Sin asunto'),
                sub: [t.status, t.agentName || 'Sin responsable'].filter(Boolean).join(' · '),
                action: t.url ? '<a class="psc-btn psc-btn--outline" href="' + esc(t.url) + '">Abrir</a>' : ''
            });
        });

        openReturns().forEach(function (r) {
            rows.push({
                when: C.relativeTime(r.created_at) || '',
                warn: true,
                title: 'Devolución RMA-' + String(r.id).padStart(6, '0'),
                sub: r.state_name || 'Abierta',
                action: '<button type="button" class="psc-btn psc-btn--outline" data-rma-id="' + esc(r.id) + '">Resolver</button>'
            });
        });

        $('#c3l-commit-count').text(rows.length);

        if (!rows.length) {
            $body.html(emptyLine('Nada pendiente con este cliente.'));
            return;
        }

        $body.html(rows.map(function (r) {
            return '<div class="c3l-commit-row">' +
                '<span class="c3l-commit-when' + (r.warn ? ' is-warn' : '') + '">' + esc(r.when) + '</span>' +
                '<span class="c3l-commit-text"><span class="c3l-commit-title">' + esc(r.title) + '</span><span class="c3l-sub">' + esc(r.sub) + '</span></span>' +
                r.action +
                '</div>';
        }).join(''));
    }

    function renderAcciones(tab) {
        renderLive();
        if (tab === 'tickets' || tab === 'prestashop') {
            renderCommitments();
        }
    }

    // ──────────────────────────────────────── estilo "Valor del cliente" ──

    function renderKpis() {
        var $el = $('#c3l-kpis');
        if (!$el.length || !C.loaded.resumen || !ordersLoaded()) {
            return;
        }

        var list = orders().filter(function (o) { return o.kind !== 'blocked'; });
        var now = Date.now();
        var yearMs = 365 * 86400000;
        var last12 = 0;
        var prev12 = 0;
        list.forEach(function (o) {
            var t = Date.parse(o.at);
            if (isNaN(t)) {
                return;
            }
            if (now - t <= yearMs) {
                last12 += o.total;
            } else if (now - t <= 2 * yearMs) {
                prev12 += o.total;
            }
        });

        var kpis = [];

        var trend = '';
        if (prev12 > 0) {
            var pct = Math.round(((last12 - prev12) / prev12) * 100);
            trend = (pct >= 0 ? '+' : '') + pct + ' % que el año anterior';
        }
        kpis.push({ label: 'Gasto 12 meses', value: C.money(last12, null), sub: trend || 'últimos 12 meses', good: prev12 > 0 && last12 >= prev12 });

        var freq = '';
        var dated = list.map(function (o) { return Date.parse(o.at); }).filter(function (t) { return !isNaN(t); }).sort();
        if (dated.length >= 2) {
            var weeks = Math.max(1, Math.round((dated[dated.length - 1] - dated[0]) / (dated.length - 1) / (7 * 86400000)));
            freq = '1 cada ' + weeks + (weeks === 1 ? ' semana' : ' semanas');
        }
        var erpList = erpOrders();
        var pedSub = freq || (list.length ? 'en la tienda' : 'sin pedidos en la tienda');
        if (erpList.length) {
            pedSub += ' · ' + erpList.length + ' en Gestión';
        }
        kpis.push({ label: 'Pedidos', value: String(list.length), sub: pedSub });

        var sum = list.reduce(function (a, o) { return a + o.total; }, 0);
        kpis.push({ label: 'Ticket medio', value: list.length ? C.money(sum / list.length, null) : '—', sub: list.length ? ('en ' + list.length + (list.length === 1 ? ' pedido' : ' pedidos')) : '' });

        var ps = C.data.prestashop;
        var returns = (ps && ps.available && Array.isArray(ps.returns)) ? ps.returns : null;
        if (returns) {
            var psOrders = Array.isArray(ps.orders) ? ps.orders.length : 0;
            var openR = openReturns().length;
            kpis.push({
                label: 'Devoluciones',
                value: psOrders ? (Math.round((returns.length / psOrders) * 100) + ' %') : String(returns.length),
                sub: openR ? (openR + ' en curso') : (returns.length + (returns.length === 1 ? ' devolución' : ' devoluciones')),
                warn: openR > 0
            });
        } else {
            kpis.push({ label: 'Devoluciones', value: '—', sub: 'sin datos de la tienda' });
        }

        var erpC = erpCustomer();
        if (erpC && erpC.customer.balance_invoiced != null) {
            var pendingErp = num(erpC.customer.balance_pending);
            kpis.push({
                label: 'Facturado en Gestión',
                value: C.money(erpC.customer.balance_invoiced, null),
                sub: pendingErp > 0 ? ('pendiente ' + C.money(pendingErp, null)) : 'ejercicio actual · sin deuda',
                warn: pendingErp > 0
            });
        }

        var stats = (C.data.resumen && C.data.resumen.stats) || {};
        var score = stats.healthScore != null ? parseInt(stats.healthScore, 10) : null;
        kpis.push({ label: 'Salud', value: score == null ? '—' : (score + ' / 100'), sub: healthLabel(score), good: score != null && score >= 70 });

        $el.html(kpis.map(function (k) {
            return '<div class="c3l-kpi">' +
                '<div class="ctf-metric-label">' + esc(k.label) + '</div>' +
                '<div class="c3l-kpi-value' + (k.good ? ' is-good' : '') + '">' + esc(k.value) + '</div>' +
                '<div class="c3l-kpi-sub' + (k.warn ? ' is-warn' : (k.good ? ' is-good' : '')) + '">' + esc(k.sub) + '</div>' +
                '</div>';
        }).join(''));
    }

    function renderSpend() {
        var $el = $('#c3l-spend');
        if (!$el.length || !ordersLoaded()) {
            return;
        }

        var now = new Date();
        var months = [];
        for (var i = 11; i >= 0; i--) {
            var d = new Date(now.getFullYear(), now.getMonth() - i, 1);
            months.push({ y: d.getFullYear(), m: d.getMonth(), total: 0 });
        }
        orders().forEach(function (o) {
            if (o.kind === 'blocked') {
                return;
            }
            var t = new Date(o.at);
            if (isNaN(t.getTime())) {
                return;
            }
            months.forEach(function (mo) {
                if (mo.y === t.getFullYear() && mo.m === t.getMonth()) {
                    mo.total += o.total;
                }
            });
        });

        erpOrders().forEach(function (o) {
            var t = new Date(o.at);
            if (isNaN(t.getTime())) {
                return;
            }
            months.forEach(function (mo) {
                if (mo.y === t.getFullYear() && mo.m === t.getMonth()) {
                    mo.erp = (mo.erp || 0) + 1;
                }
            });
        });
        var erpInRange = months.reduce(function (a, mo) { return a + (mo.erp || 0); }, 0);

        var max = months.reduce(function (a, mo) { return Math.max(a, mo.total); }, 0);
        $('#c3l-spend-range').text(MONTHS[months[0].m] + ' ' + months[0].y + ' – ' + MONTHS[months[11].m] + ' ' + months[11].y);

        var erpRow = erpInRange
            ? '<div class="c3l-erp-row" aria-label="Pedidos en Gestión por mes">' + months.map(function (mo) {
                return '<span class="c3l-erp-cell">' + (mo.erp ? mo.erp : '') + '</span>';
            }).join('') + '</div><div class="c3l-sub c3l-erp-legend">Pedidos en Gestión por mes (sin importe: Gestión no lo envía)</div>'
            : '';

        if (max <= 0) {
            $el.html(emptyLine('Sin compras en la tienda en los últimos 12 meses.') + erpRow);
            return;
        }

        var bars = months.map(function (mo, idx) {
            var h = step5((mo.total / max) * 100);
            var cls = idx === 11 ? ' is-now' : (mo.total === max ? ' is-max' : '');
            var label = MONTHS_LONG[mo.m] + ' ' + mo.y + ': ' + C.money(mo.total, null);
            return '<div class="c3l-bar-col" title="' + esc(label) + '">' +
                '<span class="c3l-bar-val">' + (mo.total > 0 ? esc(Math.round(mo.total) + ' €') : '') + '</span>' +
                '<span class="c3l-bar is-h' + h + cls + '" role="img" aria-label="' + esc(label) + '"></span>' +
                '<span class="c3l-bar-lbl">' + MONTHS[mo.m] + '</span>' +
                '</div>';
        }).join('');

        $el.html('<div class="c3l-bars">' + bars + '</div>' + erpRow);
    }

    var channelsRendered = false;

    function renderChannels() {
        if (channelsRendered || !$('#c3l-channels').length) {
            return;
        }
        channelsRendered = true;

        conversations().then(function (list) {
            var $el = $('#c3l-channels');
            if (!list.length) {
                $('#c3l-channels-total').text('');
                $el.html(emptyLine('Todavía no ha escrito por ningún canal.'));
                return;
            }
            var counts = {};
            list.forEach(function (c) {
                var k = c.channelLabel || 'Otro';
                counts[k] = (counts[k] || 0) + 1;
            });
            var rows = Object.keys(counts).map(function (k) { return { label: k, n: counts[k] }; })
                .sort(function (a, b) { return b.n - a.n; });
            // 4 tonos de verde de distinta luminosidad + el resto en gris.
            if (rows.length > 5) {
                var rest = rows.slice(4).reduce(function (a, r) { return a + r.n; }, 0);
                rows = rows.slice(0, 4).concat([{ label: 'Otros', n: rest }]);
            }
            var total = list.length;
            $('#c3l-channels-total').text(total + (total === 1 ? ' conversación' : ' conversaciones'));

            var bar = rows.map(function (r, i) {
                return '<span class="c3l-seg c3l-seg-' + i + ' c3l-w-' + step5((r.n / total) * 100) + '" title="' + esc(r.label + ': ' + r.n) + '"></span>';
            }).join('');
            var legend = rows.map(function (r, i) {
                return '<span class="c3l-legend-item"><span class="c3l-dot c3l-seg-' + i + '"></span>' + esc(r.label) + ' ' + Math.round((r.n / total) * 100) + ' %</span>';
            }).join('');

            $el.html('<div class="c3l-stack">' + bar + '</div><div class="c3l-legend">' + legend + '</div>');
        });
    }

    function renderProducts() {
        var $el = $('#c3l-products');
        if (!$el.length || !ordersLoaded()) {
            return;
        }
        var list = orders().filter(function (o) { return o.kind !== 'blocked'; });
        var byName = {};
        var monthCount = {};
        list.forEach(function (o) {
            var seen = {};
            o.lines.forEach(function (l) {
                if (!l.name) {
                    return;
                }
                var k = String(l.name);
                byName[k] = byName[k] || { name: k, qty: 0, orders: 0 };
                byName[k].qty += l.qty;
                if (!seen[k]) {
                    byName[k].orders += 1;
                    seen[k] = true;
                }
            });
            var t = new Date(o.at);
            if (!isNaN(t.getTime())) {
                monthCount[t.getMonth()] = (monthCount[t.getMonth()] || 0) + 1;
            }
        });

        var top = Object.keys(byName).map(function (k) { return byName[k]; })
            .sort(function (a, b) { return b.qty - a.qty || b.orders - a.orders; })
            .slice(0, 5);

        if (!top.length) {
            $el.html(emptyLine(list.length ? 'Los pedidos no traen el detalle de productos.' : 'Sin compras registradas.'));
            return;
        }

        var max = top[0].qty;
        var html = top.map(function (p) {
            return '<div class="c3l-prod">' +
                '<div class="c3l-prod-row"><span class="c3l-prod-name">' + esc(p.name) + '</span><span class="c3l-mono">' + p.qty + ' ud' + (p.qty === 1 ? '' : 's') + '</span></div>' +
                '<div class="c3l-track"><span class="c3l-fill c3l-w-' + step5((p.qty / max) * 100) + '"></span></div>' +
                '</div>';
        }).join('');

        // Mes en el que más compra (solo con historial suficiente).
        var bestMonth = null;
        Object.keys(monthCount).forEach(function (m) {
            if (bestMonth === null || monthCount[m] > monthCount[bestMonth]) {
                bestMonth = m;
            }
        });
        var tip = '';
        if (list.length >= 3 && bestMonth !== null && monthCount[bestMonth] >= 2) {
            tip = 'Donde más compra es en ' + MONTHS_LONG[bestMonth];
        }
        var canRecommend = typeof window.openProductRecommend === 'function';
        if (tip || canRecommend) {
            html += '<div class="c3l-hint">' +
                (tip ? '<span class="c3l-hint-text">' + esc(tip) + '</span>' : '') +
                (canRecommend ? '<button type="button" class="psc-btn psc-btn--primary" data-c3-ps-call="openProductRecommend">Recomendar productos</button>' : '') +
                '</div>';
        }

        $el.html(html);
    }

    function renderOrdersTable() {
        var $el = $('#c3l-orders');
        if (!$el.length || !ordersLoaded()) {
            return;
        }
        var shopList = orders();
        var list = shopList.concat(erpOrders()).sort(function (a, b) { return (Date.parse(b.at) || 0) - (Date.parse(a.at) || 0); });
        if (!list.length) {
            $el.html(emptyLine('Sin pedidos registrados.'));
            $('#c3l-orders-more').html('');
            return;
        }

        var kindLabel = { pending: 'Sin pagar', progress: 'En curso', closed: 'Enviado', blocked: 'Anulado' };
        var rows = list.slice(0, 5).map(function (o) {
            var first = o.lines[0] && o.lines[0].name ? (o.lines[0].name + (o.lines.length > 1 ? (' y ' + (o.lines.length - 1) + ' más') : '')) : (o.source === 'erp' ? 'Pedido de Gestión' : '—');
            var clickAttr = o.psId != null
                ? (' data-ps-order-id="' + esc(o.psId) + '" role="button" tabindex="0"')
                : ((o.erpId != null && C.erpChatOn) ? (' data-erp-order-open="' + esc(o.erpId) + '" role="button" tabindex="0"') : '');
            return '<div class="c3l-trow' + (clickAttr ? ' is-clickable' : '') + '"' + clickAttr + '>' +
                '<span class="c3l-mono">' + esc(o.ref) + '</span>' +
                '<span class="c3l-ellipsis">' + esc(first) + '</span>' +
                '<span><span class="c3l-chip is-' + esc(o.kind) + '">' + esc(o.stateName || kindLabel[o.kind] || '') + '</span></span>' +
                '<span class="c3l-muted">' + esc(C.when(o.at, false)) + '</span>' +
                '<span class="c3l-mono c3l-right">' + (o.total == null ? '<span class="c3l-muted">—</span>' : esc(C.money(o.total, o.currency))) + '</span>' +
                '</div>';
        }).join('');

        $el.html('<div class="c3l-table">' +
            '<div class="c3l-trow is-head"><span>Pedido</span><span>Productos</span><span>Estado</span><span>Fecha</span><span class="c3l-right">Total</span></div>' +
            rows + '</div>');

        var tab = list[0].source === 'erp' ? 'erp' : list[0].source;
        $('#c3l-orders-more').html($root.find('[data-contact-tab="' + tab + '"]').length
            ? '<button type="button" class="ctf-link-btn" data-ctf-open-tab="' + tab + '">Ver ' + (list.length === 1 ? 'el pedido' : ('los ' + list.length)) + '</button>'
            : '');
    }

    function renderValor(tab) {
        renderChannels();
        if (tab === 'resumen' || tab === 'prestashop' || tab === 'tienda' || tab === 'erp') {
            renderKpis();
            renderSpend();
            renderProducts();
            renderOrdersTable();
        }
    }

    // ───────────────────────────────────────── estilo "Maestro-detalle" ──

    var RAIL_KEY = 'contacts360.rail';
    var railState = { q: '', view: 'all' };
    var railXhr = null;
    var railTimer = null;
    var currentId = String($root.data('customer-id'));
    // Previsualización (?layout=maestro desde Ajustes): los saltos de la
    // lista mantienen el estilo en vez de volver al guardado.
    var previewLayout = (function () {
        try { return new URLSearchParams(window.location.search).get('layout'); } catch (e) { return null; }
    })();

    function railHref(url) {
        if (!previewLayout) {
            return url;
        }
        return url + (url.indexOf('?') === -1 ? '?' : '&') + 'layout=' + encodeURIComponent(previewLayout);
    }

    function railSave() {
        try { sessionStorage.setItem(RAIL_KEY, JSON.stringify(railState)); } catch (e) { /* sin almacenamiento */ }
    }

    function railLoadState() {
        try {
            var saved = JSON.parse(sessionStorage.getItem(RAIL_KEY) || 'null');
            if (saved && typeof saved === 'object') {
                railState.q = String(saved.q || '');
                railState.view = ['all', 'open', 'vip', 'risk'].indexOf(saved.view) !== -1 ? saved.view : 'all';
            }
        } catch (e) { /* sin almacenamiento */ }
    }

    function railFetch() {
        var $list = $('#c3l-rail-list');
        if (railXhr) {
            railXhr.abort();
        }
        railXhr = $.ajax({
            url: String($root.data('rail-url')),
            method: 'GET',
            data: { q: railState.q, view: railState.view, current: currentId },
            headers: { 'Accept': 'application/json' }
        }).done(function (resp) {
            var items = (resp && Array.isArray(resp.data)) ? resp.data : [];
            $('#c3l-rail-count').text(items.length >= 40 ? '40+' : String(items.length));
            if (!items.length) {
                $list.html(emptyLine('Ningún contacto coincide.'));
                return;
            }
            $list.html(items.map(function (c) {
                var right = c.open > 0
                    ? '<span class="c3l-rail-open">' + c.open + (c.open === 1 ? ' abierta' : ' abiertas') + '</span>'
                    : '<span class="c3l-rail-when">' + esc(c.lastSeenAt ? C.when(c.lastSeenAt, false) : '') + '</span>';
                return '<a class="c3l-rail-item' + (String(c.id) === currentId ? ' is-current' : '') + '" href="' + esc(railHref(c.url)) + '" data-c3l-rail-id="' + esc(c.id) + '"' +
                    (String(c.id) === currentId ? ' aria-current="page"' : '') + '>' +
                    '<span class="c3l-rail-av' + (c.isVip ? ' is-vip' : '') + '">' + esc(c.initials) + '</span>' +
                    '<span class="c3l-rail-body"><span class="c3l-rail-name">' + esc(c.name) + (c.isVip ? ' <span class="c3l-chip is-vip">VIP</span>' : '') + (c.isBanned ? ' <span class="c3l-chip">Bloqueado</span>' : '') + '</span>' +
                    '<span class="c3l-rail-sub">' + esc(c.sub || '') + '</span></span>' +
                    right +
                    '</a>';
            }).join(''));
            // Solo el scroll de la lista: scrollIntoView movería toda la página.
            var cur = $list.find('.is-current')[0];
            if (cur) {
                var listEl = $list[0];
                var top = cur.offsetTop;
                if (top < listEl.scrollTop || top + cur.offsetHeight > listEl.scrollTop + listEl.clientHeight) {
                    listEl.scrollTop = Math.max(0, top - 60);
                }
            }
        }).fail(function (xhr, status) {
            if (status !== 'abort') {
                $list.html(emptyLine('No se pudo cargar la lista.'));
            }
        });
    }

    function railGo(delta) {
        var $items = $('#c3l-rail-list [data-c3l-rail-id]');
        if (!$items.length) {
            return;
        }
        var idx = $items.index($items.filter('.is-current'));
        var next = idx === -1 ? 0 : idx + delta;
        if (next < 0 || next >= $items.length) {
            return;
        }
        window.location.href = $items.eq(next).attr('href');
    }

    function initMaestro() {
        railLoadState();
        $('#c3l-rail-search').val(railState.q);
        $('[data-c3l-rail-view]').removeClass('is-active').filter('[data-c3l-rail-view="' + railState.view + '"]').addClass('is-active');
        railFetch();

        $(document).on('input', '#c3l-rail-search', function () {
            var val = $(this).val();
            clearTimeout(railTimer);
            railTimer = setTimeout(function () {
                railState.q = String(val || '').trim();
                railSave();
                railFetch();
            }, 250);
        });

        $(document).on('click', '[data-c3l-rail-view]', function () {
            railState.view = $(this).data('c3l-rail-view');
            $('[data-c3l-rail-view]').removeClass('is-active');
            $(this).addClass('is-active');
            railSave();
            railFetch();
        });

        $(document).on('keydown', function (e) {
            if (e.ctrlKey || e.metaKey || e.altKey || $('.modal.show, [data-bv-modal-name].on').length) {
                return;
            }
            var typing = $(e.target).is('input, textarea, select, [contenteditable="true"]');
            if (typing) {
                if (e.key === 'Escape' && e.target.id === 'c3l-rail-search') {
                    e.target.blur();
                }
                return;
            }
            if (e.key === 'j' || e.key === 'J') {
                e.preventDefault();
                railGo(1);
            } else if (e.key === 'k' || e.key === 'K') {
                e.preventDefault();
                railGo(-1);
            } else if (e.key === '/') {
                e.preventDefault();
                $('#c3l-rail-search').trigger('focus').select();
            }
        });
    }

    // ─────────────────────────────────────── buscador de acciones (tecla .) ──

    var paletteItems = [];
    var paletteIdx = 0;

    // Acciones = los botones reales de la cabecera y del menú "···" (y las
    // pestañas); ejecutar una es pulsar el original, así que respeta los
    // mismos permisos (lo que no se pinta, no aparece).
    function collectActions() {
        var seen = {};
        var items = [];

        function add(label, group, run, dedupeKey) {
            var key = dedupeKey || label.toLowerCase();
            if (!label || seen[key]) {
                return;
            }
            seen[key] = true;
            items.push({ label: label, group: group, run: run });
        }

        var $next = $('#ctf-next-action .ctf-next-btn');
        if ($next.length && !$('#ctf-next-action').hasClass('d-none')) {
            add($.trim($next.text()), 'Sugerida', function () { $next[0].click(); });
        }

        $root.find('.ctf-hero-actions > button, .ctf-hero-actions .ct-menu .dropdown-item').each(function () {
            var el = this;
            if ($(el).closest('.d-none').length || $(el).is('[data-bs-toggle="dropdown"]')) {
                return;
            }
            // "Editar" (cabecera) y "Editar contacto" (menú) son lo mismo.
            var dedupe = $(el).is('[data-contact-edit-trigger]') ? 'edit' : null;
            if (dedupe && seen[dedupe]) {
                return;
            }
            add($.trim($(el).text()), 'Contacto', function () { el.click(); }, dedupe);
        });

        $root.find('.ctf-tabs [data-contact-tab]').each(function () {
            var tab = $(this).data('contact-tab');
            add('Ir a ' + $.trim($(this).text()), 'Pestañas', function () { C.openTab(tab); });
        });

        return items;
    }

    function paletteRender() {
        var q = String($('#c3l-palette-input').val() || '').toLowerCase().trim();
        var list = paletteItems.filter(function (it) { return !q || it.label.toLowerCase().indexOf(q) !== -1; });
        paletteIdx = Math.min(paletteIdx, Math.max(0, list.length - 1));
        var $list = $('#c3l-palette-list');
        if (!list.length) {
            $list.html(emptyLine('Ninguna acción coincide.'));
            $list.data('items', []);
            return;
        }
        var html = '';
        var lastGroup = null;
        list.forEach(function (it, i) {
            if (it.group !== lastGroup) {
                html += '<div class="c3l-palette-group">' + esc(it.group) + '</div>';
                lastGroup = it.group;
            }
            html += '<button type="button" class="c3l-palette-item' + (i === paletteIdx ? ' is-active' : '') + '" role="option" aria-selected="' + (i === paletteIdx ? 'true' : 'false') + '" data-c3l-palette-idx="' + i + '">' + esc(it.label) + '</button>';
        });
        $list.html(html);
        $list.data('items', list);
        var active = $list.find('.is-active')[0];
        if (active) {
            var listEl = $list[0];
            var top = active.offsetTop;
            if (top < listEl.scrollTop) {
                listEl.scrollTop = top;
            } else if (top + active.offsetHeight > listEl.scrollTop + listEl.clientHeight) {
                listEl.scrollTop = top + active.offsetHeight - listEl.clientHeight;
            }
        }
    }

    function paletteRun(i) {
        var list = $('#c3l-palette-list').data('items') || [];
        var it = list[i];
        if (!it) {
            return;
        }
        var $m = $('#c3l-palette');
        $m.one('hidden.bs.modal', function () { it.run(); });
        $m.modal('hide');
    }

    function paletteOpen() {
        paletteItems = collectActions();
        paletteIdx = 0;
        $('#c3l-palette-input').val('');
        paletteRender();
        $('#c3l-palette').modal('show');
    }

    function initPalette() {
        if (!$('#c3l-palette').length) {
            return;
        }

        // Tecla «.»: Ctrl/⌘ + K ya abre el buscador global de la plataforma.
        $(document).on('keydown', function (e) {
            if (e.key !== '.' || e.ctrlKey || e.metaKey || e.altKey) {
                return;
            }
            if ($(e.target).is('input, textarea, select, [contenteditable="true"]') || $('.modal.show, [data-bv-modal-name].on').length) {
                return;
            }
            e.preventDefault();
            paletteOpen();
        });

        $('#c3l-palette').on('shown.bs.modal', function () {
            $('#c3l-palette-input').trigger('focus');
        });

        $(document).on('input', '#c3l-palette-input', function () {
            paletteIdx = 0;
            paletteRender();
        });

        $(document).on('keydown', '#c3l-palette-input', function (e) {
            var n = ($('#c3l-palette-list').data('items') || []).length;
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                paletteIdx = n ? (paletteIdx + 1) % n : 0;
                paletteRender();
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                paletteIdx = n ? (paletteIdx - 1 + n) % n : 0;
                paletteRender();
            } else if (e.key === 'Enter') {
                e.preventDefault();
                paletteRun(paletteIdx);
            }
        });

        $(document).on('click', '[data-c3l-palette-idx]', function () {
            paletteRun(parseInt($(this).data('c3l-palette-idx'), 10));
        });

        $(document).on('click', '[data-c3l-palette-open]', function () {
            paletteOpen();
        });
    }

    // ──────────────────────────────────────────────────────────── wiring ──

    var RENDER = {
        linea: renderLinea,
        acciones: renderAcciones,
        valor: renderValor
    };

    $(document).on('c360:data', function (e, tab) {
        if (RENDER[layout]) {
            RENDER[layout](tab);
        }
    });

    $(function () {
        // Lo que ya llegó antes de que este fichero se enganchara.
        if (RENDER[layout]) {
            Object.keys(C.loaded).forEach(function (tab) {
                if (C.loaded[tab]) {
                    RENDER[layout](tab);
                }
            });
            RENDER[layout](null);
        }
        if (layout === 'maestro') {
            initMaestro();
        }
        initPalette();
    });
})(jQuery);
