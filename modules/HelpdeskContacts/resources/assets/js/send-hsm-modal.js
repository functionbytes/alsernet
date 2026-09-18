/**
 * Modal "Enviar plantilla WhatsApp" — compartido entre el envío individual
 * (dropdown del listado / botón de la ficha 360) y el envío masivo (barra de
 * selección del listado). Misma lógica de armado de variables/preview que
 * public/vendor/helpdesk/conversations.js (picker HSM del composer del
 * inbox), adaptada a un <select> simple en vez del panel de búsqueda.
 */
(function () {
    'use strict';

    var templates = [];
    var templatesLoaded = false;

    function escapeHtml(str) {
        return $('<div>').text(str == null ? '' : String(str)).html();
    }

    function csrfToken() {
        return $('meta[name="csrf-token"]').attr('content');
    }

    function findTemplate(id) {
        return templates.find(function (t) { return String(t.id) === String(id); });
    }

    function renderVarsForm(t) {
        var $vars = $('#send-hsm-vars');
        $vars.empty();

        if (!t || !t.param_count) {
            return;
        }

        for (var i = 1; i <= t.param_count; i++) {
            $vars.append(
                $('<div class="mb-2"></div>').append(
                    $('<label class="form-label small mb-1"></label>').text('Variable {{' + i + '}}'),
                    $('<input type="text" class="form-control form-control-sm send-hsm-var-input">')
                        .attr('data-var-idx', i)
                        .attr('placeholder', 'Variable ' + i)
                )
            );
        }
    }

    function renderPreview() {
        var id = $('#send-hsm-template').val();
        var t = findTemplate(id);
        var $preview = $('#send-hsm-preview');

        if (!t) {
            $preview.text('Elige una plantilla para ver la vista previa.');
            return;
        }

        var body = t.body || '';
        $('.send-hsm-var-input').each(function () {
            var idx = $(this).data('var-idx');
            var val = ($(this).val() || '').trim();
            body = body.split('{{' + idx + '}}').join(val || '{{' + idx + '}}');
        });

        $preview.html(escapeHtml(body).replace(/\n/g, '<br>'));
    }

    function selectTemplate(id) {
        var t = findTemplate(id);
        renderVarsForm(t);
        renderPreview();
    }

    function loadTemplates() {
        if (templatesLoaded) {
            return;
        }

        var url = $('#send-hsm-modal').data('templates-url');
        var $select = $('#send-hsm-template');
        $select.html('<option value="">Cargando plantillas...</option>');

        $.get(url).done(function (resp) {
            templates = (resp && resp.templates) || [];
            templatesLoaded = true;

            if (!templates.length) {
                $select.html('<option value="">No hay plantillas aprobadas</option>');
                return;
            }

            $select.empty();
            templates.forEach(function (t) {
                $select.append($('<option></option>').val(t.id).text(t.name));
            });

            selectTemplate(templates[0].id);
        }).fail(function () {
            $select.html('<option value="">Error al cargar plantillas</option>');
            toastr.error('No se pudieron cargar las plantillas de WhatsApp', 'Error');
        });
    }

    function resetModal() {
        $('#send-hsm-vars').empty();
        $('#send-hsm-preview').text('Elige una plantilla para ver la vista previa.');
    }

    // Trigger individual: dropdown del listado o botón de la ficha 360.
    $(document).on('click', '.send-hsm-trigger', function (e) {
        e.preventDefault();

        var $btn = $(this);
        if ($btn.hasClass('disabled')) {
            return;
        }

        var $modal = $('#send-hsm-modal');
        $modal.data('mode', 'single');
        $modal.data('customer-id', $btn.data('customer-id'));
        $modal.removeData('customer-ids');

        var name = $btn.data('customer-name') || 'este contacto';
        $('#send-hsm-target-label').text('Se enviará a ' + name + '.');

        resetModal();
        $modal.modal('show');
    });

    // Trigger masivo: botón de la barra de selección del listado.
    $(document).on('click', '[data-bulk-action="send-hsm"]', function (e) {
        e.preventDefault();

        var ids = $('.contact-check:checked').map(function () {
            return $(this).val();
        }).get();

        if (!ids.length) {
            return;
        }

        var $modal = $('#send-hsm-modal');
        $modal.data('mode', 'bulk');
        $modal.data('customer-ids', ids);
        $modal.removeData('customer-id');

        $('#send-hsm-target-label').text('Se enviará a ' + ids.length + ' contacto(s) seleccionado(s).');

        resetModal();
        $modal.modal('show');
    });

    $(document).on('shown.bs.modal', '#send-hsm-modal', loadTemplates);

    $(document).on('change', '#send-hsm-template', function () {
        selectTemplate($(this).val());
    });

    $(document).on('input', '.send-hsm-var-input', renderPreview);

    $(document).on('click', '#send-hsm-submit', function () {
        var $modal = $('#send-hsm-modal');
        var t = findTemplate($('#send-hsm-template').val());

        if (!t) {
            toastr.warning('Elige una plantilla', 'Aviso');
            return;
        }

        var variables = [];
        var $firstInvalid = null;
        $('.send-hsm-var-input').each(function () {
            var val = ($(this).val() || '').trim();
            $(this).toggleClass('is-invalid', !val);
            if (!val && !$firstInvalid) {
                $firstInvalid = $(this);
            }
            variables.push(val);
        });

        if ($firstInvalid) {
            toastr.warning('Completa todas las variables de la plantilla', 'Aviso');
            $firstInvalid.trigger('focus');
            return;
        }

        var mode = $modal.data('mode');
        var url;
        var payload = {
            // external_id es el nombre técnico registrado en Meta — mandar el
            // nombre visible (t.name) rompe el envío real con el error 132001.
            template_name: t.external_id,
            variables: variables,
        };

        if (mode === 'bulk') {
            url = $modal.data('bulk-url');
            payload.customer_ids = $modal.data('customer-ids');
        } else {
            url = $modal.data('single-url-base') + '/' + $modal.data('customer-id') + '/send-hsm';
        }

        var $submit = $('#send-hsm-submit').prop('disabled', true);

        $.ajax({
            url: url,
            method: 'POST',
            dataType: 'json',
            data: payload,
            headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
        }).done(function (resp) {
            toastr.success((resp && resp.message) || 'Plantilla enviada', 'Listo');
            $modal.modal('hide');
        }).fail(function (xhr) {
            var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'No se pudo enviar la plantilla';
            toastr.error(msg, 'Error');
        }).always(function () {
            $submit.prop('disabled', false);
        });
    });
})();
