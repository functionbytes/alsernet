@extends('layouts.theme')

@section('title', 'Agent Performance Reports')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col">
            <h1 class="h3">Agent Performance Reports</h1>
            <p class="text-muted">Track and analyze agent productivity, response times, and customer satisfaction</p>
        </div>
        <div class="col-auto">
            <button class="btn btn-secondary" id="export-btn">
                <i class="fa fa-download"></i> Export Report
            </button>
        </div>
    </div>

    <!-- Date Range and Filters -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="start-date" class="form-label small text-muted">Start Date</label>
                            <input type="date" class="form-control" id="start-date" value="{{ $startDate->toDateString() }}">
                        </div>
                        <div class="col-md-4">
                            <label for="end-date" class="form-label small text-muted">End Date</label>
                            <input type="date" class="form-control" id="end-date" value="{{ $endDate->toDateString() }}">
                        </div>
                        <div class="col-md-4">
                            <label for="quick-range" class="form-label small text-muted">Quick Range</label>
                            <select class="form-control select2" id="quick-range">
                                <option value="">Custom Range</option>
                                <option value="7" selected>Last 7 days</option>
                                <option value="30">Last 30 days</option>
                                <option value="90">Last 90 days</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <label for="agent-filter" class="form-label small text-muted">Filter by Agent</label>
                    <select class="form-control select2" id="agent-filter">
                        <option value="">All Agents</option>
                        @foreach($agents as $agent)
                            <option value="{{ $agent->id }}">{{ $agent->name }}</option>
                        @endforeach
                    </select>
                    <label for="team-filter" class="form-label small text-muted mt-2">Filter by Team</label>
                    <select class="form-control select2" id="team-filter">
                        <option value="">All Teams</option>
                        @foreach($teams as $team)
                            <option value="{{ $team->id }}">{{ $team->name }} ({{ $team->members_count }})</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs for Different Views -->
    <ul class="nav nav-tabs mb-4" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview" type="button">
                <i class="fa fa-speedometer2"></i> Overview
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="leaderboard-tab" data-bs-toggle="tab" data-bs-target="#leaderboard" type="button">
                <i class="fa fa-trophy"></i> Leaderboard
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="individual-tab" data-bs-toggle="tab" data-bs-target="#individual" type="button">
                <i class="fa fa-person"></i> Individual Agent
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="trends-tab" data-bs-toggle="tab" data-bs-target="#trends" type="button">
                <i class="fa fa-graph-up"></i> Trends
            </button>
        </li>
    </ul>

    <div class="tab-content">
        <!-- Overview Tab -->
        <div class="tab-pane fade show active" id="overview" role="tabpanel">
            <div class="row mb-4">
                <div class="col-12">
                    <h5 class="mb-3">Team Performance Summary</h5>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="row mb-4" id="overview-metrics">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="text-muted text-uppercase small mb-2">Total Conversations</h6>
                                    <h2 class="mb-0" id="metric-total-conversations">-</h2>
                                    <small class="text-success" id="metric-conversations-change"></small>
                                </div>
                                <div class="bg-primary bg-opacity-10 rounded p-2">
                                    <i class="fa fa-chat-dots text-primary fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="text-muted text-uppercase small mb-2">Resolved</h6>
                                    <h2 class="mb-0" id="metric-resolved">-</h2>
                                    <small class="text-muted" id="metric-resolution-rate"></small>
                                </div>
                                <div class="bg-success bg-opacity-10 rounded p-2">
                                    <i class="fa fa-check-circle text-success fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="text-muted text-uppercase small mb-2">Avg Response Time</h6>
                                    <h2 class="mb-0" id="metric-response-time">-</h2>
                                    <small class="text-muted">minutes</small>
                                </div>
                                <div class="bg-info bg-opacity-10 rounded p-2">
                                    <i class="fa fa-clock-history text-info fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="text-muted text-uppercase small mb-2">Avg CSAT Score</h6>
                                    <h2 class="mb-0" id="metric-csat">-</h2>
                                    <small class="text-muted">out of 5.0</small>
                                </div>
                                <div class="bg-warning bg-opacity-10 rounded p-2">
                                    <i class="fa fa-star text-warning fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Agent Performance Table -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">All Agents Performance</h5>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0" id="agents-table">
                                <thead>
                                    <tr>
                                        <th>Agent</th>
                                        <th class="text-center">Conversations</th>
                                        <th class="text-center">Resolved</th>
                                        <th class="text-center">Messages</th>
                                        <th class="text-center">First Response</th>
                                        <th class="text-center">Resolution Time</th>
                                        <th class="text-center">CSAT</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="agents-tbody">
                                    <tr>
                                        <td colspan="8" class="text-center py-4">
                                            <div class="spinner-border text-primary" role="status">
                                                <span class="visually-hidden">Loading...</span>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Leaderboard Tab -->
        <div class="tab-pane fade" id="leaderboard" role="tabpanel">
            <div class="row mb-4">
                <div class="col-md-6">
                    <h5 class="mb-3">Top Performers</h5>
                </div>
                <div class="col-md-6 text-end">
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-outline-primary active" data-metric="conversations_resolved">
                            Conversations
                        </button>
                        <button type="button" class="btn btn-outline-primary" data-metric="messages_sent">
                            Messages
                        </button>
                        <button type="button" class="btn btn-outline-primary" data-metric="first_response_time">
                            Response Time
                        </button>
                        <button type="button" class="btn btn-outline-primary" data-metric="csat_rating">
                            CSAT
                        </button>
                    </div>
                </div>
            </div>

            <div class="row" id="leaderboard-content">
                <div class="col-12">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Individual Agent Tab -->
        <div class="tab-pane fade" id="individual" role="tabpanel">
            <div class="row mb-4">
                <div class="col-md-6">
                    <label for="individual-agent-select" class="form-label">Select Agent</label>
                    <select class="form-control select2" id="individual-agent-select">
                        <option value="">Choose an agent...</option>
                        @foreach($agents as $agent)
                            <option value="{{ $agent->id }}">{{ $agent->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div id="individual-agent-content">
                <div class="alert alert-info">
                    <i class="fa fa-info-circle"></i> Select an agent to view their detailed performance metrics.
                </div>
            </div>
        </div>

        <!-- Trends Tab -->
        <div class="tab-pane fade" id="trends" role="tabpanel">
            <div class="row mb-4">
                <div class="col-md-6">
                    <label for="trends-agent-select" class="form-label">Select Agent</label>
                    <select class="form-control select2" id="trends-agent-select">
                        <option value="">All Agents (Average)</option>
                        @foreach($agents as $agent)
                            <option value="{{ $agent->id }}">{{ $agent->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="trends-metric-select" class="form-label">Metric</label>
                    <select class="form-control select2" id="trends-metric-select">
                        <option value="conversations_resolved">Conversations Resolved</option>
                        <option value="messages_sent">Messages Sent</option>
                        <option value="first_response_time">First Response Time</option>
                        <option value="csat_rating">CSAT Rating</option>
                    </select>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <canvas id="trends-chart" height="400"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
// Global variables
var currentStartDate = '{{ $startDate->toDateString() }}';
var currentEndDate = '{{ $endDate->toDateString() }}';
var trendsChart = null;

// Initialize on page load
$(document).ready(function() {
    loadOverview();
    setupEventListeners();
});

function setupEventListeners() {
    // Date range change
    $('#start-date').on('change', updateDateRange);
    $('#end-date').on('change', updateDateRange);

    // Quick range selector
    $('#quick-range').on('change', function() {
        if ($(this).val()) {
            var days = parseInt($(this).val());
            var end = new Date();
            var start = new Date();
            start.setDate(start.getDate() - (days - 1));

            $('#start-date').val(start.toISOString().split('T')[0]);
            $('#end-date').val(end.toISOString().split('T')[0]);

            updateDateRange();
        }
    });

    // Leaderboard metric buttons
    $('[data-metric]').on('click', function() {
        $('[data-metric]').removeClass('active');
        $(this).addClass('active');
        loadLeaderboard($(this).data('metric'));
    });

    // Individual agent selector
    $('#individual-agent-select').on('change', function() {
        if ($(this).val()) {
            loadIndividualAgent($(this).val());
        }
    });

    // Trends selectors
    $('#trends-agent-select').on('change', loadTrends);
    $('#trends-metric-select').on('change', loadTrends);

    // Tab changes
    $('#leaderboard-tab').on('shown.bs.tab', function() {
        loadLeaderboard('conversations_resolved');
    });

    $('#trends-tab').on('shown.bs.tab', function() {
        loadTrends();
    });
}

function updateDateRange() {
    currentStartDate = $('#start-date').val();
    currentEndDate = $('#end-date').val();
    $('#quick-range').val('');
    loadOverview();
}

function loadOverview() {
    $.ajax({
        url: '/admin/reports/agent-performance/overview',
        type: 'GET',
        data: {
            start_date: currentStartDate,
            end_date: currentEndDate
        },
        success: function(data) {
            updateOverviewMetrics(data);
            updateAgentsTable(data.agents);
        },
        error: function(xhr, status, error) {
            console.error('Error loading overview:', error);
        }
    });
}

function updateOverviewMetrics(data) {
    var totalConversations = data.agents.reduce(function(sum, a) { return sum + a.conversations_resolved; }, 0);
    var totalResolved = data.agents.reduce(function(sum, a) { return sum + a.conversations_resolved; }, 0);
    var avgResponseTime = data.agents.reduce(function(sum, a) { return sum + a.first_response_avg; }, 0) / data.agents.length;
    var avgCsat = data.agents.reduce(function(sum, a) { return sum + a.csat_rating; }, 0) / data.agents.length;

    $('#metric-total-conversation').text(totalConversations.toLocaleString());
    $('#metric-resolved').text(totalResolved.toLocaleString());
    $('#metric-response-time').text(Math.round(avgResponseTime));
    $('#metric-csat').text(avgCsat.toFixed(1));

    var resolutionRate = totalConversations > 0 ? (totalResolved / totalConversations * 100).toFixed(1) : 0;
    $('#metric-resolution-rate').text(resolutionRate + '% resolution rate');
}

function updateAgentsTable(agents) {
    var tbody = $('#agents-tbody');

    if (agents.length === 0) {
        tbody.html('<tr><td colspan="8" class="text-center text-muted py-4">No data available for this period</td></tr>');
        return;
    }

    var html = '';
    $.each(agents, function(index, agent) {
        html += `
        <tr>
            <td>
                <div class="d-flex align-items-center">
                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center me-2"
                         style="width: 36px; height: 36px; font-size: 14px;">
                        ${agent.agent.name.charAt(0).toUpperCase()}
                    </div>
                    <strong>${agent.agent.name}</strong>
                </div>
            </td>
            <td class="text-center">${agent.conversations_resolved}</td>
            <td class="text-center">
                <span class="badge bg-success">${agent.conversations_resolved}</span>
            </td>
            <td class="text-center">${agent.messages_sent}</td>
            <td class="text-center">${Math.round(agent.first_response_avg)} min</td>
            <td class="text-center">-</td>
            <td class="text-center">
                <span class="text-warning">${agent.csat_rating > 0 ? '★'.repeat(Math.round(agent.csat_rating)) + '☆'.repeat(5 - Math.round(agent.csat_rating)) : '-'}</span>
            </td>
            <td class="text-center">
                <button class="btn btn-sm btn-outline-primary" onclick="viewAgentDetails(${agent.agent.id})">
                    <i class="fa fa-eye"></i>
                </button>
            </td>
        </tr>
        `;
    });

    tbody.html(html);
}

function loadLeaderboard(metric) {
    var content = $('#leaderboard-content');
    content.html('<div class="col-12 text-center py-5"><div class="spinner-border text-primary"></div></div>');

    $.ajax({
        url: '/admin/reports/agent-performance/leaderboard',
        type: 'GET',
        data: {
            metric: metric,
            start_date: currentStartDate,
            end_date: currentEndDate,
            limit: 10
        },
        success: function(data) {
            var html = '';
            $.each(data.leaderboard, function(index, item) {
                var emoji = index === 0 ? '🥇' : index === 1 ? '🥈' : index === 2 ? '🥉' : '#' + (index + 1);
                var borderClass = index < 3 ? 'border-warning' : '';
                var textClass = index === 0 ? 'text-warning' : index === 1 ? 'text-secondary' : index === 2 ? 'text-danger' : 'text-muted';

                html += `
            <div class="col-md-6 mb-3">
                <div class="card ${index < 3 ? 'border-warning' : ''}">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <h1 class="display-4 mb-0 ${textClass}">
                                    ${emoji}
                                </h1>
                            </div>
                            <div class="flex-grow-1">
                                <h5 class="mb-1">${item.agent.name}</h5>
                                <p class="mb-0 text-muted">${formatMetricValue(metric, item.value)}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
                `;
            });

            content.html(html);
        },
        error: function(xhr, status, error) {
            console.error('Error loading leaderboard:', error);
            content.html('<div class="col-12"><div class="alert alert-danger">Error loading leaderboard</div></div>');
        }
    });
}

function loadIndividualAgent(agentId) {
    var content = $('#individual-agent-content');
    content.html('<div class="text-center py-5"><div class="spinner-border text-primary"></div></div>');

    $.ajax({
        url: '/admin/reports/agent-performance/agents/' + agentId + '/metrics',
        type: 'GET',
        data: {
            start_date: currentStartDate,
            end_date: currentEndDate
        },
        success: function(data) {
            var html = `
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <h3>${data.conversations.assigned}</h3>
                            <small class="text-muted">Conversations Assigned</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <h3>${data.conversations.resolved}</h3>
                            <small class="text-muted">Resolved</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <h3>${Math.round(data.response_times.first_response_avg)}</h3>
                            <small class="text-muted">Avg Response Time (min)</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <h3>${data.csat.avg_rating.toFixed(1)}</h3>
                            <small class="text-muted">CSAT Score</small>
                        </div>
                    </div>
                </div>
            </div>
            `;

            content.html(html);
        },
        error: function(xhr, status, error) {
            console.error('Error loading individual agent:', error);
            content.html('<div class="alert alert-danger">Error loading agent data</div>');
        }
    });
}

function loadTrends() {
    var agentId = $('#trends-agent-select').val();
    var metric = $('#trends-metric-select').val();

    if (!agentId) return;

    $.ajax({
        url: '/admin/reports/agent-performance/agents/' + agentId + '/trends',
        type: 'GET',
        data: {
            metric: metric,
            days: 30
        },
        success: function(data) {
            updateTrendsChart(data);
        },
        error: function(xhr, status, error) {
            console.error('Error loading trends:', error);
        }
    });
}

function updateTrendsChart(data) {
    var ctx = $('#trends-chart')[0].getContext('2d');

    if (trendsChart) {
        trendsChart.destroy();
    }

    trendsChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: $.map(data.data, function(d) { return d.date; }),
            datasets: [{
                label: data.metric.replace('_', ' ').toUpperCase(),
                data: $.map(data.data, function(d) { return d.value; }),
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
                    display: true,
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

function formatMetricValue(metric, value) {
    switch(metric) {
        case 'conversations_resolved':
            return `${value} conversations resolved`;
        case 'messages_sent':
            return `${value} messages sent`;
        case 'first_response_time':
        case 'resolution_time':
            return `${Math.round(value)} minutes average`;
        case 'csat_rating':
            return `${value.toFixed(1)} / 5.0 stars`;
        default:
            return value;
    }
}

function viewAgentDetails(agentId) {
    $('#individual-agent-select').val(agentId);
    var tab = new bootstrap.Tab($('#individual-tab')[0]);
    tab.show();
    loadIndividualAgent(agentId);
}
</script>
@endsection
