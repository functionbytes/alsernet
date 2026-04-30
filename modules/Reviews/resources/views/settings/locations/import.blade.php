@extends('layouts.theme')

@section('title', 'Importar reseñas - ' . $location->name)

@section('page_header')
    @include('core::components.card', ['title' => 'Importar reseñas'])
@endsection

@section('content')
    <div class="row g-3">
        {{-- CSV Import --}}
        <div class="col-12 col-lg-6">
            <div class="card h-100">
                <div class="card-header border-bottom p-3">
                    <h5 class="mb-0 fw-bold">Importar desde CSV</h5>
                    <small class="text-muted">Sube un archivo CSV con las reseñas de <strong>{{ $location->name }}</strong></small>
                </div>
                <div class="card-body">
                    @include('core::components.alerts')

                    <form id="csv-import-form" enctype="multipart/form-data">
                        @csrf

                        <div class="mb-3">
                            <label for="csv-file" class="form-label fw-semibold">Archivo CSV <span class="text-danger">*</span></label>
                            <input type="file" class="form-control" id="csv-file" name="file" accept=".csv,.txt" required>
                            <div class="form-text">Formato aceptado: CSV o TXT. Tamaño máximo: 5 MB.</div>
                            <div class="invalid-feedback" id="error-file"></div>
                        </div>

                        <div class="alert alert-info py-2 small">
                            <strong>Columnas esperadas:</strong><br>
                            <code>reviewer_name</code>, <code>star_rating</code>, <code>comment</code>, <code>review_date</code>, <code>reply_text</code>
                            <br>La primera fila debe ser el encabezado. Los campos <code>reviewer_name</code> y <code>star_rating</code> son obligatorios.
                        </div>
                    </form>
                </div>
                <div class="card-footer">
                    <button type="button" class="btn btn-primary w-100 mb-2" id="btn-import-csv">
                        <i class="fas fa-upload"></i> Importar CSV
                    </button>
                    <a href="{{ route('settings.reviews.locations.index') }}" class="btn btn-light w-100">Volver</a>
                </div>
            </div>
        </div>

        {{-- Manual Entry --}}
        <div class="col-12 col-lg-6">
            <div class="card h-100">
                <div class="card-header border-bottom p-3">
                    <h5 class="mb-0 fw-bold">Añadir reseña manual</h5>
                    <small class="text-muted">Crea una reseña manualmente para <strong>{{ $location->name }}</strong></small>
                </div>
                <div class="card-body">
                    <form id="manual-import-form">
                        @csrf

                        <div class="row g-3">
                            <div class="col-12">
                                <label for="reviewer_name" class="form-label fw-semibold">Nombre del revisor <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="reviewer_name" name="reviewer_name" placeholder="Nombre del cliente">
                                <div class="invalid-feedback" id="error-reviewer_name"></div>
                            </div>

                            <div class="col-md-6">
                                <label for="star_rating" class="form-label fw-semibold">Calificación <span class="text-danger">*</span></label>
                                <select class="form-select" id="star_rating" name="star_rating">
                                    <option value="">Seleccionar...</option>
                                    <option value="5">5 estrellas</option>
                                    <option value="4">4 estrellas</option>
                                    <option value="3">3 estrellas</option>
                                    <option value="2">2 estrellas</option>
                                    <option value="1">1 estrella</option>
                                </select>
                                <div class="invalid-feedback" id="error-star_rating"></div>
                            </div>

                            <div class="col-md-6">
                                <label for="review_date" class="form-label fw-semibold">Fecha de la reseña</label>
                                <input type="date" class="form-control" id="review_date" name="review_date">
                                <div class="invalid-feedback" id="error-review_date"></div>
                            </div>

                            <div class="col-12">
                                <label for="comment" class="form-label fw-semibold">Comentario</label>
                                <textarea class="form-control" id="comment" name="comment" rows="3" placeholder="Texto de la reseña..."></textarea>
                                <div class="invalid-feedback" id="error-comment"></div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="card-footer">
                    <button type="button" class="btn btn-success w-100 mb-2" id="btn-save-manual">
                        <i class="fas fa-plus"></i> Crear reseña
                    </button>
                    <a href="{{ route('settings.reviews.locations.index') }}" class="btn btn-light w-100">Volver</a>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
$(document).ready(function () {
    var csrfToken = $('meta[name="csrf-token"]').attr('content');
    var csvUrl = '{{ route('settings.reviews.locations.import.csv', $location) }}';
    var manualUrl = '{{ route('settings.reviews.locations.import.manual', $location) }}';

    $('#btn-import-csv').on('click', function () {
        clearErrors('csv');
        var $btn = $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Importando...');
        var formData = new FormData($('#csv-import-form')[0]);

        $.ajax({
            url: csvUrl,
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            data: formData,
            contentType: false,
            processData: false,
            success: function (response) {
                if (response.success) {
                    toastr.success(response.message);
                    $('#csv-import-form')[0].reset();
                }
            },
            error: function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    $.each(xhr.responseJSON.errors, function (field, messages) {
                        $('#' + (field === 'file' ? 'csv-file' : field)).addClass('is-invalid');
                        $('#error-' + field).text(messages[0]);
                    });
                    toastr.error('Por favor corrige los errores del formulario.');
                } else {
                    toastr.error('Error al importar el archivo. Intenta de nuevo.');
                }
            },
            complete: function () {
                $btn.prop('disabled', false).html('<i class="fas fa-upload"></i> Importar CSV');
            }
        });
    });

    $('#btn-save-manual').on('click', function () {
        clearErrors('manual');
        var $btn = $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Creando...');

        $.ajax({
            url: manualUrl,
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            data: $('#manual-import-form').serialize(),
            success: function (response) {
                if (response.success) {
                    toastr.success(response.message);
                    $('#manual-import-form')[0].reset();
                }
            },
            error: function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    $.each(xhr.responseJSON.errors, function (field, messages) {
                        $('#' + field).addClass('is-invalid');
                        $('#error-' + field).text(messages[0]);
                    });
                    toastr.error('Por favor corrige los errores del formulario.');
                } else {
                    toastr.error('Error al crear la reseña. Intenta de nuevo.');
                }
            },
            complete: function () {
                $btn.prop('disabled', false).html('<i class="fas fa-plus"></i> Crear reseña');
            }
        });
    });

    function clearErrors(form) {
        if (form === 'csv') {
            $('#csv-import-form .is-invalid').removeClass('is-invalid');
            $('#csv-import-form .invalid-feedback').text('');
        } else {
            $('#manual-import-form .is-invalid').removeClass('is-invalid');
            $('#manual-import-form .invalid-feedback').text('');
        }
    }
});
</script>
@endpush
