@extends('layouts.theme')

@section('title', 'Editar ticket #' . $ticket->ticket_number)

@section('page_header')
    @include('core::components.card', ['title' => 'Editar ticket #' . $ticket->ticket_number])
@endsection

@section('content')

    @if($ticket->isClosed())
        <div class="alert alert-warning mb-3">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Ticket cerrado:</strong> Algunos campos no pueden modificarse. Para editar campos restringidos, reabre el ticket primero.
        </div>
    @endif

    <div class="row g-3">

        {{-- Form --}}
        <div class="col-12 col-lg-8">
            <form action="{{ route('manager.helpdesk.tickets.update', $ticket->id) }}" method="POST" enctype="multipart/form-data" id="ticketForm">
                @csrf
                @method('PUT')

                {{-- Informacion del ticket --}}
                <div class="card mb-3">
                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Informacion del ticket</h5>
                        <small class="text-muted">Asunto y descripcion inicial del ticket</small>
                    </div>
                    <div class="card-body">
                        @include('core::components.alerts')

                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label">Numero de ticket</label>
                                <input type="text" class="form-control" value="{{ $ticket->ticket_number }}" disabled>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label">Cliente</label>
                                <input type="text" class="form-control" value="{{ $ticket->customer?->name ?? "Sin cliente" }} — {{ $ticket->customer?->email ?? "" }}" disabled>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Asunto <span class="text-danger">*</span></label>
                                @if($ticket->isClosed())
                                    <input type="text" class="form-control" value="{{ $ticket->subject }}" disabled>
                                @else
                                    <input type="text" name="subject"
                                           class="form-control @error('subject') is-invalid @enderror"
                                           value="{{ old('subject', $ticket->subject) }}"
                                           required>
                                    @error('subject')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                @endif
                            </div>

                            <div class="col-12">
                                <label class="form-label">Descripcion <span class="text-danger">*</span></label>
                                @if($ticket->isClosed())
                                    <textarea class="form-control" rows="6" disabled>{{ $ticket->description }}</textarea>
                                @else
                                    <textarea name="description" rows="6"
                                              class="form-control @error('description') is-invalid @enderror"
                                              required>{{ old('description', $ticket->description) }}</textarea>
                                    @error('description')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                @endif
                            </div>

                            {{-- Dynamic custom fields --}}
                            <div id="customFieldsContainer" class="col-12"></div>
                        </div>
                    </div>
                </div>

                {{-- Clasificacion --}}
                <div class="card mb-3">
                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Clasificacion</h5>
                        <small class="text-muted">Estado, prioridad, categoria y SLA aplicable</small>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label">Categoria <span class="text-danger">*</span></label>
                                @if($ticket->isClosed())
                                    <input type="text" class="form-control" value="{{ $ticket->category?->name ?? "Sin categoria" }}" disabled>
                                @else
                                    <select name="category_id"
                                            class="form-select @error('category_id') is-invalid @enderror"
                                            required
                                            id="categorySelect">
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
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                @endif
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label">Prioridad <span class="text-danger">*</span></label>
                                @if($ticket->isClosed())
                                    <input type="text" class="form-control" value="{{ ucfirst($ticket->priority) }}" disabled>
                                @else
                                    <select name="priority" class="form-select @error('priority') is-invalid @enderror" required>
                                        <option value="low" {{ old('priority', $ticket->priority) == 'low' ? 'selected' : '' }}>Baja</option>
                                        <option value="medium" {{ old('priority', $ticket->priority) == 'medium' ? 'selected' : '' }}>Media</option>
                                        <option value="high" {{ old('priority', $ticket->priority) == 'high' ? 'selected' : '' }}>Alta</option>
                                        <option value="urgent" {{ old('priority', $ticket->priority) == 'urgent' ? 'selected' : '' }}>Urgente</option>
                                    </select>
                                    @error('priority')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                @endif
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label">Estado</label>
                                <select name="status_id" class="form-select @error('status_id') is-invalid @enderror">
                                    @foreach($statuses as $status)
                                        <option value="{{ $status->id }}" {{ old('status_id', $ticket->status_id) == $status->id ? 'selected' : '' }}>
                                            {{ $status->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('status_id')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label">Politica SLA</label>
                                <select name="sla_policy_id" class="form-select">
                                    <option value="">Sin politica SLA</option>
                                    @foreach($slaPolicies as $policy)
                                        <option value="{{ $policy->id }}" {{ old('sla_policy_id', $ticket->sla_policy_id) == $policy->id ? 'selected' : '' }}>
                                            {{ $policy->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Asignacion --}}
                <div class="card mb-3">
                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Asignacion</h5>
                        <small class="text-muted">Cliente y agente asignado</small>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label">Asignar a</label>
                                <select name="assignee_id" class="form-select">
                                    <option value="">Sin asignar</option>
                                    @foreach($agents as $agent)
                                        <option value="{{ $agent->id }}" {{ old('assignee_id', $ticket->assignee_id) == $agent->id ? 'selected' : '' }}>
                                            {{ $agent->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label">Grupo</label>
                                <select name="group_id" class="form-select">
                                    <option value="">Sin grupo</option>
                                    @foreach($groups as $group)
                                        <option value="{{ $group->id }}" {{ old('group_id', $ticket->group_id) == $group->id ? 'selected' : '' }}>
                                            {{ $group->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Configuracion --}}
                <div class="card mb-3">
                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Configuracion</h5>
                        <small class="text-muted">Visibilidad y opciones adicionales</small>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Etiquetas</label>
                                <input type="text" name="tags" class="form-control"
                                       value="{{ old('tags', is_array($ticket->tags) ? implode(', ', $ticket->tags) : '') }}"
                                       placeholder="Etiquetas separadas por comas">
                            </div>

                            @if($ticket->isClosed())
                                <div class="col-12 col-md-6">
                                    <label class="form-label">Archivado</label>
                                    <select name="is_archived" class="form-select">
                                        <option value="0" {{ old('is_archived', $ticket->is_archived ? 1 : 0) == 0 ? 'selected' : '' }}>No archivado</option>
                                        <option value="1" {{ old('is_archived', $ticket->is_archived ? 1 : 0) == 1 ? 'selected' : '' }}>Archivado</option>
                                    </select>
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-1">Guardar cambios</button>
                        <a href="{{ route('manager.helpdesk.tickets.show', $ticket->id) }}" class="btn btn-light w-100">Cancelar</a>
                    </div>
                </div>

            </form>
        </div>

        {{-- Help panel --}}
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-body">
                    <h6 class="card-title mb-3">Estado actual</h6>
                    <div class="mb-2">
                        <small class="text-muted d-block">Estado</small>
                        <span class="badge" style="background-color: {{ $ticket->status?->color ?? "#6c757d" }}">
                            {{ $ticket->status?->name ?? "Sin estado" }}
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

            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-3">Sobre la edicion</h6>
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2 text-muted small"><i class="fas fa-info-circle text-primary me-2"></i> El cliente recibira notificacion si cambias el estado</li>
                        <li class="mb-2 text-muted small"><i class="fas fa-info-circle text-primary me-2"></i> Cambiar categoria puede alterar el SLA aplicable</li>
                        @if($ticket->isClosed())
                            <li class="text-muted small"><i class="fas fa-lock text-warning me-2"></i> Ticket cerrado: algunos campos estan bloqueados</li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>

    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    const existingCustomFields = @json($ticket->custom_fields ?? []);
    const $categorySelect = $('#categorySelect');
    const $customFieldsContainer = $('#customFieldsContainer');

    function renderCustomFields() {
        if (!$categorySelect.length) return;

        const $selected = $categorySelect.find(':selected');
        const fields = JSON.parse($selected.attr('data-fields') || '[]');
        const required = JSON.parse($selected.attr('data-required') || '[]');

        $customFieldsContainer.empty();

        if (fields.length === 0) return;

        $customFieldsContainer.append(
            '<div class="col-12"><h6 class="fw-semibold mb-1 border-bottom pb-2">Campos personalizados</h6></div>'
        );

        fields.forEach(function (field) {
            const isRequired = required.includes(field.name);
            const fieldName = 'custom_fields[' + field.name + ']';
            const currentValue = existingCustomFields[field.name] || '';
            let $input;

            if (field.type === 'text') {
                $input = $('<input type="text" class="form-control">')
                    .attr({ name: fieldName, placeholder: field.placeholder || '' })
                    .val(currentValue);
            } else if (field.type === 'textarea') {
                $input = $('<textarea class="form-control" rows="3">').attr('name', fieldName).val(currentValue);
            } else if (field.type === 'select') {
                $input = $('<select class="form-select">').attr('name', fieldName);
                $input.append($('<option value="">').text('Seleccione...'));
                (field.options || []).forEach(function (opt) {
                    $input.append($('<option>').val(opt).text(opt).prop('selected', currentValue === opt));
                });
            } else if (field.type === 'date') {
                $input = $('<input type="date" class="form-control">').attr('name', fieldName).val(currentValue);
            } else {
                return;
            }

            if (isRequired) {
                $input.prop('required', true);
            }

            const $label = $('<label class="form-label">').text(field.label || field.name);
            if (isRequired) {
                $label.append($('<span class="text-danger">').text('*'));
            }

            const $col = $('<div class="col-12">').append($label, $input);

            if (field.help_text) {
                $col.append($('<small class="text-muted">').text(field.help_text));
            }

            $customFieldsContainer.append($col);
        });
    }

    $categorySelect.on('change', renderCustomFields);
    renderCustomFields();
});
</script>
@endpush
