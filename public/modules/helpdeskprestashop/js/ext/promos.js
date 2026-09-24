/*!
 * HelpdeskPrestashop · extensión "promos".
 *
 *  - Pieza 33 · ps-voucher-edit: botón "Editar" en las tarjetas de cupón del
 *    cliente (tab Cupones, bloque del tab Tienda y workspace de cliente),
 *    añadido por delegación sin tocar right-panel-prestashop-tabs.js. Si el
 *    cupón ya se usó en algún pedido el modal ofrece duplicarlo.
 *  - Pieza 34 · ps-vouchers-shop: promociones públicas y vigentes, con
 *    "Usar" (cupón al carrito en vivo, ruta de carrito existente) y
 *    "Enviar al chat" (composer SIN enviar).
 *
 * Solo cambia clases, nunca estilos. La fuente es este fichero; asset()
 * sirve desde public/modules/helpdeskprestashop/js/ext/ — copiarlo tras editar.
 */
(function () {
    'use strict';

    var edit = { id: null, data: null, limits: null, busy: false };
    var shop = { data: null, error: false, loading: false, waiters: [] };

    function S() { return window.PscStore || null; }
    function C() { return window.HDCommerce || null; }
    function $edit() { return $('[data-bv-modal-name="ps-voucher-edit"]'); }
    function $shop() { return $('[data-bv-modal-name="ps-vouchers-shop"]'); }
    function canEdit() { return String($edit().data('can-edit')) === '1'; }
    function canApply() { return String($shop().data('can-apply')) === '1'; }

    function esc(s) { return S() ? S().esc(s) : $('<span>').text(s == null ? '' : String(s)).html(); }
    function escAttr(s) { return S() ? S().escAttr(s) : esc(s); }
    function money(n) { return S() ? S().money(n) : (parseFloat(n) || 0).toFixed(2).replace('.', ',') + ' €'; }
    function fmtDate(iso) { return S() ? S().date(iso, true) : String(iso || '—').slice(0, 10); }
    function csrf() { return C() ? C().csrf() : $('meta[name="csrf-token"]').attr('content'); }
    function errorMessage(xhr, fb) {
        if (C() && C().errorMessage) { return C().errorMessage(xhr, fb); }
        return (xhr && xhr.responseJSON && xhr.responseJSON.message) || fb;
    }
    function toast(kind, msg) { if (window.toastr) { toastr[kind](msg); } }

    function pct(n) { return String(Math.round((parseFloat(n) || 0) * 100) / 100).replace('.', ','); }

    function valueLabel(v) {
        if (parseFloat(v.reduction_percent) > 0) { return '−' + pct(v.reduction_percent) + ' %'; }
        if (parseFloat(v.reduction_amount) > 0) { return '−' + money(v.reduction_amount); }
        if (v.free_shipping) { return 'Envío gratis'; }
        return 'Regalo o condición especial';
    }

    // Fecha local YYYY-MM-DD (toISOString daría la del día UTC).
    function isoDay(d) {
        return d.getFullYear() + '-' + ('0' + (d.getMonth() + 1)).slice(-2) + '-' + ('0' + d.getDate()).slice(-2);
    }

    // "Permanente" para reglas que caducan dentro de muchos años: en la tienda
    // hay fechas de 2030-2123 que en la práctica significan "sin caducidad".
    function untilLabel(iso) {
        if (!iso) { return 'permanente'; }
        var d = new Date(String(iso).replace(' ', 'T'));
        if (isNaN(d.getTime())) { return 'permanente'; }
        return (d.getFullYear() - new Date().getFullYear()) >= 5 ? 'permanente' : 'hasta ' + fmtDate(iso);
    }

    /* ── Pieza 33 · botón "Editar" en las tarjetas de cupón ───────── */

    function decorateCards(root) {
        if (!canEdit() || !S()) { return; }
        var vouchers = ((S().ctx() || {}).vouchers) || [];
        if (!vouchers.length) { return; }

        $(root).find('.psc-vch').addBack('.psc-vch').each(function () {
            var $card = $(this);
            if ($card.find('[data-psc-promos-edit]').length) { return; }
            // attr y no data(): data() convierte a número un código como "900003451".
            var code = $card.find('[data-psc-copy]').attr('data-psc-copy') || $.trim($card.find('.psc-vch-code').text());
            var v = vouchers.filter(function (x) { return String(x.code) === String(code); })[0];
            if (!v || !v.id) { return; }
            $card.find('.psc-vch-hd').append(
                $('<button type="button" class="psc-promos-edit-btn">Editar</button>').attr('data-psc-promos-edit', v.id)
            );
        });
    }

    var decorateQueued = false;
    function queueDecorate() {
        if (decorateQueued) { return; }
        decorateQueued = true;
        (window.requestAnimationFrame || setTimeout)(function () {
            decorateQueued = false;
            decorateCards(document.body);
        });
    }

    // Las tarjetas las pintan tres sitios distintos (tab Tienda, tab Cupones
    // y el workspace de cliente) sin evento común: un observer las ve a todas.
    function observeCards() {
        if (!window.MutationObserver || !document.body) { return; }
        new MutationObserver(function (mutations) {
            for (var i = 0; i < mutations.length; i++) {
                var added = mutations[i].addedNodes;
                for (var j = 0; j < added.length; j++) {
                    var n = added[j];
                    if (n.nodeType === 1 && (n.classList.contains('psc-vch') || n.querySelector('.psc-vch'))) {
                        queueDecorate();
                        return;
                    }
                }
            }
        }).observe(document.body, { childList: true, subtree: true });
    }

    /* ── Pieza 33 · modal Editar cupón ───────────────────────────── */

    function editError(msg) {
        var $n = $('#psPromosEditError');
        if (!msg) { $n.addClass('bv-hidden'); return; }
        $n.removeClass('bv-hidden').find('.psc-note-txt').text(msg);
    }

    function editUrl(id) {
        var base = C() ? C().base() : null;
        return base ? base + '/ps/ext/promos/vouchers/' + id : null;
    }

    window.openPsVoucherEdit = function (id) {
        var url = editUrl(id);
        if (!url || !canEdit()) { return; }
        edit.id = id;
        edit.data = null;
        editError(null);
        $('#psPromosEditLoading').removeClass('bv-hidden');
        $('#psPromosEditForm').addClass('bv-hidden');
        $('#psPromosEditSave').text('Guardar cupón').removeClass('bv-hidden').prop('disabled', true).addClass('is-disabled');

        // Nunca un modal encima de otro: el workspace de cliente se cierra.
        C().close('ps-customer-workspace');
        C().open('ps-voucher-edit');

        $.ajax({
            url: url, method: 'GET', dataType: 'json',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf() },
        }).done(function (r) {
            if (edit.id !== id) { return; }
            edit.data = (r && r.data) || null;
            edit.limits = (r && r.limits) || {};
            if (!edit.data) { editError('No se ha podido cargar el cupón.'); return; }
            fillEdit();
        }).fail(function (xhr) {
            if (edit.id !== id) { return; }
            editError(errorMessage(xhr, 'No se ha podido cargar el cupón.'));
        }).always(function () {
            if (edit.id === id) { $('#psPromosEditLoading').addClass('bv-hidden'); }
        });
    };

    var LOCK_TEXT = {
        automatic_rule: 'Es una regla automática de la tienda (se crea y se borra sola en cada carrito): no se puede editar ni duplicar desde aquí.',
        gift_rule: 'Ya se ha usado y lleva un producto de regalo: no se puede modificar, y duplicarlo crearía un regalo nuevo sin límite de importe.',
    };

    // keep: valores que el agente ya había escrito (se conservan si el guardado
    // falla porque el cupón acaba de usarse y el modal pasa a "Duplicar").
    function fillEdit(keep) {
        var v = edit.data;
        var lim = edit.limits || {};
        var locked = !v.editable && !v.duplicable;
        var used = !locked && !v.editable;

        $('#psPromosEditCode').text(v.code || 'Sin código');
        $('#psPromosEditValue').text(valueLabel(v));

        $('#psPromosEditAmountField').toggleClass('bv-hidden', v.type !== 'amount');
        $('#psPromosEditPercentField').toggleClass('bv-hidden', v.type !== 'percent');
        $('#psPromosEditAmount').val(v.type === 'amount' ? (parseFloat(v.reduction_amount) || 0).toFixed(2) : '');
        $('#psPromosEditPercent').val(v.type === 'percent' ? (parseFloat(v.reduction_percent) || 0) : '');
        $('#psPromosEditMinimum').val((parseFloat(v.minimum_amount) || 0).toFixed(2));
        $('#psPromosEditQuantity').val(Math.max(1, parseInt(v.quantity, 10) || 1));

        var today = new Date();
        var maxDate = new Date(today.getTime() + (parseInt(lim.max_validity_days, 10) || 365) * 86400000);
        var current = String(v.date_to || '').slice(0, 10);
        var limitDay = isoDay(maxDate);
        // Al editar se puede conservar una caducidad más lejana que el tope;
        // la copia (cupón con usos) es nueva y se queda dentro del tope.
        if (used && current > limitDay) { current = limitDay; }
        $('#psPromosEditDate')
            .attr('min', isoDay(today))
            .attr('max', current > limitDay ? current : limitDay)
            .val(current >= isoDay(today) ? current : '');

        var uses = parseInt(v.uses, 10) || 0;
        $('#psPromosEditUses').text(uses + ' de ' + (uses + (parseInt(v.quantity, 10) || 0)));
        $('#psPromosEditRestrictions').text((v.restrictions || []).join(' · ') || 'Ninguna');

        $('#psPromosEditUsed').toggleClass('bv-hidden', !used).find('.psc-note-txt').text(
            'Ya se ha usado en ' + uses + (uses === 1 ? ' pedido' : ' pedidos') + ' y no se puede modificar. ' +
            'Al guardar se creará un cupón nuevo para el cliente con estos datos; el original no cambia.'
        );
        $('#psPromosEditLocked').toggleClass('bv-hidden', !locked).find('.psc-note-txt')
            .text(LOCK_TEXT[v.blocked_reason] || 'Este cupón no se puede editar ni duplicar desde aquí.');
        $('#psPromosEditForm .psc-field, #psPromosEditForm .psc-fieldrow').toggleClass('psc-promos-off', locked);

        var hint = 'Solo editable si no se ha usado. Con usos, se ofrece duplicarlo en vez de modificarlo.';
        // Al duplicar se crea un cupón nuevo: el límite cuenta entero, no
        // solo para subir (lo aplica también el puente).
        if (used) {
            if (v.type === 'amount' && lim.max_amount) { hint = 'La copia es un cupón nuevo: como mucho de ' + money(lim.max_amount) + ' y ' + (lim.max_quantity || 5) + ' usos.'; }
            if (v.type === 'percent' && lim.max_percent) { hint = 'La copia es un cupón nuevo: como mucho del ' + pct(lim.max_percent) + ' % y ' + (lim.max_quantity || 5) + ' usos.'; }
        } else {
            if (v.type === 'amount' && lim.max_amount) { hint += ' Puedes subir el importe hasta ' + money(lim.max_amount) + '.'; }
            if (v.type === 'percent' && lim.max_percent) { hint += ' Puedes subir el porcentaje hasta el ' + pct(lim.max_percent) + ' %.'; }
        }
        $('#psPromosEditHint').text(hint);

        if (keep) {
            if (keep.amount != null && !isNaN(keep.amount)) { $('#psPromosEditAmount').val(keep.amount); }
            if (keep.percent != null && !isNaN(keep.percent)) { $('#psPromosEditPercent').val(keep.percent); }
            if (!isNaN(keep.minimum)) { $('#psPromosEditMinimum').val(keep.minimum); }
            if (keep.quantity >= 1) { $('#psPromosEditQuantity').val(keep.quantity); }
            if (keep.date_to) { $('#psPromosEditDate').val(keep.date_to); }
        }

        $('#psPromosEditSave').text(used ? 'Duplicar cupón' : 'Guardar cupón').toggleClass('bv-hidden', locked);
        $('#psPromosEditForm').removeClass('bv-hidden');
        refreshEdit();
    }

    function editValues() {
        var v = edit.data || {};
        var out = {
            minimum: parseFloat(String($('#psPromosEditMinimum').val() || '0').replace(',', '.')),
            date_to: String($('#psPromosEditDate').val() || ''),
            quantity: parseInt($('#psPromosEditQuantity').val(), 10),
        };
        if (v.type === 'amount') { out.amount = parseFloat(String($('#psPromosEditAmount').val() || '').replace(',', '.')); }
        if (v.type === 'percent') { out.percent = parseFloat(String($('#psPromosEditPercent').val() || '').replace(',', '.')); }
        return out;
    }

    function editValid(vals) {
        var v = edit.data;
        if (!v || (!v.editable && !v.duplicable)) { return false; }
        if (v.type === 'amount' && !(vals.amount > 0 && vals.amount <= 500)) { return false; }
        if (v.type === 'percent' && !(vals.percent > 0 && vals.percent <= 100)) { return false; }
        if (isNaN(vals.minimum) || vals.minimum < 0) { return false; }
        if (!(vals.quantity >= 1 && vals.quantity <= 20)) { return false; }
        return /^\d{4}-\d{2}-\d{2}$/.test(vals.date_to) && vals.date_to >= isoDay(new Date());
    }

    function refreshEdit() {
        var ok = !edit.busy && editValid(editValues());
        $('#psPromosEditSave').prop('disabled', !ok).toggleClass('is-disabled', !ok);
    }

    function saveEdit() {
        var v = edit.data;
        var url = v ? editUrl(v.id) : null;
        var vals = editValues();
        if (!url || edit.busy || !editValid(vals)) { return; }

        var mode = v.editable ? 'edit' : 'duplicate';
        var $btn = $('#psPromosEditSave');
        var label = $btn.text();
        edit.busy = true;
        editError(null);
        $btn.text(mode === 'edit' ? 'Guardando…' : 'Duplicando…');
        refreshEdit();

        $.ajax({
            url: url, method: 'POST', dataType: 'json',
            data: $.extend({ mode: mode, conversation_id: (C().conversationId && C().conversationId()) || '' }, vals),
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf() },
        }).done(function (r) {
            var saved = ((r && r.data) || {}).voucher || {};
            toast('success', mode === 'edit'
                ? 'Cupón ' + (saved.code || v.code) + ' actualizado.'
                : 'Cupón duplicado: ' + (saved.code || 'nuevo código') + '.');
            C().close('ps-voucher-edit');
            if (S()) { S().refresh(); }
        }).fail(function (xhr) {
            var j = xhr.responseJSON || {};
            // Entre abrir y guardar el cliente pudo pagar con él: se pasa a
            // "Duplicar" en vez de fallar sin salida.
            if (j.error === 'voucher_used' && edit.data) {
                edit.data.editable = false;
                edit.data.blocked_reason = 'used';
                edit.data.uses = j.uses || 1;
                fillEdit(vals);
                label = 'Duplicar cupón';
            }
            editError(errorMessage(xhr, 'No se ha podido guardar el cupón.'));
        }).always(function () {
            edit.busy = false;
            $btn.text(label);
            refreshEdit();
        });
    }

    /* ── Pieza 34 · promociones de la tienda ─────────────────────── */

    function loadShop(force, cb) {
        if (!force && shop.data) { cb(); return; }
        shop.waiters.push(cb);
        if (shop.loading) { return; }
        shop.loading = true;
        $.ajax({
            url: $shop().data('shop-url'), method: 'GET', dataType: 'json',
            data: force ? { fresh: 1 } : {},
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf() },
        }).done(function (r) {
            shop.data = { promotions: (r && r.promotions) || [], automatic: (r && r.automatic) || [] };
            shop.error = false;
        }).fail(function () {
            shop.error = true;
        }).always(function () {
            shop.loading = false;
            shop.waiters.splice(0).forEach(function (fn) { fn(); });
        });
    }

    function promoSub(p) {
        var parts = [valueLabel(p)];
        if (parseFloat(p.minimum_amount) > 0) { parts.push('desde ' + money(p.minimum_amount)); }
        var extra = (p.restrictions || []).filter(function (r) { return r !== 'Sin envío gratis' && r !== 'Envío gratis'; });
        if (extra.length) { parts.push(extra[0].charAt(0).toLowerCase() + extra[0].slice(1)); }
        parts.push(untilLabel(p.date_to));
        return parts.join(' · ');
    }

    function promoRowHtml(p) {
        var hasCart = !!(S() && S().cart());
        var useAttrs = !canApply()
            ? ' disabled title="No tienes permiso para modificar el carrito"'
            : (hasCart ? '' : ' disabled title="El cliente no tiene carrito activo"');
        var name = p.name && p.name !== p.code ? p.name : '';

        return '<div class="psc-promos-row" data-psc-promos-search="' + escAttr([p.code, p.name, p.description].join(' ').toLowerCase()) + '">' +
            '<div class="psc-promos-row-main">' +
                '<span class="c">' + esc(p.code) + '</span>' +
                (name ? '<span class="n">' + esc(name) + '</span>' : '') +
                '<span class="s">' + esc(promoSub(p)) + '</span>' +
            '</div>' +
            '<div class="psc-promos-row-acts">' +
                '<button type="button" data-psc-promos-use="' + escAttr(p.code) + '"' + useAttrs + '>Usar</button>' +
                '<button type="button" class="is-secondary" data-psc-promos-send="' + escAttr(p.code) + '">Enviar al chat</button>' +
            '</div>' +
        '</div>';
    }

    // Algunas reglas de quantitydiscountpro piden código: no se pueden "Usar"
    // con la acción de carrito (solo busca cart_rule), pero sí comunicar.
    function autoRowHtml(a) {
        var how = a.code
            ? 'Se aplica en el carrito con el código ' + a.code + ' si se cumplen las condiciones'
            : 'Se aplica sola en el carrito si se cumplen las condiciones';
        return '<div class="psc-promos-row psc-promos-row--auto" data-psc-promos-search="' + escAttr([a.code || '', a.name, a.description].join(' ').toLowerCase()) + '">' +
            '<div class="psc-promos-row-main">' +
                (a.code ? '<span class="c">' + esc(a.code) + '</span>' : '') +
                '<span class="n">' + esc(a.name || a.description || 'Promoción automática') + '</span>' +
                '<span class="s">' + esc(how + ' · ' + untilLabel(a.date_to)) + '</span>' +
            '</div>' +
            '<div class="psc-promos-row-acts">' +
                '<button type="button" class="is-secondary" data-psc-promos-send-auto="' + escAttr(a.id) + '">Enviar al chat</button>' +
            '</div>' +
        '</div>';
    }

    function secLabel(text, count) {
        return '<div class="ps-sec-label"><span>' + esc(text) + '</span><span class="ct">' + count + '</span><span class="ln"></span></div>';
    }

    function shopListHtml() {
        if (shop.error && !shop.data) {
            return '<div class="psc-note psc-note--warn"><span class="psc-note-txt">No se han podido cargar las promociones de la tienda.</span>' +
                '<button type="button" class="psc-note-act" data-psc-promos-retry>Reintentar</button></div>';
        }
        if (!shop.data) {
            return '<div class="psc-skel"></div><div class="psc-loading">Cargando promociones…</div>';
        }
        var promos = shop.data.promotions;
        var autos = shop.data.automatic;
        if (!promos.length && !autos.length) {
            return S() ? S().emptyHtml('fa-bullhorn', 'Sin promociones vigentes', 'La tienda no tiene ahora mismo reglas públicas con código.')
                : '<div class="psc-state"><span class="t">Sin promociones vigentes</span></div>';
        }
        var html = '';
        if (promos.length) { html += secLabel('Con código', promos.length) + promos.map(promoRowHtml).join(''); }
        else { html += secLabel('Con código', 0) + '<div class="psc-promos-none">Ninguna regla pública con código está vigente.</div>'; }
        if (autos.length) { html += secLabel('Automáticas', autos.length) + autos.map(autoRowHtml).join(''); }
        return html + '<div class="psc-promos-none psc-promos-nomatch bv-hidden">Ninguna promoción coincide con la búsqueda.</div>';
    }

    function renderShopInto($target) {
        $target.html(shopListHtml());
        applyShopFilter($target);
    }

    // El buscador solo existe en el modal: la sección del workspace se pinta
    // siempre completa, sin heredar una búsqueda anterior del modal.
    function applyShopFilter($target) {
        var q = $target.is('#psPromosShopList') ? $.trim(String($('#psPromosSearch').val() || '')).toLowerCase() : '';
        var $rows = $target.find('.psc-promos-row');
        var shown = 0;
        $rows.each(function () {
            var hit = !q || String($(this).attr('data-psc-promos-search') || '').indexOf(q) !== -1;
            $(this).toggleClass('bv-hidden', !hit);
            if (hit) { shown++; }
        });
        $target.find('.psc-promos-nomatch').toggleClass('bv-hidden', !$rows.length || shown > 0);
    }

    function paneTargets() { return $('#psPromosShopList, .psc-promos-pane-list'); }

    function refreshShopViews(force) {
        paneTargets().each(function () { renderShopInto($(this)); });
        loadShop(!!force, function () {
            paneTargets().each(function () { renderShopInto($(this)); });
        });
    }

    window.openPsVouchersShop = function () {
        if (C()) {
            C().close('ps-customer-workspace');
            C().open('ps-vouchers-shop');
        }
        $('#psPromosSearch').val('');
        refreshShopViews(false);
        setTimeout(function () { $('#psPromosSearch').trigger('focus'); }, 50);
    };

    function findPromo(code) {
        return ((shop.data && shop.data.promotions) || []).filter(function (p) { return String(p.code) === String(code); })[0] || null;
    }

    function usePromo(code, $btn) {
        var cart = S() && S().cart();
        var base = C() ? C().base() : null;
        if (!cart || !base || !canApply()) { return; }
        $btn.prop('disabled', true).text('Aplicando…');
        $.ajax({
            url: base + '/ps/cart/' + cart.id + '/voucher', method: 'POST', dataType: 'json',
            data: { code: code },
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf() },
        }).done(function () {
            toast('success', 'Promoción ' + code + ' aplicada al carrito #' + cart.id + '.');
            if (S()) { S().refresh(); }
        }).fail(function (xhr) {
            toast('warning', errorMessage(xhr, 'No se ha podido aplicar la promoción.'));
        }).always(function () {
            $btn.prop('disabled', false).text('Usar');
        });
    }

    function sendPromo(code) {
        var p = findPromo(code);
        if (!p || !S()) { return; }
        var until = untilLabel(p.date_to);
        var conds = parseFloat(p.minimum_amount) > 0 ? ' en pedidos desde ' + money(p.minimum_amount) : '';
        S().insert('Puedes usar el código ' + p.code + ' (' + valueLabel(p) + conds + ')' +
            (until === 'permanente' ? '' : ', válido ' + until) + '. Introdúcelo en el carrito antes de pagar.');
    }

    function sendAuto(id) {
        var a = ((shop.data && shop.data.automatic) || []).filter(function (x) { return String(x.id) === String(id); })[0];
        if (!a || !S()) { return; }
        S().insert('Ahora mismo tenemos esta promoción en la tienda: ' + (a.name || a.description) + '. ' +
            (a.code
                ? 'Introduce el código ' + a.code + ' en el carrito antes de pagar; se aplica si se cumplen las condiciones.'
                : 'Se aplica sola en el carrito cuando se cumplen las condiciones, sin código.'));
    }

    /* ── Entradas: bloque del tab Tienda y sección del workspace ──── */

    function storeBlockHtml() {
        return '<div class="psc-card psc-promos-store" id="psc-promos-store-block">' +
            '<div class="psc-card-head">Promociones de la tienda</div>' +
            '<div class="psc-card-body">' +
                '<div class="psc-promos-store-txt">Reglas públicas y vigentes para aplicar al carrito o enviar al chat.</div>' +
                '<button type="button" class="psc-btn psc-btn--outline" data-psc-promos-open>Ver promociones vigentes</button>' +
            '</div>' +
        '</div>';
    }

    function registerPane() {
        if (!window.PscChat || !window.PscChat.registerPane) { return false; }
        window.PscChat.registerPane({
            key: 'promos',
            icon: 'fa-bullhorn',
            title: 'Promociones',
            sub: 'Reglas públicas vigentes',
            render: function () {
                setTimeout(function () { refreshShopViews(false); }, 0);
                var body = '<div class="psc-promos-pane-list"></div>' +
                    '<div class="psc-note psc-note--info"><span class="psc-note-txt">Solo lista las reglas públicas y vigentes. Las de un cliente concreto siguen en su sección de Cupones.</span></div>';
                return window.PscChat.card ? window.PscChat.card('Promociones de la tienda', '', body) : body;
            },
        });
        return true;
    }

    /* ── Eventos ─────────────────────────────────────────────────── */

    $(document).on('click', '[data-psc-promos-edit]', function (e) {
        e.preventDefault();
        e.stopPropagation();
        window.openPsVoucherEdit(parseInt($(this).attr('data-psc-promos-edit'), 10));
    });
    $(document).on('input change', '#psPromosEditForm input', refreshEdit);
    $(document).on('click', '#psPromosEditSave', saveEdit);

    $(document).on('click', '[data-psc-promos-open]', function () { window.openPsVouchersShop(); });
    $(document).on('input', '#psPromosSearch', function () { applyShopFilter($('#psPromosShopList')); });
    $(document).on('click', '[data-psc-promos-retry]', function () { shop.error = false; refreshShopViews(true); });
    $(document).on('click', '[data-psc-promos-use]', function () {
        usePromo(String($(this).attr('data-psc-promos-use')), $(this));
    });
    $(document).on('click', '[data-psc-promos-send]', function () {
        sendPromo(String($(this).attr('data-psc-promos-send')));
    });
    $(document).on('click', '[data-psc-promos-send-auto]', function () {
        sendAuto($(this).attr('data-psc-promos-send-auto'));
    });

    $(document).on('psc:store-rendered', function () {
        var $wrap = $('#ps-ext-wrap');
        if ($wrap.length && !$wrap.find('#psc-promos-store-block').length) { $wrap.append(storeBlockHtml()); }
        queueDecorate();
        // El carrito activo pudo cambiar: rehabilitar/deshabilitar "Usar".
        if (shop.data) { paneTargets().each(function () { renderShopInto($(this)); }); }
    });

    $(function () {
        observeCards();
        decorateCards(document.body);
        // prestashop-chat.js puede cargarse después que este fichero.
        if (!registerPane()) {
            var tries = 0;
            var timer = setInterval(function () {
                if (registerPane() || ++tries > 20) { clearInterval(timer); }
            }, 250);
        }
    });
})();
