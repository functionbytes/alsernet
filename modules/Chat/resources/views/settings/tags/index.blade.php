@extends('layouts.theme')

@section('title', 'Conversation tags')

@section('content')

<div class="container-fluid">
    <!-- Header Section -->
    <div class="row mb-4 align-items-center">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-1 fw-bold">Conversation tags</h4>
                    <p class="text-muted small mb-0">Organize and categorize conversations with tags</p>
                </div>
                <div>
                    <a href="{{ route('manager.chat.settings.tickets.tags.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Create tag
                    </a>
                </div>
            </div>
        </div>
    </div>

    @include('settings.components.alerts')

    <!-- Main Card -->
    <div class="card">
        <!-- Header Section -->
        <div class="card-header border-bottom p-3">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-md-5">
                    <label class="form-label fw-semibold mb-1">Search tags</label>
                    <form method="GET" action="{{ route('manager.chat.settings.tickets.tags.index') }}" class="d-flex gap-2">
                        <div class="input-group">
                            <span class="input-group-text bg-white">
                                <i class="fas fa-search"></i>
                            </span>
                            <input type="search" name="search" class="form-control" placeholder="Search by name or description..." value="{{ request('search') }}">
                        </div>
                        <button type="submit" class="btn btn-primary">
                            Search
                        </button>
                    </form>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label fw-semibold mb-1">Status</label>
                    <form method="GET" action="{{ route('manager.chat.settings.tickets.tags.index') }}" class="d-flex gap-2">
                        <select name="status" class="form-control select2 form-select-sm" onchange="this.form.submit()">
                            <option value="">All statuses</option>
                            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </form>
                </div>
                <div class="col-12 col-md-4 text-end">
                    @if(request('search') || request('status'))
                        <a href="{{ route('manager.chat.settings.tickets.tags.index') }}" class="btn btn-secondary btn-sm">
                            Clear filters
                        </a>
                    @endif
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        @if($stats)
        <div class="card-body border-bottom">
            <div class="row g-3">
                <div class="col-12 col-md-4">
                    <div class="card border bg-light-primary">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="card-title text-primary fw-semibold mb-2">Total</h6>
                                    <h4 class="mb-1">{{ $stats['total'] ?? 0 }}</h4>
                                    <small class="text-muted">Tags configured</small>
                                </div>
                                <i class="fas fa-tag text-primary fs-5 opacity-25"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="card border bg-light-success">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="card-title text-success fw-semibold mb-2">Active</h6>
                                    <h4 class="mb-1">{{ $stats['active'] ?? 0 }}</h4>
                                    <small class="text-muted">Tags enabled</small>
                                </div>
                                <i class="fas fa-check-circle text-success fs-5 opacity-25"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="card border bg-light-warning">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="card-title text-warning fw-semibold mb-2">Inactive</h6>
                                    <h4 class="mb-1">{{ $stats['inactive'] ?? 0 }}</h4>
                                    <small class="text-muted">Tags disabled</small>
                                </div>
                                <i class="fas fa-circle-xmark text-warning fs-5 opacity-25"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Tags Table -->
        <div class="card-body">
            @if($tags->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="fw-semibold">Name</th>
                                <th class="fw-semibold">Slug</th>
                                <th class="fw-semibold text-center">Color</th>
                                <th class="fw-semibold">Description</th>
                                <th class="fw-semibold text-center">Usage</th>
                                <th class="fw-semibold text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($tags as $tag)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <strong>{{ $tag->name }}</strong>
                                            @if(!$tag->is_active)
                                                <span class="badge bg-warning">Inactive</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <code class="bg-light px-2 py-1 rounded text-secondary">{{ $tag->slug }}</code>
                                    </td>
                                    <td class="text-center">
                                        @if($tag->color)
                                            <div class="d-flex align-items-center justify-content-center gap-2">
                                                <div class="rounded" style="width: 24px; height: 24px; background-color: {{ $tag->color }}; border: 1px solid #dee2e6;"></div>
                                                <small class="text-muted">{{ $tag->color }}</small>
                                            </div>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $tag->description ? Str::limit($tag->description, 50) : '-' }}</small>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-info">{{ $tag->usage_count ?? 0 }}</span>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="{{ route('manager.chat.settings.tickets.tags.edit', $tag->id) }}"
                                               class="btn btn-outline-primary"
                                               title="Edit tag">
                                                <i class="fas fa-pen-to-square"></i>
                                            </a>
                                            <button type="button" class="btn btn-outline-danger delete-tag"
                                                    data-tag-id="{{ $tag->id }}"
                                                    data-tag-name="{{ $tag->name }}"
                                                    title="Delete tag">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if($tags->hasPages())
                    <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
                        <div class="text-muted small">
                            Showing <strong>{{ $tags->firstItem() }}</strong> to <strong>{{ $tags->lastItem() }}</strong>
                            of <strong>{{ $tags->total() }}</strong> tags
                        </div>
                        <nav aria-label="Page navigation">
                            {{ $tags->links() }}
                        </nav>
                    </div>
                @endif

            @else
                <div class="text-center py-5">
                    <div class="d-flex flex-column align-items-center">
                        <div class="rounded-circle bg-light p-4 mb-3 d-flex align-items-center justify-content-center">
                            <i class="fas fa-tags fs-3 text-muted"></i>
                        </div>
                        <h6 class="mb-2 fw-semibold">No tags found</h6>
                        <p class="text-muted mb-3">
                            @if(request('search'))
                                No results found for "{{ request('search') }}"
                            @else
                                Create your first tag to organize conversations
                            @endif
                        </p>
                        @if(!request('search'))
                            <a href="{{ route('manager.chat.settings.tickets.tags.create') }}" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus me-2"></i>Create first tag
                            </a>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteTagModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold">Delete tag</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0">Are you sure you want to delete the tag <strong id="tagNameDisplay"></strong>? This action cannot be undone.</p>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteTagForm" method="POST" class="d-inline">
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
    // Show delete confirmation modal
    $('.delete-tag').on('click', function() {
        const tagId = $(this).data('tag-id');
        const tagName = $(this).data('tag-name');
        const deleteRoute = '{{ route("manager.chat.settings.tickets.tags.destroy", ":id") }}'.replace(':id', tagId);

        $('#tagNameDisplay').text(tagName);
        $('#deleteTagForm').attr('action', deleteRoute);
        $('#deleteTagModal').modal('show');
    });

    // Toast messages
    @if (session('success'))
        toastr.success('{{ session('success') }}', 'Success');
    @endif

    @if (session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif
});
</script>
@endpush
