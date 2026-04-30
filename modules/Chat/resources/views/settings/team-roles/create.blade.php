@extends('layouts.theme')

@section('title', 'Nuevo rol de equipo')

@section('content')

    <div class="card w-100">

        <form id="formTeamRole" method="POST" action="{{ route('settings.chat.team-roles.store') }}">

            @csrf

            <div class="card-body">
                <div class="d-flex no-block align-items-center">
                    <h5 class="mb-0">Crear rol de equipo</h5>
                </div>
                <p class="card-subtitle mb-3 mt-1">
                    Define un nuevo rol con permisos específicos para los miembros del equipo.
                </p>

                <div class="row">

                    <!-- Basic Information -->
                    <div class="col-12">
                        <h6 class="mb-1 mt-3 fw-semibold">Información básica</h6>
                        <p class="text-muted small mb-3">Define el nombre y la descripción del rol.</p>
                    </div>

                    <div class="col-12">
                        <div class="mb-3">
                            <label class="control-label col-form-label">
                                Nombre
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="name" class="form-control" value="{{ old('name') }}" required placeholder="Ej: Supervisor">
                            <small class="form-text text-muted">Nombre identificativo del rol</small>
                            @error('name')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="mb-3">
                            <label class="control-label col-form-label">Descripción</label>
                            <textarea name="description" class="form-control" rows="2" placeholder="Descripción opcional del rol">{{ old('description') }}</textarea>
                            <small class="form-text text-muted">Proporciona más contexto sobre este rol</small>
                            @error('description')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <!-- Configuration -->
                    <div class="col-12">
                        <hr class="my-4">
                        <h6 class="mb-1 fw-semibold">Configuración</h6>
                        <p class="text-muted small mb-3">Opciones de configuración del rol.</p>
                    </div>

                    <div class="col-12">
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input type="hidden" name="is_default" value="0">
                                <input type="checkbox" name="is_default" class="form-check-input" id="isDefault" value="1" {{ old('is_default') ? 'checked' : '' }}>
                                <label class="form-check-label" for="isDefault">
                                    Establecer como rol por defecto para nuevos usuarios
                                </label>
                            </div>
                            <small class="form-text text-muted">Los nuevos usuarios recibirán este rol automáticamente</small>
                        </div>
                    </div>

                    <!-- Permissions -->
                    <div class="col-12">
                        <hr class="my-4">
                        <h6 class="mb-1 fw-semibold">Permisos</h6>
                        <p class="text-muted small mb-3">Selecciona los permisos que debe tener este rol.</p>
                    </div>

                    @php
                        $groupedPermissions = [];
                        foreach ($availablePermissions as $key => $label) {
                            $parts = explode('.', $key);
                            $group = $parts[0];
                            if (!isset($groupedPermissions[$group])) {
                                $groupedPermissions[$group] = [];
                            }
                            $groupedPermissions[$group][$key] = $label;
                        }
                    @endphp

                    <div class="col-12">
                        <div class="row">
                            @foreach($groupedPermissions as $group => $permissions)
                                <div class="col-md-6 mb-4">
                                    <div class="card">
                                        <div class="card-header bg-light">
                                            <strong>{{ ucfirst($group) }}</strong>
                                        </div>
                                        <div class="card-body">
                                            @foreach($permissions as $key => $label)
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input permission-checkbox" type="checkbox"
                                                           name="permissions[]" value="{{ $key }}"
                                                           id="perm_{{ $key }}"
                                                           {{ in_array($key, old('permissions', [])) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="perm_{{ $key }}">
                                                        {{ $label }}
                                                    </label>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                </div>
            </div>

            <div class="card-footer bg-light border-top">
                <button type="submit" class="btn btn-primary w-100 mb-1">
                    Guardar
                </button>
                <a href="{{ route('settings.chat.team-roles.index') }}" class="btn btn-secondary w-100">
                    Volver
                </a>
            </div>

        </form>

    </div>

@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        @if (session('success'))
            toastr.success('{{ session('success') }}', 'Éxito');
        @endif

        @if (session('error'))
            toastr.error('{{ session('error') }}', 'Error');
        @endif
    });
</script>
@endpush
