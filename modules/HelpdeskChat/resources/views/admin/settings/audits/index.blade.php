@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col">
            <h1 class="h3"><i class="bi bi-clock-history me-2"></i> Audit Logs</h1>
            <p class="text-muted">Track all system activities and changes</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="fa fa-filter me-2"></i> Filters</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.audits.index') }}" class="row g-3">
                <div class="col-md-3">
                    <label for="event" class="form-label">Event Type</label>
                    <select name="event" id="event" class="form-select">
                        <option value="">All Events</option>
                        @foreach($events as $event)
                            <option value="{{ $event }}" {{ request('event') == $event ? 'selected' : '' }}>
                                {{ ucfirst(str_replace('_', ' ', $event)) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="user_id" class="form-label">User</label>
                    <select name="user_id" id="user_id" class="form-select">
                        <option value="">All Users</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="start_date" class="form-label">Start Date</label>
                    <input type="date" name="start_date" id="start_date" class="form-control"
                           value="{{ request('start_date') }}">
                </div>

                <div class="col-md-2">
                    <label for="end_date" class="form-label">End Date</label>
                    <input type="date" name="end_date" id="end_date" class="form-control"
                           value="{{ request('end_date') }}">
                </div>

                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fa fa-search"></i> Filter
                    </button>
                    @if(request()->hasAny(['event', 'user_id', 'start_date', 'end_date']))
                        <a href="{{ route('admin.audits.index') }}" class="btn btn-outline-secondary">
                            <i class="fa fa-times"></i> Clear
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Audit Logs Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th width="150">Timestamp</th>
                            <th>User</th>
                            <th>Event</th>
                            <th>Subject</th>
                            <th width="120">IP Address</th>
                            <th width="100">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                            <tr>
                                <td>
                                    <small class="text-muted">
                                        {{ $log->created_at->format('M d, Y') }}<br>
                                        {{ $log->created_at->format('h:i A') }}
                                    </small>
                                </td>
                                <td>
                                    @if($log->user)
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
                                                {{ strtoupper(substr($log->user->name, 0, 1)) }}
                                            </div>
                                            <div>
                                                <div class="fw-medium">{{ $log->user->name }}</div>
                                                <small class="text-muted">{{ $log->user->email }}</small>
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-muted">System</span>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $eventColors = [
                                            'created' => 'success',
                                            'updated' => 'primary',
                                            'deleted' => 'danger',
                                            'restored' => 'info',
                                            'force_deleted' => 'danger',
                                        ];
                                        $color = $eventColors[$log->event] ?? 'secondary';
                                    @endphp
                                    <span class="badge bg-{{ $color }}">
                                        {{ ucfirst(str_replace('_', ' ', $log->event)) }}
                                    </span>
                                </td>
                                <td>
                                    @if($log->auditable)
                                        <div>
                                            <span class="text-muted small">{{ class_basename($log->auditable_type) }}</span>
                                            <div class="fw-medium">
                                                {{ $log->auditable->name ?? $log->auditable->title ?? '#' . $log->auditable->id }}
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    <small class="text-muted font-monospace">{{ $log->ip_address ?? '-' }}</small>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-secondary"
                                            onclick="showDetails({{ $log->id }})">
                                        <i class="fa fa-eye"></i> Details
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                                    <p class="text-muted mt-2">No audit logs found.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($logs->hasPages())
                <div class="d-flex justify-content-center mt-4">
                    {{ $logs->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Audit Log Details Modal -->
<div class="modal fade" id="auditDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Audit Log Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="auditDetailsContent">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function showDetails(logId) {
    const modal = new bootstrap.Modal(document.getElementById('auditDetailsModal'));
    const content = document.getElementById('auditDetailsContent');

    // Show loading
    content.innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    `;

    modal.show();

    // Fetch details (for now, just show a placeholder)
    setTimeout(() => {
        content.innerHTML = `
            <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i>
                Detailed view of audit log #${logId} will be implemented here.
            </div>
            <p class="text-muted">This will show:</p>
            <ul>
                <li>Old values</li>
                <li>New values</li>
                <li>Changed fields</li>
                <li>User agent</li>
                <li>Additional metadata</li>
            </ul>
        `;
    }, 500);
}
</script>
@endsection
