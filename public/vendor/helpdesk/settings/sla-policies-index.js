/**
 * Pantalla settings/sla-policies/index.blade.php.
 * window.HdSlaPoliciesConfig (flashSuccess/flashError) lo imprime el Blade.
 */
$(document).ready(function () {
    window.HdSettingsCommon.initDeleteModal();

    window.HdSettingsCommon.initToggleStatus('.btn-toggle-status', function ($btn, response) {
        if (response.is_active) {
            $btn.attr('title', 'Desactivar');
            $btn.html('<span class="badge bg-success-subtle text-success">Activo</span>');
        } else {
            $btn.attr('title', 'Activar');
            $btn.html('<span class="badge bg-secondary-subtle text-secondary">Inactivo</span>');
        }
    });

    window.HdSettingsCommon.flashSession(window.HdSlaPoliciesConfig);
});
