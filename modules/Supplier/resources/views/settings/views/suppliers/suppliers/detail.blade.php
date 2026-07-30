@extends('layouts.theme')

@section('title', 'Detalle del proveedor')

@section('page_header')
    @include('core::components.card', ['title' => 'Proveedores'])
@endsection

@section('content')
    <div class="widget-content searchable-container list">

        <div class="card">

            {{-- Cabecera --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <h5 class="mb-0 fw-bold">{{ $supplier->label }}</h5>
                            @if($supplier->available)
                                <span class="badge bg-success-subtle text-success">Activo</span>
                            @else
                                <span class="badge bg-light text-dark">Inactivo</span>
                            @endif
                        </div>
                        <p class="small mb-0 text-muted">
                            @if($supplier->last_sync_at)
                                Última sync: {{ $supplier->last_sync_at->format('d/m/Y H:i') }}
                            @endif
                        </p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('settings.suppliers.index') }}" class="btn btn-primary">
                            <i class="fas fa-arrow-left me-1"></i>
                        </a>
                    </div>
                </div>
            </div>

            {{-- Stats --}}
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title mb-2">Total productos</h6>
                                        <h4 class="mb-1 fw-bold">{{ $stats['total_products'] }}</h4>
                                        <small class="text-muted">Artículos del proveedor</small>
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
                                        <h6 class="card-title mb-2">Activos</h6>
                                        <h4 class="mb-1 fw-bold">{{ $stats['active_products'] }}</h4>
                                        <small class="text-muted">Disponibles para venta</small>
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
                                        <h6 class="card-title mb-2">En web</h6>
                                        <h4 class="mb-1 fw-bold">{{ $stats['web_published'] }}</h4>
                                        <small class="text-muted">Publicados en tienda online</small>
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
                                        <h6 class="card-title mb-2">Categorías</h6>
                                        <h4 class="mb-1 fw-bold">{{ $stats['categories'] }}</h4>
                                        <small class="text-muted">Familias asignadas</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Tabs --}}
            <ul class="nav nav-pills user-profile-tab" id="detailTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3 active"
                            id="products-tab" data-bs-toggle="pill" data-bs-target="#products-pane"
                            type="button" role="tab" aria-selected="true">
                        <span class="d-none d-md-block">Productos</span>
                        <span class="badge bg-primary ms-2">{{ $stats['total_products'] }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3"
                            id="categories-tab" data-bs-toggle="pill" data-bs-target="#categories-pane"
                            type="button" role="tab" aria-selected="false">
                        <span class="d-none d-md-block">Categorías</span>
                        <span class="badge bg-secondary ms-2">{{ $stats['categories'] }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3"
                            id="prompts-tab" data-bs-toggle="pill" data-bs-target="#prompts-pane"
                            type="button" role="tab" aria-selected="false">
                        <span class="d-none d-md-block">Prompts</span>
                        <span class="badge bg-info ms-2" id="prompts-count-badge">{{ $stats['prompts'] }}</span>
                    </button>
                </li>
            </ul>

            {{-- Tab content --}}
            <div class="tab-content">

                {{-- Tab Productos --}}
                <div class="tab-pane fade show active" id="products-pane" role="tabpanel">

                    {{-- Filtros --}}
                    <div class="card-body border-bottom">
                        <div class="d-flex align-items-center gap-2">
                            <input type="text" id="products-search" class="form-control flex-grow-1"
                                   placeholder="Buscar por código o nombre...">
                            <div style="width: 180px; flex-shrink: 0;">
                                <select id="products-filter-status" class="form-control select2">
                                    <option value="">Todos los estados</option>
                                    <option value="1">Activo</option>
                                    <option value="0">Inactivo</option>
                                </select>
                            </div>
                            <div class="d-flex gap-1 flex-shrink-0">
                                <button type="button" id="products-search-btn" class="btn btn-primary" title="Buscar">
                                    <i class="fas fa-magnifying-glass"></i>
                                </button>
                                <button type="button" id="products-clear-btn" class="btn btn-secondary d-none" title="Limpiar filtros">
                                    <i class="fas fa-xmark"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Listado --}}
                    <div class="card-body">
                        <div class="mb-3 d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1 fw-bold">Listado de productos</h6>
                                <p class="text-muted small mb-0">Artículos del catálogo de este proveedor</p>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="3%"><input type="checkbox" id="products-select-all" class="form-check-input"></th>
                                        <th>Código</th>
                                        <th>Nombre</th>
                                        <th>Categoría</th>
                                        <th class="text-center">Estado</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="products-tbody">
                                    <tr><td colspan="8" class="text-center py-4 text-muted">Cargando...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="card-footer d-flex justify-content-between align-items-center"
                         id="products-pagination-info" style="display:none!important;">
                        <span class="text-muted small" id="products-info"></span>
                        <nav><ul class="pagination pagination-sm mb-0" id="products-pagination"></ul></nav>
                    </div>

                </div>

                {{-- Tab Categorías --}}
                <div class="tab-pane fade" id="categories-pane" role="tabpanel">
                    <div class="card-body border-bottom">
                        <div class="d-flex align-items-center gap-2">
                            <input type="text" id="categories-search" class="form-control flex-grow-1"
                                   placeholder="Buscar por nombre o deporte...">
                            <div style="width: 180px; flex-shrink: 0;">
                                <select id="categories-filter-status" class="form-control">
                                    <option value="">Todos los estados</option>
                                    <option value="1">Activo</option>
                                    <option value="0">Inactivo</option>
                                </select>
                            </div>
                            <div class="d-flex gap-1 flex-shrink-0">
                                <button type="button" id="categories-search-btn" class="btn btn-primary" title="Buscar">
                                    <i class="fas fa-magnifying-glass"></i>
                                </button>
                                <button type="button" id="categories-clear-btn" class="btn btn-secondary d-none" title="Limpiar">
                                    <i class="fas fa-xmark"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="mb-3 d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1 fw-bold">Categorías asignadas</h6>
                                <p class="text-muted small mb-0">Familias del catálogo vinculadas a este proveedor</p>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="3%"><input type="checkbox" id="categories-select-all" class="form-check-input"></th>
                                        <th>Nombre</th>
                                        <th>Deporte</th>
                                        <th class="text-center">Principal</th>
                                        <th class="text-center">Estado</th>
                                        <th class="text-center">Prioridad</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="categories-tbody">
                                    <tr><td colspan="7" class="text-center py-4 text-muted">Cargando...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Tab Prompts --}}
                <div class="tab-pane fade" id="prompts-pane" role="tabpanel">
                    <div class="card-body border-bottom">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h6 class="mb-1 fw-bold">Prompts de IA</h6>
                                <p class="text-muted small mb-0">Plantillas de prompt configuradas para este proveedor</p>
                            </div>
                            <a href="{{ route('settings.suppliers.prompts.create') }}?supplier_id={{ $supplier->id }}" class="btn btn-sm btn-primary">
                                <i class="fas fa-plus"></i>
                            </a>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <input type="text" id="prompts-search" class="form-control flex-grow-1"
                                   placeholder="Buscar por nombre o tono...">
                            <div style="width: 180px; flex-shrink: 0;">
                                <select id="prompts-filter-status" class="form-control">
                                    <option value="">Todos los estados</option>
                                    <option value="1">Activo</option>
                                    <option value="0">Inactivo</option>
                                </select>
                            </div>
                            <div class="d-flex gap-1 flex-shrink-0">
                                <button type="button" id="prompts-search-btn" class="btn btn-primary" title="Buscar">
                                    <i class="fas fa-magnifying-glass"></i>
                                </button>
                                <button type="button" id="prompts-clear-btn" class="btn btn-secondary d-none" title="Limpiar">
                                    <i class="fas fa-xmark"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="3%"><input type="checkbox" id="prompts-select-all" class="form-check-input"></th>
                                        <th>Nombre</th>
                                        <th>Alcance</th>
                                        <th>Tono</th>
                                        <th class="text-center">Prioridad</th>
                                        <th class="text-center">Estado</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="prompts-tbody">
                                    <tr><td colspan="8" class="text-center py-4 text-muted">Cargando...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>{{-- tab-content --}}

        </div>{{-- card --}}

    </div>{{-- widget-content --}}

    {{-- Bulk toolbar flotante (productos) --}}
    <div id="products-bulk-toolbar" class="position-fixed bottom-0 start-50 translate-middle-x mb-4 d-none" style="z-index:1050;">
        <button type="button" class="btn btn-primary shadow-lg px-4"
                data-bs-toggle="modal" data-bs-target="#products-bulk-modal">
            <span data-bulk-count>0</span> seleccionado(s) &mdash; Aplicar acción
        </button>
    </div>

    {{-- Bulk modal (productos) --}}
    <div class="modal fade" id="products-bulk-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Acción masiva — Productos</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">Se aplicará la acción sobre <strong><span data-bulk-count>0</span> producto(s)</strong>.</p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Acción</label>
                        <select id="products-bulk-action-select" class="form-select">
                            <option value="">Seleccionar acción...</option>
                            <option value="enable">Activar</option>
                            <option value="disable">Desactivar</option>
                            <option value="web_on">Publicar en web</option>
                            <option value="web_off">Despublicar de web</option>
                            <option value="delete">Eliminar</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button id="products-bulk-apply-btn" type="button" class="btn btn-primary w-100 mb-1">Aplicar</button>
                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Bulk toolbar flotante (categorías) --}}
    <div id="categories-bulk-toolbar" class="position-fixed bottom-0 start-50 translate-middle-x mb-4 d-none" style="z-index:1050;">
        <button type="button" class="btn btn-secondary shadow-lg px-4"
                data-bs-toggle="modal" data-bs-target="#categories-bulk-modal">
            <span data-bulk-count>0</span> seleccionado(s) &mdash; Aplicar acción
        </button>
    </div>

    {{-- Bulk modal (categorías) --}}
    <div class="modal fade" id="categories-bulk-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Acción masiva — Categorías</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">Se aplicará la acción sobre <strong><span data-bulk-count>0</span> categoría(s)</strong>.</p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Acción</label>
                        <select id="categories-bulk-action-select" class="form-select">
                            <option value="">Seleccionar acción...</option>
                            <option value="enable">Activar</option>
                            <option value="disable">Desactivar</option>
                            <option value="delete">Eliminar</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button id="categories-bulk-apply-btn" type="button" class="btn btn-primary w-100 mb-1">Aplicar</button>
                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Bulk toolbar flotante (prompts) --}}
    <div id="prompts-bulk-toolbar" class="position-fixed bottom-0 start-50 translate-middle-x mb-4 d-none" style="z-index:1050;">
        <button type="button" class="btn btn-info shadow-lg px-4"
                data-bs-toggle="modal" data-bs-target="#prompts-bulk-modal">
            <span data-bulk-count>0</span> seleccionado(s) &mdash; Aplicar acción
        </button>
    </div>

    {{-- Bulk modal (prompts) --}}
    <div class="modal fade" id="prompts-bulk-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Acción masiva — Prompts</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">Se aplicará la acción sobre <strong><span data-bulk-count>0</span> prompt(s)</strong>.</p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Acción</label>
                        <select id="prompts-bulk-action-select" class="form-select">
                            <option value="">Seleccionar acción...</option>
                            <option value="enable">Activar</option>
                            <option value="disable">Desactivar</option>
                            <option value="delete">Eliminar</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button id="prompts-bulk-apply-btn" type="button" class="btn btn-primary w-100 mb-1">Aplicar</button>
                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script src="{{ asset('core/js/bulk.js?v=2') }}"></script>
<script>
$(document).ready(function () {

    // Init select2 on all filter selects
    const s2opts = { minimumResultsForSearch: Infinity, width: '100%' };
    $('#products-filter-status').select2(s2opts);
    $('#categories-filter-status').select2(s2opts);
    $('#prompts-filter-status').select2(s2opts);

    const productsUrl   = '{{ route("settings.suppliers.products-data", $supplier->uid) }}';
    const categoriesUrl = '{{ route("settings.suppliers.categories-data", $supplier->uid) }}';
    const promptsUrl    = '{{ route("settings.suppliers.prompts-data", $supplier->uid) }}';

    const bulkProductsUrl    = '{{ route("settings.suppliers.products.bulk-action") }}';
    const bulkCategoriesUrl  = '{{ route("settings.suppliers.categories.bulk-action") }}';
    const bulkPromptsUrl     = '{{ route("settings.suppliers.prompts.bulk-action") }}';

    const chatBase        = '{{ route("settings.suppliers.products.chat", ["uid" => "__UID__"]) }}';
    const createPromptBase = '{{ route("settings.suppliers.prompts.create") }}';
    const listPromptsBase  = '{{ route("settings.suppliers.prompts.index") }}';

    // ── Productos ──────────────────────────────────────────────────────
    let productsPage  = 1;
    const perPage     = 25;
    let productsTotal = 0;

    function loadProducts() {
        const start = (productsPage - 1) * perPage;

        $.ajax({
            url: productsUrl,
            data: {
                draw: 1,
                start: start,
                length: perPage,
                'search[value]': $('#products-search').val(),
                available: $('#products-filter-status').val(),
            },
            success: function (res) {
                productsTotal    = res.recordsFiltered;
                const $tbody     = $('#products-tbody').empty();

                if (!res.data.length) {
                    $tbody.append('<tr><td colspan="8" class="text-center py-4 text-muted">Sin resultados</td></tr>');
                    $('#products-pagination-info').hide();
                    productsBulk.reset();
                    return;
                }

                res.data.forEach(function (p) {
                    const chatUrl   = chatBase.replace('__UID__', p.uid);
                    const promptUrl = createPromptBase + '?supplier_id={{ $supplier->id }}' + (p.category_id ? '&category_id=' + p.category_id : '');
                    const chatItem  = p.has_approved_content ? '' : `<li><a class="dropdown-item" href="${chatUrl}">Chat IA</a></li>`;
                    const actionsDropdown = `
                        <div class="dropdown">
                            <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-ellipsis-vertical"></i>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                ${chatItem}
                                <li><a class="dropdown-item" href="${promptUrl}">Crear prompt</a></li>
                            </ul>
                        </div>`;

                    $tbody.append(`
                        <tr>
                            <td><input type="checkbox" class="form-check-input products-bulk-checkbox" value="${p.id}"></td>
                            <td><code class="small">${p.code}</code></td>
                            <td>${p.name}</td>
                            <td class="small">${p.category}</td>
                            <td class="text-center">${p.available}</td>
                            <td class="text-center">${actionsDropdown}</td>
                        </tr>`);
                });

                productsBulk.sync();
                renderPagination(res.recordsFiltered);
                $('#products-pagination-info').show();
                $('#products-info').text(
                    `Mostrando ${start + 1}–${Math.min(start + perPage, productsTotal)} de ${productsTotal}`
                );
            },
            error: function () {
                $('#products-tbody').html('<tr><td colspan="8" class="text-center text-danger py-4">Error al cargar productos</td></tr>');
            }
        });
    }

    function renderPagination(total) {
        const pages = Math.ceil(total / perPage);
        const $pag  = $('#products-pagination').empty();

        if (pages <= 1) { return; }

        $pag.append(`<li class="page-item${productsPage === 1 ? ' disabled' : ''}"><a class="page-link" href="#" data-page="${productsPage - 1}">&laquo;</a></li>`);

        const range = 5;
        let s = Math.max(1, productsPage - Math.floor(range / 2));
        let e = Math.min(pages, s + range - 1);
        if (e - s < range - 1) { s = Math.max(1, e - range + 1); }

        for (let i = s; i <= e; i++) {
            $pag.append(`<li class="page-item${i === productsPage ? ' active' : ''}"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`);
        }

        $pag.append(`<li class="page-item${productsPage === pages ? ' disabled' : ''}"><a class="page-link" href="#" data-page="${productsPage + 1}">&raquo;</a></li>`);
    }

    $(document).on('click', '#products-pagination .page-link', function (e) {
        e.preventDefault();
        const page = parseInt($(this).data('page'));
        if (!page || page === productsPage) { return; }
        productsPage = page;
        loadProducts();
    });

    function updateProductsClearBtn() {
        const hasFilter = $('#products-search').val() || $('#products-filter-status').val();
        $('#products-clear-btn').toggleClass('d-none', !hasFilter);
    }

    function triggerSearch() {
        productsPage = 1;
        loadProducts();
        updateProductsClearBtn();
    }

    $('#products-search-btn').on('click', triggerSearch);
    $('#products-search').on('input', updateProductsClearBtn);
    $('#products-search').on('keypress', function (e) { if (e.key === 'Enter') { triggerSearch(); } });
    $('#products-filter-status').on('change', triggerSearch);

    $('#products-clear-btn').on('click', function () {
        $('#products-search').val('');
        $('#products-filter-status').val(null).trigger('change');
        $(this).addClass('d-none');
        productsPage = 1;
        loadProducts();
    });

    // Bulk — Productos
    const productsBulk = window.BulkActions.init({
        checkbox  : '.products-bulk-checkbox',
        selectAll : '#products-select-all',
        toolbar   : '#products-bulk-toolbar',
    });

    $('#products-bulk-modal').on('hide.bs.modal', function () {
        $('#products-bulk-action-select').val('');
        $('#products-bulk-apply-btn').prop('disabled', false).text('Aplicar');
        productsBulk.reset();
    });

    $('#products-bulk-apply-btn').on('click', function () {
        const action = $('#products-bulk-action-select').val();
        const ids    = productsBulk.getIds();

        if (!action) { toastr.warning('Selecciona una acción.'); return; }
        if (!ids.length) { toastr.warning('Selecciona al menos un producto.'); return; }
        if (action === 'delete' && !confirm('¿Eliminar los ' + ids.length + ' producto(s) seleccionados?')) { return; }

        $('#products-bulk-apply-btn').prop('disabled', true).text('Procesando...');

        $.ajax({
            url: bulkProductsUrl,
            method: 'POST',
            data: JSON.stringify({ action, ids, _token: $('meta[name="csrf-token"]').attr('content') }),
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                $('#products-bulk-modal').modal('hide');
                toastr.success(res.message);
                loadProducts();
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message ?? 'Error al procesar.');
                $('#products-bulk-apply-btn').prop('disabled', false).text('Aplicar');
            },
        });
    });

    loadProducts();

    // ── Categorías ─────────────────────────────────────────────────────
    let categoriesLoaded = false;
    let categoriesData   = [];

    function renderCategories(data) {
        const $tbody = $('#categories-tbody').empty();
        if (!data.length) {
            $tbody.append('<tr><td colspan="7" class="text-center py-4 text-muted">Sin categorías asignadas</td></tr>');
            return;
        }
        data.forEach(function (c) {
            const promptUrl   = `${createPromptBase}?supplier_id={{ $supplier->id }}&category_id=${c.category_id}`;
            const listUrl     = `${listPromptsBase}?supplier_id={{ $supplier->id }}&category_id=${c.category_id}`;
            const promptsLink = c.prompts_count > 0
                ? `<li><a class="dropdown-item" href="${listUrl}">Ver prompts <span class="badge bg-secondary ms-1">${c.prompts_count}</span></a></li><li><hr class="dropdown-divider"></li>`
                : '';
            $tbody.append(`
                <tr>
                    <td><input type="checkbox" class="form-check-input categories-bulk-checkbox" value="${c.category_id}"></td>
                    <td>${c.name}</td>
                    <td class="small">${c.sport}</td>
                    <td class="text-center">${c.is_primary}</td>
                    <td class="text-center">${c.is_active}</td>
                    <td class="text-center"><span class="badge bg-light text-dark">${c.priority}</span></td>
                    <td class="text-center">
                        <div class="dropdown">
                            <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-ellipsis-vertical"></i>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                ${promptsLink}
                                <li><a class="dropdown-item" href="${promptUrl}">Crear prompt</a></li>
                            </ul>
                        </div>
                    </td>
                </tr>`);
        });
        categoriesBulk.sync();
    }

    function filterCategories() {
        const q      = $('#categories-search').val().toLowerCase().trim();
        const status = $('#categories-filter-status').val();
        const filtered = categoriesData.filter(function (c) {
            const matchQ = !q || c.name.toLowerCase().includes(q) || (c.sport || '').toLowerCase().includes(q);
            const matchS = status === '' || (status === '1' ? c.is_active.includes('Activo') : c.is_active.includes('Inactivo'));
            return matchQ && matchS;
        });
        renderCategories(filtered);
        $('#categories-clear-btn').toggleClass('d-none', !q && !status);
    }

    function loadCategories() {
        if (categoriesLoaded) { return; }
        categoriesLoaded = true;

        $.ajax({
            url: categoriesUrl,
            success: function (res) {
                categoriesData = res.data;
                filterCategories();
            },
            error: function () {
                $('#categories-tbody').html('<tr><td colspan="7" class="text-center text-danger py-4">Error al cargar categorías</td></tr>');
            }
        });
    }

    $('#categories-search').on('input', filterCategories);
    $('#categories-filter-status').on('change', filterCategories);
    $('#categories-search-btn').on('click', filterCategories);
    $('#categories-clear-btn').on('click', function () {
        $('#categories-search').val('');
        $('#categories-filter-status').val(null).trigger('change');
        filterCategories();
    });

    $('#categories-tab').on('shown.bs.tab', loadCategories);

    // Bulk — Categorías
    const categoriesBulk = window.BulkActions.init({
        checkbox  : '.categories-bulk-checkbox',
        selectAll : '#categories-select-all',
        toolbar   : '#categories-bulk-toolbar',
    });

    $('#categories-bulk-modal').on('hide.bs.modal', function () {
        $('#categories-bulk-action-select').val('');
        $('#categories-bulk-apply-btn').prop('disabled', false).text('Aplicar');
        categoriesBulk.reset();
    });

    $('#categories-bulk-apply-btn').on('click', function () {
        const action = $('#categories-bulk-action-select').val();
        const ids    = categoriesBulk.getIds();

        if (!action) { toastr.warning('Selecciona una acción.'); return; }
        if (!ids.length) { toastr.warning('Selecciona al menos una categoría.'); return; }
        if (action === 'delete' && !confirm('¿Eliminar las ' + ids.length + ' categoría(s) seleccionadas?')) { return; }

        $('#categories-bulk-apply-btn').prop('disabled', true).text('Procesando...');

        $.ajax({
            url: bulkCategoriesUrl,
            method: 'POST',
            data: JSON.stringify({ action, ids, _token: $('meta[name="csrf-token"]').attr('content') }),
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                $('#categories-bulk-modal').modal('hide');
                toastr.success(res.message);
                categoriesLoaded = false;
                loadCategories();
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message ?? 'Error al procesar.');
                $('#categories-bulk-apply-btn').prop('disabled', false).text('Aplicar');
            },
        });
    });

    // ── Prompts ────────────────────────────────────────────────────────
    let promptsLoaded = false;
    let promptsData   = [];
    const editBase    = '{{ route("settings.suppliers.prompts.edit", "__UID__") }}';

    function renderPrompts(data) {
        const $tbody = $('#prompts-tbody').empty();

        if (!data.length) {
            $tbody.append('<tr><td colspan="8" class="text-center py-4 text-muted">Sin prompts configurados</td></tr>');
            return;
        }

        data.forEach(function (p) {
            const editUrl     = editBase.replace('__UID__', p.uid);
            const seoBadge    = p.seo_focus ? '<i class="fas fa-magnifying-glass text-info" title="SEO"></i>' : '<span class="text-muted">—</span>';
            const statusBadge = p.is_active ? '<span class="badge bg-success-subtle text-success">Activo</span>' : '<span class="badge bg-light text-dark">Inactivo</span>';
            const defaultMark = p.is_default ? ' <i class="fas fa-star text-warning" title="Por defecto"></i>' : '';

            $tbody.append(`
                <tr>
                    <td><input type="checkbox" class="form-check-input prompts-bulk-checkbox" value="${p.uid}"></td>
                    <td><strong>${p.label}</strong>${defaultMark}</td>
                    <td>${p.scope}</td>
                    <td class="small">${p.tone}</td>
                    <td class="text-center"><span class="badge bg-light text-dark">${p.priority}</span></td>
                    <td class="text-center">${statusBadge}</td>
                    <td class="text-center">
                        <div class="dropdown">
                            <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-ellipsis-vertical"></i>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="${editUrl}">Editar</a></li>
                            </ul>
                        </div>
                    </td>
                </tr>`);
        });

        promptsBulk.sync();
    }

    function filterPrompts() {
        const q      = $('#prompts-search').val().toLowerCase().trim();
        const status = $('#prompts-filter-status').val();
        const filtered = promptsData.filter(function (p) {
            const matchQ      = !q || p.label.toLowerCase().includes(q) || (p.tone || '').toLowerCase().includes(q);
            const matchStatus = status === '' || String(p.is_active ? 1 : 0) === status;
            return matchQ && matchStatus;
        });
        renderPrompts(filtered);
        $('#prompts-clear-btn').toggleClass('d-none', !q && !status);
    }

    function loadPrompts() {
        if (promptsLoaded) { return; }
        promptsLoaded = true;

        $.ajax({
            url: promptsUrl,
            success: function (res) {
                promptsData = res.data;
                $('#prompts-count-badge').text(res.data.length);
                filterPrompts();
            },
            error: function () {
                $('#prompts-tbody').html('<tr><td colspan="8" class="text-center text-danger py-4">Error al cargar prompts</td></tr>');
            }
        });
    }

    $('#prompts-search').on('input', filterPrompts);
    $('#prompts-filter-status').on('change', filterPrompts);
    $('#prompts-search-btn').on('click', filterPrompts);
    $('#prompts-clear-btn').on('click', function () {
        $('#prompts-search').val('');
        $('#prompts-filter-status').val(null).trigger('change');
        filterPrompts();
    });

    $('#prompts-tab').on('shown.bs.tab', loadPrompts);

    // Bulk — Prompts
    const promptsBulk = window.BulkActions.init({
        checkbox  : '.prompts-bulk-checkbox',
        selectAll : '#prompts-select-all',
        toolbar   : '#prompts-bulk-toolbar',
    });

    $('#prompts-bulk-modal').on('hide.bs.modal', function () {
        $('#prompts-bulk-action-select').val('');
        $('#prompts-bulk-apply-btn').prop('disabled', false).text('Aplicar');
        promptsBulk.reset();
    });

    $('#prompts-bulk-apply-btn').on('click', function () {
        const action = $('#prompts-bulk-action-select').val();
        const uids   = $('.prompts-bulk-checkbox:checked').map(function () { return this.value; }).get();

        if (!action) { toastr.warning('Selecciona una acción.'); return; }
        if (!uids.length) { toastr.warning('Selecciona al menos un prompt.'); return; }
        if (action === 'delete' && !confirm('¿Eliminar los ' + uids.length + ' prompt(s) seleccionados?')) { return; }

        $('#prompts-bulk-apply-btn').prop('disabled', true).text('Procesando...');

        $.ajax({
            url: bulkPromptsUrl,
            method: 'POST',
            data: JSON.stringify({ action, uids, _token: $('meta[name="csrf-token"]').attr('content') }),
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                $('#prompts-bulk-modal').modal('hide');
                toastr.success(res.message);
                promptsLoaded = false;
                loadPrompts();
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message ?? 'Error al procesar.');
                $('#prompts-bulk-apply-btn').prop('disabled', false).text('Aplicar');
            },
        });
    });

});
</script>
@endpush
