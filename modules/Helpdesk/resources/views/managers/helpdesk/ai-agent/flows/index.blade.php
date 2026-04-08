@extends('layouts.theme')

@section('title', 'Flujos - Agente IA')

@section('content')
<div class="container-fluid">
    <!-- Breadcrumb -->
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <h4 class="fw-semibold mb-3">Flujos del Agente IA</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('manager.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('manager.helpdesk.conversations.index') }}">Helpdesk</a></li>
                    <li class="breadcrumb-item active">Flujos</li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Alerts -->
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fa fa-circle-check"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fa fa-circle-exclamation"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Filters & Create Button -->
    <div class="card mb-3">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <form method="GET" class="row g-2">
                        <div class="col-auto">
                            <input type="text" name="search" class="form-control form-control-sm"
                                placeholder="Buscar flujos..." value="{{ request('search') }}">
                        </div>
                        <div class="col-auto">
                            <select name="status" class="form-select form-select-sm">
                                <option value="">Todos los estados</option>
                                @foreach ($statuses as $key => $label)
                                    <option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-auto">
                            <select name="trigger" class="form-select form-select-sm">
                                <option value="">Todos los triggers</option>
                                @foreach ($triggers as $key => $label)
                                    <option value="{{ $key }}" {{ request('trigger') === $key ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-sm btn-outline-primary">
                                <i class="fa fa-magnifying-glass"></i> Filtrar
                            </button>
                            <a href="{{ route('manager.helpdesk.ai-agent.flows.index') }}" class="btn btn-sm btn-outline-secondary">
                                <i class="fa fa-xmark"></i> Limpiar
                            </a>
                        </div>
                    </form>
                </div>
                <div class="col-md-6 text-end">
                    <a href="{{ route('manager.helpdesk.ai-agent.flows.create') }}" class="btn btn-primary">
                        <i class="fa fa-plus"></i> Nuevo Flujo
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Flows Table -->
    <div class="card">
        <div class="table-responsive">
            @if ($flows->count() > 0)
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Nombre</th>
                            <th>Trigger</th>
                            <th>Estado</th>
                            <th>Nodos</th>
                            <th>Creado</th>
                            <th>Actualizado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($flows as $flow)
                            <tr>
                                <td>
                                    <strong>{{ $flow->name }}</strong>
                                    @if ($flow->description)
                                        <br>
                                        <small class="text-muted">{{ substr($flow->description, 0, 60) }}...</small>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-info">{{ $triggers[$flow->trigger] ?? $flow->trigger }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $flow->status === 'published' ? 'success' : ($flow->status === 'archived' ? 'secondary' : 'warning') }}">
                                        {{ $statuses[$flow->status] ?? $flow->status }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-light-secondary text-dark">{{ $flow->node_count }}</span>
                                </td>
                                <td>{{ $flow->created_at->format('d/m/Y') }}</td>
                                <td>{{ $flow->updated_at->diffForHumans() }}</td>
                                <td>
                                    <a href="{{ route('manager.helpdesk.ai-agent.flows.edit', $flow) }}" class="btn btn-sm btn-light-primary me-1" title="Editar">
                                        <i class="fa fa-pen-to-square"></i>
                                    </a>

                                    @if ($flow->status === 'draft')
                                        <form action="{{ route('manager.helpdesk.ai-agent.flows.publish', $flow) }}" method="POST" style="display: inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-light-success me-1" title="Publicar">
                                                <i class="fa fa-upload"></i>
                                            </button>
                                        </form>
                                    @endif

                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-sm btn-light dropdown-toggle" data-bs-toggle="dropdown">
                                            <i class="fa fa-ellipsis-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li>
                                                <a class="dropdown-item" href="{{ route('manager.helpdesk.ai-agent.flows.duplicate', $flow) }}">
                                                    <i class="fa fa-copy"></i> Duplicar
                                                </a>
                                            </li>
                                            @if ($flow->status !== 'archived')
                                                <li>
                                                    <form action="{{ route('manager.helpdesk.ai-agent.flows.archive', $flow) }}" method="POST" style="display: inline">
                                                        @csrf
                                                        <button type="submit" class="dropdown-item">
                                                            <i class="fa fa-box-archive"></i> Archivar
                                                        </button>
                                                    </form>
                                                </li>
                                            @endif
                                            <li>
                                                <hr class="dropdown-divider">
                                            </li>
                                            <li>
                                                <form action="{{ route('manager.helpdesk.ai-agent.flows.destroy', $flow) }}" method="POST"
                                                    onsubmit="return confirm('¿Estás seguro? Esta acción no se puede deshacer.')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="dropdown-item text-danger">
                                                        <i class="fa fa-trash"></i> Eliminar
                                                    </button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <!-- Pagination -->
                <div class="d-flex justify-content-between align-items-center p-3 border-top">
                    <small class="text-muted">Mostrando {{ $flows->from() }} a {{ $flows->to() }} de {{ $flows->total() }} flujos</small>
                    {{ $flows->appends(request()->query())->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fa fa-inbox" style="font-size: 48px; color: #ccc"></i>
                    <h5 class="mt-3 text-muted">No hay flujos disponibles</h5>
                    <p class="text-muted">Crea tu primer flujo para comenzar a automatizar conversaciones</p>
                    <a href="{{ route('manager.helpdesk.ai-agent.flows.create') }}" class="btn btn-primary">
                        <i class="fa fa-plus"></i> Crear Flujo
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
