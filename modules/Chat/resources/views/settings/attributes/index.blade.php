@extends('layouts.theme')

@section('title', 'Custom attributes')

@section('content')

<div class="container-fluid">
    <!-- Header Section -->
    <div class="row mb-4 align-items-center">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-1 fw-bold">Custom attributes</h4>
                    <p class="text-muted small mb-0">Define additional fields for conversations, contacts, and tickets</p>
                </div>
                <div>
                    <a href="{{ route('manager.chat.settings.tickets.attributes.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Create attribute
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
                <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold mb-1">Search attributes</label>
                    <form method="GET" action="{{ route('manager.chat.settings.tickets.attributes.index') }}" class="d-flex gap-2">
                        <div class="input-group">
                            <span class="input-group-text bg-white">
                                <i class="fas fa-search"></i>
                            </span>
                            <input type="search" name="search" class="form-control" placeholder="Search by name or key..." value="{{ request('search') }}">
                        </div>
                        <button type="submit" class="btn btn-primary">
                            Search
                        </button>
                    </form>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label fw-semibold mb-1">Format</label>
                    <select name="format" class="form-control select2" id="formatSelect">
                        <option value="">All formats</option>
                        <option value="text" {{ request('format') === 'text' ? 'selected' : '' }}>Text</option>
                        <option value="textarea" {{ request('format') === 'textarea' ? 'selected' : '' }}>Text area</option>
                        <option value="number" {{ request('format') === 'number' ? 'selected' : '' }}>Number</option>
                        <option value="switch" {{ request('format') === 'switch' ? 'selected' : '' }}>Switch</option>
                        <option value="rating" {{ request('format') === 'rating' ? 'selected' : '' }}>Rating</option>
                        <option value="select" {{ request('format') === 'select' ? 'selected' : '' }}>Select</option>
                        <option value="checkboxGroup" {{ request('format') === 'checkboxGroup' ? 'selected' : '' }}>Checkbox group</option>
                        <option value="date" {{ request('format') === 'date' ? 'selected' : '' }}>Date</option>
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label fw-semibold mb-1">Permission</label>
                    <select name="permission" class="form-control select2" id="permissionSelect">
                        <option value="">All permissions</option>
                        <option value="userCanView" {{ request('permission') === 'userCanView' ? 'selected' : '' }}>User can view</option>
                        <option value="userCanEdit" {{ request('permission') === 'userCanEdit' ? 'selected' : '' }}>User can edit</option>
                        <option value="agentCanEdit" {{ request('permission') === 'agentCanEdit' ? 'selected' : '' }}>Agent can edit</option>
                    </select>
                </div>
                <div class="col-12 col-md-2 text-end">
                    @if(request('search') || request('format') || request('permission'))
                        <a href="{{ route('manager.chat.settings.tickets.attributes.index') }}" class="btn btn-secondary btn-sm">
                            Clear filters
                        </a>
                    @endif
                </div>
            </div>
        </div>

        <!-- Attributes Table -->
        <div class="card-body">
            @if($attributes->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="attributesTable">
                        <thead class="table-light">
                            <tr>
                                <th class="fw-semibold" style="width: 20%;">Name</th>
                                <th class="fw-semibold" style="width: 15%;">Key</th>
                                <th class="fw-semibold" style="width: 15%;">Format</th>
                                <th class="fw-semibold" style="width: 15%;">Permission</th>
                                <th class="fw-semibold text-center" style="width: 10%;">Required</th>
                                <th class="fw-semibold text-center" style="width: 10%;">Status</th>
                                <th class="fw-semibold text-center" style="width: 15%;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($attributes as $attribute)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-start flex-column">
                                            <strong>{{ $attribute->name }}</strong>
                                            @if($attribute->description)
                                                <small class="text-muted">{{ Str::limit($attribute->description, 50) }}</small>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <code class="bg-light px-2 py-1 rounded text-secondary">{{ $attribute->key }}</code>
                                    </td>
                                    <td>
                                        @php
                                            $formatLabels = [
                                                'text' => ['label' => 'Text', 'icon' => 'fa-text', 'color' => 'primary'],
                                                'textarea' => ['label' => 'Text area', 'icon' => 'fa-rectangle-list', 'color' => 'info'],
                                                'number' => ['label' => 'Number', 'icon' => 'fa-hashtag', 'color' => 'success'],
                                                'switch' => ['label' => 'Switch', 'icon' => 'fa-toggle-on', 'color' => 'warning'],
                                                'rating' => ['label' => 'Rating', 'icon' => 'fa-star', 'color' => 'danger'],
                                                'select' => ['label' => 'Select', 'icon' => 'fa-list', 'color' => 'secondary'],
                                                'checkboxGroup' => ['label' => 'Checkboxes', 'icon' => 'fa-square-check', 'color' => 'dark'],
                                                'date' => ['label' => 'Date', 'icon' => 'fa-calendar', 'color' => 'purple']
                                            ];
                                            $format = $formatLabels[$attribute->format] ?? ['label' => $attribute->format, 'icon' => 'fa-question-circle', 'color' => 'muted'];
                                        @endphp
                                        <span class="badge bg-{{ $format['color'] }}-subtle text-{{ $format['color'] }}">
                                            <i class="fas {{ $format['icon'] }}"></i> {{ $format['label'] }}
                                        </span>
                                    </td>
                                    <td>
                                        @php
                                            $permissionLabels = [
                                                'userCanView' => ['label' => 'User view', 'color' => 'info'],
                                                'userCanEdit' => ['label' => 'User edit', 'color' => 'success'],
                                                'agentCanEdit' => ['label' => 'Agent edit', 'color' => 'primary']
                                            ];
                                            $perm = $permissionLabels[$attribute->permission] ?? ['label' => $attribute->permission, 'color' => 'muted'];
                                        @endphp
                                        <span class="badge bg-{{ $perm['color'] }}-subtle text-{{ $perm['color'] }}">
                                            {{ $perm['label'] }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        @if($attribute->required)
                                            <span class="badge bg-light text-dark">Yes</span>
                                        @else
                                            <span class="text-muted">No</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <form method="POST" action="{{ route('manager.chat.settings.tickets.attributes.toggle', $attribute->id) }}" class="toggle-form">
                                            @csrf
                                            @method('PATCH')
                                            <div class="form-check form-switch d-inline-block">
                                                <input type="checkbox" class="form-check-input toggle-checkbox" role="switch"
                                                       {{ $attribute->active ? 'checked' : '' }}
                                                       onchange="this.form.submit()">
                                            </div>
                                        </form>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="{{ route('manager.chat.settings.tickets.attributes.edit', $attribute->id) }}"
                                               class="btn btn-outline-primary"
                                               title="Edit attribute">
                                                <i class="fas fa-pen-to-square"></i>
                                            </a>
                                            <button type="button" class="btn btn-outline-danger delete-attribute"
                                                    data-attribute-id="{{ $attribute->id }}"
                                                    data-attribute-name="{{ $attribute->name }}"
                                                    title="Delete attribute">
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
                @if($attributes->hasPages())
                    <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
                        <div class="text-muted small">
                            Showing <strong>{{ $attributes->firstItem() }}</strong> to <strong>{{ $attributes->lastItem() }}</strong>
                            of <strong>{{ $attributes->total() }}</strong> attributes
                        </div>
                        <nav aria-label="Page navigation">
                            {{ $attributes->links() }}
                        </nav>
                    </div>
                @endif

            @else
                <div class="text-center py-5">
                    <div class="d-flex flex-column align-items-center">
                        <div class="rounded-circle bg-light p-4 mb-3 d-flex align-items-center justify-content-center">
                            <i class="fas fa-cog fs-3 text-muted"></i>
                        </div>
                        <h6 class="mb-2 fw-semibold">No attributes found</h6>
                        <p class="text-muted mb-3">
                            @if(request('search') || request('format') || request('permission'))
                                No results found with the current filters
                            @else
                                Create your first custom attribute to extend functionality
                            @endif
                        </p>
                        @if(!request('search') && !request('format') && !request('permission'))
                            <a href="{{ route('manager.chat.settings.tickets.attributes.create') }}" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus me-2"></i>Create first attribute
                            </a>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteAttributeModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold">Delete attribute</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0">Are you sure you want to delete the attribute <strong id="attributeNameDisplay"></strong>? This action cannot be undone.</p>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteAttributeForm" method="POST" class="d-inline">
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
    $('.delete-attribute').on('click', function() {
        const attributeId = $(this).data('attribute-id');
        const attributeName = $(this).data('attribute-name');
        const deleteRoute = '{{ route("manager.chat.settings.tickets.attributes.destroy", ":id") }}'.replace(':id', attributeId);

        $('#attributeNameDisplay').text(attributeName);
        $('#deleteAttributeForm').attr('action', deleteRoute);
        $('#deleteAttributeModal').modal('show');
    });

    // Auto-submit form on select change
    $('#formatSelect, #permissionSelect').on('change', function() {
        const form = $(this).closest('form').length ? $(this).closest('form') : $('form[method="GET"]');

        // Add the changed field to the form
        const fieldName = $(this).attr('name');
        const fieldValue = $(this).val();

        // Create hidden input if not in form
        if (!form.find('[name="' + fieldName + '"]').length) {
            form.append('<input type="hidden" name="' + fieldName + '" value="' + fieldValue + '">');
        } else {
            form.find('[name="' + fieldName + '"]').val(fieldValue);
        }

        form.submit();
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
