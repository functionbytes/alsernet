@extends('layouts.theme')

@section('title', 'Productos')

@section('content')

    @include('core::components.card', ['title' => 'Productos'])



    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">

            {{-- Cabecera --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Productos de proveedores</h5>
                        <p class="small mb-0 text-muted">Artículos sincronizados desde las fuentes de cada proveedor</p>
                    </div>
                    <div class="d-flex gap-2"></div>
                </div>
            </div>

            {{-- Stats --}}
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Total</h6>
                                <h4 class="mb-1 fw-bold">{{ $stats['total'] }}</h4>
                                <small class="text-muted">Productos registrados</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Activos</h6>
                                <h4 class="mb-1 fw-bold">{{ $stats['active'] }}</h4>
                                <small class="text-muted">Productos disponibles</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">En web</h6>
                                <h4 class="mb-1 fw-bold">{{ $stats['web'] }}</h4>
                                <small class="text-muted">Publicados en tienda</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Filtros --}}
            <div class="card-body border-bottom">
                @php
                    $activeFilterCount = collect(['supplier_id', 'category_id', 'available'])->filter(fn($k) => request($k))->count();
                    $hasAnyFilter = $activeFilterCount > 0 || request('search');
                @endphp
                <form id="products-filter-form" method="GET" action="{{ route('settings.suppliers.products.index') }}">
                    <input type="hidden" name="supplier_id"  id="filter-supplier"   value="{{ request('supplier_id') }}">
                    <input type="hidden" name="category_id"  id="filter-category"   value="{{ request('category_id') }}">
                    <input type="hidden" name="available"    id="filter-available"  value="{{ request('available') }}">

                    <div class="d-flex align-items-center gap-2">
                        <input type="search" name="search" class="form-control flex-grow-1"
                               placeholder="Buscar por código o nombre..."
                               value="{{ request('search') }}">

                        <button type="button" class="btn btn-secondary position-relative flex-shrink-0"
                                data-bs-toggle="modal" data-bs-target="#products-filter-modal" title="Filtros avanzados">
                            <i class="fas fa-filter"></i>
                            @if($activeFilterCount > 0)
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary"
                                      style="font-size:0.6rem;">{{ $activeFilterCount }}</span>
                            @endif
                        </button>

                        <div class="d-flex gap-1 flex-shrink-0">
                            <button type="submit" class="btn btn-primary" title="Buscar">
                                <i class="fas fa-magnifying-glass"></i>
                            </button>
                            @if($hasAnyFilter)
                                <a href="{{ route('settings.suppliers.products.index') }}"
                                   class="btn btn-secondary" title="Limpiar filtros">
                                    <i class="fas fa-xmark"></i>
                                </a>
                            @endif
                        </div>
                    </div>

                    @if($activeFilterCount > 0)
                        <div class="d-flex gap-2 flex-wrap mt-4">
                            <div>
                                <h6 class="mb-1">Filtrados:</h6>
                            </div>
                            @if(request('supplier_id'))
                                @php $sup = $suppliers->firstWhere('id', request('supplier_id')); @endphp
                                <span class="badge bg-primary-subtle text-primary  py-1 px-2">
                                    Proveedor: {{ $sup?->label ?? request('supplier_id') }}
                                </span>
                            @endif
                            @if(request('category_id'))
                                @php $cat = $categories->firstWhere('id', request('category_id')); @endphp
                                <span class="badge bg-primary-subtle text-primary  py-1 px-2">
                                    Categoría: {{ $cat?->name ?? request('category_id') }}
                                </span>
                            @endif
                            @if(request('available') !== null && request('available') !== '')
                                <span class="badge bg-primary-subtle text-primary  py-1 px-2">
                                    Estado: {{ request('available') === '1' ? 'Activos' : 'Inactivos' }}
                                </span>
                            @endif
                        </div>
                    @endif
                </form>
            </div>

            {{-- Listado --}}
            <div class="card-body">
                <div class="mb-3 d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1 fw-bold">Listado de productos</h6>
                        <p class="text-muted small mb-0">{{ $items->total() }} productos encontrados</p>
                    </div>
                </div>

                @if($items->count())
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="3%"><input type="checkbox" id="select-all" class="form-check-input"></th>
                                    <th>Nombre</th>
                                    <th>Código</th>
                                    <th>Categoría</th>
                                    <th class="text-center">Artículos</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($items as $item)
                                    <tr>
                                        <td><input type="checkbox" class="form-check-input bulk-checkbox" value="{{ $item->id }}"></td>
                                        <td><strong>{{ $item->name }}</strong></td>
                                        <td><code class="small">{{ $item->code }}</code></td>
                                        <td class="small text-muted">{{ $item->category?->name ?? '—' }}</td>
                                        <td class="text-center">
                                            @if($item->attributes_count > 0)
                                                <span class="badge bg-primary-subtle text-primary">
                                                    {{ $item->attributes_count }}
                                                </span>
                                            @else
                                                <span class="text-muted small">—</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($item->available)
                                                <span class="badge bg-success-subtle text-success">Activo</span>
                                            @else
                                                <span class="badge bg-light text-dark">Inactivo</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('settings.suppliers.products.show', $item->id) }}">
                                                            Ver detalle
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('settings.suppliers.products.edit', $item->id) }}">
                                                            Editar
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    @if(!$item->has_approved_content)
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('settings.suppliers.products.chat', ['uid' => $item->uid]) }}">
                                                            Chat IA
                                                        </a>
                                                    </li>
                                                    @endif
                                                    @if($item->category_id)
                                                        <li>
                                                            <a class="dropdown-item" href="{{ route('settings.suppliers.prompts.create') }}?category_id={{ $item->category_id }}">
                                                               Crear prompt
                                                            </a>
                                                        </li>
                                                    @endif
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
                        <i class="fas fa-box-open fa-2x mb-3 d-block"></i>
                        <h6 class="mb-1">No hay productos disponibles</h6>
                        @if(request()->hasAny(['search', 'supplier_id', 'category_id', 'available']))
                            <p class="small mb-0">No se encontraron resultados para los filtros aplicados</p>
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
                        <form method="GET" action="{{ route('settings.suppliers.products.index') }}" class="d-inline-flex align-items-center gap-1 mb-0">
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
                                    <option value="{{ $opt }}" {{ request('per_page', 50) == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                @endforeach
                                <option value="200" {{ request('per_page') == '200' ? 'selected' : '' }}>200</option>
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

    {{-- Filter modal --}}
    <div class="modal fade" id="products-filter-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Filtros avanzados</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Proveedor</label>
                        <select id="modal-supplier" class="form-control select2-filter-modal">
                            <option value="">Todos los proveedores</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" {{ request('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                    {{ $supplier->label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Categoría</label>
                        <select id="modal-category" class="form-control select2-filter-modal">
                            <option value="">Todas las categorías</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-semibold">Estado</label>
                        <select id="modal-available" class="form-control select2-filter-modal">
                            <option value="">Todos</option>
                            <option value="1" {{ request('available') === '1' ? 'selected' : '' }}>Activos</option>
                            <option value="0" {{ request('available') === '0' ? 'selected' : '' }}>Inactivos</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" id="products-filter-apply-btn" class="btn btn-primary w-100 mb-1">
                        Aplicar filtros
                    </button>
                    <button type="button" id="products-filter-clear-btn" class="btn btn-secondary w-100">
                       Limpiar
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Bulk toolbar flotante --}}
    <div id="bulk-toolbar" class="position-fixed bottom-0 start-50 translate-middle-x mb-4 d-none" style="z-index:1050;">
        <button type="button" class="btn btn-primary shadow-lg px-4" data-bs-toggle="modal" data-bs-target="#bulk-modal">
            <span data-bulk-count>0</span> seleccionado(s) &mdash; Aplicar acción
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
                    <p class="text-muted mb-3">Se aplicará la acción sobre <strong><span data-bulk-count>0</span> producto(s)</strong>.</p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Acción</label>
                        <select id="bulk-action-select" class="form-select">
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
function requestBrowserNotificationPermission() {
    if ('Notification' in window && Notification.permission === 'default') {
        Notification.requestPermission();
    }
}

function sendBrowserNotification(title, body) {
    if ('Notification' in window && Notification.permission === 'granted') {
        new Notification(title, { body: body, icon: '/favicon.ico' });
    }
}

$(document).ready(function () {
    requestBrowserNotificationPermission();
    @if(session('success'))
        toastr.success('{{ session('success') }}', 'Éxito');
    @endif
    @if(session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif

    // Filter modal
    $('.select2-filter-modal').select2({ dropdownParent: $('#products-filter-modal'), width: '100%' });

    $('#products-filter-apply-btn').on('click', function () {
        $('#filter-supplier').val($('#modal-supplier').val());
        $('#filter-category').val($('#modal-category').val());
        $('#filter-available').val($('#modal-available').val());
        $('#products-filter-modal').modal('hide');
        $('#products-filter-form').submit();
    });

    $('#products-filter-clear-btn').on('click', function () {
        $('#modal-supplier, #modal-category, #modal-available').val(null).trigger('change');
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
        const ids    = bulk.getIds();

        if (!action) { toastr.warning('Selecciona una acción.'); return; }
        if (!ids.length) { toastr.warning('Selecciona al menos un producto.'); return; }
        if (action === 'delete' && !confirm('¿Eliminar los ' + ids.length + ' producto(s) seleccionados?')) { return; }

        $('#bulk-apply-btn').prop('disabled', true).text('Procesando...');

        $.ajax({
            url: '{{ route("settings.suppliers.products.bulk-action") }}',
            method: 'POST',
            data: JSON.stringify({ action: action, ids: ids, _token: $('meta[name="csrf-token"]').attr('content') }),
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                $('#bulk-modal').modal('hide');
                toastr.success(res.message);
                sendBrowserNotification('Acción completada', ids.length + ' producto(s) procesados correctamente.');
                setTimeout(() => location.reload(), 800);
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message ?? 'Error al procesar.');
                $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
            },
        });
    });
});
</script>
@endpush
