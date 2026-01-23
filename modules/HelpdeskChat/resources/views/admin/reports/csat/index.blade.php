@extends('layouts.admin')

@section('title', 'CSAT Report')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col">
            <h1 class="h3">Customer Satisfaction (CSAT) Report</h1>
            <p class="text-muted">Monitor customer satisfaction scores and feedback</p>
        </div>
        <div class="col-auto">
            <select class="form-select" id="period-selector" onchange="window.location.href='?period='+this.value">
                <option value="7" {{ $period == 7 ? 'selected' : '' }}>Last 7 days</option>
                <option value="30" {{ $period == 30 ? 'selected' : '' }}>Last 30 days</option>
                <option value="90" {{ $period == 90 ? 'selected' : '' }}>Last 90 days</option>
            </select>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase mb-2">Response Rate</h6>
                    <h2 class="mb-0">{{ $responseRate }}%</h2>
                    <small class="text-muted">{{ $totalCompleted }}/{{ $totalSent }} surveys</small>
                    <div class="progress mt-2" style="height: 4px;">
                        <div class="progress-bar bg-primary" style="width: {{ $responseRate }}%"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase mb-2">Average Rating</h6>
                    <h2 class="mb-0">
                        @if($averageRating > 0)
                            {{ $averageRating }} <small class="text-muted">/ 5</small>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </h2>
                    <small class="text-muted">
                        @for($i = 1; $i <= 5; $i++)
                            @if($i <= floor($averageRating))
                                ⭐
                            @elseif($i == ceil($averageRating) && $averageRating - floor($averageRating) >= 0.5)
                                ⭐
                            @else
                                ☆
                            @endif
                        @endfor
                    </small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase mb-2">Satisfaction Rate</h6>
                    <h2 class="mb-0 {{ $satisfactionRate >= 80 ? 'text-success' : ($satisfactionRate >= 60 ? 'text-warning' : 'text-danger') }}">
                        {{ $satisfactionRate }}%
                    </h2>
                    <small class="text-muted">4-5 star ratings</small>
                    <div class="progress mt-2" style="height: 4px;">
                        <div class="progress-bar {{ $satisfactionRate >= 80 ? 'bg-success' : ($satisfactionRate >= 60 ? 'bg-warning' : 'bg-danger') }}"
                             style="width: {{ $satisfactionRate }}%"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase mb-2">Total Responses</h6>
                    <h2 class="mb-0">{{ number_format($totalCompleted) }}</h2>
                    <small class="text-muted">Completed surveys</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Rating Distribution -->
        <div class="col-lg-5 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Rating Distribution</h5>
                </div>
                <div class="card-body">
                    @foreach([5, 4, 3, 2, 1] as $rating)
                        @php
                            $count = $ratings->get($rating, 0);
                            $percentage = $totalCompleted > 0 ? round(($count / $totalCompleted) * 100, 1) : 0;
                            $ratingEmoji = match($rating) {
                                5 => '😀',
                                4 => '🙂',
                                3 => '😐',
                                2 => '😕',
                                1 => '😞',
                            };
                            $ratingLabel = match($rating) {
                                5 => 'Very Satisfied',
                                4 => 'Satisfied',
                                3 => 'Neutral',
                                2 => 'Dissatisfied',
                                1 => 'Very Dissatisfied',
                            };
                        @endphp
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <div>
                                    <span style="font-size: 1.2rem;">{{ $ratingEmoji }}</span>
                                    <strong>{{ $rating }}</strong> <small class="text-muted">{{ $ratingLabel }}</small>
                                </div>
                                <span class="badge bg-secondary">{{ $count }}</span>
                            </div>
                            <div class="progress" style="height: 20px;">
                                <div class="progress-bar {{ $rating >= 4 ? 'bg-success' : ($rating == 3 ? 'bg-warning' : 'bg-danger') }}"
                                     style="width: {{ $percentage }}%">
                                    {{ $percentage }}%
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Daily Trend -->
        <div class="col-lg-7 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">7-Day Trend</h5>
                </div>
                <div class="card-body">
                    @if($dailyTrend->count() > 0)
                        <canvas id="csat-trend-chart" height="280"></canvas>
                    @else
                        <p class="text-center text-muted py-5">No data available</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Agent Performance -->
    <div class="row">
        <div class="col-12 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Agent Performance</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Agent</th>
                                <th class="text-center">Total Responses</th>
                                <th class="text-center">Average Rating</th>
                                <th class="text-center">Satisfaction Rate</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($agentPerformance as $agent)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2"
                                                 style="width: 40px; height: 40px;">
                                                {{ strtoupper(substr($agent->name, 0, 1)) }}
                                            </div>
                                            <strong>{{ $agent->name }}</strong>
                                        </div>
                                    </td>
                                    <td class="text-center">{{ $agent->total_responses }}</td>
                                    <td class="text-center">
                                        <strong>{{ $agent->avg_rating }}</strong>
                                        <small class="text-muted">/ 5</small>
                                    </td>
                                    <td class="text-center">{{ $agent->satisfaction_rate }}%</td>
                                    <td class="text-center">
                                        <span class="badge {{ $agent->avg_rating >= 4 ? 'bg-success' : ($agent->avg_rating >= 3 ? 'bg-warning' : 'bg-danger') }}">
                                            @if($agent->avg_rating >= 4)
                                                Excellent
                                            @elseif($agent->avg_rating >= 3)
                                                Good
                                            @else
                                                Needs Improvement
                                            @endif
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-3">
                                        No agent performance data available
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Feedback -->
    @if($recentFeedback->count() > 0)
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Recent Customer Feedback</h5>
                    </div>
                    <div class="card-body">
                        @foreach($recentFeedback as $response)
                            <div class="border-bottom pb-3 mb-3">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <strong>{{ $response->contact->name }}</strong>
                                        <br>
                                        <small class="text-muted">
                                            {{ $response->submitted_at->diffForHumans() }}
                                            @if($response->assignedAgent)
                                                • Agent: {{ $response->assignedAgent->name }}
                                            @endif
                                        </small>
                                    </div>
                                    <div class="text-end">
                                        <div class="mb-1">
                                            <span style="font-size: 1.5rem;">{{ $response->rating_emoji }}</span>
                                        </div>
                                        <span class="badge {{ $response->rating >= 4 ? 'bg-success' : ($response->rating == 3 ? 'bg-warning' : 'bg-danger') }}">
                                            {{ $response->rating_label }}
                                        </span>
                                    </div>
                                </div>
                                <p class="mb-0 text-muted fst-italic">"{{ $response->feedback }}"</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

@if($dailyTrend->count() > 0)
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
20 20 12 61 79 80 81 98 33 100 204 250 395 398 399 400 701function() {
    const ctx = 20 20 12 61 79 80 81 98 33 100 204 250 395 398 399 400 701"#'csat-trend-chart');
    if (ctx) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: {!! json_encode($dailyTrend->pluck('date')->map(fn($d) => \Carbon\Carbon::parse($d)->format('M d'))) !!},
                datasets: [
                    {
                        label: 'Average Rating',
                        data: {!! json_encode($dailyTrend->pluck('avg_rating')) !!},
                        borderColor: 'rgb(75, 192, 192)',
                        backgroundColor: 'rgba(75, 192, 192, 0.1)',
                        tension: 0.1,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Response Count',
                        data: {!! json_encode($dailyTrend->pluck('count')) !!},
                        borderColor: 'rgb(153, 102, 255)',
                        backgroundColor: 'rgba(153, 102, 255, 0.1)',
                        tension: 0.1,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        min: 0,
                        max: 5,
                        title: {
                            display: true,
                            text: 'Average Rating'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Response Count'
                        },
                        grid: {
                            drawOnChartArea: false,
                        },
                    },
                }
            }
        });
    }
</script>
@endif
@endsection
