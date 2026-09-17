/**
 * Formulario compartido create/edit de canal de correo
 * (managers/settings/email-channels/_form.blade.php) — propiedad de
 * HelpdeskTickets.
 *
 * Vivía como <script> suelto dentro del propio partial. Depende de jQuery,
 * select2, toastr y window.hdtEmailChannelFormConfig (que publica el propio
 * Blade como datos, no como lógica).
 *
 * Tras editar hay que copiarlo a public/modules/helpdesktickets/js/.
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var cfg = window.hdtEmailChannelFormConfig || {};
        var csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        function render(container, ok, message) {
            container.classList.remove('d-none');
            container.innerHTML = ok
                ? '<div class="alert alert-success border-0 mb-0">' + message + '</div>'
                // alert-warning, no alert-danger: el resto de estados de error de
                // esta pantalla (badge del listado, aviso del panel lateral) van
                // en ambar.
                : '<div class="alert alert-warning border-0 mb-0">' + message + '</div>';
        }

        function runTest(button, container, url, payload, emptyMessage) {
            if (!payload) {
                toastr.warning(emptyMessage);
                return;
            }

            var original = button.textContent;
            button.disabled = true;
            button.textContent = 'Probando...';

            fetch(url, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify(payload),
            }).then(function (r) { return r.json(); }).then(function (data) {
                render(container, data.success, data.message);
            }).catch(function () {
                render(container, false, 'Error inesperado al probar la conexion.');
            }).finally(function () {
                button.disabled = false;
                button.textContent = original;
            });
        }

        var $testImap = document.getElementById('btn-test-channel');
        if ($testImap) {
            $testImap.addEventListener('click', function () {
                var host = document.getElementById('channelHost').value;
                var port = document.getElementById('channelPort').value;

                // Solo servidor y puerto: la prueba abre un socket, no autentica. Antes
                // se exigian usuario y contrasena y se enviaban al servidor para nada;
                // al editar un canal el campo de contrasena viene vacio a proposito, asi
                // que el boton no llegaba a ejecutarse nunca.
                if (!host || !port) {
                    toastr.warning('Completa servidor y puerto antes de probar.');
                    return;
                }

                runTest(
                    this,
                    document.getElementById('channelTestResult'),
                    cfg.testImapUrl,
                    { host: host, port: port }
                );
            });
        }

        var $testSmtp = document.getElementById('btn-test-smtp-channel');
        if ($testSmtp) {
            $testSmtp.addEventListener('click', function () {
                var smtpHost = document.getElementById('channelSmtpHost').value;
                var smtpPort = document.getElementById('channelSmtpPort').value;

                if (!smtpHost || !smtpPort) {
                    toastr.warning('Completa servidor y puerto SMTP antes de probar.');
                    return;
                }

                runTest(
                    this,
                    document.getElementById('channelSmtpTestResult'),
                    cfg.testSmtpUrl,
                    { smtp_host: smtpHost, smtp_port: smtpPort }
                );
            });
        }

        $('.select2').select2({ width: '100%' });
    });
})();
