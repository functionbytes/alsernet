@extends('layouts.theme')

@section('title', 'Nueva categoria')

@section('content')

    @include('core::components.card', ['title' => 'Nueva categoria'])

    <div class="row g-3">

        {{-- Formulario --}}
        <div class="col-12 col-lg-8">
            <div class="card">
                <form action="{{ route('pages.categories.store') }}" method="POST" id="categoryForm">
                    @csrf
                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Nueva categoria de pagina</h5>
                        <small class="text-muted">Complete la informacion para crear una nueva categoria.</small>
                    </div>
                    <div class="card-body">
                        @include('core::components.alerts')

                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="name" class="form-label">Nombre <span class="text-danger">*</span></label>
                                <input type="text" id="name" name="name"
                                       class="form-control @error('name') is-invalid @enderror"
                                       value="{{ old('name') }}" required autofocus
                                       placeholder="ej: Noticias">
                                <small class="form-text text-muted">El slug se genera automaticamente desde el nombre</small>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-12 mb-3">
                                <label for="slug" class="form-label">Slug</label>
                                <input type="text" id="slug" name="slug"
                                       class="form-control @error('slug') is-invalid @enderror"
                                       value="{{ old('slug') }}" placeholder="auto-generado">
                                <small class="form-text text-muted">Dejar vacio para autogenerar</small>
                                @error('slug')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 mb-3">
                                <label for="description" class="form-label">Descripcion</label>
                                <textarea id="description" name="description" class="form-control" rows="3"
                                          placeholder="Descripcion opcional de la categoria">{{ old('description') }}</textarea>
                                <small class="form-text text-muted">Maximo 500 caracteres. Sea claro y conciso</small>
                            </div>

                            <div class="col-md-12 mb-3">
                                <label for="color" class="form-label">Color</label>
                                <input type="color" id="color" name="color"
                                       class="form-control @error('color') is-invalid @enderror"
                                       value="{{ old('color', '#90bb13') }}">
                                <small class="form-text text-muted">Color de identificacion visual</small>
                                @error('color')
                                    <div class="invalid-feedback">{{ $message }}</div>
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
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 mb-3">
                                <label class="form-label">Estado</label>
                                <select class="form-select select2" name="is_active">
                                    <option value="1" {{ old('is_active', '1') == '1' ? 'selected' : '' }}>Activo</option>
                                    <option value="0" {{ old('is_active') == '0' ? 'selected' : '' }}>Inactivo</option>
                                </select>
                                <small class="form-text text-muted">Las categorias inactivas no aparecen al crear paginas</small>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-1">
                            Crear categoria
                        </button>
                        <a href="{{ route('pages.categories.index') }}" class="btn btn-light w-100">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Panel informativo --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-3">¿Que es una categoria?</h6>
                    <p class="card-text text-muted">
                        Las categorias organizan las paginas por tematica, facilitando su busqueda y gestion en el sistema.
                    </p>
                </div>
                <hr class="my-0">
                <div class="card-body">
                    <h6 class="card-title mb-3">Slug</h6>
                    <p class="card-text text-muted mb-0">
                        El slug es el identificador unico en URL. Se genera automaticamente desde el nombre si se deja vacio.
                    </p>
                </div>
                <hr class="my-0">
                <div class="card-body">
                    <h6 class="card-title mb-3">Color e icono</h6>
                    <p class="card-text text-muted mb-0">
                        Se usan para identificar visualmente la categoria en listados. El icono acepta cualquier clase de <strong>Font Awesome 6</strong> (ej: <code>fas fa-folder</code>).
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
