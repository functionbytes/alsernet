@extends('layouts.theme')

@section('title', 'Exportar paginas')

@section('content')

    @include('core::components.card', ['title' => 'Exportar paginas'])

    @include('core::components.alerts')

    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-body">

                    {{-- Encabezado --}}
                    <div class="mb-4 text-center">
                        <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3" style="width: 80px; height: 80px; background: rgba(66, 133, 244, 0.1);">
                            <i class="fas fa-file-export fa-3x" ></i>
                        </div>
                        <h5 class="fw-bold">Exportar paginas</h5>
                        <p class="text-muted mb-0">
                            Descarga todas tus paginas en formato CSV o JSON para respaldo o migracion.
                        </p>
                    </div>

                    <hr class="my-4">

                    {{-- Opciones de exportacion --}}
                    <form id="export-form" method="GET">

                        <div class="mb-4">
                            <label for="format" class="form-label fw-semibold">Formato de exportacion</label>
                            <select id="format" name="format" class="form-select select2">
                                <option value="csv">CSV</option>
                                <option value="json">JSON</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">Filtrar por estado</label>
                            <select id="status" name="status" class="form-select select2">
                                <option value="">Todas las paginas</option>
                                <option value="published">Solo publicadas</option>
                                <option value="draft">Solo borradores</option>
                            </select>
                        </div>

                        {{-- Que se exporta --}}
                        <div class="bg-light rounded-3 p-4 mb-4">
                            <h6 class="fw-bold mb-3">
                                Que se incluye en la exportacion
                            </h6>
                            <div class="timeline-steps">
                                <div class="d-flex mb-3">
                                    <div class="me-3">
                                        <span class="badge bg-primary rounded-circle d-flex align-items-center justify-content-center"
                                              style="width: 32px; height: 32px;">1</span>
                                    </div>
                                    <div>
                                        <strong class="d-block">Datos basicos</strong>
                                        <small class="text-muted">Titulo, slug, estado, plantilla y fecha de publicacion.</small>
                                    </div>
                                </div>
                                <div class="d-flex mb-3">
                                    <div class="me-3">
                                        <span class="badge bg-primary rounded-circle d-flex align-items-center justify-content-center"
                                              style="width: 32px; height: 32px;">2</span>
                                    </div>
                                    <div>
                                        <strong class="d-block">Contenido</strong>
                                        <small class="text-muted">Contenido HTML completo de cada pagina.</small>
                                    </div>
                                </div>
                                <div class="d-flex mb-3">
                                    <div class="me-3">
                                        <span class="badge bg-primary rounded-circle d-flex align-items-center justify-content-center"
                                              style="width: 32px; height: 32px;">3</span>
                                    </div>
                                    <div>
                                        <strong class="d-block">Metadatos SEO</strong>
                                        <small class="text-muted">Meta descripcion y otros campos SEO configurados.</small>
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
                                        <strong class="d-block">Compatible con importacion</strong>
                                        <small class="text-muted">El archivo exportado se puede reimportar directamente en el sistema.</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Columnas del CSV --}}
                        <div class="bg-light rounded-3 p-4 mb-4">
                            <h6 class="fw-bold mb-3">Columnas incluidas en el archivo</h6>
                            <div class="list-group list-group-flush">
                                @foreach([
                                    ['title', 'Titulo de la pagina', 'Mi pagina', 'Texto', true],
                                    ['slug', 'URL amigable', 'mi-pagina', 'Texto sin espacios', true],
                                    ['content', 'Contenido HTML', '<p>Texto...</p>', 'HTML valido', true],
                                    ['status', 'Estado de publicacion', 'published', 'draft / published', false],
                                    ['template', 'Plantilla utilizada', 'default', 'Nombre de plantilla', false],
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
                    <button type="button" id="btn-export" class="btn btn-primary w-100 mb-2">
                        Exportar paginas
                    </button>
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
    $('#status').select2({ width: '100%', minimumResultsForSearch: Infinity });

    $('#btn-export').on('click', function () {
        const format = $('#format').val();
        const status = $('#status').val();
        const params = new URLSearchParams({ format });
        if (status) params.append('status', status);
        window.location.href = '{{ route('pages.export.download') }}?' + params.toString();
    });
});
</script>
@endpush
