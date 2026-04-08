@extends('layouts.helpdesk')

@section('title', 'Crear Ticket - Helpdesk')

@section('content')
    {{-- Breadcrumb Header --}}
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="fw-semibold mb-3">Crear Nuevo Ticket</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item">
                                <a href="{{ route('manager.dashboard') }}">Dashboard</a>
                            </li>
                            <li class="breadcrumb-item">
                                <a href="{{ route('manager.helpdesk.tickets.index') }}">Tickets</a>
                            </li>
                            <li class="breadcrumb-item active">Crear Ticket</li>
                        </ol>
                    </nav>
                </div>
                <a href="{{ route('manager.helpdesk.tickets.index') }}" class="btn btn-light">
                    <i class="fas fa-arrow-left me-2"></i>Volver
                </a>
            </div>
        </div>
    </div>

    <form action="{{ route('manager.helpdesk.tickets.store') }}" method="POST" enctype="multipart/form-data" id="ticketForm">
        @csrf

        <div class="row">
            {{-- Left Column: Main Form --}}
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-info-circle me-2"></i>Información del Ticket
                        </h5>
                    </div>
                    <div class="card-body">
                        {{-- Customer Selection --}}
                        <div class="mb-4">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-user text-primary me-1"></i>Cliente
                                <span class="text-danger">*</span>
                            </label>
                            <select name="customer_id" class="form-select form-select-lg @error('customer_id') is-invalid @enderror" required id="customerSelect">
                                <option value="">Seleccione un cliente...</option>
                                @foreach($customers as $customer)
                                    <option value="{{ $customer->id }}" {{ old('customer_id') == $customer->id ? 'selected' : '' }}>
                                        {{ $customer->name }} - {{ $customer->email }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">
                                <i class="fas fa-info-circle"></i> El cliente recibirá notificaciones sobre este ticket
                            </small>
                            @error('customer_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Category Selection --}}
                        <div class="mb-4">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-folder text-warning me-1"></i>Categoría
                                <span class="text-danger">*</span>
                            </label>
                            <select name="category_id" class="form-select form-select-lg @error('category_id') is-invalid @enderror" required id="categorySelect">
                                <option value="">Seleccione una categoría...</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}"
                                            data-icon="{{ $category->icon ?? 'fas fa-tag' }}"
                                            data-color="{{ $category->color ?? '#90bb13' }}"
                                            data-fields="{{ json_encode($category->custom_form_fields ?? []) }}"
                                            data-required="{{ json_encode($category->required_fields ?? []) }}"
                                            {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">
                                <i class="fas fa-info-circle"></i> Determina el flujo de trabajo y SLA aplicable
                            </small>
                            @error('category_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Subject --}}
                        <div class="mb-4">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-heading text-info me-1"></i>Asunto
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="subject" class="form-control form-control-lg @error('subject') is-invalid @enderror"
                                   value="{{ old('subject') }}" required placeholder="Breve descripción del problema">
                            <small class="form-text text-muted">
                                <i class="fas fa-lightbulb"></i> Resumen claro y conciso del ticket
                            </small>
                            @error('subject')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Description --}}
                        <div class="mb-4">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-align-left text-success me-1"></i>Descripción
                                <span class="text-danger">*</span>
                            </label>
                            <textarea name="description" rows="8" class="form-control @error('description') is-invalid @enderror"
                                      required placeholder="Describa el problema en detalle...">{{ old('description') }}</textarea>
                            <small class="form-text text-muted">
                                <i class="fas fa-pen"></i> Incluya todos los detalles relevantes, pasos para reproducir, etc.
                            </small>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Dynamic Custom Fields Container --}}
                        <div id="customFieldsContainer"></div>

                        {{-- Attachments --}}
                        <div class="mb-4">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-paperclip text-secondary me-1"></i>Archivos Adjuntos
                            </label>
                            <input type="file" name="attachments[]" class="form-control" multiple
                                   accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif,.zip,.rar">
                            <small class="form-text text-muted">
                                <i class="fas fa-file-upload"></i> Máximo 10MB por archivo. Formatos: PDF, DOC, DOCX, XLS, XLSX, imágenes, ZIP, RAR
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right Column: Metadata --}}
            <div class="col-lg-4">
                {{-- Quick Info Card --}}
                <div class="card bg-light-primary mb-3">
                    <div class="card-body">
                        <h6 class="card-title fw-semibold">
                            <i class="fas fa-rocket text-primary"></i> Configuración del Ticket
                        </h6>
                        <p class="card-text small mb-0">Asigne prioridad, agente responsable y otros parámetros</p>
                    </div>
                </div>

                {{-- Settings Card --}}
                <div class="card">
                    <div class="card-header bg-light">
                        <h6 class="mb-0 fw-semibold">
                            <i class="fas fa-cog me-2"></i>Configuración
                        </h6>
                    </div>
                    <div class="card-body">
                        {{-- Priority --}}
                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-exclamation-triangle me-1"></i>Prioridad
                                <span class="text-danger">*</span>
                            </label>
                            <select name="priority" class="form-select @error('priority') is-invalid @enderror" required>
                                <option value="low" {{ old('priority') == 'low' ? 'selected' : '' }}>
                                    <i class="fas fa-arrow-down"></i> Baja
                                </option>
                                <option value="normal" {{ old('priority', 'normal') == 'normal' ? 'selected' : '' }}>
                                    <i class="fas fa-minus"></i> Normal
                                </option>
                                <option value="high" {{ old('priority') == 'high' ? 'selected' : '' }}>
                                    <i class="fas fa-arrow-up"></i> Alta
                                </option>
                                <option value="urgent" {{ old('priority') == 'urgent' ? 'selected' : '' }}>
                                    <i class="fas fa-fire"></i> Urgente
                                </option>
                            </select>
                            @error('priority')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Status --}}
                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-flag me-1"></i>Estado Inicial
                            </label>
                            <select name="status_id" class="form-select">
                                <option value="">Por defecto ({{ $defaultStatus->name ?? 'New' }})</option>
                                @foreach($statuses as $status)
                                    <option value="{{ $status->id }}" {{ old('status_id') == $status->id ? 'selected' : '' }}>
                                        {{ $status->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Assignee --}}
                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-user-check me-1"></i>Asignar a
                            </label>
                            <select name="assignee_id" class="form-select" id="assigneeSelect">
                                <option value="">Sin asignar</option>
                                @foreach($agents as $agent)
                                    <option value="{{ $agent->id }}" {{ old('assignee_id') == $agent->id ? 'selected' : '' }}>
                                        {{ $agent->firstname }} {{ $agent->lastname }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Group --}}
                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-users me-1"></i>Grupo
                            </label>
                            <select name="group_id" class="form-select">
                                <option value="">Sin grupo</option>
                                @foreach($groups as $group)
                                    <option value="{{ $group->id }}" {{ old('group_id') == $group->id ? 'selected' : '' }}>
                                        {{ $group->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- SLA Policy --}}
                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                <i class="far fa-clock me-1"></i>Política SLA
                            </label>
                            <select name="sla_policy_id" class="form-select">
                                <option value="">Por defecto (de la categoría)</option>
                                @foreach($slaPolicies as $policy)
                                    <option value="{{ $policy->id }}" {{ old('sla_policy_id') == $policy->id ? 'selected' : '' }}>
                                        {{ $policy->name }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted d-block mt-1">
                                <i class="fas fa-info-circle"></i> Si no selecciona, se usará la política de la categoría
                            </small>
                        </div>

                        {{-- Tags --}}
                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-tags me-1"></i>Etiquetas
                            </label>
                            <input type="text" name="tags" class="form-control" value="{{ old('tags') }}"
                                   placeholder="bug, urgente, vip">
                            <small class="text-muted d-block mt-1">
                                <i class="fas fa-info-circle"></i> Separadas por comas
                            </small>
                        </div>
                    </div>
                </div>

                {{-- Action Buttons Card --}}
                <div class="card mt-3">
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-check-circle me-2"></i>Crear Ticket
                            </button>
                            <a href="{{ route('manager.helpdesk.tickets.index') }}" class="btn btn-light">
                                <i class="fas fa-times-circle me-2"></i>Cancelar
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Dynamic custom fields rendering based on category selection
    const categorySelect = document.getElementById('categorySelect');
    const customFieldsContainer = document.getElementById('customFieldsContainer');

    categorySelect?.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const fields = JSON.parse(selectedOption.getAttribute('data-fields') || '[]');
        const required = JSON.parse(selectedOption.getAttribute('data-required') || '[]');

        // Clear previous fields
        customFieldsContainer.innerHTML = '';

        if (fields.length === 0) {
            return;
        }

        // Create section header
        const header = document.createElement('div');
        header.className = 'alert alert-info mb-4';
        header.innerHTML = `
            <h6 class="alert-heading mb-0">
                <i class="fas fa-list-check me-2"></i>Campos Personalizados de la Categoría
            </h6>
        `;
        customFieldsContainer.appendChild(header);

        // Render each custom field
        fields.forEach(field => {
            const isRequired = required.includes(field.name);
            const div = document.createElement('div');
            div.className = 'mb-3';

            let inputHtml = '';
            const oldValue = '';

            if (field.type === 'text') {
                inputHtml = `<input type="text" name="custom_fields[${field.name}]" class="form-control"
                            value="${oldValue}" ${isRequired ? 'required' : ''} placeholder="${field.placeholder || ''}">`;
            } else if (field.type === 'textarea') {
                inputHtml = `<textarea name="custom_fields[${field.name}]" class="form-control" rows="3"
                            ${isRequired ? 'required' : ''}>${oldValue}</textarea>`;
            } else if (field.type === 'select') {
                let options = '<option value="">Seleccione...</option>';
                (field.options || []).forEach(opt => {
                    options += `<option value="${opt}">${opt}</option>`;
                });
                inputHtml = `<select name="custom_fields[${field.name}]" class="form-select" ${isRequired ? 'required' : ''}>${options}</select>`;
            } else if (field.type === 'date') {
                inputHtml = `<input type="date" name="custom_fields[${field.name}]" class="form-control"
                            value="${oldValue}" ${isRequired ? 'required' : ''}>`;
            }

            div.innerHTML = `
                <label class="form-label fw-semibold">
                    <i class="fas fa-circle-dot text-primary me-1" style="font-size: 8px;"></i>${field.label || field.name}
                    ${isRequired ? '<span class="text-danger">*</span>' : ''}
                </label>
                ${inputHtml}
                ${field.help_text ? `<small class="text-muted d-block mt-1"><i class="fas fa-info-circle"></i> ${field.help_text}</small>` : ''}
            `;

            customFieldsContainer.appendChild(div);
        });
    });

    // Trigger on page load if category is pre-selected
    if (categorySelect && categorySelect.value) {
        categorySelect.dispatchEvent(new Event('change'));
    }

    // Select2 for better select UX
    if (typeof $.fn.select2 !== 'undefined') {
        $('#customerSelect').select2({
            placeholder: 'Buscar cliente por nombre o email...',
            allowClear: true,
            width: '100%'
        });

        $('#assigneeSelect').select2({
            placeholder: 'Seleccionar agente...',
            allowClear: true,
            width: '100%'
        });
    }

    // Form validation feedback
    $('#ticketForm').on('submit', function(e) {
        const submitBtn = $(this).find('button[type="submit"]');
        submitBtn.prop('disabled', true);
        submitBtn.html('<i class="fas fa-spinner fa-spin me-2"></i>Creando ticket...');
    });

    // Show toastr notifications for validation errors
    @if($errors->any())
        @foreach($errors->all() as $error)
            toastr.error('{{ $error }}', 'Error de Validación');
        @endforeach
    @endif
});
</script>
@endpush
