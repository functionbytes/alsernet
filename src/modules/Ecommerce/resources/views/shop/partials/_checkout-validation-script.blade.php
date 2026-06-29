<script>
$(function () {
    const $form = $('#checkout-form');
    if (!$form.length) { return; }

    function showError($input, message) {
        $input.removeClass('input-valid').addClass('input-invalid');
        let $err = $input.siblings('.field-error');
        if (!$err.length) {
            $err = $('<div class="field-error"></div>');
            $input.after($err);
        }
        $err.text(message).show();
    }

    function clearError($input) {
        $input.removeClass('input-invalid').addClass('input-valid');
        $input.siblings('.field-error').hide();
    }

    function validateEmail($input) {
        const value = $input.val().trim();
        if (!value) { return; }
        const ok = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
        ok ? clearError($input) : showError($input, 'Ingresa un email válido');
    }

    function validateRequired($input) {
        const value = $input.val().trim();
        const name = $input.attr('placeholder') || $input.attr('name') || 'campo';
        value ? clearError($input) : showError($input, `El campo ${name} es obligatorio`);
    }

    $form.find('input[type="email"]').on('blur', function () {
        validateEmail($(this));
    });

    $form.find('[required]').not('[type="radio"]').on('blur', function () {
        validateRequired($(this));
    });

    $form.on('submit', function (e) {
        const $btn = $(this).find('button[type="submit"]');
        if ($form.find('.input-invalid').length > 0) {
            e.preventDefault();
            toastr.error('Revisa los campos marcados en rojo');
            return false;
        }
        $btn.addClass('btn-loading').prop('disabled', true);
    });
});
</script>
