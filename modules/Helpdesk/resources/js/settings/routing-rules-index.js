/**
 * Pantalla settings/routing-rules/index.blade.php.
 * window.HdRoutingRulesConfig (flashSuccess/flashError) lo imprime el Blade.
 */
$(document).ready(function () {
    window.HdSettingsCommon.initFormSelect2('.form-select');
    window.HdSettingsCommon.flashSession(window.HdRoutingRulesConfig);
    window.HdSettingsCommon.initDeleteModal();

    window.HdSettingsCommon.initToggleStatus('.toggle-status', function ($badge, response) {
        if (response.is_active) {
            $badge.removeClass('bg-secondary-subtle text-secondary').addClass('bg-success-subtle text-success').text('Activa');
        } else {
            $badge.removeClass('bg-success-subtle text-success').addClass('bg-secondary-subtle text-secondary').text('Inactiva');
        }
    });
});
