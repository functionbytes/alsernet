@extends('layouts.theme')

@section('content')

    @include('core::components.card', ['title' => 'Formularios de plantilla'])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        {{-- Stats Cards --}}
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card bg-light-info stat-card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between">
                            <div>
                                <h6 class="card-title text-info mb-2">Total formularios</h6>
                                <h3 class="mb-1 fw-bold">{{ $stats['total'] ?? 0 }}</h3>
                            </div>
                            <div class="round-48 d-flex align-items-center justify-content-center rounded bg-info-subtle">
                                <i class="fas fa-wpforms text-info fs-6"></i>
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
                        <h5 class="mb-1 fw-bold">Formularios de plantilla</h5>
                        <p class="small mb-0 text-muted">Gestiona los formularios para tus plantillas de email</p>
                    </div>
                    <div class="d-flex gap-2">
                        @if(request()->hasAny(['search', 'type']))
                            <a href="{{ route('settings.mailing.templates.forms.index') }}" class="btn btn-light-warning">
                                <i class="fas fa-times-circle me-1"></i>Limpiar búsqueda
                            </a>
                        @endif
                        <a href="{{ route('settings.mailing.templates.forms.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i>Nuevo formulario
                        </a>
                    </div>
                </div>
            </div>

            {{-- Search and Filter Section --}}
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('settings.mailing.templates.forms.index') }}" class="row g-3">
                    <div class="col-md-8">
                        <label for="search" class="form-label fw-semibold">Buscar formulario</label>
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
                            <option value="pre-chat" {{ request('type') == 'pre-chat' ? 'selected' : '' }}>Pre-chat</option>
                            <option value="post-chat" {{ request('type') == 'post-chat' ? 'selected' : '' }}>Post-chat</option>
                            <option value="custom" {{ request('type') == 'custom' ? 'selected' : '' }}>Personalizado</option>
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
                                <th>Campos</th>
                                <th>Estado</th>
                                <th>Modificación</th>
                                <th class="text-center pe-4">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($forms ?? [] as $form)
                            <tr>
                                <td class="ps-4">
                                    <div>
                                        <strong class="d-block">{{ $form->name }}</strong>
                                        @if($form->description)
                                            <small class="text-muted d-block" style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                                {{ $form->description }}
                                            </small>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    @switch($form->type)
                                        @case('pre-chat')
                                            <span class="badge bg-info-subtle text-info">Pre-chat</span>
                                            @break
                                        @case('post-chat')
                                            <span class="badge bg-success-subtle text-success">Post-chat</span>
                                            @break
                                        @case('custom')
                                            <span class="badge bg-secondary-subtle text-secondary">Personalizado</span>
                                            @break
                                        @default
                                            <span class="badge bg-light text-dark">Sin definir</span>
                                    @endswitch
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark">
                                        {{ count(json_decode($form->fields ?? '[]', true)) }} campos
                                    </span>
                                </td>
                                <td>
                                    @if($form->is_active)
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
                                    <small class="d-block">{{ $form->updated_at->format('d/m/Y H:i') }}</small>
                                    <small class="text-muted">{{ $form->updated_at->diffForHumans() }}</small>
                                </td>
                                <td class="text-center pe-4">
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-light-primary dropdown-toggle" type="button" id="actionsDropdown{{ $form->id }}" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="fas fa-ellipsis-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="actionsDropdown{{ $form->id }}">
                                            <li>
                                                <a class="dropdown-item" href="{{ route('settings.mailing.templates.forms.edit', $form->id) }}">
                                                    <i class="fas fa-edit me-2 text-primary"></i>Editar
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <a href="#" class="dropdown-item text-danger confirm-delete"
                                                   data-href="{{ route('settings.mailing.templates.forms.destroy', $form->id) }}">
                                                    <i class="fas fa-trash me-2"></i>Eliminar
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <div class="py-4">
                                        <div class="mb-3">
                                            <i class="fas fa-wpforms text-muted" style="font-size: 4rem; opacity: 0.3;"></i>
                                        </div>
                                        <h4 class="text-muted mb-2">No hay formularios disponibles</h4>
                                        <p class="text-muted mb-3">
                                            @if(request()->hasAny(['search', 'type']))
                                                No se encontraron formularios con los filtros aplicados
                                            @else
                                                Crea tu primer formulario para comenzar
                                            @endif
                                        </p>
                                        @if(!request()->hasAny(['search', 'type']))
                                            <a href="{{ route('settings.mailing.templates.forms.create') }}" class="btn btn-primary">
                                                <i class="fas fa-plus-circle me-2"></i>Crear primer formulario
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
            @if(isset($forms) && $forms->hasPages())
            <div class="card-footer bg-white border-top">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="text-muted small">
                        Mostrando {{ $forms->firstItem() }} a {{ $forms->lastItem() }} de {{ $forms->total() }} formularios
                    </div>
                    <div>
                        {{ $forms->links('pagination::bootstrap-5') }}
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
