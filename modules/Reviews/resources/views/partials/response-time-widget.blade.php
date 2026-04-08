<div class="card mb-4" id="response-time-widget">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <h4 class="card-title fw-semibold mb-0">Tiempo de respuesta</h4>
            <p class="card-subtitle mt-1">Análisis de velocidad en respuestas publicadas</p>
        </div>
        <select class="form-select form-select-sm" id="rt-days-select" style="width:auto">
            <option value="7">7 días</option>
            <option value="30" selected>30 días</option>
            <option value="90">90 días</option>
        </select>
    </div>
    <div class="card-body pt-3">

        <!-- Loading state -->
        <div id="rt-loading" class="text-center py-4">
            <i class="fas fa-spinner fa-spin me-2 text-muted"></i>
            <span class="text-muted">Cargando...</span>
        </div>

        <!-- Content -->
        <div id="rt-content" class="d-none">

            <!-- KPI row -->
            <div class="row g-3 mb-4" id="rt-kpis">
                <div class="col-6 col-md-3">
                    <div class="border rounded p-3 text-center">
                        <div class="text-muted fs-2 mb-1">Promedio</div>
                        <div class="fw-bold fs-5" id="rt-avg">—</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="border rounded p-3 text-center">
                        <div class="text-muted fs-2 mb-1">Mediana</div>
                        <div class="fw-bold fs-5" id="rt-median">—</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="border rounded p-3 text-center">
                        <div class="text-muted fs-2 mb-1">Más rápido</div>
                        <div class="fw-bold fs-5 text-success" id="rt-fastest">—</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="border rounded p-3 text-center">
                        <div class="text-muted fs-2 mb-1">Más lento</div>
                        <div class="fw-bold fs-5 text-danger" id="rt-slowest">—</div>
                    </div>
                </div>
            </div>

            <!-- Distribution chart (CSS widths) -->
            <h6 class="fw-semibold mb-3">Distribución de tiempos</h6>
            <div id="rt-distribution" class="mb-4">
                <!-- Injected by JS -->
            </div>

            <div class="row g-4">
                <!-- By user -->
                <div class="col-md-6">
                    <h6 class="fw-semibold mb-3">Por usuario</h6>
                    <div id="rt-by-user">
                        <!-- Injected by JS -->
                    </div>
                </div>

                <!-- By day of week -->
                <div class="col-md-6">
                    <h6 class="fw-semibold mb-3">Por día de la semana</h6>
                    <div id="rt-by-dow">
                        <!-- Injected by JS -->
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

    function escapeHtml(text) {
        var d = document.createElement('div');
        d.textContent = String(text ?? '');
        return d.innerHTML;
    }

    function barColor(hours) {
        if (hours <= 1)  { return 'bg-success'; }
        if (hours <= 4)  { return 'bg-info'; }
        if (hours <= 24) { return 'bg-warning'; }
        return 'bg-danger';
    }

    function renderDistribution(dist) {
        var total = 0;
        Object.values(dist).forEach(function (v) { total += v; });

        var buckets = [
            { label: '< 1h',  key: '<1h',  color: 'bg-success' },
            { label: '1 – 4h', key: '1-4h', color: 'bg-info' },
            { label: '4 – 24h', key: '4-24h', color: 'bg-warning' },
            { label: '1 – 3d', key: '1-3d', color: 'bg-orange' },
            { label: '> 3d',  key: '>3d',  color: 'bg-danger' },
        ];

        var html = '';
        buckets.forEach(function (b) {
            var count = dist[b.key] || 0;
            var pct   = total > 0 ? (count / total) * 100 : 0;
            html +=
                '<div class="mb-2">' +
                '<div class="d-flex justify-content-between mb-1">' +
                '<span class="fs-2 text-muted">' + b.label + '</span>' +
                '<span class="fw-semibold">' + count + ' <small class="text-muted">(' + pct.toFixed(0) + '%)</small></span>' +
                '</div>' +
                '<div class="progress" style="height:10px;">' +
                '<div class="progress-bar ' + b.color + '" role="progressbar" style="width:' + pct.toFixed(1) + '%" ' +
                'aria-valuenow="' + pct.toFixed(1) + '" aria-valuemin="0" aria-valuemax="100"></div>' +
                '</div></div>';
        });

        document.getElementById('rt-distribution').innerHTML = html || '<p class="text-muted">Sin datos.</p>';
    }

    function renderByUser(users) {
        var el = document.getElementById('rt-by-user');
        if (!users || users.length === 0) {
            el.innerHTML = '<p class="text-muted fs-2">Sin datos de usuarios.</p>';
            return;
        }

        var maxHours = Math.max.apply(null, users.map(function (u) { return u.avg_hours; })) || 1;
        var html = '';
        users.forEach(function (u) {
            var pct   = (u.avg_hours / maxHours) * 100;
            var color = barColor(u.avg_hours);
            var hrs   = u.avg_hours;
            var label = hrs < 1 ? Math.round(hrs * 60) + 'm' :
                        (hrs < 24 ? hrs.toFixed(1) + 'h' : (hrs / 24).toFixed(1) + 'd');

            html +=
                '<div class="mb-2">' +
                '<div class="d-flex justify-content-between mb-1">' +
                '<span class="fs-2">' + escapeHtml(u.user_name) + ' <small class="text-muted">(' + u.reply_count + ' resp.)</small></span>' +
                '<span class="fw-semibold fs-2">' + label + '</span>' +
                '</div>' +
                '<div class="progress" style="height:8px;">' +
                '<div class="progress-bar ' + color + '" role="progressbar" style="width:' + pct.toFixed(1) + '%"></div>' +
                '</div></div>';
        });

        el.innerHTML = html;
    }

    function renderByDow(dow) {
        var el = document.getElementById('rt-by-dow');
        if (!dow || dow.length === 0) {
            el.innerHTML = '<p class="text-muted fs-2">Sin datos.</p>';
            return;
        }

        var maxHours = Math.max.apply(null, dow.map(function (d) { return d.avg_hours; })) || 1;
        var html = '<table class="table table-sm align-middle">' +
            '<thead><tr><th>Día</th><th>Promedio</th><th style="width:40%">Gráfico</th><th class="text-center">Resp.</th></tr></thead><tbody>';

        dow.forEach(function (d) {
            var pct   = (d.avg_hours / maxHours) * 100;
            var color = barColor(d.avg_hours);
            var label = d.avg_hours <= 0 ? '—' :
                        (d.avg_hours < 1 ? Math.round(d.avg_hours * 60) + 'm' :
                        (d.avg_hours < 24 ? d.avg_hours.toFixed(1) + 'h' : (d.avg_hours / 24).toFixed(1) + 'd'));

            html +=
                '<tr>' +
                '<td class="fw-semibold">' + escapeHtml(d.day) + '</td>' +
                '<td>' + label + '</td>' +
                '<td>' +
                '<div class="progress" style="height:8px;">' +
                '<div class="progress-bar ' + color + '" role="progressbar" style="width:' + pct.toFixed(1) + '%"></div>' +
                '</div></td>' +
                '<td class="text-center text-muted fs-2">' + (d.reply_count || 0) + '</td>' +
                '</tr>';
        });

        html += '</tbody></table>';
        el.innerHTML = html;
    }

    function loadResponseTime(days) {
        document.getElementById('rt-loading').classList.remove('d-none');
        document.getElementById('rt-content').classList.add('d-none');

        $.get(rtUrl, { days: days }, function (res) {
            if (!res.success) { return; }
            var s = res.data.stats;

            document.getElementById('rt-avg').textContent    = s.avg_formatted    || '—';
            document.getElementById('rt-median').textContent = s.median_formatted || '—';
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
        var days = parseInt($('#rt-days-select').val()) || 30;
        loadResponseTime(days);

        $('#rt-days-select').on('change', function () {
            loadResponseTime(parseInt($(this).val()) || 30);
        });
    });
}());
</script>
@endpush
