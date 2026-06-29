# Form Patterns (Create/Edit)

## Layout: Two-Column (Form 8 + Help 4)

```blade
@extends('layouts.theme')

@section('title', 'Crear recurso')

@section('content')

    @include('core::components.card', ['title' => 'Crear recurso'])

    <div class="row g-3">

        {{-- ========== FORM COLUMN ========== --}}
        <div class="col-12 col-lg-8">
            <div class="card">
                <form id="resourceForm" action="{{ route('resource.store') }}" method="POST">
                    @csrf
                    {{-- @method('PUT') en edit --}}

                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Nuevo recurso</h5>
                        <small class="text-muted">Complete la informacion requerida.</small>
                    </div>

                    <div class="card-body">
                        @include('core::components.alerts')

                        <div class="row">
                            {{-- Text input --}}
                            <div class="col-12 col-md-12">
                                <div class="mb-3">
                                    <label class="form-label">Nombre <span class="text-danger">*</span></label>
                                    <input type="text"
                                           class="form-control @error('name') is-invalid @enderror"
                                           name="name"
                                           value="{{ old('name') }}"
                                           placeholder="ej: Producto ABC"
                                           required>
                                    <small class="form-text text-muted">Nombre del recurso</small>
                                    @error('name')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            {{-- Textarea --}}
                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="form-label">Descripcion</label>
                                    <textarea class="form-control @error('description') is-invalid @enderror"
                                              name="description"
                                              rows="3"
                                              placeholder="Descripcion breve">{{ old('description') }}</textarea>
                                    @error('description')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            {{-- Select with Select2 --}}
                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Categoria</label>
                                    <select class="form-select select2 @error('category_id') is-invalid @enderror"
                                            name="category_id">
                                        <option value="">Seleccionar...</option>
                                        @foreach($categories as $cat)
                                            <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>
                                                {{ $cat->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('category_id')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            {{-- Active/Inactive --}}
                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Estado</label>
                                    <select class="form-select select2" name="is_active">
                                        <option value="1" {{ old('is_active', '1') == '1' ? 'selected' : '' }}>Activo</option>
                                        <option value="0" {{ old('is_active') == '0' ? 'selected' : '' }}>Inactivo</option>
                                    </select>
                                </div>
                            </div>

                            {{-- Color picker --}}
                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Color</label>
                                    <input type="color"
                                           class="form-control form-control-color @error('color') is-invalid @enderror"
                                           name="color"
                                           value="{{ old('color', '#3b82f6') }}"
                                           style="height: 38px; width: 100%;">
                                    @error('color')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            {{-- Icon with preview --}}
                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Icono</label>
                                    <div class="input-group">
                                        <input type="text" id="icon" name="icon"
                                               class="form-control @error('icon') is-invalid @enderror"
                                               value="{{ old('icon', 'fas fa-folder') }}"
                                               placeholder="fas fa-folder">
                                        <span class="input-group-text">
                                            <i id="iconPreview" class="{{ old('icon', 'fas fa-folder') }}"></i>
                                        </span>
                                    </div>
                                    <small class="form-text text-muted">Clase Font Awesome 6</small>
                                    @error('icon')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            {{-- Number --}}
                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Orden</label>
                                    <input type="number"
                                           class="form-control @error('order') is-invalid @enderror"
                                           name="order"
                                           value="{{ old('order', 0) }}"
                                           min="0">
                                    @error('order')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-1">Guardar cambios</button>
                        <a href="{{ route('resource.index') }}" class="btn btn-light w-100">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>

        {{-- ========== HELP PANEL ========== --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-3">Sobre este recurso</h6>
                    <p class="card-text text-muted">
                        Explica que es el recurso y cuando usarlo.
                    </p>
                </div>
                <hr class="my-0">
                <div class="card-body">
                    <h6 class="card-title mb-3">Campos requeridos</h6>
                    <p class="card-text text-muted mb-0">
                        Los campos marcados con * son obligatorios.
                    </p>
                </div>
            </div>
        </div>

    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    // Select2
    $('.select2').select2({ width: '100%' });

    // Icon preview
    $('#icon').on('input', function() {
        $('#iconPreview').attr('class', $(this).val());
    });

    @if(session('success'))
        toastr.success('{{ session('success') }}', 'Exito');
    @endif
});
</script>
@endpush
```

## Reglas de Forms

1. **Layout**: `col-12 col-lg-8` form + `col-lg-4` panel informativo
2. **Header**: titulo + subtitulo pequeno
3. **@include('core::components.alerts')** arriba del form body
4. **Card footer**: `btn-primary w-100 mb-1` guardar + `btn-light w-100` cancelar (stacked)
5. **Required**: asterisco rojo `<span class="text-danger">*</span>`
6. **Validation**:
   - `@error('field') is-invalid @enderror` en el input
   - `<span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>` debajo
7. **Helper text**: `<small class="form-text text-muted">` debajo del input
8. **Old values**: siempre usar `old('field', $default)` para preservar al fallar validacion
9. **Edit forms**: agregar `@method('PUT')` y pasar modelo como segundo parametro en routes
10. **Select2**: siempre `.select2` class, en modales usar `dropdownParent`

## Tipos de Campo Comunes

| Tipo | Patron |
|------|--------|
| Text | `<input type="text" class="form-control @error('name') is-invalid @enderror">` |
| Textarea | `<textarea class="form-control" rows="3">` |
| Select | `<select class="form-select select2">` |
| Color | `<input type="color" class="form-control form-control-color">` |
| Icon + preview | input + span input-group-text con icon preview |
| Date | `<input type="date" class="form-control">` |
| Date range | Two date inputs with `<span class="text-muted">—</span>` |
| Number | `<input type="number" min="0">` |
| Email | `<input type="email">` |
| Password | `<input type="password">` |
| Switch | `<div class="form-check form-switch"><input type="checkbox" class="form-check-input">` |

## Section Dividers (forms largos)

```blade
<h6 class="fw-bold mb-3 border-bottom pb-2">Informacion basica</h6>
<div class="row g-3 mb-4">
    {{-- Fields --}}
</div>

<h6 class="fw-bold mb-3 border-bottom pb-2">Configuracion avanzada</h6>
<div class="row g-3">
    {{-- Fields --}}
</div>
```

**Recuerda**: titulos de seccion capitalizan SOLO la primera palabra (`Informacion basica` NO `Informacion Basica`).
