@extends('layouts.theme')
@section('title', 'Perfil — '.$customer->name)

@push('css')
<style>
    .lc-header { background: linear-gradient(135deg, #90bb13 0%, #7b0000 100%); color: white; padding: 1.5rem 2rem; border-radius: 12px; margin-bottom: 1.5rem; }
    .kpi-card { padding: 1.25rem; border-radius: 12px; }
    .kpi-card .kpi-value { font-size: 1.75rem; font-weight: 700; }
    .kpi-card .kpi-label { font-size: .75rem; text-transform: uppercase; color: #888; }
    .seg-cold { color: #6c757d } .seg-warm { color: #ffa500 } .seg-hot { color: #90bb13 }
    .event-row { padding: .5rem .75rem; border-left: 3px solid #90bb13; background: #f5f6f8; margin-bottom: .25rem; border-radius: 0 6px 6px 0; }
</style>
@endpush

@section('content')
    <div class="lc-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h4><i class="fas fa-user me-2"></i>{{ $customer->name ?? 'Visitante' }}</h4>
                <p>{{ $customer->email }} · ID #{{ $customer->id }}</p>
            </div>
            <a href="{{ url()->previous() }}" class="btn btn-light btn-sm"><i class="fas fa-arrow-left me-1"></i>Volver</a>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-3"><div class="card kpi-card"><div class="kpi-label">Sesiones</div><div class="kpi-value" id="kpiSessions">—</div></div></div>
        <div class="col-md-3"><div class="card kpi-card"><div class="kpi-label">Eventos</div><div class="kpi-value" id="kpiEvents">—</div></div></div>
        <div class="col-md-3"><div class="card kpi-card"><div class="kpi-label">Score actual</div><div class="kpi-value" id="kpiScore">—</div></div></div>
        <div class="col-md-3"><div class="card kpi-card"><div class="kpi-label">Segmento</div><div class="kpi-value" id="kpiSegment">—</div></div></div>
    </div>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="fw-semibold mb-3"><i class="fas fa-bullseye me-1"></i>Atribución</h6>
                    <dl class="row mb-0" id="attributionList"><dt class="col-sm-12 text-muted">Cargando...</dt></dl>
                </div>
            </div>
            <div class="card mt-3">
                <div class="card-body">
                    <h6 class="fw-semibold mb-3"><i class="fas fa-clock me-1"></i>Sesiones recientes</h6>
                    <div id="sessionsList"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <h6 class="fw-semibold mb-3"><i class="fas fa-stream me-1"></i>Timeline de eventos</h6>
                    <div id="eventsList"></div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
<script>
$(function () {
    const url = '{{ route('manager.engagement.customer-profile.data', ['customer' => $customer->id]) }}';

    $.get(url).done(r => {
        const d = r.data;
        $('#kpiSessions').text(d.kpis.sessions_total);
        $('#kpiEvents').text(d.kpis.events_total);
        $('#kpiScore').text(d.kpis.current_score);
        $('#kpiSegment').html(`<span class="seg-${d.kpis.current_segment}">${d.kpis.current_segment}</span>`);

        if (d.attribution) {
            $('#attributionList').html(Object.entries(d.attribution).map(([k, v]) =>
                `<dt class="col-sm-5">${k}</dt><dd class="col-sm-7">${v ?? '—'}</dd>`
            ).join(''));
        } else {
            $('#attributionList').html('<dt class="col-sm-12 text-muted">Sin datos UTM</dt>');
        }

        $('#sessionsList').html(d.sessions.length === 0
            ? '<p class="text-muted small">Sin sesiones</p>'
            : d.sessions.map(s => `
                <div class="small mb-2 pb-2 border-bottom">
                    <code>${s.session_token}</code><br>
                    <span class="text-muted">${s.current_url ?? ''}</span><br>
                    <span class="text-muted">${new Date(s.last_activity_at).toLocaleString()}</span>
                </div>`).join(''));

        $('#eventsList').html(d.events.length === 0
            ? '<p class="text-muted">Sin eventos</p>'
            : d.events.map(e => `
                <div class="event-row">
                    <strong>${e.name}</strong>
                    ${e.platform ? `<span class="badge bg-light text-dark ms-1">${e.platform}</span>` : ''}
                    <small class="text-muted float-end">${new Date(e.occurred_at).toLocaleString()}</small>
                </div>`).join(''));
    });
});
</script>
@endpush
