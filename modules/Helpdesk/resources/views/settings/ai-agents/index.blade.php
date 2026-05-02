@extends('layouts.theme')

@section('title', 'Agentes IA')

@section('content')

    @include('core::components.alerts')

    <div class="card">

        {{-- Header --}}
        <div class="card-header p-4 border-bottom border-light">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1 fw-bold">Agentes IA</h5>
                    <p class="small mb-0 text-muted">Configura agentes de inteligencia artificial para atender conversaciones de forma autonoma</p>
                </div>
                @can('helpdesk.ai-agents.manage')
                    <a href="{{ route('settings.helpdesk.ai-agents.create') }}" class="btn btn-primary">
                        Nuevo agente IA
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
                            <small class="text-muted">Agentes configurados</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-light-secondary stat-card h-100">
                        <div class="card-body">
                            <h6 class="card-title text-success mb-2">Activos</h6>
                            <h4 class="mb-1 fw-bold">{{ $stats['active'] }}</h4>
                            <small class="text-muted">Atendiendo conversaciones</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-light-secondary stat-card h-100">
                        <div class="card-body">
                            <h6 class="card-title text-secondary mb-2">Inactivos</h6>
                            <h4 class="mb-1 fw-bold">{{ $stats['inactive'] }}</h4>
                            <small class="text-muted">Pausados o en configuracion</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Search --}}
        <div class="card-body border-bottom">
            <form method="GET" action="{{ route('settings.helpdesk.ai-agents.index') }}">
                <div class="row align-items-center g-2">
                    <div class="col-md-7">
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
                        <select name="status" class="form-select">
                            <option value="">Cualquier estado</option>
                            <option value="active" @selected(request('status') === 'active')>Activos</option>
                            <option value="inactive" @selected(request('status') === 'inactive')>Inactivos</option>
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
            @if($aiAgents->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Nombre</th>
                                <th>Canales</th>
                                <th class="text-center">Max. mensajes</th>
                                <th class="text-center">Estado</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($aiAgents as $aiAgent)
                                <tr>
                                    <td>
                                        <strong>{{ $aiAgent->name }}</strong>
                                        @if($aiAgent->description)
                                            <div>
                                                <small class="text-muted">{{ Str::limit($aiAgent->description, 70) }}</small>
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        @if($aiAgent->enabled_channels && count($aiAgent->enabled_channels) > 0)
                                            <div class="d-flex flex-wrap gap-1">
                                                @foreach($aiAgent->enabled_channels as $channel)
                                                    <span class="badge bg-primary-subtle text-primary">{{ $channel }}</span>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="text-muted small">Todos los canales</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="fw-semibold">{{ $aiAgent->max_messages_before_escalation }}</span>
                                    </td>
                                    <td class="text-center">
                                        @if($aiAgent->is_active)
                                            <span class="badge bg-success-subtle text-success">Activo</span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary">Inactivo</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="fas fa-ellipsis-vertical"></i>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                @can('helpdesk.ai-agents.manage')
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('settings.helpdesk.ai-agents.edit', $aiAgent) }}">
                                                            Editar
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <button class="dropdown-item btn-toggle"
                                                            data-id="{{ $aiAgent->id }}"
                                                            data-url="{{ route('settings.helpdesk.ai-agents.toggle', $aiAgent) }}"
                                                            data-active="{{ $aiAgent->is_active ? '1' : '0' }}">
                                                            {{ $aiAgent->is_active ? 'Desactivar' : 'Activar' }}
                                                        </button>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <button class="dropdown-item btn-delete"
                                                            data-id="{{ $aiAgent->id }}"
                                                            data-url="{{ route('settings.helpdesk.ai-agents.destroy', $aiAgent) }}"
                                                            data-name="{{ $aiAgent->name }}">
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
                            <i class="fas fa-robot fs-7"></i>
                        </div>
                        <h6 class="mb-1">No hay agentes IA configurados</h6>
                        <p class="text-muted mb-3">
                            @if(request('search') || request('status'))
                                No se encontraron resultados para los filtros aplicados
                            @else
                                Crea tu primer agente IA para automatizar la atencion de conversaciones
                            @endif
                        </p>
                        @if(! request('search') && ! request('status'))
                            @can('helpdesk.ai-agents.manage')
                                <a href="{{ route('settings.helpdesk.ai-agents.create') }}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-plus"></i> Crear primer agente IA
                                </a>
                            @endcan
                        @endif
                    </div>
                </div>
            @endif
        </div>

        @if($aiAgents->hasPages())
            <div class="card-footer bg-white border-top">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="text-muted">
                        Mostrando <strong>{{ $aiAgents->firstItem() }}</strong> a <strong>{{ $aiAgents->lastItem() }}</strong>
                        de <strong>{{ $aiAgents->total() }}</strong> agentes
                    </div>
                    {{ $aiAgents->appends(request()->input())->links() }}
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

    $(document).on('click', '.btn-toggle', function () {
        const btn = $(this);
        const url = btn.data('url');

        $.ajax({
            url: url,
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (response) {
                setTimeout(() => location.reload(), 800);
            },
            error: function () {
                toastr.error('No se pudo cambiar el estado del agente.', 'Error');
            },
        });
    });

    @if(session('success'))
    @endif

    @if(session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif

});
</script>
@endpush
