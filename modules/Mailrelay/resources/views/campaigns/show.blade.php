@extends('layouts.theme')

@section('title', 'Campaign Details')

@section('head')
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
    .stat-card {
        border-left: 4px solid #007bff;
        transition: transform 0.2s;
    }
    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .stat-value {
        font-size: 2.5rem;
        font-weight: 700;
        line-height: 1;
    }
    .stat-label {
        font-size: 0.875rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #6c757d;
    }
    .content-preview {
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 1.5rem;
        background: #f8f9fa;
        max-height: 500px;
        overflow-y: auto;
    }
    .timeline-item {
        border-left: 2px solid #e9ecef;
        padding-left: 1.5rem;
        padding-bottom: 1rem;
        position: relative;
    }
    .timeline-item:before {
        content: '';
        width: 12px;
        height: 12px;
        background: #007bff;
        border-radius: 50%;
        position: absolute;
        left: -7px;
        top: 0;
    }
    .chart-container {
        position: relative;
        height: 300px;
    }
</style>
@endsection

@section('content')
<div class="container py-4">
    <!-- Header -->
    <div class="mb-4">
        <div class="d-flex align-items-center gap-2 mb-2">
            <a href="{{ route('mailrelay.campaigns.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to Campaigns
            </a>
        </div>
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <h1 class="mb-2">{{ $campaign->name }}</h1>
                <div class="d-flex align-items-center gap-3">
                    @if($campaign->status === 'draft')
                    <span class="badge bg-secondary fs-6">
                        <i class="bi bi-pencil"></i> Draft
                    </span>
                    @elseif($campaign->status === 'scheduled')
                    <span class="badge bg-info fs-6">
                        <i class="bi bi-clock"></i> Scheduled
                    </span>
                    @elseif($campaign->status === 'sending')
                    <span class="badge bg-warning fs-6">
                        <i class="bi bi-hourglass-split"></i> Sending
                    </span>
                    @elseif($campaign->status === 'sent')
                    <span class="badge bg-success fs-6">
                        <i class="bi bi-check-circle"></i> Sent
                    </span>
                    @elseif($campaign->status === 'failed')
                    <span class="badge bg-danger fs-6">
                        <i class="bi bi-x-circle"></i> Failed
                    </span>
                    @endif
                    <span class="text-muted">
                        <i class="bi bi-calendar"></i> Created {{ $campaign->created_at->format('M d, Y H:i') }}
                    </span>
                </div>
            </div>
            <div class="d-flex gap-2">
                @if($campaign->status === 'draft')
                <a href="{{ route('mailrelay.campaigns.edit', $campaign->id) }}" class="btn btn-primary">
                    <i class="bi bi-pencil"></i> Edit
                </a>
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#sendModal">
                    <i class="bi bi-send"></i> Send Now
                </button>
                @endif
                @if($campaign->status === 'sent')
                <button class="btn btn-outline-primary" onclick="window.print()">
                    <i class="bi bi-printer"></i> Print Report
                </button>
                @endif
                <div class="dropdown">
                    <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-three-dots-vertical"></i>
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#"><i class="bi bi-files"></i> Duplicate</a></li>
                        <li><a class="dropdown-item" href="#"><i class="bi bi-download"></i> Export Data</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#deleteModal">
                                <i class="bi bi-trash"></i> Delete
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    @if($campaign->status === 'scheduled')
    <div class="alert alert-info mb-4">
        <i class="bi bi-calendar-event"></i>
        <strong>Scheduled to send:</strong> {{ $campaign->scheduled_at->format('l, F j, Y \a\t g:i A') }}
        ({{ $campaign->scheduled_at->diffForHumans() }})
    </div>
    @endif

    <!-- Analytics Section (for sent campaigns) -->
    @if($campaign->status === 'sent')
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <div class="stat-label mb-2">
                        <i class="bi bi-envelope"></i> Total Sent
                    </div>
                    <div class="stat-value text-primary">
                        {{ number_format($campaign->recipients_count ?? 0) }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stat-card h-100" style="border-left-color: #28a745;">
                <div class="card-body">
                    <div class="stat-label mb-2">
                        <i class="bi bi-envelope-open"></i> Opens
                    </div>
                    <div class="stat-value text-success">
                        {{ number_format($campaign->opens ?? 0) }}
                    </div>
                    <div class="text-muted">
                        {{ $campaign->recipients_count > 0 ? number_format(($campaign->opens / $campaign->recipients_count) * 100, 2) : 0 }}% rate
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stat-card h-100" style="border-left-color: #17a2b8;">
                <div class="card-body">
                    <div class="stat-label mb-2">
                        <i class="bi bi-cursor"></i> Clicks
                    </div>
                    <div class="stat-value text-info">
                        {{ number_format($campaign->clicks ?? 0) }}
                    </div>
                    <div class="text-muted">
                        {{ $campaign->recipients_count > 0 ? number_format(($campaign->clicks / $campaign->recipients_count) * 100, 2) : 0 }}% rate
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stat-card h-100" style="border-left-color: #ffc107;">
                <div class="card-body">
                    <div class="stat-label mb-2">
                        <i class="bi bi-arrow-repeat"></i> Bounces
                    </div>
                    <div class="stat-value text-warning">
                        {{ number_format($campaign->bounces ?? 0) }}
                    </div>
                    <div class="text-muted">
                        {{ $campaign->recipients_count > 0 ? number_format(($campaign->bounces / $campaign->recipients_count) * 100, 2) : 0 }}% rate
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts -->
    <div class="row mb-4">
        <div class="col-md-8 mb-3">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-graph-up"></i> Engagement Over Time</h5>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="engagementChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-pie-chart"></i> Performance Breakdown</h5>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="performanceChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Additional Metrics -->
    <div class="row mb-4">
        <div class="col-md-6 mb-3">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-link-45deg"></i> Top Clicked Links</h5>
                </div>
                <div class="card-body">
                    @php
                        $topLinks = [
                            ['url' => 'https://example.com/product-1', 'clicks' => 156],
                            ['url' => 'https://example.com/promo', 'clicks' => 89],
                            ['url' => 'https://example.com/blog', 'clicks' => 45],
                        ];
                    @endphp
                    <div class="list-group list-group-flush">
                        @forelse($topLinks as $link)
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <div class="text-truncate me-2">
                                <small class="text-muted">{{ $link['url'] }}</small>
                            </div>
                            <span class="badge bg-primary rounded-pill">{{ $link['clicks'] }} clicks</span>
                        </div>
                        @empty
                        <p class="text-muted mb-0">No link clicks recorded</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-geo-alt"></i> Geographic Distribution</h5>
                </div>
                <div class="card-body">
                    @php
                        $locations = [
                            ['country' => 'United States', 'opens' => 234],
                            ['country' => 'United Kingdom', 'opens' => 123],
                            ['country' => 'Canada', 'opens' => 87],
                        ];
                    @endphp
                    <div class="list-group list-group-flush">
                        @forelse($locations as $location)
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <div>
                                <i class="bi bi-flag"></i> {{ $location['country'] }}
                            </div>
                            <span class="badge bg-success rounded-pill">{{ $location['opens'] }} opens</span>
                        </div>
                        @empty
                        <p class="text-muted mb-0">No geographic data available</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Campaign Details -->
    <div class="row">
        <div class="col-md-8 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-envelope-open"></i> Email Content</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>Subject:</strong>
                        <p class="mb-0">{{ $campaign->subject }}</p>
                    </div>
                    <div class="mb-3">
                        <strong>From:</strong>
                        <p class="mb-0">{{ $campaign->from_name }} &lt;{{ $campaign->from_email }}&gt;</p>
                    </div>
                    @if($campaign->reply_to)
                    <div class="mb-3">
                        <strong>Reply-To:</strong>
                        <p class="mb-0">{{ $campaign->reply_to }}</p>
                    </div>
                    @endif
                    <hr>
                    <strong>Email Body:</strong>
                    <div class="content-preview mt-2">
                        <iframe srcdoc="{{ $campaign->content ?? '' }}"
                                sandbox="allow-same-origin"
                                style="width: 100%; min-height: 300px; border: none;"
                                onload="this.style.height = this.contentDocument.body.scrollHeight + 'px'">
                        </iframe>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-4">
            <!-- Campaign Info -->
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-info-circle"></i> Campaign Info</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <small class="text-muted">Campaign ID</small>
                        <div class="fw-bold">{{ $campaign->id }}</div>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">Status</small>
                        <div class="fw-bold text-capitalize">{{ $campaign->status }}</div>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">Created</small>
                        <div>{{ $campaign->created_at->format('M d, Y H:i') }}</div>
                    </div>
                    @if($campaign->sent_at)
                    <div class="mb-3">
                        <small class="text-muted">Sent</small>
                        <div>{{ $campaign->sent_at->format('M d, Y H:i') }}</div>
                    </div>
                    @endif
                    @if($campaign->scheduled_at)
                    <div class="mb-3">
                        <small class="text-muted">Scheduled For</small>
                        <div>{{ $campaign->scheduled_at->format('M d, Y H:i') }}</div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Activity Timeline -->
            @if($campaign->status === 'sent')
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-clock-history"></i> Activity Timeline</h5>
                </div>
                <div class="card-body">
                    <div class="timeline-item">
                        <small class="text-muted">{{ $campaign->created_at->format('M d, H:i') }}</small>
                        <div class="fw-bold">Campaign Created</div>
                    </div>
                    @if($campaign->sent_at)
                    <div class="timeline-item">
                        <small class="text-muted">{{ $campaign->sent_at->format('M d, H:i') }}</small>
                        <div class="fw-bold">Campaign Sent</div>
                        <small class="text-muted">To {{ number_format($campaign->recipients_count ?? 0) }} recipients</small>
                    </div>
                    @endif
                    <div class="timeline-item">
                        <small class="text-muted">{{ now()->format('M d, H:i') }}</small>
                        <div class="fw-bold">Last Updated</div>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Send Confirmation Modal -->
<div class="modal fade" id="sendModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Send Campaign Now</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to send <strong>{{ $campaign->name }}</strong> now?</p>
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle"></i>
                    This action cannot be undone. The campaign will be sent to all recipients immediately.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form action="{{ route('mailrelay.campaigns.send', $campaign->id) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-send"></i> Send Now
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Campaign</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete <strong>{{ $campaign->name }}</strong>? This action cannot be undone.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form action="{{ route('mailrelay.campaigns.destroy', $campaign->id) }}" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
@if($campaign->status === 'sent')
<script>
    // Engagement Over Time Chart
    const engagementCtx = document.getElementById('engagementChart').getContext('2d');
    new Chart(engagementCtx, {
        type: 'line',
        data: {
            labels: ['Day 1', 'Day 2', 'Day 3', 'Day 4', 'Day 5', 'Day 6', 'Day 7'],
            datasets: [
                {
                    label: 'Opens',
                    data: [120, 190, 230, 250, 280, 290, 300],
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    tension: 0.4
                },
                {
                    label: 'Clicks',
                    data: [45, 67, 89, 95, 102, 108, 112],
                    borderColor: '#17a2b8',
                    backgroundColor: 'rgba(23, 162, 184, 0.1)',
                    tension: 0.4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Performance Breakdown Chart
    const performanceCtx = document.getElementById('performanceChart').getContext('2d');
    const totalSent = {{ $campaign->recipients_count ?? 1000 }};
    const opens = {{ $campaign->opens ?? 300 }};
    const clicks = {{ $campaign->clicks ?? 112 }};
    const unopened = totalSent - opens;

    new Chart(performanceCtx, {
        type: 'doughnut',
        data: {
            labels: ['Opened', 'Clicked', 'Unopened'],
            datasets: [{
                data: [opens - clicks, clicks, unopened],
                backgroundColor: [
                    '#28a745',
                    '#17a2b8',
                    '#e9ecef'
                ],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                }
            }
        }
    });
</script>
@endif
@endsection
