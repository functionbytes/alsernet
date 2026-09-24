/* ============================================================
   HelpdeskPrestashop · extensión "settings" («Ajustes del chat»)

   1) Inbox: window.PscQuickRepliesProvider(ctx) devuelve las respuestas
      rápidas guardadas en Ajustes (#psSettingsCfg[data-replies]) con las
      variables rellenadas con datos reales del cliente abierto. Una
      plantilla con una variable sin dato NO se ofrece (nunca se inserta
      «{pedido}» sin rellenar). right-panel-prestashop-tabs.js usa esta
      lista en vez de las de serie cuando es un array.
   2) Pantalla de ajustes: repetidor de motivos y respuestas, e insertar
      variables en el texto de la respuesta.
   ============================================================ */
(function () {
    'use strict';

    /* ── 1) Proveedor de respuestas rápidas ───────────────────── */

    var cachedTemplates = null;

    function templates() {
        if (cachedTemplates !== null) { return cachedTemplates; }
        var el = document.getElementById('psSettingsCfg');
        if (!el) { return null; }
        try {
            var parsed = JSON.parse(el.getAttribute('data-replies') || '[]');
            cachedTemplates = Array.isArray(parsed) ? parsed : [];
        } catch (e) {
            cachedTemplates = [];
        }
        return cachedTemplates;
    }

    function str(v) {
        return v == null ? '' : String(v).trim();
    }

    // 2418.6 → "2.418,60 €" (mismo formato que psMoney del panel).
    function money(n) {
        var num = parseFloat(n);
        if (!isFinite(num) || num <= 0) { return ''; }
        var parts = num.toFixed(2).split('.');
        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        return parts[0] + ',' + parts[1] + ' €';
    }

    function orderTotal(order) {
        var total = parseFloat((order.totals && order.totals.total) || order.total || 0);
        if (!(total > 0) && order.totals && order.totals.products != null) {
            total = parseFloat(order.totals.products);
        }
        return total || 0;
    }

    function returnIsOpen(stateName) {
        var s = String(stateName || '').toLowerCase();
        return !/denegad|cancelad|rechazad|complet|recibid|reembols/.test(s);
    }

    function values(ctx) {
        ctx = ctx || {};
        var order = (ctx.orders || [])[0] || null;
        var tr = order ? ((order.tracking || [])[0] || null) : null;
        var customer = ctx.customer || null;
        var rma = (ctx.returns || []).filter(function (r) { return returnIsOpen(r.state_name); })[0] || null;
        var refund = (ctx.refunds || [])[0] || null;

        var fullName = customer ? [customer.firstname, customer.lastname].filter(Boolean).join(' ') : '';

        return {
            pedido: order ? str(order.reference || order.id) : '',
            estado: order ? str((order.state && order.state.name) || order.state_name || order.status) : '',
            transportista: tr ? str(tr.carrier_name) : '',
            seguimiento: tr ? str(tr.tracking_number) : '',
            enlace_seguimiento: tr ? str(tr.tracking_url) : '',
            total: order ? money(orderTotal(order)) : '',
            cliente: customer ? str(customer.firstname || fullName) : '',
            rma: rma && rma.id ? 'RMA-' + rma.id : '',
            importe_reembolso: refund ? money(refund.amount != null ? refund.amount : refund.total) : '',
        };
    }

    // null = falta algún dato (o la variable no existe): no se ofrece.
    function fill(text, vals) {
        var missing = false;
        var out = String(text == null ? '' : text).replace(/\{([^{}]*)\}/g, function (m, name) {
            if (!Object.prototype.hasOwnProperty.call(vals, name) || vals[name] === '') {
                missing = true;
                return m;
            }
            return vals[name];
        });
        return missing ? null : out;
    }

    window.PscQuickRepliesProvider = function (ctx) {
        var list = templates();
        // Sin configuración en la página: que el panel use las de serie.
        if (list === null) { return null; }

        var vals = values(ctx);
        var replies = [];
        list.forEach(function (tpl) {
            if (!tpl || !tpl.t || !tpl.text) { return; }
            var t = fill(tpl.t, vals);
            var s = fill(tpl.s || '', vals);
            var text = fill(tpl.text, vals);
            if (t === null || s === null || text === null) { return; }
            replies.push({ t: t, s: s, text: text });
        });
        return replies;
    };

    /* ── 2) Pantalla «Ajustes del chat» ───────────────────────── */

    function initAdmin() {
        var form = document.getElementById('psSettingsForm');
        if (!form) { return; }

        var lastText = null;

        function toggleEmpty(name) {
            var list = form.querySelector('[data-settings-list="' + name + '"]');
            var empty = form.querySelector('[data-settings-empty="' + name + '"]');
            if (!list || !empty) { return; }
            empty.classList.toggle('psc-settings-hidden', list.querySelector('[data-settings-row]') !== null);
        }

        form.addEventListener('click', function (e) {
            var add = e.target.closest('[data-settings-add]');
            if (add) {
                var name = add.getAttribute('data-settings-add');
                var list = form.querySelector('[data-settings-list="' + name + '"]');
                var tpl = document.getElementById('psSettingsTpl-' + name);
                if (!list || !tpl) { return; }
                var next = parseInt(list.getAttribute('data-next') || '0', 10) || 0;
                list.setAttribute('data-next', String(next + 1));
                var wrap = document.createElement('div');
                wrap.innerHTML = tpl.innerHTML.replace(/__i__/g, String(next));
                var row = wrap.firstElementChild;
                if (!row) { return; }
                list.appendChild(row);
                toggleEmpty(name);
                var first = row.querySelector('input, textarea');
                if (first) { first.focus(); }
                return;
            }

            var remove = e.target.closest('[data-settings-remove]');
            if (remove) {
                var rowEl = remove.closest('[data-settings-row]');
                var listEl = rowEl ? rowEl.parentElement : null;
                if (rowEl) { rowEl.remove(); }
                if (listEl && listEl.hasAttribute('data-settings-list')) {
                    toggleEmpty(listEl.getAttribute('data-settings-list'));
                }
            }
        });

        form.addEventListener('focusin', function (e) {
            if (e.target.matches('[data-settings-reply-text]')) { lastText = e.target; }
        });

        document.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-settings-var]');
            if (!btn) { return; }
            var target = (lastText && document.body.contains(lastText)) ? lastText : null;
            if (!target) {
                var all = form.querySelectorAll('[data-settings-reply-text]');
                target = all.length ? all[all.length - 1] : null;
            }
            if (!target || target.disabled) { return; }
            var token = '{' + btn.getAttribute('data-settings-var') + '}';
            var start = target.selectionStart != null ? target.selectionStart : target.value.length;
            var end = target.selectionEnd != null ? target.selectionEnd : target.value.length;
            target.value = target.value.slice(0, start) + token + target.value.slice(end);
            target.focus();
            target.selectionStart = target.selectionEnd = start + token.length;
        });

        // Confirmación antes de descartar una sección personalizada.
        document.querySelectorAll('form[data-settings-reset]').forEach(function (f) {
            f.addEventListener('submit', function (e) {
                if (!window.confirm('¿Descartar lo guardado en esta sección y volver a los valores de configuración?')) {
                    e.preventDefault();
                }
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAdmin);
    } else {
        initAdmin();
    }
})();
