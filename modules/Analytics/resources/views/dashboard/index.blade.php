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
        <div class="card mb-4">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <label for="rangeSelect" class="form-label fw-semibold mb-0">Período:</label>
                    </div>
                    <div class="col-auto">
                        <select class="form-select form-select-sm" id="rangeSelect" onchange="window.location.href = '?range=' + this.value">
                            <option value="today" {{ $range === 'today' ? 'selected' : '' }}>Hoy</option>
                            <option value="last_7_days" {{ $range === 'last_7_days' ? 'selected' : '' }}>Últimos 7 días</option>
                            <option value="last_30_days" {{ $range === 'last_30_days' ? 'selected' : '' }}>Últimos 30 días</option>
                            <option value="this_month" {{ $range === 'this_month' ? 'selected' : '' }}>Este mes</option>
                            <option value="last_month" {{ $range === 'last_month' ? 'selected' : '' }}>Mes anterior</option>
                            <option value="this_year" {{ $range === 'this_year' ? 'selected' : '' }}>Este año</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Overview Stats -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-start">
                            <div class="flex-grow-1">
                                <p class="text-muted small mb-1">
                                    <i class="fas fa-eye me-1"></i>Sesiones
                                </p>
                                <h4 class="mb-0 fw-bold">{{ number_format($overviewData['sessions']) }}</h4>
                            </div>
                            <div class="badge bg-primary-subtle text-primary rounded-circle p-3">
                                <i class="fas fa-chart-line"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-start">
                            <div class="flex-grow-1">
                                <p class="text-muted small mb-1">
                                    <i class="fas fa-users me-1"></i>Usuarios
                                </p>
                                <h4 class="mb-0 fw-bold">{{ number_format($overviewData['users']) }}</h4>
                            </div>
                            <div class="badge bg-success-subtle text-success rounded-circle p-3">
                                <i class="fas fa-user"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-start">
                            <div class="flex-grow-1">
                                <p class="text-muted small mb-1">
                                    <i class="fas fa-pager me-1"></i>Vistas de Página
                                </p>
                                <h4 class="mb-0 fw-bold">{{ number_format($overviewData['pageviews']) }}</h4>
                            </div>
                            <div class="badge bg-info-subtle text-info rounded-circle p-3">
                                <i class="fas fa-file"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-start">
                            <div class="flex-grow-1">
                                <p class="text-muted small mb-1">
                                    <i class="fas fa-bolt me-1"></i>Tasa de Rebote
                                </p>
                                <h4 class="mb-0 fw-bold">{{ round($overviewData['bounce_rate'] * 100, 1) }}%</h4>
                            </div>
                            <div class="badge bg-warning-subtle text-warning rounded-circle p-3">
                                <i class="fas fa-percentage"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row mb-4">
            <!-- Line Chart -->
            <div class="col-lg-8 mb-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-light border-bottom">
                        <h6 class="mb-0 fw-bold">
                            <i class="fas fa-chart-line me-2"></i>Sesiones y Vistas de Página
                        </h6>
                    </div>
                    <div class="card-body">
                        <div id="daily-chart" style="height: 300px;"></div>
                    </div>
                </div>
            </div>

            <!-- Top Browsers -->
            <div class="col-lg-4 mb-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-light border-bottom">
                        <h6 class="mb-0 fw-bold">
                            <i class="fab fa-chrome me-2"></i>Navegadores Principales
                        </h6>
                    </div>
                    <div class="card-body">
                        @if (count($topBrowsers) > 0)
                            <div id="browser-chart" style="height: 300px;"></div>
                        @else
                            <p class="text-muted text-center py-5">No hay datos disponibles</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Pages -->
        <div class="row mb-4">
            <div class="col-lg-6 mb-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-light border-bottom">
                        <h6 class="mb-0 fw-bold">
                            <i class="fas fa-file-alt me-2"></i>Páginas Principales
                        </h6>
                    </div>
                    <div class="card-body">
                        @if (count($topPages) > 0)
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Página</th>
                                            <th class="text-end">Vistas</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($topPages as $page)
                                            <tr>
                                                <td>
                                                    <small class="text-truncate d-block" title="{{ $page['title'] }}">
                                                        {{ Str::limit($page['title'], 30) }}
                                                    </small>
                                                    <small class="text-muted text-truncate d-block" title="{{ $page['url'] }}">
                                                        {{ Str::limit($page['url'], 40) }}
                                                    </small>
                                                </td>
                                                <td class="text-end fw-semibold">{{ number_format($page['views']) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-muted text-center py-5">No hay datos disponibles</p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Top Referrers -->
            <div class="col-lg-6 mb-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-light border-bottom">
                        <h6 class="mb-0 fw-bold">
                            <i class="fas fa-link me-2"></i>Fuentes Principales
                        </h6>
                    </div>
                    <div class="card-body">
                        @if (count($topReferrers) > 0)
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Fuente</th>
                                            <th class="text-end">Vistas</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($topReferrers as $referrer)
                                            <tr>
                                                <td>
                                                    <small title="{{ $referrer['source'] }}">
                                                        {{ Str::limit($referrer['source'], 35) }}
                                                    </small>
                                                </td>
                                                <td class="text-end fw-semibold">{{ number_format($referrer['views']) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-muted text-center py-5">No hay datos disponibles</p>
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
                    colors: ["#90bb13", "#13C672"],
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
                    colors: ["#90bb13", "#13C672", "#FA896B", "#FEC90F", "#539BFF"],
                    tooltip: {
                        theme: "light"
                    }
                });
                browserChart.render();
            @endif
        </script>
    @endpush

@endsection
