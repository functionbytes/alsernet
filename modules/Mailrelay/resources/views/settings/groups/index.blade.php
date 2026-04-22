@extends('layouts.theme')

@section('title', 'Grupos de suscriptores')

@push('css')
<style>
    .stats-card { color: white; border-radius: 0.5rem; padding: 1rem; margin-bottom: 1rem; }
    .stats-card h3 { font-size: 2rem; margin: 0; }
    .stats-card p { margin: 0; opacity: 0.9; }
    .badge-status { font-size: 0.875rem; padding: 0.35em 0.65em; }
</style>
@endpush

@section('content')
<div class="py-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h2 mb-3">
                <i class="fas fa-users"></i> Grupos de suscriptores
            </h1>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('mailrelay.settings.groups.create') }}" class="btn btn-primary">
                <i class="fas fa-plus-circle"></i> Nuevo grupo
            </a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stats-card bg-primary">
                <h3>{{ $stats['total'] ?? 0 }}</h3>
                <p>Total grupos</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card bg-success">
                <h3>{{ $stats['active'] ?? 0 }}</h3>
                <p>Activos</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card bg-info">
                <h3>{{ $stats['total_subscribers'] ?? 0 }}</h3>
                <p>Total suscriptores</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card bg-secondary">
                <h3>{{ $stats['avg_per_group'] ?? 0 }}</h3>
                <p>Promedio por grupo</p>
            </div>
        </div>
    </div>

    <!-- Search Section -->
    <div class="card mb-3 p-3">
        <form method="GET" action="{{ route('mailrelay.settings.groups.index') }}" class="row g-3">
            <div class="col-md-10">
                <label for="search" class="form-label">
                    <i class="fas fa-search"></i> Buscar
                </label>
                <input
                    type="text"
                    class="form-control"
                    id="search"
                    name="search"
                    placeholder="Buscar por nombre o descripción..."
                    value="{{ request('search') }}"
                >
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search"></i> Buscar
                </button>
            </div>
        </form>

        @if(request('search'))
        <div class="mt-3">
            <a href="{{ route('mailrelay.settings.groups.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-times-circle"></i> Limpiar búsqueda
            </a>
        </div>
        @endif
    </div>

    <!-- Alerts -->
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <!-- Groups Table -->
    <div class="card shadow-sm">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0">
                <i class="fas fa-table"></i> Listado de grupos
                <span class="badge bg-secondary">{{ $groups->total() }} resultados</span>
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Nombre</th>
                            <th>Descripción</th>
                            <th>Suscriptores</th>
                            <th>Estado</th>
                            <th>Sincronizado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($groups as $group)
                        <tr>
                            <td>
                                <strong>{{ $group->name }}</strong>
                            </td>
                            <td>
                                {{ Str::limit($group->description ?? '-', 60) }}
                            </td>
                            <td>
                                <span class="badge bg-info badge-status">
                                    <i class="fas fa-users"></i> {{ $group->subscribers_count ?? 0 }}
                                </span>
                            </td>
                            <td>
                                <form action="{{ route('mailrelay.settings.groups.toggle', $group) }}"
                                      method="POST"
                                      class="d-inline"
                                      onchange="this.submit()">
                                    @csrf
                                    @method('PATCH')
                                    <select class="form-select form-select-sm" name="active">
                                        <option value="1" {{ $group->active ? 'selected' : '' }}>Activado</option>
                                        <option value="0" {{ !$group->active ? 'selected' : '' }}>Desactivado</option>
                                    </select>
                                </form>
                            </td>
                            <td>
                                @if($group->mailrelay_group_id)
                                    <span class="badge badge-status bg-success">
                                        <i class="fas fa-cloud-check"></i> Sincronizado
                                    </span>
                                    <small class="d-block text-muted">
                                        ID: {{ $group->mailrelay_group_id }}
                                    </small>
                                    @if($group->last_synced_at)
                                        <small class="d-block text-muted">
                                            {{ $group->last_synced_at->diffForHumans() }}
                                        </small>
                                    @endif
                                @else
                                    <span class="badge badge-status bg-warning text-dark">
                                        <i class="fas fa-cloud-slash"></i> No sincronizado
                                    </span>
                                @endif
                            </td>
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle"
                                            type="button"
                                            id="dropdownActions{{ $group->id }}"
                                            data-bs-toggle="dropdown"
                                            aria-expanded="false">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <ul class="dropdown-menu" aria-labelledby="dropdownActions{{ $group->id }}">
                                        <li>
                                            <a class="dropdown-item" href="{{ route('mailrelay.settings.groups.edit', $group) }}">
                                                <i class="fas fa-edit"></i> Editar
                                            </a>
                                        </li>
                                        <li>
                                            <form action="{{ route('mailrelay.settings.groups.sync', $group) }}"
                                                  method="POST"
                                                  class="d-inline">
                                                @csrf
                                                <button type="submit" class="dropdown-item">
                                                    <i class="fas fa-sync"></i> Sincronizar
                                                </button>
                                            </form>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <button type="button"
                                                    class="dropdown-item"
                                                    onclick="confirmDelete('{{ $group->id }}')">
                                                <i class="fas fa-trash"></i> Eliminar
                                            </button>
                                        </li>
                                    </ul>
                                </div>

                                <form id="delete-form-{{ $group->id }}"
                                      action="{{ route('mailrelay.settings.groups.destroy', $group) }}"
                                      method="POST"
                                      class="d-none">
                                    @csrf
                                    @method('DELETE')
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <i class="fas fa-users fs-1 text-muted"></i>
                                <p class="text-muted mt-3">No se encontraron grupos de suscriptores.</p>
                                <a href="{{ route('mailrelay.settings.groups.create') }}" class="btn btn-primary">
                                    <i class="fas fa-plus-circle"></i> Crear tu primer grupo
                                </a>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($groups->hasPages())
        <div class="card-footer bg-white">
            {{ $groups->links('pagination::bootstrap-5') }}
        </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
    function confirmDelete(groupId) {
        if (window.confirm('¿Estás seguro de que deseas eliminar este grupo? Esta acción no se puede deshacer.')) {
            document.getElementById('delete-form-' + groupId).submit();
        }
    }
</script>
@endpush
