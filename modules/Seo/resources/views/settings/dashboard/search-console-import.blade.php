@extends('layouts.theme')

@section('title', 'Importar Search Console')

@section('content')

    @include('core::components.card', ['title' => 'Importar datos de Google Search Console'])

    @include('core::components.alerts')

    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-body">

                    {{-- Encabezado --}}
                    <div class="mb-4 text-center">
                        <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3"
                             style="width: 80px; height: 80px; background: rgba(66, 133, 244, 0.1);">
                            <i class="fab fa-google fa-3x" ></i>
                        </div>
                        <h5 class="fw-bold">Importar datos de Search Console</h5>
                        <p class="text-muted mb-0">
                            Sube un archivo CSV exportado desde Google Search Console para vincular métricas SEO a tus páginas.
                        </p>
                    </div>

                    <hr class="my-4">

                    {{-- Formulario --}}
                    <form id="import-form" action="{{ route('settings.seo.search-console.import.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="mb-4">
                            <label for="csv_file" class="form-label fw-semibold">Archivo CSV de Search Console</label>
                            <input type="file" id="csv_file" name="csv_file"
                                   class="form-control form-control-lg @error('csv_file') is-invalid @enderror"
                                   accept=".csv,.txt" required>
                            @error('csv_file')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Máximo 5 MB. Formatos aceptados: .csv, .txt</small>
                        </div>

                        {{-- Cómo exportar --}}
                        <div class="bg-light rounded-3 p-4 mb-4">
                            <h6 class="fw-bold mb-3">
                                 Cómo exportar desde search console
                            </h6>
                            <div class="timeline-steps">
                                <div class="d-flex mb-3">
                                    <div class="me-3">
                                        <span class="badge bg-primary rounded-circle d-flex align-items-center justify-content-center"
                                              style="width: 32px; height: 32px;">1</span>
                                    </div>
                                    <div>
                                        <strong class="d-block">Google Search Console</strong>
                                        <small class="text-muted">Accede a tu propiedad en Search Console.</small>
                                    </div>
                                </div>
                                <div class="d-flex mb-3">
                                    <div class="me-3">
                                        <span class="badge bg-primary rounded-circle d-flex align-items-center justify-content-center"
                                              style="width: 32px; height: 32px;">2</span>
                                    </div>
                                    <div>
                                        <strong class="d-block">Resultados de búsqueda → Páginas</strong>
                                        <small class="text-muted">Abre el informe de Resultados de búsqueda y selecciona la pestaña <strong>Páginas</strong>.</small>
                                    </div>
                                </div>
                                <div class="d-flex mb-3">
                                    <div class="me-3">
                                        <span class="badge bg-primary rounded-circle d-flex align-items-center justify-content-center"
                                              style="width: 32px; height: 32px;">3</span>
                                    </div>
                                    <div>
                                        <strong class="d-block">Exportar → Descargar CSV</strong>
                                        <small class="text-muted">Haz clic en el botón Exportar y elige la opción CSV.</small>
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
                                        <strong class="d-block">Sube el archivo aquí</strong>
                                        <small class="text-muted">Los datos se vincularán a las páginas por su URL canónica.</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Formato esperado --}}
                        <div class="bg-light rounded-3 p-4 mb-4">
                            <h6 class="fw-bold mb-3">Formato esperado del CSV</h6>
                            <div class="list-group list-group-flush">
                                @foreach([
                                    ['Page', 'URL de la página', 'https://ejemplo.com/pagina', 'URL canónica completa', true],
                                    ['Clicks', 'Clics totales', '120', 'Número entero', true],
                                    ['Impressions', 'Impresiones', '3400', 'Número entero', true],
                                    ['CTR', 'Tasa de clics', '3.5%', 'Porcentaje', false],
                                    ['Position', 'Posición media', '8.2', 'Número decimal', false],
                                ] as $col)
                                    <div class="list-group-item bg-white rounded mb-2 border px-3 py-2">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="me-3">
                                                <h6 class="mb-0">{{ $col[0] }}</h6>
                                                <small class="text-muted">{{ $col[1] }}</small>
                                            </div>
                                            <div class="d-flex align-items-center gap-3 text-nowrap">
                                                <code>{{ $col[2] }}</code>
                                                <small class="text-muted d-none d-md-inline">{{ $col[3] }}</small>
                                                <span class="badge {{ $col[4] ? 'bg-primary' : 'bg-secondary' }}">{{ $col[4] ? 'requerido' : 'opcional' }}</span>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <small class="text-muted mt-2 d-block">
                                Se aceptan también exportaciones en español (Página, Clics, Impresiones, Posición).
                            </small>
                        </div>

                    </form>
                </div>

                <div class="card-footer gap-2">
                    <button type="submit" form="import-form" class="btn btn-primary w-100 py-2">
                        Importar datos
                    </button>
                    <a href="{{ route('settings.seo.report.index') }}" class="btn btn-light border w-100 py-2">
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
