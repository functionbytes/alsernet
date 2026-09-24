/**
 * Modal "Enviar plantilla WhatsApp" — compartido entre el envío individual
 * (dropdown del listado / botón de la ficha 360) y el envío masivo (barra de
 * selección del listado). Misma lógica de armado de variables/preview que
 * public/vendor/helpdesk/conversations-thread.js (picker HSM del composer del
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
                $('<div class="col-6"></div>').append(
                    $('<label class="ct-flabel"></label>').text('Variable ' + i),
                    $('<input type="text" class="ct-finput send-hsm-var-input">')
                        .attr('data-var-idx', i)
                        .attr('placeholder', '{{' + i + '}}')
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

        // Los valores de las variables salen en negrita, como en el mockup.
        var html = escapeHtml(t.body || '');
        $('.send-hsm-var-input').each(function () {
            var idx = $(this).data('var-idx');
            var val = ($(this).val() || '').trim();
            html = html.split('{{' + idx + '}}').join('<b>' + (val ? escapeHtml(val) : '{{' + idx + '}}') + '</b>');
        });

        $preview.html(html.replace(/\n/g, '<br>'));
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
                $select.append($('<option></option>').val(t.id).text(t.name + (t.language ? ' · ' + t.language : '')));
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
        $modal.removeData('valid-ids');

        var phone = $btn.data('customer-phone');
        $('#send-hsm-eyebrow').text('Contacto · WhatsApp');
        $('#sendHsmModalLabel').text('Enviar plantilla');
        $('#send-hsm-recipient').toggleClass('d-none', !phone);
        $('#send-hsm-recipient-val').text(phone || '');
        $('#send-hsm-note-single').removeClass('d-none');
        $('#send-hsm-note-bulk').addClass('d-none');
        $('#send-hsm-submit-label').text('Enviar plantilla');
        $('#send-hsm-bulk-info, #send-hsm-bulk-conv-check').addClass('d-none');

        resetModal();
        $modal.modal('show');
    });

    // Trigger masivo: botón de la barra de selección del listado. Calcula
    // aquí (no hay endpoint para esto) qué seleccionados tienen WhatsApp
    // válido y cuáles se omitirían — una fila del listado solo pinta el
    // botón .send-hsm-trigger cuando el contacto tiene whatsapp_phone, así
    // que su presencia/ausencia dentro de la fila es la señal real.
    $(document).on('click', '[data-bulk-action="send-hsm"]', function (e) {
        e.preventDefault();

        var $checked = $('.contact-check:checked');
        var total = $checked.length;
        if (!total) {
            return;
        }

        var validIds = [];
        var noPhoneCount = 0;
        var blockedCount = 0;

        $checked.each(function () {
            var $row = $(this).closest('.ctl-row');
            var id = $(this).val();
            var isBanned = $row.hasClass('is-banned');
            var hasPhone = $row.find('.send-hsm-trigger').length > 0;

            if (isBanned) {
                blockedCount++;
                return;
            }
            if (!hasPhone) {
                noPhoneCount++;
                return;
            }
            validIds.push(id);
        });

        openBulk(validIds, total, noPhoneCount, blockedCount);
    });

    // Apertura en modo masivo, también usada por el modal de Informes
    // ("Enviar plantilla a los en riesgo").
    function openBulk(validIds, total, noPhoneCount, blockedCount) {
        var $modal = $('#send-hsm-modal');
        $modal.data('mode', 'bulk');
        $modal.data('customer-ids', validIds);
        $modal.data('valid-ids', validIds);
        $modal.removeData('customer-id');

        $('#send-hsm-eyebrow').text('Contactos · Envío masivo');
        $('#sendHsmModalLabel').text('Plantilla a ' + total + (total === 1 ? ' contacto' : ' contactos'));
        $('#send-hsm-recipient').addClass('d-none');
        $('#send-hsm-valid-count').text(validIds.length + ' de ' + total);

        var omittedParts = [];
        if (noPhoneCount) { omittedParts.push(noPhoneCount + ' sin teléfono'); }
        if (blockedCount) { omittedParts.push(blockedCount + (blockedCount === 1 ? ' bloqueado' : ' bloqueados')); }
        $('#send-hsm-omitted-box').toggleClass('d-none', !omittedParts.length);
        $('#send-hsm-omitted-val').text(omittedParts.join(' · '));

        $('#send-hsm-bulk-info, #send-hsm-bulk-conv-check, #send-hsm-note-bulk').removeClass('d-none');
        $('#send-hsm-note-single').addClass('d-none');
        $('#send-hsm-submit-label').text('Encolar envío a ' + validIds.length + (validIds.length === 1 ? ' contacto' : ' contactos'));

        resetModal();
        $modal.modal('show');
    }

    window.ContactsSendHsm = { openBulk: openBulk };

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
            var validIds = $modal.data('customer-ids') || [];
            if (!validIds.length) {
                toastr.warning('Ningún seleccionado tiene WhatsApp válido para recibir la plantilla', 'Aviso');
                return;
            }
            url = $modal.data('bulk-url');
            payload.customer_ids = validIds;
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
