@extends('layouts.theme')

@section('title', 'Plantilla de etiqueta de envío')

@section('content')
    @include('core::components.card', ['title' => 'Ecommerce - Plantilla de etiqueta de envío'])
    @include('core::components.alerts')

    @php
        $twigFuncs = [
            ['name' => 'apply', 'desc' => "La etiqueta 'apply' te permite aplicar filtros Twig.", 'snippet' => "{% apply upper %}\n  ...\n{% endapply %}"],
            ['name' => 'for',   'desc' => 'Recorre cada elemento en una secuencia.',               'snippet' => "{% for item in items %}\n  ...\n{% endfor %}"],
            ['name' => 'if',    'desc' => 'La declaración if en Twig es comparable con las declaraciones if de PHP.', 'snippet' => "{% if condition %}\n  ...\n{% endif %}"],
        ];
    @endphp

    <form action="{{ route('settings.ecommerce.shipping-label-template.update') }}" method="POST" id="shipping-label-form">
        @csrf
        @method('PATCH')

        <div class="card mb-4">
            <div class="card-header p-3 border-bottom d-flex align-items-center gap-2">
                <h6 class="mb-0 fw-bold me-auto">Contenido</h6>

                {{-- Funciones --}}
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <code>&lt;/&gt;</code> Funciones
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" style="min-width:320px">
                        @foreach($twigFuncs as $func)
                        <li>
                            <a class="dropdown-item small py-1 insert-snippet" href="#" data-snippet="{{ $func['snippet'] }}">
                                <code class="text-primary">{{ $func['name'] }}</code>
                                <span class="text-muted ms-1">{{ $func['desc'] }}</span>
                            </a>
                        </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            <div class="card-body p-0">
                <textarea name="shipping_label_template" id="shipping_label_template"
                    class="@error('shipping_label_template') is-invalid @enderror">{{ old('shipping_label_template', $settings['shipping_label_template'] ?? '') }}</textarea>
                @error('shipping_label_template')
                    <div class="invalid-feedback px-3 pb-2">{{ $message }}</div>
                @enderror
            </div>
            <div class="card-footer">
                <div class="form-text">Plantilla Twig/HTML. Haz clic en una función del dropdown para insertarla en el cursor.</div>
            </div>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save me-1"></i> Guardar ajustes
            </button>
            <button type="button" class="btn btn-outline-secondary" id="btn-reset-template">
                <i class="fas fa-undo me-1"></i> Restablecer predeterminados
            </button>
            <a href="{{ route('settings.ecommerce.shipping-label-template.preview') }}" target="_blank" class="btn btn-outline-secondary">
                <i class="fas fa-eye me-1"></i> Avance
            </a>
        </div>
    </form>
@endsection

@push('css')
<link rel="stylesheet" href="{{ themeAsset('libs/codemirror/lib/codemirror.min.css') }}">
<link rel="stylesheet" href="{{ themeAsset('libs/codemirror/theme/dracula.min.css') }}">
@endpush

@push('scripts')
<script src="{{ themeAsset('libs/codemirror/lib/codemirror.min.js') }}"></script>
<script src="{{ themeAsset('libs/codemirror/mode/xml/xml.min.js') }}"></script>
<script src="{{ themeAsset('libs/codemirror/mode/javascript/javascript.min.js') }}"></script>
<script src="{{ themeAsset('libs/codemirror/mode/css/css.min.js') }}"></script>
<script src="{{ themeAsset('libs/codemirror/mode/htmlmixed/htmlmixed.min.js') }}"></script>
<script>
$(function () {
    var editor = CodeMirror.fromTextArea(document.getElementById('shipping_label_template'), {
        mode: 'htmlmixed',
        theme: 'dracula',
        lineNumbers: true,
        lineWrapping: true,
        indentUnit: 4,
        tabSize: 4,
        indentWithTabs: false,
        autoRefresh: true
    });

    editor.setSize(null, 500);

    $('#shipping-label-form').on('submit', function () {
        editor.save();
    });

    $('#btn-reset-template').on('click', function () {
        if (confirm('¿Restablecer la plantilla a los valores predeterminados?')) {
            editor.setValue('');
            editor.focus();
        }
    });

    $(document).on('click', '.insert-snippet', function (e) {
        e.preventDefault();
        var text = $(this).data('snippet');
        var doc = editor.getDoc();
        var cursor = doc.getCursor();
        doc.replaceRange(text, cursor);
        editor.focus();
    });
});
</script>
@endpush
