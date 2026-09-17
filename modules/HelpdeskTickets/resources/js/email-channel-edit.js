/**
 * Editar canal de correo (managers/settings/email-channels/edit.blade.php)
 * — propiedad de HelpdeskTickets.
 *
 * Vivía como <script> suelto dentro del propio Blade. Depende de toastr y
 * window.hdtEmailChannelEditConfig (que publica el propio Blade como datos,
 * no como lógica). El botón "Probar conexión" vive en _form.blade.php
 * (email-channel-form.js), este fichero es solo el botón "Sincronizar
 * ahora" de la tarjeta lateral.
 *
 * Tras editar hay que copiarlo a public/modules/helpdesktickets/js/.
 */
(function ($) {
    'use strict';

    $(function () {
        var cfg = window.hdtEmailChannelEditConfig || {};
        var csrfToken = $('meta[name="csrf-token"]').attr('content');
        var $btn = $('.btn-sync-channel');
        if (!$btn.length) return;

        $btn.on('click', function () {
            var $button = $(this);
            var original = $button.text();
            $button.prop('disabled', true).text('Sincronizando...');

            $.ajax({
                url: cfg.syncUrl,
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken },
                contentType: 'application/json',
                dataType: 'json',
            }).done(function (data) {
                if (data.success) {
                    toastr.success(data.message);
                } else {
                    toastr.error(data.message);
                }
                setTimeout(function () { location.reload(); }, 1200);
            }).fail(function () {
                toastr.error('Error inesperado al sincronizar el canal.');
                $button.prop('disabled', false).text(original);
            });
        });
    });
})(jQuery);
