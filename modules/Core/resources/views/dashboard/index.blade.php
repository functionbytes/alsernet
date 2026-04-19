@extends('layouts.theme')

@section('content')
<div class="container-fluid">

    {{-- Page header --}}
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="mb-0">Dashboard</h1>
            <p class="text-muted">Bienvenido, {{ auth()->user()->firstname }}</p>
        </div>
    </div>

    {{-- Analytics KPI Widget (módulo Analytics) --}}
    @include('analytics::components.dashboard-widget')

    {{-- Cookie consent widget --}}
    @include('cookie::components.consent-widget')

    {{-- KPI Cards --}}
    <div class="row g-3 mb-4" id="dashboard-kpis">

        {{-- Reseñas --}}
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                         style="width:52px;height:52px;background:#FEC90F22">
                        <i class="fas fa-star" style="color:#FEC90F;font-size:1.4rem"></i>
                    </div>
                    <div>
                        <div class="text-muted">Reseñas</div>
                        <div class="fs-4 fw-bold kpi-reviews-total">—</div>
                        <div class="text-muted" style="font-size:.75rem">
                            <span class="kpi-reviews-avg">—</span> promedio
                            &nbsp;·&nbsp;
                            <span class="kpi-reviews-today">—</span> hoy
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- PQRSF --}}
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                         style="width:52px;height:52px;background:#FA896B22">
                        <i class="fas fa-clipboard-list" style="color:#FA896B;font-size:1.4rem"></i>
                    </div>
                    <div>
                        <div class="text-muted">PQRSF pendientes</div>
                        <div class="fs-4 fw-bold kpi-attention-pending">—</div>
                        <div class="text-muted" style="font-size:.75rem">
                            <span class="kpi-attention-process">—</span> en proceso
                            &nbsp;·&nbsp;
                            <span class="kpi-attention-week">—</span> esta semana
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Formularios --}}
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                         style="width:52px;height:52px;background:#90bb1322">
                        <i class="fas fa-wpforms" style="color:#90bb13;font-size:1.4rem"></i>
                    </div>
                    <div>
                        <div class="text-muted">Formularios hoy</div>
                        <div class="fs-4 fw-bold kpi-forms-today">—</div>
                        <div class="text-muted" style="font-size:.75rem">
                            <span class="kpi-forms-unread">—</span> sin leer
                            &nbsp;·&nbsp;
                            <span class="kpi-forms-active">—</span> activos
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Estado del sistema --}}
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                         style="width:52px;height:52px;background:#13C67222">
                        <i class="fas fa-server" style="color:#13C672;font-size:1.4rem"></i>
                    </div>
                    <div>
                        <div class="text-muted">Sistema</div>
                        <div class="fs-6 fw-bold text-success">Operativo</div>
                        <div class="text-muted" style="font-size:.75rem" id="kpi-last-updated">—</div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- Cola de trabajos --}}
    <div class="row g-3 mb-4" id="queue-stats-section">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center pb-0">
                    <h6 class="mb-0 fw-semibold">Cola de trabajos</h6>
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-muted" style="font-size:.72rem" id="queue-last-updated"></span>
                        <a href="/horizon" target="_blank" class="btn btn-sm btn-light" title="Abrir Horizon">
                            <i class="fas fa-external-link-alt"></i>
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-3">

                        {{-- Horizon status --}}
                        <div class="col-xl-3 col-md-6">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fas fa-circle queue-horizon-dot" style="font-size:.65rem"></i>
                                <div>
                                    <div class="text-muted">Estado Horizon</div>
                                    <div class="fw-semibold queue-horizon-label">—</div>
                                </div>
                            </div>
                        </div>

                        {{-- Failed jobs --}}
                        <div class="col-xl-3 col-md-6">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fas fa-exclamation-triangle text-danger" style="font-size:1.1rem"></i>
                                <div>
                                    <div class="text-muted">Trabajos fallidos</div>
                                    <div class="fw-semibold">
                                        <span class="badge queue-failed-badge">—</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Total pending --}}
                        <div class="col-xl-3 col-md-6">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fas fa-clock text-warning" style="font-size:1.1rem"></i>
                                <div>
                                    <div class="text-muted">Pendientes totales</div>
                                    <div class="fw-semibold queue-total-pending">—</div>
                                </div>
                            </div>
                        </div>

                        {{-- Per-queue breakdown --}}
                        <div class="col-xl-3 col-md-6">
                            <div class="text-muted mb-1">Por cola</div>
                            <div id="queue-per-queue" class="d-flex flex-wrap gap-1">
                                <span class="text-muted">—</span>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Resumen de actividad --}}
    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0 pb-0">
                    <h6 class="mb-0 fw-semibold">Resumen de actividad — últimos 14 días</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3 mb-3">
                        <div class="col-auto">
                            <span class="text-muted">Reseñas hoy:</span>
                            <strong class="ms-1 kpi-reviews-today-inline">—</strong>
                        </div>
                        <div class="col-auto">
                            <span class="text-muted">PQRSF pendientes:</span>
                            <strong class="ms-1 kpi-attention-pending-inline text-danger">—</strong>
                        </div>
                        <div class="col-auto">
                            <span class="text-muted">Formularios esta semana:</span>
                            <strong class="ms-1 kpi-forms-week">—</strong>
                        </div>
                    </div>
                    <div id="activity-chart" style="height:220px"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Actividad + Accesos rápidos --}}
    <div class="row g-3 mb-4">

        {{-- Actividad reciente --}}
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center pb-0">
                    <h6 class="mb-0 fw-semibold">Actividad reciente</h6>
                    <button class="btn btn-sm btn-light" id="btn-refresh-activity" title="Actualizar">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
                <div class="card-body p-0" id="recent-activity-list">
                    <div class="text-center py-4 text-muted">
                        <div class="spinner-border spinner-border-sm" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Accesos rápidos --}}
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0 pb-0">
                    <h6 class="mb-0 fw-semibold">Accesos rápidos</h6>
                </div>
                <div class="card-body d-flex flex-column gap-2">
                    @if(auth()->user()?->hasAnyRole(['admin', 'super-admin', 'super-settings', 'settings', 'managers']))
                        @if(Route::has('attention.index'))
                        <a href="{{ route('attention.index') }}" class="btn btn-outline-secondary btn-sm text-start">
                            <i class="fas fa-clipboard-list me-2"></i>PQRSF
                        </a>
                        @endif
                        @if(Route::has('reviews.index'))
                        <a href="{{ route('reviews.index') }}" class="btn btn-outline-secondary btn-sm text-start">
                            <i class="fas fa-star me-2"></i>Reseñas
                        </a>
                        @endif
                        @if(Route::has('forms.index'))
                        <a href="{{ route('forms.index') }}" class="btn btn-outline-secondary btn-sm text-start">
                            <i class="fas fa-wpforms me-2"></i>Formularios
                        </a>
                        @endif
                        @if(Route::has('analytics.dashboard'))
                        <a href="{{ route('analytics.dashboard') }}" class="btn btn-outline-secondary btn-sm text-start">
                            <i class="fas fa-chart-line me-2"></i>Analytics
                        </a>
                        @endif
                    @endif

                    @if(auth()->user()?->hasAnyRole(['callcenters', 'supports']))
                        @if(Route::has('attention.index'))
                        <a href="{{ route('attention.index') }}" class="btn btn-outline-secondary btn-sm text-start">
                            <i class="fas fa-clipboard-list me-2"></i>PQRSF
                        </a>
                        @endif
                    @endif
                </div>
            </div>
        </div>

    </div>

    {{-- Security widget (only for admins with audit access) --}}
    @can('auth.audit.view')
        @include('auth::components.security-widget')
    @endcan

    {{-- Role-based widgets --}}
    <div class="row">
        <div class="col-md-12 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <p class="mb-0">
                        Tu rol: <strong>{{ ucfirst(str_replace('-', ' ', $userRole ?? 'Sin rol')) }}</strong>
                    </p>
                </div>
            </div>
        </div>

        @switch($userRole)
            @case('super-settings')
            @case('settings')
            @case('managers')
                <div class="col-md-3 mb-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center py-4">
                            <i class="fas fa-users fa-2x text-primary mb-3"></i>
                            <h6>Usuarios</h6>
                            <p class="text-muted mb-0">Gestiona usuarios</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center py-4">
                            <i class="fas fa-file fa-2x text-success mb-3"></i>
                            <h6>Documentos</h6>
                            <p class="text-muted mb-0">Gestiona documentos</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center py-4">
                            <i class="fas fa-cog fa-2x text-warning mb-3"></i>
                            <h6>Configuración</h6>
                            <p class="text-muted mb-0">Ajustes del sistema</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center py-4">
                            <i class="fas fa-chart-bar fa-2x text-info mb-3"></i>
                            <h6>Reportes</h6>
                            <p class="text-muted mb-0">Ver análisis</p>
                        </div>
                    </div>
                </div>
                @break

            @case('callcenters')
                <div class="col-md-4 mb-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center py-4">
                            <i class="fas fa-headset fa-2x text-primary mb-3"></i>
                            <h6>Conversaciones</h6>
                            <p class="text-muted mb-0">Gestiona conversaciones</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center py-4">
                            <i class="fas fa-ticket-alt fa-2x text-success mb-3"></i>
                            <h6>Tickets</h6>
                            <p class="text-muted mb-0">Ver tickets</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center py-4">
                            <i class="fas fa-chart-line fa-2x text-warning mb-3"></i>
                            <h6>Estadísticas</h6>
                            <p class="text-muted mb-0">Ver desempeño</p>
                        </div>
                    </div>
                </div>
                @break

            @case('supports')
                <div class="col-md-4 mb-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center py-4">
                            <i class="fas fa-life-ring fa-2x text-primary mb-3"></i>
                            <h6>Soporte</h6>
                            <p class="text-muted mb-0">Gestiona solicitudes</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center py-4">
                            <i class="fas fa-comments fa-2x text-warning mb-3"></i>
                            <h6>Comentarios</h6>
                            <p class="text-muted mb-0">Ver comentarios</p>
                        </div>
                    </div>
                </div>
                @break

            @default
                <div class="col-md-6 mb-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center py-4">
                            <i class="fas fa-home fa-2x text-primary mb-3"></i>
                            <h6>Panel principal</h6>
                            <p class="text-muted mb-0">Bienvenido al sistema</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center py-4">
                            <i class="fas fa-info-circle fa-2x text-info mb-3"></i>
                            <h6>Información</h6>
                            <p class="text-muted mb-0">Obtén ayuda</p>
                        </div>
                    </div>
                </div>
        @endswitch
    </div>

</div>
@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    // Cache for KPI data used across sections
    var kpiCache = {};

    function loadKpis() {
        $.getJSON('{{ route("core.dashboard.kpis") }}', function (data) {
            kpiCache = data;

            var reviews = data.reviews || {};
            var attention = data.attention || {};
            var forms = data.forms || {};

            // Reviews
            $('.kpi-reviews-total').text(reviews.total ?? '—');
            var avg = reviews.avg_rating ? Number(reviews.avg_rating).toFixed(1) + ' ★' : '—';
            $('.kpi-reviews-avg').text(avg);
            var todayCount = reviews.new_today ?? 0;
            $('.kpi-reviews-today').text(todayCount);
            $('.kpi-reviews-today-inline').text(todayCount);

            // PQRSF
            var pending = attention.pending ?? 0;
            $('.kpi-attention-pending').text(pending).toggleClass('text-danger', pending > 5);
            $('.kpi-attention-pending-inline').text(pending).toggleClass('text-danger', pending > 5);
            $('.kpi-attention-process').text(attention.in_process ?? '—');
            $('.kpi-attention-week').text(attention.total_week ?? '—');

            // Formularios
            var unread = forms.unread ?? 0;
            $('.kpi-forms-today').text(forms.submissions_today ?? '—');
            $('.kpi-forms-unread').text(unread).toggleClass('text-danger', unread > 0);
            $('.kpi-forms-active').text(forms.active_forms ?? '—');
            $('.kpi-forms-week').text(forms.submissions_today ?? '—');

            // Timestamp
            var now = new Date();
            $('#kpi-last-updated').text(
                'Actualizado ' + now.toLocaleTimeString('es', { hour: '2-digit', minute: '2-digit' })
            );
        });
    }

    function loadActivity() {
        $.getJSON('{{ route("core.dashboard.activity") }}', function (data) {
            var list = data.activities || [];

            if (!list.length) {
                $('#recent-activity-list').html(
                    '<div class="text-center py-3 text-muted">Sin actividad reciente</div>'
                );
                return;
            }

            var html = list.map(function (a) {
                return '<div class="d-flex gap-2 px-3 py-2 border-bottom">' +
                    '<i class="' + a.icon + ' text-' + a.color + ' mt-1 flex-shrink-0"></i>' +
                    '<div class="flex-grow-1 min-width-0">' +
                        '<div class="small text-truncate">' + $('<span>').text(a.description).html() + '</div>' +
                        '<div class="text-muted" style="font-size:.7rem">' +
                            $('<span>').text(a.causer).html() +
                            ' · ' + $('<span>').text(a.created_at).html() +
                        '</div>' +
                    '</div>' +
                '</div>';
            }).join('');

            $('#recent-activity-list').html(html);
        }).fail(function () {
            $('#recent-activity-list').html(
                '<div class="text-center py-3 text-muted">No se pudo cargar la actividad</div>'
            );
        });
    }

    function loadActivityChart() {
        @if(Route::has('api.analytics.overview'))
        $.getJSON('{{ route("api.analytics.overview") }}', { range: 'last_14_days' }, function (res) {
            if (!res.status || !res.data || !res.data.chart_data) {
                renderEmptyChart();
                return;
            }

            var rows = res.data.chart_data;
            var dates = rows.map(function (r) { return r.date || ''; });
            var views = rows.map(function (r) { return parseInt(r.screenPageViews || 0, 10); });

            renderLineChart(dates, views);
        }).fail(function () {
            renderEmptyChart();
        });
        @else
        renderEmptyChart();
        @endif
    }

    function renderLineChart(dates, views) {
        $('#activity-chart').dxChart({
            dataSource: dates.map(function (d, i) {
                return { date: d, views: views[i] };
            }),
            series: [{
                argumentField: 'date',
                valueField: 'views',
                name: 'Vistas',
                type: 'line',
                color: '#90bb13',
                point: { visible: false }
            }],
            argumentAxis: {
                label: { font: { size: 11 } },
                tickInterval: { days: 2 }
            },
            valueAxis: {
                label: { font: { size: 11 } },
                min: 0
            },
            legend: { visible: false },
            tooltip: {
                enabled: true,
                customizeTooltip: function (info) {
                    return { text: info.argumentText + ': ' + info.valueText + ' vistas' };
                }
            },
            animation: { enabled: true }
        });
    }

    function renderEmptyChart() {
        $('#activity-chart').html(
            '<div class="d-flex align-items-center justify-content-center h-100 text-muted">' +
            '<i class="fas fa-chart-line me-2"></i>Sin datos de páginas vistas disponibles</div>'
        );
    }

    function loadQueueStats() {
        $.getJSON('{{ route("core.dashboard.queue-stats") }}', function (data) {
            var failed = data.failed_jobs || 0;
            var total = data.total_pending || 0;
            var queues = data.pending_by_queue || {};
            var status = data.horizon_status || 'inactive';

            // Failed jobs badge
            var $badge = $('.queue-failed-badge');
            $badge.text(failed);
            $badge.removeClass('bg-success bg-danger').addClass(failed > 0 ? 'bg-danger' : 'bg-success');

            // Total pending
            $('.queue-total-pending').text(total);

            // Horizon status dot + label
            var statusMap = {
                running: { color: '#13C672', label: 'Activo' },
                paused:  { color: '#FEC90F', label: 'Pausado' },
                inactive: { color: '#adb5bd', label: 'Inactivo' },
            };
            var s = statusMap[status] || statusMap.inactive;
            $('.queue-horizon-dot').css('color', s.color);
            $('.queue-horizon-label').text(s.label);

            // Per-queue breakdown
            var knownQueues = ['default', 'reviews.sync', 'exports', 'backups', 'notifications', 'google-sync'];
            var $perQueue = $('#queue-per-queue').empty();

            // Show known queues first, then any extra ones returned by the server
            var allQueues = knownQueues.concat(
                Object.keys(queues).filter(function (q) { return knownQueues.indexOf(q) === -1; })
            );

            allQueues.forEach(function (queue) {
                var count = queues[queue] || 0;
                var cls = count > 0 ? 'bg-warning text-dark' : 'bg-light text-muted';
                $perQueue.append(
                    '<span class="badge ' + cls + '" style="font-size:.7rem">' +
                        queue + ': ' + count +
                    '</span>'
                );
            });

            // Timestamp
            var now = new Date();
            $('#queue-last-updated').text(
                'Actualizado ' + now.toLocaleTimeString('es', { hour: '2-digit', minute: '2-digit' })
            );
        }).fail(function () {
            $('.queue-failed-badge').text('?').removeClass('bg-success bg-danger').addClass('bg-secondary');
        });
    }

    // Initial load
    loadKpis();
    loadActivity();
    loadActivityChart();
    loadQueueStats();

    // Auto-refresh KPIs every 60 seconds
    setInterval(loadKpis, 60000);

    // Auto-refresh queue stats every 30 seconds
    setInterval(loadQueueStats, 30000);

    // Manual refresh button
    $('#btn-refresh-activity').on('click', function () {
        var $icon = $(this).find('i');
        $icon.addClass('fa-spin');
        loadActivity();
        loadKpis();
        setTimeout(function () { $icon.removeClass('fa-spin'); }, 1000);
    });
}());
</script>
@endpush
