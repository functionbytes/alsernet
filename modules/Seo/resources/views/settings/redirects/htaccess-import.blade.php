@extends('layouts.theme')

@section('title', 'Importar desde .htaccess')

@section('content')
    @include('core::components.card', ['title' => 'Importar desde .htaccess'])

    @include('core::components.alerts')

    <form action="{{ route('setting.seo.redirects.htaccess-import.store') }}" method="POST" novalidate>
        @csrf

        <div class="row">

            {{-- SIDEBAR --}}
            <div class="col-lg-4 order-lg-2">
                <div class="card mb-3">
                    <div class="card-header p-3 bg-white border-bottom">
                        <h5 class="mb-0 fw-bold">Formato soportado</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3">Se reconocen las siguientes directivas de Apache:</p>

                        <p class="small fw-semibold mb-1">Redirect simple:</p>
                        <pre class="bg-light p-2 rounded small mb-3" style="font-size: 0.75rem;">Redirect 301 /old-page /new-page
RedirectPermanent /another-old /another-new</pre>

                        <p class="small fw-semibold mb-1">RewriteRule con redirección:</p>
                        <pre class="bg-light p-2 rounded small mb-3" style="font-size: 0.75rem;">RewriteRule ^blog/old-slug$ /blog/new-slug [R=301,L]</pre>

                        <div class="alert alert-info border-0 bg-info-subtle p-2 mb-0">
                            <small><i class="fas fa-info-circle me-1"></i>Las líneas que empiecen con <code>#</code> y las líneas vacías son ignoradas. Solo se importan redirecciones con códigos 301, 302, 307 o 308.</small>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header p-3 bg-white border-bottom">
                        <h5 class="mb-0 fw-bold">Otras opciones</h5>
                    </div>
                    <div class="card-body">
                        <a href="{{ route('setting.seo.redirects.import') }}" class="btn btn-outline-primary w-100 mb-2">
                            <i class="fas fa-file-csv me-1"></i> Importar desde CSV
                        </a>
                        <a href="{{ route('setting.seo.redirects.export') }}" class="btn btn-outline-success w-100">
                            <i class="fas fa-download me-1"></i> Exportar redirecciones
                        </a>
                    </div>
                </div>
            </div>

            {{-- CONTENIDO PRINCIPAL --}}
            <div class="col-lg-8 order-lg-1">
                <div class="card">
                    <div class="card-header p-3 bg-white border-bottom">
                        <h5 class="mb-0 fw-bold"><i class="fas fa-file-import me-1"></i> Importar desde .htaccess</h5>
                    </div>
                    <div class="card-body">

                        <div class="mb-4">
                            <label class="form-label fw-semibold" for="htaccess_content">
                                Contenido del .htaccess <span class="text-danger">*</span>
                            </label>
                            <textarea
                                class="form-control font-monospace @error('htaccess_content') is-invalid @enderror"
                                id="htaccess_content"
                                name="htaccess_content"
                                rows="20"
                                placeholder="Pega aquí el contenido completo de tu archivo .htaccess..."
                                required>{{ old('htaccess_content') }}</textarea>
                            @error('htaccess_content')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @else
                                <div class="form-text">Pega el contenido del archivo .htaccess. Solo se procesarán las directivas de redirección.</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <div class="form-check">
                                <input type="checkbox"
                                       class="form-check-input"
                                       id="skip_existing"
                                       name="skip_existing"
                                       value="1"
                                       {{ old('skip_existing', '1') ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="skip_existing">
                                    Omitir redirecciones ya existentes
                                </label>
                            </div>
                            <div class="form-text ms-4">
                                Si está marcado, las rutas origen que ya existen no se modificarán. Si está desmarcado, se actualizarán con los datos importados.
                            </div>
                        </div>

                        <div class="alert alert-warning border-0 bg-warning-subtle">
                            <div class="d-flex align-items-start gap-2">
                                <i class="fas fa-exclamation-triangle text-warning mt-1"></i>
                                <div>
                                    <small class="fw-semibold d-block">Importante</small>
                                    <small>Las redirecciones importadas se activarán automáticamente. Los cambios son permanentes y no se pueden deshacer masivamente.</small>
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="card-footer d-flex flex-column gap-2">
                        <button type="submit" class="btn btn-primary w-100 py-2">
                            <i class="fas fa-upload me-1"></i> Importar redirecciones
                        </button>
                        <a href="{{ route('setting.seo.redirects.index') }}" class="btn btn-light border w-100 py-2">
                            <i class="fas fa-arrow-left me-1"></i> Volver a redirecciones
                        </a>
                    </div>
                </div>
            </div>

        </div>

    </form>
@endsection
