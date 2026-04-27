@extends('layouts.theme')

@section('title', 'Plantilla de factura')

@section('content')
    @include('core::components.card', ['title' => 'Ecommerce - Plantilla de factura'])
    @include('core::components.alerts')

    @php
        $twigVars = [
            ['var' => '{{ invoice.* }}',             'desc' => 'Información de la factura (código, importe, fechas, etc.)'],
            ['var' => '{{ logo_full_path }}',          'desc' => 'El logotipo del sitio con URL completa.'],
            ['var' => '{{ company_logo_full_path }}',  'desc' => 'Logo de la compañía.'],
            ['var' => '{{ site_title }}',              'desc' => 'El título del sitio.'],
            ['var' => '{{ company_name }}',            'desc' => 'El nombre de la empresa.'],
            ['var' => '{{ company_address }}',         'desc' => 'La dirección de la empresa.'],
            ['var' => '{{ company_country }}',         'desc' => 'El país de la empresa.'],
            ['var' => '{{ company_state }}',           'desc' => 'El estado de la empresa.'],
            ['var' => '{{ company_city }}',            'desc' => 'La ciudad de la empresa.'],
            ['var' => '{{ company_zipcode }}',         'desc' => 'El código postal de la empresa.'],
            ['var' => '{{ company_phone }}',           'desc' => 'El teléfono de la empresa.'],
            ['var' => '{{ company_email }}',           'desc' => 'El correo electrónico de la empresa.'],
            ['var' => '{{ company_tax_id }}',          'desc' => 'El número de identificación fiscal.'],
            ['var' => '{{ payment_method }}',          'desc' => 'Método de pago.'],
            ['var' => '{{ payment_status }}',          'desc' => 'Estado de pago.'],
            ['var' => '{{ payment_description }}',     'desc' => 'Descripción de pago.'],
            ['var' => '{{ html_attributes }}',         'desc' => 'Atributos HTML.'],
            ['var' => '{{ body_attributes }}',         'desc' => 'Atributos del body.'],
        ];
        $twigFuncs = [
            ['name' => 'apply', 'desc' => 'Permite aplicar filtros Twig.',          'snippet' => "{% apply upper %}\n  ...\n{% endapply %}"],
            ['name' => 'for',   'desc' => 'Recorre cada elemento en una secuencia.', 'snippet' => "{% for item in items %}\n  ...\n{% endfor %}"],
            ['name' => 'if',    'desc' => 'Declaración condicional.',                'snippet' => "{% if condition %}\n  ...\n{% endif %}"],
        ];
    @endphp

    <form action="{{ route('settings.ecommerce.invoice-template.update') }}" method="POST" id="invoice-template-form">
        @csrf
        @method('PATCH')

        <div class="card mb-4">
            <div class="card-header p-3 border-bottom d-flex align-items-center gap-2">
                <h6 class="mb-0 fw-bold me-auto">Contenido</h6>

                {{-- Variables --}}
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <code>&lt;/&gt;</code> variables
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" style="max-height:320px;overflow-y:auto;min-width:360px">
                        @foreach($twigVars as $item)
                        <li>
                            <a class="dropdown-item small py-1 insert-variable" href="#" data-var="{{ $item['var'] }}">
                                <code class="text-primary">{{ $item['var'] }}</code>
                                <span class="text-muted ms-1">{{ $item['desc'] }}</span>
                            </a>
                        </li>
                        @endforeach
                    </ul>
                </div>

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
                <textarea name="invoice_template" id="invoice_template"
                    class="@error('invoice_template') is-invalid @enderror">{{ old('invoice_template', $settings['invoice_template'] ?? '') }}</textarea>
                @error('invoice_template')
                    <div class="invalid-feedback px-3 pb-2">{{ $message }}</div>
                @enderror
            </div>
            <div class="card-footer">
                <div class="form-text">Plantilla Twig/HTML. Haz clic en una variable del dropdown para insertarla en el cursor.</div>
            </div>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save me-1"></i> Guardar ajustes
            </button>
            <a href="{{ route('settings.ecommerce.invoice-template.order.preview') }}" target="_blank" class="btn btn-outline-secondary">
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
    var editor = CodeMirror.fromTextArea(document.getElementById('invoice_template'), {
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

    $('#invoice-template-form').on('submit', function () {
        editor.save();
    });

    $(document).on('click', '.insert-variable, .insert-snippet', function (e) {
        e.preventDefault();
        var text = $(this).data('var') || $(this).data('snippet');
        var doc = editor.getDoc();
        var cursor = doc.getCursor();
        doc.replaceRange(text, cursor);
        editor.focus();
    });
});
</script>
@endpush
