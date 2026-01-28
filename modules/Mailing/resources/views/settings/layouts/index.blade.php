@extends('layouts.theme')

@section('content')

    @include('core::components.card', ['title' => 'Layouts de email'])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        {{-- Stats Cards --}}
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card bg-light-info stat-card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between">
                            <div>
                                <h6 class="card-title text-info mb-2">Total layouts</h6>
                                <h3 class="mb-1 fw-bold">{{ $stats['total'] ?? 0 }}</h3>
                            </div>
                            <div class="round-48 d-flex align-items-center justify-content-center rounded bg-info-subtle">
                                <i class="fas fa-layer-group text-info fs-6"></i>
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
                                <h6 class="card-title text-success mb-2">Activos</h6>
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
                                <h6 class="card-title text-warning mb-2">Inactivos</h6>
                                <h3 class="mb-1 fw-bold text-warning">{{ $stats['inactive'] ?? 0 }}</h3>
                            </div>
                            <div class="round-48 d-flex align-items-center justify-content-center rounded bg-warning-subtle">
                                <i class="fas fa-circle-xmark text-warning fs-6"></i>
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

        {{-- Main Card --}}
        <div class="card">
            {{-- Header Section --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Layouts de email</h5>
                        <p class="small mb-0 text-muted">Gestiona los layouts reutilizables para tus plantillas de email</p>
                    </div>
                    <div class="d-flex gap-2">
                        @if(request()->hasAny(['search', 'type']))
                            <a href="{{ route('settings.mailing.templates.layouts.index') }}" class="btn btn-light-warning">
                                <i class="fas fa-times-circle me-1"></i>Limpiar búsqueda
                            </a>
                        @endif
                        <a href="{{ route('settings.mailing.templates.layouts.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i>Nuevo layout
                        </a>
                    </div>
                </div>
            </div>

            {{-- Search and Filter Section --}}
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('settings.mailing.templates.layouts.index') }}" class="row g-3">
                    <div class="col-md-8">
                        <label for="search" class="form-label fw-semibold">Buscar layout</label>
                        <div class="input-group">
                            <span class="input-group-text bg-transparent">
                                <i class="fas fa-magnifying-glass"></i>
                            </span>
                            <input
                                type="text"
                                class="form-control"
                                id="search"
                                name="search"
                                placeholder="Buscar por nombre o descripción..."
                                value="{{ request('search') }}"
                            >
                        </div>
                    </div>

                    <div class="col-md-3">
                        <label for="type" class="form-label fw-semibold">Tipo</label>
                        <select class="form-select" id="type" name="type">
                            <option value="">Todos los tipos</option>
                            <option value="single-column" {{ request('type') == 'single-column' ? 'selected' : '' }}>Una columna</option>
                            <option value="two-column" {{ request('type') == 'two-column' ? 'selected' : '' }}>Dos columnas</option>
                            <option value="three-column" {{ request('type') == 'three-column' ? 'selected' : '' }}>Tres columnas</option>
                        </select>
                    </div>

                    <div class="col-md-1 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
            </div>

            {{-- Table Section --}}
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Nombre</th>
                                <th>Tipo</th>
                                <th>Ancho</th>
                                <th>Estado</th>
                                <th>Modificación</th>
                                <th class="text-center pe-4">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($layouts ?? [] as $layout)
                            <tr>
                                <td class="ps-4">
                                    <div>
                                        <strong class="d-block">{{ $layout->name }}</strong>
                                        @if($layout->description)
                                            <small class="text-muted d-block" style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                                {{ $layout->description }}
                                            </small>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    @switch($layout->type)
                                        @case('single-column')
                                            <span class="badge bg-info-subtle text-info">Una columna</span>
                                            @break
                                        @case('two-column')
                                            <span class="badge bg-success-subtle text-success">Dos columnas</span>
                                            @break
                                        @case('three-column')
                                            <span class="badge bg-warning-subtle text-warning">Tres columnas</span>
                                            @break
                                        @default
                                            <span class="badge bg-light text-dark">Sin definir</span>
                                    @endswitch
                                </td>
                                <td>
                                    <code class="small">{{ $layout->max_width ?? 600 }}px</code>
                                </td>
                                <td>
                                    @if($layout->is_active)
                                        <span class="badge bg-success-subtle text-success">
                                            <i class="fas fa-check-circle me-1"></i>Activo
                                        </span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary">
                                            <i class="fas fa-ban me-1"></i>Inactivo
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <small class="d-block">{{ $layout->updated_at->format('d/m/Y H:i') }}</small>
                                    <small class="text-muted">{{ $layout->updated_at->diffForHumans() }}</small>
                                </td>
                                <td class="text-center pe-4">
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-light-primary dropdown-toggle" type="button" id="actionsDropdown{{ $layout->id }}" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="fas fa-ellipsis-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="actionsDropdown{{ $layout->id }}">
                                            <li>
                                                <a class="dropdown-item" href="{{ route('settings.mailing.templates.layouts.edit', $layout->id) }}">
                                                    <i class="fas fa-edit me-2 text-primary"></i>Editar
                                                </a>
                                            </li>
                                            <li>
                                                <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#previewModal{{ $layout->id }}">
                                                    <i class="fas fa-eye me-2 text-info"></i>Vista previa
                                                </button>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <a href="#" class="dropdown-item text-danger confirm-delete"
                                                   data-href="{{ route('settings.mailing.templates.layouts.destroy', $layout->id) }}">
                                                    <i class="fas fa-trash me-2"></i>Eliminar
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>

                            {{-- Preview Modal --}}
                            <div class="modal fade" id="previewModal{{ $layout->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-lg modal-dialog-centered">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">
                                                <i class="fas fa-eye me-2 text-info"></i>Vista previa: {{ $layout->name }}
                                            </h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body" style="max-height: 500px; overflow-y: auto; background-color: {{ $layout->background_color ?? '#f0f0f0' }};">
                                            <div style="max-width: {{ $layout->max_width ?? 600 }}px; margin: 0 auto;">
                                                {!! str_replace('{{content}}', '<div style="padding: 20px; background-color: white; border: 2px dashed #ccc; text-align: center; color: #999;">Contenido principal aquí</div>', $layout->html) !!}
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
                                            <a href="{{ route('settings.mailing.templates.layouts.edit', $layout->id) }}" class="btn btn-primary">
                                                <i class="fas fa-edit me-1"></i>Editar layout
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
                                            <i class="fas fa-layer-group text-muted" style="font-size: 4rem; opacity: 0.3;"></i>
                                        </div>
                                        <h4 class="text-muted mb-2">No hay layouts disponibles</h4>
                                        <p class="text-muted mb-3">
                                            @if(request()->hasAny(['search', 'type']))
                                                No se encontraron layouts con los filtros aplicados
                                            @else
                                                Crea tu primer layout para comenzar
                                            @endif
                                        </p>
                                        @if(!request()->hasAny(['search', 'type']))
                                            <a href="{{ route('settings.mailing.templates.layouts.create') }}" class="btn btn-primary">
                                                <i class="fas fa-plus-circle me-2"></i>Crear primer layout
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

            {{-- Pagination Footer --}}
            @if(isset($layouts) && $layouts->hasPages())
            <div class="card-footer bg-white border-top">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="text-muted small">
                        Mostrando {{ $layouts->firstItem() }} a {{ $layouts->lastItem() }} de {{ $layouts->total() }} layouts
                    </div>
                    <div>
                        {{ $layouts->links('pagination::bootstrap-5') }}
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
        $('#type').on('change', function() {
            $(this).closest('form').submit();
        });
    });
</script>
@endpush
