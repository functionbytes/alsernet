@extends('layouts.theme')

@section('title', isset($survey) ? 'Editar encuesta: ' . $survey->name : 'Nueva encuesta')

@push('styles')
<style>.hd-icon-dot { font-size: 0.5rem; vertical-align: middle; }</style>
@endpush

@section('page_header')
    @include('core::components.card', ['title' => isset($survey) ? 'Editar encuesta' : 'Nueva encuesta'])
@endsection

@section('content')

    <div class="row g-3">

        {{-- Form --}}
        <div class="col-12 col-lg-8">
            <div class="card">
                <form action="{{ isset($survey) ? route('settings.helpdesk.surveys.update', $survey->id) : route('settings.helpdesk.surveys.store') }}"
                      method="POST" id="surveyForm">
                    @csrf
                    @if(isset($survey))
                        @method('PUT')
                    @endif

                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">{{ isset($survey) ? 'Editar: ' . $survey->name : 'Nueva encuesta' }}</h5>
                        <small class="text-muted">{{ isset($survey) ? 'Modifica la configuracion de la encuesta' : 'Define las preguntas y el disparo de la encuesta' }}</small>
                    </div>

                    <div class="card-body">
                        @include('core::components.alerts')

                        <h6 class="fw-semibold mb-1 border-bottom pb-2">Informacion basica</h6>
                        <p class="text-muted small mb-3">Nombre y condicion que activa el envio de la encuesta</p>
                        <div class="row g-3 mb-4">

                            <div class="col-12 col-md-7">
                                <div class="mb-3">
                                    <label class="form-label">Nombre <span class="text-danger">*</span></label>
                                    <input type="text" name="name"
                                           class="form-control @error('name') is-invalid @enderror"
                                           value="{{ old('name', $survey->name ?? '') }}"
                                           placeholder="Ej: Encuesta CSAT post-conversacion"
                                           required>
                                    @error('name')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12 col-md-5">
                                <div class="mb-3">
                                    <label class="form-label">Disparo <span class="text-danger">*</span></label>
                                    <select name="trigger_type" class="form-select @error('trigger_type') is-invalid @enderror" required>
                                        @foreach($triggerTypes as $value => $label)
                                            <option value="{{ $value }}" {{ old('trigger_type', $survey->trigger_type ?? '') === $value ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('trigger_type')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12 col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Estado</label>
                                    <select name="is_active" class="form-select">
                                        <option value="1" {{ old('is_active', $survey->is_active ?? true) ? 'selected' : '' }}>Activa</option>
                                        <option value="0" {{ !old('is_active', $survey->is_active ?? true) ? 'selected' : '' }}>Inactiva</option>
                                    </select>
                                </div>
                            </div>

                        </div>

                        <h6 class="fw-semibold mb-1 border-bottom pb-2">Preguntas</h6>
                        <p class="text-muted small mb-3">Define las preguntas que se incluiran en la encuesta</p>

                        <div id="questionsContainer">
                            @php
                                $existingQuestions = old('questions', $survey->questions ?? []);
                            @endphp

                            @if(!empty($existingQuestions))
                                @foreach($existingQuestions as $idx => $question)
                                    <div class="question-item card border mb-3">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start mb-3">
                                                <span class="fw-semibold small text-muted">Pregunta #{{ $idx + 1 }}</span>
                                                <button type="button" class="btn btn-sm btn-outline-secondary remove-question">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                            <input type="hidden" name="questions[{{ $idx }}][id]" value="{{ $question['id'] ?? \Illuminate\Support\Str::uuid() }}">
                                            <div class="row g-2">
                                                <div class="col-12 col-md-8">
                                                    <label class="form-label form-label-sm">Etiqueta</label>
                                                    <input type="text" name="questions[{{ $idx }}][label]"
                                                           class="form-control form-control-sm"
                                                           value="{{ $question['label'] ?? '' }}"
                                                           placeholder="Ej: ¿Como calificarias tu experiencia?"
                                                           required>
                                                </div>
                                                <div class="col-12 col-md-4">
                                                    <label class="form-label form-label-sm">Tipo</label>
                                                    <select name="questions[{{ $idx }}][type]" class="form-select form-select-sm" required>
                                                        @foreach($questionTypes as $value => $label)
                                                            <option value="{{ $value }}" {{ ($question['type'] ?? '') === $value ? 'selected' : '' }}>
                                                                {{ $label }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-12">
                                                    <div class="form-check">
                                                        <input type="hidden" name="questions[{{ $idx }}][required]" value="0">
                                                        <input class="form-check-input" type="checkbox"
                                                               name="questions[{{ $idx }}][required]" value="1"
                                                               id="req_{{ $idx }}"
                                                               {{ ($question['required'] ?? false) ? 'checked' : '' }}>
                                                        <label class="form-check-label small" for="req_{{ $idx }}">Respuesta obligatoria</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                        </div>

                        @error('questions')
                            <div class="alert alert-danger py-2 small mb-3">{{ $message }}</div>
                        @enderror

                        <button type="button" id="addQuestion" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-plus me-1"></i> Agregar pregunta
                        </button>

                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-1">
                            {{ isset($survey) ? 'Guardar cambios' : 'Crear encuesta' }}
                        </button>
                        <a href="{{ route('settings.helpdesk.surveys.index') }}" class="btn btn-light w-100">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Help panel --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-3">Sobre las encuestas</h6>
                    <p class="card-text text-muted">
                        Las encuestas se envian automaticamente a los clientes segun el evento de disparo configurado.
                    </p>
                </div>
                <hr class="my-0">
                <div class="card-body">
                    <h6 class="card-title mb-3">Tipos de pregunta</h6>
                    <ul class="list-unstyled mb-0">
                        @foreach($questionTypes as $value => $label)
                            <li class="mb-1 text-muted small"><i class="fas fa-circle text-primary me-2 hd-icon-dot"></i> {{ $label }}</li>
                        @endforeach
                    </ul>
                </div>
                @if(isset($survey))
                    <hr class="my-0">
                    <div class="card-body">
                        <h6 class="card-title mb-3">Informacion del registro</h6>
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2 text-muted small">
                                <span class="fw-semibold">Creada:</span> {{ $survey->created_at->format('d/m/Y H:i') }}
                            </li>
                            <li class="text-muted small">
                                <span class="fw-semibold">Actualizada:</span> {{ $survey->updated_at->format('d/m/Y H:i') }}
                            </li>
                        </ul>
                    </div>
                @endif
            </div>
        </div>

    </div>

    {{-- Question template (hidden) --}}
    <template id="questionTemplate">
        <div class="question-item card border mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <span class="fw-semibold small text-muted">Pregunta #<span class="question-number"></span></span>
                    <button type="button" class="btn btn-sm btn-outline-secondary remove-question">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <input type="hidden" name="questions[__IDX__][id]" value="">
                <div class="row g-2">
                    <div class="col-12 col-md-8">
                        <label class="form-label form-label-sm">Etiqueta</label>
                        <input type="text" name="questions[__IDX__][label]"
                               class="form-control form-control-sm"
                               placeholder="Ej: ¿Como calificarias tu experiencia?" required>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label form-label-sm">Tipo</label>
                        <select name="questions[__IDX__][type]" class="form-select form-select-sm" required>
                            @foreach($questionTypes as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <div class="form-check">
                            <input type="hidden" name="questions[__IDX__][required]" value="0">
                            <input class="form-check-input" type="checkbox"
                                   name="questions[__IDX__][required]" value="1" id="req___IDX__">
                            <label class="form-check-label small" for="req___IDX__">Respuesta obligatoria</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </template>

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    let questionCount = {{ count($existingQuestions ?? []) }};

    $('#addQuestion').on('click', function () {
        const template = document.getElementById('questionTemplate').innerHTML;
        const uuid = Math.random().toString(36).substr(2, 9);
        const html = template.replace(/__IDX__/g, questionCount).replace(/""/g, '"' + uuid + '"');
        const $el = $(html);
        $el.find('.question-number').text(questionCount + 1);
        $el.find('input[type="hidden"][name*="[id]"]').val(uuid);
        $('#questionsContainer').append($el);
        questionCount++;
    });

    $(document).on('click', '.remove-question', function () {
        $(this).closest('.question-item').remove();
        $('#questionsContainer .question-item').each(function (i) {
            $(this).find('.question-number').text(i + 1);
        });
    });
});
</script>
@endpush
