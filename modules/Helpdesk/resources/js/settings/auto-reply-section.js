/**
 * Partial settings/business/_auto-reply-section.blade.php — CRUD reutilizable
 * de "canal + idioma + mensaje" para bienvenida / despedida / fuera de
 * horario. window.HdAutoReplySectionConfig = { idPrefix, bulkUrl,
 * reopenModalId } lo imprime el Blade que incluye el partial (uno por
 * pagina).
 *
 * Requiere settings-common-batch3.js (window.HdSettingsCommon) y core/js/bulk.js
 * (window.BulkActions) cargados antes.
 */
$(function () {
    var cfg = window.HdAutoReplySectionConfig || {};
    var idPrefix = cfg.idPrefix;

    // width:'100%' evita el bug de select2 calculando 0px de ancho porque el
    // <select> vive dentro de un modal oculto (display:none) al momento del
    // init; dropdownParent evita que el desplegable quede detras del modal.
    $('.select2').each(function () {
        $(this).select2({
            width: '100%',
            minimumResultsForSearch: Infinity,
            dropdownParent: $(this).closest('.modal'),
        });
    });

    window.HdSettingsCommon.initFilterModal({
        modalId: idPrefix + '-filter-modal',
        applyBtnId: idPrefix + '-filter-apply-btn',
        clearBtnId: idPrefix + '-filter-clear-btn',
        formId: idPrefix + '-filter-form',
        fields: [
            { modal: '#' + idPrefix + '-modal-channel', hidden: '#' + idPrefix + '-filter-channel' },
            { modal: '#' + idPrefix + '-modal-language', hidden: '#' + idPrefix + '-filter-language' },
            { modal: '#' + idPrefix + '-modal-status', hidden: '#' + idPrefix + '-filter-status' },
        ],
    });

    window.HdSettingsCommon.initBulkActions({
        bulkUrl: cfg.bulkUrl,
        itemLabelSingular: 'mensaje',
        itemLabelPlural: 'mensaje(s)',
        confirmDelete: false,
    });

    // Validacion fallida o duplicado detectado (este ultimo llega como flash
    // 'error', no como error de formulario): reabrir el modal correspondiente
    // — el de alta, o la edicion concreta — en vez de dejar el motivo oculto
    // dentro de un modal cerrado. El Blade ya resuelve cual es (create o
    // edit-{id}) segun old('_item_id').
    if (cfg.reopenModalId) {
        var modalEl = document.getElementById(cfg.reopenModalId);
        if (modalEl) {
            new bootstrap.Modal(modalEl).show();
        }
    }
});
