/*!
 * HelpdeskPrestashop · extensión "orderdocs" (piezas 20 y 22).
 *
 * 20 · Notas del pedido: la pestaña Notas del workspace de pedido lista las
 *      notas internas reales (orders.note + mensajes privados del pedido) con
 *      autor y fecha, y crea notas con order.add_note (endpoint existente),
 *      con la opción de copiarlas como nota interna de la conversación.
 * 22 · Documentos del pedido: hoja interna con albaranes, notas de crédito y
 *      facturas; "Descargar" baja el PDF real que genera PrestaShop y "Enviar
 *      por el chat" lo sube como adjunto de la conversación abierta con el
 *      mismo endpoint que el clip del composer.
 *
 * Depende de window.PscOrderWorkspace (order-workspace.js) y de
 * window.HDCommerce (core). Fuente en modules/HelpdeskPrestashop/public/js/ext/;
 * asset() sirve desde public/modules/helpdeskprestashop/js/ext/ — copiar allí
 * tras editar.
 */
(function () {
    'use strict';

    var W = function () { return window.PscOrderWorkspace; };
    var C = function () { return window.HDCommerce; };

    var SOURCE_LABEL = {
        private_message: 'Mensaje privado',
        order_message: 'Mensaje del pedido',
    };
    var KIND = {
        invoice: { title: 'Factura', list: 'invoices' },
        delivery_slip: { title: 'Albarán de entrega', list: 'delivery_slips' },
        credit_slip: { title: 'Nota de crédito', list: 'credit_slips' },
    };

    var _notesFor = null;      // id de pedido cuyas notas ya se pidieron
    var _noteKey = null;       // Idempotency-Key del intento de nota en curso
    var _noteKeyText = null;   // texto al que pertenece esa clave
    var _noteBusy = false;
    var _docsFor = null;       // id de pedido del listado de documentos cargado
    var _docs = null;
    var _canDownload = false;
    var _confirming = false;
    var _busy = false;

    function esc(s) { return W().esc(s == null ? '' : String(s)); }
    // HDCommerce.esc no escapa comillas: para valores dentro de atributos.
    function escAttr(s) { return esc(s).replace(/"/g, '&quot;').replace(/'/g, '&#39;'); }
    function uuid() {
        if (window.crypto && window.crypto.randomUUID) { return window.crypto.randomUUID(); }
        return 'od-' + Date.now().toString(36) + '-' + Math.random().toString(36).slice(2);
    }
    function orderRef(order) { return String((order && (order.reference || order.id)) || ''); }
    function sendUrl() { return $('.bv-composer').data('bv-send-url') || ''; }
    function conversationId() {
        return (C() && C().conversationId && C().conversationId()) || $('.bv-composer').data('bv-conversation-id') || null;
    }
    // fmtDate ya devuelve HTML seguro (escapa el valor si no es una fecha).
    function fmt(iso) { return iso ? W().fmtDate(iso) : 'Sin fecha'; }
    function dateOnly(iso) {
        if (!iso) { return ''; }
        return window.PscStore && window.PscStore.date ? window.PscStore.date(iso, true) : W().fmtDate(iso);
    }

    // ───────────────────────── 20 · Notas del pedido ─────────────────────────

    function renderNotesPanel(order) {
        var canCopy = !!sendUrl();
        _noteKey = null;
        _noteKeyText = null;
        $('#powPanelNotas').html(
            '<div class="bv-po-card">' +
                '<div class="bv-po-card-h"><span class="bv-po-sec-ic"><i class="fas fa-note-sticky"></i></span>' +
                    '<div class="bv-po-card-ht"><span class="t">Notas internas</span><span class="s">Pedido #' + esc(orderRef(order)) + ' en el back-office</span></div></div>' +
                '<div class="psc-orderdocs-notes" id="psOrderdocsNotes"><div class="psc-loading"><i class="fas fa-spinner fa-spin"></i> Cargando notas…</div></div>' +
            '</div>' +
            '<div class="bv-po-card" id="psOrderdocsNoteCard">' +
                '<div class="psc-orderdocs-form">' +
                    '<div class="psc-field"><span class="lbl">Nueva nota</span>' +
                        '<textarea id="psOrderdocsNoteText" rows="3" maxlength="2000" placeholder="Solo la ve el equipo…"></textarea></div>' +
                    '<label class="psc-check' + (canCopy ? '' : ' psc-orderdocs-off') + '">' +
                        '<input type="checkbox" id="psOrderdocsNoteCopy"' + (canCopy ? '' : ' disabled') + '>' +
                        '<span>Copiar también al hilo del ticket</span></label>' +
                    (canCopy ? '' : '<div class="psc-orderdocs-hint">Abre una conversación para poder copiar la nota a su hilo.</div>') +
                    '<div class="psc-note psc-note--info"><span class="psc-note-txt">Las notas del pedido son visibles en el back-office para el equipo de almacén.</span></div>' +
                    '<button type="button" class="psc-btn psc-btn--primary" id="psOrderdocsNoteSave">Guardar nota</button>' +
                '</div>' +
            '</div>'
        );
        _notesFor = null;
        if ($('#powPanelNotas').is(':visible')) { loadNotes(); }
    }

    function loadNotes(force) {
        var id = W().orderId();
        if (!id || (!force && _notesFor === id)) { return; }
        _notesFor = id;
        $.ajax({
            url: W().customerUrl('/ps/orders/' + id + '/orderdocs/notes'),
            method: 'GET', dataType: 'json',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': W().csrf() },
        }).done(function (r) {
            if (W().orderId() !== id) { return; }
            renderNotes((r && r.data && r.data.notes) || []);
            if (r && r.can_add === false) {
                $('#psOrderdocsNoteCard').html(
                    '<div class="psc-note psc-note--lock"><span class="psc-note-txt">No tienes permiso para añadir notas a los pedidos de este cliente.</span></div>'
                );
            }
        }).fail(function (xhr) {
            if (W().orderId() !== id) { return; }
            _notesFor = null;
            $('#psOrderdocsNotes').html(
                '<div class="psc-note psc-note--warn"><span class="psc-note-txt">' +
                esc(W().errorMessage(xhr, 'No se pudieron cargar las notas del pedido.')) + '</span></div>' +
                '<button type="button" class="psc-btn psc-btn--outline" id="psOrderdocsNotesRetry">Reintentar</button>'
            );
        });
    }

    function renderNotes(notes) {
        if (!notes.length) {
            $('#psOrderdocsNotes').html(
                '<div class="psc-state"><i class="fas fa-note-sticky"></i>' +
                '<span class="t">Sin notas internas</span>' +
                '<span class="s">Este pedido no tiene notas ni mensajes privados en PrestaShop.</span></div>'
            );
            return;
        }
        $('#psOrderdocsNotes').html(notes.map(function (n) {
            var tag = SOURCE_LABEL[n.source]
                ? '<span class="psc-tag psc-tag--closed">' + esc(SOURCE_LABEL[n.source]) + '</span>'
                : '';
            return '<div class="psc-note-item">' +
                '<div class="txt psc-orderdocs-note-txt">' + esc(n.text) + '</div>' +
                '<div class="psc-orderdocs-note-foot"><span class="by">' + esc(n.author || '—') + ' · ' + fmt(n.date) + '</span>' + tag + '</div>' +
            '</div>';
        }).join(''));
    }

    $(document).on('click', '#psOrderdocsNotesRetry', function () { loadNotes(true); });

    // La pestaña Notas se pinta oculta: las notas se piden al abrirla, no en
    // cada apertura de pedido (el bridge limita a 60 peticiones/min por IP).
    $(document).on('click', '#powTabs .bv-po-tab[data-po-tab="notas"]', function () {
        setTimeout(function () { loadNotes(false); }, 0);
    });

    $(document).on('click', '#psOrderdocsNoteSave', function () {
        var order = W().order();
        var id = W().orderId();
        if (!order || !id || _noteBusy) { return; }
        var note = String($('#psOrderdocsNoteText').val() || '').trim();
        if (!note) { toastr.warning('Escribe la nota.'); return; }
        // Clave por intento (no por texto): un reintento del MISMO texto tras
        // un corte no duplica la nota, pero la misma frase otro día sí se
        // guarda. Si el agente cambia el texto antes de reintentar, clave
        // nueva: con la vieja el bridge devolvería el resultado anterior y la
        // nota corregida no se guardaría.
        if (!_noteKey || _noteKeyText !== note) {
            _noteKey = uuid();
            _noteKeyText = note;
        }
        var copy = $('#psOrderdocsNoteCopy').is(':checked') && !!sendUrl();
        var $btn = $(this).prop('disabled', true).addClass('is-disabled').text('Guardando…');
        _noteBusy = true;

        $.ajax({
            url: W().customerUrl('/ps/orders/' + id + '/note'),
            method: 'POST', dataType: 'json',
            data: { note: note },
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': W().csrf(), 'Idempotency-Key': _noteKey },
        }).done(function (r) {
            if (!r || !r.success) { toastr.warning((r && r.message) || 'No se pudo guardar la nota.'); return; }
            _noteKey = null;
            _noteKeyText = null;
            $('#psOrderdocsNoteText').val('');
            $('#psOrderdocsNoteCopy').prop('checked', false);
            if (copy) {
                copyToThread(order, note);
            } else {
                toastr.success('Nota guardada en el pedido.');
            }
            loadNotes(true);
        }).fail(function (xhr) {
            toastr.error(W().errorMessage(xhr, 'No se pudo guardar la nota.'));
        }).always(function () {
            _noteBusy = false;
            $btn.prop('disabled', false).removeClass('is-disabled').text('Guardar nota');
        });
    });

    function copyToThread(order, note) {
        $.ajax({
            url: sendUrl(), method: 'POST', dataType: 'json',
            data: { body: '[PS] Nota del pedido #' + orderRef(order) + '\n' + note, is_internal: 1, action: 'send' },
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': W().csrf() },
        }).done(function () {
            toastr.success('Nota guardada en el pedido y copiada al hilo.');
        }).fail(function (xhr) {
            toastr.warning('La nota se guardó en el pedido, pero no se pudo copiar al hilo: ' + W().errorMessage(xhr, 'error desconocido') + '.');
        });
    }

    // ─────────────────────── 22 · Documentos del pedido ──────────────────────

    function ensureSheet() {
        var $sheet = $('#psOrderdocsSheet');
        var $host = W().body().find('.bv-po-body');
        if ($sheet.length && $host.length && !$sheet.parent().is($host)) {
            $sheet.appendTo($host);
        }
    }

    function renderDocsCard() {
        $('#psOrderdocsCard').remove();
        $('#powPanelPago').append(
            '<div class="bv-po-card" id="psOrderdocsCard">' +
                '<div class="bv-po-card-h"><span class="bv-po-sec-ic"><i class="fas fa-file-lines"></i></span>' +
                    '<div class="bv-po-card-ht"><span class="t">Documentos</span><span class="s">Albarán, nota de crédito y factura en PDF</span></div></div>' +
                '<button type="button" class="psc-btn psc-btn--outline" id="psOrderdocsOpen">Ver documentos</button>' +
            '</div>'
        );
    }

    function openDocs(preferType) {
        var order = W().order();
        if (!order) { return; }
        ensureSheet();
        $('#psOrderdocsRef').text('#' + orderRef(order));
        $('#psOrderdocsError, #psOrderdocsDenied').addClass('bv-hidden');
        resetConfirm();
        W().openSheet('#psOrderdocsSheet');

        if (_docsFor === W().orderId() && _docs) {
            renderDocs(preferType);
            return;
        }
        $('#psOrderdocsList').html('<div class="psc-skel"></div><div class="psc-skel"></div><div class="psc-skel"></div>');
        updateFoot();
        var id = W().orderId();
        $.ajax({
            url: W().customerUrl('/ps/orders/' + id + '/orderdocs/documents'),
            method: 'GET', dataType: 'json',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': W().csrf() },
        }).done(function (r) {
            if (W().orderId() !== id) { return; }
            _docsFor = id;
            _docs = (r && r.data) || {};
            _canDownload = !!(r && r.can_download);
            renderDocs(preferType);
        }).fail(function (xhr) {
            if (W().orderId() !== id) { return; }
            $('#psOrderdocsList').html('');
            $('#psOrderdocsError').removeClass('bv-hidden').find('.psc-note-txt')
                .text(W().errorMessage(xhr, 'No se pudieron cargar los documentos del pedido.'));
            updateFoot();
        });
    }

    function docMeta(type, d) {
        var parts = [d.number];
        if (d.date) { parts.push(dateOnly(d.date)); }
        if (type === 'credit_slip' && d.amount != null) { parts.push(W().money(d.amount)); }
        if (type === 'invoice' && d.total != null) { parts.push(W().money(d.total)); }
        return parts.join(' · ');
    }

    function renderDocs(preferType) {
        var html = '';
        var first = null;
        ['invoice', 'delivery_slip', 'credit_slip'].forEach(function (type) {
            var rows = (_docs && _docs[KIND[type].list]) || [];
            if (!rows.length) {
                var why = type === 'invoice' && _docs && _docs.invoicing_enabled === false
                    ? 'No se emite en PrestaShop: la factura sale del ERP'
                    : (type === 'credit_slip' ? 'Aún no existe para este pedido' : 'Aún no se ha generado');
                html += '<label class="psc-radio-opt is-off">' +
                    '<input type="radio" name="psOrderdocsDoc" disabled>' +
                    '<span class="psc-orderdocs-opt"><span class="t">' + KIND[type].title + '</span>' +
                    '<span class="s">' + esc(why) + '</span></span></label>';
                return;
            }
            rows.forEach(function (d) {
                var key = type + ':' + d.id;
                if (!first || (preferType === type && first.indexOf(preferType + ':') !== 0)) { first = key; }
                html += '<label class="psc-radio-opt" data-orderdocs-key="' + escAttr(key) + '">' +
                    '<input type="radio" name="psOrderdocsDoc" value="' + escAttr(key) + '">' +
                    '<span class="psc-orderdocs-opt"><span class="t">' + KIND[type].title + '</span>' +
                    '<span class="m">' + esc(docMeta(type, d)) + '</span></span></label>';
            });
        });
        $('#psOrderdocsList').html(html);
        if (first) { selectDoc(first); }
        $('#psOrderdocsDenied').toggleClass('bv-hidden', _canDownload);
        updateFoot();
    }

    function selectedKey() { return $('input[name="psOrderdocsDoc"]:checked').val() || ''; }

    function selectDoc(key) {
        $('#psOrderdocsList .psc-radio-opt').removeClass('is-on');
        var $opt = $('#psOrderdocsList .psc-radio-opt').filter(function () { return String($(this).attr('data-orderdocs-key')) === key; });
        $opt.addClass('is-on').find('input').prop('checked', true);
    }

    function updateFoot() {
        var has = !!selectedKey() && _canDownload;
        var canChat = has && !!conversationId();
        $('#psOrderdocsDownload').prop('disabled', !has || _busy).toggleClass('is-disabled', !has || _busy);
        $('#psOrderdocsSend').prop('disabled', !canChat || _busy).toggleClass('is-disabled', !canChat || _busy);
        $('#psOrderdocsHint').find('.psc-note-txt').text(conversationId()
            ? 'Enviar por el chat manda el PDF al cliente en esta conversación sin descargarlo a tu equipo.'
            : 'Abre la conversación del cliente para poder enviarle el PDF por el chat.');
    }

    function resetConfirm() {
        _confirming = false;
        $('#psOrderdocsConfirm').addClass('bv-hidden');
        $('#psOrderdocsSend').text('Enviar por el chat');
    }

    $(document).on('change', 'input[name="psOrderdocsDoc"]', function () {
        selectDoc($(this).val());
        resetConfirm();
        updateFoot();
    });

    $(document).on('click', '#psOrderdocsOpen', function () { openDocs(null); });
    $(document).on('click', '#psOrderdocsClose, #psOrderdocsCancel', function () {
        resetConfirm();
        W().closeSheet('#psOrderdocsSheet');
    });

    // Los botones Factura/Albarán de la cabecera del workspace solo mostraban
    // un aviso; ahora abren esta hoja con ese tipo preseleccionado. Se enlazan
    // directos al botón (antes que el delegado del core en document) y cortan
    // la propagación para que no salga además el aviso.
    function bindHeaderButtons() {
        $('#powInvoiceBtn').off('click.orderdocs').on('click.orderdocs', function (e) {
            e.stopPropagation(); openDocs('invoice');
        });
        $('#powSlipBtn').off('click.orderdocs').on('click.orderdocs', function (e) {
            e.stopPropagation(); openDocs('delivery_slip');
        });
    }

    function docUrl(key, purpose) {
        var p = key.split(':');
        var qs = '?purpose=' + purpose + (conversationId() ? '&conversation_id=' + encodeURIComponent(conversationId()) : '');
        return W().customerUrl('/ps/orders/' + W().orderId() + '/orderdocs/documents/' + p[0] + '/' + p[1]) + qs;
    }

    function filenameFrom(res, fallback) {
        var cd = res.headers.get('Content-Disposition') || '';
        var m = /filename\*=UTF-8''([^;]+)/i.exec(cd) || /filename="?([^";]+)"?/i.exec(cd);
        return m ? decodeURIComponent(m[1]) : fallback;
    }

    // PDF como Blob en memoria: sirve tanto para guardarlo como para subirlo
    // al chat sin pasar por el disco del agente.
    function fetchPdf(key, purpose) {
        return fetch(docUrl(key, purpose), {
            credentials: 'same-origin',
            // JSON primero: así Laravel devuelve los 401/403/422 como JSON (con
            // su mensaje) en vez de redirigir; el PDF se sirve igual.
            headers: { 'Accept': 'application/json, application/pdf', 'X-CSRF-TOKEN': W().csrf() },
        }).then(function (res) {
            if (!res.ok) {
                return res.json().catch(function () { return {}; }).then(function (j) {
                    throw new Error((j && j.message) || (res.status === 403
                        ? 'No tienes permiso para este documento.'
                        : 'No se pudo obtener el documento.'));
                });
            }
            // Sesión caducada = 200 con la página de login: no es un PDF.
            if ((res.headers.get('Content-Type') || '').indexOf('application/pdf') !== 0) {
                throw new Error('La sesión ha caducado o PrestaShop no devolvió un PDF. Recarga la página.');
            }
            var name = filenameFrom(res, key.replace(':', '-') + '.pdf');
            return res.blob().then(function (blob) { return { blob: blob, name: name }; });
        });
    }

    function setBusy(on, $btn, label) {
        _busy = on;
        if ($btn && label) { $btn.text(label); }
        updateFoot();
    }

    $(document).on('click', '#psOrderdocsDownload', function () {
        var key = selectedKey();
        if (!key || _busy) { return; }
        var $btn = $(this);
        setBusy(true, $btn, 'Descargando…');
        fetchPdf(key, 'download').then(function (f) {
            var url = URL.createObjectURL(f.blob);
            var a = document.createElement('a');
            a.href = url;
            a.download = f.name;
            a.className = 'bv-hidden';
            document.body.appendChild(a);
            a.click();
            setTimeout(function () { URL.revokeObjectURL(url); a.remove(); }, 1000);
        }).catch(function (err) {
            toastr.error(err.message);
        }).then(function () {
            setBusy(false, $btn, 'Descargar');
        });
    });

    // Enviar por el chat: el composer no tiene adjuntos en borrador (el clip
    // sube y ENVÍA en el acto), así que se pide confirmación en dos pasos.
    $(document).on('click', '#psOrderdocsSend', function () {
        var key = selectedKey();
        var convId = conversationId();
        if (!key || !convId || _busy) { return; }
        var title = $('#psOrderdocsList .psc-radio-opt.is-on .t').text();

        if (!_confirming) {
            _confirming = true;
            $('#psOrderdocsConfirm').removeClass('bv-hidden').find('.psc-note-txt')
                .text('El PDF (' + title + ') se enviará ahora al cliente en esta conversación. No se puede deshacer.');
            $(this).text('Confirmar envío al cliente');
            return;
        }

        var $btn = $(this);
        setBusy(true, $btn, 'Enviando…');
        fetchPdf(key, 'chat').then(function (f) {
            var fd = new FormData();
            fd.append('files[]', new File([f.blob], f.name, { type: 'application/pdf' }));
            return $.ajax({
                url: '/panel/helpdesk/conversations/' + convId + '/attachments',
                method: 'POST', data: fd, processData: false, contentType: false,
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': W().csrf() },
            }).then(function (resp) {
                if (resp && resp.item && typeof window.appendBubbleToThread === 'function') {
                    window.appendBubbleToThread(resp.item, false);
                }
                toastr.success(f.name + ' enviado al cliente por el chat.');
            }, function (xhr) {
                var errs = xhr && xhr.responseJSON && xhr.responseJSON.errors;
                var first = errs ? Object.keys(errs).map(function (k) { return errs[k][0]; })[0] : null;
                throw new Error(first || W().errorMessage(xhr, 'No se pudo enviar el PDF por el chat.'));
            });
        }).catch(function (err) {
            toastr.error(err.message || 'No se pudo enviar el PDF por el chat.');
        }).then(function () {
            _busy = false;
            resetConfirm();
            updateFoot();
        });
    });

    // ───────────────────────────── Enganche ─────────────────────────────────

    $(document).on('psc:order-rendered', function (e, order) {
        if (!window.PscOrderWorkspace) { return; }
        ensureSheet();
        bindHeaderButtons();
        // Tras un cambio de estado PS puede haber generado un albarán nuevo:
        // el listado se vuelve a pedir al abrir la hoja.
        _docsFor = null;
        _docs = null;
        renderNotesPanel(order);
        renderDocsCard();
    });
})();
