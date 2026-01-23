@extends('layouts.admin')

@section('title', $team->name)

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col">
            <h1 class="h3">{{ $team->name }}</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.teams.index') }}">Teams</a></li>
                    <li class="breadcrumb-item active">{{ $team->name }}</li>
                </ol>
            </nav>
        </div>
        <div class="col-auto">
            <a href="{{ route('admin.teams.edit', $team) }}" class="btn btn-primary">
                <i class="fa fa-edit me-1"></i> Edit Team
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="card-title mb-0">Team Details</h5>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-5">Name:</dt>
                        <dd class="col-sm-7">{{ $team->name }}</dd>

                        <dt class="col-sm-5">Description:</dt>
                        <dd class="col-sm-7">{{ $team->description ?? 'N/A' }}</dd>

                        <dt class="col-sm-5">Auto Assign:</dt>
                        <dd class="col-sm-7">
                            @if($team->allow_auto_assign)
                                <span class="badge bg-success">Enabled</span>
                            @else
                                <span class="badge bg-secondary">Disabled</span>
                            @endif
                        </dd>

                        <dt class="col-sm-5">Created:</dt>
                        <dd class="col-sm-7">{{ $team->created_at->format('M d, Y H:i') }}</dd>

                        <dt class="col-sm-5">Updated:</dt>
                        <dd class="col-sm-7">{{ $team->updated_at->format('M d, Y H:i') }}</dd>
                    </dl>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Team Members ({{ $team->members->count() }})</h5>
                </div>
                <div class="list-group list-group-flush">
                    @forelse($team->members as $member)
                        <div class="list-group-item">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <strong>{{ $member->name }}</strong>
                                    <br>
                                    <small class="text-muted">{{ $member->email }}</small>
                                </div>
                                <span class="badge bg-info">{{ ucfirst($member->role) }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="list-group-item text-muted text-center">
                            No members assigned yet
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Recent Conversations</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Contact</th>
                                <th>Inbox</th>
                                <th>Status</th>
                                <th>Assignee</th>
                                <th>Last Activity</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($team->conversations as $conversation)
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.conversation.show', $conversation) }}">
                                            {{ $conversation->contact->name }}
                                        </a>
                                    </td>
                                    <td>{{ $conversation->inbox->name }}</td>
                                    <td>
                                        <span class="badge bg-{{ $conversation->status === 'open' ? 'success' : 'secondary' }}">
                                            {{ ucfirst($conversation->status) }}
                                        </span>
                                    </td>
                                    <td>{{ $conversation->assignee->name ?? 'Unassigned' }}</td>
                                    <td>{{ $conversation->last_activity_at?->diffForHumans() ?? 'N/A' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-3">
                                        No conversations assigned to this team yet
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
