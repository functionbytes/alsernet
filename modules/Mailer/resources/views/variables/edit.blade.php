@extends('layouts.theme')

@section('content')

    @include('core::components.card', ['title' => 'Editar Variable de Email'])

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <form action="{{ route('mailers.variables.update', $variable) }}" method="POST">
                    @csrf
                    @method('PATCH')

                    <!-- Hidden fields for system variables (which are disabled in UI but still need to be submitted) -->
                    @if($variable->is_system)
                        <input type="hidden" name="key" value="{{ $variable->key }}">
                        <input type="hidden" name="category" value="{{ $variable->category }}">
                        <input type="hidden" name="module" value="{{ $variable->module }}">
                        <input type="hidden" name="is_system" value="1">
                    @endif

                    <div class="card-body">
                        <!-- Header -->
                        <div class="d-flex align-items-center justify-content-between mb-4">
                            <div>
                                <h5 class="mb-1 fw-bold">Editar: {{ $variable->key }}</h5>
                                <p class="text-muted small mb-0">
                                    Actualice la información en todos los idiomas disponibles.
                                </p>
                            </div>
                            <a href="{{ route('mailers.variables.index') }}" class="btn btn-light">
                                <i class="fas fa-arrow-left me-1"></i> Volver
                            </a>
                        </div>

                        <!-- Basic Information -->
                        <div class="row mb-4">

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="key" class="form-label">
                                        Clave de Variable <span class="text-danger">*</span>
                                        <small class="text-muted">(Solo mayúsculas y guiones bajos)</small>
                                    </label>
                                    <input type="text" class="form-control @error('key') is-invalid @enderror" id="key" name="key"
                                        placeholder="ej: CUSTOMER_NAME" value="{{ old('key', $variable->key) }}" required
                                        pattern="^[A-Z_]+$" title="Solo mayúsculas y guiones bajos"
                                        @if($variable->is_system) disabled @endif>
                                    @error('key')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    @if ($variable->is_system)
                                        <small class="form-text text-warning">
                                            <i class="fas fa-exclamation-triangle me-1"></i>No se puede modificar la clave de variables del sistema
                                        </small>
                                    @endif
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Nombre <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name"
                                        placeholder="ej: Nombre del Cliente" value="{{ old('name', $variable->name) }}" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="category" class="form-label">Categoría <span class="text-danger">*</span></label>
                                    <select class="form-select @error('category') is-invalid @enderror" id="category" name="category" required
                                        @if($variable->is_system) disabled @endif>
                                        <option value="">Seleccionar categoría</option>
                                        @foreach ($categories as $value => $label)
                                            <option value="{{ $value }}" @selected(old('category', $variable->category) === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('category')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="module" class="form-label">Módulo <span class="text-danger">*</span></label>
                                    <select class="form-select @error('module') is-invalid @enderror" id="module" name="module" required
                                        @if($variable->is_system) disabled @endif>
                                        <option value="">Seleccionar módulo</option>
                                        @foreach ($modules as $value => $label)
                                            <option value="{{ $value }}" @selected(old('module', $variable->module) === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('module')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="is_enabled" class="form-label">Estado</label>
                                    <select class="form-select @error('is_enabled') is-invalid @enderror" id="is_enabled" name="is_enabled">
                                        <option value="1" {{ old('is_enabled', $variable->is_enabled) == 1 ? 'selected' : '' }}>Habilitada</option>
                                        <option value="0" {{ old('is_enabled', $variable->is_enabled) == 0 ? 'selected' : '' }}>Deshabilitada</option>
                                    </select>
                                    @error('is_enabled')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="mb-3">
                                    <label for="description" class="form-label">Descripción</label>
                                    <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description"
                                        placeholder="Describe qué representa esta variable" rows="3">{{ old('description', $variable->description) }}</textarea>
                                    @error('description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            @if($variable->is_system)
                            <div class="col-12">
                                <div class="alert alert-info bg-info-subtle border-0" role="alert">
                                    <i class="fas fa-shield-halved me-2"></i>
                                    <strong>Variable del Sistema:</strong> Esta variable es parte del núcleo del sistema y tiene restricciones de edición.
                                </div>
                            </div>
                            @endif
                        </div>

                        <hr>
                        <!-- Translations -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="fw-bold ">
                                   Traducciones
                                </h5>
                                <p class="text-muted small mb-2">
                                    Actualice la información en todos los idiomas disponibles.
                                </p>
                            </div>

                            <div class="col-12">
                                <ul class="nav nav-tabs mt-3 mb-3" id="languageTabs" role="tablist">
                                    @foreach($langs as $index => $lang)
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link {{ $index === 0 ? 'active' : '' }}"
                                                    id="lang-{{ $lang->id }}-tab"
                                                    data-bs-toggle="tab"
                                                    data-bs-target="#lang-{{ $lang->id }}"
                                                    type="button" role="tab">
                                                {{ $lang->title }}
                                                @php
                                                    $translation = $variable->translate($lang->id);
                                                @endphp
                                                @if($translation && $translation->name)

                                                @else
                                                    <i class="fas fa-exclamation-circle text-warning ms-1"></i>
                                                @endif
                                            </button>
                                        </li>
                                    @endforeach
                                </ul>

                                <div class="tab-content" id="languageTabsContent">
                                    @foreach($langs as $index => $lang)
                                        @php
                                            $translation = $variable->translate($lang->id);
                                        @endphp
                                        <div class="tab-pane fade {{ $index === 0 ? 'show active' : '' }}"
                                             id="lang-{{ $lang->id }}"
                                             role="tabpanel">

                                            <input type="hidden" name="translations[{{ $index }}][lang_id]" value="{{ $lang->id }}">

                                            <div class="row">
                                                <div class="col-md-12">
                                                    <div class="mb-3">
                                                        <label for="translations_{{ $lang->id }}_name" class="form-label">
                                                            Nombre <span class="text-danger">*</span>
                                                        </label>
                                                        <input type="text"
                                                               class="form-control @error("translations.{$index}.name") is-invalid @enderror"
                                                               id="translations_{{ $lang->id }}_name"
                                                               name="translations[{{ $index }}][name]"
                                                               value="{{ old("translations.{$index}.name", $translation?->name) }}"
                                                               required>
                                                        @error("translations.{$index}.name")
                                                            <div class="invalid-feedback">{{ $message }}</div>
                                                        @enderror
                                                    </div>
                                                </div>

                                                <div class="col-md-12">
                                                    <div class="mb-3">
                                                        <label for="translations_{{ $lang->id }}_description" class="form-label">
                                                            Descripción
                                                        </label>
                                                        <textarea class="form-control @error("translations.{$index}.description") is-invalid @enderror"
                                                                  id="translations_{{ $lang->id }}_description"
                                                                  name="translations[{{ $index }}][description]"
                                                                  rows="2">{{ old("translations.{$index}.description", $translation?->description) }}</textarea>
                                                        @error("translations.{$index}.description")
                                                            <div class="invalid-feedback">{{ $message }}</div>
                                                        @enderror
                                                    </div>
                                                </div>

                                                <div class="col-md-12">
                                                    <div class="mb-3">
                                                        <label for="translations_{{ $lang->id }}_value" class="form-label">
                                                            Valor visual en correo
                                                            <small class="text-muted">(Este es el valor que se usará en los emails)</small>
                                                        </label>
                                                        <textarea class="form-control @error("translations.{$index}.value") is-invalid @enderror"
                                                                  id="translations_{{ $lang->id }}_value"
                                                                  name="translations[{{ $index }}][value]"
                                                                  rows="2"
                                                                  placeholder="Ingresa el valor traducido para esta variable">{{ old("translations.{$index}.value", $translation?->value) }}</textarea>
                                                        @error("translations.{$index}.value")
                                                            <div class="invalid-feedback">{{ $message }}</div>
                                                        @enderror
                                                        <small class="form-text text-muted">
                                                            <i class="fas fa-info-circle me-1"></i>Dejar vacío para usar el valor Por defecto del sistema
                                                        </small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="card-footer border-top">
                            <button type="submit" class="btn btn-primary mb-1 w-100">
                               Guardar
                            </button>
                            <a href="{{ route('mailers.variables.index') }}" class="btn btn-light w-100">
                                Cancelar
                            </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection
