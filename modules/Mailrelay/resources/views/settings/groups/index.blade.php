@extends('layouts.theme')

@section('title', 'Grupos de suscriptores')

@section('head')
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
    .stats-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 0.5rem;
        padding: 1rem;
        margin-bottom: 1rem;
    }
    .stats-card h3 {
        font-size: 2rem;
        margin: 0;
    }
    .stats-card p {
        margin: 0;
        opacity: 0.9;
    }
    .search-section {
        background-color: #fff;
        padding: 1.5rem;
        border-radius: 0.5rem;
        box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);
        margin-bottom: 1.5rem;
    }
    .table-hover tbody tr:hover {
        background-color: rgba(0, 123, 255, 0.05);
    }
    .badge-status {
        font-size: 0.875rem;
        padding: 0.35em 0.65em;
    }
    .form-switch .form-check-input {
        cursor: pointer;
    }
</style>
@endsection

@section('content')
<div class="container-fluid py-4">
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
            <div class="stats-card">
                <h3>{{ $stats['total'] ?? 0 }}</h3>
                <p><i class="fas fa-layer-group"></i> Total grupos</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
                <h3>{{ $stats['active'] ?? 0 }}</h3>
                <p><i class="fas fa-check-circle"></i> Activos</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                <h3>{{ $stats['total_subscribers'] ?? 0 }}</h3>
                <p><i class="fas fa-user-friends"></i> Total suscriptores</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                <h3>{{ $stats['avg_per_group'] ?? 0 }}</h3>
                <p><i class="fas fa-chart-line"></i> Promedio por grupo</p>
            </div>
        </div>
    </div>

    <!-- Search Section -->
    <div class="search-section">
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
                                    <div class="form-check form-switch">
                                        <input
                                            class="form-check-input"
                                            type="checkbox"
                                            id="active_{{ $group->id }}"
                                            name="active"
                                            value="1"
                                            {{ $group->active ? 'checked' : '' }}
                                        >
                                        <label class="form-check-label" for="active_{{ $group->id }}">
                                            {{ $group->active ? 'Activo' : 'Inactivo' }}
                                        </label>
                                    </div>
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
                                      style="display: none;">
                                    @csrf
                                    @method('DELETE')
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <i class="fas fa-users" style="font-size: 3rem; color: #ccc;"></i>
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

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function confirmDelete(groupId) {
        if (confirm('¿Estás seguro de que deseas eliminar este grupo? Esta acción no se puede deshacer.')) {
            document.getElementById('delete-form-' + groupId).submit();
        }
    }
</script>
@endsection
