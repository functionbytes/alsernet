/**
 * ai-agent-tags.js — HelpdeskAgents module
 *
 * Etiquetas del agente IA (pestaña "Etiquetas" en Ajustes del agente).
 *
 * El contenido de #tags-container se sustituye por AJAX cada vez que se abre
 * la pestaña o se crea/edita/borra una etiqueta (ver reloadTagsTab). Por eso
 * todos los manejadores están delegados a #tags-container o a document, que
 * nunca se destruyen — así siguen funcionando tras cada recarga sin volver a
 * cargar este script.
 *
 * Depende de: jQuery, toastr (globales) y window.HelpdeskAgentsAiSettings.tags
 * (URLs de rutas, inyectadas por managers/ai-agent/settings.blade.php).
 */
(function ($) {
    'use strict';

    var config = (window.HelpdeskAgentsAiSettings && window.HelpdeskAgentsAiSettings.tags) || {};

    function urlFor(template, id) {
        return template.replace('__ID__', id);
    }

    function reloadTagsTab() {
        $.get(config.indexUrl, function (html) {
            $('#tags-container').html(html);
            var count = $('#tags-container [data-count-item]').length;
            $('#tags-count').text(count);
        });
    }

    $(document).ready(function () {
        var csrf = $('meta[name="csrf-token"]').attr('content');

        // La paginación del parcial son enlaces normales: sin esto, pinchar
        // "2" navegaba al parcial pelado (sin layout). Se recarga por AJAX.
        $('#tags-container').on('click.ajaxpage', '[data-ajax-pagination] a', function (e) {
            e.preventDefault();
            $.get(this.href, function (html) {
                $('#tags-container').html(html);
            });
        });

        // Open modal for new tag
        $(document).on('click', '#btn-new-tag, #btn-new-tag-empty', function () {
            $('#tag_id').val('');
            $('#tagForm')[0].reset();
            $('#tag_color').val('#90bb13').trigger('input');
            $('#tagModalLabel').text('Nueva etiqueta');
            $('#tagModal').modal('show');
        });

        // Open modal to edit tag (data embedded in row)
        $(document).on('click', '.tag-edit-btn', function (e) {
            e.preventDefault();
            var d = $(this).data();
            $('#tag_id').val(d.id);
            $('#tag_name').val(d.name);
            $('#tag_description').val(d.description);
            $('#tag_color').val(d.color).trigger('input');
            $('#tag_icon').val(d.icon);
            $('#tag_priority').val(d.priority);
            $('#tag_system_prompt_addition').val(d.systemPrompt);
            $('#tag_is_active').val(d.isActive ? '1' : '0');
            $('#tagModalLabel').text('Editar etiqueta');
            $('#tagModal').modal('show');
        });

        // Toggle active
        $(document).on('click', '.tag-toggle-btn', function (e) {
            e.preventDefault();
            var id = $(this).data('id');
            var active = $(this).data('active') == '1' ? 0 : 1;

            $.ajax({
                url: urlFor(config.toggleUrlTemplate, id),
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf },
                data: { is_active: active },
            }).done(function () {
                toastr.success(active ? 'Etiqueta activada' : 'Etiqueta desactivada', 'Éxito');
                reloadTagsTab();
            }).fail(function () {
                toastr.error('Error al actualizar la etiqueta', 'Error');
            });
        });

        // Delete
        $(document).on('click', '.tag-delete-btn', function (e) {
            e.preventDefault();
            var id = $(this).data('id');
            var name = $(this).data('name');

            $('#delete-modal .modal-title').text('Eliminar etiqueta: ' + name);
            $('#delete-form').attr('action', '#').off('submit').on('submit', function (ev) {
                ev.preventDefault();
                $.ajax({
                    url: urlFor(config.destroyUrlTemplate, id),
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': csrf },
                }).done(function () {
                    $('#delete-modal').modal('hide');
                    toastr.success('Etiqueta eliminada correctamente', 'Éxito');
                    reloadTagsTab();
                }).fail(function () {
                    toastr.error('Error al eliminar la etiqueta', 'Error');
                });
            });
            $('#delete-modal').modal('show');
        });

        // Form submit (create / update)
        $('#tagForm').on('submit', function (e) {
            e.preventDefault();
            var id = $('#tag_id').val();
            var url = id ? urlFor(config.updateUrlTemplate, id) : config.storeUrl;

            $('.is-invalid').removeClass('is-invalid');

            $.ajax({
                url: url,
                method: id ? 'PUT' : 'POST',
                headers: { 'X-CSRF-TOKEN': csrf },
                data: {
                    name: $('#tag_name').val(),
                    description: $('#tag_description').val(),
                    color: $('#tag_color').val(),
                    icon: $('#tag_icon').val(),
                    priority: $('#tag_priority').val(),
                    system_prompt_addition: $('#tag_system_prompt_addition').val(),
                    is_active: $('#tag_is_active').val(),
                },
            }).done(function (res) {
                $('#tagModal').modal('hide');
                toastr.success(res.message || 'Etiqueta guardada correctamente', 'Éxito');
                reloadTagsTab();
            }).fail(function (xhr) {
                if (xhr.status === 422) {
                    $.each(xhr.responseJSON.errors, function (field, messages) {
                        $('#tag_' + field).addClass('is-invalid')
                            .siblings('.invalid-feedback').text(messages[0]);
                    });
                } else {
                    toastr.error((xhr.responseJSON && xhr.responseJSON.message) || 'Error al guardar la etiqueta', 'Error');
                }
            });
        });
    });
})(jQuery);
