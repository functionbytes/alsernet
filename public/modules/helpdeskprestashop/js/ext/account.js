/*!
 * HelpdeskPrestashop · extensión "account": la cuenta del cliente en
 * PrestaShop dentro del workspace de cliente (grupo "Cuenta" del menú).
 *
 *  - Editar ficha (customer.update del diseño): nombre, apellidos, teléfono
 *    de la dirección principal, idioma, newsletter y ofertas de socios. El
 *    botón no se activa hasta que algo cambia; si la ficha cambió en la tienda
 *    mientras tanto, aviso gris oscuro con "Recargar".
 *  - Grupo y descuento (pieza 28): cambio con paso de confirmación.
 *  - Acceso a la cuenta (29): correo de restablecer contraseña de la tienda.
 *  - RGPD (30): consentimientos, exportación JSON y solicitud de borrado
 *    (solo registra la solicitud; no borra nada).
 *
 * Todo vive dentro del workspace (nunca un modal encima de otro). La ficha se
 * pide aparte (/ps/account) porque el contexto del tab Tienda no trae la
 * versión (date_upd) ni el catálogo de grupos e idiomas.
 *
 * Fuente en modules/HelpdeskPrestashop/public/js/ext/ — copiar a
 * public/modules/helpdeskprestashop/js/ext/ tras editar.
 */
(function () {
    var TTL = 60 * 1000;
    var cache = {};   // por id de cliente del helpdesk: { data, can, at }
    var loading = {};
    var pendingEditMsg = null; // aviso que debe sobrevivir al repintado tras guardar

    var CONSENT_SOURCES = {
        contactform: 'formulario de contacto',
        ps_emailsubscription: 'suscripción a la newsletter',
        lgcomments: 'opiniones',
        ps_emailalerts: 'avisos por correo',
        alvarezpquestions: 'preguntas de producto',
        alsernetquestions: 'preguntas de producto',
    };

    function S() { return window.PscStore; }
    function H() { return window.HDCommerce; }
    function esc(s) { return S().esc(s == null ? '' : s); }
    function escAttr(s) { return S().escAttr(s == null ? '' : s); }
    function cid() { return H() && H().customerId(); }
    function url(suffix) { return H().base() + '/ps/account' + (suffix || ''); }
    function entry() { return cache[cid()] || null; }
    function toast(kind, msg) { if (window.toastr) { window.toastr[kind](msg); } }

    function dateTime(iso) {
        var d = S().parse(iso);
        if (!d) { return '—'; }
        var hh = ('0' + d.getHours()).slice(-2);
        var mm = ('0' + d.getMinutes()).slice(-2);
        return S().date(iso, true) + ', ' + hh + ':' + mm;
    }

    function fetchProfile(force, cb) {
        var id = cid();
        if (!id) { cb(null, 'Abre una conversación con un cliente.'); return; }
        var hit = cache[id];
        if (!force && hit && Date.now() - hit.at < TTL) { cb(hit); return; }
        if (loading[id]) { loading[id].push(cb); return; }
        loading[id] = [cb];
        $.ajax({ url: url(), method: 'GET', dataType: 'json', headers: { 'Accept': 'application/json' } })
            .done(function (r) {
                cache[id] = { data: r.data || {}, can: r.can || {}, at: Date.now() };
                flush(id, cache[id], null);
            })
            .fail(function (xhr) {
                flush(id, null, H().errorMessage(xhr, 'No se ha podido cargar la cuenta del cliente.'));
            });
    }

    function flush(id, hit, err) {
        var waiters = loading[id] || [];
        delete loading[id];
        waiters.forEach(function (fn) { fn(hit, err); });
    }

    function paneShell(kind) {
        var hit = entry();
        // Con la ficha en memoria se pinta ya; si no, esqueleto y se rellena
        // en cuanto llega (render() de PscChat es síncrono).
        setTimeout(function () { fill(kind, false); }, 0);
        return '<div class="psc-account" data-psc-account-pane="' + kind + '">' +
            (hit && Date.now() - hit.at < TTL ? RENDER[kind](hit) : '<div class="psc-skel"></div><div class="psc-skel"></div><div class="psc-loading">Cargando cuenta…</div>') +
        '</div>';
    }

    function fill(kind, force) {
        fetchProfile(force, function (hit, err) {
            var $pane = $('[data-psc-account-pane="' + kind + '"]');
            if (!$pane.length) { return; }
            if (!hit) {
                $pane.html('<div class="psc-note psc-note--warn"><span class="psc-note-txt">' + esc(err) + '</span>' +
                    '<button type="button" class="psc-note-act" data-psc-account-reload>Reintentar</button></div>');
                return;
            }
            // Si el agente está a mitad de edición no se le pisa el formulario.
            if (!force && $pane.find('.is-dirty').length) { return; }
            $pane.html(RENDER[kind](hit));
            if (kind === 'edit') {
                refreshEditState();
                if (pendingEditMsg) { showFeedback('pscAccEditMsg', pendingEditMsg); pendingEditMsg = null; }
            }
        });
    }

    function rerenderAll() {
        Object.keys(RENDER).forEach(function (kind) {
            var $pane = $('[data-psc-account-pane="' + kind + '"]');
            if ($pane.length && entry()) { $pane.html(RENDER[kind](entry())); }
        });
        refreshEditState();
    }

    // Tras escribir: nueva ficha, y el contexto del tab Tienda y del
    // workspace (nombre, grupo) se recargan para que no enseñen lo viejo.
    function afterWrite() {
        // fill(…, true) comparte una única petición (loading[]) y, si la ficha
        // no llega, cada sección enseña el aviso de carga en vez de los datos
        // viejos con una versión que ya no vale.
        Object.keys(RENDER).forEach(function (kind) { fill(kind, true); });
        if (S()) {
            // refresh() lanza la petición forzada; load(true) se suma a ella en
            // vez de devolver el contexto viejo que aún está en memoria.
            S().refresh();
            S().load(true, function () { if (window.PscChat) { window.PscChat.rerenderCustomer(); } });
        }
    }

    function post(path, data, method) {
        return $.ajax({
            url: url(path), method: 'POST', dataType: 'json',
            data: $.extend({ conversation_id: H().conversationId() || '' }, method ? { _method: method } : {}, data || {}),
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': H().csrf() },
        });
    }

    function lockNote(text) {
        return '<div class="psc-note psc-note--lock"><span class="psc-note-txt">' + esc(text) + '</span></div>';
    }

    function infoNote(text) {
        return '<div class="psc-note psc-note--info"><span class="psc-note-txt">' + esc(text) + '</span></div>';
    }

    // Rechazos de la tienda en gris (denegado/bloqueado); el ámbar queda
    // solo para los fallos de carga (ver fill()).
    function feedback(id) {
        return '<div class="psc-note psc-note--lock bv-hidden" id="' + id + '"><span class="psc-note-txt"></span></div>';
    }

    function showFeedback(id, text, kind) {
        $('#' + id).removeClass('bv-hidden psc-note--lock psc-note--good').addClass(kind === 'good' ? 'psc-note--good' : 'psc-note--lock')
            .find('.psc-note-txt').text(text);
    }

    function conflictBox(id, text) {
        return '<div class="psc-account-conflict bv-hidden" id="' + id + '">' +
            '<span class="psc-note-txt">' + esc(text) + '</span>' +
            '<button type="button" class="psc-account-conflict-act" data-psc-account-reload>Recargar</button>' +
        '</div>';
    }

    /* ── Editar ficha ─────────────────────────────────────────── */

    function renderEdit(hit) {
        var d = hit.data;
        var can = !!hit.can.update;
        var dis = can ? '' : ' disabled';
        var phone = d.phone || null;
        var langList = d.languages || [];
        var langs = langList.map(function (l) {
            return '<option value="' + escAttr(l.id) + '"' + (String(l.id) === String(d.id_lang) ? ' selected' : '') + '>' + esc(l.name) + '</option>';
        }).join('');
        // Idioma actual ya desactivado en la tienda: se muestra tal cual para
        // que el formulario no nazca "cambiado" (el select cogería otro).
        if (!langList.some(function (l) { return String(l.id) === String(d.id_lang); })) {
            langs = '<option value="' + escAttr(d.id_lang) + '" selected>Idioma no activo en la tienda</option>' + langs;
        }

        var phoneHint = phone
            ? 'De la dirección «' + (phone.alias || 'principal') + '»' + (phone.city ? ' · ' + phone.city : '') + '. PrestaShop no guarda teléfono en el cliente.' +
              (phone.used_in_orders ? ' Esa dirección ya se usó en pedidos: la tienda guardará una copia y los pedidos anteriores no cambian.' : '')
            : 'El cliente no tiene direcciones: PrestaShop guarda el teléfono en la dirección.';

        var form = '<div class="psc-account-form" id="pscAccEditForm">' +
            conflictBox('pscAccConflict', 'La ficha ha cambiado en PrestaShop desde que la abriste. Recárgala para ver los datos actuales; tus cambios se descartarán.') +
            feedback('pscAccEditMsg') +
            '<div class="psc-fieldrow">' +
                '<label class="psc-field"><span class="lbl">Nombre</span><input type="text" class="finput" maxlength="255" data-acc-field="firstname" value="' + escAttr(d.firstname) + '"' + dis + '></label>' +
                '<label class="psc-field"><span class="lbl">Apellidos</span><input type="text" class="finput" maxlength="255" data-acc-field="lastname" value="' + escAttr(d.lastname) + '"' + dis + '></label>' +
            '</div>' +
            '<div class="psc-fieldrow">' +
                '<label class="psc-field"><span class="lbl">Teléfono</span><input type="tel" class="finput mono" maxlength="32" data-acc-field="phone" value="' + escAttr(phone ? phone.value || '' : '') + '"' + (can && phone ? '' : ' disabled') + '></label>' +
                '<label class="psc-field"><span class="lbl">Idioma</span><select data-acc-field="id_lang"' + dis + '>' + langs + '</select></label>' +
            '</div>' +
            '<span class="psc-account-hint">' + esc(phoneHint) + '</span>' +
            '<div class="psc-account-checks">' +
                '<label class="psc-check"><input type="checkbox" data-acc-field="newsletter"' + (d.newsletter ? ' checked' : '') + dis + '> Newsletter</label>' +
                '<label class="psc-check"><input type="checkbox" data-acc-field="optin"' + (d.optin ? ' checked' : '') + dis + '> Ofertas de socios</label>' +
            '</div>' +
            PscRow('Email', esc(d.email), true) +
            infoNote('El email no se edita aquí: es la clave de vinculación con el contacto.') +
        '</div>';

        var actions = can
            ? '<div class="psc-account-actions">' +
                '<button type="button" class="psc-btn psc-btn--primary is-disabled" id="pscAccSave" disabled>Guardar cambios</button>' +
                '<button type="button" class="psc-btn psc-btn--outline bv-hidden" id="pscAccDiscard">Descartar cambios</button>' +
              '</div>'
            : lockNote('No tienes permiso para editar la ficha en PrestaShop.');

        return window.PscChat.card('Datos del cliente', 'PS-' + d.id, form + actions);
    }

    function PscRow(k, v, mono) { return window.PscChat.row(k, v, mono); }

    function currentValue($el) {
        return $el.is(':checkbox') ? $el.is(':checked') : String($el.val() == null ? '' : $el.val()).trim();
    }

    function originalValue(field) {
        var d = (entry() || {}).data || {};
        if (field === 'phone') { return d.phone ? String(d.phone.value || '').trim() : ''; }
        if (field === 'newsletter' || field === 'optin') { return !!d[field]; }
        return String(d[field] == null ? '' : d[field]).trim();
    }

    function editChanges() {
        var out = {};
        $('#pscAccEditForm [data-acc-field]').each(function () {
            var $el = $(this);
            var f = $el.data('acc-field');
            if ($el.is(':disabled')) { return; }
            var v = currentValue($el);
            if (v !== originalValue(f)) { out[f] = v; }
        });
        return out;
    }

    function refreshEditState() {
        if (!$('#pscAccEditForm').length) { return; }
        var dirty = Object.keys(editChanges()).length > 0;
        $('#pscAccEditForm').toggleClass('is-dirty', dirty);
        var $save = $('#pscAccSave');
        if ($save.data('busy')) { return; }
        $save.prop('disabled', !dirty).toggleClass('is-disabled', !dirty);
        $('#pscAccDiscard').toggleClass('bv-hidden', !dirty);
    }

    $(document).on('input change', '#pscAccEditForm [data-acc-field]', refreshEditState);

    $(document).on('click', '#pscAccDiscard', function () {
        $('[data-psc-account-pane="edit"]').html(renderEdit(entry()));
        refreshEditState();
    });

    $(document).on('click', '#pscAccSave', function () {
        var hit = entry();
        var changes = editChanges();
        if (!hit || !Object.keys(changes).length) { return; }
        var d = hit.data;
        var data = { version: d.version };
        Object.keys(changes).forEach(function (f) {
            data[f] = (f === 'newsletter' || f === 'optin') ? (changes[f] ? 1 : 0) : changes[f];
        });
        if (changes.phone !== undefined && d.phone) {
            data.address_id = d.phone.address_id;
            data.address_version = d.phone.version;
        }

        var $btn = $(this).data('busy', true).prop('disabled', true).addClass('is-disabled').text('Guardando…');
        $('#pscAccEditMsg').addClass('bv-hidden');
        $('#pscAccEditForm [data-acc-field]').prop('disabled', true);

        post('', data, 'PATCH').done(function () {
            toast('success', 'Ficha guardada en PrestaShop.');
            $('#pscAccEditForm').removeClass('is-dirty');
            afterWrite();
        }).fail(function (xhr) {
            $btn.data('busy', false).text('Guardar cambios');
            $('#pscAccEditForm [data-acc-field]').each(function () {
                var f = $(this).data('acc-field');
                $(this).prop('disabled', f === 'phone' && !hit.data.phone);
            });
            if (xhr.status === 409) {
                $('#pscAccConflict').removeClass('bv-hidden');
                $btn.prop('disabled', true).addClass('is-disabled');
                return;
            }
            var r = xhr.responseJSON || {};
            if (r.saved && r.saved.length) {
                // Guardado parcial (la ficha sí, el teléfono no): la versión ya
                // cambió en la tienda, así que se recarga antes de avisar.
                pendingEditMsg = H().errorMessage(xhr, 'No se ha podido guardar el teléfono.');
                afterWrite();
                return;
            }
            showFeedback('pscAccEditMsg', H().errorMessage(xhr, 'No se ha podido guardar la ficha.'));
            refreshEditState();
        });
    });

    $(document).on('click', '[data-psc-account-reload]', function () {
        var kind = $(this).closest('[data-psc-account-pane]').data('psc-account-pane') || 'edit';
        $('[data-psc-account-pane="' + kind + '"]').html('<div class="psc-skel"></div><div class="psc-loading">Recargando…</div>');
        fill(kind, true);
    });

    /* ── Grupo y descuento (28) ───────────────────────────────── */

    function groupFacts(g) {
        if (!g) { return { discount: '—', tax: '—' }; }
        return {
            discount: g.reduction > 0 ? '−' + String(g.reduction).replace('.', ',') + ' %' : 'Sin descuento',
            tax: g.tax_excluded ? 'Precios sin IVA' : 'Precios con IVA',
        };
    }

    function renderGroup(hit) {
        var d = hit.data;
        var g = d.group || {};
        var f = groupFacts(g);
        var others = (d.groups || []).filter(function (x) {
            return x.id !== g.id && (d.member_of || []).indexOf(x.id) !== -1;
        }).map(function (x) { return x.name; });

        var rows = PscRow('Grupo actual', esc(g.name || '—'), false) +
            PscRow('Descuento del grupo', esc(f.discount), true) +
            PscRow('Impuestos', esc(f.tax), false) +
            (g.show_prices === false ? PscRow('Precios en la tienda', 'Ocultos', false) : '') +
            (others.length ? PscRow('También en', esc(others.join(', ')), false) : '');

        if (!hit.can.group) {
            return window.PscChat.card('Grupo y descuento', null, rows + lockNote('Cambiar de grupo requiere permiso de supervisor.'));
        }

        var options = (d.groups || []).map(function (x) {
            return '<option value="' + escAttr(x.id) + '"' + (x.id === g.id ? ' selected' : '') + '>' + esc(x.name) + '</option>';
        }).join('');

        return window.PscChat.card('Grupo y descuento', null, rows +
            conflictBox('pscAccGroupConflict', 'La ficha ha cambiado en PrestaShop desde que la abriste. Recárgala antes de cambiar el grupo.') +
            feedback('pscAccGroupMsg') +
            '<label class="psc-field"><span class="lbl">Cambiar a</span><select id="pscAccGroupSel">' + options + '</select></label>' +
            '<div class="psc-account-confirm bv-hidden" id="pscAccGroupConfirm">' +
                '<div class="psc-account-confirm-txt" id="pscAccGroupPreview"></div>' +
                '<button type="button" class="psc-btn psc-btn--primary" id="pscAccGroupDo">Confirmar cambio de grupo</button>' +
                '<button type="button" class="psc-btn psc-btn--outline" id="pscAccGroupCancel">Cancelar</button>' +
            '</div>' +
            '<div class="psc-account-actions" id="pscAccGroupActs">' +
                '<button type="button" class="psc-btn psc-btn--primary is-disabled" id="pscAccGroupSave" disabled>Guardar grupo</button>' +
            '</div>' +
            infoNote('Cambiar de grupo altera precios y plazos de pago: pide confirmación y queda en auditoría.'));
    }

    function selectedGroup() {
        var id = parseInt($('#pscAccGroupSel').val(), 10);
        return (((entry() || {}).data || {}).groups || []).filter(function (x) { return x.id === id; })[0] || null;
    }

    $(document).on('change', '#pscAccGroupSel', function () {
        var sel = selectedGroup();
        var cur = ((entry() || {}).data || {}).group || {};
        var changed = !!sel && sel.id !== cur.id;
        $(this).closest('.psc-field').toggleClass('is-dirty', changed);
        $('#pscAccGroupSave').prop('disabled', !changed).toggleClass('is-disabled', !changed);
        $('#pscAccGroupConfirm').addClass('bv-hidden');
        $('#pscAccGroupActs').removeClass('bv-hidden');
    });

    $(document).on('click', '#pscAccGroupSave', function () {
        var sel = selectedGroup();
        var d = (entry() || {}).data || {};
        if (!sel) { return; }
        var f = groupFacts(sel);
        var name = [d.firstname, d.lastname].filter(Boolean).join(' ') || 'El cliente';
        $('#pscAccGroupPreview').html(esc(name) + ' pasará de <b>' + esc((d.group || {}).name || '—') + '</b> a <b>' + esc(sel.name) + '</b>: ' +
            esc(f.discount.toLowerCase()) + ', ' + esc(f.tax.toLowerCase()) + (sel.show_prices === false ? ', y no verá precios en la tienda' : '') +
            '. Afecta a sus próximos pedidos y carritos; el cambio queda registrado a tu nombre.');
        $('#pscAccGroupConfirm').removeClass('bv-hidden');
        $('#pscAccGroupActs').addClass('bv-hidden');
        $('#pscAccGroupSel').prop('disabled', true);
    });

    $(document).on('click', '#pscAccGroupCancel', function () {
        $('#pscAccGroupConfirm').addClass('bv-hidden');
        $('#pscAccGroupActs').removeClass('bv-hidden');
        $('#pscAccGroupSel').prop('disabled', false);
    });

    $(document).on('click', '#pscAccGroupDo', function () {
        var sel = selectedGroup();
        var d = (entry() || {}).data || {};
        if (!sel) { return; }
        var $btn = $(this).prop('disabled', true).addClass('is-disabled').text('Guardando…');
        post('/group', { group_id: sel.id, version: d.version, confirmed: 1 }).done(function () {
            toast('success', 'Grupo cambiado a ' + sel.name + '.');
            afterWrite();
        }).fail(function (xhr) {
            $btn.prop('disabled', false).removeClass('is-disabled').text('Confirmar cambio de grupo');
            if (xhr.status === 409) {
                $('#pscAccGroupConflict').removeClass('bv-hidden');
                $btn.prop('disabled', true).addClass('is-disabled');
                return;
            }
            showFeedback('pscAccGroupMsg', H().errorMessage(xhr, 'No se ha podido cambiar el grupo.'));
        });
    });

    /* ── Acceso a la cuenta (29) ──────────────────────────────── */

    function renderAccess(hit) {
        var d = hit.data;
        var a = d.access || {};
        var state = d.is_guest ? '<span class="psc-tag psc-tag--closed">Invitado</span>'
            : (d.active ? '<span class="psc-tag psc-tag--done">Activa</span>' : '<span class="psc-tag psc-tag--blocked">Desactivada</span>');

        var rows = PscRow('Estado de la cuenta', state, false) +
            PscRow('Último acceso', a.last_connection ? esc(S().relative(a.last_connection)) + ' · ' + esc(dateTime(a.last_connection)) : 'Sin registro en la tienda', false) +
            (a.last_cart_at ? PscRow('Última actividad', 'Carrito · ' + esc(S().relative(a.last_cart_at)), false) : '') +
            PscRow('Contraseña cambiada', a.last_passwd_gen ? esc(S().date(a.last_passwd_gen, true)) : '—', false) +
            (a.reset_pending_until ? PscRow('Enlace de restablecer', 'Enviado · válido hasta ' + esc(dateTime(a.reset_pending_until)), false) : '');

        var btn;
        if (!hit.can.password_reset) {
            btn = lockNote('No tienes permiso para enviar el enlace de restablecer contraseña.');
        } else if (!a.can_reset) {
            btn = lockNote(d.is_guest ? 'Es una cuenta de invitado: no tiene contraseña que restablecer.' : 'La cuenta está desactivada: la tienda no permite restablecer su contraseña.');
        } else if (a.next_reset_at) {
            btn = lockNote('La contraseña se cambió hace poco: la tienda no permite otro enlace hasta ' + dateTime(a.next_reset_at) + '.');
        } else {
            btn = '<button type="button" class="psc-btn psc-btn--primary" id="pscAccReset">Enviar enlace de restablecer contraseña</button>';
        }

        return window.PscChat.card('Acceso a la cuenta', null, rows +
            feedback('pscAccAccessMsg') +
            '<div class="psc-account-actions">' + btn +
                (a.orders_url ? '<button type="button" class="psc-btn psc-btn--outline" id="pscAccCopyOrders">Copiar enlace de acceso a sus pedidos</button>' : '') +
            '</div>' +
            infoNote('El agente nunca ve ni fija contraseñas: solo dispara el correo de la tienda.'));
    }

    $(document).on('click', '#pscAccReset', function () {
        var $btn = $(this).prop('disabled', true).addClass('is-disabled').text('Enviando…');
        post('/password-reset', {}).done(function (r) {
            var v = (r && r.data) || {};
            toast('success', 'Correo de restablecer contraseña enviado.');
            fetchProfile(true, function () {
                rerenderAll();
                showFeedback('pscAccAccessMsg', 'La tienda ha enviado el enlace a ' + (v.to || 'su correo') +
                    (v.valid_until ? '. Caduca el ' + dateTime(v.valid_until) + '.' : '.'), 'good');
            });
        }).fail(function (xhr) {
            $btn.prop('disabled', false).removeClass('is-disabled').text('Enviar enlace de restablecer contraseña');
            showFeedback('pscAccAccessMsg', H().errorMessage(xhr, 'No se ha podido enviar el correo.'));
        });
    });

    $(document).on('click', '#pscAccCopyOrders', function () {
        var link = (((entry() || {}).data || {}).access || {}).orders_url;
        if (!link) { return; }
        var done = function () { toast('success', 'Enlace copiado.'); };
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(link).then(done, function () { S().insert(link); });
        } else {
            // Sin portapapeles (http sin TLS): se deja en el composer, sin enviar.
            S().insert(link);
        }
    });

    /* ── RGPD (30) ────────────────────────────────────────────── */

    function renderGdpr(hit) {
        var d = hit.data;
        var consents = ((d.gdpr || {}).consents || []);
        var last = consents[0];
        var accepted = function (on, when) {
            return on ? '<span class="psc-account-yes">Aceptado' + (when ? ' · ' + esc(S().date(when, true)) : '') + '</span>' : '<span class="psc-account-no">Rechazado</span>';
        };

        var rows = PscRow('Newsletter', accepted(d.newsletter, d.newsletter_date_add), false) +
            PscRow('Ofertas de socios', accepted(d.optin, null), false) +
            PscRow('Protección de datos', last
                ? '<span class="psc-account-yes">Aceptada · ' + esc(S().date(last.date, true)) + '</span>'
                : '<span class="psc-account-no">Sin registro</span>', false) +
            (last ? PscRow('Dónde', esc(last.module ? (CONSENT_SOURCES[last.module] || last.module) : 'alta de la cuenta'), false) : '');

        var acts = '';
        if (hit.can.gdpr_export) {
            acts += '<button type="button" class="psc-btn psc-btn--outline" id="pscAccExport">Exportar sus datos</button>';
        }
        if (hit.can.gdpr_request) {
            acts += '<button type="button" class="psc-btn psc-btn--danger" id="pscAccErase">Solicitar borrado de la cuenta</button>';
        }

        return window.PscChat.card('RGPD del cliente', null, rows +
            feedback('pscAccGdprMsg') +
            '<div class="psc-account-confirm psc-account-confirm--danger bv-hidden" id="pscAccEraseConfirm">' +
                '<div class="psc-account-confirm-txt">No se borrará nada ahora: la solicitud queda como nota interna en esta conversación y en la auditoría, y la confirma un responsable.</div>' +
                '<label class="psc-field"><span class="lbl">Motivo (opcional)</span><textarea id="pscAccEraseReason" maxlength="500" placeholder="Lo pidió el cliente por chat…"></textarea></label>' +
                '<button type="button" class="psc-btn psc-btn--danger" id="pscAccEraseDo">Registrar solicitud de borrado</button>' +
                '<button type="button" class="psc-btn psc-btn--outline" id="pscAccEraseCancel">Cancelar</button>' +
            '</div>' +
            (acts ? '<div class="psc-account-actions" id="pscAccGdprActs">' + acts + '</div>' : lockNote('No tienes permisos de RGPD sobre este cliente.')) +
            infoNote('El borrado lo confirma un responsable; queda registrado quién lo solicitó y desde qué conversación.'));
    }

    $(document).on('click', '#pscAccExport', function () {
        var $btn = $(this).prop('disabled', true).addClass('is-disabled').text('Preparando…');
        var reset = function () { $btn.prop('disabled', false).removeClass('is-disabled').text('Exportar sus datos'); };
        var exportUrl = url('/gdpr-export') + '?conversation_id=' + encodeURIComponent(H().conversationId() || '');
        fetch(exportUrl, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } }).then(function (res) {
            if (!res.ok) {
                return res.json().catch(function () { return {}; }).then(function (j) {
                    throw new Error((j && j.message) || 'No se ha podido exportar.');
                });
            }
            if ((res.headers.get('Content-Type') || '').indexOf('application/json') === -1) {
                throw new Error('La sesión ha caducado: recarga la página y vuelve a exportar.');
            }
            var disp = res.headers.get('Content-Disposition') || '';
            var m = disp.match(/filename="([^"]+)"/);
            return res.blob().then(function (blob) { return { blob: blob, name: m ? m[1] : 'rgpd-cliente.json' }; });
        }).then(function (file) {
            var href = URL.createObjectURL(file.blob);
            var a = document.createElement('a');
            a.href = href;
            a.download = file.name;
            document.body.appendChild(a);
            a.click();
            a.remove();
            setTimeout(function () { URL.revokeObjectURL(href); }, 1000);
            showFeedback('pscAccGdprMsg', 'Exportación descargada (' + file.name + '). Queda registrada en la auditoría.', 'good');
            reset();
        }).catch(function (e) {
            showFeedback('pscAccGdprMsg', e.message || 'No se ha podido exportar.');
            reset();
        });
    });

    $(document).on('click', '#pscAccErase', function () {
        $('#pscAccEraseConfirm').removeClass('bv-hidden');
        $('#pscAccGdprActs').addClass('bv-hidden');
        $('#pscAccEraseReason').val('').trigger('focus');
    });

    $(document).on('click', '#pscAccEraseCancel', function () {
        $('#pscAccEraseConfirm').addClass('bv-hidden');
        $('#pscAccGdprActs').removeClass('bv-hidden');
    });

    $(document).on('click', '#pscAccEraseDo', function () {
        var $btn = $(this).prop('disabled', true).addClass('is-disabled').text('Registrando…');
        var reset = function () { $btn.prop('disabled', false).removeClass('is-disabled').text('Registrar solicitud de borrado'); };
        post('/erasure-request', { reason: String($('#pscAccEraseReason').val() || '').trim() }).done(function (r) {
            $('#pscAccEraseConfirm').addClass('bv-hidden');
            $('#pscAccGdprActs').removeClass('bv-hidden');
            reset();
            if (r.already_requested) {
                showFeedback('pscAccGdprMsg', r.message || 'Ya hay una solicitud pendiente de confirmar.', 'good');
                return;
            }
            var sendUrl = $('.bv-composer').data('bv-send-url');
            if (!sendUrl || !r.note) {
                showFeedback('pscAccGdprMsg', 'Solicitud registrada en la auditoría. Abre la conversación para dejar también la nota interna.', 'good');
                return;
            }
            $.ajax({
                url: sendUrl, method: 'POST', dataType: 'json',
                data: { body: r.note, is_internal: 1, action: 'send' },
                headers: { 'X-CSRF-TOKEN': H().csrf(), 'Accept': 'application/json' },
            }).done(function () {
                toast('success', 'Solicitud de borrado registrada como nota interna.');
                showFeedback('pscAccGdprMsg', 'Solicitud registrada: nota interna en la conversación y entrada en la auditoría. No se ha borrado nada.', 'good');
            }).fail(function () {
                showFeedback('pscAccGdprMsg', 'La solicitud quedó en la auditoría, pero no se pudo guardar la nota interna.');
            });
        }).fail(function (xhr) {
            reset();
            showFeedback('pscAccGdprMsg', H().errorMessage(xhr, 'No se ha podido registrar la solicitud.'));
        });
    });

    var RENDER = { edit: renderEdit, group: renderGroup, access: renderAccess, gdpr: renderGdpr };

    /* ── Registro de secciones y atajo en el tab Tienda ───────── */

    function register() {
        var C = window.PscChat;
        if (!C || !C.registerPane || !window.PscStore) { return false; }
        C.registerPane({
            key: 'account-edit', icon: 'fa-user-pen', title: 'Editar ficha',
            sub: 'Nombre, teléfono, idioma', render: function () { return paneShell('edit'); },
        });
        C.registerPane({
            key: 'account-group', icon: 'fa-users', title: 'Grupo y descuento',
            sub: function (ctx) { var c = (ctx && ctx.customer) || {}; return (c.group && c.group.name) || 'Grupo de cliente'; },
            render: function () { return paneShell('group'); },
        });
        C.registerPane({
            key: 'account-access', icon: 'fa-key', title: 'Acceso a la cuenta',
            sub: function (ctx) { var c = (ctx && ctx.customer) || {}; return c.is_guest ? 'Invitado' : (c.active === false ? 'Desactivada' : 'Activa'); },
            render: function () { return paneShell('access'); },
        });
        C.registerPane({
            key: 'account-gdpr', icon: 'fa-shield-halved', title: 'RGPD',
            sub: function (ctx) { var c = (ctx && ctx.customer) || {}; return c.newsletter ? 'Newsletter aceptada' : 'Consentimientos y datos'; },
            render: function () { return paneShell('gdpr'); },
        });
        return true;
    }

    // "Editar datos en PrestaShop" en el tab Tienda (ficha del cliente del
    // diseño): abre el workspace directamente en la sección de edición.
    $(document).on('psc:store-rendered', function (e, ctx) {
        var $wrap = $('#ps-ext-wrap');
        $wrap.find('[data-psc-account-shortcut]').remove();
        if (!ctx || !ctx.customer || !ctx.customer.found) { return; }
        $wrap.append('<div class="psc-account-shortcut" data-psc-account-shortcut>' +
            '<button type="button" class="psc-btn psc-btn--outline" data-psc-open-customer="account-edit">Editar datos en PrestaShop</button>' +
        '</div>');
    });

    $(function () {
        if (register()) { return; }
        // prestashop-chat.js también va con defer; por si llega después.
        var tries = 0;
        var timer = setInterval(function () {
            if (register() || ++tries > 40) { clearInterval(timer); }
        }, 250);
    });
})();
