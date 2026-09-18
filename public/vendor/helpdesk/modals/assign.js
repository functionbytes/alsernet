/*!
 * Helpdesk · modal "assign" del inbox.
 *
 * Extraido de resources/views/helpdesk/inbox/partials/modals/assign.blade.php,
 * donde vivia inline y se re-descargaba en cada carga del inbox. Sin
 * interpolacion Blade: la config llega por atributos data-* del markup.
 *
 * Convencion del modulo core: su JS se sirve desde public/vendor/helpdesk/ y no
 * tiene copia fuente aparte (igual que conversations.js y kb-suggestions.js).
 */
(function () {
    $(document).on('click', '[data-bv-modal-name="assign"] .asgn-item', function () {
        $(this).closest('#assign-unified-list').find('.asgn-item').removeClass('on');
        $(this).addClass('on');
    });

    $(document).on('change', '[data-bv-modal-name="assign"] .asgn-notify-opt input', function () {
        $(this).closest('.asgn-notify-opt').toggleClass('on', this.checked);
    });

    $(document).on('input', '#assign-search', function () {
        var q = $(this).val().toLowerCase();
        $('#assign-unified-list .asgn-item').each(function () {
            $(this).toggle(!q || $(this).find('.asgn-t').text().toLowerCase().includes(q));
        });
        $('#assign-unified-list .asgn-sec-lbl').each(function () {
            var $lbl = $(this);
            var $items = $lbl.nextUntil('.asgn-sec-lbl', '.asgn-item');
            $lbl.toggle(!q || $items.filter(':visible').length > 0);
        });
    });
}());
