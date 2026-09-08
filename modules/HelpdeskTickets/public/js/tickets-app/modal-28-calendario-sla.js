'use strict';

    // ── Modal 28: Calendario y SLA ────────────────────────────
    // Etiqueta de un plazo en minutos. Los objetivos van de "15 min" a "5 d", así
    // que una sola unidad fija (todo en horas) o bien pierde precisión o bien pinta
    // "120 h" donde el agente espera "5 d".
    function slaFmtMinutes(m) {
        if (m == null) return '—';
        m = Number(m);
        if (!isFinite(m) || m < 0) return '—';
        if (m < 60) return m + ' min';
        if (m < 1440) {
            var h = Math.round(m / 60 * 10) / 10;
            return h + ' h';
        }
        var d = Math.floor(m / 1440);
        var restH = Math.round((m % 1440) / 60);
        return d + ' d' + (restH ? ' ' + restH + ' h' : '');
    }

    function slaFmtHours(h) {
        return h == null ? '—' : slaFmtMinutes(Number(h) * 60);
    }

    // Vencimiento: fecha corta + "dentro de / hace" que ya calcula el backend.
    function slaDueText(row) {
        if (!row || !row.at) return 'sin plazo';
        return formatDateShort(row.at) + (row.human ? ' · ' + row.human : '');
    }

    function openSlaCalendarModal() {
        var ticket = null;
        if (TKA.state.selected) {
            ticket = (TKA.state.tickets || []).find(function (x) { return x.id === TKA.state.selected; }) || null;
        }

        var $backdrop = openModal(modalShell({
            icon: 'fa-regular fa-calendar',
            kicker: 'SLA · calendario',
            title: 'Calendario y SLA',
            titleChip: ticket ? ticket.ticket_number : null,
            width: '2xl',
            body: '<div id="tkt-slacal-body"><div class="tkt-skeleton"></div><div class="tkt-skeleton"></div></div>',
            foot: '<button type="button" class="tkt-btn" data-modal-close>Cerrar</button>',
        }));

        if (!TKA.urls.slaCalendar) {
            $backdrop.find('#tkt-slacal-body').html('<div class="tkt-empty-box">El calendario de SLA no está disponible en esta pantalla.</div>');
            return;
        }

        var params = ticket ? { ticket: ticket.id } : {};

        $.getJSON(TKA.urls.slaCalendar, params).done(function (d) {
            renderSlaCalendar($backdrop, d);
        }).fail(function () {
            $backdrop.find('#tkt-slacal-body').html('<div class="tkt-note danger"><i class="fa-solid fa-triangle-exclamation"></i> No se ha podido leer la configuración de SLA.</div>');
        });
    }

    function renderSlaCalendar($backdrop, d) {
        d = d || {};

        var panes = [];
        if (d.ticket) panes.push({ key: 'ticket', label: 'Este ticket', html: slaPaneTicket(d.ticket) });
        panes.push({ key: 'targets', label: 'Objetivos', html: slaPaneTargets(d) });
        panes.push({ key: 'hours', label: 'Horario y pausas', html: slaPaneHours(d) });

        $backdrop.find('#tkt-slacal-body').html(
            '<div class="tkt-seg-tabs" id="tkt-slacal-tabs">' +
                panes.map(function (p, i) {
                    return '<button type="button" class="' + (i === 0 ? 'on' : '') + '" data-slacal-tab="' + p.key + '">' + escapeHtml(p.label) + '</button>';
                }).join('') +
            '</div>' +
            panes.map(function (p, i) {
                return '<div class="tkt-slacal-pane' + (i === 0 ? ' on' : '') + '" data-slacal-pane="' + p.key + '">' + p.html + '</div>';
            }).join('')
        );

        // Pie contextual: el botón principal lleva a donde se editan los plazos,
        // que es la acción que sigue al 90 % de las visitas a este modal.
        if (d.links && d.links.sla_policies) {
            $backdrop.find('.tkt-modal-foot').prepend(
                '<a class="tkt-btn tkt-btn-primary" href="' + escapeHtml(d.links.sla_policies) + '">Editar políticas de SLA</a>'
            );
        }

        $backdrop.on('click', '[data-slacal-tab]', function () {
            var key = $(this).data('slacal-tab');
            $backdrop.find('[data-slacal-tab]').removeClass('on');
            $(this).addClass('on');
            $backdrop.find('[data-slacal-pane]').removeClass('on');
            $backdrop.find('[data-slacal-pane="' + key + '"]').addClass('on');
        });

        bindSlaPauseToggles($backdrop, d);
    }

    // ── Pestaña 1: el reloj de ESTE ticket ────────────────────
    function slaPaneTicket(t) {
        var head = t.paused
            ? '<div class="tkt-headline bad">' +
                  '<div class="t">Reloj de SLA pausado</div>' +
                  '<div class="s">Pausado ' + escapeHtml(t.paused_since_human || '') +
                      ' · ' + slaFmtMinutes(t.current_pause_minutes) + ' de esta pausa' +
                      (t.accumulated_pause_minutes ? ' · ' + slaFmtMinutes(t.accumulated_pause_minutes) + ' acumulados antes' : '') +
                  '</div>' +
              '</div>'
            : '<div class="tkt-headline">' +
                  '<div class="t">Reloj de SLA en marcha</div>' +
                  '<div class="s">' + (t.accumulated_pause_minutes
                      ? slaFmtMinutes(t.accumulated_pause_minutes) + ' pausados en total hasta ahora'
                      : 'Nunca se ha pausado') + '</div>' +
              '</div>';

        // Sin política no hay vencimientos: decirlo es más útil que pintar tres
        // filas con guiones, que se leen como "aún no ha vencido".
        if (!t.policy) {
            head += '<div class="tkt-note warn"><i class="fa-solid fa-circle-info"></i> Este ticket no tiene ninguna política de SLA asignada, así que no tiene plazos que vigilar.</div>';
        }

        var rows =
            sideRow('política', t.policy ? t.policy.name : 'ninguna') +
            sideRow('prioridad', t.priority_label || '—') +
            sideRow('estado', (t.status ? t.status.name : '—') + (t.status && t.status.stops_sla ? ' · pausa el reloj' : ''), { last: true });

        var dueRows = '';
        if (t.due) {
            dueRows =
                '<div class="tkt-cap">Vencimientos</div>' +
                '<div class="tkt-side-rows">' +
                    sideRow('1ª respuesta', t.first_response_at
                        ? 'respondida ' + formatDateShort(t.first_response_at)
                        : slaDueText(t.due.first_response) + (t.due.first_response.breached ? ' · incumplido' : ''), { mono: true }) +
                    sideRow('siguiente respuesta', slaDueText(t.due.next_response) + (t.due.next_response.breached ? ' · incumplido' : ''), { mono: true }) +
                    sideRow('resolución', slaDueText(t.due.resolution) + (t.due.resolution.breached ? ' · incumplido' : ''), { mono: true, last: !t.paused }) +
                    // El vencimiento efectivo solo se separa del nominal mientras
                    // hay una pausa en curso: fuera de ese caso repetir la fila
                    // sería ruido.
                    (t.paused && t.effective_resolution_due_at
                        ? sideRow('resolución (con la pausa)', formatDateShort(t.effective_resolution_due_at), { mono: true, strong: true, last: true })
                        : '') +
                '</div>';
        }

        return head + '<div class="tkt-side-rows">' + rows + '</div>' + dueRows;
    }

    // ── Pestaña 2: objetivos por prioridad ────────────────────
    // "1ª respuesta / resolución" de una política, con la unidad que corresponda:
    // minutos si son los que lee el reloj, horas declaradas si es lo único que hay.
    function slaPolicyPair(p) {
        return p.clock_enforced
            ? slaFmtMinutes(p.first_response_minutes) + ' / ' + slaFmtMinutes(p.resolution_minutes)
            : slaFmtHours(p.declared_hours.first_response) + ' / ' + slaFmtHours(p.declared_hours.resolution);
    }

    function slaPaneTargets(d) {
        var list = d.policies || [];

        if (!list.length) {
            return '<div class="tkt-empty-box">No hay ninguna política de SLA activa.</div>';
        }

        var out = '';

        // Resumen "Objetivos por prioridad" del mockup. Sale de la columna
        // `priority` de las propias políticas (cada una está etiquetada con una),
        // no de un cálculo inventado; las políticas sin prioridad asignada no
        // aparecen aquí, solo en su ficha de abajo.
        var conPrioridad = list.filter(function (p) { return !!p.priority_label; });

        if (conPrioridad.length) {
            out += '<div class="tkt-cap">Objetivos por prioridad</div>' +
                '<div class="tkt-side-rows">' +
                    conPrioridad.map(function (p, i) {
                        return sideRow(p.priority_label, slaPolicyPair(p), { mono: true, last: i === conPrioridad.length - 1 });
                    }).join('') +
                '</div>' +
                '<div class="tkt-modal-note">1ª respuesta / resolución, según la política de cada prioridad.</div>';
        }

        // El aviso de "estos plazos no los aplica el reloj" va UNA vez por panel:
        // repetirlo en las cuatro fichas (que es el caso real hoy) lo convierte en
        // ruido y deja de leerse.
        var sinEfecto = list.filter(function (p) { return !p.clock_enforced; });

        if (sinEfecto.length) {
            out += '<div class="tkt-note warn"><i class="fa-solid fa-triangle-exclamation"></i> ' +
                (sinEfecto.length === list.length ? 'Ninguna de estas políticas fija' : sinEfecto.length + ' de estas políticas no fijan') +
                ' vencimientos: tienen los plazos en las columnas heredadas (en horas) y el cálculo lee las de minutos, que están vacías. ' +
                'Vuelve a guardarlas desde la pantalla de políticas para que empiecen a contar.</div>';
        }

        return out + list.map(function (p) {
            var chips = '';
            if (p.is_default) chips += '<span class="tkt-rchip ok">predeterminada</span> ';
            if (p.priority_label) chips += '<span class="tkt-rchip">' + escapeHtml(p.priority_label) + '</span> ';
            if (p.channel) chips += '<span class="tkt-rchip">' + escapeHtml(p.channel) + '</span> ';
            if (!p.clock_enforced) chips += '<span class="tkt-rchip strong">sin efecto</span>';

            var body;

            if (p.clock_enforced) {
                // Objetivos base + los efectivos por prioridad, que es el mismo
                // cálculo que hace el motor (base × multiplicador).
                body =
                    '<div class="tkt-side-rows">' +
                        sideRow('1ª respuesta', slaFmtMinutes(p.first_response_minutes), { mono: true }) +
                        sideRow('respuestas siguientes', slaFmtMinutes(p.next_response_minutes), { mono: true }) +
                        sideRow('resolución', slaFmtMinutes(p.resolution_minutes), { mono: true, last: true }) +
                    '</div>' +
                    (p.targets_by_priority && p.targets_by_priority.length
                        ? '<div class="tkt-cap tkt-slacal-cap">Por prioridad · 1ª respuesta / resolución</div>' +
                          '<div class="tkt-side-rows">' +
                              p.targets_by_priority.map(function (row, i) {
                                  return sideRow(
                                      row.label + ' (×' + row.multiplier + ')',
                                      slaFmtMinutes(row.first_response_minutes) + ' / ' + slaFmtMinutes(row.resolution_minutes),
                                      { mono: true, last: i === p.targets_by_priority.length - 1 }
                                  );
                              }).join('') +
                          '</div>'
                        : '');
            } else {
                // Caso real de esta instalación: la política declara horas en las
                // columnas heredadas y el motor lee las de minutos, que están
                // vacías. El "(declarada)" de cada etiqueta es lo que evita leer
                // "resolución 24 h" como un plazo que se aplica; el porqué está en
                // el aviso único de la cabecera del panel.
                body =
                    '<div class="tkt-side-rows">' +
                        sideRow('1ª respuesta (declarada)', slaFmtHours(p.declared_hours.first_response), { mono: true }) +
                        sideRow('respuestas siguientes (declarada)', slaFmtHours(p.declared_hours.next_response), { mono: true }) +
                        sideRow('resolución (declarada)', slaFmtHours(p.declared_hours.resolution), { mono: true, last: true }) +
                    '</div>';
            }

            // Horario propio de la política: es el ÚNICO que usa el reloj de
            // tickets. Va aquí, en la política, y no en la pestaña de horario, que
            // muestra el calendario de la empresa (otro alcance).
            var hoursText;
            if (!p.business_hours_only) {
                hoursText = 'cuenta 24/7';
            } else if (p.business_hours) {
                hoursText = 'horario propio definido';
            } else {
                hoursText = 'L-V 09:00–17:00 (por defecto del cálculo)';
            }

            return '<div class="tkt-side-card">' +
                '<div class="tkt-side-card-head">' + escapeHtml(p.name) + '<span class="tkt-spacer">' + chips + '</span></div>' +
                '<div class="tkt-side-card-body">' +
                    body +
                    '<div class="tkt-side-rows">' +
                        sideRow('horario', hoursText, { mono: true }) +
                        sideRow('zona horaria', p.timezone, { mono: true, last: !p.enable_escalation }) +
                        (p.enable_escalation
                            ? sideRow('escala al', (p.escalation_threshold_percent || 0) + ' % consumido', { mono: true, last: true })
                            : '') +
                    '</div>' +
                '</div>' +
            '</div>';
        }).join('');
    }

    // ── Pestaña 3: horario de la empresa, festivos y pausas ───
    function slaPaneHours(d) {
        var bh = d.business_hours || {};
        var hol = d.holidays || {};
        var pause = d.pause || {};
        var links = d.links || {};
        var out = '';

        // ── Pausa automática al esperar al cliente ──
        // Es el único ajuste editable del modal: el flag stops_sla_timer del
        // catálogo de estados, que es lo que dispara pauseSla()/resumeSla().
        out += '<div class="tkt-cap">Pausar el SLA al esperar al cliente</div>';

        // Los estados cerrados (Resuelto/Cerrado) no entran: un ticket cerrado ya
        // está fuera del control de SLA (checkBreaches filtra por closed_at), así
        // que ofrecer ahí el interruptor solo añade ruido. Excepción: si alguno lo
        // tiene puesto de verdad, se muestra — ocultar estado real sería peor.
        var todos = pause.statuses || [];
        var statuses = todos.filter(function (s) { return !s.is_closed || s.stops_sla; });
        var ocultos = todos.length - statuses.length;

        if (!statuses.length) {
            out += '<div class="tkt-empty-box">No hay estados en el catálogo.</div>';
        } else {
            var alguno = statuses.some(function (s) { return s.stops_sla; });

            out += '<div class="tkt-side-rows tkt-slacal-toggles">' +
                statuses.map(function (s) {
                    return '<button type="button" class="tkt-toggle-row tkt-slacal-toggle' + (s.stops_sla ? ' on' : '') + '"' +
                            ' data-status-id="' + s.id + '" data-stops="' + (s.stops_sla ? '1' : '0') + '"' +
                            (pause.can_manage ? '' : ' disabled') + '>' +
                            '<i class="fa-solid ' + (s.stops_sla ? 'fa-toggle-on' : 'fa-toggle-off') + '"></i>' +
                            '<span class="n">' + escapeHtml(s.name) + '</span>' +
                            '<span class="s">' + (s.stops_sla ? 'pausa' : 'no pausa') + '</span>' +
                        '</button>';
                }).join('') +
            '</div>';

            // Siempre en el HTML (oculto si ya hay alguno encendido) para que el
            // toggle pueda mostrarlo/ocultarlo sin repintar el panel entero.
            out += '<div class="tkt-note warn tkt-slacal-nopause' + (alguno ? ' off' : '') + '">' +
                '<i class="fa-solid fa-triangle-exclamation"></i> Ningún estado pausa el reloj ahora mismo: el SLA sigue corriendo también mientras se espera al cliente.</div>';

            out += '<div class="tkt-modal-note">Se aplica a los próximos cambios de estado; los tickets que ya están en ese estado no se pausan hacia atrás. ' +
                'La reanudación es automática al salir del estado o al responder el cliente por el portal, y devuelve a los plazos el tiempo esperado.' +
                (ocultos ? ' No se lista' + (ocultos === 1 ? ' 1 estado de cierre, donde' : 'n ' + ocultos + ' estados de cierre, donde') + ' el SLA ya no corre.' : '') +
                '</div>';

            if (!pause.can_manage) {
                out += '<div class="tkt-note"><i class="fa-solid fa-lock"></i> Solo lectura: hace falta el permiso de ajustes del módulo para cambiarlo.</div>';
            } else if (links.statuses) {
                out += '<a class="tkt-btn tkt-w-100" href="' + escapeHtml(links.statuses) + '">Editar el catálogo de estados</a>';
            }
        }

        // ── Horario de atención de la empresa ──
        out += '<div class="tkt-cap tkt-slacal-cap">Horario de atención de la empresa</div>';

        if (!bh.configured) {
            out += '<div class="tkt-empty-box">No hay ningún horario de atención configurado.</div>';
        } else {
            out += '<div class="tkt-side-rows">' +
                (bh.days || []).map(function (day, i) {
                    return sideRow(
                        day.name,
                        day.is_open && day.opens_at ? day.opens_at + ' – ' + day.closes_at : 'cerrado',
                        { mono: true, last: i === bh.days.length - 1 }
                    );
                }).join('') +
            '</div>';

            // Alcance real: esta rejilla NO la mira el reloj de SLA de tickets
            // (cada política lleva el suyo, ver pestaña Objetivos). Decirlo evita
            // que alguien "arregle" un plazo tocando aquí.
            out += '<div class="tkt-modal-note">Zona horaria ' + escapeHtml(bh.timezone || '—') + '. ' +
                'Este calendario rige el SLA de conversaciones' +
                (bh.used_by_escalation ? ' y el escalado de tickets' : '') +
                '; los plazos de los tickets usan el horario de su propia política.</div>';
        }

        if (links.business_hours) {
            out += '<a class="tkt-btn tkt-w-100" href="' + escapeHtml(links.business_hours) + '">Editar el horario de atención</a>';
        }

        // ── Festivos ──
        if (hol.available !== false) {
            out += '<div class="tkt-cap tkt-slacal-cap">Festivos</div>';

            if (!hol.total) {
                out += '<div class="tkt-empty-box">No hay festivos dados de alta.</div>';
            } else if (!hol.upcoming || !hol.upcoming.length) {
                out += '<div class="tkt-empty-box">' + hol.total + ' festivos dados de alta, ninguno próximo.</div>';
            } else {
                out += '<div class="tkt-side-rows">' +
                    hol.upcoming.map(function (h, i) {
                        return sideRow(
                            h.name,
                            h.date + (h.is_recurring ? ' · anual' : ''),
                            { mono: true, last: i === hol.upcoming.length - 1 }
                        );
                    }).join('') +
                '</div>';

                if (hol.total > hol.upcoming.length) {
                    out += '<div class="tkt-modal-note">' + hol.total + ' festivos en total.</div>';
                }
            }

            if (hol.total && !hol.applies_to_ticket_sla) {
                out += '<div class="tkt-note"><i class="fa-solid fa-circle-info"></i> Ninguna política activa cuenta en horas hábiles, así que los festivos no descuentan tiempo de los plazos de los tickets.</div>';
            }

            if (links.holidays) {
                out += '<a class="tkt-btn tkt-w-100" href="' + escapeHtml(links.holidays) + '">Ver festivos</a>';
            }
        }

        return out;
    }

    // Alterna stops_sla_timer de un estado sin salir del modal. Se repinta solo la
    // fila tocada con lo que devuelve el servidor, no todo el panel: el resto de
    // pestañas puede tener scroll y estado (pestaña activa) que se perdería.
    function bindSlaPauseToggles($backdrop, d) {
        if (!d.pause || !d.pause.can_manage || !TKA.urls.slaPauseStatus) return;

        $backdrop.on('click', '.tkt-slacal-toggle', function () {
            var $btn = $(this);
            if ($btn.prop('disabled')) return;

            var siguiente = $btn.data('stops') === 1 || $btn.data('stops') === '1' ? 0 : 1;

            $btn.prop('disabled', true);

            $.ajax({
                url: TKA.urls.slaPauseStatus,
                method: 'POST',
                headers: { Accept: 'application/json' },
                data: { status_id: $btn.data('status-id'), stops_sla_timer: siguiente },
                success: function (res) {
                    var stops = !!(res && res.status && res.status.stops_sla);
                    $btn.data('stops', stops ? 1 : 0)
                        .attr('data-stops', stops ? '1' : '0')
                        .toggleClass('on', stops)
                        .prop('disabled', false);
                    $btn.find('i').attr('class', 'fa-solid ' + (stops ? 'fa-toggle-on' : 'fa-toggle-off'));
                    $btn.find('.s').text(stops ? 'pausa' : 'no pausa');

                    // El aviso "ningún estado pausa el reloj" deja de ser cierto en
                    // cuanto se enciende uno.
                    var quedaAlguno = $backdrop.find('.tkt-slacal-toggle.on').length > 0;
                    $backdrop.find('.tkt-slacal-nopause').toggleClass('off', quedaAlguno);

                    if (window.toastr && res && res.message) toastr.success(res.message);
                },
                error: function (xhr) {
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'No se ha podido cambiar la pausa del SLA';
                    if (window.toastr) toastr.error(msg); else window.alert(msg);
                    $btn.prop('disabled', false);
                },
            });
        });
    }


