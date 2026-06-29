@extends('layouts.theme')

@section('title', 'Crear menú')

@section('page_header')
    @include('core::components.card', ['title' => 'Crear menú'])
@endsection

@section('content')

    <div class="row g-3">

        {{-- Formulario --}}
        <div class="col-12 col-lg-8">
            <div class="card">
                <form action="{{ route('settings.menus.store') }}" method="POST" id="menuForm">
                    @csrf
                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Nuevo menú de navegación</h5>
                        <small class="text-muted">Completa la información para registrar un nuevo menú.</small>
                    </div>
                    <div class="card-body">
                        @include('core::components.alerts')

                        <div class="col-12 mb-3">
                            <label class="form-label">Nombre <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="name"
                                   class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name') }}" required autofocus
                                   placeholder="Ej: Menú principal">
                            <small class="form-text text-muted">El slug se genera automáticamente desde el nombre</small>
                            @error('name')
                                <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>

                        <div class="col-12 mb-3">
                            <label class="form-label">Slug</label>
                            <input type="text" name="slug" id="slug"
                                   class="form-control @error('slug') is-invalid @enderror"
                                   value="{{ old('slug') }}" placeholder="auto-generado">
                            <small class="form-text text-muted">Dejar vacío para autogenerar</small>
                            @error('slug')
                                <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>

                        <div class="col-12 mb-3">
                            <label class="form-label">Ubicación</label>
                            <select name="location" class="form-select select2">
                                <option value="">Sin ubicación</option>
                                @foreach($locations as $key => $label)
                                    <option value="{{ $key }}" {{ old('location') == $key ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">Zona del tema donde se mostrará el menú</small>
                            @error('location')
                                <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>

                        <div class="col-12 mb-3">
                            <label class="form-label">Estado</label>
                            <select name="status" class="form-select select2">
                                <option value="1" {{ old('status', '1') == '1' ? 'selected' : '' }}>Activo</option>
                                <option value="0" {{ old('status') == '0' ? 'selected' : '' }}>Inactivo</option>
                            </select>
                            <small class="form-text text-muted">Los menús inactivos no se muestran en el sitio</small>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-1">
                            Crear menú
                        </button>
                        <a href="{{ route('settings.menus.index') }}" class="btn btn-light w-100">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Panel informativo --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-3">¿Qué es un menú?</h6>
                    <p class="card-text text-muted">
                        Los menús agrupan enlaces de navegación que se muestran en distintas zonas del sitio, como encabezado, pie de página o barra lateral.
                    </p>
                </div>
                <hr class="my-0">
                <div class="card-body">
                    <h6 class="card-title mb-3">Slug</h6>
                    <p class="card-text text-muted mb-0">
                        El slug es el identificador único del menú. Se genera automáticamente desde el nombre si se deja vacío.
                    </p>
                </div>
                <hr class="my-0">
                <div class="card-body">
                    <h6 class="card-title mb-3">Ubicación</h6>
                    <p class="card-text text-muted mb-0">
                        Define en qué zona del tema aparecerá el menú. Las ubicaciones disponibles dependen de la plantilla activa.
                    </p>
                </div>
            </div>
        </div>

    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    $('#name').on('input', function () {
        if ($('#slug').data('manual')) { return; }
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

    $('.select2').select2({ minimumResultsForSearch: Infinity });
});
</script>
@endpush
