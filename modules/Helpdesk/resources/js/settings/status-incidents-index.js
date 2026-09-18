/**
 * Pantalla settings/status-page/incidents.blade.php.
 * window.HdStatusIncidentsConfig (flashSuccess/flashError) lo imprime el Blade.
 */
$(document).ready(function () {
    window.HdSettingsCommon.flashSession(window.HdStatusIncidentsConfig);
    window.HdSettingsCommon.initDeleteModal();
});
