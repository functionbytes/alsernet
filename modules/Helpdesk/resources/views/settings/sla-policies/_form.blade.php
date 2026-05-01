<div class="row g-3">

    {{-- Nombre --}}
    <div class="col-12">
        <label class="form-label">
            Nombre <span class="text-danger">*</span>
        </label>
        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
            value="{{ old('name', $slaPolicy->name ?? '') }}"
            placeholder="Ej: SLA prioritario - 1 hora">
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Descripcion --}}
    <div class="col-12">
        <label class="form-label">Descripcion</label>
        <textarea name="description" rows="3"
            class="form-control @error('description') is-invalid @enderror"
            placeholder="Descripcion opcional de la politica SLA">{{ old('description', $slaPolicy->description ?? '') }}</textarea>
        @error('description')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Tiempos --}}
    <div class="col-12 col-md-6">
        <label class="form-label">Primera respuesta (minutos)</label>
        <input type="number" name="first_response_minutes" min="1"
            class="form-control @error('first_response_minutes') is-invalid @enderror"
            value="{{ old('first_response_minutes', $slaPolicy->first_response_minutes ?? '') }}"
            placeholder="Ej: 60">
        <div class="form-text">Ej: 60 = 1 hora, 120 = 2 horas, 1440 = 1 dia</div>
        @error('first_response_minutes')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12 col-md-6">
        <label class="form-label">Resolucion (minutos)</label>
        <input type="number" name="resolution_minutes" min="1"
            class="form-control @error('resolution_minutes') is-invalid @enderror"
            value="{{ old('resolution_minutes', $slaPolicy->resolution_minutes ?? '') }}"
            placeholder="Ej: 480">
        <div class="form-text">Ej: 480 = 8 horas, 2880 = 2 dias</div>
        @error('resolution_minutes')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Opciones --}}
    <div class="col-12">
        <div class="border rounded p-3">
            <p class="small text-muted mb-3 fw-semibold">Opciones de la politica</p>

            <div class="form-check mb-2">
                <input type="checkbox" name="business_hours_only" value="1"
                    id="business_hours_only"
                    class="form-check-input"
                    @checked(old('business_hours_only', $slaPolicy->business_hours_only ?? false))>
                <label class="form-check-label" for="business_hours_only">
                    Solo horario laboral
                    <small class="text-muted d-block">El conteo de tiempo se pausa fuera del horario de trabajo configurado</small>
                </label>
            </div>

            <div class="form-check mb-2">
                <input type="checkbox" name="is_active" value="1"
                    id="is_active"
                    class="form-check-input"
                    @checked(old('is_active', $slaPolicy->is_active ?? true))>
                <label class="form-check-label" for="is_active">
                    Politica activa
                    <small class="text-muted d-block">Habilitar esta politica SLA para asignar a conversaciones</small>
                </label>
            </div>

            <div class="form-check">
                <input type="checkbox" name="escalation_enabled" value="1"
                    id="escalation_enabled"
                    class="form-check-input"
                    @checked(old('escalation_enabled', $slaPolicy->escalation_enabled ?? false))>
                <label class="form-check-label" for="escalation_enabled">
                    Habilitar escalacion automatica
                    <small class="text-muted d-block">Escalar la conversacion cuando se alcance el tiempo de escalacion</small>
                </label>
            </div>
        </div>
    </div>

    {{-- Escalacion (condicional) --}}
    <div class="col-12 {{ old('escalation_enabled', $slaPolicy->escalation_enabled ?? false) ? '' : 'd-none' }}" id="escalation_field">
        <label class="form-label">Tiempo de escalacion (minutos)</label>
        <input type="number" name="escalation_minutes" min="1"
            class="form-control @error('escalation_minutes') is-invalid @enderror"
            value="{{ old('escalation_minutes', $slaPolicy->escalation_minutes ?? '') }}"
            placeholder="Ej: 30">
        <div class="form-text">Minutos sin resolucion antes de escalar la conversacion</div>
        @error('escalation_minutes')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

</div>

@push('scripts')
<script>
$(document).ready(function () {
    $('#escalation_enabled').on('change', function () {
        if ($(this).is(':checked')) {
            $('#escalation_field').show();
        } else {
            $('#escalation_field').hide();
            $('#escalation_field input').val('');
        }
    });
});
</script>
@endpush
