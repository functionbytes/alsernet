{{-- Modal: Editar información de contacto --}}
@php
    $ecCust = $selectedConversation?->customer;
@endphp
<div class="bv-modal" data-bv-modal-name="edit-contact"
     data-customer-id="{{ $ecCust?->id ?? '' }}"
     data-update-url="{{ $ecCust ? route('manager.helpdesk.customers.update', $ecCust) : '' }}">
    <div class="bv-modal-dialog">
        <div class="bv-modal-head">
            <div class="bv-modal-title"><i class="fad fa-user-pen bv-modal-title-icon"></i> Editar contacto</div>
            <button class="bv-modal-close" data-bv-close>
                <i class="fas fa-xmark"></i>
            </button>
        </div>
        <div class="bv-modal-body">
            <div class="bv-contact-form">
                <div>
                    <label class="bv-field-label">Nombre <span class="bv-required-star">*</span></label>
                    <input type="text" id="ec-name" class="bv-field-input"
                           value="{{ $ecCust?->name ?? '' }}"
                           placeholder="Nombre completo">
                </div>
                <div class="bv-contact-grid-2">
                    <div>
                        <label class="bv-field-label">Email <span class="bv-required-star">*</span></label>
                        <input type="email" id="ec-email" class="bv-field-input"
                               value="{{ $ecCust?->email ?? '' }}"
                               placeholder="correo@ejemplo.com">
                    </div>
                    <div>
                        <label class="bv-field-label">Teléfono</label>
                        <input type="tel" id="ec-phone" class="bv-field-input"
                               value="{{ $ecCust?->phone ?? '' }}"
                               placeholder="+34 600 000 000">
                    </div>
                </div>
                <div class="bv-contact-grid-2">
                    <div>
                        <label class="bv-field-label">Idioma</label>
                        <select id="ec-language" class="bv-field-input">
                            <option value="">Sin especificar</option>
                            <option value="es" {{ ($ecCust?->language ?? '') === 'es' ? 'selected' : '' }}>Español</option>
                            <option value="en" {{ ($ecCust?->language ?? '') === 'en' ? 'selected' : '' }}>English</option>
                            <option value="fr" {{ ($ecCust?->language ?? '') === 'fr' ? 'selected' : '' }}>Français</option>
                            <option value="de" {{ ($ecCust?->language ?? '') === 'de' ? 'selected' : '' }}>Deutsch</option>
                            <option value="pt" {{ ($ecCust?->language ?? '') === 'pt' ? 'selected' : '' }}>Português</option>
                            <option value="it" {{ ($ecCust?->language ?? '') === 'it' ? 'selected' : '' }}>Italiano</option>
                        </select>
                    </div>
                    <div>
                        <label class="bv-field-label">Zona horaria</label>
                        <select id="ec-timezone" class="bv-field-input">
                            <option value="">Sin especificar</option>
                            <option value="Europe/Madrid" {{ ($ecCust?->timezone ?? '') === 'Europe/Madrid' ? 'selected' : '' }}>Europe/Madrid</option>
                            <option value="America/Mexico_City" {{ ($ecCust?->timezone ?? '') === 'America/Mexico_City' ? 'selected' : '' }}>America/Mexico_City</option>
                            <option value="America/Bogota" {{ ($ecCust?->timezone ?? '') === 'America/Bogota' ? 'selected' : '' }}>America/Bogota</option>
                            <option value="America/Lima" {{ ($ecCust?->timezone ?? '') === 'America/Lima' ? 'selected' : '' }}>America/Lima</option>
                            <option value="America/New_York" {{ ($ecCust?->timezone ?? '') === 'America/New_York' ? 'selected' : '' }}>America/New_York</option>
                            <option value="UTC" {{ ($ecCust?->timezone ?? '') === 'UTC' ? 'selected' : '' }}>UTC</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="bv-field-label">Notas internas</label>
                    <textarea id="ec-notes" rows="3"
                              class="bv-field-input bv-textarea-resizable"
                              placeholder="Notas privadas sobre este contacto…">{{ $ecCust?->internal_notes ?? '' }}</textarea>
                </div>
                <div id="ec-errors" class="bv-step-hidden bv-form-error"></div>
            </div>
        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="ec-save-btn">Guardar cambios</button>
            <button class="btn-secondary" data-bv-close>Cancelar</button>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
(function () {
    var csrf = $('meta[name="csrf-token"]').attr('content');

    $(document).on('click', '#ec-save-btn', function () {
        var $modal = $('[data-bv-modal-name="edit-contact"]');
        var updateUrl = $modal.data('update-url');

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

            // Update thread header name in real time
            if (resp.customer && resp.customer.name) {
                $('.bv-th-head .nm').first().text(resp.customer.name);
                // Update right panel name
                $('.bv-right .rp3-name').first().text(resp.customer.name);
            }

            // Close modal
            $modal.removeClass('on');
            if (!$('.bv-modal.on').length) $('body').css('overflow', '');
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
