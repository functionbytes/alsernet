@extends('layouts.theme')

@section('title', 'Configuración de cookies')

@section('content')
    @include('core::components.card', ['title' => 'Configuración de cookies'])

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

                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="enabled" id="enabled"
                                       value="1" {{ old('enabled', $get('enabled')) === '1' ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="enabled">
                                    Habilitar banner de consentimiento
                                </label>
                            </div>
                            <small class="text-muted d-block mb-0">
                                Cuando está habilitado, se muestra un banner de consentimiento de cookies a los visitantes.
                            </small>
                        </div>

                        <div id="cookie-enabled-fields" class="{{ old('enabled', $get('enabled')) === '1' ? '' : 'd-none' }}">

                            {{-- General --}}
                            <div class="card-body border-bottom p-4">
                                <h6 class="fw-bold mb-3">General</h6>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
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
                                    <div class="col-md-6 mb-3" id="position-field" {{ old('style', $get('style')) === 'minimal' ? 'style=display:none' : '' }}>
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

                                <div class="mb-0">
                                    <label for="message" class="form-label">Mensaje del banner</label>
                                    <textarea class="form-control @error('message') is-invalid @enderror"
                                              id="message" name="message" rows="3"
                                              placeholder="Utilizamos cookies para mejorar tu experiencia...">{{ old('message', $get('message')) }}</textarea>
                                    @error('message')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted d-block mt-1">Texto que aparecerá en el banner de cookies.</small>
                                </div>
                            </div>

                            {{-- Textos de botones --}}
                            <div class="card-body border-bottom p-4">
                                <h6 class="fw-bold mb-3">Textos de botones</h6>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="accept_btn_text" class="form-label">Botón aceptar</label>
                                        <input type="text" class="form-control @error('accept_btn_text') is-invalid @enderror"
                                               id="accept_btn_text" name="accept_btn_text"
                                               placeholder="Aceptar" value="{{ old('accept_btn_text', $get('accept_btn_text')) }}">
                                        @error('accept_btn_text')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="reject_btn_text" class="form-label">Botón rechazar</label>
                                        <input type="text" class="form-control @error('reject_btn_text') is-invalid @enderror"
                                               id="reject_btn_text" name="reject_btn_text"
                                               placeholder="Rechazar" value="{{ old('reject_btn_text', $get('reject_btn_text')) }}">
                                        @error('reject_btn_text')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="customize_btn_text" class="form-label">Botón personalizar</label>
                                        <input type="text" class="form-control @error('customize_btn_text') is-invalid @enderror"
                                               id="customize_btn_text" name="customize_btn_text"
                                               placeholder="Personalizar preferencias" value="{{ old('customize_btn_text', $get('customize_btn_text')) }}">
                                        @error('customize_btn_text')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <small class="text-muted d-block mt-1">Para expandir opciones de categorías.</small>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="save_btn_text" class="form-label">Botón guardar preferencias</label>
                                        <input type="text" class="form-control @error('save_btn_text') is-invalid @enderror"
                                               id="save_btn_text" name="save_btn_text"
                                               placeholder="Guardar preferencias" value="{{ old('save_btn_text', $get('save_btn_text')) }}">
                                        @error('save_btn_text')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <small class="text-muted d-block mt-1">Botón dentro de la sección de categorías.</small>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-0">
                                        <label for="more_info_text" class="form-label">Texto enlace "Más información"</label>
                                        <input type="text" class="form-control @error('more_info_text') is-invalid @enderror"
                                               id="more_info_text" name="more_info_text"
                                               placeholder="Más información" value="{{ old('more_info_text', $get('more_info_text')) }}">
                                        @error('more_info_text')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6 mb-0">
                                        <label for="more_info_url" class="form-label">URL "Más información"</label>
                                        <input type="url" class="form-control @error('more_info_url') is-invalid @enderror"
                                               id="more_info_url" name="more_info_url"
                                               placeholder="https://..." value="{{ old('more_info_url', $get('more_info_url')) }}">
                                        @error('more_info_url')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            {{-- Integraciones de tracking --}}
                            <div class="card-body border-bottom p-4">
                                <h6 class="fw-bold mb-3">Integraciones de tracking</h6>

                                {{-- Google Analytics --}}
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="google_analytics_enabled"
                                               id="google_analytics_enabled" value="1"
                                               {{ old('google_analytics_enabled', $get('google_analytics_enabled')) === '1' ? 'checked' : '' }}>
                                        <label class="form-check-label fw-semibold" for="google_analytics_enabled">
                                            Habilitar Google Analytics
                                        </label>
                                    </div>
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
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="facebook_pixel_enabled"
                                               id="facebook_pixel_enabled" value="1"
                                               {{ old('facebook_pixel_enabled', $get('facebook_pixel_enabled')) === '1' ? 'checked' : '' }}>
                                        <label class="form-check-label fw-semibold" for="facebook_pixel_enabled">
                                            Habilitar Facebook Pixel
                                        </label>
                                    </div>
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
                            </div>

                            {{-- Apariencia --}}
                            <div class="card-body border-bottom p-4">
                                <h6 class="fw-bold mb-3">Apariencia</h6>

                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="bg_color" class="form-label">Color de fondo</label>
                                        <div class="input-group">
                                            <input type="color" class="form-control form-control-color color-picker"
                                                   id="bg_color_picker"
                                                   value="{{ old('bg_color', $get('bg_color', '#ffffff')) }}"
                                                   style="max-width:46px;">
                                            <input type="text" class="form-control color-text @error('bg_color') is-invalid @enderror"
                                                   id="bg_color" name="bg_color"
                                                   placeholder="#ffffff" value="{{ old('bg_color', $get('bg_color', '#ffffff')) }}">
                                        </div>
                                        @error('bg_color')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="text_color" class="form-label">Color de texto</label>
                                        <div class="input-group">
                                            <input type="color" class="form-control form-control-color color-picker"
                                                   id="text_color_picker"
                                                   value="{{ old('text_color', $get('text_color', '#000000')) }}"
                                                   style="max-width:46px;">
                                            <input type="text" class="form-control color-text @error('text_color') is-invalid @enderror"
                                                   id="text_color" name="text_color"
                                                   placeholder="#000000" value="{{ old('text_color', $get('text_color', '#000000')) }}">
                                        </div>
                                        @error('text_color')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="btn_color" class="form-label">Color de botones</label>
                                        <div class="input-group">
                                            <input type="color" class="form-control form-control-color color-picker"
                                                   id="btn_color_picker"
                                                   value="{{ old('btn_color', $get('btn_color', '#90bb13')) }}"
                                                   style="max-width:46px;">
                                            <input type="text" class="form-control color-text @error('btn_color') is-invalid @enderror"
                                                   id="btn_color" name="btn_color"
                                                   placeholder="#90bb13" value="{{ old('btn_color', $get('btn_color', '#90bb13')) }}">
                                        </div>
                                        @error('btn_color')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-4">
                                        <label for="max_width" class="form-label">Ancho máximo (px)</label>
                                        <input type="number" class="form-control @error('max_width') is-invalid @enderror"
                                               id="max_width" name="max_width" min="200" max="1920"
                                               placeholder="600" value="{{ old('max_width', $get('max_width')) }}">
                                        @error('max_width')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <small class="text-muted d-block mt-1">Entre 200 y 1920 px.</small>
                                    </div>
                                </div>
                            </div>

                            {{-- Vista previa --}}
                            <div class="card-body p-4">
                                <h6 class="fw-bold mb-3">Vista previa del banner</h6>
                                <p class="small text-muted mb-3">La vista previa se actualiza automáticamente al cambiar los campos de apariencia y textos.</p>

                                <div id="cookie-preview-wrapper" style="background:#f5f5f5; border-radius:8px; padding:16px; min-height:100px; position:relative; overflow:hidden;">
                                    <div id="cookie-preview"
                                         style="border-radius:8px; padding:16px 20px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; box-shadow:0 2px 12px rgba(0,0,0,.12);">
                                        <p id="preview-message" style="margin:0; flex:1; font-size:14px;"></p>
                                        <div style="display:flex; gap:8px; flex-shrink:0; flex-wrap:wrap;">
                                            <button type="button" id="preview-reject"
                                                    style="background:transparent; border:1px solid currentColor; border-radius:4px; padding:6px 14px; font-size:13px; cursor:default;">
                                            </button>
                                            <button type="button" id="preview-accept"
                                                    style="border:none; border-radius:4px; padding:6px 14px; font-size:13px; color:#fff; cursor:default;">
                                            </button>
                                        </div>
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

    // Color picker → actualiza el texto (fuente de verdad) y el preview
    $('.color-picker').on('input change', function () {
        var textId = this.id.replace('_picker', '');
        $('#' + textId).val(this.value);
        updatePreview();
    });

    // Texto hex → sincroniza el color picker si el valor es hex válido
    $('.color-text').on('input', function () {
        var val = this.value.trim();
        if (/^#[0-9A-Fa-f]{6}$/.test(val)) {
            $('#' + this.id + '_picker').val(val);
        }
        updatePreview();
    });

    // Actualizar preview al cambiar textos y tamaño
    $('#message, #accept_btn_text, #reject_btn_text, #max_width').on('input', updatePreview);

    function updatePreview() {
        var bg       = $('#bg_color').val()    || '#ffffff';
        var text     = $('#text_color').val()  || '#000000';
        var btn      = $('#btn_color').val()   || '#90bb13';
        var msg      = $('#message').val()     || 'Utilizamos cookies para mejorar tu experiencia en este sitio.';
        var accept   = $('#accept_btn_text').val() || 'Aceptar';
        var reject   = $('#reject_btn_text').val() || 'Rechazar';
        var maxWidth = parseInt($('#max_width').val(), 10) || 1170;

        $('#cookie-preview').css({ 'background-color': bg, 'color': text });
        $('#cookie-preview .cookie-consent-body, #cookie-preview > div').css('max-width', maxWidth + 'px');
        $('#preview-message').text(msg);
        $('#preview-accept').css({ 'background-color': btn, 'border-color': btn }).text(accept);
        $('#preview-reject').css({ 'color': text, 'border-color': text }).text(reject);
    }

    // Inicializar preview
    updatePreview();
});
</script>
@endpush
