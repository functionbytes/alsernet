<div class="card shadow-sm" id="analytics-widget">
    <div class="card-header bg-transparent border-0 pb-0 pt-4 px-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-1 fw-bold">Análisis avanzado</h5>
                <p class="text-muted mb-0 fs-2">Palabras clave, sentimiento y anomalías</p>
            </div>
            <span class="badge bg-light-primary text-primary rounded-pill px-3 py-2">
                <i class="fas fa-brain me-1"></i> Analytics
            </span>
        </div>
    </div>
    <div class="card-body pt-3">

        <!-- Tabs -->
        <ul class="nav nav-tabs mb-4" id="analytics-tabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="tab-sentiment" data-bs-toggle="tab" data-bs-target="#panel-sentiment" type="button" role="tab">
                    <i class="fas fa-heart me-1"></i> Sentimiento
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-keywords" data-bs-toggle="tab" data-bs-target="#panel-keywords" type="button" role="tab">
                    <i class="fas fa-tags me-1"></i> Palabras clave
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-anomalies" data-bs-toggle="tab" data-bs-target="#panel-anomalies" type="button" role="tab">
                    <i class="fas fa-exclamation-triangle me-1"></i> Anomalías
                    <span class="badge bg-danger ms-1 d-none" id="anomaly-badge">0</span>
                </button>
            </li>
        </ul>

        <!-- Tab content -->
        <div class="tab-content" id="analytics-tab-content">

            <!-- Sentiment panel -->
            <div class="tab-pane fade show active" id="panel-sentiment" role="tabpanel">
                <div id="sentiment-loading" class="text-center py-4">
                    <i class="fas fa-spinner fa-spin me-2 text-muted"></i>
                    <span class="text-muted">Cargando...</span>
                </div>
                <div id="sentiment-content" class="d-none">
                    <div class="row g-3 mb-3" id="sentiment-bars">
                        <!-- Injected by JS -->
                    </div>
                </div>
            </div>

            <!-- Keywords panel -->
            <div class="tab-pane fade" id="panel-keywords" role="tabpanel">
                <div id="keywords-loading" class="text-center py-4">
                    <i class="fas fa-spinner fa-spin me-2 text-muted"></i>
                    <span class="text-muted">Cargando...</span>
                </div>
                <div id="keywords-content" class="d-none">
                    <div id="keywords-cloud" class="d-flex flex-wrap gap-2 py-2">
                        <!-- Injected by JS -->
                    </div>
                    <p id="keywords-empty" class="text-muted text-center py-4 d-none">
                        <i class="fas fa-comment-slash me-2"></i> No hay datos de palabras clave para este período.
                    </p>
                </div>
            </div>

            <!-- Anomalies panel -->
            <div class="tab-pane fade" id="panel-anomalies" role="tabpanel">
                <div id="anomalies-loading" class="text-center py-4">
                    <i class="fas fa-spinner fa-spin me-2 text-muted"></i>
                    <span class="text-muted">Cargando...</span>
                </div>
                <div id="anomalies-content" class="d-none">
                    <div id="anomalies-list"></div>
                    <div id="anomalies-empty" class="d-none text-center py-4">
                        <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                        <p class="text-muted mb-0">Sin anomalías detectadas en las últimas 24 horas.</p>
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

    var analyticsUrl = '{{ route("reviews.analytics.data") }}';
    var locationId = null; // set to a numeric id to filter by location

    function escapeHtml(text) {
        var d = document.createElement('div');
        d.textContent = String(text);
        return d.innerHTML;
    }

    function renderSentiment(sentiment) {
        var bars = document.getElementById('sentiment-bars');
        var items = [
            { key: 'positive', label: 'Positivo', color: 'success', icon: 'fa-smile' },
            { key: 'neutral',  label: 'Neutral',  color: 'warning', icon: 'fa-meh' },
            { key: 'negative', label: 'Negativo', color: 'danger',  icon: 'fa-frown' },
        ];
        var html = '';
        items.forEach(function (item) {
            var pct = sentiment[item.key] || 0;
            html += '<div class="col-md-4">' +
                '<div class="d-flex align-items-center mb-1">' +
                '<i class="fas ' + item.icon + ' text-' + item.color + ' me-2"></i>' +
                '<span class="fw-semibold">' + item.label + '</span>' +
                '<span class="ms-auto text-muted fw-bold">' + pct.toFixed(1) + '%</span>' +
                '</div>' +
                '<div class="progress" style="height:10px;">' +
                '<div class="progress-bar bg-' + item.color + '" role="progressbar" style="width:' + pct + '%" aria-valuenow="' + pct + '" aria-valuemin="0" aria-valuemax="100"></div>' +
                '</div></div>';
        });
        bars.innerHTML = html;
    }

    function renderKeywords(keywords) {
        var cloud = document.getElementById('keywords-cloud');
        var empty = document.getElementById('keywords-empty');
        if (!keywords || keywords.length === 0) {
            cloud.classList.add('d-none');
            empty.classList.remove('d-none');
            return;
        }
        empty.classList.add('d-none');
        cloud.classList.remove('d-none');
        var maxCount = Math.max.apply(null, keywords.map(function (k) { return k.count; }));
        var sentimentClass = { positive: 'bg-success', neutral: 'bg-secondary', negative: 'bg-danger' };
        var html = '';
        keywords.forEach(function (kw) {
            var fontSize = 0.75 + (kw.count / maxCount) * 0.75;
            var cls = sentimentClass[kw.sentiment] || 'bg-secondary';
            html += '<span class="badge ' + cls + ' py-2 px-3" style="font-size:' + fontSize.toFixed(2) + 'rem; opacity:0.85;" title="' + kw.count + ' veces">' +
                escapeHtml(kw.word) +
                ' <small>(' + kw.count + ')</small></span>';
        });
        cloud.innerHTML = html;
    }

    function renderAnomalies(anomalies) {
        var list = document.getElementById('anomalies-list');
        var empty = document.getElementById('anomalies-empty');
        var badge = document.getElementById('anomaly-badge');

        if (!anomalies || anomalies.length === 0) {
            list.innerHTML = '';
            empty.classList.remove('d-none');
            badge.classList.add('d-none');
            return;
        }

        empty.classList.add('d-none');
        badge.textContent = anomalies.length;
        badge.classList.remove('d-none');

        var html = '<div class="list-group list-group-flush">';
        anomalies.forEach(function (a) {
            html += '<div class="list-group-item px-0">' +
                '<div class="d-flex align-items-start gap-3">' +
                '<span class="text-danger mt-1"><i class="fas fa-exclamation-circle fa-lg"></i></span>' +
                '<div>' +
                '<p class="mb-1 fw-semibold">' + escapeHtml(a.location_name) + '</p>' +
                '<p class="mb-1 text-muted fs-2">' +
                    a.current_count + ' reseñas negativas — <span class="text-danger fw-bold">' + a.multiplier.toFixed(1) + 'x</span> la tasa normal' +
                    ' (promedio: ' + a.historical_average.toFixed(1) + ')' +
                '</p>' +
                '<small class="text-muted">Detectado: ' + escapeHtml(a.detected_at) + '</small>' +
                '</div></div></div>';
        });
        html += '</div>';
        list.innerHTML = html;
    }

    function loadAnalytics() {
        var params = locationId ? '?location_id=' + locationId : '';

        $.ajax({
            url: analyticsUrl + params,
            method: 'GET',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (response) {
                if (!response.success) { return; }
                var data = response.data;

                // Sentiment
                document.getElementById('sentiment-loading').classList.add('d-none');
                document.getElementById('sentiment-content').classList.remove('d-none');
                renderSentiment(data.sentiment);

                // Keywords
                document.getElementById('keywords-loading').classList.add('d-none');
                document.getElementById('keywords-content').classList.remove('d-none');
                renderKeywords(data.keywords);

                // Anomalies
                document.getElementById('anomalies-loading').classList.add('d-none');
                document.getElementById('anomalies-content').classList.remove('d-none');
                renderAnomalies(data.anomalies);
            },
            error: function () {
                ['sentiment', 'keywords', 'anomalies'].forEach(function (p) {
                    document.getElementById(p + '-loading').innerHTML =
                        '<span class="text-danger"><i class="fas fa-times-circle me-1"></i> Error al cargar datos.</span>';
                });
            }
        });
    }

    $(document).ready(function () {
        loadAnalytics();
    });
}());
</script>
@endpush
