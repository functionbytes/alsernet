@extends('layouts.theme')

@section('page_title', 'HTML Personalizado')

@section('content')
    @include('core::components.card', ['title' => 'HTML Personalizado'])

    <div class="widget-content">
        @include('core::components.alerts')

        <div class="card">
            <div class="card-header p-4 border-bottom border-light">
                <div>
                    <h5 class="mb-1 fw-bold">Inyectar HTML personalizado</h5>
                    <p class="small mb-0 text-muted">
                        Aquí puedes agregar código HTML/CSS/JavaScript que se inyectará en el head y footer del tema.
                    </p>
                </div>
            </div>

            <div class="card-body p-4">
                <form method="POST" action="{{ route('settings.theme.custom-html.update') }}">
                    @csrf

                    {{-- HTML del Head --}}
                    <div class="mb-4">
                        <label for="header_html" class="form-label fw-semibold">
                            <i class="fas fa-code me-2 text-primary"></i>HTML en Head (CSS, Meta Tags)
                        </label>
                        <p class="small text-muted mb-2">
                            Este código se inyectará dentro de <code>&lt;head&gt;</code>. Úsalo para añadir estilos CSS, meta tags, o scripts que deben cargar temprano.
                        </p>
                        <textarea id="header_html" name="header_html" class="form-control" style="height: 250px;">{{ old('header_html', $headerHtml) }}</textarea>
                        @error('header_html')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- HTML del Footer --}}
                    <div class="mb-4">
                        <label for="footer_html" class="form-label fw-semibold">
                            <i class="fas fa-code me-2 text-primary"></i>HTML en Footer (Scripts, Widgets)
                        </label>
                        <p class="small text-muted mb-2">
                            Este código se inyectará antes de <code>&lt;/body&gt;</code>. Úsalo para añadir scripts, widgets de tracking, o código que deba cargar al final.
                        </p>
                        <textarea id="footer_html" name="footer_html" class="form-control" style="height: 250px;">{{ old('footer_html', $footerHtml) }}</textarea>
                        @error('footer_html')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Guardar cambios
                        </button>
                        <a href="{{ route('settings.templates.index') }}" class="btn btn-secondary">
                            Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@push('css')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/lib/codemirror.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/theme/monokai.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/addon/hint/show-hint.min.css">
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/lib/codemirror.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/mode/htmlmixed/htmlmixed.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/mode/css/css.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/mode/javascript/javascript.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/addon/edit/closetag.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/addon/edit/closebrackets.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/addon/edit/matchbrackets.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/addon/hint/show-hint.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/addon/hint/html-hint.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/addon/hint/css-hint.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/emmet-codemirror@1.1.106/emmet.min.js"></script>

<script>
$(document).ready(function() {
    // Initialize CodeMirror for header HTML
    const $headerTextarea = $('#header_html');
    if ($headerTextarea.length > 0) {
        const headerEditor = CodeMirror.fromTextArea($headerTextarea[0], {
            mode: 'htmlmixed',
            theme: 'monokai',
            lineNumbers: true,
            lineWrapping: true,
            indentUnit: 4,
            tabSize: 4,
            indentWithTabs: false,
            autoCloseTags: true,
            autoCloseBrackets: true,
            styleActiveLine: true,
            matchBrackets: true,
            highlightSelectionMatches: {showToken: /\w/, annotateScrollbar: true},
            extraKeys: {
                'Ctrl-Space': 'autocomplete',
                'Ctrl-/': 'toggleComment',
                'Tab': 'emmetExpandAbbreviation',
                'Ctrl-Alt-Enter': 'emmetWrapWithAbbreviation'
            }
        });

        // Sync changes back to textarea before form submission
        $headerTextarea.closest('form').on('submit', function() {
            $headerTextarea.val(headerEditor.getValue());
        });
    }

    // Initialize CodeMirror for footer HTML
    const $footerTextarea = $('#footer_html');
    if ($footerTextarea.length > 0) {
        const footerEditor = CodeMirror.fromTextArea($footerTextarea[0], {
            mode: 'htmlmixed',
            theme: 'monokai',
            lineNumbers: true,
            lineWrapping: true,
            indentUnit: 4,
            tabSize: 4,
            indentWithTabs: false,
            autoCloseTags: true,
            autoCloseBrackets: true,
            styleActiveLine: true,
            matchBrackets: true,
            highlightSelectionMatches: {showToken: /\w/, annotateScrollbar: true},
            extraKeys: {
                'Ctrl-Space': 'autocomplete',
                'Ctrl-/': 'toggleComment',
                'Tab': 'emmetExpandAbbreviation',
                'Ctrl-Alt-Enter': 'emmetWrapWithAbbreviation'
            }
        });

        // Sync changes back to textarea before form submission
        $footerTextarea.closest('form').on('submit', function() {
            $footerTextarea.val(footerEditor.getValue());
        });
    }
});
</script>
@endpush
