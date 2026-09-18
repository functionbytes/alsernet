/**
 * Listado de webhooks salientes — settings/webhooks/index.blade.php
 * Requiere: window.WebhooksIndexConfig = { flash: {success, error} }
 *
 * El boton .delete-btn usaba #deleteForm/#deleteItemName/#deleteModal, que no
 * existen (@include('core::components.delete') genera #delete-form/#delete-modal,
 * sin #deleteItemName) — el modal nunca se abria. Se corrige usando el modal
 * generico via HDSettingsCommon.wireDeleteButtonModal().
 */
$(document).ready(function () {
    var cfg = window.WebhooksIndexConfig || {};

    HDSettingsCommon.wireDeleteButtonModal();

    HDSettingsCommon.flashToastr(cfg.flash);
});
