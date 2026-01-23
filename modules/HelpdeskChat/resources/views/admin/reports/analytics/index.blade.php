@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fa fa-graph-up"></i> Conversation Analytics</h2>

        <!-- Date Range Filter -->
        <form method="GET" action="{{ route('admin.reports.analytics.index') }}" class="d-flex gap-2">
            <input type="date" name="start_date" class="form-control" value="{{ $startDate->toDateString() }}" required>
            <input type="date" name="end_date" class="form-control" value="{{ $endDate->toDateString() }}" required>
            <button type="submit" class="btn btn-primary">
                <i class="fa fa-funnel"></i> Filter
            </button>
            <button type="button" class="btn btn-outline-secondary" id="export-btn">
                <i class="fa fa-download"></i> Export
            </button>
        </form>
    </div>

    <!-- Overview Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6 class="card-title">Total Conversations</h6>
                    <h2 class="mb-0">{{ number_format($overview['total_conversations']) }}</h2>
                    <small>{{ $startDate->format('M d') }} - {{ $endDate->format('M d, Y') }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6 class="card-title">Resolved</h6>
                    <h2 class="mb-0">{{ number_format($overview['resolved_conversations']) }}</h2>
                    <small>{{ $overview['resolution_rate'] }}% resolution rate</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h6 class="card-title">Avg Response Time</h6>
                    <h2 class="mb-0">{{ $overview['avg_response_time'] }}</h2>
                    <small>minutes</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <h6 class="card-title">Avg Resolution Time</h6>
                    <h2 class="mb-0">{{ $overview['avg_resolution_time'] }}</h2>
                    <small>hours</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row 1 -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fa fa-calendar3"></i> Conversations Over Time</h5>
                </div>
                <div class="card-body">
                    <canvas id="conversationsByDayChart" height="80"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fa fa-pie-chart"></i> By Status</h5>
                </div>
                <div class="card-body">
                    <canvas id="conversationsByStatusChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row 2 -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fa fa-inbox"></i> By Channel</h5>
                </div>
                <div class="card-body">
                    <canvas id="conversationsByChannelChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fa fa-trophy"></i> Top Performing Agents</h5>
                </div>
                <div class="card-body">
                    <canvas id="topAgentsChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Busiest Hours Chart -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fa fa-clock"></i> Busiest Hours of the Day</h5>
                </div>
                <div class="card-body">
                    <canvas id="busiestHoursChart" height="60"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .card {
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        border: none;
    }
    .card-header {
        border-bottom: 1px solid #dee2e6;
    }
</style>
@endpush

@push('scripts')
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
$(document).ready(function() {
    // Chart colors
    const colors = {
        primary: '#0d6efd',
        success: '#198754',
        danger: '#dc3545',
        warning: '#ffc107',
        info: '#0dcaf0',
        secondary: '#6c757d',
        light: '#f8f9fa',
        dark: '#212529'
    };

    const colorPalette = [
        '#0d6efd', '#198754', '#ffc107', '#dc3545', '#0dcaf0',
        '#6f42c1', '#fd7e14', '#20c997', '#d63384', '#6c757d'
    ];

    // Conversations by Day Chart
    const conversationsByDayData = @json($byDay);
    new Chart(document.getElementById('conversationsByDayChart'), {
        type: 'line',
        data: {
            labels: Object.keys(conversationsByDayData),
            datasets: [{
                label: 'Conversations',
                data: Object.values(conversationsByDayData),
                borderColor: colors.primary,
                backgroundColor: colors.primary + '20',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    mode: 'index',
                    intersect: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });

    // Conversations by Status Chart
    const conversationsByStatusData = @json($byStatus);
    new Chart(document.getElementById('conversationsByStatusChart'), {
        type: 'doughnut',
        data: {
            labels: Object.keys(conversationsByStatusData),
            datasets: [{
                data: Object.values(conversationsByStatusData),
                backgroundColor: [
                    colors.warning,
                    colors.success,
                    colors.primary,
                    colors.secondary,
                    colors.info
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    // Conversations by Channel Chart
    const conversationsByChannelData = @json($byChannel);
    new Chart(document.getElementById('conversationsByChannelChart'), {
        type: 'bar',
        data: {
            labels: Object.keys(conversationsByChannelData),
            datasets: [{
                label: 'Conversations',
                data: Object.values(conversationsByChannelData),
                backgroundColor: colorPalette
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });

    // Top Agents Chart
    const topAgentsData = @json($topAgents);
    new Chart(document.getElementById('topAgentsChart'), {
        type: 'bar',
        data: {
            labels: Object.keys(topAgentsData),
            datasets: [{
                label: 'Resolved Conversations',
                data: Object.values(topAgentsData),
                backgroundColor: colors.success
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });

    // Busiest Hours Chart
    const busiestHoursData = @json($busiestHours);
    new Chart(document.getElementById('busiestHoursChart'), {
        type: 'bar',
        data: {
            labels: Object.keys(busiestHoursData),
            datasets: [{
                label: 'Conversations',
                data: Object.values(busiestHoursData),
                backgroundColor: colors.info
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });

    // Export functionality
    $('#export-btn').on('click', function() {
        const startDate = $('input[name="start_date"]').val();
        const endDate = $('input[name="end_date"]').val();

        $.ajax({
            url: '{{ route("admin.reports.analytics.export") }}',
            method: 'GET',
            data: {
                start_date: startDate,
                end_date: endDate
            },
            success: function(data) {
                // Create download link
                const dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(data, null, 2));
                const downloadAnchor = document.createElement('a');
                downloadAnchor.setAttribute("href", dataStr);
                downloadAnchor.setAttribute("download", `analytics_${startDate}_${endDate}.json`);
                document.body.appendChild(downloadAnchor);
                downloadAnchor.click();
                downloadAnchor.remove();
            },
            error: function(xhr) {
                alert('Error exporting data');
            }
        });
    });
});
</script>
@endpush
@endsection
