@extends('layouts.theme')

@section('title', 'Reglas de automatizacion')

@section('content')

    @include('core::components.alerts')

    <div class="card">

            {{-- Header --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Reglas configuradas</h5>
                        <p class="small mb-0 text-muted">Automatiza acciones cuando ocurren eventos en las conversaciones</p>
                    </div>
                    <a href="{{ route('settings.helpdesk.automation-rules.create') }}" class="btn btn-primary">
                        Nueva regla
                    </a>
                </div>
            </div>

            {{-- Stats --}}
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <h6 class="card-title text-primary mb-2">Total</h6>
                                <h4 class="mb-1 fw-bold">{{ $stats['total'] }}</h4>
                                <small class="text-muted">Reglas configuradas</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <h6 class="card-title text-success mb-2">Activas</h6>
                                <h4 class="mb-1 fw-bold">{{ $stats['active'] }}</h4>
                                <small class="text-muted">Reglas habilitadas</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <h6 class="card-title text-warning mb-2">Inactivas</h6>
                                <h4 class="mb-1 fw-bold">{{ $stats['inactive'] }}</h4>
                                <small class="text-muted">Reglas deshabilitadas</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Filters --}}
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('settings.helpdesk.automation-rules.index') }}">
                    <div class="row align-items-center g-2">
                        <div class="col-md-4">
                            <select name="event_name" class="form-select">
                                <option value="">Todos los eventos</option>
                                @foreach(\Modules\Helpdesk\Models\AutomationRule::EVENTS as $key => $label)
                                    <option value="{{ $key }}" @selected(request('event_name') === $key)>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select name="is_active" class="form-select">
                                <option value="">Todos los estados</option>
                                <option value="1" @selected(request('is_active') === '1')>Activas</option>
                                <option value="0" @selected(request('is_active') === '0')>Inactivas</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                        </div>
                        @if(request()->hasAny(['event_name', 'is_active']))
                            <div class="col-md-2">
                                <a href="{{ route('settings.helpdesk.automation-rules.index') }}" class="btn btn-light w-100">
                                    Limpiar
                                </a>
                            </div>
                        @endif
                    </div>
                </form>
            </div>

            {{-- Table --}}
            <div class="card-body">
                @if($rules->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Nombre</th>
                                    <th>Evento</th>
                                    <th class="text-center">Condiciones</th>
                                    <th class="text-center">Acciones</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-center">Disparos</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($rules as $rule)
                                    <tr>
                                        <td>
                                            <strong>{{ $rule->name }}</strong>
                                            @if($rule->description)
                                                <div>
                                                    <small class="text-muted">{{ Str::limit($rule->description, 60) }}</small>
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-primary-subtle text-primary">
                                                {{ \Modules\Helpdesk\Models\AutomationRule::EVENTS[$rule->trigger_event] ?? $rule->trigger_event }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            @php $condCount = count($rule->conditions ?? []); @endphp
                                            @if($condCount > 0)
                                                <span class="badge bg-secondary-subtle text-secondary">{{ $condCount }}</span>
                                            @else
                                                <span class="text-muted small">Ninguna</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @php $actCount = count($rule->actions ?? []); @endphp
                                            @if($actCount > 0)
                                                <span class="badge bg-secondary-subtle text-secondary">{{ $actCount }}</span>
                                            @else
                                                <span class="text-muted small">Ninguna</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <button class="btn btn-sm toggle-rule
                                                {{ $rule->is_active ? 'btn-success' : 'btn-secondary' }}"
                                                data-id="{{ $rule->id }}"
                                                data-url="{{ route('settings.helpdesk.automation-rules.toggle', $rule) }}"
                                                title="{{ $rule->is_active ? 'Desactivar' : 'Activar' }}">
                                                {{ $rule->is_active ? 'Activa' : 'Inactiva' }}
                                            </button>
                                        </td>
                                        <td class="text-center">
                                            <span class="fw-semibold">{{ number_format($rule->run_count ?? 0) }}</span>
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('settings.helpdesk.automation-rules.edit', $rule) }}">
                                                            Editar
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <button class="dropdown-item btn-delete"
                                                            data-id="{{ $rule->id }}"
                                                            data-url="{{ route('settings.helpdesk.automation-rules.destroy', $rule) }}"
                                                            data-name="{{ $rule->name }}">
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
                                <i class="fas fa-robot fs-7"></i>
                            </div>
                            <h6 class="mb-1">No hay reglas de automatizacion</h6>
                            <p class="text-muted mb-3">
                                @if(request()->hasAny(['event_name', 'is_active']))
                                    No se encontraron resultados con los filtros aplicados
                                @else
                                    Crea tu primera regla para automatizar acciones en conversaciones
                                @endif
                            </p>
                            @unless(request()->hasAny(['event_name', 'is_active']))
                                <a href="{{ route('settings.helpdesk.automation-rules.create') }}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-plus"></i> Crear primera regla
                                </a>
                            @endunless
                        </div>
                    </div>
                @endif
            </div>

            @if($rules->hasPages())
                <div class="card-footer bg-white border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted">
                            Mostrando <strong>{{ $rules->firstItem() }}</strong> a <strong>{{ $rules->lastItem() }}</strong>
                            de <strong>{{ $rules->total() }}</strong> reglas
                        </div>
                        {{ $rules->appends(request()->input())->links() }}
                    </div>
                </div>
            @endif

    </div>

    @include('core::components.delete')

@endsection

@push('scripts')
<script>
$(document).ready(function () {

    $(document).on('click', '.btn-delete', function () {
        const url = $(this).data('url');
        const name = $(this).data('name');
        $('#deleteForm').attr('action', url);
        $('#deleteItemName').text(name);
        $('#deleteModal').modal('show');
    });

    $(document).on('click', '.toggle-rule', function () {
        const btn = $(this);
        const url = btn.data('url');

        $.ajax({
            url: url,
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'X-HTTP-Method-Override': 'PATCH',
            },
            success: function (response) {
                if (response.is_active) {
                    btn.removeClass('btn-secondary').addClass('btn-success').text('Activa');
                } else {
                    btn.removeClass('btn-success').addClass('btn-secondary').text('Inactiva');
                }
                toastr.success(response.message, 'Listo');
            },
            error: function () {
                toastr.error('No se pudo cambiar el estado.', 'Error');
            },
        });
    });

    @if(session('success'))
        toastr.success('{{ session('success') }}', 'Exito');
    @endif

    @if(session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif

});
</script>
@endpush
