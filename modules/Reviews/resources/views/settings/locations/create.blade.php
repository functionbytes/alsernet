@extends('layouts.theme')

@section('title', 'Nueva ubicación')

@section('content')
    @include('core::components.card', ['title' => 'Nueva ubicación'])

    <div class="row g-3">
        <div class="col-12 col-lg-8">
            <div class="card">
                <div class="card-header border-bottom p-3">
                    <h5 class="mb-0 fw-bold">Datos de la ubicación</h5>
                    <small class="text-muted">Configura la ubicación y la estrategia de sincronización de reseñas</small>
                </div>
                <div class="card-body">
                    @include('core::components.alerts')

                    <form id="create-location-form">
                        @csrf

                        <div class="row g-3">
                            <div class="col-12">
                                <label for="name" class="form-label fw-semibold">Nombre de la ubicación <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" placeholder="Ej: Sucursal Centro" required>
                                <div class="invalid-feedback" id="error-name"></div>
                            </div>

                            <div class="col-12">
                                <label for="sync_strategy" class="form-label fw-semibold">Estrategia de sincronización <span class="text-danger">*</span></label>
                                <select class="form-select" id="sync_strategy" name="sync_strategy" required>
                                    <option value="">Seleccionar estrategia...</option>
                                    <option value="oauth">Google Business Profile (OAuth)</option>
                                    <option value="places_api">Google Places API</option>
                                    <option value="serpapi">SerpAPI</option>
                                    <option value="manual">Importación manual</option>
                                </select>
                                <div class="invalid-feedback" id="error-sync_strategy"></div>
                            </div>

                            {{-- OAuth connection (visible only for oauth strategy) --}}
                            <div class="col-12 d-none" id="field-connection">
                                <label for="connection_id" class="form-label fw-semibold">Conexión OAuth <span class="text-danger">*</span></label>
                                <select class="form-select" id="connection_id" name="connection_id">
                                    <option value="">Seleccionar conexión...</option>
                                    @foreach($connections as $connection)
                                        <option value="{{ $connection->id }}">
                                            {{ $connection->name }} ({{ $connection->google_email }})
                                        </option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback" id="error-connection_id"></div>
                                @if($connections->isEmpty())
                                    <small class="text-warning">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        No hay conexiones OAuth activas. <a href="{{ route('settings.reviews.connections.create') }}">Crear una conexión</a>.
                                    </small>
                                @endif
                            </div>

                            {{-- Place ID (visible for places_api and serpapi) --}}
                            <div class="col-12 d-none" id="field-place-id">
                                <label for="place_id" class="form-label fw-semibold">Google Place ID <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="place_id" name="place_id" placeholder="ChIJ...">
                                <div class="form-text">Puedes obtener el Place ID desde <a href="https://developers.google.com/maps/documentation/places/web-service/place-id" target="_blank">Google Maps Platform</a>.</div>
                                <div class="invalid-feedback" id="error-place_id"></div>
                            </div>

                            <div class="col-12">
                                <label for="address" class="form-label fw-semibold">Dirección</label>
                                <input type="text" class="form-control" id="address" name="address" placeholder="Calle, número, ciudad...">
                                <div class="invalid-feedback" id="error-address"></div>
                            </div>

                            <div class="col-md-6">
                                <label for="phone" class="form-label fw-semibold">Teléfono</label>
                                <input type="text" class="form-control" id="phone" name="phone" placeholder="+34 000 000 000">
                                <div class="invalid-feedback" id="error-phone"></div>
                            </div>

                            <div class="col-md-6">
                                <label for="website_url" class="form-label fw-semibold">Sitio web</label>
                                <input type="url" class="form-control" id="website_url" name="website_url" placeholder="https://...">
                                <div class="invalid-feedback" id="error-website_url"></div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="card-footer">
                    <button type="button" class="btn btn-primary w-100 mb-2" id="btn-save">
                        <i class="fas fa-save"></i> Guardar ubicación
                    </button>
                    <a href="{{ route('settings.reviews.locations.index') }}" class="btn btn-light w-100">Cancelar</a>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-3">Sobre las estrategias</h6>
                    <ul class="list-unstyled small text-muted">
                        <li class="mb-2"><strong>OAuth:</strong> Acceso completo via Google Business Profile. Requiere autenticar una cuenta de Google.</li>
                        <li class="mb-2"><strong>Places API:</strong> Obtiene reseñas publicas usando la API de Google Maps. Requiere un Place ID y clave de API.</li>
                        <li class="mb-2"><strong>SerpAPI:</strong> Obtiene reseñas mediante SerpAPI. Requiere un Place ID y clave de SerpAPI.</li>
                        <li class="mb-2"><strong>Manual:</strong> Importa reseñas desde CSV o las añade manualmente.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
$(document).ready(function () {
    var csrfToken = $('meta[name="csrf-token"]').attr('content');

    $('#sync_strategy').on('change', function () {
        var strategy = $(this).val();
        $('#field-connection').toggleClass('d-none', strategy !== 'oauth');
        $('#field-place-id').toggleClass('d-none', strategy !== 'places_api' && strategy !== 'serpapi');
    });

    $('#btn-save').on('click', function () {
        clearErrors();
        var $btn = $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');

        $.ajax({
            url: '{{ route('settings.reviews.locations.store') }}',
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            data: $('#create-location-form').serialize(),
            success: function (response) {
                if (response.success) {
                    toastr.success(response.message);
                    if (response.redirect) {
                        setTimeout(function () {
                            window.location.href = response.redirect;
                        }, 1000);
                    }
                }
            },
            error: function (xhr) {
                $btn.prop('disabled', false).html('<i class="fas fa-save"></i> Guardar ubicación');

                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    $.each(xhr.responseJSON.errors, function (field, messages) {
                        var input = $('#' + field);
                        input.addClass('is-invalid');
                        $('#error-' + field).text(messages[0]);
                    });
                    toastr.error('Por favor corrige los errores del formulario.');
                } else {
                    toastr.error('Error al guardar la ubicación. Intenta de nuevo.');
                }
            },
            complete: function () {
                $btn.prop('disabled', false).html('<i class="fas fa-save"></i> Guardar ubicación');
            }
        });
    });

    function clearErrors() {
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').text('');
    }
});
</script>
@endpush
