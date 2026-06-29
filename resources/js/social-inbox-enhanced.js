/**
 * Social Inbox Enhanced - AJAX interactions for the social inbox detail view.
 */
(function ($) {
    'use strict';

    const csrfToken = $('meta[name="csrf-token"]').attr('content');

    function getCommentId() {
        return window.currentCommentId || null;
    }

    function getPlatform() {
        return window.currentPlatform || null;
    }

    function ajax(options) {
        return $.ajax($.extend({
            headers: { 'X-CSRF-TOKEN': csrfToken },
        }, options));
    }

    function toastSuccess(message) {
        if (window.toastr) {
            toastr.success(message);
        }
    }

    function toastError(message) {
        if (window.toastr) {
            toastr.error(message);
        }
    }

    // ---------- Tags ----------

    window.loadAvailableTags = function () {
        ajax({
            url: '/panel/helpdesk-social/tags/json',
            method: 'GET',
        }).done(function (tags) {
            const $select = $('#tagSelect');
            $select.empty().append('<option value="">Seleccionar etiqueta...</option>');
            tags.forEach(function (tag) {
                $select.append(
                    '<option value="' + tag.id + '">' + tag.name + '</option>'
                );
            });
        }).fail(function () {
            toastError('No se pudieron cargar las etiquetas.');
        });
    };

    window.addTag = function (commentId) {
        const tagId = $('#tagSelect').val();
        if (!tagId) {
            toastError('Selecciona una etiqueta.');
            return;
        }

        ajax({
            url: '/panel/helpdesk-social/inbox/' + commentId + '/tags',
            method: 'POST',
            data: { tag_id: tagId },
        }).done(function () {
            toastSuccess('Etiqueta agregada.');
            window.location.reload();
        }).fail(function () {
            toastError('No se pudo agregar la etiqueta.');
        });
    };

    window.removeTag = function (commentId, tagId) {
        if (!confirm('¿Quitar esta etiqueta?')) {
            return;
        }

        ajax({
            url: '/panel/helpdesk-social/inbox/' + commentId + '/tags/' + tagId,
            method: 'DELETE',
        }).done(function () {
            toastSuccess('Etiqueta removida.');
            window.location.reload();
        }).fail(function () {
            toastError('No se pudo quitar la etiqueta.');
        });
    };

    // ---------- Saved Replies ----------

    window.loadSavedReplies = function () {
        const platform = getPlatform();
        const url = '/panel/helpdesk-social/saved-replies' + (platform ? '?platform=' + encodeURIComponent(platform) : '');

        ajax({
            url: url,
            method: 'GET',
        }).done(function (templates) {
            const $dropdown = $('#savedRepliesDropdown');
            $dropdown.empty();

            if (templates.length === 0) {
                $dropdown.append('<li><span class="dropdown-item-text text-muted">Sin plantillas</span></li>');
                return;
            }

            templates.forEach(function (template) {
                const $li = $('<li>');
                const $a = $('<a class="dropdown-item" href="#">')
                    .text(template.name)
                    .on('click', function (e) {
                        e.preventDefault();
                        $('#replyBody').val(template.body);
                    });
                $li.append($a);
                $dropdown.append($li);
            });
        }).fail(function () {
            toastError('No se pudieron cargar las respuestas guardadas.');
        });
    };

    // ---------- Internal Notes ----------

    window.postNote = function (commentId) {
        const body = $('#noteInput').val().trim();
        if (!body) {
            return;
        }

        ajax({
            url: '/panel/helpdesk-social/inbox/' + commentId + '/notes',
            method: 'POST',
            data: { body: body, type: 'internal' },
        }).done(function (note) {
            $('#noteInput').val('');
            $('#noNotesText').remove();

            const html =
                '<div class="border-start border-3 border-secondary ps-2 mb-2">' +
                '<p class="mb-1 small">' + $('<div>').text(note.body).html() + '</p>' +
                '<small class="text-muted">' + (note.user_name || 'Tú') + ' · ahora</small>' +
                '</div>';

            $('#notesList').append(html);
            toastSuccess('Nota agregada.');
        }).fail(function () {
            toastError('No se pudo agregar la nota.');
        });
    };

    // ---------- Approval Requests ----------

    window.requestApproval = function (commentId) {
        const body = $('#replyBody').val().trim();
        if (!body) {
            toastError('Escribe una respuesta antes de solicitar aprobación.');
            return;
        }

        ajax({
            url: '/panel/helpdesk-social/inbox/' + commentId + '/approvals',
            method: 'POST',
            data: { action_type: 'reply', payload: { body: body } },
        }).done(function () {
            toastSuccess('Solicitud de aprobación enviada.');
            $('#replyBody').prop('disabled', true);
        }).fail(function () {
            toastError('No se pudo enviar la solicitud de aprobación.');
        });
    };

    // ---------- AI Suggestion ----------

    window.loadAiSuggestion = function () {
        const commentId = getCommentId();
        if (!commentId) {
            return;
        }

        ajax({
            url: '/panel/helpdesk-social/inbox/' + commentId + '/ai-suggest',
            method: 'GET',
        }).done(function (response) {
            if (response.suggestion) {
                $('#aiSuggestionText').text(response.suggestion);
                $('#aiSuggestionBox').removeClass('d-none');
            }
        }).fail(function () {
            // Silently fail — AI suggestion is optional
        });
    };

    window.useAiSuggestion = function () {
        const suggestion = $('#aiSuggestionText').text();
        if (suggestion) {
            $('#replyBody').val(suggestion);
        }
    };

    // ---------- Init ----------

    $(document).ready(function () {
        if ($('#aiSuggestionBox').length) {
            loadAiSuggestion();
        }
    });
})(jQuery);
