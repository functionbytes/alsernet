@extends('layouts.theme')

@section('title', 'Workflows')

@section('content')

    @include('core::components.alerts')

    <div class="card">

        {{-- Header --}}
        <div class="card-header p-4 border-bottom border-light">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1 fw-bold">Workflows</h5>
                    <p class="small mb-0 text-muted">Automatiza acciones en base a eventos del sistema de conversaciones</p>
                </div>
                @can('helpdesk.workflows.manage')
                    <a href="{{ route('settings.helpdesk.workflows.create') }}" class="btn btn-primary">
                        Nuevo workflow
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
                            <small class="text-muted">Workflows configurados</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-light-secondary stat-card h-100">
                        <div class="card-body">
                            <h6 class="card-title text-success mb-2">Activos</h6>
                            <h4 class="mb-1 fw-bold">{{ $stats['active'] }}</h4>
                            <small class="text-muted">Workflows en ejecucion</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-light-secondary stat-card h-100">
                        <div class="card-body">
                            <h6 class="card-title text-warning mb-2">Ejecuciones</h6>
                            <h4 class="mb-1 fw-bold">{{ number_format($stats['total_runs']) }}</h4>
                            <small class="text-muted">Total de veces ejecutados</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Search --}}
        <div class="card-body border-bottom">
            <form method="GET" action="{{ route('settings.helpdesk.workflows.index') }}">
                <div class="row align-items-center g-2">
                    <div class="col-md-6">
                        <div class="input-group">
                            <span class="input-group-text bg-white">
                                <i class="fas fa-search"></i>
                            </span>
                            <input type="search" name="search" class="form-control"
                                placeholder="Buscar por nombre..."
                                value="{{ request('search') }}">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <select name="trigger_type" class="form-select">
                            <option value="">Todos los triggers</option>
                            @foreach(\Modules\Helpdesk\Models\Workflow::TRIGGER_TYPES as $key => $label)
                                <option value="{{ $key }}" @selected(request('trigger_type') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100" aria-label="Filtrar">
                            <i class="fas fa-filter"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>

        {{-- Table --}}
        <div class="card-body">
            @if($workflows->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">Nombre</th>
                                <th scope="col">Trigger</th>
                                <th scope="col" class="text-center">Nodos</th>
                                <th scope="col" class="text-center">Estado</th>
                                <th scope="col">Ultima ejecucion</th>
                                <th scope="col" class="text-center">Ejecuciones</th>
                                <th scope="col" class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($workflows as $workflow)
                                <tr>
                                    <td>
                                        <strong>{{ $workflow->name }}</strong>
                                        @if($workflow->description)
                                            <div>
                                                <small class="text-muted">{{ Str::limit($workflow->description, 60) }}</small>
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-primary-subtle text-primary">
                                            {{ \Modules\Helpdesk\Models\Workflow::TRIGGER_TYPES[$workflow->trigger_type] ?? $workflow->trigger_type }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="fw-semibold">{{ count($workflow->nodes ?? []) }}</span>
                                    </td>
                                    <td class="text-center">
                                        @if($workflow->is_active)
                                            <span class="badge bg-success-subtle text-success">Activo</span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary">Inactivo</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($workflow->last_run_at)
                                            <span class="small">{{ $workflow->last_run_at->diffForHumans() }}</span>
                                        @else
                                            <span class="text-muted small">Nunca</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="fw-semibold">{{ number_format($workflow->total_runs) }}</span>
                                    </td>
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="fas fa-ellipsis-vertical"></i>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                @can('helpdesk.workflows.manage')
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('settings.helpdesk.workflows.edit', $workflow) }}">
                                                            Editar
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <button class="dropdown-item btn-toggle"
                                                            data-url="{{ route('settings.helpdesk.workflows.toggle', $workflow) }}"
                                                            data-active="{{ $workflow->is_active ? '1' : '0' }}">
                                                            {{ $workflow->is_active ? 'Desactivar' : 'Activar' }}
                                                        </button>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <button class="dropdown-item btn-delete"
                                                            data-id="{{ $workflow->id }}"
                                                            data-url="{{ route('settings.helpdesk.workflows.destroy', $workflow) }}"
                                                            data-name="{{ $workflow->name }}">
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
                            <i class="fas fa-diagram-project fs-7"></i>
                        </div>
                        <h6 class="mb-1">No hay workflows configurados</h6>
                        <p class="text-muted mb-3">
                            @if(request('search') || request('trigger_type'))
                                No se encontraron resultados para los filtros aplicados
                            @else
                                Crea tu primer workflow para automatizar acciones en conversaciones
                            @endif
                        </p>
                        @if(! request('search') && ! request('trigger_type'))
                            @can('helpdesk.workflows.manage')
                                <a href="{{ route('settings.helpdesk.workflows.create') }}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-plus"></i> Crear primer workflow
                                </a>
                            @endcan
                        @endif
                    </div>
                </div>
            @endif
        </div>

        @if($workflows->hasPages())
            <div class="card-footer bg-white border-top">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="text-muted">
                        Mostrando <strong>{{ $workflows->firstItem() }}</strong> a <strong>{{ $workflows->lastItem() }}</strong>
                        de <strong>{{ $workflows->total() }}</strong> workflows
                    </div>
                    {{ $workflows->appends(request()->input())->links() }}
                </div>
            </div>
        @endif

    </div>

    @include('core::components.delete')

    {{-- Toggle form --}}
    <form id="toggleForm" method="POST">
        @csrf
        @method('POST')
    </form>

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
