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
                        @if(request()->hasAny(['search', 'category', 'is_active', 'sort_by', 'location_id']))
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

                    <!-- Location filter -->
                    @if($locations->count() > 0)
                        <div class="mt-2">
                            <select name="location_id" class="form-select select2"
                                    data-placeholder="Todas las ubicaciones"
                                    onchange="this.form.submit()">
                                <option value="">Todas las ubicaciones</option>
                                @foreach($locations as $location)
                                    <option value="{{ $location->id }}"
                                        {{ request('location_id') == $location->id ? 'selected' : '' }}>
                                        {{ $location->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

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
                        <p class="text-muted mb-0">Administra todas las plantillas configuradas</p>
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
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="30"><input type="checkbox" id="select-all" class="form-check-input"></th>
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
                                            <input type="checkbox" class="form-check-input bulk-checkbox" value="{{ $template->id }}">
                                        </td>
                                        <td>
                                            <strong>{{ $template->name }}</strong>
                                            @if($template->review_google_location_id)
                                                <span class="badge bg-primary-subtle text-primary ms-1" title="Ubicación específica">
                                                    <i class="fas fa-map-marker-alt"></i> {{ $template->location?->name ?? '—' }}
                                                </span>
                                            @endif
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
                                                        <a class="dropdown-item preview-template-btn"
                                                           href="#"
                                                           data-template-id="{{ $template->id }}">
                                                            Vista previa
                                                        </a>
                                                    </li>
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
                        <p class="text-muted mb-4">Comienza creando tu primera plantilla para responder reseñas mas rapido</p>
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

    @include('reviews::partials.template-preview')

    {{-- Bulk toolbar flotante --}}
    <div id="bulk-toolbar" class="position-fixed bottom-0 start-50 translate-middle-x mb-4 d-none" style="z-index:1050;">
        <button type="button" class="btn btn-primary shadow-lg px-4" data-bs-toggle="modal" data-bs-target="#bulk-modal">
            <span data-bulk-count>0</span> seleccionado(s) &mdash; Aplicar acción
        </button>
    </div>

    {{-- Bulk modal --}}
    <div class="modal fade" id="bulk-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Acción masiva</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">Se aplicará la acción sobre <strong><span data-bulk-count>0</span> plantilla(s)</strong>.</p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Acción</label>
                        <select id="bulk-action-select" class="form-select">
                            <option value="">Seleccionar acción...</option>
                            <option value="activate">Activar</option>
                            <option value="deactivate">Desactivar</option>
                            <option value="delete">Eliminar</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button id="bulk-apply-btn" type="button" class="btn btn-primary w-100 mb-1">Aplicar</button>
                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
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
    const bulk = window.BulkActions.init({ checkbox: '.bulk-checkbox' });

    $('#bulk-action-select').select2({ dropdownParent: $('#bulk-modal'), width: '100%' });

    $('#bulk-modal').on('hide.bs.modal', function () {
        $('#bulk-action-select').val('').trigger('change');
        $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
        bulk.reset();
    });

    $('#bulk-apply-btn').on('click', function () {
        const action = $('#bulk-action-select').val();
        const ids    = bulk.getIds();

        if (!action) { toastr.warning('Selecciona una acción.'); return; }
        if (!ids.length) { toastr.warning('Selecciona al menos una plantilla.'); return; }
        if (action === 'delete' && !confirm('¿Eliminar las ' + ids.length + ' plantilla(s) seleccionadas?')) { return; }

        $('#bulk-apply-btn').prop('disabled', true).text('Procesando...');

        $.ajax({
            url: '{{ route('settings.reviews.templates.bulk-action') }}',
            method: 'POST',
            data: JSON.stringify({ action, ids, _token: $('meta[name="csrf-token"]').attr('content') }),
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                $('#bulk-modal').modal('hide');
                toastr.success(res.count + ' plantilla(s) actualizadas.');
                setTimeout(() => location.reload(), 800);
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message ?? 'Error al procesar.');
                $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
            },
        });
    });

    // Advanced filters
    $('#apply-filters').on('click', function() {
        $('#filter-category').val($('#modal-filter-category').val());
        $('#filter-is-active').val($('#modal-filter-is-active').val());
        $('#filter-sort-by').val($('#modal-filter-sort-by').val());
        $('#filters-modal').modal('hide');
        $('#filter-form').submit();
    });

    // Template preview panel
    const previewUrl = '{{ rtrim(route("reviews.dashboard"), "/") }}/templates/';
    const $panel = $('#templatePreviewPanel');
    const $loading = $('#templatePreviewLoading');
    const $content = $('#templatePreviewContent');
    const $applyBtn = $('#applyTemplateBtn');
    let offcanvasInstance = null;

    $(document).on('click', '.preview-template-btn', function(e) {
        e.preventDefault();

        const templateId = $(this).data('template-id');
        const reviewId = $(this).data('review-id') || null;

        $loading.removeClass('d-none');
        $content.addClass('d-none');
        $applyBtn.prop('disabled', true);

        if (!offcanvasInstance) {
            offcanvasInstance = new bootstrap.Offcanvas(document.getElementById('templatePreviewPanel'));
        }
        offcanvasInstance.show();

        const params = reviewId ? { review_id: reviewId } : {};

        $.get(previewUrl + templateId + '/preview', params, function(data) {
            $('#previewTemplateName').text(data.name || '—');
            $('#previewTemplateCategory').text(data.category || '—');
            $('#previewTemplateText').text(data.template_text || '');
            $('#previewTemplateRendered').text(data.rendered_text || data.template_text || '');

            $applyBtn.data('template-id', templateId).prop('disabled', false);
        }).fail(function(xhr) {
            const msg = xhr.responseJSON?.message || 'No se pudo cargar el template.';
            toastr.error(msg);
            if (offcanvasInstance) {
                offcanvasInstance.hide();
            }
        }).always(function() {
            $loading.addClass('d-none');
            $content.removeClass('d-none');
        });
    });

    $applyBtn.on('click', function() {
        const text = $('#previewTemplateText').text();
        $('textarea[name="reply_text"], #replyTextarea').val(text).trigger('input');
        toastr.success('Template aplicado correctamente');

        if (offcanvasInstance) {
            offcanvasInstance.hide();
        }
    });
});
</script>
@endpush

