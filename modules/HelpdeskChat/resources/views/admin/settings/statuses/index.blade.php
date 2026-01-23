@extends('layouts.admin')

@section('title', 'Conversation statuses')

@section('content')

<div class="container-fluid">
    <!-- Header Section -->
    <div class="row mb-4 align-items-center">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-1 fw-bold">Conversation statuses</h4>
                    <p class="text-muted small mb-0">Define and organize the conversation workflow states</p>
                </div>
                <div>
                    <a href="{{ route('manager.helpdesk.settings.tickets.statuses.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Create status
                    </a>
                </div>
            </div>
        </div>
    </div>

    @include('admin.components.alerts')

    <!-- Main Card -->
    <div class="card">
        <!-- Header Section -->
        <div class="card-header border-bottom p-3">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-md-5">
                    <label class="form-label fw-semibold mb-1">Search statuses</label>
                    <form method="GET" action="{{ route('manager.helpdesk.settings.tickets.statuses.index') }}" class="d-flex gap-2">
                        <div class="input-group">
                            <span class="input-group-text bg-white">
                                <i class="fas fa-search"></i>
                            </span>
                            <input type="search" name="search" class="form-control" placeholder="Search by name or slug..." value="{{ request('search') }}">
                        </div>
                        <button type="submit" class="btn btn-primary">
                            Search
                        </button>
                    </form>
                </div>
                <div class="col-12 col-md-7 text-end">
                    @if(request('search'))
                        <a href="{{ route('manager.helpdesk.settings.tickets.statuses.index') }}" class="btn btn-secondary btn-sm">
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
                <div class="col-12 col-md-6 col-lg-3">
                    <div class="card border bg-light-primary">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="card-title text-primary fw-semibold mb-2">Total</h6>
                                    <h4 class="mb-1">{{ $stats['total'] ?? 0 }}</h4>
                                    <small class="text-muted">Statuses configured</small>
                                </div>
                                <i class="fas fa-list-check text-primary fs-5 opacity-25"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-3">
                    <div class="card border bg-light-success">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="card-title text-success fw-semibold mb-2">Active</h6>
                                    <h4 class="mb-1">{{ $stats['active'] ?? 0 }}</h4>
                                    <small class="text-muted">Statuses enabled</small>
                                </div>
                                <i class="fas fa-check-circle text-success fs-5 opacity-25"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-3">
                    <div class="card border bg-light-warning">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="card-title text-warning fw-semibold mb-2">Inactive</h6>
                                    <h4 class="mb-1">{{ $stats['inactive'] ?? 0 }}</h4>
                                    <small class="text-muted">Statuses disabled</small>
                                </div>
                                <i class="fas fa-circle-xmark text-warning fs-5 opacity-25"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-3">
                    <div class="card border bg-light-info">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="card-title text-info fw-semibold mb-2">System</h6>
                                    <h4 class="mb-1">{{ $stats['system'] ?? 0 }}</h4>
                                    <small class="text-muted">System statuses</small>
                                </div>
                                <i class="fas fa-gear text-info fs-5 opacity-25"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Statuses Table -->
        <div class="card-body">
            @if($statuses->count() > 0)
                <div class="alert alert-info mb-3">
                    <i class="fas fa-hand-pointer me-2"></i>Drag and drop to reorder statuses
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="statusesTable">
                        <thead class="table-light">
                            <tr>
                                <th class="fw-semibold" style="width: 5%;"></th>
                                <th class="fw-semibold" style="width: 25%;">Name</th>
                                <th class="fw-semibold" style="width: 20%;">Slug</th>
                                <th class="fw-semibold" style="width: 25%;">Description</th>
                                <th class="fw-semibold text-center" style="width: 10%;">Status</th>
                                <th class="fw-semibold text-center" style="width: 15%;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="statusesList">
                            @foreach($statuses as $status)
                                <tr data-id="{{ $status->id }}" class="sortable-row">
                                    <td class="drag-handle text-center align-middle">
                                        <i class="fas fa-grip-vertical" style="cursor: grab;"></i>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <strong>{{ $status->name }}</strong>
                                            @if($status->is_default)
                                                <span class="badge bg-primary">Default</span>
                                            @endif
                                            @if($status->is_system)
                                                <span class="badge bg-warning">System</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <code class="bg-light px-2 py-1 rounded text-secondary">{{ $status->slug }}</code>
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $status->description ? Str::limit($status->description, 60) : '-' }}</small>
                                    </td>
                                    <td class="text-center">
                                        <form method="POST" action="{{ route('manager.helpdesk.settings.tickets.statuses.toggle', $status->id) }}" class="toggle-form">
                                            @csrf
                                            @method('PATCH')
                                            <div class="form-check form-switch d-inline-block">
                                                <input type="checkbox" class="form-check-input toggle-checkbox" role="switch"
                                                       {{ $status->active ? 'checked' : '' }}
                                                       onchange="this.form.submit()">
                                            </div>
                                        </form>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="{{ route('manager.helpdesk.settings.tickets.statuses.edit', $status->id) }}"
                                               class="btn btn-outline-primary"
                                               title="Edit status">
                                                <i class="fas fa-pen-to-square"></i>
                                            </a>
                                            @if($status->canDelete())
                                                <button type="button" class="btn btn-outline-danger delete-status"
                                                        data-status-id="{{ $status->id }}"
                                                        data-status-name="{{ $status->name }}"
                                                        title="Delete status">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if($statuses->hasPages())
                    <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
                        <div class="text-muted small">
                            Showing <strong>{{ $statuses->firstItem() }}</strong> to <strong>{{ $statuses->lastItem() }}</strong>
                            of <strong>{{ $statuses->total() }}</strong> statuses
                        </div>
                        <nav aria-label="Page navigation">
                            {{ $statuses->links() }}
                        </nav>
                    </div>
                @endif

            @else
                <div class="text-center py-5">
                    <div class="d-flex flex-column align-items-center">
                        <div class="rounded-circle bg-light p-4 mb-3 d-flex align-items-center justify-content-center">
                            <i class="fas fa-list-check fs-3 text-muted"></i>
                        </div>
                        <h6 class="mb-2 fw-semibold">No statuses found</h6>
                        <p class="text-muted mb-3">
                            @if(request('search'))
                                No results found for "{{ request('search') }}"
                            @else
                                Create your first status to organize the conversation workflow
                            @endif
                        </p>
                        @if(!request('search'))
                            <a href="{{ route('manager.helpdesk.settings.tickets.statuses.create') }}" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus me-2"></i>Create first status
                            </a>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteStatusModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold">Delete status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0">Are you sure you want to delete the status <strong id="statusNameDisplay"></strong>? This action cannot be undone.</p>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteStatusForm" method="POST" class="d-inline">
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
<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>

<style>
    .sortable-row {
        cursor: move;
    }
    .sortable-row:hover {
        background-color: #f8f9fa;
    }
    .ui-sortable-helper {
        display: table;
        background-color: #fff;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
    .ui-sortable-placeholder {
        background-color: #e9ecef;
        visibility: visible !important;
        border: 2px dashed #dee2e6;
    }
    .drag-handle {
        cursor: grab;
    }
    .drag-handle:active {
        cursor: grabbing;
    }
</style>

<script>
$(document).ready(function() {
    // Show delete confirmation modal
    $('.delete-status').on('click', function() {
        const statusId = $(this).data('status-id');
        const statusName = $(this).data('status-name');
        const deleteRoute = '{{ route("manager.helpdesk.settings.tickets.statuses.destroy", ":id") }}'.replace(':id', statusId);

        $('#statusNameDisplay').text(statusName);
        $('#deleteStatusForm').attr('action', deleteRoute);
        $('#deleteStatusModal').modal('show');
    });

    // Initialize sortable
    $('#statusesList').sortable({
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
            $('#statusesList tr').each(function() {
                ids.push($(this).data('id'));
            });

            // Save new order
            $.ajax({
                url: '{{ route('manager.helpdesk.settings.tickets.statuses.reorder') }}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    ids: ids
                },
                success: function(response) {
                    toastr.success(response.message || 'Order updated successfully', 'Success');
                },
                error: function(xhr) {
                    toastr.error('Error updating order', 'Error');
                    $('#statusesList').sortable('cancel');
                }
            });
        }
    });

    // Prevent multiple toggle submissions
    $('.toggle-form').on('submit', function() {
        $(this).find('.toggle-checkbox').prop('disabled', true);
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
