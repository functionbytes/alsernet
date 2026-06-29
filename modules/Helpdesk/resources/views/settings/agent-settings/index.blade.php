@extends('layouts.theme')

@section('title', 'Configuracion de agentes')

@push('styles')
<style>.hd-avatar-sm { width: 36px; height: 36px; font-size: 14px; font-weight: 600; }</style>
@endpush

@section('content')

    @include('core::components.alerts')

    <div class="card">

        {{-- Header --}}
        <div class="card-header p-4 border-bottom border-light">
            <div>
                <h5 class="mb-1 fw-bold">Configuracion de agentes</h5>
                <p class="small mb-0 text-muted">Gestiona la disponibilidad, limites de conversaciones y habilidades de cada agente</p>
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
                            <h6 class="card-title text-success mb-2">Disponibles</h6>
                            <h4 class="mb-1 fw-bold">{{ $stats['available'] }}</h4>
                            <small class="text-muted">Listos para recibir conversaciones</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-light-secondary stat-card h-100">
                        <div class="card-body">
                            <h6 class="card-title text-warning mb-2">En vacaciones</h6>
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

        {{-- Search --}}
        <div class="card-body border-bottom">
            <form method="GET" action="{{ route('settings.helpdesk.agent-settings.index') }}">
                <div class="row align-items-center g-2">
                    <div class="col-md-7">
                        <div class="input-group">
                            <span class="input-group-text bg-white">
                                <i class="fas fa-search"></i>
                            </span>
                            <input type="search" name="search" class="form-control"
                                placeholder="Buscar por nombre o email..."
                                value="{{ request('search') }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select name="available" class="form-select">
                            <option value="">Cualquier disponibilidad</option>
                            <option value="1" @selected(request('available') === '1')>Disponibles</option>
                            <option value="0" @selected(request('available') === '0')>No disponibles</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-filter"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>

        {{-- Table --}}
        <div class="card-body">
            @if($agents->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Agente</th>
                                <th>Rol</th>
                                <th class="text-center">Disponible</th>
                                <th class="text-center">Acepta conv.</th>
                                <th class="text-center">Max. abiertas</th>
                                <th>Vacaciones hasta</th>
                                <th>Habilidades</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($agents as $agent)
                                @php
                                    $settings = $settingsMap->get($agent->id);
                                    $agentSkills = $skillsMap->get($agent->id, collect());
                                @endphp
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="hd-avatar-sm avatar avatar-sm rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center flex-shrink-0">
                                                {{ strtoupper(substr($agent->firstname ?? $agent->name, 0, 1)) }}
                                            </div>
                                            <div>
                                                <div class="fw-semibold">{{ $agent->name }}</div>
                                                <small class="text-muted">{{ $agent->email }}</small>
                                            </div>
                                        </div>
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
                                            <span class="badge bg-danger-subtle text-danger">No disponible</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @php $accepts = $settings?->accepts_conversations ?? 'yes' @endphp
                                        @if($accepts === 'yes')
                                            <span class="badge bg-success-subtle text-success">Siempre</span>
                                        @elseif($accepts === 'no')
                                            <span class="badge bg-danger-subtle text-danger">Nunca</span>
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
                                    <td>
                                        @if($agentSkills->count() > 0)
                                            <div class="d-flex flex-wrap gap-1">
                                                @foreach($agentSkills->take(3) as $skill)
                                                    <span class="badge bg-info-subtle text-info">{{ $skill->skill_name }}</span>
                                                @endforeach
                                                @if($agentSkills->count() > 3)
                                                    <span class="badge bg-secondary-subtle text-secondary">+{{ $agentSkills->count() - 3 }}</span>
                                                @endif
                                            </div>
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

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    @if(session('success'))
        toastr.success('{{ session('success') }}', 'Exito');
    @endif

    @if(session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif
});
</script>
@endpush
