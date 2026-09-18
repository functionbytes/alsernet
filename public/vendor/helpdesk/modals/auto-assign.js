/*!
 * Helpdesk · modal "auto-assign" del inbox.
 *
 * Extraido de resources/views/helpdesk/inbox/partials/modals/auto-assign.blade.php,
 * donde vivia inline y se re-descargaba en cada carga del inbox. Sin
 * interpolacion Blade: la config llega vía AJAX a /panel/helpdesk/auto-assignment.
 *
 * Convencion del modulo core: su JS se sirve desde public/vendor/helpdesk/ y no
 * tiene copia fuente aparte (igual que conversations.js y kb-suggestions.js).
 */
(function ($) {
    'use strict';

    function csrf() { return $('meta[name="csrf-token"]').attr('content'); }

    function fill(data) {
        var strategy = (data && data.strategy) || 'round_robin';
        $('#aaEnabled').val(data && data.enabled ? '1' : '0');
        $('#aaStrategyList .reason').removeClass('on');
        $('#aaStrategyList .reason[data-bv-value="' + strategy + '"]').addClass('on');
        $('#aaRetry').val((data && data.retry) || 'off');
        $('#aaFallback').val((data && data.fallback) || 'queue');
    }

    $(document).on('bv:modal:open', function (e, name) {
        if (name !== 'auto-assign') { return; }
        $.ajax({
            url: '/panel/helpdesk/auto-assignment', method: 'GET',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf() }
        }).done(fill);
    });

    $(document).on('click', '#aaStrategyList .reason', function () {
        $('#aaStrategyList .reason').removeClass('on');
        $(this).addClass('on');
    });

    $(document).on('click', '#bv-auto-assign-save', function () {
        var $btn = $(this).prop('disabled', true);
        $.ajax({
            url: '/panel/helpdesk/auto-assignment', method: 'PUT',
            data: {
                enabled: $('#aaEnabled').val(),
                strategy: $('#aaStrategyList .reason.on').data('bv-value') || 'round_robin',
                retry: $('#aaRetry').val(),
                fallback: $('#aaFallback').val(),
            },
            headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' }
        }).done(function () {
            $('[data-bv-modal-name="auto-assign"]').removeClass('on');
            if ($('.bv-modal.on').length === 0) { $('body').css('overflow', ''); }
            if (window.toastr) { toastr.success('Estrategia de auto-asignación guardada'); }
        }).fail(function (xhr) {
            var msg = (xhr && xhr.responseJSON && xhr.responseJSON.message) || 'Error al guardar la estrategia';
            if (window.toastr) { toastr.error(msg); }
        }).always(function () { $btn.prop('disabled', false); });
    });

}(window.jQuery));
