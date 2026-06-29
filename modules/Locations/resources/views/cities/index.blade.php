@extends('layouts.theme')

@section('title', 'Ciudades')

@section('page_header')
    @include('core::components.card', ['title' => 'Ciudades'])
@endsection

@section('content')

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">

            {{-- Header --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Ciudades</h5>
                        <p class="small mb-0 text-muted">Gestión de ciudades por estado y país</p>
                    </div>
                    @can('locations.cities.create')
                        <a href="{{ route('locations.cities.create') }}" class="btn btn-primary ms-auto">
                            Nueva ciudad
                        </a>
                    @endcan
                </div>
            </div>

            {{-- Search + filters --}}
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('locations.cities.index') }}" id="filterForm">
                    <div class="d-flex gap-2 align-items-center flex-wrap">
                        <div class="flex-fill" style="min-width:200px">
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="search" name="search" class="form-control -0 ps-0"
                                       placeholder="Buscar por nombre..."
                                       value="{{ request('search') }}">
                            </div>
                        </div>
                        <select id="filter-country" name="country_id" class="form-select flex-shrink-0" style="width:auto">
                            <option value="">Todos los países</option>
                            @foreach($countries as $country)
                                <option value="{{ $country->id }}" {{ request('country_id') == $country->id ? 'selected' : '' }}>
                                    {{ $country->name }}
                                </option>
                            @endforeach
                        </select>
                        <select id="filter-state" name="state_id" class="form-select flex-shrink-0" style="width:auto">
                            <option value="">Todos los estados</option>
                        </select>
                        <select name="is_active" class="form-select flex-shrink-0" style="width:auto">
                            <option value="">Todos</option>
                            <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Activo</option>
                            <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Inactivo</option>
                        </select>
                        <button type="submit" class="btn btn-primary flex-shrink-0">Filtrar</button>
                        @if(request('search') || request('country_id') || request('state_id') || (request()->has('is_active') && request('is_active') !== ''))
                            <a href="{{ route('locations.cities.index') }}" class="btn btn-outline-secondary flex-shrink-0" title="Limpiar">
                                <i class="fas fa-times"></i>
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            {{-- Table --}}
            <div class="card-body">
                @if($cities->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle text-nowrap">
                            <thead class="table-light">
                                <tr>
                                    <th width="3%">
                                        <input type="checkbox" id="select-all" class="form-check-input">
                                    </th>
                                    <th>Ciudad</th>
                                    <th>Estado</th>
                                    <th>País</th>
                                    <th>Activo</th>
                                    <th>Orden</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($cities as $city)
                                    <tr>
                                        <td>
                                            <input type="checkbox" class="form-check-input bulk-checkbox" value="{{ $city->id }}">
                                        </td>
                                        <td class="fw-semibold">{{ $city->name }}</td>
                                        <td>{{ $city->state?->name ?? '—' }}</td>
                                        <td>
                                            <span class="me-1">{{ $city->country?->flag_emoji }}</span>
                                            {{ $city->country?->name ?? '—' }}
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $city->is_active ? 'success' : 'secondary' }}-subtle text-{{ $city->is_active ? 'success' : 'secondary' }}">
                                                {{ $city->is_active ? 'Activo' : 'Inactivo' }}
                                            </span>
                                        </td>
                                        <td>{{ $city->order }}</td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <a href="#" class="text-muted" data-bs-toggle="dropdown" data-bs-boundary="viewport">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    @can('locations.cities.update')
                                                        <li><a class="dropdown-item" href="{{ route('locations.cities.edit', $city) }}">Editar</a></li>
                                                    @endcan
                                                    @can('locations.cities.delete')
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <a class="dropdown-item delete-btn" href="#"
                                                               data-url="{{ route('locations.cities.destroy', $city) }}"
                                                               data-title="Eliminar {{ $city->name }}">
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
                        <i class="fas fa-city fa-3x mb-3 text-muted opacity-50"></i>
                        <h5 class="fw-bold mb-2">
                            @if(request('search') || request('country_id') || request('state_id'))
                                No se encontraron resultados
                            @else
                                No hay ciudades registradas
                            @endif
                        </h5>
                        <p class="text-muted mb-4">
                            @if(request('search'))
                                No hay resultados para "{{ request('search') }}"
                            @else
                                Aún no hay ciudades creadas en el sistema
                            @endif
                        </p>
                        @can('locations.cities.create')
                            <a href="{{ route('locations.cities.create') }}" class="btn btn-primary">
                                Nueva ciudad
                            </a>
                        @endcan
                    </div>
                @endif
            </div>

            {{-- Pagination --}}
            @if($cities->hasPages())
                <div class="card-footer bg-white border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted small">
                            Mostrando {{ $cities->firstItem() }}–{{ $cities->lastItem() }} de {{ $cities->total() }}
                        </div>
                        <div>
                            {{ $cities->appends(request()->query())->links() }}
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
                    <p class="text-muted mb-3">Se aplicará la acción sobre <strong><span data-bulk-count>0</span> ciudad(es)</strong>.</p>
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
    const statesUrl = '{{ route('api.locations.states') }}';
    const currentStateId = '{{ request('state_id') }}';

    // Populate states on page load if country is pre-selected
    const currentCountryId = $('#filter-country').val();
    if (currentCountryId) {
        loadStates(currentCountryId, currentStateId);
    }

    $('#filter-country').on('change', function () {
        loadStates($(this).val(), '');
    });

    function loadStates(countryId, selectedStateId) {
        const $stateSelect = $('#filter-state');

        if (!countryId) {
            $stateSelect.html('<option value="">Todos los estados</option>');
            return;
        }

        $.get(statesUrl, { country_id: countryId }, function (data) {
            let options = '<option value="">Todos los estados</option>';
            data.forEach(function (s) {
                const selected = selectedStateId && s.id == selectedStateId ? ' selected' : '';
                options += `<option value="${s.id}"${selected}>${s.name}</option>`;
            });
            $stateSelect.html(options);
        });
    }

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
        if (action === 'delete' && !confirm('¿Eliminar las ' + ids.length + ' ciudad(es) seleccionadas?')) return;

        $('#bulk-apply-btn').prop('disabled', true).text('Procesando...');

        $.ajax({
            url: '{{ route('locations.cities.bulk-action') }}',
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
