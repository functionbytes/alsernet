@extends('layouts.theme')

@section('content')

    <div class="card w-100">

        <form id="formGroup" method="POST" action="{{ route('manager.helpdesk.backups.tickets.groups.update', $group->id) }}">

            {{ csrf_field() }}
            @method('PUT')

            <div class="card-body">
                <div class="d-flex no-block align-items-center">
                    <h5 class="mb-0">Editar grupo</h5>
                </div>
                <p class="card-subtitle mb-3 mt-1">
                    Modifica la configuración de este grupo. Los cambios afectarán cómo se asignan los tickets.
                </p>

                <div class="row">

                    <!-- Basic Information -->
                    <div class="col-12">
                        <h6 class="mb-1 mt-3 fw-semibold">Información básica</h6>
                        <p class="text-muted mb-3">Define el nombre y descripción del grupo.</p>
                    </div>

                    <div class="col-12">
                        <div class="mb-3">
                            <label class="control-label col-form-label">
                                Nombre del grupo
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $group->name) }}" required placeholder="Ej: Soporte Técnico Nivel 1">
                            <small class="form-text text-muted">Nombre visible del grupo</small>
                            @error('name')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="mb-3">
                            <label class="control-label col-form-label">Descripción</label>
                            <textarea name="description" class="form-control" rows="3" placeholder="Descripción del grupo">{{ old('description', $group->description) }}</textarea>
                            <small class="form-text text-muted">Proporciona más contexto sobre este grupo</small>
                            @error('description')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <!-- Assignment Configuration -->
                    <div class="col-12">
                        <hr class="my-4">
                        <h6 class="mb-1 fw-semibold">Configuración de asignación</h6>
                        <p class="text-muted mb-3">Define cómo se asignarán los tickets a los miembros del grupo.</p>
                    </div>

                    <div class="col-12">
                        <div class="mb-3">
                            <label class="control-label col-form-label">
                                Modo de asignación
                                <span class="text-danger">*</span>
                            </label>
                            <select name="assignment_mode" class="form-select" required>
                                <option value="manual" {{ old('assignment_mode', $group->assignment_mode) == 'manual' ? 'selected' : '' }}>
                                    Manual - El administrador asigna manualmente
                                </option>
                                <option value="round_robin" {{ old('assignment_mode', $group->assignment_mode) == 'round_robin' ? 'selected' : '' }}>
                                    Round Robin - Rotación entre agentes
                                </option>
                                <option value="load_balanced" {{ old('assignment_mode', $group->assignment_mode) == 'load_balanced' ? 'selected' : '' }}>
                                    Balance de Carga - Asigna al agente con menos tickets activos
                                </option>
                            </select>
                            <small class="form-text text-muted">Estrategia de asignación automática de tickets</small>
                            @error('assignment_mode')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <!-- Members -->
                    <div class="col-12">
                        <hr class="my-4">
                        <h6 class="mb-1 fw-semibold">Miembros del grupo</h6>
                        <p class="text-muted mb-3">Selecciona los agentes que pertenecen a este grupo.</p>
                    </div>

                    <div class="col-12">
                        <div class="mb-3">
                            <label class="control-label col-form-label">Agentes</label>
                            <select name="users[]" class="form-select" multiple size="8">
                                @foreach($users ?? [] as $user)
                                    <option value="{{ $user->id }}" {{ in_array($user->id, old('users', $group->users ?? [])) ? 'selected' : '' }}>
                                        {{ $user->name }} ({{ $user->email }})
                                    </option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">Mantén Ctrl/Cmd para seleccionar múltiples agentes</small>
                            @error('users')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <!-- Options -->
                    <div class="col-12">
                        <hr class="my-4">
                        <h6 class="mb-1 fw-semibold">Opciones</h6>
                        <p class="text-muted mb-3">Configura el comportamiento del grupo.</p>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="border-bottom pb-3 mb-3">
                            <div class="form-check form-switch">
                                <input type="hidden" name="is_active" value="0">
                                <input type="checkbox" name="is_active" class="form-check-input" id="activeCheck" value="1" {{ old('is_active', $group->is_active) ? 'checked' : '' }}>
                                <label class="form-check-label" for="activeCheck">
                                    <strong>Grupo activo</strong>
                                    <small class="d-block text-muted">Permite que este grupo esté disponible para asignación de tickets.</small>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="border-bottom pb-3 mb-3">
                            <div class="form-check form-switch">
                                <input type="hidden" name="is_default" value="0">
                                <input type="checkbox" name="is_default" class="form-check-input" id="defaultCheck" value="1" {{ old('is_default', $group->is_default) ? 'checked' : '' }}>
                                <label class="form-check-label" for="defaultCheck">
                                    <strong>Grupo Por defecto</strong>
                                    <small class="d-block text-muted">Se selecciona automáticamente para nuevos tickets sin grupo asignado.</small>
                                </label>
                            </div>
                        </div>
                    </div>

                </div>

            </div>

            <div class="card-footer">
                <button type="submit" class="btn btn-info px-4 waves-effect waves-light mt-2 w-100">
                    Guardar
                </button>
                <a href="{{ route('manager.helpdesk.backups.tickets.groups.index') }}" class="btn btn-secondary px-4 waves-effect waves-light mt-2 w-100">
                    Volver
                </a>
            </div>

        </form>

    </div>

@endsection

@section('scripts')
<script>
$(document).ready(function() {
    @if (session('success'))
        toastr.success('{{ session('success') }}', 'Grupo actualizado');
    @endif

    @if (session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif
});
</script>
@endsection
