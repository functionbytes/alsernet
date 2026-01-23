@extends('layouts.theme')

@section('content')

    @include('core::components.card', ['title' => 'Crear Usuario'])

    {{-- Alertas --}}
    @include('core::components.alerts')

    <div class="card">
        <form id="formUsers" enctype="multipart/form-data" role="form" onSubmit="return false">
            @csrf

            <div class="card-header bg-white border-bottom">
                <div class="row align-items-center">
                    <div class="col">
                        <h5 class="mb-0">Nuevo usuario del sistema</h5>
                        <p class="text-muted mb-0 small">Complete la información para crear un nuevo usuario</p>
                    </div>
                    <div class="col-auto">
                        <a href="{{ route('settings.users.index') }}" class="btn btn-light">
                            <i class="fas fa-arrow-left me-2"></i>Volver
                        </a>
                    </div>
                </div>
            </div>

            <div class="card-body">
                {{-- Sección: Información personal --}}
                <h6 class="fw-bold mb-1">Información personal</h6>
                <p class="text-muted small mb-4">Datos básicos del usuario como nombre, identificación y contacto</p>

                <div class="row mb-4">
                    <div class="col-12 col-md-6 mb-3">
                        <label class="form-label">Nombre <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="firstname"
                               placeholder="Ej: Juan" required>
                        <small class="text-muted">Nombre del usuario</small>
                    </div>

                    <div class="col-12 col-md-6 mb-3">
                        <label class="form-label">Apellido <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="lastname"
                               placeholder="Ej: Pérez" required>
                        <small class="text-muted">Apellido del usuario</small>
                    </div>

                    <div class="col-12 col-md-6 mb-3">
                        <label class="form-label">Identificación</label>
                        <input type="text" class="form-control" name="identification"
                               placeholder="Ej: 1234567890">
                        <small class="text-muted">DNI, cédula o documento de identidad</small>
                    </div>

                    <div class="col-12 col-md-6 mb-3">
                        <label class="form-label">Teléfono</label>
                        <input type="text" class="form-control" name="cellphone"
                               placeholder="Ej: +34 600 123 456">
                        <small class="text-muted">Número de contacto</small>
                    </div>

                    <div class="col-12 mb-3">
                        <label class="form-label">Dirección</label>
                        <textarea class="form-control" name="address" rows="2"
                                  placeholder="Dirección completa del usuario"></textarea>
                        <small class="text-muted">Dirección física del usuario</small>
                    </div>
                </div>

                {{-- Sección: Cuenta y acceso --}}
                <h6 class="fw-bold mb-1 mt-4">Cuenta y acceso</h6>
                <p class="text-muted small mb-4">Configuración de autenticación, rol y estado de la cuenta</p>

                <div class="row mb-4">
                    <div class="col-12 col-md-12 mb-3">
                        <label class="form-label">Correo electronico <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" name="email"
                               placeholder="Ej: usuario@ejemplo.com" required>
                        <small class="text-muted">Email para acceso al sistema</small>
                    </div>

                    <div class="col-12 col-md-6 mb-3">
                        <label class="form-label">Contraseña <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" name="password"
                               placeholder="Contraseña segura" required>
                        <small class="text-muted">Mínimo 8 caracteres</small>
                    </div>

                    <div class="col-12 col-md-6 mb-3">
                        <label class="form-label">Rol <span class="text-danger">*</span></label>
                        <select class="form-select select2" id="roleSelect" name="role" data-placeholder="Seleccione un rol" required>
                            <option value=""></option>
                            @foreach($roles as $roleId => $roleName)
                                <option value="{{ $roleName }}">{{ Str::title(str_replace('-', ' ', $roleName)) }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">Rol del usuario en el sistema</small>
                    </div>

                    <div class="col-12 col-md-6 mb-3">
                        <label class="form-label">Estado</label>
                        <select class="form-select select2" id="statusSelect" name="available" data-placeholder="Seleccione estado">
                            <option value=""></option>
                            <option value="1" selected>Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                        <small class="text-muted">Estado de la cuenta</small>
                    </div>

                    <div class="col-12 col-md-6 mb-3">
                        <label class="form-label">Usuario verificado</label>
                        <select class="form-select select2" id="verifiedSelect" name="verified" data-placeholder="Seleccione estado de verificación">
                            <option value=""></option>
                            <option value="1">Verificado</option>
                            <option value="0" selected>No verificado</option>
                        </select>
                        <small class="text-muted">Estado de verificación de email del usuario</small>
                    </div>

                </div>

                {{-- Sección: Información adicional --}}
                <h6 class="fw-bold mb-1 mt-4">Información adicional</h6>
                <p class="text-muted small mb-4">Empresa, zona horaria y verificación del usuario</p>

                <div class="row mb-4">
                    <div class="col-12 col-md-6 mb-3">
                        <label class="form-label">Empresa</label>
                        <input type="text" class="form-control" name="company"
                               placeholder="Nombre de la empresa">
                        <small class="text-muted">Empresa a la que pertenece</small>
                    </div>

                    <div class="col-12 col-md-6 mb-3">
                        <label class="form-label">Zona horaria</label>
                        <select class="form-select select2" id="timezoneSelect" name="timezone" data-placeholder="Seleccionar zona horaria">
                            <option value=""></option>
                            <option value="America/New_York">America/New_York (EST)</option>
                            <option value="America/Los_Angeles">America/Los_Angeles (PST)</option>
                            <option value="America/Chicago">America/Chicago (CST)</option>
                            <option value="America/Denver">America/Denver (MST)</option>
                            <option value="Europe/London">Europe/London (GMT)</option>
                            <option value="Europe/Madrid" selected>Europe/Madrid (CET)</option>
                            <option value="Europe/Paris">Europe/Paris (CET)</option>
                            <option value="Asia/Tokyo">Asia/Tokyo (JST)</option>
                            <option value="Australia/Sydney">Australia/Sydney (AEST)</option>
                        </select>
                        <small class="text-muted">Zona horaria del usuario</small>
                    </div>


                </div>
            </div>

            <div class="card-footer border-top">
                <button type="submit" class="btn btn-primary  w-100 mb-1">
                    Guardar
                </button>
                <a href="{{ route('settings.users.index') }}" class="btn btn-secondary w-100">
                    Cancelar
                </a>
            </div>

        </form>
    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Initialize Select2 for all select fields
    $('#roleSelect, #statusSelect, #timezoneSelect, #verifiedSelect').select2({
        allowClear: false,
        language: {
            noResults: function() {
                return 'Sin resultados';
            },
            searching: function() {
                return 'Buscando...';
            }
        }
    });

    // Form validation
    $("#formUsers").validate({
        rules: {
            firstname: {
                required: true,
                minlength: 2,
                maxlength: 100,
            },
            lastname: {
                required: true,
                minlength: 2,
                maxlength: 100,
            },
            email: {
                required: true,
                email: true,
            },
            password: {
                required: true,
                minlength: 8,
            },
            role: {
                required: true,
            },
            cellphone: {
                maxlength: 20,
            },
            identification: {
                maxlength: 50,
            }
        },
        messages: {
            firstname: {
                required: "El nombre es obligatorio.",
                minlength: "Debe contener al menos 2 caracteres.",
                maxlength: "No puede exceder los 100 caracteres."
            },
            lastname: {
                required: "El apellido es obligatorio.",
                minlength: "Debe contener al menos 2 caracteres.",
                maxlength: "No puede exceder los 100 caracteres."
            },
            email: {
                required: "El email es obligatorio.",
                email: "Ingrese un email válido."
            },
            password: {
                required: "La contraseña es obligatoria.",
                minlength: "La contraseña debe tener al menos 8 caracteres."
            },
            role: {
                required: "Debe seleccionar un rol."
            }
        },
        highlight: function(element) {
            $(element).addClass('is-invalid');

            // Para Select2
            if ($(element).hasClass('select2')) {
                $(element).next('.select2-container')
                    .find('.select2-selection')
                    .addClass('is-invalid');
            }
        },
        unhighlight: function(element) {
            $(element).removeClass('is-invalid');

            // Para Select2
            if ($(element).hasClass('select2')) {
                $(element).next('.select2-container')
                    .find('.select2-selection')
                    .removeClass('is-invalid');
            }
        },
        errorPlacement: function(error, element) {
            error.addClass('field-validation-error');

            // Colocar error después del contenedor Select2
            if ($(element).hasClass('select2')) {
                error.insertAfter(element.next('.select2-container'));
            } else {
                error.insertAfter(element);
            }
        },
        submitHandler: function(form) {
            const submitBtn = $(form).find('button[type="submit"]');
            submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Creando...');

            $.ajax({
                url: "{{ route('settings.users.store') }}",
                type: "POST",
                data: new FormData(form),
                contentType: false,
                processData: false,
                success: function(response) {
                    toastr.success(response.message || 'Usuario creado correctamente');
                    setTimeout(function() {
                        window.location.href = "{{ route('settings.users.index') }}";
                    }, 1500);
                },
                error: function(xhr) {
                    submitBtn.prop('disabled', false).html('<i class="fas fa-save me-2"></i>Crear Usuario');

                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        toastr.error(xhr.responseJSON.message);
                    } else {
                        toastr.error('Error al crear el usuario. Intente nuevamente.');
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

    // Validar Select2 al cambiar
    $('#roleSelect, #statusSelect, #timezoneSelect, #verifiedSelect').on('change', function() {
        $(this).valid();
    });
});
</script>
@endpush
