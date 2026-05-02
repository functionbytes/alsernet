@extends('layouts.theme')

@section('title', isset($form) ? 'Editar formulario: ' . $form->name : 'Nuevo formulario pre-chat')

@section('page_header')
    @include('core::components.card', ['title' => isset($form) ? 'Editar formulario' : 'Nuevo formulario pre-chat'])
@endsection

@section('content')

    <div class="row g-3">

        {{-- Form --}}
        <div class="col-12 col-lg-8">
            <div class="card">
                <form action="{{ isset($form) ? route('settings.helpdesk-livechat.pre-chat-forms.update', $form->id) : route('settings.helpdesk-livechat.pre-chat-forms.store') }}"
                      method="POST">
                    @csrf
                    @if(isset($form))
                        @method('PUT')
                    @endif

                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">{{ isset($form) ? 'Editar: ' . $form->name : 'Nuevo formulario pre-chat' }}</h5>
                        <small class="text-muted">{{ isset($form) ? 'Modifica la configuracion del formulario' : 'Define los campos que el cliente vera antes de iniciar el chat' }}</small>
                    </div>

                    <div class="card-body">
                        @include('core::components.alerts')

                        <h6 class="fw-semibold mb-1 border-bottom pb-2">Informacion basica</h6>
                        <p class="text-muted small mb-3">Nombre e inbox al que aplica este formulario</p>
                        <div class="row g-3 mb-4">

                            <div class="col-12 col-md-7">
                                <div class="mb-3">
                                    <label class="form-label">Nombre <span class="text-danger">*</span></label>
                                    <input type="text" name="name"
                                           class="form-control @error('name') is-invalid @enderror"
                                           value="{{ old('name', $form->name ?? '') }}"
                                           placeholder="Ej: Formulario soporte tecnico"
                                           required>
                                    @error('name')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12 col-md-5">
                                <div class="mb-3">
                                    <label class="form-label">Bandeja (inbox)</label>
                                    <select name="inbox_id" class="form-select @error('inbox_id') is-invalid @enderror">
                                        <option value="">Global — aplica a todas</option>
                                        @foreach($inboxes as $inbox)
                                            <option value="{{ $inbox->id }}"
                                                {{ old('inbox_id', $form->inbox_id ?? '') == $inbox->id ? 'selected' : '' }}>
                                                {{ $inbox->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="form-text text-muted">Si no seleccionas una bandeja, aplica globalmente</small>
                                    @error('inbox_id')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12 col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Estado</label>
                                    <select name="is_active" class="form-select">
                                        <option value="1" {{ old('is_active', $form->is_active ?? true) ? 'selected' : '' }}>Activo</option>
                                        <option value="0" {{ !old('is_active', $form->is_active ?? true) ? 'selected' : '' }}>Inactivo</option>
                                    </select>
                                </div>
                            </div>

                        </div>

                        <h6 class="fw-semibold mb-1 border-bottom pb-2">Campos del formulario</h6>
                        <p class="text-muted small mb-3">Define los campos que se mostraran al cliente antes del chat</p>

                        <div id="fieldsContainer">
                            @php
                                $existingFields = old('fields', $form->fields ?? []);
                            @endphp

                            @if(!empty($existingFields))
                                @foreach($existingFields as $idx => $field)
                                    <div class="field-item card border mb-3">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start mb-3">
                                                <span class="fw-semibold small text-muted">Campo #{{ $idx + 1 }}</span>
                                                <button type="button" class="btn btn-sm btn-outline-secondary remove-field">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                            <div class="row g-2">
                                                <div class="col-12 col-md-3">
                                                    <label class="form-label form-label-sm">Clave</label>
                                                    <input type="text" name="fields[{{ $idx }}][key]"
                                                           class="form-control form-control-sm"
                                                           value="{{ $field['key'] ?? '' }}"
                                                           placeholder="ej: nombre" required>
                                                </div>
                                                <div class="col-12 col-md-5">
                                                    <label class="form-label form-label-sm">Etiqueta visible</label>
                                                    <input type="text" name="fields[{{ $idx }}][label]"
                                                           class="form-control form-control-sm"
                                                           value="{{ $field['label'] ?? '' }}"
                                                           placeholder="Ej: Tu nombre" required>
                                                </div>
                                                <div class="col-12 col-md-4">
                                                    <label class="form-label form-label-sm">Tipo de campo</label>
                                                    <select name="fields[{{ $idx }}][type]" class="form-select form-select-sm" required>
                                                        @foreach(['text' => 'Texto', 'email' => 'Email', 'phone' => 'Telefono', 'select' => 'Selector', 'textarea' => 'Area de texto', 'checkbox' => 'Casilla'] as $val => $lbl)
                                                            <option value="{{ $val }}" {{ ($field['type'] ?? '') === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-12">
                                                    <div class="form-check">
                                                        <input type="hidden" name="fields[{{ $idx }}][required]" value="0">
                                                        <input class="form-check-input" type="checkbox"
                                                               name="fields[{{ $idx }}][required]" value="1"
                                                               id="freq_{{ $idx }}"
                                                               {{ ($field['required'] ?? false) ? 'checked' : '' }}>
                                                        <label class="form-check-label small" for="freq_{{ $idx }}">Campo obligatorio</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                        </div>

                        @error('fields')
                            <div class="alert alert-danger py-2 small mb-3">{{ $message }}</div>
                        @enderror

                        <button type="button" id="addField" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-plus me-1"></i> Agregar campo
                        </button>

                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-1">
                            {{ isset($form) ? 'Guardar cambios' : 'Crear formulario' }}
                        </button>
                        <a href="{{ route('settings.helpdesk-livechat.pre-chat-forms.index') }}" class="btn btn-light w-100">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Help panel --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-3">Sobre los formularios pre-chat</h6>
                    <p class="card-text text-muted">
                        El formulario se muestra al cliente antes de iniciar una conversacion. Si asignas una bandeja especifica, solo aplica a esa bandeja.
                    </p>
                </div>
                <hr class="my-0">
                <div class="card-body">
                    <h6 class="card-title mb-3">Tipos de campo</h6>
                    <ul class="list-unstyled mb-0">
                        <li class="mb-1 text-muted small"><i class="fas fa-font me-2 text-muted"></i> <b>Texto</b> — campo de texto libre</li>
                        <li class="mb-1 text-muted small"><i class="fas fa-at me-2 text-muted"></i> <b>Email</b> — correo electronico</li>
                        <li class="mb-1 text-muted small"><i class="fas fa-phone me-2 text-muted"></i> <b>Telefono</b> — numero de telefono</li>
                        <li class="mb-1 text-muted small"><i class="fas fa-list me-2 text-muted"></i> <b>Selector</b> — lista desplegable</li>
                        <li class="mb-1 text-muted small"><i class="fas fa-align-left me-2 text-muted"></i> <b>Area de texto</b> — texto largo</li>
                        <li class="text-muted small"><i class="fas fa-check-square me-2 text-muted"></i> <b>Casilla</b> — si/no</li>
                    </ul>
                </div>
                @if(isset($form))
                    <hr class="my-0">
                    <div class="card-body">
                        <h6 class="card-title mb-3">Informacion del registro</h6>
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2 text-muted small">
                                <span class="fw-semibold">Creado:</span> {{ $form->created_at->format('d/m/Y H:i') }}
                            </li>
                            <li class="text-muted small">
                                <span class="fw-semibold">Actualizado:</span> {{ $form->updated_at->format('d/m/Y H:i') }}
                            </li>
                        </ul>
                    </div>
                @endif
            </div>
        </div>

    </div>

    {{-- Field template (hidden) --}}
    <template id="fieldTemplate">
        <div class="field-item card border mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <span class="fw-semibold small text-muted">Campo #<span class="field-number"></span></span>
                    <button type="button" class="btn btn-sm btn-outline-secondary remove-field">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="row g-2">
                    <div class="col-12 col-md-3">
                        <label class="form-label form-label-sm">Clave</label>
                        <input type="text" name="fields[__IDX__][key]"
                               class="form-control form-control-sm" placeholder="ej: nombre" required>
                    </div>
                    <div class="col-12 col-md-5">
                        <label class="form-label form-label-sm">Etiqueta visible</label>
                        <input type="text" name="fields[__IDX__][label]"
                               class="form-control form-control-sm" placeholder="Ej: Tu nombre" required>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label form-label-sm">Tipo de campo</label>
                        <select name="fields[__IDX__][type]" class="form-select form-select-sm" required>
                            <option value="text">Texto</option>
                            <option value="email">Email</option>
                            <option value="phone">Telefono</option>
                            <option value="select">Selector</option>
                            <option value="textarea">Area de texto</option>
                            <option value="checkbox">Casilla</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <div class="form-check">
                            <input type="hidden" name="fields[__IDX__][required]" value="0">
                            <input class="form-check-input" type="checkbox"
                                   name="fields[__IDX__][required]" value="1" id="freq___IDX__">
                            <label class="form-check-label small" for="freq___IDX__">Campo obligatorio</label>
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
    let fieldCount = {{ count($existingFields ?? []) }};

    $('#addField').on('click', function () {
        const template = document.getElementById('fieldTemplate').innerHTML;
        const html = template.replace(/__IDX__/g, fieldCount);
        const $el = $(html);
        $el.find('.field-number').text(fieldCount + 1);
        $('#fieldsContainer').append($el);
        fieldCount++;
    });

    $(document).on('click', '.remove-field', function () {
        $(this).closest('.field-item').remove();
        $('#fieldsContainer .field-item').each(function (i) {
            $(this).find('.field-number').text(i + 1);
        });
    });
});
</script>
@endpush
