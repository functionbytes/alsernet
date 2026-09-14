@extends('layouts.theme')

@section('title', 'Configuracion de agentes')

@push('styles')
<style>
.hd-bulk-toolbar { z-index: 1050; }
</style>
@endpush

@section('page_header')
    @include('core::components.card', ['title' => 'Configuracion de agentes'])
@endsection

@section('content')

    @include('core::components.alerts')

    <div class="card">

        {{-- Header --}}
        <div class="card-header p-4 border-bottom border-light">
            <div>
                <h5 class="mb-1 fw-bold">Configuracion de agentes</h5>
                <p class="small mb-0 text-muted">Gestiona la disponibilidad y limites de conversaciones de cada agente</p>
            </div>
        </div>

        {{-- Stats --}}
        <div class="card-body border-bottom">
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="card bg-light-secondary stat-card h-100">
                        <div class="card-body">
                            <h6 class="card-title text-primary mb-2">Total agentes</h6>
                            <h4 class="mb-1 fw-bold">{{ $stats['total'] }}</h4>
                            <small class="text-muted">Con rol de agente</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-light-secondary stat-card h-100">
                        <div class="card-body">
                            <h6 class="card-title  mb-2">Disponibles</h6>
                            <h4 class="mb-1 fw-bold">{{ $stats['available'] }}</h4>
                            <small class="text-muted">Listos para recibir conversaciones</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-light-secondary stat-card h-100">
                        <div class="card-body">
                            <h6 class="card-title  mb-2">En vacaciones</h6>
                            <h4 class="mb-1 fw-bold">{{ $stats['on_vacation'] }}</h4>
                            <small class="text-muted">Con fecha de vacaciones activa</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-light-secondary stat-card h-100">
                        <div class="card-body">
                            <h6 class="card-title text-secondary mb-2">Con limite</h6>
                            <h4 class="mb-1 fw-bold">{{ $stats['with_limit'] }}</h4>
                            <small class="text-muted">Limite de conversaciones activo</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filtros --}}
        <div class="card-body border-bottom">
            @php
                $activeFilterCount = collect(['available'])->filter(fn ($k) => request($k) !== null && request($k) !== '')->count();
                $hasAnyFilter = $activeFilterCount > 0 || request('search');
            @endphp
            <form id="agent-settings-filter-form" method="GET" action="{{ route('settings.helpdesk.agent-settings.index') }}">
                <input type="hidden" name="available" id="filter-available" value="{{ request('available') }}">

                <div class="d-flex align-items-center gap-2">
                    <input type="search" name="search" class="form-control flex-grow-1"
                           placeholder="Buscar por nombre o email..."
                           value="{{ request('search') }}">

                    <button type="button" class="btn btn-secondary position-relative flex-shrink-0"
                            data-bs-toggle="modal" data-bs-target="#agent-settings-filter-modal" title="Filtros avanzados">
                        <i class="fas fa-filter"></i>
                        @if($activeFilterCount > 0)
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary">{{ $activeFilterCount }}</span>
                        @endif
                    </button>

                    <div class="d-flex gap-1 flex-shrink-0">
                        <button type="submit" class="btn btn-primary" title="Buscar">
                            <i class="fas fa-magnifying-glass"></i>
                        </button>
                        @if($hasAnyFilter)
                            <a href="{{ route('settings.helpdesk.agent-settings.index') }}"
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
                        @if(request('available') !== null && request('available') !== '')
                            <span class="badge bg-primary-subtle text-primary py-1 px-2">
                                Disponibilidad: {{ request('available') === '1' ? 'Disponibles' : 'No disponibles' }}
                            </span>
                        @endif
                    </div>
                @endif
            </form>
        </div>

        {{-- Table --}}
        <div class="card-body">
            @if($agents->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="3%"><input type="checkbox" id="select-all" class="form-check-input"></th>
                                <th scope="col">Agente</th>
                                <th scope="col">Rol</th>
                                <th scope="col" class="text-center">Disponible</th>
                                <th scope="col" class="text-center">Acepta conv.</th>
                                <th scope="col" class="text-center">Max. abiertas</th>
                                <th scope="col">Vacaciones hasta</th>
                                <th scope="col" class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($agents as $agent)
                                @php
                                    $settings = $settingsMap->get($agent->id);
                                @endphp
                                <tr>
                                    <td><input type="checkbox" class="form-check-input bulk-checkbox" value="{{ $agent->id }}"></td>
                                    <td>
                                        <div class="fw-semibold">{{ $agent->name }}</div>
                                        <small class="text-muted">{{ $agent->email }}</small>
                                    </td>
                                    <td>
                                        @foreach($agent->roles->take(2) as $role)
                                            <span class="badge bg-secondary-subtle text-secondary">{{ $role->name }}</span>
                                        @endforeach
                                    </td>
                                    <td class="text-center">
                                        @if($settings?->is_available)
                                            <span class="badge bg-success-subtle text-success">Disponible</span>
                                        @else
                                            <span class="badge bg-info-subtle text-info">No disponible</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @php $accepts = $settings?->accepts_conversations ?? 'yes' @endphp
                                        @if($accepts === 'yes')
                                            <span class="badge bg-success-subtle text-success">Siempre</span>
                                        @elseif($accepts === 'no')
                                            <span class="badge bg-info-subtle text-info">Nunca</span>
                                        @else
                                            <span class="badge bg-warning-subtle text-warning">Horario</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @php $max = $settings?->max_concurrent_conversations ?? 0 @endphp
                                        @if($max > 0)
                                            <span class="fw-semibold">{{ $max }}</span>
                                        @else
                                            <span class="text-muted small">Sin limite</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($settings?->vacation_until && $settings->vacation_until->isFuture())
                                            <span class="text-warning small">{{ $settings->vacation_until->format('d/m/Y') }}</span>
                                        @else
                                            <span class="text-muted small">—</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="fas fa-ellipsis-vertical"></i>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                @can('helpdesk.agents.manage')
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('settings.helpdesk.agent-settings.edit', $agent) }}">
                                                            Editar configuracion
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
                        <div class="mb-3">
                            <i class="fas fa-users fa-2x text-muted"></i>
                        </div>
                        <h6 class="mb-1">No hay agentes</h6>
                        <p class="text-muted mb-0">
                            @if(request('search') || request('available'))
                                No se encontraron agentes con los filtros aplicados
                            @else
                                No hay usuarios con rol de agente configurados
                            @endif
                        </p>
                    </div>
                </div>
            @endif
        </div>

        @if($agents->hasPages())
            <div class="card-footer bg-white border-top">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="text-muted small">
                        Mostrando <strong>{{ $agents->firstItem() }}</strong> a <strong>{{ $agents->lastItem() }}</strong>
                        de <strong>{{ $agents->total() }}</strong> agentes
                    </div>
                    {{ $agents->appends(request()->input())->links() }}
                </div>
            </div>
        @endif

    </div>

    {{-- Filter modal --}}
    <div class="modal fade" id="agent-settings-filter-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Filtros avanzados</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-0">
                        <label class="form-label fw-semibold">Disponibilidad</label>
                        <select id="modal-available" class="form-control select2-filter-modal">
                            <option value="">Cualquier disponibilidad</option>
                            <option value="1" {{ request('available') === '1' ? 'selected' : '' }}>Disponibles</option>
                            <option value="0" {{ request('available') === '0' ? 'selected' : '' }}>No disponibles</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" id="agent-settings-filter-apply-btn" class="btn btn-primary w-100 mb-1">
                        Aplicar filtros
                    </button>
                    <button type="button" id="agent-settings-filter-clear-btn" class="btn btn-secondary w-100">
                        Limpiar
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Bulk toolbar flotante --}}
    <div id="bulk-toolbar" class="position-fixed bottom-0 start-50 translate-middle-x mb-4 d-none hd-bulk-toolbar">
        <button type="button" class="btn btn-primary shadow-lg px-4" data-bs-toggle="modal" data-bs-target="#bulk-modal">
            <span data-bulk-count>0</span> agente(s) seleccionado(s) &mdash; Aplicar acción
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
                    <p class="text-muted mb-3">Se aplicará la acción sobre <strong><span data-bulk-count>0</span> agente(s) seleccionado(s)</strong>.</p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Acción</label>
                        <select id="bulk-action-select" class="form-select">
                            <option value="">Seleccionar acción...</option>
                            <option value="available">Marcar como disponible</option>
                            <option value="unavailable">Marcar como no disponible</option>
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
$(document).ready(function () {
    @if(session('success'))
        toastr.success('{{ session('success') }}', 'Exito');
    @endif

    @if(session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif

    // Filter modal
    $('.select2-filter-modal').select2({ dropdownParent: $('#agent-settings-filter-modal'), width: '100%' });

    $('#agent-settings-filter-apply-btn').on('click', function () {
        $('#filter-available').val($('#modal-available').val());
        $('#agent-settings-filter-modal').modal('hide');
        $('#agent-settings-filter-form').submit();
    });

    $('#agent-settings-filter-clear-btn').on('click', function () {
        $('#modal-available').val(null).trigger('change');
    });

    // Bulk actions
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
        if (!ids.length) { toastr.warning('Selecciona al menos un agente.'); return; }

        $('#bulk-apply-btn').prop('disabled', true).text('Procesando...');

        $.ajax({
            url: '{{ route("settings.helpdesk.agent-settings.bulk-action") }}',
            method: 'POST',
            data: JSON.stringify({ action: action, ids: ids, _token: $('meta[name="csrf-token"]').attr('content') }),
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
