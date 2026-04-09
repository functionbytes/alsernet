@extends('layouts.theme')

@section('title', 'Editar idioma')

@section('content')

    @include('core::components.card', ['title' => 'Editar idioma'])

    @include('core::components.alerts')

    <form action="{{ route('locales.update', $locale) }}" method="POST" id="editForm" novalidate>
        @csrf
        @method('PUT')

        <div class="row g-4">

            {{-- Formulario principal --}}
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-1">Editar idioma</h5>
                        <p class="text-muted small mb-4">Actualice la información del idioma.</p>

                        @if ($locale->is_default)
                            <div class="alert alert-warning d-flex align-items-center gap-2 mb-4">
                                <i class="fas fa-circle-info"></i>
                                <span>Este idioma es el predeterminado del sistema</span>
                            </div>
                        @endif

                        {{-- Código --}}
                        <div class="mb-3">
                            <label for="code" class="form-label fw-semibold">
                                Código <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   id="code"
                                   name="code"
                                   class="form-control @error('code') is-invalid @enderror"
                                   value="{{ old('code', $locale->code) }}"
                                   placeholder="es"
                                   maxlength="10"
                                   autofocus
                                   required>
                            @error('code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @else
                                <div class="form-text">Código ISO (es, en, pt, fr...)</div>
                            @enderror
                        </div>

                        {{-- Nombre en inglés --}}
                        <div class="mb-3">
                            <label for="name" class="form-label fw-semibold">
                                Nombre en inglés <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   id="name"
                                   name="name"
                                   class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name', $locale->name) }}"
                                   placeholder="Spanish"
                                   required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Nombre nativo --}}
                        <div class="mb-3">
                            <label for="native_name" class="form-label fw-semibold">
                                Nombre nativo <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   id="native_name"
                                   name="native_name"
                                   class="form-control @error('native_name') is-invalid @enderror"
                                   value="{{ old('native_name', $locale->native_name) }}"
                                   placeholder="Español"
                                   required>
                            @error('native_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Bandera --}}
                        <div class="mb-3">
                            <label for="flag" class="form-label fw-semibold">Bandera</label>
                            <input type="text"
                                   id="flag"
                                   name="flag"
                                   class="form-control @error('flag') is-invalid @enderror"
                                   value="{{ old('flag', $locale->flag) }}"
                                   placeholder="🇪🇸">
                            @error('flag')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @else
                                <div class="form-text">Emoji de bandera del idioma</div>
                            @enderror
                        </div>

                        {{-- Orden --}}
                        <div class="mb-3">
                            <label for="order" class="form-label fw-semibold">Orden</label>
                            <input type="number"
                                   id="order"
                                   name="order"
                                   class="form-control @error('order') is-invalid @enderror"
                                   value="{{ old('order', $locale->order) }}"
                                   min="0">
                            @error('order')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>


                        {{-- Estado --}}
                        <div class="mb-3">
                            <label for="is_active" class="form-label fw-semibold">Estado</label>
                            <select id="is_active" name="is_active"
                                    class="form-select select2 @error('is_active') is-invalid @enderror"
                                    {{ $locale->is_default ? 'disabled' : '' }}>
                                <option value="1" {{ old('is_active', $locale->is_active) ? 'selected' : '' }}>Activo</option>
                                <option value="0" {{ !old('is_active', $locale->is_active) ? 'selected' : '' }}>Inactivo</option>
                            </select>
                            @if ($locale->is_default)
                                <div class="form-text">El idioma predeterminado siempre está activo</div>
                            @endif
                            @error('is_active')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                    </div>

                    <div class="card-footer p-4 pt-0 border-0">
                        <button type="submit" class="btn btn-primary w-100 mb-2" id="submitBtn">
                            Guardar cambios
                        </button>
                        <a href="{{ route('locales.index') }}" class="btn btn-secondary w-100">
                            Cancelar
                        </a>
                    </div>
                </div>
            </div>

            {{-- Sidebar informativo --}}
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-body p-4">
                        <h6 class="fw-bold mb-2">¿Qué es un idioma?</h6>
                        <p class="text-muted small mb-4">
                            Los idiomas definen las versiones de contenido disponibles en el sitio.
                            Cada idioma tiene un código ISO único que se usa en las URLs y traducciones.
                        </p>

                        <h6 class="fw-bold mb-2">Buenas prácticas</h6>
                        <ul class="list-unstyled small text-muted mb-0">
                            <li class="mb-2">
Usa códigos ISO estándar (es, en, pt, fr...)
                            </li>
                            <li class="mb-2">
El nombre nativo se muestra a los usuarios del sitio
                            </li>
                            <li class="mb-2">
El orden controla cómo aparecen en los selectores
                            </li>
                            <li>
Desactiva idiomas que no estén en uso
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

        </div>

    </form>

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    $('#is_active').select2({ width: '100%', minimumResultsForSearch: Infinity });

    $('#editForm').on('submit', function () {
        $('#submitBtn')
            .prop('disabled', true)
            .html('<i class="fas fa-spinner fa-spin me-1"></i> Guardando...');
    });

    @if(session('success'))
        toastr.success('{{ session('success') }}', 'Éxito');
    @endif
    @if(session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif
});
</script>
@endpush
