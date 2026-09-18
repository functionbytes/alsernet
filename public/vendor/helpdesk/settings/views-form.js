/**
 * Formulario de vista guardada (alta/edicion) —
 * settings/views/create.blade.php y settings/views/edit.blade.php
 */
$(document).ready(function () {
    HDSettingsCommon.initFormSelect2();

    $('#viewForm').on('submit', function () {
        $('.filters-hidden').remove();
        try {
            const obj = JSON.parse($('#filtersJson').val() || '{}');
            const form = this;
            Object.entries(obj).forEach(function ([k, v]) {
                $(form).append($('<input>', {
                    type: 'hidden',
                    class: 'filters-hidden',
                    name: 'filters[' + k + ']',
                    value: v,
                }));
            });
        } catch (e) {
            // JSON invalido — no se agregan filtros
        }
    });
});
