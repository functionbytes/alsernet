@extends('layouts.theme')

@section('title', isset($template) ? 'Editar plantilla' : 'Nueva plantilla')

@section('content')

    @include('core::components.card', ['title' => isset($template) ? 'Editar plantilla' : 'Nueva plantilla'])

    <div class="row g-3">

        {{-- Form --}}
        <div class="col-12 col-lg-8">
            <div class="card">
                <form action="{{ isset($template) ? route('manager.helpdesk.ticket-templates.update', $template->id) : route('manager.helpdesk.ticket-templates.store') }}"
                      method="POST">
                    @csrf
                    @isset($template)
                        @method('PUT')
                    @endisset

                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">{{ isset($template) ? 'Editar plantilla' : 'Nueva plantilla' }}</h5>
                        <small class="text-muted">{{ isset($template) ? 'Modifica los datos de la plantilla' : 'Crea una nueva plantilla reutilizable para tickets' }}</small>
                    </div>

                    <div class="card-body">
                        @include('core::components.alerts')

                        {{-- Informacion basica --}}
                        <h6 class="fw-semibold mb-1 border-bottom pb-2">Informacion basica</h6>
                        <p class="text-muted small mb-3">Nombre interno de la plantilla y descripcion de su proposito</p>
                        <div class="row g-3 mb-4">

                            <div class="col-12">
                                <label class="form-label">Nombre <span class="text-danger">*</span></label>
                                <input type="text" name="name"
                                       class="form-control @error('name') is-invalid @enderror"
                                       value="{{ old('name', $template->name ?? '') }}"
                                       placeholder="Ej: Soporte tecnico general"
                                       required>
                                @error('name')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label">Descripcion</label>
                                <input type="text" name="description"
                                       class="form-control @error('description') is-invalid @enderror"
                                       value="{{ old('description', $template->description ?? '') }}"
                                       placeholder="Breve descripcion del uso de esta plantilla">
                                @error('description')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                        </div>

                        {{-- Contenido --}}
                        <h6 class="fw-semibold mb-1 border-bottom pb-2">Contenido</h6>
                        <p class="text-muted small mb-3">Asunto y cuerpo del ticket que se creara al aplicar la plantilla. Acepta variables como {cliente}, {fecha}</p>
                        <div class="row g-3 mb-4">

                            <div class="col-12">
                                <label class="form-label">Asunto <span class="text-danger">*</span></label>
                                <input type="text" name="subject"
                                       class="form-control @error('subject') is-invalid @enderror"
                                       value="{{ old('subject', $template->subject ?? '') }}"
                                       placeholder="Asunto del ticket"
                                       required>
                                @error('subject')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label">Cuerpo <span class="text-danger">*</span></label>
                                <textarea name="body" rows="8"
                                          class="form-control @error('body') is-invalid @enderror"
                                          placeholder="Contenido de la plantilla..."
                                          required>{{ old('body', $template->body ?? '') }}</textarea>
                                @error('body')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                        </div>

                        {{-- Clasificacion --}}
                        <h6 class="fw-semibold mb-1 border-bottom pb-2">Clasificacion</h6>
                        <p class="text-muted small mb-3">Categoria y prioridad predeterminadas al aplicar la plantilla</p>
                        <div class="row g-3 mb-4">

                            <div class="col-12 col-md-6">
                                <label class="form-label">Categoria</label>
                                <select name="category_id" class="form-select @error('category_id') is-invalid @enderror">
                                    <option value="">Sin categoria</option>
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat->id }}"
                                            {{ old('category_id', $template->category_id ?? '') == $cat->id ? 'selected' : '' }}>
                                            {{ $cat->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('category_id')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label">Prioridad</label>
                                <select name="priority_id" class="form-select @error('priority_id') is-invalid @enderror">
                                    <option value="">Sin prioridad</option>
                                    @foreach($priorities as $prio)
                                        <option value="{{ $prio->id }}"
                                            {{ old('priority_id', $template->priority_id ?? '') == $prio->id ? 'selected' : '' }}>
                                            {{ $prio->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('priority_id')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                        </div>

                        {{-- Configuracion --}}
                        <h6 class="fw-semibold mb-1 border-bottom pb-2">Configuracion</h6>
                        <p class="text-muted small mb-3">Disponibilidad de la plantilla para uso</p>
                        <div class="row g-3">

                            <div class="col-12 col-md-6">
                                <label class="form-label">Estado</label>
                                <select name="is_active" class="form-select @error('is_active') is-invalid @enderror">
                                    <option value="1" {{ old('is_active', $template->is_active ?? 1) == 1 ? 'selected' : '' }}>
                                        Activa — disponible para aplicar
                                    </option>
                                    <option value="0" {{ old('is_active', $template->is_active ?? 1) == 0 ? 'selected' : '' }}>
                                        Inactiva — oculta
                                    </option>
                                </select>
                                @error('is_active')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                        </div>

                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-1">
                            {{ isset($template) ? 'Guardar cambios' : 'Crear plantilla' }}
                        </button>
                        <a href="{{ route('manager.helpdesk.ticket-templates.index') }}" class="btn btn-light w-100">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Help panel --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-3">Sobre las plantillas</h6>
                    <p class="card-text text-muted">
                        Las plantillas permiten crear tickets con informacion predefinida, agilizando la gestion de solicitudes recurrentes.
                    </p>
                </div>
                <hr class="my-0">
                <div class="card-body">
                    <h6 class="card-title mb-3">Buenas practicas</h6>
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2 text-muted small"><i class="fas fa-check-circle text-success me-2"></i> Usa nombres descriptivos que indiquen el tipo de solicitud</li>
                        <li class="mb-2 text-muted small"><i class="fas fa-check-circle text-success me-2"></i> Incluye variables como {cliente} o {fecha} para personalizar el contenido</li>
                        <li class="mb-2 text-muted small"><i class="fas fa-check-circle text-success me-2"></i> Asigna categoria y prioridad para que los tickets se clasifiquen automaticamente</li>
                        <li class="text-muted small"><i class="fas fa-check-circle text-success me-2"></i> Desactiva las plantillas obsoletas en lugar de eliminarlas</li>
                    </ul>
                </div>
            </div>
        </div>

    </div>

@endsection
