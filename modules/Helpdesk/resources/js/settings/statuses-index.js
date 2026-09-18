/**
 * Pantalla settings/statuses/index.blade.php.
 * window.HdStatusesConfig = { flashSuccess, flashError, bulkUrl } lo imprime el Blade.
 */
$(document).ready(function () {
    var cfg = window.HdStatusesConfig || {};

    window.HdSettingsCommon.flashSession(cfg);
    window.HdSettingsCommon.initDeleteModal();

    window.HdSettingsCommon.initFilterModal({
        modalId: 'statuses-filter-modal',
        applyBtnId: 'statuses-filter-apply-btn',
        clearBtnId: 'statuses-filter-clear-btn',
        formId: 'filterForm',
        fields: [
            { modal: '#modal-status', hidden: '#filter-status' },
        ],
    });

    window.HdSettingsCommon.initBulkActions({
        bulkUrl: cfg.bulkUrl,
        itemLabelSingular: 'estado',
        itemLabelPlural: 'estado(s)',
    });
});
