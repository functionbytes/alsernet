@extends('layouts.theme')

@section('page_title', __('template::template.custom_css_editor'))

@section('content')
    <div class="page-wrapper">
        <div class="container-xl">
            @include('core::components.alerts')

            <div class="row g-4">
                {{-- Columna Principal --}}
                <div class="col-lg-9">
                    <form action="{{ route('settings.templates.custom-css.update') }}" method="POST" id="custom-css-form">
                        @csrf

                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-code me-2"></i>{{ __('template::template.custom_css_editor') }}
                                </h5>
                            </div>

                            <div class="card-body">
                                {{-- Información --}}
                                <div class="alert alert-info mb-4" role="alert">
                                    <i class="fas fa-info-circle me-2"></i>
                                    {!! __('template::template.custom_css_help') !!}
                                </div>

                                {{-- Editor CSS --}}
                                <div class="mb-3">
                                    <label class="form-label">
                                        {{ __('template::template.custom_css') }}
                                    </label>
                                    <textarea
                                        name="custom_css"
                                        id="css-editor"
                                        class="form-control font-monospace @error('custom_css') is-invalid @enderror"
                                        rows="20"
                                        style="font-size: 13px; font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', 'Consolas', 'source-code-pro', monospace;">{{ old('custom_css', $customCss) }}</textarea>
                                    @error('custom_css')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Contador de caracteres --}}
                                <div class="text-muted small mb-3">
                                    <span id="char-count">0</span> / 65535 caracteres
                                </div>

                                {{-- Botones --}}
                                <div class="btn-list">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>{{ __('core::core.save') }}
                                    </button>

                                    <a href="{{ route('settings.templates.index') }}" class="btn btn-secondary">
                                        {{ __('core::core.cancel') }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                {{-- Columna Lateral --}}
                <div class="col-lg-3">
                    {{-- Información de plantilla activa --}}
                    <div class="card mb-3">
                        <div class="card-body">
                            <h5 class="card-title">
                                <i class="fas fa-check-circle text-success me-2"></i>{{ __('template::template.active_template') }}
                            </h5>
                            <p class="mb-0">
                                <strong>{{ $activeTemplateName }}</strong>
                            </p>
                            <small class="text-muted d-block mt-2">
                                El CSS se aplica globalmente al sitio
                            </small>
                        </div>
                    </div>

                    {{-- Ejemplos rápidos --}}
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">
                                <i class="fas fa-lightbulb me-2"></i>Ejemplos
                            </h5>

                            <div class="mb-3">
                                <small class="text-muted d-block mb-2">Cambiar color primario:</small>
                                <code style="font-size: 11px; display: block; white-space: normal; word-break: break-word;" class="bg-light p-2 rounded">
:root {<br>
&nbsp;&nbsp;--primary-color: #FF5733;<br>
}
                                </code>
                            </div>

                            <div>
                                <small class="text-muted d-block mb-2">Estilos personalizados:</small>
                                <code style="font-size: 11px; display: block; white-space: normal; word-break: break-word;" class="bg-light p-2 rounded">
.navbar {<br>
&nbsp;&nbsp;background-color: #f8f9fa;<br>
&nbsp;&nbsp;border-bottom: 3px solid #90bb13;<br>
}
                                </code>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('footer-scripts')
        <script>
            $(document).ready(function() {
                // Actualizar contador de caracteres
                const $cssEditor = $('#css-editor');
                const $charCount = $('#char-count');

                function updateCharCount() {
                    $charCount.text($cssEditor.val().length);
                }

                $cssEditor.on('input', updateCharCount);
                updateCharCount();
            });
        </script>
    @endpush
@endsection
