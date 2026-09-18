/**
 * chatflow-analytics.js — HelpdeskChatFlow module
 *
 * Behaviour for the flow analytics page: draws the drop-off bars from their
 * `data-rate` attribute (avoids inline styles) and reloads the page when the
 * date-range select changes.
 */
(function ($) {
    'use strict';

    $(function () {
        $('.progress-bar[data-rate]').each(function () {
            $(this).css('width', $(this).data('rate') + '%');
        });

        $('#analytics-range').on('change', function () {
            $('#analytics-range-form').trigger('submit');
        });
    });
})(jQuery);
