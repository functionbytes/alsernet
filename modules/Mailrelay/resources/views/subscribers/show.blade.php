@extends('layouts.theme')

@section('title', 'Subscriber Details')

@section('head')
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
<style>
    .detail-card {
        background-color: #fff;
        border-radius: 0.5rem;
        box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);
        margin-bottom: 1.5rem;
    }
    .detail-card .card-header {
        background-color: #f8f9fa;
        border-bottom: 2px solid #e9ecef;
        font-weight: 600;
    }
    .detail-row {
        padding: 0.75rem 0;
        border-bottom: 1px solid #f1f3f5;
    }
    .detail-row:last-child {
        border-bottom: none;
    }
    .detail-label {
        font-weight: 600;
        color: #495057;
        margin-bottom: 0.25rem;
    }
    .detail-value {
        color: #212529;
    }
    .badge-large {
        font-size: 1rem;
        padding: 0.5em 1em;
    }
    .timeline-item {
        padding: 1rem;
        border-left: 3px solid #0d6efd;
        margin-bottom: 1rem;
        background-color: #f8f9fa;
        border-radius: 0 0.25rem 0.25rem 0;
    }
    .metadata-box {
        background-color: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 0.25rem;
        padding: 1rem;
        font-family: monospace;
        font-size: 0.875rem;
        max-height: 300px;
        overflow-y: auto;
    }
    .action-buttons .btn {
        margin-right: 0.5rem;
        margin-bottom: 0.5rem;
    }
    .status-indicator {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        display: inline-block;
        margin-right: 0.5rem;
    }
</style>
@endsection

@section('content')
<div class="container py-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h2">
                <i class="bi bi-person-circle"></i> Subscriber Details
            </h1>
            <p class="text-muted">Complete information about this subscriber</p>
        </div>
        <div class="col-md-4 text-end action-buttons">
            <a href="{{ route('mailrelay.subscribers.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
            <a href="{{ route('mailrelay.subscribers.edit', $subscriber) }}" class="btn btn-primary">
                <i class="bi bi-pencil"></i> Edit
            </a>
        </div>
    </div>

    <!-- Alerts -->
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <div class="row">
        <!-- Left Column -->
        <div class="col-md-8">
            <!-- Basic Information -->
            <div class="detail-card">
                <div class="card-header">
                    <i class="bi bi-person-fill"></i> Basic Information
                </div>
                <div class="card-body">
                    <div class="detail-row">
                        <div class="detail-label">Email Address</div>
                        <div class="detail-value">
                            <h4>
                                <i class="bi bi-envelope"></i> {{ $subscriber->email }}
                            </h4>
                        </div>
                    </div>

                    <div class="detail-row">
                        <div class="detail-label">Full Name</div>
                        <div class="detail-value">
                            {{ $subscriber->name ?? 'Not provided' }}
                        </div>
                    </div>

                    <div class="detail-row">
                        <div class="detail-label">Subscriber ID</div>
                        <div class="detail-value">
                            <code>{{ $subscriber->id }}</code>
                        </div>
                    </div>

                    <div class="detail-row">
                        <div class="detail-label">Current Status</div>
                        <div class="detail-value">
                            @switch($subscriber->status->value)
                                @case('active')
                                    <span class="status-indicator" style="background-color: #28a745;"></span>
                                    <span class="badge badge-large bg-success">
                                        <i class="bi bi-check-circle"></i> Active
                                    </span>
                                    @break
                                @case('pending')
                                    <span class="status-indicator" style="background-color: #ffc107;"></span>
                                    <span class="badge badge-large bg-warning text-dark">
                                        <i class="bi bi-clock"></i> Pending
                                    </span>
                                    @break
                                @case('unsubscribed')
                                    <span class="status-indicator" style="background-color: #6c757d;"></span>
                                    <span class="badge badge-large bg-secondary">
                                        <i class="bi bi-x-circle"></i> Unsubscribed
                                    </span>
                                    @break
                                @case('bounced')
                                    <span class="status-indicator" style="background-color: #dc3545;"></span>
                                    <span class="badge badge-large bg-danger">
                                        <i class="bi bi-exclamation-triangle"></i> Bounced
                                    </span>
                                    @break
                                @case('banned')
                                    <span class="status-indicator" style="background-color: #212529;"></span>
                                    <span class="badge badge-large bg-dark">
                                        <i class="bi bi-ban"></i> Banned
                                    </span>
                                    @break
                            @endswitch
                        </div>
                    </div>
                </div>
            </div>

            <!-- Email Validation Status -->
            <div class="detail-card">
                <div class="card-header">
                    <i class="bi bi-shield-check"></i> Email Validation Status
                </div>
                <div class="card-body">
                    <div class="detail-row">
                        <div class="detail-label">Validation Status</div>
                        <div class="detail-value">
                            @if($subscriber->validated_at)
                                <span class="badge badge-large bg-info">
                                    <i class="bi bi-shield-check"></i> Validated
                                </span>
                            @else
                                <span class="badge badge-large bg-light text-dark">
                                    <i class="bi bi-shield-x"></i> Not Validated
                                </span>
                            @endif
                        </div>
                    </div>

                    @if($subscriber->validated_at)
                    <div class="detail-row">
                        <div class="detail-label">Validated At</div>
                        <div class="detail-value">
                            <i class="bi bi-calendar-check"></i>
                            {{ $subscriber->validated_at->format('F d, Y H:i:s') }}
                            <small class="text-muted">({{ $subscriber->validated_at->diffForHumans() }})</small>
                        </div>
                    </div>
                    @endif

                    @if(isset($validationDetails))
                    <div class="detail-row">
                        <div class="detail-label">Validation Details</div>
                        <div class="detail-value">
                            <div class="metadata-box">
                                {{ json_encode($validationDetails, JSON_PRETTY_PRINT) }}
                            </div>
                        </div>
                    </div>
                    @endif

                    <div class="mt-3">
                        <button class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-arrow-repeat"></i> Re-validate Email
                        </button>
                    </div>
                </div>
            </div>

            <!-- Mailrelay Synchronization -->
            <div class="detail-card">
                <div class="card-header">
                    <i class="bi bi-cloud"></i> Mailrelay Synchronization
                </div>
                <div class="card-body">
                    <div class="detail-row">
                        <div class="detail-label">Sync Status</div>
                        <div class="detail-value">
                            @if($subscriber->mailrelay_id)
                                <span class="badge badge-large bg-primary">
                                    <i class="bi bi-cloud-check"></i> Synced
                                </span>
                            @else
                                <span class="badge badge-large bg-light text-dark">
                                    <i class="bi bi-cloud-slash"></i> Not Synced
                                </span>
                            @endif
                        </div>
                    </div>

                    @if($subscriber->mailrelay_id)
                    <div class="detail-row">
                        <div class="detail-label">Mailrelay ID</div>
                        <div class="detail-value">
                            <code>{{ $subscriber->mailrelay_id }}</code>
                        </div>
                    </div>
                    @endif

                    @if($subscriber->last_synced_at)
                    <div class="detail-row">
                        <div class="detail-label">Last Synced</div>
                        <div class="detail-value">
                            <i class="bi bi-clock-history"></i>
                            {{ $subscriber->last_synced_at->format('F d, Y H:i:s') }}
                            <small class="text-muted">({{ $subscriber->last_synced_at->diffForHumans() }})</small>
                        </div>
                    </div>
                    @endif

                    <div class="detail-row">
                        <div class="detail-label">Mailrelay Groups</div>
                        <div class="detail-value">
                            @forelse($subscriber->groups ?? [] as $group)
                                <span class="badge bg-secondary me-1">{{ $group->name }}</span>
                            @empty
                                <span class="text-muted">No groups assigned</span>
                            @endforelse
                        </div>
                    </div>

                    <div class="mt-3">
                        <button class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-cloud-upload"></i> Sync to Mailrelay Now
                        </button>
                    </div>
                </div>
            </div>

            <!-- Custom Fields -->
            @if($subscriber->custom_fields && count($subscriber->custom_fields) > 0)
            <div class="detail-card">
                <div class="card-header">
                    <i class="bi bi-sliders"></i> Custom Fields
                </div>
                <div class="card-body">
                    @foreach($subscriber->custom_fields as $key => $value)
                    <div class="detail-row">
                        <div class="detail-label">{{ $key }}</div>
                        <div class="detail-value">{{ $value }}</div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Metadata -->
            @if($subscriber->metadata && count($subscriber->metadata) > 0)
            <div class="detail-card">
                <div class="card-header">
                    <i class="bi bi-file-earmark-text"></i> Additional Metadata
                </div>
                <div class="card-body">
                    <div class="metadata-box">
                        {{ json_encode($subscriber->metadata, JSON_PRETTY_PRINT) }}
                    </div>
                </div>
            </div>
            @endif
        </div>

        <!-- Right Column -->
        <div class="col-md-4">
            <!-- Quick Actions -->
            <div class="detail-card">
                <div class="card-header">
                    <i class="bi bi-lightning"></i> Quick Actions
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        @if($subscriber->status->value === 'active')
                            <button class="btn btn-outline-warning" onclick="confirmUnsubscribe()">
                                <i class="bi bi-x-circle"></i> Unsubscribe
                            </button>
                        @elseif($subscriber->status->value === 'unsubscribed')
                            <button class="btn btn-outline-success" onclick="confirmResubscribe()">
                                <i class="bi bi-check-circle"></i> Resubscribe
                            </button>
                        @endif

                        <a href="{{ route('mailrelay.subscribers.edit', $subscriber) }}" class="btn btn-outline-primary">
                            <i class="bi bi-pencil"></i> Edit Details
                        </a>

                        <button class="btn btn-outline-danger" onclick="confirmDelete()">
                            <i class="bi bi-trash"></i> Delete Subscriber
                        </button>
                    </div>

                    <form id="unsubscribe-form" action="{{ route('mailrelay.subscribers.unsubscribe', $subscriber) }}" method="POST" style="display: none;">
                        @csrf
                        @method('PATCH')
                    </form>

                    <form id="resubscribe-form" action="{{ route('mailrelay.subscribers.resubscribe', $subscriber) }}" method="POST" style="display: none;">
                        @csrf
                        @method('PATCH')
                    </form>

                    <form id="delete-form" action="{{ route('mailrelay.subscribers.destroy', $subscriber) }}" method="POST" style="display: none;">
                        @csrf
                        @method('DELETE')
                    </form>
                </div>
            </div>

            <!-- Timeline -->
            <div class="detail-card">
                <div class="card-header">
                    <i class="bi bi-clock-history"></i> Activity Timeline
                </div>
                <div class="card-body">
                    @if($subscriber->created_at)
                    <div class="timeline-item">
                        <div class="fw-bold">
                            <i class="bi bi-plus-circle"></i> Created
                        </div>
                        <small class="text-muted">
                            {{ $subscriber->created_at->format('M d, Y H:i') }}
                        </small>
                    </div>
                    @endif

                    @if($subscriber->subscribed_at)
                    <div class="timeline-item" style="border-left-color: #28a745;">
                        <div class="fw-bold">
                            <i class="bi bi-check-circle"></i> Subscribed
                        </div>
                        <small class="text-muted">
                            {{ $subscriber->subscribed_at->format('M d, Y H:i') }}
                        </small>
                    </div>
                    @endif

                    @if($subscriber->validated_at)
                    <div class="timeline-item" style="border-left-color: #17a2b8;">
                        <div class="fw-bold">
                            <i class="bi bi-shield-check"></i> Email Validated
                        </div>
                        <small class="text-muted">
                            {{ $subscriber->validated_at->format('M d, Y H:i') }}
                        </small>
                    </div>
                    @endif

                    @if($subscriber->last_synced_at)
                    <div class="timeline-item" style="border-left-color: #6610f2;">
                        <div class="fw-bold">
                            <i class="bi bi-cloud-check"></i> Synced to Mailrelay
                        </div>
                        <small class="text-muted">
                            {{ $subscriber->last_synced_at->format('M d, Y H:i') }}
                        </small>
                    </div>
                    @endif

                    @if($subscriber->unsubscribed_at)
                    <div class="timeline-item" style="border-left-color: #6c757d;">
                        <div class="fw-bold">
                            <i class="bi bi-x-circle"></i> Unsubscribed
                        </div>
                        <small class="text-muted">
                            {{ $subscriber->unsubscribed_at->format('M d, Y H:i') }}
                        </small>
                    </div>
                    @endif

                    @if($subscriber->updated_at && $subscriber->updated_at != $subscriber->created_at)
                    <div class="timeline-item" style="border-left-color: #ffc107;">
                        <div class="fw-bold">
                            <i class="bi bi-pencil"></i> Last Updated
                        </div>
                        <small class="text-muted">
                            {{ $subscriber->updated_at->format('M d, Y H:i') }}
                        </small>
                    </div>
                    @endif
                </div>
            </div>

            <!-- System Information -->
            <div class="detail-card">
                <div class="card-header">
                    <i class="bi bi-info-circle"></i> System Information
                </div>
                <div class="card-body">
                    <div class="detail-row">
                        <div class="detail-label">Created At</div>
                        <div class="detail-value">
                            <small>{{ $subscriber->created_at->format('F d, Y H:i:s') }}</small>
                            <br>
                            <small class="text-muted">{{ $subscriber->created_at->diffForHumans() }}</small>
                        </div>
                    </div>

                    <div class="detail-row">
                        <div class="detail-label">Last Updated</div>
                        <div class="detail-value">
                            <small>{{ $subscriber->updated_at->format('F d, Y H:i:s') }}</small>
                            <br>
                            <small class="text-muted">{{ $subscriber->updated_at->diffForHumans() }}</small>
                        </div>
                    </div>

                    @if($subscriber->deleted_at)
                    <div class="detail-row">
                        <div class="detail-label">Deleted At</div>
                        <div class="detail-value">
                            <small>{{ $subscriber->deleted_at->format('F d, Y H:i:s') }}</small>
                            <br>
                            <small class="text-muted">{{ $subscriber->deleted_at->diffForHumans() }}</small>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function confirmUnsubscribe() {
        if (confirm('Are you sure you want to unsubscribe this subscriber?')) {
            document.getElementById('unsubscribe-form').submit();
        }
    }

    function confirmResubscribe() {
        if (confirm('Are you sure you want to resubscribe this subscriber?')) {
            document.getElementById('resubscribe-form').submit();
        }
    }

    function confirmDelete() {
        if (confirm('Are you sure you want to delete this subscriber? This action cannot be undone.')) {
            document.getElementById('delete-form').submit();
        }
    }
</script>
@endsection
