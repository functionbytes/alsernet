@extends('layouts.theme')

@section('title', 'Control de acceso: ' . $form->name)

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">

            <div class="d-flex align-items-center gap-2 mb-4">
                <a href="{{ route('settings.forms.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <div>
                    <h5 class="mb-0 fw-bold">{{ $form->name }}</h5>
                    <span class="text-muted">{{ $form->slug }}</span>
                </div>
            </div>

            @include('forms::settings.partials.tabs', ['active' => 'access'])

            <div class="row">
                <div class="col-lg-8">
                    <form action="{{ route('settings.forms.settings.access.update', $form) }}" method="POST" id="accessForm">
                        @csrf
                        @method('PATCH')

                        {{-- Tipo de acceso --}}
                        <div class="card mb-4">
                            <div class="card-body">
                                <h6 class="fw-bold mb-3 border-bottom pb-2">Tipo de acceso</h6>
                                <div class="row g-3">

                                    <div class="col-12">
                                        <label for="access_control" class="form-label">Quién puede enviar este formulario</label>
                                        <select id="access_control" name="access_control" class="form-select">
                                            <option value="public"        {{ old('access_control', $form->access_control) === 'public'        ? 'selected' : '' }}>Público (cualquier visitante)</option>
                                            <option value="authenticated" {{ old('access_control', $form->access_control) === 'authenticated' ? 'selected' : '' }}>Solo usuarios autenticados</option>
                                            <option value="roles"         {{ old('access_control', $form->access_control) === 'roles'         ? 'selected' : '' }}>Roles específicos</option>
                                        </select>
                                    </div>

                                    {{-- Roles (condicional) --}}
                                    <div id="rolesGroup" class="{{ old('access_control', $form->access_control) === 'roles' ? '' : 'd-none' }}">
                                        <label class="form-label">Roles con acceso</label>
                                        <div class="row g-2">
                                            @foreach ($roles as $role)
                                                <div class="col-md-4">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox"
                                                               name="allowed_roles[]"
                                                               value="{{ $role->name }}"
                                                               id="role_{{ $role->id }}"
                                                               {{ in_array($role->name, old('allowed_roles', $form->allowed_roles ?? [])) ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="role_{{ $role->id }}">
                                                            {{ $role->name }}
                                                        </label>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                        @if ($roles->isEmpty())
                                            <p class="text-muted mt-2">No hay roles configurados en el sistema.</p>
                                        @endif
                                    </div>

                                </div>
                            </div>
                        </div>

                        {{-- Protección con contraseña --}}
                        <div class="card mb-4">
                            <div class="card-body">
                                <h6 class="fw-bold mb-3 border-bottom pb-2">Protección con contraseña</h6>
                                <div class="row g-3">

                                    <div class="col-12">
                                        <div class="form-check form-switch">
                                            <input type="hidden" name="is_password_protected" value="0">
                                            <input class="form-check-input" type="checkbox" id="is_password_protected"
                                                   name="is_password_protected" value="1"
                                                   {{ old('is_password_protected', $form->is_password_protected) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="is_password_protected">
                                                Proteger el formulario con contraseña
                                            </label>
                                        </div>
                                    </div>

                                    <div id="passwordGroup" class="{{ old('is_password_protected', $form->is_password_protected) ? '' : 'd-none' }}">
                                        <label for="password" class="form-label">Contraseña</label>
                                        <input type="password" id="password" name="password" class="form-control"
                                               autocomplete="new-password"
                                               placeholder="Mínimo 6 caracteres">
                                        <div class="form-text">El usuario deberá ingresar esta contraseña antes de ver el formulario. Dejar vacío para mantener la contraseña actual.</div>
                                    </div>

                                </div>
                            </div>
                        </div>

                        {{-- Límites de envío --}}
                        <div class="card mb-4">
                            <div class="card-body">
                                <h6 class="fw-bold mb-3 border-bottom pb-2">Límites de envío</h6>
                                <div class="row g-3">

                                    <div class="col-md-6">
                                        <div class="form-check form-switch">
                                            <input type="hidden" name="limit_per_user" value="0">
                                            <input class="form-check-input" type="checkbox" id="limit_per_user"
                                                   name="limit_per_user" value="1"
                                                   {{ old('limit_per_user', $form->limit_per_user) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="limit_per_user">
                                                Limitar 1 envío por usuario
                                            </label>
                                        </div>
                                        <div class="form-text small">Requiere autenticación para funcionar.</div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-check form-switch">
                                            <input type="hidden" name="prevent_duplicate_email" value="0">
                                            <input class="form-check-input" type="checkbox" id="prevent_duplicate_email"
                                                   name="prevent_duplicate_email" value="1"
                                                   {{ old('prevent_duplicate_email', $form->prevent_duplicate_email) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="prevent_duplicate_email">
                                                Prevenir emails duplicados
                                            </label>
                                        </div>
                                        <div class="form-text small">Rechaza envíos con un email ya registrado.</div>
                                    </div>

                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Guardar cambios
                            </button>
                        </div>

                    </form>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $('#access_control').on('change', function () {
        $('#rolesGroup').toggleClass('d-none', this.value !== 'roles');
    });

    $('#is_password_protected').on('change', function () {
        $('#passwordGroup').toggleClass('d-none', !this.checked);
    });
</script>
@endpush
