@extends('layouts.theme')

@section('title', 'Configuración Social')

@section('content')
    <div class="row">
        <div class="col-12">
            <h1 class="h3 mb-4">Configuración Social</h1>

            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('settings.helpdesk.social.update') }}">
                        @csrf
                        @method('PUT')

                        <h5 class="mb-3">Auto-respuesta</h5>
                        <div class="mb-3 form-check">
                            <input type="hidden" name="auto_reply_enabled" value="0">
                            <input type="checkbox" class="form-check-input" id="auto_reply_enabled" name="auto_reply_enabled" value="1" {{ ($config['auto_reply']['enabled'] ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="auto_reply_enabled">Activar auto-respuesta</label>
                        </div>

                        <h5 class="mb-3 mt-4">Clasificación de intenciones</h5>
                        <div class="mb-3 form-check">
                            <input type="hidden" name="intent_classification_enabled" value="0">
                            <input type="checkbox" class="form-check-input" id="intent_classification_enabled" name="intent_classification_enabled" value="1" {{ ($config['intent_classification']['enabled'] ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="intent_classification_enabled">Activar clasificación de intenciones</label>
                        </div>

                        <div class="mb-3">
                            <label for="intent_provider" class="form-label">Proveedor de clasificación</label>
                            <select class="form-select" id="intent_provider" name="intent_provider">
                                <option value="rules" {{ ($config['intent_classification']['provider'] ?? '') === 'rules' ? 'selected' : '' }}>Reglas</option>
                                <option value="openai" {{ ($config['intent_classification']['provider'] ?? '') === 'openai' ? 'selected' : '' }}>OpenAI</option>
                                <option value="hybrid" {{ ($config['intent_classification']['provider'] ?? '') === 'hybrid' ? 'selected' : '' }}>Híbrido</option>
                            </select>
                        </div>

                        <h5 class="mb-3 mt-4">Comentarios</h5>
                        <div class="mb-3 form-check">
                            <input type="hidden" name="comments_enabled" value="0">
                            <input type="checkbox" class="form-check-input" id="comments_enabled" name="comments_enabled" value="1" {{ ($config['comments']['enabled'] ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="comments_enabled">Activar sincronización de comentarios</label>
                        </div>

                        <h5 class="mb-3 mt-4">Integraciones</h5>
                        <div class="mb-3 form-check">
                            <input type="hidden" name="meta_enabled" value="0">
                            <input type="checkbox" class="form-check-input" id="meta_enabled" name="meta_enabled" value="1" {{ ($config['integrations']['meta']['enabled'] ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="meta_enabled">Meta (Facebook + Instagram)</label>
                        </div>

                        <div class="mb-3 form-check">
                            <input type="hidden" name="whatsapp_enabled" value="0">
                            <input type="checkbox" class="form-check-input" id="whatsapp_enabled" name="whatsapp_enabled" value="1" {{ ($config['integrations']['whatsapp']['enabled'] ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="whatsapp_enabled">WhatsApp</label>
                        </div>

                        <button type="submit" class="btn btn-primary">Guardar configuración</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
