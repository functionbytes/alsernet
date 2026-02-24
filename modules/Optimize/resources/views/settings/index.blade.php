@extends('layouts.theme')

@section('title', 'Optimización')

@section('content')
    @include('core::components.card', ['title' => 'Optimización'])

    @include('core::components.alerts')

    <form method="POST" action="{{ route('settings.optimize.update') }}">
        @csrf

        <div class="card">
            {{-- Header --}}
            <div class="card-header p-4 border-bottom">
                <h5 class="mb-1 fw-bold">Optimizar rendimiento</h5>
                <p class="small mb-0 text-muted">Configura las opciones de minificación y optimización del HTML generado por la aplicación</p>
            </div>

            {{-- Master toggle --}}
            <div class="card-body border-bottom p-4">
                <div class="mb-0">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="enabled" id="enabled"
                               value="1" {{ $get('enabled') === '1' ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="enabled">
                            Activar optimización
                        </label>
                    </div>
                    <small class="text-muted d-block mt-1">
                        Cuando está habilitado, el HTML de las páginas públicas se optimiza automáticamente.
                    </small>
                </div>
            </div>

            {{-- Options grid --}}
            <div class="card-body p-4" id="optimize-options">
                <div class="row g-3">

                    {{-- collapse_whitespace --}}
                    <div class="col-md-6">
                        <div class="card bg-light border-0 h-100">
                            <div class="card-body d-flex gap-3 align-items-start">
                                <i class="fas fa-compress fa-fw text-muted mt-1"></i>
                                <div class="flex-grow-1">
                                    <label class="fw-semibold mb-0" for="collapse_whitespace">Colapsar espacios en blanco</label>
                                    <p class="text-muted mb-0">Elimina espacios y saltos de línea innecesarios del HTML</p>
                                </div>
                                <div class="form-check form-switch mb-0 flex-shrink-0">
                                    <input class="form-check-input" type="checkbox" name="collapse_whitespace" id="collapse_whitespace"
                                           value="1" {{ $get('collapse_whitespace') === '1' ? 'checked' : '' }}>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- elide_attributes --}}
                    <div class="col-md-6">
                        <div class="card bg-light border-0 h-100">
                            <div class="card-body d-flex gap-3 align-items-start">
                                <i class="fas fa-tag fa-fw text-muted mt-1"></i>
                                <div class="flex-grow-1">
                                    <label class="fw-semibold mb-0" for="elide_attributes">Eliminar atributos por defecto</label>
                                    <p class="text-muted mb-0">Elimina valores de atributos HTML que coinciden con el valor por defecto</p>
                                </div>
                                <div class="form-check form-switch mb-0 flex-shrink-0">
                                    <input class="form-check-input" type="checkbox" name="elide_attributes" id="elide_attributes"
                                           value="1" {{ $get('elide_attributes') === '1' ? 'checked' : '' }}>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- inline_css --}}
                    <div class="col-md-6">
                        <div class="card bg-light border-0 h-100">
                            <div class="card-body d-flex gap-3 align-items-start">
                                <i class="fas fa-paint-brush fa-fw text-muted mt-1"></i>
                                <div class="flex-grow-1">
                                    <label class="fw-semibold mb-0" for="inline_css">CSS en línea</label>
                                    <p class="text-muted mb-0">Mueve los estilos inline a una clase CSS en el head del documento</p>
                                </div>
                                <div class="form-check form-switch mb-0 flex-shrink-0">
                                    <input class="form-check-input" type="checkbox" name="inline_css" id="inline_css"
                                           value="1" {{ $get('inline_css') === '1' ? 'checked' : '' }}>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- insert_dns_prefetch --}}
                    <div class="col-md-6">
                        <div class="card bg-light border-0 h-100">
                            <div class="card-body d-flex gap-3 align-items-start">
                                <i class="fas fa-server fa-fw text-muted mt-1"></i>
                                <div class="flex-grow-1">
                                    <label class="fw-semibold mb-0" for="insert_dns_prefetch">Insertar DNS prefetch</label>
                                    <p class="text-muted mb-0">Inyecta etiquetas de prefetch de DNS para acelerar la carga de recursos externos</p>
                                </div>
                                <div class="form-check form-switch mb-0 flex-shrink-0">
                                    <input class="form-check-input" type="checkbox" name="insert_dns_prefetch" id="insert_dns_prefetch"
                                           value="1" {{ $get('insert_dns_prefetch') === '1' ? 'checked' : '' }}>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- remove_comments --}}
                    <div class="col-md-6">
                        <div class="card bg-light border-0 h-100">
                            <div class="card-body d-flex gap-3 align-items-start">
                                <i class="fas fa-comment-slash fa-fw text-muted mt-1"></i>
                                <div class="flex-grow-1">
                                    <label class="fw-semibold mb-0" for="remove_comments">Eliminar comentarios HTML</label>
                                    <p class="text-muted mb-0">Elimina comentarios HTML, JS y CSS del código fuente</p>
                                </div>
                                <div class="form-check form-switch mb-0 flex-shrink-0">
                                    <input class="form-check-input" type="checkbox" name="remove_comments" id="remove_comments"
                                           value="1" {{ $get('remove_comments') === '1' ? 'checked' : '' }}>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- remove_quotes --}}
                    <div class="col-md-6">
                        <div class="card bg-light border-0 h-100">
                            <div class="card-body d-flex gap-3 align-items-start">
                                <i class="fas fa-quote-left fa-fw text-muted mt-1"></i>
                                <div class="flex-grow-1">
                                    <label class="fw-semibold mb-0" for="remove_quotes">Eliminar comillas innecesarias</label>
                                    <p class="text-muted mb-0">Elimina comillas innecesarias en atributos HTML</p>
                                </div>
                                <div class="form-check form-switch mb-0 flex-shrink-0">
                                    <input class="form-check-input" type="checkbox" name="remove_quotes" id="remove_quotes"
                                           value="1" {{ $get('remove_quotes') === '1' ? 'checked' : '' }}>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- defer_javascript --}}
                    <div class="col-md-6">
                        <div class="card bg-light border-0 h-100">
                            <div class="card-body d-flex gap-3 align-items-start">
                                <i class="fas fa-clock fa-fw text-muted mt-1"></i>
                                <div class="flex-grow-1">
                                    <label class="fw-semibold mb-0" for="defer_javascript">Diferir JavaScript</label>
                                    <p class="text-muted mb-0">Agrega el atributo defer a los scripts externos para no bloquear el renderizado</p>
                                </div>
                                <div class="form-check form-switch mb-0 flex-shrink-0">
                                    <input class="form-check-input" type="checkbox" name="defer_javascript" id="defer_javascript"
                                           value="1" {{ $get('defer_javascript') === '1' ? 'checked' : '' }}>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            {{-- Footer --}}
            <div class="card-footer p-4 border-top d-flex justify-content-end gap-2">
                <a href="javascript:history.back()" class="btn btn-outline-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i> Guardar cambios
                </button>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
<script>
$(document).ready(function () {
    var $options = $('#optimize-options');
    var $toggle  = $('#enabled');

    function syncOptions() {
        $options.toggleClass('opacity-50', !$toggle.is(':checked'));
        $options.find('input[type="checkbox"]').prop('disabled', !$toggle.is(':checked'));
    }

    syncOptions();
    $toggle.on('change', syncOptions);
});
</script>
@endpush
