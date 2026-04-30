@extends('layouts.theme')

@section('title', 'Nueva política SLA')

@section('content')

    @include('core::components.alerts')

    <div class="card w-100">

        <form id="formSlaPolicy" method="POST" action="{{ route('settings.chat.sla-policies.store') }}">

            @csrf

            <div class="card-body">
                <div class="d-flex no-block align-items-center">
                    <h5 class="mb-0">Crear nueva política SLA</h5>
                </div>
                <p class="card-subtitle mb-3 mt-1">
                    Define tiempos de respuesta y resolución para gestionar conversaciones. Las políticas SLA ayudan a garantizar la calidad del servicio al cliente.
                </p>

                <div class="row">

                    <!-- Basic Information -->
                    <div class="col-12">
                        <h6 class="mb-1 mt-3 fw-semibold">Información básica</h6>
                        <p class="text-muted small mb-3">Define el nombre y la descripción de la política SLA.</p>
                    </div>

                    <div class="col-12">
                        <div class="mb-3">
                            <label class="control-label col-form-label">
                                Nombre
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required placeholder="Ej: SLA Soporte estándar">
                            <small class="form-text text-muted">Nombre identificativo de la política</small>
                            @error('name')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="mb-3">
                            <label class="control-label col-form-label">Descripción</label>
                            <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="2" placeholder="Descripción opcional de la política">{{ old('description') }}</textarea>
                            <small class="form-text text-muted">Proporciona más contexto sobre esta política SLA</small>
                            @error('description')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <!-- Response Times -->
                    <div class="col-12">
                        <hr class="my-4">
                        <h6 class="mb-1 fw-semibold">Tiempos de respuesta</h6>
                        <p class="text-muted small mb-3">Define los tiempos máximos para responder a las conversaciones. Los tiempos se miden en minutos.</p>
                    </div>

                    <div class="col-12 col-md-4">
                        <div class="mb-3">
                            <label class="control-label col-form-label">Primera respuesta (minutos)</label>
                            <input type="number" name="first_response_time_minutes" class="form-control time-input @error('first_response_time_minutes') is-invalid @enderror" value="{{ old('first_response_time_minutes') }}" min="1" placeholder="Ej: 60" data-target="first-calc">
                            <small class="form-text text-muted">
                                Tiempo para primera respuesta del agente
                                <span id="first-calc" class="fw-bold text-primary"></span>
                            </small>
                            @error('first_response_time_minutes')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12 col-md-4">
                        <div class="mb-3">
                            <label class="control-label col-form-label">Siguiente respuesta (minutos)</label>
                            <input type="number" name="next_response_time_minutes" class="form-control time-input @error('next_response_time_minutes') is-invalid @enderror" value="{{ old('next_response_time_minutes') }}" min="1" placeholder="Ej: 120" data-target="next-calc">
                            <small class="form-text text-muted">
                                Tiempo para respuestas posteriores
                                <span id="next-calc" class="fw-bold text-primary"></span>
                            </small>
                            @error('next_response_time_minutes')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12 col-md-4">
                        <div class="mb-3">
                            <label class="control-label col-form-label">Tiempo de resolución (minutos)</label>
                            <input type="number" name="resolution_time_minutes" class="form-control time-input @error('resolution_time_minutes') is-invalid @enderror" value="{{ old('resolution_time_minutes') }}" min="1" placeholder="Ej: 1440" data-target="resolution-calc">
                            <small class="form-text text-muted">
                                Tiempo para resolver la conversación
                                <span id="resolution-calc" class="fw-bold text-primary"></span>
                            </small>
                            @error('resolution_time_minutes')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <!-- Configuration -->
                    <div class="col-12">
                        <hr class="my-4">
                        <h6 class="mb-1 fw-semibold">Configuración</h6>
                        <p class="text-muted small mb-3">Opciones adicionales para la política SLA.</p>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input type="hidden" name="business_hours_only" value="0">
                                <input type="checkbox" name="business_hours_only" class="form-check-input" id="businessHours" value="1" {{ old('business_hours_only') ? 'checked' : '' }}>
                                <label class="form-check-label" for="businessHours">
                                    Solo horario comercial
                                </label>
                            </div>
                            <small class="form-text text-muted">Calcular tiempos SLA solo en horario laboral</small>
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="control-label col-form-label">Prioridad</label>
                            <input type="number" name="priority" class="form-control @error('priority') is-invalid @enderror" value="{{ old('priority', 0) }}" min="0" placeholder="0">
                            <small class="form-text text-muted">Mayor número = mayor prioridad</small>
                            @error('priority')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <!-- Status -->
                    <div class="col-12">
                        <hr class="my-4">
                        <h6 class="mb-1 fw-semibold">Estado</h6>
                        <p class="text-muted small mb-3">Especifica si esta política está activa y disponible para usar.</p>
                    </div>

                    <div class="col-12">
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input type="hidden" name="is_active" value="0">
                                <input type="checkbox" name="is_active" class="form-check-input" id="isActive" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="isActive">
                                    Activar política
                                </label>
                            </div>
                            <small class="form-text text-muted">Las políticas desactivadas no se aplicarán a nuevas conversaciones</small>
                        </div>
                    </div>

                </div>
            </div>

            <div class="card-footer bg-light border-top">
                <button type="submit" class="btn btn-primary w-100 mb-1">
                    Guardar
                </button>
                <a href="{{ route('settings.chat.sla-policies.index') }}" class="btn btn-secondary w-100">
                    Volver
                </a>
            </div>

        </form>

    </div>

@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // Calculate and display hours/minutes
        function updateTimeDisplay() {
            $('.time-input').each(function() {
                const minutes = parseInt($(this).val()) || 0;
                const targetId = $(this).data('target');
                const hours = Math.floor(minutes / 60);
                const mins = minutes % 60;

                if (minutes > 0) {
                    let display = '(';
                    if (hours > 0) {
                        display += hours + 'h';
                    }
                    if (mins > 0) {
                        if (hours > 0) display += ' ';
                        display += mins + 'm';
                    }
                    display += ')';
                    $('#' + targetId).text(display);
                } else {
                    $('#' + targetId).text('');
                }
            });
        }

        // Update on input
        $('.time-input').on('input', updateTimeDisplay);

        // Initial update
        updateTimeDisplay();

        @if (session('success'))
            toastr.success('{{ session('success') }}', 'Éxito');
        @endif

        @if (session('error'))
            toastr.error('{{ session('error') }}', 'Error');
        @endif
    });
</script>
@endpush
