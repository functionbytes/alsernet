@once
@push('css')
<style>
    .rt-kpi-inner  { display: flex; flex-direction: column; height: 100%; }
    .rt-kpi-body   { padding: 1rem 1rem 0.5rem; flex: 1; }
    .rt-icon-bottom { height: 60px; padding: 0 1rem 0.75rem; display: flex; align-items: flex-end; }
    .rt-icon       { font-size: 2rem; color: #b10100; }
    .rt-icon-muted { font-size: 2rem; color: #333333; }
</style>
@endpush
@endonce

<div class="card mb-4" id="response-time-widget">
    <div class="card-header">
        <h4 class="card-title fw-semibold mb-0">Tiempo de respuesta</h4>
        <p class="card-subtitle mt-1">Análisis de velocidad en respuestas publicadas</p>
    </div>
    <div class="card-body pt-3">

        <div id="rt-loading" class="text-center py-4">
            <i class="fas fa-spinner fa-spin me-2 text-muted"></i>
            <span class="text-muted">Cargando...</span>
        </div>

        <div id="rt-content" class="d-none">

            <!-- KPI row -->
            <div class="row g-3 mb-4">

                <div class="col-6 col-md-3">
                    <div class="card h-100">
                        <div class="rt-kpi-inner">
                            <div class="rt-kpi-body">
                                <p class="mb-1 fs-3 text-muted">Promedio</p>
                                <h4 class="fw-semibold mb-1" id="rt-avg">—</h4>
                            </div>
                            <div class="rt-icon-bottom">
                                <i class="fas fa-clock rt-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-md-3">
                    <div class="card h-100">
                        <div class="rt-kpi-inner">
                            <div class="rt-kpi-body">
                                <p class="mb-1 fs-3 text-muted">Mediana</p>
                                <h4 class="fw-semibold mb-1" id="rt-median">—</h4>
                            </div>
                            <div class="rt-icon-bottom">
                                <i class="fas fa-chart-bar rt-icon-muted"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-md-3">
                    <div class="card h-100">
                        <div class="rt-kpi-inner">
                            <div class="rt-kpi-body">
                                <p class="mb-1 fs-3 text-muted">Más rápido</p>
                                <h4 class="fw-semibold mb-1" id="rt-fastest">—</h4>
                            </div>
                            <div class="rt-icon-bottom">
                                <i class="fas fa-bolt rt-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-md-3">
                    <div class="card h-100">
                        <div class="rt-kpi-inner">
                            <div class="rt-kpi-body">
                                <p class="mb-1 fs-3 text-muted">Más lento</p>
                                <h4 class="fw-semibold mb-1" id="rt-slowest">—</h4>
                            </div>
                            <div class="rt-icon-bottom">
                                <i class="fas fa-hourglass-end rt-icon-muted"></i>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Distribution -->
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <h4 class="card-title fw-semibold mb-0">Distribución</h4>
                            <p class="card-subtitle mt-1">Por tramo de tiempo</p>
                        </div>
                        <div class="card-body">
                            <div id="rt-distribution"></div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <h4 class="card-title fw-semibold mb-0">Por usuario</h4>
                            <p class="card-subtitle mt-1">Tiempo promedio de respuesta</p>
                        </div>
                        <div class="card-body">
                            <div id="rt-by-user"></div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <h4 class="card-title fw-semibold mb-0">Por día</h4>
                            <p class="card-subtitle mt-1">Velocidad según día de la semana</p>
                        </div>
                        <div class="card-body">
                            <div id="rt-by-dow"></div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    'use strict';

    var rtUrl = '{{ route("reviews.analytics.response-time") }}';

    var RT_BUCKETS = [
        { label: '< 1h',   key: '<1h',  color: '#b10100' },
        { label: '1 – 4h', key: '1-4h', color: '#7b0000' },
        { label: '4 – 24h',key: '4-24h',color: '#c41c1c' },
        { label: '1 – 3d', key: '1-3d', color: '#555555' },
        { label: '> 3 días',key: '>3d', color: '#333333' },
    ];

    function escapeHtml(text) {
        var d = document.createElement('div');
        d.textContent = String(text ?? '');
        return d.innerHTML;
    }

    function barColorForHours(hours) {
        if (hours <= 1)  { return '#b10100'; }
        if (hours <= 4)  { return '#7b0000'; }
        if (hours <= 24) { return '#c41c1c'; }
        return '#333333';
    }

    function renderDistribution(dist) {
        var total = 0;
        Object.values(dist).forEach(function (v) { total += v; });

        var html = '';
        RT_BUCKETS.forEach(function (b) {
            var count = dist[b.key] || 0;
            var pct   = total > 0 ? (count / total) * 100 : 0;
            html +=
                '<div class="mb-3">' +
                '<div class="d-flex justify-content-between mb-1">' +
                '<span class="fs-2">' + b.label + '</span>' +
                '<span class="fw-semibold fs-2">' + count + ' <small class="text-muted">(' + pct.toFixed(0) + '%)</small></span>' +
                '</div>' +
                '<div class="progress" style="height:8px;">' +
                '<div class="progress-bar" role="progressbar" style="width:' + pct.toFixed(1) + '%;background:' + b.color + ';"></div>' +
                '</div></div>';
        });

        document.getElementById('rt-distribution').innerHTML = html || '<p class="text-muted small">Sin datos.</p>';
    }

    function renderByUser(users) {
        var el = document.getElementById('rt-by-user');
        if (!users || users.length === 0) {
            el.innerHTML = '<p class="text-muted small">Sin datos de usuarios.</p>';
            return;
        }

        var maxH = Math.max.apply(null, users.map(function (u) { return u.avg_hours; })) || 1;
        var html = '';
        users.forEach(function (u) {
            var pct   = (u.avg_hours / maxH) * 100;
            var color = barColorForHours(u.avg_hours);
            var hrs   = u.avg_hours;
            var label = hrs < 1 ? Math.round(hrs * 60) + 'm' :
                        (hrs < 24 ? hrs.toFixed(1) + 'h' : (hrs / 24).toFixed(1) + 'd');

            html +=
                '<div class="mb-3">' +
                '<div class="d-flex justify-content-between mb-1">' +
                '<span class="fs-2 fw-semibold">' + escapeHtml(u.user_name) + '</span>' +
                '<span class="fs-2">' + label + ' <small class="text-muted">(' + u.reply_count + ')</small></span>' +
                '</div>' +
                '<div class="progress" style="height:8px;">' +
                '<div class="progress-bar" role="progressbar" style="width:' + pct.toFixed(1) + '%;background:' + color + ';"></div>' +
                '</div></div>';
        });

        el.innerHTML = html;
    }

    function renderByDow(dow) {
        var el = document.getElementById('rt-by-dow');
        if (!dow || dow.length === 0) {
            el.innerHTML = '<p class="text-muted small">Sin datos.</p>';
            return;
        }

        var maxH = Math.max.apply(null, dow.map(function (d) { return d.avg_hours; })) || 1;
        var html = '';
        dow.forEach(function (d) {
            var pct   = (d.avg_hours / maxH) * 100;
            var color = barColorForHours(d.avg_hours);
            var label = d.avg_hours <= 0 ? '—' :
                        (d.avg_hours < 1 ? Math.round(d.avg_hours * 60) + 'm' :
                        (d.avg_hours < 24 ? d.avg_hours.toFixed(1) + 'h' : (d.avg_hours / 24).toFixed(1) + 'd'));

            html +=
                '<div class="mb-3">' +
                '<div class="d-flex justify-content-between mb-1">' +
                '<span class="fs-2 fw-semibold">' + escapeHtml(d.day) + '</span>' +
                '<span class="fs-2">' + label + ' <small class="text-muted">(' + (d.reply_count || 0) + ')</small></span>' +
                '</div>' +
                '<div class="progress" style="height:8px;">' +
                '<div class="progress-bar" role="progressbar" style="width:' + pct.toFixed(1) + '%;background:' + color + ';"></div>' +
                '</div></div>';
        });

        el.innerHTML = html;
    }

    function loadResponseTime() {
        document.getElementById('rt-loading').classList.remove('d-none');
        document.getElementById('rt-content').classList.add('d-none');

        $.get(rtUrl, { days: 30 }, function (res) {
            if (!res.success) { return; }
            var s = res.data.stats;

            document.getElementById('rt-avg').textContent     = s.avg_formatted     || '—';
            document.getElementById('rt-median').textContent  = s.median_formatted  || '—';
            document.getElementById('rt-fastest').textContent = s.fastest_formatted || '—';
            document.getElementById('rt-slowest').textContent = s.slowest_formatted || '—';

            renderDistribution(s.response_time_distribution);
            renderByUser(res.data.by_user);
            renderByDow(res.data.by_day_of_week);

            document.getElementById('rt-loading').classList.add('d-none');
            document.getElementById('rt-content').classList.remove('d-none');
        }).fail(function () {
            document.getElementById('rt-loading').innerHTML =
                '<span class="text-danger"><i class="fas fa-times-circle me-1"></i> Error al cargar datos.</span>';
        });
    }

    $(document).ready(function () {
        loadResponseTime();
    });
}());
</script>
@endpush
