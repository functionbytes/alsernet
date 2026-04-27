@extends('layouts.theme')

@section('title', 'Crear redirección')

@section('content')
    @include('core::components.card', ['title' => 'Crear redirección'])

    @include('core::components.alerts')

    <form id="formRedirect" action="{{ route('settings.seo.redirects.store') }}" method="POST" novalidate>
        @csrf

        <div class="row">

            {{-- SIDEBAR --}}
            <div class="col-lg-4 order-lg-2">

                {{-- Tipos de redirección --}}
                <div class="card mb-3">
                    <div class="card-header p-3 bg-white border-bottom">
                        <h5 class="mb-0 fw-bold">Tipos de redirección</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <span class="badge bg-success-subtle text-success mb-1">301 — Permanente</span>
                            <p class="text-muted mb-0">Transfiere el 90-99% del valor SEO. <strong>Recomendado para cambios definitivos.</strong></p>
                        </div>
                        <div class="mb-3">
                            <span class="badge bg-info-subtle text-info mb-1">302 — Temporal</span>
                            <p class="text-muted mb-0">No transfiere valor SEO. Útil para mantenimiento o pruebas.</p>
                        </div>
                        <div class="mb-3">
                            <span class="badge bg-warning-subtle text-warning mb-1">307 — Temporal (preserva método)</span>
                            <p class="text-muted mb-0">Similar a 302 pero garantiza que el método HTTP se mantenga.</p>
                        </div>
                        <div class="mb-0">
                            <span class="badge bg-primary-subtle text-primary mb-1">308 — Permanente (preserva método)</span>
                            <p class="text-muted mb-0">Similar a 301 pero garantiza que el método HTTP se mantenga.</p>
                        </div>
                    </div>
                </div>

                {{-- Ejemplos --}}
                <div class="card mb-3">
                    <div class="card-header p-3 bg-white border-bottom">
                        <h5 class="mb-0 fw-bold">Ejemplos de uso</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <strong class="small d-block mb-1">Cambio de estructura:</strong>
                            <code class="d-block small text-primary">/blog/articulo-viejo</code>
                            <i class="fas fa-arrow-down text-muted"></i>
                            <code class="d-block small text-muted">/articulos/articulo-nuevo</code>
                        </div>
                        <div class="mb-3">
                            <strong class="small d-block mb-1">Redirección externa:</strong>
                            <code class="d-block small text-primary">/contacto</code>
                            <i class="fas fa-arrow-down text-muted"></i>
                            <code class="d-block small text-muted">https://ejemplo.com/contacto</code>
                        </div>
                        <div class="mb-0">
                            <strong class="small d-block mb-1">Página eliminada:</strong>
                            <code class="d-block small text-primary">/producto-descontinuado</code>
                            <i class="fas fa-arrow-down text-muted"></i>
                            <code class="d-block small text-muted">/productos</code>
                        </div>
                    </div>
                </div>

                {{-- Mejores prácticas --}}
                <div class="card">
                    <div class="card-header p-3 bg-white border-bottom">
                        <h5 class="mb-0 fw-bold">Mejores prácticas</h5>
                    </div>
                    <div class="card-body">
                        <ul class="text-muted mb-0">
                            <li class="mb-2">Usa 301 para cambios permanentes de contenido</li>
                            <li class="mb-2">Evita cadenas de redirecciones (A → B → C)</li>
                            <li class="mb-2">Redirige a contenido similar o relevante</li>
                            <li class="mb-2">Mantén las redirecciones activas al menos 1 año</li>
                            <li class="mb-0">Verifica que la ruta destino existe antes de crear</li>
                        </ul>
                    </div>
                </div>

            </div>

            {{-- CONTENIDO PRINCIPAL --}}
            <div class="col-lg-8 order-lg-1">
                <div class="card">
                    <div class="card-header p-3 bg-white border-bottom">
                        <h5 class="mb-0 fw-bold">Datos de la redirección</h5>
                    </div>
                    <div class="card-body">

                        {{-- Ruta origen --}}
                        <div class="col-md-12 mb-4">
                            <label class="form-label fw-semibold">
                                Ruta origen <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control @error('source_path') is-invalid @enderror"
                                   id="sourcePath"
                                   name="source_path"
                                   value="{{ old('source_path', request('source_path')) }}"
                                   required
                                   placeholder="/pagina-antigua"
                                   maxlength="255">
                            @error('source_path')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @else
                                <div class="form-text">La URL que dejará de funcionar o que quieres redirigir.</div>
                            @enderror
                        </div>

                        {{-- Ruta destino --}}
                        <div class="col-md-12 mb-4">
                            <label class="form-label fw-semibold">
                                Ruta destino <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control @error('target_path') is-invalid @enderror"
                                   id="targetPath"
                                   name="target_path"
                                   value="{{ old('target_path') }}"
                                   required
                                   placeholder="/pagina-nueva"
                                   maxlength="255">
                            @error('target_path')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @else
                                <div class="form-text">Puede ser una ruta relativa (<code>/nueva-ruta</code>) o una URL completa.</div>
                            @enderror
                        </div>

                            {{-- Tipo de redirección --}}
                            <div class="col-md-12 mb-4">
                                <label class="form-label fw-semibold">
                                    Tipo de redirección <span class="text-danger">*</span>
                                </label>
                                <select class="form-select select2 @error('status_code') is-invalid @enderror"
                                        id="statusCode"
                                        name="status_code"
                                        required>
                                    <option value="301" {{ old('status_code', '301') == '301' ? 'selected' : '' }}>301 — Permanente</option>
                                    <option value="302" {{ old('status_code') == '302' ? 'selected' : '' }}>302 — Temporal</option>
                                    <option value="307" {{ old('status_code') == '307' ? 'selected' : '' }}>307 — Temporal (preserva método)</option>
                                    <option value="308" {{ old('status_code') == '308' ? 'selected' : '' }}>308 — Permanente (preserva método)</option>
                                </select>
                                @error('status_code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @else
                                    <div class="form-text">301/308 para cambios definitivos, 302/307 para temporales.</div>
                                @enderror
                            </div>

                            {{-- Estado --}}
                            <div class="col-md-12 mb-4">
                                <label class="form-label fw-semibold">
                                    Estado <span class="text-danger">*</span>
                                </label>
                                <select class="form-select select2 @error('is_active') is-invalid @enderror"
                                        id="isActive"
                                        name="is_active"
                                        required>
                                    <option value="1" {{ old('is_active', '1') == '1' ? 'selected' : '' }}>Activo</option>
                                    <option value="0" {{ old('is_active') == '0' ? 'selected' : '' }}>Inactivo</option>
                                </select>
                                @error('is_active')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @else
                                    <div class="form-text">Solo las redirecciones activas se aplican.</div>
                                @enderror
                            </div>

                            {{-- Patrón regex --}}
                            <div class="col-md-12 mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_regex" id="is_regex" value="1"
                                           {{ old('is_regex') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_regex">
                                        <i class="fas fa-code"></i> Patrón regex
                                        <small class="text-muted ms-1">— permite usar expresiones regulares en la ruta de origen</small>
                                    </label>
                                </div>
                                @error('is_regex')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Patrón wildcard --}}
                            <div class="col-md-12 mb-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_wildcard" id="is_wildcard" value="1"
                                           {{ old('is_wildcard') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_wildcard">
                                        <i class="fas fa-asterisk"></i> Patrón wildcard
                                        <small class="text-muted ms-1">— usa * para coincidir con cualquier texto (ej: <code>/blog/*/old</code>)</small>
                                    </label>
                                </div>
                                @error('is_wildcard')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Rango de fechas --}}
                            <div class="row mb-3" id="date-range-fields">
                                <div class="col-md-12">
                                    <label class="form-label">Activo desde <small class="text-muted">(opcional)</small></label>
                                    <input type="datetime-local" name="active_from" class="form-control @error('active_from') is-invalid @enderror"
                                           value="{{ old('active_from') }}">
                                    @error('active_from')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Activo hasta <small class="text-muted">(opcional)</small></label>
                                    <input type="datetime-local" name="active_until" class="form-control @error('active_until') is-invalid @enderror"
                                           value="{{ old('active_until') }}">
                                    @error('active_until')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @else
                                        <small class="text-muted">Si se establece, la redirección expirará automáticamente</small>
                                    @enderror
                                </div>
                            </div>

                    </div>

                    <div class="card-footer gap-2">
                        <button type="submit" id="connect-google" class="btn btn-primary w-100 py-2">
                            Crear redirección
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
