/**
 * Pantalla settings/email/index.blade.php.
 * window.HdEmailSettingsConfig = { testSmtpUrl, testImapUrl } lo imprime el
 * Blade (son rutas con nombre — no se pueden generar en JS puro).
 *
 * Requiere settings-common-batch3.js (window.HdSettingsCommon) cargado antes.
 */
(function ($) {
    'use strict';

    var cfg = window.HdEmailSettingsConfig || {};
    var csrf = document.querySelector('meta[name="csrf-token"]').content;

    window.HdSettingsCommon.initFormSelect2('.form-select');

    // Toggle visibility smtp/imap fields
    $('#outbound_enabled').on('change', function () {
        $('#smtp-fields').toggleClass('d-none', !this.checked);
    });

    $('#inbound_enabled').on('change', function () {
        $('#imap-fields').toggleClass('d-none', !this.checked);
    });

    // Toggle password visibility
    $(document).on('click', '.toggle-password', function () {
        var $input = $('#' + $(this).data('target'));
        var isPassword = $input.attr('type') === 'password';
        $input.attr('type', isPassword ? 'text' : 'password');
        $(this).find('i').toggleClass('fa-eye', !isPassword).toggleClass('fa-eye-slash', isPassword);
    });

    // Save form via AJAX
    $('#emailSettingsForm').on('submit', function (e) {
        e.preventDefault();
        var $btn = $('#saveBtn').prop('disabled', true).text('Guardando...');

        fetch(this.action, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body: new FormData(this),
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.status) {
                toastr.success(data.message, 'Guardado');
            } else {
                toastr.error(data.message || 'Error al guardar', 'Error');
            }
        })
        .catch(function () { toastr.error('Error al guardar la configuración', 'Error'); })
        .finally(function () { $btn.prop('disabled', false).text('Guardar configuracion'); });
    });

    // Test SMTP connection
    $('#testSmtpBtn').on('click', function () {
        var $btn = $(this).prop('disabled', true);
        var $result = $('#smtp-test-result').text('Probando...').removeClass('text-success text-dark');

        fetch(cfg.testSmtpUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify({
                host:       $('#smtp_host').val(),
                port:       $('#smtp_port').val(),
                username:   $('#smtp_username').val(),
                password:   $('#smtp_password').val(),
                encryption: $('#smtp_encryption').val(),
            }),
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.status) {
                $result.addClass('text-success').text(data.message);
                toastr.success(data.message, 'SMTP');
            } else {
                $result.addClass('text-dark').text(data.message);
                toastr.error(data.message, 'SMTP');
            }
        })
        .catch(function () {
            $result.addClass('text-dark').text('Error al ejecutar la prueba');
            toastr.error('Error al probar la conexión SMTP', 'Error');
        })
        .finally(function () { $btn.prop('disabled', false); });
    });

    // Test IMAP connection
    $('#testImapBtn').on('click', function () {
        var $btn = $(this).prop('disabled', true);
        var $result = $('#imap-test-result').text('Probando...').removeClass('text-success text-dark');

        fetch(cfg.testImapUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify({
                host:       $('#imap_host').val(),
                port:       $('#imap_port').val(),
                username:   $('#imap_username').val(),
                password:   $('#imap_password').val(),
                encryption: $('#imap_encryption').val(),
                folder:     $('#imap_folder').val(),
            }),
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.status) {
                $result.addClass('text-success').text(data.message);
                toastr.success(data.message, 'IMAP');
            } else {
                $result.addClass('text-dark').text(data.message);
                toastr.error(data.message, 'IMAP');
            }
        })
        .catch(function () {
            $result.addClass('text-dark').text('Error al ejecutar la prueba');
            toastr.error('Error al probar la conexión IMAP', 'Error');
        })
        .finally(function () { $btn.prop('disabled', false); });
    });
})(jQuery);
