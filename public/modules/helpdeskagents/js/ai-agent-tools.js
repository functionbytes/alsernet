/**
 * ai-agent-tools.js — HelpdeskAgents module
 *
 * Herramientas del agente IA (pestaña "Herramientas" en Ajustes del agente).
 *
 * El contenido de #tools-container se sustituye por AJAX cada vez que se abre
 * la pestaña o se crea/edita/borra una herramienta (ver reloadToolsTab). Por
 * eso todos los manejadores están delegados a #tools-container o a document,
 * que nunca se destruyen — así siguen funcionando tras cada recarga sin
 * volver a cargar este script.
 *
 * Depende de: jQuery, toastr (globales) y window.HelpdeskAgentsAiSettings.tools
 * (URLs de rutas, inyectadas por managers/ai-agent/settings.blade.php).
 */
(function ($) {
    'use strict';

    var config = (window.HelpdeskAgentsAiSettings && window.HelpdeskAgentsAiSettings.tools) || {};

    function urlFor(template, id) {
        return template.replace('__ID__', id);
    }

    function reloadToolsTab() {
        $.get(config.indexUrl, function (html) {
            $('#tools-container').html(html);
            var count = $('#tools-container [data-count-item]').length;
            $('#tools-count').text(count);
        });
    }

    $(document).ready(function () {
        var csrf = $('meta[name="csrf-token"]').attr('content');

        // La paginación del parcial son enlaces normales: sin esto, pinchar
        // "2" navegaba al parcial pelado (sin layout). Se recarga por AJAX.
        $('#tools-container').on('click.ajaxpage', '[data-ajax-pagination] a', function (e) {
            e.preventDefault();
            $.get(this.href, function (html) {
                $('#tools-container').html(html);
            });
        });

        // New tool
        $(document).on('click', '#btn-new-tool, #btn-new-tool-empty', function () {
            $('#tool_id').val('');
            $('#toolForm')[0].reset();
            $('#toolModalLabel').text('Nueva herramienta');
            $('#toolModal').modal('show');
        });

        // Edit tool (load via AJAX)
        $(document).on('click', '.tool-edit-btn', function (e) {
            e.preventDefault();
            var id = $(this).data('id');

            $.get(config.indexUrl + '/' + id, function (data) {
                $('#tool_id').val(data.id);
                $('#tool_name').val(data.name);
                $('#tool_description').val(data.description);
                $('#tool_type').val(data.type);
                $('#tool_parameters').val(data.parameters ? JSON.stringify(data.parameters, null, 2) : '');
                $('#tool_implementation').val(data.implementation);
                $('#tool_auth_config').val(data.auth_config ? JSON.stringify(data.auth_config, null, 2) : '');
                $('#tool_requires_approval').val(data.requires_approval ? '1' : '0');
                $('#tool_is_active').val(data.is_active ? '1' : '0');
                $('#toolModalLabel').text('Editar herramienta');
                $('#toolModal').modal('show');
            }).fail(function () {
                toastr.error('Error al cargar la herramienta', 'Error');
            });
        });

        // Toggle active
        $(document).on('click', '.tool-toggle-btn', function (e) {
            e.preventDefault();
            var id = $(this).data('id');
            var active = $(this).data('active') == '1' ? 0 : 1;

            $.ajax({
                url: urlFor(config.toggleUrlTemplate, id),
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf },
                data: { is_active: active },
            }).done(function () {
                toastr.success(active ? 'Herramienta activada' : 'Herramienta desactivada', 'Éxito');
                reloadToolsTab();
            }).fail(function () {
                toastr.error('Error al actualizar la herramienta', 'Error');
            });
        });

        // Delete
        $(document).on('click', '.tool-delete-btn', function (e) {
            e.preventDefault();
            var id = $(this).data('id');
            var name = $(this).data('name');

            $('#delete-modal .modal-title').text('Eliminar herramienta: ' + name);
            $('#delete-form').attr('action', '#').off('submit').on('submit', function (ev) {
                ev.preventDefault();
                // #delete-form/#delete-modal son el modal genérico de
                // layouts.theme, que también escucha su submit por
                // delegación en document (línea "Delete confirmation modal").
                // Sin stopPropagation() ese manejador genérico TAMBIÉN
                // disparaba tras este, mandando un POST con action="#" que
                // acababa en 405 y un segundo toast de error fantasma.
                ev.stopPropagation();
                $.ajax({
                    url: urlFor(config.destroyUrlTemplate, id),
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': csrf },
                }).done(function () {
                    $('#delete-modal').modal('hide');
                    toastr.success('Herramienta eliminada correctamente', 'Éxito');
                    reloadToolsTab();
                }).fail(function () {
                    toastr.error('Error al eliminar la herramienta', 'Error');
                });
            });
            $('#delete-modal').modal('show');
        });

        // Form submit
        $('#toolForm').on('submit', function (e) {
            e.preventDefault();
            var id = $('#tool_id').val();
            var url = id ? urlFor(config.updateUrlTemplate, id) : config.storeUrl;

            var parameters = null;
            var authConfig = null;

            try {
                var pVal = $('#tool_parameters').val().trim();
                var aVal = $('#tool_auth_config').val().trim();
                parameters = pVal ? JSON.parse(pVal) : null;
                authConfig = aVal ? JSON.parse(aVal) : null;
            } catch (err) {
                toastr.error('Formato JSON invalido: ' + err.message, 'Error');
                return;
            }

            $('.is-invalid').removeClass('is-invalid');

            $.ajax({
                url: url,
                method: id ? 'PUT' : 'POST',
                headers: { 'X-CSRF-TOKEN': csrf },
                data: {
                    name: $('#tool_name').val(),
                    description: $('#tool_description').val(),
                    type: $('#tool_type').val(),
                    parameters: parameters,
                    implementation: $('#tool_implementation').val(),
                    auth_config: authConfig,
                    requires_approval: $('#tool_requires_approval').val(),
                    is_active: $('#tool_is_active').val(),
                },
            }).done(function (res) {
                $('#toolModal').modal('hide');
                toastr.success(res.message || 'Herramienta guardada correctamente', 'Éxito');
                reloadToolsTab();
            }).fail(function (xhr) {
                if (xhr.status === 422) {
                    $.each(xhr.responseJSON.errors, function (field, messages) {
                        $('#tool_' + field).addClass('is-invalid')
                            .siblings('.invalid-feedback').text(messages[0]);
                    });
                } else {
                    toastr.error((xhr.responseJSON && xhr.responseJSON.message) || 'Error al guardar la herramienta', 'Error');
                }
            });
        });
    });
})(jQuery);
