@extends('layouts.theme')

@section('content')

    @include('core::components.card', ['title' => 'Crear Variable de Email'])

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <form action="{{ route('mailers.variables.store') }}" method="POST">
                    @csrf

                    <div class="card-body">
                        <!-- Header -->
                        <div class="d-flex align-items-center justify-content-between mb-4">
                            <div>
                                <h5 class="mb-1 fw-bold">Crear Nueva Variable de Email</h5>
                                <p class="text-muted small mb-0">
                                    Complete la información en todos los idiomas disponibles.
                                </p>
                            </div>
                            <a href="{{ route('mailers.variables.index') }}" class="btn btn-light">
                                <i class="fas fa-arrow-left me-1"></i> Volver
                            </a>
                        </div>

                        <!-- Basic Information -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="fw-bold mb-3 border-bottom pb-2">
                                    <i class="fas fa-cog me-2"></i>Información Básica
                                </h6>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="key" class="form-label">
                                        Clave de Variable <span class="text-danger">*</span>
                                        <small class="text-muted">(Solo mayúsculas y guiones bajos)</small>
                                    </label>
                                    <input type="text" class="form-control @error('key') is-invalid @enderror" id="key" name="key"
                                        placeholder="ej: CUSTOMER_NAME, ORDER_NUMBER" value="{{ old('key') }}" required
                                        pattern="^[A-Z_]+$" title="Solo mayúsculas y guiones bajos">
                                    @error('key')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Nombre <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name"
                                        placeholder="ej: Nombre del Cliente" value="{{ old('name') }}" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="category" class="form-label">Categoría <span class="text-danger">*</span></label>
                                    <select class="form-select @error('category') is-invalid @enderror" id="category" name="category" required>
                                        <option value="">Seleccionar categoría</option>
                                        @foreach ($categories as $value => $label)
                                            <option value="{{ $value }}" @selected(old('category') === $value)>{{ $label }}</option>
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
                                    <select class="form-select @error('module') is-invalid @enderror" id="module" name="module" required>
                                        <option value="">Seleccionar módulo</option>
                                        @foreach ($modules as $value => $label)
                                            <option value="{{ $value }}" @selected(old('module') === $value)>{{ $label }}</option>
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
                                        <option value="1" {{ old('is_enabled', '1') == '1' ? 'selected' : '' }}>Habilitada</option>
                                        <option value="0" {{ old('is_enabled') == '0' ? 'selected' : '' }}>Deshabilitada</option>
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
                                        placeholder="Describe qué representa esta variable y cuándo se utiliza" rows="3">{{ old('description') }}</textarea>
                                    @error('description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="is_system" name="is_system" value="1" @checked(old('is_system'))>
                                    <label class="form-check-label" for="is_system">
                                        <strong>Variable del Sistema</strong>
                                        <small class="text-muted d-block">Marcar si esta variable es crítica para el sistema</small>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Translations -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="fw-bold mb-3 border-bottom pb-2">
                                    <i class="fas fa-language me-2"></i>Traducciones
                                </h6>
                            </div>

                            <div class="col-12">
                                <ul class="nav nav-tabs mb-3" id="languageTabs" role="tablist">
                                    @foreach($langs as $index => $lang)
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link {{ $index === 0 ? 'active' : '' }}"
                                                    id="lang-{{ $lang->id }}-tab"
                                                    data-bs-toggle="tab"
                                                    data-bs-target="#lang-{{ $lang->id }}"
                                                    type="button" role="tab">
                                                {{ $lang->title }}
                                                <span class="badge bg-danger ms-1">*</span>
                                            </button>
                                        </li>
                                    @endforeach
                                </ul>

                                <div class="tab-content" id="languageTabsContent">
                                    @foreach($langs as $index => $lang)
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
                                                               value="{{ old("translations.{$index}.name") }}"
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
                                                                  rows="2">{{ old("translations.{$index}.description") }}</textarea>
                                                        @error("translations.{$index}.description")
                                                            <div class="invalid-feedback">{{ $message }}</div>
                                                        @enderror
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
                        <div class="d-flex gap-2 justify-content-end">
                            <a href="{{ route('mailers.variables.index') }}" class="btn btn-light">
                                <i class="fas fa-times me-1"></i> Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Crear Variable
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection
