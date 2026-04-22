@extends('layouts.theme')

@section('title', 'Automatizaciones')

@section('head')
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
    .automation-card {
        transition: transform 0.2s, box-shadow 0.2s;
        border: 1px solid #dee2e6;
        border-radius: 8px;
    }
    .automation-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    .stats-card {
        border-radius: 8px;
        padding: 1.5rem;
    }
    .icon-circle {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .empty-state-icon {
        font-size: 4rem;
        color: #dee2e6;
    }
</style>
@endsection

@section('content')
<div class="py-4 px-3">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="mb-1 fw-bold">Automatizaciones</h5>
            <p class="text-muted mb-0">Crea flujos automáticos para tus campañas</p>
        </div>
        <div class="d-flex gap-2">
            @if(request('search') || request('status'))
            <a href="{{ route('settings.mailrelay.automations.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-times me-2"></i>Limpiar búsqueda
            </a>
            @endif
            <a href="{{ route('settings.mailrelay.automations.create') }}" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Nueva automatización
            </a>
        </div>
    </div>

    <!-- Success/Error Messages -->
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card stats-card shadow-sm bg-primary-subtle">
                <div class="d-flex align-items-center">
                    <div class="icon-circle bg-primary text-white me-3">
                        <i class="fas fa-robot fa-lg"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-1">Total automatizaciones</h6>
                        <h3 class="mb-0 fw-bold">{{ $stats['total'] ?? 0 }}</h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card stats-card shadow-sm bg-success-subtle">
                <div class="d-flex align-items-center">
                    <div class="icon-circle bg-success text-white me-3">
                        <i class="fas fa-check-circle fa-lg"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-1">Activas</h6>
                        <h3 class="mb-0 fw-bold text-success">{{ $stats['active'] ?? 0 }}</h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card stats-card shadow-sm bg-warning-subtle">
                <div class="d-flex align-items-center">
                    <div class="icon-circle bg-warning text-dark me-3">
                        <i class="fas fa-pause-circle fa-lg"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-1">Pausadas</h6>
                        <h3 class="mb-0 fw-bold text-warning">{{ $stats['paused'] ?? 0 }}</h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card stats-card shadow-sm bg-info-subtle">
                <div class="d-flex align-items-center">
                    <div class="icon-circle bg-info text-white me-3">
                        <i class="fas fa-chart-line fa-lg"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-1">Completadas hoy</h6>
                        <h3 class="mb-0 fw-bold text-info">{{ $stats['executed_today'] ?? 0 }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Search and Filters -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('settings.mailrelay.automations.index') }}" class="row g-3">
                <div class="col-md-6">
                    <label for="search" class="form-label">Buscar</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white">
                            <i class="fas fa-search text-muted"></i>
                        </span>
                        <input type="text"
                               name="search"
                               id="search"
                               class="form-control border-start-0"
                               placeholder="Buscar por nombre o descripción..."
                               value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-4">
                    <label for="status" class="form-label">Estado</label>
                    <select class="select2" name="status" id="status" class="form-select">
                        <option value="">Todas</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Activas</option>
                        <option value="paused" {{ request('status') === 'paused' ? 'selected' : '' }}>Pausadas</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactivas</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search me-2"></i>Buscar
                    </button>
                </div>
            </form>
        </div>
    </div>

    @if($automations->isEmpty())
    <!-- Empty State -->
    <div class="text-center py-5">
        <i class="fas fa-robot empty-state-icon"></i>
        <h4 class="mt-4 text-muted">No hay automatizaciones</h4>
        <p class="text-muted mb-4">Crea tu primera automatización para comenzar</p>
        <a href="{{ route('settings.mailrelay.automations.create') }}" class="btn btn-primary btn-lg">
            <i class="fas fa-plus-circle me-2"></i>Crear automatización
        </a>
    </div>
    @else
    <!-- Automation Cards Grid -->
    <div class="row g-3">
        @foreach($automations as $automation)
        <div class="col-md-6 col-lg-4">
            <div class="card automation-card h-100 shadow-sm">
                <!-- Card Header -->
                <div class="card-header border-bottom bg-white py-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <h6 class="mb-0 fw-bold">{{ $automation->name }}</h6>
                        @if($automation->status === 'active')
                            <span class="badge bg-success">Activa</span>
                        @elseif($automation->status === 'paused')
                            <span class="badge bg-warning text-dark">Pausada</span>
                        @else
                            <span class="badge bg-secondary">Inactiva</span>
                        @endif
                    </div>
                </div>

                <!-- Card Body -->
                <div class="card-body">
                    <!-- Description -->
                    <p class="text-muted mb-3">
                        {{ Str::limit($automation->description ?? 'Sin descripción', 100) }}
                    </p>

                    <!-- Trigger Info -->
                    <div class="mb-3">
                        <span class="badge bg-info-subtle text-info border border-info">
                            <i class="fas fa-bolt me-1"></i>Cuando: {{ $automation->trigger_name ?? $automation->trigger_type ?? 'Sin trigger' }}
                        </span>
                    </div>

                    <!-- Stats -->
                    <div class="mb-3">
                        <small class="d-block text-muted">
                            <i class="fas fa-cogs me-1"></i>{{ $automation->actions_count ?? 0 }} acciones configuradas
                        </small>
                        <small class="d-block text-muted">
                            <i class="fas fa-play-circle me-1"></i>Ejecutada {{ number_format($automation->executions_count ?? 0) }} veces
                        </small>
                        @if($automation->last_executed_at)
                        <small class="d-block text-muted">
                            <i class="fas fa-clock me-1"></i>Última ejecución: {{ $automation->last_executed_at->diffForHumans() }}
                        </small>
                        @endif
                    </div>
                </div>

                <!-- Card Footer -->
                <div class="card-footer border-top bg-white py-3">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <select class="form-select form-select-sm w-auto"
                                id="status{{ $automation->id }}"
                                onchange="toggleStatus({{ $automation->id }})">
                            <option value="active" {{ $automation->status === 'active' ? 'selected' : '' }}>Activa</option>
                            <option value="paused" {{ $automation->status === 'paused' ? 'selected' : '' }}>Pausada</option>
                            <option value="inactive" {{ $automation->status === 'inactive' ? 'selected' : '' }}>Inactiva</option>
                        </select>

                        <!-- Dropdown Menu -->
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle"
                                    type="button"
                                    id="dropdown{{ $automation->id }}"
                                    data-bs-toggle="dropdown"
                                    aria-expanded="false">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdown{{ $automation->id }}">
                                <li>
                                    <a class="dropdown-item" href="{{ route('settings.mailrelay.automations.edit', $automation->id) }}">
                                        <i class="fas fa-pencil me-2"></i>Editar
                                    </a>
                                </li>
                                <li>
                                    <form action="{{ route('settings.mailrelay.automations.duplicate', $automation->id) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="dropdown-item">
                                            <i class="fas fa-copy me-2"></i>Duplicar
                                        </button>
                                    </form>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <button type="button"
                                            class="dropdown-item"
                                            data-bs-toggle="modal"
                                            data-bs-target="#deleteModal{{ $automation->id }}">
                                        <i class="fas fa-trash me-2"></i>Eliminar
                                    </button>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Modal -->
        <div class="modal fade" id="deleteModal{{ $automation->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Eliminar automatización</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>¿Estás seguro de que deseas eliminar <strong>{{ $automation->name }}</strong>?</p>
                        <p class="text-danger mb-0">Esta acción no se puede deshacer.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <form action="{{ route('settings.mailrelay.automations.destroy', $automation->id) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-trash me-2"></i>Eliminar
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <!-- Pagination -->
    @if($automations->hasPages())
    <div class="d-flex justify-content-center mt-4">
        {{ $automations->links('pagination::bootstrap-5') }}
    </div>
    @endif
    @endif
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleStatus(automationId) {
    const select = document.getElementById('status' + automationId);
    const status = select.value;
    const prevValue = select.dataset.prev || select.value;
    select.dataset.prev = status;

    fetch(`{{ url('settings/mailrelay/automations') }}/${automationId}/toggle`, {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ status: status })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            const card = select.closest('.card');
            const badge = card.querySelector('.badge');

            if (status === 'active') {
                badge.className = 'badge bg-success';
                badge.textContent = 'Activa';
            } else if (status === 'paused') {
                badge.className = 'badge bg-warning text-dark';
                badge.textContent = 'Pausada';
            } else {
                badge.className = 'badge bg-secondary';
                badge.textContent = 'Inactiva';
            }

            toastr.success('Estado actualizado correctamente');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        select.value = prevValue;
        toastr.error('Error al actualizar el estado');
    });
}
</script>
@endsection
