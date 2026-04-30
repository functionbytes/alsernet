@extends('layouts.theme')

@section('title', 'Crear ticket')

@section('page_header')
    @include('core::components.card', ['title' => 'Crear ticket'])
@endsection

@section('content')

    <div class="row g-3">

        {{-- Form --}}
        <div class="col-12 col-lg-8">
            <form action="{{ route('manager.helpdesk.tickets.store') }}" method="POST" enctype="multipart/form-data" id="ticketForm">
                @csrf

                {{-- Informacion del ticket --}}
                <div class="card mb-3">
                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Informacion del ticket</h5>
                        <small class="text-muted">Asunto y descripcion inicial del ticket</small>
                    </div>
                    <div class="card-body">
                        @include('core::components.alerts')

                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Asunto <span class="text-danger">*</span></label>
                                <input type="text" name="subject"
                                       class="form-control @error('subject') is-invalid @enderror"
                                       value="{{ old('subject') }}"
                                       placeholder="Breve descripcion del problema"
                                       required>
                                @error('subject')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                                <div id="kb-suggestions" class="mt-2"></div>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Descripcion <span class="text-danger">*</span></label>
                                <textarea name="description" rows="8"
                                          class="form-control @error('description') is-invalid @enderror"
                                          placeholder="Describa el problema en detalle..."
                                          required>{{ old('description') }}</textarea>
                                @error('description')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label">Archivos adjuntos</label>
                                <input type="file" name="attachments[]" class="form-control" multiple
                                       accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif,.zip,.rar">
                                <small class="text-muted">Maximo 10MB por archivo. Formatos: PDF, DOC, DOCX, XLS, XLSX, imagenes, ZIP, RAR</small>
                            </div>

                            {{-- Dynamic custom fields injected here --}}
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
                                <select name="category_id"
                                        class="form-select @error('category_id') is-invalid @enderror"
                                        required
                                        id="categorySelect">
                                    <option value="">Seleccione una categoria...</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}"
                                                data-fields="{{ json_encode($category->custom_form_fields ?? []) }}"
                                                data-required="{{ json_encode($category->required_fields ?? []) }}"
                                                {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('category_id')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label">Prioridad <span class="text-danger">*</span></label>
                                <select name="priority" class="form-select @error('priority') is-invalid @enderror" required>
                                    <option value="low" {{ old('priority') == 'low' ? 'selected' : '' }}>Baja</option>
                                    <option value="medium" {{ old('priority', 'medium') == 'medium' ? 'selected' : '' }}>Media</option>
                                    <option value="high" {{ old('priority') == 'high' ? 'selected' : '' }}>Alta</option>
                                    <option value="urgent" {{ old('priority') == 'urgent' ? 'selected' : '' }}>Urgente</option>
                                </select>
                                @error('priority')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label">Estado inicial</label>
                                <select name="status_id" class="form-select">
                                    <option value="">Por defecto ({{ $defaultStatus->name ?? 'New' }})</option>
                                    @foreach($statuses as $status)
                                        <option value="{{ $status->id }}" {{ old('status_id') == $status->id ? 'selected' : '' }}>
                                            {{ $status->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label">Politica SLA</label>
                                <select name="sla_policy_id" class="form-select">
                                    <option value="">Por defecto (de la categoria)</option>
                                    @foreach($slaPolicies as $policy)
                                        <option value="{{ $policy->id }}" {{ old('sla_policy_id') == $policy->id ? 'selected' : '' }}>
                                            {{ $policy->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Si no selecciona, se usara la politica de la categoria</small>
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
                            <div class="col-12">
                                <label class="form-label">Cliente <span class="text-danger">*</span></label>
                                <select name="customer_id"
                                        class="form-select @error('customer_id') is-invalid @enderror"
                                        required
                                        id="customerSelect">
                                    <option value="">Seleccione un cliente...</option>
                                    @foreach($customers as $customer)
                                        <option value="{{ $customer->id }}" {{ old('customer_id') == $customer->id ? 'selected' : '' }}>
                                            {{ $customer->name }} — {{ $customer->email }}
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted">El cliente recibira notificaciones sobre este ticket</small>
                                @error('customer_id')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label">Asignar a</label>
                                <select name="assignee_id" class="form-select" id="assigneeSelect">
                                    <option value="">Sin asignar</option>
                                    @foreach($agents as $agent)
                                        <option value="{{ $agent->id }}" {{ old('assignee_id') == $agent->id ? 'selected' : '' }}>
                                            {{ $agent->firstname }} {{ $agent->lastname }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label">Grupo</label>
                                <select name="group_id" class="form-select">
                                    <option value="">Sin grupo</option>
                                    @foreach($groups as $group)
                                        <option value="{{ $group->id }}" {{ old('group_id') == $group->id ? 'selected' : '' }}>
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
                                       value="{{ old('tags') }}"
                                       placeholder="bug, urgente, vip">
                                <small class="text-muted">Separadas por comas</small>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-1">
                            Crear ticket
                        </button>
                        <a href="{{ route('manager.helpdesk.tickets.index') }}" class="btn btn-light w-100">Cancelar</a>
                    </div>
                </div>

            </form>
        </div>

        {{-- Help panel --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-3">Sobre los tickets</h6>
                    <p class="card-text text-muted">
                        Los tickets permiten registrar y gestionar solicitudes de soporte de clientes, con seguimiento de estado, prioridad y SLA.
                    </p>
                </div>
                <hr class="my-0">
                <div class="card-body">
                    <h6 class="card-title mb-3">Buenas practicas</h6>
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2 text-muted small"><i class="fas fa-check-circle text-success me-2"></i> Usa un asunto claro y descriptivo</li>
                        <li class="mb-2 text-muted small"><i class="fas fa-check-circle text-success me-2"></i> Selecciona la categoria correcta para aplicar el SLA adecuado</li>
                        <li class="mb-2 text-muted small"><i class="fas fa-check-circle text-success me-2"></i> Asigna al agente responsable desde el inicio</li>
                        <li class="text-muted small"><i class="fas fa-check-circle text-success me-2"></i> Adjunta capturas o documentos relevantes</li>
                    </ul>
                </div>
            </div>
        </div>

    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    // Dynamic custom fields based on category selection
    const $categorySelect = $('#categorySelect');
    const $customFieldsContainer = $('#customFieldsContainer');

    $categorySelect.on('change', function () {
        const $selected = $(this).find(':selected');
        const fields = JSON.parse($selected.attr('data-fields') || '[]');
        const required = JSON.parse($selected.attr('data-required') || '[]');

        $customFieldsContainer.empty();

        if (fields.length === 0) return;

        $customFieldsContainer.append(
            '<div class="col-12"><h6 class="fw-semibold mb-1 border-bottom pb-2">Campos personalizados</h6></div>'
        );

        fields.forEach(function (field) {
            const isRequired = required.includes(field.name);
            const requiredAttr = isRequired ? 'required' : '';
            const requiredMark = isRequired ? '<span class="text-danger">*</span>' : '';
            let inputHtml = '';

            if (field.type === 'text') {
                inputHtml = `<input type="text" name="custom_fields[${field.name}]" class="form-control" ${requiredAttr} placeholder="${field.placeholder || ''}">`;
            } else if (field.type === 'textarea') {
                inputHtml = `<textarea name="custom_fields[${field.name}]" class="form-control" rows="3" ${requiredAttr}></textarea>`;
            } else if (field.type === 'select') {
                let options = '<option value="">Seleccione...</option>';
                (field.options || []).forEach(function (opt) { options += `<option value="${opt}">${opt}</option>`; });
                inputHtml = `<select name="custom_fields[${field.name}]" class="form-select" ${requiredAttr}>${options}</select>`;
            } else if (field.type === 'date') {
                inputHtml = `<input type="date" name="custom_fields[${field.name}]" class="form-control" ${requiredAttr}>`;
            }

            $customFieldsContainer.append(`
                <div class="col-12">
                    <label class="form-label">${field.label || field.name} ${requiredMark}</label>
                    ${inputHtml}
                    ${field.help_text ? `<small class="text-muted">${field.help_text}</small>` : ''}
                </div>
            `);
        });
    });

    // Trigger on page load if category is pre-selected
    if ($categorySelect.val()) {
        $categorySelect.trigger('change');
    }

    // Select2 for customer and assignee
    if ($.fn.select2) {
        $('#customerSelect').select2({ placeholder: 'Buscar cliente por nombre o email...', allowClear: true, width: '100%' });
        $('#assigneeSelect').select2({ placeholder: 'Seleccionar agente...', allowClear: true, width: '100%' });
    }

    // Disable submit while sending
    $('#ticketForm').on('submit', function () {
        const $btn = $(this).find('button[type="submit"]');
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Creando ticket...');
    });

    // Knowledge base suggestions on subject input
    let kbTimer;
    $(document).on('input', 'input[name="subject"]', function () {
        clearTimeout(kbTimer);
        const q = $(this).val().trim();
        if (q.length < 3) {
            $('#kb-suggestions').empty();
            return;
        }
        kbTimer = setTimeout(function () {
            $.get('/helpcenter/search', { q: q }).done(function (res) {
                if (res.articles && res.articles.length) {
                    const items = res.articles.map(function (a) {
                        return '<a href="/helpcenter/articles/' + a.slug + '" target="_blank" class="list-group-item list-group-item-action small py-2">'
                            + '<i class="fas fa-book me-2 text-muted"></i>' + a.title
                            + '</a>';
                    }).join('');
                    $('#kb-suggestions').html(
                        '<div class="alert alert-info p-2 mb-0">'
                        + '<strong class="small"><i class="fas fa-lightbulb me-1"></i> ¿Esto podria resolverlo?</strong>'
                        + '<div class="list-group mt-2">' + items + '</div>'
                        + '</div>'
                    );
                } else {
                    $('#kb-suggestions').empty();
                }
            });
        }, 500);
    });
});
</script>
@endpush
