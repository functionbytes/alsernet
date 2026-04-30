@extends('layouts.theme')

@section('title', 'Estados y provincias')

@section('page_header')
    @include('core::components.card', ['title' => 'Estados y provincias'])
@endsection

@section('content')

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">

            {{-- Header --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Estados y provincias</h5>
                        <p class="small mb-0 text-muted">Gestión de estados y provincias por país</p>
                    </div>
                    @can('locations.states.create')
                        <a href="{{ route('locations.states.create') }}" class="btn btn-primary ms-auto">
                            Nuevo estado
                        </a>
                    @endcan
                </div>
            </div>

            {{-- Search + filters --}}
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('locations.states.index') }}" id="filterForm">
                    <div class="d-flex gap-2 align-items-center flex-wrap">
                        <div class="flex-fill" style="min-width:200px">
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="search" name="search" class="form-control border-start-0 ps-0"
                                       placeholder="Buscar por nombre o código..."
                                       value="{{ request('search') }}">
                            </div>
                        </div>
                        <select name="country_id" class="form-select flex-shrink-0" style="width:auto">
                            <option value="">Todos los países</option>
                            @foreach($countries as $country)
                                <option value="{{ $country->id }}" {{ request('country_id') == $country->id ? 'selected' : '' }}>
                                    {{ $country->name }}
                                </option>
                            @endforeach
                        </select>
                        <select name="is_active" class="form-select flex-shrink-0" style="width:auto">
                            <option value="">Todos</option>
                            <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Activo</option>
                            <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Inactivo</option>
                        </select>
                        <button type="submit" class="btn btn-primary flex-shrink-0">Filtrar</button>
                        @if(request('search') || request('country_id') || (request()->has('is_active') && request('is_active') !== ''))
                            <a href="{{ route('locations.states.index') }}" class="btn btn-outline-secondary flex-shrink-0" title="Limpiar">
                                <i class="fas fa-times"></i>
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            {{-- Table --}}
            <div class="card-body">
                @if($states->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle text-nowrap">
                            <thead class="table-light">
                                <tr>
                                    <th width="3%">
                                        <input type="checkbox" id="select-all" class="form-check-input">
                                    </th>
                                    <th>Estado/provincia</th>
                                    <th>Código</th>
                                    <th>País</th>
                                    <th>Activo</th>
                                    <th>Orden</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($states as $state)
                                    <tr>
                                        <td>
                                            <input type="checkbox" class="form-check-input bulk-checkbox" value="{{ $state->id }}">
                                        </td>
                                        <td class="fw-semibold">{{ $state->name }}</td>
                                        <td>
                                            @if($state->code)
                                                <span class="badge bg-secondary-subtle text-secondary">{{ $state->code }}</span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="me-1">{{ $state->country?->flag_emoji }}</span>
                                            {{ $state->country?->name }}
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $state->is_active ? 'success' : 'secondary' }}-subtle text-{{ $state->is_active ? 'success' : 'secondary' }}">
                                                {{ $state->is_active ? 'Activo' : 'Inactivo' }}
                                            </span>
                                        </td>
                                        <td>{{ $state->order }}</td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <a href="#" class="text-muted" data-bs-toggle="dropdown" data-bs-boundary="viewport">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    @can('locations.states.update')
                                                        <li><a class="dropdown-item" href="{{ route('locations.states.edit', $state) }}">Editar</a></li>
                                                    @endcan
                                                    @can('locations.states.delete')
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <a class="dropdown-item delete-btn" href="#"
                                                               data-url="{{ route('locations.states.destroy', $state) }}"
                                                               data-title="Eliminar {{ $state->name }}">
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
                        <i class="fas fa-map fa-3x mb-3 text-muted opacity-50"></i>
                        <h5 class="fw-bold mb-2">
                            @if(request('search') || request('country_id'))
                                No se encontraron resultados
                            @else
                                No hay estados registrados
                            @endif
                        </h5>
                        <p class="text-muted mb-4">
                            @if(request('search'))
                                No hay resultados para "{{ request('search') }}"
                            @else
                                Aún no hay estados o provincias creados
                            @endif
                        </p>
                        @can('locations.states.create')
                            <a href="{{ route('locations.states.create') }}" class="btn btn-primary">
                                Nuevo estado
                            </a>
                        @endcan
                    </div>
                @endif
            </div>

            {{-- Pagination --}}
            @if($states->hasPages())
                <div class="card-footer bg-white border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted small">
                            Mostrando {{ $states->firstItem() }}–{{ $states->lastItem() }} de {{ $states->total() }}
                        </div>
                        <div>
                            {{ $states->appends(request()->query())->links() }}
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
                    <p class="text-muted mb-3">Se aplicará la acción sobre <strong><span data-bulk-count>0</span> estado(s)</strong>.</p>
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
        if (action === 'delete' && !confirm('¿Eliminar los ' + ids.length + ' estado(s) seleccionados?')) return;

        $('#bulk-apply-btn').prop('disabled', true).text('Procesando...');

        $.ajax({
            url: '{{ route('locations.states.bulk-action') }}',
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
