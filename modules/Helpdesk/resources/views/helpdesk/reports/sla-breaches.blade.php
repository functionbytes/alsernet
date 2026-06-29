@extends('layouts.theme')
@section('title', 'Incumplimientos SLA · Helpdesk')
@section('content')

<div class="d-flex align-items-center gap-3 mb-4 flex-wrap">
    <h1 class="h4 mb-0 fw-bold">
        <i class="fas fa-gauge-high text-danger me-2"></i>Incumplimientos SLA
    </h1>
    <p class="text-muted small mb-0 w-100 order-3 mt-1">
        Tickets con SLA de resolución incumplido agrupados por agente, y los próximos vencimientos en las siguientes 24 horas.
    </p>
    <div class="ms-auto order-2">
        <button id="btn-refresh" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-rotate me-1"></i>Actualizar
        </button>
    </div>
</div>

{{-- Unavailable state (HelpdeskTickets disabled) --}}
<div id="state-unavailable" class="card border-0 shadow-sm d-none">
    <div class="card-body text-center py-5 text-muted">
        <i class="fas fa-plug-circle-xmark fa-2x mb-2 d-block"></i>
        El módulo de tickets no está disponible. No se pueden mostrar los incumplimientos SLA.
    </div>
</div>

<div id="state-content" class="d-none">

    {{-- Breached by agent --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-transparent border-0 pb-0 pt-3">
            <h6 class="fw-semibold mb-0">
                <i class="fas fa-triangle-exclamation text-danger me-1"></i>
                SLA incumplido por agente
            </h6>
        </div>
        <div class="card-body" id="breached-by-agent">
            <p class="text-muted mb-0">Cargando datos...</p>
        </div>
    </div>

    {{-- Upcoming breaches --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent border-0 pb-0 pt-3">
            <h6 class="fw-semibold mb-0">
                <i class="fas fa-clock text-warning me-1"></i>
                Próximos vencimientos (24 h)
            </h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Ticket</th>
                            <th>Asunto</th>
                            <th>Vence</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-upcoming">
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">Cargando datos...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script>
(function () {
    const dataUrl = "{{ route('manager.helpdesk.reports.sla-breaches.data') }}";

    function esc(value) {
        return $('<span>').text(value == null ? '' : value).html();
    }

    function formatDateTime(iso) {
        if (!iso) {
            return '—';
        }
        return new Date(iso).toLocaleString('es-ES', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' });
    }

    function formatOverdue(minutes) {
        if (!minutes || minutes <= 0) {
            return '';
        }
        const hours = Math.floor(minutes / 60);
        const mins = minutes % 60;
        const label = hours > 0 ? (hours + 'h ' + mins + 'm') : (mins + 'm');
        return ' <span class="badge bg-danger ms-1">' + label + ' de retraso</span>';
    }

    function ticketLink(row) {
        const label = esc(row.number || ('#' + row.id));
        if (!row.url) {
            return '<span class="fw-semibold">' + label + '</span>';
        }
        return '<a href="' + row.url + '" class="fw-semibold text-decoration-none">' + label + '</a>';
    }

    function renderBreachedByAgent(groups) {
        const container = $('#breached-by-agent');
        container.empty();

        if (!groups || groups.length === 0) {
            container.html(
                '<div class="text-center text-muted py-4">' +
                '<i class="fas fa-circle-check fa-2x mb-2 d-block text-primary"></i>' +
                'No hay tickets con SLA incumplido.' +
                '</div>'
            );
            return;
        }

        groups.forEach(function (group) {
            const tickets = group.tickets || [];
            let rows = '';

            tickets.forEach(function (t) {
                rows +=
                    '<tr>' +
                    '<td>' + ticketLink(t) + '</td>' +
                    '<td class="text-muted">' + esc(t.subject) + '</td>' +
                    '<td class="text-nowrap small text-muted">' + formatDateTime(t.dueAt) + formatOverdue(t.overdueMinutes) + '</td>' +
                    '</tr>';
            });

            container.append(
                '<div class="mb-3">' +
                '<div class="d-flex align-items-center gap-2 mb-2">' +
                '<span class="fw-semibold">' + esc(group.agentName) + '</span>' +
                '<span class="badge bg-danger">' + group.count + '</span>' +
                '</div>' +
                '<div class="table-responsive">' +
                '<table class="table table-sm table-hover align-middle mb-0">' +
                '<thead class="table-light"><tr><th>Ticket</th><th>Asunto</th><th>Vencía</th></tr></thead>' +
                '<tbody>' + rows + '</tbody>' +
                '</table></div></div>'
            );
        });
    }

    function renderUpcoming(upcoming) {
        const tbody = $('#tbody-upcoming');
        tbody.empty();

        if (!upcoming || upcoming.length === 0) {
            tbody.html('<tr><td colspan="4" class="text-center text-muted py-4">Sin vencimientos en las próximas 24 horas.</td></tr>');
            return;
        }

        upcoming.forEach(function (t) {
            tbody.append(
                '<tr>' +
                '<td>' + ticketLink(t) + '</td>' +
                '<td class="text-muted">' + esc(t.subject) + '</td>' +
                '<td class="text-nowrap small text-muted">' + formatDateTime(t.dueAt) + '</td>' +
                '<td class="text-end">' + (t.url ? '<a href="' + t.url + '" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye me-1"></i>Ver</a>' : '<span class="text-muted small">—</span>') + '</td>' +
                '</tr>'
            );
        });
    }

    function load() {
        $('#state-unavailable').addClass('d-none');
        $('#state-content').removeClass('d-none');
        $('#breached-by-agent').html('<p class="text-muted mb-0">Cargando datos...</p>');
        $('#tbody-upcoming').html('<tr><td colspan="4" class="text-center text-muted py-4">Cargando datos...</td></tr>');

        $.getJSON(dataUrl).done(function (data) {
            if (!data.available) {
                $('#state-content').addClass('d-none');
                $('#state-unavailable').removeClass('d-none');
                return;
            }

            renderBreachedByAgent(data.breachedByAgent);
            renderUpcoming(data.upcoming);
        }).fail(function () {
            toastr.error('Error al cargar el reporte de incumplimientos SLA.');
        });
    }

    $('#btn-refresh').on('click', load);

    load();
})();
</script>
@endpush
@endsection
