@extends('layouts.theme')

@section('title', 'Drip Campaigns')

@section('content')

    @include('core::components.alerts')

    <div class="card">

        {{-- Header --}}
        <div class="card-header p-4 border-bottom border-light">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1 fw-bold">Drip Campaigns</h5>
                    <p class="small mb-0 text-muted">Secuencias automatizadas de mensajes que se envian segun disparadores configurados</p>
                </div>
                @can('helpdesk.drip-campaigns.manage')
                    <a href="{{ route('settings.helpdesk.drip-campaigns.create') }}" class="btn btn-primary">
                        Nueva campaña
                    </a>
                @endcan
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
                            <small class="text-muted">Campañas configuradas</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-light-secondary stat-card h-100">
                        <div class="card-body">
                            <h6 class="card-title text-success mb-2">Activas</h6>
                            <h4 class="mb-1 fw-bold">{{ $stats['active'] }}</h4>
                            <small class="text-muted">Campañas en ejecucion</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-light-secondary stat-card h-100">
                        <div class="card-body">
                            <h6 class="card-title text-warning mb-2">Ejecuciones</h6>
                            <h4 class="mb-1 fw-bold">{{ number_format($stats['executions']) }}</h4>
                            <small class="text-muted">Total ejecuciones registradas</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Search --}}
        <div class="card-body border-bottom">
            <form method="GET" action="{{ route('settings.helpdesk.drip-campaigns.index') }}">
                <div class="row align-items-center g-2">
                    <div class="col-md-5">
                        <div class="input-group">
                            <span class="input-group-text bg-white">
                                <i class="fas fa-search"></i>
                            </span>
                            <input type="search" name="search" class="form-control"
                                placeholder="Buscar por nombre o descripcion..."
                                value="{{ request('search') }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select name="trigger_type" class="form-select">
                            <option value="">Todos los disparadores</option>
                            @foreach(\Modules\Helpdesk\Models\Campaigns\DripCampaign::TRIGGER_TYPES as $key => $label)
                                <option value="{{ $key }}" @selected(request('trigger_type') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="status" class="form-select">
                            <option value="">Cualquier estado</option>
                            <option value="active" @selected(request('status') === 'active')>Activas</option>
                            <option value="inactive" @selected(request('status') === 'inactive')>Inactivas</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-filter"></i> Filtrar
                        </button>
                    </div>
                </div>
            </form>
        </div>

        {{-- Table --}}
        <div class="card-body">
            @if($campaigns->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Nombre</th>
                                <th>Disparador</th>
                                <th class="text-center">Pasos</th>
                                <th class="text-center">Ejecuciones</th>
                                <th class="text-center">Estado</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($campaigns as $campaign)
                                <tr>
                                    <td>
                                        <strong>{{ $campaign->name }}</strong>
                                        @if($campaign->description)
                                            <div>
                                                <small class="text-muted">{{ Str::limit($campaign->description, 60) }}</small>
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-info-subtle text-info">
                                            {{ \Modules\Helpdesk\Models\Campaigns\DripCampaign::TRIGGER_TYPES[$campaign->trigger_type] ?? $campaign->trigger_type }}
                                        </span>
                                        @if($campaign->trigger_value)
                                            <div>
                                                <small class="text-muted">{{ $campaign->trigger_value }}</small>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="fw-semibold">{{ $campaign->steps_count }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="fw-semibold">{{ number_format($campaign->executions_count) }}</span>
                                    </td>
                                    <td class="text-center">
                                        @if($campaign->is_active)
                                            <span class="badge bg-success-subtle text-success">Activa</span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary">Inactiva</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="fas fa-ellipsis-vertical"></i>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                @can('helpdesk.drip-campaigns.manage')
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('settings.helpdesk.drip-campaigns.edit', $campaign) }}">
                                                            Editar
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <button class="dropdown-item btn-toggle"
                                                            data-url="{{ route('settings.helpdesk.drip-campaigns.toggle', $campaign) }}"
                                                            data-name="{{ $campaign->name }}"
                                                            data-active="{{ $campaign->is_active ? '1' : '0' }}">
                                                            {{ $campaign->is_active ? 'Desactivar' : 'Activar' }}
                                                        </button>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <button class="dropdown-item btn-delete"
                                                            data-id="{{ $campaign->id }}"
                                                            data-url="{{ route('settings.helpdesk.drip-campaigns.destroy', $campaign) }}"
                                                            data-name="{{ $campaign->name }}">
                                                            Eliminar
                                                        </button>
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
                            <i class="fas fa-paper-plane fs-7"></i>
                        </div>
                        <h6 class="mb-1">No hay drip campaigns</h6>
                        <p class="text-muted mb-3">
                            @if(request('search') || request('trigger_type') || request('status'))
                                No se encontraron resultados para los filtros aplicados
                            @else
                                Crea tu primera campaña drip para automatizar el seguimiento de clientes
                            @endif
                        </p>
                        @if(! request('search') && ! request('trigger_type') && ! request('status'))
                            @can('helpdesk.drip-campaigns.manage')
                                <a href="{{ route('settings.helpdesk.drip-campaigns.create') }}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-plus"></i> Crear primera campaña
                                </a>
                            @endcan
                        @endif
                    </div>
                </div>
            @endif
        </div>

        @if($campaigns->hasPages())
            <div class="card-footer bg-white border-top">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="text-muted">
                        Mostrando <strong>{{ $campaigns->firstItem() }}</strong> a <strong>{{ $campaigns->lastItem() }}</strong>
                        de <strong>{{ $campaigns->total() }}</strong> campañas
                    </div>
                    {{ $campaigns->appends(request()->input())->links() }}
                </div>
            </div>
        @endif

    </div>

    {{-- Toggle form --}}
    <form id="toggleForm" method="POST" class="d-none">
        @csrf
        @method('POST')
    </form>

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

    $(document).on('click', '.btn-toggle', function () {
        const url = $(this).data('url');
        $('#toggleForm').attr('action', url).submit();
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
