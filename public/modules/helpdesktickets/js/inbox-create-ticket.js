/**
 * Modal "Escalar a ticket" de la bandeja — propiedad de HelpdeskTickets.
 *
 * Vivía dentro de conversations.js (el bundle del CORE Helpdesk) pese a que la
 * vista, la ruta, el controlador y el servicio son de este módulo: con la
 * integración desactivada se seguía sirviendo igual. Se carga desde el propio
 * slot (inbox-slots/create-ticket-modal.blade.php), así que solo llega al
 * navegador cuando el modal se renderiza.
 *
 * Solo depende de jQuery, toastr y window.bvTicketI18n (que publica el slot).
 * Tras editarlo hay que copiarlo a public/modules/helpdesktickets/js/.
 */
(function () {
    'use strict';

    // Pre-fill create-ticket modal when opened
    $(document).on('click', '[data-bv-modal="create-ticket"]', function () {
        // Misma fuente que usa el envío (.bv-composer): antes el contexto leía
        // el id de .bv-conv.on y el POST el de .bv-composer, así que en cuanto
        // las dos se desincronizaban el modal decía una conversación y el
        // ticket se creaba en otra.
        var convId = currentConversationId() || '';
        var $messages = $('.bv-msg');
        var msgCount = $messages.length;

        renderTicketContext(convId, msgCount);
        renderSlaHint();

        // Build description from recent messages (last 5)
        var descLines = [];
        $messages.slice(-5).each(function () {
            var $bubble = $(this).find('.bv-bubble');
            var author = $bubble.data('bv-author') || '—';
            var body = $bubble.data('bv-body') || '';
            if (body) {
                descLines.push(author + ': ' + body);
            }
        });
        var description = descLines.join('\n');
        if (description) {
            $('#bv-ticket-description').val(description);
        }

        // Pre-fill subject from first customer message if empty
        var $subjectInput = $('#bv-ticket-subject');
        if (!$subjectInput.val().trim() && descLines.length > 0) {
            var firstMsg = descLines[0].replace(/^[^:]+:\s*/, '').substring(0, 80);
            $subjectInput.val(firstMsg);
        }
    });

    // ─── Create Ticket Modal ──────────────────────────────────────────────────

    // El id de la conversación abierta. Única fuente para el modal de escalado.
    function currentConversationId() {
        // Tres fuentes en orden de fiabilidad: el composer del hilo abierto, la
        // fila seleccionada de la bandeja y, como último recurso, el ?selected=
        // de la URL — que es lo único que queda cuando se entra por enlace
        // directo a una conversación sin composer (cerrada o archivada).
        var fromUrl = new URLSearchParams(window.location.search).get('selected');

        return $('.bv-composer').data('bv-conversation-id')
            || $('.bv-conv.on').data('bv-conv-id')
            || fromUrl
            || '';
    }

    // El texto del contexto sale de las dos formas del plural que la plantilla
    // deja en data-tpl-one/data-tpl-many (traducidas en servidor). Antes el JS
    // concatenaba '#' + id sobre un '#' que ya estaba en el marcado ("##2226")
    // y escribía siempre "N mensajes", incluido "1 mensajes".
    function renderTicketContext(convId, count) {
        var $ctx = $('#bv-ticket-context');
        if (!$ctx.length) return;

        var tpl = count === 1 ? $ctx.data('tpl-one') : $ctx.data('tpl-many');
        if (!tpl) return;

        $ctx.text(String(tpl)
            .replace(':id', convId || '—')
            .replace(':count', count));
    }

    function ticketI18n(key, fallback) {
        return (window.bvTicketI18n && window.bvTicketI18n[key]) || fallback;
    }

    // Qué SLA heredará el ticket según la categoría elegida. El texto ya viene
    // traducido y formateado del servidor en data-sla de cada <option>.
    function renderSlaHint() {
        var $sel = $('#bv-ticket-category');
        var $hint = $('#bv-ticket-sla-hint');
        if (!$sel.length || !$hint.length) return;

        var sla = $sel.find('option:selected').data('sla');
        $hint.text(sla ? ($sel.data('sla-prefix') + ' ' + sla) : $sel.data('sla-none'));
    }

    $(document).on('change', '#bv-ticket-category', renderSlaHint);

    // Añade el ticket recién creado al aviso de "esta conversación ya tiene
    // ticket": el modal es un único nodo por página, así que si el agente lo
    // reabre sin recargar tiene que ver que ya escaló.
    function markConversationEscalated(ticket) {
        if (!ticket || !ticket.ticket_number) return;

        var $box = $('#bv-ticket-duplicates');
        var $list = $('#bv-ticket-dup-list');
        if (!$box.length || !$list.length) return;

        var yaEsta = $list.find('a').filter(function () {
            return $(this).text().trim() === ticket.ticket_number;
        }).length > 0;

        if (!yaEsta) {
            if ($list.children().length) { $list.append(', '); }
            $list.append(
                $('<a>', { href: ticket.url, target: '_blank', rel: 'noopener' })
                    .text(ticket.ticket_number)
            );
        }

        $box.removeClass('bv-hidden');

        var $btn = $('#bv-btn-create-ticket');
        if ($btn.data('label-another')) { $btn.data('label', $btn.data('label-another')); }
    }

    $(document).on('click', '#bv-ticket-priority .prio-card', function () {
        $(this).siblings().removeClass('on');
        $(this).addClass('on');
    });

    $(document).on('click', '#bv-btn-create-ticket', function () {
        var $btn = $(this);
        var subject = $('#bv-ticket-subject').val().trim();
        if (!subject) {
            var subjectMsg = ticketI18n('subjectRequired', 'El asunto es obligatorio.');
            if (window.toastr) toastr.error(subjectMsg);
            else alert(subjectMsg);
            return;
        }
        // Sin fallback a 'medium': esa prioridad no existe en HelpdeskTickets.
        var priority = $('#bv-ticket-priority .prio-card.on').data('priority') || 'normal';
        var categoryId = $('#bv-ticket-category').val() || null;
        var assigneeId = $('#bv-ticket-assignee').val() || null;
        var description = $('#bv-ticket-description').val().trim();

        var convId = currentConversationId();
        if (!convId) {
            if (window.toastr) toastr.error(ticketI18n('noConversation', 'No hay conversación activa.'));
            return;
        }

        // Botón sin icono, aquí y al restaurarlo: el 'complete' original le
        // enchufaba un <i class="fa-ticket"> que no estaba en el marcado, así
        // que tras el primer envío el botón ya no volvía a su estado inicial.
        var labelIdle = $btn.data('label') || $btn.text().trim();
        $btn.prop('disabled', true).text($btn.data('label-busy') || 'Creando...');

        $.ajax({
            url: '/panel/helpdesk/conversations/' + convId + '/ticket',
            method: 'POST',
            dataType: 'json',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                subject: subject,
                description: description,
                priority: priority,
                category_id: categoryId,
                assignee_id: assigneeId,
                group_id: $('#bv-ticket-group').val() || null,
                // Las dos casillas del modal no se enviaban: adjuntar la
                // transcripción y notificar al cliente eran decorativas.
                attach_transcript: $('#bv-ticket-attach-chat').is(':checked') ? 1 : 0,
                notify_customer: $('#bv-ticket-notify').is(':checked') ? 1 : 0,
            },
            success: function (res) {
                // closeModal() lives in a different IIFE in this bundle and isn't
                // reachable from here; replicate its non-lightbox behavior inline.
                $('[data-bv-modal-name="create-ticket"]').removeClass('on');
                if ($('.bv-modal.on').length === 0) { $('body').css('overflow', ''); }

                // window.open() dentro del callback AJAX lo bloquea el navegador
                // (ya no es un gesto directo del usuario) y el ticket recién
                // creado no se veía por ninguna parte. Ahora el aviso lleva un
                // enlace real, que sí abre pestaña al pulsarlo.
                var msg = res.message || 'Ticket creado correctamente.';
                if (window.toastr && res.ticket_url) {
                    toastr.success(
                        msg + ' <a href="' + res.ticket_url + '" target="_blank" rel="noopener" class="text-white text-decoration-underline">' +
                        ticketI18n('viewTicket', 'Ver ticket') + '</a>',
                        null,
                        { enableHtml: true, timeOut: 12000, extendedTimeOut: 6000 }
                    );
                } else if (window.toastr) {
                    toastr.success(msg);
                }

                // El refresco del panel va PRIMERO y aislado. Antes era la última
                // de las tres llamadas: cualquier fallo en las dos anteriores
                // (que tocan nodos del hilo y del formulario que no siempre
                // están en el DOM — la conversación puede estar cerrada, el
                // modal recién inyectado, el hilo repintado por la SPA) cortaba
                // la ejecución del callback antes de llegar aquí, y el ticket
                // recién creado no aparecía en el panel hasta pulsar F5. Ese es
                // el "a veces" que se veía: no fallaba el refresco, es que no
                // llegaba a ejecutarse.
                refreshTicketsPanel();

                try {
                    markConversationEscalated(res.ticket);
                } catch (e) {
                    if (window.console) console.warn('[tickets] no se pudo marcar la conversación como escalada', e);
                }
                try {
                    resetCreateTicketForm();
                } catch (e) {
                    if (window.console) console.warn('[tickets] no se pudo limpiar el formulario', e);
                }
            },
            error: function (xhr) {
                var msg = ticketI18n('error', 'Error al crear el ticket.');
                if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                // 422 de validación: el mensaje útil está en errors, no en message.
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    var first = Object.keys(xhr.responseJSON.errors)[0];
                    if (first) msg = xhr.responseJSON.errors[first][0];
                }
                if (window.toastr) toastr.error(msg);
                else alert(msg);
            },
            complete: function () {
                $btn.prop('disabled', false).text(labelIdle);
            }
        });
    });

    // Deja el modal listo para el siguiente escalado: es un único nodo por
    // página, así que sin esto la segunda vez se abría con el asunto y la
    // descripción del ticket anterior.
    function resetCreateTicketForm() {
        $('#bv-ticket-subject').val('');
        $('#bv-ticket-description').val('');
        $('#bv-ticket-attach-chat, #bv-ticket-notify').prop('checked', true);
        $('#bv-ticket-priority .prio-card').removeClass('on')
            .filter('[data-priority="normal"]').addClass('on');
        $('#bv-ticket-category, #bv-ticket-assignee, #bv-ticket-group').val('').trigger('change.select2');
        renderSlaHint();
    }

    // El tab "Tickets" del panel derecho se pinta en servidor, así que tras
    // escalar seguía diciendo "Sin tickets relacionados" hasta recargar. Se
    // recarga solo ese fragmento pidiendo de nuevo la conversación abierta.
    //
    // Se publica en window porque el modal de detalle (conversations.js, otro
    // bundle) necesita exactamente lo mismo después de resolver o asignar un
    // ticket: sin esto, la tarjeta del panel seguiría diciendo "Nuevo · Sin
    // asignar" hasta recargar la bandeja.
    window.bvRefreshTicketsPanel = refreshTicketsPanel;

    function refreshTicketsPanel() {
        var $tab = $('[data-bv-tab-content="tickets"]');
        if (!$tab.length) return;

        var convId = currentConversationId();
        if (!convId) return;

        // Endpoint de fragmento, igual que right-panel/{files,previous,activity}:
        // devuelve solo este tab ya renderizado en servidor, así no hay que
        // reimplementar en JS el markup de las tarjetas ni recargar la bandeja.
        $.ajax({ url: '/panel/helpdesk/conversations/' + convId + '/right-panel/tickets', cache: false })
            .done(function (html) {
                var $fresh = $('<div>').append($.parseHTML(String(html)))
                    .find('[data-bv-tab-content="tickets"]').first();
                if (!$fresh.length) {
                    if (window.console) console.warn('[tickets] el fragmento del panel no traía el tab');
                    return;
                }
                $tab.html($fresh.html());
            })
            // Sin esto, un 403/404/500 dejaba el panel con los datos viejos y
            // sin ninguna pista de por qué: el agente solo veía que su ticket
            // "no se había creado", cuando sí estaba.
            .fail(function (xhr) {
                if (window.console) console.warn('[tickets] no se pudo refrescar el panel', xhr && xhr.status);
                if (window.toastr) {
                    toastr.info('El ticket se creó, pero el panel no se ha podido actualizar. Recarga para verlo.');
                }
            });
    }

})();
