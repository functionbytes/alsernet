@extends('layouts.theme')

@section('title', 'Segmentos de Clientes')

@section('content')

    @include('core::components.card', ['title' => 'Segmentos de Clientes'])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <!-- Main Card -->
        <div class="card">
            <!-- Header Section -->
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Segmentos de clientes</h5>
                        <p class="small mb-0 text-muted">Crea y gestiona grupos dinámicos de clientes basados en filtros personalizados</p>
                    </div>
                    <div class="d-flex gap-2">
                        @if(request('search'))
                            <a href="{{ route('chat.customers.segments.index') }}"
                               class="btn btn-light">
                                <i class="fas fa-times me-1"></i> Limpiar
                            </a>
                        @endif
                        <a href="{{ route('chat.customers.segments.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Nuevo segmento
                        </a>
                        <button type="button" class="btn btn-secondary" onclick="location.reload()">
                            <i class="fas fa-sync me-1"></i> Actualizar
                        </button>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title text-primary mb-2">Total segmentos</h6>
                                        <h4 class="mb-1 fw-bold">{{ $stats['total'] ?? 0 }}</h4>
                                        <small class="text-muted">Segmentos creados</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title mb-2">Activos</h6>
                                        <h4 class="mb-1 fw-bold">{{ $stats['active'] ?? 0 }}</h4>
                                        <small class="text-muted">En uso</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title text-info mb-2">Total clientes</h6>
                                        <h4 class="mb-1 fw-bold">{{ $stats['total_customers'] ?? 0 }}</h4>
                                        <small class="text-muted">En segmentos</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title text-warning mb-2">Última actualización</h6>
                                        <h4 class="mb-1 fw-bold">{{ $stats['last_updated'] ?? '-' }}</h4>
                                        <small class="text-muted">Más reciente</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search and Filters -->
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-8">
                        <form method="GET" action="{{ route('chat.customers.segments.index') }}">
                            <div class="input-group">
                                <span class="input-group-text bg-white">
                                    <i class="fas fa-search"></i>
                                </span>
                                <input type="search"
                                       name="search"
                                       class="form-control"
                                       placeholder="Buscar segmento por nombre o descripción..."
                                       value="{{ request('search') }}">
                                <button type="submit" class="btn btn-primary">
                                    Buscar
                                </button>
                            </div>
                        </form>
                    </div>
                    <div class="col-md-4">
                        <select class="form-control select2" id="filterStatus" onchange="filterByStatus(this.value)">
                            <option value="">Todos los estados</option>
                            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Activos</option>
                            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactivos</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Segments Table -->
            <div class="card-body">
                <div class="mb-3">
                    <h6 class="mb-1 fw-bold">Lista de segmentos</h6>
                    <p class="text-muted small mb-0">Total: {{ $segments->total() }} segmentos</p>
                </div>

                <div class="alert alert-info border-0 bg-info-subtle mb-3">
                    <div class="d-flex align-items-start gap-2">
                        <i class="fas fa-info-circle mt-1"></i>
                        <div>
                            <small class="fw-semibold">Información:</small>
                            <small class="d-block">Los segmentos se actualizan automáticamente según los criterios definidos. Haz clic en el nombre para ver los clientes incluidos.</small>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Segmento</th>
                                <th>Criterios</th>
                                <th class="text-center">Clientes</th>
                                <th>Última actualización</th>
                                <th>Estado</th>
                                <th class="text-end pe-4">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($segments as $segment)
                            <tr>
                                <!-- Segment Name & Description -->
                                <td class="ps-4">
                                    <div>
                                        <h6 class="mb-0 fw-semibold">
                                            <a href="{{ route('chat.customers.segments.show', $segment->id) }}"
                                               class="text-dark text-decoration-none">
                                                {{ $segment->name }}
                                            </a>
                                        </h6>
                                        @if($segment->description)
                                            <small class="text-muted">{{ Str::limit($segment->description, 50) }}</small>
                                        @endif
                                    </div>
                                </td>

                                <!-- Criteria -->
                                <td>
                                    <div class="d-flex flex-wrap gap-1">
                                        @php
                                            $criteria = is_array($segment->criteria) ? $segment->criteria : json_decode($segment->criteria, true);
                                            $criteriaCount = is_array($criteria) ? count($criteria) : 0;
                                        @endphp
                                        <span class="badge bg-light text-dark">
                                            <i class="fas fa-filter"></i> {{ $criteriaCount }} filtros
                                        </span>
                                    </div>
                                </td>

                                <!-- Customers Count -->
                                <td class="text-center">
                                    <span class="badge bg-primary-subtle text-primary fs-6">
                                        {{ $segment->customers_count ?? 0 }}
                                    </span>
                                </td>

                                <!-- Last Updated -->
                                <td>
                                    <small class="text-muted">
                                        {{ $segment->updated_at->diffForHumans() }}
                                    </small>
                                </td>

                                <!-- Status -->
                                <td>
                                    @if($segment->is_active)
                                        <span class="badge bg-success-subtle text-success">
                                            <i class="fas fa-check"></i> Activo
                                        </span>
                                    @else
                                        <span class="badge bg-info-subtle text-info">
                                            <i class="fas fa-pause"></i> Inactivo
                                        </span>
                                    @endif
                                </td>

                                <!-- Actions -->
                                <td class="text-end pe-4">
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-light" data-bs-toggle="dropdown">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <a class="dropdown-item" href="{{ route('chat.customers.segments.show', $segment->id) }}">
                                                    <i class="fas fa-eye"></i> Ver clientes
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="{{ route('chat.customers.segments.edit', $segment->id) }}">
                                                    <i class="fas fa-edit"></i> Editar
                                                </a>
                                            </li>
                                            <li>
                                                <button type="button" class="dropdown-item" onclick="refreshSegment({{ $segment->id }})">
                                                    <i class="fas fa-sync"></i> Actualizar conteo
                                                </button>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            @if($segment->is_active)
                                                <li>
                                                    <button type="button" class="dropdown-item text-warning" onclick="toggleStatus({{ $segment->id }}, false)">
                                                        <i class="fas fa-pause"></i> Desactivar
                                                    </button>
                                                </li>
                                            @else
                                                <li>
                                                    <button type="button" class="dropdown-item text-success" onclick="toggleStatus({{ $segment->id }}, true)">
                                                        <i class="fas fa-play"></i> Activar
                                                    </button>
                                                </li>
                                            @endif
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <form method="POST" action="{{ route('chat.customers.segments.destroy', $segment->id) }}" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="dropdown-item text-danger"
                                                            onclick="return confirm('¿Eliminar este segmento? Esta acción no se puede deshacer.')">
                                                        <i class="fas fa-trash"></i> Eliminar
                                                    </button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <div class="d-flex flex-column align-items-center">
                                        <div class="round-48 rounded-circle bg-light-subtle text-muted mb-3 d-flex align-items-center justify-content-center">
                                            <i class="fas fa-filter fs-7"></i>
                                        </div>
                                        <h6 class="mb-1">No hay segmentos creados</h6>
                                        <p class="text-muted mb-3">Crea tu primer segmento para organizar clientes</p>
                                        <a href="{{ route('chat.customers.segments.create') }}" class="btn btn-sm btn-primary">
                                            <i class="fas fa-plus"></i> Crear segmento
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if($segments->hasPages())
                    <div class="mt-3 d-flex justify-content-center">
                        {{ $segments->links() }}
                    </div>
                @endif
            </div>
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

// Filter by status
function filterByStatus(status) {
    const url = new URL(window.location.href);
    if (status) {
        url.searchParams.set('status', status);
    } else {
        url.searchParams.delete('status');
    }
    window.location.href = url.toString();
}

// Refresh segment customer count
function refreshSegment(segmentId) {
    axios.post(`/settings/chat/customers/segments/${segmentId}/refresh`)
        .then(response => {
            toastr.success(response.data.message || 'Segmento actualizado correctamente', 'Éxito');
            setTimeout(() => location.reload(), 1000);
        })
        .catch(error => {
            const message = error.response?.data?.message || 'Error al actualizar el segmento';
            toastr.error(message, 'Error');
        });
}

// Toggle segment active status
function toggleStatus(segmentId, activate) {
    const action = activate ? 'activar' : 'desactivar';
    if (!confirm(`¿Seguro que deseas ${action} este segmento?`)) {
        return;
    }

    axios.post(`/settings/chat/customers/segments/${segmentId}/toggle-status`, {
        is_active: activate
    })
    .then(response => {
        toastr.success(response.data.message || `Segmento ${activate ? 'activado' : 'desactivado'} correctamente`, 'Éxito');
        setTimeout(() => location.reload(), 1000);
    })
    .catch(error => {
        const message = error.response?.data?.message || 'Error al cambiar el estado';
        toastr.error(message, 'Error');
    });
}
</script>
@endpush
