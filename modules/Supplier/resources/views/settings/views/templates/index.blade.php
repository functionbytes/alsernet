@extends('layouts.theme')

@section('title', 'Plantillas de Prompts')

@section('page_header')
    @include('core::components.card', ['title' => 'Biblioteca de plantillas de prompts'])
@endsection

@section('content')
    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">
            <!-- Header Section -->
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Plantillas de prompts</h5>
                        <p class="small mb-0 text-muted">Plantillas reutilizables para crear prompts rápidamente</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('settings.suppliers.templates.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title text-primary mb-2">Total</h6>
                                        <h4 class="mb-1 fw-bold">{{ $stats['total_templates'] }}</h4>
                                        <small class="text-muted">Disponibles para clonar</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title mb-2">Prompts creados</h6>
                                        <h4 class="mb-1 fw-bold">{{ $stats['total_cloned'] }}</h4>
                                        <small class="text-muted">Desde plantillas</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title text-info mb-2">Categorías</h6>
                                        <h4 class="mb-1 fw-bold">{{ $stats['categories_count'] }}</h4>
                                        <small class="text-muted">De plantillas</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title text-warning mb-2">Tipos</h6>
                                        <h4 class="mb-1 fw-bold">{{ count($contentTypes) }}</h4>
                                        <small class="text-muted">De contenido disponibles</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Section -->
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('settings.suppliers.templates.index') }}">
                    <div class="d-flex align-items-center gap-2">
                        <input type="search" name="search" class="form-control flex-grow-1"
                               placeholder="Buscar por nombre..."
                               value="{{ request('search') }}">
                        <div style="width: 180px; flex-shrink: 0;">
                            <select class="form-control select2" name="category">
                                <option value="">Todas las categorías</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat }}" {{ request('category') == $cat ? 'selected' : '' }}>
                                        {{ ucfirst($cat) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div style="width: 180px; flex-shrink: 0;">
                            <select class="form-control select2" name="content_type">
                                <option value="">Todos los tipos</option>
                                @foreach($contentTypes as $type)
                                    <option value="{{ $type }}" {{ request('content_type') == $type ? 'selected' : '' }}>
                                        {{ ucfirst(str_replace('_', ' ', $type)) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="d-flex gap-1 flex-shrink-0">
                            <button type="submit" class="btn btn-primary" title="Buscar">
                                <i class="fas fa-magnifying-glass"></i>
                            </button>
                            @if(request('search') || request('category') || request('content_type'))
                                <a href="{{ route('settings.suppliers.templates.index') }}"
                                   class="btn btn-secondary" title="Limpiar filtros">
                                    <i class="fas fa-xmark"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>

            <!-- Templates List -->
            <div class="card-body">
                @if($templates->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="3%"><input type="checkbox" id="select-all" class="form-check-input"></th>
                                    <th>Nombre</th>
                                    <th>Categoría</th>
                                    <th>Tipo</th>
                                    <th class="text-center">Prompts creados</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($templates as $template)
                                    <tr>
                                        <td><input type="checkbox" class="form-check-input bulk-checkbox" value="{{ $template->uid }}"></td>
                                        <td>
                                            <strong>{{ $template->label }}</strong>
                                        </td>
                                        <td>
                                            @if($template->template_category)
                                                <span class="badge bg-info-subtle text-info">{{ $template->template_category }}</span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-primary-subtle text-primary">
                                                {{ str_replace('_', ' ', $template->content_type) }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <small class="text-muted">{{ $template->cloned_prompts_count }}</small>
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-ellipsis"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('settings.suppliers.templates.edit', $template->uid) }}">
                                                            Editar
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <button type="button"
                                                                class="dropdown-item clone-template-btn"
                                                                data-template-uid="{{ $template->uid }}"
                                                                data-template-label="{{ $template->label }}"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#cloneModal">
                                                            Clonar
                                                        </button>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <button type="button"
                                                                class="dropdown-item delete-btn"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#delete-modal"
                                                                data-url="{{ route('settings.suppliers.templates.destroy', $template->uid) }}"
                                                                data-title="Eliminar plantilla: {{ $template->label }}">
                                                            Eliminar
                                                        </button>
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
                        <div class="d-flex flex-column align-items-center">
                            <div class="round-48 rounded-circle bg-light-subtle text-muted mb-3 d-flex align-items-center justify-content-center">
                                <i class="fas fa-file-alt fs-7"></i>
                            </div>
                            <h6 class="mb-1">No hay plantillas disponibles</h6>
                            <p class="text-muted mb-3">
                                @if(request('search') || request('category') || request('content_type'))
                                    No se encontraron plantillas con los filtros seleccionados
                                @else
                                    Crea tu primera plantilla para comenzar
                                @endif
                            </p>
                            @if(!request('category') && !request('content_type'))
                                <a href="{{ route('settings.suppliers.templates.create') }}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-plus"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            <!-- Pagination -->
            <div class="card-footer bg-white border-top py-2">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2">
                        @if($templates->total() > 0)
                        <span class="text-muted small">
                            Mostrando {{ $templates->firstItem() }}–{{ $templates->lastItem() }} de {{ $templates->total() }}
                        </span>
                        @endif
                        <form method="GET" action="{{ route('settings.suppliers.templates.index') }}" class="d-inline-flex align-items-center gap-1 mb-0">
                            @foreach(request()->except(['per_page', 'page']) as $key => $value)
                                @if(is_array($value))
                                    @foreach($value as $v)
                                        <input type="hidden" name="{{ $key }}[]" value="{{ $v }}">
                                    @endforeach
                                @else
                                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                @endif
                            @endforeach
                            <label class="text-muted small mb-0">Por página:</label>
                            <select name="per_page" class="form-select form-select-sm d-inline-block w-auto" onchange="this.form.submit()">
                                @foreach([10, 20, 50, 100] as $opt)
                                    <option value="{{ $opt }}" {{ request('per_page', 20) == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                @endforeach
                                <option value="200" {{ request('per_page') == '200' ? 'selected' : '' }}>200</option>
                            </select>
                        </form>
                    </div>
                    @if($templates->hasPages())
                    <nav>{{ $templates->appends(request()->query())->links('pagination::bootstrap-5') }}</nav>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Clone Modal -->
    <div class="modal fade" id="cloneModal" tabindex="-1" aria-labelledby="cloneModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form id="cloneForm" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="cloneModalLabel">Clonar plantilla</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Plantilla seleccionada:</strong> <span id="selectedTemplateName"></span>
                        </div>

                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label fw-semibold">Nombre del nuevo prompt <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="label" required placeholder="Ej: Nike Golf - Descripción Detallada">
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold">Scope <span class="text-danger">*</span></label>
                                <select class="form-control select2" name="scope" id="scopeSelect" required>
                                    <option value="">Seleccionar scope...</option>
                                    <option value="global">Global (todos los productos)</option>
                                    <option value="supplier">Proveedor específico</option>
                                    <option value="category">Categoría específica</option>
                                    <option value="supplier_category">Proveedor + Categoría (más específico)</option>
                                </select>
                                <small class="text-muted">El scope determina cuándo se usará este prompt</small>
                            </div>

                            <div id="supplierContainer" class="col-12" style="display: none;">
                                <label class="form-label fw-semibold">Proveedor <span class="text-danger">*</span></label>
                                <select class="form-control select2" name="supplier_id" id="supplierSelect">
                                    <option value="">Seleccionar proveedor...</option>
                                    @foreach($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}">{{ $supplier->label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div id="categoryContainer" class="col-12" style="display: none;">
                                <label class="form-label fw-semibold">Categoría <span class="text-danger">*</span></label>
                                <select class="form-control select2" name="category_id" id="categorySelect">
                                    <option value="">Seleccionar categoría...</option>
                                    @foreach($allCategories as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold">Prioridad</label>
                                <input type="number" class="form-control" name="priority" value="0" min="0" max="100">
                                <small class="text-muted">Mayor prioridad = se selecciona primero (0-100)</small>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary mb-1 w-100">Crear prompt desde plantilla </button>
                        <button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @include('core::components.delete')

    {{-- Bulk toolbar flotante --}}
    <div id="bulk-toolbar" class="position-fixed bottom-0 start-50 translate-middle-x mb-4 d-none" style="z-index:1050;">
        <button type="button" class="btn btn-primary shadow-lg px-4" data-bs-toggle="modal" data-bs-target="#bulk-modal">
            <span data-bulk-count>0</span> seleccionada(s) &mdash; Aplicar acción
        </button>
    </div>

    {{-- Bulk modal --}}
    <div class="modal fade" id="bulk-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Acción masiva</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">Se aplicará la acción sobre <strong><span data-bulk-count>0</span> plantilla(s)</strong>.</p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Acción</label>
                        <select id="bulk-action-select" class="form-select">
                            <option value="">Seleccionar acción...</option>
                            <option value="delete">Eliminar</option>
                        </select>
                    </div>
                    <div class="alert alert-warning small mb-0">
                        <i class="fas fa-triangle-exclamation me-1"></i>
                        Las plantillas con prompts asociados no se eliminarán.
                    </div>
                </div>
                <div class="modal-footer">
                    <button id="bulk-apply-btn" type="button" class="btn btn-primary w-100 mb-1">Aplicar</button>
                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script src="{{ asset('core/js/bulk.js?v=2') }}"></script>
<script>
$(document).ready(function() {
    $('.select2').select2({
        allowClear: false,
        minimumResultsForSearch: Infinity
    });

    const bulk = window.BulkActions.init({ checkbox: '.bulk-checkbox' });

    $('#bulk-action-select').select2({ dropdownParent: $('#bulk-modal'), width: '100%' });

    $('#bulk-modal').on('hide.bs.modal', function () {
        $('#bulk-action-select').val('').trigger('change');
        $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
        bulk.reset();
    });

    $('#bulk-apply-btn').on('click', function () {
        const action = $('#bulk-action-select').val();
        const uids   = bulk.getIds();

        if (!action) { toastr.warning('Selecciona una acción.'); return; }
        if (!uids.length) { toastr.warning('Selecciona al menos una plantilla.'); return; }
        if (!confirm('¿Eliminar las ' + uids.length + ' plantilla(s) seleccionadas?')) { return; }

        $('#bulk-apply-btn').prop('disabled', true).text('Procesando...');

        $.ajax({
            url: '{{ route("settings.suppliers.templates.bulk-action") }}',
            method: 'POST',
            data: JSON.stringify({ action: action, uids: uids, _token: $('meta[name="csrf-token"]').attr('content') }),
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                $('#bulk-modal').modal('hide');
                toastr.success(res.message);
                setTimeout(() => location.reload(), 800);
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message ?? 'Error al procesar.');
                $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
            },
        });
    });

    // Init select2 inside clone modal with correct parent & width
    $('#cloneModal').on('shown.bs.modal', function () {
        $('#scopeSelect, #supplierSelect, #categorySelect').each(function () {
            if ($(this).hasClass('select2-hidden-accessible')) {
                $(this).select2('destroy');
            }
            $(this).select2({
                dropdownParent: $('#cloneModal'),
                width: '100%',
                allowClear: false,
                minimumResultsForSearch: Infinity,
            });
        });
    });

    // Handle clone modal
    $('.clone-template-btn').on('click', function() {
        const templateUid  = $(this).data('template-uid');
        const templateLabel = $(this).data('template-label');
        const cloneUrl = '{{ route("settings.suppliers.templates.clone", "__UID__") }}'.replace('__UID__', templateUid);

        $('#selectedTemplateName').text(templateLabel);
        $('#cloneForm').attr('action', cloneUrl);
    });

    // Handle scope change
    $('#scopeSelect').on('change', function() {
        const scope = $(this).val();

        $('#supplierContainer').hide();
        $('#categoryContainer').hide();
        $('#supplierSelect').prop('required', false);
        $('#categorySelect').prop('required', false);

        if (scope === 'supplier' || scope === 'supplier_category') {
            $('#supplierContainer').show();
            $('#supplierSelect').prop('required', true);
        }
        if (scope === 'category' || scope === 'supplier_category') {
            $('#categoryContainer').show();
            $('#categorySelect').prop('required', true);
        }
    });

    // Handle delete — AJAX
    $('.delete-btn').on('click', function() {
        $('#delete-modal .modal-title').text($(this).data('title'));
        $('#delete-form').data('url', $(this).data('url'));
    });

    $('#delete-form').on('submit', function(e) {
        e.preventDefault();
        const url = $(this).data('url');
        const $btn = $(this).find('[type=submit]');

        $btn.prop('disabled', true).text('Eliminando...');

        $.ajax({
            url: url,
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function(response) {
                $('#delete-modal').modal('hide');
                toastr.success(response.message || 'Plantilla eliminada', 'Éxito');
                setTimeout(() => location.reload(), 800);
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Error al eliminar', 'Error');
                $btn.prop('disabled', false).text('Confirmar eliminación');
            }
        });
    });

    @if (session('success'))
        toastr.success('{{ session('success') }}', 'Éxito');
    @endif

    @if (session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif
});
</script>
@endpush
