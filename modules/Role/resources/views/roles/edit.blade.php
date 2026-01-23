@extends('layouts.theme')

@section('content')

    @include('core::components.card', ['title' => 'Editar Rol'])

    {{-- Alertas --}}
    @include('core::components.alerts')

    <div class="card">
        <form id="formRoles" enctype="multipart/form-data" role="form" onSubmit="return false">
            @csrf
            <input type="hidden" name="id" id="id" value="{{ $role->id }}">

            <div class="card-header bg-white">
                <div class="row align-items-center">
                    <div class="col">
                        <h5 class="mb-0">Editar rol: {{ $role->name }}</h5>
                        <p class="text-muted mb-0 small">Actualiza la información del rol en el sistema</p>
                    </div>
                </div>
            </div>

            <div class="card-body">
                {{-- Sección: Información básica --}}
                <h6 class="fw-bold mb-0">Información básica</h6>
                <p class="text-muted small mb-3">Define el nombre, identificador único y descripción del rol</p>

                <div class="row mb-4">
                    <div class="col-md-12 mb-3">
                        <label class="form-label">Nombre del rol <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name"
                               value="{{ $role->name }}"
                               placeholder="Ej: supervisor-inventario" required>
                        <small class="text-muted">Mínimo 3 caracteres, máximo 50. Use minúsculas y guiones</small>
                    </div>

                    <div class="col-md-12 mb-3">
                        <label class="form-label">Descripción</label>
                        <textarea class="form-control" name="description" rows="3"
                                  placeholder="Describe el propósito y responsabilidades de este rol...">{{ $role->description ?? '' }}</textarea>
                        <small class="text-muted">Máximo 255 caracteres. Sea claro y conciso</small>
                    </div>
                </div>

                {{-- Sección: Configuración --}}
                <h6 class="fw-bold mb-0">Configuración</h6>
                <p class="text-muted small mb-3">Define el tipo de autenticación para este rol</p>

                <div class="row mb-0">
                    <div class="col-md-12 mb-3">
                        <label class="form-label">Guard <span class="text-danger">*</span></label>
                        <select class="form-select select2" name="guard_name" required>
                            <option value="web" {{ $role->guard_name == 'web' ? 'selected' : '' }}>Web (Navegador)</option>
                            <option value="api" {{ $role->guard_name == 'api' ? 'selected' : '' }}>API (Token/OAuth)</option>
                        </select>
                        <small class="text-muted">Define el tipo de autenticación para este rol</small>
                    </div>
                </div>

                @if(in_array($role->name, ['super-admin', 'customer']))
                    <div class="alert alert-warning border-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Rol del sistema:</strong> Este es un rol protegido del sistema. Algunas opciones están limitadas para preservar la integridad del sistema.
                    </div>
                @endif
            </div>

            <div class="card-footer">
                <button type="submit" class="btn btn-primary mb-1 w-100">
                    Guardar
                </button>
                <a href="{{ route('settings.roles.index') }}" class="btn btn-secondary  w-100">
                    Volver
                </a>
            </div>


        </form>
    </div>

    {{-- Delete Modal --}}
    @include('core::components.delete')

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
            submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Guardando...');

            $.ajax({
                url: "{{ route('settings.roles.update', $role->id) }}",
                type: "PUT",
                data: new FormData(form),
                contentType: false,
                processData: false,
                success: function(response) {
                    toastr.success(response.message || 'Rol actualizado correctamente');
                    setTimeout(function() {
                        window.location.href = "{{ route('settings.roles.index') }}";
                    }, 1500);
                },
                error: function(xhr) {
                    submitBtn.prop('disabled', false).html('<i class="fas fa-save me-2"></i>Guardar Cambios');

                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        toastr.error(xhr.responseJSON.message);
                    } else {
                        toastr.error('Error al actualizar el rol. Intente nuevamente.');
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

    // Delete confirmation
    $('.delete-btn').on('click', function(e) {
        e.preventDefault();
        const deleteUrl = $(this).data('url');
        const deleteTitle = $(this).data('title');

        $('#delete-modal .modal-title').text(deleteTitle);
        $('#delete-form').attr('action', deleteUrl);
        $('#delete-modal').modal('show');
    });
});
</script>
@endpush
