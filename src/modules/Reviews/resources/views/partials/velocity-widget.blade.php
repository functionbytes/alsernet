@once
@push('css')
<style>
    .velocity-mini-bars { height: 48px; display: flex; align-items: flex-end; gap: 2px; margin-top: .75rem; }
    .velocity-spark-bar { flex: 1; border-radius: 2px 2px 0 0; min-height: 3px; }
    .velocity-big-icon  { font-size: 1.75rem; }
    .vcmp-icon          { font-size: .6rem; }
</style>
@endpush
@endonce

<div class="card mb-4" id="velocity-widget">
    <div class="card-header">
        <h4 class="card-title fw-semibold mb-0">Velocidad de reseñas</h4>
        <p class="card-subtitle mt-1">Tendencia y frecuencia de nuevas reseñas</p>
    </div>
    <div class="card-body pt-3">

        <!-- Loading state -->
        <div id="velocity-loading" class="text-center py-4">
            <i class="fas fa-spinner fa-spin me-2 text-muted"></i>
            <span class="text-muted">Cargando...</span>
        </div>

        <!-- Content -->
        <div id="velocity-content" class="d-none">

            <!-- KPI Cards -->
            <div class="row g-3 mb-4">

                {{-- Últimos 30 días --}}
                <div class="col-6 col-md-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <p class="text-muted fw-semibold mb-2 fs-3">Últimos 30 días</p>
                            <h4 class="fw-semibold mb-2" id="velocity-current">—</h4>
                            <div class="d-flex align-items-center" id="velocity-current-cmp"></div>
                            <div class="velocity-mini-bars" id="velocity-mini-current"></div>
                        </div>
                    </div>
                </div>

                {{-- 30 días anteriores --}}
                <div class="col-6 col-md-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <p class="text-muted fw-semibold mb-2 fs-3">30 días anteriores</p>
                            <h4 class="fw-semibold mb-2" id="velocity-previous">—</h4>
                            <div class="d-flex align-items-center">
                                <span class="me-1 rounded-circle bg-light-secondary round-20 d-flex align-items-center justify-content-center">
                                    <i class="fas fa-clock text-muted vcmp-icon"></i>
                                </span>
                                <p class="fs-3 mb-0 text-muted">período comparado</p>
                            </div>
                            <div class="velocity-mini-bars" id="velocity-mini-previous"></div>
                        </div>
                    </div>
                </div>

                {{-- Variación --}}
                <div class="col-6 col-md-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <p class="text-muted fw-semibold mb-2 fs-3">Variación</p>
                            <h4 class="fw-semibold mb-3" id="velocity-change">—</h4>
                            <div class="d-flex align-items-center">
                                <i class="fas fa-arrow-trend-up velocity-big-icon text-danger" id="velocity-change-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Día con más reseñas --}}
                <div class="col-6 col-md-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <p class="text-muted fw-semibold mb-2 fs-3">Día con más reseñas</p>
                            <h4 class="fw-semibold mb-3 text-truncate" id="velocity-peak-day">—</h4>
                            <div class="d-flex align-items-center">
                                <i class="fas fa-crown velocity-big-icon text-danger"></i>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Last 14 days bar chart (CSS) -->
            <h6 class="fw-semibold mb-3">Últimos 14 días</h6>
            <div id="velocity-bar-chart" class="mb-1" style="height:80px; display:flex; align-items:flex-end; gap:3px;">
                <!-- Injected by JS -->
            </div>
            <div id="velocity-bar-labels" class="d-flex gap-0 mb-4" style="font-size:0.65rem; color:#9aa4b0; overflow:hidden;">
                <!-- Injected by JS -->
            </div>

            <!-- Peak days breakdown -->
            <h6 class="fw-semibold mb-3">Reseñas por día de la semana</h6>
            <div id="velocity-peak-days">
                <!-- Injected by JS -->
            </div>

        </div>

    </div>
</div>

@push('scripts')
<script>
(function () {
    'use strict';

    var velocityUrl = '{{ route("reviews.analytics.velocity") }}';
    var DAY_LABELS_ES = { Monday: 'Lun', Tuesday: 'Mar', Wednesday: 'Mie', Thursday: 'Jue', Friday: 'Vie', Saturday: 'Sab', Sunday: 'Dom' };

    function escapeHtml(text) {
        var d = document.createElement('div');
        d.textContent = String(text ?? '');
        return d.innerHTML;
    }

    function renderMiniBars(containerId, counts, color) {
        var slice = counts.slice(-10);
        var max   = Math.max.apply(null, slice) || 1;
        var html  = '';
        slice.forEach(function (count) {
            var pct = (count / max) * 100;
            var bg  = count === 0 ? '#e9ecef' : color;
            html += '<div class="velocity-spark-bar" style="height:' + pct.toFixed(1) + '%;background:' + bg + ';"></div>';
        });
        var el = document.getElementById(containerId);
        if (el) { el.innerHTML = html; }
    }

    function renderCmpBadge(containerId, current, previous) {
        var el = document.getElementById(containerId);
        if (!el || !previous) { return; }
        var pct   = ((current - previous) / previous * 100).toFixed(1);
        var isUp  = parseFloat(pct) >= 0;
        var sign  = isUp ? '+' : '';
        var bgCls = isUp ? 'bg-light-danger' : 'bg-light-secondary';
        var iCls  = isUp ? 'fa-arrow-up text-danger' : 'fa-arrow-down text-muted';

        el.innerHTML =
            '<span class="me-1 rounded-circle ' + bgCls + ' round-20 d-flex align-items-center justify-content-center">' +
            '<i class="fas ' + iCls + ' vcmp-icon"></i>' +
            '</span>' +
            '<p class="text-dark me-1 fs-3 mb-0">' + sign + pct + '%</p>' +
            '<p class="fs-3 mb-0 text-muted">vs anterior</p>';
    }

    function renderBarChart(dates, counts) {
        var last14dates  = dates.slice(-14);
        var last14counts = counts.slice(-14);
        var maxCount = Math.max.apply(null, last14counts) || 1;

        var chartHtml = '';
        var labelHtml = '';

        last14dates.forEach(function (date, i) {
            var count = last14counts[i];
            var pct   = (count / maxCount) * 100;
            var color = count === 0 ? '#e9ecef' : '#90bb13';
            var title = date + ': ' + count + ' reseña' + (count !== 1 ? 's' : '');

            chartHtml += '<div style="flex:1;background:' + color + ';height:' + pct.toFixed(1) + '%;min-height:2px;border-radius:2px 2px 0 0;cursor:default;" title="' + escapeHtml(title) + '"></div>';

            var parts = date.split('-');
            labelHtml += '<div style="flex:1;text-align:center;overflow:hidden;white-space:nowrap;">' + (parts[2] || '') + '</div>';
        });

        document.getElementById('velocity-bar-chart').innerHTML = chartHtml || '<p class="text-muted">Sin datos.</p>';
        document.getElementById('velocity-bar-labels').innerHTML = labelHtml;
    }

    function renderPeakDays(peakDays) {
        var el      = document.getElementById('velocity-peak-days');
        var max     = Math.max.apply(null, Object.values(peakDays)) || 1;
        var bestDay = null;
        var bestVal = -1;
        var html    = '';

        Object.entries(peakDays).forEach(function (entry) {
            if (entry[1] > bestVal) { bestVal = entry[1]; bestDay = entry[0]; }
        });

        Object.entries(peakDays).forEach(function (entry) {
            var day    = entry[0];
            var count  = entry[1];
            var pct    = (count / max) * 100;
            var isBest = day === bestDay;
            var barColor   = isBest ? '#90bb13' : '#e8e8e8';
            var labelStyle = isBest ? 'color:#90bb13;font-weight:700;' : '';
            var crown      = isBest ? ' <i class="fas fa-crown" style="color:#90bb13;font-size:0.7rem;"></i>' : '';

            html +=
                '<div class="mb-2">' +
                '<div class="d-flex justify-content-between mb-1">' +
                '<span class="fs-2" style="' + labelStyle + '">' + escapeHtml(DAY_LABELS_ES[day] || day) + crown + '</span>' +
                '<span class="fw-semibold fs-2">' + count + '</span>' +
                '</div>' +
                '<div class="progress" style="height:8px;">' +
                '<div class="progress-bar" role="progressbar" style="width:' + pct.toFixed(1) + '%;background:' + barColor + ';"></div>' +
                '</div></div>';
        });

        el.innerHTML = html || '<p class="text-muted fs-2">Sin datos.</p>';
    }

    function loadVelocity() {
        document.getElementById('velocity-loading').classList.remove('d-none');
        document.getElementById('velocity-content').classList.add('d-none');

        $.get(velocityUrl, { days: 90 }, function (res) {
            if (!res.success) { return; }

            var v = res.data.velocity;
            var t = res.data.trend;
            var p = res.data.peak_days;

            document.getElementById('velocity-current').textContent  = t.current_period;
            document.getElementById('velocity-previous').textContent = t.previous_period;

            var changeEl   = document.getElementById('velocity-change');
            var changeIcon = document.getElementById('velocity-change-icon');
            var changeSign = t.change_pct > 0 ? '+' : '';
            var isUp       = t.trend === 'up';
            var isDown     = t.trend === 'down';

            changeEl.textContent = changeSign + t.change_pct + '%';
            changeEl.style.color = isUp ? '#90bb13' : (isDown ? '#333333' : '#9aa4b0');

            if (changeIcon) {
                changeIcon.className = 'fas velocity-big-icon ' +
                    (isUp ? 'fa-arrow-trend-up text-danger' : (isDown ? 'fa-arrow-trend-down text-muted' : 'fa-minus text-muted'));
            }

            var peakDayName = '';
            var peakDayMax  = -1;
            Object.entries(p).forEach(function (entry) {
                if (entry[1] > peakDayMax) { peakDayMax = entry[1]; peakDayName = entry[0]; }
            });
            document.getElementById('velocity-peak-day').textContent = peakDayName ? (DAY_LABELS_ES[peakDayName] || peakDayName) : '—';

            var allCounts = v.counts || [];
            renderMiniBars('velocity-mini-current',  allCounts, '#90bb13');
            renderMiniBars('velocity-mini-previous', allCounts.slice(0, Math.max(0, allCounts.length - 10)), '#333333');
            renderCmpBadge('velocity-current-cmp', t.current_period, t.previous_period);

            renderBarChart(v.dates, v.counts);
            renderPeakDays(p);

            document.getElementById('velocity-loading').classList.add('d-none');
            document.getElementById('velocity-content').classList.remove('d-none');
        }).fail(function () {
            document.getElementById('velocity-loading').innerHTML =
                '<span class="text-danger"><i class="fas fa-times-circle me-1"></i> Error al cargar datos.</span>';
        });
    }

    $(document).ready(function () {
        loadVelocity();
    });
}());
</script>
@endpush
