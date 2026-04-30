@extends('layouts.theme')

@section('content')
@include('core::components.alerts')

<div class="card bg-info-subtle mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h5 class="card-title mb-2">Crear canal API</h5>
                <p class="card-text text-muted mb-0">
                    Configura un nuevo canal API para integraciones personalizadas
                </p>
            </div>
            <a href="{{ route('settings.chat.channels.api.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8 offset-lg-2">
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('settings.chat.channels.api.store') }}">
                    @csrf

                    <div class="mb-3">
                        <label for="inbox_name" class="control-label col-form-label">
                            Nombre del inbox <span class="text-danger">*</span>
                        </label>
                        <input type="text"
                               class="form-control @error('inbox_name') is-invalid @enderror"
                               id="inbox_name"
                               name="inbox_name"
                               value="{{ old('inbox_name') }}"
                               required>
                        <small class="form-text text-muted">Este nombre aparecerá en la lista de conversaciones</small>
                        @error('inbox_name')
                            <span class="field-validation-error">
                                <i class="fas fa-circle-exclamation"></i> {{ $message }}
                            </span>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="identifier" class="control-label col-form-label">
                            Identificador del canal <span class="text-danger">*</span>
                        </label>
                        <input type="text"
                               class="form-control @error('identifier') is-invalid @enderror"
                               id="identifier"
                               name="identifier"
                               value="{{ old('identifier') }}"
                               placeholder="mi-canal-api"
                               required>
                        <small class="form-text text-muted">Identificador único para llamadas API (alfanumérico, guiones, guiones bajos)</small>
                        @error('identifier')
                            <span class="field-validation-error">
                                <i class="fas fa-circle-exclamation"></i> {{ $message }}
                            </span>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="webhook_url" class="control-label col-form-label">
                            Webhook URL
                        </label>
                        <input type="url"
                               class="form-control @error('webhook_url') is-invalid @enderror"
                               id="webhook_url"
                               name="webhook_url"
                               value="{{ old('webhook_url') }}"
                               placeholder="https://tu-api.com/webhook">
                        <small class="form-text text-muted">Opcional: URL para enviar mensajes salientes</small>
                        @error('webhook_url')
                            <span class="field-validation-error">
                                <i class="fas fa-circle-exclamation"></i> {{ $message }}
                            </span>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="additional_settings" class="control-label col-form-label">
                            Configuración adicional (JSON)
                        </label>
                        <textarea class="form-control @error('additional_settings') is-invalid @enderror"
                                  id="additional_settings"
                                  name="additional_settings"
                                  rows="4"
                                  placeholder='{"custom_header": "value", "timeout": 30}'>{{ old('additional_settings') }}</textarea>
                        <small class="form-text text-muted">Opcional: Cabeceras personalizadas, autenticación u otra configuración en formato JSON</small>
                        @error('additional_settings')
                            <span class="field-validation-error">
                                <i class="fas fa-circle-exclamation"></i> {{ $message }}
                            </span>
                        @enderror
                    </div>

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> <strong>Nota:</strong> El token HMAC y el token de verificación del webhook se generarán automáticamente por seguridad.
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-save"></i> Crear canal API
                        </button>
                        <a href="{{ route('settings.chat.channels.api.index') }}" class="btn btn-secondary w-100">
                            <i class="fas fa-times"></i> Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
