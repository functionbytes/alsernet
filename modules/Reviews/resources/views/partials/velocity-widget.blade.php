<div class="card mb-4" id="velocity-widget">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <h4 class="card-title fw-semibold mb-0">Velocidad de reseñas</h4>
            <p class="card-subtitle mt-1">Tendencia y frecuencia de nuevas reseñas</p>
        </div>
        <select class="form-select form-select-sm" id="velocity-days-select" style="width:auto">
            <option value="30">30 días</option>
            <option value="60">60 días</option>
            <option value="90" selected>90 días</option>
        </select>
    </div>
    <div class="card-body pt-3">

        <!-- Loading state -->
        <div id="velocity-loading" class="text-center py-4">
            <i class="fas fa-spinner fa-spin me-2 text-muted"></i>
            <span class="text-muted">Cargando...</span>
        </div>

        <!-- Content -->
        <div id="velocity-content" class="d-none">

            <!-- Trend KPIs -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <div class="border rounded p-3 text-center">
                        <div class="text-muted fs-2 mb-1">Últimos 30 días</div>
                        <div class="fw-bold fs-4" id="velocity-current">—</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="border rounded p-3 text-center">
                        <div class="text-muted fs-2 mb-1">30 días anteriores</div>
                        <div class="fw-bold fs-4" id="velocity-previous">—</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="border rounded p-3 text-center">
                        <div class="text-muted fs-2 mb-1">Variacion</div>
                        <div class="fw-bold fs-4" id="velocity-change">—</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="border rounded p-3 text-center">
                        <div class="text-muted fs-2 mb-1">Dia con mas reseñas</div>
                        <div class="fw-bold fs-5 text-truncate" id="velocity-peak-day">—</div>
                    </div>
                </div>
            </div>

            <!-- Last 14 days bar chart (CSS) -->
            <h6 class="fw-semibold mb-3">Ultimos 14 días</h6>
            <div id="velocity-bar-chart" class="mb-4" style="height:80px; display:flex; align-items:flex-end; gap:3px;">
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

    function renderBarChart(dates, counts) {
        var last14dates  = dates.slice(-14);
        var last14counts = counts.slice(-14);
        var maxCount = Math.max.apply(null, last14counts) || 1;

        var chartHtml  = '';
        var labelHtml  = '';
        var barWidth   = (100 / last14dates.length).toFixed(2) + '%';

        last14dates.forEach(function (date, i) {
            var count  = last14counts[i];
            var pct    = (count / maxCount) * 100;
            var color  = count === 0 ? '#e9ecef' : '#90bb13';
            var title  = date + ': ' + count + ' reseña' + (count !== 1 ? 's' : '');

            chartHtml +=
                '<div style="flex:1;background:' + color + ';height:' + pct.toFixed(1) + '%;min-height:2px;border-radius:2px 2px 0 0;cursor:default;" title="' + escapeHtml(title) + '"></div>';

            var parts = date.split('-');
            labelHtml +=
                '<div style="flex:1;text-align:center;overflow:hidden;white-space:nowrap;">' + (parts[2] || '') + '</div>';
        });

        document.getElementById('velocity-bar-chart').innerHTML = chartHtml || '<p class="text-muted">Sin datos.</p>';
        document.getElementById('velocity-bar-labels').innerHTML = labelHtml;
    }

    function renderPeakDays(peakDays) {
        var el     = document.getElementById('velocity-peak-days');
        var max    = Math.max.apply(null, Object.values(peakDays)) || 1;
        var html   = '';
        var bestDay = null;
        var bestVal = -1;

        Object.entries(peakDays).forEach(function (entry) {
            if (entry[1] > bestVal) { bestVal = entry[1]; bestDay = entry[0]; }
        });

        Object.entries(peakDays).forEach(function (entry) {
            var day   = entry[0];
            var count = entry[1];
            var pct   = (count / max) * 100;
            var isBest = day === bestDay;

            html +=
                '<div class="mb-2">' +
                '<div class="d-flex justify-content-between mb-1">' +
                '<span class="fs-2' + (isBest ? ' fw-bold text-primary' : '') + '">' + escapeHtml(DAY_LABELS_ES[day] || day) + (isBest ? ' <i class="fas fa-crown text-warning" title="Dia pico"></i>' : '') + '</span>' +
                '<span class="fw-semibold fs-2">' + count + '</span>' +
                '</div>' +
                '<div class="progress" style="height:8px;">' +
                '<div class="progress-bar' + (isBest ? ' bg-primary' : ' bg-secondary bg-opacity-50') + '" role="progressbar" style="width:' + pct.toFixed(1) + '%"></div>' +
                '</div></div>';
        });

        el.innerHTML = html || '<p class="text-muted fs-2">Sin datos.</p>';
    }

    function loadVelocity(days) {
        document.getElementById('velocity-loading').classList.remove('d-none');
        document.getElementById('velocity-content').classList.add('d-none');

        $.get(velocityUrl, { days: days }, function (res) {
            if (! res.success) { return; }

            var v = res.data.velocity;
            var t = res.data.trend;
            var p = res.data.peak_days;

            // Trend KPIs
            document.getElementById('velocity-current').textContent   = t.current_period;
            document.getElementById('velocity-previous').textContent  = t.previous_period;

            var changeEl  = document.getElementById('velocity-change');
            var changeSign = t.change_pct > 0 ? '+' : '';
            var changeIcon = t.trend === 'up' ? ' <i class="fas fa-arrow-up text-success"></i>' :
                             (t.trend === 'down' ? ' <i class="fas fa-arrow-down text-danger"></i>' : '');
            var changeClass = t.trend === 'up' ? 'text-success' : (t.trend === 'down' ? 'text-danger' : 'text-muted');
            changeEl.className = 'fw-bold fs-4 ' + changeClass;
            changeEl.innerHTML = changeSign + t.change_pct + '%' + changeIcon;

            // Peak day in KPI card
            var peakDayName = '';
            var peakDayMax  = -1;
            Object.entries(p).forEach(function (entry) {
                if (entry[1] > peakDayMax) { peakDayMax = entry[1]; peakDayName = entry[0]; }
            });
            document.getElementById('velocity-peak-day').textContent = peakDayName ? (DAY_LABELS_ES[peakDayName] || peakDayName) : '—';

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
        var days = parseInt($('#velocity-days-select').val()) || 90;
        loadVelocity(days);

        $('#velocity-days-select').on('change', function () {
            loadVelocity(parseInt($(this).val()) || 90);
        });
    });
}());
</script>
@endpush
