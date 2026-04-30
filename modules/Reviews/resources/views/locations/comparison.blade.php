@extends('layouts.theme')

@section('title', 'Comparación de ubicaciones')

@section('page_header')
    @include('core::components.card', ['title' => 'Comparación de ubicaciones'])
@endsection

@section('content')

    <!-- Filters -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-lg-7">
                    <label class="form-label fw-semibold">Ubicaciones <small class="text-muted">(mín. 2, máx. 10)</small></label>
                    <div id="location-checkboxes" class="d-flex flex-wrap gap-2">
                        @forelse($locations as $location)
                            <div class="form-check form-check-inline border rounded px-3 py-2 location-check-item">
                                <input class="form-check-input location-checkbox" type="checkbox"
                                    id="loc-{{ $location->id }}"
                                    value="{{ $location->id }}"
                                    {{ $loop->index < 3 ? 'checked' : '' }}>
                                <label class="form-check-label" for="loc-{{ $location->id }}">
                                    {{ $location->name }}
                                    @if($location->address)
                                        <small class="text-muted d-block">{{ Str::limit($location->address, 40) }}</small>
                                    @endif
                                </label>
                            </div>
                        @empty
                            <p class="text-muted mb-0">No hay ubicaciones activas disponibles.</p>
                        @endforelse
                    </div>
                </div>
                <div class="col-lg-3">
                    <label class="form-label fw-semibold">Período</label>
                    <select class="form-select" id="period-select">
                        <option value="7">Últimos 7 días</option>
                        <option value="30" selected>Últimos 30 días</option>
                        <option value="90">Últimos 90 días</option>
                    </select>
                </div>
                <div class="col-lg-2">
                    <button class="btn btn-primary w-100" id="btn-compare">
                        <i class="fas fa-chart-bar me-1"></i> Comparar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Rankings card -->
    <div class="card shadow-sm mb-4" id="rankings-card">
        <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1 fw-bold">Ranking de ubicaciones</h5>
                    <p class="text-muted mb-0 fs-2">Ordenado por rating promedio</p>
                </div>
                <span class="badge bg-light-primary text-primary rounded-pill px-3 py-2">
                    <i class="fas fa-trophy me-1"></i> Ranking
                </span>
            </div>
        </div>
        <div class="card-body pt-3">
            <div id="rankings-loading" class="text-center py-4">
                <i class="fas fa-spinner fa-spin me-2 text-muted"></i>
                <span class="text-muted">Cargando rankings...</span>
            </div>
            <div id="rankings-content" class="d-none">
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="rankings-table">
                        <thead>
                            <tr>
                                <th style="width:50px">#</th>
                                <th>Ubicación</th>
                                <th class="text-center">Reseñas</th>
                                <th class="text-center">Rating</th>
                                <th class="text-center">Tasa respuesta</th>
                                <th class="text-center">Tiempo resp.</th>
                                <th class="text-center">Pendientes</th>
                                <th class="text-center">Tendencia</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Comparison table -->
    <div class="card shadow-sm mb-4" id="comparison-card">
        <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1 fw-bold">Comparación detallada</h5>
                    <p class="text-muted mb-0 fs-2" id="comparison-period-label">Últimos 30 días</p>
                </div>
                <div id="comparison-summary-badges" class="d-flex gap-2"></div>
            </div>
        </div>
        <div class="card-body pt-3">
            <div id="comparison-loading" class="text-center py-4 d-none">
                <i class="fas fa-spinner fa-spin me-2 text-muted"></i>
                <span class="text-muted">Calculando comparación...</span>
            </div>
            <div id="comparison-content">
                <p class="text-muted text-center py-5" id="comparison-placeholder">
                    <i class="fas fa-chart-bar fa-2x mb-2 d-block opacity-50"></i>
                    Selecciona al menos 2 ubicaciones y presiona "Comparar"
                </p>
            </div>
        </div>
    </div>

@endsection

@push('styles')
<style>
    .location-check-item {
        cursor: pointer;
        transition: all 0.2s;
        background: #f8f9fa;
    }
    .location-check-item:has(.location-checkbox:checked) {
        background: rgba(144, 187, 19, 0.08);
        border-color: #b10100 !important;
    }
    .badge.bg-light-primary { background: rgba(144, 187, 19, 0.1) !important; color: #b10100 !important; }
    .badge.bg-light-success { background: rgba(19, 198, 114, 0.1) !important; color: #13C672 !important; }
    .badge.bg-light-danger  { background: rgba(250, 137, 107, 0.1) !important; color: #FA896B !important; }
    .fs-2 { font-size: 0.875rem !important; }

    .rating-bar-wrap { min-width: 80px; }
    .progress { height: 8px; }

    .rank-badge {
        width: 28px; height: 28px;
        border-radius: 50%;
        display: inline-flex; align-items: center; justify-content: center;
        font-weight: 700; font-size: 0.75rem;
    }
    .rank-1 { background: #FEC90F; color: #fff; }
    .rank-2 { background: #c0c0c0; color: #fff; }
    .rank-3 { background: #cd7f32; color: #fff; }
    .rank-other { background: #e9ecef; color: #5a6a85; }

    .row-best  { background: rgba(19, 198, 114, 0.05) !important; }
    .row-worst { background: rgba(250, 137, 107, 0.05) !important; }

    .table thead th {
        font-size: 0.8125rem; font-weight: 600;
        text-transform: uppercase; letter-spacing: 0.5px;
        color: #5A6A85; border-bottom: 2px solid #e9ecef;
    }
    .table td { font-size: 0.875rem; vertical-align: middle; border-bottom: 1px solid #f9f9f9; }

    .delta-up   { color: #13C672; }
    .delta-down { color: #FA896B; }
    .delta-flat { color: #adb5bd; }
</style>
@endpush

@push('scripts')
<script>
(function () {
    'use strict';

    var dataUrl     = '{{ route("reviews.locations.comparison.data") }}';
    var rankingsUrl = '{{ route("reviews.locations.comparison.rankings") }}';

    /* ------------------------------------------------------------------ */
    /* Helpers                                                              */
    /* ------------------------------------------------------------------ */
    function escapeHtml(text) {
        var d = document.createElement('div');
        d.textContent = String(text ?? '');
        return d.innerHTML;
    }

    function stars(rating) {
        var n = Math.round(parseFloat(rating) || 0);
        var html = '';
        for (var i = 1; i <= 5; i++) {
            html += i <= n
                ? '<i class="fas fa-star text-warning" style="font-size:.7rem"></i>'
                : '<i class="far fa-star text-muted" style="font-size:.7rem"></i>';
        }
        return html;
    }

    function progressBar(pct, color) {
        color = color || 'primary';
        var w = Math.min(100, Math.max(0, parseFloat(pct) || 0));
        return '<div class="progress mt-1 rating-bar-wrap">' +
            '<div class="progress-bar bg-' + color + '" role="progressbar" style="width:' + w + '%" ' +
            'aria-valuenow="' + w + '" aria-valuemin="0" aria-valuemax="100"></div></div>';
    }

    function formatHours(h) {
        if (!h || h <= 0) { return '—'; }
        var totalMin = Math.round(h * 60);
        if (totalMin < 60) { return totalMin + 'm'; }
        var hrs = Math.floor(totalMin / 60);
        var mins = totalMin % 60;
        if (hrs >= 24) {
            var days = Math.floor(hrs / 24);
            var remH = hrs % 24;
            return remH > 0 ? days + 'd ' + remH + 'h' : days + 'd';
        }
        return mins > 0 ? hrs + 'h ' + mins + 'm' : hrs + 'h';
    }

    function deltaHtml(delta) {
        if (!delta || delta === 0) {
            return '<span class="delta-flat"><i class="fas fa-minus"></i> 0.0</span>';
        }
        if (delta > 0) {
            return '<span class="delta-up"><i class="fas fa-arrow-up"></i> +' + delta.toFixed(1) + '</span>';
        }
        return '<span class="delta-down"><i class="fas fa-arrow-down"></i> ' + delta.toFixed(1) + '</span>';
    }

    /* ------------------------------------------------------------------ */
    /* Rankings                                                             */
    /* ------------------------------------------------------------------ */
    function loadRankings(days) {
        $('#rankings-loading').removeClass('d-none');
        $('#rankings-content').addClass('d-none');

        $.get(rankingsUrl, { days: days }, function (res) {
            if (!res.success) { return; }
            renderRankings(res.data);
        }).fail(function () {
            $('#rankings-loading').html('<span class="text-danger"><i class="fas fa-times-circle me-1"></i> Error al cargar rankings.</span>');
        });
    }

    function renderRankings(items) {
        var tbody = $('#rankings-table tbody').empty();

        if (!items || items.length === 0) {
            tbody.append('<tr><td colspan="8" class="text-center text-muted py-4">No hay datos disponibles.</td></tr>');
            $('#rankings-loading').addClass('d-none');
            $('#rankings-content').removeClass('d-none');
            return;
        }

        items.forEach(function (item) {
            var rankClass = item.rank <= 3 ? 'rank-' + item.rank : 'rank-other';
            var rankBadge = '<span class="rank-badge ' + rankClass + '">' + item.rank + '</span>';

            var responseRateBar = progressBar(item.response_rate,
                item.response_rate >= 80 ? 'success' : item.response_rate >= 50 ? 'warning' : 'danger');

            tbody.append(
                '<tr>' +
                '<td>' + rankBadge + '</td>' +
                '<td><span class="fw-semibold">' + escapeHtml(item.location_name) + '</span></td>' +
                '<td class="text-center">' + (item.total_reviews || 0).toLocaleString() + '</td>' +
                '<td class="text-center">' + stars(item.avg_rating) + ' <small class="text-muted ms-1">' + (parseFloat(item.avg_rating) || 0).toFixed(1) + '</small></td>' +
                '<td><div class="d-flex align-items-center gap-1"><span class="text-muted" style="min-width:36px">' + (parseFloat(item.response_rate) || 0).toFixed(0) + '%</span>' + responseRateBar + '</div></td>' +
                '<td class="text-center text-muted">' + formatHours(item.avg_response_time_hours) + '</td>' +
                '<td class="text-center">' + (item.pending_replies > 0 ? '<span class="badge bg-warning text-dark">' + item.pending_replies + '</span>' : '<span class="text-muted">0</span>') + '</td>' +
                '<td class="text-center">' + deltaHtml(item.delta_rating) + '</td>' +
                '</tr>'
            );
        });

        $('#rankings-loading').addClass('d-none');
        $('#rankings-content').removeClass('d-none');
    }

    /* ------------------------------------------------------------------ */
    /* Comparison                                                           */
    /* ------------------------------------------------------------------ */
    function loadComparison() {
        var locationIds = [];
        $('.location-checkbox:checked').each(function () {
            locationIds.push(parseInt($(this).val()));
        });

        if (locationIds.length < 2) {
            toastr.warning('Selecciona al menos 2 ubicaciones para comparar.');
            return;
        }
        if (locationIds.length > 10) {
            toastr.warning('Máximo 10 ubicaciones.');
            return;
        }

        var days = parseInt($('#period-select').val()) || 30;
        var labels = { 7: 'Últimos 7 días', 30: 'Últimos 30 días', 90: 'Últimos 90 días' };
        $('#comparison-period-label').text(labels[days] || 'Últimos 30 días');

        $('#comparison-loading').removeClass('d-none');
        $('#comparison-placeholder').addClass('d-none');
        $('#comparison-content').empty().append($('#comparison-loading'));

        var params = { days: days };
        locationIds.forEach(function (id, i) {
            params['location_ids[' + i + ']'] = id;
        });

        $.get(dataUrl, params, function (res) {
            if (!res.success) { return; }
            renderComparison(res.data.comparison, res.data.summary, days);
            loadRankings(days);
        }).fail(function (xhr) {
            var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Error al cargar comparación.';
            toastr.error(msg);
            $('#comparison-loading').addClass('d-none');
        });
    }

    function renderComparison(items, summary, days) {
        var bestId  = summary.best  ? summary.best.location_id  : null;
        var worstId = summary.worst ? summary.worst.location_id : null;

        // Summary badges
        var summaryHtml = '';
        if (summary.best) {
            summaryHtml += '<span class="badge bg-light-success"><i class="fas fa-trophy me-1"></i>' + escapeHtml(summary.best.location_name) + ' (mejor)</span>';
        }
        if (summary.worst && summary.worst.location_id !== bestId) {
            summaryHtml += '<span class="badge bg-light-danger"><i class="fas fa-exclamation-triangle me-1"></i>' + escapeHtml(summary.worst.location_name) + ' (atención)</span>';
        }
        $('#comparison-summary-badges').html(summaryHtml);

        var maxReviews = Math.max.apply(null, items.map(function (i) { return i.total_reviews || 1; }));
        var maxRespTime = Math.max.apply(null, items.map(function (i) { return i.avg_response_time_hours || 1; }));

        var tableHtml =
            '<div class="table-responsive">' +
            '<table class="table table-hover align-middle">' +
            '<thead><tr>' +
            '<th>Ubicación</th>' +
            '<th class="text-center">Reseñas</th>' +
            '<th class="text-center">Rating promedio</th>' +
            '<th class="text-center">Tasa de respuesta</th>' +
            '<th class="text-center">Tiempo de respuesta</th>' +
            '<th class="text-center">Pendientes</th>' +
            '</tr></thead>' +
            '<tbody>';

        items.forEach(function (item) {
            var rowClass = item.location_id === bestId ? 'row-best' :
                (item.location_id === worstId && bestId !== worstId ? 'row-worst' : '');

            var reviewsBar = progressBar((item.total_reviews / maxReviews) * 100,
                item.location_id === bestId ? 'success' : 'primary');

            var ratingBar  = progressBar((item.avg_rating / 5) * 100,
                item.avg_rating >= 4 ? 'success' : item.avg_rating >= 3 ? 'warning' : 'danger');

            var responseBar = progressBar(item.response_rate,
                item.response_rate >= 80 ? 'success' : item.response_rate >= 50 ? 'warning' : 'danger');

            var respTimeBar = progressBar(maxRespTime > 0 ? (item.avg_response_time_hours / maxRespTime) * 100 : 0,
                item.avg_response_time_hours <= 4 ? 'success' : item.avg_response_time_hours <= 24 ? 'warning' : 'danger');

            var badge = item.location_id === bestId
                ? ' <span class="badge bg-success ms-1" style="font-size:.65rem">Mejor</span>'
                : (item.location_id === worstId && bestId !== worstId
                    ? ' <span class="badge bg-warning text-dark ms-1" style="font-size:.65rem">Atención</span>'
                    : '');

            tableHtml +=
                '<tr class="' + rowClass + '">' +
                '<td><span class="fw-semibold">' + escapeHtml(item.location_name) + '</span>' + badge + '</td>' +
                '<td><div>' + (item.total_reviews || 0).toLocaleString() + '</div>' + reviewsBar + '</td>' +
                '<td>' + stars(item.avg_rating) +
                    '<div>' + (parseFloat(item.avg_rating) || 0).toFixed(2) + ' / 5</div>' + ratingBar + '</td>' +
                '<td><div>' + (parseFloat(item.response_rate) || 0).toFixed(1) + '%</div>' + responseBar + '</td>' +
                '<td><div>' + formatHours(item.avg_response_time_hours) + '</div>' + respTimeBar + '</td>' +
                '<td class="text-center">' +
                    (item.pending_replies > 0
                        ? '<span class="badge bg-danger rounded-pill">' + item.pending_replies + '</span>'
                        : '<span class="text-success"><i class="fas fa-check-circle"></i></span>') +
                '</td>' +
                '</tr>';
        });

        tableHtml += '</tbody></table></div>';

        var $content = $('#comparison-content').empty();
        $('#comparison-loading').addClass('d-none');
        $content.html(tableHtml);
    }

    /* ------------------------------------------------------------------ */
    /* Init                                                                 */
    /* ------------------------------------------------------------------ */
    $(document).ready(function () {
        var days = parseInt($('#period-select').val()) || 30;
        loadRankings(days);

        $('#btn-compare').on('click', loadComparison);

        // Allow clicking the period dropdown to reload rankings immediately
        $('#period-select').on('change', function () {
            loadRankings(parseInt($(this).val()) || 30);
        });

        // Enforce max 10 selections
        $(document).on('change', '.location-checkbox', function () {
            var checked = $('.location-checkbox:checked').length;
            if (checked > 10) {
                $(this).prop('checked', false);
                toastr.warning('Máximo 10 ubicaciones para comparar.');
            }
        });
    });
}());
</script>
@endpush
