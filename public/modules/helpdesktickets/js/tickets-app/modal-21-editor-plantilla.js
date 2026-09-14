'use strict';

    // ── Modal 21: Editor de plantilla ─────────────────────────
    function openTemplateEditorModal(reply, onSaved) {
        var $backdrop = openModal(modalShell({
            icon: 'fa-regular fa-pen-to-square', kicker: 'Plantillas · editar',
            titleChip: reply.title || '',
            title: 'Editor de plantilla', width: 'lg',
            body: '<div class="tkt-field-row">' +
                    '<div class="tkt-field"><label class="tkt-label" for="tkt-tpled-title">Nombre<span class="req">*</span></label>' +
                        '<input type="text" class="tkt-input" id="tkt-tpled-title" value="' + escapeHtml(reply.title || '') + '"></div>' +
                    '<div class="tkt-field"><label class="tkt-label" for="tkt-tpled-code">Atajo</label>' +
                        '<input type="text" class="tkt-input" id="tkt-tpled-code" value="' + escapeHtml(reply.short_code || '') + '" placeholder="/doc"></div>' +
                  '</div>' +
                  '<div class="tkt-field"><label class="tkt-label" for="tkt-tpled-body">Contenido<span class="req">*</span>' +
                    // Sintaxis real de TicketVariableInterpolator (fuente única
                    // de variables): snake_case plano, nunca con puntos —
                    // {{cliente.nombre}} nunca se sustituía (bug 11-sep-2026).
                    '<span class="hint">variables: {{customer_name}} {{ticket_number}} {{agent_name}}</span></label>' +
                    '<textarea class="tkt-input" id="tkt-tpled-body" style="min-height:160px">' + escapeHtml(reply.content || '') + '</textarea></div>' +
                  '<div class="tkt-cap">Vista previa</div>' +
                  '<div class="tkt-tpl-preview" id="tkt-tpled-preview">' + escapeHtml(reply.content || '') + '</div>',
            foot: '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-tpled-save">Guardar plantilla</button>' +
                  '<button type="button" class="tkt-btn" id="tkt-tpled-back">Volver</button>',
        }));

        $backdrop.on('input', '#tkt-tpled-body', function () { $backdrop.find('#tkt-tpled-preview').text(this.value); });

        $backdrop.on('click', '#tkt-tpled-save', function () {
            var title = ($('#tkt-tpled-title').val() || '').trim();
            var content = ($('#tkt-tpled-body').val() || '').trim();
            if (!title || !content) { if (window.toastr) toastr.error('El nombre y el contenido son obligatorios'); return; }
            var $btn = $(this).prop('disabled', true).text('Guardando…');
            $.ajax({
                // POST + _method=PUT: un PUT real por AJAX devuelve 405 en este
                // entorno Docker aunque route:list lo registre.
                url: TKA.urls.cannedUpdateTemplate.replace('__REPLY__', reply.id),
                method: 'POST',
                data: { _method: 'PUT', title: title, content: content, short_code: ($('#tkt-tpled-code').val() || '').trim() || null },
                headers: { Accept: 'application/json' },
                success: function () {
                    if (window.toastr) toastr.success('Plantilla guardada');
                    var local = (TKA.state.cannedReplies || []).find(function (r) { return String(r.id) === String(reply.id); });
                    if (local) { local.title = title; local.content = content; }
                    closeModal(); if (onSaved) onSaved();
                },
                error: function (xhr) {
                    var msg = apiErrorMessage(xhr, 'No se pudo guardar la plantilla');
                    if (window.toastr) toastr.error(msg); else window.alert(msg);
                    $btn.prop('disabled', false).text('Guardar plantilla');
                },
            });
        });
        $backdrop.on('click', '#tkt-tpled-back', function () { closeModal(); if (onSaved) onSaved(); });
    }


