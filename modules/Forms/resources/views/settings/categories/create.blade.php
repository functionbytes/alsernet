@extends('layouts.theme')

@section('title', 'Nueva categoría')

@section('content')

    @include('core::components.card', ['title' => 'Nueva categoría'])

    <div class="row g-3">

        {{-- Formulario --}}
        <div class="col-12 col-lg-8">
            <div class="card">
                <form action="{{ route('settings.forms.categories.store') }}" method="POST" id="categoryForm">
                    @csrf
                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Nueva categoría de formulario</h5>
                        <small class="text-muted">Complete la información para crear una nueva categoría.</small>
                    </div>
                    <div class="card-body">
                        @include('core::components.alerts')

                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="name" class="form-label">Nombre <span class="text-danger">*</span></label>
                                <input type="text" id="name" name="name"
                                       class="form-control @error('name') is-invalid @enderror"
                                       value="{{ old('name') }}" required autofocus
                                       placeholder="ej: Solicitudes internas">
                                <small class="form-text text-muted">El slug se genera automáticamente desde el nombre</small>
                                @error('name')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-md-12 mb-3">
                                <label for="slug" class="form-label">Slug</label>
                                <input type="text" id="slug" name="slug"
                                       class="form-control @error('slug') is-invalid @enderror"
                                       value="{{ old('slug') }}" placeholder="auto-generado">
                                <small class="form-text text-muted">Dejar vacío para autogenerar</small>
                                @error('slug')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12 mb-3">
                                <label for="description" class="form-label">Descripción</label>
                                <textarea id="description" name="description" class="form-control" rows="3"
                                          placeholder="Descripción opcional de la categoría">{{ old('description') }}</textarea>
                                <small class="form-text text-muted">Máximo 255 caracteres. Sea claro y conciso</small>
                            </div>

                            <div class="col-md-12 mb-3">
                                <label for="color" class="form-label">Color</label>
                                <input type="color" id="color" name="color"
                                       class="form-control @error('color') is-invalid @enderror"
                                       value="{{ old('color', '#90bb13') }}">
                                <small class="form-text text-muted">Color de identificación visual</small>
                                @error('color')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-md-12 mb-3">
                                <label for="icon" class="form-label">Icono</label>
                                <div class="input-group">
                                    <input type="text" id="icon" name="icon"
                                           class="form-control @error('icon') is-invalid @enderror"
                                           value="{{ old('icon') }}" placeholder="fas fa-folder">
                                    <span class="input-group-text">
                                        <i id="iconPreview" class="{{ old('icon', 'fas fa-folder') }}"></i>
                                    </span>
                                </div>
                                <small class="form-text text-muted">Clase Font Awesome 6 (ej: <code>fas fa-folder</code>)</small>
                                @error('icon')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12 mb-3">
                                <label class="form-label">Estado</label>
                                <select class="form-select select2" name="is_active">
                                    <option value="1" {{ old('is_active', '1') == '1' ? 'selected' : '' }}>Activo</option>
                                    <option value="0" {{ old('is_active') == '0' ? 'selected' : '' }}>Inactivo</option>
                                </select>
                                <small class="form-text text-muted">Las categorías inactivas no aparecen al crear formularios</small>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-1">
                            Crear categoría
                        </button>
                        <a href="{{ route('settings.forms.categories.index') }}" class="btn btn-light w-100">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Panel informativo --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-3">¿Qué es una categoría?</h6>
                    <p class="card-text text-muted">
                        Las categorías organizan los formularios por temática, facilitando su búsqueda y gestión en el sistema.
                    </p>
                </div>
                <hr class="my-0">
                <div class="card-body">
                    <h6 class="card-title mb-3">Slug</h6>
                    <p class="card-text text-muted mb-0">
                        El slug es el identificador único en URL. Se genera automáticamente desde el nombre si se deja vacío.
                    </p>
                </div>
                <hr class="my-0">
                <div class="card-body">
                    <h6 class="card-title mb-3">Color e icono</h6>
                    <p class="card-text text-muted mb-0">
                        Se usan para identificar visualmente la categoría en listados. El icono acepta cualquier clase de <strong>Font Awesome 6</strong> (ej: <code>fas fa-folder</code>).
                    </p>
                </div>
            </div>
        </div>

    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    // Slug auto-generation
    $('#name').on('input', function () {
        if ($('#slug').data('manual')) return;
        $('#slug').val(
            $(this).val()
                .toLowerCase()
                .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                .replace(/[^a-z0-9\s-]/g, '')
                .trim()
                .replace(/\s+/g, '-')
        );
    });

    $('#slug').on('input', function () {
        $(this).data('manual', $(this).val().length > 0);
    });

    // Icon preview
    $('#icon').on('input', function () {
        $('#iconPreview').attr('class', $(this).val());
    });

    $('.select2').select2({ minimumResultsForSearch: Infinity });
});
</script>
@endpush
