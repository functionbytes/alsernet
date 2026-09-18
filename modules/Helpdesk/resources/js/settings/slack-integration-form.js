/**
 * Parcial settings/slack-integrations/_form.blade.php (crear/editar).
 */
$(document).ready(function () {
    window.HdSettingsCommon.initFormSelect2('.select2');

    $('#btn_change_webhook').on('click', function () {
        var input = $('#webhook_url');
        input.prop('disabled', false).attr('type', 'url').focus();
        $(this).hide();
    });
});
