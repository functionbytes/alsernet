@extends('layouts.theme')

@section('title', 'Nuevo formulario')

@section('content')
    @include('core::components.card', ['title' => 'Nuevo formulario'])

    @include('core::components.alerts')

    <form action="{{ route('settings.forms.store') }}" method="POST" id="createForm" novalidate>
        @csrf

        <div class="row">

            {{-- SIDEBAR --}}
            <div class="col-lg-4 order-lg-2">

                {{-- Próximos pasos --}}
                <div class="card mb-3">
                    <div class="card-header p-3 bg-white border-bottom">
                        <h5 class="mb-0 fw-bold">Próximos pasos</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3">Una vez creado el formulario podrás:</p>
                        <ul class="list-unstyled small text-muted mb-0">
                            <li class="mb-2">Agregar campos personalizados (texto, email, selects, etc.)</li>
                            <li class="mb-2">Configurar notificaciones por email</li>
                            <li class="mb-2">Controlar el acceso (público, privado, por roles)</li>
                            <li class="mb-0">Insertar el formulario con un shortcode</li>
                        </ul>
                    </div>
                </div>

                {{-- Nota --}}
                <div class="card">
                    <div class="card-header p-3 bg-white border-bottom">
                        <h5 class="mb-0 fw-bold">Nota importante</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-0">
                            El formulario se creará <strong>inactivo</strong> hasta que añadas campos y lo actives manualmente.
                        </p>
                    </div>
                </div>

            </div>

            {{-- CONTENIDO PRINCIPAL --}}
            <div class="col-lg-8 order-lg-1">
                <div class="card">
                    <div class="card-header p-3 bg-white border-bottom">
                        <h5 class="mb-0 fw-bold">Datos del formulario</h5>
                    </div>
                    <div class="card-body">

                        {{-- Nombre --}}
                        <div class="mb-4">
                            <label for="name" class="form-label fw-semibold">
                                Nombre <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   id="name"
                                   name="name"
                                   class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name') }}"
                                   placeholder="Ej: Formulario de contacto"
                                   autofocus
                                   required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @else
                                <div class="form-text">Nombre interno para identificar el formulario.</div>
                            @enderror
                        </div>

                        {{-- Categoría --}}
                        <div class="mb-4">
                            <label for="category_id" class="form-label fw-semibold">Categoría</label>
                            <select id="category_id" name="category_id" class="form-select select2 @error('category_id') is-invalid @enderror">
                                <option value="">Sin categoría</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('category_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @else
                                <div class="form-text">Opcional. Agrupa formularios por categoría.</div>
                            @enderror
                        </div>

                        {{-- Descripción --}}
                        <div class="mb-4">
                            <label for="description" class="form-label fw-semibold">Descripción</label>
                            <textarea id="description"
                                      name="description"
                                      class="form-control @error('description') is-invalid @enderror"
                                      rows="3"
                                      placeholder="Descripción interna del formulario (opcional)">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @else
                                <div class="form-text">Solo visible en el panel de administración.</div>
                            @enderror
                        </div>

                        {{-- Mensaje de éxito --}}
                        <div class="mb-4">
                            <label for="success_message" class="form-label fw-semibold">Mensaje de éxito</label>
                            <textarea id="success_message"
                                      name="success_message"
                                      class="form-control @error('success_message') is-invalid @enderror"
                                      rows="3"
                                      placeholder="Mensaje que verá el usuario tras enviar el formulario">{{ old('success_message', '¡Gracias! Tu formulario ha sido enviado correctamente.') }}</textarea>
                            @error('success_message')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @else
                                <div class="form-text">Se muestra al usuario después de enviar el formulario correctamente.</div>
                            @enderror
                        </div>

                        {{-- Estado --}}
                        <div class="mb-4">
                            <label for="is_active" class="form-label fw-semibold">
                                Estado <span class="text-danger">*</span>
                            </label>
                            <select id="is_active" name="is_active" class="form-select select2">
                                <option value="1" {{ old('is_active', '1') === '1' ? 'selected' : '' }}>Activo</option>
                                <option value="0" {{ old('is_active') === '0' ? 'selected' : '' }}>Inactivo</option>
                            </select>
                            @error('is_active')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @else
                                <div class="form-text">Solo los formularios activos son visibles al público.</div>
                            @enderror
                        </div>

                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-1" id="submitBtn">
                            Guardar
                        </button>
                        <a href="{{ route('settings.forms.index') }}" class="btn btn-secondary w-100">
                            Cancelar
                        </a>
                    </div>

                </div>
            </div>

        </div>

    </form>

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    @if(session('success'))
        toastr.success('{{ session('success') }}', 'Éxito');
    @endif
    @if(session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif

    $('#createForm').on('submit', function () {
        $('#submitBtn')
            .prop('disabled', true)
            .html('<i class="fas fa-spinner fa-spin me-1"></i> Guardando...');
    });
});
</script>
@endpush
