/**
 * Bootstrap generico para pantallas simples de settings/helpdesk que solo
 * necesitan: flash toastr desde sesion, el modal de borrado generico
 * (.delete-btn / #delete-modal / #delete-form) y, opcionalmente, un select2
 * por defecto. Usado por pantallas que no tienen logica propia mas alla de
 * este boilerplate (ver window.HdSettingsPageConfig, impreso por cada Blade).
 *
 * Requiere que settings-common-batch3.js (window.HdSettingsCommon) se cargue
 * antes que este archivo.
 */
$(document).ready(function () {
    var cfg = window.HdSettingsPageConfig || {};

    if (cfg.select2Selector) {
        window.HdSettingsCommon.initFormSelect2(cfg.select2Selector);
    }

    window.HdSettingsCommon.initDeleteModal();
    window.HdSettingsCommon.flashSession(cfg);
});
