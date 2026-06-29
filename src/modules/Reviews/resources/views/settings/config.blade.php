@extends('layouts.theme')

@section('title', 'Configuración de reseñas')

@section('content')
    @include('core::components.card', ['title' => 'Configuración de reseñas'])

    <div class="widget-content searchable-container list">

        <div class="row g-4 align-items-start">

            {{-- Columna izquierda: formulario --}}
            <div class="col-lg-8">
                <form action="{{ route('settings.reviews.config.update') }}" method="POST" id="reviews-config-form">
                    @csrf
                    @method('PUT')

                    <div class="card">
                        <div class="card-body">
                            @include('core::components.alerts')

                            {{-- Sincronización --}}
                            <h6 class="fw-bold text-dark mb-1">Sincronización</h6>
                            <p class="text-muted mb-3">Frecuencia con la que se importan reseñas desde Google My Business.</p>

                            <label for="sync_interval_minutes" class="form-label fw-semibold">Intervalo de sincronización</label>
                            <select class="form-select select2" id="sync_interval_minutes" name="sync_interval_minutes">
                                <option value="5"  {{ config('reviews.general.sync_interval_minutes', 15) == 5  ? 'selected' : '' }}>Cada 5 minutos</option>
                                <option value="10" {{ config('reviews.general.sync_interval_minutes', 15) == 10 ? 'selected' : '' }}>Cada 10 minutos</option>
                                <option value="15" {{ config('reviews.general.sync_interval_minutes', 15) == 15 ? 'selected' : '' }}>Cada 15 minutos</option>
                                <option value="30" {{ config('reviews.general.sync_interval_minutes', 15) == 30 ? 'selected' : '' }}>Cada 30 minutos</option>
                                <option value="60" {{ config('reviews.general.sync_interval_minutes', 15) == 60 ? 'selected' : '' }}>Cada 1 hora</option>
                            </select>
                            <small class="text-muted d-block mt-1 mb-0">Frecuencia de sincronización automática desde Google</small>

                        </div>

                        <hr class="my-0">

                        {{-- Respuestas automáticas --}}
                        <div class="card-body">
                            <h6 class="fw-bold text-dark mb-1">Respuestas automáticas</h6>
                            <p class="text-muted mb-3">Controla qué respuestas se publican automáticamente en Google.</p>

                            <label for="auto_publish_replies" class="form-label fw-semibold">Publicación automática</label>
                            <select class="form-select select2" id="auto_publish_replies" name="auto_publish_replies">
                                <option value="disabled" {{ config('reviews.general.auto_publish_replies', 'disabled') == 'disabled' ? 'selected' : '' }}>Desactivado (manual)</option>
                                <option value="positive" {{ config('reviews.general.auto_publish_replies', 'disabled') == 'positive' ? 'selected' : '' }}>Solo reseñas positivas (4-5 estrellas)</option>
                                <option value="negative" {{ config('reviews.general.auto_publish_replies', 'disabled') == 'negative' ? 'selected' : '' }}>Solo reseñas negativas (1-2 estrellas)</option>
                                <option value="all"      {{ config('reviews.general.auto_publish_replies', 'disabled') == 'all'      ? 'selected' : '' }}>Todas las reseñas</option>
                            </select>
                            <small class="text-muted d-block mt-1">Publicar automáticamente en Google cuando el sistema responda</small>
                        </div>

                        <hr class="my-0">

                        {{-- Visibilidad --}}
                        <div class="card-body">
                            <h6 class="fw-bold text-dark mb-1">Visibilidad por defecto</h6>
                            <p class="text-muted mb-3">Controla si las reseñas nuevas se muestran de inmediato o pasan por moderación.</p>

                            <label for="default_visibility" class="form-label fw-semibold">Modo de publicación</label>
                            <select class="form-select select2" id="default_visibility" name="default_visibility">
                                <option value="1" {{ config('reviews.general.default_moderation_visible', true) ? 'selected' : '' }}>Visible automáticamente</option>
                                <option value="0" {{ !config('reviews.general.default_moderation_visible', true) ? 'selected' : '' }}>Oculta (requiere moderación)</option>
                            </select>
                            <small class="text-muted d-block mt-1">Se aplica a reseñas importadas sin respuesta existente</small>
                        </div>

                        <hr class="my-0">

                        {{-- Google OAuth --}}
                        <div class="card-body">
                            <h6 class="fw-bold text-dark mb-1">Credenciales Google OAuth</h6>
                            <p class="text-muted mb-3">Necesarias para conectar y publicar respuestas en Google My Business.</p>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="google_client_id" class="form-label fw-semibold">Client ID</label>
                                    <input type="password" class="form-control" id="google_client_id"
                                           name="google_client_id"
                                           value="{{ config('reviews.google.client_id') ? '••••••••••••' : '' }}"
                                           placeholder="Tu Google Client ID"
                                           autocomplete="off">
                                    <small class="text-muted d-block mt-1">Desde Google Cloud Console</small>
                                </div>
                                <div class="col-md-6">
                                    <label for="google_client_secret" class="form-label fw-semibold">Client Secret</label>
                                    <input type="password" class="form-control" id="google_client_secret"
                                           name="google_client_secret"
                                           value="{{ config('reviews.google.client_secret') ? '••••••••••••' : '' }}"
                                           placeholder="Tu Google Client Secret"
                                           autocomplete="off">
                                    <small class="text-muted d-block mt-1">Se almacena encriptado</small>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold">URI de redirección OAuth</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control font-monospace bg-light"
                                               value="{{ route('settings.reviews.oauth.callback') }}"
                                               readonly id="oauth-redirect-uri">
                                        <button type="button" class="btn btn-outline-secondary" onclick="copyOauthUri()">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </div>
                                    <small class="text-muted d-block mt-1">Registra esta URL exactamente en Google Cloud Console → Credenciales → URI de redirección</small>
                                </div>
                            </div>
                        </div>

                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-save me-1"></i> Guardar ajustes
                            </button>
                        </div>

                    </div>
                </form>
            </div>

            {{-- Columna derecha: sidebar --}}
            <div class="col-lg-4">

                {{-- Conexiones activas --}}
                <div class="card mb-3">
                    <div class="card-body">
                        <h6 class="fw-bold mb-1">Conexiones Google</h6>
                        <p class="text-muted mb-3">Gestiona las cuentas Google vinculadas y las ubicaciones sincronizadas.</p>
                        <a href="{{ route('settings.reviews.connections.index') }}" class="btn btn-primary w-100 mb-2">
                            Gestionar conexiones
                        </a>
                        <a href="{{ route('settings.reviews.locations.index') }}" class="btn btn-outline-secondary w-100">
                            Ver ubicaciones
                        </a>
                    </div>
                </div>

                {{-- Ayuda --}}
                <div class="card">
                    <div class="card-header border-bottom">
                        <h6 class="mb-0 fw-bold">Cómo configurar Google OAuth</h6>
                    </div>
                    <div class="card-body">

                        <h6 class="fw-semibold mb-2">1. Crear proyecto y habilitar APIs</h6>
                        <ol class="text-muted ps-3 mb-3">
                            <li class="mb-1">Ve a <a href="https://console.cloud.google.com" target="_blank" class="fw-semibold">Google Cloud Console</a></li>
                            <li class="mb-1">Crea o selecciona un proyecto</li>
                            <li>Habilita <strong>Google My Business API</strong></li>
                        </ol>

                        <hr class="my-3">

                        <h6 class="fw-semibold mb-2">2. Crear credenciales OAuth</h6>
                        <ol class="text-muted ps-3 mb-3">
                            <li class="mb-1">API y servicios → Credenciales → Crear credenciales</li>
                            <li class="mb-1">Tipo: <strong>ID de cliente de OAuth 2.0</strong></li>
                            <li class="mb-1">Agrega la URI de redirección del formulario</li>
                            <li>Copia <strong>Client ID</strong> y <strong>Client Secret</strong></li>
                        </ol>

                        <hr class="my-3">

                        <h6 class="fw-semibold mb-2">Seguridad</h6>
                        <ul class="text-muted  mb-0">
                            <li class="mb-1">Las credenciales se guardan encriptadas</li>
                            <li class="mb-1">Nunca compartas el Client Secret</li>
                            <li>Revoca el acceso en Google si desconectas</li>
                        </ul>

                    </div>
                </div>

            </div>

        </div>
    </div>

@endsection

@push('css')
<link rel="stylesheet" href="{{ asset('core/select2/css/select2.min.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('core/select2/js/select2.min.js') }}"></script>
<script>
$(document).ready(function () {
    $('.select2').select2({ width: '100%' });
});

function copyOauthUri() {
    const input = document.getElementById('oauth-redirect-uri');
    navigator.clipboard.writeText(input.value).then(() => {
        toastr.success('URI copiada al portapapeles');
    });
}
</script>
@endpush
