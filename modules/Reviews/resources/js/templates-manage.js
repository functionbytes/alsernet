/**
 * Templates Management - Gestión de plantillas de respuesta
 * La funcionalidad principal está en el blade @push('scripts')
 */

$(document).ready(function() {
    // Preview de plantilla con variables
    function previewTemplate(templateBody) {
        const preview = templateBody
            .replace(/{reviewer_name}/g, '<span class="badge bg-primary-subtle text-primary">Nombre del cliente</span>')
            .replace(/{location_name}/g, '<span class="badge bg-info-subtle text-info">Nombre de ubicación</span>')
            .replace(/{star_rating}/g, '<span class="badge bg-warning-subtle text-warning">Calificación</span>');

        return preview;
    }

    // Mostrar preview al escribir en el modal
    $('#body').on('input', function() {
        const body = $(this).val();
        const previewDiv = $('#template-preview');

        if (previewDiv.length) {
            const preview = previewTemplate(body);
            previewDiv.html(preview || '<em class="text-muted">La vista previa aparecerá aquí...</em>');
        }
    });

    // Validar formulario antes de enviar
    $('#template-form').on('submit', function(e) {
        const name = $('#name').val().trim();
        const body = $('#body').val().trim();

        if (!name) {
            e.preventDefault();
            toastr.error('El nombre de la plantilla es obligatorio');
            $('#name').focus();
            return false;
        }

        if (!body) {
            e.preventDefault();
            toastr.error('El cuerpo de la plantilla es obligatorio');
            $('#body').focus();
            return false;
        }

        if (body.length > 4096) {
            e.preventDefault();
            toastr.error('El cuerpo de la plantilla no puede exceder 4096 caracteres');
            $('#body').focus();
            return false;
        }
    });

    // Atajos de teclado en el modal
    $('#templateModal').on('shown.bs.modal', function() {
        $('#name').focus();

        $(document).on('keydown.templateModal', function(e) {
            if (e.key === 'Escape') {
                $('#templateModal').modal('hide');
            }
        });
    });

    $('#templateModal').on('hidden.bs.modal', function() {
        $(document).off('keydown.templateModal');
    });

    // Contador de caracteres para el body
    const maxLength = 4096;
    $('#body').on('input', function() {
        const length = $(this).val().length;
        let counter = $('.char-counter');

        if (!counter.length) {
            counter = $('<small class="form-text text-muted char-counter"></small>');
            $(this).after(counter);
        }

        counter.text(`${length} / ${maxLength} caracteres`);

        if (length > maxLength) {
            counter.removeClass('text-muted').addClass('text-danger');
        } else {
            counter.removeClass('text-danger').addClass('text-muted');
        }
    });

    // Insertar variable en la posición del cursor
    window.insertVariable = function(variable) {
        const textarea = $('#body')[0];
        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        const text = textarea.value;

        const before = text.substring(0, start);
        const after = text.substring(end, text.length);

        textarea.value = before + variable + after;
        textarea.selectionStart = textarea.selectionEnd = start + variable.length;
        textarea.focus();

        $('#body').trigger('input');
    };
});
