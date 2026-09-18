/**
 * Formulario de nueva plantilla de WhatsApp — settings/whatsapp-templates/create.blade.php
 * Requiere: window.WhatsappTemplateFormConfig = { flash: {success, error} }
 */
$(document).ready(function () {
    var cfg = window.WhatsappTemplateFormConfig || {};

    HDSettingsCommon.initFormSelect2();
    HDSettingsCommon.flashToastr(cfg.flash);
});
