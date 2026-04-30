@extends('layouts.theme')

@section('title', 'SLA Report')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col">
            <h1 class="h3">SLA Performance Report</h1>
            <p class="text-muted">Monitor SLA compliance and performance metrics</p>
        </div>
        <div class="col-auto">
            <select class="form-control select2" id="period-selector" onchange="window.location.href='?period='+this.value">
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
                    <h6 class="text-muted text-uppercase mb-2">Total with SLA</h6>
                    <h2 class="mb-0">{{ number_format($totalWithSla) }}</h2>
                    <small class="text-muted">Conversations</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase mb-2">First Response</h6>
                    <h2 class="mb-0 {{ $firstResponseBreachRate > 20 ? 'text-danger' : 'text-success' }}">
                        {{ $firstResponseBreachRate }}%
                    </h2>
                    <small class="text-muted">Breach Rate</small>
                    <div class="progress mt-2" style="height: 4px;">
                        <div class="progress-bar {{ $firstResponseBreachRate > 20 ? 'bg-danger' : 'bg-success' }}"
                             style="width: {{ 100 - $firstResponseBreachRate }}%"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase mb-2">Resolution</h6>
                    <h2 class="mb-0 {{ $resolutionBreachRate > 20 ? 'text-danger' : 'text-success' }}">
                        {{ $resolutionBreachRate }}%
                    </h2>
                    <small class="text-muted">Breach Rate</small>
                    <div class="progress mt-2" style="height: 4px;">
                        <div class="progress-bar {{ $resolutionBreachRate > 20 ? 'bg-danger' : 'bg-success' }}"
                             style="width: {{ 100 - $resolutionBreachRate }}%"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-warning">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase mb-2">Approaching Breach</h6>
                    <h2 class="mb-0 text-warning">{{ $approaching->count() }}</h2>
                    <small class="text-muted">Within 1 hour</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Conversations Approaching Breach -->
    @if($approaching->count() > 0)
        <div class="alert alert-warning mb-4">
            <h5 class="alert-heading"><i class="fa fa-exclamation-triangle me-2"></i>Urgent: Approaching SLA Breach</h5>
            <div class="list-group list-group-flush">
                @foreach($approaching as $conversation)
                    @php
                        $elapsed = now()->diffInMinutes($conversation->created_at);
                        $target = $conversation->slaPolicy->first_response_time_minutes;
                        $remaining = max(0, $target - $elapsed);
                    @endphp
                    <div class="list-group-item bg-transparent border-0 px-0">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <a href="{{ route('chat.conversations.show', $conversation) }}" class="text-decoration-none">
                                    <strong>{{ $conversation->customer->name }}</strong>
                                </a>
                                <br>
                                <small class="text-muted">{{ $conversation->inbox->name }}</small>
                            </div>
                            <span class="badge bg-warning text-dark">
                                <i class="fa fa-clock"></i> {{ $remaining }} min remaining
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="row">
        <!-- Performance by Agent -->
        <div class="col-lg-8 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Performance by Agent</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Agent</th>
                                <th class="text-center">Conversations</th>
                                <th class="text-center">First Response</th>
                                <th class="text-center">Resolution</th>
                                <th class="text-center">Compliance</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($agentPerformance as $agent)
                                @php
                                    $totalTracked = $agent->first_response_met + $agent->first_response_breached + $agent->resolution_met + $agent->resolution_breached;
                                    $totalMet = $agent->first_response_met + $agent->resolution_met;
                                    $compliance = $totalTracked > 0 ? round(($totalMet / $totalTracked) * 100, 1) : 0;
                                @endphp
                                <tr>
                                    <td><strong>{{ $agent->name }}</strong></td>
                                    <td class="text-center">{{ $agent->total_conversations }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-success">{{ $agent->first_response_met }}</span>
                                        <span class="badge bg-danger">{{ $agent->first_response_breached }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-success">{{ $agent->resolution_met }}</span>
                                        <span class="badge bg-danger">{{ $agent->resolution_breached }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge {{ $compliance >= 80 ? 'bg-success' : ($compliance >= 60 ? 'bg-warning' : 'bg-danger') }}">
                                            {{ $compliance }}%
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-3">
                                        No agent data available for selected period
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Active Policies & Daily Trend -->
        <div class="col-lg-4">
            <!-- Active SLA Policies -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Active SLA Policies</h5>
                </div>
                <div class="list-group list-group-flush">
                    @forelse($activePolicies as $policy)
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <strong>{{ $policy->name }}</strong>
                                    <br>
                                    <small class="text-muted">
                                        {{ $policy->formatted_first_response_time }} / {{ $policy->formatted_resolution_time }}
                                    </small>
                                </div>
                                <span class="badge bg-secondary">{{ $policy->conversations_count }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="list-group-item text-center text-muted">
                            No active policies
                        </div>
                    @endforelse
                </div>
                @if($activePolicies->count() > 0)
                    <div class="card-footer">
                        <a href="{{ route('settings.chat.sla-policies.index') }}" class="btn btn-sm btn-outline-primary w-100">
                            Manage Policies
                        </a>
                    </div>
                @endif
            </div>

            <!-- Daily Trend Chart -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">7-Day Trend</h5>
                </div>
                <div class="card-body">
                    @if($dailyTrend->count() > 0)
                        <canvas id="sla-trend-chart" height="200"></canvas>
                    @else
                        <p class="text-center text-muted">No data available</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- First Response Details -->
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">First Response SLA</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-4">
                            <h3 class="text-success">{{ $firstResponseStats->met }}</h3>
                            <small class="text-muted">Met</small>
                        </div>
                        <div class="col-4">
                            <h3 class="text-danger">{{ $firstResponseStats->breached }}</h3>
                            <small class="text-muted">Breached</small>
                        </div>
                        <div class="col-4">
                            <h3 class="text-primary">{{ $firstResponseStats->total }}</h3>
                            <small class="text-muted">Total</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Resolution SLA</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-4">
                            <h3 class="text-success">{{ $resolutionStats->met }}</h3>
                            <small class="text-muted">Met</small>
                        </div>
                        <div class="col-4">
                            <h3 class="text-danger">{{ $resolutionStats->breached }}</h3>
                            <small class="text-muted">Breached</small>
                        </div>
                        <div class="col-4">
                            <h3 class="text-primary">{{ $resolutionStats->total }}</h3>
                            <small class="text-muted">Total</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@if($dailyTrend->count() > 0)
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
20 20 12 61 79 80 81 98 33 100 204 250 395 398 399 400 701function() {
    const ctx = 20 20 12 61 79 80 81 98 33 100 204 250 395 398 399 400 701"#'sla-trend-chart');
    if (ctx) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: {!! json_encode($dailyTrend->pluck('date')->map(fn($d) => \Carbon\Carbon::parse($d)->format('M d'))) !!},
                datasets: [
                    {
                        label: 'Total Conversations',
                        data: {!! json_encode($dailyTrend->pluck('total')) !!},
                        borderColor: 'rgb(75, 192, 192)',
                        backgroundColor: 'rgba(75, 192, 192, 0.1)',
                        tension: 0.1
                    },
                    {
                        label: 'Breached',
                        data: {!! json_encode($dailyTrend->pluck('breached')) !!},
                        borderColor: 'rgb(255, 99, 132)',
                        backgroundColor: 'rgba(255, 99, 132, 0.1)',
                        tension: 0.1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
</script>
@endif
@endsection
