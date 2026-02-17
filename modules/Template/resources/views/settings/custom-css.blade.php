@extends('layouts.theme')

@section('page_title', 'CSS personalizado')

@section('content')
    <div class="row g-4">

        {{-- Columna principal --}}
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header p-4 border-bottom">
                    <h5 class="mb-1 fw-bold">CSS personalizado</h5>
                    <p class="small mb-0 text-muted">
                        El CSS se aplica globalmente a la plantilla activa del sitio.
                    </p>
                </div>

                <div class="card-body p-4">
                    <form method="POST" action="{{ route('settings.templates.custom-css.update') }}" id="custom-css-form">
                        @csrf

                        <div class="mb-4">
                            <label for="css-editor" class="form-label fw-semibold">
                                Estilo personalizado
                            </label>
                            <p class="small text-muted mb-2">
                                Escribe reglas CSS que se aplicarán globalmente. Úsalo para personalizar colores, tipografía, espacios y más.
                            </p>
                            <textarea id="css-editor" name="custom_css"
                                      class="form-control font-monospace @error('custom_css') is-invalid @enderror"
                                      rows="16"
                                      placeholder="/* Escribe tu CSS personalizado aquí */&#10;body {&#10;  background-color: #fff;&#10;}">{{ old('custom_css', $customCss) }}</textarea>
                            @error('custom_css')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror

                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            Guardar
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Columna lateral --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3 border-bottom pb-2">Plantilla activa</h6>
                    <p class="mb-1 fw-semibold text-uppercase small">{{ $activeTemplateName ?? setting('template', 'default') }}</p>
                    <p class="small text-muted mb-4">El CSS se aplica a esta plantilla</p>

                    <h6 class="fw-bold mb-3 border-bottom pb-2">Ejemplos rápidos</h6>
                    <p class="small text-muted mb-1">Cambiar color primario:</p>
                    <pre class="small bg-light p-2 rounded mb-3" style="font-size: 0.78rem;">:root {
  --primary-color: #90bb13;
}</pre>

                    <p class="small text-muted mb-1">Ocultar un elemento:</p>
                    <pre class="small bg-light p-2 rounded mb-4" style="font-size: 0.78rem;">.mi-elemento {
  display: none;
}</pre>

                    <div class="alert alert-warning small mb-0">
                        <i class="fas fa-triangle-exclamation me-1"></i>
                        <strong>Precaución:</strong> los cambios afectan a todos los usuarios. Guarda una copia antes de modificar.
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection

@push('css')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/lib/codemirror.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/theme/monokai.min.css">
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/lib/codemirror.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/mode/css/css.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/addon/edit/closebrackets.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/addon/edit/matchbrackets.min.js"></script>

<script>
$(document).ready(function () {
    var editorOptions = {
        mode: 'css',
        theme: 'monokai',
        lineNumbers: true,
        lineWrapping: true,
        indentUnit: 4,
        tabSize: 4,
        indentWithTabs: false,
        autoCloseBrackets: true,
        matchBrackets: true
    };

    var cssEditor = CodeMirror.fromTextArea(document.getElementById('css-editor'), editorOptions);

    $('#custom-css-form').on('submit', function () {
        cssEditor.save();
    });

    // Update character count
    const $charCount = $('#char-count');
    function updateCharCount() {
        $charCount.text(cssEditor.getValue().length.toLocaleString());
    }

    cssEditor.on('change', updateCharCount);
    updateCharCount();
});
</script>
@endpush
