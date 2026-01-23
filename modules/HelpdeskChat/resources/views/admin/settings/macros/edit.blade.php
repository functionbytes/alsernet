@extends('layouts.admin')

@section('title', 'Edit macro')

@section('content')

<div class="container-fluid">
    <!-- Header Section -->
    <div class="row mb-4 align-items-center">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-1 fw-bold">Edit macro</h4>
                    <p class="text-muted small mb-0">Update automated actions and conditions for {{ $macro->name }}</p>
                </div>
                <div>
                    <a href="{{ route('admin.macros.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back
                    </a>
                </div>
            </div>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show">
            <strong>Validation error:</strong>
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <!-- Main Content -->
        <div class="col-12 col-lg-8">
            <!-- Main Card -->
            <div class="card mb-3">
                <div class="card-header border-bottom">
                    <h5 class="mb-0 fw-bold">Macro information</h5>
                </div>

                <form id="macroForm" method="POST" action="{{ route('admin.macros.update', $macro) }}" novalidate>
                    @csrf
                    @method('PUT')

                    <div class="card-body">
                        <div class="row g-4">
                            <!-- Name -->
                            <div class="col-12 col-md-6">
                                <div class="form-group">
                                    <label class="form-label">
                                        Macro name
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" name="name" class="form-control" value="{{ old('name', $macro->name) }}" placeholder="e.g., Auto-assign urgent tickets">
                                    @error('name')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted d-block mt-1">Descriptive name for this macro</small>
                                </div>
                            </div>

                            <!-- Visibility -->
                            <div class="col-12 col-md-6">
                                <div class="form-group">
                                    <label class="form-label">
                                        Visibility
                                        <span class="text-danger">*</span>
                                    </label>
                                    <select name="visibility" class="form-select">
                                        <option value="{{ $visibilityOptions['personal'] }}" {{ old('visibility', $macro->visibility) == $visibilityOptions['personal'] ? 'selected' : '' }}>
                                            Personal (Only me)
                                        </option>
                                        <option value="{{ $visibilityOptions['global'] }}" {{ old('visibility', $macro->visibility) == $visibilityOptions['global'] ? 'selected' : '' }}>
                                            Global (Everyone)
                                        </option>
                                        <option value="{{ $visibilityOptions['team'] }}" {{ old('visibility', $macro->visibility) == $visibilityOptions['team'] ? 'selected' : '' }}>
                                            Team
                                        </option>
                                    </select>
                                    @error('visibility')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Description -->
                            <div class="col-12">
                                <div class="form-group">
                                    <label class="form-label">Description</label>
                                    <textarea name="description" class="form-control" rows="3" placeholder="Optional description of what this macro does">{{ old('description', $macro->description) }}</textarea>
                                    @error('description')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted d-block mt-1">Help other users understand the purpose of this macro</small>
                                </div>
                            </div>

                            <!-- Enabled Switch -->
                            <div class="col-12 col-md-6">
                                <div class="form-check form-switch">
                                    <input type="hidden" name="enabled" value="0">
                                    <input type="checkbox" name="enabled" class="form-check-input" id="enabledCheck" value="1" {{ old('enabled', $macro->enabled) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="enabledCheck">
                                        <strong>Macro is enabled</strong>
                                    </label>
                                    <small class="text-muted d-block mt-1">When enabled, this macro can be executed</small>
                                </div>
                            </div>

                            <!-- Divider -->
                            <div class="col-12">
                                <hr class="my-2">
                            </div>

                            <!-- Actions Section -->
                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <label class="form-label mb-0">
                                        Actions
                                        <span class="text-danger">*</span>
                                    </label>
                                    <button type="button" class="btn btn-sm btn-outline-primary" id="add-action">
                                        <i class="fas fa-plus me-1"></i>Add action
                                    </button>
                                </div>

                                <div id="actions-container">
                                    @foreach(old('actions', $macro->actions ?? []) as $index => $action)
                                        <div class="card mb-2 action-item" data-index="{{ $index }}">
                                            <div class="card-body">
                                                <div class="row align-items-start g-2">
                                                    <div class="col-12 col-md-4">
                                                        <label class="form-label small">Action type</label>
                                                        <select class="form-select form-select-sm action-type"
                                                                name="actions[{{ $index }}][action]" required>
                                                            <option value="">Select action...</option>
                                                            <option value="assign_agent" {{ ($action['action'] ?? '') == 'assign_agent' ? 'selected' : '' }}>Assign to agent</option>
                                                            <option value="assign_team" {{ ($action['action'] ?? '') == 'assign_team' ? 'selected' : '' }}>Assign to team</option>
                                                            <option value="add_label" {{ ($action['action'] ?? '') == 'add_label' ? 'selected' : '' }}>Add label</option>
                                                            <option value="remove_label" {{ ($action['action'] ?? '') == 'remove_label' ? 'selected' : '' }}>Remove label</option>
                                                            <option value="change_status" {{ ($action['action'] ?? '') == 'change_status' ? 'selected' : '' }}>Change status</option>
                                                            <option value="change_priority" {{ ($action['action'] ?? '') == 'change_priority' ? 'selected' : '' }}>Change priority</option>
                                                            <option value="send_message" {{ ($action['action'] ?? '') == 'send_message' ? 'selected' : '' }}>Send message</option>
                                                            <option value="add_private_note" {{ ($action['action'] ?? '') == 'add_private_note' ? 'selected' : '' }}>Add private note</option>
                                                            <option value="resolve_conversation" {{ ($action['action'] ?? '') == 'resolve_conversation' ? 'selected' : '' }}>Resolve conversation</option>
                                                            <option value="reopen_conversation" {{ ($action['action'] ?? '') == 'reopen_conversation' ? 'selected' : '' }}>Reopen conversation</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-12 col-md-7">
                                                        <label class="form-label small">Value</label>
                                                        <div class="action-value-container"></div>
                                                    </div>
                                                    <div class="col-12 col-md-1">
                                                        <label class="form-label small">&nbsp;</label>
                                                        <button type="button" class="btn btn-sm btn-danger remove-action d-block w-100">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                @error('actions')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="card-footer bg-light border-top d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Update macro
                        </button>
                        <a href="{{ route('admin.macros.index') }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-12 col-lg-4">
            <div class="card">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-semibold">How macros work</h6>
                </div>
                <div class="card-body">
                    <small class="text-muted">
                        Macros allow you to automate repetitive actions on conversations.
                        Select multiple actions to execute them in sequence when you run the macro.
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

@php
    $users = auth()->user()->account->users;
    $teams = auth()->user()->account->teams;
    $labels = auth()->user()->account->labels;
    $existingActions = old('actions', $macro->actions ?? []);
@endphp

@endsection

@push('scripts')
<script>
const dropdownData = {
    users: @json($users->map(fn($u) => ['id' => $u->id, 'name' => $u->name])),
    teams: @json($teams->map(fn($t) => ['id' => $t->id, 'name' => $t->name])),
    labels: @json($labels->map(fn($l) => ['id' => $l->title, 'name' => $l->title]))
};

const existingActions = @json($existingActions);

$(document).ready(function() {
    let actionIndex = {{ count($macro->actions ?? []) }};

    // Initialize form validation
    $('#macroForm').validate({
        rules: {
            name: {
                required: true,
                minlength: 2,
                maxlength: 100
            },
            visibility: {
                required: true
            },
            description: {
                maxlength: 500
            }
        },
        messages: {
            name: {
                required: 'Macro name is required',
                minlength: 'Macro name must be at least 2 characters',
                maxlength: 'Macro name cannot exceed 100 characters'
            },
            visibility: {
                required: 'Visibility is required'
            },
            description: {
                maxlength: 'Description cannot exceed 500 characters'
            }
        },
        errorElement: 'div',
        errorClass: 'invalid-feedback d-block',
        highlight: function(element) {
            $(element).addClass('is-invalid');
        },
        unhighlight: function(element) {
            $(element).removeClass('is-invalid');
        }
    });

    function createValueInput(actionType, index, currentValue = '') {
        let html = '';

        switch(actionType) {
            case 'assign_agent':
                html = `<select class="form-select form-select-sm" name="actions[${index}][value]" required>
                    <option value="">Select agent...</option>
                    ${dropdownData.users.map(u => `<option value="${u.id}" ${currentValue == u.id ? 'selected' : ''}>${u.name}</option>`).join('')}
                </select>`;
                break;

            case 'assign_team':
                html = `<select class="form-select form-select-sm" name="actions[${index}][value]" required>
                    <option value="">Select team...</option>
                    ${dropdownData.teams.map(t => `<option value="${t.id}" ${currentValue == t.id ? 'selected' : ''}>${t.name}</option>`).join('')}
                </select>`;
                break;

            case 'add_label':
            case 'remove_label':
                html = `<select class="form-select form-select-sm" name="actions[${index}][value]" required>
                    <option value="">Select label...</option>
                    ${dropdownData.labels.map(l => `<option value="${l.id}" ${currentValue == l.id ? 'selected' : ''}>${l.name}</option>`).join('')}
                </select>`;
                break;

            case 'change_status':
                html = `<select class="form-select form-select-sm" name="actions[${index}][value]" required>
                    <option value="">Select status...</option>
                    <option value="open" ${currentValue == 'open' ? 'selected' : ''}>Open</option>
                    <option value="pending" ${currentValue == 'pending' ? 'selected' : ''}>Pending</option>
                    <option value="resolved" ${currentValue == 'resolved' ? 'selected' : ''}>Resolved</option>
                </select>`;
                break;

            case 'change_priority':
                html = `<select class="form-select form-select-sm" name="actions[${index}][value]" required>
                    <option value="">Select priority...</option>
                    <option value="low" ${currentValue == 'low' ? 'selected' : ''}>Low</option>
                    <option value="medium" ${currentValue == 'medium' ? 'selected' : ''}>Medium</option>
                    <option value="high" ${currentValue == 'high' ? 'selected' : ''}>High</option>
                    <option value="urgent" ${currentValue == 'urgent' ? 'selected' : ''}>Urgent</option>
                </select>`;
                break;

            case 'send_message':
            case 'add_private_note':
                html = `<textarea class="form-control form-control-sm" name="actions[${index}][value]" rows="3" required placeholder="Enter message...">${currentValue}</textarea>`;
                break;

            case 'resolve_conversation':
            case 'reopen_conversation':
                html = `<input type="hidden" name="actions[${index}][value]" value="">
                        <small class="text-muted">No value required for this action</small>`;
                break;

            default:
                html = `<input type="text" class="form-control form-control-sm" name="actions[${index}][value]" placeholder="Value" value="${currentValue}">`;
        }

        return html;
    }

    function updateValueField($actionItem) {
        const actionType = $actionItem.find('.action-type').val();
        const index = $actionItem.data('index');
        const $container = $actionItem.find('.action-value-container');

        // Get current value if exists
        const $currentValueElement = $container.find('[name*="[value]"]');
        const currentValue = $currentValueElement.length ? $currentValueElement.val() : '';

        $container.html(createValueInput(actionType, index, currentValue));
    }

    // Initialize existing actions
    $('.action-item').each(function(idx) {
        const $item = $(this);
        const actionType = existingActions[idx]?.action || '';
        const actionValue = existingActions[idx]?.value || '';

        if (actionType) {
            $item.find('.action-type').val(actionType);
            const $container = $item.find('.action-value-container');
            $container.html(createValueInput(actionType, idx, actionValue));
        }
    });

    // Add action
    $('#add-action').on('click', function() {
        const $container = $('#actions-container');
        const actionHtml = `
            <div class="card mb-2 action-item" data-index="${actionIndex}">
                <div class="card-body">
                    <div class="row align-items-start g-2">
                        <div class="col-12 col-md-4">
                            <label class="form-label small">Action type</label>
                            <select class="form-select form-select-sm action-type"
                                    name="actions[${actionIndex}][action]" required>
                                <option value="">Select action...</option>
                                <option value="assign_agent">Assign to agent</option>
                                <option value="assign_team">Assign to team</option>
                                <option value="add_label">Add label</option>
                                <option value="remove_label">Remove label</option>
                                <option value="change_status">Change status</option>
                                <option value="change_priority">Change priority</option>
                                <option value="send_message">Send message</option>
                                <option value="add_private_note">Add private note</option>
                                <option value="resolve_conversation">Resolve conversation</option>
                                <option value="reopen_conversation">Reopen conversation</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-7">
                            <label class="form-label small">Value</label>
                            <div class="action-value-container"></div>
                        </div>
                        <div class="col-12 col-md-1">
                            <label class="form-label small">&nbsp;</label>
                            <button type="button" class="btn btn-sm btn-danger remove-action d-block w-100">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        $container.append(actionHtml);
        actionIndex++;
    });

    // Handle action type change (event delegation)
    $(document).on('change', '#actions-container .action-type', function() {
        const $actionItem = $(this).closest('.action-item');
        updateValueField($actionItem);
    });

    // Remove action (event delegation)
    $(document).on('click', '#actions-container .remove-action', function() {
        $(this).closest('.action-item').remove();
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
