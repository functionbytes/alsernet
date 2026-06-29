<script>
$(function () {
    const $form = $('#register-form');
    if (!$form.length) { return; }

    function showError($input, msg) {
        $input.removeClass('input-valid').addClass('input-invalid');
        let $err = $input.siblings('.field-error');
        if (!$err.length) {
            $err = $('<div class="field-error"></div>');
            $input.after($err);
        }
        $err.text(msg).show();
    }

    function clearError($input) {
        $input.removeClass('input-invalid').addClass('input-valid');
        $input.siblings('.field-error').hide();
    }

    $form.find('input[name="email"]').on('blur', function () {
        const value = $(this).val().trim();
        if (!value) { return; }
        /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)
            ? clearError($(this))
            : showError($(this), 'Email inválido');
    });

    $form.find('input[name="password"]').on('input', function () {
        const value = $(this).val();

        if (value.length < 8) {
            showError($(this), 'Mínimo 8 caracteres');
        } else {
            clearError($(this));
        }

        let strength = 0;
        if (value.length >= 8)          { strength++; }
        if (/[A-Z]/.test(value))        { strength++; }
        if (/[0-9]/.test(value))        { strength++; }
        if (/[^A-Za-z0-9]/.test(value)) { strength++; }

        const colors = ['#dc3545', '#fd7e14', '#ffc107', '#20c997', '#198754'];
        const labels = ['Muy débil', 'Débil', 'Regular', 'Buena', 'Fuerte'];

        let $strength = $('#password-strength');
        if (!$strength.length) {
            $strength = $('<div id="password-strength" class="mt-1 small"></div>');
            $(this).parent().append($strength);
        }

        $strength.html(
            `<div style="height:4px;background:${colors[strength]};width:${(strength + 1) * 20}%;border-radius:2px;margin-bottom:2px"></div>` +
            `<span class="text-muted">${labels[strength]}</span>`
        );
    });

    $form.find('input[name="password_confirmation"]').on('input blur', function () {
        const conf = $(this).val();
        if (!conf) { return; }
        const pwd = $form.find('input[name="password"]').val();
        pwd === conf
            ? clearError($(this))
            : showError($(this), 'Las contraseñas no coinciden');
    });

    $form.on('submit', function (e) {
        const $btn = $(this).find('button[type="submit"]');
        if ($form.find('.input-invalid').length > 0) {
            e.preventDefault();
            toastr.error('Revisa los errores antes de continuar');
            return false;
        }
        $btn.addClass('btn-loading').prop('disabled', true);
    });
});
</script>
