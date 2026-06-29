@extends('layouts.theme')

@section('title', 'Editar departamento')

@section('page_header')
    @include('core::components.card', ['title' => 'Editar departamento'])
@endsection

@section('content')

    <div class="row g-3">

        {{-- Formulario --}}
        <div class="col-12 col-lg-8">
            <div class="card">
                <form id="formDepartment" action="{{ route('settings.attention.departments.update', $department) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Editar departamento</h5>
                        <small class="text-muted">Modifique la información del departamento.</small>
                    </div>
                    <div class="card-body">
                        @include('core::components.alerts')

                        <div class="row">
                            <div class="col-12 col-md-12">
                                <div class="mb-3">
                                    <label class="form-label">Nombre <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror"
                                           name="name" value="{{ old('name', $department->name) }}"
                                           placeholder="ej: Atención al Ciudadano" required>
                                    <small class="form-text text-muted">Nombre del departamento</small>
                                    @error('name')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-12 col-md-12">
                                <div class="mb-3">
                                    <label class="form-label">Correo electronico</label>
                                    <input type="email" class="form-control @error('email') is-invalid @enderror"
                                           name="email" value="{{ old('email', $department->email) }}"
                                           placeholder="departamento@ejemplo.com">
                                    <small class="form-text text-muted">Email de contacto del departamento</small>
                                    @error('email')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="form-label">Descripción</label>
                                    <textarea class="form-control @error('description') is-invalid @enderror"
                                              name="description" rows="3"
                                              placeholder="Descripción del departamento y sus funciones">{{ old('description', $department->description) }}</textarea>
                                    @error('description')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-12 col-md-12">
                                <div class="mb-3">
                                    <label class="form-label">Nombre del responsable</label>
                                    <input type="text" class="form-control @error('responsible_name') is-invalid @enderror"
                                           name="responsible_name" value="{{ old('responsible_name', $department->responsible_name) }}"
                                           placeholder="Juan Pérez">
                                    @error('responsible_name')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-12 col-md-12">
                                <div class="mb-3">
                                    <label class="form-label">Correo electronico</label>
                                    <input type="email" class="form-control @error('responsible_email') is-invalid @enderror"
                                           name="responsible_email" value="{{ old('responsible_email', $department->responsible_email) }}"
                                           placeholder="responsable@ejemplo.com">
                                    @error('responsible_email')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-12 col-md-12">
                                <div class="mb-3">
                                    <label class="form-label">Teléfono</label>
                                    <input type="text" class="form-control @error('phone') is-invalid @enderror"
                                           name="phone" value="{{ old('phone', $department->phone) }}"
                                           placeholder="(123) 456-7890">
                                    @error('phone')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-12 col-md-12">
                                <div class="mb-3">
                                    <label class="form-label">Estado</label>
                                    <select class="form-select select2" name="is_active">
                                        <option value="1" {{ old('is_active', $department->is_active) == 1 ? 'selected' : '' }}>Activo</option>
                                        <option value="0" {{ old('is_active', $department->is_active) == 0 ? 'selected' : '' }}>Inactivo</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="form-label">Usuarios asignados</label>
                                    <select class="form-select select2" name="users[]" multiple data-placeholder="Seleccionar usuarios...">
                                        @foreach($users as $user)
                                            <option value="{{ $user->id }}"
                                                {{ (in_array($user->id, old('users', [])) || $department->users->contains($user->id)) ? 'selected' : '' }}>
                                                {{ $user->firstname }} {{ $user->lastname }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="form-text text-muted">Usuarios que gestionarán peticiones de este departamento</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                            <button type="submit" class="btn btn-primary w-100 mb-1">Guardar cambios</button>
                            <a href="{{ route('settings.attention.departments.index') }}" class="btn btn-light w-100 ">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Panel informativo --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-3">¿Qué es un departamento?</h6>
                    <p class="card-text text-muted">
                        Los departamentos agrupan a los funcionarios responsables de atender solicitudes peticiones según su área de gestión.
                    </p>
                </div>
                <hr class="my-0">
                <div class="card-body">
                    <h6 class="card-title mb-3">Responsable</h6>
                    <p class="card-text text-muted mb-0">
                        El responsable del departamento recibe notificaciones cuando se asignen nuevas solicitudes y puede supervisar el seguimiento.
                    </p>
                </div>
                <hr class="my-0">
                <div class="card-body">
                    <h6 class="card-title mb-3">Usuarios asignados</h6>
                    <p class="card-text text-muted mb-0">
                        Asigna los funcionarios que gestionarán las peticiones enrutadas a este departamento. Pueden ser modificados en cualquier momento.
                    </p>
                </div>
            </div>
        </div>

    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('select[name="is_active"]').select2('destroy').select2({
        width: '100%',
        minimumResultsForSearch: Infinity,
    });
    $('select[name="users[]"]').select2('destroy').select2({
        width: '100%',
        placeholder: 'Seleccionar usuarios...',
    });
});
</script>
@endpush
