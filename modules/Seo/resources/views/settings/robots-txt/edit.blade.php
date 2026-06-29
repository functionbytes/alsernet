@extends('layouts.theme')

@section('page_title', 'Robots.txt')

@section('content')
    <div class="row g-4">

        {{-- Columna principal --}}
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header p-4 border-bottom">
                    <h5 class="mb-1 fw-bold">Robots.txt</h5>
                    <p class="small mb-0 text-muted">
                        Configura el archivo robots.txt que controla el acceso de los bots de los motores de búsqueda a tu sitio.
                    </p>
                </div>

                <div class="card-body p-4">
                    <form method="POST" action="{{ route('settings.seo.robots.update') }}" id="robots-txt-form">
                        @csrf

                        <div class="mb-4">
                            <label for="robots-editor" class="form-label fw-semibold">
                                Contenido
                            </label>
                            <p class="text-muted mb-2">
                                Define las reglas de rastreo para los motores de búsqueda. Usa directivas User-agent, Allow, Disallow y Sitemap.
                            </p>
                            <textarea id="robots-editor" name="robots_txt"
                                      class="form-control font-monospace @error('robots_txt') is-invalid @enderror">{{ old('robots_txt', $robotsTxt) }}</textarea>
                            @error('robots_txt')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-primary w-100 mb-2">
                            Guardar
                        </button>
                        <button type="button" class="btn btn-outline-secondary w-100" data-action="reset-robots">
                            Restaurar al default
                        </button>
                    </form>

                    <form method="POST" action="{{ route('settings.seo.robots.reset') }}" id="form-reset-robots" class="d-none">
                        @csrf
                    </form>
                </div>

                <hr class="my-0">

                {{-- URL Tester --}}
                <div class="card-body p-4">
                    <h6 class="fw-bold text-dark mb-1">Probar URL</h6>
                    <p class="text-muted mb-3">Comprueba si una URL está bloqueada por tu robots.txt actual.</p>

                    <div class="input-group mb-3">
                        <input type="url" id="robots-test-url" class="form-control"
                               placeholder="https://tusitio.com/pagina-privada">
                        <button class="btn btn-outline-primary" id="btn-test-robots">
                            Probar
                        </button>
                    </div>
                    <div id="robots-test-result" class="d-none">
                        <div id="robots-test-allowed" class="alert alert-success d-none mb-0">
                            <strong>Accesible</strong> — Esta URL no está bloqueada.
                            <div id="robots-test-rule" class="small mt-1 text-muted"></div>
                        </div>
                        <div id="robots-test-blocked" class="alert alert-danger d-none mb-0">
                            <strong>Bloqueada</strong> — Esta URL está bloqueada por robots.txt.
                            <div id="robots-test-rule-blocked" class="small mt-1"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Columna lateral --}}
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3 border-bottom pb-2">Estado actual</h6>
                    <div class="d-flex flex-column gap-2">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">User-agent</span>
                            <span class="fw-bold" id="count-user-agent">0</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Allow</span>
                            <span class="fw-bold" id="count-allow">0</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Disallow</span>
                            <span class="fw-bold" id="count-disallow">0</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Sitemap</span>
                            <span class="fw-bold" id="count-sitemap">0</span>
                        </div>
                    </div>

                    <hr class="my-3">

                    <h6 class="fw-bold mb-3 border-bottom pb-2">URL pública</h6>
                    <p class="text-muted mb-1">El archivo se sirve en:</p>
                    <code class="small">{{ url('/robots.txt') }}</code>
                </div>
            </div>

            <div class="card">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3 border-bottom pb-2">Directivas comunes</h6>

                    <p class="text-muted mb-1">Permitir todo:</p>
                    <pre class="small bg-light p-2 rounded mb-3" style="font-size: 0.78rem;">User-agent: *
Allow: /</pre>

                    <p class="text-muted mb-1">Bloquear una carpeta:</p>
                    <pre class="small bg-light p-2 rounded mb-3" style="font-size: 0.78rem;">User-agent: *
Disallow: /admin/</pre>

                    <p class="text-muted mb-1">Añadir sitemap:</p>
                    <pre class="small bg-light p-2 rounded mb-3" style="font-size: 0.78rem;">Sitemap: {{ url('/sitemap.xml') }}</pre>

                    <div class="alert alert-warning small mb-0">
                        <strong>Precaución:</strong> los cambios afectan a cómo los motores de búsqueda rastrean tu sitio. Revisa antes de guardar.
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
<script>
$(document).on('click', '[data-action="reset-robots"]', function () {
    if (window.confirm('¿Restaurar el robots.txt al valor por defecto? Esta acción no se puede deshacer.')) {
        document.getElementById('form-reset-robots').submit();
    }
});

$(document).ready(function () {
    var robotsEditor = CodeMirror.fromTextArea(document.getElementById('robots-editor'), {
        mode: null,
        theme: 'monokai',
        lineNumbers: true,
        indentUnit: 4,
        indentWithTabs: false,
        lineWrapping: true,
        matchBrackets: false,
        styleActiveLine: true,
    });

    $('#robots-txt-form').on('submit', function () {
        robotsEditor.save();
    });

    function updateCounters(content) {
        var lines = content.split('\n');
        var counts = { 'user-agent': 0, allow: 0, disallow: 0, sitemap: 0 };

        lines.forEach(function (line) {
            var trimmed = line.trim().toLowerCase();
            if (trimmed.startsWith('user-agent:')) { counts['user-agent']++; }
            else if (trimmed.startsWith('allow:')) { counts.allow++; }
            else if (trimmed.startsWith('disallow:')) { counts.disallow++; }
            else if (trimmed.startsWith('sitemap:')) { counts.sitemap++; }
        });

        $('#count-user-agent').text(counts['user-agent']);
        $('#count-allow').text(counts.allow);
        $('#count-disallow').text(counts.disallow);
        $('#count-sitemap').text(counts.sitemap);
    }

    updateCounters(robotsEditor.getValue());
    robotsEditor.on('change', function (editor) {
        updateCounters(editor.getValue());
    });

    // URL Tester
    $('#btn-test-robots').on('click', function () {
        var url = $('#robots-test-url').val().trim();
        if (!url) return;

        $(this).prop('disabled', true).text('Probando...');

        $.post('{{ route("settings.seo.robots.test-url") }}', {
            _token: $('meta[name="csrf-token"]').attr('content'),
            url: url,
        }, function (data) {
            $('#robots-test-result').removeClass('d-none');

            if (data.is_blocked) {
                $('#robots-test-allowed').addClass('d-none');
                $('#robots-test-blocked').removeClass('d-none');
                $('#robots-test-rule-blocked').text(data.matching_rule ? 'Regla: ' + data.matching_rule : '');
            } else {
                $('#robots-test-blocked').addClass('d-none');
                $('#robots-test-allowed').removeClass('d-none');
                $('#robots-test-rule').text(data.matching_rule ? 'Regla aplicada: ' + data.matching_rule : 'Ninguna regla aplicable');
            }
        }).fail(function () {
            toastr.error('Error al probar la URL');
        }).always(function () {
            $('#btn-test-robots').prop('disabled', false).text('Probar');
        });
    });
});
</script>
@endpush
