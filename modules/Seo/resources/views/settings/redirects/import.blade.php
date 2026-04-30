@extends('layouts.theme')

@section('title', 'Importar redirecciones')

@section('page_header')
    @include('core::components.card', ['title' => 'Importar redirecciones'])
@endsection

@section('content')
    @include('core::components.alerts')

    <form action="{{ route('settings.seo.redirects.import.store') }}" method="POST" enctype="multipart/form-data" novalidate>
        @csrf

        <div class="row">

            {{-- SIDEBAR --}}
            <div class="col-lg-4 order-lg-2">

                {{-- Formato CSV --}}
                <div class="card mb-3">
                    <div class="card-header p-3 bg-white border-bottom">
                        <h5 class="mb-0 fw-bold">Formato del archivo CSV</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3">El archivo debe contener las siguientes columnas en la primera fila:</p>
                        <div class="mb-3">
                            <code class="d-block small bg-light p-2 rounded">source_path,target_path,status_code</code>
                        </div>
                        <p class="text-muted mb-2">Ejemplo de contenido:</p>
                        <pre class="bg-light p-2 rounded small mb-3" style="font-size: 0.75rem; overflow-x: auto;">/pagina-vieja,/pagina-nueva,301
/blog-antiguo,/blog,301
/temporal,/destino,302</pre>
                        <div class="mb-2">
                            <span class="badge bg-success-subtle text-success mb-1">source_path</span>
                            <p class="text-muted mb-0">Ruta origen que será redirigida.</p>
                        </div>
                        <div class="mb-2">
                            <span class="badge bg-info-subtle text-info mb-1">target_path</span>
                            <p class="text-muted mb-0">Ruta o URL destino.</p>
                        </div>
                        <div class="mb-0">
                            <span class="badge bg-warning-subtle text-warning mb-1">status_code</span>
                            <p class="text-muted mb-0">Código HTTP: 301, 302, 307 o 308. Si el valor no es válido se usará 301.</p>
                        </div>
                    </div>
                </div>

                {{-- Descargar plantilla --}}
                <div class="card">
                    <div class="card-header p-3 bg-white border-bottom">
                        <h5 class="mb-0 fw-bold">Plantilla</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3">Descarga las redirecciones actuales como CSV para usarlo de referencia o para hacer modificaciones masivas.</p>
                        <a href="{{ route('settings.seo.redirects.export') }}" class="btn btn-outline-success w-100">
                            <i class="fas fa-download me-1"></i> Descargar redirecciones actuales
                        </a>
                    </div>
                </div>

            </div>

            {{-- CONTENIDO PRINCIPAL --}}
            <div class="col-lg-8 order-lg-1">
                <div class="card">
                    <div class="card-header p-3 bg-white border-bottom">
                        <h5 class="mb-0 fw-bold">Importar redirecciones</h5>
                    </div>
                    <div class="card-body">

                        {{-- Archivo CSV --}}
                        <div class="mb-4">
                            <label class="form-label fw-semibold" for="csv_file">
                                <i class="fas fa-file-csv me-1"></i> Archivo CSV <span class="text-danger">*</span>
                            </label>
                            <input type="file"
                                   class="form-control @error('csv_file') is-invalid @enderror"
                                   id="csv_file"
                                   name="csv_file"
                                   accept=".csv,.txt"
                                   required>
                            @error('csv_file')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @else
                                <div class="form-text">Archivos .csv o .txt. Tamaño máximo: 2 MB.</div>
                            @enderror
                        </div>

                        {{-- Opcion skip_existing --}}
                        <div class="mb-4">
                            <div class="form-check">
                                <input type="checkbox"
                                       class="form-check-input"
                                       id="skip_existing"
                                       name="skip_existing"
                                       value="1"
                                       checked>
                                <label class="form-check-label fw-semibold" for="skip_existing">
                                    Omitir redirecciones ya existentes
                                </label>
                            </div>
                            <div class="form-text ms-4">
                                Si está marcado, las redirecciones cuya ruta origen ya existe no serán modificadas. Si está desmarcado, se actualizarán con los datos del archivo.
                            </div>
                        </div>

                        <div class="alert alert-warning border-0 bg-warning-subtle">
                            <div class="d-flex align-items-start gap-2">
                                <i class="fas fa-exclamation-triangle text-warning mt-1"></i>
                                <div>
                                    <small class="fw-semibold d-block">Importante</small>
                                    <small>La importación limpiará la caché de redirecciones al finalizar. Las redirecciones importadas se activarán automáticamente.</small>
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="card-footer gap-2">
                        <button type="submit" class="btn btn-primary w-100 py-2">
                            <i class="fas fa-upload me-1"></i> Importar
                        </button>
                        <a href="{{ route('settings.seo.redirects.index') }}" class="btn btn-light border w-100 py-2">
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
});
</script>
@endpush
