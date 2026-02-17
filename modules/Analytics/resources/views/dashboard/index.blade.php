@extends('layouts.theme')

@section('content')

    @include('core::components.card', ['title' => 'Analytics Dashboard'])

    <div class="widget-content">

        @if (!$isConfigured)
            <div class="alert alert-warning border-0 bg-warning-subtle text-warning mb-4">
                <div class="d-flex align-items-start gap-2">
                    <i class="fas fa-exclamation-triangle mt-1"></i>
                    <div>
                        <strong>Analytics no configurado</strong><br>
                        <small>Por favor, <a href="{{ route('settings.analytics.index') }}" class="text-warning fw-semibold">configura Google Analytics</a> primero para ver los datos.</small>
                    </div>
                </div>
            </div>
        @endif

        <!-- Period Selector -->
        <div class="mb-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <label for="rangeSelect" class="form-label fw-semibold mb-2 d-block">Período:</label>
                <select class="form-select" id="rangeSelect" onchange="window.location.href = '?range=' + this.value" style="width: 180px;">
                    <option value="today" {{ $range === 'today' ? 'selected' : '' }}>Hoy</option>
                    <option value="last_7_days" {{ $range === 'last_7_days' ? 'selected' : '' }}>Últimos 7 días</option>
                    <option value="last_30_days" {{ $range === 'last_30_days' ? 'selected' : '' }}>Últimos 30 días</option>
                    <option value="this_month" {{ $range === 'this_month' ? 'selected' : '' }}>Este mes</option>
                    <option value="last_month" {{ $range === 'last_month' ? 'selected' : '' }}>Mes anterior</option>
                    <option value="this_year" {{ $range === 'this_year' ? 'selected' : '' }}>Este año</option>
                </select>
            </div>
        </div>

        <!-- Overview Stats with Modern Cards -->
        <div class="row mb-4 g-3">
            <!-- Sesiones Card -->
            <div class="col-lg-3 col-md-6">
                <div class="card border-0 rounded-lg h-100" style="background: linear-gradient(135deg, #c8f7dc 0%, #a1ead8 100%); min-height: 140px;">
                    <div class="card-body d-flex flex-column justify-content-between">
                        <div>
                            <small class="text-muted fw-semibold d-block mb-2">
                                <i class="fas fa-chart-line me-1"></i>Sesiones
                            </small>
                            <h3 class="mb-0 fw-bold text-dark">{{ number_format($overviewData['sessions']) }}</h3>
                        </div>
                        <div class="text-end">
                            <i class="fas fa-eye fa-2x" style="color: rgba(13, 160, 113, 0.3);"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Usuarios Card -->
            <div class="col-lg-3 col-md-6">
                <div class="card border-0 rounded-lg h-100" style="background: linear-gradient(135deg, #ffd8d8 0%, #ffc9c9 100%); min-height: 140px;">
                    <div class="card-body d-flex flex-column justify-content-between">
                        <div>
                            <small class="text-muted fw-semibold d-block mb-2">
                                <i class="fas fa-users me-1"></i>Usuarios
                            </small>
                            <h3 class="mb-0 fw-bold text-dark">{{ number_format($overviewData['users']) }}</h3>
                        </div>
                        <div class="text-end">
                            <i class="fas fa-user fa-2x" style="color: rgba(250, 137, 107, 0.3);"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Vistas de Página Card -->
            <div class="col-lg-3 col-md-6">
                <div class="card border-0 rounded-lg h-100" style="background: linear-gradient(135deg, #d4e8ff 0%, #c0deff 100%); min-height: 140px;">
                    <div class="card-body d-flex flex-column justify-content-between">
                        <div>
                            <small class="text-muted fw-semibold d-block mb-2">
                                <i class="fas fa-file me-1"></i>Vistas de Página
                            </small>
                            <h3 class="mb-0 fw-bold text-dark">{{ number_format($overviewData['pageviews']) }}</h3>
                        </div>
                        <div class="text-end">
                            <i class="fas fa-pager fa-2x" style="color: rgba(57, 139, 230, 0.3);"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tasa de Rebote Card -->
            <div class="col-lg-3 col-md-6">
                <div class="card border-0 rounded-lg h-100" style="background: linear-gradient(135deg, #ffe5cc 0%, #ffd8b8 100%); min-height: 140px;">
                    <div class="card-body d-flex flex-column justify-content-between">
                        <div>
                            <small class="text-muted fw-semibold d-block mb-2">
                                <i class="fas fa-percentage me-1"></i>Tasa de Rebote
                            </small>
                            <h3 class="mb-0 fw-bold text-dark">{{ round($overviewData['bounce_rate'] * 100, 1) }}%</h3>
                        </div>
                        <div class="text-end">
                            <i class="fas fa-chart-pie fa-2x" style="color: rgba(254, 201, 15, 0.3);"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row mb-4 g-3">
            <!-- Line Chart -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-bottom py-3">
                        <h5 class="mb-0 fw-bold text-dark">
                            <i class="fas fa-chart-line me-2" style="color: #539BFF;"></i>Sesiones y Vistas de Página
                        </h5>
                        <small class="text-muted d-block mt-1">Tendencia diaria</small>
                    </div>
                    <div class="card-body">
                        <div id="daily-chart" style="height: 350px;"></div>
                    </div>
                </div>
            </div>

            <!-- Top Browsers -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-bottom py-3">
                        <h5 class="mb-0 fw-bold text-dark">
                            <i class="fab fa-chrome me-2" style="color: #539BFF;"></i>Navegadores
                        </h5>
                        <small class="text-muted d-block mt-1">Principales navegadores</small>
                    </div>
                    <div class="card-body">
                        @if (count($topBrowsers) > 0)
                            <div id="browser-chart" style="height: 350px;"></div>
                        @else
                            <p class="text-muted text-center py-5">No hay datos disponibles</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Pages & Referrers -->
        <div class="row g-3">
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-bottom py-3">
                        <h5 class="mb-0 fw-bold text-dark">
                            <i class="fas fa-file-alt me-2" style="color: #539BFF;"></i>Páginas Principales
                        </h5>
                        <small class="text-muted d-block mt-1">Páginas más visitadas</small>
                    </div>
                    <div class="card-body">
                        @if (count($topPages) > 0)
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr style="border-bottom: 2px solid #f0f0f0;">
                                            <th class="border-0">Página</th>
                                            <th class="text-end border-0">Vistas</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($topPages as $page)
                                            <tr>
                                                <td class="py-3">
                                                    <small class="text-truncate d-block fw-semibold" title="{{ $page['title'] }}">
                                                        {{ Str::limit($page['title'], 30) }}
                                                    </small>
                                                    <small class="text-muted text-truncate d-block" title="{{ $page['url'] }}">
                                                        {{ Str::limit($page['url'], 40) }}
                                                    </small>
                                                </td>
                                                <td class="text-end py-3 fw-semibold">
                                                    <span class="badge bg-primary-subtle text-primary">{{ number_format($page['views']) }}</span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-5">
                                <i class="fas fa-inbox fa-3x text-muted mb-3" style="opacity: 0.3;"></i>
                                <p class="text-muted">No hay datos disponibles</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Top Referrers -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-bottom py-3">
                        <h5 class="mb-0 fw-bold text-dark">
                            <i class="fas fa-link me-2" style="color: #539BFF;"></i>Fuentes Principales
                        </h5>
                        <small class="text-muted d-block mt-1">Fuentes de tráfico</small>
                    </div>
                    <div class="card-body">
                        @if (count($topReferrers) > 0)
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr style="border-bottom: 2px solid #f0f0f0;">
                                            <th class="border-0">Fuente</th>
                                            <th class="text-end border-0">Vistas</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($topReferrers as $referrer)
                                            <tr>
                                                <td class="py-3">
                                                    <small class="fw-semibold" title="{{ $referrer['source'] }}">
                                                        {{ Str::limit($referrer['source'], 35) }}
                                                    </small>
                                                </td>
                                                <td class="text-end py-3 fw-semibold">
                                                    <span class="badge bg-success-subtle text-success">{{ number_format($referrer['views']) }}</span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-5">
                                <i class="fas fa-inbox fa-3x text-muted mb-3" style="opacity: 0.3;"></i>
                                <p class="text-muted">No hay datos disponibles</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/apexcharts@latest/dist/apexcharts.min.js"></script>
        <script>
            // Daily Chart
            @if (count($dailyData['dates']) > 0)
                const dailyChart = new ApexCharts(document.querySelector("#daily-chart"), {
                    series: [
                        {
                            name: "Sesiones",
                            data: @json($dailyData['sessions'])
                        },
                        {
                            name: "Vistas de Página",
                            data: @json($dailyData['pageviews'])
                        }
                    ],
                    chart: {
                        type: "area",
                        stacked: false,
                        height: 300,
                        toolbar: {
                            show: false
                        }
                    },
                    colors: ["#081A28", "#13C672"],
                    stroke: {
                        curve: "smooth",
                        width: 2
                    },
                    xaxis: {
                        categories: @json($dailyData['dates']),
                        type: "datetime"
                    },
                    yaxis: {
                        labels: {
                            formatter: function(val) {
                                return Math.round(val);
                            }
                        }
                    },
                    grid: {
                        show: true,
                        borderColor: "#f0f0f0"
                    },
                    tooltip: {
                        theme: "light"
                    }
                });
                dailyChart.render();
            @endif

            // Browser Chart
            @if (count($topBrowsers) > 0)
                const browserNames = @json(array_column($topBrowsers, 'name'));
                const browserSessions = @json(array_column($topBrowsers, 'sessions'));

                const browserChart = new ApexCharts(document.querySelector("#browser-chart"), {
                    series: browserSessions,
                    labels: browserNames,
                    chart: {
                        type: "donut",
                        height: 300
                    },
                    colors: ["#081A28", "#13C672", "#FA896B", "#FEC90F", "#539BFF"],
                    tooltip: {
                        theme: "light"
                    }
                });
                browserChart.render();
            @endif
        </script>
    @endpush

@endsection
