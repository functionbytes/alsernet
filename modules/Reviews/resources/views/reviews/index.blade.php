@extends('layouts.theme')

@section('title', 'Gestión de reseñas')

@section('content')

    @include('core::components.card', ['title' => 'Reseñas de Google My Business'])

    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-lg-3 col-md-6">
            <x-reviews::stats-widget
                title="Total reseñas"
                :value="$stats['total'] ?? 0"
                icon="fas fa-star"
            />
        </div>
        <div class="col-lg-3 col-md-6">
            <x-reviews::stats-widget
                title="Rating promedio"
                :value="number_format($stats['avg_rating'] ?? 0, 1)"
                icon="fas fa-chart-line"
                subtitle="de 5.0"
            />
        </div>
        <div class="col-lg-3 col-md-6">
            <x-reviews::stats-widget
                title="Sin responder"
                :value="$stats['unanswered'] ?? 0"
                icon="fas fa-exclamation-circle"
            />
        </div>
        <div class="col-lg-3 col-md-6">
            <x-reviews::stats-widget
                title="Respondidas hoy"
                :value="$stats['replied_today'] ?? 0"
                icon="fas fa-check-circle"
            />
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-3">
        <div class="card-body">
            <a data-bs-toggle="collapse" href="#filters" class="btn btn-light">
                <i class="fas fa-filter"></i> Filtros
            </a>
            <div class="collapse mt-3" id="filters">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Ubicación</label>
                        <select class="form-select" id="filter-location">
                            <option value="">Todas las ubicaciones</option>
                            @foreach($locations ?? [] as $loc)
                                <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Rating</label>
                        <div class="btn-group w-100" role="group">
                            @for($i = 1; $i <= 5; $i++)
                                <input type="checkbox" class="btn-check filter-rating" id="rating-{{ $i }}" value="{{ $i }}">
                                <label class="btn btn-outline-warning" for="rating-{{ $i }}">{{ $i }}★</label>
                            @endfor
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Estado respuesta</label>
                        <select class="form-select" id="filter-reply-status">
                            <option value="">Todas</option>
                            <option value="unanswered">Sin responder</option>
                            <option value="draft">Borrador</option>
                            <option value="published">Publicada</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Visibilidad</label>
                        <select class="form-select" id="filter-visibility">
                            <option value="">Todas</option>
                            <option value="1">Visibles</option>
                            <option value="0">Ocultas</option>
                        </select>
                    </div>
                </div>
                <div class="mt-3 d-flex gap-2">
                    <button class="btn btn-primary" id="apply-filters">
                        <i class="fas fa-search"></i> Aplicar filtros
                    </button>
                    <button class="btn btn-light" id="clear-filters">Limpiar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Barra de acciones en lote (oculta por defecto) -->
    <div class="card mb-3" id="bulk-actions-bar" style="display: none;">
        <div class="card-body py-2">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="fas fa-check-circle text-primary"></i>
                    <span class="fw-semibold">
                        <span id="selected-count">0</span> reseñas seleccionadas
                    </span>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-success" id="bulk-make-visible" title="Hacer visibles">
                        <i class="fas fa-eye"></i> Visibles
                    </button>
                    <button class="btn btn-sm btn-secondary" id="bulk-hide" title="Ocultar">
                        <i class="fas fa-eye-slash"></i> Ocultar
                    </button>
                    <button class="btn btn-sm btn-warning" id="bulk-feature" title="Marcar destacadas">
                        <i class="fas fa-star"></i> Destacadas
                    </button>
                    <button class="btn btn-sm btn-outline-warning" id="bulk-unfeature" title="Quitar destacadas">
                        <i class="far fa-star"></i> Quitar
                    </button>
                    <button class="btn btn-sm btn-light" id="cancel-selection" title="Cancelar selección">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Reviews Table -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold">Listado de reseñas</h6>
            <button class="btn btn-sm btn-success" id="export-csv">
                <i class="fas fa-file-csv"></i> Exportar CSV
            </button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="reviews-table" class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 40px;">
                                <input type="checkbox" class="form-check-input" id="select-all">
                            </th>
                            <th>Fecha</th>
                            <th>Ubicación</th>
                            <th>Cliente</th>
                            <th>Rating</th>
                            <th>Comentario</th>
                            <th>Respuesta</th>
                            <th>Visible</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- DataTables cargará el contenido vía AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Reply Modal -->
    <div class="modal fade" id="replyModal" tabindex="-1" aria-labelledby="replyModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="replyModalLabel">Responder reseña</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Review Info -->
                    <div class="card mb-3 bg-light border-0">
                        <div class="card-body py-2">
                            <div class="d-flex align-items-center gap-2">
                                <strong id="modal-reviewer-name"></strong>
                                <span id="modal-star-rating"></span>
                            </div>
                            <p class="mb-0 mt-2 text-muted small" id="modal-comment"></p>
                        </div>
                    </div>

                    <!-- Suggestions Section -->
                    <div id="suggestions-section">
                        <div class="d-flex align-items-center mb-2">
                            <i class="fas fa-lightbulb text-warning me-2"></i>
                            <h6 class="mb-0">Sugerencias de plantillas</h6>
                        </div>
                        <div id="suggestions-loading" class="text-center py-3">
                            <div class="spinner-border spinner-border-sm text-primary" role="status">
                                <span class="visually-hidden">Cargando...</span>
                            </div>
                            <span class="ms-2 text-muted">Cargando sugerencias...</span>
                        </div>
                        <div id="suggestions-container" class="d-none"></div>
                        <div id="suggestions-empty" class="alert alert-info d-none">
                            <i class="fas fa-info-circle"></i> No hay plantillas sugeridas para esta reseña. Puedes escribir tu propia respuesta.
                        </div>
                    </div>

                    <!-- Reply Form -->
                    <div class="mt-3">
                        <label for="reply-text" class="form-label">Tu respuesta</label>
                        <textarea class="form-control" id="reply-text" rows="5" placeholder="Escribe tu respuesta aquí..."></textarea>
                        <small class="form-text text-muted">
                            Variables disponibles: {reviewer_name}, {location_name}, {star_rating}
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-warning" id="save-reply-draft">
                        <i class="fas fa-save"></i> Guardar borrador
                    </button>
                    <button type="button" class="btn btn-success" id="publish-reply">
                        <i class="fas fa-check"></i> Guardar y publicar
                    </button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Inicializar DataTable
    window.table = $('#reviews-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("reviews.data") }}',
            data: function(d) {
                d.location_id = $('#filter-location').val();
                d.ratings = $('.filter-rating:checked').map(function() { return $(this).val(); }).get();
                d.reply_status = $('#filter-reply-status').val();
                d.visibility = $('#filter-visibility').val();
            }
        },
        columns: [
            { data: 'id', orderable: false, searchable: false, render: function(data) {
                return `<input type="checkbox" class="form-check-input review-checkbox" value="${data}">`;
            }},
            { data: 'review_time', name: 'review_time', render: function(data) {
                return window.formatReviewDate ? window.formatReviewDate(data) : data;
            }},
            { data: 'location_name', name: 'location.name' },
            { data: 'reviewer_name', name: 'reviewer_name' },
            { data: 'star_rating', name: 'star_rating', orderable: false, render: function(data) {
                return window.renderStars ? window.renderStars(data) : data;
            }},
            { data: 'comment', name: 'comment', render: function(data) {
                if (!data) return '<em class="text-muted">Sin comentario</em>';
                return data.length > 100 ? data.substring(0, 100) + '...' : data;
            }},
            { data: 'reply_status', name: 'reply_status', render: function(data) {
                const badges = {
                    'unanswered': '<span class="badge bg-danger-subtle text-danger">Sin responder</span>',
                    'draft': '<span class="badge bg-warning-subtle text-warning">Borrador</span>',
                    'published': '<span class="badge bg-success-subtle text-success">Publicada</span>',
                    'failed': '<span class="badge bg-secondary">Error</span>',
                };
                return badges[data] || '';
            }},
            { data: 'is_visible', name: 'moderation.is_visible', orderable: false, render: function(data, type, row) {
                const checked = data ? 'checked' : '';
                return `<div class="form-check form-switch">
                    <input class="form-check-input toggle-visibility" type="checkbox" ${checked} data-review-id="${row.id}">
                </div>`;
            }},
            { data: 'actions', orderable: false, searchable: false },
        ],
        order: [[1, 'desc']],
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
        }
    });

    // Aplicar filtros
    $('#apply-filters').on('click', function() {
        table.ajax.reload();
    });

    // Limpiar filtros
    $('#clear-filters').on('click', function() {
        $('#filter-location').val('');
        $('#filter-reply-status').val('');
        $('#filter-visibility').val('');
        $('.filter-rating').prop('checked', false);
        table.ajax.reload();
    });

    // Toggle visibilidad
    $(document).on('change', '.toggle-visibility', function() {
        const reviewId = $(this).data('review-id');
        const isVisible = $(this).is(':checked');

        $.ajax({
            url: `/reviews/${reviewId}/moderate`,
            method: 'PATCH',
            data: { is_visible: isVisible ? 1 : 0 },
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function() {
                toastr.success('Visibilidad actualizada');
            },
            error: function() {
                toastr.error('Error al actualizar visibilidad');
            }
        });
    });

    // Exportar CSV
    $('#export-csv').on('click', function() {
        const params = $.param({
            location_id: $('#filter-location').val(),
            ratings: $('.filter-rating:checked').map(function() { return $(this).val(); }).get(),
            reply_status: $('#filter-reply-status').val(),
            visibility: $('#filter-visibility').val()
        });
        window.location.href = '{{ route("reviews.export") }}?' + params;
    });

    // Handle Reply button click
    $(document).on('click', '.btn-reply', function() {
        const reviewId = $(this).data('review-id');
        const row = table.row($(this).closest('tr')).data();

        window.currentReviewId = reviewId;
        window.currentReviewData = row;

        // Populate modal with review info
        $('#modal-reviewer-name').text(row.reviewer_name);
        $('#modal-star-rating').html(window.renderStars(row.star_rating));
        $('#modal-comment').text(row.comment || 'Sin comentario');
        $('#reply-text').val('');

        // Show modal
        $('#replyModal').modal('show');

        // Load suggestions
        loadSuggestions(reviewId);
    });

    function loadSuggestions(reviewId) {
        const $loading = $('#suggestions-loading');
        const $container = $('#suggestions-container');
        const $empty = $('#suggestions-empty');

        $loading.removeClass('d-none');
        $container.addClass('d-none').empty();
        $empty.addClass('d-none');

        $.ajax({
            url: `/reviews/${reviewId}/suggestions`,
            method: 'GET',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function(response) {
                $loading.addClass('d-none');
                response.suggestions?.length > 0
                    ? displaySuggestions(response.suggestions)
                    : $empty.removeClass('d-none');
            },
            error: function() {
                $loading.addClass('d-none');
                $empty.removeClass('d-none');
            }
        });
    }

    function displaySuggestions(suggestions) {
        const $container = $('#suggestions-container').empty();

        suggestions.forEach(function(suggestion) {
            const preview = suggestion.body.length > 120
                ? suggestion.body.substring(0, 120) + '...'
                : suggestion.body;

            $container.append(`
                <div class="card mb-2">
                    <div class="card-body py-2">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <strong class="small">${suggestion.name}</strong>
                                    ${getCategoryBadge(suggestion.category)}
                                </div>
                                <p class="mb-0 text-muted small">${preview}</p>
                            </div>
                            <button class="btn btn-sm btn-success ms-2 use-template" data-template-body="${escapeHtml(suggestion.body)}">
                                <i class="fas fa-check"></i> Usar
                            </button>
                        </div>
                    </div>
                </div>
            `);
        });

        $container.removeClass('d-none');
    }

    function getCategoryBadge(category) {
        const badges = {
            'positive': '<span class="badge bg-success-subtle text-success">Positiva</span>',
            'negative': '<span class="badge bg-danger-subtle text-danger">Negativa</span>',
            'neutral': '<span class="badge bg-warning-subtle text-warning">Neutral</span>',
            'general': '<span class="badge bg-secondary-subtle text-secondary">General</span>',
        };
        return badges[category] || '';
    }

    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    // Use template button
    $(document).on('click', '.use-template', function() {
        const text = $(this).data('template-body')
            .replace(/{reviewer_name}/g, window.currentReviewData.reviewer_name)
            .replace(/{location_name}/g, window.currentReviewData.location_name)
            .replace(/{star_rating}/g, window.currentReviewData.star_rating);

        $('#reply-text').val(text).focus();
    });

    // Save draft
    $('#save-reply-draft').on('click', function() {
        saveReply('draft');
    });

    // Publish reply
    $('#publish-reply').on('click', function() {
        if (confirm('¿Publicar esta respuesta en Google? Esta acción no se puede deshacer.')) {
            saveReply('published');
        }
    });

    function saveReply(status) {
        const replyText = $('#reply-text').val().trim();

        if (!replyText) {
            toastr.error('Debes escribir una respuesta');
            return;
        }

        $.ajax({
            url: '{{ route("reviews.replies.store") }}',
            method: 'POST',
            data: {
                review_id: window.currentReviewId,
                reply_text: replyText,
                status: status
            },
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            beforeSend: function() {
                $('#save-reply-draft, #publish-reply').prop('disabled', true);
            },
            success: function(response) {
                toastr.success(response.message || 'Respuesta guardada correctamente');
                $('#replyModal').modal('hide');
                table.ajax.reload(null, false);
            },
            error: function(xhr) {
                const message = xhr.status === 422
                    ? (xhr.responseJSON?.errors ? Object.values(xhr.responseJSON.errors).flat().join('\n') : 'Error de validación')
                    : (xhr.responseJSON?.message || 'Error al guardar respuesta');
                toastr.error(message);
            },
            complete: function() {
                $('#save-reply-draft, #publish-reply').prop('disabled', false);
            }
        });
    }
});
</script>
<script src="{{ asset('modules/Reviews/resources/js/reviews-index.js') }}"></script>
@endpush
