/**
 * Pantalla settings/drip-campaigns/index.blade.php.
 * window.HdDripCampaignsIndexConfig = { flashSuccess, flashError } lo
 * imprime el Blade.
 *
 * Requiere settings-common-batch3.js (window.HdSettingsCommon) cargado antes.
 */
$(document).ready(function () {
    var cfg = window.HdDripCampaignsIndexConfig || {};

    window.HdSettingsCommon.initFormSelect2('.form-select');
    window.HdSettingsCommon.initDeleteModal();
    window.HdSettingsCommon.flashSession(cfg);

    $(document).on('click', '.btn-toggle', function () {
        var url = $(this).data('url');
        $('#toggleForm').attr('action', url).submit();
    });
});
