@extends('layouts.theme')

@section('title', isset($automation) ? 'Editar automatización' : 'Nueva automatización')

@section('page_header')
    @include('core::components.card', ['title' => isset($automation) ? 'Editar automatización' : 'Nueva automatización'])
@endsection

@section('content')

    @include('core::components.alerts')

    <form action="{{ isset($automation) ? route('remarketing.automations.update', $automation) : route('remarketing.automations.store') }}"
          method="POST" id="automationForm">
        @csrf
        @if(isset($automation)) @method('PUT') @endif
        <input type="hidden" name="steps_json" id="stepsJson">

        <div class="row g-3">

            {{-- Left: general settings --}}
            <div class="col-12 col-lg-4">
                <div class="card">
                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Configuración general</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">

                            <div class="col-12">
                                <label class="form-label">Nombre <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                       value="{{ old('name', $automation->name ?? '') }}" required>
                                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label">Tienda <span class="text-danger">*</span></label>
                                <select name="store_id" class="form-select @error('store_id') is-invalid @enderror">
                                    <option value="">Selecciona una tienda</option>
                                    @foreach($stores as $store)
                                        <option value="{{ $store->id }}" {{ old('store_id', $automation->store_id ?? '') == $store->id ? 'selected' : '' }}>
                                            {{ $store->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('store_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label">Disparador <span class="text-danger">*</span></label>
                                <select name="trigger" class="form-select @error('trigger') is-invalid @enderror">
                                    <option value="">Selecciona un disparador</option>
                                    <option value="welcome"        {{ old('trigger', $automation->trigger ?? '') === 'welcome'        ? 'selected' : '' }}>Bienvenida (nuevo suscriptor)</option>
                                    <option value="cart_abandoned"  {{ old('trigger', $automation->trigger ?? '') === 'cart_abandoned'  ? 'selected' : '' }}>Carrito abandonado</option>
                                    <option value="post_purchase"   {{ old('trigger', $automation->trigger ?? '') === 'post_purchase'   ? 'selected' : '' }}>Post-compra</option>
                                    <option value="win_back"        {{ old('trigger', $automation->trigger ?? '') === 'win_back'        ? 'selected' : '' }}>Recuperar cliente (win-back)</option>
                                </select>
                                @error('trigger') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label">Estado</label>
                                <select name="status" class="form-select">
                                    <option value="draft"  {{ old('status', $automation->status ?? 'draft')  === 'draft'  ? 'selected' : '' }}>Borrador</option>
                                    <option value="active" {{ old('status', $automation->status ?? '') === 'active' ? 'selected' : '' }}>Activa</option>
                                    <option value="paused" {{ old('status', $automation->status ?? '') === 'paused' ? 'selected' : '' }}>Pausada</option>
                                </select>
                            </div>

                            <div class="col-12">
                                <hr class="my-2">
                                <h6 class="mb-1">Goal (evento de salida)</h6>
                                <p class="text-muted small mb-2">
                                    Si el cliente realiza este evento durante la ventana, la automatización termina y no recibe más mensajes.
                                </p>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Evento goal</label>
                                <input type="text" name="goal_event" class="form-control"
                                       value="{{ old('goal_event', $automation->goal_event ?? '') }}"
                                       placeholder="purchase, signup, custom_event">
                                <div class="form-text">Nombre del evento (deja vacío para sin goal).</div>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Ventana (horas)</label>
                                <input type="number" name="goal_window_hours" class="form-control"
                                       value="{{ old('goal_window_hours', $automation->goal_window_hours ?? '') }}"
                                       min="1" max="8760">
                                <div class="form-text">Vacío = sin límite temporal.</div>
                            </div>

                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-2">
                            <i class="fas fa-save me-1"></i>
                            {{ isset($automation) ? 'Guardar cambios' : 'Crear automatización' }}
                        </button>
                        <a href="{{ route('remarketing.automations.index') }}" class="btn btn-light w-100">Cancelar</a>
                    </div>
                </div>
            </div>

            {{-- Right: steps editor --}}
            <div class="col-12 col-lg-8">
                <div class="card">
                    <div class="card-header border-bottom p-3 d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0 fw-bold">Pasos de la automatización</h5>
                            <small class="text-muted">Los pasos se ejecutan en orden de arriba a abajo</small>
                        </div>
                        <span class="badge bg-secondary-subtle text-secondary" id="steps-count">0 pasos</span>
                    </div>
                    <div class="card-body">
                        <div id="steps-container">
                            {{-- Steps rendered here by JS --}}
                        </div>
                        <button type="button" id="btn-add-step" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-plus me-1"></i> Añadir paso
                        </button>
                    </div>
                </div>
            </div>

        </div>

    </form>

    {{-- Hidden step template for cloning --}}
    <div id="tmpl-step-row" class="d-none">
        @include('remarketing::partials.step-row')
    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {

    var stepsContainer = $('#steps-container');

    function updateStepsCount() {
        var count = stepsContainer.find('.step-row').length;
        $('#steps-count').text(count + ' paso' + (count !== 1 ? 's' : ''));
    }

    function addStep(data) {
        var $step = $('#tmpl-step-row .step-row').clone();
        var idx = Date.now() + Math.random();
        $step.attr('data-step-index', idx);

        if (data) {
            $step.find('.step-type').val(data.type);
            toggleStepConfig($step, data.type);
            if (data.type === 'wait' && data.config) {
                $step.find('.step-wait-hours').val(data.config.hours || 24);
            }
            if (data.type === 'send_email' && data.config) {
                $step.find('.step-template').val(data.config.template_id || '');
                $step.find('.step-subject').val(data.config.subject || '');
            }
        }

        stepsContainer.append($step);
        updateStepsCount();
    }

    function toggleStepConfig($step, type) {
        if (type === 'wait') {
            $step.find('.step-config-wait').removeClass('d-none');
            $step.find('.step-config-email').addClass('d-none');
        } else {
            $step.find('.step-config-wait').addClass('d-none');
            $step.find('.step-config-email').removeClass('d-none');
        }
    }

    $('#btn-add-step').on('click', function () { addStep(null); });

    $(document).on('change', '.step-type', function () {
        toggleStepConfig($(this).closest('.step-row'), $(this).val());
    });

    $(document).on('click', '.btn-remove-step', function () {
        $(this).closest('.step-row').remove();
        updateStepsCount();
    });

    $(document).on('click', '.btn-step-up', function () {
        var $row = $(this).closest('.step-row');
        var $prev = $row.prev('.step-row');
        if ($prev.length) { $row.insertBefore($prev); }
    });

    $(document).on('click', '.btn-step-down', function () {
        var $row = $(this).closest('.step-row');
        var $next = $row.next('.step-row');
        if ($next.length) { $row.insertAfter($next); }
    });

    // Serialize steps before submit
    $('#automationForm').on('submit', function () {
        var steps = [];
        stepsContainer.find('.step-row').each(function (i) {
            var type = $(this).find('.step-type').val();
            var config = {};
            if (type === 'wait') {
                config.hours = parseInt($(this).find('.step-wait-hours').val(), 10) || 24;
            } else {
                config.template_id = $(this).find('.step-template').val();
                config.subject      = $(this).find('.step-subject').val();
            }
            steps.push({ sort_order: i, type: type, config: config });
        });
        $('#stepsJson').val(JSON.stringify(steps));
    });

    // Load existing steps on edit
    @if(isset($automation) && $automation->steps && $automation->steps->count() > 0)
        var existingSteps = @json($automation->steps->sortBy('sort_order')->values());
        existingSteps.forEach(function (s) {
            addStep({ type: s.type, config: s.config });
        });
    @else
        // Add default first step
        addStep({ type: 'wait', config: { hours: 1 } });
        addStep({ type: 'send_email', config: {} });
    @endif

});
</script>
@endpush
