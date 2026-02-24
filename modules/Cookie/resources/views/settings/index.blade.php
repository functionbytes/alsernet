@extends('layouts.theme')

@section('title', 'Configuración de cookies')

@section('content')
    @include('core::components.card', ['title' => 'Configuración de cookies'])

    @include('core::components.alerts')

    <form method="POST" action="{{ route('settings.cookie.update') }}">
        @csrf

        <div class="card">
            {{-- Header --}}
            <div class="card-header p-4 border-bottom">
                <h5 class="mb-1 fw-bold">Configuración de cookies</h5>
                <p class="small mb-0 text-muted">Personaliza el banner de consentimiento de cookies que se muestra a los visitantes</p>
            </div>

            {{-- Sección general --}}
            <div class="card-body border-bottom p-4">
                <h6 class="fw-bold mb-3">General</h6>

                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="enabled" id="enabled"
                               value="1" {{ $get('enabled') === '1' ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="enabled">
                            Habilitar banner de consentimiento
                        </label>
                    </div>
                    <small class="text-muted d-block mt-1">
                        Cuando está habilitado, se muestra un banner de consentimiento de cookies a los visitantes.
                    </small>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="style" class="form-label">Estilo de presentación</label>
                        <select class="form-select select2" id="style" name="style" data-placeholder="Seleccionar estilo">
                            <option value="full-width" {{ $get('style') === 'full-width' ? 'selected' : '' }}>Full Width (ancho completo)</option>
                            <option value="minimal" {{ $get('style') === 'minimal' ? 'selected' : '' }}>Minimal (card flotante)</option>
                        </select>
                        <small class="text-muted d-block mt-1">Full Width: banner en toda la pantalla. Minimal: tarjeta flotante en esquina inferior izquierda.</small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="position" class="form-label">Posición del banner</label>
                        <select class="form-select select2" id="position" name="position" data-placeholder="Seleccionar posición">
                            <option value="bottom" {{ $get('position') === 'bottom' ? 'selected' : '' }}>Inferior</option>
                            <option value="top" {{ $get('position') === 'top' ? 'selected' : '' }}>Superior</option>
                            <option value="center" {{ $get('position') === 'center' ? 'selected' : '' }}>Centro</option>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="message" class="form-label">Mensaje del banner</label>
                    <textarea class="form-control" id="message" name="message" rows="3"
                              placeholder="Utilizamos cookies para mejorar tu experiencia...">{!! $get('message') !!}</textarea>
                    <small class="text-muted d-block mt-1">Texto que aparecerá en el banner de cookies.</small>
                </div>
            </div>

            {{-- Sección textos de botones --}}
            <div class="card-body border-bottom p-4">
                <h6 class="fw-bold mb-3">Textos de botones</h6>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="accept_btn_text" class="form-label">Botón aceptar</label>
                        <input type="text" class="form-control" id="accept_btn_text" name="accept_btn_text"
                               placeholder="Aceptar" value="{{ $get('accept_btn_text') }}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="reject_btn_text" class="form-label">Botón rechazar</label>
                        <input type="text" class="form-control" id="reject_btn_text" name="reject_btn_text"
                               placeholder="Rechazar" value="{{ $get('reject_btn_text') }}">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="customize_btn_text" class="form-label">Botón personalizar</label>
                        <input type="text" class="form-control" id="customize_btn_text" name="customize_btn_text"
                               placeholder="Personalizar preferencias" value="{{ $get('customize_btn_text') }}">
                        <small class="text-muted d-block mt-1">Para expandir opciones de categorías.</small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="save_btn_text" class="form-label">Botón guardar preferencias</label>
                        <input type="text" class="form-control" id="save_btn_text" name="save_btn_text"
                               placeholder="Guardar preferencias" value="{{ $get('save_btn_text') }}">
                        <small class="text-muted d-block mt-1">Botón dentro de la sección de categorías.</small>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-0">
                        <label for="more_info_text" class="form-label">Texto enlace "Más información"</label>
                        <input type="text" class="form-control" id="more_info_text" name="more_info_text"
                               placeholder="Más información" value="{{ $get('more_info_text') }}">
                    </div>
                    <div class="col-md-6 mb-0">
                        <label for="more_info_url" class="form-label">URL "Más información"</label>
                        <input type="url" class="form-control" id="more_info_url" name="more_info_url"
                               placeholder="https://..." value="{{ $get('more_info_url') }}">
                    </div>
                </div>
            </div>

            {{-- Sección integraciones de tracking --}}
            <div class="card-body border-bottom p-4">
                <h6 class="fw-bold mb-3">Integraciones de Tracking</h6>

                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="google_analytics_enabled" id="google_analytics_enabled"
                               value="1" {{ $get('google_analytics_enabled') === '1' ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="google_analytics_enabled">
                            Habilitar Google Analytics
                        </label>
                    </div>
                    <small class="text-muted d-block mt-1">
                        Permite rastrear el consentimiento de usuarios con Google Analytics.
                    </small>
                </div>

                <div class="mb-3">
                    <label for="google_analytics_id" class="form-label">Google Analytics Tracking ID</label>
                    <input type="text" class="form-control" id="google_analytics_id" name="google_analytics_id"
                           placeholder="G-XXXXXXXXXX" value="{{ $get('google_analytics_id') }}">
                    <small class="text-muted d-block mt-1">Ejemplo: G-12345ABCDE. Este es tu ID de propiedad de Google Analytics.</small>
                </div>

                <hr class="my-3">

                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="facebook_pixel_enabled" id="facebook_pixel_enabled"
                               value="1" {{ $get('facebook_pixel_enabled') === '1' ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="facebook_pixel_enabled">
                            Habilitar Facebook Pixel
                        </label>
                    </div>
                    <small class="text-muted d-block mt-1">
                        Permite rastrear el consentimiento de usuarios con Facebook Pixel.
                    </small>
                </div>

                <div class="mb-0">
                    <label for="facebook_pixel_id" class="form-label">Facebook Pixel ID</label>
                    <input type="text" class="form-control" id="facebook_pixel_id" name="facebook_pixel_id"
                           placeholder="123456789" value="{{ $get('facebook_pixel_id') }}">
                    <small class="text-muted d-block mt-1">Tu ID de pixel de Facebook (solo números).</small>
                </div>
            </div>

            {{-- Sección colores y tamaño --}}
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3">Apariencia</h6>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="bg_color" class="form-label">Color de fondo</label>
                        <div class="input-group">
                            <input type="color" class="form-control form-control-color" id="bg_color"
                                   name="bg_color" value="{{ $get('bg_color', '#ffffff') }}" style="max-width:46px;">
                            <input type="text" class="form-control color-text" placeholder="#ffffff"
                                   value="{{ $get('bg_color', '#ffffff') }}" readonly>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="text_color" class="form-label">Color de texto</label>
                        <div class="input-group">
                            <input type="color" class="form-control form-control-color" id="text_color"
                                   name="text_color" value="{{ $get('text_color', '#000000') }}" style="max-width:46px;">
                            <input type="text" class="form-control color-text" placeholder="#000000"
                                   value="{{ $get('text_color', '#000000') }}" readonly>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="btn_color" class="form-label">Color de botones</label>
                        <div class="input-group">
                            <input type="color" class="form-control form-control-color" id="btn_color"
                                   name="btn_color" value="{{ $get('btn_color', '#90bb13') }}" style="max-width:46px;">
                            <input type="text" class="form-control color-text" placeholder="#90bb13"
                                   value="{{ $get('btn_color', '#90bb13') }}" readonly>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <label for="max_width" class="form-label">Ancho máximo (px)</label>
                        <input type="text" class="form-control" id="max_width" name="max_width"
                               placeholder="600" value="{{ $get('max_width') }}">
                    </div>
                </div>
            </div>

            {{-- Footer --}}
            <div class="card-footer p-4 border-top d-flex justify-content-end gap-2">
                <a href="javascript:history.back()" class="btn btn-outline-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i> Guardar cambios
                </button>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
<script>
$(document).ready(function () {
    // Inicializar Select2
    $('#style, #position').select2({
        minimumResultsForSearch: Infinity,
        width: '100%'
    });

    // Sincronizar color picker con input de texto
    $('input[type="color"]').on('input change', function () {
        $(this).closest('.input-group').find('.color-text').val(this.value);
    });
});
</script>
@endpush
