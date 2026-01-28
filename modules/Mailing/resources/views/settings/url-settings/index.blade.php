@extends('layouts.theme')

@section('content')

    @include('core::components.card', ['title' => 'Configuración de URLs'])

    @if ($message = session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fa fa-check-circle me-2"></i> {{ $message }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if ($message = session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fa fa-circle-exclamation me-2"></i> {{ $message }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <form method="POST" action="{{ route('settings.mailing.settings.urls.store') }}" id="formUrlSettings">
        @csrf

        {{-- Application URLs Card --}}
        <div class="card mb-3">
            <div class="card-header p-4 border-bottom border-light">
                <h6 class="mb-0 fw-bold">URLs de la aplicación</h6>
            </div>

            <div class="card-body">
                <div class="alert alert-info alert-sm py-2 px-3 mb-3" role="alert">
                    <i class="fa fa-info-circle me-2"></i> Estas URLs son utilizadas para redireccionar y rastrear actividades en tus campañas de email.
                </div>

                <div class="row">
                    {{-- App URL --}}
                    <div class="col-12">
                        <div class="mb-3">
                            <label for="app_url" class="form-label">
                                URL base de la aplicación <span class="text-danger">*</span>
                            </label>
                            <input type="url"
                                   class="form-control @error('app_url') is-invalid @enderror"
                                   id="app_url"
                                   name="app_url"
                                   value="{{ old('app_url', $settings['app_url'] ?? '') }}"
                                   placeholder="https://ejemplo.com"
                                   required>
                            @error('app_url')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                            <small class="text-muted">URL raíz de tu aplicación (sin barra diagonal al final)</small>
                        </div>
                    </div>

                    {{-- Webhook URL --}}
                    <div class="col-12">
                        <div class="mb-3">
                            <label for="webhook_url" class="form-label">
                                URL de webhooks <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input type="url"
                                       class="form-control @error('webhook_url') is-invalid @enderror"
                                       id="webhook_url"
                                       name="webhook_url"
                                       value="{{ old('webhook_url', $settings['webhook_url'] ?? '') }}"
                                       placeholder="https://ejemplo.com/api/webhooks"
                                       required>
                                <button class="btn btn-outline-secondary" type="button" id="testWebhookBtn">
                                    <i class="fa fa-flask me-2"></i>Probar
                                </button>
                            </div>
                            @error('webhook_url')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                            <small class="text-muted">Endpoint donde Mailrelay enviará notificaciones de eventos</small>
                        </div>
                    </div>

                    {{-- Tracking URL --}}
                    <div class="col-12">
                        <div class="mb-3">
                            <label for="tracking_url" class="form-label">
                                URL de seguimiento <span class="text-danger">*</span>
                            </label>
                            <input type="url"
                                   class="form-control @error('tracking_url') is-invalid @enderror"
                                   id="tracking_url"
                                   name="tracking_url"
                                   value="{{ old('tracking_url', $settings['tracking_url'] ?? '') }}"
                                   placeholder="https://ejemplo.com/track"
                                   required>
                            @error('tracking_url')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                            <small class="text-muted">URL para rastrear aperturas y clics en emails</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Redirect URLs Card --}}
        <div class="card mb-3">
            <div class="card-header p-4 border-bottom border-light">
                <h6 class="mb-0 fw-bold">URLs de redirección</h6>
            </div>

            <div class="card-body">
                <div class="alert alert-info alert-sm py-2 px-3 mb-3" role="alert">
                    <i class="fa fa-info-circle me-2"></i> URLs a las que redireccionar después de ciertas acciones.
                </div>

                <div class="row">
                    {{-- Unsubscribe Success URL --}}
                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label for="unsubscribe_success_url" class="form-label">
                                URL de desuscripción exitosa
                            </label>
                            <input type="url"
                                   class="form-control @error('unsubscribe_success_url') is-invalid @enderror"
                                   id="unsubscribe_success_url"
                                   name="unsubscribe_success_url"
                                   value="{{ old('unsubscribe_success_url', $settings['unsubscribe_success_url'] ?? '') }}"
                                   placeholder="https://ejemplo.com/unsub-success">
                            @error('unsubscribe_success_url')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                            <small class="text-muted">Página a mostrar después de desuscribirse</small>
                        </div>
                    </div>

                    {{-- Preferences Center URL --}}
                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label for="preferences_center_url" class="form-label">
                                URL del centro de preferencias
                            </label>
                            <input type="url"
                                   class="form-control @error('preferences_center_url') is-invalid @enderror"
                                   id="preferences_center_url"
                                   name="preferences_center_url"
                                   value="{{ old('preferences_center_url', $settings['preferences_center_url'] ?? '') }}"
                                   placeholder="https://ejemplo.com/preferences">
                            @error('preferences_center_url')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                            <small class="text-muted">Página donde los suscriptores pueden gestionar sus preferencias</small>
                        </div>
                    </div>

                    {{-- Confirm Subscription URL --}}
                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label for="confirm_subscription_url" class="form-label">
                                URL de confirmación de suscripción
                            </label>
                            <input type="url"
                                   class="form-control @error('confirm_subscription_url') is-invalid @enderror"
                                   id="confirm_subscription_url"
                                   name="confirm_subscription_url"
                                   value="{{ old('confirm_subscription_url', $settings['confirm_subscription_url'] ?? '') }}"
                                   placeholder="https://ejemplo.com/confirm">
                            @error('confirm_subscription_url')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                            <small class="text-muted">Página mostrada después de confirmar suscripción</small>
                        </div>
                    </div>

                    {{-- Landing Page URL --}}
                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label for="landing_page_url" class="form-label">
                                URL de página de aterrizaje
                            </label>
                            <input type="url"
                                   class="form-control @error('landing_page_url') is-invalid @enderror"
                                   id="landing_page_url"
                                   name="landing_page_url"
                                   value="{{ old('landing_page_url', $settings['landing_page_url'] ?? '') }}"
                                   placeholder="https://ejemplo.com/landing">
                            @error('landing_page_url')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                            <small class="text-muted">URL predeterminada para formularios de suscripción</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Advanced URL Settings Card --}}
        <div class="card mb-3">
            <div class="card-header p-4 border-bottom border-light">
                <h6 class="mb-0 fw-bold">Configuración avanzada de URLs</h6>
            </div>

            <div class="card-body">
                <div class="row">
                    {{-- Use HTTPS --}}
                    <div class="col-12 col-md-6">
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input"
                                   type="checkbox"
                                   id="force_https"
                                   name="force_https"
                                   value="1"
                                   {{ old('force_https', $settings['force_https'] ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="force_https">
                                Forzar HTTPS en todas las URLs
                            </label>
                        </div>
                        <small class="text-muted d-block ms-4 mb-3">Reemplazar http:// con https:// automáticamente</small>
                    </div>

                    {{-- Include Tracking ID --}}
                    <div class="col-12 col-md-6">
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input"
                                   type="checkbox"
                                   id="include_tracking_id"
                                   name="include_tracking_id"
                                   value="1"
                                   {{ old('include_tracking_id', $settings['include_tracking_id'] ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="include_tracking_id">
                                Incluir ID de seguimiento en URLs
                            </label>
                        </div>
                        <small class="text-muted d-block ms-4 mb-3">Añadir identificadores únicos a los enlaces rastreados</small>
                    </div>

                    {{-- URL Shortener --}}
                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label for="url_shortener_service" class="form-label">
                                Servicio de acortador de URLs
                            </label>
                            <select class="form-select @error('url_shortener_service') is-invalid @enderror"
                                    id="url_shortener_service"
                                    name="url_shortener_service">
                                <option value="">Deshabilitado</option>
                                <option value="bitly" {{ old('url_shortener_service', $settings['url_shortener_service'] ?? '') === 'bitly' ? 'selected' : '' }}>Bitly</option>
                                <option value="tinyurl" {{ old('url_shortener_service', $settings['url_shortener_service'] ?? '') === 'tinyurl' ? 'selected' : '' }}>TinyURL</option>
                                <option value="custom" {{ old('url_shortener_service', $settings['url_shortener_service'] ?? '') === 'custom' ? 'selected' : '' }}>Personalizado</option>
                            </select>
                            @error('url_shortener_service')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                            <small class="text-muted">Acortar URLs automáticamente en campañas</small>
                        </div>
                    </div>

                    {{-- Max URL Length --}}
                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label for="max_url_length" class="form-label">
                                Longitud máxima de URL
                            </label>
                            <input type="number"
                                   class="form-control @error('max_url_length') is-invalid @enderror"
                                   id="max_url_length"
                                   name="max_url_length"
                                   value="{{ old('max_url_length', $settings['max_url_length'] ?? 2048) }}"
                                   placeholder="2048"
                                   min="100"
                                   max="8000">
                            @error('max_url_length')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                            <small class="text-muted">Límite de caracteres para URLs en emails</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Action Buttons --}}
        <div class="card">
            <div class="card-body d-flex gap-2 justify-content-end">
                <a href="{{ route('settings.mailing.index') }}" class="btn btn-outline-secondary">
                    <i class="fa fa-times me-2"></i>Cancelar
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-save me-2"></i>Guardar configuración
                </button>
            </div>
        </div>

    </form>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const testWebhookBtn = document.getElementById('testWebhookBtn');

    if (testWebhookBtn) {
        testWebhookBtn.addEventListener('click', function() {
            const webhookUrl = document.getElementById('webhook_url').value;

            if (!webhookUrl) {
                alert('Por favor, ingresa una URL de webhook primero.');
                return;
            }

            this.disabled = true;
            const originalText = this.innerHTML;
            this.innerHTML = '<i class="fa fa-spinner fa-spin me-2"></i>Probando...';

            // Test webhook connection
            fetch(webhookUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    test: true,
                    message: 'Prueba de conexión'
                })
            })
            .then(response => {
                if (response.ok) {
                    alert('Conexión exitosa! El webhook respondió correctamente.');
                } else {
                    alert('El webhook respondió con estado: ' + response.status);
                }
            })
            .catch(error => {
                alert('Error al probar la conexión: ' + error.message);
            })
            .finally(() => {
                this.disabled = false;
                this.innerHTML = originalText;
            });
        });
    }
});
</script>
@endpush
