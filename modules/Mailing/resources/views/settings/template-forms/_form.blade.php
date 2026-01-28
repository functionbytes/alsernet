{{-- Template Form Builder Component --}}

<div class="row">
    <div class="col-12 col-md-8">
        {{-- Sección 1: Información básica --}}
        <h6 class="fw-bold mb-3 border-bottom pb-2">
            Información básica
        </h6>

        <div class="row mb-4">
            <div class="col-12 col-md-6">
                <div class="mb-3">
                    <label class="form-label fw-semibold">
                        Nombre del formulario <span class="text-danger">*</span>
                    </label>
                    <input type="text"
                           class="form-control @error('name') is-invalid @enderror"
                           name="name"
                           value="{{ old('name', $templateForm->name ?? '') }}"
                           maxlength="100"
                           placeholder="Ej: Formulario de Bienvenida"
                           required>
                    <small class="form-text text-muted d-block mt-1">Nombre interno para identificar el formulario</small>
                    @error('name')
                        <div class="invalid-feedback d-block mt-1">
                            <i class="fas fa-circle-exclamation me-1"></i>{{ $message }}
                        </div>
                    @enderror
                </div>
            </div>

            <div class="col-12 col-md-6">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Tipo de formulario</label>
                    <select class="form-select @error('type') is-invalid @enderror" name="type">
                        <option value="">Seleccionar tipo...</option>
                        <option value="pre-chat" {{ old('type', $templateForm->type ?? '') == 'pre-chat' ? 'selected' : '' }}>Pre-chat</option>
                        <option value="post-chat" {{ old('type', $templateForm->type ?? '') == 'post-chat' ? 'selected' : '' }}>Post-chat</option>
                        <option value="custom" {{ old('type', $templateForm->type ?? '') == 'custom' ? 'selected' : '' }}>Personalizado</option>
                    </select>
                    @error('type')
                        <div class="invalid-feedback d-block">
                            <i class="fas fa-circle-exclamation me-1"></i>{{ $message }}
                        </div>
                    @enderror
                </div>
            </div>

            <div class="col-12">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Descripción</label>
                    <textarea class="form-control @error('description') is-invalid @enderror"
                              name="description"
                              rows="2"
                              maxlength="255"
                              placeholder="Descripción breve del formulario">{{ old('description', $templateForm->description ?? '') }}</textarea>
                    <small class="form-text text-muted d-block mt-1">Información adicional sobre el propósito</small>
                    @error('description')
                        <div class="invalid-feedback d-block mt-1">
                            <i class="fas fa-circle-exclamation me-1"></i>{{ $message }}
                        </div>
                    @enderror
                </div>
            </div>
        </div>

        <hr class="my-4">

        {{-- Sección 2: Configuración del formulario --}}
        <h6 class="fw-bold mb-3 border-bottom pb-2">
            Configuración
        </h6>

        <div class="row mb-4">
            <div class="col-12 col-md-6">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Número de columnas</label>
                    <select class="form-select @error('columns') is-invalid @enderror" name="columns">
                        <option value="1" {{ old('columns', $templateForm->columns ?? 1) == 1 ? 'selected' : '' }}>1 columna</option>
                        <option value="2" {{ old('columns', $templateForm->columns ?? 1) == 2 ? 'selected' : '' }}>2 columnas</option>
                        <option value="3" {{ old('columns', $templateForm->columns ?? 1) == 3 ? 'selected' : '' }}>3 columnas</option>
                    </select>
                </div>
            </div>

            <div class="col-12 col-md-6">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Texto del botón</label>
                    <input type="text"
                           class="form-control @error('button_text') is-invalid @enderror"
                           name="button_text"
                           value="{{ old('button_text', $templateForm->button_text ?? 'Enviar') }}"
                           maxlength="50"
                           placeholder="Ej: Enviar">
                    @error('button_text')
                        <div class="invalid-feedback d-block mt-1">
                            <i class="fas fa-circle-exclamation me-1"></i>{{ $message }}
                        </div>
                    @enderror
                </div>
            </div>

            <div class="col-12">
                <div class="form-check form-switch">
                    <input class="form-check-input"
                           type="checkbox"
                           name="requires_email"
                           id="requiresEmail"
                           value="1"
                           {{ old('requires_email', $templateForm->requires_email ?? 0) ? 'checked' : '' }}>
                    <label class="form-check-label" for="requiresEmail">
                        Campo de email requerido
                    </label>
                </div>
            </div>
        </div>

        <hr class="my-4">

        {{-- Sección 3: Campos del formulario --}}
        <h6 class="fw-bold mb-3 border-bottom pb-2">
            Campos del formulario
        </h6>

        <div id="form-fields-container" class="mb-4">
            @if(isset($templateForm) && $templateForm->fields)
                @foreach(json_decode($templateForm->fields, true) ?? [] as $index => $field)
                    <div class="card card-sm mb-3 field-item" data-index="{{ $index }}">
                        <div class="card-body">
                            <div class="row g-2">
                                <div class="col-12 col-md-4">
                                    <label class="form-label form-label-sm">Nombre del campo</label>
                                    <input type="text" class="form-control form-control-sm field-name" 
                                           value="{{ $field['name'] ?? '' }}" placeholder="Ej: nombre">
                                </div>
                                <div class="col-12 col-md-4">
                                    <label class="form-label form-label-sm">Etiqueta</label>
                                    <input type="text" class="form-control form-control-sm field-label" 
                                           value="{{ $field['label'] ?? '' }}" placeholder="Ej: Nombre completo">
                                </div>
                                <div class="col-12 col-md-3">
                                    <label class="form-label form-label-sm">Tipo</label>
                                    <select class="form-select form-select-sm field-type">
                                        <option value="text" {{ ($field['type'] ?? '') == 'text' ? 'selected' : '' }}>Texto</option>
                                        <option value="email" {{ ($field['type'] ?? '') == 'email' ? 'selected' : '' }}>Email</option>
                                        <option value="phone" {{ ($field['type'] ?? '') == 'phone' ? 'selected' : '' }}>Teléfono</option>
                                        <option value="textarea" {{ ($field['type'] ?? '') == 'textarea' ? 'selected' : '' }}>Área de texto</option>
                                        <option value="select" {{ ($field['type'] ?? '') == 'select' ? 'selected' : '' }}>Seleccionador</option>
                                    </select>
                                </div>
                                <div class="col-12 col-md-1 d-flex align-items-end">
                                    <button type="button" class="btn btn-sm btn-light-danger w-100" onclick="removeField(this)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="mt-2">
                                <div class="form-check form-check-sm">
                                    <input class="form-check-input field-required" type="checkbox" 
                                           {{ ($field['required'] ?? false) ? 'checked' : '' }}>
                                    <label class="form-check-label form-label-sm">
                                        Requerido
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>

        <button type="button" class="btn btn-sm btn-light-primary" onclick="addField()">
            <i class="fas fa-plus me-1"></i>Agregar campo
        </button>
    </div>

    <div class="col-12 col-md-4">
        {{-- Panel lateral: Configuración adicional --}}
        <div class="card border-light">
            <div class="card-header bg-light border-bottom">
                <h6 class="mb-0 fw-bold">Opciones</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Estado</label>
                    <div class="form-check form-switch">
                        <input class="form-check-input"
                               type="checkbox"
                               name="is_active"
                               id="isActive"
                               value="1"
                               {{ old('is_active', $templateForm->is_active ?? 0) ? 'checked' : '' }}>
                        <label class="form-check-label" for="isActive">
                            Formulario activo
                        </label>
                    </div>
                    <small class="form-text text-muted d-block mt-1">Solo los formularios activos serán mostrados</small>
                </div>

                <hr>

                <div class="mb-3">
                    <p class="small text-muted mb-2"><strong>Información:</strong></p>
                    @if(isset($templateForm))
                        <ul class="list-unstyled small text-muted">
                            <li class="mb-1">
                                <i class="fas fa-calendar me-2"></i>
                                <strong>Creado:</strong> {{ $templateForm->created_at->format('d/m/Y H:i') }}
                            </li>
                            <li>
                                <i class="fas fa-clock me-2"></i>
                                <strong>Modificado:</strong> {{ $templateForm->updated_at->format('d/m/Y H:i') }}
                            </li>
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function addField() {
        const container = document.getElementById('form-fields-container');
        const index = container.children.length;
        const newField = document.createElement('div');
        newField.className = 'card card-sm mb-3 field-item';
        newField.setAttribute('data-index', index);
        newField.innerHTML = `
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-12 col-md-4">
                        <label class="form-label form-label-sm">Nombre del campo</label>
                        <input type="text" class="form-control form-control-sm field-name" placeholder="Ej: nombre">
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label form-label-sm">Etiqueta</label>
                        <input type="text" class="form-control form-control-sm field-label" placeholder="Ej: Nombre completo">
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label form-label-sm">Tipo</label>
                        <select class="form-select form-select-sm field-type">
                            <option value="text">Texto</option>
                            <option value="email">Email</option>
                            <option value="phone">Teléfono</option>
                            <option value="textarea">Área de texto</option>
                            <option value="select">Seleccionador</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-1 d-flex align-items-end">
                        <button type="button" class="btn btn-sm btn-light-danger w-100" onclick="removeField(this)">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                <div class="mt-2">
                    <div class="form-check form-check-sm">
                        <input class="form-check-input field-required" type="checkbox">
                        <label class="form-check-label form-label-sm">Requerido</label>
                    </div>
                </div>
            </div>
        `;
        container.appendChild(newField);
    }

    function removeField(btn) {
        btn.closest('.field-item').remove();
    }

    function serializeFields() {
        const fields = [];
        document.querySelectorAll('.field-item').forEach(item => {
            fields.push({
                name: item.querySelector('.field-name').value,
                label: item.querySelector('.field-label').value,
                type: item.querySelector('.field-type').value,
                required: item.querySelector('.field-required').checked
            });
        });
        return JSON.stringify(fields);
    }

    $(document).on('submit', '#formTemplateForm', function(e) {
        const fieldsInput = document.createElement('input');
        fieldsInput.type = 'hidden';
        fieldsInput.name = 'fields';
        fieldsInput.value = serializeFields();
        this.appendChild(fieldsInput);
    });
</script>
@endpush
