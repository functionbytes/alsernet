@extends('layouts.theme')

@section('title', $pageTitle)

@push('styles')
<link rel="stylesheet" href="{{ asset('modules/pricelabels/css/editor.css') }}">
@if($fontFaceCss)
    <style>{!! $fontFaceCss !!}</style>
@endif
@endpush

@section('content')

    @php
        $fieldLabels = collect($fieldDefinitions)->pluck('label', 'key')->all();
        $fieldLabels['label'] = 'Texto fijo (label)';
        $showVertical = in_array($template->orientation, ['vertical', 'both'], true);
        $showHorizontal = in_array($template->orientation, ['horizontal', 'both'], true);
    @endphp

    @include('core::components.card', ['title' => $pageTitle])

    <nav class="pricelabels-subnav mb-3">
        <a href="#section-datos" class="pricelabels-subnav-link">Datos y estilo</a>
        @if($showVertical)
            <a href="#section-vertical" class="pricelabels-subnav-link">Vista previa vertical</a>
        @endif
        @if($showHorizontal)
            <a href="#section-horizontal" class="pricelabels-subnav-link">Vista previa horizontal</a>
        @endif
        <a href="#section-generar" class="pricelabels-subnav-link">Generar PDF</a>
        <a href="{{ route('pricelabels.positions.edit', $template) }}" class="pricelabels-subnav-link pricelabels-subnav-link-alt">
            Editar solo posiciones
        </a>
    </nav>

    <div class="row g-3">

        <div class="col-12" id="section-datos">
            <div class="card">
                <form id="templateForm" action="{{ route('pricelabels.update', $template) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Datos generales</h5>
                        <small class="text-muted">Nombre, estado, texto fijo e imagenes base de la plantilla.</small>
                    </div>

                    <div class="card-body">
                        @include('core::components.alerts')

                        <div class="row g-3 mb-4">
                            <div class="col-12 col-md-6">
                                <label class="form-label">Nombre <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror"
                                       name="name" value="{{ old('name', $template->name) }}" required>
                                @error('name')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12 col-md-3">
                                <label class="form-label">Estado</label>
                                <select class="form-select select2" name="is_active">
                                    <option value="1" {{ old('is_active', $template->is_active ? '1' : '0') == '1' ? 'selected' : '' }}>Activa</option>
                                    <option value="0" {{ old('is_active', $template->is_active ? '1' : '0') == '0' ? 'selected' : '' }}>Inactiva</option>
                                </select>
                            </div>

                            <div class="col-12 col-md-3">
                                <label class="form-label">Texto fijo</label>
                                <input type="text" class="form-control @error('label_text') is-invalid @enderror"
                                       name="label_text" value="{{ old('label_text', $template->label_text) }}">
                                @error('label_text')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12 col-md-3">
                                <label class="form-label">Esta plantilla es para</label>
                                <select class="form-select select2" name="orientation">
                                    <option value="vertical" {{ old('orientation', $template->orientation) == 'vertical' ? 'selected' : '' }}>Vertical</option>
                                    <option value="horizontal" {{ old('orientation', $template->orientation) == 'horizontal' ? 'selected' : '' }}>Horizontal</option>
                                    <option value="both" {{ old('orientation', $template->orientation) == 'both' ? 'selected' : '' }}>Ambas</option>
                                </select>
                                @error('orientation')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                                <small class="form-text text-muted">Cambiar y guardar actualiza las columnas de estilo abajo.</small>
                            </div>

                            @if($showVertical)
                                <div class="col-12 col-md-3">
                                    <label class="form-label">Filas x columnas (vertical)</label>
                                    <div class="d-flex gap-2">
                                        <input type="number" min="1" max="20" class="form-control" name="vertical_rows" value="{{ old('vertical_rows', $template->vertical_rows) }}">
                                        <input type="number" min="1" max="20" class="form-control" name="vertical_columns" value="{{ old('vertical_columns', $template->vertical_columns) }}">
                                    </div>
                                </div>
                            @endif

                            @if($showHorizontal)
                                <div class="col-12 col-md-3">
                                    <label class="form-label">Filas x columnas (horizontal)</label>
                                    <div class="d-flex gap-2">
                                        <input type="number" min="1" max="20" class="form-control" name="horizontal_rows" value="{{ old('horizontal_rows', $template->horizontal_rows) }}">
                                        <input type="number" min="1" max="20" class="form-control" name="horizontal_columns" value="{{ old('horizontal_columns', $template->horizontal_columns) }}">
                                    </div>
                                </div>
                            @endif

                            @if($showVertical)
                                <div class="col-12 col-md-6">
                                    <label class="form-label">Imagen base vertical</label>
                                    <input type="file" class="form-control @error('image_vertical') is-invalid @enderror"
                                           name="image_vertical" accept="image/*">
                                    @if($template->image_vertical)
                                        <small class="form-text text-muted">Actual: {{ basename($template->image_vertical) }}</small>
                                    @endif
                                    @error('image_vertical')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            @endif

                            @if($showHorizontal)
                                <div class="col-12 col-md-6">
                                    <label class="form-label">Imagen base horizontal</label>
                                    <input type="file" class="form-control @error('image_horizontal') is-invalid @enderror"
                                           name="image_horizontal" accept="image/*">
                                    @if($template->image_horizontal)
                                        <small class="form-text text-muted">Actual: {{ basename($template->image_horizontal) }}</small>
                                    @endif
                                    @error('image_horizontal')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            @endif
                        </div>

                        @include('pricelabels::admin.templates.partials.style-table', ['showFieldActions' => true])
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-1">Guardar cambios</button>
                        <a href="{{ route('pricelabels.index') }}" class="btn btn-light w-100">Volver al listado</a>
                    </div>
                </form>

                <div class="card-footer border-top">
                    <h6 class="fw-bold mb-3">Anadir campo</h6>
                    <form action="{{ route('pricelabels.fields.store', $template) }}" method="POST" class="row g-2 align-items-end">
                        @csrf
                        <div class="col-12 col-md-4">
                            <label class="form-label">Nombre del campo</label>
                            <input type="text" class="form-control" name="label" placeholder="ej: Promocion" required>
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label">Columna Excel</label>
                            <input type="text" class="form-control" name="excel_column" placeholder="ej: E" maxlength="2" required>
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label">Tipo</label>
                            <select class="form-select" name="type" id="new-field-type">
                                <option value="text">Texto</option>
                                <option value="price">Precio</option>
                                <option value="barcode">Codigo de barras</option>
                                <option value="qr">Codigo QR</option>
                            </select>
                        </div>
                        <div class="col-6 col-md-3 d-none" id="new-field-symbology-wrapper">
                            <label class="form-label">Simbologia</label>
                            <select class="form-select" name="barcode_type">
                                @foreach($symbologies as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-2">
                            <button type="submit" class="btn btn-outline-primary w-100">Anadir</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        @include('pricelabels::admin.templates.partials.positions-canvas')

        <div class="col-12" id="section-generar">
            <div class="card">
                <div class="card-header border-bottom p-3">
                    <h5 class="mb-0 fw-bold">Generar PDF</h5>
                    <small class="text-muted">
                        Columnas del Excel:
                        @foreach($fieldDefinitions as $definition)
                            {{ strtoupper($definition['label']) }} ({{ $definition['excel_column'] }}){{ ! $loop->last ? ',' : '' }}
                        @endforeach
                        (fila 1 = cabecera)
                        &middot;
                        <a href="{{ route('pricelabels.excel-template', $template) }}">Descargar plantilla Excel</a>
                    </small>
                </div>
                <div class="card-body">
                    <form id="generate-form" action="{{ route('pricelabels.generate', $template) }}" method="POST"
                          enctype="multipart/form-data" data-preview-url="{{ route('pricelabels.preview-excel', $template) }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Archivo Excel (XLSX/XLS)</label>
                            <input type="file" id="generate-excel" name="excel_file" accept=".xlsx,.xls" class="form-control" required>
                            <div id="generate-excel-preview" class="small mt-2 d-none"></div>
                        </div>
                        @if($showVertical)
                            <button type="submit" name="type" value="vertical" class="btn btn-primary" {{ $template->image_vertical ? '' : 'disabled' }}>
                                Generar PDF vertical
                            </button>
                        @endif
                        @if($showHorizontal)
                            <button type="submit" name="type" value="horizontal" class="btn btn-outline-primary" {{ $template->image_horizontal ? '' : 'disabled' }}>
                                Generar PDF horizontal
                            </button>
                        @endif
                        <div id="generate-status" class="small mt-2"></div>
                    </form>
                </div>
            </div>
        </div>

    </div>

    @include('core::components.delete')
    @include('pricelabels::admin.templates.partials.preview-pdf-modal')

    <script>
        window.PRICE_LABELS_EDITOR = {
            urls: {
                savePositions: "{{ route('pricelabels.positions.save', $template) }}",
                previewPdf: "{{ route('pricelabels.preview-pdf', $template) }}",
            },
            positions: {
                vertical: @json($positionsVertical),
                horizontal: @json($positionsHorizontal),
            },
            fields: @json($fields),
            fieldKeys: @json($fieldKeys),
            fieldLabels: @json($fieldLabels),
            fontStacks: @json($fontStacks),
            barcodePreviews: @json($barcodePreviews),
            labelText: @json($template->label_text),
            grid: {
                vertical: { rows: {{ $template->vertical_rows }}, columns: {{ $template->vertical_columns }} },
                horizontal: { rows: {{ $template->horizontal_rows }}, columns: {{ $template->horizontal_columns }} },
            },
            newFieldKey: @json(session('new_field_key')),
            sampleRow: @json($sampleRow ?? null),
        };
    </script>

@endsection

@push('scripts')
<script src="{{ asset('modules/pricelabels/js/vendor/interact.min.js') }}"></script>
<script src="{{ asset('modules/pricelabels/js/generate-shared.js') }}"></script>
<script src="{{ asset('modules/pricelabels/js/editor.js') }}"></script>
<script>
$(document).ready(function () {
    $('.select2').select2({ width: '100%' });

    $('#new-field-type').on('change', function () {
        $('#new-field-symbology-wrapper').toggleClass('d-none', $(this).val() !== 'barcode');
    }).trigger('change');

    $('.delete-btn').on('click', function () {
        $('#delete-modal .modal-title').text($(this).data('title'));
        $('#delete-form').attr('action', $(this).data('url'));
    });

    @if(session('success'))
        toastr.success('{{ session('success') }}', 'Exito');
    @endif
});
</script>
@endpush
