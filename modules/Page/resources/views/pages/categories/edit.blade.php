@extends('layouts.theme')

@section('title', 'Editar categoria')

@section('content')

    @include('core::components.card', ['title' => 'Editar categoria'])

    <div class="row g-3">

        {{-- Formulario --}}
        <div class="col-12 col-lg-8">
            <div class="card">
                <form action="{{ route('pages.categories.update', $category) }}" method="POST" id="categoryForm">
                    @csrf
                    @method('PATCH')
                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Editar categoria</h5>
                        <small class="text-muted">Actualice la informacion de la categoria.</small>
                    </div>
                    <div class="card-body">
                        @include('core::components.alerts')

                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="name" class="form-label">Nombre <span class="text-danger">*</span></label>
                                <input type="text" id="name" name="name"
                                       class="form-control @error('name') is-invalid @enderror"
                                       value="{{ old('name', $category->name) }}" required autofocus
                                       placeholder="ej: Noticias">
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-12 mb-3">
                                <label for="slug" class="form-label">Slug</label>
                                <input type="text" id="slug" name="slug"
                                       class="form-control @error('slug') is-invalid @enderror"
                                       value="{{ old('slug', $category->slug) }}">
                                <small class="form-text text-muted">Modificar el slug puede afectar URLs existentes</small>
                                @error('slug')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 mb-3">
                                <label for="description" class="form-label">Descripcion</label>
                                <textarea id="description" name="description" class="form-control" rows="3"
                                          placeholder="Descripcion opcional de la categoria">{{ old('description', $category->description) }}</textarea>
                                <small class="form-text text-muted">Maximo 500 caracteres. Sea claro y conciso</small>
                            </div>

                            <div class="col-md-12 mb-3">
                                <label for="color" class="form-label">Color</label>
                                <input type="color" id="color" name="color"
                                       class="form-control @error('color') is-invalid @enderror"
                                       value="{{ old('color', $category->color ?? '#90bb13') }}">
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
                                           value="{{ old('icon', $category->icon) }}" placeholder="fas fa-folder">
                                    <span class="input-group-text">
                                        <i id="iconPreview" class="{{ old('icon', $category->icon ?? 'fas fa-folder') }}"></i>
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
                                    <option value="1" {{ old('is_active', $category->is_active) == 1 ? 'selected' : '' }}>Activo</option>
                                    <option value="0" {{ old('is_active', $category->is_active) == 0 ? 'selected' : '' }}>Inactivo</option>
                                </select>
                                <small class="form-text text-muted">Las categorias inactivas no aparecen al crear paginas</small>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-1">
                            Guardar cambios
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
                    <h6 class="card-title mb-3">Informacion</h6>
                    <dl class="row g-0 small mb-0">
                        <dt class="col-5 text-muted">Paginas</dt>
                        <dd class="col-7">{{ $category->pages_count }}</dd>
                        <dt class="col-5 text-muted">Creada</dt>
                        <dd class="col-7">{{ $category->created_at?->format('d/m/Y') ?? '—' }}</dd>
                        <dt class="col-5 text-muted">Actualizada</dt>
                        <dd class="col-7">{{ $category->updated_at?->format('d/m/Y') ?? '—' }}</dd>
                    </dl>
                    @if($category->pages_count > 0)
                        <div class="alert alert-warning mt-3 mb-0 py-2 small">
                            <i class="fas fa-exclamation-triangle me-1"></i>
                            Esta categoria no puede eliminarse mientras tenga paginas asignadas.
                        </div>
                    @endif
                </div>
                <hr class="my-0">
                <div class="card-body">
                    <h6 class="card-title mb-3">Slug</h6>
                    <p class="card-text text-muted mb-0">
                        El slug es el identificador unico en URL. Modificarlo puede romper enlaces existentes.
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
    $('#icon').on('input', function () {
        $('#iconPreview').attr('class', $(this).val());
    });

    $('.select2').select2({ minimumResultsForSearch: Infinity });
});
</script>
@endpush
