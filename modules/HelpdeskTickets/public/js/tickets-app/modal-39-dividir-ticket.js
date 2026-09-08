'use strict';

    // ── Modal 39: Dividir ticket ──────────────────────────────
    function openSplitModal(t) {
        var d = TKA.state.currentDetail;
        var thread = (d && d.thread) || [];
        if (thread.length < 2) {
            if (window.toastr) toastr.info('Hace falta más de un mensaje en el hilo para poder dividirlo');
            return;
        }
        var picked = {};

        function rowsHtml() {
            return thread.map(function (m) {
                var preview = String(m.body || '').replace(/<[^>]+>/g, '').trim().slice(0, 70);
                return '<label class="tkt-pick as-option' + (picked[m.id] ? ' on' : '') + '">' +
                    '<input type="checkbox" data-split-item="' + m.id + '"' + (picked[m.id] ? ' checked' : '') + '>' +
                    '<span class="who"><span class="n">' + escapeHtml(preview || '(sin texto)') + '</span>' +
                    '<span class="s">' + escapeHtml([m.sender_name, m.created_at_human].filter(Boolean).join(' · ')) + '</span></span>' +
                '</label>';
            }).join('');
        }

        var $backdrop = openModal(modalShell({
            icon: 'fa-solid fa-scissors', kicker: 'Ticket · dividir',
            title: 'Dividir ticket', titleChip: t.ticket_number, width: 'lg',
            body: '<div class="tkt-note"><i class="fa-solid fa-circle-info"></i> Útil cuando el cliente mezcla dos asuntos en la misma conversación. Los mensajes se MUEVEN, no se copian.</div>' +
                '<div class="tkt-cap">Mensajes a mover</div>' +
                '<div class="tkt-pick-list" id="tkt-split-list">' + rowsHtml() + '</div>' +
                '<div class="tkt-field"><label class="tkt-label" for="tkt-split-subject">Asunto del nuevo ticket<span class="req">*</span></label>' +
                    '<input type="text" class="tkt-input" id="tkt-split-subject" value="' + escapeHtml(t.subject || '') + '"></div>' +
                '<div class="tkt-field-row">' +
                    '<div class="tkt-field"><label class="tkt-label" for="tkt-split-category">Categoría</label>' +
                        '<select class="tkt-input" id="tkt-split-category" data-no-select2><option value="">La misma</option>' + optionsHtml(TKA.state.categories, 'id', t.category_id) + '</select></div>' +
                    '<div class="tkt-field"><label class="tkt-label" for="tkt-split-assignee">Agente</label>' +
                        '<select class="tkt-input" id="tkt-split-assignee" data-no-select2><option value="">Sin asignar</option>' + optionsHtml(TKA.state.agentsFull, 'id', '') + '</select></div>' +
                '</div>' +
                '<label class="tkt-check"><input type="checkbox" id="tkt-split-link" checked> Vincular ambos tickets entre sí</label>',
            foot: '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-split-confirm" disabled>Dividir ticket</button>' +
                  '<button type="button" class="tkt-btn" data-modal-close>Cancelar</button>',
        }));

        function refreshConfirm() {
            var n = Object.keys(picked).length;
            $backdrop.find('#tkt-split-confirm')
                .prop('disabled', n === 0 || n >= thread.length)
                .text(n ? 'Mover ' + n + (n === 1 ? ' mensaje' : ' mensajes') : 'Dividir ticket');
            $backdrop.find('#tkt-split-warn').remove();
            if (n >= thread.length) {
                $backdrop.find('#tkt-split-list').after('<div class="tkt-note" id="tkt-split-warn"><i class="fa-solid fa-triangle-exclamation"></i> Deja al menos un mensaje en el ticket original.</div>');
            }
        }

        $backdrop.on('change', '[data-split-item]', function () {
            var id = parseInt($(this).data('split-item'), 10);
            if (this.checked) picked[id] = true; else delete picked[id];
            $(this).closest('.tkt-pick').toggleClass('on', this.checked);
            refreshConfirm();
        });

        $backdrop.on('click', '#tkt-split-confirm', function () {
            var subject = ($('#tkt-split-subject').val() || '').trim();
            if (!subject) { if (window.toastr) toastr.error('Indica el asunto del nuevo ticket'); return; }
            var $btn = $(this).prop('disabled', true).text('Dividiendo…');
            $.ajax({
                url: t.url_split, method: 'POST',
                data: {
                    subject: subject, item_ids: Object.keys(picked),
                    category_id: $('#tkt-split-category').val() || null,
                    assignee_id: $('#tkt-split-assignee').val() || null,
                    link_tickets: $('#tkt-split-link').is(':checked') ? 1 : 0,
                },
                headers: { Accept: 'application/json' },
                success: function (resp) {
                    if (window.toastr) toastr.success((resp && resp.message) || 'Ticket dividido');
                    closeModal();
                    window.location = TKA.urls.index + '?ticket=' + resp.ticket_id;
                },
                error: function (xhr) {
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'No se pudo dividir el ticket';
                    if (window.toastr) toastr.error(msg); else window.alert(msg);
                    $btn.prop('disabled', false).text('Dividir ticket');
                },
            });
        });
    }


