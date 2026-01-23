@extends('layouts.admin')

@section('title', 'Macros')

@section('content')

<div class="container-fluid">
    <!-- Header Section -->
    <div class="row mb-4 align-items-center">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-1 fw-bold">Macros</h4>
                    <p class="text-muted small mb-0">Automate repetitive actions on conversations</p>
                </div>
                <div>
                    <a href="{{ route('admin.macros.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Create macro
                    </a>
                </div>
            </div>
        </div>
    </div>

    @include('helpdeskchat::components.alerts')

    <!-- Main Card -->
    <div class="card">
        <!-- Macros Table -->
        <div class="card-body">
            @if($macros->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="macrosTable">
                        <thead class="table-light">
                            <tr>
                                <th class="fw-semibold" style="width: 25%;">Name</th>
                                <th class="fw-semibold" style="width: 15%;">Visibility</th>
                                <th class="fw-semibold text-center" style="width: 15%;">Actions</th>
                                <th class="fw-semibold" style="width: 20%;">Created by</th>
                                <th class="fw-semibold text-center" style="width: 15%;">Options</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($macros as $macro)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-start flex-column">
                                            <strong>{{ $macro->name }}</strong>
                                            @if($macro->description)
                                                <small class="text-muted">{{ Str::limit($macro->description, 50) }}</small>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        @php
                                            $visibilityLabels = ['Personal', 'Global', 'Team'];
                                            $visibilityColors = ['secondary', 'success', 'info'];
                                        @endphp
                                        <span class="badge bg-{{ $visibilityColors[$macro->visibility] }}-subtle text-{{ $visibilityColors[$macro->visibility] }}">
                                            {{ $visibilityLabels[$macro->visibility] }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark">{{ count($macro->actions) }} {{ Str::plural('action', count($macro->actions)) }}</span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-start flex-column">
                                            <span>{{ $macro->createdBy->name ?? 'Unknown' }}</span>
                                            <small class="text-muted">{{ $macro->created_at->diffForHumans() }}</small>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="{{ route('admin.macros.show', $macro) }}"
                                               class="btn btn-outline-secondary"
                                               title="View macro">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('admin.macros.edit', $macro) }}"
                                               class="btn btn-outline-primary"
                                               title="Edit macro">
                                                <i class="fas fa-pen-to-square"></i>
                                            </a>
                                            <button type="button" class="btn btn-outline-danger delete-macro"
                                                    data-macro-id="{{ $macro->id }}"
                                                    data-macro-name="{{ $macro->name }}"
                                                    title="Delete macro">
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
                @if($macros->hasPages())
                    <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
                        <div class="text-muted small">
                            Showing <strong>{{ $macros->firstItem() }}</strong> to <strong>{{ $macros->lastItem() }}</strong>
                            of <strong>{{ $macros->total() }}</strong> macros
                        </div>
                        <nav aria-label="Page navigation">
                            {{ $macros->links() }}
                        </nav>
                    </div>
                @endif

            @else
                <div class="text-center py-5">
                    <div class="d-flex flex-column align-items-center">
                        <div class="rounded-circle bg-light p-4 mb-3 d-flex align-items-center justify-content-center">
                            <i class="fas fa-bolt fs-3 text-muted"></i>
                        </div>
                        <h6 class="mb-2 fw-semibold">No macros found</h6>
                        <p class="text-muted mb-3">
                            Create your first macro to automate repetitive actions
                        </p>
                        <a href="{{ route('admin.macros.create') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus me-2"></i>Create first macro
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteMacroModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold">Delete macro</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0">Are you sure you want to delete the macro <strong id="macroNameDisplay"></strong>? This action cannot be undone.</p>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteMacroForm" method="POST" class="d-inline">
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
    $('#macrosTable').DataTable({
        responsive: true,
        order: [[0, 'asc']],
        pageLength: 25,
        language: {
            search: "Search macros:",
            lengthMenu: "Show _MENU_ macros",
            info: "Showing _START_ to _END_ of _TOTAL_ macros",
            infoEmpty: "No macros available",
            infoFiltered: "(filtered from _MAX_ total macros)"
        }
    });

    // Show delete confirmation modal
    $('.delete-macro').on('click', function() {
        const macroId = $(this).data('macro-id');
        const macroName = $(this).data('macro-name');
        const deleteRoute = '{{ route("admin.macros.destroy", ":id") }}'.replace(':id', macroId);

        $('#macroNameDisplay').text(macroName);
        $('#deleteMacroForm').attr('action', deleteRoute);
        $('#deleteMacroModal').modal('show');
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
