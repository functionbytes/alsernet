@extends('layouts.theme')

@section('content')
@include('core::components.alerts')

<div class="card bg-info-subtle mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h5 class="card-title mb-2">Editar canal API</h5>
                <p class="card-text text-muted mb-0">
                    Modifica la configuración del canal API
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
                <form method="POST" action="{{ route('settings.chat.channels.api.update', $api) }}">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label for="inbox_name" class="control-label col-form-label">
                            Nombre del inbox <span class="text-danger">*</span>
                        </label>
                        <input type="text"
                               class="form-control @error('inbox_name') is-invalid @enderror"
                               id="inbox_name"
                               name="inbox_name"
                               value="{{ old('inbox_name', $api->inbox->name ?? '') }}"
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
                               value="{{ old('identifier', $api->identifier) }}"
                               required>
                        <small class="form-text text-muted">Identificador único para llamadas API</small>
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
                               value="{{ old('webhook_url', $api->webhook_url) }}"
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
                                  placeholder='{"custom_header": "value", "timeout": 30}'>{{ old('additional_settings', $api->additional_settings) }}</textarea>
                        <small class="form-text text-muted">Opcional: Cabeceras personalizadas, autenticación u otra configuración en formato JSON</small>
                        @error('additional_settings')
                            <span class="field-validation-error">
                                <i class="fas fa-circle-exclamation"></i> {{ $message }}
                            </span>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input type="checkbox"
                                   class="form-check-input"
                                   id="active"
                                   name="active"
                                   value="1"
                                   {{ old('active', $api->active) ? 'checked' : '' }}>
                            <label class="form-check-label" for="active">
                                Canal activo (recibirá mensajes)
                            </label>
                        </div>
                    </div>

                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> <strong>Nota:</strong> El token HMAC no se puede cambiar aquí. Usa el botón "Regenerar" en la vista de detalles del canal.
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-save"></i> Actualizar canal API
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
