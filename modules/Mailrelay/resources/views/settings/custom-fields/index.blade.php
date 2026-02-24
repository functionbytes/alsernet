@extends('layouts.theme')

@section('content')

    @include('core::components.card', ['title' => 'Campos personalizados'])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <!-- Header -->
        <div class="card mb-3">
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="h4 mb-1 fw-bold">Campos personalizados</h1>
                        <p class="text-muted mb-0">Gestiona los campos personalizados para tus suscriptores de Mailrelay</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('settings.mailrelay.custom-fields.index') }}" class="btn btn-secondary">
                            <i class="fas fa-eraser me-1"></i> Limpiar
                        </a>
                        <a href="{{ route('settings.mailrelay.custom-fields.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus-circle me-1"></i> Nuevo campo
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row g-3 mb-3">
            <div class="col-md-3">
                <div class="card bg-light-secondary stat-card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between">
                            <div>
                                <h6 class="card-title text-primary mb-2">
                                    Total campos
                                </h6>
                                <h3 class="mb-0 fw-bold">{{ $stats['total'] ?? 0 }}</h3>
                            </div>
                            <div class="rounded-circle bg-primary bg-opacity-10 p-3">
                                <i class="fas fa-list text-primary fs-5"></i>
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
                                <h6 class="card-title mb-2">
                                    Activos
                                </h6>
                                <h3 class="mb-0 fw-bold">{{ $stats['active'] ?? 0 }}</h3>
                            </div>
                            <div class="rounded-circle bg-success bg-opacity-10 p-3">
                                <i class="fas fa-check-circle text-success fs-5"></i>
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
                                <h6 class="card-title text-danger mb-2">
                                    Requeridos
                                </h6>
                                <h3 class="mb-0 fw-bold">{{ $stats['required'] ?? 0 }}</h3>
                            </div>
                            <div class="rounded-circle bg-danger bg-opacity-10 p-3">
                                <i class="fas fa-star text-danger fs-5"></i>
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
                                <h6 class="card-title text-info mb-2">
                                    Sincronizados con Mailrelay
                                </h6>
                                <h3 class="mb-0 fw-bold">{{ $stats['synced'] ?? 0 }}</h3>
                            </div>
                            <div class="rounded-circle bg-info bg-opacity-10 p-3">
                                <i class="fas fa-sync-alt text-info fs-5"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search and Filter Section -->
        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" action="{{ route('settings.mailrelay.custom-fields.index') }}" class="row g-3">
                    <div class="col-md-6">
                        <label for="search" class="form-label">
                            <i class="fas fa-search me-1"></i> Buscar campo
                        </label>
                        <input
                            type="text"
                            class="form-control"
                            id="search"
                            name="search"
                            placeholder="Nombre o clave del campo..."
                            value="{{ request('search') }}"
                        >
                    </div>

                    <div class="col-md-4">
                        <label for="type" class="form-label">
                            <i class="fas fa-filter me-1"></i> Tipo de campo
                        </label>
                        <select class="form-select" id="type" name="type">
                            <option value="">Todos los tipos</option>
                            <option value="text" {{ request('type') == 'text' ? 'selected' : '' }}>Text</option>
                            <option value="email" {{ request('type') == 'email' ? 'selected' : '' }}>Email</option>
                            <option value="number" {{ request('type') == 'number' ? 'selected' : '' }}>Number</option>
                            <option value="date" {{ request('type') == 'date' ? 'selected' : '' }}>Date</option>
                            <option value="select" {{ request('type') == 'select' ? 'selected' : '' }}>Select</option>
                            <option value="checkbox" {{ request('type') == 'checkbox' ? 'selected' : '' }}>Checkbox</option>
                        </select>
                    </div>

                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search me-1"></i> Buscar
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Custom Fields Table -->
        <div class="card">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="mb-0 fw-bold">
                    Lista de campos personalizados
                    @if(isset($customFields))
                    <span class="badge bg-secondary ms-2">{{ $customFields->total() }} resultados</span>
                    @endif
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Nombre</th>
                                <th>Clave/Key</th>
                                <th>Tipo</th>
                                <th>Requerido</th>
                                <th>Orden</th>
                                <th>Sync Mailrelay</th>
                                <th width="120">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($customFields ?? [] as $field)
                            <tr>
                                <td>
                                    <strong>{{ $field->name }}</strong>
                                    @if($field->description)
                                    <br>
                                    <small class="text-muted">{{ Str::limit($field->description, 50) }}</small>
                                    @endif
                                </td>
                                <td>
                                    <code class="text-primary">{{ $field->key }}</code>
                                </td>
                                <td>
                                    @php
                                        $typeColors = [
                                            'text' => 'info',
                                            'email' => 'primary',
                                            'number' => 'success',
                                            'date' => 'warning',
                                            'select' => 'secondary',
                                            'checkbox' => 'dark',
                                        ];
                                        $color = $typeColors[$field->type] ?? 'secondary';
                                    @endphp
                                    <span class="badge bg-{{ $color }}">
                                        {{ ucfirst($field->type) }}
                                    </span>
                                </td>
                                <td>
                                    @if($field->is_required)
                                        <span class="badge bg-success">
                                            Sí
                                        </span>
                                    @else
                                        <span class="badge bg-light text-dark">
                                            No
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark">{{ $field->order ?? 0 }}</span>
                                </td>
                                <td>
                                    @if($field->mailrelay_field_id && $field->sync_with_mailrelay)
                                        <span class="badge bg-success">
                                            <i class="fas fa-check-circle me-1"></i> ID: {{ $field->mailrelay_field_id }}
                                        </span>
                                    @else
                                        <span class="badge bg-warning text-dark">
                                            <i class="fas fa-times-circle me-1"></i> No sync
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li>
                                                <a class="dropdown-item" href="{{ route('settings.mailrelay.custom-fields.edit', $field) }}">
                                                    <i class="fas fa-edit me-2"></i> Editar
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#deleteModal{{ $field->id }}">
                                                    <i class="fas fa-trash me-2"></i> Eliminar
                                                </button>
                                            </li>
                                        </ul>
                                    </div>

                                    <!-- Delete Modal -->
                                    <div class="modal fade" id="deleteModal{{ $field->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Confirmar eliminación</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p>¿Estás seguro de eliminar el campo personalizado <strong>{{ $field->name }}</strong>?</p>
                                                    <p class="text-muted small mb-0">Esta acción no se puede deshacer.</p>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                    <form action="{{ route('settings.mailrelay.custom-fields.destroy', $field) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-danger">
                                                            <i class="fas fa-trash me-1"></i> Eliminar
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <div class="empty-state">
                                        <i class="fas fa-list text-muted" style="font-size: 4rem; opacity: 0.3;"></i>
                                        <h4 class="mt-3">No hay campos personalizados</h4>
                                        <p class="text-muted mb-3">
                                            @if(request()->hasAny(['search', 'type']))
                                                No se encontraron campos con los filtros aplicados.
                                            @else
                                                Crea tu primer campo personalizado para comenzar a personalizar tus suscriptores.
                                            @endif
                                        </p>
                                        @if(!request()->hasAny(['search', 'type']))
                                        <a href="{{ route('settings.mailrelay.custom-fields.create') }}" class="btn btn-primary">
                                            <i class="fas fa-plus-circle me-1"></i> Crear primer campo
                                        </a>
                                        @else
                                        <a href="{{ route('settings.mailrelay.custom-fields.index') }}" class="btn btn-secondary">
                                            <i class="fas fa-eraser me-1"></i> Limpiar filtros
                                        </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if(isset($customFields) && $customFields->hasPages())
            <div class="card-footer bg-white border-top">
                {{ $customFields->appends(request()->query())->links('pagination::bootstrap-5') }}
            </div>
            @endif
        </div>

    </div>

@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // Auto-submit search form on type change
        $('#type').on('change', function() {
            $(this).closest('form').submit();
        });
    });
</script>
@endpush
