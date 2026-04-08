@extends('layouts.theme')

@section('title', 'Insignias de valoracion')

@section('content')

    @include('core::components.card', ['title' => 'Insignias de valoracion'])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Insignias de valoracion</h5>
                        <p class="small mb-0 text-muted">Genera insignias con tu calificacion de Google para incrustar en tu sitio web.</p>
                    </div>
                </div>
            </div>

            {{-- Content --}}
            <div class="card-body p-0">

                @if ($locations->isEmpty())
                    <div class="p-4 text-center py-5">
                        <div class="d-flex flex-column align-items-center">
                            <div class="round-48 rounded-circle bg-light-subtle text-muted mb-3 d-flex align-items-center justify-content-center">
                                <i class="fas fa-star fs-7"></i>
                            </div>
                            <h6 class="mb-1">No hay ubicaciones activas</h6>
                            <p class="text-muted mb-0">Activa al menos una ubicacion de Google para generar insignias.</p>
                        </div>
                    </div>
                @else

                    <div class="row g-0">

                        {{-- Left column: controls --}}
                        <div class="col-lg-4 border-end">
                            <div class="p-4">

                                <div class="mb-4">
                                    <label class="form-label fw-semibold">Ubicacion</label>
                                    <select class="form-select select2" id="badge-location">
                                        <option value="">Selecciona una ubicacion</option>
                                        @foreach ($locations as $loc)
                                            <option value="{{ $loc->id }}"
                                                    data-rating="{{ number_format((float) $loc->average_rating, 1) }}"
                                                    data-reviews="{{ $loc->total_reviews }}"
                                                    data-name="{{ $loc->name }}">
                                                {{ $loc->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label fw-semibold">Estilo</label>
                                    <select class="form-select select2" id="badge-style">
                                        <option value="standard">Estandar</option>
                                        <option value="minimal">Minimal</option>
                                        <option value="dark">Oscuro</option>
                                        <option value="light">Claro</option>
                                    </select>
                                </div>

                                <div id="download-actions" class="d-none">
                                    <div class="d-grid gap-2">
                                        <a href="#" class="btn btn-primary" id="btn-download-svg">
                                            Descargar SVG
                                        </a>
                                        <a href="#" class="btn btn-outline-secondary" id="btn-download-png">
                                            Descargar PNG
                                        </a>
                                    </div>
                                </div>

                            </div>
                        </div>

                        {{-- Right column: preview + embed --}}
                        <div class="col-lg-8">
                            <div class="p-4">

                                <div class="mb-4">
                                    <label class="form-label fw-semibold">Vista previa</label>
                                    <div id="badge-preview-container" class="border rounded p-3 d-flex justify-content-center align-items-center bg-light" style="min-height:160px;">
                                        <span class="text-muted small">Selecciona una ubicacion para ver la vista previa</span>
                                    </div>
                                </div>

                                <div id="embed-section" class="d-none">
                                    <label class="form-label fw-semibold">Codigo de integracion</label>
                                    <div class="input-group">
                                        <textarea class="form-control font-monospace small"
                                                  id="embed-code-text"
                                                  rows="2"
                                                  readonly></textarea>
                                        <button class="btn btn-outline-secondary"
                                                type="button"
                                                id="btn-copy-embed"
                                                title="Copiar codigo">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </div>
                                    <p class="text-muted small mt-2 mb-0">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Pega este codigo en tu sitio web. La insignia se actualiza automaticamente con tu calificacion actual.
                                    </p>
                                </div>

                            </div>
                        </div>

                    </div>

                @endif

            </div>
        </div>

    </div>

@endsection

@push('scripts')
<script>
$(function () {
    $('#badge-location').select2({
        placeholder: 'Selecciona una ubicacion',
        allowClear: true,
        language: { noResults: function () { return 'Sin resultados'; } }
    });

    $('#badge-style').select2({
        minimumResultsForSearch: Infinity,
        language: { noResults: function () { return 'Sin resultados'; } }
    });

    var previewBaseUrl = '{{ rtrim(route("reviews.badges.index"), "/") }}';
    var embedBaseUrl   = '{{ rtrim(route("reviews.badges.index"), "/") }}';

    function getLocationId()  { return $('#badge-location').val(); }
    function getStyle()       { return $('#badge-style').val(); }

    function loadPreview() {
        var locationId = getLocationId();
        if (! locationId) { return; }

        var url = previewBaseUrl + '/' + locationId + '/preview?style=' + getStyle();

        $('#badge-preview-container').html('<div class="text-muted">Cargando…</div>');

        $.ajax({
            url: url,
            method: 'GET',
            dataType: 'text',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (svgContent) {
                $('#badge-preview-container').html(svgContent);
                loadEmbedCode(locationId);
                updateDownloadLinks(locationId);
                $('#download-actions').removeClass('d-none');
            },
            error: function () {
                $('#badge-preview-container').html('<span class="text-danger small">Error al cargar la vista previa.</span>');
            },
        });
    }

    function loadEmbedCode(locationId) {
        var url = embedBaseUrl + '/' + locationId + '/embed?style=' + getStyle();

        $.get(url, function (data) {
            $('#embed-code-text').val(data.embed_code);
            $('#embed-section').removeClass('d-none');
        });
    }

    function updateDownloadLinks(locationId) {
        var style = getStyle();
        $('#btn-download-svg').attr('href', previewBaseUrl + '/' + locationId + '/download?format=svg&style=' + style);
        $('#btn-download-png').attr('href', previewBaseUrl + '/' + locationId + '/download?format=png&style=' + style);
    }

    // Location change
    $('#badge-location').on('change', function () {
        if ($(this).val()) {
            loadPreview();
        } else {
            $('#badge-preview-container').html('<span class="text-muted small">Selecciona una ubicacion para ver la vista previa</span>');
            $('#embed-section').addClass('d-none');
            $('#download-actions').addClass('d-none');
        }
    });

    // Style selector
    $('#badge-style').on('change', function () {
        if (getLocationId()) {
            loadPreview();
        }
    });

    // Copy embed code
    $('#btn-copy-embed').on('click', function () {
        var text = $('#embed-code-text').val();
        navigator.clipboard.writeText(text)
            .then(function () { toastr.success('Codigo copiado al portapapeles.'); })
            .catch(function () { toastr.error('No se pudo copiar.'); });
    });
});
</script>
@endpush
