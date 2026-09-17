/**
 * Listado de workflows — settings/workflows/index.blade.php
 * Requiere: window.WorkflowsIndexConfig = { flash: {success, error} }
 *
 * El boton .delete-btn usaba #deleteForm/#deleteItemName/#deleteModal, que no
 * existen (@include('core::components.delete') genera #delete-form/#delete-modal,
 * sin #deleteItemName) — el modal nunca se abria. Se corrige usando el modal
 * generico via HDSettingsCommon.wireDeleteButtonModal().
 */
$(document).ready(function () {
    var cfg = window.WorkflowsIndexConfig || {};

    HDSettingsCommon.initFormSelect2();
    HDSettingsCommon.wireDeleteButtonModal();

    $(document).on('click', '.btn-toggle', function () {
        const url = $(this).data('url');
        $('#toggleForm').attr('action', url).submit();
    });

    HDSettingsCommon.flashToastr(cfg.flash);
});
