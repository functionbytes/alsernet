/**
 * Pantalla settings/integrations.blade.php — solo muestra el flash de
 * sesion tras guardar. window.HdIntegrationsConfig lo imprime el Blade.
 */
$(document).ready(function () {
    window.HdSettingsCommon.flashSession(window.HdIntegrationsConfig);
});
