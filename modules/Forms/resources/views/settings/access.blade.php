@extends('layouts.theme')

@section('title', 'Control de acceso: ' . $form->name)

@section('content')

    @include('core::components.card', ['title' => 'Control de acceso'])

    <div class="widget-content searchable-container list">

        <div class="card mb-4">
            <div class="card-header border-bottom">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <a href="{{ route('settings.forms.index') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                        <div>
                            <h5 class="mb-0 fw-bold">{{ $form->name }}</h5>
                            <small class="text-muted">{{ $form->slug }}</small>
                        </div>
                        @if ($form->is_active)
                            <span class="badge bg-light-success text-success">Activo</span>
                        @else
                            <span class="badge bg-light-danger text-danger">Inactivo</span>
                        @endif
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('settings.forms.preview', $form) }}" target="_blank" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-desktop me-1"></i> Preview
                        </a>
                        <a href="{{ route('settings.forms.submissions.index', $form) }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-inbox me-1"></i> Submissions
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 align-items-start">
            <div class="col-lg-9">
                <form action="{{ route('settings.forms.settings.access.update', $form) }}" method="POST" id="accessForm">
                    @csrf
                    @method('PATCH')

                    <div class="card">

                        <div class="card-header p-4 border-bottom">
                            <h5 class="mb-1 fw-bold">Control de acceso</h5>
                            <p class="small mb-0 text-muted">Define quién puede acceder y enviar este formulario</p>
                        </div>

                        {{-- Tipo de acceso --}}
                        <div class="card-body">
                            <h6 class="fw-bold mb-1">Tipo de acceso</h6>
                            <p class="text-muted mb-3">Define quién puede enviar este formulario.</p>

                            <div class="row g-3">
                                <div class="col-12">
                                    <label for="access_control" class="form-label">Quién puede enviar este formulario</label>
                                    <select id="access_control" name="access_control" class="form-select">
                                        <option value="public"        {{ old('access_control', $form->access_control) === 'public'        ? 'selected' : '' }}>Público (cualquier visitante)</option>
                                        <option value="authenticated" {{ old('access_control', $form->access_control) === 'authenticated' ? 'selected' : '' }}>Solo usuarios autenticados</option>
                                        <option value="roles"         {{ old('access_control', $form->access_control) === 'roles'         ? 'selected' : '' }}>Roles específicos</option>
                                    </select>
                                </div>

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

                        <hr class="my-4">

                        {{-- Protección con contraseña --}}
                        <div class="card-body">
                            <h6 class="fw-bold mb-1">Protección con contraseña</h6>
                            <p class="text-muted mb-3">Requiere una contraseña antes de mostrar el formulario.</p>

                            <div class="row g-3">
                                <div class="col-12">
                                    <div class="mb-2">
                                        <label class="form-label" for="is_password_protected">Protección con contraseña</label>
                                        <select class="form-select" name="is_password_protected" id="is_password_protected">
                                            <option value="1" {{ old('is_password_protected', $form->is_password_protected) == 1 ? 'selected' : '' }}>Activado</option>
                                            <option value="0" {{ old('is_password_protected', $form->is_password_protected) == 0 ? 'selected' : '' }}>Desactivado</option>
                                        </select>
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

                        <hr class="my-4">

                        {{-- Límites de envío --}}
                        <div class="card-body">
                            <h6 class="fw-bold mb-1">Límites de envío</h6>
                            <p class="text-muted mb-3">Restricciones sobre cuántas veces puede enviar un mismo usuario.</p>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="mb-2">
                                        <label class="form-label" for="limit_per_user">Limitar 1 envío por usuario</label>
                                        <select class="form-select" name="limit_per_user" id="limit_per_user">
                                            <option value="1" {{ old('limit_per_user', $form->limit_per_user) == 1 ? 'selected' : '' }}>Activado</option>
                                            <option value="0" {{ old('limit_per_user', $form->limit_per_user) == 0 ? 'selected' : '' }}>Desactivado</option>
                                        </select>
                                    </div>
                                    <div class="form-text small">Requiere autenticación para funcionar.</div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-2">
                                        <label class="form-label" for="prevent_duplicate_email">Prevenir emails duplicados</label>
                                        <select class="form-select" name="prevent_duplicate_email" id="prevent_duplicate_email">
                                            <option value="1" {{ old('prevent_duplicate_email', $form->prevent_duplicate_email) == 1 ? 'selected' : '' }}>Activado</option>
                                            <option value="0" {{ old('prevent_duplicate_email', $form->prevent_duplicate_email) == 0 ? 'selected' : '' }}>Desactivado</option>
                                        </select>
                                    </div>
                                    <div class="form-text small">Rechaza envíos con un email ya registrado.</div>
                                </div>
                            </div>
                        </div>

                        <div class="card-footer p-4">
                            <button type="submit" class="btn btn-primary w-100">
                                Guardar cambios
                            </button>
                        </div>

                    </div>
                </form>
            </div>

            <div class="col-lg-3">
                @include('forms::settings.partials.tabs', ['active' => 'access'])

                <div class="card mt-3">
                    <div class="card-header border-bottom">
                        <h6 class="mb-0 fw-bold">Tokens de acceso</h6>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-2">Genera tokens para acceder al formulario de forma programática.</p>
                        <a href="{{ route('settings.forms.access-tokens.index', $form) }}" class="btn btn-outline-secondary btn-sm w-100">
                            Gestionar tokens
                        </a>
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
        $('#passwordGroup').toggleClass('d-none', this.value !== '1');
    });
</script>
@endpush
