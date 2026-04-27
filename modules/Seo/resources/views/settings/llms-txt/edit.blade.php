@extends('layouts.theme')

@section('page_title', 'llms.txt')

@section('content')
    <div class="row g-4">

        {{-- Columna principal --}}
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header p-4 border-bottom">
                    <h5 class="mb-1 fw-bold">llms.txt</h5>
                    <p class="small mb-0 text-muted">
                        Describe tu sitio para motores de IA (ChatGPT, Claude, Perplexity, Gemini). Formato Markdown con enlaces a las URLs más relevantes.
                    </p>
                </div>

                <div class="card-body p-4">
                    <form method="POST" action="{{ route('settings.seo.llms.update') }}" id="llms-txt-form">
                        @csrf

                        <div class="mb-4">
                            <label for="llms-editor" class="form-label fw-semibold">Contenido</label>
                            <p class="text-muted mb-2">
                                Markdown simple. Un encabezado H1 con el nombre del sitio, un párrafo con blockquote como resumen, y listas de enlaces por sección.
                            </p>
                            <textarea id="llms-editor" name="llms_txt"
                                      class="form-control font-monospace @error('llms_txt') is-invalid @enderror">{{ old('llms_txt', $llmsTxt) }}</textarea>
                            @error('llms_txt')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-primary w-100 mb-2">
                            Guardar
                        </button>
                        <button type="button" class="btn btn-outline-secondary w-100" data-action="reset-llms">
                            Restaurar al default
                        </button>
                    </form>

                    <form method="POST" action="{{ route('settings.seo.llms.reset') }}" id="form-reset-llms" class="d-none">
                        @csrf
                    </form>
                </div>
            </div>
        </div>

        {{-- Columna lateral --}}
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3 border-bottom pb-2">URL pública</h6>
                    <p class="text-muted mb-1">Los motores de IA buscan el archivo en:</p>
                    <code class="small">{{ url('/llms.txt') }}</code>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3 border-bottom pb-2">¿Qué es llms.txt?</h6>
                    <p class="text-muted small mb-2">
                        Estándar propuesto en 2024 para ayudar a modelos de lenguaje a entender el sitio.
                        Complementa <code>robots.txt</code>, no lo reemplaza.
                    </p>
                    <p class="text-muted small mb-0">
                        Especificación: <a href="https://llmstxt.org" target="_blank" rel="noopener">llmstxt.org</a>
                    </p>
                </div>
            </div>

            <div class="card">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3 border-bottom pb-2">Plantilla básica</h6>
                    <pre class="small bg-light p-2 rounded mb-0" style="font-size: 0.72rem; white-space: pre-wrap;"># Nombre del sitio

&gt; Descripción breve en una línea.

## Secciones

- [Docs](https://.../docs): Guía para desarrolladores.
- [Blog](https://.../blog): Artículos recientes.

## Opcional

- [Contacto](https://.../contacto)</pre>
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
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/mode/markdown/markdown.min.js"></script>
<script>
$(document).on('click', '[data-action="reset-llms"]', function () {
    if (window.confirm('¿Restaurar el llms.txt al valor por defecto? Esta acción no se puede deshacer.')) {
        document.getElementById('form-reset-llms').submit();
    }
});

$(document).ready(function () {
    var llmsEditor = CodeMirror.fromTextArea(document.getElementById('llms-editor'), {
        mode: 'markdown',
        theme: 'monokai',
        lineNumbers: true,
        indentUnit: 4,
        indentWithTabs: false,
        lineWrapping: true,
        matchBrackets: false,
        styleActiveLine: true,
    });

    $('#llms-txt-form').on('submit', function () {
        llmsEditor.save();
    });
});
</script>
@endpush
