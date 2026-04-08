@extends('layouts.theme')

@section('title', 'Importar backup JSON SEO')

@section('content')
    @include('core::components.card', ['title' => 'Importar backup JSON SEO'])

    @include('core::components.alerts')

    <form action="{{ route('setting.seo.metas.import-json.store') }}" method="POST" enctype="multipart/form-data" novalidate>
        @csrf

        <div class="row">

            {{-- SIDEBAR --}}
            <div class="col-lg-4 order-lg-2">

                <div class="card mb-3">
                    <div class="card-header p-3 bg-white border-bottom">
                        <h5 class="mb-0 fw-bold">Sobre el formato JSON</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3">El archivo JSON debe haber sido generado con la opción "Exportar JSON" de este módulo. Contiene redirects, plantillas y URLs estáticas.</p>
                        <div class="alert alert-warning p-2 small mb-3">
                            <i class="fas fa-exclamation-triangle me-1"></i>
                            Los metas existentes no se sobreescriben por defecto. Usa la opción "Omitir existentes" desmarcada para actualizar.
                        </div>
                        <a href="{{ route('setting.seo.metas.export-json') }}" class="btn btn-outline-secondary btn-sm w-100">
                            <i class="fas fa-file-code me-1"></i> Descargar backup JSON actual
                        </a>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header p-3 bg-white border-bottom">
                        <h5 class="mb-0 fw-bold">Volver</h5>
                    </div>
                    <div class="card-body">
                        <a href="{{ route('setting.seo.metas.index') }}" class="btn btn-outline-secondary w-100">
                            <i class="fas fa-arrow-left me-1"></i> Volver al listado
                        </a>
                    </div>
                </div>

            </div>

            {{-- CONTENIDO PRINCIPAL --}}
            <div class="col-lg-8 order-lg-1">
                <div class="card">
                    <div class="card-header p-3 bg-white border-bottom">
                        <h5 class="mb-0 fw-bold">Importar backup JSON</h5>
                    </div>
                    <div class="card-body">

                        <div class="mb-4">
                            <label class="form-label fw-semibold" for="json_file">
                                <i class="fas fa-file-code me-1"></i> Archivo JSON <span class="text-danger">*</span>
                            </label>
                            <input type="file"
                                   class="form-control @error('json_file') is-invalid @enderror"
                                   id="json_file"
                                   name="json_file"
                                   accept=".json,.txt"
                                   required>
                            @error('json_file')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @else
                                <div class="form-text">Archivos .json o .txt. Tamaño máximo: 10 MB.</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Datos a importar</label>
                            <div class="d-flex flex-column gap-2">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="import_redirects"
                                           name="import_redirects" value="1" checked>
                                    <label class="form-check-label" for="import_redirects">Importar redirects</label>
                                </div>
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="import_templates"
                                           name="import_templates" value="1" checked>
                                    <label class="form-check-label" for="import_templates">Importar plantillas SEO</label>
                                </div>
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="skip_existing"
                                           name="skip_existing" value="1" checked>
                                    <label class="form-check-label" for="skip_existing">Omitir registros existentes</label>
                                    <div class="form-text ms-0">Si está marcado, los registros con el mismo identificador no se modificarán.</div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="card-footer d-flex flex-column gap-2">
                        <button type="submit" class="btn btn-primary w-100 py-2">
                            <i class="fas fa-file-import me-1"></i> Importar JSON
                        </button>
                        <a href="{{ route('setting.seo.metas.index') }}" class="btn btn-light border w-100 py-2">
                            Cancelar
                        </a>
                    </div>

                </div>
            </div>

        </div>

    </form>

@endsection
