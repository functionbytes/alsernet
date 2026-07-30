/*!
 * Helpdesk · modal "{name}" del inbox.
 *
 * Extraido de resources/views/helpdesk/inbox/partials/modals/{name}.blade.php,
 * donde vivia inline y se re-descargaba en cada carga del inbox. Sin
 * interpolacion Blade: la config llega por atributos data-* del markup.
 *
 * Convencion del modulo core: su JS se sirve desde public/vendor/helpdesk/ y no
 * tiene copia fuente aparte (igual que conversations.js y kb-suggestions.js).
 */
(function () {
    var csrf = $('meta[name="csrf-token"]').attr('content');

    // El modal es único en el DOM y se rellena con los datos del cliente de
    // la conversación actualmente abierta (fuente: .bv-right, que sí se
    // re-renderiza en cada cambio de conversación vía SPA). Sin esto, el
    // formulario quedaba "congelado" con el primer cliente cargado en la
    // página y el guardado actualizaba un cliente distinto al visible.
    function populateForm() {
        var c = window.HDCommerce.customer();
        $('#ec-name').val(c.name || '');
        $('#ec-email').val(c.email || '');
        $('#ec-phone').val(c.phone || '');
        $('#ec-language').val(c.language || '');
        $('#ec-timezone').val(c.timezone || '');
        $('#ec-notes').val(c.notes || '');
        $('#ec-errors').hide().text('');
    }

    // Abrir vía evento bv:modal:open (lo dispara HDCommerce.open)
    $(document).on('bv:modal:open', function (e, name) {
        if (name === 'edit-contact') { populateForm(); }
    });

    // Trigger declarativo data-bv-modal="edit-contact" no pasa por HDCommerce.open,
    // así que observamos la apertura de la clase "on" para repoblar el formulario.
    var ecNode = document.querySelector('[data-bv-modal-name="edit-contact"]');
    if (ecNode) {
        (new MutationObserver(function (mutations) {
            mutations.forEach(function (m) {
                if (m.attributeName !== 'class') { return; }
                if ($(m.target).hasClass('on')) { populateForm(); }
            });
        })).observe(ecNode, { attributes: true });
    }

    $(document).on('click', '#ec-save-btn', function () {
        var $modal = $('[data-bv-modal-name="edit-contact"]');
        var updateUrl = window.HDCommerce.updateUrl();

        if (!updateUrl) {
            if (window.toastr) toastr.warning('No hay contacto activo');
            return;
        }

        var name  = $('#ec-name').val().trim();
        var email = $('#ec-email').val().trim();

        if (!name) {
            $('#ec-errors').text('El nombre es obligatorio.').show();
            $('#ec-name').focus();
            return;
        }

        if (!email) {
            $('#ec-errors').text('El correo electrónico es obligatorio.').show();
            $('#ec-email').focus();
            return;
        }

        $('#ec-errors').hide().text('');
        var $btn = $(this).prop('disabled', true);

        $.ajax({
            url: updateUrl,
            method: 'PUT',
            dataType: 'json',
            data: {
                name:           name,
                email:          email,
                phone:          $('#ec-phone').val().trim() || null,
                language:       $('#ec-language').val() || null,
                timezone:       $('#ec-timezone').val() || null,
                internal_notes: $('#ec-notes').val().trim() || null,
            },
            headers: {
                'X-CSRF-TOKEN': csrf,
                'Accept':       'application/json',
            },
        })
        .done(function (resp) {
            if (window.toastr) toastr.success(resp.message || 'Contacto actualizado');

            // Close modal
            $modal.removeClass('on');
            if (!$('.bv-modal.on').length) $('body').css('overflow', '');

            // Recargar el pane (thread + panel derecho) para que toda la UI
            // -incluido este mismo modal la próxima vez que se abra- refleje
            // los datos recién guardados en el cliente.
            var convId = window.HDCommerce.conversationId();
            if (convId && typeof window.bvLoadConversationPane === 'function') {
                window.bvLoadConversationPane(convId, null, { push: false });
            }
        })
        .fail(function (xhr) {
            var errors = xhr?.responseJSON?.errors || {};
            var msgs   = Object.values(errors).flat();
            var msg    = msgs.length
                ? msgs[0]
                : (xhr?.responseJSON?.message || 'No se pudo actualizar el contacto');

            $('#ec-errors').text(msg).show();
        })
        .always(function () {
            $btn.prop('disabled', false);
        });
    });

    // Clear error state when user types
    $(document).on('input', '#ec-name, #ec-email', function () {
        $('#ec-errors').hide().text('');
    });
}());
