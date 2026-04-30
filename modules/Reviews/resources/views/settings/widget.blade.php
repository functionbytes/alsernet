@extends('layouts.theme')

@section('title', 'Widget de reseñas')

@section('page_header')
    @include('core::components.card', ['title' => 'Widget de reseñas'])
@endsection

@section('content')

    <div class="row g-3">

        {{-- Configuracion --}}
        <div class="col-12 col-lg-8">
            <div class="card">
                <div class="card-header border-bottom p-3">
                    <h5 class="mb-0 fw-bold">Widget de reseñas</h5>
                    <small class="text-muted">Genera un widget embebible para mostrar tus reseñas en cualquier sitio web.</small>
                </div>
                <div class="card-body">
                    @include('core::components.alerts')

                    <div class="mb-3">
                        <label for="widget_location_id" class="form-label">Ubicacion</label>
                        <select class="form-select select2" id="widget_location_id">
                            <option value="">Todas las ubicaciones</option>
                            @foreach($locations as $location)
                                <option value="{{ $location->id }}">{{ $location->name }}</option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">Filtra las reseñas por ubicacion de Google</small>
                    </div>

                    <div class="mb-3">
                        <label for="widget_min_rating" class="form-label">Calificacion minima</label>
                        <select class="form-select" id="widget_min_rating">
                            <option value="1">1 estrella o mas</option>
                            <option value="2">2 estrellas o mas</option>
                            <option value="3">3 estrellas o mas</option>
                            <option value="4" selected>4 estrellas o mas</option>
                            <option value="5">Solo 5 estrellas</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="widget_limit" class="form-label">Numero de reseñas</label>
                        <input type="number" class="form-control" id="widget_limit"
                               value="5" min="1" max="20">
                        <small class="form-text text-muted">Maximo 20 reseñas</small>
                    </div>

                    {{-- Embed code (hidden until generated) --}}
                    <div id="embed-section" class="mt-3" class="d-none">
                        <hr>
                        <h6 class="fw-bold mb-3">Codigo de integracion</h6>

                        <div class="mb-3">
                            <div class="input-group">
                                <textarea class="form-control font-monospace" id="iframe-code" rows="3" readonly></textarea>
                                <button class="btn btn-info" type="button" id="btn-copy-iframe"
                                        title="Copiar">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                            <small class="form-text text-muted">Pega este codigo en el HTML de tu sitio web</small>
                        </div>

                        <div class="alert alert-info d-flex align-items-start gap-2">
                            <i class="fas fa-info-circle mt-1 flex-shrink-0"></i>
                            <div>
                                <strong>Como usar el widget</strong>
                                <p class="mb-0 small">Copia el codigo iframe y pegalo en cualquier pagina HTML.
                                    El widget se adapta al ancho del contenedor y muestra las reseñas en tiempo real.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="button" class="btn btn-primary w-100 mb-1" id="btn-generate">
                        Generar codigo
                    </button>
                    <button type="button" class="btn btn-light w-100" id="btn-preview">
                        Previsualizar
                    </button>
                </div>
            </div>
        </div>

        {{-- Panel informativo --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-3">¿Qué es el widget?</h6>
                    <p class="card-text text-muted">
                        El widget es un componente embebible que muestra tus reseñas de Google en cualquier sitio web externo mediante un iframe.
                    </p>
                </div>
                <hr class="my-0">
                <div class="card-body">
                    <h6 class="card-title mb-3">Filtros</h6>
                    <p class="card-text text-muted mb-0">
                        Puedes filtrar por ubicacion de Google y calificacion minima para mostrar solo las reseñas que deseas destacar.
                    </p>
                </div>
                <hr class="my-0">
                <div class="card-body">
                    <h6 class="card-title mb-3">Como integrar</h6>
                    <p class="card-text text-muted mb-0">
                        Genera el codigo y pega el iframe en el HTML de tu sitio web. El widget se actualiza automaticamente con tus reseñas en tiempo real.
                    </p>
                </div>
            </div>
        </div>

    </div>

    {{-- Preview modal --}}
    <div class="modal fade" id="widget-preview-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Vista previa del widget</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">
                    <iframe id="widget-preview-frame" src="" width="100%" height="450"
                            frameborder="0" style="display:block;"></iframe>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
$(function () {
    var widgetBaseUrl = '{{ route('reviews.widget') }}';
    var embedCodeUrl  = '{{ route('reviews.embed-code') }}';

    function buildParams() {
        return {
            location_id : $('#widget_location_id').val() || null,
            min_rating  : $('#widget_min_rating').val(),
            limit       : Math.min(Math.max(parseInt($('#widget_limit').val()) || 5, 1), 20),
        };
    }

    function buildWidgetUrl(params) {
        var qs = $.param($.grep(
            Object.entries(params),
            function (e) { return e[1] !== null && e[1] !== ''; }
        ).reduce(function (acc, e) { acc[e[0]] = e[1]; return acc; }, {}));

        return qs ? widgetBaseUrl + '?' + qs : widgetBaseUrl;
    }

    $('#btn-preview').on('click', function () {
        var url = buildWidgetUrl(buildParams());
        $('#widget-preview-frame').attr('src', url);
        var modal = new bootstrap.Modal(document.getElementById('widget-preview-modal'));
        modal.show();
    });

    $('#btn-generate').on('click', function () {
        var params = buildParams();

        $.ajax({
            url    : embedCodeUrl,
            method : 'GET',
            data   : params,
            success: function (res) {
                $('#iframe-code').val(res.iframe);
                $('#embed-section').show();
                $('html, body').animate({ scrollTop: $('#embed-section').offset().top - 80 }, 300);
            },
            error: function () {
                toastr.error('No se pudo generar el codigo. Inténtalo de nuevo.');
            },
        });
    });

    $('#btn-copy-iframe').on('click', function () {
        var text = $('#iframe-code').val();
        if (! text) { return; }

        navigator.clipboard.writeText(text).then(function () {
            toastr.success('Codigo copiado al portapapeles');
        }).catch(function () {
            toastr.error('No se pudo copiar. Selecciona y copia manualmente.');
        });
    });

    $('.select2').select2({ minimumResultsForSearch: Infinity });
});
</script>
@endpush
