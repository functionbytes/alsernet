@extends('layouts.theme')

@section('content')

    @include('core::components.card', ['title' => 'Crear Rol'])

    {{-- Alertas --}}
    @include('core::components.alerts')

    <div class="card">
        <form id="formRoles" enctype="multipart/form-data" role="form" onSubmit="return false">
            @csrf

            <div class="card-header bg-white border-bottom">
                <div class="row align-items-center">
                    <div class="col">
                        <h5 class="mb-0">Nuevo rol del sistema</h5>
                        <p class="text-muted mb-0 small">Complete la información para crear un nuevo rol</p>
                    </div>
                </div>
            </div>

            <div class="card-body">
                {{-- Sección: Información básica --}}
                <h6 class="fw-bold mb-0 ">Información básica</h6>
                <p class="text-muted small mb-3">Define el nombre, identificador único y descripción del rol</p>

                <div class="row mb-4">
                    <div class="col-md-12 mb-3">
                        <label class="form-label">Nombre del rol <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name"
                               placeholder="Ej: supervisor-inventario" required>
                        <small class="text-muted">Mínimo 3 caracteres, máximo 50. Use minúsculas y guiones</small>
                    </div>

                    <div class="col-md-12 mb-3">
                        <label class="form-label">Descripción</label>
                        <textarea class="form-control" name="description" rows="3"
                                  placeholder="Describe el propósito y responsabilidades de este rol..."></textarea>
                        <small class="text-muted">Máximo 255 caracteres. Sea claro y conciso</small>
                    </div>
                </div>

                {{-- Sección: Configuración --}}
                <h6 class="fw-bold mb-0 ">Configuración</h6>
                <p class="text-muted small mb-3">Define el tipo de autenticación para este rol</p>

                <div class="row mb-4">
                    <div class="col-md-12 mb-3">
                        <label class="form-label">Guard <span class="text-danger">*</span></label>
                        <select class="form-select select2" name="guard_name" required>
                            <option value="web" selected>Web (Navegador)</option>
                            <option value="api">API (Token/OAuth)</option>
                        </select>
                        <small class="text-muted">Define el tipo de autenticación para este rol</small>
                    </div>
                </div>

                {{-- Información sobre permisos --}}
                <div class="alert bg-light border-light">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Asignación de permisos:</strong> Una vez creado el rol, podrá asignar permisos usando la opción
                    "Gestionar Permisos" desde la lista de roles o desde la página de detalle del rol.
                </div>
            </div>

            <div class="card-footer bg-white border-top">

                        <button type="submit" class="btn btn-primary w-100 mb-1">
                            Guardar
                        </button>

                        <a href="{{ route('settings.roles.index') }}" class="btn btn-secondary w-100 mb-2">
                            Cancelar
                        </a>
            </div>
        </form>
    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Form validation
    $("#formRoles").validate({
        rules: {
            name: {
                required: true,
                minlength: 3,
                maxlength: 50,
            },
            description: {
                maxlength: 255,
            },
            guard_name: {
                required: true,
            }
        },
        messages: {
            name: {
                required: "El nombre del rol es obligatorio.",
                minlength: "Debe contener al menos 3 caracteres.",
                maxlength: "No puede exceder los 50 caracteres."
            },
            description: {
                maxlength: "La descripción no puede exceder 255 caracteres."
            },
            guard_name: {
                required: "Debe seleccionar un guard."
            }
        },
        submitHandler: function(form) {
            const submitBtn = $(form).find('button[type="submit"]');
            submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Creando...');

            $.ajax({
                url: "{{ route('settings.roles.store') }}",
                type: "POST",
                data: new FormData(form),
                contentType: false,
                processData: false,
                success: function(response) {
                    toastr.success(response.message || 'Rol creado correctamente');
                    setTimeout(function() {
                        window.location.href = "{{ route('settings.roles.index') }}";
                    }, 1500);
                },
                error: function(xhr) {
                    submitBtn.prop('disabled', false).html('<i class="fas fa-save me-2"></i>Crear Rol');

                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        toastr.error(xhr.responseJSON.message);
                    } else {
                        toastr.error('Error al crear el rol. Intente nuevamente.');
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
