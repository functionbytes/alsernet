/*!
 * HelpdeskPrestashop · extensión "address" — pieza 05, incidencia de envío
 * (hoja ps-ship-claim dentro del workspace de pedido).
 *
 * - Tarjeta en la pestaña Envío (solo pedidos ya enviados/entregados o con
 *   seguimiento) que abre la hoja #psAddressClaimSheet.
 * - "Abrir reclamación": POST a ps/ext/address/orders/{id}/ship-claim → nota
 *   interna del PEDIDO en PrestaShop (order.add_note). No hay API de
 *   transportista: no se abre ninguna reclamación externa.
 * - "Guardar como nota interna": nota de la conversación (mismo endpoint que
 *   el composer, is_internal=1).
 * - Adjuntos: los que el cliente ya mandó en esta conversación (se leen del
 *   hilo pintado); se referencian por nombre y enlace en la nota.
 *
 * Fuente: modules/HelpdeskPrestashop/public/js/ext/ — copiar a
 * public/modules/helpdeskprestashop/js/ext/ tras editar.
 */
(function () {
    var SHEET = '#psAddressClaimSheet';
    var _order = null;
    var _api = null;
    var _files = [];
    var _idemKey = null;

    var PLACEHOLDERS = {
        not_arrived: 'Ej.: el seguimiento marca entregado pero el cliente no ha recibido el paquete.',
        damaged: 'Ej.: la caja llegó abierta y el producto tiene golpes; el cliente adjunta fotos.',
        incomplete: 'Ej.: faltan unidades o piezas respecto al pedido.',
    };

    // esc() del workspace (jQuery .text().html()) no escapa comillas: para
    // valores dentro de atributos (href/src) hace falta escAttr.
    function esc(s) { return _api ? _api.esc(s) : escAttr(s); }
    function escAttr(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }
    // Solo enlaces http(s): la URL de seguimiento viene del transportista
    // configurado en PrestaShop y no debe poder ser un javascript:.
    function safeUrl(u) { return /^https?:\/\//i.test(String(u || '')) ? String(u) : ''; }
    function $sheet() { return $(SHEET); }

    // La hoja vive en el blade de la extensión; se mueve una sola vez al
    // cuerpo del workspace para cubrirlo como el resto de hojas .ps-sheet.
    function mountSheet(api) {
        var $s = $sheet();
        if (!$s.length || $s.data('psc-mounted')) { return; }
        var $body = api.body().find('.bv-po-body');
        if (!$body.length) { return; }
        $s.appendTo($body).data('psc-mounted', 1);
    }

    function shippedLike(order, api) {
        var st = api.stateOf(order) || {};
        return !!(st.shipped || st.delivery || (order.tracking || []).length);
    }

    function trackingOf(order, api) {
        var tr = (order.tracking || [])[0] || {};
        var listed = api.listedOrder(order.id);
        var ltr = (listed && (listed.tracking || [])[0]) || {};
        var number = tr.tracking_number || '';
        var same = number && ltr.tracking_number && String(ltr.tracking_number) === String(number);
        return {
            carrier: tr.carrier_name || (same && ltr.carrier_name) || '',
            number: number,
            url: same ? safeUrl(ltr.tracking_url) : '',
        };
    }

    function lastState(order) {
        // Fechas "Y-m-d H:i:s" del bridge: se ordenan como texto (new Date()
        // con ese formato da NaN en Safari y el orden quedaría al azar).
        var h = (order.history || []).slice().sort(function (a, b) { return String(b.date || '').localeCompare(String(a.date || '')); })[0];
        if (!h) { return null; }
        var when = window.PscStore ? window.PscStore.date(h.date) : h.date;
        return { name: h.state_name || order.state_name || '', when: when };
    }

    // ── Tarjeta en la pestaña Envío ──
    $(document).on('psc:order-rendered', function (e, order, api) {
        if (!$sheet().length || !order || !api) { return; }
        _api = api;
        mountSheet(api);
        if (!shippedLike(order, api)) { return; }
        $('#powPanelEnvio').append(
            '<div class="bv-po-card">' +
                '<div class="bv-po-card-h"><span class="bv-po-sec-ic"><i class="fas fa-truck-ramp-box"></i></span>' +
                    '<div class="bv-po-card-ht"><span class="t">Incidencia de envío</span><span class="s">No ha llegado, llegó dañado o incompleto</span></div></div>' +
                '<button type="button" class="btn-secondary bv-po-btn" id="psAddressClaimOpenBtn">Registrar incidencia</button>' +
            '</div>'
        );
    });

    // ── Adjuntos que el cliente mandó en esta conversación ──
    // Solo mensajes entrantes que no sean notas internas; imágenes y ficheros.
    function collectCustomerFiles(max) {
        var out = [];
        var seen = {};
        $('.bv-thread .bv-msg.in').each(function () {
            var $msg = $(this);
            if ($msg.find('.bv-bubble.note').length) { return; }
            $msg.find('.bv-attach-thumb, .bv-attach-file').each(function () {
                var url = $(this).attr('href') || '';
                if (!/^https?:\/\//i.test(url) && url.charAt(0) !== '/') { return; }
                var abs = url.charAt(0) === '/' ? window.location.origin + url : url;
                if (seen[abs]) { return; }
                seen[abs] = 1;
                var isImg = $(this).hasClass('bv-attach-thumb');
                var name = isImg ? ($(this).find('img').attr('alt') || '') : $(this).find('.bv-attach-file-name').text();
                name = $.trim(name) || decodeURIComponent(abs.split('/').pop().split('?')[0] || 'adjunto');
                out.push({ url: abs, name: name.slice(0, 120), image: isImg });
            });
        });
        return out.slice(-max);
    }

    function renderFiles() {
        var max = parseInt($sheet().data('max-attachments'), 10) || 10;
        _files = collectCustomerFiles(max);
        if (!_files.length) {
            $('#psAddressClaimFiles').html('<div class="psc-address-empty">El cliente no ha enviado fotos ni ficheros en esta conversación. Pídeselas por el chat si hacen falta para la reclamación.</div>');
            return;
        }
        $('#psAddressClaimFiles').html(
            '<div class="psc-address-files-hd">' + _files.length + (_files.length === 1 ? ' adjunto del mensaje disponible' : ' adjuntos del mensaje disponibles') + '</div>' +
            _files.map(function (f, i) {
                return '<label class="psc-address-file is-on">' +
                    '<input type="checkbox" class="psAddressClaimFile" value="' + i + '" checked>' +
                    '<span class="psc-thumb psc-thumb--md">' + (f.image ? '<img src="' + escAttr(f.url) + '" alt="" loading="lazy">' : '<i class="far fa-file"></i>') + '</span>' +
                    '<span class="psc-address-file-nm">' + esc(f.name) + '</span>' +
                '</label>';
            }).join('')
        );
    }

    function renderShip(order, api) {
        var t = trackingOf(order, api);
        var last = lastState(order);
        $('#psAddressClaimShip').html(
            '<div class="psc-address-ship-col">' +
                '<span class="k">Envío</span>' +
                '<span class="v">' + esc(t.carrier || 'Transportista sin indicar') + '</span>' +
                (t.number
                    ? (t.url
                        ? '<a class="mono psc-address-ship-link" href="' + escAttr(t.url) + '" target="_blank" rel="noopener">' + esc(t.number) + '</a>'
                        : '<span class="mono">' + esc(t.number) + '</span>')
                    : '<span class="psc-address-muted">Sin número de seguimiento</span>') +
            '</div>' +
            '<div class="psc-address-ship-col">' +
                '<span class="k">Último estado en la tienda</span>' +
                (last
                    ? '<span class="v">' + esc(last.name) + '</span><span class="psc-address-muted">' + esc(last.when) + '</span>'
                    : '<span class="psc-address-muted">Sin historial</span>') +
            '</div>'
        );
    }

    function currentType() {
        return $('#psAddressClaimType button.is-on').data('claim-type') || $('#psAddressClaimType button').first().data('claim-type');
    }
    function currentTypeLabel() {
        return $.trim($('#psAddressClaimType button.is-on').text());
    }

    function openSheet() {
        var api = _api || window.PscOrderWorkspace;
        if (!api || !api.order()) { return; }
        _api = api;
        _order = api.order();
        mountSheet(api);
        // Clave nueva por apertura: dos clics en la misma apertura = una nota.
        _idemKey = 'shipclaim-' + _order.id + '-' + Date.now() + '-' + Math.random().toString(36).slice(2, 10);
        $('#psAddressClaimRef').text('#' + (_order.reference || _order.id));
        $('#psAddressClaimType button').removeClass('is-on').first().addClass('is-on');
        $('#psAddressClaimDetail').val('').attr('placeholder', PLACEHOLDERS[currentType()] || '');
        renderShip(_order, api);
        renderFiles();
        api.openSheet(SHEET);
    }
    function closeSheet() {
        if (_api) { _api.closeSheet(SHEET); }
    }

    function selectedFiles() {
        return $('#psAddressClaimFiles .psAddressClaimFile:checked').map(function () {
            return _files[parseInt($(this).val(), 10)];
        }).get().filter(Boolean);
    }

    function detailOrWarn() {
        var detail = $.trim($('#psAddressClaimDetail').val() || '');
        if (detail.length < 3) {
            toastr.warning('Describe la incidencia para la reclamación.');
            $('#psAddressClaimDetail').trigger('focus');
            return null;
        }
        return detail;
    }

    function setBusy(on) {
        $sheet().toggleClass('is-busy', on);
        $('#psAddressClaimSubmit, #psAddressClaimNote').prop('disabled', on).toggleClass('is-disabled', on);
    }

    $(document).on('click', '#psAddressClaimOpenBtn', openSheet);
    $(document).on('click', '#psAddressClaimClose, #psAddressClaimCancel', closeSheet);

    $(document).on('click', '#psAddressClaimType button', function () {
        $('#psAddressClaimType button').removeClass('is-on');
        $(this).addClass('is-on');
        $('#psAddressClaimDetail').attr('placeholder', PLACEHOLDERS[currentType()] || '');
    });
    $(document).on('change', '.psAddressClaimFile', function () {
        $(this).closest('.psc-address-file').toggleClass('is-on', $(this).is(':checked'));
    });

    // ── Abrir reclamación → nota interna del pedido en PrestaShop ──
    $(document).on('click', '#psAddressClaimSubmit', function () {
        if (!_order || !_api) { return; }
        var detail = detailOrWarn();
        if (detail === null) { return; }
        var t = trackingOf(_order, _api);
        var C = window.HDCommerce;
        var url = String($sheet().data('url-template') || '')
            .replace('__CUSTOMER__', encodeURIComponent(C.customerId()))
            .replace('__ORDER__', encodeURIComponent(_order.id));
        var payload = {
            type: currentType(),
            detail: detail,
            carrier: t.carrier || '',
            tracking_number: t.number || '',
            conversation_id: C.conversationId() || '',
            attachments: selectedFiles().map(function (f) { return { name: f.name, url: f.url }; }),
        };
        setBusy(true);
        $.ajax({
            url: url, method: 'POST', dataType: 'json', data: payload,
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': _api.csrf(), 'Idempotency-Key': _idemKey },
        }).done(function (r) {
            if (r && r.success) {
                toastr.success('Incidencia registrada como nota interna del pedido #' + (_order.reference || _order.id) + ' en PrestaShop.');
                closeSheet();
            } else {
                toastr.warning((r && r.message) || 'No se pudo registrar la incidencia.');
            }
        }).fail(function (xhr) {
            toastr.error(_api.errorMessage(xhr, 'No se pudo registrar la incidencia.'));
        }).always(function () { setBusy(false); });
    });

    // ── Guardar como nota interna de la conversación ──
    $(document).on('click', '#psAddressClaimNote', function () {
        if (!_order || !_api) { return; }
        var detail = detailOrWarn();
        if (detail === null) { return; }
        var sendUrl = $('.bv-composer').data('bv-send-url');
        if (!sendUrl) { toastr.warning('Abre una conversación para guardar la nota.'); return; }
        var t = trackingOf(_order, _api);
        var last = lastState(_order);
        var lines = ['[PS] Incidencia de envío · Pedido #' + (_order.reference || _order.id) + ' · ' + currentTypeLabel()];
        var ship = [t.carrier ? 'Transportista: ' + t.carrier : '', t.number ? 'Seguimiento: ' + t.number : ''].filter(Boolean).join(' · ');
        if (ship) { lines.push(ship); }
        if (last) { lines.push('Último estado en la tienda: ' + last.name + ' · ' + last.when); }
        lines.push('Detalle: ' + detail);
        var files = selectedFiles();
        if (files.length) {
            lines.push('Adjuntos del cliente:');
            files.forEach(function (f) { lines.push('- ' + f.name + ' ' + f.url); });
        }
        setBusy(true);
        $.ajax({
            url: sendUrl, method: 'POST', dataType: 'json',
            data: { body: lines.join('\n'), is_internal: 1, action: 'send' },
            headers: { 'X-CSRF-TOKEN': _api.csrf(), 'Accept': 'application/json' },
        }).done(function () {
            toastr.success('Incidencia guardada como nota interna de la conversación.');
            closeSheet();
        }).fail(function (xhr) {
            toastr.error(_api.errorMessage(xhr, 'No se pudo guardar la nota.'));
        }).always(function () { setBusy(false); });
    });
})();
