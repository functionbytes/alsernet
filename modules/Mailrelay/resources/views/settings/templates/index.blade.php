@extends('layouts.theme')

@section('content')

    @include('core::components.card', ['title' => 'Plantillas de email'])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card bg-light-info stat-card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between">
                            <div>
                                <h6 class="card-title text-info mb-2">Total plantillas</h6>
                                <h3 class="mb-1 fw-bold">{{ $stats['total'] ?? 0 }}</h3>
                            </div>
                            <div class="round-48 d-flex align-items-center justify-content-center rounded bg-info-subtle">
                                <i class="fas fa-envelope text-info fs-6"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="card bg-light-success stat-card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between">
                            <div>
                                <h6 class="card-title mb-2">Activas</h6>
                                <h3 class="mb-1 fw-bold text-success">{{ $stats['active'] ?? 0 }}</h3>
                            </div>
                            <div class="round-48 d-flex align-items-center justify-content-center rounded bg-success-subtle">
                                <i class="fas fa-check-circle text-success fs-6"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="card bg-light-warning stat-card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between">
                            <div>
                                <h6 class="card-title text-warning mb-2">Borradores</h6>
                                <h3 class="mb-1 fw-bold text-warning">{{ $stats['draft'] ?? 0 }}</h3>
                            </div>
                            <div class="round-48 d-flex align-items-center justify-content-center rounded bg-warning-subtle">
                                <i class="fas fa-file-alt text-warning fs-6"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="card bg-light-secondary stat-card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between">
                            <div>
                                <h6 class="card-title text-secondary mb-2">Última actualización</h6>
                                <p class="mb-0 small">
                                    @if(isset($stats['last_updated']))
                                        {{ $stats['last_updated']->diffForHumans() }}
                                    @else
                                        Sin actualizaciones
                                    @endif
                                </p>
                            </div>
                            <div class="round-48 d-flex align-items-center justify-content-center rounded bg-secondary-subtle">
                                <i class="fas fa-clock text-secondary fs-6"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Card -->
        <div class="card">
            <!-- Header Section -->
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Plantillas de email</h5>
                        <p class="small mb-0 text-muted">Gestiona las plantillas para tus campañas de email</p>
                    </div>
                    <div class="d-flex gap-2">
                        @if(request()->hasAny(['search', 'status']))
                            <a href="{{ route('settings.mailrelay.templates.index') }}" class="btn btn-light-warning">
                                <i class="fas fa-times-circle me-1"></i> Limpiar búsqueda
                            </a>
                        @endif
                        <a href="{{ route('settings.mailrelay.templates.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> Nueva plantilla
                        </a>
                    </div>
                </div>
            </div>

            <!-- Search and Filter Section -->
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('settings.mailrelay.templates.index') }}" class="row g-3">
                    <div class="col-md-8">
                        <label for="search" class="form-label fw-semibold">Buscar plantilla</label>
                        <div class="input-group">
                            <span class="input-group-text bg-transparent">
                                <i class="fas fa-magnifying-glass"></i>
                            </span>
                            <input
                                type="text"
                                class="form-control"
                                id="search"
                                name="search"
                                placeholder="Buscar por nombre, descripción o asunto..."
                                value="{{ request('search') }}"
                            >
                        </div>
                    </div>

                    <div class="col-md-3">
                        <label for="status" class="form-label fw-semibold">Estado</label>
                        <select class="form-select select2" id="status" name="status">
                            <option value="">Todos los estados</option>
                            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Activas</option>
                            <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Borradores</option>
                        </select>
                    </div>

                    <div class="col-md-1 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Templates Table -->
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Nombre</th>
                                <th>Tipo</th>
                                <th>Asunto</th>
                                <th>Estado</th>
                                <th>Última modificación</th>
                                <th class="text-center pe-4">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($templates ?? [] as $template)
                            <tr>
                                <td class="ps-4">
                                    <div>
                                        <strong class="d-block">{{ $template->name }}</strong>
                                        @if($template->description)
                                            <small class="text-muted d-block" style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                                {{ $template->description }}
                                            </small>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    @switch($template->type)
                                        @case('welcome')
                                            <span class="badge bg-success-subtle text-success">
                                                Bienvenida
                                            </span>
                                            @break
                                        @case('newsletter')
                                            <span class="badge bg-info-subtle text-info">
                                                Newsletter
                                            </span>
                                            @break
                                        @case('transactional')
                                            <span class="badge bg-warning-subtle text-warning">
                                                Transaccional
                                            </span>
                                            @break
                                        @case('custom')
                                            <span class="badge bg-secondary-subtle text-secondary">
                                                Personalizado
                                            </span>
                                            @break
                                        @default
                                            <span class="badge bg-light text-dark">
                                                Sin definir
                                            </span>
                                    @endswitch
                                </td>
                                <td>
                                    @if($template->subject)
                                        <code class="small" style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; display: inline-block;">
                                            {{ $template->subject }}
                                        </code>
                                    @else
                                        <span class="text-muted">Sin asunto</span>
                                    @endif
                                </td>
                                <td>
                                    @if($template->is_active)
                                        <span class="badge bg-success-subtle text-success">
                                            <i class="fas fa-check-circle me-1"></i>Activa
                                        </span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary">
                                            <i class="fas fa-file-alt me-1"></i>Borrador
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <div>
                                        <small class="d-block">{{ $template->updated_at->format('d/m/Y H:i') }}</small>
                                        <small class="text-muted">{{ $template->updated_at->diffForHumans() }}</small>
                                    </div>
                                </td>
                                <td class="text-center pe-4">
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-light-primary dropdown-toggle" type="button" id="actionsDropdown{{ $template->id }}" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="fas fa-ellipsis-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="actionsDropdown{{ $template->id }}">
                                            <li>
                                                <a class="dropdown-item" href="{{ route('settings.mailrelay.templates.edit', $template->id) }}">
                                                    <i class="fas fa-edit me-2 text-primary"></i>Editar
                                                </a>
                                            </li>
                                            <li>
                                                <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#previewModal{{ $template->id }}">
                                                    <i class="fas fa-eye me-2 text-info"></i>Vista previa
                                                </button>
                                            </li>
                                            <li>
                                                <form action="{{ route('settings.mailrelay.templates.duplicate', $template->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="dropdown-item">
                                                        <i class="fas fa-copy me-2 text-secondary"></i>Duplicar
                                                    </button>
                                                </form>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <a href="#" class="dropdown-item confirm-delete"
                                                   data-href="{{ route('settings.mailrelay.templates.destroy', $template->id) }}">
                                                    <i class="fas fa-trash me-2"></i>Eliminar
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>

                            <!-- Preview Modal -->
                            <div class="modal fade" id="previewModal{{ $template->id }}" tabindex="-1" aria-labelledby="previewModalLabel{{ $template->id }}" aria-hidden="true">
                                <div class="modal-dialog modal-lg modal-dialog-centered">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="previewModalLabel{{ $template->id }}">
                                                Vista previa: {{ $template->name }}
                                            </h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <strong>Asunto:</strong>
                                                <span class="text-muted">{{ $template->subject ?? 'Sin asunto' }}</span>
                                            </div>
                                            <div class="border rounded p-3" style="max-height: 400px; overflow-y: auto;">
                                                @if($template->content)
                                                    <iframe srcdoc="{{ $template->content }}"
                                                            sandbox="allow-same-origin"
                                                            style="width: 100%; min-height: 300px; border: none;"
                                                            onload="this.style.height = this.contentDocument.body.scrollHeight + 'px'">
                                                    </iframe>
                                                @else
                                                    <p class="text-muted">Sin contenido</p>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
                                            <a href="{{ route('settings.mailrelay.templates.edit', $template->id) }}" class="btn btn-primary">
                                                <i class="fas fa-edit me-1"></i>Editar plantilla
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <div class="py-4">
                                        <div class="mb-3">
                                            <i class="fas fa-envelope text-muted" style="font-size: 4rem; opacity: 0.3;"></i>
                                        </div>
                                        <h4 class="text-muted mb-2">No hay plantillas disponibles</h4>
                                        <p class="text-muted mb-3">
                                            @if(request()->hasAny(['search', 'status']))
                                                No se encontraron plantillas con los filtros aplicados
                                            @else
                                                Crea tu primera plantilla para comenzar
                                            @endif
                                        </p>
                                        @if(!request()->hasAny(['search', 'status']))
                                            <a href="{{ route('settings.mailrelay.templates.create') }}" class="btn btn-primary">
                                                <i class="fas fa-plus-circle me-2"></i>Crear primera plantilla
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

            <!-- Pagination Footer -->
            @if(isset($templates) && $templates->hasPages())
            <div class="card-footer bg-white border-top">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="text-muted">
                        Mostrando {{ $templates->firstItem() }} a {{ $templates->lastItem() }} de {{ $templates->total() }} plantillas
                    </div>
                    <div>
                        {{ $templates->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            </div>
            @endif
        </div>

    </div>

@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // Auto-submit form on filter change
        $('#status').on('change', function() {
            $(this).closest('form').submit();
        });

        // Clear search input shortcut
        $('#search').on('keyup', function(e) {
            if (e.key === 'Escape') {
                $(this).val('');
            }
        });
    });
</script>
@endpush
