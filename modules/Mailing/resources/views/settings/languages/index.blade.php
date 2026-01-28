@extends('layouts.theme')

@section('content')

    @include('core::components.card', ['title' => 'Idiomas'])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card bg-light-info stat-card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between">
                            <div>
                                <h6 class="card-title text-info mb-2">Total idiomas</h6>
                                <h3 class="mb-1 fw-bold">{{ $stats['total'] ?? 0 }}</h3>
                            </div>
                            <div class="round-48 d-flex align-items-center justify-content-center rounded bg-info-subtle">
                                <i class="fas fa-globe text-info fs-6"></i>
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
                                <h6 class="card-title text-warning mb-2">Predeterminado</h6>
                                <p class="mb-0 small fw-semibold">
                                    @if($defaultLanguage)
                                        <span class="badge bg-warning-subtle text-warning">{{ $defaultLanguage->language_name }}</span>
                                    @else
                                        <span class="text-muted">Sin asignar</span>
                                    @endif
                                </p>
                            </div>
                            <div class="round-48 d-flex align-items-center justify-content-center rounded bg-warning-subtle">
                                <i class="fas fa-star text-warning fs-6"></i>
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
                        <h5 class="mb-1 fw-bold">Gestión de idiomas</h5>
                        <p class="small mb-0 text-muted">Crea y gestiona los idiomas disponibles para tus campañas</p>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-light-info" data-bs-toggle="modal" data-bs-target="#importModal">
                            <i class="fas fa-file-import me-1"></i> Importar
                        </button>
                        @if($languages->count() > 0)
                            <a href="{{ route('settings.mailing.templates.languages.export') }}" class="btn btn-light-success">
                                <i class="fas fa-file-export me-1"></i> Exportar
                            </a>
                        @endif
                        <a href="{{ route('settings.mailing.templates.languages.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> Nuevo idioma
                        </a>
                    </div>
                </div>
            </div>

            <!-- Search and Filter Section -->
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('settings.mailing.templates.languages.index') }}" class="row g-3">
                    <div class="col-md-8">
                        <label for="search" class="form-label fw-semibold">Buscar idioma</label>
                        <div class="input-group">
                            <span class="input-group-text bg-transparent">
                                <i class="fas fa-magnifying-glass"></i>
                            </span>
                            <input
                                type="text"
                                class="form-control"
                                id="search"
                                name="search"
                                placeholder="Buscar por código, nombre..."
                                value="{{ request('search') }}"
                            >
                        </div>
                    </div>

                    <div class="col-md-3">
                        <label for="status" class="form-label fw-semibold">Estado</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">Todos los estados</option>
                            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Activos</option>
                            <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactivos</option>
                        </select>
                    </div>

                    <div class="col-md-1 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Languages Table -->
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Idioma</th>
                                <th>Código</th>
                                <th>Estado</th>
                                <th>Predeterminado</th>
                                <th>Traducciones</th>
                                <th>Última modificación</th>
                                <th class="text-center pe-4">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($languages ?? [] as $language)
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center">
                                        <div class="p-2 bg-light-primary rounded me-3">
                                            <i class="fas fa-language text-primary"></i>
                                        </div>
                                        <div>
                                            <strong class="d-block">{{ $language->language_name }}</strong>
                                            <small class="text-muted">{{ $language->language_code }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <code class="small bg-light p-1 rounded">{{ $language->language_code }}</code>
                                </td>
                                <td>
                                    @if($language->is_active)
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
                                    @if($language->is_default)
                                        <span class="badge bg-warning-subtle text-warning">
                                            <i class="fas fa-star me-1"></i>Por defecto
                                        </span>
                                    @else
                                        <span class="text-muted small">-</span>
                                    @endif
                                </td>
                                <td>
                                    <div>
                                        <small class="d-block">
                                            {{ $language->translations_count ?? 0 }} elementos
                                        </small>
                                        <small class="text-muted">
                                            @php
                                                $totalCount = isset($stats['total_translations']) ? $stats['total_translations'] : 1;
                                                $percentage = $totalCount > 0 ? round(($language->translations_count ?? 0) / $totalCount * 100) : 0;
                                            @endphp
                                            {{ $percentage }}% completado
                                        </small>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <small class="d-block">{{ $language->updated_at->format('d/m/Y H:i') }}</small>
                                        <small class="text-muted">{{ $language->updated_at->diffForHumans() }}</small>
                                    </div>
                                </td>
                                <td class="text-center pe-4">
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-light-primary dropdown-toggle" type="button" id="actionsDropdown{{ $language->id }}" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="fas fa-ellipsis-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="actionsDropdown{{ $language->id }}">
                                            <li>
                                                <a class="dropdown-item" href="{{ route('settings.mailing.templates.languages.edit', $language->id) }}">
                                                    <i class="fas fa-edit me-2 text-primary"></i>Editar
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="{{ route('settings.mailing.templates.languages.show', $language->id) }}">
                                                    <i class="fas fa-eye me-2 text-info"></i>Ver traducciones
                                                </a>
                                            </li>
                                            @if(!$language->is_default)
                                            <li>
                                                <form action="{{ route('settings.mailing.templates.languages.setDefault', $language->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="dropdown-item">
                                                        <i class="fas fa-star me-2 text-warning"></i>Establecer predeterminado
                                                    </button>
                                                </form>
                                            </li>
                                            @endif
                                            <li>
                                                <a href="{{ route('settings.mailing.templates.languages.export', $language->id) }}" class="dropdown-item">
                                                    <i class="fas fa-download me-2 text-secondary"></i>Descargar
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <a href="#" class="dropdown-item text-danger confirm-delete"
                                                   data-href="{{ route('settings.mailing.templates.languages.destroy', $language->id) }}">
                                                    <i class="fas fa-trash me-2"></i>Eliminar
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <div class="py-4">
                                        <div class="mb-3">
                                            <i class="fas fa-globe text-muted" style="font-size: 4rem; opacity: 0.3;"></i>
                                        </div>
                                        <h4 class="text-muted mb-2">No hay idiomas disponibles</h4>
                                        <p class="text-muted mb-3">
                                            @if(request()->hasAny(['search', 'status']))
                                                No se encontraron idiomas con los filtros aplicados
                                            @else
                                                Crea tu primer idioma para comenzar
                                            @endif
                                        </p>
                                        @if(!request()->hasAny(['search', 'status']))
                                            <a href="{{ route('settings.mailing.templates.languages.create') }}" class="btn btn-primary">
                                                <i class="fas fa-plus-circle me-2"></i>Crear primer idioma
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
            @if(isset($languages) && $languages->hasPages())
            <div class="card-footer bg-white border-top">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="text-muted small">
                        Mostrando {{ $languages->firstItem() }} a {{ $languages->lastItem() }} de {{ $languages->total() }} idiomas
                    </div>
                    <div>
                        {{ $languages->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            </div>
            @endif
        </div>

    </div>

    <!-- Import Modal -->
    <div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="importModalLabel">
                        <i class="fas fa-file-import me-2"></i>Importar idioma
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('settings.mailing.templates.languages.import') }}" method="POST" enctype="multipart/form-data" id="importForm">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="languageFile" class="form-label">
                                Archivo JSON <span class="text-danger">*</span>
                            </label>
                            <input type="file"
                                   class="form-control"
                                   id="languageFile"
                                   name="file"
                                   accept=".json"
                                   required>
                            <small class="form-text text-muted">
                                Selecciona un archivo JSON exportado con las traducciones del idioma
                            </small>
                        </div>

                        <div class="alert alert-info">
                            <i class="fas fa-lightbulb me-2"></i>
                            <strong>Formato esperado:</strong>
                            <p class="mb-0 small mt-2">
                                El archivo debe contener un objeto JSON con los códigos de idioma como clave y un objeto con los datos del idioma como valor.
                            </p>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-upload me-2"></i>Importar
                        </button>
                    </div>
                </form>
            </div>
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

        // File validation for import
        $('#languageFile').on('change', function() {
            const file = this.files[0];
            if (file) {
                if (file.type !== 'application/json') {
                    alert('Por favor, selecciona un archivo JSON válido');
                    $(this).val('');
                    return;
                }
                if (file.size > 5 * 1024 * 1024) {
                    alert('El archivo no puede superar 5MB');
                    $(this).val('');
                }
            }
        });

        // Submit import form
        $('#importForm').on('submit', function(e) {
            const file = $('#languageFile')[0].files[0];
            if (!file) {
                e.preventDefault();
                alert('Por favor, selecciona un archivo para importar');
            }
        });
    });
</script>
@endpush
