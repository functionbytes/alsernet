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
    // Mismo formato que el resto de piezas PrestaShop ("1.234,56 €"); el
    // HDCommerce.money del core ("€ 1.234,56") lo comparten otros módulos.
    function money(n) { return window.PscStore ? window.PscStore.money(n) : C.money(n); }
    function ctx() { return (window.PscStore && window.PscStore.ctx()) || {}; }
    function stateOf(order) {
        return ((_states || []).filter(function (s) { return s.id === order.state_id; })[0]) || {};
    }
    // Pedido del listado del contexto (trae tracking_url y quantity_returned,
    // que order.detail no incluye).
    function ctxOrder(id) {
        return (ctx().orders || []).filter(function (o) { return String(o.id) === String(id); })[0] || null;
    }
    // Seguimiento coherente: el nº sale de order.detail (fresco); el enlace
    // del listado solo se usa si es del MISMO nº (el listado puede venir de
    // caché y apuntar a un envío anterior).
    function trackingInfo(order) {
        var tr = (order.tracking || [])[0] || {};
        var listed = ctxOrder(order.id);
        var ltr = (listed && (listed.tracking || [])[0]) || {};
        var number = tr.tracking_number || ltr.tracking_number || '';
        var sameNumber = number && ltr.tracking_number && String(ltr.tracking_number) === String(number);
        return {
            number: number,
            carrier: (sameNumber && ltr.carrier_name) || tr.carrier_name || ltr.carrier_name || '',
            url: sameNumber ? (ltr.tracking_url || '') : '',
        };
    }

    function insertComposer(text) {
        if (window.PscStore && window.PscStore.insert(text)) {
            toastr.info('Insertado en el composer. Revísalo antes de enviar.');
        }
    }
    function esc(s) { return C.esc(s); }

    function fmtDate(s) {
        if (!s) { return '—'; }
        var d = new Date(String(s).replace(' ', 'T'));
        if (isNaN(d.getTime())) { return esc(s); }
        return d.toLocaleDateString('es-ES', { day: '2-digit', month: 'short', year: 'numeric' }) +
            ' ' + d.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
    }

    // Pill de estado con el color real de PrestaShop (fondo suave + texto)
    // Pill de estado en la paleta del chat (sin el color libre de PS, que
    // puede ser rojo/azul): misma familia que el listado de pedidos.
    function statusPill(name, color) {
        var kind = window.PscStore ? window.PscStore.orderKind({ state_name: name, state: stateOf(_order || {}) }) : 'pending';
        var tag = window.PscStore ? window.PscStore.orderKindTag(kind) : 'psc-tag--pending';
        return '<span class="psc-tag ' + tag + '">' + esc(name || '—') + '</span>';
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
        $('#powInsertCard').removeClass('bv-hidden');
        renderEstado(order);
        renderEnvio(order);
        renderCliente(order);
        renderPago(order);
        renderCorreos(order);
        renderNotas(order);
        renderHistorial(order);
        loadDocuments();
        setReady();
        // Punto de extensión: las piezas en js/ext/*.js añaden tarjetas a los
        // paneles (#powPanelEstado, #powPanelEnvio, #powPanelPago, …) o sus
        // propias hojas (.ps-sheet dentro de .bv-po-body) al recibir esto.
        $(document).trigger('psc:order-rendered', [order, window.PscOrderWorkspace]);
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
        $('#powSummary').text(lines.length + (lines.length === 1 ? ' artículo · ' : ' artículos · ') + units + (units === 1 ? ' unidad' : ' unidades'));

        if (!lines.length) {
            $('#powLines').html('<div class="bv-po-empty">Este pedido no tiene líneas.</div>');
            return;
        }
        $('#powLines').html(lines.map(function (l) {
            return '<div class="bv-po-line">' +
                '<div class="thumb psc-thumb">' + (l.image_url ? '<img src="' + esc(l.image_url) + '" alt="" loading="lazy">' : '<i class="fas fa-box"></i>') + '</div>' +
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
        // Solo con el pedido cobrado y sin enviar (shipStateFor): sugerir
        // "Marcar enviado" en uno pendiente de pago invitaba a enviar sin cobrar.
        var shipState = shipStateFor(order);
        var cta = '';
        if (shipState) {
            var shipNotify = shipState.notify_default !== null && shipState.notify_default !== undefined ? !!shipState.notify_default : !!shipState.send_email;
            cta = '<div class="bv-po-cta ok"><div class="ic"><i class="fas fa-box-open"></i></div>' +
                '<div class="tx"><div class="t">Listo para enviar</div><div class="s">' +
                    (shipNotify && shipState.send_email ? 'Marca el pedido como enviado y envía al cliente el correo «' + esc(shipState.template_label || shipState.template) + '»' : 'Marca el pedido como enviado (sin correo al cliente)') +
                '</div></div>' +
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
                '<div class="psc-state-info" id="powStateInfo"></div>' +
                '<label class="bv-po-check"><input type="checkbox" id="powNotify"> Notificar al cliente el cambio</label>' +
                '<button type="button" class="btn-primary bv-po-btn" id="powApplyState">Aplicar cambio de estado</button>' +
            '</div>' +
            renderCancelCard(order) +
            meta
        );
        toggleStateWarn();
    }

    // "Marcar enviado" en 1 clic (cambia al estado Enviado + notifica).
    $(document).on('click', '#powMarkShipped', function () {
        if (!_orderId) { return; }
        var stateId = parseInt($(this).data('ship'), 10);
        var ship = (_states || []).filter(function (s) { return s.id === stateId; })[0] || {};
        var notify = ship.notify_default !== null && ship.notify_default !== undefined ? !!ship.notify_default : !!ship.send_email;
        var $btn = $(this).prop('disabled', true);
        $.ajax({
            url: '/panel/helpdesk/customers/' + C.customerId() + '/ps/orders/' + _orderId + '/status',
            method: 'POST', dataType: 'json',
            data: { state_id: stateId, notify: notify ? 1 : 0 },
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

    // Qué pasa al aplicar el estado elegido: correo real de PrestaShop
    // (order_state.send_email/template), factura/albarán y el aviso
    // configurado en Ajustes → Avisos de cambio de estado. "Notificar" se
    // marca según esa configuración (o según PrestaShop si no hay).
    function toggleStateWarn() {
        var id = parseInt($('#powStateSelect').val(), 10);
        var st = (_states || []).filter(function (s) { return s.id === id; })[0];
        var $info = $('#powStateInfo');
        if (!st || !_order || id === _order.state_id) {
            $info.empty();
            return;
        }
        var notes = [];
        if (st.send_email) {
            notes.push('<div class="psc-note psc-note--info"><span class="psc-note-txt">Con «Notificar al cliente» marcado, PrestaShop le enviará el correo <b>' +
                esc(st.template_label || st.template || 'del estado') + '</b>. Sin marcar no se envía nada.</span></div>');
        } else {
            notes.push('<div class="psc-note psc-note--info"><span class="psc-note-txt">Este estado no envía ningún correo al cliente, aunque marques «Notificar».</span></div>');
        }
        if (st.invoice || st.shipped || st.delivery) {
            notes.push('<div class="psc-note psc-note--lock"><span class="psc-note-txt">Este estado genera factura o albarán en PrestaShop.</span></div>');
        }
        if (st.agent_notice) {
            notes.push('<div class="psc-note psc-note--warn"><span class="psc-note-txt">' + esc(st.agent_notice) + '</span></div>');
        }
        $info.html(notes.join(''));
        var notify = st.notify_default !== null && st.notify_default !== undefined ? !!st.notify_default : !!st.send_email;
        $('#powNotify').prop('checked', notify);
    }

    function shipStateFor(order) {
        var st = stateOf(order);
        if (!st.paid || st.shipped || st.delivery) { return null; }
        // Estado estándar "Enviado" de PrestaShop (PS_OS_SHIPPING = 4).
        return (_states || []).filter(function (s) { return s.id === 4; })[0] ||
            (_states || []).filter(function (s) { return /^enviado$/i.test(String(s.name).trim()); })[0] || null;
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

        var trackUrl = trackingInfo(order).url;
        var shareBtns = tr && tr.tracking_number
            ? '<div class="bv-po-stack">' +
                '<button type="button" class="btn-primary bv-po-btn" id="powTrackToChat">Enviar seguimiento al chat</button>' +
                (trackUrl ? '<a class="btn-secondary bv-po-btn" href="' + esc(trackUrl) + '" target="_blank" rel="noopener">Ver en la web del transportista</a>' : '') +
              '</div>'
            : '';

        $('#powPanelEnvio').html(
            '<div class="bv-po-card">' +
                '<div class="bv-po-card-h"><span class="bv-po-sec-ic"><i class="fas fa-truck"></i></span>' +
                    '<div class="bv-po-card-ht"><span class="t">Envío</span><span class="s">Transportista y seguimiento</span></div></div>' +
                current + shareBtns +
                '<div class="bv-po-field"><label class="bv-po-lbl">Número de seguimiento</label>' +
                    '<input type="text" class="bv-po-input" id="powTracking" maxlength="64" placeholder="Ej. 1Z999AA10123456784"></div>' +
                (shipStateFor(order)
                    ? '<button type="button" class="btn-primary bv-po-btn" id="powShipOneStep">Asignar, marcar enviado y pegar en el chat</button>' +
                      '<button type="button" class="btn-secondary bv-po-btn" id="powApplyTracking">Solo asignar seguimiento</button>'
                    : '<button type="button" class="btn-primary bv-po-btn" id="powApplyTracking">Asignar seguimiento</button>') +
            '</div>'
        );
        if (tr && tr.tracking_number) { $('#powTracking').val(tr.tracking_number); }
    }

    // ── Pestaña Cliente (dirección de envío) ──
    function renderCliente(order) {
        var a = order.shipping_address || {};
        var c = C.customer();
        var recipient = [a.firstname, a.lastname].filter(Boolean).map(esc).join(' ');
        var addr = [a.address1, a.address2].filter(Boolean).map(esc).join('<br>');
        var loc = [a.postcode, a.city].filter(Boolean).map(esc).join(' ');
        var line = [loc, a.state, a.country].filter(Boolean).map(esc).join(' · ');

        var prev = prevOrders();
        var pc = ctx().customer || {};
        var count = pc.orders_count || prev.length;
        var spent = pc.ltv != null ? pc.ltv : prev.reduce(function (s, o) { return s + (o.total || 0); }, 0);

        var statsCard = '<div class="bv-po-card">' +
            '<div class="bv-po-card-h"><span class="bv-po-sec-ic"><i class="fas fa-chart-simple"></i></span><div class="bv-po-card-ht"><span class="t">Perfil del cliente</span></div>' +
                '<button type="button" class="btn-secondary btn-sm" data-psc-open-customer="dashboard">Ficha</button></div>' +
            '<div class="bv-cw-stats">' +
                '<div class="st"><div class="v">' + count + '</div><div class="k">Pedidos</div></div>' +
                '<div class="st"><div class="v">' + money(spent) + '</div><div class="k">Gastado</div></div>' +
                '<div class="st"><div class="v">' + (count ? money(spent / count) : '—') + '</div><div class="k">Ticket medio</div></div>' +
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
                        '<div class="oa">' + money(o.total) + '</div><i class="fas fa-chevron-right bv-po-chev"></i></div>';
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
                    '<div class="bv-po-card-ht"><span class="t">Dirección de envío</span>' +
                    (recipient || a.company ? '<span class="s">' + (recipient || '') + (recipient && a.company ? ' · ' : '') + (a.company ? esc(a.company) : '') + '</span>' : '') +
                    '</div>' +
                    '<button type="button" class="btn-secondary btn-sm" id="powAddrEdit">Editar</button></div>' +
                (addr || loc || line
                    ? '<div class="bv-po-addr">' + (addr || '') + (addr && (loc || line) ? '<br>' : '') + line + '</div>'
                    : '<div class="bv-po-empty">Sin dirección de envío.</div>') +
            '</div>'
        );
    }

    $(document).on('click', '#powAddrEdit', openAddressSheet);

    // ── Hoja "Cambiar dirección" (#psAddrSheet) — mismo payload que antes:
    // POST .../address con { address_id, type }; ahora el tipo (envío/facturación)
    // se elige en el segmentado en vez de ir fijo a 'delivery'.
    var _addrType = 'delivery';
    var _addrList = [];

    function currentAddressFor(type) {
        if (!_order) { return null; }
        var a = (type === 'invoice' ? _order.billing_address : _order.shipping_address) || null;
        if (a && a.id == null) {
            a.id = type === 'invoice' ? _order.billing_address_id : _order.shipping_address_id;
        }
        return a;
    }

    // Aviso gris oscuro (no bloquea): con el pedido ya en preparación o
    // posterior, el albarán no se reimprime solo.
    function renderAddressWarn() {
        var st = stateOf(_order || {});
        if (st.paid || st.shipped || st.delivery) {
            $('#psAddrWarn').removeClass('bv-hidden').find('.psc-note-txt').text(
                'El pedido ya está «' + (_order.state_name || '') + '». Cambiar la dirección ahora no reimprime el albarán: avisa a logística.');
        } else {
            $('#psAddrWarn').addClass('bv-hidden');
        }
    }

    function renderCurrentAddressCard() {
        var a = currentAddressFor(_addrType);
        if (!a) { $('#psAddrCurrent').empty(); return; }
        var name = [a.firstname, a.lastname].filter(Boolean).join(' ');
        var line = [a.address1 || a.address, a.address2, a.postcode, a.city, a.country].filter(Boolean).map(esc).join(' · ');
        $('#psAddrCurrent').html(
            '<div class="ps-addr-current">' +
                '<span class="lbl">Dirección actual</span>' +
                '<span class="nm">' + esc(name) + (a.alias ? ' · ' + esc(a.alias) : '') + '</span>' +
                '<span class="ln">' + line + '</span>' +
            '</div>'
        );
    }

    function openAddressSheet() {
        if (!_order) { return; }
        _addrType = 'delivery';
        $('#psAddrRef').text(_order.reference || ('#' + _order.id));
        $('#psAddrTypeSeg button').removeClass('is-on').filter('[data-addr-type="delivery"]').addClass('is-on');
        $('#psAddrSearch').val('');
        closeAddrNewForm();
        $('#psAddrSave').prop('disabled', true).addClass('is-disabled');
        renderCurrentAddressCard();
        renderAddressWarn();
        loadAddressList();
        openSheet('#psAddrSheet');
    }
    function closeAddressSheet() { closeSheet('#psAddrSheet'); }

    // Las hojas cubren el cuerpo del workspace y esconden su pie ("Cerrar"):
    // una sola botonera visible, sin modal sobre modal.
    function openSheet(sel) {
        $('.ps-sheet').addClass('bv-hidden');
        $(sel).removeClass('bv-hidden');
        $body().find('.bv-po-dialog').addClass('is-sheet-open');
    }
    function closeSheet(sel) {
        $(sel).addClass('bv-hidden');
        $body().find('.bv-po-dialog').removeClass('is-sheet-open');
    }

    function loadAddressList() {
        $('#psAddrList').html('<div class="bv-oc-loading"><i class="fas fa-spinner fa-spin"></i> Cargando direcciones…</div>');
        $.ajax({ url: C.base() + '/ps/addresses', method: 'GET', dataType: 'json',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': C.csrf() } })
        .done(function (r) {
            _addrList = r.addresses || r.data || [];
            renderAddressList();
        }).fail(function () { $('#psAddrList').html('<div class="bv-po-empty">No se pudieron cargar las direcciones.</div>'); });
    }

    function renderAddressList() {
        var current = currentAddressFor(_addrType);
        var currentId = current ? current.id : null;
        var term = ($('#psAddrSearch').val() || '').toLowerCase();
        var rows = _addrList.filter(function (a) {
            if (!term) { return true; }
            var haystack = [a.alias, a.firstname, a.lastname, a.address1 || a.address, a.postcode, a.city].filter(Boolean).join(' ').toLowerCase();
            return haystack.indexOf(term) !== -1;
        });
        if (!rows.length) {
            $('#psAddrList').html('<div class="bv-po-empty">' + (_addrList.length ? 'Sin resultados.' : 'El cliente no tiene direcciones guardadas.') + '</div>');
            return;
        }
        $('#psAddrList').html(rows.map(function (a) {
            var isCurrent = currentId != null && String(a.id) === String(currentId);
            var body = [a.address1 || a.address, a.address2].filter(Boolean).map(esc).join(', ');
            var loc2 = [a.postcode, a.city, a.country].filter(Boolean).map(esc).join(' ');
            return '<label class="ps-addr-opt' + (isCurrent ? ' is-current' : '') + '">' +
                '<input type="radio" name="psAddr" value="' + esc(a.id) + '"' + (isCurrent ? ' disabled' : '') + '>' +
                '<span class="ab">' +
                    '<span class="psc-addr-kind">' + esc(a.alias || 'Dirección') + (isCurrent ? ' · en uso' : '') + '</span>' +
                    '<span class="psc-addr-name">' + esc(((a.firstname || '') + ' ' + (a.lastname || '')).trim()) + '</span>' +
                    '<span class="psc-addr-line">' + body + (body && loc2 ? '<br>' : '') + loc2 + '</span>' +
                '</span>' +
            '</label>';
        }).join(''));
    }

    $(document).on('click', '#psAddrTypeSeg button', function () {
        _addrType = $(this).data('addr-type');
        $('#psAddrTypeSeg button').removeClass('is-on');
        $(this).addClass('is-on');
        renderCurrentAddressCard();
        renderAddressList();
    });
    $(document).on('input', '#psAddrSearch', renderAddressList);
    $(document).on('change', '#psAddrList input[name="psAddr"]', function () {
        $('#psAddrSave').prop('disabled', false).removeClass('is-disabled');
    });
    $(document).on('click', '#psAddrClose, #psAddrCancel', closeAddressSheet);

    $(document).on('click', '#psAddrSave', function () {
        if (!_orderId) { return; }
        var addrId = $('#psAddrList input[name="psAddr"]:checked').val();
        if (!addrId) { toastr.warning('Selecciona una dirección.'); return; }
        var $btn = $(this).prop('disabled', true);
        $.ajax({
            url: '/panel/helpdesk/customers/' + C.customerId() + '/ps/orders/' + _orderId + '/address',
            method: 'POST', dataType: 'json',
            data: { address_id: addrId, type: _addrType },
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': C.csrf() },
        }).done(function (r) {
            if (r && r.success) {
                var label = _addrType === 'invoice' ? 'facturación' : 'envío';
                var picked = _addrList.filter(function (a) { return String(a.id) === String(addrId); })[0] || {};
                toastr.success('Dirección de ' + label + ' actualizada' + (picked.alias ? ' a ' + picked.alias : '') + '.');
                closeAddressSheet();
                loadOrder(_orderId);
            }
            else { toastr.warning((r && r.message) || 'No se pudo cambiar la dirección.'); }
        }).fail(function (xhr) {
            toastr.error(C.errorMessage(xhr, 'No se pudo cambiar la dirección.'));
        }).always(function () { $btn.prop('disabled', false); });
    });

    // ── Sub-formulario "Crear dirección nueva" (pieza 27) — POST real a /ps/addresses.
    // País: países activos de PS (acción address.countries, cacheada en cliente
    // y en Laravel). Provincia: country.states del país elegido, se repuebla al
    // cambiarlo. El bridge revalida la combinación país/provincia/CP/DNI.
    var _addrCountries = null;      // { countries: [...], default_country_id, supports_default }
    var _addrCountriesXhr = null;

    function addrCountry(id) {
        return ((_addrCountries && _addrCountries.countries) || []).filter(function (c) { return String(c.id) === String(id); })[0] || null;
    }

    // select2 con el tema por defecto (el de bootstrap-5 no tiene su CSS
    // cargado en el panel) y el desplegable dentro de la hoja para que no
    // quede detrás del modal.
    function addrSelect2($sel, placeholder) {
        if (!(window.jQuery && $.fn.select2)) { return; }
        if ($sel.data('select2')) { $sel.select2('destroy'); }
        $sel.select2({ dropdownParent: $('#psAddrSheet'), width: '100%', placeholder: placeholder, minimumResultsForSearch: 0 });
    }

    function loadAddrCountries(done) {
        if (_addrCountries) { done(); return; }
        if (_addrCountriesXhr) { _addrCountriesXhr.always(function () { if (_addrCountries) { done(); } }); return; }
        $('#psAddrNewCountry').html('<option value="">Cargando países…</option>').prop('disabled', true);
        _addrCountriesXhr = $.ajax({ url: '/panel/helpdesk/ps/ext/address/countries', method: 'GET', dataType: 'json',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': C.csrf() } })
        .done(function (r) {
            if (r && r.success && (r.countries || []).length) {
                _addrCountries = { countries: r.countries, default_country_id: r.default_country_id, supports_default: !!r.supports_default };
                done();
            } else {
                addrCountriesFailed();
            }
        }).fail(function (xhr) {
            addrCountriesFailed(C.errorMessage(xhr, ''));
        }).always(function () { _addrCountriesXhr = null; });
    }

    function addrCountriesFailed(msg) {
        $('#psAddrNewCountry').html('<option value="">Sin países</option>').prop('disabled', true);
        $('#psAddrNewCountryHint').addClass('is-warn').text(msg || 'No se pudo cargar la lista de países de PrestaShop. Vuelve a abrir el formulario para reintentar.');
        $('#psAddrNewSave').prop('disabled', true).addClass('is-disabled');
    }

    function renderAddrCountries() {
        var def = _addrCountries.default_country_id;
        $('#psAddrNewCountry').prop('disabled', false).html(_addrCountries.countries.map(function (c) {
            return '<option value="' + esc(c.id) + '"' + (String(c.id) === String(def) ? ' selected' : '') + '>' + esc(c.name) + '</option>';
        }).join(''));
        addrSelect2($('#psAddrNewCountry'), 'Elige un país');
        $('#psAddrNewDefaultWrap').toggleClass('bv-hidden', !_addrCountries.supports_default);
        $('#psAddrNewSave').prop('disabled', false).removeClass('is-disabled');
        onAddrCountryChange();
    }

    function onAddrCountryChange() {
        var c = addrCountry($('#psAddrNewCountry').val());
        var $state = $('#psAddrNewState');
        $('#psAddrNewDniWrap').toggleClass('bv-hidden', !(c && c.dni_required));
        $('#psAddrNewPostcode').attr('placeholder', c && c.zip_format ? c.zip_format.replace(/N/g, '0').replace(/L/g, 'A').replace(/C/g, c.iso || '') : '');
        var hint = 'Las provincias se cargan del país elegido.';
        if (c && c.zip_required && c.zip_format) { hint = 'Código postal con formato ' + c.zip_format + ' (N = número, L = letra).'; }
        $('#psAddrNewCountryHint').removeClass('is-warn').text(hint);

        if (!c || !c.has_states) {
            $state.html('');
            $('#psAddrNewStateWrap').addClass('bv-hidden');
            return;
        }
        $('#psAddrNewStateWrap').removeClass('bv-hidden');
        $state.html('<option value="">Cargando…</option>').prop('disabled', true);
        addrSelect2($state, 'Cargando…');
        var countryId = c.id;
        $.ajax({ url: '/panel/helpdesk/ps/country-states', method: 'GET', dataType: 'json', data: { id_country: countryId },
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': C.csrf() } })
        .done(function (r) {
            // El agente pudo cambiar de país mientras llegaba la respuesta.
            if (String($('#psAddrNewCountry').val()) !== String(countryId)) { return; }
            var states = (r && r.states) || [];
            if (!states.length) {
                $state.html('<option value="">Sin provincias disponibles</option>');
                addrSelect2($state, 'Sin provincias disponibles');
                $('#psAddrNewCountryHint').addClass('is-warn').text('PrestaShop no devolvió provincias para este país: no se podrá guardar hasta que responda.');
                return;
            }
            $state.prop('disabled', false).html('<option value=""></option>' + states.map(function (st) {
                return '<option value="' + esc(st.id) + '">' + esc(st.name) + '</option>';
            }).join(''));
            addrSelect2($state, 'Elige provincia o estado');
        }).fail(function () {
            if (String($('#psAddrNewCountry').val()) !== String(countryId)) { return; }
            // select2 no relee las <option> cambiadas: sin reiniciarlo seguiría
            // mostrando "Cargando…".
            $state.html('<option value="">Sin provincias</option>');
            addrSelect2($state, 'Sin provincias');
            $('#psAddrNewCountryHint').addClass('is-warn').text('No se pudieron cargar las provincias de este país.');
        });
    }

    function resetAddrNewForm() {
        $('#psAddrNewForm').find('input[type="text"]').val('');
        $('#psAddrNewDefault').prop('checked', false);
        if (_addrCountries) { renderAddrCountries(); }
    }

    function openAddrNewForm() {
        $('#psAddrNewForm').removeClass('bv-hidden');
        $('#psAddrNewToggle').addClass('bv-hidden');
        // Solo se pinta la primera vez: al reabrir se respeta lo ya elegido.
        // Si la carga falló, el flag no se pone y reabrir reintenta.
        if ($('#psAddrNewCountry').data('psc-ready')) { return; }
        loadAddrCountries(function () {
            renderAddrCountries();
            $('#psAddrNewCountry').data('psc-ready', 1);
        });
    }
    function closeAddrNewForm() {
        $('#psAddrNewForm').addClass('bv-hidden');
        $('#psAddrNewToggle').removeClass('bv-hidden');
    }

    $(document).on('click', '#psAddrNewToggle', function () {
        if ($('#psAddrNewForm').hasClass('bv-hidden')) { openAddrNewForm(); } else { closeAddrNewForm(); }
    });
    $(document).on('click', '#psAddrNewCancel', closeAddrNewForm);
    $(document).on('change', '#psAddrNewCountry', onAddrCountryChange);

    $(document).on('click', '#psAddrNewSave', function () {
        if (!C.customerId()) { return; }
        var country = addrCountry($('#psAddrNewCountry').val());
        var data = {
            alias: ($('#psAddrNewAlias').val() || '').trim(),
            firstname: ($('#psAddrNewFirstname').val() || '').trim(),
            lastname: ($('#psAddrNewLastname').val() || '').trim(),
            company: ($('#psAddrNewCompany').val() || '').trim(),
            address1: ($('#psAddrNewAddress1').val() || '').trim(),
            address2: ($('#psAddrNewAddress2').val() || '').trim(),
            postcode: ($('#psAddrNewPostcode').val() || '').trim(),
            city: ($('#psAddrNewCity').val() || '').trim(),
            phone: ($('#psAddrNewPhone').val() || '').trim(),
            phone_mobile: ($('#psAddrNewPhoneMobile').val() || '').trim(),
            id_country: country ? country.id : '',
            id_state: country && country.has_states ? ($('#psAddrNewState').val() || '') : '',
            dni: country && country.dni_required ? ($('#psAddrNewDni').val() || '').trim() : '',
            default: _addrCountries && _addrCountries.supports_default && $('#psAddrNewDefault').is(':checked') ? 1 : 0,
        };
        if (!data.firstname || !data.lastname || !data.address1 || !data.postcode || !data.city) {
            toastr.warning('Completa nombre, apellidos, dirección, código postal y ciudad.');
            return;
        }
        if (!country) { toastr.warning('Elige el país.'); return; }
        if (country.has_states && !data.id_state) { toastr.warning('Elige la provincia o el estado.'); return; }
        if (country.dni_required && !data.dni) { toastr.warning('Este país exige documento de identidad.'); return; }
        var $btn = $(this).prop('disabled', true);
        $.ajax({
            url: C.base() + '/ps/addresses',
            method: 'POST', dataType: 'json',
            data: data,
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': C.csrf() },
        }).done(function (r) {
            if (r && r.success) {
                toastr.success(r.data && r.data.default ? 'Dirección creada y marcada como dirección de envío por defecto.' : 'Dirección creada.');
                resetAddrNewForm();
                closeAddrNewForm();
                loadAddressList();
                if (window.PscStore) { window.PscStore.refresh(); }
            } else {
                toastr.warning((r && r.message) || 'No se pudo crear la dirección.');
            }
        }).fail(function (xhr) {
            toastr.error(C.errorMessage(xhr, 'No se pudo crear la dirección.'));
        }).always(function () { $btn.prop('disabled', false); });
    });

    // Pedidos del cliente ya cargados en el panel del inbox (PrestaShop).
    function prevOrders() {
        var P = window.PscStore;
        return (ctx().orders || []).map(function (o) {
            return { id: o.id, ref: '#' + (o.reference || o.id), status: P ? P.orderStateName(o) : '',
                date: P ? P.date(o.placed_at, true) : '', total: P ? P.orderTotal(o) : 0 };
        });
    }

    // Abrir otro pedido del cliente sin cerrar el modal.
    $(document).on('click', '.bv-po-prevord', function () {
        var id = $(this).data('prev-id');
        if (id && String(id) !== String(_orderId)) { window.openPsOrderWorkspace(id); }
    });

    // ── Pestaña Pago ──
    function renderPago(order) {
        var pays = order.payments || [];
        var rows = pays.map(function (p) {
            return '<div class="bv-po-kv">' +
                '<div><div class="k">Método</div><div class="v">' + esc(p.payment_method) + '</div></div>' +
                '<div><div class="k">Importe</div><div class="v mono">' + money(p.amount) + '</div></div>' +
                (p.transaction_id ? '<div><div class="k">Transacción</div><div class="v mono">' + esc(p.transaction_id) + '</div></div>' : '') +
                (p.date_add ? '<div><div class="k">Fecha</div><div class="v mono">' + fmtDate(p.date_add) + '</div></div>' : '') +
            '</div>';
        }).join('<div class="bv-po-sep"></div>');
        // Devolución: el botón abre la hoja #psRetSheet a pantalla completa
        // (usa order_detail_id = línea.id) → start_return.
        var returnCard = '';
        // Devolver solo tiene sentido con el pedido cobrado (o ya enviado).
        var payState = stateOf(order);
        if ((order.lines || []).length && (payState.paid || payState.shipped || payState.delivery)) {
            returnCard = '<div class="bv-po-card">' +
                '<div class="bv-po-card-h"><span class="bv-po-sec-ic"><i class="fas fa-rotate-left"></i></span><div class="bv-po-card-ht"><span class="t">Devolución</span><span class="s">Iniciar una devolución de este pedido</span></div></div>' +
                '<button type="button" class="btn-secondary bv-po-btn" id="powReturnToggle">Iniciar devolución</button>' +
            '</div>';
        }
        var method = (ctxOrder(order.id) || {}).payment_method;

        $('#powPanelPago').html(
            '<div class="bv-po-card">' +
                '<div class="bv-po-card-h"><span class="bv-po-sec-ic"><i class="fas fa-credit-card"></i></span>' +
                    '<div class="bv-po-card-ht"><span class="t">Pago</span><span class="s">' + (pays.length ? pays.length + ' movimiento(s)' : esc(method || 'Sin movimientos registrados')) + '</span></div></div>' +
                (rows || '<div class="bv-po-empty">PrestaShop no tiene movimientos de pago registrados para este pedido.</div>') +
            '</div>' + returnCard
        );
    }

    $(document).on('click', '#powReturnToggle', openReturnSheet);

    // ── Hoja "Iniciar devolución" (#psRetSheet) — mismo payload que antes:
    // POST .../return con { items: [{ order_detail_id, quantity }] }.
    // Unidades ya devueltas por línea: las RMA del cliente traen
    // order_detail_id (= línea.id); el listado de pedidos trae
    // quantity_returned por producto como respaldo.
    function returnedByLine(order) {
        var out = {};
        (ctx().returns || []).forEach(function (r) {
            if (String(r.order_id) !== String(order.id)) { return; }
            (r.items || []).forEach(function (it) {
                var k = String(it.order_detail_id);
                out[k] = out[k] || { qty: 0, rma: 'RMA-' + r.id };
                out[k].qty += parseInt(it.quantity, 10) || 0;
            });
        });
        var listed = ctxOrder(order.id);
        (order.lines || []).forEach(function (l) {
            if (out[String(l.id)] || !listed) { return; }
            var match = (listed.lines || []).filter(function (x) {
                return String(x.product_id) === String(l.product_id) && String(x.attribute_id || 0) === String(l.product_attribute_id || 0);
            })[0];
            if (match && parseInt(match.quantity_returned, 10) > 0) {
                out[String(l.id)] = { qty: parseInt(match.quantity_returned, 10), rma: null };
            }
        });
        return out;
    }

    function openReturnSheet() {
        if (!_order) { return; }
        var returned = returnedByLine(_order);
        $('#psRetRef').text('Pedido #' + (_order.reference || _order.id));
        $('#psRetAll').prop('checked', false);
        $('#psRetError').addClass('bv-hidden');
        $('#psRetLines').html((_order.lines || []).map(function (l) {
            var qty = parseInt(l.quantity, 10) || 1;
            var done = returned[String(l.id)];
            var left = Math.max(0, qty - (done ? done.qty : 0));
            var unit = qty ? (parseFloat(l.total) || 0) / qty : 0;
            var sub = left === 0
                ? 'Ya devuelto' + (done && done.rma ? ' en ' + done.rma : '')
                : [l.reference, qty + (qty === 1 ? ' comprada' : ' compradas'), money(unit)].filter(Boolean).join(' · ') +
                  (done ? ' · ' + done.qty + ' ya devuelta' + (done.qty === 1 ? '' : 's') : '');
            return '<label class="ps-ret-line' + (left === 0 ? ' is-done' : '') + '" data-line="' + l.id + '" data-max="' + left + '" data-unit="' + unit + '">' +
                '<input type="checkbox" class="psRetChk"' + (left === 0 ? ' disabled' : '') + '>' +
                '<span class="psc-thumb psc-thumb--md">' + (l.image_url ? '<img src="' + esc(l.image_url) + '" alt="" loading="lazy">' : 'foto') + '</span>' +
                '<span class="nm"><span class="t">' + esc(l.name) + '</span><span class="s">' + esc(sub) + '</span></span>' +
                (left > 0
                    ? '<span class="ps-cart-qty-stepper stepper">' +
                        '<button type="button" class="ps-cart-qty-btn" data-psc-qty="-1" aria-label="Una unidad menos">−</button>' +
                        '<input type="number" class="ps-qty-input psRetQty" value="1" min="1" max="' + left + '">' +
                        '<button type="button" class="ps-cart-qty-btn" data-psc-qty="1" aria-label="Una unidad más">+</button>' +
                      '</span>'
                    : '') +
            '</label>';
        }).join(''));
        recalcReturnSheet();
        $('#psRetReason').val('').trigger('change');
        $('#psRetDetail').val('');
        if (window.jQuery && $.fn.select2 && !$('#psRetReason').data('select2')) {
            $('#psRetReason').select2({ dropdownParent: $('#psRetSheet'), width: '100%', placeholder: 'Selecciona un motivo', minimumResultsForSearch: 0 });
        }
        openSheet('#psRetSheet');
    }
    function closeReturnSheet() { closeSheet('#psRetSheet'); }

    function recalcReturnSheet() {
        var count = 0, units = 0, amount = 0;
        $('#psRetLines .ps-ret-line').each(function () {
            var $line = $(this);
            var on = $line.find('.psRetChk').is(':checked');
            $line.toggleClass('is-on', on);
            if (!on) { return; }
            var qty = parseInt($line.find('.psRetQty').val(), 10) || 1;
            count++;
            units += qty;
            amount += qty * (parseFloat($line.data('unit')) || 0);
        });
        $('#psRetCount').text(count
            ? count + (count === 1 ? ' línea · ' : ' líneas · ') + units + (units === 1 ? ' unidad' : ' unidades')
            : 'Ninguna línea marcada');
        $('#psRetAmount').text(money(amount));
        $('#psRetSubmit').prop('disabled', !count).toggleClass('is-disabled', !count);
    }

    $(document).on('change', '#psRetLines .psRetChk', recalcReturnSheet);
    $(document).on('input', '#psRetLines .psRetQty', recalcReturnSheet);
    $(document).on('click', '#psRetLines .ps-cart-qty-btn', function () {
        var $line = $(this).closest('.ps-ret-line');
        var $input = $line.find('.psRetQty');
        var max = parseInt($line.data('max'), 10) || 1;
        var next = (parseInt($input.val(), 10) || 1) + (parseInt($(this).data('psc-qty'), 10) || 0);
        $input.val(Math.min(max, Math.max(1, next)));
        recalcReturnSheet();
    });
    $(document).on('change', '#psRetAll', function () {
        $('#psRetLines .psRetChk:not(:disabled)').prop('checked', $(this).is(':checked'));
        recalcReturnSheet();
    });
    $(document).on('click', '#psRetClose, #psRetCancel', closeReturnSheet);

    $(document).on('click', '#psRetSubmit', function () {
        if (!_orderId || $(this).is(':disabled')) { return; }
        var items = [];
        $('#psRetLines .ps-ret-line').each(function () {
            var $line = $(this);
            if (!$line.find('.psRetChk').is(':checked')) { return; }
            items.push({
                order_detail_id: parseInt($line.data('line'), 10),
                quantity: parseInt($line.find('.psRetQty').val(), 10) || 1,
            });
        });
        if (!items.length) { toastr.warning('Selecciona al menos un artículo.'); return; }
        // Motivo/detalle: StartOrderReturnRequest no los acepta (PrestaShop no
        // tiene ese campo en este endpoint) — se guardan como nota interna de
        // la conversación, mismo patrón ya confirmado en product-recommend.js
        // (POST al endpoint del composer con is_internal:1), no un evento
        // custom que nadie escucha.
        var reasonText = $('#psRetReason option:selected').text().trim();
        var detailText = $('#psRetDetail').val().trim();
        var noteParts = [];
        if ($('#psRetReason').val()) { noteParts.push('Motivo: ' + reasonText); }
        if (detailText) { noteParts.push(detailText); }

        var $btn = $(this).prop('disabled', true).text('Enviando…');
        var $sheet = $('#psRetSheet').addClass('is-busy');
        $sheet.find('input, select, textarea').prop('disabled', true);
        $('#psRetError').addClass('bv-hidden');
        $.ajax({
            url: '/panel/helpdesk/customers/' + C.customerId() + '/ps/orders/' + _orderId + '/return',
            method: 'POST', dataType: 'json',
            data: { items: items },
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': C.csrf() },
        }).done(function (r) {
            if (!r || !r.success) { showReturnError((r && r.message) || 'No se pudo iniciar la devolución.'); return; }
            var rmaId = r.data && (r.data.return_id || r.data.id);
            toastr.success('Devolución ' + (rmaId ? 'RMA-' + rmaId + ' ' : '') + 'creada. Nota añadida a la conversación.');
            if (window.PscStore) { window.PscStore.reloadReturns(); }
            noteParts.unshift('Pedido #' + (_order.reference || _order.id) + (rmaId ? ' · RMA-' + rmaId : ''));
            if (noteParts.length) {
                var sendUrl = $('.bv-composer').data('bv-send-url');
                if (sendUrl) {
                    $.ajax({
                        url: sendUrl, method: 'POST', dataType: 'json',
                        data: { body: '[PS] Devolución solicitada — ' + noteParts.join(' · '), is_internal: 1, action: 'send' },
                        headers: { 'X-CSRF-TOKEN': C.csrf(), 'Accept': 'application/json' },
                    }).fail(function () { toastr.error('No se pudo guardar la nota interna.'); });
                }
            }
            closeReturnSheet();
            loadOrder(_orderId);
        }).fail(function (xhr) {
            showReturnError(C.errorMessage(xhr, 'No se pudo iniciar la devolución.'));
        }).always(function () {
            $btn.text('Solicitar devolución');
            $sheet.removeClass('is-busy');
            $sheet.find('input, select, textarea').prop('disabled', false);
            $sheet.find('.ps-ret-line.is-done .psRetChk').prop('disabled', true);
            recalcReturnSheet();
        });
    });

    // Error ámbar arriba de la hoja; la selección se conserva.
    function showReturnError(msg) {
        $('#psRetError').removeClass('bv-hidden').find('.psc-note-txt').text('PrestaShop ha rechazado la devolución: ' + msg);
        $('#psRetSheet .ps-sheet-body').scrollTop(0);
    }

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
                '<button type="button" class="btn-secondary bv-po-btn" id="powHistoryNote">Copiar historial como nota</button>' +
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
        // Cliente vinculado por id cuyo email de Helpdesk no coincide con el
        // de la tienda: sin external_id el bridge no resuelve la propiedad
        // del pedido (mismo parámetro que ya manda Contactos 360).
        var psCustomer = ctx().customer || {};
        var query = { email: email };
        if (psCustomer.found && psCustomer.id) { query.external_id = psCustomer.id; }
        loadStates(function () {
            $.ajax({
                url: '/panel/helpdesk/ps/orders/' + orderId + '/detail',
                method: 'GET', dataType: 'json',
                data: query,
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

    // ── Envío en un paso: seguimiento → estado "Enviado" → texto al composer ──
    $(document).on('click', '#powShipOneStep', function () {
        if (!_orderId || !_order) { return; }
        var tracking = ($('#powTracking').val() || '').trim();
        if (!tracking) { toastr.warning('Introduce un número de seguimiento.'); return; }
        var ship = shipStateFor(_order);
        if (!ship) { toastr.warning('Este pedido no se puede marcar como enviado.'); return; }
        var notify = ship.notify_default !== null && ship.notify_default !== undefined ? !!ship.notify_default : !!ship.send_email;
        var $btn = $(this).prop('disabled', true).text('Aplicando…');
        var base = '/panel/helpdesk/customers/' + C.customerId() + '/ps/orders/' + _orderId;
        var headers = { 'Accept': 'application/json', 'X-CSRF-TOKEN': C.csrf() };
        var ref = _order.reference || _order.id;
        var carrier = trackingInfo(_order).carrier;

        $.ajax({ url: base + '/tracking', method: 'POST', dataType: 'json', data: { tracking_number: tracking }, headers: headers })
            .then(function (r) {
                if (!r || !r.success) { return $.Deferred().reject({ responseJSON: r }).promise(); }
                return $.ajax({ url: base + '/status', method: 'POST', dataType: 'json', data: { state_id: ship.id, notify: notify ? 1 : 0 }, headers: headers });
            })
            .done(function (r) {
                if (!r || !r.success) { toastr.warning((r && r.message) || 'Seguimiento asignado, pero no se pudo marcar como enviado.'); loadOrder(_orderId); return; }
                insertComposer('Tu pedido #' + ref + ' ya ha salido' + (carrier ? ' con ' + carrier : '') + '. Número de seguimiento: ' + tracking + '.');
                toastr.success('Seguimiento asignado y pedido marcado como enviado' + (notify && ship.send_email ? ' (correo al cliente enviado).' : '.'));
                if (window.PscStore) { window.PscStore.load(true); }
                loadOrder(_orderId);
            })
            .fail(function (xhr) {
                toastr.error(C.errorMessage(xhr, 'No se pudo completar el envío en un paso.'));
                loadOrder(_orderId);
            })
            .always(function () { $btn.prop('disabled', false).text('Asignar, marcar enviado y pegar en el chat'); });
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

    // ── Pieza 01: seguimiento → composer (transportista, número y enlace) ──
    $(document).on('click', '#powTrackToChat', function () {
        if (!_order) { return; }
        var t = trackingInfo(_order);
        insertComposer('Tu pedido #' + (_order.reference || _order.id) + ' va con ' + (t.carrier || 'el transportista') +
            ', número de seguimiento ' + t.number + '.' + (t.url ? '\nPuedes seguirlo aquí: ' + t.url : ''));
    });

    // ── Pieza 18: tarjeta del pedido como bloque de texto (WhatsApp/email) ──
    $(document).on('click', '#powInsertCard', function () {
        if (!_order) { return; }
        var t = trackingInfo(_order);
        var lines = (_order.lines || []).map(function (l) { return '• ' + l.name + ' ×' + (parseInt(l.quantity, 10) || 1); });
        insertComposer('Pedido #' + (_order.reference || _order.id) + ' — ' + (_order.state_name || '') + '\n' +
            lines.join('\n') + '\nTotal: ' + money((_order.totals || {}).total) +
            (t.url ? '\nSeguimiento: ' + t.url : (t.number ? '\nSeguimiento: ' + t.number : '')));
    });

    // ── Pieza 19: historial de estados → nota interna de la conversación ──
    $(document).on('click', '#powHistoryNote', function () {
        if (!_order) { return; }
        var sendUrl = $('.bv-composer').data('bv-send-url');
        if (!sendUrl) { toastr.warning('Abre una conversación para guardar la nota.'); return; }
        var rows = (_order.history || []).slice().sort(function (a, b) { return new Date(a.date) - new Date(b.date); })
            .map(function (h) { return fmtDate(h.date) + ' · ' + h.state_name; });
        if (!rows.length) { toastr.info('Este pedido no tiene historial de estados.'); return; }
        var $btn = $(this).prop('disabled', true);
        $.ajax({
            url: sendUrl, method: 'POST', dataType: 'json',
            data: { body: '[PS] Historial del pedido #' + (_order.reference || _order.id) + '\n' + rows.join('\n'), is_internal: 1, action: 'send' },
            headers: { 'X-CSRF-TOKEN': C.csrf(), 'Accept': 'application/json' },
        }).done(function () { toastr.success('Historial guardado como nota interna.'); })
          .fail(function (xhr) { toastr.error(C.errorMessage(xhr, 'No se pudo guardar la nota.')); })
          .always(function () { $btn.prop('disabled', false); });
    });

    // ── Pieza 07: anular pedido (cambio real al estado "Cancelado" de PS) ──
    // Estado estándar de cancelación de PrestaShop (PS_OS_CANCELED = 6,
    // "Pedido cancelado"). Hay otros con "cancel"/"anulado" en el nombre
    // (Anulado CEX, Financiación cancelada, seQura…) que son de pasarelas o
    // transportistas y NO deben usarse para anular desde el chat.
    function cancelState() {
        var states = _states || [];
        return states.filter(function (s) { return s.id === 6; })[0] ||
            states.filter(function (s) { return /^(pedido )?cancelado$/i.test(String(s.name).trim()); })[0] || null;
    }

    function renderCancelCard(order) {
        var st = stateOf(order);
        var target = cancelState();
        if (!target || /cancelad|anulad/i.test(order.state_name || '')) { return ''; }
        var blocked = st.shipped || st.delivery;
        return '<div class="bv-po-card">' +
            '<div class="bv-po-card-h"><span class="bv-po-sec-ic"><i class="fas fa-ban"></i></span>' +
                '<div class="bv-po-card-ht"><span class="t">Anular pedido</span><span class="s">' +
                (blocked ? 'Ya enviado: la vía es devolución o reembolso' : 'Pasa a «' + esc(target.name) + '» y libera el stock') + '</span></div></div>' +
            (blocked ? '' : '<button type="button" class="btn-secondary bv-po-btn" id="powCancelOpen">Anular pedido</button>') +
        '</div>';
    }

    $(document).on('click', '#powCancelOpen', function () {
        if (!_order) { return; }
        var ref = String(_order.reference || _order.id);
        var paid = stateOf(_order).paid;
        $('#psCancelRef').text('Pedido #' + ref);
        $('#psCancelTarget').text((cancelState() || {}).name || 'Cancelado');
        $('#psCancelPaid').toggleClass('bv-hidden', !paid).find('.psc-note-txt')
            .text('Pago cobrado: ' + money((_order.totals || {}).total) + ' por reembolsar. El reembolso se emite aparte desde el back-office.');
        $('#psCancelConfirm').val('').attr('placeholder', ref).data('ref', ref);
        $('#psCancelReason').val('');
        $('#psCancelNotify').prop('checked', true);
        $('#psCancelSubmit').prop('disabled', true).addClass('is-disabled');
        openSheet('#psCancelSheet');
    });

    $(document).on('input change', '#psCancelConfirm, #psCancelReason', function () {
        var ok = String($('#psCancelConfirm').val() || '').trim().toUpperCase() === String($('#psCancelConfirm').data('ref')).toUpperCase() &&
            !!$('#psCancelReason').val();
        $('#psCancelSubmit').prop('disabled', !ok).toggleClass('is-disabled', !ok);
    });

    $(document).on('click', '#psCancelClose, #psCancelCancel', function () { closeSheet('#psCancelSheet'); });

    $(document).on('click', '#psCancelSubmit', function () {
        var target = cancelState();
        if (!_orderId || !target || $(this).is(':disabled')) { return; }
        var reason = $('#psCancelReason option:selected').text().trim();
        var $btn = $(this).prop('disabled', true).text('Anulando…');
        $.ajax({
            url: '/panel/helpdesk/customers/' + C.customerId() + '/ps/orders/' + _orderId + '/status',
            method: 'POST', dataType: 'json',
            data: { state_id: target.id, notify: $('#psCancelNotify').is(':checked') ? 1 : 0 },
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': C.csrf() },
        }).done(function (r) {
            if (!r || !r.success) { toastr.warning((r && r.message) || 'No se pudo anular el pedido.'); return; }
            // Motivo como nota interna del pedido (visible para almacén).
            $.ajax({
                url: '/panel/helpdesk/customers/' + C.customerId() + '/ps/orders/' + _orderId + '/note',
                method: 'POST', dataType: 'json', data: { note: 'Anulado desde el chat. Motivo: ' + reason },
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': C.csrf() },
            });
            toastr.success('Pedido anulado.');
            closeSheet('#psCancelSheet');
            if (window.PscStore) { window.PscStore.load(true); }
            loadOrder(_orderId);
        }).fail(function (xhr) {
            toastr.error(C.errorMessage(xhr, 'No se pudo anular el pedido.'));
        }).always(function () { $btn.text('Anular pedido'); });
    });

    // ── API para extensiones (js/ext/*.js) ──
    window.PscOrderWorkspace = {
        order: function () { return _order; },
        orderId: function () { return _orderId; },
        reload: function () { if (_orderId) { loadOrder(_orderId); } },
        states: function () { return _states || []; },
        stateOf: stateOf,
        listedOrder: ctxOrder,
        openSheet: openSheet,
        closeSheet: closeSheet,
        money: money,
        esc: esc,
        fmtDate: fmtDate,
        insert: insertComposer,
        body: $body,
        // Ruta de acción por cliente: /panel/helpdesk/customers/{id}{suffix}
        customerUrl: function (suffix) { return '/panel/helpdesk/customers/' + C.customerId() + suffix; },
        csrf: function () { return C.csrf(); },
        errorMessage: function (xhr, fb) { return C.errorMessage(xhr, fb); },
    };

    // ── API pública ──
    window.openPsOrderWorkspace = function (orderId) {
        if (!orderId) { return; }
        if (!C.customerId()) { if (window.toastr) { toastr.warning('Selecciona una conversación con cliente.'); } return; }
        // Reset a pestaña Estado y hojas cerradas (por si venimos de otro pedido)
        $('#powTabs .bv-po-tab').removeClass('on').filter('[data-po-tab="estado"]').addClass('on');
        $body().find('.bv-po-panel').addClass('bv-hidden').filter('[data-po-panel="estado"]').removeClass('bv-hidden');
        $('.ps-sheet').addClass('bv-hidden');
        $body().find('.bv-po-dialog').removeClass('is-sheet-open');
        $('#powInsertCard').addClass('bv-hidden');
        C.open('ps-order-workspace');
        loadOrder(orderId);
    };
})();
