<div class="row g-4">
    <div class="col-lg-8">
        <div class="card border">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3">Cambiar contraseña</h6>
                <p class="text-muted mb-4">Actualiza tu contraseña para mantener tu cuenta segura</p>

                <form id="formPassword" onSubmit="return false">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label">Contraseña actual <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" name="current_password"
                               placeholder="Ingresa tu contraseña actual" required>
                        <small class="text-muted">Necesitas confirmar tu contraseña actual</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nueva contraseña <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" name="new_password" id="new_password"
                               placeholder="Mínimo 8 caracteres" required>
                        <div class="mt-2" id="password-strength-wrapper" class="d-none">
                            <div class="progress" style="height:6px">
                                <div id="password-strength-bar" class="progress-bar" role="progressbar" style="width:0%"></div>
                            </div>
                            <small id="password-strength-text" class="text-muted mt-1 d-block"></small>
                        </div>
                        <small class="text-muted">Debe tener al menos 8 caracteres</small>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Confirmar nueva contraseña <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" name="new_password_confirmation"
                               placeholder="Repite tu nueva contraseña" required>
                        <small class="text-muted">Debe coincidir con la nueva contraseña</small>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-key me-2"></i>Actualizar contraseña
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border bg-light">
            <div class="card-body p-4">
                <div class="text-center mb-3">
                    <i class="fas fa-shield-halved fa-3x text-primary mb-3"></i>
                    <h6 class="fw-bold">Consejos de seguridad</h6>
                </div>

                <ul class="text-muted mb-0">
                    <li class="mb-2">Usa al menos 8 caracteres</li>
                    <li class="mb-2">Combina letras, números y símbolos</li>
                    <li class="mb-2">No uses información personal</li>
                    <li class="mb-2">Cambia tu contraseña regularmente</li>
                    <li>No reutilices contraseñas antiguas</li>
                </ul>
            </div>
        </div>

        <div class="card border mt-3">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-2">Última actualización</h6>
                <p class="text-muted mb-0">{{ $user->updated_at->format('d/m/Y H:i') }}</p>
                <small class="text-muted">{{ $user->updated_at->diffForHumans() }}</small>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {

    // Password strength meter
    function passwordStrength(pw) {
        let score = 0;
        if (pw.length >= 8)  { score++; }
        if (pw.length >= 12) { score++; }
        if (/[A-Z]/.test(pw))  { score++; }
        if (/[0-9]/.test(pw))  { score++; }
        if (/[^A-Za-z0-9]/.test(pw)) { score++; }
        return score;
    }

    const strengthLabels = ['', 'Muy débil', 'Débil', 'Regular', 'Fuerte', 'Muy fuerte'];
    const strengthClasses = ['', 'bg-danger', 'bg-warning', 'bg-info', 'bg-primary', 'bg-success'];

    $('#new_password').on('input', function () {
        const val = $(this).val();
        const $wrapper = $('#password-strength-wrapper');
        const $bar = $('#password-strength-bar');
        const $text = $('#password-strength-text');

        if (!val) {
            $wrapper.hide();
            return;
        }

        $wrapper.show();
        const score = passwordStrength(val);
        const pct = (score / 5) * 100;
        $bar.css('width', pct + '%')
            .removeClass('bg-danger bg-warning bg-info bg-primary bg-success')
            .addClass(strengthClasses[score] || 'bg-danger');
        $text.text(strengthLabels[score] || '');
    });

    $("#formPassword").validate({
        rules: {
            current_password: {
                required: true,
                minlength: 8,
            },
            new_password: {
                required: true,
                minlength: 8,
            },
            new_password_confirmation: {
                required: true,
                equalTo: "#new_password"
            }
        },
        messages: {
            current_password: {
                required: "La contraseña actual es obligatoria.",
                minlength: "Debe tener al menos 8 caracteres."
            },
            new_password: {
                required: "La nueva contraseña es obligatoria.",
                minlength: "Debe tener al menos 8 caracteres."
            },
            new_password_confirmation: {
                required: "Debes confirmar la nueva contraseña.",
                equalTo: "Las contraseñas no coinciden."
            }
        },
        submitHandler: function(form) {
            const submitBtn = $(form).find('button[type="submit"]');
            const originalHtml = submitBtn.html();
            submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Actualizando...');

            $.ajax({
                url: "{{ route('settings.auth.password.update') }}",
                type: "POST",
                data: $(form).serialize(),
                success: function(response) {
                    toastr.success(response.message || 'Contraseña actualizada correctamente');
                    $(form)[0].reset();
                    submitBtn.prop('disabled', false).html(originalHtml);
                },
                error: function(xhr) {
                    submitBtn.prop('disabled', false).html(originalHtml);

                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        toastr.error(xhr.responseJSON.message);
                    } else {
                        toastr.error('Error al actualizar la contraseña. Intente nuevamente.');
                    }

                    if (xhr.responseJSON && xhr.responseJSON.errors) {
                        $.each(xhr.responseJSON.errors, function(key, value) {
                            toastr.error(value[0]);
                        });
                    }
                }
            });
        }
    });
});
</script>
@endpush
