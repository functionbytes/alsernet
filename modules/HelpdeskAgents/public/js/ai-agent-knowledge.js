/**
 * ai-agent-knowledge.js — HelpdeskAgents module
 *
 * Base de conocimiento del agente IA (pestaña "Base de conocimiento" en
 * Ajustes del agente).
 *
 * El contenido de #knowledge-container se sustituye por AJAX cada vez que se
 * abre la pestaña o se crea/edita/borra un documento (ver reloadKnowledgeTab).
 * Por eso todos los manejadores están delegados a #knowledge-container o a
 * document, que nunca se destruyen — así siguen funcionando tras cada
 * recarga sin volver a cargar este script.
 *
 * Depende de: jQuery, toastr (globales) y window.HelpdeskAgentsAiSettings.knowledge
 * (URLs de rutas, inyectadas por managers/ai-agent/settings.blade.php).
 */
(function ($) {
    'use strict';

    var config = (window.HelpdeskAgentsAiSettings && window.HelpdeskAgentsAiSettings.knowledge) || {};

    function urlFor(template, id) {
        return template.replace('__ID__', id);
    }

    function reloadKnowledgeTab() {
        $.get(config.indexUrl, function (html) {
            $('#knowledge-container').html(html);
            var count = $('#knowledge-container [data-count-item]').length;
            $('#knowledge-count').text(count);
        });
    }

    $(document).ready(function () {
        var csrf = $('meta[name="csrf-token"]').attr('content');

        // La paginación del parcial son enlaces normales: sin esto, pinchar
        // "2" navegaba al parcial pelado (sin layout). Se recarga por AJAX.
        $('#knowledge-container').on('click.ajaxpage', '[data-ajax-pagination] a', function (e) {
            e.preventDefault();
            $.get(this.href, function (html) {
                $('#knowledge-container').html(html);
            });
        });

        // New document
        $(document).on('click', '#btn-new-knowledge, #btn-new-knowledge-empty', function () {
            $('#knowledge_id').val('');
            $('#knowledgeForm')[0].reset();
            $('#knowledgeModalLabel').text('Nuevo documento');
            $('#knowledgeModal').modal('show');
        });

        // Edit document (load via AJAX)
        $(document).on('click', '.knowledge-edit-btn', function (e) {
            e.preventDefault();
            var id = $(this).data('id');

            $.get(config.indexUrl + '/' + id, function (data) {
                $('#knowledge_id').val(data.id);
                $('#knowledge_title').val(data.title);
                $('#knowledge_content').val(data.content);
                $('#knowledge_type').val(data.type);
                $('#knowledge_source_url').val(data.source_url);
                $('#knowledge_source_type').val(data.source_type);
                $('#knowledge_tags').val(data.tags ? data.tags.join(', ') : '');
                $('#knowledge_summary').val(data.summary);
                $('#knowledge_is_active').val(data.is_active ? '1' : '0');
                $('#knowledgeModalLabel').text('Editar documento');
                $('#knowledgeModal').modal('show');
            }).fail(function () {
                toastr.error('Error al cargar el documento', 'Error');
            });
        });

        // Toggle active
        $(document).on('click', '.knowledge-toggle-btn', function (e) {
            e.preventDefault();
            var id = $(this).data('id');
            var active = $(this).data('active') == '1' ? 0 : 1;

            $.ajax({
                url: urlFor(config.toggleUrlTemplate, id),
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf },
                data: { is_active: active },
            }).done(function () {
                toastr.success(active ? 'Documento activado' : 'Documento desactivado', 'Éxito');
                reloadKnowledgeTab();
            }).fail(function () {
                toastr.error('Error al actualizar el documento', 'Error');
            });
        });

        // Generate embedding
        $(document).on('click', '.knowledge-embedding-btn', function (e) {
            e.preventDefault();
            var id = $(this).data('id');

            $.ajax({
                url: urlFor(config.generateEmbeddingUrlTemplate, id),
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf },
            }).done(function () {
                toastr.success('Embedding generado correctamente', 'Éxito');
                reloadKnowledgeTab();
            }).fail(function () {
                toastr.error('Error al generar el embedding', 'Error');
            });
        });

        // Delete
        $(document).on('click', '.knowledge-delete-btn', function (e) {
            e.preventDefault();
            var id = $(this).data('id');
            var name = $(this).data('name');

            $('#delete-modal .modal-title').text('Eliminar documento: ' + name);
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
                    toastr.success('Documento eliminado correctamente', 'Éxito');
                    reloadKnowledgeTab();
                }).fail(function () {
                    toastr.error('Error al eliminar el documento', 'Error');
                });
            });
            $('#delete-modal').modal('show');
        });

        // Form submit
        $('#knowledgeForm').on('submit', function (e) {
            e.preventDefault();
            var id = $('#knowledge_id').val();
            var url = id ? urlFor(config.updateUrlTemplate, id) : config.storeUrl;

            var tagsRaw = $('#knowledge_tags').val().trim();
            var tags = tagsRaw ? tagsRaw.split(',').map(function (t) { return t.trim(); }).filter(Boolean) : [];

            $('.is-invalid').removeClass('is-invalid');

            $.ajax({
                url: url,
                method: id ? 'PUT' : 'POST',
                headers: { 'X-CSRF-TOKEN': csrf },
                data: {
                    title: $('#knowledge_title').val(),
                    content: $('#knowledge_content').val(),
                    type: $('#knowledge_type').val(),
                    source_url: $('#knowledge_source_url').val(),
                    source_type: $('#knowledge_source_type').val(),
                    tags: tags,
                    summary: $('#knowledge_summary').val(),
                    is_active: $('#knowledge_is_active').val(),
                },
            }).done(function (res) {
                $('#knowledgeModal').modal('hide');
                toastr.success(res.message || 'Documento guardado correctamente', 'Éxito');
                reloadKnowledgeTab();
            }).fail(function (xhr) {
                if (xhr.status === 422) {
                    $.each(xhr.responseJSON.errors, function (field, messages) {
                        $('#knowledge_' + field).addClass('is-invalid')
                            .siblings('.invalid-feedback').text(messages[0]);
                    });
                } else {
                    toastr.error((xhr.responseJSON && xhr.responseJSON.message) || 'Error al guardar el documento', 'Error');
                }
            });
        });
    });
})(jQuery);
