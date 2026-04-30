@extends('helpdesk::settings.layout')

@php
    $title = 'Configuración de IA';
    $description = 'Configura el proveedor de inteligencia artificial y sus parámetros';
    $icon = 'fa-robot';
    $breadcrumb = 'IA';
@endphp

@section('backups-content')
<form method="POST" action="{{ route('settings.helpdesk.ai.update') }}">
    @csrf
    @method('PUT')

    <div class="form-section">
        <h5><i class="fas fa-brain"></i> Proveedor LLM</h5>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="llm_provider">Proveedor</label>
                <select class="form-select" id="llm_provider" name="llm_provider">
                    @foreach($providers ?? [] as $value => $label)
                        <option value="{{ $value }}" {{ ($backups['llm_provider'] ?? 'openai') === $value ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div class="form-section">
        <h5><i class="fas fa-key"></i> Claves de API</h5>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="openai_api_key">OpenAI API Key</label>
                <input type="password" class="form-control" id="openai_api_key" name="openai_api_key"
                    value="{{ old('openai_api_key', $backups['openai_api_key'] ?? '') }}"
                    placeholder="sk-...">
                <input type="text" class="form-control mt-1" id="openai_model" name="openai_model"
                    value="{{ old('openai_model', $backups['openai_model'] ?? 'gpt-4o') }}"
                    placeholder="Modelo (ej: gpt-4o)">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="anthropic_api_key">Anthropic API Key</label>
                <input type="password" class="form-control" id="anthropic_api_key" name="anthropic_api_key"
                    value="{{ old('anthropic_api_key', $backups['anthropic_api_key'] ?? '') }}"
                    placeholder="sk-ant-...">
                <input type="text" class="form-control mt-1" id="anthropic_model" name="anthropic_model"
                    value="{{ old('anthropic_model', $backups['anthropic_model'] ?? '') }}"
                    placeholder="Modelo (ej: claude-opus-4-5)">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="gemini_api_key">Google Gemini API Key</label>
                <input type="password" class="form-control" id="gemini_api_key" name="gemini_api_key"
                    value="{{ old('gemini_api_key', $backups['gemini_api_key'] ?? '') }}"
                    placeholder="AIza...">
                <input type="text" class="form-control mt-1" id="gemini_model" name="gemini_model"
                    value="{{ old('gemini_model', $backups['gemini_model'] ?? 'gemini-2.0-flash') }}"
                    placeholder="Modelo (ej: gemini-2.0-flash)">
            </div>
        </div>
    </div>

    <div class="form-section">
        <h5><i class="fas fa-sliders-h"></i> Parámetros del modelo</h5>
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label" for="temperature">Temperature</label>
                <input type="number" class="form-control" id="temperature" name="temperature"
                    value="{{ old('temperature', $backups['temperature'] ?? 0.7) }}" min="0" max="2" step="0.1" required>
                <span class="form-help">0 = determinístico, 2 = muy creativo</span>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="max_tokens">Máx. tokens</label>
                <input type="number" class="form-control" id="max_tokens" name="max_tokens"
                    value="{{ old('max_tokens', $backups['max_tokens'] ?? 2000) }}" min="100" max="128000" required>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="top_p">Top P</label>
                <input type="number" class="form-control" id="top_p" name="top_p"
                    value="{{ old('top_p', $backups['top_p'] ?? 1.0) }}" min="0" max="1" step="0.05" required>
            </div>
        </div>
    </div>

    <div class="form-section">
        <h5><i class="fas fa-database"></i> Embeddings y RAG</h5>
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label" for="embeddings_provider">Proveedor de embeddings</label>
                <select class="form-select" id="embeddings_provider" name="embeddings_provider">
                    <option value="openai" {{ ($backups['embeddings_provider'] ?? 'openai') === 'openai' ? 'selected' : '' }}>OpenAI</option>
                    <option value="gemini" {{ ($backups['embeddings_provider'] ?? '') === 'gemini' ? 'selected' : '' }}>Google Gemini</option>
                </select>
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="enable_embeddings" name="enable_embeddings" value="1"
                        {{ ($backups['enable_embeddings'] ?? true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="enable_embeddings">Habilitar embeddings</label>
                </div>
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="enable_rag" name="enable_rag" value="1"
                        {{ ($backups['enable_rag'] ?? false) ? 'checked' : '' }}>
                    <label class="form-check-label" for="enable_rag">Habilitar RAG</label>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end">
        <button type="submit" class="btn btn-primary btn-save">
            <i class="fas fa-save me-2"></i> Guardar configuración
        </button>
    </div>
</form>
@endsection
