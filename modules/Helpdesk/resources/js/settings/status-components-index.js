/**
 * Pantalla settings/status-page/components.blade.php.
 * window.HdStatusComponentsConfig (flashSuccess/flashError) lo imprime el Blade.
 */
$(document).ready(function () {
    window.HdSettingsCommon.flashSession(window.HdStatusComponentsConfig);
    window.HdSettingsCommon.initDeleteModal();
});
