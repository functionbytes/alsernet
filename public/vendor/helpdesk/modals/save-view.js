/*!
 * Helpdesk · modal "save-view" del inbox.
 *
 * Extraido de resources/views/helpdesk/inbox/partials/modals/save-view.blade.php,
 * donde vivia inline y se re-descargaba en cada carga del inbox. Sin
 * interpolacion Blade: la config llega por atributos data-* del markup.
 *
 * Convencion del modulo core: su JS se sirve desde public/vendor/helpdesk/ y no
 * tiene copia fuente aparte (igual que conversations.js y kb-suggestions.js).
 */
(function () {
    // conversations.js handles: open (#bv-save-view-btn), primary cancel (#bv-save-view-cancel), confirm (#bv-save-view-confirm)
    // We handle: second cancel button, backdrop click, keyboard shortcuts

    $(document).on('click', '#bv-save-view-cancel-2', function () {
        $('#bv-save-view-modal').css('display', 'none');
    });

    $(document).on('click', '#bv-save-view-modal', function (e) {
        if (e.target === this) { $(this).css('display', 'none'); }
    });

    $(document).on('keydown', '#bv-save-view-name', function (e) {
        if (e.key === 'Enter') { $('#bv-save-view-confirm').trigger('click'); }
        if (e.key === 'Escape') { $('#bv-save-view-modal').css('display', 'none'); }
    });
}());
