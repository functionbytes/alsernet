/**
 * Reviews Index - Selección múltiple y acciones en lote
 */

$(document).ready(function() {
    let selectedReviews = [];

    window.formatReviewDate = function(dateString) {
        if (!dateString) return '-';
        const date = new Date(dateString);
        return date.toLocaleDateString('es-ES', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    };

    window.renderStars = function(starRating) {
        const stars = parseInt(starRating.replace('_STAR', ''));
        let html = '';
        for (let i = 1; i <= 5; i++) {
            html += i <= stars
                ? '<i class="fas fa-star text-warning"></i>'
                : '<i class="far fa-star text-muted"></i>';
        }
        return html;
    };

    function updateSelectionUI() {
        const count = selectedReviews.length;
        const total = $('.review-checkbox').length;
        const checked = $('.review-checkbox:checked').length;

        $('#selected-count').text(count);
        $('#bulk-actions-bar').toggle(count > 0);
        $('#select-all').prop('checked', total > 0 && total === checked);
        $('#select-all').prop('indeterminate', checked > 0 && checked < total);
    }

    function clearSelection() {
        selectedReviews = [];
        $('.review-checkbox, #select-all').prop('checked', false);
        updateSelectionUI();
    }

    $(document).on('change', '#select-all', function() {
        const isChecked = $(this).is(':checked');
        $('.review-checkbox').prop('checked', isChecked);

        selectedReviews = isChecked
            ? $('.review-checkbox').map(function() { return parseInt($(this).val()); }).get()
            : [];

        updateSelectionUI();
    });

    $(document).on('change', '.review-checkbox', function() {
        const reviewId = parseInt($(this).val());

        if ($(this).is(':checked')) {
            if (!selectedReviews.includes(reviewId)) {
                selectedReviews.push(reviewId);
            }
        } else {
            selectedReviews = selectedReviews.filter(id => id !== reviewId);
        }

        updateSelectionUI();
    });

    $('#cancel-selection').on('click', clearSelection);

    function executeBulkAction(action, actionLabel) {
        if (selectedReviews.length === 0) {
            toastr.warning('No hay reseñas seleccionadas');
            return;
        }

        if (!confirm(`¿Está seguro de ${actionLabel} ${selectedReviews.length} reseña(s)?`)) {
            return;
        }

        $.ajax({
            url: '/reviews/bulk-moderate',
            method: 'POST',
            data: { review_ids: selectedReviews, action },
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            beforeSend: () => $('#bulk-actions-bar button').prop('disabled', true),
            success: function(response) {
                toastr.success(response.message || 'Acción completada correctamente');
                clearSelection();
                if (typeof table !== 'undefined') {
                    table.ajax.reload(null, false);
                }
            },
            error: function(xhr) {
                const errors = xhr.responseJSON?.errors;
                if (xhr.status === 422 && errors) {
                    toastr.error(Object.values(errors).flat().join('\n') || 'Error de validación');
                } else {
                    toastr.error(xhr.responseJSON?.message || 'Error al ejecutar la acción');
                }
            },
            complete: () => $('#bulk-actions-bar button').prop('disabled', false)
        });
    }

    $('#bulk-make-visible').on('click', () => executeBulkAction('visible', 'hacer visibles'));
    $('#bulk-hide').on('click', () => executeBulkAction('hidden', 'ocultar'));
    $('#bulk-feature').on('click', () => executeBulkAction('featured', 'marcar como destacadas'));
    $('#bulk-unfeature').on('click', () => executeBulkAction('unfeatured', 'quitar como destacadas'));

    const hasActiveFilters = $('#filter-location').val() ||
                            $('#filter-reply-status').val() ||
                            $('#filter-visibility').val() ||
                            $('.filter-rating:checked').length > 0;

    if (!hasActiveFilters) {
        $('#filters').collapse('hide');
    }

    $(document).on('draw.dt', '#reviews-table', function() {
        $('.review-checkbox').each(function() {
            const reviewId = parseInt($(this).val());
            $(this).prop('checked', selectedReviews.includes(reviewId));
        });
        updateSelectionUI();
    });
});
