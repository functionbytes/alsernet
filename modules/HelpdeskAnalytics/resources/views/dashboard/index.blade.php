@extends('layouts.theme')
@section('title', 'Analytics · Helpdesk')
@section('page_header')
    @include('core::components.card', ['title' => 'Analytics · Helpdesk'])
@endsection

@section('content')

<div class="d-flex align-items-center gap-3 mb-4 flex-wrap">
    <h1 class="h4 mb-0 fw-bold">
        <i class="fas fa-chart-line text-primary me-2"></i>{{ __('helpdeskanalytics::messages.title') }}
    </h1>
    <p class="text-muted small mb-0 w-100 order-3 mt-1">
        {{ __('helpdeskanalytics::messages.subtitle') }}
    </p>
    <form id="filters" class="ms-auto order-2 d-flex gap-2 align-items-end">
        <div>
            <label class="form-label small mb-1" for="f-from">{{ __('helpdeskanalytics::messages.from') }}</label>
            <input type="date" id="f-from" name="from" class="form-control form-control-sm">
        </div>
        <div>
            <label class="form-label small mb-1" for="f-to">{{ __('helpdeskanalytics::messages.to') }}</label>
            <input type="date" id="f-to" name="to" class="form-control form-control-sm">
        </div>
        <button type="submit" class="btn btn-sm btn-primary">
            <i class="fas fa-filter me-1"></i>{{ __('helpdeskanalytics::messages.apply') }}
        </button>
    </form>
</div>

<div class="row g-3 mb-4" id="kpi-cards">
    <div class="col-6 col-lg">
        <div class="card border-0 shadow-sm"><div class="card-body">
            <div class="text-muted small">{{ __('helpdeskanalytics::messages.conversations') }}</div><div class="h4 fw-bold mb-0" id="kpi-conversations">—</div>
        </div></div>
    </div>
    <div class="col-6 col-lg">
        <div class="card border-0 shadow-sm"><div class="card-body">
            <div class="text-muted small">{{ __('helpdeskanalytics::messages.closed') }}</div><div class="h4 fw-bold mb-0" id="kpi-closed">—</div>
        </div></div>
    </div>
    <div class="col-6 col-lg">
        <div class="card border-0 shadow-sm"><div class="card-body">
            <div class="text-muted small">{{ __('helpdeskanalytics::messages.open_now') }}</div><div class="h4 fw-bold mb-0" id="kpi-open">—</div>
        </div></div>
    </div>
    <div class="col-6 col-lg">
        <div class="card border-0 shadow-sm"><div class="card-body">
            <div class="text-muted small">{{ __('helpdeskanalytics::messages.first_response_avg') }}</div><div class="h4 fw-bold mb-0" id="kpi-frt">—</div>
        </div></div>
    </div>
    <div class="col-6 col-lg">
        <div class="card border-0 shadow-sm"><div class="card-body">
            <div class="text-muted small">{{ __('helpdeskanalytics::messages.csat_avg') }}</div><div class="h4 fw-bold mb-0" id="kpi-csat">—</div>
        </div></div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <h6 class="fw-semibold mb-3">{{ __('helpdeskanalytics::messages.daily_trend') }}</h6>
            <canvas id="chart-trends" height="120"></canvas>
        </div></div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <h6 class="fw-semibold mb-3">{{ __('helpdeskanalytics::messages.by_channel') }}</h6>
            <canvas id="chart-channels" height="200"></canvas>
        </div></div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <h6 class="fw-semibold mb-3">{{ __('helpdeskanalytics::messages.customer_health') }}</h6>
            <canvas id="chart-customers" height="200"></canvas>
            <small id="cust-sampled" class="text-muted d-none d-block mt-2">
                <i class="fas fa-circle-info me-1"></i>{{ __('helpdeskanalytics::messages.sampled_note', ['count' => 5000]) }}
            </small>
        </div></div>
    </div>
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body pb-0"><h6 class="fw-semibold mb-0">{{ __('helpdeskanalytics::messages.agent_performance') }}</h6></div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead><tr>
                        <th>{{ __('helpdeskanalytics::messages.agent') }}</th>
                        <th>{{ __('helpdeskanalytics::messages.closed') }}</th>
                        <th>CSAT</th>
                        <th>{{ __('helpdeskanalytics::messages.first_response_short') }}</th>
                        <th>{{ __('helpdeskanalytics::messages.messages') }}</th>
                        <th>{{ __('helpdeskanalytics::messages.agent_tickets_closed') }}</th>
                        <th>{{ __('helpdeskanalytics::messages.agent_tickets_frt') }}</th>
                        <th>{{ __('helpdeskanalytics::messages.agent_tickets_resolution') }}</th>
                    </tr></thead>
                    <tbody id="agent-rows"><tr><td colspan="8" class="text-center text-muted py-3">{{ __('helpdeskanalytics::messages.loading') }}</td></tr></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mt-1">
    <div class="col-12">
        <h6 class="fw-semibold mb-3 mt-2">
            <i class="fas fa-ticket text-primary me-1"></i>{{ __('helpdeskanalytics::messages.tickets_title') }}
        </h6>
    </div>
</div>

<div class="row g-3 mb-4" id="ticket-kpi-cards">
    <div class="col-6 col-lg">
        <div class="card border-0 shadow-sm"><div class="card-body">
            <div class="text-muted small">{{ __('helpdeskanalytics::messages.tickets_created') }}</div><div class="h4 fw-bold mb-0" id="kpi-tickets-created">—</div>
        </div></div>
    </div>
    <div class="col-6 col-lg">
        <div class="card border-0 shadow-sm"><div class="card-body">
            <div class="text-muted small">{{ __('helpdeskanalytics::messages.tickets_closed') }}</div><div class="h4 fw-bold mb-0" id="kpi-tickets-closed">—</div>
        </div></div>
    </div>
    <div class="col-6 col-lg">
        <div class="card border-0 shadow-sm"><div class="card-body">
            <div class="text-muted small">{{ __('helpdeskanalytics::messages.tickets_resolved') }}</div><div class="h4 fw-bold mb-0" id="kpi-tickets-resolved">—</div>
        </div></div>
    </div>
    <div class="col-6 col-lg">
        <div class="card border-0 shadow-sm"><div class="card-body">
            <div class="text-muted small">{{ __('helpdeskanalytics::messages.tickets_sla_breached') }}</div><div class="h4 fw-bold mb-0" id="kpi-tickets-sla-breached">—</div>
        </div></div>
    </div>
    <div class="col-6 col-lg">
        <div class="card border-0 shadow-sm"><div class="card-body">
            <div class="text-muted small">{{ __('helpdeskanalytics::messages.tickets_unassigned') }}</div><div class="h4 fw-bold mb-0" id="kpi-tickets-unassigned">—</div>
        </div></div>
    </div>
    <div class="col-6 col-lg">
        <div class="card border-0 shadow-sm"><div class="card-body">
            <div class="text-muted small">{{ __('helpdeskanalytics::messages.tickets_first_response_avg') }}</div><div class="h4 fw-bold mb-0" id="kpi-tickets-frt">—</div>
        </div></div>
    </div>
    <div class="col-6 col-lg">
        <div class="card border-0 shadow-sm"><div class="card-body">
            <div class="text-muted small">{{ __('helpdeskanalytics::messages.tickets_resolution_avg') }}</div><div class="h4 fw-bold mb-0" id="kpi-tickets-resolution">—</div>
        </div></div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body pb-0"><h6 class="fw-semibold mb-0">{{ __('helpdeskanalytics::messages.tickets_by_priority') }}</h6></div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead><tr><th>{{ __('helpdeskanalytics::messages.priority') }}</th><th>{{ __('helpdeskanalytics::messages.tickets_title') }}</th></tr></thead>
                    <tbody id="ticket-priority-rows"><tr><td colspan="2" class="text-center text-muted py-3">{{ __('helpdeskanalytics::messages.loading') }}</td></tr></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
$(function () {
    const dataUrl = @json(route('helpdeskanalytics.data'));
    const charts = {};

    function destroyChart(id) {
        if (charts[id]) { charts[id].destroy(); delete charts[id]; }
    }

    function secs(s) {
        if (!s) return '—';
        if (s < 60) return s + 's';
        if (s < 3600) return Math.round(s / 60) + 'm';
        return Math.round(s / 3600) + 'h';
    }

    function renderAgents(rows) {
        if (!rows.length) {
            $('#agent-rows').html('<tr><td colspan="8" class="text-center text-muted py-3">{{ __('helpdeskanalytics::messages.no_data_range') }}</td></tr>');
            return;
        }
        $('#agent-rows').html(rows.map(function (a) {
            return '<tr>' +
                '<td>' + $('<div>').text(a.name).html() + '</td>' +
                '<td>' + a.closed_count + '</td>' +
                '<td>' + (a.csat_avg || '—') + '</td>' +
                '<td>' + secs(a.avg_response_seconds) + '</td>' +
                '<td>' + a.message_count + '</td>' +
                '<td>' + (a.ticket_closed_count || 0) + '</td>' +
                '<td>' + minutes(a.ticket_avg_first_response_minutes) + '</td>' +
                '<td>' + minutes(a.ticket_avg_resolution_minutes) + '</td>' +
                '</tr>';
        }).join(''));
    }

    function minutes(m) {
        if (!m) return '—';
        if (m < 60) return m + 'm';
        return Math.round(m / 60) + 'h';
    }

    function renderTickets(t) {
        t = t || {};
        $('#kpi-tickets-created').text(t.total_created ?? 0);
        $('#kpi-tickets-closed').text(t.total_closed ?? 0);
        $('#kpi-tickets-resolved').text(t.total_resolved ?? 0);
        $('#kpi-tickets-sla-breached').text(t.sla_breached ?? 0);
        $('#kpi-tickets-unassigned').text(t.unassigned ?? 0);
        $('#kpi-tickets-frt').text(minutes(t.avg_first_response_minutes));
        $('#kpi-tickets-resolution').text(minutes(t.avg_resolution_minutes));

        const byPriority = t.by_priority || [];

        if (!byPriority.length) {
            $('#ticket-priority-rows').html('<tr><td colspan="2" class="text-center text-muted py-3">{{ __('helpdeskanalytics::messages.no_data_range') }}</td></tr>');
            return;
        }
        $('#ticket-priority-rows').html(byPriority.map(function (p) {
            return '<tr>' +
                '<td>' + $('<div>').text(p.priority).html() + '</td>' +
                '<td>' + p.count + '</td>' +
                '</tr>';
        }).join(''));
    }

    function load() {
        $.get(dataUrl, $('#filters').serialize()).done(function (res) {
            const o = res.overview || {};
            $('#kpi-conversations').text(o.conversations ?? 0);
            $('#kpi-closed').text(o.closed ?? 0);
            $('#kpi-open').text(o.open ?? 0);
            $('#kpi-frt').text(secs(o.avg_first_response_seconds));
            $('#kpi-csat').text(o.csat_avg ?? 0);

            const trends = res.trends || [];
            destroyChart('chart-trends');
            charts['chart-trends'] = new Chart(document.getElementById('chart-trends'), {
                type: 'line',
                data: {
                    labels: trends.map(t => t.date),
                    datasets: [
                        { label: '{{ __("helpdeskanalytics::messages.created") }}', data: trends.map(t => t.created), borderColor: '#90bb13', tension: 0.3 },
                        { label: '{{ __("helpdeskanalytics::messages.closed") }}', data: trends.map(t => t.closed), borderColor: '#6c757d', tension: 0.3 },
                    ],
                },
                options: { responsive: true, maintainAspectRatio: false },
            });

            const channels = res.channels || [];
            destroyChart('chart-channels');
            charts['chart-channels'] = new Chart(document.getElementById('chart-channels'), {
                type: 'doughnut',
                data: { labels: channels.map(c => c.channel), datasets: [{ data: channels.map(c => c.count) }] },
                options: { responsive: true, maintainAspectRatio: false },
            });

            const cust = res.customers || {};
            destroyChart('chart-customers');
            charts['chart-customers'] = new Chart(document.getElementById('chart-customers'), {
                type: 'doughnut',
                data: {
                    labels: ['{{ __("helpdeskanalytics::messages.health_healthy") }}', '{{ __("helpdeskanalytics::messages.health_neutral") }}', '{{ __("helpdeskanalytics::messages.health_at_risk") }}'],
                    datasets: [{ data: [cust.healthy || 0, cust.neutral || 0, cust.at_risk || 0], backgroundColor: ['#13C672', '#FEC90F', '#FA896B'] }],
                },
                options: { responsive: true, maintainAspectRatio: false },
            });
            $('#cust-sampled').toggleClass('d-none', !cust.sampled);

            renderAgents(res.agents || []);
            renderTickets(res.tickets);
        }).fail(function () {
            toastr.error('No se pudieron cargar las metricas.');
        });
    }

    $('#filters').on('submit', function (e) { e.preventDefault(); load(); });
    load();
});
</script>
@endpush
