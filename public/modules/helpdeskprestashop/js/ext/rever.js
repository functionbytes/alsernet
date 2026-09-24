/*!
 * HelpdeskPrestashop · extensión "rever": cambios de producto gestionados por
 * REVER (plataforma externa de devoluciones y cambios). Solo lectura.
 *
 * - Workspace de pedido: tarjeta "Cambios (Rever)" en la pestaña Estado con el
 *   pedido de cambio relacionado, su estado, lo que el cliente devuelve y lo
 *   que recibe, importes y botón para abrir el otro pedido.
 * - Tab Devoluciones del panel derecho (#bv-ps-returns): sección "Cambios
 *   gestionados por Rever" con los cambios del cliente.
 *
 * Todo sale de PrestaShop vía el bridge (rever.order_exchanges). Lo que REVER
 * no guarda en la tienda (estado del paquete de vuelta, etiqueta, motivo) no
 * se muestra ni se deduce.
 *
 * Fuente en modules/HelpdeskPrestashop/public/js/ext/; asset() sirve desde
 * public/modules/helpdeskprestashop/js/ext/ — copiar allí tras editar.
 */
(function () {
    'use strict';

    var CACHE_MS = 120000;

    var C = function () { return window.HDCommerce; };
    var S = function () { return window.PscStore; };

    var _cust = { id: null, at: 0, data: null, loading: false, waiters: [] };
    var _orderReq = 0;

    function esc(s) { return S() ? S().esc(s) : $('<span>').text(s == null ? '' : String(s)).html(); }
    function escAttr(s) {
        if (S() && S().escAttr) { return S().escAttr(s); }
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }
    function money(n) {
        if (S()) { return S().money(n); }
        return (Number(n) || 0).toLocaleString('es-ES', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' €';
    }
    function day(iso) { return iso ? (S() ? S().date(iso, true) : String(iso).slice(0, 10)) : '—'; }
    function customerId() { return C() && C().customerId ? C().customerId() : null; }
    function csrf() { return C() && C().csrf ? C().csrf() : ($('meta[name="csrf-token"]').attr('content') || ''); }
    function base() { return '/panel/helpdesk/customers/' + customerId() + '/ps/ext/rever'; }
    function errorMessage(xhr, fb) { return C() && C().errorMessage ? C().errorMessage(xhr, fb) : fb; }

    var STATE_TAG = {
        exchange_hold: 'psc-tag--pending',
        paid: 'psc-tag--progress',
        shipped: 'psc-tag--done',
        delivered: 'psc-tag--done',
        canceled: 'psc-tag--closed',
        return_started: 'psc-tag--pending',
        return_partial: 'psc-tag--progress',
        return_completed: 'psc-tag--done',
        return_declined: 'psc-tag--blocked',
        other: 'psc-tag--closed',
    };
    function stateTag(o) {
        if (!o) { return ''; }
        return '<span class="psc-tag ' + (STATE_TAG[o.state_kind] || STATE_TAG.other) + '">' + esc(o.state_name || '—') + '</span>';
    }

    function processTag(p) {
        if (!p) { return ''; }
        return p.status === 'finished'
            ? '<span class="psc-tag psc-tag--done">Devolución completada</span>'
            : '<span class="psc-tag psc-tag--pending">Devolución en curso</span>';
    }

    /* ───────────────────── Datos (con caché por cliente) ───────────────────── */

    function loadCustomer(cb, force) {
        var cid = customerId();
        if (!cid) { cb(null); return; }
        if (!force && _cust.id === cid && _cust.data && (Date.now() - _cust.at) < CACHE_MS) { cb(_cust.data); return; }
        if (_cust.id !== cid) { _cust = { id: cid, at: 0, data: null, loading: false, waiters: [] }; }
        _cust.waiters.push(cb);
        if (_cust.loading) { return; }
        _cust.loading = true;
        $.ajax({
            url: base() + '/exchanges', method: 'GET', dataType: 'json',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf() },
        }).done(function (r) {
            if (_cust.id !== cid) { return; }
            _cust.data = (r && r.success && r.data) ? r.data : null;
            _cust.at = Date.now();
        }).fail(function () {
            if (_cust.id !== cid) { return; }
            _cust.data = null;
        }).always(function () {
            if (_cust.id !== cid) { return; }
            _cust.loading = false;
            var w = _cust.waiters.splice(0);
            w.forEach(function (fn) { fn(_cust.data); });
        });
    }

    /* ─────────────────────── Piezas de marcado comunes ─────────────────────── */

    function linesHtml(lines, priceKey) {
        return '<div class="psc-rever-lines">' + (lines || []).map(function (l) {
            return '<div class="psc-rever-line">' +
                '<span class="nm">' + esc(l.name || 'Producto') +
                    (l.reference ? '<span class="rf">' + esc(l.reference) + '</span>' : '') + '</span>' +
                '<span class="qty">×' + (parseInt(l.quantity, 10) || 1) + '</span>' +
                (l[priceKey] != null ? '<span class="pr">' + money(l[priceKey]) + '</span>' : '') +
            '</div>';
        }).join('') + '</div>';
    }

    function returnedHtml(ex) {
        var p = ex.process || null;
        if (ex.returned && ex.returned.lines && ex.returned.lines.length) {
            return linesHtml(ex.returned.lines, 'amount') +
                '<div class="psc-rever-src">Según la nota de crédito ' + esc(ex.returned.number || ('#' + ex.returned.slip_id)) +
                ' del ' + esc(day(ex.returned.date)) + ' en el pedido original.</div>';
        }
        var summary = p && p.products_count != null
            ? 'Rever anotó ' + esc(p.products_count) + ' producto' + (Number(p.products_count) === 1 ? '' : 's') +
              (p.total_value != null ? ' por valor de ' + money(p.total_value) : '') + '.'
            : 'Rever no dejó en PrestaShop el detalle de lo devuelto.';
        var why = p && p.status !== 'finished'
            ? ' El detalle por producto aparece cuando Rever completa la devolución y genera la nota de crédito.'
            : '';
        return '<div class="psc-rever-src">' + summary + why + '</div>';
    }

    function amountsHtml(ex) {
        var eo = ex.exchange_order || {};
        var p = ex.process || {};
        var rows = [];
        if (p.total_value != null) { rows.push(['Valor devuelto', money(p.total_value)]); }
        rows.push(['Pedido de cambio', money((Number(eo.total_products) || 0) + (Number(eo.total_shipping) || 0))]);
        if (ex.voucher) { rows.push(['Vale Rever aplicado', money(ex.voucher.value) + (ex.voucher.free_shipping ? ' · envío gratis' : '')]); }
        if (ex.extra_payment != null) { rows.push(['Diferencia pagada en Rever', money(ex.extra_payment)]); }
        return '<div class="psc-rever-kv">' + rows.map(function (r) {
            return '<div><span class="k">' + esc(r[0]) + '</span><span class="v">' + r[1] + '</span></div>';
        }).join('') + '</div>';
    }

    function trackingHtml(eo) {
        var t = eo.tracking || null;
        if (t && t.number) {
            return '<div class="psc-rever-src">Envío del cambio: ' + esc(t.carrier || 'transportista') + ' · <span class="psc-rever-mono">' + esc(t.number) + '</span>' +
                (t.url ? ' · <a href="' + escAttr(t.url) + '" target="_blank" rel="noopener noreferrer">Seguimiento</a>' : '') + '</div>';
        }
        return '<div class="psc-rever-src">El pedido de cambio aún no tiene número de seguimiento en PrestaShop.</div>';
    }

    /* ─────────────────────── Workspace de pedido ─────────────────────── */

    function exchangeBlock(ex, currentId) {
        var eo = ex.exchange_order || {};
        var oo = ex.original_order || null;
        var p = ex.process || null;
        var isExchange = Number(eo.id) === Number(currentId);
        var hold = eo.state_kind === 'exchange_hold';

        var btns = '';
        if (!isExchange && eo.id) {
            btns += '<button type="button" class="psc-btn psc-btn--primary" data-psc-rever-open="' + escAttr(eo.id) + '">Abrir pedido de cambio #' + esc(eo.reference || eo.id) + '</button>';
        }
        if (isExchange && oo && oo.id) {
            btns += '<button type="button" class="psc-btn psc-btn--primary" data-psc-rever-open="' + escAttr(oo.id) + '">Abrir pedido original #' + esc(oo.reference || oo.id) + '</button>';
        }
        if (p && p.id) {
            btns += '<button type="button" class="psc-btn psc-btn--outline" data-psc-rever-copy="' + escAttr(p.id) + '">Copiar nº de proceso</button>';
        }

        return '<div class="psc-rever-ex">' +
            '<div class="psc-rever-ex-hd">' +
                '<span class="psc-rever-ref">' + (isExchange ? 'Este pedido' : 'Cambio #' + esc(eo.reference || eo.id)) + '</span>' +
                stateTag(eo) +
            '</div>' +
            '<div class="psc-rever-meta">Creado por Rever el ' + esc(day(eo.date)) +
                (oo ? ' · pedido original <span class="psc-link-ref">#' + esc(oo.reference || oo.id) + '</span> (' + esc(oo.state_name || '—') + ')'
                    : (ex.origin_reference ? ' · pedido original #' + esc(ex.origin_reference) + ' (no encontrado para este cliente)' : '')) +
            '</div>' +
            (hold ? '<div class="psc-note psc-note--info"><span class="psc-note-txt">Retenido en "' + esc(eo.state_name) + '": Rever lo libera para su preparación cuando completa la devolución.</span></div>' : '') +
            (p ? '<div class="psc-rever-proc">' + processTag(p) +
                '<span class="psc-rever-mono">' + esc(p.id) + '</span>' +
                '<span class="psc-rever-dates">' + (p.started_at ? 'Iniciada ' + esc(day(p.started_at)) : '') +
                    (p.finished_at ? ' · completada ' + esc(day(p.finished_at)) : '') + '</span></div>' : '') +
            '<div class="psc-rever-cols">' +
                '<div class="psc-rever-col"><div class="psc-rever-lbl">Devuelve</div>' + returnedHtml(ex) + '</div>' +
                '<div class="psc-rever-col"><div class="psc-rever-lbl">Recibe</div>' +
                    ((eo.lines || []).length ? linesHtml(eo.lines, 'total') : '<div class="psc-rever-src">Sin líneas en el pedido de cambio.</div>') +
                '</div>' +
            '</div>' +
            amountsHtml(ex) +
            trackingHtml(eo) +
            (btns ? '<div class="psc-rever-acts">' + btns + '</div>' : '') +
        '</div>';
    }

    function returnOnlyBlock(p) {
        var s = p.credit_slip || null;
        return '<div class="psc-rever-ex">' +
            '<div class="psc-rever-ex-hd"><span class="psc-rever-ref">Devolución sin cambio</span>' + processTag(p) + '</div>' +
            '<div class="psc-rever-proc"><span class="psc-rever-mono">' + esc(p.id) + '</span>' +
                '<span class="psc-rever-dates">' + (p.started_at ? 'Iniciada ' + esc(day(p.started_at)) : '') +
                (p.finished_at ? ' · completada ' + esc(day(p.finished_at)) : '') + '</span></div>' +
            (s && s.lines && s.lines.length
                ? linesHtml(s.lines, 'amount') + '<div class="psc-rever-src">Nota de crédito ' + esc(s.number) + ' · ' + money(s.amount) + '</div>'
                : '<div class="psc-rever-src">' + (p.products_count != null ? 'Rever anotó ' + esc(p.products_count) + ' producto' + (Number(p.products_count) === 1 ? '' : 's') + (p.total_value != null ? ' por valor de ' + money(p.total_value) : '') + '.' : '') + '</div>') +
        '</div>';
    }

    function orderCard(data, currentId) {
        var ex = data.exchanges || [];
        var extra = (data.processes || []).filter(function (p) { return !p.exchange_order_id; });
        var sub;
        if (data.role === 'exchange') {
            sub = 'Pedido creado por Rever para gestionar un cambio';
        } else if (ex.length) {
            sub = ex.length + ' cambio' + (ex.length === 1 ? '' : 's') + ' gestionado' + (ex.length === 1 ? '' : 's') + ' por Rever';
        } else {
            sub = 'Devoluciones gestionadas por Rever';
        }
        return '<div class="bv-po-card psc-rever-card" id="psReverCard">' +
            '<div class="bv-po-card-h"><span class="bv-po-sec-ic"><i class="fas fa-right-left"></i></span>' +
                '<div class="bv-po-card-ht"><span class="t">Cambios (Rever)</span><span class="s">' + esc(sub) + '</span></div></div>' +
            '<div class="psc-rever-list">' +
                ex.map(function (e) { return exchangeBlock(e, currentId); }).join('') +
                extra.map(returnOnlyBlock).join('') +
            '</div>' +
            '<div class="psc-note psc-note--info"><span class="psc-note-txt">El estado del paquete de vuelta, la etiqueta y el motivo están en Rever: PrestaShop no los guarda.</span></div>' +
        '</div>';
    }

    // ¿Merece la pena pedir los cambios de este pedido? Evita una llamada al
    // bridge por cada pedido abierto: solo si Rever aparece en el pedido o en
    // la lista de cambios del cliente.
    function looksRever(order, list) {
        var id = Number(order.id);
        var states = (list && list.rever_states) || {};
        var ids = Object.keys(states).map(function (k) { return Number(states[k]); }).filter(Boolean);
        var hit = ids.indexOf(Number(order.state_id)) !== -1;
        (order.history || []).forEach(function (h) {
            if (ids.indexOf(Number(h.state_id)) !== -1 || /REVER|^Devoluci[oó]n - |Cambio productos/i.test(h.state_name || '')) { hit = true; }
        });
        (order.payments || []).forEach(function (p) { if (/REVER/i.test(p.payment_method || '')) { hit = true; } });
        ((list && list.exchanges) || []).forEach(function (e) {
            if (Number((e.exchange_order || {}).id) === id || Number((e.original_order || {}).id) === id) { hit = true; }
        });
        return hit;
    }

    $(document).on('psc:order-rendered', function (e, order, api) {
        if (!order || !order.id || !api) { return; }
        var id = Number(order.id);
        var req = ++_orderReq;
        $('#psReverCard').remove();

        loadCustomer(function (list) {
            if (req !== _orderReq || Number(api.orderId()) !== id) { return; }
            if (!looksRever(order, list)) { return; }
            $.ajax({
                url: base() + '/orders/' + id, method: 'GET', dataType: 'json',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf() },
            }).done(function (r) {
                if (req !== _orderReq || Number(api.orderId()) !== id) { return; }
                var d = r && r.success ? r.data : null;
                if (!d || (!(d.exchanges || []).length && !(d.processes || []).length)) { return; }
                $('#psReverCard').remove();
                $('#powPanelEstado').prepend(orderCard(d, id));
            }).fail(function (xhr) {
                if (req !== _orderReq || Number(api.orderId()) !== id || xhr.status === 404 || xhr.status === 403) { return; }
                $('#psReverCard').remove();
                $('#powPanelEstado').prepend(
                    '<div class="bv-po-card psc-rever-card" id="psReverCard"><div class="psc-note psc-note--warn"><span class="psc-note-txt">' +
                    esc(errorMessage(xhr, 'No se pudieron leer los cambios de Rever.')) + '</span></div></div>'
                );
            });
        });
    });

    $(document).on('click', '[data-psc-rever-open]', function () {
        var id = parseInt($(this).data('psc-rever-open'), 10);
        if (id && typeof window.openPsOrderWorkspace === 'function') { window.openPsOrderWorkspace(id); }
    });

    $(document).on('click', '[data-psc-rever-copy]', function () {
        var text = String($(this).data('psc-rever-copy') || '');
        if (!text || !navigator.clipboard) { return; }
        navigator.clipboard.writeText(text);
        if (window.toastr) { toastr.info('Copiado: ' + text); }
    });

    /* ───────────────── Tab Devoluciones del panel derecho ───────────────── */

    function summaryLine(lbl, lines) {
        var txt = (lines || []).map(function (l) { return (parseInt(l.quantity, 10) || 1) + '× ' + (l.name || 'Producto'); }).join(', ');
        return '<div class="psc-rever-sum"><span class="lbl">' + esc(lbl) + '</span><span class="txt">' + esc(txt || '—') + '</span></div>';
    }

    function returnsCard(ex) {
        var eo = ex.exchange_order || {};
        var oo = ex.original_order || null;
        var p = ex.process || null;
        var back = ex.returned && ex.returned.lines && ex.returned.lines.length
            ? summaryLine('Devuelve', ex.returned.lines)
            : '<div class="psc-rever-sum"><span class="lbl">Devuelve</span><span class="txt">' +
                (p && p.products_count != null ? esc(p.products_count) + ' producto' + (Number(p.products_count) === 1 ? '' : 's') + (p.total_value != null ? ' · ' + money(p.total_value) : '') : '—') +
                (p && p.status !== 'finished' ? ' (detalle al completarse)' : '') + '</span></div>';

        return '<div class="psc-rma psc-rever-rma" data-psc-rever-order="' + escAttr(eo.id) + '">' +
            '<div class="psc-rma-hd"><span class="psc-rma-ref">Cambio #' + esc(eo.reference || eo.id) + '</span>' + stateTag(eo) + '</div>' +
            '<div class="psc-rma-meta">' + esc(day(eo.date)) +
                (oo ? ' · original <span class="psc-link-ref">#' + esc(oo.reference || oo.id) + '</span>' : '') +
                (p ? ' · ' + (p.status === 'finished' ? 'devolución completada' : 'devolución en curso') : '') + '</div>' +
            back +
            summaryLine('Recibe', eo.lines) +
            '<div class="psc-rma-acts">' +
                '<button type="button" data-psc-rever-open="' + escAttr(eo.id) + '">Ver cambio</button>' +
                (oo ? '<button type="button" data-psc-rever-open="' + escAttr(oo.id) + '">Ver original</button>' : '') +
            '</div>' +
        '</div>';
    }

    function sectionHtml(data) {
        var ex = (data && data.exchanges) || [];
        if (!ex.length) { return ''; }
        var total = Number(data.total) || ex.length;
        return '<div class="ps-sec-label"><span>Cambios gestionados por Rever</span><span class="ct">' + esc(total) + '</span><span class="ln"></span></div>' +
            ex.map(returnsCard).join('') +
            (total > ex.length ? '<div class="psc-rever-src">Se muestran los ' + ex.length + ' cambios más recientes.</div>' : '');
    }

    function decorateReturns() {
        var $tab = $('#bv-ps-returns');
        if (!$tab.length || !customerId()) { return; }
        // Mientras el tab está cargando (esqueleto) no se toca: se repinta entero.
        if ($tab.find('.psc-skel').length || !$.trim($tab.html())) { return; }
        if ($tab.find('.psc-rever-sec').length) { return; }

        var cid = customerId();
        var $sec = $('<div class="psc-rever-sec psc-card-body"><div class="psc-loading">Buscando cambios de Rever…</div></div>');
        $tab.append($sec);
        loadCustomer(function (data) {
            if (customerId() !== cid || !$.contains(document, $sec[0])) { return; }
            var html = data ? sectionHtml(data) : '';
            if (!html) { $sec.addClass('bv-hidden').empty(); return; }
            $sec.html(html);
        });
    }

    $(document).on('psc:returns-rendered', function () { setTimeout(decorateReturns, 0); });

    $(function () {
        var el = document.getElementById('bv-ps-returns');
        // El tab vacío ("Sin devoluciones") no dispara psc:returns-rendered y
        // los clientes de Rever casi nunca tienen RMA de PrestaShop.
        if (el && window.MutationObserver) {
            new MutationObserver(function () { decorateReturns(); }).observe(el, { childList: true });
        }
    });
})();
