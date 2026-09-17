/**
 * Pantalla "Correos programados" (managers/emails/scheduled.blade.php) —
 * propiedad de HelpdeskTickets.
 *
 * Vivía como <script> suelto dentro del propio Blade. Solo depende de
 * jQuery, toastr (opcional) y window.bvSchedConfig (dataUrl/bulkUrl/
 * ticketsUrl), que publica el propio Blade como datos, no como lógica.
 *
 * Tras editar hay que copiarlo a public/modules/helpdesktickets/js/.
 */
(function () {
    'use strict';

    $(function () {
        var cfg = window.bvSchedConfig || {};
        var dataUrl = cfg.dataUrl;
        var bulkUrl = cfg.bulkUrl;
        var ticketsUrl = cfg.ticketsUrl || '';
        var csrf = $('meta[name="csrf-token"]').attr('content');
        var rows = [];

        // Selector de modo de vista (Lista/Compacta/Kanban) — igual que en
        // HelpdeskEmailActivity, opera sobre `rows` ya cargadas, sin AJAX propio al
        // cambiar de modo. Clave de localStorage distinta a la de ese módulo
        // para no pisar su preferencia (son pantallas y modos distintos).
        var modeStorageKey = 'helpdesktickets:scheduled-view-mode';
        var validModes = ['list', 'compact', 'kanban'];
        var $listView = $('#sched-list-view');
        var $kanbanView = $('#sched-kanban-view');

        function esc(s) { return $('<div>').text(s == null ? '' : s).html(); }

        function ticketUrl(m) {
            // La ficha completa (/full) se eliminó el 8-sep-2026: el listado con
            // el panel superpuesto (?ticket=) es la única vista de detalle.
            return m.ticket_id ? (ticketsUrl + '?ticket=' + m.ticket_id) : '#';
        }

        // Franja horaria de scheduled_at para el Kanban — "Atrasado" se calcula
        // por hora exacta (ya debería haber salido), el resto por día natural.
        // Fallback a 'later' si no hay fecha parseable: en la práctica todas las
        // filas de esta vista traen scheduled_at (TicketMail::scopeScheduled()
        // exige whereNotNull), pero no se descarta la fila por eso.
        function schedBucket(scheduledAtIso) {
            var when = scheduledAtIso ? new Date(scheduledAtIso) : null;
            if (!when || isNaN(when.getTime())) return 'later';

            var now = new Date();
            if (when < now) return 'overdue';

            var startOfToday = new Date(now.getFullYear(), now.getMonth(), now.getDate());
            var startOfWhenDay = new Date(when.getFullYear(), when.getMonth(), when.getDate());
            var diffDays = Math.round((startOfWhenDay - startOfToday) / 86400000);

            if (diffDays === 0) return 'today';
            if (diffDays <= 6) return 'week';
            return 'later';
        }

        // Orden de columnas por urgencia decreciente + paleta tomada de
        // tickets-app.css (--tkt-danger/--tkt-warn/--tkt-info/--tkt-text-faint):
        // rojo solo para la única columna que de verdad pide acción (correos
        // que ya deberían haber salido).
        var SCHED_BUCKETS = [
            { key: 'overdue', label: 'Atrasado', color: '#dc2626' },
            { key: 'today', label: 'Hoy', color: '#f59e0b' },
            { key: 'week', label: 'Esta semana', color: '#1e3a8a' },
            { key: 'later', label: 'Más adelante', color: '#72727b' },
        ];

        function renderSchedCard(m) {
            var scheduled = m.scheduled_at ? new Date(m.scheduled_at).toLocaleString() : '—';
            var ticketLabel = m.ticket_number || (m.ticket_id ? ('#' + m.ticket_id) : '—');

            return '<a class="sched-kcard" href="' + ticketUrl(m) + '">'
                + '<div class="sched-kcard-subject">' + esc(m.subject || '(sin asunto)') + '</div>'
                + '<div class="sched-kcard-recipient">' + esc(m.to || '') + '</div>'
                + '<div class="sched-kcard-meta">' + esc(ticketLabel) + ' · ' + esc(scheduled) + '</div>'
                + '</a>';
        }

        // Agrupa SOLO las filas ya cargadas (rows) en columnas por franja
        // horaria — agrupar por estado no aporta nada aquí (todo está en
        // 'scheduled'), a diferencia del Kanban de HelpdeskEmailActivity. Columnas
        // vacías no se pintan.
        function renderKanban() {
            var byBucket = new Map();
            rows.forEach(function (m) {
                var key = schedBucket(m.scheduled_at);
                if (!byBucket.has(key)) byBucket.set(key, []);
                byBucket.get(key).push(m);
            });

            var html = '';
            SCHED_BUCKETS.forEach(function (bucket) {
                var list = byBucket.get(bucket.key);
                if (!list || !list.length) return;

                // Dentro de cada columna, lo más próximo a salir primero.
                list.sort(function (a, b) { return (a.scheduled_at || '').localeCompare(b.scheduled_at || ''); });

                html += '<div class="sched-kcol">'
                    + '<div class="sched-kcol-head">'
                    // Color dinámico: variable CSS inline consumida por
                    // .sched-kcol-dot { background-color: var(--dot-color) }
                    // en scheduled-emails.css, nunca un style="" con el valor.
                    + '<span class="sched-kcol-dot" style="--dot-color:' + bucket.color + '"></span>'
                    + '<span>' + esc(bucket.label) + '</span>'
                    + '<span class="sched-kcol-count">' + list.length + '</span>'
                    + '</div>'
                    + '<div class="sched-kcol-body">' + list.map(renderSchedCard).join('') + '</div>'
                    + '</div>';
            });

            return html || '<div class="sched-kanban-empty text-center text-muted py-4">No hay correos programados.</div>';
        }

        function applyMode(mode) {
            if (validModes.indexOf(mode) === -1) mode = 'list';

            $('#sched-mode-switch .sched-mode-btn').removeClass('on')
                .filter('[data-sched-mode="' + mode + '"]').addClass('on');

            $listView.removeClass('sched-mode-hidden sched-compact');
            $kanbanView.addClass('sched-mode-hidden');

            if (mode === 'kanban') {
                $listView.addClass('sched-mode-hidden');
                $kanbanView.removeClass('sched-mode-hidden');
            } else if (mode === 'compact') {
                $listView.addClass('sched-compact');
            }

            try { window.localStorage.setItem(modeStorageKey, mode); } catch (e) { /* localStorage bloqueado: se ignora, el modo simplemente no persiste */ }
        }

        function render() {
            var $tbody = $('#sched-tbody');
            $tbody.find('tr:not(#sched-loading):not(#sched-empty)').remove();
            $('#sched-loading').addClass('d-none');
            $('#sched-empty').toggleClass('d-none', rows.length > 0);

            rows.forEach(function (m) {
                var scheduled = m.scheduled_at ? new Date(m.scheduled_at).toLocaleString() : '—';

                $('<tr></tr>')
                    .append($('<td></td>').append($('<input type="checkbox" class="sched-checkbox">').val(m.id)))
                    .append($('<td></td>').append($('<a></a>').attr('href', ticketUrl(m)).text(m.ticket_number || ('#' + m.ticket_id))))
                    .append($('<td></td>').text(m.subject || '(sin asunto)'))
                    .append($('<td></td>').text(m.to || ''))
                    .append($('<td></td>').text(scheduled))
                    .append(
                        $('<td></td>').append(
                            $('<div class="dropdown"></div>').append(
                                $('<button type="button" class="btn btn-sm btn-light" data-bs-toggle="dropdown"><i class="fas fa-ellipsis-vertical"></i></button>'),
                                $('<ul class="dropdown-menu dropdown-menu-end"></ul>').append(
                                    $('<li></li>').append($('<a class="dropdown-item sched-resend-one" href="#">Enviar ahora</a>').data('id', m.id)),
                                    $('<li></li>').append($('<a class="dropdown-item sched-cancel-one" href="#">Cancelar</a>').data('id', m.id)),
                                ),
                            ),
                        ),
                    )
                    .appendTo($tbody);
            });

            updateBulkToolbar();

            // La vista Kanban se refresca siempre (esté visible o no) para que
            // nunca quede con datos viejos si el agente cambia de modo justo
            // después de una acción masiva (que vuelve a pedir `rows` vía
            // load()). Coste insignificante: como mucho 50 filas (paginate(50)
            // en TicketMailsController::index()).
            $kanbanView.html(renderKanban());
        }

        function updateBulkToolbar() {
            var count = $('.sched-checkbox:checked').length;
            $('#sched-bulk-toolbar').toggleClass('d-none', count === 0);
            $('#sched-bulk-count').text(count);
        }

        function load() {
            $.getJSON(dataUrl).done(function (res) {
                rows = (res && res.data) || [];
                render();
            }).fail(function () {
                $('#sched-loading').html('<td colspan="6" class="text-center text-danger py-4">No se pudo cargar.</td>');
            });
        }

        function bulk(action, ids) {
            if (!ids.length) return;
            $.ajax({
                url: bulkUrl,
                method: 'POST',
                data: { action: action, mail_ids: ids },
                headers: { 'X-CSRF-TOKEN': csrf },
            }).done(function (res) {
                if (window.toastr) toastr.success(res.message || 'Hecho');
                load();
            }).fail(function (xhr) {
                if (window.toastr) toastr.error((xhr.responseJSON && xhr.responseJSON.message) || 'Error');
            });
        }

        $(document).on('change', '#sched-select-all', function () {
            $('.sched-checkbox').prop('checked', $(this).prop('checked'));
            updateBulkToolbar();
        });

        $(document).on('change', '.sched-checkbox', updateBulkToolbar);

        $('#sched-bulk-resend').on('click', function () {
            bulk('resend', $('.sched-checkbox:checked').map(function () { return $(this).val(); }).get());
        });

        $('#sched-bulk-cancel').on('click', function () {
            bulk('cancel_scheduled', $('.sched-checkbox:checked').map(function () { return $(this).val(); }).get());
        });

        $(document).on('click', '.sched-resend-one', function (e) {
            e.preventDefault();
            bulk('resend', [$(this).data('id')]);
        });

        $(document).on('click', '.sched-cancel-one', function (e) {
            e.preventDefault();
            bulk('cancel_scheduled', [$(this).data('id')]);
        });

        $('#sched-mode-switch').on('click', '.sched-mode-btn', function () {
            applyMode($(this).data('sched-mode'));
        });

        var savedMode = 'list';
        try {
            var stored = window.localStorage.getItem(modeStorageKey);
            if (stored && validModes.indexOf(stored) !== -1) savedMode = stored;
        } catch (e) { /* localStorage bloqueado: se queda en 'list' */ }
        applyMode(savedMode);

        load();
    });
})();
