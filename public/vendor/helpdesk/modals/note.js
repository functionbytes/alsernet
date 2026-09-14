/*!
 * Helpdesk · modal "note" del inbox.
 *
 * Extraido de resources/views/helpdesk/inbox/partials/modals/note.blade.php.
 * La config que antes se interpolaba con Blade (rutas, flags, datos de sesion,
 * textos traducidos) viaja ahora por atributos data-* del markup, que es lo que
 * permite servir este JS como fichero estatico cacheable.
 */
(function() {
    var csrf = $('meta[name="csrf-token"]').attr('content');
    function agentRoot()     { return $('[data-bv-modal-name="note"]'); }
    function currentAgentName()     { return agentRoot().data('agent-name'); }
    function currentAgentInitials() { return agentRoot().data('agent-initials'); }

    function loadNotes() {
        var convId = $('.bv-composer').data('bv-conversation-id');
        var $list = $('#noteList');
        var $title = $('#noteListTitle');
        if (!convId) {
            $list.html('<div class="bv-list-state">Sin conversación activa</div>');
            $title.hide();
            return;
        }
        $list.html('<div class="bv-list-state"><i class="fas fa-circle-notch fa-spin"></i> Cargando…</div>');
        $.ajax({
            url: '/panel/helpdesk/conversations/' + convId + '/notes',
            method: 'GET',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
        }).done(function(resp) {
            var notes = resp.notes || [];
            if (!notes.length) {
                $list.html('<div class="bv-list-state">Sin notas internas</div>');
                $title.hide();
                return;
            }
            $title.show().text('Notas existentes (' + notes.length + ')');
            var html = '';
            notes.forEach(function(n) {
                var pinned = n.is_pinned ? '<span class="pin-flag"><i class="fas fa-thumbtack"></i></span>' : '';
                var colorClass = 'c' + ((n.id % 8) + 1);
                html += '<div class="mv4-note" data-note-id="' + n.id + '">' +
                    pinned +
                    '<div class="av ' + colorClass + '">' + escapeHtml(n.initials || 'A') + '</div>' +
                    '<div class="body">' +
                        '<div class="head"><b>' + escapeHtml(n.author) + '</b><span>' + escapeHtml(n.time_ago) + '</span></div>' +
                        '<div class="txt">' + escapeHtml(n.body) + '</div>' +
                    '</div>' +
                    '<div class="actions">' +
                        '<button class="tt note-btn-delete" data-tt="Eliminar"><i class="far fa-trash-can"></i></button>' +
                    '</div>' +
                '</div>';
            });
            $list.html(html);
        }).fail(function() {
            $list.html('<div class="bv-list-state">Error al cargar notas</div>');
            $title.hide();
        });
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    $(document).on('click', '#noteBtnSave', function() {
        var txt = $('#noteBody').val().trim();
        if (!txt) {
            toastr.warning('Escribe algo antes de guardar');
            return;
        }

        var $btn = $(this);
        var $composer = $('.bv-composer[data-bv-conversation-id]');
        var sendUrl = $composer.data('bv-send-url');

        if (!sendUrl) {
            toastr.warning('No hay conversación activa');
            return;
        }

        $btn.prop('disabled', true);

        $.ajax({
            url: sendUrl,
            method: 'POST',
            dataType: 'json',
            data: {
                body: txt,
                is_internal: 1,
                action: 'send',
                metadata: { is_pinned: $('#notePin').is(':checked') ? 1 : 0 },
            },
            headers: {
                'X-CSRF-TOKEN': csrf,
                'Accept': 'application/json',
            },
        })
            .done(function(resp) {
                if (resp && resp.item) {
                    $(document).trigger('bv:message:added', [resp.item, true]);
                }
                $('#noteBody').val('');
                $('#notePin').prop('checked', false);
                loadNotes();
            })
            .fail(function(xhr) {
                var msg = xhr?.responseJSON?.errors?.body?.[0]
                    || xhr?.responseJSON?.message
                    || 'No se pudo guardar la nota';
                toastr.error(msg);
            })
            .always(function() {
                $btn.prop('disabled', false);
            });
    });

    $(document).on('click', '.note-btn-delete', function() {
        var $note = $(this).closest('.mv4-note');
        var noteId = $note.data('note-id');
        if (!noteId) return;
        window.__confirm('¿Eliminar esta nota?', function () {
        $.ajax({
            url: '/panel/helpdesk/conversation-items/' + noteId,
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
        }).done(function() {
            $note.fadeOut(200, function() { $(this).remove(); });
            var count = $('.mv4-note').length;
            if (count <= 1) $('#noteListTitle').hide();
            else $('#noteListTitle').text('Notas existentes (' + (count - 1) + ')');
        }).fail(function() {
            toastr.error('No se pudo eliminar la nota');
        });
    });
    });

    $(document).on('bv:modal:open', function(e, name) {
        if (name !== 'note') return;
        $('#noteBody').val('');
        $('#notePin').prop('checked', false);
        // Update composer avatar to current agent
        $('.mv4-note-compose .av').text(currentAgentInitials() || 'A').attr('class', 'av c1');
        $('.mv4-note-compose span b').text(currentAgentName() || 'Agente');
        loadNotes();
    });
}());
