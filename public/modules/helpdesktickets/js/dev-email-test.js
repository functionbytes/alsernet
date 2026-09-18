/**
 * Herramienta de desarrollo "Email test" (dev/email-test.blade.php) —
 * propiedad de HelpdeskTickets.
 *
 * Vivía como <script> suelto dentro del propio Blade. Solo depende de
 * jQuery, toastr y window.hdtDevEmailTestConfig (sendUrl/syncUrl), que
 * publica el propio Blade como datos, no como lógica.
 *
 * Tras editar hay que copiarlo a public/modules/helpdesktickets/js/.
 */
(function () {
    'use strict';

    $(document).ready(function () {
        var cfg = window.hdtDevEmailTestConfig || {};
        var csrfToken = $('meta[name="csrf-token"]').attr('content');

        function clearFieldErrors() {
            $('#send-email-form .form-control').removeClass('is-invalid');
            $('#send-email-form .invalid-feedback').text('');
        }

        function showFieldErrors(errors) {
            $.each(errors, function (field, messages) {
                $('#' + field).addClass('is-invalid');
                $('#error-' + field).text(messages[0]);
            });
        }

        function setSendLoading(loading) {
            $('#btn-send-text').toggleClass('d-none', loading);
            $('#btn-send-spinner').toggleClass('d-none', !loading);
            $('#btn-send-email').prop('disabled', loading);
        }

        function setSyncLoading(loading) {
            $('#btn-sync-text').toggleClass('d-none', loading);
            $('#btn-sync-spinner').toggleClass('d-none', !loading);
            $('#btn-sync').prop('disabled', loading);
        }

        function renderTicketsTable(tickets) {
            var $container = $('#tickets-result');
            $container.empty();

            if (!tickets || tickets.length === 0) {
                $container.append($('<p class="text-muted small mb-0">').text('No se encontraron tickets recientes.'));
                return;
            }

            var $tbody = $('<tbody>');
            tickets.forEach(function (t) {
                $tbody.append(
                    $('<tr>').append(
                        $('<td>').text(t.id),
                        $('<td>').text(t.ticket_number || '—'),
                        $('<td>').text(t.subject || '—'),
                        $('<td>').append($('<span class="badge bg-secondary">').text(t.source || '—')),
                        $('<td class="text-nowrap">').text(t.created_at || '—')
                    )
                );
            });

            var $table = $('<table class="table table-sm table-hover align-middle mb-0">').append(
                $('<thead>').append(
                    $('<tr>').append(
                        $('<th>').text('#'),
                        $('<th>').text('Numero'),
                        $('<th>').text('Asunto'),
                        $('<th>').text('Fuente'),
                        $('<th>').text('Fecha')
                    )
                ),
                $tbody
            );

            $container.append($('<div class="table-responsive">').append($table));
        }

        $('#btn-send-email').on('click', function () {
            clearFieldErrors();
            setSendLoading(true);

            $.ajax({
                url: cfg.sendUrl,
                method: 'POST',
                dataType: 'json',
                headers: { 'X-CSRF-TOKEN': csrfToken },
                data: $('#send-email-form').serialize(),
                success: function (res) {
                    toastr.success(res.message || 'Email enviado correctamente');
                    toastr.info('Ahora haz click en "Sincronizar" para crear el ticket.');
                },
                error: function (xhr) {
                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        showFieldErrors(xhr.responseJSON.errors);
                        toastr.error('Corrige los errores del formulario.');
                    } else {
                        var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Error al enviar el email.';
                        toastr.error(msg);
                    }
                },
                complete: function () {
                    setSendLoading(false);
                }
            });
        });

        $('#btn-sync').on('click', function () {
            setSyncLoading(true);
            $('#tickets-result').html('<p class="text-muted small mb-0"><span class="spinner-border spinner-border-sm me-1"></span> Sincronizando...</p>');

            $.ajax({
                url: cfg.syncUrl,
                method: 'POST',
                dataType: 'json',
                headers: { 'X-CSRF-TOKEN': csrfToken },
                success: function (res) {
                    toastr.success('Sincronizacion completada');
                    renderTicketsTable(res.tickets);
                },
                error: function (xhr) {
                    var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Error al sincronizar.';
                    toastr.error(msg);
                    $('#tickets-result').empty().append($('<p class="text-danger small mb-0">').text(msg));
                },
                complete: function () {
                    setSyncLoading(false);
                }
            });
        });
    });
})();
