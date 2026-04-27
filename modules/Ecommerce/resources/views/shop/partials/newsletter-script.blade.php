<script>
$(document).on('submit', '.js-newsletter-form', function (e) {
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
            toastr.success(r.message || 'Te has suscrito exitosamente.');
            $form[0].reset();
        },
        error: function (xhr) {
            if (xhr.status === 422) {
                var errors = xhr.responseJSON && xhr.responseJSON.errors;
                if (errors) {
                    $.each(errors, function (field, messages) {
                        toastr.error(messages[0]);
                    });
                } else {
                    toastr.error('Por favor verifica los datos ingresados.');
                }
            } else if (xhr.status === 429) {
                toastr.warning('Demasiados intentos. Intenta de nuevo en un momento.');
            } else {
                toastr.error('Error al suscribirse. Intenta de nuevo.');
            }
        },
        complete: function () {
            $btn.prop('disabled', false);
        }
    });
});
</script>
