'use strict';

    // ── Modal 31: Notificaciones ──────────────────────────────
    function openNotificationsModal() {
        // Estado REAL del permiso del navegador.
        var perm = (typeof Notification !== 'undefined') ? Notification.permission : 'unsupported';
        var permLabel = { granted: 'Activadas', denied: 'Bloqueadas por el navegador', default: 'Sin conceder', unsupported: 'No compatible' }[perm];

        var $backdrop = openModal(modalShell({
            icon: 'fa-regular fa-bell', kicker: 'Ajustes · notificaciones',
            title: 'Notificaciones', width: 'lg',
            body: '<div class="tkt-side-rows">' + sideRow('Notificaciones del navegador', permLabel, { strong: true, last: true }) + '</div>' +
                  (perm === 'default' ? '<button type="button" class="tkt-btn tkt-w-100" id="tkt-notif-ask">Permitir notificaciones</button>' : '') +
                  (perm === 'denied' ? '<div class="tkt-note"><i class="fa-solid fa-circle-info"></i> El navegador tiene bloqueadas las notificaciones de este sitio. Hay que reactivarlas desde su configuración, no se puede pedir de nuevo desde aquí.</div>' : '') +
                  '<div class="tkt-cap tkt-cap-spaced">Avisarme cuando</div>' +
                  '<div id="tkt-notif-prefs"><div class="tkt-empty-box">Cargando tus preferencias de aviso…</div></div>' +
                  '<div id="tkt-notif-always"></div>' +
                  '<div id="tkt-notif-team-channels"></div>',
            foot: '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-notif-save" disabled>Guardar preferencias</button>' +
                  '<button type="button" class="tkt-btn" id="tkt-notif-open">Abrir el panel de avisos</button>' +
                  '<button type="button" class="tkt-btn" data-modal-close>Cerrar</button>',
        }));

        // ── Pintado de la lista de eventos ────────────────────────
        // Una fila por evento, con una casilla por canal gobernable. El servidor
        // manda sólo los canales que la via() de esa notificación consulta de
        // verdad: donde el canal está cableado a fuego no viene, y así no se
        // enseña un interruptor que el agente mueve sin efecto.
        function prefsHtml(events) {
            if (!events.length) {
                return '<div class="tkt-empty-box">Ningún aviso de ticket admite ajuste por ahora.</div>';
            }

            return events.map(function (ev) {
                var canales = ev.channels.map(function (ch) {
                    var id = 'tkt-notif-' + ev.key.replace(/\./g, '-') + '-' + ch.key;

                    return '<label class="tkt-check sm tkt-notif-ch" for="' + id + '">' +
                        '<input type="checkbox" id="' + id + '"' +
                            ' data-notif-type="' + escapeHtml(ev.key) + '"' +
                            ' data-notif-channel="' + escapeHtml(ch.key) + '"' +
                            (ch.enabled ? ' checked' : '') + '>' +
                        escapeHtml(ch.label) +
                    '</label>';
                }).join('');

                return '<div class="tkt-notif-row">' +
                    '<div class="tkt-notif-main">' +
                        '<span class="tkt-option-title">' + escapeHtml(ev.label) + '</span>' +
                        (ev.description ? '<span class="tkt-option-sub">' + escapeHtml(ev.description) + '</span>' : '') +
                    '</div>' +
                    '<div class="tkt-notif-channels">' + canales + '</div>' +
                '</div>';
            }).join('');
        }

        // Los avisos sin interruptor se dicen en voz alta en lugar de callarlos:
        // si no, el agente busca el ajuste de "responde el cliente" y no lo
        // encuentra en ninguna parte porque esa via() no pregunta.
        function alwaysHtml(items) {
            if (!items || !items.length) return '';

            return '<div class="tkt-note"><i class="fa-solid fa-circle-info"></i>' +
                '<span>Estos avisos llegan siempre y no se pueden desactivar: ' +
                items.map(function (i) { return escapeHtml(i.charAt(0).toLowerCase() + i.slice(1)); }).join('; ') +
                '.</span></div>';
        }

        // Slack/Teams son un webhook de EQUIPO, no una preferencia por
        // agente: un solo campo por plataforma, en blanco = no tocar el que
        // ya hubiera (mismo criterio que un campo de contraseña). Ahora
        // mismo solo avisan de "SLA incumplido" (ver
        // SendSlaBreachBroadcastNotification) — se dice así de claro para no
        // insinuar que cualquier evento de la lista de arriba llega también aquí.
        function teamChannelsHtml(tc) {
            tc = tc || {};

            function campo(platform, label, configured) {
                return '<div class="tkt-field"><label class="tkt-label">' + escapeHtml(label) +
                    (configured ? ' <span class="tkt-rchip ok">configurado</span>' : '') + '</label>' +
                    '<input type="url" class="tkt-input" data-team-channel="' + platform + '" placeholder="' +
                    (configured ? 'Dejar en blanco para no cambiarlo' : 'https://…') + '"></div>';
            }

            return '<div class="tkt-cap tkt-cap-spaced">Avisar también al equipo (Slack/Teams)</div>' +
                '<div class="tkt-note"><i class="fa-solid fa-circle-info"></i> Un único webhook por plataforma para todo el equipo, no por agente. Por ahora solo avisan cuando se incumple un SLA.</div>' +
                campo('slack', 'Webhook de Slack', tc.slack_configured) +
                campo('teams', 'Webhook de Teams', tc.teams_configured) +
                '<button type="button" class="tkt-btn tkt-btn-sm" id="tkt-notif-channels-save">Guardar integraciones</button> ' +
                ((tc.slack_configured || tc.teams_configured) ? '<button type="button" class="tkt-btn tkt-btn-sm" id="tkt-notif-channels-test">Enviar prueba</button>' : '');
        }

        function cargar() {
            if (!TKA.urls.notifPrefs) {
                $backdrop.find('#tkt-notif-prefs').html('<div class="tkt-empty-box">Las preferencias de aviso no están disponibles en esta instalación.</div>');

                return;
            }

            $.getJSON(TKA.urls.notifPrefs).done(function (res) {
                var catalogo = res || {};
                $backdrop.find('#tkt-notif-prefs').html(prefsHtml(catalogo.events || []));
                $backdrop.find('#tkt-notif-always').html(alwaysHtml(catalogo.always_on));
                if (TKA.urls.notifTeamChannels) {
                    $backdrop.find('#tkt-notif-team-channels').html(teamChannelsHtml(catalogo.team_channels));
                }
                // Sólo se habilita al mover algo: guardar sin cambios escribiría
                // filas iguales a lo que ya hay.
                $backdrop.find('#tkt-notif-save').prop('disabled', true);
            }).fail(function () {
                $backdrop.find('#tkt-notif-prefs').html('<div class="tkt-empty-box">No se han podido cargar tus preferencias de aviso.</div>');
            });
        }

        cargar();

        $backdrop.on('click', '#tkt-notif-channels-save', function () {
            var $btn = $(this).prop('disabled', true);
            var data = {};
            $backdrop.find('[data-team-channel]').each(function () {
                var val = $(this).val().trim();
                if (val) data[$(this).data('team-channel') + '_webhook_url'] = val;
            });
            $.ajax({ url: TKA.urls.notifTeamChannels, method: 'PATCH', data: data })
                .done(function (resp) {
                    if (window.toastr) toastr.success((resp && resp.message) || 'Guardado');
                    cargar();
                })
                .fail(function (xhr) {
                    var msg = apiErrorMessage(xhr, 'No se pudo guardar.');
                    if (window.toastr) toastr.error(msg); else window.alert(msg);
                })
                .always(function () { $btn.prop('disabled', false); });
        });

        $backdrop.on('click', '#tkt-notif-channels-test', function () {
            var $btn = $(this).prop('disabled', true).text('Enviando…');
            $.post(TKA.urls.notifTeamChannelsTest).done(function (resp) {
                if (window.toastr) toastr.success((resp && resp.message) || 'Prueba enviada'); else window.alert('Prueba enviada');
            }).fail(function (xhr) {
                var msg = apiErrorMessage(xhr, 'No se pudo enviar la prueba.');
                if (window.toastr) toastr.error(msg); else window.alert(msg);
            }).always(function () {
                $btn.prop('disabled', false).text('Enviar prueba');
            });
        });

        $backdrop.on('change', '#tkt-notif-prefs input[type="checkbox"]', function () {
            $backdrop.find('#tkt-notif-save').prop('disabled', false);
        });

        $backdrop.on('click', '#tkt-notif-save', function () {
            var $btn = $(this);

            var preferences = $backdrop.find('#tkt-notif-prefs input[type="checkbox"]').map(function () {
                return {
                    notification_type: $(this).data('notif-type'),
                    channel: $(this).data('notif-channel'),
                    // 1/0 y no true/false: jQuery serializa el booleano como
                    // la cadena "true"/"false" en form-urlencoded, y la regla
                    // `boolean` de Laravel solo acepta true/false reales, 1, 0,
                    // "1" y "0" — con la cadena devolvía 422 y no se guardaba
                    // ninguna preferencia.
                    enabled: this.checked ? 1 : 0,
                };
            }).get();

            if (!preferences.length) return;

            $btn.prop('disabled', true);

            // El CSRF lo añade el $.ajaxSetup global del layout, igual que en el
            // resto de llamadas de este archivo.
            $.ajax({
                url: TKA.urls.notifPrefsUpdate,
                method: 'POST',
                headers: { Accept: 'application/json' },
                data: { preferences: preferences },
            }).done(function (res) {
                var msg = (res && res.message) ? res.message : 'Preferencias de aviso guardadas.';
                if (window.toastr) toastr.success(msg); else window.alert(msg);
                closeModal();
            }).fail(function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'No se han podido guardar las preferencias.';
                if (window.toastr) toastr.error(msg); else window.alert(msg);
                $btn.prop('disabled', false);
            });
        });

        $backdrop.on('click', '#tkt-notif-ask', function () {
            Notification.requestPermission().then(function () { closeModal(); openNotificationsModal(); });
        });
        // "Abrir el panel de avisos" apuntaba a openNoticesPanel(), que no
        // se llegó a escribir nunca: el botón cerraba el modal y no hacía
        // nada más. El panel de avisos de esta app es el centro de
        // notificaciones del propio panel, así que lleva ahí.
        $backdrop.on('click', '#tkt-notif-open', function () {
            closeModal();
            if (TKA.urls.notificationsIndex) {
                window.location = TKA.urls.notificationsIndex;
            } else if (window.toastr) {
                toastr.info('El panel de avisos no está disponible en esta instalación.');
            }
        });
    }


