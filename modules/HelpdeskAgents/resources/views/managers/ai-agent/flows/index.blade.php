@extends('layouts.theme')

@section('title', 'Flujos de IA')

@section('content')

    @include('core::components.card', ['title' => 'Flujos de IA'])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">

            {{-- Header --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Flujos de agente IA</h5>
                        <p class="small mb-0 text-muted">Secuencias de pasos automatizados que el agente ejecuta en conversaciones</p>
                    </div>
                    <div class="ms-auto">
                        <a href="{{ route('manager.helpdesk.ai.flows.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> Nuevo flujo
                        </a>
                    </div>
                </div>
            </div>

            {{-- Stats --}}
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-6 col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Total</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($flows->total()) }}</h4>
                                <small class="text-muted">Flujos registrados</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Publicados</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['published']) }}</h4>
                                <small class="text-muted">Activos y funcionales</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Borradores</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['draft']) }}</h4>
                                <small class="text-muted">Aun no publicados</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Archivados</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['archived']) }}</h4>
                                <small class="text-muted">Ocultos del uso</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Search --}}
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('manager.helpdesk.ai.flows.index') }}" id="filterForm">
                    <div class="d-flex gap-2 align-items-center">
                        <div class="flex-fill">
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-1">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="search" name="search" class="form-control border-start-0 ps-0"
                                       placeholder="Buscar por nombre..."
                                       value="{{ request('search') }}">
                            </div>
                        </div>
                        <select name="status" class="form-select w-auto flex-shrink-0">
                            <option value="">Todos los estados</option>
                            @foreach ($statuses as $key => $label)
                                <option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        <select name="trigger_type" class="form-select w-auto flex-shrink-0">
                            <option value="">Todos los triggers</option>
                            @foreach ($triggers as $key => $label)
                                <option value="{{ $key }}" {{ request('trigger_type') === $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn btn-primary flex-shrink-0">
                            <i class="fas fa-search"></i>
                        </button>
                        @if(request('search') || request('status') || request('trigger_type'))
                            <a href="{{ route('manager.helpdesk.ai.flows.index') }}"
                               class="btn btn-outline-secondary flex-shrink-0" title="Limpiar">
                                <i class="fas fa-times"></i>
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            {{-- Table --}}
            <div class="card-body">
                @if($flows->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Nombre</th>
                                    <th>Trigger</th>
                                    <th>Version</th>
                                    <th>Estado</th>
                                    <th>Actualizado</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($flows as $flow)
                                    <tr>
                                        <td>
                                            <span class="fw-semibold">{{ $flow->name }}</span>
                                            @if($flow->description)
                                                <div class="small text-muted">{{ Str::limit($flow->description, 60) }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-info-subtle text-info">
                                                {{ $triggers[$flow->trigger] ?? $flow->trigger }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="text-muted small">v{{ $flow->version ?? '1' }}</span>
                                        </td>
                                        <td>
                                            @php
                                                $statusColor = match($flow->status) {
                                                    'published' => 'success',
                                                    'archived'  => 'secondary',
                                                    default     => 'warning',
                                                };
                                            @endphp
                                            <span class="badge bg-{{ $statusColor }}-subtle text-{{ $statusColor }}">
                                                {{ $statuses[$flow->status] ?? $flow->status }}
                                            </span>
                                        </td>
                                        <td>
                                            <small class="text-muted">{{ $flow->updated_at->diffForHumans() }}</small>
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <a href="#" class="text-muted" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('manager.helpdesk.ai.flows.edit', $flow) }}">
                                                            Editar
                                                        </a>
                                                    </li>
                                                    @if($flow->status === 'draft')
                                                        <li>
                                                            <form action="{{ route('manager.helpdesk.ai.flows.publish', $flow) }}" method="POST">
                                                                @csrf
                                                                <button type="submit" class="dropdown-item">Publicar</button>
                                                            </form>
                                                        </li>
                                                    @endif
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('manager.helpdesk.ai.flows.duplicate', $flow) }}">
                                                            Duplicar
                                                        </a>
                                                    </li>
                                                    @if($flow->status !== 'archived')
                                                        <li>
                                                            <form action="{{ route('manager.helpdesk.ai.flows.archive', $flow) }}" method="POST">
                                                                @csrf
                                                                <button type="submit" class="dropdown-item">Archivar</button>
                                                            </form>
                                                        </li>
                                                    @endif
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <a class="dropdown-item delete-btn" href="#"
                                                           data-bs-toggle="modal"
                                                           data-bs-target="#delete-modal"
                                                           data-url="{{ route('manager.helpdesk.ai.flows.destroy', $flow) }}"
                                                           data-title="Eliminar flujo: {{ $flow->name }}">
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
                        <i class="fas fa-diagram-project fa-3x mb-3 text-muted opacity-50"></i>
                        <h5 class="fw-bold mb-2">
                            @if(request('search') || request('status') || request('trigger_type'))
                                No se encontraron resultados
                            @else
                                No hay flujos configurados
                            @endif
                        </h5>
                        <p class="text-muted mb-4">
                            @if(request('search') || request('status') || request('trigger'))
                                Prueba ajustando los filtros de busqueda
                            @else
                                Crea tu primer flujo para automatizar conversaciones del agente IA
                            @endif
                        </p>
                        @if(request('search') || request('status') || request('trigger'))
                            <a href="{{ route('manager.helpdesk.ai.flows.index') }}" class="btn btn-secondary">Limpiar filtros</a>
                        @else
                            <a href="{{ route('manager.helpdesk.ai.flows.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i> Nuevo flujo
                            </a>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Pagination --}}
            @if($flows->hasPages())
                <div class="card-footer bg-white border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted small">
                            Mostrando {{ $flows->firstItem() }} - {{ $flows->lastItem() }} de {{ $flows->total() }}
                        </div>
                        <div>
                            {{ $flows->appends(request()->input())->links() }}
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
        toastr.success(@json(session('success')), 'Exito');
    @endif
    @if(session('error'))
        toastr.error(@json(session('error')), 'Error');
    @endif

    $(document).on('click', '.delete-btn', function () {
        $('#delete-modal .modal-title').text($(this).data('title'));
        $('#delete-form').attr('action', $(this).data('url'));
    });
});
</script>
@endpush
