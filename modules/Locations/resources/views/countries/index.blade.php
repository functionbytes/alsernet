@extends('layouts.theme')

@section('title', 'Países')

@section('content')

    @include('core::components.card', ['title' => 'Países'])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">

            {{-- Header --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Países</h5>
                        <p class="small mb-0 text-muted">Gestión de países disponibles en el sistema</p>
                    </div>
                    @can('locations.countries.create')
                        <a href="{{ route('locations.countries.create') }}" class="btn btn-primary ms-auto">
                            Nuevo país
                        </a>
                    @endcan
                </div>
            </div>

            {{-- Search + filters --}}
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('locations.countries.index') }}" id="filterForm">
                    <div class="d-flex gap-2 align-items-center">
                        <div class="flex-fill">
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="search" name="search" class="form-control border-start-0 ps-0"
                                       placeholder="Buscar por nombre o código..."
                                       value="{{ request('search') }}">
                            </div>
                        </div>
                        <select name="is_active" class="form-select flex-shrink-0" style="width:auto">
                            <option value="">Todos</option>
                            <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Activo</option>
                            <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Inactivo</option>
                        </select>
                        <button type="submit" class="btn btn-primary flex-shrink-0">Filtrar</button>
                        @if(request('search') || request()->has('is_active') && request('is_active') !== '')
                            <a href="{{ route('locations.countries.index') }}" class="btn btn-outline-secondary flex-shrink-0" title="Limpiar">
                                <i class="fas fa-times"></i>
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            {{-- Table --}}
            <div class="card-body">
                @if($countries->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle text-nowrap">
                            <thead class="table-light">
                                <tr>
                                    <th width="3%">
                                        <input type="checkbox" id="select-all" class="form-check-input">
                                    </th>
                                    <th>País</th>
                                    <th>Código</th>
                                    <th>Teléfono</th>
                                    <th>Moneda</th>
                                    <th>Activo</th>
                                    <th>Orden</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($countries as $country)
                                    <tr>
                                        <td>
                                            <input type="checkbox" class="form-check-input bulk-checkbox" value="{{ $country->id }}">
                                        </td>
                                        <td>
                                            <span class="me-1">{{ $country->flag_emoji }}</span>
                                            <span class="fw-semibold">{{ $country->name }}</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary-subtle text-secondary">{{ $country->code }}</span>
                                        </td>
                                        <td>{{ $country->phone_code }}</td>
                                        <td>
                                            @if($country->currency_symbol || $country->currency_code)
                                                {{ $country->currency_symbol }} {{ $country->currency_code }}
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $country->is_active ? 'success' : 'secondary' }}-subtle text-{{ $country->is_active ? 'success' : 'secondary' }}">
                                                {{ $country->is_active ? 'Activo' : 'Inactivo' }}
                                            </span>
                                        </td>
                                        <td>{{ $country->order }}</td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <a href="#" class="text-muted" data-bs-toggle="dropdown" data-bs-boundary="viewport">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    @can('locations.countries.update')
                                                        <li><a class="dropdown-item" href="{{ route('locations.countries.edit', $country) }}">Editar</a></li>
                                                    @endcan
                                                    @can('locations.countries.delete')
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <a class="dropdown-item delete-btn" href="#"
                                                               data-url="{{ route('locations.countries.destroy', $country) }}"
                                                               data-title="Eliminar {{ $country->name }}">
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
                        <i class="fas fa-globe fa-3x mb-3 text-muted opacity-50"></i>
                        <h5 class="fw-bold mb-2">
                            @if(request('search') || request('is_active') !== null)
                                No se encontraron resultados
                            @else
                                No hay países registrados
                            @endif
                        </h5>
                        <p class="text-muted mb-4">
                            @if(request('search'))
                                No hay resultados para "{{ request('search') }}"
                            @else
                                Aún no hay países creados en el sistema
                            @endif
                        </p>
                        @can('locations.countries.create')
                            <a href="{{ route('locations.countries.create') }}" class="btn btn-primary">
                                Nuevo país
                            </a>
                        @endcan
                    </div>
                @endif
            </div>

            {{-- Pagination --}}
            @if($countries->hasPages())
                <div class="card-footer bg-white border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted small">
                            Mostrando {{ $countries->firstItem() }}–{{ $countries->lastItem() }} de {{ $countries->total() }}
                        </div>
                        <div>
                            {{ $countries->appends(request()->query())->links() }}
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Bulk toolbar --}}
    <div id="bulk-toolbar" class="position-fixed bottom-0 start-50 translate-middle-x mb-4 d-none" style="z-index:1050;">
        <button type="button" class="btn btn-primary shadow-lg px-4" data-bs-toggle="modal" data-bs-target="#bulk-modal">
            <span data-bulk-count>0</span> seleccionado(s) — Aplicar acción
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
                    <p class="text-muted mb-3">Se aplicará la acción sobre <strong><span data-bulk-count>0</span> país(es)</strong>.</p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Acción</label>
                        <select id="bulk-action-select" class="form-select">
                            <option value="">Seleccionar acción...</option>
                            <option value="activate">Activar</option>
                            <option value="deactivate">Desactivar</option>
                            <option value="delete">Eliminar</option>
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

    @include('core::components.delete')

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    const bulk = window.BulkActions.init({ checkbox: '.bulk-checkbox' });

    $('#bulk-modal').on('hide.bs.modal', function () {
        $('#bulk-action-select').val('');
        $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
        bulk.reset();
    });

    $('#bulk-apply-btn').on('click', function () {
        const action = $('#bulk-action-select').val();
        const ids = bulk.getIds();

        if (!action) { toastr.warning('Selecciona una acción.'); return; }
        if (!ids.length) { toastr.warning('Selecciona al menos un registro.'); return; }
        if (action === 'delete' && !confirm('¿Eliminar los ' + ids.length + ' país(es) seleccionados?')) return;

        $('#bulk-apply-btn').prop('disabled', true).text('Procesando...');

        $.ajax({
            url: '{{ route('locations.countries.bulk-action') }}',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ action: action, ids: ids }),
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function () {
                $('#bulk-modal').modal('hide');
                toastr.success('Acción aplicada correctamente.');
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

    @if(session('success'))
        toastr.success('{{ session('success') }}');
    @endif
});
</script>
@endpush
