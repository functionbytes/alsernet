@extends('layouts.theme')

@section('content')

    @include('core::components.card', ['title' => 'Editar permiso'])

    {{-- Alertas --}}
    @include('core::components.alerts')

    <div class="card">
        <form id="formPermissions" enctype="multipart/form-data" role="form" onSubmit="return false">
            @csrf
            <input type="hidden" name="id" value="{{ $permission->id }}">

            <div class="card-header bg-white border-bottom">
                <div class="row align-items-center">
                    <div class="col">
                        <h5 class="mb-0">Editar permiso: {{ $permission->name }}</h5>
                        <p class="text-muted mb-0 small">Actualiza la información del permiso en el sistema</p>
                    </div>
                </div>
            </div>

            <div class="card-body">
                {{-- Sección: Información básica --}}
                <h6 class="">
                    Información básica
                </h6>
                <p class="text-muted mb-3 small">
                    Define el nombre único del permiso, el tipo de autenticación (guard) y una descripción clara de lo que permite hacer
                </p>


                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nombre del permiso <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name"
                               value="{{ $permission->name }}"
                               placeholder="Ej: users.create" required>
                        <small class="text-muted">Use formato: módulo.acción (ej: products.view, orders.edit)</small>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Guard <span class="text-danger">*</span></label>
                        <select class="form-select select2" name="guard_name" required>
                            <option value="web" {{ $permission->guard_name == 'web' ? 'selected' : '' }}>Web (Navegador)</option>
                            <option value="api" {{ $permission->guard_name == 'api' ? 'selected' : '' }}>API (Token/OAuth)</option>
                        </select>
                        <small class="text-muted">Define el tipo de autenticación para este permiso</small>
                    </div>

                    <div class="col-md-12 mb-0">
                        <label class="form-label">Descripción</label>
                        <textarea class="form-control" name="description" rows="3"
                                  placeholder="Describe qué permite hacer este permiso...">{{ $permission->description ?? '' }}</textarea>
                        <small class="text-muted">Máximo 255 caracteres. Sea claro y conciso</small>
                    </div>
                </div>

                @if($permission->roles()->count() > 0)
                    <div class="alert alert-light border-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Atención:</strong> Este permiso está asignado a {{ $permission->roles()->count() }} rol(es).
                        Los cambios afectarán a todos los roles que lo utilizan.
                    </div>
                @endif
            </div>

            <div class="card-footer bg-white border-top">
                        <button type="submit" class="btn btn-primary w-100 mb-1">
                            Guardar
                        </button>
                        <a href="{{ route('settings.permissions.index') }}" class="btn btn-secondary w-100">
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
    $("#formPermissions").validate({
        rules: {
            name: {
                required: true,
                minlength: 3,
                maxlength: 100,
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
                required: "El nombre del permiso es obligatorio.",
                minlength: "Debe contener al menos 3 caracteres.",
                maxlength: "No puede exceder los 100 caracteres."
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

            const formData = new FormData(form);
            const permissionId = formData.get('id');

            $.ajax({
                url: "{{ route('settings.permissions.update', $permission->id) }}",
                type: "PUT",
                data: formData,
                contentType: false,
                processData: false,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    toastr.success(response.message || 'Permiso actualizado correctamente');
                    setTimeout(function() {
                        window.location.href = "{{ route('settings.permissions.index') }}";
                    }, 1500);
                },
                error: function(xhr) {
                    submitBtn.prop('disabled', false).html('<i class="fas fa-save me-2"></i>Guardar Cambios');

                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        toastr.error(xhr.responseJSON.message);
                    } else {
                        toastr.error('Error al actualizar el permiso. Intente nuevamente.');
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
