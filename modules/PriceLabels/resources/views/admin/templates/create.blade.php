@extends('layouts.theme')

@section('title', $pageTitle)

@section('content')

    @include('core::components.card', ['title' => $pageTitle])

    <div class="row g-3">

        <div class="col-12 col-lg-8">
            <div class="card">
                <form id="templateForm" action="{{ route('pricelabels.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Nueva plantilla</h5>
                        <small class="text-muted">Define el nombre y las imagenes base. Las posiciones y estilos de cada campo se ajustan despues, en el editor visual.</small>
                    </div>

                    <div class="card-body">
                        @include('core::components.alerts')

                        <div class="row">
                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="form-label">Nombre <span class="text-danger">*</span></label>
                                    <input type="text"
                                           class="form-control @error('name') is-invalid @enderror"
                                           name="name"
                                           value="{{ old('name') }}"
                                           placeholder="ej: Etiquetas tienda Madrid"
                                           required>
                                    @error('name')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Estado</label>
                                    <select class="form-select select2" name="is_active">
                                        <option value="1" {{ old('is_active', '1') == '1' ? 'selected' : '' }}>Activa</option>
                                        <option value="0" {{ old('is_active') == '0' ? 'selected' : '' }}>Inactiva</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Esta plantilla es para</label>
                                    <select class="form-select select2" name="orientation" id="orientationSelect">
                                        <option value="vertical" {{ old('orientation') == 'vertical' ? 'selected' : '' }}>Vertical</option>
                                        <option value="horizontal" {{ old('orientation') == 'horizontal' ? 'selected' : '' }}>Horizontal</option>
                                        <option value="both" {{ old('orientation', 'both') == 'both' ? 'selected' : '' }}>Ambas</option>
                                    </select>
                                    @error('orientation')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12 orientation-block orientation-vertical">
                                <div class="row">
                                    <div class="col-12 col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Filas (vertical)</label>
                                            <input type="number" min="1" max="20" class="form-control" name="vertical_rows" value="{{ old('vertical_rows', 2) }}">
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Columnas (vertical)</label>
                                            <input type="number" min="1" max="20" class="form-control" name="vertical_columns" value="{{ old('vertical_columns', 2) }}">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 orientation-block orientation-horizontal">
                                <div class="row">
                                    <div class="col-12 col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Filas (horizontal)</label>
                                            <input type="number" min="1" max="20" class="form-control" name="horizontal_rows" value="{{ old('horizontal_rows', 2) }}">
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Columnas (horizontal)</label>
                                            <input type="number" min="1" max="20" class="form-control" name="horizontal_columns" value="{{ old('horizontal_columns', 4) }}">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="form-label">Texto fijo</label>
                                    <input type="text"
                                           class="form-control @error('label_text') is-invalid @enderror"
                                           name="label_text"
                                           value="{{ old('label_text', 'Precio recomendado:') }}">
                                    <small class="form-text text-muted">Se repite en cada etiqueta (ej: "Precio recomendado:")</small>
                                    @error('label_text')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12 col-md-6 orientation-block orientation-vertical">
                                <div class="mb-3">
                                    <label class="form-label">Imagen base vertical</label>
                                    <input type="file"
                                           class="form-control @error('image_vertical') is-invalid @enderror"
                                           name="image_vertical" accept="image/*">
                                    <small class="form-text text-muted">A4 vertical</small>
                                    @error('image_vertical')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12 col-md-6 orientation-block orientation-horizontal">
                                <div class="mb-3">
                                    <label class="form-label">Imagen base horizontal</label>
                                    <input type="file"
                                           class="form-control @error('image_horizontal') is-invalid @enderror"
                                           name="image_horizontal" accept="image/*">
                                    <small class="form-text text-muted">A4 apaisado</small>
                                    @error('image_horizontal')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-1">Guardar y continuar</button>
                        <a href="{{ route('pricelabels.index') }}" class="btn btn-light w-100">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-3">Sobre las plantillas</h6>
                    <p class="card-text text-muted">
                        Una plantilla define el fondo, colores, fuentes y posiciones de cada campo
                        (referencia, descripcion, PVP) que se usan al generar el PDF de etiquetas.
                    </p>
                </div>
                <hr class="my-0">
                <div class="card-body">
                    <h6 class="card-title mb-3">Siguiente paso</h6>
                    <p class="card-text text-muted mb-0">
                        Tras guardar, se abre el editor visual para ajustar posiciones y estilos,
                        y subir el Excel con el catalogo para generar el PDF.
                    </p>
                </div>
            </div>
        </div>

    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    $('.select2').select2({ width: '100%' });

    function toggleOrientationBlocks() {
        var orientation = $('#orientationSelect').val();
        $('.orientation-vertical').toggle(orientation === 'vertical' || orientation === 'both');
        $('.orientation-horizontal').toggle(orientation === 'horizontal' || orientation === 'both');
    }

    toggleOrientationBlocks();
    $('#orientationSelect').on('change', toggleOrientationBlocks);
});
</script>
@endpush
