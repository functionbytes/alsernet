@extends('layouts.theme')

@section('title', $pageTitle)

@push('styles')
<link rel="stylesheet" href="{{ asset('modules/pricelabels/css/editor.css') }}">
@endpush

@section('content')

    @include('core::components.card', ['title' => $pageTitle])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">

            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">{{ $pageTitle }}</h5>
                        <p class="small mb-0 text-muted">Plantillas para generar PDF de etiquetas de precio</p>
                    </div>
                    <div class="ms-auto">
                        <a href="{{ route('pricelabels.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> Nueva plantilla
                        </a>
                    </div>
                </div>
            </div>

            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('pricelabels.index') }}" id="filterForm">
                    <div class="d-flex gap-2 align-items-center">
                        <div class="flex-fill">
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-1">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="search" name="search" class="form-control border-start-0 ps-0"
                                       placeholder="Buscar por nombre..."
                                       value="{{ request('search') }}">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary flex-shrink-0">
                            <i class="fas fa-search"></i>
                        </button>
                        @if(request('search'))
                            <a href="{{ route('pricelabels.index') }}" class="btn btn-outline-secondary flex-shrink-0" title="Limpiar">
                                <i class="fas fa-times"></i>
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            <div class="card-body">
                @if($templates->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle text-nowrap">
                            <thead class="table-light">
                                <tr>
                                    <th width="3%"><input type="checkbox" id="select-all" class="form-check-input"></th>
                                    <th width="5%">Vista previa</th>
                                    <th>Nombre</th>
                                    <th>Estado</th>
                                    <th>Imagenes / Grid</th>
                                    <th>Actualizada</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($templates as $template)
                                    <tr>
                                        <td>
                                            <input type="checkbox" class="form-check-input bulk-checkbox" value="{{ $template->id }}">
                                        </td>
                                        <td>
                                            @if($template->thumbnail_url)
                                                <img src="{{ $template->thumbnail_url }}" alt="" class="pricelabels-index-thumb" loading="lazy">
                                            @else
                                                <span class="text-muted small">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="small fw-semibold">{{ $template->name }}</div>
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $template->is_active ? 'success' : 'secondary' }}-subtle text-{{ $template->is_active ? 'success' : 'secondary' }}">
                                                {{ $template->is_active ? 'Activa' : 'Inactiva' }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($template->image_vertical)
                                                <span class="badge bg-info-subtle text-info">Vertical</span>
                                            @endif
                                            @if($template->image_horizontal)
                                                <span class="badge bg-info-subtle text-info">Horizontal</span>
                                            @endif
                                            @if(! $template->image_vertical && ! $template->image_horizontal)
                                                <span class="text-muted small">Sin imagenes</span>
                                            @endif
                                            <div class="small text-muted">{{ $template->grid_summary }}</div>
                                        </td>
                                        <td>
                                            <div class="small">{{ $template->updated_at->format('d/m/Y H:i') }}</div>
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <a href="#" class="text-muted" data-bs-toggle="dropdown" data-bs-boundary="viewport">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item generate-btn" href="#"
                                                           data-url="{{ route('pricelabels.generate', $template) }}"
                                                           data-preview-url="{{ route('pricelabels.preview-excel', $template) }}"
                                                           data-name="{{ $template->name }}"
                                                           data-has-vertical="{{ $template->image_vertical ? 1 : 0 }}"
                                                           data-has-horizontal="{{ $template->image_horizontal ? 1 : 0 }}"
                                                           data-field-labels="{{ json_encode($template->field_labels_map) }}">
                                                            Generar PDF
                                                        </a>
                                                    </li>
                                                    <li><a class="dropdown-item" href="{{ route('pricelabels.edit', $template) }}">Editar</a></li>
                                                    <li><a class="dropdown-item" href="{{ route('pricelabels.positions.edit', $template) }}">Editar posiciones</a></li>
                                                    <li>
                                                        <a class="dropdown-item duplicate-btn" href="#"
                                                           data-url="{{ route('pricelabels.duplicate', $template) }}">
                                                            Duplicar
                                                        </a>
                                                    </li>
                                                    <li><a class="dropdown-item" href="{{ route('pricelabels.history.index', ['template_id' => $template->id]) }}">Ver historial</a></li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <a class="dropdown-item delete-btn" href="#"
                                                           data-url="{{ route('pricelabels.destroy', $template) }}"
                                                           data-title="Eliminar {{ $template->name }}">
                                                            Eliminar
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-tags fa-3x mb-3 text-muted opacity-50"></i>
                        <h5 class="fw-bold mb-2">
                            @if(request('search'))
                                No se encontraron resultados
                            @else
                                No hay plantillas
                            @endif
                        </h5>
                        <p class="text-muted mb-4">
                            @if(request('search'))
                                No hay resultados para "{{ request('search') }}"
                            @else
                                Aun no hay plantillas de etiquetas creadas
                            @endif
                        </p>
                        @if(request('search'))
                            <a href="{{ route('pricelabels.index') }}" class="btn btn-secondary">Limpiar filtros</a>
                        @else
                            <a href="{{ route('pricelabels.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i> Nueva plantilla
                            </a>
                        @endif
                    </div>
                @endif
            </div>

            @if($templates->hasPages())
                <div class="card-footer bg-white border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted">
                            Mostrando {{ $templates->firstItem() }} - {{ $templates->lastItem() }} de {{ $templates->total() }}
                        </div>
                        <div>
                            {{ $templates->appends(request()->input())->links() }}
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <div id="bulk-toolbar" class="position-fixed bottom-0 start-50 translate-middle-x mb-4 d-none" style="z-index:1050;">
        <button type="button" class="btn btn-primary shadow-lg px-4" data-bs-toggle="modal" data-bs-target="#bulk-modal">
            <span data-bulk-count>0</span> seleccionado(s) &mdash; Aplicar accion
        </button>
    </div>

    <div class="modal fade" id="bulk-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Accion masiva</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">Se aplicara la accion sobre <strong><span data-bulk-count>0</span> plantilla(s)</strong>.</p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Accion</label>
                        <select id="bulk-action-select" class="form-select">
                            <option value="">Seleccionar accion...</option>
                            <option value="delete">Eliminar</option>
                            <option value="activate">Activar</option>
                            <option value="deactivate">Desactivar</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer flex-column">
                    <button id="bulk-apply-btn" type="button" class="btn btn-primary w-100 mb-2">Aplicar</button>
                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="generate-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="generate-form" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Generar PDF</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted mb-3">
                            Genera el PDF de <strong id="generate-modal-name"></strong> a partir de su plantilla ya configurada,
                            subiendo solo el catalogo en Excel.
                        </p>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Formato</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="type" value="vertical" id="generate-type-vertical" checked>
                                <label class="form-check-label" for="generate-type-vertical">Vertical (4 etiquetas por hoja)</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="type" value="horizontal" id="generate-type-horizontal">
                                <label class="form-check-label" for="generate-type-horizontal">Horizontal (8 etiquetas por hoja)</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Archivo Excel (XLSX/XLS)</label>
                            <input type="file" id="generate-modal-excel" name="excel_file" accept=".xlsx,.xls" class="form-control" required>
                            <small class="form-text text-muted" id="generate-modal-columns-hint"></small>
                            <div id="generate-modal-preview" class="small mt-2 d-none"></div>
                            <div id="generate-modal-status" class="small mt-2"></div>
                        </div>
                    </div>
                    <div class="modal-footer flex-column">
                        <button type="submit" class="btn btn-primary w-100 mb-2">Generar</button>
                        <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <form id="duplicate-form" method="POST" class="d-none">
        @csrf
    </form>

    @include('core::components.delete')

@endsection

@push('scripts')
<script src="{{ asset('modules/pricelabels/js/generate-shared.js') }}"></script>
<script>
$(document).ready(function () {
    const bulk = window.BulkActions.init({ checkbox: '.bulk-checkbox' });

    $('#bulk-action-select').select2({
        dropdownParent: $('#bulk-modal'),
        width: '100%'
    });

    $('#bulk-modal').on('hide.bs.modal', function () {
        $('#bulk-action-select').val('').trigger('change');
        $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
        bulk.reset();
    });

    $('#bulk-apply-btn').on('click', function () {
        const action = $('#bulk-action-select').val();
        const ids = bulk.getIds();

        if (!action) { toastr.warning('Selecciona una accion.'); return; }
        if (!ids.length) { toastr.warning('Selecciona al menos una plantilla.'); return; }
        if (action === 'delete' && !confirm('¿Eliminar las ' + ids.length + ' plantilla(s)?')) return;

        $('#bulk-apply-btn').prop('disabled', true).text('Procesando...');

        $.ajax({
            url: '{{ route('pricelabels.bulk-action') }}',
            method: 'POST',
            data: JSON.stringify({ action: action, ids: ids }),
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                $('#bulk-modal').modal('hide');
                toastr.success('Plantillas procesadas correctamente.');
                setTimeout(() => location.reload(), 800);
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message ?? 'Error al procesar.');
                $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
            },
        });
    });

    $(document).on('click', '.delete-btn', function (e) {
        e.preventDefault();
        $('#delete-modal .modal-title').text($(this).data('title'));
        $('#delete-form').attr('action', $(this).data('url'));
        $('#delete-modal').modal('show');
    });

    $(document).on('click', '.generate-btn', function (e) {
        e.preventDefault();
        const $btn = $(this);
        const hasVertical = $btn.data('has-vertical') == 1;
        const hasHorizontal = $btn.data('has-horizontal') == 1;
        const fieldLabels = $btn.data('field-labels') || {};

        $('#generate-form').attr('action', $btn.data('url')).data('preview-url', $btn.data('preview-url')).data('field-labels', fieldLabels);
        $('#generate-modal-name').text($btn.data('name'));
        $('#generate-modal-excel').val('');
        $('#generate-modal-preview').addClass('d-none').empty();
        $('#generate-modal-status').empty();

        const columnLabels = Object.keys(fieldLabels).filter((key) => key !== 'label').map((key) => fieldLabels[key].toUpperCase());
        $('#generate-modal-columns-hint').text(columnLabels.length ? 'Columnas: ' + columnLabels.join(', ') + ' (fila 1 = cabecera)' : '');

        $('#generate-type-vertical').prop('disabled', !hasVertical);
        $('#generate-type-horizontal').prop('disabled', !hasHorizontal);
        $('#generate-type-vertical').prop('checked', hasVertical);
        $('#generate-type-horizontal').prop('checked', !hasVertical && hasHorizontal);

        $('#generate-modal').modal('show');
    });

    $(document).on('click', '.duplicate-btn', function (e) {
        e.preventDefault();
        $('#duplicate-form').attr('action', $(this).data('url')).trigger('submit');
    });

    $('#generate-modal-excel').on('change', function () {
        const file = this.files[0];
        const url = $('#generate-form').data('preview-url');
        const fieldLabels = $('#generate-form').data('field-labels') || {};
        const $preview = $('#generate-modal-preview');

        if (!file || !url) { $preview.addClass('d-none').empty(); return; }

        const fd = new FormData();
        fd.append('excel_file', file);

        $preview.removeClass('d-none').html('<span class="text-muted">Analizando archivo...</span>');

        $.ajax({
            url: url,
            method: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) { window.PriceLabelsShared.renderExcelPreview($preview, res, fieldLabels); },
            error: function (xhr) {
                $preview.removeClass('d-none').html('<span class="text-danger">' + (xhr.responseJSON?.message ?? 'No se pudo leer el archivo.') + '</span>');
            },
        });
    });

    let generateIsSubmitting = false;

    $('#generate-form').on('submit', function (e) {
        e.preventDefault();

        if (generateIsSubmitting) { return; }
        generateIsSubmitting = true;

        const $form = $(this);
        const $submitBtn = $form.find('button[type="submit"]');

        $submitBtn.prop('disabled', true).text('Generando...');
        $('#generate-modal-status').html('<span class="text-muted">Enviando archivo...</span>');

        const fd = new FormData(this);

        function restoreButton() {
            generateIsSubmitting = false;
            $submitBtn.prop('disabled', false).text('Generar');
        }

        $.ajax({
            url: $form.attr('action'),
            method: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                $('#generate-modal-status').html('<span class="text-muted">Procesando etiquetas en segundo plano...</span>');
                window.PriceLabelsShared.pollGenerationStatus({
                    statusUrl: res.status_url,
                    $status: $('#generate-modal-status'),
                    onDone: restoreButton,
                });
            },
            error: function (xhr) {
                const message = xhr.responseJSON?.message ?? (xhr.responseJSON?.errors && Object.values(xhr.responseJSON.errors)[0][0]) ?? 'Error al enviar el archivo.';
                $('#generate-modal-status').html('<span class="text-danger">' + message + '</span>');
                toastr.error(message);
                restoreButton();
            },
        });
    });

    @if(session('success'))
        toastr.success('{{ session('success') }}', 'Exito');
    @endif
});
</script>
@endpush
