@extends('layouts.admin')

@section('title', 'Canned responses')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col">
            <h1 class="h3">Canned responses</h1>
            <p class="text-muted">Quick reply templates for common conversations with variable substitution</p>
        </div>
        <div class="col-auto">
            <a href="{{ route('admin.canneds.create') }}" class="btn btn-primary">
                <i class="fa fa-plus-circle me-1"></i> Create new response
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
            <form method="GET" action="{{ route('admin.canneds.index') }}">
                <div class="row">
                    <div class="col-12 col-md-4 mb-3 mb-md-0">
                        <label for="search" class="form-label">Search</label>
                        <input type="text" class="form-control" id="search" name="search" value="{{ request('search') }}" placeholder="Search by short code, title or content...">
                    </div>
                    <div class="col-12 col-md-3 mb-3 mb-md-0">
                        <label for="visibility" class="form-label">Visibility</label>
                        <select class="form-select" id="visibility" name="visibility">
                            <option value="">All</option>
                            <option value="personal" {{ request('visibility') === 'personal' ? 'selected' : '' }}>Personal</option>
                            <option value="team" {{ request('visibility') === 'team' ? 'selected' : '' }}>Team</option>
                            <option value="everyone" {{ request('visibility') === 'everyone' ? 'selected' : '' }}>Everyone</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-2 mb-3 mb-md-0">
                        <label for="sort" class="form-label">Sort by</label>
                        <select class="form-select" id="sort" name="sort">
                            <option value="created_at" {{ request('sort') === 'created_at' ? 'selected' : '' }}>Recently created</option>
                            <option value="usage" {{ request('sort') === 'usage' ? 'selected' : '' }}>Most used</option>
                            <option value="short_code" {{ request('sort') === 'short_code' ? 'selected' : '' }}>Short code</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="fa fa-search"></i> Search
                        </button>
                        @if(request('search') || request('visibility') || request('sort'))
                            <a href="{{ route('admin.canneds.index') }}" class="btn btn-secondary">
                                Clear
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Responses Table -->
    <div class="card">
        <div class="card-body">
            @if($canneds->count() > 0)
                <div class="table-responsive">
                    <table id="cannedsTable" class="table table-hover">
                        <thead>
                            <tr>
                                <th>Short code</th>
                                <th>Title / Content</th>
                                <th>Visibility</th>
                                <th>Usage</th>
                                <th>Created by</th>
                                <th>Created</th>
                                <th width="150">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($canneds as $response)
                                <tr>
                                    <td>
                                        <code class="bg-light px-2 py-1 rounded">{{ $response->short_code }}</code>
                                    </td>
                                    <td>
                                        @if($response->title)
                                            <strong>{{ $response->title }}</strong><br>
                                        @endif
                                        <small class="text-muted">{{ Str::limit($response->content, 60) }}</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $response->visibility_badge_color }}">
                                            {{ $response->visibility_label }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">{{ $response->usage_count }}</span>
                                        @if($response->last_used_at)
                                            <br><small class="text-muted">{{ $response->last_used_at->diffForHumans() }}</small>
                                        @endif
                                    </td>
                                    <td>{{ $response->user?->name ?? 'System' }}</td>
                                    <td>{{ $response->created_at->format('M d, Y') }}</td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-info" title="Preview"
                                                onclick="showPreview('{{ addslashes($response->title ?? 'Preview') }}', '{{ addslashes($response->content) }}')">
                                                <i class="fa fa-eye"></i>
                                            </button>
                                            <a href="{{ route('admin.canneds.edit', $response) }}" class="btn btn-outline-secondary" title="Edit">
                                                <i class="fa fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-outline-danger delete-btn" title="Delete"
                                                data-url="{{ route('admin.canneds.destroy', $response) }}">
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
                    <h6 class="mb-2">No canned responses to display</h6>
                    <p class="text-muted mb-3">
                        @if(request('search'))
                            No results found for "{{ request('search') }}"
                        @else
                            Create your first canned response to save time when responding to common questions
                        @endif
                    </p>
                    @if(!request('search'))
                        <a href="{{ route('admin.canneds.create') }}" class="btn btn-primary">
                            <i class="fa fa-plus"></i> Create first response
                        </a>
                    @endif
                </div>
            @endif
        </div>
    </div>

    <!-- Pagination -->
    @if($canneds->hasPages())
        <div class="mt-3">
            {{ $canneds->links() }}
        </div>
    @endif
</div>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="previewTitle">Response preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="previewContent"></div>
            <div class="modal-footer">
                <small class="text-muted me-auto">Variables like @{{contact.name}} will be replaced when used</small>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
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
                Are you sure you want to delete this canned response? This action cannot be undone.
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
function showPreview(title, content) {
    $('#previewTitle').text(title);
    $('#previewContent').html('<pre class="mb-0">' + content + '</pre>');
    new bootstrap.Modal($('#previewModal')).show();
}

$(document).ready(function() {
    // Initialize DataTables
    $('#cannedsTable').DataTable({
        paging: true,
        searching: true,
        ordering: true,
        info: true,
        lengthChange: true,
        pageLength: 10,
        order: [[0, 'asc']],
        language: {
            search: 'Search responses:',
            lengthMenu: 'Show _MENU_ responses per page',
            info: 'Showing _START_ to _END_ of _TOTAL_ responses',
            infoEmpty: 'No responses found',
            emptyTable: 'No responses found',
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
