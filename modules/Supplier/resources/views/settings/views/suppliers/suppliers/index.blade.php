@extends('layouts.theme')

@section('title', 'Proveedores')

@section('page_header')
    @include('core::components.card', ['title' => 'Proveedores'])
@endsection

@section('content')
    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">
            <!-- Header Section -->
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Proveedores</h5>
                        <p class="small mb-0 text-muted">Gestiona los proveedores y sus fuentes de productos</p>
                    </div>
                    <div class="dropdown">
                        <button class="link text-dark p-3" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-ellipsis-vertical me-1"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="{{ route('settings.suppliers.import.erp') }}">
                                   Importar proveedor
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="{{ route('settings.suppliers.create') }}">
                                    Nuevo proveedor
                                </a>
                            </li>
                        </ul>
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
                                        <h4 class="mb-1 fw-bold">{{ $stats['total'] }}</h4>
                                        <small class="text-muted">Proveedores configurados</small>
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
                                        <h4 class="mb-1 fw-bold">{{ $stats['active'] }}</h4>
                                        <small class="text-muted">Proveedores habilitados</small>
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
                                        <h6 class="card-title mb-2">Inactivos</h6>
                                        <h4 class="mb-1 fw-bold">{{ $stats['inactive'] }}</h4>
                                        <small class="text-muted">Proveedores deshabilitados</small>
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
                                        <h6 class="card-title mb-2">Sincronizados</h6>
                                        <h4 class="mb-1 fw-bold">{{ $stats['synced'] }}</h4>
                                        <small class="text-muted">Con al menos una sincronización</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @if($limboCounts > 0)
            <!-- Limbo alert -->
            <div class="card-body border-bottom py-3">
                <a href="/panel/setting/suppliers/content?status=pending_generation"
                   class="d-flex align-items-center gap-3 p-3 rounded-3 text-decoration-none"
                   style="background:#fff8e1;border:1px solid #ffe082;">
                    <span class="d-flex align-items-center justify-content-center rounded-circle flex-shrink-0"
                          style="width:36px;height:36px;background:#ffc107;">
                        <i class="fas fa-triangle-exclamation text-white" style="font-size:.85rem;"></i>
                    </span>
                    <div>
                        <div class="fw-semibold" style="color:#856404;">
                            {{ $limboCounts }} {{ $limboCounts === 1 ? 'contenido pendiente' : 'contenidos pendientes' }} de generación (sin prompt activo)
                        </div>
                        <div class="small" style="color:#a07030;">
                            Estos registros no se procesarán hasta que se les asigne un prompt. Haz clic para revisarlos.
                        </div>
                    </div>
                    <i class="fas fa-arrow-right ms-auto" style="color:#ffc107;"></i>
                </a>
            </div>
            @endif

            <!-- Search Section -->
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('settings.suppliers.index') }}">
                    <div class="d-flex align-items-center gap-2">
                        <input type="search" name="search" class="form-control flex-grow-1"
                               placeholder="Buscar por código o nombre..."
                               value="{{ request('search') }}">
                        <div style="width: 180px; flex-shrink: 0;">
                            <select class="form-control select2" name="available">
                                <option value="">Todos los estados</option>
                                <option value="1" {{ request('available') == '1' ? 'selected' : '' }}>Activo</option>
                                <option value="0" {{ request('available') === '0' ? 'selected' : '' }}>Inactivo</option>
                            </select>
                        </div>
                        <div class="d-flex gap-1 flex-shrink-0">
                            <button type="submit" class="btn btn-primary" title="Buscar">
                                <i class="fas fa-magnifying-glass"></i>
                            </button>
                            @if(request('search') || request('available'))
                                <a href="{{ route('settings.suppliers.index') }}"
                                   class="btn btn-secondary" title="Limpiar filtros">
                                    <i class="fas fa-xmark"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>

            <!-- Suppliers List -->
            <div class="card-body">
                @if($suppliers->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="3%"><input type="checkbox" id="select-all" class="form-check-input"></th>
                                    <th>Nombre</th>
                                    <th class="text-center">Estado</th>
                                    <th>Última sync</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($suppliers as $supplier)
                                    <tr>
                                        <td><input type="checkbox" class="form-check-input bulk-checkbox" value="{{ $supplier->uid }}"></td>
                                        <td>
                                                <strong>{{ $supplier->label }}</strong>
                                        </td>
                                        <td class="text-center">
                                            @if($supplier->available)
                                                <span class="badge bg-success-subtle text-success">Activo</span>
                                            @else
                                                <span class="badge bg-light text-dark">Inactivo</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="text-muted">
                                                {{ $supplier->last_sync_at ? $supplier->last_sync_at->format('d/m/Y H:i') : '—' }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-ellipsis"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('settings.suppliers.detail', $supplier->uid) }}">
                                                            Ver detalle
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('settings.suppliers.sources.index', $supplier->uid) }}">
                                                            Fuentes
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('settings.suppliers.edit', $supplier->uid) }}">
                                                            Editar
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <button type="button"
                                                                class="dropdown-item delete-btn"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#delete-modal"
                                                                data-url="{{ route('settings.suppliers.destroy', $supplier->uid) }}"
                                                                data-title="Eliminar proveedor: {{ $supplier->label }}">
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
                                <i class="fas fa-inbox fs-7"></i>
                            </div>
                            <h6 class="mb-1">No hay proveedores para mostrar</h6>
                            <p class="text-muted mb-3">
                                @if(request('search'))
                                    No se encontraron resultados para "{{ request('search') }}"
                                @else
                                    Crea tu primer proveedor para comenzar
                                @endif
                            </p>
                            @if(!request('search'))
                                <a href="{{ route('settings.suppliers.create') }}" class="btn btn-sm btn-primary">
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
                        @if($suppliers->total() > 0)
                        <span class="text-muted small">
                            Mostrando {{ $suppliers->firstItem() }}–{{ $suppliers->lastItem() }} de {{ $suppliers->total() }}
                        </span>
                        @endif
                        <form method="GET" action="{{ route('settings.suppliers.index') }}" class="d-inline-flex align-items-center gap-1 mb-0">
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
                                    <option value="{{ $opt }}" {{ request('per_page', 15) == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                @endforeach
                                <option value="200" {{ request('per_page') == '200' ? 'selected' : '' }}>200</option>
                            </select>
                        </form>
                    </div>
                    @if($suppliers->hasPages())
                    <nav>{{ $suppliers->appends(request()->query())->links('pagination::bootstrap-5') }}</nav>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @include('core::components.delete')

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
                    <p class="text-muted mb-3">Se aplicará la acción sobre <strong><span data-bulk-count>0</span> proveedor(es)</strong>.</p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Acción</label>
                        <select id="bulk-action-select" class="form-select">
                            <option value="">Seleccionar acción...</option>
                            <option value="enable">Activar</option>
                            <option value="disable">Desactivar</option>
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
$(document).ready(function() {
    $('.select2').select2({
        allowClear: false,
        minimumResultsForSearch: Infinity
    });

    $('.delete-btn').on('click', function() {
        const deleteUrl = $(this).data('url');
        const deleteTitle = $(this).data('title');

        $('#delete-modal .modal-title').text(deleteTitle);
        $('#delete-form').data('url', deleteUrl);
    });

    $('#delete-form').on('submit', function(e) {
        e.preventDefault();

        const url = $(this).data('url');
        const $btn = $(this).find('[type=submit]');

        $btn.prop('disabled', true).text('Eliminando...');

        $.ajax({
            url: url,
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                $('#delete-modal').modal('hide');
                toastr.success(response.message || 'Proveedor eliminado exitosamente', 'Éxito');
                setTimeout(function() { location.reload(); }, 800);
            },
            error: function(xhr) {
                const msg = xhr.responseJSON?.message || 'Error al eliminar el proveedor';
                toastr.error(msg, 'Error');
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
        if (!uids.length) { toastr.warning('Selecciona al menos un proveedor.'); return; }
        if (action === 'delete' && !confirm('¿Eliminar los ' + uids.length + ' proveedor(es) seleccionados?')) { return; }

        $('#bulk-apply-btn').prop('disabled', true).text('Procesando...');

        $.ajax({
            url: '{{ route("settings.suppliers.bulk-action") }}',
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
});
</script>
@endpush
