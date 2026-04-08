@extends('layouts.theme')

@section('title', 'Importar paginas')

@section('content')

    @include('core::components.card', ['title' => 'Importar paginas'])

    @include('core::components.alerts')

    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-body">

                    {{-- Encabezado --}}
                    <div class="mb-4 text-center">
                        <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3" style="width: 80px; height: 80px; background: rgba(66, 133, 244, 0.1);">
                            <i class="fas fa-file-import fa-3x" ></i>
                        </div>
                        <h5 class="fw-bold">Importar paginas</h5>
                        <p class="text-muted mb-0">
                            Sube un archivo CSV o JSON para importar paginas de forma masiva al sistema.
                        </p>
                    </div>

                    <hr class="my-4">

                    @if(session('import_errors'))
                        <div class="alert alert-warning">
                            <strong>Advertencias durante la importacion:</strong>
                            <ul class="mb-0 mt-1">
                                @foreach(session('import_errors') as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- Formulario --}}
                    <form id="import-form" action="{{ route('pages.import.process') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="mb-4">
                            <label for="format" class="form-label fw-semibold">Formato del archivo</label>
                            <select id="format" name="format" class="form-select select2">
                                <option value="csv">CSV</option>
                                <option value="json">JSON</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label for="file" class="form-label fw-semibold">Archivo de importacion</label>
                            <input type="file" id="file" name="file"
                                   class="form-control form-control-lg @error('file') is-invalid @enderror"
                                   accept=".csv,.txt,.json" required>
                            @error('file')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Maximo 10 MB. Formatos aceptados: .csv, .txt, .json</small>
                        </div>

                        {{-- Como preparar el archivo --}}
                        <div class="bg-light rounded-3 p-4 mb-4">
                            <h6 class="fw-bold mb-3">
                                Como preparar el archivo
                            </h6>
                            <div class="timeline-steps">
                                <div class="d-flex mb-3">
                                    <div class="me-3">
                                        <span class="badge bg-primary rounded-circle d-flex align-items-center justify-content-center"
                                              style="width: 32px; height: 32px;">1</span>
                                    </div>
                                    <div>
                                        <strong class="d-block">Descarga la plantilla</strong>
                                        <small class="text-muted">Usa la plantilla CSV de ejemplo para asegurar el formato correcto.</small>
                                    </div>
                                </div>
                                <div class="d-flex mb-3">
                                    <div class="me-3">
                                        <span class="badge bg-primary rounded-circle d-flex align-items-center justify-content-center"
                                              style="width: 32px; height: 32px;">2</span>
                                    </div>
                                    <div>
                                        <strong class="d-block">Completa los datos</strong>
                                        <small class="text-muted">Rellena las columnas obligatorias: <strong>title</strong>, <strong>slug</strong> y <strong>content</strong>.</small>
                                    </div>
                                </div>
                                <div class="d-flex mb-3">
                                    <div class="me-3">
                                        <span class="badge bg-primary rounded-circle d-flex align-items-center justify-content-center"
                                              style="width: 32px; height: 32px;">3</span>
                                    </div>
                                    <div>
                                        <strong class="d-block">Guarda como CSV o JSON</strong>
                                        <small class="text-muted">Exporta el archivo en formato CSV (separado por comas) o JSON.</small>
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
                                        <strong class="d-block">Sube el archivo aqui</strong>
                                        <small class="text-muted">Las paginas se crearan automaticamente con los datos proporcionados.</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Formato esperado --}}
                        <div class="bg-light rounded-3 p-4 mb-4">
                            <h6 class="fw-bold mb-3">Columnas esperadas del CSV</h6>
                            <div class="list-group list-group-flush">
                                @foreach([
                                    ['title', 'Titulo de la pagina', 'Mi pagina', 'Texto', true],
                                    ['slug', 'URL amigable', 'mi-pagina', 'Texto sin espacios', true],
                                    ['content', 'Contenido HTML', '<p>Texto...</p>', 'HTML valido', true],
                                    ['status', 'Estado de publicacion', 'draft', 'draft / published', false],
                                    ['template', 'Plantilla', 'default', 'Nombre de plantilla', false],
                                    ['description', 'Meta descripcion', 'Descripcion SEO...', 'Texto plano', false],
                                    ['published_at', 'Fecha de publicacion', '2026-01-15', 'Formato YYYY-MM-DD', false],
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
                        </div>

                    </form>
                </div>

                <div class="card-footer gap-2">
                    <button type="submit" form="import-form" class="btn btn-primary w-100 mb-1">
                        Importar paginas
                    </button>
                    <a href="{{ route('pages.export.download', ['format' => 'csv']) }}" class="btn btn-secondary w-100 mb-1">
                        Descargar plantilla CSV
                    </a>
                    <a href="{{ route('pages.index') }}" class="btn btn-light border w-100">
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

@push('scripts')
<script>
$(document).ready(function () {
    $('#format').select2({ width: '100%', minimumResultsForSearch: Infinity });

    @if(session('success'))
    toastr.success('{{ session('success') }}', 'Exito');
    @endif
    @if(session('error'))
    toastr.error('{{ session('error') }}', 'Error');
    @endif
});
</script>
@endpush
