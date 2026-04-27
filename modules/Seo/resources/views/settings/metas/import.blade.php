@extends('layouts.theme')

@section('title', 'Importar metas SEO')

@section('content')
    @include('core::components.card', ['title' => 'Importar metas SEO'])

    @include('core::components.alerts')

    <form action="{{ route('settings.seo.metas.import') }}" method="POST" enctype="multipart/form-data" novalidate>
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
                            <code class="d-block small bg-light p-2 rounded">seoable_type,seoable_id,title,description,keywords,og_title,og_description,og_image,og_type,twitter_card,robots</code>
                        </div>
                        <p class="text-muted mb-2">Ejemplo de contenido:</p>
                        <pre class="bg-light p-2 rounded small mb-3" style="font-size: 0.75rem; overflow-x: auto;">Page,1,Mi Página,Descripción aquí,kw1 kw2,Mi Página OG,,,,summary,index,follow
BlogPost,5,Mi Post,Descripción del post,blog seo,,,,,summary_large_image,index,follow</pre>
                        <div class="mb-2">
                            <span class="badge bg-danger-subtle text-danger mb-1">seoable_type</span>
                            <p class="text-muted mb-0">Debe ser <strong>Page</strong> o <strong>BlogPost</strong>.</p>
                        </div>
                        <div class="mb-2">
                            <span class="badge bg-warning-subtle text-warning mb-1">seoable_id</span>
                            <p class="text-muted mb-0">ID numérico del modelo asociado.</p>
                        </div>
                        <div class="mb-0">
                            <span class="badge bg-info-subtle text-info mb-1">robots</span>
                            <p class="text-muted mb-0">Ej: <code>index,follow</code> o <code>noindex,follow</code>.</p>
                        </div>
                    </div>
                </div>

                {{-- Descargar export --}}
                <div class="card">
                    <div class="card-header p-3 bg-white border-bottom">
                        <h5 class="mb-0 fw-bold">Plantilla</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3">Descarga las metas SEO actuales como CSV para usarlo de referencia o para hacer modificaciones masivas.</p>
                        <a href="{{ route('settings.seo.metas.export') }}" class="btn btn-outline-success w-100">
                            <i class="fas fa-download me-1"></i> Descargar metas actuales (CSV)
                        </a>
                    </div>
                </div>

            </div>

            {{-- CONTENIDO PRINCIPAL --}}
            <div class="col-lg-8 order-lg-1">
                <div class="card">
                    <div class="card-header p-3 bg-white border-bottom">
                        <h5 class="mb-0 fw-bold">Importar metas SEO</h5>
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
                                <div class="form-text">Archivos .csv o .txt. Tamaño máximo: 4 MB.</div>
                            @enderror
                        </div>

                        {{-- Opción update_existing --}}
                        <div class="mb-4">
                            <div class="form-check">
                                <input type="checkbox"
                                       class="form-check-input"
                                       id="update_existing"
                                       name="update_existing"
                                       value="1">
                                <label class="form-check-label fw-semibold" for="update_existing">
                                    Actualizar registros existentes
                                </label>
                            </div>
                            <div class="form-text ms-4">
                                Si está marcado, las metas SEO ya existentes serán actualizadas con los datos del archivo. Si está desmarcado, se omitirán.
                            </div>
                        </div>

                        <div class="alert alert-warning border-0 bg-warning-subtle">
                            <div class="d-flex align-items-start gap-2">
                                <i class="fas fa-exclamation-triangle text-warning mt-1"></i>
                                <div>
                                    <small class="fw-semibold d-block">Importante</small>
                                    <small>El campo <strong>seoable_type</strong> debe ser exactamente <strong>Page</strong> o <strong>BlogPost</strong>. Registros con tipos no reconocidos serán contados como errores.</small>
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="card-footer gap-2">
                        <button type="submit" class="btn btn-primary w-100 py-2">
                            <i class="fas fa-upload me-1"></i> Importar
                        </button>
                        <a href="{{ route('settings.seo.metas.index') }}" class="btn btn-light border w-100 py-2">
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
