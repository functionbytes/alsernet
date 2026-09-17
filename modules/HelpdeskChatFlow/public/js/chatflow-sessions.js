/**
 * chatflow-sessions.js — HelpdeskChatFlow module
 *
 * Shows session flash messages on the flow sessions page. Reads them from
 * window.HelpdeskChatFlowSessions, emitted by sessions.blade.php.
 */
(function ($) {
    'use strict';

    var config = window.HelpdeskChatFlowSessions || {};

    $(function () {
        if (config.successMessage) { toastr.success(config.successMessage, 'Exito'); }
        if (config.errorMessage) { toastr.error(config.errorMessage, 'Error'); }
    });
})(jQuery);
