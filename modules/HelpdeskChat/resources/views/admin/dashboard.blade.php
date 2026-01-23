@extends('layouts.admin')

@section('content')
<div class="row" data-dashboard-account-id="{{ auth()->user()->account_id }}">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-0">Dashboard</h2>
                <small class="text-muted">
                    <span id="realtime-status" class="badge bg-secondary">
                        <i class="bi bi-circle-fill"></i> Connecting...
                    </span>
                    <span class="ms-2">Last updated: <span id="last-updated">{{ now()->format('H:i:s') }}</span></span>
                </small>
            </div>
            <button type="button" class="btn btn-sm btn-outline-primary" id="refresh-metrics-btn" onclick="manualRefresh()">
                <i class="bi bi-arrow-clockwise"></i> Refresh Metrics
            </button>
        </div>
    </div>
</div>

<!-- Performance Alerts -->
@if(!empty($alerts) && count($alerts) > 0)
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-warning">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="bi bi-exclamation-triangle-fill"></i> Performance Alerts</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    @foreach($alerts as $alert)
                        <div class="col-md-6 col-lg-4 mb-3">
                            <div class="alert alert-{{ $alert['type'] }} mb-0 h-100">
                                <div class="d-flex align-items-start">
                                    <div class="flex-shrink-0 me-2">
                                        @if($alert['priority'] === 'high')
                                            <i class="bi bi-exclamation-circle-fill fs-4"></i>
                                        @elseif($alert['priority'] === 'medium')
                                            <i class="bi bi-exclamation-triangle-fill fs-4"></i>
                                        @else
                                            <i class="bi bi-info-circle-fill fs-4"></i>
                                        @endif
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="alert-heading mb-1">{{ $alert['title'] }}</h6>
                                        <p class="mb-0 small">{{ $alert['message'] }}</p>
                                        @if(isset($alert['action']))
                                            <small class="d-block mt-1 fw-bold">
                                                <i class="bi bi-arrow-right"></i> {{ $alert['action'] }}
                                            </small>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endif

<!-- Overview Statistics Cards -->
<div class="row mb-4">
    <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
        <div class="card border-primary h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1 small">Total Conversations</h6>
                        <h3 class="mb-0" id="total-conversations">{{ $metrics['overview']['total_conversations'] }}</h3>
                        @if(isset($comparisons['conversation']))
                            <small class="d-flex align-items-center mt-1
                                {{ $comparisons['conversation']['trend'] === 'up' ? 'text-success' : ($comparisons['conversation']['trend'] === 'down' ? 'text-danger' : 'text-muted') }}">
                                @if($comparisons['conversation']['trend'] === 'up')
                                    <i class="bi bi-arrow-up-circle-fill me-1"></i>
                                @elseif($comparisons['conversation']['trend'] === 'down')
                                    <i class="bi bi-arrow-down-circle-fill me-1"></i>
                                @else
                                    <i class="bi bi-dash-circle-fill me-1"></i>
                                @endif
                                {{ abs($comparisons['conversation']['change_percent']) }}% vs last month
                            </small>
                        @endif
                    </div>
                    <div class="text-primary">
                        <i class="bi bi-chat-dots fs-2"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
        <div class="card border-warning h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1 small">Open</h6>
                        <h3 class="mb-0" id="open-conversations">{{ $metrics['overview']['open_conversations'] }}</h3>
                    </div>
                    <div class="text-warning">
                        <i class="bi bi-chat-left-text fs-2"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
        <div class="card border-success h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1 small">Resolved Today</h6>
                        <h3 class="mb-0" id="resolved-today">{{ $metrics['overview']['resolved_today'] }}</h3>
                    </div>
                    <div class="text-success">
                        <i class="bi bi-check-circle fs-2"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
        <div class="card border-info h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1 small">Avg Response</h6>
                        <h3 class="mb-0" id="avg-response-time">{{ number_format($metrics['overview']['avg_response_time'], 1) }}m</h3>
                        @if(isset($comparisons['response_time']))
                            <small class="d-flex align-items-center mt-1
                                {{ $comparisons['response_time']['trend'] === 'down' ? 'text-success' : ($comparisons['response_time']['trend'] === 'up' ? 'text-danger' : 'text-muted') }}">
                                @if($comparisons['response_time']['trend'] === 'down')
                                    <i class="bi bi-arrow-down-circle-fill me-1"></i>
                                @elseif($comparisons['response_time']['trend'] === 'up')
                                    <i class="bi bi-arrow-up-circle-fill me-1"></i>
                                @else
                                    <i class="bi bi-dash-circle-fill me-1"></i>
                                @endif
                                {{ abs($comparisons['response_time']['change_percent']) }}% vs last month
                            </small>
                        @endif
                    </div>
                    <div class="text-info">
                        <i class="bi bi-clock-history fs-2"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
        <div class="card border-primary h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1 small">Active Agents</h6>
                        <h3 class="mb-0" id="active-agents">{{ $metrics['overview']['active_agents'] }}</h3>
                    </div>
                    <div class="text-primary">
                        <i class="bi bi-people fs-2"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
        <div class="card border-secondary h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1 small">Messages Today</h6>
                        <h3 class="mb-0" id="messages-today">{{ $metrics['overview']['total_messages_today'] }}</h3>
                        @if(isset($comparisons['messages']))
                            <small class="d-flex align-items-center mt-1
                                {{ $comparisons['messages']['trend'] === 'up' ? 'text-success' : ($comparisons['messages']['trend'] === 'down' ? 'text-danger' : 'text-muted') }}">
                                @if($comparisons['messages']['trend'] === 'up')
                                    <i class="bi bi-arrow-up-circle-fill me-1"></i>
                                @elseif($comparisons['messages']['trend'] === 'down')
                                    <i class="bi bi-arrow-down-circle-fill me-1"></i>
                                @else
                                    <i class="bi bi-dash-circle-fill me-1"></i>
                                @endif
                                {{ abs($comparisons['messages']['change_percent']) }}% vs last month
                            </small>
                        @endif
                    </div>
                    <div class="text-secondary">
                        <i class="bi bi-envelope fs-2"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row 1: Conversations & Agents -->
<div class="row mb-4">
    <!-- Conversations by Status -->
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card h-100">
            <div class="card-header bg-white">
                <h6 class="mb-0">Conversations by Status</h6>
            </div>
            <div class="card-body">
                <canvas id="statusChart" height="200"></canvas>
            </div>
        </div>
    </div>

    <!-- Conversations by Priority -->
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card h-100">
            <div class="card-header bg-white">
                <h6 class="mb-0">Conversations by Priority</h6>
            </div>
            <div class="card-body">
                <canvas id="priorityChart" height="200"></canvas>
            </div>
        </div>
    </div>

    <!-- Top Inboxes -->
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card h-100">
            <div class="card-header bg-white">
                <h6 class="mb-0">Top 5 Inboxes</h6>
            </div>
            <div class="card-body">
                <canvas id="inboxChart" height="200"></canvas>
            </div>
        </div>
    </div>

    <!-- Agent Availability -->
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card h-100">
            <div class="card-header bg-white">
                <h6 class="mb-0">Agent Availability</h6>
            </div>
            <div class="card-body">
                <canvas id="availabilityChart" height="200"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row 2: Trends -->
<div class="row mb-4">
    <!-- Conversations Trend (7 days) -->
    <div class="col-lg-6 mb-3">
        <div class="card h-100">
            <div class="card-header bg-white">
                <h6 class="mb-0">Conversations Trend (Last 7 Days)</h6>
            </div>
            <div class="card-body">
                <canvas id="conversationsTrendChart" height="100"></canvas>
            </div>
        </div>
    </div>

    <!-- Messages Trend (7 days) -->
    <div class="col-lg-6 mb-3">
        <div class="card h-100">
            <div class="card-header bg-white">
                <h6 class="mb-0">Messages Trend (Last 7 Days)</h6>
            </div>
            <div class="card-body">
                <canvas id="messagesTrendChart" height="100"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row 3: Agent Performance & Response Times -->
<div class="row mb-4">
    <!-- Top Performers -->
    <div class="col-lg-6 mb-3">
        <div class="card h-100">
            <div class="card-header bg-white">
                <h6 class="mb-0">Top Performers (Resolved Last 7 Days)</h6>
            </div>
            <div class="card-body">
                <canvas id="topPerformersChart" height="100"></canvas>
            </div>
        </div>
    </div>

    <!-- Response Time by Agent -->
    <div class="col-lg-6 mb-3">
        <div class="card h-100">
            <div class="card-header bg-white">
                <h6 class="mb-0">Avg Response Time by Agent (Top 10)</h6>
            </div>
            <div class="card-body">
                <canvas id="responseTimeAgentChart" height="100"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Hourly Trend & Resolution Rate -->
<div class="row mb-4">
    <!-- Hourly Conversation Trend (Last 24h) -->
    <div class="col-lg-8 mb-3">
        <div class="card h-100">
            <div class="card-header bg-white">
                <h6 class="mb-0">Hourly Conversation Trend (Last 24 Hours)</h6>
            </div>
            <div class="card-body">
                <canvas id="hourlyTrendChart" height="80"></canvas>
            </div>
        </div>
    </div>

    <!-- Resolution Rate -->
    <div class="col-lg-4 mb-3">
        <div class="card h-100">
            <div class="card-header bg-white">
                <h6 class="mb-0">Resolution Rate</h6>
            </div>
            <div class="card-body d-flex align-items-center justify-content-center">
                <div class="text-center">
                    <h1 class="display-3 mb-0 text-success">{{ number_format($metrics['trending']['resolution_rate'], 1) }}%</h1>
                    <p class="text-muted mb-0">of all conversations resolved</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Conversations -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">Recent Conversations</h5>
            </div>
            <div class="card-body p-0">
                @if($recentConversations->isEmpty())
                    <div class="p-4 text-center text-muted">
                        <i class="bi bi-chat-dots fs-1 d-block mb-2"></i>
                        No conversations yet
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th>Contact</th>
                                    <th>Inbox</th>
                                    <th>Status</th>
                                    <th>Assignee</th>
                                    <th>Last Activity</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentConversations as $conversation)
                                    <tr>
                                        <td>
                                            <strong>{{ $conversation->contact->name }}</strong><br>
                                            <small class="text-muted">{{ $conversation->contact->email }}</small>
                                        </td>
                                        <td>{{ $conversation->inbox->name }}</td>
                                        <td>
                                            @if($conversation->status === 'open')
                                                <span class="badge bg-warning">Open</span>
                                            @elseif($conversation->status === 'resolved')
                                                <span class="badge bg-success">Resolved</span>
                                            @else
                                                <span class="badge bg-secondary">Pending</span>
                                            @endif
                                        </td>
                                        <td>{{ $conversation->assignee?->name ?? 'Unassigned' }}</td>
                                        <td>{{ $conversation->last_activity_at->diffForHumans() }}</td>
                                        <td>
                                            <a href="{{ route('admin.conversation.show', $conversation) }}" class="btn btn-sm btn-primary">
                                                <i class="bi bi-eye"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
/* Pulse animation for metric updates */
@keyframes pulse-update {
    0% { box-shadow: 0 0 0 0 rgba(13, 110, 253, 0.4); }
    50% { box-shadow: 0 0 0 5px rgba(13, 110, 253, 0); }
    100% { box-shadow: 0 0 0 0 rgba(13, 110, 253, 0); }
}

.pulse-update {
    animation: pulse-update 1s ease-out;
}

/* Spin animation for refresh button */
@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.spin {
    display: inline-block;
    animation: spin 1s linear infinite;
}

/* Blinking circle for real-time status */
@keyframes blink {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

#realtime-status .bi-circle-fill {
    animation: blink 2s ease-in-out infinite;
}

#realtime-status.bg-success .bi-circle-fill {
    animation: none;
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// Initial metrics data from server
let metricsData = @json($metrics);

// Chart instances
let charts = {};

// Debounce timer for real-time updates
let updateDebounceTimer = null;

// Account ID for WebSocket subscription
const accountId = {{ auth()->user()->account_id }};

// Initialize all charts
$(function() {
    initializeCharts();
    initializeRealtimeListeners();

    // Auto-refresh every 60 seconds as fallback
    setInterval(refreshAllMetrics, 60000);
});

/**
 * Initialize real-time WebSocket listeners
 */
function initializeRealtimeListeners() {
    if (typeof Echo === 'undefined') {
        console.warn('Laravel Echo not available. Real-time updates disabled.');
        updateRealtimeStatus('offline', 'WebSocket Unavailable');
        return;
    }

    // Update status to connected when Echo is ready
    updateRealtimeStatus('connected', 'Real-time Connected');

    // Listen to account channel for real-time events
    Echo.private(`account.${accountId}`)
        // New message sent
        .listen('.message.sent', function(e) {
            console.log('Dashboard: New message received', e);
            debouncedMetricsUpdate();
            showRealtimeNotification('New message received');
        })
        // Conversation status changed
        .listen('.conversation.status.changed', function(e) {
            console.log('Dashboard: Conversation status changed', e);
            debouncedMetricsUpdate();
            showRealtimeNotification(`Conversation ${e.new_status}`);
        })
        // Conversation assigned
        .listen('App\\Events\\Conversations\\ConversationAssigned', function(e) {
            console.log('Dashboard: Conversation assigned', e);
            debouncedMetricsUpdate();
        });
}

/**
 * Debounced metric update to prevent excessive API calls
 */
function debouncedMetricsUpdate() {
    clearTimeout(updateDebounceTimer);
    updateDebounceTimer = setTimeout(refreshAllMetrics, 2000);
}

/**
 * Show brief notification for real-time updates
 */
function showRealtimeNotification(message) {
    const $refreshBtn = $('#refresh-metrics-btn');
    const originalHtml = $refreshBtn.html();

    $refreshBtn.html('<i class="bi bi-bell-fill"></i> ' + message);
    $refreshBtn.addClass('btn-warning').removeClass('btn-outline-primary');

    setTimeout(() => {
        $refreshBtn.html(originalHtml);
        $refreshBtn.removeClass('btn-warning').addClass('btn-outline-primary');
    }, 3000);
}

/**
 * Update real-time connection status indicator
 */
function updateRealtimeStatus(status, label) {
    const $statusBadge = $('#realtime-status');
    const classes = {
        'connected': 'bg-success',
        'connecting': 'bg-secondary',
        'offline': 'bg-danger'
    };

    $statusBadge.removeClass('bg-success bg-secondary bg-danger')
        .addClass(classes[status])
        .html(`<i class="bi bi-circle-fill"></i> ${label}`);
}

/**
 * Update last updated timestamp
 */
function updateLastUpdatedTime() {
    const now = new Date();
    const timeString = now.toLocaleTimeString('en-US', { hour12: false, hour: '2-digit', minute: '2-digit', second: '2-digit' });
    $('#last-updated').text(timeString);
}

/**
 * Manual refresh triggered by button click
 */
function manualRefresh() {
    const $btn = $('#refresh-metrics-btn');
    const originalHtml = $btn.html();

    $btn.prop('disabled', true).html('<i class="bi bi-arrow-clockwise spin"></i> Refreshing...');

    refreshAllMetrics().then(() => {
        $btn.prop('disabled', false).html(originalHtml);
    }).catch(() => {
        $btn.prop('disabled', false).html(originalHtml);
    });
}

function initializeCharts() {
    // Conversations by Status
    charts.status = new Chart($('#statusChart')[0], {
        type: 'doughnut',
        data: {
            labels: Object.keys(metricsData.conversations.by_status).map(s => s.charAt(0).toUpperCase() + s.slice(1)),
            datasets: [{
                data: Object.values(metricsData.conversations.by_status),
                backgroundColor: ['#ffc107', '#198754', '#6c757d', '#dc3545']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { boxWidth: 12, font: { size: 10 } }
                }
            }
        }
    });

    // Conversations by Priority
    charts.priority = new Chart($('#priorityChart')[0], {
        type: 'doughnut',
        data: {
            labels: Object.keys(metricsData.conversations.by_priority).map(p => p.charAt(0).toUpperCase() + p.slice(1)),
            datasets: [{
                data: Object.values(metricsData.conversations.by_priority),
                backgroundColor: ['#dc3545', '#ffc107', '#0d6efd', '#6c757d']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { boxWidth: 12, font: { size: 10 } }
                }
            }
        }
    });

    // Top 5 Inboxes
    charts.inbox = new Chart($('#inboxChart')[0], {
        type: 'bar',
        data: {
            labels: Object.keys(metricsData.conversations.by_inbox),
            datasets: [{
                label: 'Conversations',
                data: Object.values(metricsData.conversations.by_inbox),
                backgroundColor: '#0d6efd'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            plugins: {
                legend: { display: false }
            },
            scales: {
                x: { beginAtZero: true }
            }
        }
    });

    // Agent Availability
    charts.availability = new Chart($('#availabilityChart')[0], {
        type: 'doughnut',
        data: {
            labels: Object.keys(metricsData.agents.availability_distribution).map(s => s.charAt(0).toUpperCase() + s.slice(1)),
            datasets: [{
                data: Object.values(metricsData.agents.availability_distribution),
                backgroundColor: ['#198754', '#ffc107', '#fd7e14', '#6c757d']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { boxWidth: 12, font: { size: 10 } }
                }
            }
        }
    });

    // Conversations Trend (7 days)
    charts.conversationsTrend = new Chart($('#conversationsTrendChart')[0], {
        type: 'line',
        data: {
            labels: metricsData.trending.conversations_7d.map(d => d.date),
            datasets: [{
                label: 'Conversations',
                data: metricsData.trending.conversations_7d.map(d => d.count),
                borderColor: '#0d6efd',
                backgroundColor: 'rgba(13, 110, 253, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: { beginAtZero: true }
            }
        }
    });

    // Messages Trend (7 days)
    charts.messagesTrend = new Chart($('#messagesTrendChart')[0], {
        type: 'line',
        data: {
            labels: metricsData.trending.messages_7d.map(d => d.date),
            datasets: [{
                label: 'Messages',
                data: metricsData.trending.messages_7d.map(d => d.count),
                borderColor: '#198754',
                backgroundColor: 'rgba(25, 135, 84, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: { beginAtZero: true }
            }
        }
    });

    // Top Performers
    charts.topPerformers = new Chart($('#topPerformersChart')[0], {
        type: 'bar',
        data: {
            labels: metricsData.agents.top_performers.map(a => a.name),
            datasets: [{
                label: 'Resolved',
                data: metricsData.agents.top_performers.map(a => a.resolved_count),
                backgroundColor: '#198754'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: { beginAtZero: true }
            }
        }
    });

    // Response Time by Agent
    charts.responseTimeAgent = new Chart($('#responseTimeAgentChart')[0], {
        type: 'bar',
        data: {
            labels: metricsData.response_times.by_agent.map(a => a.name),
            datasets: [{
                label: 'Minutes',
                data: metricsData.response_times.by_agent.map(a => a.avg_response_time),
                backgroundColor: '#0dcaf0'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: { beginAtZero: true }
            }
        }
    });

    // Hourly Trend
    charts.hourlyTrend = new Chart($('#hourlyTrendChart')[0], {
        type: 'bar',
        data: {
            labels: Array.from({length: 24}, (_, i) => i + ':00'),
            datasets: [{
                label: 'Conversations',
                data: metricsData.conversations.hourly_trend,
                backgroundColor: '#ffc107'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
}

function refreshAllMetrics() {
    return $.ajax({
        url: '{{ route("admin.dashboard") }}/metrics',
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            metricsData = data;
            updateOverviewCards(data.overview);
            updateCharts(data);
            updateLastUpdatedTime();

            // Add subtle pulse animation to indicate update
            $('.card').addClass('pulse-update');
            setTimeout(() => $('.card').removeClass('pulse-update'), 1000);
        },
        error: function(error) {
            console.error('Error refreshing metrics:', error);
            updateRealtimeStatus('offline', 'Update Failed');
        }
    });
}

function updateOverviewCards(overview) {
    $('#total-conversation').text(overview.total_conversations);
    $('#open-conversation').text(overview.open_conversations);
    $('#resolved-today').text(overview.resolved_today);
    $('#avg-response-time').text(overview.avg_response_time.toFixed(1) + 'm');
    $('#active-agents').text(overview.active_agents);
    $('#messages-today').text(overview.total_messages_today);
}

function updateCharts(data) {
    // Update Status Chart
    charts.status.data.labels = Object.keys(data.conversations.by_status).map(s => s.charAt(0).toUpperCase() + s.slice(1));
    charts.status.data.datasets[0].data = Object.values(data.conversations.by_status);
    charts.status.update();

    // Update Priority Chart
    charts.priority.data.labels = Object.keys(data.conversations.by_priority).map(p => p.charAt(0).toUpperCase() + p.slice(1));
    charts.priority.data.datasets[0].data = Object.values(data.conversations.by_priority);
    charts.priority.update();

    // Update Inbox Chart
    charts.inbox.data.labels = Object.keys(data.conversations.by_inbox);
    charts.inbox.data.datasets[0].data = Object.values(data.conversations.by_inbox);
    charts.inbox.update();

    // Update Availability Chart
    charts.availability.data.labels = Object.keys(data.agents.availability_distribution).map(s => s.charAt(0).toUpperCase() + s.slice(1));
    charts.availability.data.datasets[0].data = Object.values(data.agents.availability_distribution);
    charts.availability.update();

    // Update Conversations Trend
    charts.conversationsTrend.data.labels = data.trending.conversations_7d.map(d => d.date);
    charts.conversationsTrend.data.datasets[0].data = data.trending.conversations_7d.map(d => d.count);
    charts.conversationsTrend.update();

    // Update Messages Trend
    charts.messagesTrend.data.labels = data.trending.messages_7d.map(d => d.date);
    charts.messagesTrend.data.datasets[0].data = data.trending.messages_7d.map(d => d.count);
    charts.messagesTrend.update();

    // Update Top Performers
    charts.topPerformers.data.labels = data.agents.top_performers.map(a => a.name);
    charts.topPerformers.data.datasets[0].data = data.agents.top_performers.map(a => a.resolved_count);
    charts.topPerformers.update();

    // Update Response Time by Agent
    charts.responseTimeAgent.data.labels = data.response_times.by_agent.map(a => a.name);
    charts.responseTimeAgent.data.datasets[0].data = data.response_times.by_agent.map(a => a.avg_response_time);
    charts.responseTimeAgent.update();

    // Update Hourly Trend
    charts.hourlyTrend.data.datasets[0].data = data.conversations.hourly_trend;
    charts.hourlyTrend.update();
}
</script>
@endpush
