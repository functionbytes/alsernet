<div class="row g-4">

    {{-- Seccion 1: Informacion basica --}}
    <div class="col-12">
        <h6 class="fw-bold border-bottom pb-2 mb-3">Informacion basica</h6>
        <div class="row g-3">

            {{-- Nombre --}}
            <div class="col-12">
                <label class="form-label">
                    Nombre <span class="text-danger">*</span>
                </label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                    value="{{ old('name', $dripCampaign->name ?? '') }}"
                    placeholder="Ej: Seguimiento post-cierre de conversacion">
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- Descripcion --}}
            <div class="col-12">
                <label class="form-label">Descripcion</label>
                <textarea name="description" rows="2"
                    class="form-control @error('description') is-invalid @enderror"
                    placeholder="Descripcion opcional de la campaña...">{{ old('description', $dripCampaign->description ?? '') }}</textarea>
                @error('description')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- Tipo de disparador --}}
            <div class="col-12 col-md-6">
                <label class="form-label">
                    Tipo de disparador <span class="text-danger">*</span>
                </label>
                <select name="trigger_type" id="trigger_type" class="form-select @error('trigger_type') is-invalid @enderror">
                    <option value="">Seleccionar disparador...</option>
                    @foreach($triggerTypes as $key => $label)
                        <option value="{{ $key }}" @selected(old('trigger_type', $dripCampaign->trigger_type ?? '') === $key)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                @error('trigger_type')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- Valor del disparador --}}
            <div class="col-12 col-md-6" id="trigger_value_wrapper">
                <label class="form-label">Valor del disparador</label>
                <input type="text" name="trigger_value" id="trigger_value"
                    class="form-control @error('trigger_value') is-invalid @enderror"
                    value="{{ old('trigger_value', $dripCampaign->trigger_value ?? '') }}"
                    placeholder="Ej: nombre-de-etiqueta o palabra clave">
                <div class="form-text">Tag, keyword u otro valor segun el disparador seleccionado</div>
                @error('trigger_value')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- Estado --}}
            <div class="col-12">
                <div class="form-check">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" class="form-check-input" name="is_active" value="1"
                        id="is_active"
                        @checked(old('is_active', $dripCampaign->is_active ?? true))>
                    <label class="form-check-label" for="is_active">
                        Campaña activa
                    </label>
                    <div class="form-text">Las campañas inactivas no se disparan aunque se cumplan las condiciones</div>
                </div>
            </div>

        </div>
    </div>

    {{-- Seccion 2: Pasos de la campaña --}}
    <div class="col-12">
        <h6 class="fw-bold border-bottom pb-2 mb-3">Pasos de la campaña</h6>

        <div id="steps-container">
            @if(isset($dripCampaign) && $dripCampaign->steps->count() > 0)
                @foreach($dripCampaign->steps as $i => $step)
                    <div class="card border mb-3 step-card" data-index="{{ $i }}">
                        <div class="card-header d-flex justify-content-between align-items-center py-2 px-3 bg-light">
                            <span class="fw-semibold small step-label">Paso {{ $i + 1 }}</span>
                            <button type="button" class="btn btn-sm btn-link text-muted p-0 btn-remove-step">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-12 col-md-3">
                                    <label class="form-label">Minutos de espera <span class="text-danger">*</span></label>
                                    <input type="number" name="steps[{{ $i }}][delay_minutes]"
                                        class="form-control @error("steps.{$i}.delay_minutes") is-invalid @enderror"
                                        value="{{ old("steps.{$i}.delay_minutes", $step->delay_minutes) }}"
                                        min="0" placeholder="0">
                                    @error("steps.{$i}.delay_minutes")
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-12 col-md-3">
                                    <label class="form-label">Canal <span class="text-danger">*</span></label>
                                    <select name="steps[{{ $i }}][channel]" class="form-select @error("steps.{$i}.channel") is-invalid @enderror">
                                        <option value="widget" @selected(old("steps.{$i}.channel", $step->channel) === 'widget')>Widget</option>
                                        <option value="email" @selected(old("steps.{$i}.channel", $step->channel) === 'email')>Email</option>
                                        <option value="whatsapp" @selected(old("steps.{$i}.channel", $step->channel) === 'whatsapp')>WhatsApp</option>
                                    </select>
                                    @error("steps.{$i}.channel")
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-12 col-md-3">
                                    <label class="form-label">Tipo de plantilla</label>
                                    <select name="steps[{{ $i }}][template_type]" class="form-select @error("steps.{$i}.template_type") is-invalid @enderror">
                                        <option value="text" @selected(old("steps.{$i}.template_type", $step->template_type) === 'text')>Texto libre</option>
                                        <option value="hsm" @selected(old("steps.{$i}.template_type", $step->template_type) === 'hsm')>HSM (WhatsApp)</option>
                                    </select>
                                    @error("steps.{$i}.template_type")
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Mensaje</label>
                                    <textarea name="steps[{{ $i }}][body]" rows="3"
                                        class="form-control @error("steps.{$i}.body") is-invalid @enderror"
                                        placeholder="Contenido del mensaje para este paso...">{{ old("steps.{$i}.body", $step->body) }}</textarea>
                                    @error("steps.{$i}.body")
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>

        <button type="button" id="btn-add-step" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-plus"></i> Agregar paso
        </button>

    </div>

</div>

{{-- Step template (hidden) --}}
<div id="step-template" class="d-none">
    <div class="card border mb-3 step-card" data-index="INDEX">
        <div class="card-header d-flex justify-content-between align-items-center py-2 px-3 bg-light">
            <span class="fw-semibold small step-label">Paso INDEX_DISPLAY</span>
            <button type="button" class="btn btn-sm btn-link text-muted p-0 btn-remove-step">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-12 col-md-3">
                    <label class="form-label">Minutos de espera <span class="text-danger">*</span></label>
                    <input type="number" name="steps[INDEX][delay_minutes]" class="form-control" value="0" min="0" placeholder="0">
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">Canal <span class="text-danger">*</span></label>
                    <select name="steps[INDEX][channel]" class="form-select">
                        <option value="widget">Widget</option>
                        <option value="email">Email</option>
                        <option value="whatsapp">WhatsApp</option>
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">Tipo de plantilla</label>
                    <select name="steps[INDEX][template_type]" class="form-select">
                        <option value="text">Texto libre</option>
                        <option value="hsm">HSM (WhatsApp)</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Mensaje</label>
                    <textarea name="steps[INDEX][body]" rows="3" class="form-control" placeholder="Contenido del mensaje para este paso..."></textarea>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function () {
    const triggerTypesWithValue = ['tag_added'];
    let stepCount = $('#steps-container .step-card').length;

    // Disparador: mostrar/ocultar campo de valor
    function toggleTriggerValue() {
        const type = $('#trigger_type').val();
        const needsValue = triggerTypesWithValue.includes(type);
        $('#trigger_value_wrapper').toggle(needsValue);
        if (!needsValue) {
            $('#trigger_value').val('');
        }
    }

    $('#trigger_type').on('change', toggleTriggerValue);
    toggleTriggerValue();

    // En create, inicializar con 1 paso vacio
    @if(!isset($dripCampaign))
    addStep();
    @endif

    // Agregar paso
    $('#btn-add-step').on('click', function () {
        addStep();
    });

    function addStep() {
        const template = $('#step-template').html();
        const html = template
            .replace(/INDEX_DISPLAY/g, stepCount + 1)
            .replace(/INDEX/g, stepCount);

        $('#steps-container').append(html);
        stepCount++;
        renumberSteps();
    }

    // Eliminar paso
    $(document).on('click', '.btn-remove-step', function () {
        $(this).closest('.step-card').remove();
        renumberSteps();
    });

    // Renumerar pasos tras agregar/eliminar
    function renumberSteps() {
        $('#steps-container .step-card').each(function (i) {
            $(this).attr('data-index', i);
            $(this).find('.step-label').text('Paso ' + (i + 1));
            $(this).find('input, select, textarea').each(function () {
                const name = $(this).attr('name');
                if (name) {
                    $(this).attr('name', name.replace(/steps\[\d+\]/, 'steps[' + i + ']'));
                }
            });
        });
        stepCount = $('#steps-container .step-card').length;
    }
});
</script>
@endpush
