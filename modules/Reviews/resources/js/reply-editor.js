/**
 * Reply Editor - Editor de respuestas a reseñas
 * Funcionalidad adicional para el formulario de respuestas
 */

$(document).ready(function() {
    // Preview de variables en tiempo real
    function updatePreview() {
        const replyText = $('#reply_text').val();
        const previewDiv = $('#reply-preview');

        if (previewDiv.length && replyText) {
            const reviewerName = previewDiv.data('reviewer-name') || '{reviewer_name}';
            const locationName = previewDiv.data('location-name') || '{location_name}';
            const starRating = previewDiv.data('star-rating') || '{star_rating}';

            const preview = replyText
                .replace(/{reviewer_name}/g, `<strong>${reviewerName}</strong>`)
                .replace(/{location_name}/g, `<strong>${locationName}</strong>`)
                .replace(/{star_rating}/g, `<strong>${starRating}</strong>`);

            previewDiv.html(preview);
        }
    }

    // Actualizar preview al escribir
    $('#reply_text').on('input', updatePreview);

    // Contador de caracteres
    const maxLength = 4096;
    $('#reply_text').on('input', function() {
        const length = $(this).val().length;
        const counter = $('#char-counter');

        if (counter.length) {
            counter.text(`${length} / ${maxLength}`);

            if (length > maxLength) {
                counter.addClass('text-danger');
            } else {
                counter.removeClass('text-danger');
            }
        }
    });

    // Validación antes de guardar
    function validateReply(replyText) {
        if (!replyText.trim()) {
            toastr.error('La respuesta no puede estar vacía');
            return false;
        }

        if (replyText.length > maxLength) {
            toastr.error(`La respuesta no puede exceder ${maxLength} caracteres`);
            return false;
        }

        return true;
    }

    // Atajos de teclado
    $('#reply_text').on('keydown', function(e) {
        // Ctrl/Cmd + Enter para guardar como borrador
        if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
            e.preventDefault();
            $('#save-draft').click();
        }
    });

    // Confirmar antes de abandonar si hay cambios sin guardar
    let originalText = $('#reply_text').val();
    window.addEventListener('beforeunload', function(e) {
        const currentText = $('#reply_text').val();
        if (currentText && currentText !== originalText) {
            e.preventDefault();
            e.returnValue = '';
            return '';
        }
    });

    // Actualizar texto original después de guardar
    $(document).on('reply-saved', function() {
        originalText = $('#reply_text').val();
    });

    // Export validation function
    window.validateReply = validateReply;
});
