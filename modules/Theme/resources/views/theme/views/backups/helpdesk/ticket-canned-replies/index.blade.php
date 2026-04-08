@extends('layouts.theme')

@section('title', 'Respuestas predefinidas')

@section('content')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-comment-dots me-2 text-primary"></i>
                Respuestas predefinidas
            </h4>
            <p class="text-muted mb-0">Respuestas rápidas reutilizables para los agentes</p>
        </div>
        <div class="d-flex gap-2">
            @if(request('search') || request('type') || request('category'))
                <a href="{{ route('manager.helpdesk.backups.tickets.canned-replies.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-times me-1"></i> Limpiar
                </a>
            @endif
            <a href="{{ route('manager.helpdesk.backups.tickets.canned-replies.create') }}" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i> Nueva respuesta
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Stats --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <p class="text-muted mb-1">Total</p>
                    <h4 class="fw-bold mb-0">{{ $stats['total'] }}</h4>
                    <small class="text-muted">respuestas</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <p class="text-primary small mb-1">Globales</p>
                    <h4 class="fw-bold mb-0">{{ $stats['global'] }}</h4>
                    <small class="text-muted">compartidas</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <p class="text-secondary small mb-1">Personales</p>
                    <h4 class="fw-bold mb-0">{{ $stats['personal'] }}</h4>
                    <small class="text-muted">propias</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <p class="text-success small mb-1">Activas</p>
                    <h4 class="fw-bold mb-0">{{ $stats['active'] }}</h4>
                    <small class="text-muted">habilitadas</small>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        {{-- Filters --}}
        <div class="card-body border-bottom">
            <form method="GET" action="{{ route('manager.helpdesk.backups.tickets.canned-replies.index') }}">
                <div class="row g-2 align-items-center">
                    <div class="col-md-5">
                        <div class="input-group">
                            <span class="input-group-text bg-white">
                                <i class="fas fa-search text-muted"></i>
                            </span>
                            <input type="search" name="search" class="form-control border-start-0"
                                   placeholder="Buscar por título, atajo o contenido..." value="{{ request('search') }}">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <select name="type" class="form-select">
                            <option value="">Todos los tipos</option>
                            <option value="global" {{ request('type') === 'global' ? 'selected' : '' }}>Globales</option>
                            <option value="personal" {{ request('type') === 'personal' ? 'selected' : '' }}>Personales</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="category" class="form-select">
                            <option value="">Todas las categorías</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ request('category') == $cat->id ? 'selected' : '' }}>
                                    {{ $cat->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                    </div>
                </div>
            </form>
        </div>

        {{-- Table --}}
        <div class="card-body p-0">
            @if($replies->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Título</th>
                                <th>Atajo</th>
                                <th>Vista previa</th>
                                <th class="text-center">Tipo</th>
                                <th class="text-center" width="80">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($replies as $reply)
                                <tr>
                                    <td class="align-middle">
                                        <strong>{{ $reply->title }}</strong>
                                        @if($reply->tags && count($reply->tags))
                                            <div class="mt-1">
                                                @foreach($reply->tags as $tag)
                                                    <span class="badge bg-light text-secondary border small">{{ $tag }}</span>
                                                @endforeach
                                            </div>
                                        @endif
                                    </td>
                                    <td class="align-middle">
                                        @if($reply->shortcut)
                                            <code class="bg-light px-2 py-1 rounded small">{{ $reply->shortcut }}</code>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="align-middle">
                                        <small class="text-muted">{{ Str::limit(strip_tags($reply->body), 100) }}</small>
                                    </td>
                                    <td class="text-center align-middle">
                                        @if($reply->is_global)
                                            <span class="badge bg-primary-subtle text-primary">Global</span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary">Personal</span>
                                        @endif
                                    </td>
                                    <td class="text-center align-middle">
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-light" data-bs-toggle="dropdown">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('manager.helpdesk.backups.tickets.canned-replies.edit', $reply->id) }}">
                                                        <i class="fas fa-pen me-2"></i> Editar
                                                    </a>
                                                </li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <form method="POST" action="{{ route('manager.helpdesk.backups.tickets.canned-replies.destroy', $reply->id) }}"
                                                          onsubmit="return confirm('¿Eliminar esta respuesta?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="dropdown-item text-danger">
                                                            <i class="fas fa-trash me-2"></i> Eliminar
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
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-comment-dots fa-3x text-muted mb-3"></i>
                    <h6 class="text-muted">
                        @if(request('search') || request('type') || request('category'))
                            Sin resultados para los filtros seleccionados
                        @else
                            No hay respuestas predefinidas
                        @endif
                    </h6>
                    @if(!request('search') && !request('type') && !request('category'))
                        <a href="{{ route('manager.helpdesk.backups.tickets.canned-replies.create') }}" class="btn btn-primary btn-sm mt-2">
                            <i class="fas fa-plus me-1"></i> Crear primera respuesta
                        </a>
                    @endif
                </div>
            @endif
        </div>

        @if($replies->hasPages())
            <div class="card-footer bg-white d-flex justify-content-between align-items-center">
                <small class="text-muted">
                    Mostrando {{ $replies->firstItem() }}–{{ $replies->lastItem() }} de {{ $replies->total() }}
                </small>
                {{ $replies->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>
</div>
@endsection
