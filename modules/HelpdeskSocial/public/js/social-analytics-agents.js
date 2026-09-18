/**
 * Rendimiento de agentes (managers/social-analytics/agents.blade.php).
 *
 * Solo fija el ancho de las barras de progreso dinamicas via la custom
 * property --hso-progress (.hso-progress-bar-dynamic la consume en CSS) —
 * nunca via style="" inline.
 */
(function ($) {
    'use strict';

    $(function () {
        $('[data-progress]').each(function () {
            $(this).css('--hso-progress', $(this).data('progress'));
        });
    });
})(jQuery);
