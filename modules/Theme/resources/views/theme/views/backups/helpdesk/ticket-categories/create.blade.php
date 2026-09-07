@extends('layouts.theme')

@section('title', 'Nueva categoria de ticket')

@section('page_header')
    @include('core::components.card', ['title' => 'Nueva categoria de ticket'])
@endsection

@section('content')

    <div class="row g-3">

        {{-- Form --}}
        <div class="col-12 col-lg-8">
            <div class="card">
                <form id="categoryForm" action="{{ route('manager.helpdesk.settings.ticket-categories.store') }}" method="POST">
                    @csrf

                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Nueva categoria</h5>
                        <small class="text-muted">Crea una categoria para clasificar los tickets</small>
                    </div>

                    <div class="card-body">
                        @include('core::components.alerts')

                        <h6 class="fw-semibold mb-1">Informacion basica</h6>
                        <p class="text-muted small mb-3">Nombre, slug y descripcion visible de la categoria</p>
                        <div class="row g-3 mb-4">

                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Nombre <span class="text-danger">*</span></label>
                                    <input type="text" name="name"
                                           class="form-control @error('name') is-invalid @enderror"
                                           value="{{ old('name') }}"
                                           placeholder="Ej: Soporte tecnico"
                                           required>
                                    @error('name')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Slug</label>
                                    @include('core::components.slug-field', [
                                        'value' => old('slug', ''),
                                        'from' => 'input[name=name]',
                                        'placeholder' => 'soporte-tecnico',
                                    ])
                                    <small class="form-text text-muted">Sigue al nombre mientras no lo edites a mano</small>
                                    @error('slug')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="form-label">Descripcion</label>
                                    <textarea name="description"
                                              class="form-control @error('description') is-invalid @enderror"
                                              rows="3"
                                              placeholder="Describe el alcance de esta categoria">{{ old('description') }}</textarea>
                                    @error('description')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                        </div>

                        <h6 class="fw-semibold mb-1">Apariencia</h6>
                        <p class="text-muted small mb-3">Icono y color que identifican visualmente la categoria en listados</p>
                        <div class="row g-3 mb-4">

                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="form-label">Icono</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i id="iconPreview" class="{{ old('icon', 'fas fa-tag') }}"></i>
                                        </span>
                                        <input type="text" name="icon" id="iconInput"
                                               class="form-control @error('icon') is-invalid @enderror"
                                               value="{{ old('icon', 'fas fa-tag') }}"
                                               placeholder="fas fa-tag">
                                    </div>
                                    <small class="form-text text-muted">Clase Font Awesome 6. Ej: fas fa-headset, fas fa-bug</small>
                                    @error('icon')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="form-label">Color</label>
                                    @include('core::components.color-field', [
                                        'name' => 'color',
                                        'value' => old('color', '#90bb13'),
                                        'preview' => old('name', 'Categoria'),
                                        'previewFrom' => 'input[name=name]',
                                    ])
                                    @error('color')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <h6 class="fw-semibold mb-1">Configuracion</h6>
                        <p class="text-muted small mb-3">Politica SLA por defecto y disponibilidad de la categoria</p>
                        <div class="row g-3">

                            @if(isset($slaPolicies) && $slaPolicies->count())
                                <div class="col-12 col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Politica SLA por defecto</label>
                                        <select name="default_sla_policy_id"
                                                class="form-select select2 @error('default_sla_policy_id') is-invalid @enderror">
                                            <option value="">Sin politica SLA</option>
                                            @foreach($slaPolicies as $sla)
                                                <option value="{{ $sla->id }}" {{ old('default_sla_policy_id') == $sla->id ? 'selected' : '' }}>
                                                    {{ $sla->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('default_sla_policy_id')
                                            <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            @endif

                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label for="active" class="form-label">Estado</label>
                                    <select class="form-select select2 @error('active') is-invalid @enderror" id="active" name="active" required>
                                        <option value="1" {{ old('active', 1) == 1 ? 'selected' : '' }}>Activa</option>
                                        <option value="0" {{ old('active', 1) == 0 ? 'selected' : '' }}>Inactiva</option>
                                    </select>
                                    <small class="form-text text-muted">Las inactivas no estan disponibles para nuevos tickets</small>
                                    @error('active')
                                        <div class="field-validation-error">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-1">Guardar categoria</button>
                        <a href="{{ route('manager.helpdesk.settings.ticket-categories.index') }}" class="btn btn-light w-100">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Help panel --}}
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">Sobre las categorias</h6>
                </div>
                <div class="card-body">
                    <p class="card-text text-muted">
                        Las categorias permiten clasificar los tickets para facilitar su gestion y enrutamiento hacia el equipo correcto.
                    </p>
                </div>
            </div>
            <div class="card">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">Buenas practicas</h6>
                </div>
                <div class="card-body">
                    <ul class="text-muted mb-0">
                        <li class="mb-2">Usa nombres cortos y descriptivos</li>
                        <li class="mb-2">Asigna un color distinto por categoria</li>
                        <li class="mb-2">Configura una politica SLA para controlar tiempos</li>
                        <li class="mb-0">El slug se genera automaticamente desde el nombre</li>
                    </ul>
                </div>
            </div>
        </div>

    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    // Icon preview
    $('#iconInput').on('input', function () {
        $('#iconPreview').attr('class', $(this).val() || 'fas fa-tag');
    });
    // Select2
    $('.select2').select2({ width: '100%' });
});
</script>
@endpush
