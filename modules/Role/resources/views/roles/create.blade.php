@extends('layouts.theme')

@section('title', 'Crear rol')

@section('content')

    @include('core::components.card', ['title' => 'Crear rol'])

    <div class="row g-3">

        {{-- Formulario --}}
        <div class="col-12 col-lg-8">
            <div class="card">
                <form id="formRoles" enctype="multipart/form-data" role="form" onSubmit="return false">
                    @csrf
                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Nuevo rol del sistema</h5>
                        <small class="text-muted">Complete la información para crear un nuevo rol.</small>
                    </div>
                    <div class="card-body">
                        @include('core::components.alerts')

                        <div class="col-12 mb-3">
                            <label class="form-label">Nombre del rol <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name"
                                   placeholder="ej: supervisor-inventario" required autofocus>
                            <small class="form-text text-muted">Mínimo 3 caracteres, máximo 50. Use minúsculas y guiones</small>
                        </div>

                        <div class="col-12 mb-3">
                            <label class="form-label">Descripción</label>
                            <textarea class="form-control" name="description" rows="3"
                                      placeholder="Describe el propósito y responsabilidades de este rol..."></textarea>
                            <small class="form-text text-muted">Máximo 255 caracteres. Sea claro y conciso</small>
                        </div>

                        <div class="col-12 mb-3">
                            <label class="form-label">Guard <span class="text-danger">*</span></label>
                            <select class="form-select select2" name="guard_name" required>
                                <option value="web" selected>Web (Navegador)</option>
                                <option value="api">API (Token/OAuth)</option>
                            </select>
                            <small class="form-text text-muted">Define el tipo de autenticación para este rol</small>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-1">
                            Crear rol
                        </button>
                        <a href="{{ route('settings.roles.index') }}" class="btn btn-light w-100">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Panel informativo --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-3">¿Qué es un rol?</h6>
                    <p class="card-text text-muted">
                        Los roles agrupan un conjunto de permisos y se asignan a usuarios para controlar qué acciones pueden realizar en el sistema.
                    </p>
                </div>
                <hr class="my-0">
                <div class="card-body">
                    <h6 class="card-title mb-3">Convención de nombres</h6>
                    <p class="card-text text-muted mb-2">Use minúsculas y guiones. Ejemplos:</p>
                    <ul class="text-muted  mb-0">
                        <li><code>supervisor-inventario</code></li>
                        <li><code>editor-contenido</code></li>
                        <li><code>soporte-nivel-1</code></li>
                    </ul>
                </div>
                <hr class="my-0">
                <div class="card-body">
                    <h6 class="card-title mb-3">Permisos</h6>
                    <p class="card-text text-muted mb-0">
                        Una vez creado el rol, podrá asignar permisos usando <strong>Gestionar permisos</strong> desde el listado de roles.
                    </p>
                </div>
                <hr class="my-0">
                <div class="card-body">
                    <h6 class="card-title mb-3">Guard</h6>
                    <p class="card-text text-muted mb-0">
                        <strong>Web</strong>: para usuarios autenticados por sesión (navegador).<br>
                        <strong>API</strong>: para autenticación por token o OAuth.
                    </p>
                </div>
            </div>
        </div>

    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    $('.select2').select2({ minimumResultsForSearch: Infinity });

    $('#formRoles').validate({
        rules: {
            name: { required: true, minlength: 3, maxlength: 50 },
            description: { maxlength: 255 },
            guard_name: { required: true },
        },
        messages: {
            name: {
                required: 'El nombre del rol es obligatorio.',
                minlength: 'Debe contener al menos 3 caracteres.',
                maxlength: 'No puede exceder los 50 caracteres.',
            },
            description: { maxlength: 'La descripción no puede exceder 255 caracteres.' },
            guard_name: { required: 'Debe seleccionar un guard.' },
        },
        submitHandler: function (form) {
            const submitBtn = $(form).find('button[type="submit"]');
            const original  = submitBtn.html();
            submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Creando...');

            $.ajax({
                url: '{{ route('settings.roles.store') }}',
                type: 'POST',
                data: new FormData(form),
                contentType: false,
                processData: false,
                success: function (res) {
                    toastr.success(res.message || 'Rol creado correctamente');
                    setTimeout(() => { window.location.href = '{{ route('settings.roles.index') }}'; }, 1500);
                },
                error: function (xhr) {
                    submitBtn.prop('disabled', false).html(original);
                    const errors = xhr.responseJSON?.errors;
                    if (errors) {
                        $.each(errors, function (k, v) { toastr.error(v[0]); });
                    } else {
                        toastr.error(xhr.responseJSON?.message || 'Error al crear el rol.');
                    }
                },
            });
        },
    });
});
</script>
@endpush
