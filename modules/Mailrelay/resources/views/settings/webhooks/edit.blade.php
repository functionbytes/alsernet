@extends('layouts.theme')

@section('title', 'Editar Webhook')

@section('content')
<div>
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Editar Webhook</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('settings.mailrelay.webhooks.update', $webhook->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="name" class="form-label">Nombre del Webhook</label>
                        <input type="text"
                               class="form-control @error('name') is-invalid @enderror"
                               id="name"
                               name="name"
                               value="{{ old('name', $webhook->name) }}"
                               required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="url" class="form-label">URL del Webhook</label>
                        <input type="url"
                               class="form-control @error('url') is-invalid @enderror"
                               id="url"
                               name="url"
                               value="{{ old('url', $webhook->url) }}"
                               required>
                        @error('url')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="event_type" class="form-label">Tipo de Evento</label>
                        <select class="form-select @error('event_type') is-invalid @enderror select2"
                                id="event_type"
                                name="event_type"
                                required>
                            @foreach(\Modules\Mailrelay\Models\MailrelayWebhook::EVENT_TYPES as $key => $label)
                                <option value="{{ $key }}" {{ old('event_type', $webhook->event_type) == $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('event_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="secret" class="form-label">Secret (Opcional)</label>
                        <input type="text"
                               class="form-control @error('secret') is-invalid @enderror"
                               id="secret"
                               name="secret"
                               value="{{ old('secret', $webhook->secret) }}"
                               placeholder="Clave secreta para firma HMAC">
                        <small class="text-muted">Dejar vacío para mantener el actual</small>
                        @error('secret')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="form-check">
                            <input class="form-check-input"
                                   type="checkbox"
                                   id="active"
                                   name="active"
                                   value="1"
                                   {{ old('active', $webhook->active) ? 'checked' : '' }}>
                            <label class="form-check-label" for="active">
                                Webhook activo
                            </label>
                        </div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <div class="form-check">
                            <input class="form-check-input"
                                   type="checkbox"
                                   id="verify_ssl"
                                   name="verify_ssl"
                                   value="1"
                                   {{ old('verify_ssl', $webhook->verify_ssl) ? 'checked' : '' }}>
                            <label class="form-check-label" for="verify_ssl">
                                Verificar certificado SSL
                            </label>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between mt-4">
                    <a href="{{ route('settings.mailrelay.webhooks.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
