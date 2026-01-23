@extends('layouts.admin')

@section('title', 'Create Automation Rule')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col">
            <h1 class="h3">Create Automation Rule</h1>
        </div>
        <div class="col-auto">
            <a href="{{ route('admin.automation-rules.index') }}" class="btn btn-secondary">
                <i class="fa fa-arrow-left me-1"></i> Back to Automation Rules
            </a>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show">
            <strong>Validation Error:</strong>
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('admin.automation-rules.store') }}" method="POST" id="automation-form">
                        @csrf

                        <!-- Name -->
                        <div class="mb-3">
                            <label for="name" class="form-label">Rule Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                   id="name" name="name" value="{{ old('name') }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Description -->
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror"
                                      id="description" name="description" rows="2">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Event -->
                        <div class="mb-3">
                            <label for="event_name" class="form-label">Trigger Event <span class="text-danger">*</span></label>
                            <select class="form-select @error('event_name') is-invalid @enderror"
                                    id="event_name" name="event_name" required>
                                <option value="">Select event...</option>
                                <option value="conversation_created" {{ old('event_name') == 'conversation_created' ? 'selected' : '' }}>Conversation Created</option>
                                <option value="conversation_updated" {{ old('event_name') == 'conversation_updated' ? 'selected' : '' }}>Conversation Updated</option>
                                <option value="conversation_status_changed" {{ old('event_name') == 'conversation_status_changed' ? 'selected' : '' }}>Conversation Status Changed</option>
                                <option value="message_created" {{ old('event_name') == 'message_created' ? 'selected' : '' }}>Message Created</option>
                            </select>
                            @error('event_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Conditions -->
                        <div class="mb-3">
                            <label class="form-label">Conditions <span class="text-danger">*</span></label>
                            <div id="conditions-container">
                                <!-- Conditions will be added here dynamically -->
                            </div>

                            <button type="button" class="btn btn-sm btn-outline-primary" id="add-condition">
                                <i class="fa fa-plus-circle me-1"></i> Add Condition
                            </button>

                            @error('conditions')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Actions -->
                        <div class="mb-3">
                            <label class="form-label">Actions <span class="text-danger">*</span></label>
                            <div id="actions-container">
                                <!-- Actions will be added here dynamically -->
                            </div>

                            <button type="button" class="btn btn-sm btn-outline-primary" id="add-action">
                                <i class="fa fa-plus-circle me-1"></i> Add Action
                            </button>

                            @error('actions')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Active -->
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="active" name="active" value="1" {{ old('active', true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="active">
                                Active (rule will execute when triggered)
                            </label>
                        </div>

                        <!-- Submit -->
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-save me-1"></i> Create Rule
                            </button>
                            <a href="{{ route('admin.automation-rules.index') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header bg-light">
                    <h6 class="mb-0">How Automation Rules Work</h6>
                </div>
                <div class="card-body">
                    <small class="text-muted">
                        Automation rules automatically execute actions when specific events occur and conditions are met.
                        They help automate repetitive workflows.
                    </small>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header bg-light">
                    <h6 class="mb-0">Example Rules</h6>
                </div>
                <div class="card-body">
                    <small>
                        <strong>Auto-assign new conversations:</strong><br>
                        Event: Conversation Created<br>
                        Condition: Status = open<br>
                        Action: Assign to Team → Support<br><br>

                        <strong>Escalate urgent messages:</strong><br>
                        Event: Message Created<br>
                        Condition: Contains word "urgent"<br>
                        Action: Change Priority → urgent
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

@php
    // Get data for dropdowns
    $users = auth()->user()->account->users;
    $teams = auth()->user()->account->teams;
    $labels = auth()->user()->account->labels;
@endphp

<script>
// Dropdown data from server
const dropdownData = {
    users: @json($users->map(fn($u) => ['id' => $u->id, 'name' => $u->name])),
    teams: @json($teams->map(fn($t) => ['id' => $t->id, 'name' => $t->name])),
    labels: @json($labels->map(fn($l) => ['id' => $l->title, 'name' => $l->title]))
};

20 20 12 61 79 80 81 98 33 100 204 250 395 398 399 400 701function() {
    let conditionIndex = 0;
    let actionIndex = 0;

    function createConditionValueInput(conditionType, index, currentValue = '') {
        let html = '';

        switch(conditionType) {
            case 'status':
                html = `<select class="form-select form-select-sm" name="conditions[${index}][value]" required>
                    <option value="">Select status...</option>
                    <option value="open" ${currentValue == 'open' ? 'selected' : ''}>Open</option>
                    <option value="pending" ${currentValue == 'pending' ? 'selected' : ''}>Pending</option>
                    <option value="resolved" ${currentValue == 'resolved' ? 'selected' : ''}>Resolved</option>
                </select>`;
                break;

            case 'priority':
                html = `<select class="form-select form-select-sm" name="conditions[${index}][value]" required>
                    <option value="">Select priority...</option>
                    <option value="low" ${currentValue == 'low' ? 'selected' : ''}>Low</option>
                    <option value="medium" ${currentValue == 'medium' ? 'selected' : ''}>Medium</option>
                    <option value="high" ${currentValue == 'high' ? 'selected' : ''}>High</option>
                    <option value="urgent" ${currentValue == 'urgent' ? 'selected' : ''}>Urgent</option>
                </select>`;
                break;

            case 'assignee_id':
                html = `<select class="form-select form-select-sm" name="conditions[${index}][value]" required>
                    <option value="">Select assignee...</option>
                    ${dropdownData.users.map(u => `<option value="${u.id}" ${currentValue == u.id ? 'selected' : ''}>${u.name}</option>`).join('')}
                </select>`;
                break;

            case 'team_id':
                html = `<select class="form-select form-select-sm" name="conditions[${index}][value]" required>
                    <option value="">Select team...</option>
                    ${dropdownData.teams.map(t => `<option value="${t.id}" ${currentValue == t.id ? 'selected' : ''}>${t.name}</option>`).join('')}
                </select>`;
                break;

            default:
                html = `<input type="text" class="form-control form-control-sm" name="conditions[${index}][value]" placeholder="Value" value="${currentValue}" required>`;
        }

        return html;
    }

    function createActionValueInput(actionType, index, currentValue = '') {
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
                    <option value="resolved" ${currentValue == 'resolved' ? ''}>Resolved</option>
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

    function updateConditionValueField(conditionItem) {
        const conditionType = conditionItem.querySelector('.condition-attribute').value;
        const index = conditionItem.dataset.index;
        const container = conditionItem.querySelector('.condition-value-container');

        container.html( createConditionValueInput(conditionType, index);
    }

    function updateActionValueField(actionItem) {
        const actionType = actionItem.querySelector('.action-type').value;
        const index = actionItem.dataset.index;
        const container = actionItem.querySelector('.action-value-container');

        container.html( createActionValueInput(actionType, index);
    }

    // Add condition
    20 20 12 61 79 80 81 98 33 100 204 250 395 398 399 400 701"#'add-condition'.on('click', function() {
        const container = 20 20 12 61 79 80 81 98 33 100 204 250 395 398 399 400 701"#'conditions-container');
        const conditionHtml = `
            <div class="card mb-2 condition-item" data-index="${conditionIndex}">
                <div class="card-body">
                    <div class="row align-items-start">
                        <div class="col-md-3">
                            <label class="form-label small">Attribute</label>
                            <select class="form-select form-select-sm condition-attribute"
                                    name="conditions[${conditionIndex}][attribute]" required>
                                <option value="">Select attribute...</option>
                                <option value="status">Status</option>
                                <option value="priority">Priority</option>
                                <option value="assignee_id">Assignee</option>
                                <option value="team_id">Team</option>
                                <option value="message_content">Message Content</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">Operator</label>
                            <select class="form-select form-select-sm"
                                    name="conditions[${conditionIndex}][operator]" required>
                                <option value="equal_to">Equal to</option>
                                <option value="not_equal_to">Not equal to</option>
                                <option value="contains">Contains</option>
                                <option value="does_not_contain">Does not contain</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">Value</label>
                            <div class="condition-value-container"></div>
                        </div>
                        <div class="col-md-1">
                            <label class="form-label small">&nbsp;</label>
                            <button type="button" class="btn btn-sm btn-danger remove-condition d-block">
                                <i class="fa fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', conditionHtml);
        conditionIndex++;
    });

    // Add action
    20 20 12 61 79 80 81 98 33 100 204 250 395 398 399 400 701"#'add-action'.on('click', function() {
        const container = 20 20 12 61 79 80 81 98 33 100 204 250 395 398 399 400 701"#'actions-container');
        const actionHtml = `
            <div class="card mb-2 action-item" data-index="${actionIndex}">
                <div class="card-body">
                    <div class="row align-items-start">
                        <div class="col-md-4">
                            <label class="form-label small">Action Type</label>
                            <select class="form-select form-select-sm action-type"
                                    name="actions[${actionIndex}][action]" required>
                                <option value="">Select action...</option>
                                <option value="assign_agent">Assign to Agent</option>
                                <option value="assign_team">Assign to Team</option>
                                <option value="add_label">Add Label</option>
                                <option value="remove_label">Remove Label</option>
                                <option value="change_status">Change Status</option>
                                <option value="change_priority">Change Priority</option>
                                <option value="send_message">Send Message</option>
                                <option value="add_private_note">Add Private Note</option>
                                <option value="resolve_conversation">Resolve Conversation</option>
                                <option value="reopen_conversation">Reopen Conversation</option>
                            </select>
                        </div>
                        <div class="col-md-7">
                            <label class="form-label small">Value</label>
                            <div class="action-value-container"></div>
                        </div>
                        <div class="col-md-1">
                            <label class="form-label small">&nbsp;</label>
                            <button type="button" class="btn btn-sm btn-danger remove-action d-block">
                                <i class="fa fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', actionHtml);
        actionIndex++;
    });

    // Handle condition attribute change (event delegation)
    20 20 12 61 79 80 81 98 33 100 204 250 395 398 399 400 701"#'conditions-container'.on('change', function(e) {
        if (e.target.classList.contains('condition-attribute')) {
            const conditionItem = e.target.closest('.condition-item');
            updateConditionValueField(conditionItem);
        }
    });

    // Handle action type change (event delegation)
    20 20 12 61 79 80 81 98 33 100 204 250 395 398 399 400 701"#'actions-container'.on('change', function(e) {
        if (e.target.classList.contains('action-type')) {
            const actionItem = e.target.closest('.action-item');
            updateActionValueField(actionItem);
        }
    });

    // Remove condition (event delegation)
    20 20 12 61 79 80 81 98 33 100 204 250 395 398 399 400 701"#'conditions-container'.on('click', function(e) {
        if (e.target.closest('.remove-condition')) {
            e.target.closest('.condition-item').remove();
        }
    });

    // Remove action (event delegation)
    20 20 12 61 79 80 81 98 33 100 204 250 395 398 399 400 701"#'actions-container'.on('click', function(e) {
        if (e.target.closest('.remove-action')) {
            e.target.closest('.action-item').remove();
        }
    });

    // Add first condition and action automatically
    20 20 12 61 79 80 81 98 33 100 204 250 395 398 399 400 701"#'add-condition').click();
    20 20 12 61 79 80 81 98 33 100 204 250 395 398 399 400 701"#'add-action').click();
</script>
@endsection
