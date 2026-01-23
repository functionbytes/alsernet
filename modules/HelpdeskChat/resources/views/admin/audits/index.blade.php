@extends('layouts.admin')

@section('title', 'Audit Log')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col">
            <h1 class="h3">Audit Log</h1>
            <p class="text-muted">Track all important activities and changes in your account</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.audits.index') }}" class="row g-3">
                <div class="col-md-3">
                    <label for="event" class="form-label">Event Type</label>
                    <select name="event" id="event" class="form-select">
                        <option value="">All Events</option>
                        @foreach($events as $eventOption)
                            <option value="{{ $eventOption }}" {{ request('event') == $eventOption ? 'selected' : '' }}>
                                {{ ucwords(str_replace(['.', '_'], ' ', $eventOption)) }}
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
                    <input type="date" name="start_date" id="start_date" class="form-control" value="{{ request('start_date') }}">
                </div>
                <div class="col-md-2">
                    <label for="end_date" class="form-label">End Date</label>
                    <input type="date" name="end_date" id="end_date" class="form-control" value="{{ request('end_date') }}">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="bi bi-filter"></i> Filter
                    </button>
                    <a href="{{ route('admin.audits.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle"></i> Clear
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Audit Logs Table -->
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 50px">#</th>
                        <th style="width: 180px">Date & Time</th>
                        <th>Event</th>
                        <th style="width: 150px">User</th>
                        <th style="width: 200px">Details</th>
                        <th style="width: 120px">IP Address</th>
                        <th style="width: 80px"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td class="text-muted">{{ $log->id }}</td>
                            <td>
                                <small class="text-muted">
                                    {{ $log->created_at->format('M d, Y') }}<br>
                                    {{ $log->created_at->format('h:i A') }}
                                </small>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    @php
                                        $badgeClass = match(true) {
                                            str_contains($log->event, 'created') => 'bg-success',
                                            str_contains($log->event, 'updated') => 'bg-info',
                                            str_contains($log->event, 'deleted') => 'bg-danger',
                                            str_contains($log->event, 'login') => 'bg-primary',
                                            str_contains($log->event, 'logout') => 'bg-secondary',
                                            default => 'bg-secondary'
                                        };
                                    @endphp
                                    <span class="badge {{ $badgeClass }} me-2">{{ explode('.', $log->event)[0] }}</span>
                                    <strong>{{ $log->description }}</strong>
                                </div>
                            </td>
                            <td>
                                @if($log->user)
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px; font-size: 14px;">
                                            {{ strtoupper(substr($log->user->name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <small><strong>{{ $log->user->name }}</strong></small>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-muted">System</span>
                                @endif
                            </td>
                            <td>
                                @if($log->auditable)
                                    <small class="text-muted">
                                        {{ class_basename($log->auditable_type) }}
                                        #{{ $log->auditable_id }}
                                    </small>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                <small class="text-muted">{{ $log->ip_address ?? '-' }}</small>
                            </td>
                            <td>
                                @if($log->old_values || $log->new_values)
                                    <button type="button"
                                            class="btn btn-sm btn-outline-secondary"
                                            data-bs-toggle="modal"
                                            data-bs-target="#log-details-{{ $log->id }}">
                                        <i class="bi bi-eye"></i> View
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                                <p class="mt-2">No audit logs found</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($logs->hasPages())
            <div class="card-footer">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Detail Modals -->
@foreach($logs as $log)
    @if($log->old_values || $log->new_values)
        <div class="modal fade" id="log-details-{{ $log->id }}" tabindex="-1" aria-labelledby="log-details-{{ $log->id }}-label" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="log-details-{{ $log->id }}-label">
                            Audit Log Details - #{{ $log->id }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <strong>Event:</strong> {{ $log->description }}
                        </div>
                        <div class="mb-3">
                            <strong>User:</strong> {{ $log->user?->name ?? 'System' }}
                        </div>
                        <div class="mb-3">
                            <strong>Date:</strong> {{ $log->created_at->format('M d, Y h:i A') }}
                        </div>
                        <div class="mb-3">
                            <strong>IP Address:</strong> {{ $log->ip_address ?? 'N/A' }}
                        </div>
                        @if($log->user_agent)
                            <div class="mb-3">
                                <strong>User Agent:</strong> <small class="text-muted">{{ $log->user_agent }}</small>
                            </div>
                        @endif

                        <hr>

                        <div class="row">
                            @if($log->old_values)
                                <div class="col-md-6">
                                    <h6 class="text-danger">Old Values</h6>
                                    <div class="bg-light p-3 rounded">
                                        <pre class="mb-0" style="font-size: 12px;">{{ json_encode($log->old_values, JSON_PRETTY_PRINT) }}</pre>
                                    </div>
                                </div>
                            @endif
                            @if($log->new_values)
                                <div class="col-md-{{ $log->old_values ? '6' : '12' }}">
                                    <h6 class="text-success">New Values</h6>
                                    <div class="bg-light p-3 rounded">
                                        <pre class="mb-0" style="font-size: 12px;">{{ json_encode($log->new_values, JSON_PRETTY_PRINT) }}</pre>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endforeach
@endsection
