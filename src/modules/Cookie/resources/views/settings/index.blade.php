@extends('layouts.theme')

@section('title', 'Configuración de cookies')

@section('page_header')
    @include('core::components.card', ['title' => 'Configuración de cookies'])
@endsection

@section('content')
    @include('core::components.alerts')

    <div class="widget-content searchable-container list">

        <div class="row g-4 align-items-start">

            {{-- Columna izquierda: formulario --}}
            <div class="col-lg-8">
                <form method="POST" action="{{ route('settings.cookie.update') }}">
                    @csrf
                    @method('PATCH')

                    <div class="card">

                        {{-- Estado del servicio --}}
                        <div class="card-body border-bottom p-4">
                            <h6 class="fw-bold text-dark mb-1">Estado del servicio</h6>
                            <p class="text-muted mb-3">Habilita o deshabilita el banner de consentimiento de cookies en tu sitio web.</p>

                            <div class="mb-2"><label class="form-label" for="enabled">Habilitar banner de consentimiento</label><select class="form-select" name="enabled" id="enabled"><option value="1">Activado</option><option value="0">Desactivado</option></select></div>
                            <small class="text-muted d-block mb-0">
                                Cuando está habilitado, se muestra un banner de consentimiento de cookies a los visitantes.
                            </small>
                        </div>

                        <div id="cookie-enabled-fields" class="{{ old('enabled', $get('enabled')) === '1' ? '' : 'd-none' }}">

                            {{-- Página de política de cookies --}}
                            <div class="card-body border-bottom p-4">
                                <h6 class="fw-bold mb-1">Página de política de cookies</h6>
                                <p class="text-muted mb-3">Selecciona la página que se abrirá al hacer clic en "Más información" desde el banner.</p>

                                <div class="mb-2">
                                    <label for="policy_page_id" class="form-label">Página</label>
                                    <select class="form-select @error('policy_page_id') is-invalid @enderror" id="policy_page_id" name="policy_page_id">
                                        <option value="">— Usar URL por defecto ({{ config('Cookie.general.learn_more_url', '/cookie/policy') }}) —</option>
                                        @foreach($pages as $page)
                                            <option value="{{ $page->id }}" {{ (int) old('policy_page_id', $get('policy_page_id')) === $page->id ? 'selected' : '' }}>
                                                {{ $page->title }} — /{{ $page->slug }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('policy_page_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted d-block mt-1">
                                        Las páginas se gestionan desde <a href="{{ route('pages.index') }}" target="_blank">/panel/pages</a>. Solo se listan las publicadas.
                                    </small>
                                </div>
                            </div>

                            {{-- General --}}
                            <div class="card-body border-bottom p-4">
                                <h6 class="fw-bold mb-3">General</h6>

                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label for="style" class="form-label">Estilo de presentación</label>
                                        <select class="form-select @error('style') is-invalid @enderror select2" id="style" name="style">
                                            <option value="full-width" {{ old('style', $get('style')) === 'full-width' ? 'selected' : '' }}>Full Width (ancho completo)</option>
                                            <option value="minimal" {{ old('style', $get('style')) === 'minimal' ? 'selected' : '' }}>Minimal (card flotante)</option>
                                        </select>
                                        @error('style')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <small class="text-muted d-block mt-1">Full Width: banner en toda la pantalla. Minimal: tarjeta flotante en esquina inferior izquierda.</small>
                                    </div>
                                    <div class="col-md-6 mb-3 {{ old('style', $get('style')) === 'minimal' ? 'd-none' : '' }}" id="position-field">
                                        <label for="position" class="form-label">Posición del banner</label>
                                        <select class="form-select @error('position') is-invalid @enderror select2" id="position" name="position">
                                            <option value="bottom" {{ old('position', $get('position')) === 'bottom' ? 'selected' : '' }}>Inferior</option>
                                            <option value="top" {{ old('position', $get('position')) === 'top' ? 'selected' : '' }}>Superior</option>
                                            <option value="center" {{ old('position', $get('position')) === 'center' ? 'selected' : '' }}>Centro</option>
                                        </select>
                                        @error('position')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                            </div>

                            {{-- Textos del banner (multiidioma) --}}
                            <div class="card-body border-bottom p-4">
                                <h6 class="fw-bold mb-1">Textos del banner</h6>
                                <p class="text-muted mb-3">Los textos (mensaje, botones, categorías) se gestionan por idioma desde la sección de traducciones del sistema.</p>
                                <div class="alert alert-info d-flex align-items-start mb-2">
                                    <i class="fas fa-language me-3 mt-1"></i>
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold mb-1">Personalizar textos por idioma</div>
                                        <p class="mb-2 small">Edita el grupo <code>cookie::messages</code> en cada locale activo para personalizar los textos del banner, modal y categorías de cookies.</p>
                                        <a href="{{ route('locales.translations.index') }}?group=cookie%3A%3Amessages" class="btn btn-sm btn-primary">
                                            <i class="fas fa-arrow-up-right-from-square me-1"></i>Ir a Traducciones
                                        </a>
                                    </div>
                                </div>
                            </div>

                            {{-- Integraciones de tracking --}}
                            <div class="card-body border-bottom p-4">
                                <h6 class="fw-bold mb-3">Integraciones de tracking</h6>

                                {{-- Google Analytics --}}
                                <div class="mb-3">
                                    <div class="mb-2"><label class="form-label" for="google_analytics_enabled">Habilitar Google Analytics</label><select class="form-select" name="google_analytics_enabled" id="google_analytics_enabled"><option value="1">Activado</option><option value="0">Desactivado</option></select></div>
                                    <small class="text-muted d-block mt-1">
                                        Permite rastrear el consentimiento de usuarios con Google Analytics.
                                    </small>
                                </div>

                                <div id="ga-fields" class="mb-3 {{ old('google_analytics_enabled', $get('google_analytics_enabled')) === '1' ? '' : 'd-none' }}">
                                    <label for="google_analytics_id" class="form-label">Google Analytics Tracking ID</label>
                                    <input type="text" class="form-control @error('google_analytics_id') is-invalid @enderror"
                                           id="google_analytics_id" name="google_analytics_id"
                                           placeholder="G-XXXXXXXXXX" value="{{ old('google_analytics_id', $get('google_analytics_id')) }}">
                                    @error('google_analytics_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted d-block mt-1">Ejemplo: G-12345ABCDE. Este es tu ID de propiedad de Google Analytics.</small>
                                </div>

                                <hr class="my-3">

                                {{-- Facebook Pixel --}}
                                <div class="mb-3">
                                    <div class="mb-2"><label class="form-label" for="facebook_pixel_enabled">Habilitar Facebook Pixel</label><select class="form-select" name="facebook_pixel_enabled" id="facebook_pixel_enabled"><option value="1">Activado</option><option value="0">Desactivado</option></select></div>
                                    <small class="text-muted d-block mt-1">
                                        Permite rastrear el consentimiento de usuarios con Facebook Pixel.
                                    </small>
                                </div>

                                <div id="fb-fields" class="mb-0 {{ old('facebook_pixel_enabled', $get('facebook_pixel_enabled')) === '1' ? '' : 'd-none' }}">
                                    <label for="facebook_pixel_id" class="form-label">Facebook Pixel ID</label>
                                    <input type="text" class="form-control @error('facebook_pixel_id') is-invalid @enderror"
                                           id="facebook_pixel_id" name="facebook_pixel_id"
                                           placeholder="123456789" value="{{ old('facebook_pixel_id', $get('facebook_pixel_id')) }}">
                                    @error('facebook_pixel_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted d-block mt-1">Tu ID de pixel de Facebook (solo números).</small>
                                </div>

                                <hr class="my-3">

                                {{-- Google Tag Manager --}}
                                <div class="mb-3">
                                    <div class="mb-2"><label class="form-label" for="google_tag_manager_enabled">Habilitar Google Tag Manager</label><select class="form-select" name="google_tag_manager_enabled" id="google_tag_manager_enabled"><option value="1">Activado</option><option value="0">Desactivado</option></select></div>
                                    <small class="text-muted d-block mt-1">
                                        Carga GTM con soporte de consentimiento. Complementa GA4 con control centralizado de tags.
                                    </small>
                                </div>

                                <div id="gtm-fields" class="mb-3 {{ old('google_tag_manager_enabled', $get('google_tag_manager_enabled')) === '1' ? '' : 'd-none' }}">
                                    <label for="google_tag_manager_id" class="form-label">Google Tag Manager ID</label>
                                    <input type="text" class="form-control @error('google_tag_manager_id') is-invalid @enderror"
                                           id="google_tag_manager_id" name="google_tag_manager_id"
                                           placeholder="GTM-XXXXXXX" value="{{ old('google_tag_manager_id', $get('google_tag_manager_id')) }}">
                                    @error('google_tag_manager_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted d-block mt-1">Ejemplo: GTM-ABC1234. Encuéntralo en tu cuenta de Google Tag Manager.</small>
                                </div>

                                <hr class="my-3">

                                {{-- Microsoft UET --}}
                                <div class="mb-3">
                                    <div class="mb-2"><label class="form-label" for="microsoft_uet_enabled">Habilitar Microsoft/Bing UET</label><select class="form-select" name="microsoft_uet_enabled" id="microsoft_uet_enabled"><option value="1">Activado</option><option value="0">Desactivado</option></select></div>
                                    <small class="text-muted d-block mt-1">
                                        Universal Event Tracking para campañas de Microsoft Advertising (Bing Ads).
                                    </small>
                                </div>

                                <div id="ms-uet-fields" class="mb-3 {{ old('microsoft_uet_enabled', $get('microsoft_uet_enabled')) === '1' ? '' : 'd-none' }}">
                                    <label for="microsoft_uet_id" class="form-label">Microsoft UET Tag ID</label>
                                    <input type="text" class="form-control @error('microsoft_uet_id') is-invalid @enderror"
                                           id="microsoft_uet_id" name="microsoft_uet_id"
                                           placeholder="XXXXXXXXXXXXXXX" value="{{ old('microsoft_uet_id', $get('microsoft_uet_id')) }}">
                                    @error('microsoft_uet_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted d-block mt-1">ID numérico de tu etiqueta UET en Microsoft Advertising.</small>
                                </div>

                                <hr class="my-3">

                                {{-- LinkedIn Insight Tag --}}
                                <div class="mb-3">
                                    <div class="mb-2"><label class="form-label" for="linkedin_insight_enabled">Habilitar LinkedIn Insight Tag</label><select class="form-select" name="linkedin_insight_enabled" id="linkedin_insight_enabled"><option value="1">Activado</option><option value="0">Desactivado</option></select></div>
                                    <small class="text-muted d-block mt-1">
                                        Habilita conversiones y audiencias para campañas de LinkedIn Ads.
                                    </small>
                                </div>

                                <div id="linkedin-fields" class="mb-3 {{ old('linkedin_insight_enabled', $get('linkedin_insight_enabled')) === '1' ? '' : 'd-none' }}">
                                    <label for="linkedin_partner_id" class="form-label">LinkedIn Partner ID</label>
                                    <input type="text" class="form-control @error('linkedin_partner_id') is-invalid @enderror"
                                           id="linkedin_partner_id" name="linkedin_partner_id"
                                           placeholder="1234567" value="{{ old('linkedin_partner_id', $get('linkedin_partner_id')) }}">
                                    @error('linkedin_partner_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted d-block mt-1">ID numérico de tu cuenta de LinkedIn Campaign Manager.</small>
                                </div>

                                <hr class="my-3">

                                {{-- TikTok Pixel --}}
                                <div class="mb-3">
                                    <div class="mb-2"><label class="form-label" for="tiktok_pixel_enabled">Habilitar TikTok Pixel</label><select class="form-select" name="tiktok_pixel_enabled" id="tiktok_pixel_enabled"><option value="1">Activado</option><option value="0">Desactivado</option></select></div>
                                    <small class="text-muted d-block mt-1">
                                        Permite rastrear conversiones y audiencias para campañas de TikTok Ads.
                                    </small>
                                </div>

                                <div id="tiktok-fields" class="mb-3 {{ old('tiktok_pixel_enabled', $get('tiktok_pixel_enabled')) === '1' ? '' : 'd-none' }}">
                                    <label for="tiktok_pixel_id" class="form-label">TikTok Pixel ID</label>
                                    <input type="text" class="form-control @error('tiktok_pixel_id') is-invalid @enderror"
                                           id="tiktok_pixel_id" name="tiktok_pixel_id"
                                           placeholder="CXXXXXXXXXXXXXXXXXX" value="{{ old('tiktok_pixel_id', $get('tiktok_pixel_id')) }}">
                                    @error('tiktok_pixel_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted d-block mt-1">Encuéntralo en TikTok Ads Manager → Activos → Eventos.</small>
                                </div>

                                <hr class="my-3">

                                {{-- Twitter/X Pixel --}}
                                <div class="mb-3">
                                    <div class="mb-2"><label class="form-label" for="twitter_pixel_enabled">Habilitar Twitter/X Pixel</label><select class="form-select" name="twitter_pixel_enabled" id="twitter_pixel_enabled"><option value="1">Activado</option><option value="0">Desactivado</option></select></div>
                                    <small class="text-muted d-block mt-1">
                                        Permite rastrear conversiones y audiencias para campañas de Twitter/X Ads.
                                    </small>
                                </div>

                                <div id="twitter-fields" class="mb-0 {{ old('twitter_pixel_enabled', $get('twitter_pixel_enabled')) === '1' ? '' : 'd-none' }}">
                                    <label for="twitter_pixel_id" class="form-label">Twitter/X Pixel ID</label>
                                    <input type="text" class="form-control @error('twitter_pixel_id') is-invalid @enderror"
                                           id="twitter_pixel_id" name="twitter_pixel_id"
                                           placeholder="o1234" value="{{ old('twitter_pixel_id', $get('twitter_pixel_id')) }}">
                                    @error('twitter_pixel_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted d-block mt-1">Encuéntralo en Twitter/X Ads Manager → Herramientas → Pixel.</small>
                                </div>
                            </div>

                            {{-- Geo-targeting (GDPR) --}}
                            <div class="card-body border-bottom p-4">
                                <h6 class="fw-bold mb-1">Geo-targeting GDPR</h6>
                                <p class="text-muted mb-3">Muestra el banner solo a visitantes de la Unión Europea y países GDPR.</p>

                                <div class="mb-2"><label class="form-label" for="geo_targeting_enabled">Habilitar geo-targeting (solo UE/GDPR)</label><select class="form-select" name="geo_targeting_enabled" id="geo_targeting_enabled"><option value="1">Activado</option><option value="0">Desactivado</option></select></div>
                                <small class="text-muted d-block">Cuando está activo, el banner solo se muestra a visitantes de países con regulación GDPR. Los visitantes de otros países no verán el banner.</small>
                            </div>

                            {{-- Control de re-consentimiento --}}
                            <div class="card-body border-bottom p-4">
                                <h6 class="fw-bold mb-3">Control de re-consentimiento</h6>

                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="consent_version" class="form-label">Versión de política de cookies</label>
                                        <input type="text" class="form-control @error('consent_version') is-invalid @enderror"
                                               id="consent_version" name="consent_version"
                                               placeholder="1.0" value="{{ old('consent_version', $get('consent_version', '1.0')) }}">
                                        @error('consent_version')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <small class="text-muted d-block mt-1">Cambia esta versión para forzar que todos los visitantes vean el banner nuevamente.</small>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="log_retention_days" class="form-label">Retención de logs (días)</label>
                                        <input type="number" class="form-control @error('log_retention_days') is-invalid @enderror"
                                               id="log_retention_days" name="log_retention_days"
                                               min="7" max="3650" value="{{ old('log_retention_days', $get('log_retention_days', 90)) }}">
                                        @error('log_retention_days')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <small class="text-muted d-block mt-1">Los registros más antiguos se eliminan automáticamente.</small>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="alert_threshold" class="form-label">Umbral de alerta (%)</label>
                                        <input type="number" class="form-control @error('alert_threshold') is-invalid @enderror"
                                               id="alert_threshold" name="alert_threshold"
                                               min="0" max="100" value="{{ old('alert_threshold', $get('alert_threshold', 30)) }}">
                                        @error('alert_threshold')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <small class="text-muted d-block mt-1">Recibe una notificación si la tasa de aceptación cae por debajo de este porcentaje.</small>
                                    </div>
                                </div>
                            </div>

                        </div>{{-- /cookie-enabled-fields --}}

                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary w-100">
                                Guardar configuración
                            </button>
                        </div>

                    </div>
                </form>
            </div>

            {{-- Columna derecha: sidebar --}}
            <div class="col-lg-4">

                {{-- Registros --}}
                <div class="card mb-3">
                    <div class="card-body">
                        <h6 class="fw-bold mb-1">Registros de consentimiento</h6>
                        <p class="text-muted mb-3">Consulta el historial de aceptaciones y rechazos de cookies por parte de los visitantes.</p>
                        <a href="{{ route('settings.cookie.logs') }}" class="btn btn-outline-secondary w-100">
                            Ver registros
                        </a>
                    </div>
                </div>

                {{-- Ayuda --}}
                <div class="card">
                    <div class="card-header border-bottom">
                        <h6 class="mb-0 fw-bold">Cómo funciona el banner</h6>
                    </div>
                    <div class="card-body">

                        <h6 class="fw-semibold mb-2">Estilos disponibles</h6>
                        <ul class="text-muted ps-3 mb-3">
                            <li class="mb-1"><strong>Full Width:</strong> banner fijo en la parte superior o inferior de toda la pantalla</li>
                            <li><strong>Minimal:</strong> tarjeta flotante en la esquina inferior izquierda, menos intrusiva</li>
                        </ul>

                        <hr class="my-3">

                        <h6 class="fw-semibold mb-2">Integraciones de tracking</h6>
                        <ol class="text-muted ps-3 mb-3">
                            <li class="mb-1">Activa Google Analytics e ingresa tu <strong>Measurement ID</strong> (formato <code>G-XXXXXXX</code>)</li>
                            <li class="mb-1">Activa Facebook Pixel e ingresa tu <strong>Pixel ID</strong> (solo números)</li>
                            <li>Los scripts solo se cargan cuando el visitante acepta las cookies</li>
                        </ol>

                        <hr class="my-3">

                        <h6 class="fw-semibold mb-2">Buenas prácticas</h6>
                        <ul class="text-muted ps-3 mb-0">
                            <li class="mb-1">Enlaza tu política de privacidad en "Más información"</li>
                            <li class="mb-1">Usa colores con contraste suficiente para legibilidad</li>
                            <li>El banner se muestra solo a visitantes que aún no han dado su consentimiento</li>
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
    // Mostrar/ocultar todos los campos al habilitar/deshabilitar el banner
    $('#enabled').on('change', function () {
        $('#cookie-enabled-fields').toggleClass('d-none', !this.checked);
    });

    // Ocultar posición cuando el estilo es "minimal" (siempre va en esquina inferior)
    $('#style').on('change', function () {
        $('#position-field').toggle(this.value !== 'minimal');
    });

    // Toggle campos de tracking
    $('#google_analytics_enabled').on('change', function () {
        $('#ga-fields').toggleClass('d-none', !this.checked);
    });

    $('#facebook_pixel_enabled').on('change', function () {
        $('#fb-fields').toggleClass('d-none', !this.checked);
    });

    $('#google_tag_manager_enabled').on('change', function () {
        $('#gtm-fields').toggleClass('d-none', !this.checked);
    });

    $('#microsoft_uet_enabled').on('change', function () {
        $('#ms-uet-fields').toggleClass('d-none', !this.checked);
    });

    $('#linkedin_insight_enabled').on('change', function () {
        $('#linkedin-fields').toggleClass('d-none', !this.checked);
    });

    $('#tiktok_pixel_enabled').on('change', function () {
        $('#tiktok-fields').toggleClass('d-none', !this.checked);
    });

    $('#twitter_pixel_enabled').on('change', function () {
        $('#twitter-fields').toggleClass('d-none', !this.checked);
    });

});
</script>
@endpush
