/**
 * Helpdesk · Settings → Atributos personalizados (crear / editar).
 * Compartido por attributes/create.blade.php y attributes/edit.blade.php,
 * que son practicamente identicos salvo por: el modo (crea la clave
 * automaticamente a partir del nombre solo en creacion), el numero inicial
 * de opciones ya guardadas y el mensaje de flash. Esas diferencias llegan
 * via window.HdAttributesFormConfig.
 *
 * Select2 (incl. el allowClear que solo aplica en creacion) y el flash de
 * sesion los cubre settings-common.js.
 */
(function ($) {
    'use strict';

    $(function () {
        const config = window.HdAttributesFormConfig || {};
        let optionCounter = config.optionCount || 1;

        $('#formatSelect').on('change', function () {
            const format = $(this).val();

            $('#optionsContainer').addClass('d-none');
            $('#numberRangeContainer').addClass('d-none');

            if (format === 'select' || format === 'checkboxGroup') {
                $('#optionsContainer').removeClass('d-none');
            } else if (format === 'number') {
                $('#numberRangeContainer').removeClass('d-none');
            }
        }).trigger('change');

        $('#addOption').on('click', function () {
            optionCounter++;

            const optionHtml = `
                <div class="option-item mb-2">
                    <div class="input-group">
                        <input type="text" name="options[]" class="form-control" placeholder="Opción ${optionCounter}">
                        <button type="button" class="btn btn-outline-danger remove-option">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `;

            $('#optionsList').append(optionHtml);
        });

        $(document).on('click', '.remove-option', function () {
            if ($('.option-item').length > 1) {
                $(this).closest('.option-item').remove();
            } else {
                toastr.warning('Debe mantener al menos una opción', 'Advertencia');
            }
        });

        // Solo en creacion: autogenera la clave unica a partir del nombre
        // mientras el usuario no la haya escrito ya a mano.
        if (config.mode === 'create') {
            $('input[name="name"]').on('input', function () {
                if (!$('input[name="key"]').val()) {
                    const key = $(this).val()
                        .toLowerCase()
                        .replace(/[^a-z0-9]+/g, '_')
                        .replace(/^_+|_+$/g, '');

                    $('input[name="key"]').val(key);
                }
            });
        }
    });
})(jQuery);
