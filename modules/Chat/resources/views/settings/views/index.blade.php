@extends('layouts.theme')

@section('title', 'Views')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col">
            <h1 class="h3">Conversation views</h1>
            <p class="text-muted">Create and manage custom filters to organize conversations</p>
        </div>
        <div class="col-auto">
            <a href="{{ route('manager.chat.settings.tickets.views.create') }}" class="btn btn-primary">
                <i class="fa fa-plus-circle me-1"></i> Create new view
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

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-12 col-md-6 col-lg-3 mb-3 mb-lg-0">
            <div class="card bg-light">
                <div class="card-body">
                    <h6 class="card-title text-primary mb-2">Total</h6>
                    <h4 class="mb-1 fw-bold">{{ $stats['total'] }}</h4>
                    <small class="text-muted">Configured views</small>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-lg-3 mb-3 mb-lg-0">
            <div class="card bg-light">
                <div class="card-body">
                    <h6 class="card-title text-info mb-2">Personal</h6>
                    <h4 class="mb-1 fw-bold">{{ $stats['personal'] }}</h4>
                    <small class="text-muted">Just for you</small>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-lg-3 mb-3 mb-md-0">
            <div class="card bg-light">
                <div class="card-body">
                    <h6 class="card-title mb-2">Public</h6>
                    <h4 class="mb-1 fw-bold">{{ $stats['public'] }}</h4>
                    <small class="text-muted">Shared with team</small>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-lg-3">
            <div class="card bg-light">
                <div class="card-body">
                    <h6 class="card-title text-warning mb-2">System</h6>
                    <h4 class="mb-1 fw-bold">{{ $stats['system'] }}</h4>
                    <small class="text-muted">Predefined views</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Search and Filter -->
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('manager.chat.settings.tickets.views.index') }}">
                <div class="row">
                    <div class="col-12 col-md-6 mb-3 mb-md-0">
                        <div class="input-group">
                            <span class="input-group-text bg-white">
                                <i class="fa fa-search"></i>
                            </span>
                            <input type="search" name="search" class="form-control" placeholder="Search by name..." value="{{ request('search') }}">
                        </div>
                    </div>
                    <div class="col-12 col-md-3 mb-3 mb-md-0">
                        <select name="scope" class="form-control select2">
                            <option value="">All views</option>
                            <option value="personal" {{ request('scope') === 'personal' ? 'selected' : '' }}>My views</option>
                            <option value="public" {{ request('scope') === 'public' ? 'selected' : '' }}>Public views</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-3">
                        <button type="submit" class="btn btn-primary w-100">
                            Search
                        </button>
                        @if(request('search') || request('scope'))
                            <a href="{{ route('manager.chat.settings.tickets.views.index') }}" class="btn btn-secondary w-100 mt-2">
                                Clear
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Views Table -->
    <div class="card">
        <div class="card-body">
            @if($views->count() > 0)
                <div class="alert alert-info mb-3">
                    <i class="fa fa-hand-pointer me-2"></i> Drag and drop to reorder views
                </div>

                <div class="table-responsive">
                    <table id="viewsTable" class="table table-hover">
                        <thead>
                            <tr>
                                <th width="50"></th>
                                <th>Name</th>
                                <th>Filters</th>
                                <th>Scope</th>
                                <th>Default</th>
                                <th width="150">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="viewsList">
                            @foreach($views as $view)
                                <tr data-id="{{ $view->id }}" class="sortable-row">
                                    <td class="drag-handle text-center align-middle">
                                        <i class="fa fa-grip-vertical" style="cursor: grab;"></i>
                                    </td>
                                    <td>
                                        <div>
                                            <strong>{{ $view->name }}</strong>
                                            @if($view->description)
                                                <div class="small text-muted mt-1">{{ Str::limit($view->description, 60) }}</div>
                                            @endif
                                            @if($view->is_system)
                                                <span class="badge bg-warning mt-1">System</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $view->getFilterSummary() }}</small>
                                    </td>
                                    <td>
                                        @if($view->is_public)
                                            <span class="badge bg-success">
                                                <i class="fa fa-globe"></i> Public
                                            </span>
                                        @else
                                            <span class="badge bg-info">
                                                <i class="fa fa-user"></i> Personal
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($view->is_default)
                                            <span class="badge bg-primary">
                                                <i class="fa fa-star"></i> Yes
                                            </span>
                                        @else
                                            <span class="text-muted">No</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            @if($view->canEdit(Auth::id()))
                                                <a href="{{ route('manager.chat.settings.tickets.views.edit', $view->id) }}" class="btn btn-secondary" title="Edit">
                                                    <i class="fa fa-edit"></i>
                                                </a>
                                            @endif
                                            @if($view->canDelete())
                                                <button type="button" class="btn btn-outline-danger" title="Delete"
                                                    onclick="confirmDelete('{{ route('manager.chat.settings.tickets.views.destroy', $view->id) }}')">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            @endif
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
                        <i class="fa fa-eye fa-3x"></i>
                    </div>
                    <h6 class="mb-2">No views to display</h6>
                    <p class="text-muted mb-3">
                        @if(request('search'))
                            No results found for "{{ request('search') }}"
                        @else
                            Create your first custom view to organize conversations
                        @endif
                    </p>
                    @if(!request('search'))
                        <a href="{{ route('manager.chat.settings.tickets.views.create') }}" class="btn btn-primary">
                            <i class="fa fa-plus"></i> Create first view
                        </a>
                    @endif
                </div>
            @endif
        </div>
    </div>

    <!-- Pagination -->
    @if($views->hasPages())
        <div class="mt-3">
            {{ $views->links() }}
        </div>
    @endif
</div>

<form id="delete-form" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>
@endsection

@push('scripts')
<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>

<script>
function confirmDelete(url) {
    if (confirm('Are you sure you want to delete this view?')) {
        const form = document.querySelector('#delete-form');
        form.action = url;
        form.submit();
    }
}

$(document).ready(function() {
    // Initialize DataTables
    $('#viewsTable').DataTable({
        paging: true,
        searching: true,
        ordering: true,
        info: true,
        lengthChange: true,
        pageLength: 10,
        order: [[1, 'asc']],
        language: {
            search: 'Search views:',
            lengthMenu: 'Show _MENU_ views per page',
            info: 'Showing _START_ to _END_ of _TOTAL_ views',
            infoEmpty: 'No views found',
            emptyTable: 'No views found',
            paginate: {
                first: 'First',
                last: 'Last',
                next: 'Next',
                previous: 'Previous'
            }
        },
        columnDefs: [
            {
                targets: [0, 5],
                orderable: false,
                searchable: false
            }
        ]
    });

    // Initialize sortable
    $('#viewsList').sortable({
        handle: '.drag-handle',
        axis: 'y',
        cursor: 'grabbing',
        placeholder: 'ui-sortable-placeholder',
        helper: function(e, tr) {
            var $originals = tr.children();
            var $helper = tr.clone();
            $helper.children().each(function(index) {
                $(this).width($originals.eq(index).width());
            });
            return $helper;
        },
        start: function(e, ui) {
            ui.placeholder.height(ui.item.height());
        },
        update: function(event, ui) {
            const ids = [];
            $('#viewsList tr').each(function() {
                ids.push($(this).data('id'));
            });

            // Save new order
            $.ajax({
                url: '{{ route('manager.chat.settings.tickets.views.reorder') }}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    ids: ids
                },
                success: function(response) {
                    if (typeof toastr !== 'undefined') {
                        toastr.success(response.message || 'Order updated successfully', 'Success');
                    }
                },
                error: function(xhr) {
                    if (typeof toastr !== 'undefined') {
                        toastr.error('Error updating order', 'Error');
                    }
                    $('#viewsList').sortable('cancel');
                }
            });
        }
    });
});
</script>
@endpush
