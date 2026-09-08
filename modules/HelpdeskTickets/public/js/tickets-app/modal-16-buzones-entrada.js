'use strict';

    // ── Modal 16: Buzones de entrada ──────────────────────────
    // ── Modal 16: Buzones de entrada ──────────────────────────

    // "hace 2 min" del mockup. Se calcula en el navegador desde el ISO y no con
    // diffForHumans en el servidor porque la app corre con locale 'en' por
    // defecto en Docker y devolvería "2 minutes ago" en una pantalla en español.
    function mbxSince(iso) {
        if (!iso) return null;
        var ts = new Date(iso).getTime();
        if (isNaN(ts)) return null;
        var secs = Math.round((Date.now() - ts) / 1000);
        if (secs < 60) return 'hace un momento';
        var mins = Math.round(secs / 60);
        if (mins < 60) return 'hace ' + mins + ' min';
        var hours = Math.round(mins / 60);
        if (hours < 24) return 'hace ' + hours + ' h';
        var days = Math.round(hours / 24);
        if (days < 30) return 'hace ' + days + (days === 1 ? ' día' : ' días');
        return formatDateShort(iso);
    }

    // Estado del buzón, derivado SOLO de datos reales: los dos interruptores
    // (con ambos apagados FetchTicketEmailsJob se salta el buzón entero),
    // last_error y last_checked_at. Nada de "conectado" por defecto.
    function mbxState(b) {
        var proto = (b.encryption ? String(b.encryption).toUpperCase() + ' · ' : '') + 'IMAP';
        if (!b.create_tickets && !b.create_replies) return { text: 'En pausa · ' + proto, cls: 'idle' };
        if (b.last_error) return { text: 'Con error · ' + proto, cls: 'err' };
        if (!b.last_checked_at) return { text: 'Sin lecturas · ' + proto, cls: 'idle' };
        return { text: 'Conectado · ' + proto, cls: 'ok' };
    }

    function openMailboxesModal() {
        var boxes = [];
        var canManage = false;
        var currentId = null;

        var $backdrop = openModal(modalShell({
            icon: 'fa-solid fa-inbox', kicker: 'Ajustes · correo',
            title: 'Buzones de entrada', width: 'xl',
            body: '<div id="tkt-mbx-body"><div class="tkt-skeleton"></div></div>',
            foot: '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-mbx-save" disabled>Guardar</button>' +
                  '<button type="button" class="tkt-btn" id="tkt-mbx-test" disabled>Probar conexión</button>' +
                  '<button type="button" class="tkt-btn" data-modal-close>Cerrar</button>',
        }));

        function current() {
            return boxes.filter(function (b) { return String(b.id) === String(currentId); })[0] || null;
        }

        // Enlace a la pantalla completa: alta, credenciales, SMTP y borrado NO se
        // tocan desde aquí a propósito (este modal solo cambia comportamiento).
        function moreLink(label) {
            if (!TKA.urls.emailChannels) return '';
            return '<div class="tkt-mbx-more"><a href="' + escapeHtml(TKA.urls.emailChannels) + '">' + escapeHtml(label) + '</a></div>';
        }

        function render() {
            var $body = $backdrop.find('#tkt-mbx-body');

            if (!boxes.length) {
                $backdrop.find('#tkt-mbx-save, #tkt-mbx-test').prop('disabled', true);
                $body.html('<div class="tkt-empty-box">No hay ningún buzón de correo entrante configurado.</div>' +
                    moreLink('Configurar buzones'));
                return;
            }

            if (!current()) currentId = boxes[0].id;
            var b = current();
            var st = mbxState(b);

            var options = boxes.map(function (m) {
                var label = m.name || m.username || 'Buzón';
                if (m.name && m.username) label = m.name + ' — ' + m.username;
                return '<option value="' + escapeHtml(String(m.id)) + '"' +
                    (String(m.id) === String(currentId) ? ' selected' : '') + '>' + escapeHtml(label) + '</option>';
            }).join('');

            var imap = [b.host, b.port].filter(Boolean).join(':') || '—';
            if (b.encryption) imap += ' · ' + String(b.encryption).toUpperCase();

            var smtp = [b.smtp_host, b.smtp_port].filter(Boolean).join(':');
            if (smtp && b.smtp_encryption) smtp += ' · ' + String(b.smtp_encryption).toUpperCase();

            var lastRead = mbxSince(b.last_checked_at) || 'sin lecturas todavía';

            $body.html(
                '<div class="tkt-field-row">' +
                    '<div class="tkt-field"><label class="tkt-label" for="tkt-mbx-pick">Buzón</label>' +
                        '<select class="tkt-select" id="tkt-mbx-pick">' + options + '</select></div>' +
                    '<div class="tkt-field"><label class="tkt-label">Estado</label>' +
                        '<div class="tkt-mbx-state ' + st.cls + '">' + escapeHtml(st.text) + '</div></div>' +
                '</div>' +
                '<div class="tkt-side-card"><div class="tkt-side-card-body tight">' +
                    sideRow('IMAP', imap, { mono: true }) +
                    // La fila SMTP solo aparece si el canal tiene servidor de
                    // salida propio; sin él las respuestas salen por el mailer
                    // global y enseñar "—" haría pensar que está mal configurado.
                    (smtp ? sideRow('SMTP', smtp, { mono: true }) : '') +
                    sideRow('Última lectura', lastRead, { last: !b.last_error }) +
                    (b.last_error ? sideRow('Último error', String(b.last_error).slice(0, 120), { mono: true, last: true }) : '') +
                '</div></div>' +
                '<label class="tkt-check"><input type="checkbox" id="tkt-mbx-tickets"' +
                    (b.create_tickets ? ' checked' : '') + (canManage ? '' : ' disabled') + '> Crear tickets con los correos entrantes</label>' +
                '<label class="tkt-check"><input type="checkbox" id="tkt-mbx-replies"' +
                    (b.create_replies ? ' checked' : '') + (canManage ? '' : ' disabled') + '> Añadir respuestas al ticket existente</label>' +
                (canManage ? '' : '<div class="tkt-note">Solo lectura: hace falta el permiso de ajustes de tickets para cambiar el comportamiento de un buzón.</div>') +
                '<div id="tkt-mbx-result"></div>' +
                moreLink('Configurar buzones (credenciales, alta y borrado)')
            );

            $backdrop.find('#tkt-mbx-save').prop('disabled', !canManage);
            $backdrop.find('#tkt-mbx-test').prop('disabled', !canManage);
        }

        function note(cls, text) {
            $backdrop.find('#tkt-mbx-result').html('<div class="tkt-note ' + cls + '">' + escapeHtml(text) + '</div>');
        }

        // Solo lectura con lo que ya sirve settings-snapshot: si las rutas nuevas
        // todavía no están registradas, el modal sigue enseñando los buzones en
        // vez de quedarse en blanco (mismos nombres de campo en las dos fuentes).
        function loadFromSnapshot() {
            withSettings(function (d) {
                boxes = (d && d.mailboxes) || [];
                canManage = false;
                render();
            });
        }

        if (TKA.urls.mailboxes) {
            $.getJSON(TKA.urls.mailboxes)
                .done(function (d) {
                    boxes = (d && d.mailboxes) || [];
                    canManage = !!(d && d.can_manage);
                    render();
                })
                .fail(loadFromSnapshot);
        } else {
            loadFromSnapshot();
        }

        $backdrop.on('change', '#tkt-mbx-pick', function () {
            currentId = $(this).val();
            render();
        });

        $backdrop.on('click', '#tkt-mbx-save', function () {
            var b = current();
            if (!b || !TKA.urls.mailboxBehaviorTemplate) return;

            var tickets = $backdrop.find('#tkt-mbx-tickets').is(':checked');
            var replies = $backdrop.find('#tkt-mbx-replies').is(':checked');
            var $btn = $(this).prop('disabled', true).text('Guardando…');

            $.ajax({
                // POST y no PUT: un PUT real por AJAX devuelve 405 en este entorno
                // Docker aunque route:list lo registre.
                url: TKA.urls.mailboxBehaviorTemplate.replace('__MBX__', encodeURIComponent(b.id)),
                method: 'POST',
                data: { create_tickets: tickets ? 1 : 0, create_replies: replies ? 1 : 0 },
                headers: { Accept: 'application/json' },
                success: function (res) {
                    var msg = (res && res.message) || 'Buzón guardado.';
                    if (window.toastr) toastr.success(msg); else window.alert(msg);
                    if (res && res.mailbox) {
                        boxes = boxes.map(function (m) { return String(m.id) === String(b.id) ? res.mailbox : m; });
                    }
                    render();
                    // Apagar los dos interruptores deja el buzón sin leer: el
                    // aviso se queda en el modal, no solo en un toast que se va.
                    if (!tickets && !replies) note('warn', msg);
                },
                error: function (xhr) {
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'No se pudo guardar el buzón.';
                    if (window.toastr) toastr.error(msg); else window.alert(msg);
                },
                complete: function () {
                    $btn.prop('disabled', !canManage).text('Guardar');
                },
            });
        });

        $backdrop.on('click', '#tkt-mbx-test', function () {
            var b = current();
            if (!b || !TKA.urls.mailboxTestTemplate) return;

            var $btn = $(this).prop('disabled', true).text('Probando…');
            note('', 'Conectando con ' + [b.host, b.port].filter(Boolean).join(':') + '…');

            $.ajax({
                url: TKA.urls.mailboxTestTemplate.replace('__MBX__', encodeURIComponent(b.id)),
                method: 'POST',
                headers: { Accept: 'application/json' },
                success: function (res) {
                    note('ok', (res && res.message) || 'El servidor acepta conexiones.');
                },
                error: function (xhr) {
                    // La prueba es un chequeo TCP: no valida credenciales, así que
                    // el fallo se cuenta tal cual lo devuelve el servidor.
                    note('danger', (xhr.responseJSON && xhr.responseJSON.message) || 'No se pudo probar la conexión.');
                },
                complete: function () {
                    $btn.prop('disabled', !canManage).text('Probar conexión');
                },
            });
        });
    }


