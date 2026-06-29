@extends('layouts.theme')

@section('title', 'Nuevo idioma')

@section('page_header')
    @include('core::components.card', ['title' => 'Nuevo idioma'])
@endsection

@section('content')

    @include('core::components.alerts')

    <form action="{{ route('locales.store') }}" method="POST" id="createForm" novalidate>
        @csrf

        <div class="row g-4">

            {{-- Formulario principal --}}
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-1">Nuevo idioma</h5>
                        <p class="text-muted small mb-4">Complete la información para registrar un nuevo idioma.</p>

                        {{-- Código --}}
                        <div class="mb-3">
                            <label for="code" class="form-label fw-semibold">
                                Código <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   id="code"
                                   name="code"
                                   class="form-control @error('code') is-invalid @enderror"
                                   value="{{ old('code') }}"
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
                                   value="{{ old('name') }}"
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
                                   value="{{ old('native_name') }}"
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
                                   value="{{ old('flag') }}"
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
                                   value="{{ old('order', 0) }}"
                                   min="0">
                            @error('order')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>


                        {{-- Estado --}}
                        <div class="mb-3">
                            <label for="is_active" class="form-label fw-semibold">Estado</label>
                            <select id="is_active" name="is_active"
                                    class="form-select select2 @error('is_active') is-invalid @enderror">
                                <option value="1" {{ old('is_active', '1') ? 'selected' : '' }}>Activo</option>
                                <option value="0" {{ old('is_active') === '0' ? 'selected' : '' }}>Inactivo</option>
                            </select>
                            @error('is_active')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                    </div>

                    <div class="card-footer p-4 pt-0 border-0">
                        <button type="submit" class="btn btn-primary w-100 mb-2" id="submitBtn">
                            Guardar
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

    $('#createForm').on('submit', function () {
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
