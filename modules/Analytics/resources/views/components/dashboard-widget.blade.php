@if(class_exists('Modules\Analytics\Analytics') && setting('google_analytics_enable') && setting('google_analytics_property_id'))
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span class="fw-semibold">
            <i class="fas fa-chart-line me-2 text-primary"></i>Analytics — últimos 7 días
        </span>
        <a href="{{ route('analytics.dashboard') }}" class="btn btn-sm btn-outline-primary">
            Ver detalle
        </a>
    </div>
    <div class="card-body">
        <div class="row g-3" id="analytics-kpi-widget">
            <div class="col-6 col-md-3 text-center">
                <div class="fs-4 fw-bold" id="kpi-sessions">
                    <span class="spinner-border spinner-border-sm text-primary"></span>
                </div>
                <div class="text-muted">Sesiones</div>
            </div>
            <div class="col-6 col-md-3 text-center">
                <div class="fs-4 fw-bold" id="kpi-users">
                    <span class="spinner-border spinner-border-sm text-primary"></span>
                </div>
                <div class="text-muted">Usuarios</div>
            </div>
            <div class="col-6 col-md-3 text-center">
                <div class="fs-4 fw-bold" id="kpi-pageviews">
                    <span class="spinner-border spinner-border-sm text-primary"></span>
                </div>
                <div class="text-muted">Páginas vistas</div>
            </div>
            <div class="col-6 col-md-3 text-center">
                <div class="fs-4 fw-bold" id="kpi-bounce">
                    <span class="spinner-border spinner-border-sm text-primary"></span>
                </div>
                <div class="text-muted">Rebote</div>
            </div>
        </div>
    </div>
</div>

<script>
$(function () {
    $.get('{{ route("api.analytics.overview") }}', { range: 'last_7_days' })
        .done(function (res) {
            if (!res.status || !res.data || !res.data.totals) {
                $('#analytics-kpi-widget').html('<p class="text-muted text-center mb-0">Sin datos disponibles</p>');
                return;
            }

            var t = res.data.totals;
            $('#kpi-sessions').text(t.sessions != null ? Number(t.sessions).toLocaleString() : '—');
            $('#kpi-users').text(t.totalUsers != null ? Number(t.totalUsers).toLocaleString() : '—');
            $('#kpi-pageviews').text(t.screenPageViews != null ? Number(t.screenPageViews).toLocaleString() : '—');
            $('#kpi-bounce').text(t.bounceRate != null ? parseFloat(t.bounceRate).toFixed(1) + '%' : '—');
        })
        .fail(function () {
            $('#analytics-kpi-widget').html('<p class="text-muted text-center mb-0">No disponible</p>');
        });
});
</script>
@endif
