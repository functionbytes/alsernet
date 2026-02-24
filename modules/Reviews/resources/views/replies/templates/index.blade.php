@extends('layouts.theme')

@section('title', 'Plantillas de respuesta')

@section('content')
    @include('core::components.card', ['title' => 'Plantillas de respuesta'])

    <div class="widget-content searchable-container list">
        @include('core::components.alerts')

        <div class="card">
            <!-- Header Section -->
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Plantillas de respuesta</h5>
                        <p class="small mb-0 text-muted">Gestiona plantillas predefinidas para responder reseñas de forma rapida y consistente</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('settings.reviews.templates.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Status Cards -->
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title mb-2">Total plantillas</h6>
                                        <h4 class="mb-1 fw-bold">{{ number_format($stats['total']) }}</h4>
                                        <small class="text-muted">Configuradas en el sistema</small>
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
                                        <h6 class="card-title mb-2">Activas</h6>
                                        <h4 class="mb-1 fw-bold">{{ number_format($stats['active']) }}</h4>
                                        <small class="text-muted">{{ number_format($stats['inactive']) }} inactivas</small>
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
                                        <h6 class="card-title mb-2">Por categoria</h6>
                                        <h4 class="mb-1 fw-bold">{{ number_format($stats['positive']) }}</h4>
                                        <small class="text-muted">Positivas / {{ number_format($stats['negative']) }} negativas</small>
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
                                        <h6 class="card-title mb-2">Total usos</h6>
                                        <h4 class="mb-1 fw-bold">{{ number_format($stats['total_usage']) }}</h4>
                                        <small class="text-muted">Veces utilizadas</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters Section -->
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('settings.reviews.templates.index') }}" id="filter-form">
                    <div class="d-flex gap-2 align-items-center">
                        <div class="input-group flex-grow-1">
                            <span class="input-group-text bg-white">
                                <i class="fas fa-search"></i>
                            </span>
                            <input type="search"
                                   name="search"
                                   class="form-control"
                                   placeholder="Buscar por nombre o contenido..."
                                   value="{{ request('search') }}">
                        </div>
                        <button type="button"
                                class="btn btn-outline-primary position-relative"
                                data-bs-toggle="modal"
                                data-bs-target="#filters-modal"
                                style="min-width: 45px;">
                            <i class="fas fa-filter"></i>
                            @if(request('category') || request('is_active') !== null || request('sort_by'))
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary">
                                    {{ collect([request('category'), request('is_active'), request('sort_by')])->filter()->count() }}
                                </span>
                            @endif
                        </button>
                        @if(request()->hasAny(['search', 'category', 'is_active', 'sort_by']))
                            <a href="{{ route('settings.reviews.templates.index') }}"
                               class="btn btn-outline-secondary"
                               title="Limpiar filtros"
                               style="min-width: 45px;">
                                <i class="fas fa-times"></i>
                            </a>
                        @endif
                        <button type="submit" class="btn btn-primary" style="min-width: 45px;">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>

                    <!-- Hidden inputs for filters -->
                    <input type="hidden" name="category" id="filter-category" value="{{ request('category') }}">
                    <input type="hidden" name="is_active" id="filter-is-active" value="{{ request('is_active') }}">
                    <input type="hidden" name="sort_by" id="filter-sort-by" value="{{ request('sort_by', 'created_at') }}">
                </form>
            </div>

            <!-- Templates List -->
            <div class="card-body">
                <div class="mb-3 d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1 fw-bold">Listado de plantillas</h6>
                        <p class="text-muted small mb-0">Administra todas las plantillas configuradas</p>
                    </div>
                </div>

                <div class="alert alert-info border-0 bg-info-subtle mb-3">
                    <div class="d-flex align-items-start gap-2">
                        <div>
                            <small class="fw-semibold">Variables disponibles:</small>
                            <small class="d-block">{reviewer_name}, {location_name}, {star_rating}, {comment_summary}, {date}</small>
                        </div>
                    </div>
                </div>

                @if($templates->count() > 0)
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="select-all">
                            <label class="form-check-label  text-muted" for="select-all">Seleccionar todo</label>
                        </div>
                        <button type="button" class="btn btn-outline-primary d-none" id="bulk-delete-btn" data-bs-toggle="modal" data-bs-target="#bulk-delete-modal">
                            <i class="fas fa-trash me-1"></i>(<span id="selected-count">0</span>)
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="30"></th>
                                    <th>Nombre</th>
                                    <th>Categoria</th>
                                    <th>Extracto</th>
                                    <th class="text-center">Usos</th>
                                    <th class="text-center">Estado</th>
                                    <th>Creado</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($templates as $template)
                                    <tr>
                                        <td>
                                            <input type="checkbox" class="form-check-input template-checkbox" value="{{ $template->id }}">
                                        </td>
                                        <td>
                                            <strong>{{ $template->name }}</strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-light-subtle text-black">
                                                {{ ucfirst($template->category) }}
                                            </span>
                                        </td>
                                        <td>
                                            <small class="text-muted">{{ Str::limit($template->body, 60) }}</small>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-light text-dark text-black">{{ number_format($template->usage_count) }}</span>
                                        </td>
                                        <td class="text-center">
                                            <form action="{{ route('settings.reviews.templates.toggle-active', $template) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-sm btn-link p-0 border-0">
                                                    @if($template->is_active)
                                                        <span class="badge bg-info-subtle text-black">Activo</span>
                                                    @else
                                                        <span class="badge bg-secondary-subtle text-black">Inactivo</span>
                                                    @endif
                                                </button>
                                            </form>
                                        </td>
                                        <td>
                                            <small class="text-muted">{{ $template->created_at->diffForHumans() }}</small>
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <a href="#"
                                                   class="text-muted"
                                                   data-bs-toggle="dropdown"
                                                   data-bs-auto-close="true"
                                                   data-bs-boundary="viewport">
                                                    <i class="fa fa-ellipsis-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('settings.reviews.templates.edit', $template) }}">
                                                           Editar
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <a class="dropdown-item delete-btn"
                                                           data-bs-toggle="modal"
                                                           data-bs-target="#delete-modal"
                                                           data-url="{{ route('settings.reviews.templates.destroy', $template) }}"
                                                           data-title="Eliminar: {{ $template->name }}">
                                                            Eliminar
                                                        </a>
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
                        <div class="mb-4">
                            <i class="fas fa-file-alt fa-4x text-muted opacity-50"></i>
                        </div>
                        <h5 class="text-muted mb-2">No hay plantillas configuradas</h5>
                        <p class="text-muted small mb-4">Comienza creando tu primera plantilla para responder reseñas mas rapido</p>
                        <a href="{{ route('settings.reviews.templates.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> Crear primera plantilla
                        </a>
                    </div>
                @endif
            </div>

            @if($templates->hasPages())
                <div class="card-footer">{{ $templates->links() }}</div>
            @endif
        </div>
    </div>

    @include('core::components.delete')

    {{-- Modal eliminacion masiva --}}
    <div id="bulk-delete-modal" class="modal fade">
        <div class="modal-dialog modal-md modal-dialog-centered">
            <div class="modal-content">
                <form id="bulk-delete-form" method="POST" action="{{ route('settings.reviews.templates.bulk-delete') }}">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="ids" id="bulk-delete-ids">
                    <div class="modal-header">
                        <h5 class="modal-title">Eliminacion masiva</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body text-center">
                        <div class="display-4 text-warning mb-3">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <h4 class="my-0">Eliminar plantillas seleccionadas?</h4>
                        <p>Se eliminaran <strong id="bulk-count">0</strong> plantillas. Esta accion no se puede deshacer.</p>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-danger">Confirmar eliminacion</button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal filtros avanzados --}}
    <div id="filters-modal" class="modal fade">
        <div class="modal-dialog modal-md modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Filtros avanzados</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Categoría</label>
                        <select class="form-select select2" id="modal-filter-category" data-placeholder="Todas las categorías">
                            <option value="">Todas las categorías</option>
                            <option value="positive" {{ request('category') === 'positive' ? 'selected' : '' }}>Positivas</option>
                            <option value="negative" {{ request('category') === 'negative' ? 'selected' : '' }}>Negativas</option>
                            <option value="neutral" {{ request('category') === 'neutral' ? 'selected' : '' }}>Neutrales</option>
                            <option value="general" {{ request('category') === 'general' ? 'selected' : '' }}>Generales</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Estado</label>
                        <select class="form-select select2" id="modal-filter-is-active" data-placeholder="Todos los estados">
                            <option value="">Todos los estados</option>
                            <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Activas</option>
                            <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Inactivas</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ordenar por</label>
                        <select class="form-select select2" id="modal-filter-sort-by" data-placeholder="Ordenar por">
                            <option value="created_at" {{ request('sort_by', 'created_at') === 'created_at' ? 'selected' : '' }}>Fecha de creación</option>
                            <option value="name" {{ request('sort_by') === 'name' ? 'selected' : '' }}>Nombre</option>
                            <option value="usage_count" {{ request('sort_by') === 'usage_count' ? 'selected' : '' }}>Más usadas</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary w-100" id="apply-filters">Aplicar filtros</button>
                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Inicializar Select2 para filtros avanzados
    $('#modal-filter-category, #modal-filter-is-active, #modal-filter-sort-by').select2({
        minimumResultsForSearch: Infinity,
        width: '100%',
        dropdownParent: $('#filters-modal')
    });

    // Delete modal
    $('.delete-btn').on('click', function() {
        $('#delete-modal .modal-title').text($(this).data('title'));
        $('#delete-form').attr('action', $(this).data('url'));
    });

    // Bulk selection
    const $selectAll = $('#select-all');
    const $checkboxes = $('.template-checkbox');
    const $bulkBtn = $('#bulk-delete-btn');
    const $selectedCount = $('#selected-count');
    const $bulkCount = $('#bulk-count');
    const $bulkIds = $('#bulk-delete-ids');

    function updateBulkState() {
        const selected = $checkboxes.filter(':checked');
        const count = selected.length;

        $bulkBtn.toggleClass('d-none', count === 0);
        $selectedCount.text(count);
        $bulkCount.text(count);
        $bulkIds.val(JSON.stringify(selected.map(function() { return $(this).val(); }).get()));
    }

    $selectAll.on('change', function() {
        $checkboxes.prop('checked', $(this).is(':checked'));
        updateBulkState();
    });

    $checkboxes.on('change', updateBulkState);

    // Advanced filters
    $('#apply-filters').on('click', function() {
        $('#filter-category').val($('#modal-filter-category').val());
        $('#filter-is-active').val($('#modal-filter-is-active').val());
        $('#filter-sort-by').val($('#modal-filter-sort-by').val());
        $('#filters-modal').modal('hide');
        $('#filter-form').submit();
    });
});
</script>
@endpush

