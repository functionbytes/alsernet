<!-- Save Filter Modal -->
<div class="modal fade" id="saveFilterModal" tabindex="-1" aria-labelledby="saveFilterModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="saveFilterModalLabel">Guardar filtros actuales</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="save-filter-form">
                    <div class="mb-3">
                        <label for="new-filter-name" class="form-label">Nombre del filtro</label>
                        <input type="text" class="form-control" id="new-filter-name" required maxlength="100"
                               placeholder="Ej: Reseñas sin responder últimos 7 días">
                        <small class="form-text text-muted">Elige un nombre descriptivo que te ayude a identificar este filtro.</small>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="set-as-default">
                            <label class="form-check-label" for="set-as-default">
                                Establecer como filtro predeterminado
                            </label>
                        </div>
                    </div>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Filtros que se guardarán:</strong>
                        <ul class="mb-0 mt-2" id="filter-preview">
                            <li class="text-muted">Configura los filtros en la lista de reseñas</li>
                        </ul>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btn-confirm-save-filter">
                    <i class="fas fa-save"></i> Guardar filtro
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    // Load saved filters into dropdown
    function loadSavedFilters() {
        // Using a dedicated API endpoint for JSON response
        $.ajax({
            url: '{{ route("reviews.saved-filters.index") }}',
            method: 'GET',
            dataType: 'json',
            success: function(filters) {
                const $list = $('#saved-filters-list');
                $list.empty();

                // Handle both array response and object with data property
                const filterList = Array.isArray(filters) ? filters : (filters.data || []);

                if (filterList.length === 0) {
                    $list.append('<li class="px-3 py-2 text-muted">No hay filtros guardados</li>');
                    $list.append('<li><hr class="dropdown-divider"></li>');
                    $list.append(`
                        <li>
                            <a class="dropdown-item" href="{{ route('reviews.saved-filters.index') }}">
                                <i class="fas fa-cog"></i> Gestionar filtros
                            </a>
                        </li>
                    `);
                    return;
                }

                filterList.forEach(function(filter) {
                    const defaultBadge = filter.is_default
                        ? '<span class="badge bg-success ms-1">Predeterminado</span>'
                        : '';

                    $list.append(`
                        <li>
                            <form action="/reviews/saved-filters/${filter.id}/apply" method="POST" class="px-3 py-2">
                                <input type="hidden" name="_token" value="${$('meta[name="csrf-token"]').attr('content')}">
                                <button type="submit" class="btn btn-link text-decoration-none text-start w-100 p-0">
                                    <i class="fas fa-filter text-primary"></i>
                                    ${filter.name} ${defaultBadge}
                                </button>
                            </form>
                        </li>
                    `);
                });

                $list.append('<li><hr class="dropdown-divider"></li>');
                $list.append(`
                    <li>
                        <a class="dropdown-item" href="{{ route('reviews.saved-filters.index') }}">
                            <i class="fas fa-cog"></i> Gestionar filtros
                        </a>
                    </li>
                `);
            },
            error: function() {
                $('#saved-filters-list').html('<li class="px-3 py-2 text-muted">Error al cargar filtros</li>');
            }
        });
    }

    // Load on page load
    loadSavedFilters();

    // Open save filter modal
    $('#btn-save-current-filter').on('click', function() {
        const currentFilters = getCurrentFilters();
        displayFilterPreview(currentFilters);
        $('#saveFilterModal').modal('show');
    });

    // Get current filter state
    function getCurrentFilters() {
        const filters = {};

        // Location
        const locationId = $('#filter-location').val();
        if (locationId) {
            filters.location_id = locationId;
        }

        // Star ratings
        const ratings = $('.filter-rating:checked').map(function() {
            return $(this).val();
        }).get();
        if (ratings.length > 0) {
            filters.star_rating = ratings;
        }

        // Reply status
        const replyStatus = $('#filter-reply-status').val();
        if (replyStatus === 'unanswered') {
            filters.has_google_reply = false;
        } else if (replyStatus === 'published') {
            filters.has_google_reply = true;
        }

        // Visibility
        const visibility = $('#filter-visibility').val();
        if (visibility !== '') {
            filters.is_visible = visibility === '1';
        }

        // Add more filters as needed...

        return filters;
    }

    // Display filter preview
    function displayFilterPreview(filters) {
        const $preview = $('#filter-preview');
        $preview.empty();

        if (Object.keys(filters).length === 0) {
            $preview.append('<li class="text-muted">Sin filtros aplicados</li>');
            return;
        }

        if (filters.location_id) {
            const locationName = $('#filter-location option:selected').text();
            $preview.append(`<li>Ubicación: ${locationName}</li>`);
        }

        if (filters.star_rating) {
            $preview.append(`<li>Rating: ${filters.star_rating.join(', ')} estrellas</li>`);
        }

        if (filters.hasOwnProperty('has_google_reply')) {
            $preview.append(`<li>${filters.has_google_reply ? 'Con respuesta' : 'Sin responder'}</li>`);
        }

        if (filters.hasOwnProperty('is_visible')) {
            $preview.append(`<li>${filters.is_visible ? 'Visibles' : 'Ocultas'}</li>`);
        }
    }

    // Save filter
    $('#btn-confirm-save-filter').on('click', function() {
        const filterName = $('#new-filter-name').val().trim();
        const isDefault = $('#set-as-default').is(':checked');
        const filters = getCurrentFilters();

        if (!filterName) {
            toastr.error('El nombre del filtro es obligatorio');
            return;
        }

        if (Object.keys(filters).length === 0) {
            toastr.warning('No hay filtros configurados para guardar');
            return;
        }

        $.ajax({
            url: '{{ route("reviews.saved-filters.store") }}',
            method: 'POST',
            data: {
                name: filterName,
                filters_json: JSON.stringify(filters),
                is_default: isDefault ? 1 : 0
            },
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            beforeSend: function() {
                $('#btn-confirm-save-filter').prop('disabled', true);
            },
            success: function(response) {
                toastr.success(response.message || 'Filtro guardado correctamente');
                $('#saveFilterModal').modal('hide');
                $('#new-filter-name').val('');
                $('#set-as-default').prop('checked', false);
                loadSavedFilters(); // Reload dropdown
            },
            error: function(xhr) {
                const message = xhr.status === 422
                    ? (xhr.responseJSON?.errors ? Object.values(xhr.responseJSON.errors).flat().join('\n') : 'Error de validación')
                    : (xhr.responseJSON?.message || 'Error al guardar filtro');
                toastr.error(message);
            },
            complete: function() {
                $('#btn-confirm-save-filter').prop('disabled', false);
            }
        });
    });
});
</script>
@endpush
