@extends('layouts.theme')

@section('title', 'Editar Credencial')

@section('page_header')
    @include('core::components.card', ['title' => 'Editar Credencial'])
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Editar credencial de: {{ $endpoint->name }}</h4>
                </div>

                <form action="{{ route('settings.erp.endpoints.credentials.update', [$endpoint, $credential]) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="auth_type" class="form-label">Tipo de Autenticación</label>
                                <select class="form-control select2 @error('auth_type') is-invalid @enderror"
                                        id="auth_type" name="auth_type" required>
                                    <option value="none" {{ old('auth_type', $credential->auth_type) === 'none' ? 'selected' : '' }}>Sin autenticación</option>
                                    <option value="basic" {{ old('auth_type', $credential->auth_type) === 'basic' ? 'selected' : '' }}>Basic Auth</option>
                                    <option value="bearer" {{ old('auth_type', $credential->auth_type) === 'bearer' ? 'selected' : '' }}>Bearer Token</option>
                                    <option value="api_key" {{ old('auth_type', $credential->auth_type) === 'api_key' ? 'selected' : '' }}>API Key</option>
                                    <option value="custom" {{ old('auth_type', $credential->auth_type) === 'custom' ? 'selected' : '' }}>Custom Headers</option>
                                </select>
                                @error('auth_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="expires_at" class="form-label">Fecha de Expiración <small class="text-muted">(opcional)</small></label>
                                <input type="datetime-local" class="form-control @error('expires_at') is-invalid @enderror"
                                       id="expires_at" name="expires_at" value="{{ old('expires_at', $credential->expires_at ? $credential->expires_at->format('Y-m-d\TH:i') : '') }}">
                                @error('expires_at')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Basic Auth Fields --}}
                            <div class="col-md-6 mb-3 auth-field auth-basic" style="display: none;">
                                <label for="username" class="form-label">Usuario</label>
                                <input type="text" class="form-control @error('username') is-invalid @enderror"
                                       id="username" name="username" value="{{ old('username', $credential->username) }}">
                                @error('username')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3 auth-field auth-basic" style="display: none;">
                                <label for="password" class="form-label">Contraseña <small class="text-muted">(dejar en blanco para mantener)</small></label>
                                <input type="password" class="form-control @error('password') is-invalid @enderror"
                                       id="password" name="password" placeholder="********">
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Bearer Token Field --}}
                            <div class="col-md-12 mb-3 auth-field auth-bearer" style="display: none;">
                                <label for="token" class="form-label">Bearer Token <small class="text-muted">(dejar en blanco para mantener)</small></label>
                                <textarea class="form-control @error('token') is-invalid @enderror"
                                          id="token" name="token" rows="3" placeholder="********">{{ old('token') }}</textarea>
                                @error('token')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- API Key Fields --}}
                            <div class="col-md-6 mb-3 auth-field auth-api_key" style="display: none;">
                                <label for="api_key_header" class="form-label">Nombre del Header</label>
                                <input type="text" class="form-control @error('api_key_header') is-invalid @enderror"
                                       id="api_key_header" name="api_key_header" value="{{ old('api_key_header', $credential->api_key_header ?? 'X-API-Key') }}"
                                       placeholder="X-API-Key">
                                @error('api_key_header')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3 auth-field auth-api_key" style="display: none;">
                                <label for="api_key" class="form-label">API Key <small class="text-muted">(dejar en blanco para mantener)</small></label>
                                <input type="text" class="form-control @error('api_key') is-invalid @enderror"
                                       id="api_key" name="api_key" value="{{ old('api_key') }}" placeholder="********">
                                @error('api_key')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Custom Headers Field --}}
                            <div class="col-md-12 mb-3 auth-field auth-custom" style="display: none;">
                                <label for="custom_headers" class="form-label">Custom Headers <small class="text-muted">(JSON)</small></label>
                                <textarea class="form-control @error('custom_headers') is-invalid @enderror"
                                          id="custom_headers" name="custom_headers" rows="5">{{ old('custom_headers', $credential->custom_headers ? json_encode($credential->custom_headers, JSON_PRETTY_PRINT) : '{}') }}</textarea>
                                <small class="text-muted">Ejemplo: {"X-Custom-Header": "value", "X-Another-Header": "value2"}</small>
                                @error('custom_headers')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-12 mb-3">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" {{ old('is_active', $credential->is_active) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_active">
                                        Activar esta credencial (desactivará otras credenciales activas)
                                    </label>
                                </div>
                            </div>

                            @if($credential->last_used_at)
                            <div class="col-md-12 mb-3">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Última utilización: {{ $credential->last_used_at->diffForHumans() }} ({{ $credential->last_used_at->format('Y-m-d H:i:s') }})
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Guardar Cambios
                        </button>
                        <a href="{{ route('settings.erp.endpoints.credentials.index', $endpoint) }}" class="btn btn-secondary">
                            <i class="fas fa-times me-1"></i> Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function() {
    const authType = $('#auth_type');

    function toggleAuthFields() {
        const selected = authType.val();

        // Hide all auth fields
        $('.auth-field').hide();

        // Show relevant fields based on selection
        if (selected !== 'none') {
            $('.auth-' + selected).show();
        }
    }

    // Initial state
    toggleAuthFields();

    // On change
    authType.on('change', toggleAuthFields);
});
</script>
@endpush
