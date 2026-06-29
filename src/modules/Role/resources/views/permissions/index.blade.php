@extends('layouts.theme')

@section('title', 'Gestión de Permisos')

@section('page_header')
    @include('core::components.card', ['title' => 'Gestión de Permisos'])
@endsection

@section('content')

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">

            {{-- Header --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Permisos del sistema</h5>
                        <p class="small mb-0 text-muted">Administra los permisos disponibles para los roles</p>
                    </div>
                    <div class="d-flex gap-2">
                        @can('permissions.create')
                            <a href="{{ route('settings.permissions.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i> Crear permiso
                            </a>
                        @endcan
                    </div>
                </div>
            </div>

            {{-- Stats --}}
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Total</h6>
                                <h4 class="mb-1 fw-bold">{{ $permissions->total() }}</h4>
                                <small class="text-muted">Total permisos</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Permisos web</h6>
                                <h4 class="mb-1 fw-bold">{{ \Spatie\Permission\Models\Permission::where('guard_name', 'web')->count() }}</h4>
                                <small class="text-muted">Guard Web</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Permisos API</h6>
                                <h4 class="mb-1 fw-bold">{{ \Spatie\Permission\Models\Permission::where('guard_name', 'api')->count() }}</h4>
                                <small class="text-muted">Guard API</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Roles</h6>
                                <h4 class="mb-1 fw-bold">{{ \Spatie\Permission\Models\Role::count() }}</h4>
                                <small class="text-muted">Roles totales</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Search --}}
            <div class="card-body border-bottom">
                <form action="{{ route('settings.permissions.index') }}" method="GET">
                    <div class="d-flex flex-column flex-lg-row gap-3 align-items-stretch">
                        <div class="flex-fill">
                            <div class="input-group h-100">
                                <span class="input-group-text bg-white border-end-1">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="search" name="search" class="form-control -0 ps-0"
                                       placeholder="Buscar por nombre de permiso..."
                                       value="{{ request('search') }}">
                            </div>
                        </div>
                        <div class="d-flex gap-2 flex-shrink-0">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-1"></i>
                            </button>
                            @if(request('search'))
                                <a href="{{ route('settings.permissions.index') }}" class="btn btn-outline-secondary" title="Limpiar filtros">
                                    <i class="fas fa-times"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>

            {{-- Table --}}
            <div class="card-body">
                @if($permissions->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="3%"><input type="checkbox" id="select-all" class="form-check-input"></th>
                                    <th>Nombre del permiso</th>
                                    <th>Guard</th>
                                    <th class="text-center">Roles asignados</th>
                                    <th>Fecha de creacion</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($permissions as $permission)
                                    <tr>
                                        <td><input type="checkbox" class="form-check-input bulk-checkbox" value="{{ $permission->id }}"></td>
                                        <td>
                                            <a href="{{ route('settings.permissions.edit', $permission->id) }}" class="text-decoration-none fw-semibold">
                                                {{ $permission->name }}
                                            </a>
                                            @if($permission->description)
                                                <small class="d-block text-muted">{{ Str::limit($permission->description, 50) }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            @if($permission->guard_name === 'web')
                                                <span class="badge bg-success-subtle text-success">Web</span>
                                            @elseif($permission->guard_name === 'api')
                                                <span class="badge bg-info-subtle text-info">API</span>
                                            @else
                                                <span class="badge bg-light text-black">{{ $permission->guard_name }}</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-primary-subtle text-primary">{{ $permission->roles()->count() }}</span>
                                        </td>
                                        <td>{{ $permission->created_at->format('d/m/Y H:i') }}</td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    @can('permissions.edit')
                                                        <li>
                                                            <a href="{{ route('settings.permissions.edit', $permission->id) }}" class="dropdown-item">
                                                                Editar
                                                            </a>
                                                        </li>
                                                    @endcan
                                                    @can('permissions.delete')
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <a class="dropdown-item delete-btn" href="javascript:void(0)"
                                                               data-bs-toggle="modal" data-bs-target="#delete-modal"
                                                               data-url="{{ route('settings.permissions.destroy', $permission->id) }}"
                                                               data-title="Eliminar permiso: {{ $permission->name }}">
                                                                Eliminar
                                                            </a>
                                                        </li>
                                                    @endcan
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
                                <i class="fas fa-key fs-7"></i>
                            </div>
                            <h6 class="mb-1">
                                @if(request('search'))
                                    No se encontraron permisos
                                @else
                                    No hay permisos configurados
                                @endif
                            </h6>
                            <p class="text-muted mb-3">
                                @if(request('search'))
                                    No hay resultados para los criterios de búsqueda
                                @else
                                    Crea el primer permiso para gestionar accesos del sistema
                                @endif
                            </p>
                            @if(!request('search'))
                                <a href="{{ route('settings.permissions.create') }}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-plus me-1"></i> Crear permiso
                                </a>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            {{-- Pagination --}}
            @if($permissions->hasPages())
                <div class="card-footer bg-white border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted">
                            Mostrando <strong>{{ $permissions->firstItem() }}</strong> a <strong>{{ $permissions->lastItem() }}</strong>
                            de <strong>{{ $permissions->total() }}</strong> permisos
                        </div>
                        {{ $permissions->links() }}
                    </div>
                </div>
            @endif

        </div>
    </div>

    {{-- Bulk toolbar --}}
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
                    <p class="text-muted mb-3">Se aplicará la acción sobre <strong><span data-bulk-count>0</span> permiso(s)</strong>.</p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Acción</label>
                        <select id="bulk-action-select" class="form-select">
                            <option value="">Seleccionar acción...</option>
                            <option value="delete">Eliminar</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button id="bulk-apply-btn" type="button" class="btn btn-primary w-100 mb-1">Aplicar</button>
                    <button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    @include('core::components.delete')

@endsection

@push('scripts')
<script>
$(document).ready(function () {
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
        if (!ids.length) { toastr.warning('Selecciona al menos un permiso.'); return; }
        if (!confirm('¿Eliminar los ' + ids.length + ' permiso(s) seleccionados? Los asignados a roles serán omitidos.')) { return; }

        $('#bulk-apply-btn').prop('disabled', true).text('Procesando...');

        $.ajax({
            url: '{{ route('settings.permissions.bulk-action') }}',
            method: 'POST',
            data: JSON.stringify({ action, ids, _token: $('meta[name="csrf-token"]').attr('content') }),
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

    $('.delete-btn').on('click', function () {
        $('#delete-modal .modal-title').text($(this).data('title'));
        $('#delete-form').attr('action', $(this).data('url'));
    });

    @if(session('success'))
        toastr.success('{{ session('success') }}', 'Éxito');
    @endif
    @if(session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif
});
</script>
@endpush
