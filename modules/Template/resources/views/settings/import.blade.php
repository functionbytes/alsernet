@extends('layouts.theme')

@section('title', 'Importar plantilla desde ZIP')

@section('content')

    @include('core::components.card', ['title' => 'Importar plantilla desde ZIP'])

    @include('core::components.alerts')

    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-body">

                    {{-- Encabezado --}}
                    <div class="mb-4 text-center">
                        <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3"
                             style="width: 80px; height: 80px; background: rgba(144, 187, 19, 0.1);">
                            <i class="fas fa-file-zipper fa-3x" style="color: #90bb13;"></i>
                        </div>
                        <h5 class="fw-bold">Importar plantilla desde ZIP</h5>
                        <p class="text-muted mb-0">
                            Sube un archivo ZIP con la estructura de tu plantilla para instalarla en el sistema.
                        </p>
                    </div>

                    <hr class="my-4">

                    {{-- Formulario --}}
                    <form id="import-form" action="{{ route('settings.templates.import') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="mb-4">
                            <label for="zip_file" class="form-label fw-semibold">Archivo ZIP de la plantilla</label>
                            <input type="file" id="zip_file" name="zip_file"
                                   class="form-control form-control-lg @error('zip_file') is-invalid @enderror"
                                   accept=".zip" required>
                            @error('zip_file')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Máximo 10 MB. Formato aceptado: .zip</small>
                        </div>

                        {{-- Cómo preparar el ZIP --}}
                        <div class="bg-light rounded-3 p-4 mb-4">
                            <h6 class="fw-bold mb-3">Cómo preparar tu plantilla</h6>
                            <div class="timeline-steps">
                                <div class="d-flex mb-3">
                                    <div class="me-3">
                                        <span class="badge bg-primary rounded-circle d-flex align-items-center justify-content-center"
                                              style="width: 32px; height: 32px;">1</span>
                                    </div>
                                    <div>
                                        <strong class="d-block">Prepara tu plantilla</strong>
                                        <small class="text-muted">Crea una carpeta con el nombre del slug de tu plantilla.</small>
                                    </div>
                                </div>
                                <div class="d-flex mb-3">
                                    <div class="me-3">
                                        <span class="badge bg-primary rounded-circle d-flex align-items-center justify-content-center"
                                              style="width: 32px; height: 32px;">2</span>
                                    </div>
                                    <div>
                                        <strong class="d-block">Añade template.json</strong>
                                        <small class="text-muted">Incluye un archivo <code>template.json</code> en la raíz con los campos <code>name</code>, <code>slug</code>, <code>description</code>, <code>author</code> y <code>version</code>.</small>
                                    </div>
                                </div>
                                <div class="d-flex mb-3">
                                    <div class="me-3">
                                        <span class="badge bg-primary rounded-circle d-flex align-items-center justify-content-center"
                                              style="width: 32px; height: 32px;">3</span>
                                    </div>
                                    <div>
                                        <strong class="d-block">Estructura de carpetas</strong>
                                        <small class="text-muted">Organiza tus archivos en <code>layouts/</code>, <code>partials/</code>, <code>assets/css/</code>, <code>assets/js/</code>.</small>
                                    </div>
                                </div>
                                <div class="d-flex">
                                    <div class="me-3">
                                        <span class="badge bg-success rounded-circle d-flex align-items-center justify-content-center"
                                              style="width: 32px; height: 32px;">
                                            <i class="fas fa-check"></i>
                                        </span>
                                    </div>
                                    <div>
                                        <strong class="d-block">Comprime en ZIP</strong>
                                        <small class="text-muted">Comprime toda la carpeta en un archivo .zip y súbelo aquí.</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Estructura esperada del ZIP --}}
                        <div class="bg-light rounded-3 p-4 mb-4">
                            <h6 class="fw-bold mb-3">Estructura esperada del ZIP</h6>
                            <div class="list-group list-group-flush">
                                @foreach([
                                    ['template.json', 'Metadatos de la plantilla', '{"name": "Mi Plantilla", ...}', true],
                                    ['layouts/default.blade.php', 'Layout principal', 'archivo Blade', true],
                                    ['partials/', 'Parciales opcionales', 'fragmentos reutilizables', false],
                                    ['assets/css/', 'Estilos CSS', 'archivos .css', false],
                                    ['assets/js/', 'Scripts JS', 'archivos .js', false],
                                    ['screenshot.png', 'Previsualización', 'imagen 800x600px', false],
                                ] as $item)
                                    <div class="list-group-item bg-white rounded mb-2 border px-3 py-2">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="me-3">
                                                <h6 class="mb-0">{{ $item[0] }}</h6>
                                                <small class="text-muted">{{ $item[1] }}</small>
                                            </div>
                                            <div class="d-flex align-items-center gap-3 text-nowrap">
                                                <code>{{ $item[2] }}</code>
                                                <span class="badge {{ $item[3] ? 'bg-primary' : 'bg-secondary' }}">{{ $item[3] ? 'requerido' : 'opcional' }}</span>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <small class="text-muted mt-2 d-block">
                                El slug del ZIP debe ser único. Si ya existe una plantilla con ese slug, se sobreescribirá.
                            </small>
                        </div>

                    </form>
                </div>

                <div class="card-footer gap-2">
                    <button type="submit" form="import-form" class="btn btn-primary w-100 py-2">
                        <i class="fas fa-upload me-2"></i>Importar plantilla
                    </button>
                    <a href="{{ route('settings.templates.index') }}" class="btn btn-light border w-100 py-2">
                        Cancelar
                    </a>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('styles')
<style>
    .form-control:focus {
        border-color: #90bb13;
        box-shadow: 0 0 0 0.2rem rgba(144, 187, 19, 0.15);
    }
</style>
@endpush
