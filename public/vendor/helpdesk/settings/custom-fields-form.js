/**
 * Partial settings/custom-fields/_form.blade.php.
 * window.HdCustomFieldFormConfig = { isEditing } lo imprime el Blade.
 *
 * Requiere settings-common-batch3.js (window.HdSettingsCommon) cargado antes.
 */
$(document).ready(function () {
    window.HdSettingsCommon.initFormSelect2('.form-select');

    var cfg = window.HdCustomFieldFormConfig || {};
    var selectTypes = ['select', 'multi-select'];

    function toggleOptions() {
        var type = $('#field_type').val();
        $('#options_section').toggleClass('d-none', selectTypes.indexOf(type) === -1);
    }

    function slugify(text) {
        return text
            .toLowerCase()
            .replace(/[^a-z0-9\s_]/g, '')
            .trim()
            .replace(/[\s-]+/g, '_');
    }

    $('#field_type').on('change', toggleOptions);
    toggleOptions();

    $('#field_label').on('input', function () {
        if (! cfg.isEditing) {
            $('#key_preview').text(slugify($(this).val()) || '');
        }
    });
});
