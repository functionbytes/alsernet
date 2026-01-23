@extends('layouts.admin')

@section('title', 'API Usage Dashboard')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col">
            <h1 class="h3"><i class="fa fa-graph-up"></i> API Usage Dashboard</h1>
            <p class="text-muted">Monitor API rate limits and usage statistics</p>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Total Requests Today</h6>
                    <h2 class="mb-0" id="total-requests">{{ $userStats->sum('today') }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Active Users</h6>
                    <h2 class="mb-0">{{ $userStats->where('today', '>', 0)->count() }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Top Endpoint</h6>
                    <h2 class="mb-0 small">{{ $endpoints[0]['endpoint'] ?? 'N/A' }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Peak Hour</h6>
                    <h2 class="mb-0" id="peak-hour">
                        {{ collect($hourlyUsage)->sortByDesc('requests')->first()['hour'] ?? 'N/A' }}
                    </h2>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Hourly Usage Chart -->
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fa fa-bar-chart"></i> Hourly Usage (Today)</h5>
                </div>
                <div class="card-body">
                    <canvas id="hourly-chart" height="100"></canvas>
                </div>
            </div>

            <!-- Top Endpoints -->
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fa fa-list-ol"></i> Top Endpoints</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Endpoint</th>
                                    <th class="text-end">Requests</th>
                                    <th width="200">Usage</th>
                                </tr>
                            </thead>
                            <tbody id="endpoints-table">
                                @forelse($endpoints as $endpoint)
                                <tr>
                                    <td><code>{{ $endpoint['endpoint'] }}</code></td>
                                    <td class="text-end">{{ number_format($endpoint['requests']) }}</td>
                                    <td>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar" role="progressbar"
                                                 style="width: {{ ($endpoint['requests'] / max(array_column($endpoints, 'requests')) * 100) }}%">
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted">No data available</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- User Stats -->
        <div class="col-lg-4">
            <div class="card sticky-top" style="top: 20px;">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fa fa-people"></i> User Activity</h5>
                </div>
                <div class="card-body" style="max-height: 600px; overflow-y: auto;">
                    @foreach($userStats as $user)
                    <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                        <div>
                            <strong>{{ $user['name'] }}</strong><br>
                            <small class="text-muted">Limit: {{ number_format($user['limit']) }}/min</small>
                        </div>
                        <div class="text-end">
                            <h5 class="mb-0 {{ $user['today'] > 0 ? 'text-primary' : 'text-muted' }}">
                                {{ number_format($user['today']) }}
                            </h5>
                            <small class="text-muted">requests</small>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
$(document).ready(function() {
    // Hourly usage data
    const hourlyData = @json($hourlyUsage);

    // Create chart
    const ctx = $('#hourly-chart')[0].getContext('2d');
    const chart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: hourlyData.map(h => h.hour),
            datasets: [{
                label: 'Requests',
                data: hourlyData.map(h => h.requests),
                backgroundColor: 'rgba(54, 162, 235, 0.5)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.parsed.y + ' requests';
                        }
                    }
                }
            }
        }
    });

    // Auto-refresh every 30 seconds
    setInterval(function() {
        $.ajax({
            url: '{{ route("admin.api-usage.data") }}',
            method: 'GET',
            success: function(response) {
                // Update chart
                chart.data.datasets[0].data = response.hourly_usage.map(h => h.requests);
                chart.update();

                // Update total
                const total = response.hourly_usage.reduce((sum, h) => sum + h.requests, 0);
                $('#total-requests').text(total.toLocaleString());

                // Update endpoints table
                updateEndpointsTable(response.top_endpoints);
            },
            error: function(xhr) {
                console.error('Failed to refresh data:', xhr);
            }
        });
    }, 30000);

    function updateEndpointsTable(endpoints) {
        if (endpoints.length === 0) {
            $('#endpoints-table').html('<tr><td colspan="3" class="text-center text-muted">No data available</td></tr>');
            return;
        }

        const maxRequests = Math.max(...endpoints.map(e => e.requests));
        let html = '';

        endpoints.forEach(endpoint => {
            const percentage = (endpoint.requests / maxRequests * 100);
            html += `
                <tr>
                    <td><code>${endpoint.endpoint}</code></td>
                    <td class="text-end">${endpoint.requests.toLocaleString()}</td>
                    <td>
                        <div class="progress" style="height: 20px;">
                            <div class="progress-bar" role="progressbar" style="width: ${percentage}%"></div>
                        </div>
                    </td>
                </tr>
            `;
        });

        $('#endpoints-table').html(html);
    }
});
</script>
@endsection
