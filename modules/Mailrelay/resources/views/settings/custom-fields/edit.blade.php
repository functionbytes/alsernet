@extends('layouts.theme')

@section('title', 'Editar campo personalizado')

@section('head')
<style>
    .nav-tabs .nav-link {
        color: #6c757d;
        border: none;
        border-bottom: 2px solid transparent;
        padding: 0.75rem 1.25rem;
        transition: all 0.2s;
    }

    .nav-tabs .nav-link:hover {
        border-color: #dee2e6;
        color: #495057;
    }

    .nav-tabs .nav-link.active {
        color: #495057;
        background-color: transparent;
        border-color: transparent transparent #0d6efd;
        font-weight: 600;
    }

    .tab-content {
        padding: 1.5rem 0;
    }

    .info-card {
        background-color: #e7f3ff;
        border-left: 4px solid #0d6efd;
        padding: 1rem;
        border-radius: 0.25rem;
    }

    .option-item {
        display: flex;
        gap: 0.5rem;
        margin-bottom: 0.75rem;
        align-items: start;
    }

    .option-item input {
        flex: 1;
    }

    .btn-add-option {
        width: 100%;
        border-style: dashed;
    }

    .form-label .required {
        color: #dc3545;
    }

    .card-header {
        background-color: #f8f9fa;
        border-bottom: 2px solid #dee2e6;
    }

    .metadata-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 0.5rem;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .metadata-card .stat-label {
        opacity: 0.9;
        font-size: 0.875rem;
        margin-bottom: 0.25rem;
    }

    .metadata-card .stat-value {
        font-size: 1.25rem;
        font-weight: bold;
    }

    .sync-status-badge {
        padding: 0.5rem 1rem;
        border-radius: 0.25rem;
        display: inline-block;
        font-weight: 600;
    }
</style>
@endsection

@section('content')
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col">
            <h1 class="h2 mb-3">
                <i class="fas fa-edit"></i> Editar campo personalizado
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('mailrelay.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('mailrelay.settings.custom-fields.index') }}">Campos personalizados</a></li>
                    <li class="breadcrumb-item active">Editar</li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Metadata Card -->
    <div class="metadata-card">
        <div class="row">
            <div class="col-md-4">
                <div class="stat-label">
                    <i class="fas fa-calendar-plus"></i> Creado
                </div>
                <div class="stat-value">
                    {{ $customField->created_at->format('d/m/Y H:i') }}
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-label">
                    <i class="fas fa-calendar-edit"></i> Modificado
                </div>
                <div class="stat-value">
                    {{ $customField->updated_at->diffForHumans() }}
                </div>
                <small style="opacity: 0.8;">{{ $customField->updated_at->format('d/m/Y H:i') }}</small>
            </div>
            <div class="col-md-4">
                <div class="stat-label">
                    <i class="fas fa-users"></i> Uso
                </div>
                <div class="stat-value">
                    {{ $customField->subscribers_count ?? 0 }} suscriptores
                </div>
                @if($customField->subscribers_count > 0)
                <small style="opacity: 0.8;">Este campo está en uso</small>
                @endif
            </div>
        </div>
    </div>

    @if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle"></i> <strong>Error:</strong> Por favor corrige los errores a continuación.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        <ul class="mb-0 mt-2">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <form action="{{ route('mailrelay.settings.custom-fields.update', $customField) }}" method="POST">
        @csrf
        @method('PUT')

        <!-- Tabs Navigation -->
        <ul class="nav nav-tabs mb-3" id="fieldTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="basic-tab" data-bs-toggle="tab" data-bs-target="#basic" type="button" role="tab">
                    <i class="fas fa-info-circle"></i> Información básica
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="options-tab" data-bs-toggle="tab" data-bs-target="#options" type="button" role="tab">
                    <i class="fas fa-list"></i> Opciones de campo
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="validation-tab" data-bs-toggle="tab" data-bs-target="#validation" type="button" role="tab">
                    <i class="fas fa-check-circle"></i> Validación
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="integration-tab" data-bs-toggle="tab" data-bs-target="#integration" type="button" role="tab">
                    <i class="fas fa-sync-alt"></i> Integración Mailrelay
                </button>
            </li>
        </ul>

        <!-- Tabs Content -->
        <div class="tab-content" id="fieldTabsContent">
            <!-- Tab 1: Información básica -->
            <div class="tab-pane fade show active" id="basic" role="tabpanel">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h6 class="mb-0 fw-bold">Información básica del campo</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">
                                    Nombre del campo <span class="required">*</span>
                                </label>
                                <input
                                    type="text"
                                    class="form-control @error('name') is-invalid @enderror"
                                    id="name"
                                    name="name"
                                    value="{{ old('name', $customField->name) }}"
                                    required
                                    placeholder="Ej: Teléfono, Empresa, Ciudad"
                                >
                                @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Nombre visible que verán los usuarios</div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="key" class="form-label">
                                    Clave interna <span class="required">*</span>
                                </label>
                                <input
                                    type="text"
                                    class="form-control @error('key') is-invalid @enderror"
                                    id="key"
                                    name="key"
                                    value="{{ old('key', $customField->key) }}"
                                    required
                                    pattern="[a-z0-9_-]+"
                                    placeholder="Ej: phone, company, city"
                                >
                                @error('key')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Solo minúsculas, números, guiones y guiones bajos</div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">
                                Descripción
                            </label>
                            <textarea
                                class="form-control @error('description') is-invalid @enderror"
                                id="description"
                                name="description"
                                rows="2"
                                placeholder="Descripción del campo (opcional)"
                            >{{ old('description', $customField->description) }}</textarea>
                            @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="type" class="form-label">
                                    Tipo de campo <span class="required">*</span>
                                </label>
                                <select
                                    class="form-select @error('type') is-invalid @enderror"
                                    id="type"
                                    name="type"
                                    required
                                >
                                    <option value="">Seleccionar tipo...</option>
                                    <option value="text" {{ old('type', $customField->type) == 'text' ? 'selected' : '' }}>Text</option>
                                    <option value="email" {{ old('type', $customField->type) == 'email' ? 'selected' : '' }}>Email</option>
                                    <option value="number" {{ old('type', $customField->type) == 'number' ? 'selected' : '' }}>Number</option>
                                    <option value="date" {{ old('type', $customField->type) == 'date' ? 'selected' : '' }}>Date</option>
                                    <option value="select" {{ old('type', $customField->type) == 'select' ? 'selected' : '' }}>Select (Dropdown)</option>
                                    <option value="checkbox" {{ old('type', $customField->type) == 'checkbox' ? 'selected' : '' }}>Checkbox</option>
                                    <option value="radio" {{ old('type', $customField->type) == 'radio' ? 'selected' : '' }}>Radio Buttons</option>
                                </select>
                                @error('type')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="order" class="form-label">
                                    Orden de visualización
                                </label>
                                <input
                                    type="number"
                                    class="form-control @error('order') is-invalid @enderror"
                                    id="order"
                                    name="order"
                                    value="{{ old('order', $customField->order ?? 0) }}"
                                    min="0"
                                    step="1"
                                >
                                @error('order')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Orden en el que aparecerá el campo (menor = primero)</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab 2: Opciones de campo -->
            <div class="tab-pane fade" id="options" role="tabpanel">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h6 class="mb-0 fw-bold">Opciones del campo</h6>
                    </div>
                    <div class="card-body">
                        <div class="info-card mb-3" id="options-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Información:</strong> Las opciones solo se aplican a campos de tipo Select o Radio Buttons.
                        </div>

                        <div id="options-container">
                            <div class="mb-3">
                                <label class="form-label">Opciones disponibles</label>
                                <div id="options-list">
                                    @php
                                        $existingOptions = old('options', $customField->options ? json_decode($customField->options, true) : []);
                                    @endphp
                                    @if(is_array($existingOptions) && count($existingOptions) > 0)
                                        @foreach($existingOptions as $index => $option)
                                        <div class="option-item">
                                            <input type="text" class="form-control" name="options[{{ $index }}][value]" placeholder="Valor" value="{{ $option['value'] ?? '' }}" />
                                            <input type="text" class="form-control" name="options[{{ $index }}][label]" placeholder="Etiqueta" value="{{ $option['label'] ?? '' }}" />
                                            <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeOption(this)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                        @endforeach
                                    @else
                                        <div class="option-item">
                                            <input type="text" class="form-control" name="options[0][value]" placeholder="Valor" />
                                            <input type="text" class="form-control" name="options[0][label]" placeholder="Etiqueta" />
                                            <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeOption(this)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    @endif
                                </div>
                                <button type="button" class="btn btn-outline-primary btn-add-option mt-2" onclick="addOption()">
                                    <i class="fas fa-plus"></i> Agregar opción
                                </button>
                            </div>
                        </div>

                        <div class="alert alert-warning d-none" id="no-options-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            Este tipo de campo no requiere opciones.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab 3: Validación -->
            <div class="tab-pane fade" id="validation" role="tabpanel">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h6 class="mb-0 fw-bold">Reglas de validación</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="form-check form-switch">
                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        id="is_required"
                                        name="is_required"
                                        value="1"
                                        {{ old('is_required', $customField->is_required) ? 'checked' : '' }}
                                    >
                                    <label class="form-check-label" for="is_required">
                                        <strong>Campo requerido</strong>
                                        <br>
                                        <small class="text-muted">Los usuarios deben completar este campo</small>
                                    </label>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="form-check form-switch">
                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        id="is_unique"
                                        name="is_unique"
                                        value="1"
                                        {{ old('is_unique', $customField->is_unique) ? 'checked' : '' }}
                                    >
                                    <label class="form-check-label" for="is_unique">
                                        <strong>Valor único</strong>
                                        <br>
                                        <small class="text-muted">No permitir valores duplicados</small>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="row" id="text-validation-options">
                            <div class="col-md-6 mb-3">
                                <label for="min_length" class="form-label">
                                    Longitud mínima
                                </label>
                                <input
                                    type="number"
                                    class="form-control @error('min_length') is-invalid @enderror"
                                    id="min_length"
                                    name="min_length"
                                    value="{{ old('min_length', $customField->min_length) }}"
                                    min="0"
                                    placeholder="Ej: 3"
                                >
                                @error('min_length')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Número mínimo de caracteres (solo para campos de texto)</div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="max_length" class="form-label">
                                    Longitud máxima
                                </label>
                                <input
                                    type="number"
                                    class="form-control @error('max_length') is-invalid @enderror"
                                    id="max_length"
                                    name="max_length"
                                    value="{{ old('max_length', $customField->max_length) }}"
                                    min="1"
                                    placeholder="Ej: 100"
                                >
                                @error('max_length')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Número máximo de caracteres (solo para campos de texto)</div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="regex_pattern" class="form-label">
                                Patrón regex (opcional)
                            </label>
                            <input
                                type="text"
                                class="form-control @error('regex_pattern') is-invalid @enderror"
                                id="regex_pattern"
                                name="regex_pattern"
                                value="{{ old('regex_pattern', $customField->regex_pattern) }}"
                                placeholder="Ej: ^[0-9]{9}$ para teléfonos de 9 dígitos"
                            >
                            @error('regex_pattern')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Expresión regular para validación personalizada</div>
                        </div>

                        <div class="mb-3">
                            <label for="regex_cue" class="form-label">
                                Mensaje de error personalizado
                            </label>
                            <input
                                type="text"
                                class="form-control @error('regex_cue') is-invalid @enderror"
                                id="regex_cue"
                                name="regex_cue"
                                value="{{ old('regex_cue', $customField->regex_cue) }}"
                                placeholder="Ej: El teléfono debe tener 9 dígitos"
                            >
                            @error('regex_cue')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Mensaje que verán los usuarios si la validación falla</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab 4: Integración Mailrelay -->
            <div class="tab-pane fade" id="integration" role="tabpanel">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h6 class="mb-0 fw-bold">Configuración de sincronización con Mailrelay</h6>
                    </div>
                    <div class="card-body">
                        @if($customField->sync_with_mailrelay && $customField->mailrelay_field_id)
                        <div class="alert alert-success mb-4">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-check-circle"></i>
                                    <strong>Campo sincronizado</strong>
                                    <br>
                                    <small>ID Mailrelay: <code>{{ $customField->mailrelay_field_id }}</code></small>
                                    @if($customField->last_synced_at)
                                    <br>
                                    <small>Última sincronización: {{ $customField->last_synced_at->format('d/m/Y H:i') }} ({{ $customField->last_synced_at->diffForHumans() }})</small>
                                    @endif
                                </div>
                                <form action="{{ route('mailrelay.settings.custom-fields.sync', $customField) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-primary">
                                        <i class="fas fa-sync"></i> Sincronizar ahora
                                    </button>
                                </form>
                            </div>
                        </div>
                        @else
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Sincronización con Mailrelay:</strong>
                            Si activas la sincronización, este campo se enviará automáticamente a Mailrelay cuando los usuarios se suscriban o actualicen sus datos.
                        </div>
                        @endif

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    id="sync_with_mailrelay"
                                    name="sync_with_mailrelay"
                                    value="1"
                                    {{ old('sync_with_mailrelay', $customField->sync_with_mailrelay) ? 'checked' : '' }}
                                >
                                <label class="form-check-label" for="sync_with_mailrelay">
                                    <strong>Sincronizar con Mailrelay</strong>
                                    <br>
                                    <small class="text-muted">Enviar este campo a Mailrelay automáticamente</small>
                                </label>
                            </div>
                        </div>

                        <div id="mailrelay-options">
                            <div class="mb-3">
                                <label for="mailrelay_field_id" class="form-label">
                                    ID de campo en Mailrelay
                                </label>
                                <input
                                    type="text"
                                    class="form-control @error('mailrelay_field_id') is-invalid @enderror"
                                    id="mailrelay_field_id"
                                    name="mailrelay_field_id"
                                    value="{{ old('mailrelay_field_id', $customField->mailrelay_field_id) }}"
                                    placeholder="Ej: field_123"
                                    {{ $customField->mailrelay_field_id ? 'readonly' : '' }}
                                >
                                @error('mailrelay_field_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">
                                    @if($customField->mailrelay_field_id)
                                    Este campo ya está vinculado con Mailrelay. No se puede modificar el ID.
                                    @else
                                    ID del campo correspondiente en Mailrelay. Si no lo conoces, déjalo vacío y se creará automáticamente.
                                    @endif
                                </div>
                            </div>

                            @if(!$customField->mailrelay_field_id)
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                <strong>Nota:</strong> Si dejas el ID vacío, el sistema intentará crear el campo en Mailrelay automáticamente la primera vez que se sincronice un suscriptor con este campo.
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="d-flex gap-2 mt-4">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Guardar cambios
            </button>
            <a href="{{ route('mailrelay.settings.custom-fields.index') }}" class="btn btn-secondary">
                <i class="fas fa-times"></i> Cancelar
            </a>
            @if($customField->sync_with_mailrelay && $customField->mailrelay_field_id)
            <button type="button" class="btn btn-info" onclick="document.getElementById('sync-form').submit()">
                <i class="fas fa-sync"></i> Sincronizar ahora
            </button>
            @endif
        </div>
    </form>

    @if($customField->sync_with_mailrelay && $customField->mailrelay_field_id)
    <!-- Hidden sync form -->
    <form id="sync-form" action="{{ route('mailrelay.settings.custom-fields.sync', $customField) }}" method="POST" style="display: none;">
        @csrf
    </form>
    @endif
</div>
@endsection

@section('scripts')
<script>
    let optionIndex = {{ is_array($existingOptions ?? []) ? count($existingOptions ?? []) : 1 }};

    // Toggle options section based on field type
    document.getElementById('type').addEventListener('change', function() {
        const type = this.value;
        const optionsContainer = document.getElementById('options-container');
        const noOptionsWarning = document.getElementById('no-options-warning');
        const textValidation = document.getElementById('text-validation-options');

        if (type === 'select' || type === 'radio') {
            optionsContainer.classList.remove('d-none');
            noOptionsWarning.classList.add('d-none');
        } else {
            optionsContainer.classList.add('d-none');
            noOptionsWarning.classList.remove('d-none');
        }

        // Show/hide text validation options
        if (type === 'text' || type === 'email') {
            textValidation.style.display = 'flex';
        } else {
            textValidation.style.display = 'none';
        }
    });

    // Toggle Mailrelay options
    document.getElementById('sync_with_mailrelay').addEventListener('change', function() {
        const mailrelayOptions = document.getElementById('mailrelay-options');
        if (this.checked) {
            mailrelayOptions.style.display = 'block';
        } else {
            mailrelayOptions.style.display = 'none';
        }
    });

    // Auto-generate key from name (only if key is empty)
    document.getElementById('name').addEventListener('input', function() {
        const keyInput = document.getElementById('key');
        // Only auto-generate if the key field is empty or was auto-generated
        if (!keyInput.value || keyInput.dataset.autogenerated === 'true') {
            const key = this.value
                .toLowerCase()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .replace(/[^a-z0-9\s-]/g, '')
                .replace(/\s+/g, '_')
                .replace(/-+/g, '_')
                .substring(0, 50);

            keyInput.value = key;
            keyInput.dataset.autogenerated = 'true';
        }
    });

    // Mark manual key changes
    document.getElementById('key').addEventListener('input', function() {
        this.dataset.autogenerated = 'false';
    });

    function addOption() {
        const optionsList = document.getElementById('options-list');
        const optionHtml = `
            <div class="option-item">
                <input type="text" class="form-control" name="options[${optionIndex}][value]" placeholder="Valor" />
                <input type="text" class="form-control" name="options[${optionIndex}][label]" placeholder="Etiqueta" />
                <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeOption(this)">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        `;
        optionsList.insertAdjacentHTML('beforeend', optionHtml);
        optionIndex++;
    }

    function removeOption(button) {
        const optionItem = button.closest('.option-item');
        const optionsList = document.getElementById('options-list');

        // Keep at least one option
        if (optionsList.children.length > 1) {
            optionItem.remove();
        } else {
            alert('Debe haber al menos una opción disponible');
        }
    }

    // Initialize visibility on page load
    document.addEventListener('DOMContentLoaded', function() {
        const typeSelect = document.getElementById('type');
        if (typeSelect.value) {
            typeSelect.dispatchEvent(new Event('change'));
        }

        const syncCheckbox = document.getElementById('sync_with_mailrelay');
        if (syncCheckbox.checked) {
            syncCheckbox.dispatchEvent(new Event('change'));
        } else {
            document.getElementById('mailrelay-options').style.display = 'none';
        }
    });
</script>
@endsection
