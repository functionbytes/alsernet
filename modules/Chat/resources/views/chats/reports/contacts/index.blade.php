@extends('layouts.theme')

@section('title', 'Customers Reports')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col">
            <h1 class="h3">Contact Reports</h1>
            <p class="text-muted">Analytics and insights about your contacts</p>
        </div>
        <div class="col-auto">
            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#dateRangeModal">
                <i class="fa fa-calendar-range"></i> Date Range
            </button>
            <button type="button" class="btn btn-primary" onclick="exportReport()">
                <i class="fa fa-download"></i> Export Report
            </button>
        </div>
    </div>

    <!-- Date Range Display -->
    <div class="alert alert-info mb-4">
        <i class="fa fa-info-circle"></i>
        Showing data from <strong>{{ $dateRange['start']->format('M d, Y') }}</strong> to <strong>{{ $dateRange['end']->format('M d, Y') }}</strong>
    </div>

    <!-- Key Metrics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Total Contacts</h6>
                    <h2 class="mb-0">{{ number_format($metrics['total_contacts']) }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted mb-2">New Contacts</h6>
                    <h2 class="mb-0 text-success">{{ number_format($metrics['new_contacts']) }}</h2>
                    <small class="text-muted">in selected period</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Active Contacts</h6>
                    <h2 class="mb-0 text-primary">{{ number_format($metrics['active_contacts']) }}</h2>
                    <small class="text-muted">with recent activity</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Blocked Contacts</h6>
                    <h2 class="mb-0 text-danger">{{ number_format($metrics['blocked_contacts']) }}</h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Contact Growth Trend</h5>
                </div>
                <div class="card-body">
                    <canvas id="growthChart" height="250"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Activity Trend</h5>
                </div>
                <div class="card-body">
                    <canvas id="activityChart" height="250"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Distribution Charts -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Contacts by Inbox</h5>
                </div>
                <div class="card-body">
                    <canvas id="inboxChart" height="250"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Top 10 Countries</h5>
                </div>
                <div class="card-body">
                    <canvas id="countryChart" height="250"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Date Range Modal -->
<div class="modal fade" id="dateRangeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Select Date Range</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="GET" action="{{ route('settings.chat.reports.contacts.index') }}">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Start Date</label>
                        <input type="date" name="start_date" class="form-control" value="{{ $dateRange['start']->format('Y-m-d') }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">End Date</label>
                        <input type="date" name="end_date" class="form-control" value="{{ $dateRange['end']->format('Y-m-d') }}">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Apply</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const metrics = @json($metrics);

// Growth Trend Chart
const growthCtx = document.getElementById('growthChart').getContext('2d');
new Chart(growthCtx, {
    type: 'line',
    data: {
        labels: metrics.growth_trend.map(item => item.date),
        datasets: [{
            label: 'New Contacts',
            data: metrics.growth_trend.map(item => item.count),
            borderColor: 'rgb(75, 192, 192)',
            backgroundColor: 'rgba(75, 192, 192, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
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

// Activity Trend Chart
const activityCtx = document.getElementById('activityChart').getContext('2d');
new Chart(activityCtx, {
    type: 'line',
    data: {
        labels: metrics.activity_trend.map(item => item.date),
        datasets: [{
            label: 'Active Contacts',
            data: metrics.activity_trend.map(item => item.count),
            borderColor: 'rgb(54, 162, 235)',
            backgroundColor: 'rgba(54, 162, 235, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
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

// Inbox Distribution Chart
const inboxCtx = document.getElementById('inboxChart').getContext('2d');
new Chart(inboxCtx, {
    type: 'doughnut',
    data: {
        labels: metrics.contacts_by_inbox.map(item => item.label),
        datasets: [{
            data: metrics.contacts_by_inbox.map(item => item.value),
            backgroundColor: [
                'rgba(255, 99, 132, 0.8)',
                'rgba(54, 162, 235, 0.8)',
                'rgba(255, 206, 86, 0.8)',
                'rgba(75, 192, 192, 0.8)',
                'rgba(153, 102, 255, 0.8)',
                'rgba(255, 159, 64, 0.8)'
            ]
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});

// Country Distribution Chart
const countryCtx = document.getElementById('countryChart').getContext('2d');
new Chart(countryCtx, {
    type: 'bar',
    data: {
        labels: metrics.contacts_by_country.map(item => item.label),
        datasets: [{
            label: 'Contacts',
            data: metrics.contacts_by_country.map(item => item.value),
            backgroundColor: 'rgba(75, 192, 192, 0.8)'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        indexAxis: 'y',
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

function exportReport() {
    const params = new URLSearchParams(window.location.search);
    $.ajax({
        url: '{{ route("settings.reports.contacts.export") }}?' + params,
        type: 'POST',
        contentType: 'application/json',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        success: function(data) {
            alert(data.message);
            window.open(data.download_url, '_blank');
        },
        error: function(err) {
            alert('Error exporting report');
        }
    });
}
</script>
@endsection
