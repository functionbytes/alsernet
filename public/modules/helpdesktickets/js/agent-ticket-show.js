/**
 * Detalle de ticket del agente (agents/tickets/show.blade.php) — propiedad
 * de HelpdeskTickets.
 *
 * Vivía como <script> suelto dentro del propio Blade. Solo depende de
 * window.Echo (opcional) y window.hdtAgentTicketShowConfig (ticketId), que
 * publica el propio Blade como datos, no como lógica.
 *
 * Tras editar hay que copiarlo a public/modules/helpdesktickets/js/.
 */
(function () {
    'use strict';

    var cfg = window.hdtAgentTicketShowConfig || {};

    if (typeof window.Echo === 'undefined' || !cfg.ticketId) return;

    window.Echo.private('helpdesk.ticket.' + cfg.ticketId)
        .listen('.ticket.message.new', function (data) {
            var time = new Date(data.message.created_at).toLocaleTimeString();

            var $author = $('<strong class="small">').text(data.message.author);
            var $time = $('<small class="text-muted ms-auto">').text(time);
            var $header = $('<div class="d-flex align-items-center gap-2 mb-2">').append($author, $time);
            var $body = $('<div>').text(data.message.content);
            var $card = $('<div class="card shadow-sm mb-2">').append(
                $('<div class="card-body">').append($header, $body)
            );

            $('#messages-container').append($card);

            var container = document.getElementById('messages-container');
            if (container) {
                container.scrollTop = container.scrollHeight;
            }
        });
})();
