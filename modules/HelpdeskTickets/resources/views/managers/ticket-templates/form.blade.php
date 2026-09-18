@extends('layouts.theme')

@section('title', isset($template) ? 'Editar plantilla' : 'Nueva plantilla')

@section('page_header')
    @include('core::components.card', ['title' => isset($template) ? 'Editar plantilla' : 'Nueva plantilla'])
@endsection

@section('content')

    <div class="row g-3">

        {{-- Form --}}
        <div class="col-12 col-lg-8">
            <div class="card">
                <form action="{{ isset($template) ? route('manager.helpdesk.ticket-templates.update', $template->id) : route('manager.helpdesk.ticket-templates.store') }}"
                      method="POST">
                    @csrf
                    @isset($template)
                        @method('PUT')
                    @endisset

                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">{{ isset($template) ? 'Editar plantilla' : 'Nueva plantilla' }}</h5>
                        <small class="text-muted">{{ isset($template) ? 'Modifica los datos de la plantilla' : 'Crea una nueva plantilla reutilizable para tickets' }}</small>
                    </div>

                    <div class="card-body">
                        @include('core::components.alerts')

                        {{-- Información básica --}}
                        <h6 class="fw-semibold mb-1">Información básica</h6>
                        <p class="text-muted small mb-3">Nombre interno de la plantilla y descripción de su propósito</p>
                        <div class="row g-3 mb-4">

                            <div class="col-12">
                                <label class="form-label">Nombre <span class="text-danger">*</span></label>
                                <input type="text" name="name"
                                       class="form-control @error('name') is-invalid @enderror"
                                       value="{{ old('name', $template->name ?? '') }}"
                                       placeholder="Ej: Soporte técnico general"
                                       required>
                                @error('name')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label">Descripción</label>
                                <input type="text" name="description"
                                       class="form-control @error('description') is-invalid @enderror"
                                       value="{{ old('description', $template->description ?? '') }}"
                                       placeholder="Breve descripción del uso de esta plantilla">
                                @error('description')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                        </div>

                        {{-- Contenido --}}
                        <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-1">
                            <h6 class="fw-semibold mb-0">Contenido</h6>
                            <button type="button" id="previewTemplateBtn" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-eye me-1"></i> Vista previa
                            </button>
                        </div>
                        <p class="text-muted small mb-3">Asunto y cuerpo del ticket que se creará al aplicar la plantilla. Acepta variables — ver la lista completa en el panel de la derecha.</p>
                        <div class="row g-3 mb-4">

                            <div class="col-12">
                                <label class="form-label">Asunto <span class="text-danger">*</span></label>
                                <input type="text" name="subject" id="templateSubjectInput"
                                       class="form-control @error('subject') is-invalid @enderror"
                                       value="{{ old('subject', $template->subject ?? '') }}"
                                       placeholder="Asunto del ticket"
                                       required>
                                @error('subject')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label">Cuerpo <span class="text-danger">*</span></label>
                                <textarea name="body" id="templateBodyInput" rows="8"
                                          class="form-control @error('body') is-invalid @enderror"
                                          placeholder="Contenido de la plantilla..."
                                          required>{{ old('body', $template->body ?? '') }}</textarea>
                                @error('body')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12" id="templatePreviewBox" hidden>
                                <div class="alert alert-secondary mb-0">
                                    <div class="small fw-semibold mb-1">Vista previa con datos de ejemplo</div>
                                    <div class="small mb-2"><strong id="templatePreviewSubject"></strong></div>
                                    <div class="small tpl-preview-body" id="templatePreviewBody"></div>
                                </div>
                            </div>

                        </div>

                        {{-- Clasificación --}}
                        <h6 class="fw-semibold mb-1">Clasificación</h6>
                        <p class="text-muted small mb-3">Categoría y prioridad predeterminadas al aplicar la plantilla</p>
                        <div class="row g-3 mb-4">

                            <div class="col-12 col-md-6">
                                <label class="form-label">Categoría</label>
                                <select name="category_id" class="form-select select2 @error('category_id') is-invalid @enderror">
                                    <option value="">Sin categoría</option>
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat->id }}"
                                            {{ old('category_id', $template->category_id ?? '') == $cat->id ? 'selected' : '' }}>
                                            {{ $cat->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('category_id')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label">Prioridad</label>
                                @php $currentPriority = old('priority', $template->priority ?? ''); @endphp
                                <select name="priority" class="form-select select2 @error('priority') is-invalid @enderror">
                                    <option value="">Sin prioridad</option>
                                    <option value="low" {{ $currentPriority == 'low' ? 'selected' : '' }}>Baja</option>
                                    <option value="normal" {{ $currentPriority == 'normal' ? 'selected' : '' }}>Media</option>
                                    <option value="high" {{ $currentPriority == 'high' ? 'selected' : '' }}>Alta</option>
                                    <option value="urgent" {{ $currentPriority == 'urgent' ? 'selected' : '' }}>Urgente</option>
                                </select>
                                @error('priority')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                        </div>

                        {{-- Configuración --}}
                        <h6 class="fw-semibold mb-1">Configuración</h6>
                        <p class="text-muted small mb-3">Disponibilidad y alcance de la plantilla</p>
                        <div class="row g-3">

                            <div class="col-12 col-md-6">
                                <label class="form-label">Estado</label>
                                <select name="is_active" class="form-select select2 @error('is_active') is-invalid @enderror">
                                    <option value="1" {{ old('is_active', $template->is_active ?? 1) == 1 ? 'selected' : '' }}>
                                        Activa — disponible para aplicar
                                    </option>
                                    <option value="0" {{ old('is_active', $template->is_active ?? 1) == 0 ? 'selected' : '' }}>
                                        Inactiva — oculta
                                    </option>
                                </select>
                                @error('is_active')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                            @if($canManageGeneral)
                                <div class="col-12 col-md-6">
                                    <label class="form-label">Alcance</label>
                                    @php $currentIsGeneral = old('is_general', isset($template) ? ($template->isGeneral() ? 1 : 0) : 0); @endphp
                                    <select name="is_general" class="form-select select2">
                                        <option value="0" {{ $currentIsGeneral == 0 ? 'selected' : '' }}>
                                            Personal — solo tu la ves y usas
                                        </option>
                                        <option value="1" {{ $currentIsGeneral == 1 ? 'selected' : '' }}>
                                            General — visible y usable por todos
                                        </option>
                                    </select>
                                </div>
                            @else
                                <div class="col-12 col-md-6">
                                    <label class="form-label">Alcance</label>
                                    <input type="text" class="form-control" value="Personal — solo tu la ves y usas" disabled>
                                    <small class="text-muted">Solo un administrador puede compartir una plantilla con todos</small>
                                </div>
                            @endif

                        </div>

                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-1">
                            {{ isset($template) ? 'Guardar cambios' : 'Crear plantilla' }}
                        </button>
                        <a href="{{ route('manager.helpdesk.ticket-templates.index') }}" class="btn btn-light w-100">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Help panel --}}
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">Sobre las plantillas</h6>
                </div>
                <div class="card-body">
                    <p class="card-text text-muted">
                        Las plantillas permiten crear tickets con información predefinida, agilizando la gestión de solicitudes recurrentes.
                    </p>
                </div>
            </div>
            <div class="card mb-3">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">Buenas prácticas</h6>
                </div>
                <div class="card-body">
                    <ul class="text-muted mb-0">
                        <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Usa nombres descriptivos que indiquen el tipo de solicitud</li>
                        <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Incluye variables como @{{customer_name}} para personalizar el contenido</li>
                        <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Asigna categoría y prioridad para que los tickets se clasifiquen automáticamente</li>
                        <li class="mb-0"><i class="fas fa-check-circle text-success me-2"></i> Desactiva las plantillas obsoletas en lugar de eliminarlas</li>
                    </ul>
                </div>
            </div>
            <div class="card">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">Variables disponibles</h6>
                </div>
                <div class="card-body">
                    <p class="small text-muted mb-3">Se sustituyen automáticamente al crear el ticket — @{{ticket_number}} no se rellena hasta ese momento, así que aparecerá vacío mientras editas.</p>
                    @foreach(\Modules\HelpdeskTickets\Services\TicketVariableInterpolator::availableVariables() as $group => $vars)
                        <div class="mb-3">
                            <div class="small fw-semibold mb-1">{{ $group }}</div>
                            @foreach($vars as $var => $desc)
                                <div class="d-flex justify-content-between small mb-1">
                                    <code>{{ $var }}</code>
                                    <span class="text-muted text-end ms-2">{{ $desc }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

    </div>

@endsection

<style>
.tpl-preview-body {
    white-space: pre-wrap;
}
</style>

@push('scripts')
<script>
$(document).ready(function () {
    $('.select2').select2({ width: '100%' });

    // Vista previa con datos de ejemplo — puramente en el navegador, no llama
    // al servidor ni al ERP; el mismo texto de ejemplo para todas las
    // variables listadas en el panel de la derecha (TicketVariableInterpolator::availableVariables()).
    var SAMPLE_VALUES = {
        '@{{ticket_number}}': 'TCK-2026-00123',
        '@{{ticket_subject}}': 'Asunto de ejemplo',
        '@{{ticket_status}}': 'Abierto',
        '@{{ticket_priority}}': 'Media',
        '@{{ticket_category}}': 'Soporte técnico',
        '@{{customer_name}}': 'Ana Pérez',
        '@{{customer_email}}': 'ana.perez@ejemplo.com',
        '@{{customer_phone}}': '600 111 222',
        '@{{agent_name}}': 'Tu nombre',
        '@{{assignee_name}}': 'Tu nombre',
        '@{{fecha}}': new Date().toLocaleDateString('es-ES'),
        '@{{erp_id_cliente}}': '4521',
        '@{{erp_nif}}': 'B12345678',
        '@{{erp_ciudad}}': 'Madrid',
        '@{{erp_saldo_pendiente}}': '150.00',
        '@{{erp_limite_credito}}': '5000',
        '@{{erp_ultimo_pedido_numero}}': 'PED-000987',
        '@{{erp_ultimo_pedido_fecha}}': '15/08/2026',
    };

    function applySample(text) {
        Object.keys(SAMPLE_VALUES).forEach(function (key) {
            text = text.split(key).join(SAMPLE_VALUES[key]);
        });

        return text;
    }

    $('#previewTemplateBtn').on('click', function () {
        var subject = $('#templateSubjectInput').val() || '';
        var body = $('#templateBodyInput').val() || '';

        $('#templatePreviewSubject').text(applySample(subject));
        $('#templatePreviewBody').text(applySample(body));
        $('#templatePreviewBox').prop('hidden', false);
    });
});
</script>
@endpush
