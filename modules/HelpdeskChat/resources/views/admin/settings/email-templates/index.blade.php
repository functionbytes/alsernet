@extends('layouts.admin')

@section('title', 'Email Templates')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col">
            <h1 class="h3">Email Templates</h1>
            <p class="text-muted">Manage automated email notifications sent by the system</p>
        </div>
        <div class="col-auto">
            <a href="{{ route('admin.email-templates.create') }}" class="btn btn-primary">
                <i class="fa fa-plus-circle me-1"></i> Create Template
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Filters -->
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.email-templates.index') }}" class="row g-3">
                <div class="col-md-4">
                    <label for="event_type" class="form-label">Event Type</label>
                    <select class="form-select" id="event_type" name="event_type">
                        <option value="">All Event Types</option>
                        @foreach($eventTypes as $key => $label)
                            <option value="{{ $key }}" {{ request('event_type') === $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="active" class="form-label">Status</label>
                    <select class="form-select" id="active" name="active">
                        <option value="">All</option>
                        <option value="1" {{ request('active') === '1' ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ request('active') === '0' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <div class="col-md-5 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="bi bi-funnel"></i> Apply Filters
                    </button>
                    <a href="{{ route('admin.email-templates.index') }}" class="btn btn-outline-secondary">
                        <i class="fa fa-times-circle"></i> Clear
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Templates Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Event Type</th>
                            <th>Subject</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th width="200">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($templates as $template)
                            <tr>
                                <td>
                                    <strong>{{ $template->name }}</strong>
                                    @if($template->description)
                                        <br><small class="text-muted">{{ Str::limit($template->description, 50) }}</small>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-info">
                                        {{ $eventTypes[$template->event_type] ?? $template->event_type }}
                                    </span>
                                </td>
                                <td>
                                    <small class="text-muted">{{ Str::limit($template->subject, 40) }}</small>
                                </td>
                                <td>
                                    <form method="POST" action="{{ route('admin.email-templates.toggle', $template) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-{{ $template->active ? 'success' : 'secondary' }}">
                                            <i class="bi bi-{{ $template->active ? 'check-circle' : 'x-circle' }}"></i>
                                            {{ $template->active ? 'Active' : 'Inactive' }}
                                        </button>
                                    </form>
                                </td>
                                <td>{{ $template->created_at->format('M d, Y') }}</td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('admin.email-templates.preview', $template) }}" class="btn btn-outline-info" title="Preview" target="_blank">
                                            <i class="fa fa-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.email-templates.edit', $template) }}" class="btn btn-outline-secondary" title="Edit">
                                            <i class="fa fa-edit"></i>
                                        </a>
                                        <button type="button" class="btn btn-outline-danger" title="Delete"
                                            onclick="confirmDelete('{{ route('admin.email-templates.destroy', $template) }}')">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    <i class="bi bi-envelope fs-1 d-block mb-2"></i>
                                    No email templates found. <a href="{{ route('admin.email-templates.create') }}">Create one now</a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-center mt-3">
                {{ $templates->links() }}
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
    if (confirm('Are you sure you want to delete this email template?')) {
        const form = $('#delete-form');
        form.attr('action', url);
        form.submit();
    }
}
</script>
@endsection
