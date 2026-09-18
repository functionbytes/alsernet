/**
 * Pantalla settings/macros/index.blade.php.
 * window.HdMacrosConfig = { flashSuccess, flashError, bulkUrl } lo imprime el Blade.
 */
$(document).ready(function () {
    var cfg = window.HdMacrosConfig || {};

    window.HdSettingsCommon.initDeleteModal();
    window.HdSettingsCommon.flashSession(cfg);

    window.HdSettingsCommon.initFilterModal({
        modalId: 'macros-filter-modal',
        applyBtnId: 'macros-filter-apply-btn',
        clearBtnId: 'macros-filter-clear-btn',
        formId: 'macros-filter-form',
        fields: [
            { modal: '#modal-visibility', hidden: '#filter-visibility' },
            { modal: '#modal-status', hidden: '#filter-status' },
        ],
    });

    // confirmDelete usa window.__confirm (dialogo propio del tema, definido
    // globalmente en layouts/theme.blade.php) en vez del confirm() nativo
    // que tenia esta pantalla antes de extraerse.
    window.HdSettingsCommon.initBulkActions({
        bulkUrl: cfg.bulkUrl,
        itemLabelSingular: 'macro',
        itemLabelPlural: 'macro(s)',
    });
});
