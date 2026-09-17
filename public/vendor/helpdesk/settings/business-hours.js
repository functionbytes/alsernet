/**
 * Pantalla settings/business/hours.blade.php.
 * window.HdBusinessHoursConfig = { flashSuccess, flashError } lo imprime el
 * Blade.
 *
 * Requiere settings-common-batch3.js (window.HdSettingsCommon) cargado antes.
 */
$(document).ready(function () {
    var cfg = window.HdBusinessHoursConfig || {};

    $('.form-select').select2({ width: '100%' });

    function toggleDayInputs(day, isOpen) {
        var $row = $('[data-day="' + day + '"]').closest('tr');
        $row.find('.time-input').prop('disabled', !isOpen);
        $row.toggleClass('bh-row-closed', !isOpen);
    }

    // Initialize state on load
    $('.day-toggle').each(function () {
        toggleDayInputs($(this).data('day'), $(this).is(':checked'));
    });

    // Toggle on change
    $(document).on('change', '.day-toggle', function () {
        toggleDayInputs($(this).data('day'), $(this).is(':checked'));
    });

    window.HdSettingsCommon.flashSession(cfg);
});
