/**
 * Formulario "New support ticket" del portal de cliente
 * (portal/tickets/create.blade.php) — propiedad de HelpdeskTickets.
 *
 * Vivía como <script> suelto dentro del propio Blade. Solo depende de
 * jQuery y window.hdtPortalTicketCreateConfig (suggestUrl), que publica el
 * propio Blade como datos, no como lógica.
 *
 * Tras editar hay que copiarlo a public/modules/helpdesktickets/js/.
 */
(function () {
    'use strict';

    $(function () {
        var cfg = window.hdtPortalTicketCreateConfig || {};
        var suggestUrl = cfg.suggestUrl;

        // Deflexión: artículos que podrían resolver la duda antes de crear el
        // ticket. Antes esto llamaba directamente al buscador del centro de ayuda
        // con lo tecleado en el asunto; ahora pasa por el endpoint del portal, que
        // además considera la descripción y descarta lo que no responde de verdad
        // a la consulta (ver TicketDeflectionService).
        var $subject = $('input[name="subject"]');
        var $description = $('textarea[name="description"]');
        var $container = $('#kb-suggestions');

        var kbTimer;
        var lastQuery = '';

        function suggest() {
            var subject = $subject.val().trim();
            var description = $description.val().trim();
            var signature = subject + '|' + description;

            if ((subject + ' ' + description).trim().length < 12) {
                $container.empty();
                lastQuery = '';
                return;
            }

            // Sin cambios reales desde la última consulta, no se repite: cada
            // llamada cuesta, y el cliente sigue escribiendo mucho después de
            // haber dicho ya de qué va su problema.
            if (signature === lastQuery) return;
            lastQuery = signature;

            $.ajax({
                url: suggestUrl,
                method: 'POST',
                dataType: 'json',
                data: { subject: subject, description: description },
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            }).done(function (res) {
                $container.empty();

                if (!res.articles || !res.articles.length) return;

                var $list = $('<div class="list-group mt-2">');

                res.articles.forEach(function (a) {
                    $list.append(
                        $('<a target="_blank" class="list-group-item list-group-item-action small py-2">')
                            .attr('href', a.url)
                            .append($('<i class="fas fa-book me-2 text-muted">'))
                            .append(document.createTextNode(a.title))
                    );
                });

                $container.append(
                    $('<div class="alert alert-info p-2 mb-0">').append(
                        $('<strong class="small">').append(
                            $('<i class="fas fa-lightbulb me-1">'),
                            document.createTextNode(' Could this solve it?')
                        ),
                        $list,
                        $('<div class="small text-muted mt-2">').text(
                            'If not, just carry on — your ticket will be created normally.'
                        )
                    )
                );
            });
        }

        // 1200 ms, no 500: detrás hay una llamada con coste, no una búsqueda local.
        $subject.add($description).on('input', function () {
            clearTimeout(kbTimer);
            kbTimer = setTimeout(suggest, 1200);
        });
    });
})();
