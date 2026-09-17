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
                <i class="fas fa-circle-info me-1"></i>{{ __('helpdeskanalytics::messages.sampled_note', ['count' => $customerSegmentLimit]) }}
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
{{-- Config/i18n inline (datos, no lógica): la lógica real vive en
     public/js/dashboard.js, que no tiene acceso a __()/route(). --}}
<script>
window.HelpdeskAnalyticsDashboard = {
    dataUrl: @json(route('helpdeskanalytics.data')),
    i18n: {
        noDataRange: @json(__('helpdeskanalytics::messages.no_data_range')),
        created: @json(__('helpdeskanalytics::messages.created')),
        closed: @json(__('helpdeskanalytics::messages.closed')),
        healthHealthy: @json(__('helpdeskanalytics::messages.health_healthy')),
        healthNeutral: @json(__('helpdeskanalytics::messages.health_neutral')),
        healthAtRisk: @json(__('helpdeskanalytics::messages.health_at_risk')),
        loadError: @json(__('helpdeskanalytics::messages.load_error')),
    },
};
</script>
<script src="{{ asset('modules/helpdeskanalytics/js/dashboard.js') }}?v={{ @filemtime(public_path('modules/helpdeskanalytics/js/dashboard.js')) }}" defer></script>
@endpush
