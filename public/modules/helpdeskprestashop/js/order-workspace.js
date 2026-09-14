/*!
 * HelpdeskPrestashop · modal "order-workspace" del inbox.
 *
 * Extraido de resources/views/modals/order-workspace.blade.php, donde vivia como
 * <script> inline que se re-descargaba en CADA carga del inbox (el modal se
 * incluye siempre desde helpdesk/inbox/partials/modals.blade.php). No tiene
 * interpolacion Blade: la config llega por atributos data-* y por
 * window.HDCommerce, que define el core en modals/_commerce-js.blade.php.
 *
 * OJO 1: depende de window.HDCommerce en el nivel superior (var C = ...), asi
 * que debe cargarse DESPUES de _commerce-js — lo garantiza el orden de
 * @include en modals.blade.php (_commerce-js va en la linea 36, este en 38-42).
 * OJO 2: la fuente es este fichero; asset() sirve desde
 * public/modules/helpdeskprestashop/js/ — hay que copiarlo alli tras editar.
 */
(function () {
    var C = window.HDCommerce;
    var _orderId = null;
    var _order = null;
    var _states = null; // catálogo de estados PS (cacheado en cliente)

    function $body() { return $('[data-bv-modal-name="ps-order-workspace"]'); }
    function money(n) { return C.money(n); }
    function esc(s) { return C.esc(s); }

    function fmtDate(s) {
        if (!s) { return '—'; }
        var d = new Date(String(s).replace(' ', 'T'));
        if (isNaN(d.getTime())) { return esc(s); }
        return d.toLocaleDateString('es-ES', { day: '2-digit', month: 'short', year: 'numeric' }) +
            ' ' + d.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
    }

    // Pill de estado con el color real de PrestaShop (fondo suave + texto)
    function statusPill(name, color) {
        var c = color || '#71717a';
        var style = 'background:' + c + '22;color:' + c + ';border:1px solid ' + c + '44';
        return '<span class="bv-po-pill" style="' + style + '">' + esc(name || '—') + '</span>';
    }

    function setLoading() {
        $('#powLoading').removeClass('bv-hidden');
        $('#powError').addClass('bv-hidden');
        $('#powGrid').addClass('bv-hidden');
    }
    function setError(msg) {
        $('#powLoading').addClass('bv-hidden');
        $('#powGrid').addClass('bv-hidden');
        $('#powError').removeClass('bv-hidden').find('span').text(msg || 'No se pudo cargar el pedido.');
    }
    function setReady() {
        $('#powLoading').addClass('bv-hidden');
        $('#powError').addClass('bv-hidden');
        $('#powGrid').removeClass('bv-hidden');
    }

    // ── Render principal ──
    function render(order) {
        _order = order;
        var ref = order.reference || ('#' + order.id);
        var custName = C.customer().name || '';

        $('#powTitle').html('Pedido <span class="bv-po-chip">#' + esc(ref) + '</span>' +
            (custName ? ' <span class="bv-po-crumb">' + esc(custName) + '</span>' : ''));
        $('#powStatus').html(statusPill(order.state_name, order.state_color));

        var url0 = (order.lines && order.lines[0] && order.lines[0].url) || '';
        if (url0) { $('#powStoreLink').attr('href', url0).removeClass('bv-hidden'); }
        else { $('#powStoreLink').addClass('bv-hidden'); }

        renderLines(order);
        renderTotals(order);
        renderEstado(order);
        renderEnvio(order);
        renderCliente(order);
        renderPago(order);
        renderCorreos(order);
        renderNotas(order);
        renderHistorial(order);
        loadDocuments();
        setReady();
    }

    var _docs = null;
    function loadDocuments() {
        _docs = null;
        $('#powInvoiceBtn, #powSlipBtn').addClass('bv-hidden');
        $.ajax({
            url: '/panel/helpdesk/customers/' + C.customerId() + '/ps/orders/' + _orderId + '/documents',
            method: 'GET', dataType: 'json',
            data: {},
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': C.csrf() },
        }).done(function (r) {
            _docs = (r && r.data) || { invoices: [], delivery_slips: [] };
            if ((_docs.invoices || []).length) { $('#powInvoiceBtn').removeClass('bv-hidden'); }
            if ((_docs.delivery_slips || []).length) { $('#powSlipBtn').removeClass('bv-hidden'); }
        });
    }

    $(document).on('click', '#powInvoiceBtn', function () {
        var inv = (_docs && _docs.invoices && _docs.invoices[0]) || null;
        if (inv) { toastr.info('Factura ' + esc(inv.number) + (inv.date ? ' · ' + fmtDate(inv.date) : '') + ' emitida en PrestaShop.'); }
        else { toastr.info('Este pedido no tiene factura emitida.'); }
    });
    $(document).on('click', '#powSlipBtn', function () {
        var s = (_docs && _docs.delivery_slips && _docs.delivery_slips[0]) || null;
        if (s) { toastr.info('Albarán ' + esc(s.number) + (s.date ? ' · ' + fmtDate(s.date) : '') + '.'); }
        else { toastr.info('Este pedido no tiene albarán.'); }
    });

    // Stepper de progreso (= .wv-steps del mockup) derivado del estado PS real:
    // un estado con flag paid→fase 1, shipped→fase 3, delivery→fase 4.
    function progressSteps(order) {
        var st = ((_states || []).filter(function (s) { return s.id === order.state_id; })[0]) || {};
        var phase = 1; // Pago aceptado
        if (st.shipped) { phase = 3; }
        else if (st.paid) { phase = 2; }
        if (st.delivery) { phase = 4; }
        var labels = ['Pago', 'Preparación', 'Enviado', 'Entregado'];
        var html = '<div class="bv-po-steps">';
        labels.forEach(function (lbl, i) {
            var n = i + 1;
            var cls = n < phase ? 'done' : (n === phase ? 'curr' : 'todo');
            html += '<div class="bv-po-step ' + cls + '"><div class="bv-po-node">' +
                (n < phase ? '<i class="fas fa-check"></i>' : n) + '</div><div class="bv-po-steplbl">' + lbl + '</div></div>';
        });
        return html + '</div>';
    }

    function renderLines(order) {
        var lines = order.lines || [];
        var units = lines.reduce(function (s, l) { return s + (parseInt(l.quantity, 10) || 0); }, 0);
        $('#powSummary').text(lines.length + ' artículo(s) · ' + units + ' unidades');

        if (!lines.length) {
            $('#powLines').html('<div class="bv-po-empty">Este pedido no tiene líneas.</div>');
            return;
        }
        $('#powLines').html(lines.map(function (l) {
            return '<div class="bv-po-line">' +
                '<div class="thumb"><i class="fas fa-box"></i></div>' +
                '<div class="body">' +
                    '<div class="nm">' + esc(l.name) + '</div>' +
                    '<div class="sku">' + (l.reference ? 'Ref: ' + esc(l.reference) : '') + '</div>' +
                '</div>' +
                '<div class="qty">×' + (parseInt(l.quantity, 10) || 1) + '</div>' +
                '<div class="price">' + money(l.total) + '</div>' +
            '</div>';
        }).join(''));
    }

    function renderTotals(order) {
        var t = order.totals || {};
        var rows = '';
        rows += '<div class="row"><span class="k">Subtotal</span><span class="v">' + money(t.subtotal) + '</span></div>';
        if (Number(t.discount) > 0) {
            rows += '<div class="row discount"><span class="k">Descuento</span><span class="v">− ' + money(t.discount) + '</span></div>';
        }
        rows += '<div class="row"><span class="k">Envío</span><span class="v">' + money(t.shipping) + '</span></div>';
        if (Number(t.tax) > 0) {
            rows += '<div class="row"><span class="k">Impuestos</span><span class="v">' + money(t.tax) + '</span></div>';
        }
        rows += '<div class="row total"><span class="k">Total</span><span class="v">' + money(t.total) + '</span></div>';
        $('#powTotals').html(rows);
    }

    // ── Pestaña Estado (cambio de estado real) ──
    function renderEstado(order) {
        var opts = (_states || []).map(function (s) {
            return '<option value="' + s.id + '"' + (s.id === order.state_id ? ' selected' : '') + '>' + esc(s.name) + '</option>';
        }).join('');

        // ws-cta "Marcar enviado": solo si hay un estado "Enviado" y el pedido
        // aún no está enviado.
        var st = ((_states || []).filter(function (s) { return s.id === order.state_id; })[0]) || {};
        var shipState = (_states || []).filter(function (s) { return /enviado/i.test(s.name) && !/no |sin /i.test(s.name); })[0];
        var cta = '';
        if (shipState && !st.shipped && !st.delivery) {
            cta = '<div class="bv-po-cta ok"><div class="ic"><i class="fas fa-box-open"></i></div>' +
                '<div class="tx"><div class="t">Listo para enviar</div><div class="s">Marca el pedido como enviado y notifica al cliente</div></div>' +
                '<button type="button" class="btn-primary btn-sm" id="powMarkShipped" data-ship="' + shipState.id + '">Marcar enviado</button></div>';
        }

        var meta = '<div class="bv-po-card">' +
            '<div class="bv-po-card-h"><span class="bv-po-sec-ic"><i class="fas fa-database"></i></span><div class="bv-po-card-ht"><span class="t">Metadatos</span><span class="s">Información técnica</span></div></div>' +
            '<div class="bv-po-kv">' +
                '<div><div class="k">ID pedido</div><div class="v mono">' + esc(order.id) + '</div></div>' +
                '<div><div class="k">Referencia</div><div class="v mono">' + esc(order.reference || '—') + '</div></div>' +
                '<div><div class="k">Moneda</div><div class="v">' + esc(order.currency || '—') + '</div></div>' +
                '<div><div class="k">Creado</div><div class="v mono">' + fmtDate(order.created_at) + '</div></div>' +
                '<div><div class="k">Actualizado</div><div class="v mono">' + fmtDate(order.updated_at) + '</div></div>' +
            '</div></div>';

        $('#powPanelEstado').html(
            cta +
            '<div class="bv-po-card">' +
                '<div class="bv-po-card-h"><span class="bv-po-sec-ic"><i class="fas fa-list-check"></i></span>' +
                    '<div class="bv-po-card-ht"><span class="t">Progreso del pedido</span><span class="s">Pago · Preparación · Enviado · Entregado</span></div></div>' +
                progressSteps(order) +
            '</div>' +
            '<div class="bv-po-card">' +
                '<div class="bv-po-card-h"><span class="bv-po-sec-ic"><i class="fas fa-circle-info"></i></span>' +
                    '<div class="bv-po-card-ht"><span class="t">Estado del pedido</span><span class="s">Estado actual y cambio</span></div></div>' +
                '<div class="bv-po-current">' + statusPill(order.state_name, order.state_color) + '</div>' +
                '<div class="bv-po-field"><label class="bv-po-lbl">Cambiar estado</label>' +
                    '<select class="bv-po-select" id="powStateSelect">' + (opts || '<option>—</option>') + '</select></div>' +
                '<div class="bv-po-warn bv-hidden" id="powStateWarn"><i class="fas fa-triangle-exclamation"></i> Este estado genera factura o albarán en PrestaShop.</div>' +
                '<label class="bv-po-check"><input type="checkbox" id="powNotify"> Notificar al cliente el cambio</label>' +
                '<button type="button" class="btn-primary bv-po-btn" id="powApplyState">Aplicar cambio de estado</button>' +
            '</div>' +
            meta
        );
        toggleStateWarn();
    }

    // "Marcar enviado" en 1 clic (cambia al estado Enviado + notifica).
    $(document).on('click', '#powMarkShipped', function () {
        if (!_orderId) { return; }
        var stateId = parseInt($(this).data('ship'), 10);
        var $btn = $(this).prop('disabled', true);
        $.ajax({
            url: '/panel/helpdesk/customers/' + C.customerId() + '/ps/orders/' + _orderId + '/status',
            method: 'POST', dataType: 'json',
            data: { state_id: stateId, notify: 1 },
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': C.csrf() },
        }).done(function (r) {
            if (r && r.success) { toastr.success('Pedido marcado como enviado.'); loadOrder(_orderId); }
            else { toastr.warning((r && r.message) || 'No se pudo marcar como enviado.'); }
        }).fail(function (xhr) {
            toastr.error(C.errorMessage(xhr, 'No se pudo marcar como enviado.'));
        }).always(function () { $btn.prop('disabled', false); });
    });

    // ── Pestaña Correos (reenvío real de correos del pedido) ──
    function renderCorreos(order) {
        var actions = [
            { type: 'order_conf', icon: 'fa-circle-check', nm: 'Confirmación de pedido', ds: 'Reenviar el correo de confirmación' },
            { type: 'shipped', icon: 'fa-truck', nm: 'Aviso de envío', ds: 'Notificar que el pedido ha salido' },
            { type: 'order_customer_comment', icon: 'fa-star', nm: 'Actualización del pedido', ds: 'Enviar una actualización al cliente' },
        ];
        $('#powPanelCorreos').html(
            '<div class="bv-po-card">' +
                '<div class="bv-po-card-h"><span class="bv-po-sec-ic"><i class="fas fa-paper-plane"></i></span>' +
                    '<div class="bv-po-card-ht"><span class="t">Correos del pedido</span><span class="s">Reenviar notificaciones al cliente</span></div></div>' +
                actions.map(function (a) {
                    return '<div class="bv-po-mailrow"><div class="ic"><i class="fas ' + a.icon + '"></i></div>' +
                        '<div class="body"><div class="nm">' + a.nm + '</div><div class="ds">' + a.ds + '</div></div>' +
                        '<button type="button" class="btn-secondary btn-sm powSendMail" data-mail="' + a.type + '">Enviar</button></div>';
                }).join('') +
            '</div>'
        );
    }

    $(document).on('click', '.powSendMail', function () {
        if (!_orderId) { return; }
        var type = $(this).data('mail');
        var $btn = $(this).prop('disabled', true).text('Enviando…');
        $.ajax({
            url: '/panel/helpdesk/customers/' + C.customerId() + '/ps/orders/' + _orderId + '/email',
            method: 'POST', dataType: 'json',
            data: { type: type },
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': C.csrf() },
        }).done(function (r) {
            if (r && r.success) { toastr.success('Correo enviado a ' + ((r.data && r.data.to) || 'el cliente') + '.'); }
            else { toastr.warning((r && r.message) || 'No se pudo enviar el correo.'); }
        }).fail(function (xhr) {
            toastr.error(C.errorMessage(xhr, 'No se pudo enviar el correo.'));
        }).always(function () { $btn.prop('disabled', false).text('Enviar'); });
    });

    // ── Pestaña Notas (nota interna real vía order.add_note) ──
    function renderNotas(order) {
        $('#powPanelNotas').html(
            '<div class="bv-po-card">' +
                '<div class="bv-po-card-h"><span class="bv-po-sec-ic"><i class="fas fa-pen-to-square"></i></span>' +
                    '<div class="bv-po-card-ht"><span class="t">Nota interna</span><span class="s">Visible en el back office de PrestaShop</span></div></div>' +
                '<div class="bv-po-field"><textarea class="bv-po-input bv-po-textarea" id="powNote" rows="4" placeholder="Escribe una nota interna del pedido…"></textarea></div>' +
                '<button type="button" class="btn-primary bv-po-btn" id="powAddNote">Añadir nota</button>' +
            '</div>'
        );
    }

    function toggleStateWarn() {
        var id = parseInt($('#powStateSelect').val(), 10);
        var st = (_states || []).filter(function (s) { return s.id === id; })[0];
        var risky = st && (st.shipped || st.delivery);
        $('#powStateWarn').toggleClass('bv-hidden', !risky);
    }

    // ── Pestaña Envío (asignar seguimiento) ──
    function renderEnvio(order) {
        var tr = (order.tracking && order.tracking[0]) || null;
        var current = tr
            ? '<div class="bv-po-track"><div class="carrier"><i class="fas fa-truck-fast"></i></div>' +
                '<div class="body"><div class="c">' + esc(tr.carrier_name || tr.carrier || 'Transportista') + '</div>' +
                '<div class="t">Seguimiento: ' + esc(tr.tracking_number || '—') + '</div></div></div>'
            : '<div class="bv-po-track empty"><div class="carrier"><i class="fas fa-truck"></i></div>' +
                '<div class="body"><div class="c">Sin seguimiento asignado</div><div class="t">Asigna un número de seguimiento</div></div></div>';

        $('#powPanelEnvio').html(
            '<div class="bv-po-card">' +
                '<div class="bv-po-card-h"><span class="bv-po-sec-ic"><i class="fas fa-truck"></i></span>' +
                    '<div class="bv-po-card-ht"><span class="t">Envío</span><span class="s">Transportista y seguimiento</span></div></div>' +
                current +
                '<div class="bv-po-field"><label class="bv-po-lbl">Número de seguimiento</label>' +
                    '<input type="text" class="bv-po-input" id="powTracking" maxlength="64" placeholder="Ej. 1Z999AA10123456784"></div>' +
                '<button type="button" class="btn-primary bv-po-btn" id="powApplyTracking">Asignar seguimiento</button>' +
            '</div>'
        );
        if (tr && tr.tracking_number) { $('#powTracking').val(tr.tracking_number); }
    }

    // ── Pestaña Cliente (dirección de envío) ──
    function renderCliente(order) {
        var a = order.shipping_address || {};
        var c = C.customer();
        var addr = [a.address1, a.address2].filter(Boolean).map(esc).join('<br>');
        var loc = [a.postcode, a.city].filter(Boolean).map(esc).join(' ');
        var line = [loc, a.state, a.country].filter(Boolean).map(esc).join(' · ');

        var prev = prevOrders();
        var spent = prev.reduce(function (s, o) { return s + (o.total || 0); }, 0);

        var statsCard = '<div class="bv-po-card">' +
            '<div class="bv-po-card-h"><span class="bv-po-sec-ic"><i class="fas fa-chart-simple"></i></span><div class="bv-po-card-ht"><span class="t">Perfil del cliente</span></div></div>' +
            '<div class="bv-cw-stats">' +
                '<div class="st"><div class="v">' + prev.length + '</div><div class="k">Pedidos</div></div>' +
                '<div class="st"><div class="v">' + money(spent) + '</div><div class="k">Gastado</div></div>' +
                '<div class="st"><div class="v">' + (order.currency || '€') + '</div><div class="k">Moneda</div></div>' +
            '</div></div>';

        // Pedidos anteriores del cliente (reutiliza los ya cargados en el inbox).
        var others = prev.filter(function (o) { return String(o.id) !== String(order.id); });
        var prevCard = '';
        if (others.length) {
            prevCard = '<div class="bv-po-card">' +
                '<div class="bv-po-card-h"><span class="bv-po-sec-ic"><i class="fas fa-clock-rotate-left"></i></span><div class="bv-po-card-ht"><span class="t">Pedidos anteriores</span><span class="s">' + others.length + ' pedido(s)</span></div></div>' +
                others.map(function (o) {
                    return '<div class="bv-cw-ord bv-po-prevord" data-prev-id="' + esc(o.id) + '"><div class="oi"><i class="fas fa-box"></i></div>' +
                        '<div class="ob"><div class="n">' + esc(o.ref) + '</div><div class="m">' + esc(o.status) + (o.date ? ' · ' + esc(o.date) : '') + '</div></div>' +
                        '<div class="oa">' + money(o.total) + '</div><i class="fas fa-chevron-right" style="color:#9ca3af;font-size:10px"></i></div>';
                }).join('') + '</div>';
        }

        $('#powPanelCliente').html(
            statsCard +
            '<div class="bv-po-card">' +
                '<div class="bv-po-card-h"><span class="bv-po-sec-ic"><i class="far fa-address-card"></i></span>' +
                    '<div class="bv-po-card-ht"><span class="t">Cliente</span><span class="s">Datos de contacto</span></div></div>' +
                '<div class="bv-po-kv">' +
                    (c.name ? '<div><div class="k">Nombre</div><div class="v">' + esc(c.name) + '</div></div>' : '') +
                    (c.email ? '<div><div class="k">Correo</div><div class="v mono">' + esc(c.email) + '</div></div>' : '') +
                    ((c.phone || a.phone) ? '<div><div class="k">Teléfono</div><div class="v mono">' + esc(c.phone || a.phone) + '</div></div>' : '') +
                '</div>' +
            '</div>' +
            prevCard +
            '<div class="bv-po-card">' +
                '<div class="bv-po-card-h"><span class="bv-po-sec-ic"><i class="fas fa-location-dot"></i></span>' +
                    '<div class="bv-po-card-ht"><span class="t">Dirección de envío</span></div>' +
                    '<button type="button" class="btn-secondary btn-sm" id="powAddrEdit">Editar</button></div>' +
                (addr || loc || line
                    ? '<div class="bv-po-addr">' + (addr || '') + (addr && (loc || line) ? '<br>' : '') + line + '</div>'
                    : '<div class="bv-po-empty">Sin dirección de envío.</div>') +
                '<div class="bv-po-addrpicker bv-hidden" id="powAddrPicker"></div>' +
            '</div>'
        );
    }

    // ── Selector de direcciones del cliente (ver + cambiar la del pedido) ──
    $(document).on('click', '#powAddrEdit', function () {
        var $p = $('#powAddrPicker');
        if (!$p.hasClass('bv-hidden')) { $p.addClass('bv-hidden'); return; }
        $p.removeClass('bv-hidden').html('<div class="bv-oc-loading"><i class="fas fa-spinner fa-spin"></i> Cargando direcciones…</div>');
        var base = C.base();
        $.ajax({ url: base + '/ps/addresses', method: 'GET', dataType: 'json',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': C.csrf() } })
        .done(function (r) {
            var list = r.addresses || r.data || [];
            if (!list.length) { $p.html('<div class="bv-po-empty">El cliente no tiene direcciones guardadas.</div>'); return; }
            $p.html(list.map(function (a) {
                var body = [a.address1 || a.address, a.address2].filter(Boolean).map(esc).join(', ');
                var loc2 = [a.postcode, a.city, a.country].filter(Boolean).map(esc).join(' ');
                return '<label class="bv-po-addropt"><input type="radio" name="powAddr" value="' + esc(a.id) + '">' +
                    '<span class="ab"><span class="al">' + esc(a.alias || 'Dirección') + '</span>' +
                    '<span class="an">' + esc((a.firstname || '') + ' ' + (a.lastname || '')) + '</span>' +
                    '<span class="aln">' + body + (body && loc2 ? '<br>' : '') + loc2 + '</span></span></label>';
            }).join('') + '<button type="button" class="btn-primary btn-sm bv-po-btn" id="powAddrSave">Guardar dirección</button>');
        }).fail(function () { $p.html('<div class="bv-po-empty">No se pudieron cargar las direcciones.</div>'); });
    });

    $(document).on('click', '#powAddrSave', function () {
        if (!_orderId) { return; }
        var addrId = $('#powAddrPicker input[name="powAddr"]:checked').val();
        if (!addrId) { toastr.warning('Selecciona una dirección.'); return; }
        var $btn = $(this).prop('disabled', true);
        $.ajax({
            url: '/panel/helpdesk/customers/' + C.customerId() + '/ps/orders/' + _orderId + '/address',
            method: 'POST', dataType: 'json',
            data: { address_id: addrId, type: 'delivery' },
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': C.csrf() },
        }).done(function (r) {
            if (r && r.success) { toastr.success('Dirección de envío actualizada.'); loadOrder(_orderId); }
            else { toastr.warning((r && r.message) || 'No se pudo cambiar la dirección.'); }
        }).fail(function (xhr) {
            toastr.error(C.errorMessage(xhr, 'No se pudo cambiar la dirección.'));
        }).always(function () { $btn.prop('disabled', false); });
    });

    // Pedidos del cliente ya cargados en el panel del inbox (PrestaShop).
    function prevOrders() {
        return $('.rp3-order[data-order-platform="prestashop"]').map(function () {
            var $o = $(this);
            var raw = String($o.data('order-total') || '0').replace(/\./g, '').replace(',', '.');
            return { id: $o.data('order-id'), ref: $o.data('order-ref') || ('#' + $o.data('order-id')),
                status: $o.data('order-status') || '', date: $o.data('order-date') || '', total: parseFloat(raw) || 0 };
        }).get();
    }

    // Abrir otro pedido del cliente sin cerrar el modal.
    $(document).on('click', '.bv-po-prevord', function () {
        var id = $(this).data('prev-id');
        if (id && String(id) !== String(_orderId)) { window.openPsOrderWorkspace(id); }
    });

    // ── Pestaña Pago ──
    function renderPago(order) {
        var pays = order.payments || [];
        if (!pays.length) {
            $('#powPanelPago').html('<div class="bv-po-card"><div class="bv-po-empty">Sin información de pago.</div></div>');
            return;
        }
        var rows = pays.map(function (p) {
            return '<div class="bv-po-kv">' +
                '<div><div class="k">Método</div><div class="v">' + esc(p.payment_method) + '</div></div>' +
                '<div><div class="k">Importe</div><div class="v mono">' + money(p.amount) + '</div></div>' +
                (p.transaction_id ? '<div><div class="k">Transacción</div><div class="v mono">' + esc(p.transaction_id) + '</div></div>' : '') +
                (p.date_add ? '<div><div class="k">Fecha</div><div class="v mono">' + fmtDate(p.date_add) + '</div></div>' : '') +
            '</div>';
        }).join('<div class="bv-po-sep"></div>');
        // Devolución: selector de líneas (usa order_detail_id = línea.id) → start_return.
        var lines = order.lines || [];
        var returnCard = '';
        if (lines.length) {
            returnCard = '<div class="bv-po-card">' +
                '<div class="bv-po-card-h"><span class="bv-po-sec-ic"><i class="fas fa-rotate-left"></i></span><div class="bv-po-card-ht"><span class="t">Devolución</span><span class="s">Iniciar una devolución de este pedido</span></div></div>' +
                '<button type="button" class="btn-secondary bv-po-btn" id="powReturnToggle">Iniciar devolución</button>' +
                '<div class="bv-po-return bv-hidden" id="powReturnForm">' +
                    lines.map(function (l) {
                        return '<label class="bv-po-retline"><input type="checkbox" class="powRetChk" data-line="' + l.id + '" data-max="' + (l.quantity || 1) + '" checked>' +
                            '<span class="nm">' + esc(l.name) + '</span>' +
                            '<span class="qty"><input type="number" class="powRetQty" min="1" max="' + (l.quantity || 1) + '" value="' + (l.quantity || 1) + '"></span></label>';
                    }).join('') +
                    '<div class="bv-po-retfoot"><button type="button" class="btn-primary btn-sm" id="powReturnConfirm">Confirmar devolución</button>' +
                    '<button type="button" class="btn-secondary btn-sm" id="powReturnCancel">Cancelar</button></div>' +
                '</div>' +
            '</div>';
        }

        $('#powPanelPago').html(
            '<div class="bv-po-card">' +
                '<div class="bv-po-card-h"><span class="bv-po-sec-ic"><i class="fas fa-credit-card"></i></span>' +
                    '<div class="bv-po-card-ht"><span class="t">Pago</span><span class="s">' + pays.length + ' movimiento(s)</span></div></div>' +
                rows +
            '</div>' + returnCard
        );
    }

    $(document).on('click', '#powReturnToggle', function () { $('#powReturnForm').toggleClass('bv-hidden'); });
    $(document).on('click', '#powReturnCancel', function () { $('#powReturnForm').addClass('bv-hidden'); });
    $(document).on('click', '#powReturnConfirm', function () {
        if (!_orderId) { return; }
        var items = [];
        $('#powReturnForm .powRetChk:checked').each(function () {
            var $row = $(this).closest('.bv-po-retline');
            var qty = parseInt($row.find('.powRetQty').val(), 10) || 1;
            items.push({ order_detail_id: parseInt($(this).data('line'), 10), quantity: qty });
        });
        if (!items.length) { toastr.warning('Selecciona al menos un artículo.'); return; }
        var $btn = $(this).prop('disabled', true);
        $.ajax({
            url: '/panel/helpdesk/customers/' + C.customerId() + '/ps/orders/' + _orderId + '/return',
            method: 'POST', dataType: 'json',
            data: { items: items },
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': C.csrf() },
        }).done(function (r) {
            if (r && r.success) { toastr.success('Devolución iniciada.'); $('#powReturnForm').addClass('bv-hidden'); loadOrder(_orderId); }
            else { toastr.warning((r && r.message) || 'No se pudo iniciar la devolución.'); }
        }).fail(function (xhr) {
            toastr.error(C.errorMessage(xhr, 'No se pudo iniciar la devolución.'));
        }).always(function () { $btn.prop('disabled', false); });
    });

    // ── Pestaña Historial (timeline real, estado + pago, con filtro) ──
    function renderHistorial(order) {
        // Fusiona cambios de estado y pagos en una sola línea de tiempo ordenada.
        var events = [];
        (order.history || []).forEach(function (h) {
            events.push({ kind: 'estado', lbl: h.state_name, at: h.date, color: h.color || '#71717a' });
        });
        (order.payments || []).forEach(function (p) {
            events.push({ kind: 'pago', lbl: p.payment_method + ' · ' + money(p.amount), at: p.date_add, color: '#90bb13' });
        });
        events.sort(function (a, b) { return new Date(b.at || 0) - new Date(a.at || 0); });

        if (!events.length) {
            $('#powPanelHistorial').html('<div class="bv-po-card"><div class="bv-po-empty">Sin historial.</div></div>');
            return;
        }
        var items = events.map(function (e, i) {
            var chip = e.kind === 'pago' ? '<span class="bv-po-tl-chip"><i class="fas fa-credit-card"></i> Pago</span>' : '';
            return '<div class="bv-po-tl-item bv-po-tl-f" data-tlk="' + e.kind + '">' +
                '<div class="bv-po-tl-dot' + (i === 0 ? ' ok' : '') + '"></div>' +
                '<div class="bv-po-tl-lbl">' + esc(e.lbl) + chip + '</div>' +
                '<div class="bv-po-tl-sub">' + fmtDate(e.at) + '</div>' +
            '</div>';
        }).join('');
        $('#powPanelHistorial').html(
            '<div class="bv-po-card">' +
                '<div class="bv-po-card-h"><span class="bv-po-sec-ic"><i class="fas fa-clock-rotate-left"></i></span>' +
                    '<div class="bv-po-card-ht"><span class="t">Historial</span><span class="s">Estados y pagos</span></div></div>' +
                '<div class="bv-po-tlfilter" id="powTlFilter">' +
                    '<button type="button" class="on" data-tlf="all">Todo</button>' +
                    '<button type="button" data-tlf="estado">Estado</button>' +
                    '<button type="button" data-tlf="pago">Pago</button>' +
                '</div>' +
                '<div class="bv-po-tl">' + items + '</div>' +
            '</div>'
        );
    }

    $(document).on('click', '#powTlFilter button', function () {
        var f = $(this).data('tlf');
        $('#powTlFilter button').removeClass('on'); $(this).addClass('on');
        $('#powPanelHistorial .bv-po-tl-f').each(function () { $(this).toggle(f === 'all' || $(this).data('tlk') === f); });
    });

    // ── Carga ──
    function loadStates(cb) {
        if (_states) { cb(); return; }
        $.ajax({ url: '/panel/helpdesk/ps/order-states', method: 'GET', dataType: 'json',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': C.csrf() } })
            .done(function (r) { _states = r.states || []; })
            .always(function () { _states = _states || []; cb(); });
    }

    function loadOrder(orderId) {
        _orderId = orderId;
        setLoading();
        var email = C.customer().email || '';
        loadStates(function () {
            $.ajax({
                url: '/panel/helpdesk/ps/orders/' + orderId + '/detail',
                method: 'GET', dataType: 'json',
                data: { email: email },
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': C.csrf() },
            }).done(function (r) {
                if (r && r.success && r.data) { render(r.data); }
                else { setError('El pedido no está disponible.'); }
            }).fail(function (xhr) {
                setError(xhr.status === 503 ? 'PrestaShop no responde ahora mismo.' : 'No se pudo cargar el pedido.');
            });
        });
    }

    // ── Pestañas ──
    $(document).on('click', '#powTabs .bv-po-tab', function () {
        var go = $(this).data('po-tab');
        $('#powTabs .bv-po-tab').removeClass('on');
        $(this).addClass('on');
        $body().find('.bv-po-panel').addClass('bv-hidden').filter('[data-po-panel="' + go + '"]').removeClass('bv-hidden');
    });

    // ── Aviso de estado con efectos ──
    $(document).on('change', '#powStateSelect', toggleStateWarn);

    // ── Aplicar cambio de estado ──
    $(document).on('click', '#powApplyState', function () {
        if (!_orderId) { return; }
        var stateId = parseInt($('#powStateSelect').val(), 10);
        var notify = $('#powNotify').is(':checked');
        var $btn = $(this).prop('disabled', true);
        $.ajax({
            url: '/panel/helpdesk/customers/' + C.customerId() + '/ps/orders/' + _orderId + '/status',
            method: 'POST', dataType: 'json',
            data: { state_id: stateId, notify: notify ? 1 : 0 },
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': C.csrf() },
        }).done(function (r) {
            if (r && r.success) {
                toastr.success(r.data && r.data.changed === false ? 'El pedido ya estaba en ese estado.' : 'Estado actualizado.');
                loadOrder(_orderId); // refresca pill, historial y detalle
            } else {
                toastr.warning((r && r.message) || 'No se pudo cambiar el estado.');
            }
        }).fail(function (xhr) {
            toastr.error(C.errorMessage(xhr, 'No se pudo cambiar el estado.'));
        }).always(function () { $btn.prop('disabled', false); });
    });

    // ── Asignar seguimiento ──
    $(document).on('click', '#powApplyTracking', function () {
        if (!_orderId) { return; }
        var tracking = ($('#powTracking').val() || '').trim();
        if (!tracking) { toastr.warning('Introduce un número de seguimiento.'); return; }
        var $btn = $(this).prop('disabled', true);
        $.ajax({
            url: '/panel/helpdesk/customers/' + C.customerId() + '/ps/orders/' + _orderId + '/tracking',
            method: 'POST', dataType: 'json',
            data: { tracking_number: tracking },
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': C.csrf() },
        }).done(function (r) {
            if (r && r.success) {
                toastr.success('Seguimiento asignado.');
                loadOrder(_orderId);
            } else {
                toastr.warning((r && r.message) || 'No se pudo asignar el seguimiento.');
            }
        }).fail(function (xhr) {
            toastr.error(C.errorMessage(xhr, 'No se pudo asignar el seguimiento.'));
        }).always(function () { $btn.prop('disabled', false); });
    });

    // ── Añadir nota interna al pedido (real, order.add_note del bridge) ──
    $(document).on('click', '#powAddNote', function () {
        if (!_orderId) { return; }
        var note = ($('#powNote').val() || '').trim();
        if (!note) { toastr.warning('Escribe una nota.'); return; }
        var $btn = $(this).prop('disabled', true);
        $.ajax({
            url: '/panel/helpdesk/customers/' + C.customerId() + '/ps/orders/' + _orderId + '/note',
            method: 'POST', dataType: 'json',
            data: { note: note },
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': C.csrf() },
        }).done(function (r) {
            if (r && r.success) { toastr.success('Nota añadida al pedido.'); $('#powNote').val(''); }
            else { toastr.warning((r && r.message) || 'No se pudo añadir la nota.'); }
        }).fail(function (xhr) {
            toastr.error(C.errorMessage(xhr, 'No se pudo añadir la nota.'));
        }).always(function () { $btn.prop('disabled', false); });
    });

    // ── API pública ──
    window.openPsOrderWorkspace = function (orderId) {
        if (!orderId) { return; }
        if (!C.customerId()) { if (window.toastr) { toastr.warning('Selecciona una conversación con cliente.'); } return; }
        // Reset a pestaña Estado
        $('#powTabs .bv-po-tab').removeClass('on').filter('[data-po-tab="estado"]').addClass('on');
        $body().find('.bv-po-panel').addClass('bv-hidden').filter('[data-po-panel="estado"]').removeClass('bv-hidden');
        C.open('ps-order-workspace');
        loadOrder(orderId);
    };
})();
