@extends('layouts.theme')

@section('title', 'Redirecciones SEO')

@section('content')
    @include('core::components.card', ['title' => 'Redirecciones SEO'])

    <div class="widget-content searchable-container list">
        @include('core::components.alerts')

        <div class="card">
            <!-- Header Section -->
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Redirecciones SEO</h5>
                        <p class="small mb-0 text-muted">Gestiona las redirecciones URL para mejorar el SEO y la experiencia de usuario</p>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button"
                                class="btn btn-outline-primary"
                                data-bs-toggle="modal"
                                data-bs-target="#clear-cache-modal">
                            <i class="fas fa-sync me-1"></i>
                        </button>
                        <a href="{{ route('setting.seo.redirects.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> Nueva redireccion
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
                                        <h6 class="card-title text-primary mb-2">Total de redirecciones</h6>
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
                                        <h6 class="card-title text-success mb-2">Activas</h6>
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
                                        <h6 class="card-title text-info mb-2">Por tipo</h6>
                                        <h4 class="mb-1 fw-bold">{{ number_format($stats['permanent']) }}</h4>
                                        <small class="text-muted">301 permanente / {{ number_format($stats['temporary']) }} 302 temporal</small>
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
                                        <h6 class="card-title text-warning mb-2">Total de visitas</h6>
                                        <h4 class="mb-1 fw-bold">{{ number_format($stats['total_hits']) }}</h4>
                                        <small class="text-muted">Redirecciones procesadas</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters Section -->
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('setting.seo.redirects.index') }}" id="filter-form">
                    <div class="row g-3 align-items-center">
                        <div class="col-md-6">
                            <div class="input-group input-group-lg">
                                <span class="input-group-text bg-white border-end-0">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="text"
                                       name="search"
                                       class="form-control border-start-0 ps-0"
                                       placeholder="Buscar por ruta origen o destino..."
                                       value="{{ request('search') }}">
                            </div>
                        </div>
                        <div class="col-md-6 text-end">
                            <button type="button"
                                    class="btn btn-outline-primary"
                                    data-bs-toggle="modal"
                                    data-bs-target="#filters-modal">
                                <i class="fas fa-filter me-2"></i>Filtros avanzados
                                @if(request('status_code') || request('is_active') !== null || request('sort_by'))
                                    <span class="badge bg-primary ms-1">
                                        {{ collect([request('status_code'), request('is_active'), request('sort_by')])->filter()->count() }}
                                    </span>
                                @endif
                            </button>
                            @if(request()->hasAny(['search', 'status_code', 'is_active', 'sort_by']))
                                <a href="{{ route('setting.seo.redirects.index') }}"
                                   class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-1"></i>Limpiar
                                </a>
                            @endif
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-1"></i>Buscar
                            </button>
                        </div>
                    </div>

                    <!-- Hidden inputs for filters -->
                    <input type="hidden" name="status_code" id="filter-status-code" value="{{ request('status_code') }}">
                    <input type="hidden" name="is_active" id="filter-is-active" value="{{ request('is_active') }}">
                    <input type="hidden" name="sort_by" id="filter-sort-by" value="{{ request('sort_by', 'created_at') }}">
                </form>
            </div>

            <!-- Redirects List -->
            <div class="card-body">
                <div class="mb-3 d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1 fw-bold">Listado de redirecciones</h6>
                        <p class="text-muted small mb-0">Administra todas las redirecciones configuradas</p>
                    </div>
                </div>

                <div class="alert alert-info border-0 bg-info-subtle mb-3">
                    <div class="d-flex align-items-start gap-2">
                        <div>
                            <small class="fw-semibold">Importante:</small>
                            <small class="d-block">Las redirecciones 301 son permanentes y son las recomendadas para SEO. Las 302 son temporales.</small>
                        </div>
                    </div>
                </div>

                @if($redirects->count() > 0)
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="select-all">
                            <label class="form-check-label small text-muted" for="select-all">Seleccionar todo</label>
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
                                    <th>Ruta origen</th>
                                    <th>Ruta destino</th>
                                    <th class="text-center">Codigo</th>
                                    <th class="text-center">Visitas</th>
                                    <th class="text-center">Estado</th>
                                    <th>Creado</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($redirects as $redirect)
                                    <tr>
                                        <td>
                                            <input type="checkbox" class="form-check-input redirect-checkbox" value="{{ $redirect->id }}">
                                        </td>
                                        <td>
                                            <code class="text-primary">{{ $redirect->source_path }}</code>
                                        </td>
                                        <td>
                                            <code class="text-muted">{{ $redirect->target_path }}</code>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge {{ $redirect->isPermanent() ? 'bg-success-subtle  text-white' : 'bg-info-subtle  text-white' }}">
                                                {{ $redirect->status_code }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-light text-dark text-black">{{ number_format($redirect->hits_count) }}</span>
                                        </td>
                                        <td class="text-center">
                                            <form action="{{ route('setting.seo.redirects.toggle-active', $redirect) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-sm btn-link p-0 border-0">
                                                    @if($redirect->is_active)
                                                        <span class="badge bg-success-subtle text-white">Activo</span>
                                                    @else
                                                        <span class="badge bg-secondary-subtle text-secondary">Inactivo</span>
                                                    @endif
                                                </button>
                                            </form>
                                        </td>
                                        <td>
                                            <small class="text-muted">{{ $redirect->created_at->diffForHumans() }}</small>
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <a href="#" class="text-muted" data-bs-toggle="dropdown">
                                                    <i class="fa fa-ellipsis-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('setting.seo.redirects.edit', $redirect) }}">
                                                           Editar
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <a class="dropdown-item delete-btn"
                                                           data-bs-toggle="modal"
                                                           data-bs-target="#delete-modal"
                                                           data-url="{{ route('setting.seo.redirects.destroy', $redirect) }}"
                                                           data-title="Eliminar: {{ $redirect->source_path }}">
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
                            <i class="fas fa-route fa-4x text-muted opacity-50"></i>
                        </div>
                        <h5 class="text-muted mb-2">No hay redirecciones configuradas</h5>
                        <p class="text-muted small mb-4">Comienza creando tu primera redirección para mejorar el SEO de tu sitio</p>
                        <a href="{{ route('setting.seo.redirects.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> Crear primera redireccion
                        </a>
                    </div>
                @endif
            </div>

            @if($redirects->hasPages())
                <div class="card-footer">{{ $redirects->links() }}</div>
            @endif
        </div>
    </div>

    @include('core::components.delete')

    {{-- Modal eliminacion masiva --}}
    <div id="bulk-delete-modal" class="modal fade">
        <div class="modal-dialog modal-md modal-dialog-centered">
            <div class="modal-content">
                <form id="bulk-delete-form" method="POST" action="{{ route('setting.seo.redirects.bulk-delete') }}">
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
                        <h4 class="my-0">Eliminar redirecciones seleccionadas?</h4>
                        <p>Se eliminaran <strong id="bulk-count">0</strong> redirecciones. Esta accion no se puede deshacer.</p>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-danger">Confirmar eliminacion</button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal limpiar cache --}}
    <div id="clear-cache-modal" class="modal fade">
        <div class="modal-dialog modal-md modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center px-4 pb-4">
                    <div class="mb-3">
                        <i class="fas fa-sync fa-3x text-primary"></i>
                    </div>
                    <h4 class="mb-2">Limpiar cache de redirecciones</h4>
                    <p class="text-muted mb-4">
                        Se limpiará toda la cache de redirecciones almacenada. Las redirecciones se volverán a cachear automáticamente cuando se utilicen.
                    </p>
                    <div class="d-grid gap-2">
                        <a href="{{ route('setting.seo.redirects.clear-cache') }}" class="btn btn-primary">
                            <i class="fas fa-sync me-2"></i>Confirmar y limpiar cache
                        </a>
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            Cancelar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Delete modal
    $('.delete-btn').on('click', function() {
        $('#delete-modal .modal-title').text($(this).data('title'));
        $('#delete-form').attr('action', $(this).data('url'));
    });

    // Bulk selection
    const $selectAll = $('#select-all');
    const $checkboxes = $('.redirect-checkbox');
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
});
</script>
@endpush
