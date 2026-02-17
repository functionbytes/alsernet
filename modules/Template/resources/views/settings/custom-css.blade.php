@extends('layouts.theme')

@section('page_title', __('template::template.custom_css_editor'))

@section('content')
    <div class="page-wrapper">
        <div class="container-xl">
            @include('core::components.alerts')

            <div class="row g-4">
                {{-- Columna principal --}}
                <div class="col-lg-8">
                    <form action="{{ route('settings.templates.custom-css.update') }}" method="POST" id="custom-css-form">
                        @csrf

                        <div class="card">
                            <div class="card-header">
                                <div>
                                    <h5 class="card-title mb-1">CSS personalizado</h5>
                                    <small class="text-muted">El CSS se aplica globalmente a la plantilla activa del sitio</small>
                                </div>
                            </div>

                            <div class="card-body">
                                <div class="mb-1">
                                    <label class="form-label">{{ __('template::template.custom_css') }}</label>
                                    <textarea
                                        name="custom_css"
                                        id="css-editor"
                                        class="form-control font-monospace @error('custom_css') is-invalid @enderror"
                                        rows="20">{{ old('custom_css', $customCss) }}</textarea>
                                    @error('custom_css')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <small class="text-muted">
                                    <span id="char-count">0</span> / 65535 caracteres
                                </small>
                            </div>

                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-save me-2"></i>Guardar CSS
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                {{-- Columna lateral --}}
                <div class="col-lg-4">
                    {{-- Plantilla activa --}}
                    <div class="card mb-3">
                        <div class="card-body">
                            <h6 class="fw-bold mb-2 border-bottom pb-2">
                                <i class="fas fa-check-circle text-success me-2"></i>{{ __('template::template.active_template') }}
                            </h6>
                            <p class="mb-1 fw-semibold">{{ $activeTemplate ?? setting('template', 'default') }}</p>
                            <small class="text-muted">El CSS se aplica a esta plantilla</small>
                        </div>
                    </div>

                    {{-- Ejemplos rápidos --}}
                    <div class="card mb-3">
                        <div class="card-body">
                            <h6 class="fw-bold mb-3 border-bottom pb-2">
                                <i class="fas fa-lightbulb me-2"></i>Ejemplos rápidos
                            </h6>

                            <p class="small text-muted mb-1">Cambiar color primario:</p>
                            <pre class="small bg-light p-2 rounded mb-3">:root {
  --primary-color: #FF5733;
}</pre>

                            <p class="small text-muted mb-1">Estilos de navegación:</p>
                            <pre class="small bg-light p-2 rounded mb-0">.navbar {
  background: #f8f9fa;
  border-bottom: 3px
    solid #90bb13;
}</pre>
                        </div>
                    </div>

                    {{-- Advertencias --}}
                    <div class="card border-warning">
                        <div class="card-body">
                            <h6 class="fw-bold mb-3 border-bottom pb-2 text-warning">
                                <i class="fas fa-triangle-exclamation me-2"></i>Recomendaciones
                            </h6>
                            <ul class="small mb-0 ps-3">
                                <li class="mb-2">Usa variables CSS (<code>:root</code>) para cambios globales de color.</li>
                                <li class="mb-2">Valida el CSS antes de guardar para evitar errores visuales.</li>
                                <li class="mb-2">Los cambios se aplican a todos los usuarios del sitio.</li>
                                <li>Mantén una copia de seguridad del CSS antes de modificar.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const $cssEditor = $('#css-editor');
        const $charCount = $('#char-count');

        function updateCharCount() {
            $charCount.text($cssEditor.val().length);
        }

        $cssEditor.on('input', updateCharCount);
        updateCharCount();
    </script>
@endpush
