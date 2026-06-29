@extends('layouts.theme')

@section('title', 'Reglas de automatización')

@section('page_header')
    @include('core::components.card', ['title' => 'Reglas de automatización'])
@endsection

@section('content')

    <div class="widget-content searchable-container list">
        @include('core::components.alerts')

        <div class="card">

            {{-- Header --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Reglas configuradas</h5>
                        <p class="small mb-0 text-muted">Automatiza acciones cuando ocurren eventos en las conversaciones</p>
                    </div>
                    <div class="ms-auto">
                        <div class="btn-group">
                            <button type="button" class="btn bg-primary-subtle text-primary dropdown-toggle"
                                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                Acciones
                            </button>
                            <div class="dropdown-menu dropdown-menu-end">
                                <a class="dropdown-item" href="{{ route('settings.helpdesk.rules.create') }}">Nueva regla</a>
                            </div>
                        </div>
                    </div>
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
                <form class="form-search" action="{{ route('settings.helpdesk.rules.index') }}" method="GET">
                    <div class="d-flex flex-column flex-lg-row gap-3 align-items-stretch">
                        <div class="flex-fill">
                            <select name="event_name" class="form-select">
                                <option value="">Todos los eventos</option>
                                @foreach(\Modules\Helpdesk\Models\AutomationRule::EVENTS as $key => $label)
                                    <option value="{{ $key }}" @selected(request('event_name') === $key)>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex-fill">
                            <select name="is_active" class="form-select">
                                <option value="">Todos los estados</option>
                                <option value="1" @selected(request('is_active') === '1')>Activas</option>
                                <option value="0" @selected(request('is_active') === '0')>Inactivas</option>
                            </select>
                        </div>
                        <div class="d-flex gap-2 flex-shrink-0">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i>
                            </button>
                            @if(request()->hasAny(['event_name', 'is_active']))
                                <a href="{{ route('settings.helpdesk.rules.index') }}" class="btn btn-outline-secondary" title="Limpiar filtros">
                                    <i class="fas fa-times"></i>
                                </a>
                            @endif
                        </div>
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
                                            <span class="badge bg-light text-dark border">
                                                {{ \Modules\Helpdesk\Models\AutomationRule::EVENTS[$rule->trigger_event] ?? $rule->trigger_event }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            @php $condCount = count($rule->conditions ?? []); @endphp
                                            @if($condCount > 0)
                                                <span class="fw-semibold">{{ $condCount }}</span>
                                            @else
                                                <span class="text-muted small">Ninguna</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @php $actCount = count($rule->actions ?? []); @endphp
                                            @if($actCount > 0)
                                                <span class="fw-semibold">{{ $actCount }}</span>
                                            @else
                                                <span class="text-muted small">Ninguna</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <button class="btn btn-sm toggle-rule {{ $rule->is_active ? 'btn-success' : 'btn-secondary' }}"
                                                data-id="{{ $rule->id }}"
                                                data-url="{{ route('settings.helpdesk.rules.toggle', $rule) }}"
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
                                                        <a class="dropdown-item" href="{{ route('settings.helpdesk.rules.edit', $rule) }}">
                                                            Editar
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <button class="dropdown-item btn-delete"
                                                            data-id="{{ $rule->id }}"
                                                            data-url="{{ route('settings.helpdesk.rules.destroy', $rule) }}"
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
                        <i class="fas fa-robot fa-4x text-muted opacity-50 mb-3"></i>
                        <h5 class="text-muted mb-2">No hay reglas de automatización</h5>
                        <p class="text-muted mb-0">
                            @if(request()->hasAny(['event_name', 'is_active']))
                                No se encontraron resultados con los filtros aplicados
                            @else
                                Crea tu primera regla para automatizar acciones en conversaciones
                            @endif
                        </p>
                        @unless(request()->hasAny(['event_name', 'is_active']))
                            <div class="mt-3">
                                <a href="{{ route('settings.helpdesk.rules.create') }}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-plus"></i> Crear primera regla
                                </a>
                            </div>
                        @endunless
                    </div>
                @endif
            </div>

            @if($rules->hasPages())
                <div class="card-footer">{{ $rules->appends(request()->input())->links() }}</div>
            @endif

        </div>
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
        $.ajax({
            url: btn.data('url'),
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
