@extends('layouts.theme')

@section('page_title', 'JavaScript Personalizado')

@section('content')
    @include('core::components.card', ['title' => 'JavaScript Personalizado'])

    <div class="widget-content">
        @include('core::components.alerts')

        <div class="card">
            <div class="card-header p-4 border-bottom border-light">
                <div>
                    <h5 class="mb-1 fw-bold">Inyectar JavaScript personalizado</h5>
                    <p class="small mb-0 text-muted">
                        Aquí puedes agregar código JavaScript que se inyectará en el head y footer del tema activo.
                    </p>
                </div>
            </div>

            <div class="card-body p-4">
                <form method="POST" action="{{ route('settings.theme.custom-js.update') }}">
                    @csrf

                    {{-- JS del Head --}}
                    <div class="mb-4">
                        <label for="header_js" class="form-label fw-semibold">
                            <i class="fas fa-code me-2 text-primary"></i>JavaScript en Head
                        </label>
                        <p class="small text-muted mb-2">
                            Este código se inyectará dentro de <code>&lt;head&gt;</code>. Úsalo para añadir scripts que deben cargar de forma temprana (analytics, variables globales, etc.).
                        </p>
                        <textarea id="header_js" name="header_js" class="form-control" style="height: 250px;">{{ old('header_js', $headerJs) }}</textarea>
                        @error('header_js')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- JS del Footer --}}
                    <div class="mb-4">
                        <label for="footer_js" class="form-label fw-semibold">
                            <i class="fas fa-code me-2 text-primary"></i>JavaScript en Footer
                        </label>
                        <p class="small text-muted mb-2">
                            Este código se inyectará antes de <code>&lt;/body&gt;</code>. Úsalo para añadir scripts de interacción, tracking o widgets que deben cargar al final.
                        </p>
                        <textarea id="footer_js" name="footer_js" class="form-control" style="height: 250px;">{{ old('footer_js', $footerJs) }}</textarea>
                        @error('footer_js')
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
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/lib/codemirror.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/mode/javascript/javascript.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/addon/edit/closebrackets.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/addon/edit/matchbrackets.min.js"></script>

<script>
$(document).ready(function() {
    const jsEditorConfig = {
        mode: 'javascript',
        theme: 'monokai',
        lineNumbers: true,
        lineWrapping: true,
        indentUnit: 4,
        tabSize: 4,
        indentWithTabs: false,
        autoCloseBrackets: true,
        matchBrackets: true,
        extraKeys: {
            'Ctrl-/': 'toggleComment'
        }
    };

    const $headerTextarea = $('#header_js');
    if ($headerTextarea.length > 0) {
        const headerEditor = CodeMirror.fromTextArea($headerTextarea[0], jsEditorConfig);
        $headerTextarea.closest('form').on('submit', function() {
            $headerTextarea.val(headerEditor.getValue());
        });
    }

    const $footerTextarea = $('#footer_js');
    if ($footerTextarea.length > 0) {
        const footerEditor = CodeMirror.fromTextArea($footerTextarea[0], jsEditorConfig);
        $footerTextarea.closest('form').on('submit', function() {
            $footerTextarea.val(footerEditor.getValue());
        });
    }
});
</script>
@endpush
