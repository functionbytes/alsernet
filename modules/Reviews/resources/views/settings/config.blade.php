@extends('layouts.theme')

@section('title', 'Configuración de reseñas')

@section('content')
    <div class="row">
        <!-- Formulario principal (izquierda) -->
        <div class="col-lg-8">
            <div class="card w-100">
                <form action="{{ route('settings.reviews.config.update') }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="card-body">
                        <h5 class="mb-2">Configuración de reseñas</h5>
                        <p class="card-subtitle mb-3">
                            Personaliza el comportamiento del módulo de reseñas, incluyendo sincronización con Google, publicación automática de respuestas y moderación de contenido.
                        </p>

                        <div class="row">
                            <!-- Intervalo de sincronización -->
                            <div class="col-12 col-md-12">
                                <div class="mb-3">
                                    <label for="sync_interval_minutes" class="control-label col-form-label">Intervalo de sincronización</label>
                                    <select class="form-select" id="sync_interval_minutes" name="sync_interval_minutes">
                                        <option value="5" {{ (config('reviews.general.sync_interval_minutes') ?? 15) == 5 ? 'selected' : '' }}>Cada 5 minutos</option>
                                        <option value="10" {{ (config('reviews.general.sync_interval_minutes') ?? 15) == 10 ? 'selected' : '' }}>Cada 10 minutos</option>
                                        <option value="15" {{ (config('reviews.general.sync_interval_minutes') ?? 15) == 15 ? 'selected' : '' }}>Cada 15 minutos</option>
                                        <option value="30" {{ (config('reviews.general.sync_interval_minutes') ?? 15) == 30 ? 'selected' : '' }}>Cada 30 minutos</option>
                                        <option value="60" {{ (config('reviews.general.sync_interval_minutes') ?? 15) == 60 ? 'selected' : '' }}>Cada 1 hora</option>
                                    </select>
                                    <small class="form-text text-muted">Frecuencia de sincronización desde Google</small>
                                </div>
                            </div>

                            <!-- Auto-publicar respuestas -->
                            <div class="col-12 col-md-12">
                                <div class="mb-3">
                                    <label for="auto_publish_replies" class="control-label col-form-label">Respuestas automáticas</label>
                                    <select class="form-select" id="auto_publish_replies" name="auto_publish_replies">
                                        <option value="disabled" {{ (config('reviews.general.auto_publish_replies') ?? 'disabled') == 'disabled' ? 'selected' : '' }}>Desactivado (manual)</option>
                                        <option value="positive" {{ (config('reviews.general.auto_publish_replies') ?? 'disabled') == 'positive' ? 'selected' : '' }}>Solo reseñas positivas (4-5 estrellas)</option>
                                        <option value="negative" {{ (config('reviews.general.auto_publish_replies') ?? 'disabled') == 'negative' ? 'selected' : '' }}>Solo reseñas negativas (1-2 estrellas)</option>
                                        <option value="all" {{ (config('reviews.general.auto_publish_replies') ?? 'disabled') == 'all' ? 'selected' : '' }}>Todas las reseñas</option>
                                    </select>
                                    <small class="form-text text-muted">Publicar automáticamente en Google</small>
                                </div>
                            </div>

                            <!-- Visibilidad por defecto -->
                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Visibilidad por defecto</label>
                                    <div class="form-check">
                                        <input class="form-check-input"
                                               type="radio"
                                               name="default_visibility"
                                               id="visibility_visible"
                                               value="1"
                                               {{ (config('reviews.general.default_moderation_visible') ?? true) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="visibility_visible">
                                            Visible automáticamente
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input"
                                               type="radio"
                                               name="default_visibility"
                                               id="visibility_hidden"
                                               value="0"
                                               {{ !(config('reviews.general.default_moderation_visible') ?? true) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="visibility_hidden">
                                            Oculta (requiere moderación)
                                        </label>
                                    </div>
                                    <small class="form-text text-muted">Controla la visibilidad de nuevas reseñas</small>
                                </div>
                            </div>

                            <!-- Google Client ID -->
                            <div class="col-12 col-md-12">
                                <div class="mb-3">
                                    <label for="google_client_id" class="control-label col-form-label">Google Client ID</label>
                                    <input type="password"
                                           class="form-control"
                                           id="google_client_id"
                                           name="google_client_id"
                                           value="{{ config('reviews.google.client_id') ? '••••••••••••' : '' }}"
                                           placeholder="Tu Client ID">
                                    <small class="form-text text-muted">Desde Google Cloud Console</small>
                                </div>
                            </div>

                            <!-- Google Client Secret -->
                            <div class="col-12 col-md-12">
                                <div class="mb-3">
                                    <label for="google_client_secret" class="control-label col-form-label">Google Client Secret</label>
                                    <input type="password"
                                           class="form-control"
                                           id="google_client_secret"
                                           name="google_client_secret"
                                           value="{{ config('reviews.google.client_secret') ? '••••••••••••' : '' }}"
                                           placeholder="Tu Client Secret">
                                    <small class="form-text text-muted">Se almacena encriptado en .env</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-info px-4 waves-effect waves-light mt-2 w-100">
                            Guardar cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Panel informativo (derecha) -->
        <div class="col-lg-4">
            <!-- Guía rápida -->
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-3">
                        Guía rápida
                    </h6>
                    <p class="card-text text-muted mb-0">
                        Configura tus credenciales de Google para sincronizar reseñas automáticamente y responder a los clientes directamente desde tu plataforma.
                    </p>
                </div>
                <hr>
                <div class="card-body">
                    <h6 class="card-title mb-3">
                        Obtener credenciales
                    </h6>
                    <div class=" text-muted">
                        <p class="mb-2"><strong>Paso 1:</strong> Ve a <a href="https://console.cloud.google.com" target="_blank" class="text-primary">Google Cloud Console</a></p>
                        <p class="mb-2"><strong>Paso 2:</strong> Crea o selecciona un proyecto</p>
                        <p class="mb-2"><strong>Paso 3:</strong> Habilita estas APIs:</p>
                        <ul class="ps-3 mb-2">
                            <li>Google My Business API</li>
                            <li>Google Contacts API (opcional)</li>
                        </ul>
                        <p class="mb-2"><strong>Paso 4:</strong> Crea credenciales OAuth 2.0</p>
                        <p class="mb-0"><strong>Paso 5:</strong> Copia Client ID y Secret aquí</p>
                    </div>
                </div>
                <hr>
                <div class="card-body">
                    <h6 class="card-title mb-3 text-info">
                        URI de redirección
                    </h6>
                    <p class=" text-muted mb-2">Usa esta URL en Google Cloud Console:</p>
                    <code class="d-block bg-light p-2 rounded small text-break">{{ route('settings.reviews.oauth.callback') }}</code>
                    <small class="form-text text-muted mt-2 d-block">
                        Cópiala exactamente como aparece aquí al configurar tus credenciales OAuth.
                    </small>
                </div>
            <hr>
                <div class="card-body">
                    <h6 class="card-title mb-3 text-warning">
                        eguridad
                    </h6>
                    <ul class="text-muted mb-0">
                        <li class="mb-2">Tus credenciales se almacenan encriptadas en el servidor</li>
                        <li class="mb-2">Nunca compartas Client Secret públicamente</li>
                        <li>Revoca credenciales si desconectas Google</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

@endsection
