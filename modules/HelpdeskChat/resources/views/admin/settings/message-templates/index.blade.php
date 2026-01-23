@extends('layouts.admin')

@section('title', 'Message templates')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col">
            <h1 class="h3">Message templates</h1>
            <p class="text-muted">Quick message templates for conversations with variable substitution</p>
        </div>
        <div class="col-auto">
            <a href="{{ route('admin.message-templates.create') }}" class="btn btn-primary">
                <i class="fa fa-plus-circle me-1"></i> Create new template
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Filters -->
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.message-templates.index') }}">
                <div class="row">
                    <div class="col-12 col-md-4 mb-3 mb-md-0">
                        <label for="category" class="form-label">Category</label>
                        <select name="category" id="category" class="form-select">
                            <option value="">All categories</option>
                            @foreach($categories as $key => $label)
                                <option value="{{ $key }}" {{ request('category') == $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-4 mb-3 mb-md-0">
                        <label for="search" class="form-label">Search</label>
                        <input type="text" name="search" id="search" class="form-control"
                               placeholder="Search by name, content, or shortcut..."
                               value="{{ request('search') }}">
                    </div>
                    <div class="col-12 col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="fa fa-search"></i> Search
                        </button>
                        @if(request('search') || request('category'))
                            <a href="{{ route('admin.message-templates.index') }}" class="btn btn-secondary">
                                Clear
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Templates Table -->
    <div class="card">
        <div class="card-body">
            @if($templates->count() > 0)
                <div class="table-responsive">
                    <table id="templatesTable" class="table table-hover">
                        <thead>
                            <tr>
                                <th>Name / Content</th>
                                <th>Shortcut</th>
                                <th>Category</th>
                                <th>Type</th>
                                <th>Usage</th>
                                <th>Last used</th>
                                <th width="150">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($templates as $template)
                                <tr>
                                    <td>
                                        <strong>{{ $template->name }}</strong>
                                        <br>
                                        <small class="text-muted">
                                            {{ Str::limit($template->content, 60) }}
                                        </small>
                                    </td>
                                    <td>
                                        @if($template->shortcut)
                                            <code class="bg-light px-2 py-1 rounded">/{{ $template->shortcut }}</code>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($template->category)
                                            <span class="badge bg-secondary">
                                                {{ $categories[$template->category] ?? $template->category }}
                                            </span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($template->is_public)
                                            <span class="badge bg-success">Public</span>
                                        @else
                                            <span class="badge bg-info">Private</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">{{ number_format($template->usage_count) }}</span>
                                    </td>
                                    <td>
                                        @if($template->last_used_at)
                                            <small class="text-muted">
                                                {{ $template->last_used_at->diffForHumans() }}
                                            </small>
                                        @else
                                            <span class="text-muted">Never</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('admin.message-templates.show', $template) }}"
                                               class="btn btn-outline-info"
                                               title="View">
                                                <i class="fa fa-eye"></i>
                                            </a>
                                            <a href="{{ route('admin.message-templates.edit', $template) }}"
                                               class="btn btn-outline-secondary"
                                               title="Edit">
                                                <i class="fa fa-edit"></i>
                                            </a>
                                            <button type="button"
                                                    class="btn btn-outline-danger delete-btn"
                                                    data-url="{{ route('admin.message-templates.destroy', $template) }}"
                                                    title="Delete">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-5">
                    <div class="mb-3 text-muted">
                        <i class="fa fa-comment-dots fa-3x"></i>
                    </div>
                    <h6 class="mb-2">No message templates to display</h6>
                    <p class="text-muted mb-3">
                        @if(request('search'))
                            No results found for "{{ request('search') }}"
                        @else
                            Create your first message template to save time when responding to conversations
                        @endif
                    </p>
                    @if(!request('search'))
                        <a href="{{ route('admin.message-templates.create') }}" class="btn btn-primary">
                            <i class="fa fa-plus"></i> Create first template
                        </a>
                    @endif
                </div>
            @endif
        </div>
    </div>

    <!-- Pagination -->
    @if($templates->hasPages())
        <div class="mt-3">
            {{ $templates->links() }}
        </div>
    @endif
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm deletion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this message template? This action cannot be undone.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="delete-form" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Initialize DataTables
    $('#templatesTable').DataTable({
        paging: true,
        searching: true,
        ordering: true,
        info: true,
        lengthChange: true,
        pageLength: 10,
        order: [[0, 'asc']],
        language: {
            search: 'Search templates:',
            lengthMenu: 'Show _MENU_ templates per page',
            info: 'Showing _START_ to _END_ of _TOTAL_ templates',
            infoEmpty: 'No templates found',
            emptyTable: 'No templates found',
            paginate: {
                first: 'First',
                last: 'Last',
                next: 'Next',
                previous: 'Previous'
            }
        },
        columnDefs: [
            {
                targets: [6],
                orderable: false,
                searchable: false
            }
        ]
    });

    // Delete confirmation
    $('.delete-btn').on('click', function() {
        const url = $(this).data('url');
        $('#delete-form').attr('action', url);
        new bootstrap.Modal($('#deleteModal')).show();
    });
});
</script>
@endpush
