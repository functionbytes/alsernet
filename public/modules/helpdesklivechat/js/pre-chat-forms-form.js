/**
 * Alta/edicion de formulario pre-chat (settings/pre-chat-forms/form.blade.php).
 * Extraido del <script> inline de esa vista: agregar/quitar campos dinamicos
 * a partir de la plantilla #fieldTemplate.
 *
 * El numero de campos ya existentes (para no repetir __IDX__) llega por
 * data-field-count en #fieldsContainer — lo unico que este fichero no puede
 * resolver por su cuenta.
 */
(function () {
    'use strict';

    $(document).ready(function () {
        $('.form-select').select2({ width: '100%' });

        var $container = $('#fieldsContainer');
        var fieldCount = parseInt($container.data('field-count'), 10) || 0;

        $('#addField').on('click', function () {
            var template = document.getElementById('fieldTemplate').innerHTML;
            var html = template.replace(/__IDX__/g, fieldCount);
            var $el = $(html);
            $el.find('.field-number').text(fieldCount + 1);
            $container.append($el);
            $el.find('.form-select').select2({ width: '100%' });
            fieldCount++;
        });

        $(document).on('click', '.remove-field', function () {
            $(this).closest('.field-item').remove();
            $('#fieldsContainer .field-item').each(function (i) {
                $(this).find('.field-number').text(i + 1);
            });
        });
    });
})();
