{{-- Modal: Editar información de contacto --}}
<div class="bv-modal" data-bv-modal-name="edit-contact">
    <div class="modal w-md">
        <div class="modal-head">
            <div class="modal-icon"><i class="fa-solid fa-user-pen"></i></div>
            <div class="modal-title-wrap">
                <div class="modal-label">{{ __('helpdesk::helpdesk.inbox.modals.edit_contact_label') }}</div>
                <div class="modal-title">{{ __('helpdesk::helpdesk.inbox.modals.edit_contact_title') }}</div>
            </div>
            <button class="modal-close" data-bv-close><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <div class="field">
                <div class="flabel">{{ __('helpdesk::helpdesk.inbox.modals.edit_contact_field_name') }} <span class="req">*</span></div>
                <input id="ec-name" class="finput" type="text" placeholder="{{ __('helpdesk::helpdesk.inbox.modals.edit_contact_placeholder_name') }}">
            </div>
            <div class="frow">
                <div class="field">
                    <div class="flabel">Email <span class="req">*</span></div>
                    <input id="ec-email" class="finput" type="email" placeholder="{{ __('helpdesk::helpdesk.inbox.modals.edit_contact_placeholder_email') }}">
                </div>
                <div class="field">
                    <div class="flabel">{{ __('helpdesk::helpdesk.inbox.modals.edit_contact_field_phone') }}</div>
                    <input id="ec-phone" class="finput" type="tel" placeholder="+34 600 000 000">
                </div>
            </div>
            <div class="frow">
                <div class="field">
                    <div class="flabel">{{ __('helpdesk::helpdesk.inbox.modals.edit_contact_field_language') }}</div>
                    <select id="ec-language" class="fselect">
                        <option value="">{{ __('helpdesk::helpdesk.inbox.modals.edit_contact_unspecified') }}</option>
                        <option value="es">Español</option>
                        <option value="en">English</option>
                        <option value="fr">Français</option>
                        <option value="de">Deutsch</option>
                        <option value="pt">Português</option>
                        <option value="it">Italiano</option>
                    </select>
                </div>
                <div class="field">
                    <div class="flabel">{{ __('helpdesk::helpdesk.inbox.modals.edit_contact_field_timezone') }}</div>
                    <select id="ec-timezone" class="fselect">
                        <option value="">{{ __('helpdesk::helpdesk.inbox.modals.edit_contact_unspecified') }}</option>
                        <option value="Europe/Madrid">Europe/Madrid</option>
                        <option value="America/Mexico_City">America/Mexico_City</option>
                        <option value="America/Bogota">America/Bogota</option>
                        <option value="America/Lima">America/Lima</option>
                        <option value="America/New_York">America/New_York</option>
                        <option value="UTC">UTC</option>
                    </select>
                </div>
            </div>
            <div class="field">
                <div class="flabel">{{ __('helpdesk::helpdesk.inbox.modals.edit_contact_field_notes') }}</div>
                <textarea id="ec-notes" class="finput" rows="3" placeholder="{{ __('helpdesk::helpdesk.inbox.modals.edit_contact_placeholder_notes') }}"></textarea>
            </div>
            <div id="ec-errors" class="bv-step-hidden field-error"></div>
        </div>
        <div class="modal-foot">
            <button class="btn btn-primary" id="ec-save-btn">{{ __('helpdesk::helpdesk.inbox.modals.edit_contact_btn_save') }}</button>
            <button class="btn btn-outline" data-bv-close>{{ __('helpdesk::helpdesk.inbox.modals.cancel') }}</button>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
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
</script>
@endpush
@endonce
