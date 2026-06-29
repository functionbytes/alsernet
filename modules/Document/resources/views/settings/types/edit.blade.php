@extends('layouts.theme')

@section('content')
    <div class="row">
        <div class="col-lg-12 d-flex align-items-stretch">
            <div class="card w-100">
                <form id="formDocumentType" action="{{ route('settings.documents.types.update', $documentType->slug) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <div class="card-header border-bottom p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-1 fw-bold">Editar tipo de documento</h5>
                                <p class="mb-0 text-muted small">Actualice la información del tipo de documento.</p>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        @include('core::components.alerts')

                        <!-- Tabs Navigation -->
                        <ul class="nav nav-tabs mb-4" id="documentTypeTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active"
                                        id="basic-info-tab"
                                        data-bs-toggle="tab"
                                        data-bs-target="#basic-info"
                                        type="button" role="tab">
                                    Información básica
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link"
                                        id="validation-stages-tab"
                                        data-bs-toggle="tab"
                                        data-bs-target="#validation-stages"
                                        type="button" role="tab">
                                    Etapas de validación
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link"
                                        id="requirements-tab"
                                        data-bs-toggle="tab"
                                        data-bs-target="#requirements"
                                        type="button" role="tab">
                                    Requisitos de documentos
                                </button>
                            </li>
                        </ul>

                        <!-- Tabs Content -->
                        <div class="tab-content" id="documentTypeTabsContent">
                            <!-- Tab 1: Información Básica -->
                            <div class="tab-pane fade show active"
                                 id="basic-info"
                                 role="tabpanel">
                                <div class="card-body">

                        {{-- Basic Information Section --}}
                        <div class="col-12">
                            <h5 class="fw-bold mb-0">
                                Configuración básica
                            </h5>
                            <p class="text-muted mb-4">Información general del tipo de documento.</p>
                        </div>

                        <div class="row">


                            <div class="col-12 col-md-12">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Etiqueta</label>
                                    <input type="text" class="form-control @error('label') is-invalid @enderror"
                                           name="label" value="{{ old('label', $documentType->label) }}"
                                           placeholder="ej: Documento de Identidad">
                                    <small class="form-text text-muted">Etiqueta mostrada en la interfaz</small>
                                    @error('label')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12 col-md-12">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Descripción</label>
                                    <textarea class="form-control @error('description') is-invalid @enderror"
                                              name="description" rows="3"
                                              placeholder="Descripción del tipo de documento">{{ old('description', $documentType->description) }}</textarea>
                                    <small class="form-text text-muted">Proporciona información adicional sobre este tipo de documento</small>
                                    @error('description')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">
                                        Slug <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control @error('slug') is-invalid @enderror"
                                           name="slug" value="{{ old('slug', $documentType->slug) }}"
                                           pattern="[a-z0-9_\-]+" required>
                                    <small class="form-text text-muted">Identificador único, solo minúsculas, números y guiones</small>
                                    @error('slug')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Estado</label>
                                    <select class="form-select select2  select2 @error('is_active') is-invalid @enderror" name="is_active">
                                        <option value="1" {{ old('is_active', $documentType->is_active) == 1 ? 'selected' : '' }}>Activo</option>
                                        <option value="0" {{ old('is_active', $documentType->is_active) == 0 ? 'selected' : '' }}>Inactivo</option>
                                    </select>
                                    @error('is_active')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Icono (Font Awesome)</label>
                                    <input type="text" class="form-control @error('icon') is-invalid @enderror"
                                           id="icon" name="icon" value="{{ old('icon', $documentType->icon) }}"
                                           placeholder="fa fa-file">
                                    <small class="form-text text-muted">Vista previa: <i id="iconPreview" class="{{ old('icon', $documentType->icon) }} ms-1"></i></small>
                                    @error('icon')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Color</label>
                                    <input type="color" class="form-control form-control-color @error('color') is-invalid @enderror"
                                           name="color" value="{{ old('color', $documentType->color) }}"
                                           style="height: 38px; width: 100%;">
                                    @error('color')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Orden de visualización</label>
                                    <input type="number" class="form-control @error('sort_order') is-invalid @enderror"
                                           name="sort_order" value="{{ old('sort_order', $documentType->sort_order) }}"
                                           min="0" placeholder="0">
                                    <small class="form-text text-muted">Menor número aparece primero</small>
                                    @error('sort_order')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Multiplicador SLA</label>
                                    <input type="number" class="form-control @error('sla_multiplier') is-invalid @enderror"
                                           name="sla_multiplier" value="{{ old('sla_multiplier', $documentType->sla_multiplier) }}"
                                           min="0" max="100" step="0.1" placeholder="1.0">
                                    <small class="form-text text-muted">Factor de tiempo para SLA</small>
                                    @error('sla_multiplier')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Associated PrestaShop Labels Section -->
                        <div class="row mt-5 border-top pt-4">
                            <div class="col-12">
                                <h5 class="fw-bold mb-0">
                                    Etiquetas de PrestaShop
                                </h5>
                                <p class="text-muted mb-4">Configura las etiquetas de PrestaShop que corresponden a este tipo de documento. Los productos con estas etiquetas requerirán este documento.</p>
                            </div>

                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Etiquetas asociadas</label>
                                    <input type="text" class="form-control @error('associated_labels') is-invalid @enderror"
                                           name="associated_labels"
                                           value="{{ old('associated_labels', implode(', ', $documentType->associated_labels ?? [])) }}"
                                           placeholder="Ej: DNI, CORTA, BALINES45, BALINES55">
                                    <small class="form-text text-muted">Ingresa las etiquetas separadas por comas. Se convertirán a mayúsculas automáticamente. Después de guardar, ejecuta la sincronización desde el panel de bloqueos.</small>
                                    @error('associated_labels')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            @if(!empty($documentType->associated_labels))
                                <div class="col-12">
                                    <div class="d-flex flex-wrap gap-2">
                                        @foreach($documentType->associated_labels as $label)
                                            <span class="badge bg-primary">{{ $label }}</span>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>

                        </div>

                            </div>
                            <!-- End Tab 1: Información Básica -->

                            <!-- Tab 2: Etapas de Validación -->
                            <div class="tab-pane fade"
                                 id="validation-stages"
                                 role="tabpanel">

                                <div class=" ">

                                    <div class="card-body">

                                        <div class="col-12">
                                            <div class="d-flex justify-content-between align-items-center mb-4">
                                                <div>
                                                    <h5 class="fw-bold mb-0">
                                                        Etapas de validación
                                                    </h5>
                                                    <p class="text-muted mb-0">Configura el flujo de validación con orden y condiciones.</p>
                                                </div>
                                                @if(!$validatorGroups->isEmpty())
                                                    <button type="button" class="btn btn-sm btn-primary" id="addStageBtn">
                                                        <i class="fas fa-plus me-1"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </div>

                                            <div id="validationStagesContainer" class="mb-3">
                                                @if($validatorGroups->isEmpty())
                                                    <div class="alert bg-warning-subtle border-warning">
                                                        <h6 class="mb-0 fw-semibold">No hay grupos validadores configurados</h6>
                                                        <p class="mb-0">
                                                            <a href="{{ route('settings.documents.groups.index') }}" class="alert-link">Crear grupos primero</a>
                                                        </p>
                                                    </div>
                                                @else
                                                    @php
                                                        // Get existing stages with conditions
                                                        $existingStages = $documentType->getValidationStagesWithConditions();
                                                        $stagesMap = collect($existingStages)->keyBy('key');
                                                    @endphp

                                                    <div id="stagesList">
                                                        @foreach($validatorGroups as $index => $group)
                                                            @php
                                                                $existingStage = $stagesMap->get($group->key);
                                                                $isEnabled = $existingStage !== null;
                                                            @endphp

                                                            @if($isEnabled)
                                                                @php
                                                                    $order = $existingStage['order'] ?? ($index + 1);
                                                                    $conditions = $existingStage['conditions'] ?? [];
                                                                    $selectedConditions = array_keys(array_filter($conditions));
                                                                @endphp

                                                        <div class="card mb-3 stage-item" data-key="{{ $group->key }}" data-name="{{ $group->name }}" data-description="{{ $group->description ?? '' }}">
                                                            <div class="card-header border-bottom">
                                                                <div class="d-flex align-items-center justify-content-between">
                                                                    <div>
                                                                        <h6 class="mb-0 fw-semibold">
                                                                            {{ $group->name }}
                                                                        </h6>
                                                                        @if($group->description)
                                                                            <small class="text-muted">{{ $group->description }}</small>
                                                                        @endif
                                                                    </div>
                                                                    <div class="d-flex align-items-center gap-2">
                                                                        <span class="badge bg-primary text-white">{{ $group->key }}</span>
                                                                        <button type="button" class="btn btn-sm btn-secondary remove-stage-btn">
                                                                            <i class="fas fa-trash-alt"></i>
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <div class="card-body">
                                                                <div class="row g-3">
                                                                    <!-- Orden -->
                                                                    <div class="col-md-12 col-sm-12 mt-0">
                                                                        <div class="mb-3">
                                                                            <label class="control-label col-form-label">Orden</label>
                                                                            <input type="number"
                                                                                   class="form-control stage-order"
                                                                                   name="stage_order_{{ $group->key }}"
                                                                                   value="{{ $order }}"
                                                                                   min="1"
                                                                                   placeholder="1">
                                                                            <small class="form-text text-muted">Menor número = mayor prioridad</small>
                                                                        </div>
                                                                    </div>

                                                                    <!-- Condiciones -->
                                                                    <div class="col-md-12 mt-0">
                                                                            <label class="control-label col-form-label">Condiciones de ejecución</label>

                                                                            @if($validationConditions->isEmpty())
                                                                                <div class="alert bg-warning-subtle border-warning mb-0">
                                                                                    <h6 class="mb-0 fw-semibold">No hay condiciones configuradas</h6>
                                                                                    <p class="mb-0">
                                                                                        <a href="{{ route('settings.documents.conditions.index') }}" class="alert-link">Crear condiciones</a>
                                                                                    </p>
                                                                                </div>
                                                                        @else
                                                                            <select multiple
                                                                                    class="form-select select2 select2 select2-conditions stage-conditions-select"
                                                                                    id="conditions_{{ $group->key }}"
                                                                                    name="stage_conditions_{{ $group->key }}[]"
                                                                                    data-key="{{ $group->key }}"
                                                                                    data-selected-conditions="{{ json_encode($conditions) }}"
                                                                                    data-placeholder="Seleccionar condiciones para ejecutar esta etapa...">
                                                                                @foreach($validationConditions as $validationCondition)
                                                                                    <option value="{{ $validationCondition->key }}"
                                                                                            data-description="{{ $validationCondition->description ?? '' }}"
                                                                                            data-sale-types="{{ is_array($validationCondition->sale_types) && !empty($validationCondition->sale_types) ? implode(', ', $validationCondition->sale_types) : '' }}"
                                                                                            {{ in_array($validationCondition->key, $selectedConditions) ? 'selected' : '' }}>
                                                                                        {{ $validationCondition->name }} ({{ $validationCondition->key }})
                                                                                    </option>
                                                                                @endforeach
                                                                            </select>
                                                                            <small class="form-text text-muted d-block mt-2">
                                                                                Sin condiciones = la etapa siempre se ejecuta. Con condiciones = solo se ejecuta si se cumplen.
                                                                            </small>
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                            @endif
                                                        @endforeach
                                                    </div>

                                                    <!-- Mensaje cuando no hay etapas -->
                                                    <div id="noStagesMessage" class="text-center py-5" style="display: {{ $stagesMap->isEmpty() ? 'block' : 'none' }}">
                                                        <i class="fas fa-layer-group fa-3x text-muted mb-3"></i>
                                                        <p class="text-muted mb-1">No hay etapas de validación configuradas</p>
                                                        <small class="text-muted">Haz clic en <strong>"Agregar etapa"</strong> para comenzar</small>
                                                    </div>

                                                    <div class="alert bg-light border-0 mb-0">
                                                        <h6 class="mb-0 fw-semibold">Tip</h6>
                                                        <p class="mb-0">Agrega solo las etapas que necesitas y configura su orden y condiciones. Las condiciones permiten que ciertas etapas solo se ejecuten en documentos específicos.</p>
                                                    </div>

                                                    <!-- Template para nuevas etapas (hidden) -->
                                                    <template id="stageItemTemplate">
                                                        <div class="card mb-3 stage-item" data-key="@{{KEY}}">
                                                            <div class="card-header border-bottom">
                                                                <div class="d-flex align-items-center justify-content-between">
                                                                    <div>
                                                                        <h6 class="mb-0 fw-semibold">@{{NAME}}</h6>
                                                                        <small class="text-muted">@{{DESCRIPTION}}</small>
                                                                    </div>
                                                                    <div class="d-flex align-items-center gap-2">
                                                                        <span class="badge bg-primary text-white">@{{KEY}}</span>
                                                                        <button type="button" class="btn btn-sm btn-secondary remove-stage-btn">
                                                                            <i class="fas fa-trash-alt"></i>
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <div class="card-body">
                                                                <div class="row g-3">
                                                                    <div class="col-md-6 col-sm-12">
                                                                        <div class="mb-3">
                                                                            <label class="control-label col-form-label">Orden</label>
                                                                            <input type="number" class="form-control stage-order" name="stage_order_@{{KEY}}" value="1" min="1" placeholder="1">
                                                                            <small class="form-text text-muted">Menor número = mayor prioridad</small>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-6 col-sm-12"></div>
                                                                    <div class="col-md-12">
                                                                        <label class="control-label col-form-label">Condiciones de ejecución</label>
                                                                        @{{CONDITIONS_SELECT}}
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </template>

                                                @endif
                                            </div>

                                    </div>
                                </div>
                            </div>
                            <!-- End Tab 2: Etapas de Validación -->

                            <!-- Tab 3: Requisitos de Documentos -->
                            <div class="tab-pane fade"
                                 id="requirements"
                                 role="tabpanel">

                                <div class=" ">

                                    <div class="card-body">


                                        {{-- Requirements Section --}}
                                            <div class="col-12">
                                                <div class="d-flex justify-content-between align-items-center mb-4">
                                                    <div>
                                                        <h5 class="fw-bold mb-0">
                                                            Requisitos de documentos
                                                        </h5>
                                                        <p class="text-muted mb-0">Archivos que el usuario debe subir para este tipo.</p>
                                                    </div>
                                                    <button type="button" class="btn btn-sm btn-primary" id="addRequirement">
                                                        <i class="fas fa-plus"></i>
                                                    </button>
                                                </div>
                                            </div>

                                            <div id="requirementsContainer">
                                                @forelse($documentType->requirements as $reqIndex => $requirement)
                                                    <div class="card mb-3 requirement-item" data-index="{{ $reqIndex }}">
                                                        <div class="card-header border-bottom">
                                                            <div class="d-flex justify-content-between align-items-center">
                                                                <h6 class="mb-0 fw-semibold">Requisito #{{ $reqIndex + 1 }}</h6>
                                                                <button type="button" class="btn btn-sm btn-primary remove-requirement">
                                                                    <i class="fas fa-trash-alt"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                        <div class="card-body">
                                                            <input type="hidden" name="requirements[{{ $reqIndex }}][id]" value="{{ $requirement->id }}">

                                                            <!-- Basic Fields -->
                                                            <div class="row g-3 mb-3">
                                                                <div class="col-lg-4">
                                                                    <div class="mb-3">
                                                                        <label class="control-label col-form-label">
                                                                            Clave <span class="text-danger">*</span>
                                                                        </label>
                                                                        <input type="text" class="form-control" name="requirements[{{ $reqIndex }}][key]"
                                                                               value="{{ old("requirements.{$reqIndex}.key", $requirement->key) }}"
                                                                               placeholder="ej: dni_frontal" required>
                                                                    </div>
                                                                </div>
                                                                <div class="col-lg-4">
                                                                    <div class="mb-3">
                                                                        <label class="control-label col-form-label">
                                                                            Tamaño máx (KB)
                                                                        </label>
                                                                        <input type="number" class="form-control" name="requirements[{{ $reqIndex }}][max_file_size]"
                                                                               value="{{ old("requirements.{$reqIndex}.max_file_size", $requirement->max_file_size) }}"
                                                                               min="1" placeholder="5120">
                                                                    </div>
                                                                </div>
                                                                <div class="col-lg-4">
                                                                    <div class="mb-3">
                                                                        <label class="control-label col-form-label">
                                                                            Extensiones permitidas
                                                                        </label>
                                                                        @php
                                                                            $oldExtensions = old("requirements.{$reqIndex}.allowed_extensions");
                                                                            $extensionsValue = '';

                                                                            if ($oldExtensions !== null) {
                                                                                // old() returned something (could be array or string)
                                                                                $extensionsValue = is_array($oldExtensions) ? implode(',', $oldExtensions) : $oldExtensions;
                                                                            } elseif (is_array($requirement->allowed_extensions) && !empty($requirement->allowed_extensions)) {
                                                                                // Use model value
                                                                                $extensionsValue = implode(',', $requirement->allowed_extensions);
                                                                            }
                                                                        @endphp
                                                                        <input type="text" class="form-control extensions-input"
                                                                               name="requirements[{{ $reqIndex }}][allowed_extensions][]"
                                                                               value="{{ $extensionsValue }}"
                                                                               placeholder="pdf,jpg,jpeg,png">
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <div class="row mb-3">
                                                                <div class="col-12">
                                                                    <div class="form-check form-check-inline">
                                                                        <input class="form-check-input" type="checkbox"
                                                                               name="requirements[{{ $reqIndex }}][is_required]" value="1"
                                                                               {{ old("requirements.{$reqIndex}.is_required", $requirement->is_required) ? 'checked' : '' }}
                                                                               id="is_required_{{ $reqIndex }}">
                                                                        <label class="form-check-label" for="is_required_{{ $reqIndex }}">
                                                                            Obligatorio
                                                                        </label>
                                                                    </div>
                                                                    <div class="form-check form-check-inline">
                                                                        <input class="form-check-input" type="checkbox"
                                                                               name="requirements[{{ $reqIndex }}][accepts_multiple]" value="1"
                                                                               {{ old("requirements.{$reqIndex}.accepts_multiple", $requirement->accepts_multiple) ? 'checked' : '' }}
                                                                               id="accepts_multiple_{{ $reqIndex }}">
                                                                        <label class="form-check-label" for="accepts_multiple_{{ $reqIndex }}">
                                                                            Múltiples archivos
                                                                        </label>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <!-- Language Translations -->
                                                            <div class="border-top pt-3 mt-3">
                                                                <h6 class="mb-3 fw-semibold">
                                                                    Traducciones
                                                                </h6>
                                                                <ul class="nav nav-pills mb-3" id="req{{ $reqIndex }}Tabs" role="tablist">
                                                                    @foreach($langs as $langIndex => $lang)
                                                                        <li class="nav-item" role="presentation">
                                                                            <button class="nav-link {{ $langIndex === 0 ? 'active' : '' }}"
                                                                                    id="req{{ $reqIndex }}-lang{{ $lang->id }}-tab"
                                                                                    data-bs-toggle="tab"
                                                                                    data-bs-target="#req{{ $reqIndex }}-lang{{ $lang->id }}"
                                                                                    type="button" role="tab">
                                                                                {{ $lang->title }}
                                                                            </button>
                                                                        </li>
                                                                    @endforeach
                                                                </ul>
                                                                <div class="tab-content">
                                                                    @foreach($langs as $langIndex => $lang)
                                                                        @php
                                                                            $translation = $requirement->translate($lang->id);
                                                                        @endphp
                                                                        <div class="tab-pane fade {{ $langIndex === 0 ? 'show active' : '' }}"
                                                                             id="req{{ $reqIndex }}-lang{{ $lang->id }}"
                                                                             role="tabpanel">
                                                                            <input type="hidden" name="requirements[{{ $reqIndex }}][translations][{{ $langIndex }}][lang_id]" value="{{ $lang->id }}">
                                                                            <div class="row">
                                                                                <div class="col-lg-6 mb-2">
                                                                                    <div class="mb-3">
                                                                                        <label class="control-label col-form-label">Nombre <span class="text-danger">*</span></label>
                                                                                        <input type="text" class="form-control"
                                                                                               name="requirements[{{ $reqIndex }}][translations][{{ $langIndex }}][name]"
                                                                                               value="{{ old("requirements.{$reqIndex}.translations.{$langIndex}.name", $translation?->name) }}" required>
                                                                                    </div>
                                                                                </div>
                                                                                <div class="col-lg-6 mb-2">
                                                                                    <div class="mb-3">
                                                                                        <label class="control-label col-form-label">Texto de ayuda</label>
                                                                                        <input type="text" class="form-control"
                                                                                               name="requirements[{{ $reqIndex }}][translations][{{ $langIndex }}][help_text]"
                                                                                               value="{{ old("requirements.{$reqIndex}.translations.{$langIndex}.help_text", $translation?->help_text) }}">
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    @endforeach
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @empty
                                                    <div class="text-center py-5" id="noRequirementsMessage">
                                                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                                        <p class="text-muted mb-1">No hay requisitos de documentos configurados</p>
                                                        <small class="text-muted">Haz clic en <strong>"Agregar requisito"</strong> para comenzar</small>
                                                    </div>
                                                @endforelse
                                            </div>

                                    </div>
                                </div>
                            </div>
                            <!-- End Tab 3: Requisitos de Documentos -->

                        </div>
                        <!-- End Tab Content -->

                    </div>

                        <!-- Footer -->
                        <div class="card-footer border-top">
                            <button type="submit" class="btn btn-primary w-100 mb-1">
                                Guardar
                            </button>
                            <a href="{{ route('settings.documents.types.index') }}" class="btn btn-secondary w-100">
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
    let requirementIndex = {{ $documentType->requirements->count() }};

    // Icon preview
    $('#icon').on('input', function() {
        $('#iconPreview').attr('class', $(this).val() + ' ms-1');
    });

    // ====== VALIDATION STAGES MANAGEMENT ======
    @if(!$validatorGroups->isEmpty())
    // Available validator groups
    const availableStages = @json($validatorGroups->map(function($group) {
        return [
            'key' => $group->key,
            'name' => $group->name,
            'description' => $group->description ?? ''
        ];
    })->values());

    const validationConditionsHtml = `
        @if($validationConditions->isEmpty())
            <div class="alert bg-warning-subtle border-warning mb-0">
                <h6 class="mb-0 fw-semibold">No hay condiciones configuradas</h6>
                <p class="mb-0">
                    <a href="{{ route('settings.documents.conditions.index') }}" class="alert-link">Crear condiciones</a>
                </p>
            </div>
        @else
            <select multiple class="form-select select2 select2 select2-conditions stage-conditions-select" name="stage_conditions_@{{KEY}}[]" data-key="@{{KEY}}" data-placeholder="Seleccionar condiciones para ejecutar esta etapa...">
                @foreach($validationConditions as $validationCondition)
                    <option value="{{ $validationCondition->key }}"
                            data-description="{{ $validationCondition->description ?? '' }}"
                            data-sale-types="{{ is_array($validationCondition->sale_types) && !empty($validationCondition->sale_types) ? implode(', ', $validationCondition->sale_types) : '' }}">
                        {{ $validationCondition->name }} ({{ $validationCondition->key }})
                    </option>
                @endforeach
            </select>
            <small class="form-text text-muted d-block mt-2">
                Sin condiciones = la etapa siempre se ejecuta. Con condiciones = solo se ejecuta si se cumplen.
            </small>
        @endif
    `;

    // Get already added stage keys
    function getAddedStageKeys() {
        var keys = [];
        $('.stage-item').each(function() {
            keys.push($(this).data('key'));
        });
        return keys;
    }

    // Update no stages message visibility
    function updateNoStagesMessage() {
        var hasStages = $('.stage-item').length > 0;
        if (hasStages) {
            $('#noStagesMessage').hide();
        } else {
            $('#noStagesMessage').show();
        }
    }

    // Add stage button
    $('#addStageBtn').on('click', function() {
        var addedKeys = getAddedStageKeys();
        var availableToAdd = [];

        // Filter available stages
        $.each(availableStages, function(index, stage) {
            if (addedKeys.indexOf(stage.key) === -1) {
                availableToAdd.push(stage);
            }
        });

        if (availableToAdd.length === 0) {
            alert('Todas las etapas disponibles ya han sido agregadas');
            return;
        }

        // Create select options
        var options = '<option value="">Selecciona una etapa...</option>';
        $.each(availableToAdd, function(index, stage) {
            options += '<option value="' + stage.key + '" data-name="' + stage.name + '" data-description="' + stage.description + '">' + stage.name + ' (' + stage.key + ')</option>';
        });

        // Create modal HTML
        var selectHtml = '<select id="stageSelector" class="form-select select2">' + options + '</select>';

        var modalHtml = '<div class="modal fade" id="addStageModal" tabindex="-1">' +
            '<div class="modal-dialog modal-dialog-centered">' +
                '<div class="modal-content">' +
                    '<div class="modal-header">' +
                        '<h5 class="modal-title">Agregar etapa de validación</h5>' +
                        '<button type="button" class="btn-close" data-bs-dismiss="modal"></button>' +
                    '</div>' +
                    '<div class="modal-body">' +
                        '<label class="form-label">Selecciona una etapa para agregar:</label>' +
                        selectHtml +
                    '</div>' +
            '<div class="modal-footer">' +
            '<button type="button" class="btn btn-primary w-100 mb-1" id="confirmAddStage">Agregar</button>' +
            '<button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>' +
            '</div>' +
                '</div>' +
            '</div>' +
        '</div>';

        var $modal = $(modalHtml).appendTo('body');

        // Show modal using jQuery
        $modal.modal('show');

        // Handle add button
        $modal.on('click', '#confirmAddStage', function() {
            var selectedKey = $modal.find('#stageSelector').val();
            var selectedOption = $modal.find('#stageSelector option:selected');

            if (!selectedKey) {
                alert('Por favor selecciona una etapa');
                return;
            }

            var stageName = selectedOption.data('name');
            var stageDescription = selectedOption.data('description');

            addStage(selectedKey, stageName, stageDescription);

            $modal.modal('hide');
        });

        // Clean up on modal close
        $modal.on('hidden.bs.modal', function() {
            $modal.remove();
        });
    });

    // Add stage function
    function addStage(key, name, description) {
        var template = $('#stageItemTemplate').html();
        var conditionsHtml = validationConditionsHtml;

        // Replace KEY placeholder in conditions
        var keyRegex = new RegExp('\\{\\{KEY\\}\\}', 'g');
        conditionsHtml = conditionsHtml.replace(keyRegex, key);

        // Replace all placeholders in template
        var html = template;
        html = html.replace(keyRegex, key);
        html = html.replace(new RegExp('\\{\\{NAME\\}\\}', 'g'), name);
        html = html.replace(new RegExp('\\{\\{DESCRIPTION\\}\\}', 'g'), description || '');
        html = html.replace(new RegExp('\\{\\{CONDITIONS_SELECT\\}\\}', 'g'), conditionsHtml);

        var $newStage = $(html);
        $('#stagesList').append($newStage);

        // Initialize select2 for conditions
        $newStage.find('.select2-conditions').select2({
            placeholder: 'Seleccionar condiciones...',
            allowClear: true,
            width: '100%',
            templateResult: formatConditionOption,
            templateSelection: formatConditionSelection
        });

        // No need to attach handler here - using delegated handler below
        updateNoStagesMessage();
    }

    // Show delete confirmation modal (reusable)
    function showDeleteConfirmModal(title, message, onConfirm) {
        // Remove any existing delete confirmation modals first
        $('#deleteConfirmModal').remove();
        $('.modal-backdrop').remove();

        var modalHtml = '<div class="modal fade" id="deleteConfirmModal" tabindex="-1">' +
            '<div class="modal-dialog modal-dialog-centered">' +
                '<div class="modal-content">' +
                    '<div class="modal-header position-relative">' +
                        '<h5 class="modal-title">' + title + '</h5>' +
                        '<button type="button" class="btn-close position-absolute end-0 me-3" data-bs-dismiss="modal"></button>' +
                    '</div>' +
                    '<div class="modal-body">' +
                        '<p class="mb-0">' + message + '</p>' +
                    '</div>' +
                    '<div class="modal-footer justify-content-center">' +
                        '<button type="button" class="btn btn-primary w-100  mb-1" id="confirmDeleteBtn">Eliminar</button>' +
                        '<button type="button" class="btn btn-secondary  w-100" data-bs-dismiss="modal">Cancelar</button>' +
                    '</div>' +
                '</div>' +
            '</div>' +
        '</div>';

        var $modal = $(modalHtml).appendTo('body');
        $modal.modal('show');

        // Use .one() to ensure handler only executes once
        $modal.one('click', '#confirmDeleteBtn', function() {
            onConfirm();
            $modal.modal('hide');
        });

        // Clean up after modal is completely hidden
        $modal.one('hidden.bs.modal', function() {
            $modal.remove();
            $('.modal-backdrop').remove();
            $('body').removeClass('modal-open').css('padding-right', '');
        });
    }

    // Remove stage function with modal
    function removeStage($stageItem) {
        var stageName = $stageItem.find('h6').first().text().trim();
        showDeleteConfirmModal(
            'Confirmar eliminación',
            '¿Estás seguro de eliminar la etapa "' + stageName + '"?',
            function() {
                $stageItem.remove();
                updateNoStagesMessage();
            }
        );
    }

    // Attach remove handlers to existing stages
    $(document).on('click', '.remove-stage-btn', function() {
        removeStage($(this).closest('.stage-item'));
    });

    @endif
    // ====== END VALIDATION STAGES MANAGEMENT ======

    // Update no requirements message visibility
    function updateNoRequirementsMessage() {
        var hasRequirements = $('.requirement-item').length > 0;
        if (hasRequirements) {
            $('#noRequirementsMessage').hide();
        } else {
            $('#noRequirementsMessage').show();
        }
    }

    // Add requirement
    $('#addRequirement').on('click', function() {
        updateNoRequirementsMessage();

        const index = requirementIndex++;

        // Build language tabs
        let langTabs = '';
        let langContent = '';

        @foreach($langs as $langIndex => $lang)
        langTabs += `
            <li class="nav-item" role="presentation">
                <button class="nav-link {{ $langIndex === 0 ? 'active' : '' }}"
                        id="req${index}-lang{{ $lang->id }}-tab"
                        data-bs-toggle="tab"
                        data-bs-target="#req${index}-lang{{ $lang->id }}"
                        type="button" role="tab">
                    {{ $lang->title }}
                </button>
            </li>
        `;

        langContent += `
            <div class="tab-pane fade {{ $langIndex === 0 ? 'show active' : '' }}"
                 id="req${index}-lang{{ $lang->id }}"
                 role="tabpanel">
                <input type="hidden" name="requirements[${index}][translations][{{ $langIndex }}][lang_id]" value="{{ $lang->id }}">
                <div class="row">
                    <div class="col-lg-6 mb-2">
                        <div class="mb-3">
                            <label class="control-label col-form-label">Nombre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control"
                                   name="requirements[${index}][translations][{{ $langIndex }}][name]" required>
                        </div>
                    </div>
                    <div class="col-lg-6 mb-2">
                        <div class="mb-3">
                            <label class="control-label col-form-label">Texto de ayuda</label>
                            <input type="text" class="form-control"
                                   name="requirements[${index}][translations][{{ $langIndex }}][help_text]">
                        </div>
                    </div>
                </div>
            </div>
        `;
        @endforeach

        const html = `
            <div class="card mb-3 requirement-item" data-index="${index}">
                <div class="card-header border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-semibold">Requisito #${index + 1}</h6>
                        <button type="button" class="btn btn-sm btn-primary remove-requirement">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Basic Fields -->
                    <div class="row g-3 mb-3">
                        <div class="col-lg-4">
                            <div class="mb-3">
                                <label class="control-label col-form-label">Clave <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="requirements[${index}][key]" placeholder="ej: dni_frontal" required>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="mb-3">
                                <label class="control-label col-form-label">Tamaño máx (KB)</label>
                                <input type="number" class="form-control" name="requirements[${index}][max_file_size]" value="5120" min="1" placeholder="5120">
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="mb-3">
                                <label class="control-label col-form-label">Extensiones permitidas</label>
                                <input type="text" class="form-control extensions-input" name="requirements[${index}][allowed_extensions][]" value="pdf,jpg,jpeg,png" placeholder="pdf,jpg,jpeg,png">
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" name="requirements[${index}][is_required]" value="1" id="is_required_${index}">
                                <label class="form-check-label" for="is_required_${index}">Obligatorio</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" name="requirements[${index}][accepts_multiple]" value="1" id="accepts_multiple_${index}">
                                <label class="form-check-label" for="accepts_multiple_${index}">Múltiples archivos</label>
                            </div>
                        </div>
                    </div>

                    <!-- Language Translations -->
                    <div class="border-top pt-3 mt-3">
                        <h6 class="mb-3 fw-semibold">Traducciones</h6>
                        <ul class="nav nav-pills mb-3" id="req${index}Tabs" role="tablist">
                            ${langTabs}
                        </ul>
                        <div class="tab-content">
                            ${langContent}
                        </div>
                    </div>
                </div>
            </div>
        </div>
        `;

        $('#requirementsContainer').append(html);

        // Attach remove handler with confirmation modal for new requirements
        $('.requirement-item').last().find('.remove-requirement').on('click', function() {
            var $requirementItem = $(this).closest('.requirement-item');
            var requirementNumber = $requirementItem.find('.fw-semibold').text().trim();

            showDeleteConfirmModal(
                'Confirmar eliminación',
                '¿Estás seguro de eliminar el requisito "' + requirementNumber + '"?',
                function() {
                    $requirementItem.remove();
                    updateNoRequirementsMessage();
                }
            );
        });

        updateNoRequirementsMessage();
    });

    // Remove existing requirements with confirmation modal
    $(document).on('click', '.remove-requirement', function() {
        var $requirementItem = $(this).closest('.requirement-item');
        var requirementNumber = $requirementItem.find('.fw-semibold').first().text().trim();

        showDeleteConfirmModal(
            'Confirmar eliminación',
            '¿Estás seguro de eliminar el requisito "' + requirementNumber + '"?',
            function() {
                $requirementItem.remove();
                updateNoRequirementsMessage();
            }
        );
    });

    // Initialize Select2 for conditions
    $('.select2-conditions').select2({
        placeholder: 'Seleccionar condiciones...',
        allowClear: true,
        width: '100%',
        templateResult: formatConditionOption,
        templateSelection: formatConditionSelection
    });

    // Set selected values from data attributes
    $('.select2-conditions').each(function() {
        const $select = $(this);
        const selectedConditionsAttr = $select.data('selected-conditions');

        if (selectedConditionsAttr && typeof selectedConditionsAttr === 'string') {
            try {
                const selectedConditions = JSON.parse(selectedConditionsAttr);
                const selectedKeys = Object.keys(selectedConditions).filter(key => selectedConditions[key]);
                $select.val(selectedKeys).trigger('change');
            } catch (e) {
                console.warn('Failed to parse selected conditions:', e);
            }
        }
    });

    function formatConditionOption(option) {
        if (!option.id) return option.text;

        const $option = $(option.element);
        const description = $option.data('description');
        const saleTypes = $option.data('sale-types');

        let html = '<div class="select2-option-content">';
        html += '<div class="fw-semibold">' + option.text + '</div>';

        if (description) {
            html += '<small class="text-muted d-block">' + description + '</small>';
        }

        html += '</div>';
        return $(html);
    }

    function formatConditionSelection(option) {
        return option.text;
    }

    // Fix for HTML5 validation: Remove 'required' from all hidden inputs
    // This prevents "An invalid form control is not focusable" error
    // Use 'click' on submit button to run BEFORE HTML5 validation
    $('button[type="submit"]').on('click', function(e) {
        // Remove 'required' from ALL inputs that are not visible
        $('#formDocumentType input[required]').each(function() {
            // Check if the input or any of its parents are hidden
            if (!$(this).is(':visible')) {
                $(this).removeAttr('required');
            }
        });
    });

    $('#formDocumentType').on('submit', function(e) {
        // Handle extensions array conversion
        $('.extensions-input').each(function() {
            const value = $(this).val();
            const match = $(this).attr('name').match(/requirements\[(\d+)\]/);
            if (!match) return;

            const index = match[1];
            $(this).remove();

            if (value) {
                value.split(',').map(ext => ext.trim()).filter(ext => ext).forEach(ext => {
                    $('<input>').attr({
                        type: 'hidden',
                        name: `requirements[${index}][allowed_extensions][]`,
                        value: ext
                    }).appendTo('#formDocumentType');
                });
            }
        });

        // Build validation_stages array with order and conditions
        const validationStages = [];
        $('.stage-item').each(function() {
            const $stageItem = $(this);
            const key = $stageItem.data('key');
            const order = parseInt($stageItem.find('.stage-order').val()) || 1;
            const $select = $stageItem.find('.stage-conditions-select');

            // Build conditions object from selected conditions
            const conditions = {};

            // Get selected values - try both .val() and data attribute as fallback
            let selectedConditions = $select.val() || [];

            // If no values from .val(), try to get from data attribute
            if (selectedConditions.length === 0) {
                const dataAttr = $select.data('selected-conditions');
                if (dataAttr && typeof dataAttr === 'object') {
                    // Data attribute has the conditions object
                    Object.keys(dataAttr).forEach(key => {
                        if (dataAttr[key]) {
                            selectedConditions.push(key);
                        }
                    });
                }
            }

            selectedConditions.forEach(function(conditionKey) {
                conditions[conditionKey] = true;
            });

            validationStages.push({
                key: key,
                order: order,
                conditions: conditions
            });
        });

        // Sort by order
        validationStages.sort((a, b) => a.order - b.order);

        // Remove old validation_stages inputs if any
        $('input[name^="validation_stages"]').remove();

        // Add validation_stages as JSON array
        validationStages.forEach((stage, index) => {
            $('<input>').attr({
                type: 'hidden',
                name: `validation_stages[${index}][key]`,
                value: stage.key
            }).appendTo('#formDocumentType');

            $('<input>').attr({
                type: 'hidden',
                name: `validation_stages[${index}][order]`,
                value: stage.order
            }).appendTo('#formDocumentType');

            // Add conditions for this stage
            Object.keys(stage.conditions).forEach((conditionKey) => {
                if (stage.conditions[conditionKey]) {
                    $('<input>').attr({
                        type: 'hidden',
                        name: `validation_stages[${index}][conditions][${conditionKey}]`,
                        value: '1'
                    }).appendTo('#formDocumentType');
                }
            });
        });
    });
});
</script>
@endpush
