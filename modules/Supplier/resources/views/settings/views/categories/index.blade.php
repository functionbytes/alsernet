@extends('layouts.theme')

@section('title', 'Categorías')

@section('page_header')
    @include('core::components.card', ['title' => 'Categorías'])
@endsection

@section('content')
    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">

            {{-- Cabecera --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Categorías</h5>
                        <p class="small mb-0 text-muted">Jerarquía sincronizada: Deporte → Categoría → Familia → Subfamilia</p>
                    </div>
                    <div class="dropdown">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-ellipsis-vertical"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a href="{{ route('settings.suppliers.categories.excel.index') }}" class="dropdown-item">
                                    Importar excel
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a href="{{ route('settings.suppliers.categories.excel.config') }}" class="dropdown-item">
                                    Configurar excel
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            {{-- Stats --}}
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Deportes</h6>
                                <h4 class="mb-1 fw-bold">{{ $stats['sports'] }}</h4>
                                <small class="text-muted">Deportes registrados</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Categorías</h6>
                                <h4 class="mb-1 fw-bold">{{ $stats['categorias'] }}</h4>
                                <small class="text-muted">Categorías registradas</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Familias</h6>
                                <h4 class="mb-1 fw-bold">{{ $stats['familias'] }}</h4>
                                <small class="text-muted">Familias registradas</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Subfamilias</h6>
                                <h4 class="mb-1 fw-bold">{{ $stats['subfamilias'] }}</h4>
                                <small class="text-muted">{{ $stats['last_sync'] ? \Carbon\Carbon::parse($stats['last_sync'])->format('d/m/Y H:i') : '—' }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Tabs --}}
            <ul class="nav nav-pills user-profile-tab" role="tablist">
                <li class="nav-item" role="presentation">
                    <a class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3 {{ $type === 'familias' ? 'active' : '' }}"
                       href="{{ route('settings.suppliers.categories.index') }}">
                        <span class="d-none d-md-block">Familias</span>
                        <span class="badge bg-primary ms-2">{{ $stats['familias'] }}</span>
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3 {{ $type === 'categorias' ? 'active' : '' }}"
                       href="{{ route('settings.suppliers.categories.index') }}?type=categorias">
                        <span class="d-none d-md-block">Categorías</span>
                        <span class="badge bg-secondary ms-2">{{ $stats['categorias'] }}</span>
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3 {{ $type === 'subfamilias' ? 'active' : '' }}"
                       href="{{ route('settings.suppliers.categories.index') }}?type=subfamilias">
                        <span class="d-none d-md-block">Subfamilias</span>
                        <span class="badge bg-secondary ms-2">{{ $stats['subfamilias'] }}</span>
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3 {{ $type === 'sports' ? 'active' : '' }}"
                       href="{{ route('settings.suppliers.categories.index') }}?type=sports">
                        <span class="d-none d-md-block">Deportes</span>
                        <span class="badge bg-secondary ms-2">{{ $stats['sports'] }}</span>
                    </a>
                </li>
            </ul>

            {{-- Filtros en cascada --}}
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('settings.suppliers.categories.index') }}" id="filter-form">
                    <input type="hidden" name="type" value="{{ $type }}">
                    <div class="d-flex align-items-center gap-2 flex-wrap">

                        {{-- Búsqueda primero --}}
                        <div class="flex-grow-1">
                            <input type="search" name="search" class="form-control"
                                   placeholder="Buscar por nombre..."
                                   value="{{ $searchKey }}">
                        </div>

                        @if($type !== 'sports')
                        <div style="min-width:160px;">
                            <select name="sport_id" id="filter-sport" class="form-select select2">
                                <option value="">Todos los deportes</option>
                                @foreach($sports as $sport)
                                    <option value="{{ $sport->id }}" {{ $filterSport == $sport->id ? 'selected' : '' }}>
                                        {{ $sport->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        @endif

                        @if(in_array($type, ['familias', 'subfamilias']) && $categorias->isNotEmpty())
                        <div style="min-width:200px;">
                            <select name="erp_categoria_id" id="filter-cat" class="form-select select2">
                                <option value="">Todas las categorías</option>
                                @foreach($categorias as $cat)
                                    <option value="{{ $cat->erp_categoria_id }}" {{ $filterCat == $cat->erp_categoria_id ? 'selected' : '' }}>
                                        {{ $cat->erp_categoria_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        @endif

                        @if($type === 'subfamilias' && $familias->isNotEmpty())
                        <div style="min-width:200px;">
                            <select name="familia_id" id="filter-fam" class="form-select select2">
                                <option value="">Todas las familias</option>
                                @foreach($familias as $fam)
                                    <option value="{{ $fam->id }}" {{ $filterFam == $fam->id ? 'selected' : '' }}>
                                        {{ $fam->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        @endif

                        <button type="submit" class="btn btn-primary" title="Buscar">
                            <i class="fas fa-magnifying-glass"></i>
                        </button>

                        @if($searchKey || $filterSport || $filterCat || $filterFam)
                        <a href="{{ route('settings.suppliers.categories.index') }}?type={{ $type }}"
                           class="btn btn-secondary" title="Limpiar filtros">
                            <i class="fas fa-xmark"></i>
                        </a>
                        @endif
                    </div>
                </form>
            </div>

            {{-- Listado --}}
            <div class="card-body">
                <div class="mb-3 d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1 fw-bold">{{ $title }}</h6>
                        <p class="text-muted small mb-0">{{ $items->total() }} {{ strtolower($title) }} encontradas</p>
                    </div>
                </div>

                @if($items->count())
                    <div class="table-responsive">
                        <table class="table search-table align-middle text-nowrap">
                            <thead class="header-item">
                            <tr>
                                <th width="3%"><input type="checkbox" id="select-all" class="form-check-input"></th>
                                <th>Nombre</th>
                                @if($type === 'familias')
                                    <th>Categoría</th>
                                    <th>Deporte</th>
                                    <th class="text-center">Productos</th>
                                @elseif($type === 'categorias')
                                    <th>Deporte</th>
                                    <th class="text-center">Familias</th>
                                @elseif($type === 'subfamilias')
                                    <th>Familia</th>
                                    <th>Prompt asignado</th>
                                @endif
                                <th>Erp</th>
                                <th class="text-center">Estado</th>
                                <th class="text-center">Última sync</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($items as $item)
                                <tr class="search-items">
                                    <td><input type="checkbox" class="form-check-input bulk-checkbox" value="{{ $item->id }}"></td>
                                    <td>
                                        <strong>{{ $item->name }}</strong>
                                        @if(!empty($item->short_name) && $item->short_name !== $item->name)
                                            <small class="text-muted d-block">{{ $item->short_name }}</small>
                                        @endif
                                    </td>
                                    @if($type === 'familias')
                                        <td>
                                            @if($item->erp_categoria_name)
                                                <a href="{{ route('settings.suppliers.categories.index') }}?type=familias&erp_categoria_id={{ $item->erp_categoria_id }}"
                                                   class="badge bg-light-info text-info text-decoration-none">
                                                    {{ $item->erp_categoria_name }}
                                                </a>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($item->sport)
                                                <a href="{{ route('settings.suppliers.categories.index') }}?type=familias&sport_id={{ $item->sport_id }}"
                                                   class="badge bg-light-primary text-primary text-decoration-none">
                                                    {{ $item->sport->name }}
                                                </a>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($item->products_count > 0)
                                                <a href="{{ route('settings.suppliers.products.index') }}?category_id={{ $item->id }}">
                                                    {{ $item->products_count }}
                                                </a>
                                            @else
                                                <span class="text-muted small">0</span>
                                            @endif
                                        </td>
                                    @elseif($type === 'categorias')
                                        <td>
                                            @php $sport = $sports->firstWhere('id', $item->sport_id); @endphp
                                            @if($sport)
                                                <a href="{{ route('settings.suppliers.categories.index') }}?type=categorias&sport_id={{ $item->sport_id }}"
                                                   class="badge bg-light-primary text-primary text-decoration-none">
                                                    {{ $sport->name }}
                                                </a>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('settings.suppliers.categories.index') }}?type=familias&erp_categoria_id={{ $item->id }}"
                                               class="small">
                                                {{ $item->familias_count }}
                                            </a>
                                        </td>
                                    @elseif($type === 'subfamilias')
                                        <td>
                                            @if($item->category)
                                                <a href="{{ route('settings.suppliers.categories.index') }}?type=subfamilias&familia_id={{ $item->category_id }}"
                                                   class="badge bg-light-secondary text-dark text-decoration-none">
                                                    {{ $item->category->name }}
                                                </a>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            @php
                                                $itemPrompts = $subfamilyPrompts
                                                    ->where('sf_id', $item->id)
                                                    ->pluck('prompt')
                                                    ->unique('id')
                                                    ->values();
                                            @endphp
                                            @if($itemPrompts->isNotEmpty())
                                                @foreach($itemPrompts->take(2) as $pr)
                                                    <a href="{{ route('settings.suppliers.prompts.edit', $pr->uid) }}"
                                                       class="d-inline-block mb-1"
                                                       title="{{ $pr->label }}">
                                                        {{ Str::limit($pr->label, 28) }}
                                                    </a>
                                                @endforeach
                                                @if($itemPrompts->count() > 2)
                                                    <small class="text-muted d-block">+{{ $itemPrompts->count() - 2 }} más</small>
                                                @endif
                                            @else
                                                <span class="text-muted small">—</span>
                                            @endif
                                        </td>
                                    @endif
                                    <td><span class="text-muted small">{{ $item->erp_id ?? '—' }}</span></td>
                                    <td class="text-center">
                                        @if($item->available)
                                            <span class="badge bg-light-primary text-primary">Activa</span>
                                        @else
                                            <span class="badge bg-light text-dark">Inactiva</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="text-muted small">
                                            {{ $item->last_sync_at?->format('d/m/Y H:i') ?? '—' }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="dropdown dropstart">
                                            <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="fa-duotone fa-solid fa-ellipsis"></i>
                                            </a>
                                            <ul class="dropdown-menu">
                                                @if($type === 'familias')
                                                    <li>
                                                        <a class="dropdown-item d-flex align-items-center gap-3"
                                                           href="{{ route('settings.suppliers.categories.index') }}?type=subfamilias&familia_id={{ $item->id }}">
                                                            Ver subfamilias
                                                        </a>
                                                    </li>
                                                    @if($item->products_count > 0)
                                                    <li>
                                                        <a class="dropdown-item d-flex align-items-center gap-3"
                                                           href="{{ route('settings.suppliers.products.index') }}?category_id={{ $item->id }}">
                                                            Ver productos ({{ $item->products_count }})
                                                        </a>
                                                    </li>
                                                    @endif
                                                    <li>
                                                        <a class="dropdown-item d-flex align-items-center gap-3"
                                                           href="{{ route('settings.suppliers.prompts.create') }}?category_id={{ $item->id }}">
                                                            Crear prompt
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item d-flex align-items-center gap-3"
                                                           href="{{ route('settings.suppliers.categories.edit', $item->id) }}">
                                                            Editar
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                @endif
                                                <li>
                                                    <a class="dropdown-item delete-item-btn d-flex align-items-center gap-3 text-danger"
                                                       href="#"
                                                       data-id="{{ $item->id }}"
                                                       data-name="{{ $item->name }}"
                                                       data-type="{{ $type }}">
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
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-folder-open fa-2x mb-3 d-block"></i>
                        <h6 class="mb-1">No hay {{ strtolower($title) }} disponibles</h6>
                        @if($searchKey || $filterSport || $filterCat || $filterFam)
                            <p class="small mb-2">No se encontraron resultados con los filtros aplicados.</p>
                            <a href="{{ route('settings.suppliers.categories.index') }}?type={{ $type }}" class="btn btn-sm btn-outline-secondary">
                                Limpiar filtros
                            </a>
                        @endif
                    </div>
                @endif
            </div>

            <div class="card-footer bg-white border-top py-2">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2">
                        @if($items->total() > 0)
                        <span class="text-muted small">
                            Mostrando {{ $items->firstItem() }}–{{ $items->lastItem() }} de {{ $items->total() }}
                        </span>
                        @endif
                        <form method="GET" action="{{ route('settings.suppliers.categories.index') }}" class="d-inline-flex align-items-center gap-1 mb-0">
                            @foreach(request()->except(['per_page', 'page']) as $key => $value)
                                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                            @endforeach
                            <label class="text-muted small mb-0">Por página:</label>
                            <select name="per_page" class="form-select form-select-sm d-inline-block w-auto" onchange="this.form.submit()">
                                @foreach([10, 20, 50, 100, 200] as $opt)
                                    <option value="{{ $opt }}" {{ request('per_page', 50) == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                @endforeach
                            </select>
                        </form>
                    </div>
                    @if($items->hasPages())
                    <nav>{{ $items->appends(request()->query())->links('pagination::bootstrap-5') }}</nav>
                    @endif
                </div>
            </div>

        </div>
    </div>

    {{-- Bulk toolbar --}}
    <div id="bulk-toolbar" class="position-fixed bottom-0 start-50 translate-middle-x mb-4 d-none" style="z-index:1050;">
        <button type="button" class="btn btn-primary shadow-lg px-4" data-bs-toggle="modal" data-bs-target="#bulk-modal">
            <span data-bulk-count>0</span> seleccionado(s) — Aplicar acción
        </button>
    </div>

    {{-- Modal: Bulk --}}
    <div class="modal fade" id="bulk-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Acción masiva</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">Acción sobre <strong><span data-bulk-count>0</span> elemento(s)</strong>.</p>
                    <select id="bulk-action-select" class="form-select">
                        <option value="">Seleccionar acción...</option>
                        <option value="enable">Activar</option>
                        <option value="disable">Desactivar</option>
                        <option value="delete">Eliminar</option>
                    </select>
                </div>
                <div class="modal-footer">
                    <button id="bulk-apply-btn" type="button" class="btn btn-primary w-100 mb-1">Aplicar</button>
                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal: Importar Excel --}}
    <div class="modal fade" id="import-excel-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title fw-bold">Importar desde Excel</h5>
                        <small class="text-muted">
                            Nivel configurado: <strong>{{ ucfirst(\Modules\Core\Models\Setting::get('supplier.excel_import.import_level', 'grupo')) }}</strong>
                            — <a href="{{ route('settings.suppliers.categories.excel.config') }}">Cambiar</a>
                        </small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-light border mb-3 p-2">
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            Sube el Excel del ERP (formato Golf.XLS). Los deportes y familias se actualizan automáticamente.
                        </small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Archivo Excel <span class="text-danger">*</span></label>
                        <input type="file" id="import-excel-file" class="form-control" accept=".xlsx,.xls,.csv">
                        <div class="form-text">Formatos: .xlsx, .xls, .csv — máximo 10 MB</div>
                    </div>
                    <div id="import-result" class="d-none"></div>
                </div>
                <div class="modal-footer">
                    <button id="import-excel-btn" type="button" class="btn btn-primary w-100 mb-1">
                        <i class="fas fa-upload me-1"></i> Importar
                    </button>
                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal: Confirmar eliminar --}}
    <div class="modal fade" id="delete-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmar eliminación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">¿Eliminar <strong id="delete-category-name"></strong>?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <form id="delete-form" method="POST">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-danger">Eliminar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script src="{{ asset('core/js/bulk.js?v=2') }}"></script>
    <script>
    $(function () {
        const currentType = '{{ $type }}';
        const bulk = window.BulkActions.init({ checkbox: '.bulk-checkbox' });

        $('#bulk-action-select').select2({ dropdownParent: $('#bulk-modal'), width: '100%' });
        $('#bulk-modal').on('hide.bs.modal', function () {
            $('#bulk-action-select').val('').trigger('change');
            $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
            bulk.reset();
        });

        $('#bulk-apply-btn').on('click', function () {
            const action = $('#bulk-action-select').val();
            const ids = bulk.getIds();
            if (!action) { toastr.warning('Selecciona una acción.'); return; }
            if (!ids.length) { toastr.warning('Selecciona al menos un elemento.'); return; }
            if (action === 'delete' && !confirm('¿Eliminar los ' + ids.length + ' elemento(s)?')) return;

            $('#bulk-apply-btn').prop('disabled', true).text('Procesando...');
            $.ajax({
                url: '{{ route("settings.suppliers.categories.bulk-action") }}',
                method: 'POST',
                data: JSON.stringify({ action, ids: ids.map(Number), type: currentType, _token: $('meta[name="csrf-token"]').attr('content') }),
                contentType: 'application/json',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: res => { $('#bulk-modal').modal('hide'); toastr.success(res.message); setTimeout(() => location.reload(), 800); },
                error: xhr => { toastr.error(xhr.responseJSON?.message ?? 'Error.'); $('#bulk-apply-btn').prop('disabled', false).text('Aplicar'); }
            });
        });

        // Delete individual
        const destroyBase = '{{ route("settings.suppliers.categories.destroy", ["id" => "__ID__"]) }}';
        $(document).on('click', '.delete-item-btn', function (e) {
            e.preventDefault();
            $('#delete-category-name').text($(this).data('name'));
            const url = destroyBase.replace('__ID__', $(this).data('id')) + ($(this).data('type') === 'sports' ? '?type=sports' : '');
            $('#delete-form').attr('action', url);
            $('#delete-modal').modal('show');
        });

        // Import Excel
        $('#import-excel-modal').on('hidden.bs.modal', function () {
            $('#import-excel-file').val('');
            $('#import-result').addClass('d-none').html('');
            $('#import-excel-btn').prop('disabled', false).html('<i class="fas fa-upload me-1"></i> Importar');
        });

        $('#import-excel-btn').on('click', function () {
            const file = $('#import-excel-file')[0].files[0];
            if (!file) { toastr.warning('Selecciona un archivo primero.'); return; }
            const btn = $(this);
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Procesando...');
            const fd = new FormData();
            fd.append('file', file);
            fd.append('_token', $('meta[name="csrf-token"]').attr('content'));
            $.ajax({
                url: '{{ route("settings.suppliers.categories.excel.import") }}',
                method: 'POST', data: fd, processData: false, contentType: false,
                success: function (res) {
                    let html = '<div class="alert alert-success mb-0"><strong>' + res.message + '</strong>';
                    if (res.errors?.length) { html += '<ul class="mb-0 mt-2 small">'; res.errors.forEach(e => { html += '<li>' + e + '</li>'; }); html += '</ul>'; }
                    html += '</div>';
                    $('#import-result').removeClass('d-none').html(html);
                    btn.prop('disabled', false).html('<i class="fas fa-upload me-1"></i> Importar');
                    setTimeout(() => location.reload(), 1800);
                },
                error: function (xhr) {
                    const msg = xhr.responseJSON?.message ?? (xhr.responseJSON?.errors?.file?.[0] ?? 'Error al importar.');
                    $('#import-result').removeClass('d-none').html('<div class="alert alert-danger mb-0">' + msg + '</div>');
                    btn.prop('disabled', false).html('<i class="fas fa-upload me-1"></i> Importar');
                }
            });
        });

        // Filtros en cascada auto-submit
        $('#filter-sport, #filter-cat').on('change', function () { $('#filter-form').submit(); });
    });
    </script>
@endpush
