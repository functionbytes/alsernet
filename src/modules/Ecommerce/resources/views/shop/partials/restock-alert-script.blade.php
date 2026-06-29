<script>
$(document).on('submit', '.js-restock-alert-form', function (e) {
    e.preventDefault();
    var $form = $(this);
    var $btn = $form.find('button[type="submit"]').prop('disabled', true);

    $.ajax({
        url: $form.attr('action'),
        method: 'POST',
        data: $form.serialize(),
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            'Accept': 'application/json'
        },
        success: function (r) {
            toastr.success(r.message || 'Te avisaremos cuando esté disponible.');
            $form.find('input[name="email"]').prop('disabled', true);
            $btn.prop('disabled', true).text('Solicitud registrada');
        },
        error: function (xhr) {
            if (xhr.status === 422) {
                var errors = xhr.responseJSON && xhr.responseJSON.errors;
                if (errors) {
                    $.each(errors, function (field, messages) {
                        toastr.error(messages[0]);
                    });
                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                    toastr.warning(xhr.responseJSON.message);
                } else {
                    toastr.error('Por favor verifica los datos ingresados.');
                }
            } else if (xhr.status === 429) {
                toastr.warning('Demasiados intentos. Intenta de nuevo en un momento.');
            } else {
                toastr.error('Error al registrar la solicitud. Intenta de nuevo.');
            }
            $btn.prop('disabled', false);
        }
    });
});
</script>
