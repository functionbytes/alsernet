@extends('layouts.admin')

@section('title', 'Performance Insights')

@section('content')

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold mb-1">
                        <i class="fas fa-chart-line me-2"></i>Performance Insights
                    </h2>
                    <p class="text-muted mb-0">Análisis avanzado de rendimiento</p>
                </div>
                <div class="d-flex gap-2">
                    <input type="date"
                           class="form-control"
                           id="startDate"
                           value="{{ $startDate }}"
                           style="width: 160px;">
                    <input type="date"
                           class="form-control"
                           id="endDate"
                           value="{{ $endDate }}"
                           style="width: 160px;">
                    <button class="btn btn-primary" id="applyDateRange">
                        <i class="fas fa-sync-alt"></i> Actualizar
                    </button>
                    <a href="{{ route('admin.social.insights.export', ['start_date' => $startDate, 'end_date' => $endDate]) }}"
                       class="btn btn-outline-secondary">
                        <i class="fas fa-download"></i> Export CSV
                    </a>
                </div>
            </div>

            <!-- Overview Cards -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <p class="text-muted mb-1">Total Posts</p>
                                    <h3 class="mb-0">{{ number_format($insights['overview']['total_posts']) }}</h3>
                                    @php
                                        $postsGrowth = $insights['growth']['posts_growth'] ?? 0;
                                    @endphp
                                    <small class="text-{{ $postsGrowth >= 0 ? 'success' : 'danger' }}">
                                        <i class="fas fa-arrow-{{ $postsGrowth >= 0 ? 'up' : 'down' }}"></i>
                                        {{ abs($postsGrowth) }}% vs período anterior
                                    </small>
                                </div>
                                <div class="bg-primary bg-opacity-10 rounded p-3">
                                    <i class="fas fa-file-alt fs-1 text-primary"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <p class="text-muted mb-1">Total Reach</p>
                                    <h3 class="mb-0">{{ number_format($insights['overview']['total_reach']) }}</h3>
                                    @php
                                        $reachGrowth = $insights['growth']['reach_growth'] ?? 0;
                                    @endphp
                                    <small class="text-{{ $reachGrowth >= 0 ? 'success' : 'danger' }}">
                                        <i class="fas fa-arrow-{{ $reachGrowth >= 0 ? 'up' : 'down' }}"></i>
                                        {{ abs($reachGrowth) }}% vs período anterior
                                    </small>
                                </div>
                                <div class="bg-info bg-opacity-10 rounded p-3">
                                    <i class="fas fa-users fs-1 text-info"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <p class="text-muted mb-1">Engagement Rate</p>
                                    <h3 class="mb-0">{{ $insights['engagement']['engagement_rate'] }}%</h3>
                                    @php
                                        $engagementGrowth = $insights['growth']['engagement_growth'] ?? 0;
                                    @endphp
                                    <small class="text-{{ $engagementGrowth >= 0 ? 'success' : 'danger' }}">
                                        <i class="fas fa-arrow-{{ $engagementGrowth >= 0 ? 'up' : 'down' }}"></i>
                                        {{ abs($engagementGrowth) }}% vs período anterior
                                    </small>
                                </div>
                                <div class="bg-success bg-opacity-10 rounded p-3">
                                    <i class="fas fa-heart fs-1 text-success"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <p class="text-muted mb-1">Total Engagements</p>
                                    <h3 class="mb-0">{{ number_format($insights['overview']['total_engagements']) }}</h3>
                                    <small class="text-muted">
                                        Avg: {{ number_format($insights['overview']['avg_engagements']) }}/post
                                    </small>
                                </div>
                                <div class="bg-warning bg-opacity-10 rounded p-3">
                                    <i class="fas fa-chart-bar fs-1 text-warning"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="row g-3 mb-4">
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">
                                <i class="fas fa-chart-line me-2"></i>Tendencia de Engagement
                            </h5>
                        </div>
                        <div class="card-body">
                            <canvas id="trendsChart" height="80"></canvas>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">
                                <i class="fas fa-share me-2"></i>Por Red Social
                            </h5>
                        </div>
                        <div class="card-body">
                            <canvas id="networkChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Performance by Hour & Type -->
            <div class="row g-3 mb-4">
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">
                                <i class="fas fa-clock me-2"></i>Performance por Hora
                            </h5>
                        </div>
                        <div class="card-body">
                            <canvas id="hourlyChart" height="100"></canvas>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">
                                <i class="fas fa-th-large me-2"></i>Performance por Tipo
                            </h5>
                        </div>
                        <div class="card-body">
                            <canvas id="typeChart" height="100"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Posts -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="fas fa-trophy me-2"></i>Top Performing Posts
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Content</th>
                                    <th>Network</th>
                                    <th>Reach</th>
                                    <th>Engagements</th>
                                    <th>Engagement Rate</th>
                                    <th>Published</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($insights['top_posts'] as $index => $post)
                                    <tr>
                                        <td>
                                            @if($index === 0)
                                                <span class="badge bg-warning text-dark">👑</span>
                                            @else
                                                {{ $index + 1 }}
                                            @endif
                                        </td>
                                        <td>{{ Str::limit($post['content'], 60) }}</td>
                                        <td>
                                            @php
                                                $networkClass = match($post['network']) {
                                                    'facebook' => 'ti-brand-facebook text-primary',
                                                    'instagram' => 'ti-brand-instagram text-danger',
                                                    'twitter' => 'ti-brand-x text-dark',
                                                    'linkedin' => 'ti-brand-linkedin text-info',
                                                    default => 'ti-share'
                                                };
                                            @endphp
                                            <i class="ti {{ $networkClass }}"></i>
                                            {{ ucfirst($post['network']) }}
                                        </td>
                                        <td>{{ number_format($post['reach']) }}</td>
                                        <td>
                                            <span class="badge bg-light text-dark">
                                                {{ number_format($post['engagements']) }}
                                            </span>
                                        </td>
                                        <td>
                                            @php
                                                $rate = $post['engagement_rate'];
                                                $color = $rate >= 5 ? 'success' : ($rate >= 2 ? 'warning' : 'secondary');
                                            @endphp
                                            <span class="badge bg-{{ $color }}">{{ $rate }}%</span>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                {{ \Carbon\Carbon::parse($post['published_at'])->format('M d, Y H:i') }}
                                            </small>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
.card { transition: transform 0.2s; }
.card:hover { transform: translateY(-2px); }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// Date Range Update
document.getElementById('applyDateRange').addEventListener('click', function() {
    const start = document.getElementById('startDate').value;
    const end = document.getElementById('endDate').value;
    window.location.href = `?start_date=${start}&end_date=${end}`;
});

// Trends Chart
const trendsCtx = document.getElementById('trendsChart').getContext('2d');
new Chart(trendsCtx, {
    type: 'line',
    data: {
        labels: @json(array_column($insights['trends'], 'date')),
        datasets: [{
            label: 'Engagements',
            data: @json(array_column($insights['trends'], 'engagements')),
            borderColor: 'rgb(75, 192, 192)',
            tension: 0.1,
            fill: true,
            backgroundColor: 'rgba(75, 192, 192, 0.1)'
        }, {
            label: 'Reach',
            data: @json(array_column($insights['trends'], 'reach')),
            borderColor: 'rgb(54, 162, 235)',
            tension: 0.1,
            fill: true,
            backgroundColor: 'rgba(54, 162, 235, 0.1)'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: { legend: { position: 'top' } }
    }
});

// Network Chart
const networkCtx = document.getElementById('networkChart').getContext('2d');
new Chart(networkCtx, {
    type: 'doughnut',
    data: {
        labels: @json(array_column($insights['by_network'], 'network')),
        datasets: [{
            data: @json(array_column($insights['by_network'], 'total_engagements')),
            backgroundColor: ['#3b5998', '#E4405F', '#000000', '#0077b5']
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: { legend: { position: 'bottom' } }
    }
});

// Hourly Chart
const hourlyCtx = document.getElementById('hourlyChart').getContext('2d');
new Chart(hourlyCtx, {
    type: 'bar',
    data: {
        labels: @json(array_column($insights['by_hour'], 'hour')),
        datasets: [{
            label: 'Avg Engagement Rate',
            data: @json(array_column($insights['by_hour'], 'engagement_rate')),
            backgroundColor: 'rgba(54, 162, 235, 0.5)',
            borderColor: 'rgb(54, 162, 235)',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        scales: { y: { beginAtZero: true } }
    }
});

// Type Chart
const typeCtx = document.getElementById('typeChart').getContext('2d');
new Chart(typeCtx, {
    type: 'bar',
    data: {
        labels: @json(array_column($insights['by_type'], 'type')),
        datasets: [{
            label: 'Engagement Rate',
            data: @json(array_column($insights['by_type'], 'engagement_rate')),
            backgroundColor: ['#198754', '#0dcaf0', '#ffc107', '#dc3545']
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        indexAxis: 'y',
        scales: { x: { beginAtZero: true } }
    }
});
</script>
@endpush
