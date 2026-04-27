@extends('layouts.theme')

@section('page_title', 'Schema.org — '.($meta->title ?? 'meta #'.$meta->id))

@section('content')
    @include('core::components.alerts')

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header p-4 border-bottom">
                    <h5 class="mb-1 fw-bold">Schema.org personalizado</h5>
                    <p class="small mb-0 text-muted">
                        JSON-LD que se inyecta en <code>&lt;head&gt;</code> de esta página. Ayuda a que Google muestre rich snippets.
                    </p>
                </div>

                <form method="POST" action="{{ route('settings.seo.schema-org.update', $meta) }}" id="schema-form">
                    @csrf
                    @method('PUT')

                    <div class="card-body p-4">
                        <div class="mb-3">
                            <label for="schema-type" class="form-label fw-semibold">Tipo de schema</label>
                            <select id="schema-type" name="schema_type" class="form-select">
                                <option value="">— ninguno (usar defaults) —</option>
                                @foreach($types as $type)
                                    <option value="{{ $type }}" @selected(old('schema_type', $currentType) === $type)>{{ $type }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Elegir un tipo carga una plantilla que puedes editar.</small>
                        </div>

                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <label for="schema-editor" class="form-label fw-semibold mb-0">JSON-LD</label>
                                <div>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-load-template">Cargar plantilla</button>
                                    <button type="button" class="btn btn-sm btn-outline-primary" id="btn-validate">Validar</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-format">Formatear</button>
                                </div>
                            </div>
                            <textarea id="schema-editor" name="schema_custom"
                                      class="form-control font-monospace @error('schema_custom') is-invalid @enderror"
                                      rows="20">{{ old('schema_custom', $currentSchema ? json_encode($currentSchema, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '') }}</textarea>
                            @error('schema_custom')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div id="validation-result" class="d-none mb-3"></div>
                    </div>

                    <div class="card-footer p-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-fill">Guardar</button>
                        <a href="{{ route('settings.seo.metas.edit', $meta) }}" class="btn btn-outline-secondary">Volver al meta</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-2 border-bottom pb-2">Página asociada</h6>
                    <p class="small mb-1"><strong>Título:</strong> {{ $meta->title ?: '—' }}</p>
                    <p class="small mb-1"><strong>Modelo:</strong> <code>{{ $meta->short_type }}</code> #{{ $meta->seoable_id }}</p>
                    @if($meta->canonical_url)
                        <p class="small mb-0"><strong>URL:</strong> <a href="{{ $meta->canonical_url }}" target="_blank" rel="noopener" class="text-break">{{ $meta->canonical_url }}</a></p>
                    @endif
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-2 border-bottom pb-2">Tipos soportados</h6>
                    <ul class="small text-muted mb-0 ps-3">
                        <li><strong>Article</strong> — posts, noticias</li>
                        <li><strong>Product</strong> — productos e-commerce</li>
                        <li><strong>FAQPage</strong> — preguntas frecuentes</li>
                        <li><strong>Recipe</strong> — recetas de cocina</li>
                        <li><strong>Event</strong> — eventos con fecha</li>
                        <li><strong>HowTo</strong> — guías paso a paso</li>
                        <li><strong>WebPage</strong> — genérico</li>
                    </ul>
                </div>
            </div>

            <div class="card">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-2 border-bottom pb-2">Recursos</h6>
                    <p class="small mb-2">
                        <a href="https://validator.schema.org" target="_blank" rel="noopener">Validador oficial Schema.org</a>
                    </p>
                    <p class="small mb-2">
                        <a href="https://search.google.com/test/rich-results" target="_blank" rel="noopener">Test Rich Results de Google</a>
                    </p>
                    <p class="small mb-0">
                        <a href="https://schema.org/docs/gs.html" target="_blank" rel="noopener">Documentación Schema.org</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
const TEMPLATES = @json($templates);

$('#btn-load-template').on('click', function () {
    const type = $('#schema-type').val();
    if (! type || ! TEMPLATES[type]) {
        toastr.warning('Selecciona un tipo primero.');
        return;
    }
    if ($('#schema-editor').val().trim() && ! window.confirm('¿Sobrescribir el JSON actual?')) {
        return;
    }
    $('#schema-editor').val(JSON.stringify(TEMPLATES[type], null, 2));
    toastr.success('Plantilla cargada.');
});

$('#btn-format').on('click', function () {
    const raw = $('#schema-editor').val();
    if (! raw.trim()) return;
    try {
        $('#schema-editor').val(JSON.stringify(JSON.parse(raw), null, 2));
    } catch (e) {
        toastr.error('JSON inválido: ' + e.message);
    }
});

$('#btn-validate').on('click', function () {
    const $result = $('#validation-result');
    $.ajax({
        url: '{{ route('settings.seo.schema-org.validate', $meta) }}',
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            schema_custom: $('#schema-editor').val(),
        },
        success: function (res) {
            $result.removeClass('d-none alert-success alert-danger');
            if (res.valid) {
                $result.addClass('alert alert-success')
                    .html('<strong>Válido</strong> — el schema cumple los requisitos básicos.');
            } else {
                $result.addClass('alert alert-danger')
                    .html('<strong>Inválido:</strong><ul class="mb-0">' +
                        res.errors.map(e => '<li>' + e + '</li>').join('') + '</ul>');
            }
        },
        error: function () {
            toastr.error('Error al validar.');
        },
    });
});
</script>
@endpush
