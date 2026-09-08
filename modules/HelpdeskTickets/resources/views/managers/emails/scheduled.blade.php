@extends('layouts.theme')

@section('title', 'Correos programados')

@section('page_header')
    @include('core::components.card', ['title' => 'Correos programados'])
@endsection

@section('content')
    <div class="card">
        <div class="card-header p-4 border-bottom d-flex align-items-center justify-content-between">
            <div>
                <h5 class="mb-1 fw-bold">Correos programados</h5>
                <p class="small mb-0 text-muted">Correos de tickets aún no enviados, con fecha de envío futura — de todos los tickets a la vez.</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                {{-- Selector de modo de vista (Lista/Compacta/Kanban) — mismo
                     concepto que el de HelpdeskEmailActivity (evx-mode-switch),
                     con prefijo de clase propio (sched-) y paleta de
                     HelpdeskTickets. Opera SOLO sobre las filas ya cargadas
                     en `rows` (ver @push('scripts')), sin volver a pedir
                     nada al servidor al cambiar de modo. --}}
                <div class="sched-mode-switch" id="sched-mode-switch" role="group" aria-label="Modo de vista">
                    <button type="button" class="sched-mode-btn on" data-sched-mode="list">
                        <i class="fa-solid fa-list" aria-hidden="true"></i> Lista
                    </button>
                    <button type="button" class="sched-mode-btn" data-sched-mode="compact">
                        <i class="fa-solid fa-bars" aria-hidden="true"></i> Compacta
                    </button>
                    <button type="button" class="sched-mode-btn" data-sched-mode="kanban">
                        <i class="fa-solid fa-table-columns" aria-hidden="true"></i> Kanban
                    </button>
                </div>
                <div id="sched-bulk-toolbar" class="d-none">
                    <span class="small text-muted me-2"><span id="sched-bulk-count">0</span> seleccionados</span>
                    <button type="button" class="btn btn-primary btn-sm" id="sched-bulk-resend">Enviar ahora</button>
                    <button type="button" class="btn btn-light btn-sm" id="sched-bulk-cancel">Cancelar</button>
                </div>
            </div>
        </div>

        <div id="sched-list-view" class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width:1%"><input type="checkbox" id="sched-select-all"></th>
                        <th>Ticket</th>
                        <th>Asunto</th>
                        <th>Para</th>
                        <th>Programado para</th>
                        <th style="width:1%"></th>
                    </tr>
                </thead>
                <tbody id="sched-tbody">
                    <tr id="sched-loading"><td colspan="6" class="text-center text-muted py-4">Cargando…</td></tr>
                    <tr id="sched-empty" class="d-none"><td colspan="6" class="text-center text-muted py-4">No hay correos programados.</td></tr>
                </tbody>
            </table>
        </div>

        {{-- Vista Kanban — la rellena el JS a partir de las mismas filas ya
             cargadas en la tabla de arriba (ver @push('scripts')). Agrupa por
             franja horaria de scheduled_at (Atrasado/Hoy/Esta semana/Más
             adelante): agrupar por estado no aporta nada en esta pantalla
             (todo está en estado 'scheduled' por definición), la pregunta
             real de un agente aquí es "qué sale y cuándo". --}}
        <div id="sched-kanban-view" class="sched-mode-hidden">
            <div class="sched-kanban-empty text-center text-muted py-4">Cargando…</div>
        </div>
    </div>
@endsection

{{-- Todo el CSS/JS del selector de modos vive aquí, en el propio archivo: la
     pantalla no tenía ninguna hoja/script propios y se mantiene autocontenida
     a propósito (sin publicar un .css nuevo). Los valores de color/radio se
     toman "a mano" de las variables --tkt-* de tickets-app.css (mismo
     vocabulario visual del módulo, ver .tkt-kcol/.tkt-kcard de
     tickets/index.blade.php) en vez de cargar ese archivo aquí: su selector
     raíz .tkt trae `body:has(.tkt) .mc-content { margin:0!important;
     height:calc(100vh - 80px)!important; ... }`, pensado para el shell de
     app a pantalla completa de esa otra pantalla — cargarlo en esta rompería
     el layout normal de tarjeta que usa scheduled.blade.php. --}}
@push('css')
<style>
.sched-mode-switch { display: inline-flex; align-items: center; gap: 2px; background: #f4f4f5; border-radius: 8px; padding: 3px; }
.sched-mode-btn { display: inline-flex; align-items: center; gap: 6px; padding: 5px 10px; border: none; background: transparent; border-radius: 6px; color: #52525b; font-size: 11.5px; font-weight: 600; line-height: 1.2; white-space: nowrap; cursor: pointer; transition: .12s; }
.sched-mode-btn i { font-size: 10px; }
.sched-mode-btn:hover { color: #18181b; }
.sched-mode-btn.on { background: #fff; color: #18181b; box-shadow: 0 1px 2px rgba(0,0,0,.04); }
.sched-mode-hidden { display: none !important; }

/* Densidad compacta — mismo criterio que el selector de HelpdeskEmailActivity:
   menos padding vertical en las celdas, sin tocar la estructura de la
   tabla (misma tabla que en modo Lista). */
#sched-list-view.sched-compact .table > tbody > tr > td { padding-top: 5.5px; padding-bottom: 5.5px; }

/* ── Kanban por franja horaria ──────────────────────────────────────────
   "Atrasado" (rojo, --tkt-danger) es la única columna que de verdad pide
   acción: scheduled_at ya pasó y el correo sigue sin enviarse (cola
   atascada). "Hoy" en ámbar (--tkt-warn) son los que aún pueden revisarse
   antes de salir. "Esta semana"/"Más adelante" bajan en urgencia hacia
   azul (--tkt-info) y gris (--tkt-text-faint). El color de cada punto lo
   pone el JS inline (style="background:..."), igual que .evx-kcol-dot. */
#sched-kanban-view { display: flex; align-items: flex-start; gap: 12px; padding: 16px; overflow-x: auto; }
.sched-kcol { width: 260px; flex-shrink: 0; display: flex; flex-direction: column; background: #f4f4f5; border-radius: 10px; }
.sched-kcol-head { display: flex; align-items: center; gap: 8px; padding: 10px 12px; border-bottom: 1px solid #e8e8e8; font-size: 12px; font-weight: 700; color: #18181b; }
.sched-kcol-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.sched-kcol-count { margin-left: auto; font-family: 'JetBrains Mono', ui-monospace, monospace; font-size: 11px; font-weight: 600; color: #72727b; }
.sched-kcol-body { flex: 1; display: flex; flex-direction: column; gap: 8px; padding: 10px; max-height: 62vh; overflow-y: auto; }
.sched-kcard { display: block; background: #fff; border: 1px solid #e8e8e8; border-radius: 10px; padding: 10px 11px; box-shadow: 0 1px 2px rgba(0,0,0,.04); text-decoration: none; color: inherit; transition: .12s; }
.sched-kcard:hover { border-color: #d4d4d8; color: inherit; }
.sched-kcard-subject { font-size: 12px; font-weight: 700; line-height: 1.35; color: #18181b; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.sched-kcard-recipient { margin-top: 3px; font-size: 11px; color: #52525b; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.sched-kcard-meta { margin-top: 5px; font-size: 10px; color: #72727b; }
.sched-kanban-empty { width: 100%; }
</style>
@endpush

@push('scripts')
<script>
$(function () {
    const dataUrl = @json($dataUrl);
    const bulkUrl = @json($bulkUrl);
    const csrf = $('meta[name="csrf-token"]').attr('content');
    let rows = [];

    // Selector de modo de vista (Lista/Compacta/Kanban) — igual que en
    // HelpdeskEmailActivity, opera sobre `rows` ya cargadas, sin AJAX propio al
    // cambiar de modo. Clave de localStorage distinta a la de ese módulo
    // para no pisar su preferencia (son pantallas y modos distintos).
    const modeStorageKey = 'helpdesktickets:scheduled-view-mode';
    const validModes = ['list', 'compact', 'kanban'];
    const $listView = $('#sched-list-view');
    const $kanbanView = $('#sched-kanban-view');

    function esc(s) { return $('<div>').text(s == null ? '' : s).html(); }

    function ticketUrl(m) {
        // La ficha completa (/full) se eliminó el 8-sep-2026: el listado con
        // el panel superpuesto (?ticket=) es la única vista de detalle.
        return m.ticket_id ? '{{ url("panel/helpdesk/tickets") }}?ticket=' + m.ticket_id : '#';
    }

    // Franja horaria de scheduled_at para el Kanban — "Atrasado" se calcula
    // por hora exacta (ya debería haber salido), el resto por día natural.
    // Fallback a 'later' si no hay fecha parseable: en la práctica todas las
    // filas de esta vista traen scheduled_at (TicketMail::scopeScheduled()
    // exige whereNotNull), pero no se descarta la fila por eso.
    function schedBucket(scheduledAtIso) {
        const when = scheduledAtIso ? new Date(scheduledAtIso) : null;
        if (!when || isNaN(when.getTime())) return 'later';

        const now = new Date();
        if (when < now) return 'overdue';

        const startOfToday = new Date(now.getFullYear(), now.getMonth(), now.getDate());
        const startOfWhenDay = new Date(when.getFullYear(), when.getMonth(), when.getDate());
        const diffDays = Math.round((startOfWhenDay - startOfToday) / 86400000);

        if (diffDays === 0) return 'today';
        if (diffDays <= 6) return 'week';
        return 'later';
    }

    // Orden de columnas por urgencia decreciente + paleta tomada de
    // tickets-app.css (--tkt-danger/--tkt-warn/--tkt-info/--tkt-text-faint):
    // rojo solo para la única columna que de verdad pide acción (correos
    // que ya deberían haber salido).
    const SCHED_BUCKETS = [
        { key: 'overdue', label: 'Atrasado', color: '#dc2626' },
        { key: 'today', label: 'Hoy', color: '#f59e0b' },
        { key: 'week', label: 'Esta semana', color: '#1e3a8a' },
        { key: 'later', label: 'Más adelante', color: '#72727b' },
    ];

    function renderSchedCard(m) {
        const scheduled = m.scheduled_at ? new Date(m.scheduled_at).toLocaleString() : '—';
        const ticketLabel = m.ticket_number || (m.ticket_id ? ('#' + m.ticket_id) : '—');

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
        const byBucket = new Map();
        rows.forEach(function (m) {
            const key = schedBucket(m.scheduled_at);
            if (!byBucket.has(key)) byBucket.set(key, []);
            byBucket.get(key).push(m);
        });

        let html = '';
        SCHED_BUCKETS.forEach(function (bucket) {
            const list = byBucket.get(bucket.key);
            if (!list || !list.length) return;

            // Dentro de cada columna, lo más próximo a salir primero.
            list.sort(function (a, b) { return (a.scheduled_at || '').localeCompare(b.scheduled_at || ''); });

            html += '<div class="sched-kcol">'
                + '<div class="sched-kcol-head">'
                + '<span class="sched-kcol-dot" style="background-color:' + bucket.color + '"></span>'
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
        const $tbody = $('#sched-tbody');
        $tbody.find('tr:not(#sched-loading):not(#sched-empty)').remove();
        $('#sched-loading').addClass('d-none');
        $('#sched-empty').toggleClass('d-none', rows.length > 0);

        rows.forEach(function (m) {
            const scheduled = m.scheduled_at ? new Date(m.scheduled_at).toLocaleString() : '—';

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
        const count = $('.sched-checkbox:checked').length;
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

    let savedMode = 'list';
    try {
        const stored = window.localStorage.getItem(modeStorageKey);
        if (stored && validModes.indexOf(stored) !== -1) savedMode = stored;
    } catch (e) { /* localStorage bloqueado: se queda en 'list' */ }
    applyMode(savedMode);

    load();
});
</script>
@endpush
