@extends('layouts.helpdesk')

@section('title', 'Editar Ticket #' . $ticket->ticket_number . ' - Helpdesk')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                {{-- Header --}}
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <div>
                        <h4 class="mb-1">
                            <i class="fas fa-edit me-2 text-primary"></i>
                            Editar Ticket #{{ $ticket->ticket_number }}
                        </h4>
                        <p class="text-muted mb-0">Modifique la información del ticket</p>
                    </div>
                    <div class="btn-group">
                        <a href="{{ route('manager.helpdesk.tickets.show', $ticket->id) }}" class="btn btn-light">
                            <i class="fas fa-arrow-left"></i> Volver
                        </a>
                    </div>
                </div>

                {{-- Form Card --}}
                <div class="card">
                    <div class="card-body">
                        <form action="{{ route('manager.helpdesk.tickets.update', $ticket->id) }}" method="POST" enctype="multipart/form-data" id="ticketForm">
                            @csrf
                            @method('PUT')

                            <div class="row">
                                {{-- Left Column --}}
                                <div class="col-lg-8">
                                    {{-- Ticket Number (Read-only) --}}
                                    <div class="mb-4">
                                        <label class="form-label fw-semibold">Número de Ticket</label>
                                        <input type="text" class="form-control" value="{{ $ticket->ticket_number }}" disabled>
                                    </div>

                                    {{-- Customer (Read-only for closed tickets) --}}
                                    <div class="mb-4">
                                        <label class="form-label fw-semibold">Cliente</label>
                                        <input type="text" class="form-control" value="{{ $ticket->customer->name }} - {{ $ticket->customer->email }}" disabled>
                                    </div>

                                    {{-- Category Selection --}}
                                    <div class="mb-4">
                                        <label class="form-label fw-semibold">Categoría <span class="text-danger">*</span></label>
                                        @if($ticket->isClosed())
                                            <input type="text" class="form-control" value="{{ $ticket->category->name }}" disabled>
                                        @else
                                            <select name="category_id" class="form-select @error('category_id') is-invalid @enderror" required id="categorySelect">
                                                @foreach($categories as $category)
                                                    <option value="{{ $category->id }}"
                                                            data-fields="{{ json_encode($category->custom_form_fields ?? []) }}"
                                                            data-required="{{ json_encode($category->required_fields ?? []) }}"
                                                            {{ old('category_id', $ticket->category_id) == $category->id ? 'selected' : '' }}>
                                                        {{ $category->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('category_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        @endif
                                    </div>

                                    {{-- Subject --}}
                                    <div class="mb-4">
                                        <label class="form-label fw-semibold">Asunto <span class="text-danger">*</span></label>
                                        @if($ticket->isClosed())
                                            <input type="text" class="form-control" value="{{ $ticket->subject }}" disabled>
                                        @else
                                            <input type="text" name="subject" class="form-control @error('subject') is-invalid @enderror"
                                                   value="{{ old('subject', $ticket->subject) }}" required>
                                            @error('subject')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        @endif
                                    </div>

                                    {{-- Description --}}
                                    <div class="mb-4">
                                        <label class="form-label fw-semibold">Descripción <span class="text-danger">*</span></label>
                                        @if($ticket->isClosed())
                                            <textarea class="form-control" rows="6" disabled>{{ $ticket->description }}</textarea>
                                        @else
                                            <textarea name="description" rows="6" class="form-control @error('description') is-invalid @enderror"
                                                      required>{{ old('description', $ticket->description) }}</textarea>
                                            @error('description')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        @endif
                                    </div>

                                    {{-- Dynamic Custom Fields Container --}}
                                    <div id="customFieldsContainer"></div>

                                    {{-- Tags --}}
                                    <div class="mb-4">
                                        <label class="form-label fw-semibold">Etiquetas</label>
                                        <input type="text" name="tags" class="form-control"
                                               value="{{ old('tags', is_array($ticket->tags) ? implode(', ', $ticket->tags) : '') }}"
                                               placeholder="Etiquetas separadas por comas">
                                    </div>
                                </div>

                                {{-- Right Column --}}
                                <div class="col-lg-4">
                                    {{-- Priority --}}
                                    <div class="mb-4">
                                        <label class="form-label fw-semibold">Prioridad <span class="text-danger">*</span></label>
                                        @if($ticket->isClosed())
                                            <input type="text" class="form-control" value="{{ ucfirst($ticket->priority) }}" disabled>
                                        @else
                                            <select name="priority" class="form-select @error('priority') is-invalid @enderror" required>
                                                <option value="low" {{ old('priority', $ticket->priority) == 'low' ? 'selected' : '' }}>Baja</option>
                                                <option value="normal" {{ old('priority', $ticket->priority) == 'normal' ? 'selected' : '' }}>Normal</option>
                                                <option value="high" {{ old('priority', $ticket->priority) == 'high' ? 'selected' : '' }}>Alta</option>
                                                <option value="urgent" {{ old('priority', $ticket->priority) == 'urgent' ? 'selected' : '' }}>Urgente</option>
                                            </select>
                                            @error('priority')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        @endif
                                    </div>

                                    {{-- Status --}}
                                    <div class="mb-4">
                                        <label class="form-label fw-semibold">Estado</label>
                                        <select name="status_id" class="form-select @error('status_id') is-invalid @enderror">
                                            @foreach($statuses as $status)
                                                <option value="{{ $status->id }}" {{ old('status_id', $ticket->status_id) == $status->id ? 'selected' : '' }}>
                                                    {{ $status->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('status_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        @if($ticket->isClosed() && !auth()->user()->can('manager.helpdesk.tickets.reopen'))
                                            <small class="text-warning">
                                                <i class="fas fa-exclamation-triangle"></i> No tiene permisos para reabrir tickets cerrados
                                            </small>
                                        @endif
                                    </div>

                                    {{-- Assignee --}}
                                    <div class="mb-4">
                                        <label class="form-label fw-semibold">Asignar a</label>
                                        <select name="assignee_id" class="form-select">
                                            <option value="">Sin asignar</option>
                                            @foreach($agents as $agent)
                                                <option value="{{ $agent->id }}" {{ old('assignee_id', $ticket->assignee_id) == $agent->id ? 'selected' : '' }}>
                                                    {{ $agent->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    {{-- Group --}}
                                    <div class="mb-4">
                                        <label class="form-label fw-semibold">Grupo</label>
                                        <select name="group_id" class="form-select">
                                            <option value="">Sin grupo</option>
                                            @foreach($groups as $group)
                                                <option value="{{ $group->id }}" {{ old('group_id', $ticket->group_id) == $group->id ? 'selected' : '' }}>
                                                    {{ $group->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    {{-- SLA Policy --}}
                                    <div class="mb-4">
                                        <label class="form-label fw-semibold">Política SLA</label>
                                        <select name="sla_policy_id" class="form-select">
                                            <option value="">Sin política SLA</option>
                                            @foreach($slaPolicies as $policy)
                                                <option value="{{ $policy->id }}" {{ old('sla_policy_id', $ticket->sla_policy_id) == $policy->id ? 'selected' : '' }}>
                                                    {{ $policy->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    {{-- Archive Status --}}
                                    @if($ticket->isClosed())
                                        <div class="mb-4">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="is_archived" id="isArchived"
                                                       value="1" {{ old('is_archived', $ticket->is_archived) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="isArchived">
                                                    <i class="fas fa-archive"></i> Ticket archivado
                                                </label>
                                            </div>
                                        </div>
                                    @endif

                                    {{-- Current Status Info --}}
                                    <div class="card bg-light border-0 mb-4">
                                        <div class="card-body p-3">
                                            <h6 class="fw-semibold mb-2">Estado Actual</h6>
                                            <div class="mb-2">
                                                <small class="text-muted d-block">Estado</small>
                                                <span class="badge" style="background-color: {{ $ticket->status->color }}">
                                                    {{ $ticket->status->name }}
                                                </span>
                                            </div>
                                            @if($ticket->assigned_at)
                                                <div class="mb-2">
                                                    <small class="text-muted d-block">Asignado</small>
                                                    <small>{{ $ticket->assigned_at->format('d/m/Y H:i') }}</small>
                                                </div>
                                            @endif
                                            @if($ticket->resolved_at)
                                                <div class="mb-2">
                                                    <small class="text-muted d-block">Resuelto</small>
                                                    <small>{{ $ticket->resolved_at->format('d/m/Y H:i') }}</small>
                                                </div>
                                            @endif
                                            @if($ticket->closed_at)
                                                <div class="mb-2">
                                                    <small class="text-muted d-block">Cerrado</small>
                                                    <small>{{ $ticket->closed_at->format('d/m/Y H:i') }}</small>
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- Action Buttons --}}
                                    <div class="d-grid gap-2">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-check"></i> Guardar Cambios
                                        </button>
                                        <a href="{{ route('manager.helpdesk.tickets.show', $ticket->id) }}" class="btn btn-light">
                                            <i class="fas fa-times"></i> Cancelar
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- Warning for Closed Tickets --}}
                @if($ticket->isClosed())
                    <div class="alert alert-warning mt-3">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Ticket Cerrado:</strong> Algunos campos no pueden modificarse en tickets cerrados.
                        Para modificar campos restringidos, debe reabrir el ticket primero.
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    // Dynamic custom fields rendering based on category selection
    const categorySelect = document.getElementById('categorySelect');
    const customFieldsContainer = document.getElementById('customFieldsContainer');
    const existingCustomFields = @json($ticket->custom_fields ?? []);

    function renderCustomFields() {
        if (!categorySelect) return;

        const selectedOption = categorySelect.options[categorySelect.selectedIndex];
        const fields = JSON.parse(selectedOption.getAttribute('data-fields') || '[]');
        const required = JSON.parse(selectedOption.getAttribute('data-required') || '[]');

        // Clear previous fields
        customFieldsContainer.innerHTML = '';

        if (fields.length === 0) {
            return;
        }

        // Create header
        const header = document.createElement('h6');
        header.className = 'fw-semibold mb-3 mt-4';
        header.innerHTML = '<i class="far fa-file-alt"></i> Campos Personalizados';
        customFieldsContainer.appendChild(header);

        // Render each custom field
        fields.forEach(field => {
            const isRequired = required.includes(field.name);
            const div = document.createElement('div');
            div.className = 'mb-3';

            let inputHtml = '';
            const currentValue = existingCustomFields[field.name] || '';

            if (field.type === 'text') {
                inputHtml = `<input type="text" name="custom_fields[${field.name}]" class="form-control"
                            value="${currentValue}" ${isRequired ? 'required' : ''} placeholder="${field.placeholder || ''}">`;
            } else if (field.type === 'textarea') {
                inputHtml = `<textarea name="custom_fields[${field.name}]" class="form-control" rows="3"
                            ${isRequired ? 'required' : ''}>${currentValue}</textarea>`;
            } else if (field.type === 'select') {
                let options = '<option value="">Seleccione...</option>';
                (field.options || []).forEach(opt => {
                    const selected = currentValue === opt ? 'selected' : '';
                    options += `<option value="${opt}" ${selected}>${opt}</option>`;
                });
                inputHtml = `<select name="custom_fields[${field.name}]" class="form-select" ${isRequired ? 'required' : ''}>${options}</select>`;
            } else if (field.type === 'date') {
                inputHtml = `<input type="date" name="custom_fields[${field.name}]" class="form-control"
                            value="${currentValue}" ${isRequired ? 'required' : ''}>`;
            }

            div.innerHTML = `
                <label class="form-label">${field.label || field.name} ${isRequired ? '<span class="text-danger">*</span>' : ''}</label>
                ${inputHtml}
                ${field.help_text ? `<small class="text-muted">${field.help_text}</small>` : ''}
            `;

            customFieldsContainer.appendChild(div);
        });
    }

    categorySelect?.addEventListener('change', renderCustomFields);

    // Trigger on page load
    if (categorySelect) {
        renderCustomFields();
    }
</script>
@endpush
