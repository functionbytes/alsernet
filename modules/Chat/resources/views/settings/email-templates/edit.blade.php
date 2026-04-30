@extends('layouts.theme')

@section('title', 'Editar plantilla de email')

@section('content')
    <div class="row">
        <div class="col-lg-12 d-flex align-items-stretch">
            <div class="card w-100">
                <form id="formEmailTemplate" action="{{ route('settings.chat.email.update', $emailTemplate) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="card-header border-bottom p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-1 fw-bold">Editar plantilla de email</h5>
                                <p class="mb-0 text-muted small">{{ $emailTemplate->name }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        @include('core::components.alerts')

                        <div class="row">
                            <!-- Main Content -->
                            <div class="col-lg-8">
                                <!-- Basic Information -->
                                <div class="mb-4">
                                    <h6 class="fw-bold mb-3 border-bottom pb-2">Información básica</h6>
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <div class="mb-3">
                                                <label class="control-label col-form-label">Nombre <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control @error('name') is-invalid @enderror"
                                                       name="name" value="{{ old('name', $emailTemplate->name) }}" required
                                                       placeholder="ej: Notificación de nuevo mensaje">
                                                <small class="form-text text-muted">Nombre identificativo para uso interno</small>
                                                @error('name')
                                                    <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="control-label col-form-label">Tipo de evento <span class="text-danger">*</span></label>
                                                <select class="form-control select2 select2 @error('event_type') is-invalid @enderror"
                                                        name="event_type" required>
                                                    <option value="">Seleccionar evento...</option>
                                                    @foreach($eventTypes as $key => $label)
                                                        <option value="{{ $key }}" {{ old('event_type', $emailTemplate->event_type) === $key ? 'selected' : '' }}>
                                                            {{ $label }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @error('event_type')
                                                    <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="control-label col-form-label">Estado</label>
                                                <select class="form-control select2 select2 @error('active') is-invalid @enderror"
                                                        name="active" data-minimum-results-for-search="Infinity">
                                                    <option value="1" {{ old('active', $emailTemplate->active) == '1' ? 'selected' : '' }}>Activa</option>
                                                    <option value="0" {{ old('active', $emailTemplate->active) == '0' ? 'selected' : '' }}>Inactiva</option>
                                                </select>
                                                @error('active')
                                                    <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="col-12">
                                            <div class="mb-3">
                                                <label class="control-label col-form-label">Descripción</label>
                                                <textarea class="form-control @error('description') is-invalid @enderror"
                                                          name="description" rows="2"
                                                          placeholder="Descripción del propósito de esta plantilla">{{ old('description', $emailTemplate->description) }}</textarea>
                                                @error('description')
                                                    <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Email Content -->
                                <div class="mb-4">
                                    <h6 class="fw-bold mb-3 border-bottom pb-2">Contenido del email</h6>
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <div class="mb-3">
                                                <label class="control-label col-form-label">Asunto <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control @error('subject') is-invalid @enderror"
                                                       name="subject" value="{{ old('subject', $emailTemplate->subject) }}" required
                                                       placeholder="Usa variables como @{{contact_name}} o @{{conversation_id}}">
                                                @error('subject')
                                                    <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="col-12">
                                            <div class="mb-3">
                                                <label class="control-label col-form-label">Cuerpo HTML <span class="text-danger">*</span></label>
                                                <textarea class="form-control @error('body_html') is-invalid @enderror"
                                                          name="body_html" rows="15" required>{{ old('body_html', $emailTemplate->body_html) }}</textarea>
                                                <small class="form-text text-muted">Puedes usar HTML para dar formato</small>
                                                @error('body_html')
                                                    <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="col-12">
                                            <div class="mb-3">
                                                <label class="control-label col-form-label">Cuerpo texto plano</label>
                                                <textarea class="form-control @error('body_text') is-invalid @enderror"
                                                          name="body_text" rows="10">{{ old('body_text', $emailTemplate->body_text) }}</textarea>
                                                <small class="form-text text-muted">Alternativa para clientes de email que no soportan HTML</small>
                                                @error('body_text')
                                                    <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Sidebar -->
                            <div class="col-lg-4">
                                <div class="card mb-3 sticky-top" style="top: 20px;">
                                    <div class="card-header border-bottom">
                                        <h6 class="mb-0 fw-semibold">Variables disponibles</h6>
                                    </div>
                                    <div class="card-body">
                                        <p class="text-muted small mb-3">Haz clic para copiar una variable:</p>
                                        <div class="d-flex flex-column gap-2">
                                            @foreach($variables as $key => $description)
                                                <button type="button" class="btn btn-sm btn-outline-primary text-start variable-btn"
                                                        data-variable="{{ $key }}" title="{{ $description }}">
                                                    <code class="text-primary">@{{ {{ $key }} }}</code>
                                                </button>
                                                <small class="text-muted mb-2">{{ $description }}</small>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>

                                <div class="card">
                                    <div class="card-body">
                                        <a href="{{ route('settings.chat.email.preview', $emailTemplate) }}"
                                           class="btn btn-info w-100 mb-2" target="_blank">
                                            <i class="fa fa-eye"></i> Vista previa
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="card-footer border-top">
                        <button type="submit" class="btn btn-primary w-100 mb-1">
                            Actualizar plantilla
                        </button>
                        <a href="{{ route('settings.chat.email.index') }}" class="btn btn-secondary w-100">
                            Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Initialize Select2
    $('.select2').select2({
        allowClear: false
    });

    // Copy variable to clipboard
    $('.variable-btn').on('click', function(e) {
        e.preventDefault();
        const variable = '@{{' + $(this).data('variable') + '}}';

        if (navigator.clipboard) {
            navigator.clipboard.writeText(variable).then(function() {
                toastr.success('Variable copiada: ' + variable);
            });
        } else {
            // Fallback for older browsers
            const $temp = $('<input>');
            $('body').append($temp);
            $temp.val(variable).select();
            document.execCommand('copy');
            $temp.remove();
            toastr.success('Variable copiada: ' + variable);
        }
    });
});
</script>
@endpush
