@extends('layouts.admin')

@section('title', 'Integrations Hooks')

@section('content')

    @include('helpdeskchat::includes.card', ['title' => 'Integration Hooks', 'icon' => 'fa-plug'])

    <div class="widget-content">
        @include('helpdeskchat::components.alerts')

        <div class="d-flex justify-content-between align-items-center mb-4">
            <p class="text-muted mb-0">
                <i class="fa fa-info-circle me-1"></i>
                Third-party integrations (Slack, Dialogflow, etc.)
            </p>
            <a href="{{ route('admin.integrations.create') }}" class="btn btn-primary">
                <i class="fa fa-plus-circle me-2"></i>Create Integration
            </a>
        </div>

        <div class="card border">
            <div class="card-body">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>App ID</th>
                        <th>Inbox</th>
                        <th>Status</th>
                        <th>Hook Type</th>
                        <th width="200">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($integrations as $hook)
                        <tr>
                            <td><strong>{{ $hook->app_id }}</strong></td>
                            <td>{{ $hook->inbox->name ?? 'Account Level' }}</td>
                            <td>
                                <form action="{{ route('admin.integrations.toggle', $hook) }}" method="POST" style="display: inline;">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-{{ $hook->status === 1 ? 'success' : 'outline-secondary' }}">
                                        <i class="fa fa-{{ $hook->status === 1 ? 'check' : 'times' }} me-1"></i>
                                        {{ $hook->status === 1 ? 'Enabled' : 'Disabled' }}
                                    </button>
                                </form>
                            </td>
                            <td><code>{{ $hook->hook_type }}</code></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('admin.integrations.edit', $hook) }}" class="btn btn-outline-primary" title="Edit">
                                        <i class="fa fa-edit"></i>
                                    </a>
                                    <button type="button" class="btn btn-outline-danger" title="Delete"
                                        onclick="if(confirm('Are you sure you want to delete this integration?')) document.getElementById('delete-{{ $hook->id }}').submit()">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </div>
                                <form id="delete-{{ $hook->id }}" action="{{ route('admin.integrations.destroy', $hook) }}" method="POST" style="display:none">
                                    @csrf @method('DELETE')
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">No integration hooks found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
                {{ $integrations->links() }}
            </div>
        </div>
    </div>

@endsection
