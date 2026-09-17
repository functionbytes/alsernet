/**
 * chatflow-index.js — HelpdeskChatFlow module
 *
 * Behaviour for the chat flows list page: session toasts, wiring the shared
 * delete modal to the clicked row, and applying each template's accent
 * color from its `data-color` attribute (avoids inline styles).
 *
 * Reads session flash messages from window.HelpdeskChatFlowIndex, emitted by
 * index.blade.php.
 */
(function ($) {
    'use strict';

    var config = window.HelpdeskChatFlowIndex || {};

    $(function () {
        if (config.successMessage) { toastr.success(config.successMessage, 'Exito'); }
        if (config.errorMessage) { toastr.error(config.errorMessage, 'Error'); }

        $(document).on('click', '.delete-btn', function () {
            $('#delete-modal .modal-title').text($(this).data('title'));
            $('#delete-form').attr('action', $(this).data('url'));
        });

        $('.template-icon').each(function () {
            $(this).css('background-color', $(this).data('color'));
        });
    });
})(jQuery);
