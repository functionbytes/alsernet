@extends('layouts.admin')

@section('title', $slaPolicy->name)

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col">
            <h1 class="h3">{{ $slaPolicy->name }}</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.sla-policies.index') }}">SLA Policies</a></li>
                    <li class="breadcrumb-item active">{{ $slaPolicy->name }}</li>
                </ol>
            </nav>
        </div>
        <div class="col-auto">
            <a href="{{ route('admin.sla-policies.edit', $slaPolicy) }}" class="btn btn-primary">
                <i class="fa fa-edit me-1"></i> Edit Policy
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <!-- Policy Details Card -->
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="card-title mb-0">Policy Details</h5>
                </div>
                <div class="card-body">
                    @if($slaPolicy->description)
                        <p class="text-muted">{{ $slaPolicy->description }}</p>
                    @endif

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <h6 class="text-muted">First Response Time</h6>
                            @if($slaPolicy->first_response_time_minutes)
                                <h4>{{ $slaPolicy->formatted_first_response_time }}</h4>
                                <small class="text-muted">{{ $slaPolicy->first_response_time_minutes }} minutes</small>
                            @else
                                <p class="text-muted">Not configured</p>
                            @endif
                        </div>

                        <div class="col-md-6 mb-3">
                            <h6 class="text-muted">Resolution Time</h6>
                            @if($slaPolicy->resolution_time_minutes)
                                <h4>{{ $slaPolicy->formatted_resolution_time }}</h4>
                                <small class="text-muted">{{ $slaPolicy->resolution_time_minutes }} minutes</small>
                            @else
                                <p class="text-muted">Not configured</p>
                            @endif
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-muted">Status</h6>
                            @if($slaPolicy->is_active)
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-secondary">Inactive</span>
                            @endif
                        </div>

                        <div class="col-md-6">
                            <h6 class="text-muted">Business Hours</h6>
                            @if($slaPolicy->business_hours_only)
                                <span class="badge bg-info">Business Hours Only</span>
                            @else
                                <span class="badge bg-secondary">24/7</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Conversations Card -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Recent Conversations</h5>
                </div>
                <div class="card-body">
                    @if($slaPolicy->conversations->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Contact</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($slaPolicy->conversations as $conversation)
                                        <tr>
                                            <td>#{{ $conversation->id }}</td>
                                            <td>{{ $conversation->contact->full_name }}</td>
                                            <td>
                                                @if($conversation->status === 'open')
                                                    <span class="badge bg-success">Open</span>
                                                @elseif($conversation->status === 'pending')
                                                    <span class="badge bg-warning">Pending</span>
                                                @else
                                                    <span class="badge bg-secondary">{{ ucfirst($conversation->status) }}</span>
                                                @endif
                                            </td>
                                            <td>{{ $conversation->created_at->diffForHumans() }}</td>
                                            <td>
                                                <a href="{{ route('admin.conversation.show', $conversation) }}" class="btn btn-sm btn-outline-secondary">
                                                    View
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted mb-0">No conversations using this SLA policy yet.</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <!-- Statistics Card -->
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="card-title mb-0">Statistics</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h6 class="text-muted mb-1">Created</h6>
                        <p class="mb-0">{{ $slaPolicy->created_at->format('M d, Y g:i A') }}</p>
                        <small class="text-muted">{{ $slaPolicy->created_at->diffForHumans() }}</small>
                    </div>

                    <div class="mb-3">
                        <h6 class="text-muted mb-1">Last Updated</h6>
                        <p class="mb-0">{{ $slaPolicy->updated_at->format('M d, Y g:i A') }}</p>
                        <small class="text-muted">{{ $slaPolicy->updated_at->diffForHumans() }}</small>
                    </div>

                    <hr>

                    <div>
                        <h6 class="text-muted mb-1">Total Conversations</h6>
                        <h4 class="mb-0">{{ $slaPolicy->conversations()->count() }}</h4>
                    </div>
                </div>
            </div>

            <!-- Actions Card -->
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title">Actions</h6>
                    <div class="d-grid gap-2">
                        <a href="{{ route('admin.sla-policies.edit', $slaPolicy) }}" class="btn btn-outline-primary">
                            <i class="fa fa-edit me-1"></i> Edit Policy
                        </a>
                        <button type="button" class="btn btn-outline-danger"
                            onclick="confirmDelete('{{ route('admin.sla-policies.destroy', $slaPolicy) }}')">
                            <i class="fa fa-trash me-1"></i> Delete Policy
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<form id="delete-form" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>

<script>
function confirmDelete(url) {
    if (confirm('Are you sure you want to delete this SLA policy? This action cannot be undone.')) {
        const form = 20 20 12 61 79 80 81 98 33 100 204 250 395 398 399 400 701"#'delete-form');
        form.action = url;
        form.submit();
    }
}
</script>
@endsection
