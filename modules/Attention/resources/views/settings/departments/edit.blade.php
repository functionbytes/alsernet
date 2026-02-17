@extends('layouts.theme')

@section('title', 'Editar Departamento')

@section('content')
    <div class="row">
        <div class="col-lg-12 d-flex align-items-stretch">
            <div class="card w-100">
                <form id="formDepartment" action="{{ route('settings.attention.departments.update', $department) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <div class="card-header border-bottom p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-1 fw-bold">Editar Departamento</h5>
                                <p class="mb-0 text-muted small">Modifique la información del departamento.</p>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        @include('core::components.alerts')

                        <div class="row">
                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Nombre <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror"
                                           name="name" value="{{ old('name', $department->name) }}"
                                           placeholder="ej: Atención al Ciudadano" required>
                                    <small class="form-text text-muted">Nombre del departamento</small>
                                    @error('name')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Email</label>
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
                                    <label class="control-label col-form-label">Descripción</label>
                                    <textarea class="form-control @error('description') is-invalid @enderror"
                                              name="description" rows="3"
                                              placeholder="Descripción del departamento y sus funciones">{{ old('description', $department->description) }}</textarea>
                                    @error('description')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Nombre del Responsable</label>
                                    <input type="text" class="form-control @error('responsible_name') is-invalid @enderror"
                                           name="responsible_name" value="{{ old('responsible_name', $department->responsible_name) }}"
                                           placeholder="Juan Pérez">
                                    @error('responsible_name')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Email del Responsable</label>
                                    <input type="email" class="form-control @error('responsible_email') is-invalid @enderror"
                                           name="responsible_email" value="{{ old('responsible_email', $department->responsible_email) }}"
                                           placeholder="responsable@ejemplo.com">
                                    @error('responsible_email')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Teléfono</label>
                                    <input type="text" class="form-control @error('phone') is-invalid @enderror"
                                           name="phone" value="{{ old('phone', $department->phone) }}"
                                           placeholder="(123) 456-7890">
                                    @error('phone')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Estado</label>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="is_active" 
                                               id="is_active" value="1" {{ old('is_active', $department->is_active) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_active">
                                            Departamento activo
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Usuarios Asignados</label>
                                    <select class="form-select select2" name="users[]" multiple data-placeholder="Seleccionar usuarios...">
                                        @foreach($users as $user)
                                            <option value="{{ $user->id }}" 
                                                {{ (in_array($user->id, old('users', [])) || $department->users->contains($user->id)) ? 'selected' : '' }}>
                                                {{ $user->firstname }} {{ $user->lastname }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="form-text text-muted">Usuarios que gestionarán PQRSF de este departamento</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('settings.attention.departments.index') }}" class="btn btn-light">
                                Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                Actualizar Departamento
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('.select2').select2({
        theme: 'bootstrap-5',
        width: '100%',
    });
});
</script>
@endpush
