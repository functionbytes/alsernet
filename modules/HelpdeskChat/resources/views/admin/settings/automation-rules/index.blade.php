@extends('layouts.admin')

@section('title', 'Automation Rules')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col">
            <h1 class="h3">Automation Rules</h1>
        </div>
        <div class="col-auto">
            <a href="{{ route('admin.automation-rules.create') }}" class="btn btn-primary">
                <i class="fa fa-plus-circle me-1"></i> Create Rule
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Event</th>
                        <th>Conditions</th>
                        <th>Actions</th>
                        <th>Status</th>
                        <th width="200">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($automationRules as $rule)
                        <tr>
                            <td><strong>{{ $rule->name }}</strong></td>
                            <td><code>{{ $rule->event_name }}</code></td>
                            <td><span class="badge bg-secondary">{{ count($rule->conditions) }} conditions</span></td>
                            <td><span class="badge bg-info">{{ count($rule->actions) }} actions</span></td>
                            <td>
                                <form action="{{ route('admin.automation-rules.toggle', $rule) }}" method="POST" style="display: inline;">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-{{ $rule->active ? 'success' : 'secondary' }}">
                                        {{ $rule->active ? 'Active' : 'Inactive' }}
                                    </button>
                                </form>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('admin.automation-rules.edit', $rule) }}" class="btn btn-outline-secondary">
                                        <i class="fa fa-edit"></i>
                                    </a>
                                    <button type="button" class="btn btn-outline-danger"
                                        onclick="if(confirm('Delete this rule?')) 20 20 12 61 79 80 81 98 33 100 204 250 395 398 399 400 701"#'delete-{{ $rule->id }}').submit()">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </div>
                                <form id="delete-{{ $rule->id }}" action="{{ route('admin.automation-rules.destroy', $rule) }}" method="POST" style="display:none">
                                    @csrf @method('DELETE')
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">
                                No automation rules found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            {{ $automationRules->links() }}
        </div>
    </div>
</div>
@endsection
