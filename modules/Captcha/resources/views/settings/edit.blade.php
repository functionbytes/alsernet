@extends('layouts.theme')

@section('title', 'Captcha')

@section('content')

    @include('core::components.card', ['title' => 'Captcha'])

    @include('core::components.alerts')

    <div class="widget-content searchable-container list">

        <div class="row g-4 align-items-start">

            {{-- Formulario (izquierda) --}}
            <div class="col-lg-8">
                <form action="{{ route('captcha.settings.update') }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="card">

                        {{-- Estado del servicio --}}
                        <div class="card-body">
                            <h6 class="fw-bold text-dark mb-1">Estado del servicio</h6>
                            <p class="text-muted mb-3">Habilita o deshabilita la protección reCAPTCHA de Google en los formularios del sitio.</p>

                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" id="enable_captcha"
                                       name="enable_captcha" value="1"
                                       {{ $enable_captcha ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="enable_captcha">
                                    Habilitar reCAPTCHA
                                </label>
                            </div>
                            <small class="text-muted d-block mb-3">Protege contra spam y abuso automatizado en formularios de contacto, login y registro</small>

                            <div class="alert alert-info border-0 mb-0">
                                <small>
                                    <strong>Información:</strong> Necesitas una cuenta de Google reCAPTCHA configurada.
                                    <a href="https://www.google.com/recaptcha/admin/create" target="_blank" class="fw-semibold">Obtener claves →</a>
                                </small>
                            </div>
                        </div>

                        <div id="recaptcha-settings" class="{{ $enable_captcha ? '' : 'd-none' }}">

                            <hr class="my-0">

                            {{-- Tipo de verificación --}}
                            <div class="card-body">
                                <h6 class="fw-bold text-dark mb-1">Tipo de verificación</h6>
                                <p class="text-muted mb-3">Selecciona la versión de reCAPTCHA que se aplicará en los formularios protegidos.</p>

                                <div class="alert alert-warning border-0 mb-3 py-2">
                                    <small>
                                        <i class="fas fa-exclamation-triangle me-1"></i>
                                        Una configuración incorrecta podría interrumpir el inicio de sesión y el registro de usuarios.
                                    </small>
                                </div>

                                <label for="captcha_type" class="form-label fw-semibold">Versión</label>
                                <select class="form-select select2" id="captcha_type" name="captcha_type">
                                    <option value="v2" {{ $captcha_type === 'v2' ? 'selected' : '' }}>V2 — Desafío "No soy un robot"</option>
                                    <option value="v3" {{ $captcha_type === 'v3' ? 'selected' : '' }}>V3 — Invisible, score de riesgo</option>
                                </select>
                                <small class="text-muted d-block mt-1">V2 muestra un widget visible al usuario. V3 funciona en segundo plano sin interrumpir la experiencia.</small>

                                <div class="mt-3" id="v3-score-setting" style="display: {{ $captcha_type === 'v3' ? 'block' : 'none' }}">
                                    <label for="captcha_score" class="form-label fw-semibold">Umbral de puntuación</label>
                                    <input type="number" step="0.1" min="0" max="1"
                                           class="form-control @error('captcha_score') is-invalid @enderror"
                                           id="captcha_score" name="captcha_score"
                                           value="{{ $captcha_score }}">
                                    <small class="text-muted d-block mt-1">Solicitudes con score inferior a este valor se consideran sospechosas. Recomendado: 0.5</small>
                                    @error('captcha_score')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <hr class="my-0">

                            {{-- Credenciales --}}
                            <div class="card-body">
                                <h6 class="fw-bold text-dark mb-1">Credenciales</h6>
                                <p class="text-muted mb-3">Ingresa las claves proporcionadas por Google reCAPTCHA Admin Console para tu dominio.</p>

                                <div class="row g-3">
                                    <div class="col-12">
                                        <label for="captcha_site_key" class="form-label fw-semibold">
                                            Clave del sitio (Site Key) <span class="text-danger">*</span>
                                        </label>
                                        <input type="text"
                                               class="form-control @error('captcha_site_key') is-invalid @enderror"
                                               id="captcha_site_key" name="captcha_site_key"
                                               value="{{ $captcha_site_key }}"
                                               placeholder="6LcXXXXXXXXXXXXXXXXXXXXXXXXX">
                                        @error('captcha_site_key')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <small class="text-muted d-block mt-1">Clave pública que se incluye en el frontend. La encuentras en Google reCAPTCHA Admin → Configuración.</small>
                                    </div>
                                    <div class="col-12">
                                        <label for="captcha_secret" class="form-label fw-semibold">
                                            Clave secreta (Secret Key) <span class="text-danger">*</span>
                                        </label>
                                        <input type="password"
                                               class="form-control @error('captcha_secret') is-invalid @enderror"
                                               id="captcha_secret" name="captcha_secret"
                                               value="{{ $captcha_secret }}"
                                               placeholder="6LcXXXXXXXXXXXXXXXXXXXXXXXXX"
                                               autocomplete="off">
                                        @error('captcha_secret')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <small class="text-muted d-block mt-1">Clave privada para validar las respuestas en el servidor. Se almacena encriptada.</small>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary w-100">
                                Guardar configuración
                            </button>
                        </div>

                    </div>

                </form>
            </div>

            {{-- Panel informativo (derecha) --}}
            <div class="col-lg-4">

                <div class="card mb-3">
                    <div class="card-header border-bottom">
                        <h6 class="mb-0 fw-bold">Cómo obtener claves</h6>
                    </div>
                    <div class="card-body">
                        <ol class="text-muted ps-3 mb-0">
                            <li class="mb-2">Ve a <a href="https://www.google.com/recaptcha/admin/create" target="_blank" class="fw-semibold">Google reCAPTCHA Admin</a></li>
                            <li class="mb-2">Registra tu dominio (<code>{{ request()->getHost() }}</code>)</li>
                            <li class="mb-2">Selecciona el tipo <strong>v2</strong> o <strong>v3</strong></li>
                            <li>Copia la <em>Site Key</em> y <em>Secret Key</em> generadas</li>
                        </ol>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-header border-bottom">
                        <h6 class="mb-0 fw-bold">v2 vs v3</h6>
                    </div>
                    <div class="card-body">
                        <h6 class="fw-semibold mb-2">v2 — Checkbox</h6>
                        <p class="text-muted mb-3">Muestra el widget "No soy un robot". Requiere interacción del usuario. Ideal para formularios de contacto.</p>

                        <hr class="my-3">

                        <h6 class="fw-semibold mb-2">v3 — Invisible</h6>
                        <p class="text-muted mb-0">Asigna un score de riesgo (0.0–1.0) sin interrumpir. Ideal para login y registro donde la fricción debe ser mínima.</p>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header border-bottom">
                        <h6 class="mb-0 fw-bold">Seguridad</h6>
                    </div>
                    <div class="card-body">
                        <ul class="text-muted mb-0">
                            <li class="mb-2">La <em>Secret Key</em> se almacena encriptada en el servidor.</li>
                            <li class="mb-2">Nunca expongas la <em>Secret Key</em> en el frontend.</li>
                            <li>Regenera tus claves desde Google si sospechas que fueron comprometidas.</li>
                        </ul>
                    </div>
                </div>

            </div>

        </div>

    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    $('.select2').select2({ width: '100%' });

    $('#enable_captcha').on('change', function () {
        $('#recaptcha-settings').toggleClass('d-none', !$(this).is(':checked'));
    });

    $('#captcha_type').on('change', function () {
        $('#v3-score-setting').toggle($(this).val() === 'v3');
    });
});
</script>
@endpush
