@extends('layouts.theme')

@section('title', 'Crear permiso')

@section('page_header')
    @include('core::components.card', ['title' => 'Crear permiso'])
@endsection

@section('content')

    <div class="row g-3">

        {{-- Formulario --}}
        <div class="col-12 col-lg-8">
            <div class="card">
                <form id="formPermissions" enctype="multipart/form-data" role="form" onSubmit="return false">
                    @csrf
                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Nuevo permiso del sistema</h5>
                        <small class="text-muted">Complete la información para crear un nuevo permiso.</small>
                    </div>
                    <div class="card-body">
                        @include('core::components.alerts')

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nombre del permiso <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="name"
                                       placeholder="ej: users.create" required autofocus>
                                <small class="form-text text-muted">Use formato: módulo.acción (ej: products.view, orders.edit)</small>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Guard <span class="text-danger">*</span></label>
                                <select class="form-select select2" name="guard_name" required>
                                    <option value="web" selected>Web (Navegador)</option>
                                    <option value="api">API (Token/OAuth)</option>
                                </select>
                                <small class="form-text text-muted">Define el tipo de autenticación para este permiso</small>
                            </div>

                            <div class="col-12 mb-3">
                                <label class="form-label">Descripción</label>
                                <textarea class="form-control" name="description" rows="3"
                                          placeholder="Describe qué permite hacer este permiso..."></textarea>
                                <small class="form-text text-muted">Máximo 255 caracteres. Sea claro y conciso</small>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-1">
                            Crear permiso
                        </button>
                        <a href="{{ route('settings.permissions.index') }}" class="btn btn-light w-100">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Panel informativo --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-3">¿Qué es un permiso?</h6>
                    <p class="card-text text-muted">
                        Los permisos definen las acciones específicas que un usuario puede realizar dentro del sistema. Se asignan a roles para controlar el acceso.
                    </p>
                </div>
                <hr class="my-0">
                <div class="card-body">
                    <h6 class="card-title mb-3">Convención de nombres</h6>
                    <p class="card-text text-muted mb-2">
                        Use el formato <code>módulo.acción</code> para mantener consistencia. Ejemplos:
                    </p>
                    <ul class="text-muted  mb-0">
                        <li><code>users.view</code></li>
                        <li><code>products.create</code></li>
                        <li><code>orders.delete</code></li>
                    </ul>
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

    $('#formPermissions').validate({
        rules: {
            name: { required: true, minlength: 3, maxlength: 100 },
            description: { maxlength: 255 },
            guard_name: { required: true },
        },
        messages: {
            name: {
                required: 'El nombre del permiso es obligatorio.',
                minlength: 'Debe contener al menos 3 caracteres.',
                maxlength: 'No puede exceder los 100 caracteres.',
            },
            description: { maxlength: 'La descripción no puede exceder 255 caracteres.' },
            guard_name: { required: 'Debe seleccionar un guard.' },
        },
        submitHandler: function (form) {
            const submitBtn = $(form).find('button[type="submit"]');
            const original  = submitBtn.html();
            submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Creando...');

            $.ajax({
                url: '{{ route('settings.permissions.store') }}',
                type: 'POST',
                data: new FormData(form),
                contentType: false,
                processData: false,
                success: function (res) {
                    toastr.success(res.message || 'Permiso creado correctamente');
                    setTimeout(() => { window.location.href = '{{ route('settings.permissions.index') }}'; }, 1500);
                },
                error: function (xhr) {
                    submitBtn.prop('disabled', false).html(original);
                    const errors = xhr.responseJSON?.errors;
                    if (errors) {
                        $.each(errors, function (k, v) { toastr.error(v[0]); });
                    } else {
                        toastr.error(xhr.responseJSON?.message || 'Error al crear el permiso.');
                    }
                },
            });
        },
    });
});
</script>
@endpush
