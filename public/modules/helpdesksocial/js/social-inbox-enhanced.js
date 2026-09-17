/**
 * Detalle de comentario social (managers/social-inbox/show.blade.php):
 * etiquetas del comentario, notas internas, respuestas guardadas y
 * solicitud de aprobación. Sustituye a un `js/social-inbox-enhanced.js`
 * que se referenciaba desde la vista pero no existía en ningún sitio del
 * repo — todos los botones (Añadir/quitar etiqueta, Escribir nota,
 * Recargar plantillas, Solicitar aprobación) fallaban en silencio con un
 * ReferenceError.
 *
 * El backend de etiquetas/notas/aprobación ya existía completo (rutas API
 * bajo /api/helpdesk/social/, protegidas con Sanctum stateful — confirmado
 * funcionando porque social-inbox-index.js ya llama a esa misma API desde
 * el navegador). Lo único que faltaba de verdad era el endpoint para
 * asociar/quitar una etiqueta a UN comentario (la relación Eloquent
 * `SocialComment::tags()` existía, pero no había ruta/controlador que la
 * expusiera) — se añadió `attachTag`/`detachTag` en SocialInboxController
 * junto con este archivo.
 *
 * La "Sugerencia IA" (#aiSuggestionBox) NO tiene backend en ningún sitio
 * del módulo (ni servicio, ni endpoint) — no es una limpieza, es una
 * funcionalidad nunca construida. Se deja intencionalmente inerte (el box
 * ya nace oculto con d-none y este fichero nunca la muestra); el botón
 * "Usar sugerencia" queda con un stub seguro para no romper si algún día
 * alguien la muestra a mano.
 *
 * Depende de #social-comment-show[data-comment-id] para saber sobre qué
 * comentario operar — lo único que este fichero no puede resolver solo.
 */
(function ($) {
    'use strict';

    var API_BASE = '/api/helpdesk/social';
    var platform = $('#social-comment-show').data('platform');

    function csrfHeaders() {
        return { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') };
    }

    function flashError(xhr, fallback) {
        var msg = (xhr && xhr.responseJSON && xhr.responseJSON.message) || fallback;
        if (window.toastr) {
            toastr.error(msg);
        }
    }

    // El color de cada etiqueta viene en data-tag-color (nunca en un style=""
    // inline) — .hso-tag-badge lo consume via la custom property --hso-tag-color.
    function applyTagColors() {
        $('#tagsContainer [data-tag-color]').each(function () {
            $(this).css('--hso-tag-color', $(this).data('tag-color'));
        });
    }

    $(applyTagColors);

    // ── Etiquetas ──────────────────────────────────────────────────────
    // Nombre esperado por social-inbox-show.js, que ya llama a
    // window.loadAvailableTags() en su propio $(document).ready si existe.
    window.loadAvailableTags = function () {
        var $select = $('#tagSelect');
        if (!$select.length) { return; }

        $.get(API_BASE + '/tags', { per_page: 100 }).done(function (res) {
            var current = $('#tagsContainer [data-tag-id]').map(function () {
                return String($(this).data('tag-id'));
            }).get();

            $select.find('option:not(:first)').remove();
            (res.data || []).forEach(function (tag) {
                if (current.indexOf(String(tag.id)) !== -1) { return; }
                $select.append($('<option>', { value: tag.id, text: tag.name }));
            });
        }).fail(function (xhr) {
            flashError(xhr, 'No se pudieron cargar las etiquetas.');
        });
    };

    window.addTag = function (id) {
        var tagId = $('#tagSelect').val();
        if (!tagId) { return; }

        $.ajax({
            url: API_BASE + '/inbox/' + id + '/tags',
            method: 'POST',
            headers: csrfHeaders(),
            data: { tag_id: tagId },
        }).done(function () {
            window.location.reload();
        }).fail(function (xhr) {
            flashError(xhr, 'No se pudo añadir la etiqueta.');
        });
    };

    window.removeTag = function (id, tagId) {
        $.ajax({
            url: API_BASE + '/inbox/' + id + '/tags/' + tagId,
            method: 'DELETE',
            headers: csrfHeaders(),
        }).done(function () {
            window.location.reload();
        }).fail(function (xhr) {
            flashError(xhr, 'No se pudo quitar la etiqueta.');
        });
    };

    // ── Notas internas ─────────────────────────────────────────────────
    window.postNote = function (id) {
        var $input = $('#noteInput');
        var body = $.trim($input.val());
        if (!body) { return; }

        $.ajax({
            url: API_BASE + '/comments/' + id + '/notes',
            method: 'POST',
            headers: csrfHeaders(),
            // social_comment_id: StoreSocialNoteRequest lo exige en las
            // reglas de validacion aunque el controlador ya lo rellena del
            // {comment} de ruta despues de validar — sin esto, 422.
            data: { body: body, social_comment_id: id },
        }).done(function (res) {
            var note = res.data;
            $('#noNotesText').remove();
            $('#notesList').prepend(
                $('<div>', { class: 'border-3 border-secondary ps-2 mb-2' }).append(
                    $('<p>', { class: 'mb-1 small', text: note.body }),
                    $('<small>', {
                        class: 'text-muted',
                        text: (note.user ? note.user.name : 'Sistema') + ' · ahora mismo',
                    })
                )
            );
            $input.val('');
        }).fail(function (xhr) {
            flashError(xhr, 'No se pudo guardar la nota.');
        });
    };

    // ── Respuestas guardadas ───────────────────────────────────────────
    window.loadSavedReplies = function () {
        var $menu = $('#savedRepliesDropdown');
        if (!$menu.length) { return; }

        $menu.html('<li><span class="dropdown-item-text text-muted">Cargando...</span></li>');

        $.get('/panel/helpdesk/social/saved-replies', { platform: platform }).done(function (templates) {
            $menu.empty();
            if (!templates || !templates.length) {
                $menu.append('<li><span class="dropdown-item-text text-muted">Sin plantillas para este canal.</span></li>');
                return;
            }
            templates.forEach(function (tpl) {
                var $item = $('<li>').append(
                    $('<a>', { class: 'dropdown-item', href: '#', text: tpl.name })
                        .on('click', function (e) {
                            e.preventDefault();
                            $('#replyBody').val(tpl.body);
                        })
                );
                $menu.append($item);
            });
        }).fail(function (xhr) {
            $menu.html('<li><span class="dropdown-item-text text-danger">Error al cargar plantillas.</span></li>');
            flashError(xhr, 'No se pudieron cargar las respuestas guardadas.');
        });
    };

    // ── Sugerencia IA: sin backend, inerte a propósito (ver cabecera) ──
    window.useAiSuggestion = function () {
        var text = $.trim($('#aiSuggestionText').text());
        if (text) {
            $('#replyBody').val(text);
        }
    };

    // ── Aprobación ─────────────────────────────────────────────────────
    window.requestApproval = function (id) {
        var body = $.trim($('#replyBody').val());

        $.ajax({
            url: API_BASE + '/approval-requests',
            method: 'POST',
            headers: csrfHeaders(),
            data: {
                social_comment_id: id,
                action_type: 'reply',
                payload: body ? { body: body } : undefined,
            },
        }).done(function () {
            if (window.toastr) {
                toastr.success('Solicitud de aprobación enviada.');
            }
        }).fail(function (xhr) {
            flashError(xhr, 'No se pudo solicitar la aprobación.');
        });
    };

    // No hay auto-init aqui: social-inbox-show.js ya llama a
    // window.loadSavedReplies() y window.loadAvailableTags() en su propio
    // $(document).ready tras cargar este fichero (que se sirve antes, sin
    // defer, así que ambas funciones ya existen cuando las invoca).
})(jQuery);
