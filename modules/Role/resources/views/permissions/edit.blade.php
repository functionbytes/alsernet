@extends('layouts.theme')

@section('title', 'Editar permiso')

@section('content')

    @include('core::components.card', ['title' => 'Editar permiso'])

    <div class="row g-3">

        {{-- Formulario --}}
        <div class="col-12 col-lg-8">
            <div class="card">
                <form id="formPermissions" enctype="multipart/form-data" role="form" onSubmit="return false">
                    @csrf
                    <input type="hidden" name="id" value="{{ $permission->id }}">
                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Editar permiso</h5>
                        <small class="text-muted">Actualiza la información del permiso en el sistema.</small>
                    </div>
                    <div class="card-body">
                        @include('core::components.alerts')

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nombre del permiso <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="name"
                                       value="{{ $permission->name }}"
                                       placeholder="ej: users.create" required>
                                <small class="form-text text-muted">Use formato: módulo.acción (ej: products.view, orders.edit)</small>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Guard <span class="text-danger">*</span></label>
                                <select class="form-select select2" name="guard_name" required>
                                    <option value="web" {{ $permission->guard_name === 'web' ? 'selected' : '' }}>Web (Navegador)</option>
                                    <option value="api" {{ $permission->guard_name === 'api' ? 'selected' : '' }}>API (Token/OAuth)</option>
                                </select>
                                <small class="form-text text-muted">Define el tipo de autenticación para este permiso</small>
                            </div>

                            <div class="col-12 mb-3">
                                <label class="form-label">Descripción</label>
                                <textarea class="form-control" name="description" rows="3"
                                          placeholder="Describe qué permite hacer este permiso...">{{ $permission->description ?? '' }}</textarea>
                                <small class="form-text text-muted">Máximo 255 caracteres. Sea claro y conciso</small>
                            </div>

                            @if($permission->roles()->count() > 0)
                                <div class="col-12">
                                    <div class="alert alert-warning border-0 mb-0">
                                        <i class="fas fa-triangle-exclamation me-2"></i>
                                        <strong>Atención:</strong> Este permiso está asignado a {{ $permission->roles()->count() }} rol(es). Los cambios afectarán a todos los roles que lo utilizan.
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-1">
                            Guardar cambios
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
                    <h6 class="card-title mb-3">Nombre del permiso</h6>
                    <p class="card-text text-muted mb-0">
                        Use el formato <code>módulo.acción</code>. Ejemplos: <code>users.view</code>, <code>products.create</code>, <code>orders.delete</code>.
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
                <hr class="my-0">
                <div class="card-body">
                    <h6 class="card-title mb-3">Roles asignados</h6>
                    @php $rolesCount = $permission->roles()->count(); @endphp
                    @if($rolesCount > 0)
                        <p class="card-text text-muted mb-2">
                            Este permiso está actualmente asignado a <strong>{{ $rolesCount }} rol(es)</strong>.
                        </p>
                        <ul class="text-muted  mb-0">
                            @foreach($permission->roles()->limit(5)->get() as $role)
                                <li>{{ $role->name }}</li>
                            @endforeach
                            @if($rolesCount > 5)
                                <li class="text-muted">... y {{ $rolesCount - 5 }} más</li>
                            @endif
                        </ul>
                    @else
                        <p class="card-text text-muted mb-0">Este permiso no está asignado a ningún rol todavía.</p>
                    @endif
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
            submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Guardando...');

            $.ajax({
                url: '{{ route('settings.permissions.update', $permission->id) }}',
                type: 'PUT',
                data: new FormData(form),
                contentType: false,
                processData: false,
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: function (res) {
                    toastr.success(res.message || 'Permiso actualizado correctamente');
                    setTimeout(() => { window.location.href = '{{ route('settings.permissions.index') }}'; }, 1500);
                },
                error: function (xhr) {
                    submitBtn.prop('disabled', false).html('Guardar cambios');
                    const errors = xhr.responseJSON?.errors;
                    if (errors) {
                        $.each(errors, function (k, v) { toastr.error(v[0]); });
                    } else {
                        toastr.error(xhr.responseJSON?.message || 'Error al actualizar el permiso.');
                    }
                },
            });
        },
    });
});
</script>
@endpush
