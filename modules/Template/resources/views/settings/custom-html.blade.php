@extends('layouts.theme')

@section('page_title', 'HTML personalizado')

@section('content')
    <div class="row g-4">

        {{-- Columna principal --}}
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header p-4 border-bottom">
                    <h5 class="mb-1 fw-bold">HTML personalizado</h5>
                    <p class="small mb-0 text-muted">
                        Inyecta código HTML/CSS en el encabezado y pie de página del tema activo.
                    </p>
                </div>

                <div class="card-body p-4">
                    <form method="POST" action="{{ route('settings.theme.custom-html.update') }}" id="custom-html-form">
                        @csrf

                        {{-- HTML del encabezado --}}
                        <div class="mb-4">
                            <label for="header_html" class="form-label fw-semibold">
                                <i class="fas fa-code me-2 text-primary"></i>HTML del encabezado
                            </label>
                            <p class="small text-muted mb-2">
                                Se inyecta dentro de <code>&lt;head&gt;</code>. Ideal para estilos CSS, meta tags y scripts de carga temprana.
                            </p>
                            <textarea id="header_html" name="header_html"
                                      class="form-control font-monospace @error('header_html') is-invalid @enderror"
                                      rows="12">{{ old('header_html', $headerHtml) }}</textarea>
                            @error('header_html')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- HTML del pie de página --}}
                        <div class="mb-4">
                            <label for="footer_html" class="form-label fw-semibold">
                                <i class="fas fa-code me-2 text-primary"></i>HTML del pie de página
                            </label>
                            <p class="small text-muted mb-2">
                                Se inyecta antes de <code>&lt;/body&gt;</code>. Ideal para scripts de analytics, widgets y código de carga tardía.
                            </p>
                            <textarea id="footer_html" name="footer_html"
                                      class="form-control font-monospace @error('footer_html') is-invalid @enderror"
                                      rows="12">{{ old('footer_html', $footerHtml) }}</textarea>
                            @error('footer_html')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            Guardar cambios
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Columna lateral --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3 border-bottom pb-2">Sobre el HTML del encabezado</h6>
                    <p class="small text-muted mb-3">
                        Todo lo que escribas aquí se colocará dentro de la etiqueta <code>&lt;head&gt;</code> de cada página.
                        Úsalo para:
                    </p>
                    <ul class="small text-muted ps-3 mb-4">
                        <li>Hojas de estilos externas (<code>&lt;link&gt;</code>)</li>
                        <li>Meta tags de SEO o redes sociales</li>
                        <li>Scripts que deben cargar antes del contenido</li>
                        <li>Variables globales de configuración</li>
                    </ul>

                    <h6 class="fw-bold mb-3 border-bottom pb-2">Sobre el HTML del pie de página</h6>
                    <p class="small text-muted mb-3">
                        Este bloque se inyecta justo antes de <code>&lt;/body&gt;</code>. Úsalo para:
                    </p>
                    <ul class="small text-muted ps-3 mb-4">
                        <li>Scripts de analytics (GA, GTM, Hotjar…)</li>
                        <li>Widgets de chat o soporte</li>
                        <li>Scripts de terceros que no bloquean el renderizado</li>
                        <li>Píxeles de conversión</li>
                    </ul>

                    <div class="alert alert-warning small mb-0">
                        <i class="fas fa-triangle-exclamation me-1"></i>
                        <strong>Precaución:</strong> el código se inyecta tal cual. Un error de sintaxis puede afectar la visualización del sitio.
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
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/mode/htmlmixed/htmlmixed.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/mode/css/css.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/mode/javascript/javascript.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/addon/edit/closetag.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/addon/edit/closebrackets.min.js"></script>

<script>
$(document).ready(function () {
    var editorOptions = {
        mode: 'htmlmixed',
        theme: 'monokai',
        lineNumbers: true,
        lineWrapping: true,
        indentUnit: 4,
        tabSize: 4,
        indentWithTabs: false,
        autoCloseTags: true,
        autoCloseBrackets: true
    };

    var headerEditor = CodeMirror.fromTextArea(document.getElementById('header_html'), editorOptions);
    var footerEditor = CodeMirror.fromTextArea(document.getElementById('footer_html'), editorOptions);

    $('#custom-html-form').on('submit', function () {
        headerEditor.save();
        footerEditor.save();
    });
});
</script>
@endpush
