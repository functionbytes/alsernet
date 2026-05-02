@extends('layouts.theme')

@section('title', 'Reglas de enrutamiento')

@section('page_header')
    @include('core::components.card', ['title' => 'Reglas de enrutamiento'])
@endsection

@section('content')

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">

            {{-- Header --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Reglas de enrutamiento automatico</h5>
                        <p class="small mb-0 text-muted">Asigna conversaciones automaticamente segun palabras clave del mensaje</p>
                    </div>
                    <div class="ms-auto">
                        <a href="{{ route('settings.helpdesk.routing-rules.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> Nueva regla
                        </a>
                    </div>
                </div>
            </div>

            {{-- Stats --}}
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-6 col-md-4">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Total</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['total']) }}</h4>
                                <small class="text-muted">Reglas configuradas</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Activas</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['active']) }}</h4>
                                <small class="text-muted">En funcionamiento</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Inactivas</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['inactive']) }}</h4>
                                <small class="text-muted">Deshabilitadas</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Search --}}
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('settings.helpdesk.routing-rules.index') }}">
                    <div class="d-flex gap-2 align-items-center">
                        <div class="flex-fill">
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-1">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="search" name="search" class="form-control border-start-0 ps-0"
                                       placeholder="Buscar por palabra clave..."
                                       value="{{ request('search') }}">
                            </div>
                        </div>
                        <select name="is_active" class="form-select w-auto flex-shrink-0">
                            <option value="">Todos los estados</option>
                            <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Activas</option>
                            <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Inactivas</option>
                        </select>
                        <button type="submit" class="btn btn-primary flex-shrink-0">
                            <i class="fas fa-search"></i>
                        </button>
                        @if(request()->hasAny(['search', 'is_active']))
                            <a href="{{ route('settings.helpdesk.routing-rules.index') }}"
                               class="btn btn-outline-secondary flex-shrink-0" title="Limpiar">
                                <i class="fas fa-times"></i>
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            {{-- Table --}}
            <div class="card-body">
                @if($rules->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle text-nowrap">
                            <thead class="table-light">
                                <tr>
                                    <th>Prioridad</th>
                                    <th>Palabra clave</th>
                                    <th>Tipo de coincidencia</th>
                                    <th>Asignar a</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($rules as $rule)
                                    @php
                                        $matchLabels = \Modules\Helpdesk\Models\RoutingRule::MATCH_TYPES;
                                    @endphp
                                    <tr>
                                        <td>
                                            <span class="badge bg-secondary-subtle text-secondary fw-bold">
                                                {{ $rule->priority ?? 0 }}
                                            </span>
                                        </td>
                                        <td>
                                            <code class="bg-light px-2 py-1 rounded small">{{ $rule->keyword }}</code>
                                        </td>
                                        <td>
                                            <small class="text-muted">{{ $matchLabels[$rule->match_type] ?? $rule->match_type }}</small>
                                        </td>
                                        <td>
                                            @if($rule->assign_to_user_id)
                                                <small class="text-muted">Agente #{{ $rule->assign_to_user_id }}</small>
                                            @elseif($rule->assign_to_team_id)
                                                <small class="text-muted">Equipo #{{ $rule->assign_to_team_id }}</small>
                                            @else
                                                <small class="text-muted">—</small>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span class="badge toggle-status {{ $rule->is_active ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}"
                                                  data-url="{{ route('settings.helpdesk.routing-rules.toggle', $rule->id) }}"
                                                  role="button" title="Clic para cambiar estado">
                                                {{ $rule->is_active ? 'Activa' : 'Inactiva' }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <a href="#" class="text-muted" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('settings.helpdesk.routing-rules.edit', $rule->id) }}">
                                                            Editar
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <a class="dropdown-item delete-btn" href="#"
                                                           data-bs-toggle="modal"
                                                           data-bs-target="#delete-modal"
                                                           data-url="{{ route('settings.helpdesk.routing-rules.destroy', $rule->id) }}"
                                                           data-title="Eliminar regla: {{ $rule->keyword }}">
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
                    <div class="text-center py-5">
                        <i class="fas fa-route fa-3x mb-3 text-muted opacity-50"></i>
                        <h5 class="fw-bold mb-2">
                            @if(request()->hasAny(['search', 'is_active']))
                                No se encontraron resultados
                            @else
                                No hay reglas de enrutamiento configuradas
                            @endif
                        </h5>
                        <p class="text-muted mb-4">
                            @if(request()->hasAny(['search', 'is_active']))
                                Intenta con otros filtros de busqueda
                            @else
                                Crea reglas para asignar conversaciones automaticamente segun palabras clave
                            @endif
                        </p>
                        @if(request()->hasAny(['search', 'is_active']))
                            <a href="{{ route('settings.helpdesk.routing-rules.index') }}" class="btn btn-secondary">Limpiar filtros</a>
                        @else
                            <a href="{{ route('settings.helpdesk.routing-rules.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i> Nueva regla
                            </a>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Pagination --}}
            @if($rules->hasPages())
                <div class="card-footer bg-white border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted small">
                            Mostrando {{ $rules->firstItem() }} - {{ $rules->lastItem() }} de {{ $rules->total() }}
                        </div>
                        <div>
                            {{ $rules->appends(request()->input())->links() }}
                        </div>
                    </div>
                </div>
            @endif

        </div>
    </div>

    @include('core::components.delete')

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    @if(session('success'))
    @endif
    @if(session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif

    $(document).on('click', '.delete-btn', function () {
        $('#delete-modal .modal-title').text($(this).data('title'));
        $('#delete-form').attr('action', $(this).data('url'));
    });

    $(document).on('click', '.toggle-status', function () {
        const $badge = $(this);
        $.ajax({
            url: $badge.data('url'),
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {
                if (response.is_active) {
                    $badge.removeClass('bg-secondary-subtle text-secondary').addClass('bg-success-subtle text-success').text('Activa');
                } else {
                    $badge.removeClass('bg-success-subtle text-success').addClass('bg-secondary-subtle text-secondary').text('Inactiva');
                }
            },
            error: function () {
                toastr.error('No se pudo cambiar el estado.', 'Error');
            }
        });
    });
});
</script>
@endpush
