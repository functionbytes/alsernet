@extends('layouts.admin')

@section('title', 'Create macro')

@section('content')

<div class="container-fluid">
    <!-- Header Section -->
    <div class="row mb-4 align-items-center">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-1 fw-bold">Create macro</h4>
                    <p class="text-muted small mb-0">Define automated actions and conditions for conversations</p>
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

    <form id="macroForm" method="POST" action="{{ route('admin.macros.store') }}" novalidate>
        @csrf
        <input type="hidden" name="actions" id="actions-json">
        <input type="hidden" name="conditions" id="conditions-json">

        <div class="row">
            <!-- Main Content -->
            <div class="col-12 col-lg-8">
                <!-- Basic Information -->
                <div class="card mb-3">
                    <div class="card-header border-bottom">
                        <h5 class="mb-0 fw-bold">Basic information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-4">
                            <div class="col-12 col-md-6">
                                <div class="form-group">
                                    <label class="form-label">
                                        Macro name
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" name="name" class="form-control" value="{{ old('name') }}" placeholder="e.g., Auto-assign urgent tickets">
                                    @error('name')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted d-block mt-1">Descriptive name for this macro</small>
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <div class="form-group">
                                    <label class="form-label">
                                        Visibility
                                        <span class="text-danger">*</span>
                                    </label>
                                    <select name="visibility" class="form-select">
                                        <option value="{{ $visibilityOptions['global'] }}" {{ old('visibility') == $visibilityOptions['global'] ? 'selected' : '' }}>
                                            Global (Everyone)
                                        </option>
                                        <option value="{{ $visibilityOptions['personal'] }}" {{ old('visibility') == $visibilityOptions['personal'] ? 'selected' : '' }}>
                                            Personal (Only me)
                                        </option>
                                        <option value="{{ $visibilityOptions['team'] }}" {{ old('visibility') == $visibilityOptions['team'] ? 'selected' : '' }}>
                                            Team
                                        </option>
                                    </select>
                                    @error('visibility')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="form-group">
                                    <label class="form-label">Description</label>
                                    <textarea name="description" class="form-control" rows="3" placeholder="Optional description of what this macro does">{{ old('description') }}</textarea>
                                    @error('description')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted d-block mt-1">Help other users understand the purpose of this macro</small>
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <div class="form-check form-switch">
                                    <input type="hidden" name="enabled" value="0">
                                    <input type="checkbox" name="enabled" class="form-check-input" id="enabledCheck" value="1" {{ old('enabled', true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="enabledCheck">
                                        <strong>Macro is enabled</strong>
                                    </label>
                                    <small class="text-muted d-block mt-1">When enabled, this macro can be executed</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Conditions -->
                <div class="card mb-3">
                    <div class="card-header border-bottom d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold">Conditions (Optional)</h5>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="add-condition">
                            <i class="fas fa-plus me-1"></i>Add condition
                        </button>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-3">Define when this macro should execute. Leave empty to always execute.</p>

                        <div id="conditions-container"></div>

                        <div id="no-conditions-msg" class="text-center py-3 text-muted">
                            <i class="fas fa-info-circle me-1"></i>No conditions set - macro will always execute
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="card mb-3">
                    <div class="card-header border-bottom d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold">
                            Actions
                            <span class="text-danger">*</span>
                        </h5>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="add-action">
                            <i class="fas fa-plus me-1"></i>Add action
                        </button>
                    </div>
                    <div class="card-body">
                        <div id="actions-container"></div>
                        <div id="no-actions-msg" class="text-danger text-center py-3">
                            <i class="fas fa-exclamation-circle me-1"></i>At least one action is required
                        </div>
                    </div>
                </div>

                <!-- Submit Buttons -->
                <div class="d-flex gap-2 mb-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Create macro
                    </button>
                    <a href="{{ route('admin.macros.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-12 col-lg-4">
                <div class="card mb-3 sticky-top" style="top: 20px;">
                    <div class="card-header border-bottom">
                        <h6 class="mb-0 fw-semibold">
                            <i class="fas fa-question-circle me-1"></i>How it works
                        </h6>
                    </div>
                    <div class="card-body">
                        <small class="text-muted">
                            <strong>1. Conditions:</strong> Define when macro executes<br>
                            <strong>2. Actions:</strong> What happens when executed<br>
                            <strong>3. Order:</strong> Actions run in sequence<br><br>
                            <strong>Example:</strong><br>
                            IF status = open AND priority = urgent<br>
                            THEN assign to team + send message
                        </small>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header border-bottom">
                        <h6 class="mb-0 fw-semibold">Available fields</h6>
                    </div>
                    <div class="card-body">
                        <small class="text-muted">
                            <strong>Conversation:</strong> status, priority, assignee, team, labels<br>
                            <strong>Metrics:</strong> message count, waiting since, created hours ago<br>
                            <strong>Contact:</strong> email, phone, name
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

@php
    $users = auth()->user()->account->users;
    $teams = auth()->user()->account->teams;
    $labels = auth()->user()->account->labels;
@endphp

@endsection

@push('scripts')
<script>
const dropdownData = {
    users: @json($users->map(fn($u) => ['id' => $u->id, 'name' => $u->name])),
    teams: @json($teams->map(fn($t) => ['id' => $t->id, 'name' => $t->name])),
    labels: @json($labels->map(fn($l) => ['title' => $l->title]))
};

$(document).ready(function() {
    let actionIndex = 0;
    let conditionIndex = 0;

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

    // Field definitions for conditions
    const conditionFields = {
        'status': { label: 'Status', type: 'select', options: ['open', 'pending', 'resolved'] },
        'priority': { label: 'Priority', type: 'select', options: ['low', 'medium', 'high', 'urgent'] },
        'assignee_id': { label: 'Assignee', type: 'select', options: 'users' },
        'team_id': { label: 'Team', type: 'select', options: 'teams' },
        'labels': { label: 'Labels', type: 'text' },
        'message_count': { label: 'Message count', type: 'number' },
        'waiting_since': { label: 'Waiting (minutes)', type: 'number' },
        'created_hours_ago': { label: 'Age (hours)', type: 'number' },
        'contact_email': { label: 'Contact email', type: 'text' },
    };

    const operators = {
        'equals': 'Equals',
        'not_equals': 'Not equals',
        'contains': 'Contains',
        'greater_than': 'Greater than',
        'less_than': 'Less than',
        'is_empty': 'Is empty',
        'is_not_empty': 'Is not empty',
    };

    // === CONDITIONS ===
    function addCondition() {
        const index = conditionIndex++;
        const html = `
            <div class="card mb-2 condition-item" data-index="${index}">
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-12 col-md-3">
                            <select class="form-select form-select-sm condition-field" data-index="${index}">
                                <option value="">Select field...</option>
                                ${Object.entries(conditionFields).map(([key, field]) =>
                                    `<option value="${key}">${field.label}</option>`
                                ).join('')}
                            </select>
                        </div>
                        <div class="col-12 col-md-3">
                            <select class="form-select form-select-sm condition-operator" data-index="${index}">
                                <option value="">Operator...</option>
                                ${Object.entries(operators).map(([key, label]) =>
                                    `<option value="${key}">${label}</option>`
                                ).join('')}
                            </select>
                        </div>
                        <div class="col-12 col-md-5">
                            <div class="condition-value-container" data-index="${index}">
                                <input type="text" class="form-control form-control-sm" placeholder="Value" disabled>
                            </div>
                        </div>
                        <div class="col-12 col-md-1">
                            <button type="button" class="btn btn-sm btn-danger remove-condition w-100" data-index="${index}">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        $('#conditions-container').append(html);
        updateConditionsVisibility();
    }

    function updateConditionValue(index) {
        const $item = $(`.condition-item[data-index="${index}"]`);
        const field = $item.find('.condition-field').val();
        const $container = $item.find('.condition-value-container');

        if (!field) {
            $container.html('<input type="text" class="form-control form-control-sm" placeholder="Value" disabled>');
            return;
        }

        const fieldDef = conditionFields[field];
        let html = '';

        if (fieldDef.type === 'select') {
            const options = fieldDef.options === 'users' ? dropdownData.users :
                          fieldDef.options === 'teams' ? dropdownData.teams :
                          fieldDef.options;

            html = `<select class="form-select form-select-sm condition-value" data-index="${index}">
                <option value="">Select...</option>`;

            if (Array.isArray(options) && typeof options[0] === 'object') {
                options.forEach(opt => {
                    html += `<option value="${opt.id}">${opt.name}</option>`;
                });
            } else {
                options.forEach(opt => {
                    html += `<option value="${opt}">${opt}</option>`;
                });
            }

            html += '</select>';
        } else if (fieldDef.type === 'number') {
            html = `<input type="number" class="form-control form-control-sm condition-value" data-index="${index}">`;
        } else {
            html = `<input type="text" class="form-control form-control-sm condition-value" data-index="${index}">`;
        }

        $container.html(html);
    }

    function updateConditionsVisibility() {
        const hasConditions = $('.condition-item').length > 0;
        $('#no-conditions-msg').toggle(!hasConditions);
    }

    // === ACTIONS ===
    function addAction() {
        const index = actionIndex++;
        const html = `
            <div class="card mb-2 action-item" data-index="${index}">
                <div class="card-body">
                    <div class="row g-2 align-items-start">
                        <div class="col-12 col-md-4">
                            <select class="form-select form-select-sm action-type" data-index="${index}" required>
                                <option value="">Select action...</option>
                                <option value="assign_agent">Assign to agent</option>
                                <option value="assign_team">Assign to team</option>
                                <option value="add_label">Add label</option>
                                <option value="remove_label">Remove label</option>
                                <option value="change_status">Change status</option>
                                <option value="change_priority">Change priority</option>
                                <option value="send_message">Send message</option>
                                <option value="add_private_note">Add private note</option>
                                <option value="resolve_conversation">Resolve</option>
                                <option value="reopen_conversation">Reopen</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-7">
                            <div class="action-value-container" data-index="${index}"></div>
                        </div>
                        <div class="col-12 col-md-1">
                            <button type="button" class="btn btn-sm btn-danger remove-action w-100" data-index="${index}">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        $('#actions-container').append(html);
        updateActionsVisibility();
    }

    function updateActionValue(index) {
        const $item = $(`.action-item[data-index="${index}"]`);
        const actionType = $item.find('.action-type').val();
        const $container = $item.find('.action-value-container');

        let html = '';

        switch(actionType) {
            case 'assign_agent':
                html = `<select class="form-select form-select-sm action-value" data-index="${index}" required>
                    <option value="">Select agent...</option>
                    ${dropdownData.users.map(u => `<option value="${u.id}">${u.name}</option>`).join('')}
                </select>`;
                break;

            case 'assign_team':
                html = `<select class="form-select form-select-sm action-value" data-index="${index}" required>
                    <option value="">Select team...</option>
                    ${dropdownData.teams.map(t => `<option value="${t.id}">${t.name}</option>`).join('')}
                </select>`;
                break;

            case 'add_label':
            case 'remove_label':
                html = `<select class="form-select form-select-sm action-value" data-index="${index}" required>
                    <option value="">Select label...</option>
                    ${dropdownData.labels.map(l => `<option value="${l.title}">${l.title}</option>`).join('')}
                </select>`;
                break;

            case 'change_status':
                html = `<select class="form-select form-select-sm action-value" data-index="${index}" required>
                    <option value="">Select status...</option>
                    <option value="open">Open</option>
                    <option value="pending">Pending</option>
                    <option value="resolved">Resolved</option>
                </select>`;
                break;

            case 'change_priority':
                html = `<select class="form-select form-select-sm action-value" data-index="${index}" required>
                    <option value="">Select priority...</option>
                    <option value="low">Low</option>
                    <option value="medium">Medium</option>
                    <option value="high">High</option>
                    <option value="urgent">Urgent</option>
                </select>`;
                break;

            case 'send_message':
            case 'add_private_note':
                html = `<textarea class="form-control form-control-sm action-value" data-index="${index}" rows="2" required placeholder="Enter message..."></textarea>`;
                break;

            case 'resolve_conversation':
            case 'reopen_conversation':
                html = `<input type="hidden" class="action-value" data-index="${index}" value="">
                        <small class="text-muted">No value required</small>`;
                break;

            default:
                html = '<input type="text" class="form-control form-control-sm" placeholder="Select action first" disabled>';
        }

        $container.html(html);
    }

    function updateActionsVisibility() {
        const hasActions = $('.action-item').length > 0;
        $('#no-actions-msg').toggle(!hasActions);
    }

    // === SERIALIZATION ===
    function serializeConditions() {
        const conditionsList = [];

        $('.condition-item').each(function() {
            const $item = $(this);
            const index = $item.data('index');
            const field = $item.find('.condition-field').val();
            const operator = $item.find('.condition-operator').val();
            const value = $item.find('.condition-value').val();

            if (field && operator) {
                conditionsList.push({ field, operator, value });
            }
        });

        return conditionsList.length > 0 ? { operator: 'and', conditions: conditionsList } : null;
    }

    function serializeActions() {
        const actionsList = [];

        $('.action-item').each(function() {
            const $item = $(this);
            const index = $item.data('index');
            const action = $item.find('.action-type').val();
            const value = $item.find('.action-value').val();

            if (action) {
                actionsList.push({ action, value: value || '' });
            }
        });

        return actionsList;
    }

    // === EVENT HANDLERS ===
    $('#add-condition').on('click', function() {
        addCondition();
    });

    $('#add-action').on('click', function() {
        addAction();
    });

    $(document).on('change', '.condition-field, .condition-operator', function() {
        const index = $(this).data('index');
        updateConditionValue(index);
    });

    $(document).on('change', '.action-type', function() {
        const index = $(this).data('index');
        updateActionValue(index);
    });

    $(document).on('click', '.remove-condition', function() {
        $(this).closest('.condition-item').remove();
        updateConditionsVisibility();
    });

    $(document).on('click', '.remove-action', function() {
        $(this).closest('.action-item').remove();
        updateActionsVisibility();
    });

    // Form submission
    $('#macroForm').on('submit', function(e) {
        const actions = serializeActions();
        const conditions = serializeConditions();

        if (actions.length === 0) {
            e.preventDefault();
            toastr.error('Please add at least one action', 'Validation error');
            return false;
        }

        $('#actions-json').val(JSON.stringify(actions));
        $('#conditions-json').val(conditions ? JSON.stringify(conditions) : '');
    });

    // Initialize with one action
    addAction();

    // Toast messages
    @if (session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif

    @if (session('success'))
        toastr.success('{{ session('success') }}', 'Success');
    @endif
});
</script>
@endpush
