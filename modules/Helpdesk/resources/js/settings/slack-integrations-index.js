/**
 * Pantalla settings/slack-integrations/index.blade.php.
 * window.HdSlackIntegrationsConfig = { flashSuccess, flashError, bulkUrl } lo imprime el Blade.
 */
$(document).ready(function () {
    var cfg = window.HdSlackIntegrationsConfig || {};

    window.HdSettingsCommon.initDeleteModal();
    window.HdSettingsCommon.flashSession(cfg);

    $(document).on('click', '.btn-toggle', function () {
        var url = $(this).data('url');
        $('#toggleForm').attr('action', url).submit();
    });

    window.HdSettingsCommon.initFilterModal({
        modalId: 'slack-integrations-filter-modal',
        applyBtnId: 'slack-integrations-filter-apply-btn',
        clearBtnId: 'slack-integrations-filter-clear-btn',
        formId: 'slack-integrations-filter-form',
        fields: [
            { modal: '#modal-status', hidden: '#filter-status' },
        ],
    });

    window.HdSettingsCommon.initBulkActions({
        bulkUrl: cfg.bulkUrl,
        itemLabelSingular: 'integración',
        itemLabelPlural: 'integración(es)',
    });
});
