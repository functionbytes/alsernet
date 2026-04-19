@extends('layouts.theme')

@section('title', 'Editar rol')

@section('content')

    @include('core::components.card', ['title' => 'Editar rol'])

    <div class="row g-3">

        {{-- Formulario --}}
        <div class="col-12 col-lg-8">
            <div class="card">
                <form id="formRoles" enctype="multipart/form-data" role="form" onSubmit="return false">
                    @csrf
                    <input type="hidden" name="id" id="id" value="{{ $role->id }}">
                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Editar rol: {{ $role->name }}</h5>
                        <small class="text-muted">Actualiza la información del rol en el sistema.</small>
                    </div>
                    <div class="card-body">
                        @include('core::components.alerts')

                        @if(in_array($role->name, ['super-settings', 'customer']))
                            <div class="alert alert-warning border-0 mb-3">
                                <i class="fas fa-triangle-exclamation me-2"></i>
                                <strong>Rol del sistema:</strong> Este es un rol protegido. Algunas opciones están limitadas para preservar la integridad del sistema.
                            </div>
                        @endif

                        <div class="col-12 mb-3">
                            <label class="form-label">Nombre del rol <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name"
                                   value="{{ $role->name }}"
                                   placeholder="ej: supervisor-inventario" required autofocus>
                            <small class="form-text text-muted">Mínimo 3 caracteres, máximo 50. Use minúsculas y guiones</small>
                        </div>

                        <div class="col-12 mb-3">
                            <label class="form-label">Descripción</label>
                            <textarea class="form-control" name="description" rows="3"
                                      placeholder="Describe el propósito y responsabilidades de este rol...">{{ $role->description ?? '' }}</textarea>
                            <small class="form-text text-muted">Máximo 255 caracteres. Sea claro y conciso</small>
                        </div>

                        <div class="col-12 mb-3">
                            <label class="form-label">Guard <span class="text-danger">*</span></label>
                            <select class="form-select select2" name="guard_name" required>
                                <option value="web" {{ $role->guard_name === 'web' ? 'selected' : '' }}>Web (Navegador)</option>
                                <option value="api" {{ $role->guard_name === 'api' ? 'selected' : '' }}>API (Token/OAuth)</option>
                            </select>
                            <small class="form-text text-muted">Define el tipo de autenticación para este rol</small>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-1">
                            Guardar cambios
                        </button>
                        <a href="{{ route('settings.roles.index') }}" class="btn btn-light w-100">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Panel informativo --}}
        <div class="col-lg-4">

            {{-- Información del rol --}}
            <div class="card mb-3">
                <div class="card-header p-3 bg-white border-bottom">
                    <h5 class="mb-0 fw-bold">Información del rol</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Guard:</span>
                            <span class="fw-bold">{{ $role->guard_name === 'web' ? 'Web' : 'API' }}</span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Usuarios asignados:</span>
                            <span class="fw-bold">{{ $role->users()->count() }}</span>
                        </div>
                    </div>
                    <div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Permisos asignados:</span>
                            <span class="fw-bold">{{ $role->permissions()->count() }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Acciones --}}
            <div class="card mb-3">
                <div class="card-header p-3 bg-white border-bottom">
                    <h5 class="mb-0 fw-bold">Acciones</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('settings.roles.show.permissions', $role->id) }}" class="btn btn-outline-secondary">
                            Gestionar permisos
                        </a>
                        <a href="{{ route('settings.roles.show.modules', $role->id) }}" class="btn btn-outline-secondary">
                            Gestionar módulos
                        </a>
                        <a href="{{ route('settings.roles.show.users', $role->id) }}" class="btn btn-outline-secondary">
                            Ver usuarios
                        </a>
                        <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#copyPermsModal">
                            Copiar permisos de otro rol
                        </button>
                        <a href="{{ route('settings.roles.index') }}" class="btn btn-outline-secondary">
                            Volver al listado
                        </a>
                    </div>
                </div>
            </div>

            {{-- Convención de nombres --}}
            <div class="card mb-3">
                <div class="card-header p-3 bg-white border-bottom">
                    <h5 class="mb-0 fw-bold">Convención de nombres</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-2">Use minúsculas y guiones. Ejemplos:</p>
                    <ul class="text-muted  mb-0">
                        <li><code>supervisor-inventario</code></li>
                        <li><code>editor-contenido</code></li>
                        <li><code>soporte-nivel-1</code></li>
                    </ul>
                </div>
            </div>

            {{-- Guard --}}
            <div class="card">
                <div class="card-header p-3 bg-white border-bottom">
                    <h5 class="mb-0 fw-bold">Guard</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-0">
                        <strong>Web</strong>: para usuarios autenticados por sesión (navegador).<br>
                        <strong>API</strong>: para autenticación por token o OAuth.
                    </p>
                </div>
            </div>

        </div>

    </div>

<div class="modal fade" id="copyPermsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Copiar permisos de otro rol</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info border-0">
                    <i class="fas fa-circle-info me-2"></i>
                    Se agregarán los permisos del rol seleccionado al rol actual. Los permisos existentes no se eliminarán.
                </div>
                <div class="mb-3">
                    <label class="form-label">Copiar permisos desde</label>
                    <select id="sourceRoleSelect" class="form-select">
                        <option value="">Selecciona un rol...</option>
                        @foreach(\Spatie\Permission\Models\Role::where('id', '!=', $role->id)->orderBy('name')->get() as $r)
                            <option value="{{ $r->id }}">{{ $r->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" id="copyPermsBtn" class="btn btn-primary w-100 mb-2">Copiar permisos</button>
                <button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">Cancelar</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    $('.select2').select2({ minimumResultsForSearch: Infinity });

    $('#copyPermsBtn').on('click', function () {
        var sourceId = $('#sourceRoleSelect').val();
        if (!sourceId) { toastr.warning('Selecciona un rol de origen.'); return; }

        var $btn = $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Copiando...');

        $.ajax({
            url: '{{ route("settings.roles.copy-from", $role->id) }}',
            type: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data: { source_role_id: sourceId },
            success: function (r) {
                toastr.success(r.message, 'Éxito', { positionClass: 'toast-bottom-right' });
                $('#copyPermsModal').modal('hide');
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message ?? 'Error al copiar permisos.', 'Error', { positionClass: 'toast-bottom-right' });
            },
            complete: function () {
                $btn.prop('disabled', false).html('Copiar permisos');
            }
        });
    });

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
            submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Guardando...');

            $.ajax({
                url: '{{ route('settings.roles.update', $role->id) }}',
                type: 'PUT',
                data: new FormData(form),
                contentType: false,
                processData: false,
                success: function (res) {
                    toastr.success(res.message || 'Rol actualizado correctamente');
                    setTimeout(() => { window.location.href = '{{ route('settings.roles.index') }}'; }, 1500);
                },
                error: function (xhr) {
                    submitBtn.prop('disabled', false).html(original);
                    const errors = xhr.responseJSON?.errors;
                    if (errors) {
                        $.each(errors, function (k, v) { toastr.error(v[0]); });
                    } else {
                        toastr.error(xhr.responseJSON?.message || 'Error al actualizar el rol.');
                    }
                },
            });
        },
    });
});
</script>
@endpush
