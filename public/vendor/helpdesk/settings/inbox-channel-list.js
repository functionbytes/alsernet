/**
 * Pantalla settings/inboxes/channel-list.blade.php.
 */
$(document).ready(function () {
    $('.hd-inbox-icon[data-color]').each(function () {
        $(this).css({ background: $(this).data('color'), color: '#fff' });
    });
});
