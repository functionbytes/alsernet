@extends('layouts.theme')

@section('title', 'Tags de Conversaciones')

@section('content')

    @include('core::components.card', ['title' => 'Tags de Conversaciones'])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <!-- System Settings Card -->
        <div class="card">
            <!-- Header Section -->
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Tags disponibles</h5>
                        <p class="small mb-0 text-muted">Organiza y categoriza tus conversaciones con etiquetas</p>
                    </div>
                    <div class="d-flex gap-2">
                        @if(request('search'))
                            <a href="{{ route('manager.helpdesk.backups.tickets.tags.index') }}" class="btn btn-secondary">
                                Limpiar búsqueda
                            </a>
                        @endif
                        <a href="{{ route('manager.helpdesk.backups.tickets.tags.create') }}" class="btn btn-primary">
                            Nuevo tag
                        </a>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title text-primary mb-2">
                                            Total
                                        </h6>
                                        <h4 class="mb-1 fw-bold">{{ $stats['total'] }}</h4>
                                        <small class="text-muted">Tags configurados</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title text-success mb-2">
                                            Activos
                                        </h6>
                                        <h4 class="mb-1 fw-bold">{{ $stats['active'] }}</h4>
                                        <small class="text-muted">Tags habilitados</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title text-warning mb-2">
                                            Inactivos
                                        </h6>
                                        <h4 class="mb-1 fw-bold">{{ $stats['inactive'] }}</h4>
                                        <small class="text-muted">Tags deshabilitados</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search Section -->
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('manager.helpdesk.backups.tickets.tags.index') }}">
                    <div class="row align-items-center g-2">
                        <div class="col-md-7">
                            <div class="input-group">
                                <span class="input-group-text bg-white">
                                    <i class="fas fa-search"></i>
                                </span>
                                <input type="search" name="search" class="form-control" placeholder="Buscar por nombre o descripción..." value="{{ request('search') }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select name="status" class="form-select">
                                <option value="">Todos los estados</option>
                                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Activos</option>
                                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactivos</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                Buscar
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Tags List -->
            <div class="card-body">
                @if($tags->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                            <tr>
                                <th width="25%">Nombre</th>
                                <th width="20%">Slug</th>
                                <th width="10%" class="text-center">Color</th>
                                <th width="25%">Descripción</th>
                                <th width="10%" class="text-center">Uso</th>
                                <th width="10%" class="text-center">Acciones</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($tags as $tag)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <strong>{{ $tag->name }}</strong>
                                            @if(!$tag->is_active)
                                                <span class="badge bg-warning-subtle text-warning">Inactivo</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <code class="bg-light px-2 py-1 rounded">{{ $tag->slug }}</code>
                                    </td>
                                    <td class="text-center">
                                        @if($tag->color)
                                            <div class="d-flex align-items-center justify-content-center gap-2">
                                                <div style="width: 24px; height: 24px; background-color: {{ $tag->color }}; border-radius: 4px; border: 1px solid #dee2e6;"></div>
                                                <small class="text-muted">{{ $tag->color }}</small>
                                            </div>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $tag->description ? Str::limit($tag->description, 60) : '-' }}</small>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-info-subtle text-info">
                                            {{ $tag->usage_count }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="fa-duotone fa-solid fa-ellipsis"></i>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('manager.helpdesk.backups.tickets.tags.edit', $tag->id) }}">
                                                        Editar
                                                    </a>
                                                </li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <form method="POST" action="{{ route('manager.helpdesk.backups.tickets.tags.destroy', $tag->id) }}"
                                                          onsubmit="return confirm('¿Estás seguro de eliminar este tag? Esta acción no se puede deshacer.')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="dropdown-item text-danger">
                                                            Eliminar
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
                        <div class="d-flex flex-column align-items-center">
                            <div class="round-48 rounded-circle bg-light-subtle text-muted mb-3 d-flex align-items-center justify-content-center">
                                <i class="fas fa-tags fs-7"></i>
                            </div>
                            <h6 class="mb-1">No hay tags para mostrar</h6>
                            <p class="text-muted mb-3">
                                @if(request('search'))
                                    No se encontraron resultados para "{{ request('search') }}"
                                @else
                                    Crea tu primer tag para organizar las conversaciones
                                @endif
                            </p>
                            @if(!request('search'))
                                <a href="{{ route('manager.helpdesk.backups.tickets.tags.create') }}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-plus"></i> Crear Primer Tag
                                </a>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            <!-- Pagination -->
            @if($tags->hasPages())
                <div class="card-footer bg-white border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted">
                            Mostrando <strong>{{ $tags->firstItem() }}</strong> a <strong>{{ $tags->lastItem() }}</strong>
                            de <strong>{{ $tags->total() }}</strong> tags
                        </div>
                        <nav aria-label="Page navigation">
                            {{ $tags->links() }}
                        </nav>
                    </div>
                </div>
            @endif
        </div>
    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    @if (session('success'))
        toastr.success('{{ session('success') }}', 'Éxito');
    @endif

    @if (session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif
});
</script>
@endpush
