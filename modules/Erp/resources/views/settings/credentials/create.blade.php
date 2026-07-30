@extends('layouts.theme')

@section('title', 'Crear Credencial ERP')

@section('page_header')
    @include('core::components.card', ['title' => 'Crear Credencial ERP'])
@endsection

@section('content')

    <div class="card w-100">

        <form method="POST" action="{{ route('settings.erp.credentials.store') }}">

            @csrf

            <div class="card-body">
                <div class="d-flex no-block align-items-center">
                    <h5 class="mb-0">Crear nueva credencial ERP</h5>
                </div>
                <p class="card-subtitle mb-3 mt-1">
                    Configura una nueva credencial de autenticación para los endpoints.
                </p>

                <div class="row">

                    <!-- Basic Information -->
                    <div class="col-12">
                        <h6 class="mb-1 mt-3 fw-semibold">Información básica</h6>
                        <p class="text-muted small mb-3">Define el nombre y la descripción de la credencial.</p>
                    </div>

                    <div class="col-12">
                        <div class="mb-3">
                            <label class="control-label col-form-label">
                                Nombre
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name') }}" required placeholder="Ej: Gestión - Basic Auth">
                            <small class="form-text text-muted">Nombre identificativo de la credencial</small>
                            @error('name')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="mb-3">
                            <label class="control-label col-form-label">Descripción</label>
                            <textarea name="description" class="form-control @error('description') is-invalid @enderror"
                                      rows="2" placeholder="Descripción opcional de la credencial">{{ old('description') }}</textarea>
                            <small class="form-text text-muted">Proporciona más contexto sobre esta credencial</small>
                            @error('description')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <!-- Authentication Type -->
                    <div class="col-12">
                        <hr class="my-4">
                        <h6 class="mb-1 fw-semibold">Tipo de autenticación</h6>
                        <p class="text-muted small mb-3">Selecciona el método de autenticación que utilizarán los endpoints.</p>
                    </div>

                    <div class="col-12">
                        <div class="mb-3">
                            <label class="control-label col-form-label">
                                Método de autenticación
                                <span class="text-danger">*</span>
                            </label>
                            <select name="auth_type" id="auth_type" class="form-control select2 @error('auth_type') is-invalid @enderror" required>
                                <option value="">Seleccionar tipo...</option>
                                <option value="none" {{ old('auth_type') === 'none' ? 'selected' : '' }}>Sin autenticación</option>
                                <option value="basic" {{ old('auth_type') === 'basic' ? 'selected' : '' }}>Basic Auth</option>
                                <option value="bearer" {{ old('auth_type') === 'bearer' ? 'selected' : '' }}>Bearer Token</option>
                                <option value="api_key" {{ old('auth_type') === 'api_key' ? 'selected' : '' }}>API Key</option>
                                <option value="custom" {{ old('auth_type') === 'custom' ? 'selected' : '' }}>Custom Headers</option>
                            </select>
                            @error('auth_type')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <!-- Basic Auth Fields -->
                    <div id="basic-auth-fields" class="auth-fields" style="display: none;">
                        <div class="col-12 col-md-6">
                            <div class="mb-3">
                                <label class="control-label col-form-label">Usuario</label>
                                <input type="text" name="username" class="form-control @error('username') is-invalid @enderror"
                                       value="{{ old('username') }}" placeholder="usuario">
                                <small class="form-text text-muted">Nombre de usuario para autenticación básica</small>
                                @error('username')
                                    <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="mb-3">
                                <label class="control-label col-form-label">Contraseña</label>
                                <input type="password" name="password" class="form-control @error('password') is-invalid @enderror"
                                       placeholder="••••••••">
                                <small class="form-text text-muted">Contraseña para autenticación básica</small>
                                @error('password')
                                    <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Bearer Token Fields -->
                    <div id="bearer-fields" class="auth-fields" style="display: none;">
                        <div class="col-12">
                            <div class="mb-3">
                                <label class="control-label col-form-label">Bearer Token</label>
                                <textarea name="token" class="form-control @error('token') is-invalid @enderror"
                                          rows="3" placeholder="eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...">{{ old('token') }}</textarea>
                                <small class="form-text text-muted">Token JWT o similar que se enviará en el header Authorization</small>
                                @error('token')
                                    <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- API Key Fields -->
                    <div id="api-key-fields" class="auth-fields" style="display: none;">
                        <div class="col-12 col-md-8">
                            <div class="mb-3">
                                <label class="control-label col-form-label">API Key</label>
                                <input type="text" name="api_key" class="form-control @error('api_key') is-invalid @enderror"
                                       value="{{ old('api_key') }}" placeholder="sk_live_51K...">
                                <small class="form-text text-muted">Clave API que se enviará en el header personalizado</small>
                                @error('api_key')
                                    <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="col-12 col-md-4">
                            <div class="mb-3">
                                <label class="control-label col-form-label">Nombre del header</label>
                                <input type="text" name="api_key_header" class="form-control @error('api_key_header') is-invalid @enderror"
                                       value="{{ old('api_key_header', 'X-API-Key') }}" placeholder="X-API-Key">
                                <small class="form-text text-muted">Header HTTP donde se enviará la API key</small>
                                @error('api_key_header')
                                    <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Custom Headers Fields -->
                    <div id="custom-fields" class="auth-fields" style="display: none;">
                        <div class="col-12">
                            <div class="mb-3">
                                <label class="control-label col-form-label">Custom Headers (JSON)</label>
                                <textarea name="custom_headers_json" id="custom_headers_json" class="form-control @error('custom_headers') is-invalid @enderror"
                                          rows="4">{{ old('custom_headers_json', '{}') }}</textarea>
                                <small class="form-text text-muted">Formato JSON. Ejemplo: {"Authorization": "Bearer xyz", "X-Custom": "value"}</small>
                                @error('custom_headers')
                                    <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Additional Settings -->
                    <div class="col-12">
                        <hr class="my-4">
                        <h6 class="mb-1 fw-semibold">Configuración adicional</h6>
                        <p class="text-muted small mb-3">Ajusta la expiración y el estado de la credencial.</p>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="control-label col-form-label">Fecha de expiración</label>
                            <input type="datetime-local" name="expires_at" class="form-control @error('expires_at') is-invalid @enderror"
                                   value="{{ old('expires_at') }}">
                            <small class="form-text text-muted">Opcional. Deja vacío si no expira</small>
                            @error('expires_at')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="control-label col-form-label">
                                Estado
                                <span class="text-danger">*</span>
                            </label>
                            <select name="is_active" class="form-control select2 @error('is_active') is-invalid @enderror" required>
                                <option value="1" {{ old('is_active', '1') == '1' ? 'selected' : '' }}>Activa</option>
                                <option value="0" {{ old('is_active', '1') == '0' ? 'selected' : '' }}>Inactiva</option>
                            </select>
                            <small class="form-text text-muted">Las credenciales desactivadas no se pueden asignar a endpoints</small>
                            @error('is_active')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                </div>
            </div>

            <div class="card-footer bg-light border-top">
                <button type="submit" class="btn btn-primary w-100 mb-1">
                    Crear
                </button>
                <a href="{{ route('settings.erp.credentials.index') }}" class="btn btn-secondary w-100">
                    Cancelar
                </a>
            </div>

        </form>

    </div>

@endsection

@push('scripts')
<script>
$(function() {
    // Show/hide auth fields based on auth_type
    $('#auth_type').on('change', function() {
        const authType = $(this).val();
        $('.auth-fields').hide();

        if (authType === 'basic') {
            $('#basic-auth-fields').show();
        } else if (authType === 'bearer') {
            $('#bearer-fields').show();
        } else if (authType === 'api_key') {
            $('#api-key-fields').show();
        } else if (authType === 'custom') {
            $('#custom-fields').show();
        }
    }).trigger('change');

    // Convert custom_headers JSON to object before submit
    $('form').on('submit', function() {
        const customHeadersJson = $('#custom_headers_json').val();
        if (customHeadersJson && $('#auth_type').val() === 'custom') {
            try {
                const headers = JSON.parse(customHeadersJson);
                // Create hidden inputs for each header
                Object.entries(headers).forEach(([key, value]) => {
                    $('<input>').attr({
                        type: 'hidden',
                        name: `custom_headers[${key}]`,
                        value: value
                    }).appendTo(this);
                });
            } catch (e) {
                alert('JSON inválido en Custom Headers');
                return false;
            }
        }
    });
});
</script>
@endpush
