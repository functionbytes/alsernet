@extends('layouts.theme')

@section('title', 'Gestión de reseñas')

@section('content')

    @include('core::components.card', ['title' => 'Gestión de reseñas'])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">

            {{-- Header --}}
            <div class="card-header p-4 border-bottom">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Reseñas de Google My Business</h5>
                        <p class="small mb-0 text-muted">Gestiona y responde las reseñas de tus ubicaciones de Google</p>
                    </div>
                    <div class="d-flex gap-2">
                        <div class="dropdown">
                            <a href="#" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown">
                                Acciones
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="{{ route('import.show', 'reviews') }}">Importar</a></li>
                                <li><a class="dropdown-item" id="export-csv-link" href="#">Exportar CSV</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Stats --}}
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Total reseñas</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['total'] ?? 0) }}</h4>
                                <small class="text-muted">En el sistema</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Rating promedio</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['avg_rating'] ?? 0, 1) }}</h4>
                                <small class="text-muted">de 5.0</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Sin responder</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['unanswered'] ?? 0) }}</h4>
                                <small class="text-muted">Pendientes de respuesta</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Respondidas hoy</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['replied_today'] ?? 0) }}</h4>
                                <small class="text-muted">Respondidas hoy</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Filters --}}
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('reviews.index') }}" id="filter-form">
                    <div class="d-flex flex-column flex-lg-row gap-3 align-items-stretch">
                        <div class="flex-fill">
                            <div class="input-group h-100">
                                <span class="input-group-text bg-white border-end-0">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="search" name="search" id="search-input" class="form-control border-start-0 ps-0"
                                       placeholder="Buscar por cliente o comentario..."
                                       value="{{ request('search') }}">
                            </div>
                        </div>
                        <div class="flex-shrink-0" style="min-width: 190px;">
                            <select name="reply_status" class="form-select select2 h-100" id="filter-reply-status">
                                <option value="">Todos los estados</option>
                                <option value="unanswered" {{ request('reply_status') === 'unanswered' ? 'selected' : '' }}>Sin responder</option>
                                <option value="draft"      {{ request('reply_status') === 'draft'      ? 'selected' : '' }}>Borrador</option>
                                <option value="published"  {{ request('reply_status') === 'published'  ? 'selected' : '' }}>Publicada</option>
                            </select>
                        </div>
                        <div class="flex-shrink-0" style="min-width: 190px;">
                            <select name="location_id" class="form-select select2 h-100" id="filter-location">
                                <option value="">Todas las ubicaciones</option>
                                @foreach($locations ?? [] as $loc)
                                    <option value="{{ $loc->id }}" {{ request('location_id') == $loc->id ? 'selected' : '' }}>
                                        {{ $loc->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="d-flex gap-2 flex-shrink-0">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-1"></i>
                            </button>
                            <button type="button" class="btn btn-outline-secondary" title="Filtros avanzados"
                                    data-bs-toggle="collapse" data-bs-target="#advanced-filters">
                                <i class="fas fa-sliders-h"></i>
                            </button>
                            @if(request()->hasAny(['search', 'reply_status', 'location_id', 'ratings', 'is_visible']))
                                <a href="{{ route('reviews.index') }}" class="btn btn-outline-secondary" title="Limpiar filtros">
                                    <i class="fas fa-times"></i>
                                </a>
                            @endif
                        </div>
                    </div>

                    {{-- Advanced filters --}}
                    <div class="collapse mt-3 {{ request()->hasAny(['ratings', 'is_visible']) ? 'show' : '' }}" id="advanced-filters">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Rating</label>
                                <div class="btn-group w-100" role="group">
                                    @for($i = 1; $i <= 5; $i++)
                                        <input type="checkbox" class="btn-check" name="ratings[]" id="rating-{{ $i }}"
                                               value="{{ $i }}" {{ in_array((string)$i, (array) request('ratings', [])) ? 'checked' : '' }}>
                                        <label class="btn btn-outline-warning" for="rating-{{ $i }}">{{ $i }}★</label>
                                    @endfor
                                </div>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Visibilidad</label>
                                <select name="is_visible" class="form-select" id="filter-visibility">
                                    <option value="">Todas</option>
                                    <option value="1" {{ request('is_visible') === '1' ? 'selected' : '' }}>Visibles</option>
                                    <option value="0" {{ request('is_visible') === '0' ? 'selected' : '' }}>Ocultas</option>
                                </select>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <div>@include('reviews::saved-filters._dropdown')</div>
                        </div>
                    </div>
                </form>
            </div>

            {{-- Table --}}
            <div class="card-body">
                @if($reviews->isEmpty())
                    <div class="text-center py-5">
                        <div class="d-flex flex-column align-items-center">
                            <div class="round-48 rounded-circle bg-light-subtle text-muted mb-3 d-flex align-items-center justify-content-center">
                                <i class="fas fa-star fs-7"></i>
                            </div>
                            <h6 class="mb-1">
                                @if(request()->hasAny(['search', 'reply_status', 'location_id', 'ratings', 'is_visible']))
                                    No se encontraron reseñas
                                @else
                                    No hay reseñas disponibles
                                @endif
                            </h6>
                            <p class="text-muted mb-0">
                                @if(request()->hasAny(['search', 'reply_status', 'location_id', 'ratings', 'is_visible']))
                                    No hay resultados para los criterios de búsqueda
                                @else
                                    Las reseñas sincronizadas desde Google aparecerán aquí
                                @endif
                            </p>
                        </div>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="reviews-table">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 40px;"><input type="checkbox" class="form-check-input" id="select-all"></th>
                                    <th>Fecha</th>
                                    <th>Ubicación</th>
                                    <th>Cliente</th>
                                    <th>Rating</th>
                                    <th>Comentario</th>
                                    <th>Respuesta</th>
                                    <th>Etiquetas</th>
                                    <th>Visible</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($reviews as $review)
                                    @php
                                        $tags = $review->moderation?->tags ?? [];
                                        $replyStatus = $review->reply_status;
                                    @endphp
                                    <tr>
                                        <td><input type="checkbox" class="form-check-input bulk-checkbox" value="{{ $review->id }}"></td>
                                        <td><small>{{ $review->review_time?->format('d/m/Y') ?? '—' }}</small></td>
                                        <td><small>{{ $review->location?->name ?? '—' }}</small></td>
                                        <td><strong>{{ $review->reviewer_name }}</strong></td>
                                        <td>
                                            @for($s = 1; $s <= 5; $s++)
                                                <i class="fas fa-star {{ $s <= $review->star_rating->value() ? 'text-warning' : 'text-muted opacity-25' }}" style="font-size:.8rem;"></i>
                                            @endfor
                                        </td>
                                        <td>
                                            @if($review->comment)
                                                <span title="{{ $review->comment }}">{{ \Illuminate\Support\Str::limit($review->comment, 80) }}</span>
                                            @else
                                                <em class="text-muted">Sin comentario</em>
                                            @endif
                                        </td>
                                        <td>
                                            @if($replyStatus === 'published')
                                                <span class="badge bg-success-subtle text-success">Publicada</span>
                                            @elseif($replyStatus === 'draft')
                                                <span class="badge bg-warning-subtle text-warning">Borrador</span>
                                            @elseif($replyStatus === 'failed')
                                                <span class="badge bg-secondary">Error</span>
                                            @else
                                                <span class="badge bg-danger-subtle text-danger">Sin responder</span>
                                            @endif
                                        </td>
                                        <td>
                                            @forelse($tags as $tag)
                                                <span class="tag-badge">{{ $tag }}</span>
                                            @empty
                                                <span class="text-muted">—</span>
                                            @endforelse
                                        </td>
                                        <td>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input toggle-visibility" type="checkbox"
                                                       {{ $review->is_visible ? 'checked' : '' }}
                                                       data-review-id="{{ $review->id }}">
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <a href="javascript:void(0)" class="text-muted" data-bs-toggle="dropdown">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    @if(!$review->google_reply_text)
                                                        <li>
                                                            <a class="dropdown-item btn-reply" href="javascript:void(0)"
                                                               data-review-id="{{ $review->id }}"
                                                               data-reviewer-name="{{ $review->reviewer_name }}"
                                                               data-star-rating="{{ $review->star_rating->name }}"
                                                               data-comment="{{ $review->comment }}"
                                                               data-location-name="{{ $review->location?->name }}">
                                                                Responder
                                                            </a>
                                                        </li>
                                                    @endif
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('reviews.show', $review->id) }}">
                                                            Ver detalle
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item report-review-btn" href="javascript:void(0)"
                                                           data-review-id="{{ $review->id }}">
                                                            Reportar a Google
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
                @endif
            </div>

            @if($reviews->hasPages())
                <div class="card-footer">
                    {{ $reviews->links() }}
                </div>
            @endif

        </div>
    </div>

    @include('reviews::saved-filters._save-modal')

    {{-- Floating bulk toolbar --}}
    <div id="bulk-toolbar" class="position-fixed bottom-0 start-50 translate-middle-x mb-4 d-none" style="z-index:1050;">
        <button type="button" class="btn btn-primary shadow-lg px-4" data-bs-toggle="modal" data-bs-target="#bulk-modal">
            <span data-bulk-count>0</span> seleccionada(s) &mdash; Aplicar acción
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
                    <p class="text-muted mb-3">Se aplicará la acción sobre <strong><span data-bulk-count>0</span> reseña(s)</strong>.</p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Acción</label>
                        <select id="bulk-action-select" class="form-select">
                            <option value="">Seleccionar acción...</option>
                            <option value="make_visible">Hacer visibles</option>
                            <option value="hide">Ocultar</option>
                            <option value="feature">Marcar como destacadas</option>
                            <option value="unfeature">Quitar destacado</option>
                            <option value="add_tags">Agregar etiquetas...</option>
                            <option value="remove_tags">Quitar etiquetas...</option>
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

    {{-- Bulk tags modal --}}
    <div class="modal fade" id="bulkTagsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="bulkTagsModalTitle">Gestionar etiquetas</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3" id="bulk-selected-info"></p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Etiquetas</label>
                        <select class="form-select select2" id="bulk-tags-select" multiple="multiple"></select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary w-100 mb-1" id="bulk-tags-apply-btn">Aplicar</button>
                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Reply modal --}}
    <div class="modal fade" id="replyModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Responder reseña</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="card mb-3 bg-light border-0">
                        <div class="card-body py-2">
                            <div class="d-flex align-items-center gap-2">
                                <strong id="modal-reviewer-name"></strong>
                                <span id="modal-star-rating"></span>
                            </div>
                            <p class="mb-0 mt-2 text-muted" id="modal-comment"></p>
                        </div>
                    </div>

                    <div id="suggestions-section">
                        <div class="d-flex align-items-center mb-2">
                            <i class="fas fa-lightbulb text-warning me-2"></i>
                            <h6 class="mb-0">Sugerencias de plantillas</h6>
                        </div>
                        <div id="suggestions-loading" class="text-center py-3">
                            <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                            <span class="ms-2 text-muted">Cargando sugerencias...</span>
                        </div>
                        <div id="suggestions-container" class="d-none"></div>
                        <div id="suggestions-empty" class="alert alert-info d-none">
                            No hay plantillas sugeridas para esta reseña. Puedes escribir tu propia respuesta.
                        </div>
                    </div>

                    <div class="mt-3">
                        <label for="reply-text" class="form-label">Tu respuesta</label>
                        <textarea class="form-control" id="reply-text" rows="5" placeholder="Escribe tu respuesta aquí..."></textarea>
                        <small class="form-text text-muted">Variables: {reviewer_name}, {location_name}, {star_rating}</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary w-100 mb-1" id="publish-reply">
                        Guardar y publicar
                    </button>
                    <button type="button" class="btn btn-secondary w-100 mb-1" id="save-reply-draft">
                        Guardar borrador
                    </button>
                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Report modal --}}
    <div class="modal fade" id="reportReviewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Reportar reseña</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">Reportar como inapropiada a Google</p>
                    @foreach(['SPAM' => 'Spam', 'FAKE_REVIEW' => 'Reseña falsa', 'HATE_SPEECH' => 'Discurso de odio', 'HARASSMENT' => 'Acoso', 'OTHER' => 'Otro'] as $value => $label)
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="report_reason" value="{{ $value }}" id="reason_{{ $value }}">
                            <label class="form-check-label" for="reason_{{ $value }}">{{ $label }}</label>
                        </div>
                    @endforeach
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary w-100 mb-1" id="submitReportBtn">Reportar</button>
                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('css')
<style>
    .tag-badge {
        display: inline-block;
        padding: 4px 8px;
        margin: 2px;
        background-color: #90bb13;
        color: #fff;
        border-radius: 4px;
        font-size: 0.75rem;
        white-space: nowrap;
    }
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function () {
    const bulk = window.BulkActions.init({ checkbox: '.bulk-checkbox' });
    let bulkPendingTagAction = null;

    // Select all
    $('#select-all').on('change', function () {
        $('.bulk-checkbox').prop('checked', $(this).is(':checked')).trigger('change');
    });

    // -----------------------------------------------------------------------
    // Tags select2
    // -----------------------------------------------------------------------

    $('#filter-tags, #bulk-tags-select').select2({
        tags: true,
        tokenSeparators: [','],
        placeholder: 'Seleccionar o escribir etiquetas...',
        ajax: {
            url: '{{ route("reviews.tags.list") }}',
            dataType: 'json',
            delay: 250,
            data: function (params) { return { q: params.term }; },
            processResults: function (data) {
                return { results: data.tags.map(tag => ({ id: tag, text: tag })) };
            },
            cache: true,
        },
    });

    // -----------------------------------------------------------------------
    // Bulk modal
    // -----------------------------------------------------------------------

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
        if (!ids.length) { toastr.warning('Selecciona al menos una reseña.'); return; }

        if (action === 'add_tags' || action === 'remove_tags') {
            bulkPendingTagAction = action;
            $('#bulkTagsModalTitle').text(action === 'add_tags' ? 'Agregar etiquetas' : 'Quitar etiquetas');
            $('#bulk-selected-info').text(ids.length + ' reseña(s) seleccionada(s)');
            $('#bulk-tags-select').val(null).trigger('change');
            $('#bulk-modal').modal('hide');
            $('#bulkTagsModal').modal('show');
            return;
        }

        if (!confirm('¿Aplicar "' + action + '" sobre ' + ids.length + ' reseña(s)?')) { return; }

        $('#bulk-apply-btn').prop('disabled', true).text('Procesando...');

        $.ajax({
            url: '{{ route("reviews.bulk-moderate") }}',
            method: 'POST',
            data: { review_ids: ids, action: action },
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                $('#bulk-modal').modal('hide');
                toastr.success(res.message || 'Acción aplicada correctamente.');
                setTimeout(() => location.reload(), 800);
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message ?? 'Error al procesar.');
                $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
            },
        });
    });

    // -----------------------------------------------------------------------
    // Bulk tags modal
    // -----------------------------------------------------------------------

    $('#bulk-tags-select').select2({
        tags: true,
        tokenSeparators: [','],
        placeholder: 'Seleccionar o escribir etiquetas...',
        dropdownParent: $('#bulkTagsModal'),
        ajax: {
            url: '{{ route("reviews.tags.list") }}',
            dataType: 'json',
            delay: 250,
            data: function (params) { return { q: params.term }; },
            processResults: function (data) {
                return { results: data.tags.map(tag => ({ id: tag, text: tag })) };
            },
            cache: true,
        },
    });

    $('#bulk-tags-apply-btn').on('click', function () {
        const tags = $('#bulk-tags-select').val();
        const ids  = bulk.getIds();

        if (!tags || tags.length === 0) { toastr.error('Selecciona al menos una etiqueta.'); return; }

        const btn = $(this);
        btn.prop('disabled', true).text('Procesando...');

        $.ajax({
            url: '{{ route("reviews.bulk-moderate") }}',
            method: 'POST',
            data: { review_ids: ids, action: bulkPendingTagAction, tags: tags },
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                $('#bulkTagsModal').modal('hide');
                toastr.success(res.message || 'Etiquetas actualizadas.');
                setTimeout(() => location.reload(), 800);
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message ?? 'Error al procesar.');
            },
            complete: function () {
                btn.prop('disabled', false).text('Aplicar');
                bulkPendingTagAction = null;
            },
        });
    });

    // -----------------------------------------------------------------------
    // Export
    // -----------------------------------------------------------------------

    $('#export-csv-link').on('click', function (e) {
        e.preventDefault();
        const params = $.param({
            location_id:  $('#filter-location').val(),
            reply_status: $('#filter-reply-status').val(),
        });
        window.location.href = '{{ route("reviews.export") }}?' + params;
    });

    // -----------------------------------------------------------------------
    // Toggle visibility (inline switch)
    // -----------------------------------------------------------------------

    $(document).on('change', '.toggle-visibility', function () {
        const reviewId = $(this).data('review-id');
        const isVisible = $(this).is(':checked');

        $.ajax({
            url: `/reviews/${reviewId}/moderate`,
            method: 'PATCH',
            data: { is_visible: isVisible ? 1 : 0 },
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function () { toastr.success('Visibilidad actualizada'); },
            error: function () { toastr.error('Error al actualizar visibilidad'); },
        });
    });

    // -----------------------------------------------------------------------
    // Reply
    // -----------------------------------------------------------------------

    $(document).on('click', '.btn-reply', function () {
        const btn = $(this);
        window.currentReviewId   = btn.data('review-id');
        window.currentReviewData = {
            reviewer_name: btn.data('reviewer-name'),
            star_rating:   btn.data('star-rating'),
            comment:       btn.data('comment'),
            location_name: btn.data('location-name'),
        };

        $('#modal-reviewer-name').text(window.currentReviewData.reviewer_name);
        $('#modal-star-rating').html(window.renderStars ? window.renderStars(window.currentReviewData.star_rating) : '');
        $('#modal-comment').text(window.currentReviewData.comment || 'Sin comentario');
        $('#reply-text').val('');

        $('#replyModal').modal('show');
        loadSuggestions(window.currentReviewId);
    });

    function loadSuggestions(reviewId) {
        const $loading   = $('#suggestions-loading');
        const $container = $('#suggestions-container');
        const $empty     = $('#suggestions-empty');

        $loading.removeClass('d-none');
        $container.addClass('d-none').empty();
        $empty.addClass('d-none');

        $.ajax({
            url: `/reviews/${reviewId}/suggestions`,
            method: 'GET',
            success: function (response) {
                $loading.addClass('d-none');
                response.suggestions?.length > 0
                    ? displaySuggestions(response.suggestions)
                    : $empty.removeClass('d-none');
            },
            error: function () {
                $loading.addClass('d-none');
                $empty.removeClass('d-none');
            },
        });
    }

    function displaySuggestions(suggestions) {
        const $container = $('#suggestions-container').empty();
        const badgeMap = {
            positive: '<span class="badge bg-success-subtle text-success">Positiva</span>',
            negative: '<span class="badge bg-danger-subtle text-danger">Negativa</span>',
            neutral:  '<span class="badge bg-warning-subtle text-warning">Neutral</span>',
            general:  '<span class="badge bg-secondary-subtle text-secondary">General</span>',
        };

        suggestions.forEach(function (s) {
            const preview = s.body.length > 120 ? s.body.substring(0, 120) + '...' : s.body;
            $container.append(`
                <div class="card mb-2">
                    <div class="card-body py-2">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <strong class="small">${s.name}</strong>
                                    ${badgeMap[s.category] || ''}
                                </div>
                                <p class="mb-0 text-muted">${preview}</p>
                            </div>
                            <button class="btn btn-sm btn-primary ms-2 use-template" data-template-body="${escapeHtml(s.body)}">
                                Usar
                            </button>
                        </div>
                    </div>
                </div>
            `);
        });

        $container.removeClass('d-none');
    }

    function escapeHtml(text) {
        return text.replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));
    }

    $(document).on('click', '.use-template', function () {
        const text = $(this).data('template-body')
            .replace(/{reviewer_name}/g, window.currentReviewData.reviewer_name)
            .replace(/{location_name}/g, window.currentReviewData.location_name)
            .replace(/{star_rating}/g, window.currentReviewData.star_rating);
        $('#reply-text').val(text).focus();
    });

    $('#save-reply-draft').on('click', function () { saveReply('draft'); });
    $('#publish-reply').on('click', function () {
        if (confirm('¿Publicar esta respuesta en Google? Esta acción no se puede deshacer.')) {
            saveReply('published');
        }
    });

    function saveReply(status) {
        const replyText = $('#reply-text').val().trim();
        if (!replyText) { toastr.error('Debes escribir una respuesta'); return; }

        $.ajax({
            url: '{{ route("reviews.replies.store") }}',
            method: 'POST',
            data: { review_id: window.currentReviewId, reply_text: replyText, status: status },
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            beforeSend: function () { $('#save-reply-draft, #publish-reply').prop('disabled', true); },
            success: function (response) {
                toastr.success(response.message || 'Respuesta guardada correctamente');
                $('#replyModal').modal('hide');
                setTimeout(() => location.reload(), 800);
            },
            error: function (xhr) {
                const message = xhr.status === 422
                    ? Object.values(xhr.responseJSON?.errors ?? {}).flat().join('\n')
                    : (xhr.responseJSON?.message || 'Error al guardar respuesta');
                toastr.error(message);
            },
            complete: function () { $('#save-reply-draft, #publish-reply').prop('disabled', false); },
        });
    }

    // -----------------------------------------------------------------------
    // Report
    // -----------------------------------------------------------------------

    $(document).on('click', '.report-review-btn', function () {
        $('input[name="report_reason"]').prop('checked', false);
        $('#reportReviewModal').data('review-id', $(this).data('review-id')).modal('show');
    });

    $('#submitReportBtn').on('click', function () {
        const reviewId = $('#reportReviewModal').data('review-id');
        const reason   = $('input[name="report_reason"]:checked').val();

        if (!reason) { toastr.warning('Selecciona un motivo'); return; }

        const btn = $(this);
        btn.prop('disabled', true).text('Reportando...');

        $.ajax({
            url: `/reviews/reviews/${reviewId}/report`,
            method: 'POST',
            data: { reason, _token: $('meta[name="csrf-token"]').attr('content') },
            success() { $('#reportReviewModal').modal('hide'); toastr.success('Reseña reportada a Google'); },
            error()   { toastr.error('Error al reportar. Intenta de nuevo.'); },
            complete() { btn.prop('disabled', false).text('Reportar'); },
        });
    });

    // -----------------------------------------------------------------------
    // Char counter
    // -----------------------------------------------------------------------

    $(document).on('input', '#reply-text', function () {
        const max = 4096;
        const len = $(this).val().length;
        let counter = $(this).next('.char-counter');
        if (!counter.length) {
            counter = $('<small class="char-counter d-block mt-1"></small>');
            $(this).after(counter);
        }
        counter.text(`${len} / ${max} caracteres`)
               .toggleClass('text-danger', len > max - 100)
               .toggleClass('text-muted', len <= max - 100);
        $('#save-reply-draft, #publish-reply').prop('disabled', len > max);
    });

    // Flash
    @if(session('success'))
        toastr.success(@json(session('success')));
    @endif
    @if(session('error'))
        toastr.error(@json(session('error')));
    @endif
});
</script>
<script src="{{ asset('modules/Reviews/resources/js/reviews-index.js') }}"></script>
@endpush
