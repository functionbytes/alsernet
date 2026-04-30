@extends('layouts.theme')

@section('page_title', 'Configuracion del newsletter')

@section('page_header')
    @include('core::components.card', ['title' => 'Configuracion del newsletter'])
@endsection

@section('content')

    @include('core::components.alerts')

    <div class="widget-content searchable-container list">

        <div class="row g-4 align-items-start">

            {{-- Columna izquierda --}}
            <div class="col-lg-8">
                <form method="POST" action="{{ route('settings.newsletter.update') }}" id="newsletterForm">
                    @csrf
                    @method('PATCH')

                    <div class="card">

                        {{-- Notificaciones --}}
                        <div class="card-body">
                            <h6 class="fw-bold text-dark mb-1">Notificaciones</h6>
                            <p class="text-muted mb-3">Recibe alertas por email cuando alguien se suscribe al boletín.</p>

                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="email_notifications"
                                       id="email_notifications" value="1"
                                       {{ old('email_notifications', $settings['email_notifications'] ?? '') === '1' ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="email_notifications">
                                    Habilitar notificaciones por email
                                </label>
                            </div>
                            <small class="text-muted d-block mb-3">Se enviará un email al administrador cuando alguien se suscriba.</small>

                            <div id="email-notifications-fields" class="{{ old('email_notifications', $settings['email_notifications'] ?? '') === '1' ? '' : 'd-none' }}">

                                {{-- Confirmación de suscripción --}}
                                <div class="mb-4">
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" name="email_subscription_enabled"
                                               id="email_subscription_enabled" value="1"
                                               {{ old('email_subscription_enabled', $settings['email_subscription_enabled'] ?? '') === '1' ? 'checked' : '' }}>
                                        <label class="form-check-label fw-semibold" for="email_subscription_enabled">
                                            <strong>Habilitar</strong> notificación de suscripción
                                        </label>
                                    </div>
                                    <p class="text-muted small mb-2">Se envía automáticamente cuando alguien completa su suscripción al boletín.</p>
                                    <div id="subscription-template-field" class="{{ old('email_subscription_enabled', $settings['email_subscription_enabled'] ?? '') === '1' ? '' : 'd-none' }}">
                                        <label for="email_subscription_template_id" class="form-label fw-semibold">Plantilla de email (opcional)</label>
                                        <select class="form-select select2-template" id="email_subscription_template_id"
                                                name="email_subscription_template_id"
                                                data-placeholder="Selecciona un template o deja vacío para usar el predefinido">
                                            <option value=""></option>
                                            @foreach($availableTemplates as $tpl)
                                                <option value="{{ $tpl['id'] }}"
                                                    {{ (string)($settings['email_subscription_template_id'] ?? '') === (string)$tpl['id'] ? 'selected' : '' }}>
                                                    {{ $tpl['text'] }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                {{-- Confirmación de baja --}}
                                <div class="mb-0">
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" name="email_unsubscription_enabled"
                                               id="email_unsubscription_enabled" value="1"
                                               {{ old('email_unsubscription_enabled', $settings['email_unsubscription_enabled'] ?? '') === '1' ? 'checked' : '' }}>
                                        <label class="form-check-label fw-semibold" for="email_unsubscription_enabled">
                                            <strong>Habilitar</strong> notificación de baja
                                        </label>
                                    </div>
                                    <p class="text-muted small mb-2">Se envía cuando el suscriptor cancela su suscripción al boletín.</p>
                                    <div id="unsubscription-template-field" class="{{ old('email_unsubscription_enabled', $settings['email_unsubscription_enabled'] ?? '') === '1' ? '' : 'd-none' }}">
                                        <label for="email_unsubscription_template_id" class="form-label fw-semibold">Plantilla de email (opcional)</label>
                                        <select class="form-select select2-template" id="email_unsubscription_template_id"
                                                name="email_unsubscription_template_id"
                                                data-placeholder="Selecciona un template o deja vacío para usar el predefinido">
                                            <option value=""></option>
                                            @foreach($availableTemplates as $tpl)
                                                <option value="{{ $tpl['id'] }}"
                                                    {{ (string)($settings['email_unsubscription_template_id'] ?? '') === (string)$tpl['id'] ? 'selected' : '' }}>
                                                    {{ $tpl['text'] }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                            </div>
                        </div>

                        <hr class="my-0">

                        {{-- reCAPTCHA --}}
                        <div class="card-body">
                            <h6 class="fw-bold text-dark mb-1">Verificacion de seguridad</h6>
                            <p class="text-muted mb-3">Protege los formularios de newsletter con Google reCAPTCHA.</p>

                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" name="recaptcha_enabled"
                                       id="recaptcha_enabled" value="1"
                                       {{ old('recaptcha_enabled', $settings['recaptcha_enabled'] ?? '') === '1' ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="recaptcha_enabled">
                                    Habilitar reCAPTCHA en formularios del newsletter
                                </label>
                            </div>
                            <small class="text-muted d-block mt-1">
                                Requiere configurar las claves de reCAPTCHA en
                                <a href="{{ route('settings.captcha.edit') }}">Configuración › Captcha</a>.
                            </small>
                        </div>

                        <hr class="my-0">

                        {{-- Popup del boletin --}}
                        <div class="card-body">
                            <h6 class="fw-bold text-dark mb-1">Popup del boletín</h6>
                            <p class="text-muted mb-3">Muestra un popup en el sitio invitando a los visitantes a suscribirse.</p>

                            <div class="form-check form-switch mb-2" id="popupToggleWrapper">
                                <input class="form-check-input" type="checkbox" name="popup_enabled"
                                       id="popup_enabled" value="1"
                                       {{ old('popup_enabled', $settings['popup_enabled'] ?? '') === '1' ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="popup_enabled">
                                    Habilitar popup del boletín
                                </label>
                            </div>

                            <div id="popup-fields" class="{{ old('popup_enabled', $settings['popup_enabled'] ?? '') === '1' ? '' : 'd-none' }}">

                                <div class="alert alert-info py-2 mb-3">
                                    <small>
                                        El título, subtítulo y descripción del popup se gestionan como traducciones.
                                        <a href="{{ route('locales.translations.edit', ['locale' => app()->getLocale(), 'group' => 'theme']) }}">Editar textos del popup</a>
                                        (claves: <code>newsletter_popup_title</code>, <code>newsletter_popup_subtitle</code>, <code>newsletter_popup_description</code>).
                                    </small>
                                </div>

                                <div class="mb-0">
                                    <label for="popup_delay" class="form-label fw-semibold">Retraso del popup (segundos)</label>
                                    <input type="number" class="form-control @error('popup_delay') is-invalid @enderror"
                                           id="popup_delay" name="popup_delay" min="0" max="60"
                                           value="{{ old('popup_delay', $settings['popup_delay'] ?? '0') }}">
                                    <small class="text-muted d-block mt-1">0 = se muestra de inmediato</small>
                                    @error('popup_delay')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                            </div>
                        </div>

                        <hr class="my-0">

                        {{-- Integracion Mailjet --}}
                        <div class="card-body">
                            <h6 class="fw-bold text-dark mb-1">Integración Mailjet</h6>
                            <p class="text-muted mb-3">Sincroniza los suscriptores con una lista de Mailjet para gestionar envíos de email.</p>

                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="mailjet_enabled"
                                       id="mailjet_enabled" value="1"
                                       {{ old('mailjet_enabled', $settings['mailjet_enabled'] ?? '') === '1' ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="mailjet_enabled">
                                    Habilitar integración con Mailjet
                                </label>
                            </div>

                            <div id="mailjet-fields" class="{{ old('mailjet_enabled', $settings['mailjet_enabled'] ?? '') === '1' ? '' : 'd-none' }}">

                                <div class="mb-3">
                                    <label for="mailjet_api_key" class="form-label fw-semibold">Clave API de Mailjet</label>
                                    <input type="text" class="form-control @error('mailjet_api_key') is-invalid @enderror"
                                           id="mailjet_api_key" name="mailjet_api_key"
                                           value="{{ old('mailjet_api_key', $settings['mailjet_api_key'] ?? '') }}">
                                    @error('mailjet_api_key')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="mailjet_api_secret" class="form-label fw-semibold">API Secret de Mailjet</label>
                                    <input type="password" class="form-control @error('mailjet_api_secret') is-invalid @enderror"
                                           id="mailjet_api_secret" name="mailjet_api_secret"
                                           value="{{ old('mailjet_api_secret', $settings['mailjet_api_secret'] ?? '') }}">
                                    @error('mailjet_api_secret')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="mailjet_list_id" class="form-label fw-semibold">ID de lista de Mailjet</label>
                                    <input type="text" class="form-control @error('mailjet_list_id') is-invalid @enderror"
                                           id="mailjet_list_id" name="mailjet_list_id"
                                           value="{{ old('mailjet_list_id', $settings['mailjet_list_id'] ?? '') }}">
                                    @error('mailjet_list_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="alert alert-info border-0 py-2 mb-0">
                                    <small>Las credenciales se almacenan en la base de datos. Usa variables de entorno en producción.</small>
                                </div>

                            </div>
                        </div>

                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary w-100">Guardar configuración</button>
                        </div>

                    </div>
                </form>
            </div>

            {{-- Columna derecha --}}
            <div class="col-lg-4">

                <div class="card mb-3">
                    <div class="card-body">
                        <h6 class="fw-bold mb-1">Suscriptores</h6>
                        <p class="text-muted mb-3">Gestiona la lista de personas suscritas al boletín.</p>
                        <a href="{{ route('newsletter.index') }}" class="btn btn-primary w-100">
                            Ver suscriptores
                        </a>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-body">
                        <h6 class="fw-bold mb-1">Plantillas de email</h6>
                        <p class="text-muted mb-3">Edita el contenido de los emails transaccionales del newsletter.</p>
                        <a href="{{ route('mailers.templates.index', ['module' => 'newsletter']) }}" class="btn btn-outline-secondary w-100">
                            Gestionar plantillas
                        </a>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header border-bottom">
                        <h6 class="mb-0 fw-bold">Cómo configurar Mailjet</h6>
                    </div>
                    <div class="card-body">

                        <h6 class="fw-semibold mb-2">Clave API y Secret</h6>
                        <ol class="text-muted ps-3 mb-3">
                            <li class="mb-1">Inicia sesión en <strong>Mailjet</strong></li>
                            <li class="mb-1">Ve a <strong>Account Settings → API Keys</strong></li>
                            <li>Copia la <strong>API Key</strong> y el <strong>Secret Key</strong></li>
                        </ol>

                        <hr class="my-3">

                        <h6 class="fw-semibold mb-2">ID de lista de contactos</h6>
                        <ol class="text-muted ps-3 mb-0">
                            <li class="mb-1">Ve a <strong>Contacts → Contact Lists</strong></li>
                            <li class="mb-1">Crea o selecciona una lista existente</li>
                            <li>Copia el <strong>ID numérico</strong> de la lista</li>
                        </ol>

                    </div>
                </div>

            </div>

        </div>

    </div>

@endsection

@push('css')
<link href="{{ asset('core/select2/css/select2.min.css') }}" rel="stylesheet" />
@endpush

@push('scripts')
<script src="{{ asset('core/select2/js/select2.min.js') }}"></script>
<script>
(function () {
    const popupToggle = document.getElementById('popup_enabled');
    const popupFields = document.getElementById('popup-fields');
    const mailjetToggle = document.getElementById('mailjet_enabled');
    const mailjetFields = document.getElementById('mailjet-fields');
    const emailNotifToggle = document.getElementById('email_notifications');
    const emailNotifFields = document.getElementById('email-notifications-fields');

    popupToggle?.addEventListener('change', function () {
        popupFields.classList.toggle('d-none', !this.checked);
    });

    mailjetToggle?.addEventListener('change', function () {
        mailjetFields.classList.toggle('d-none', !this.checked);
    });

    emailNotifToggle?.addEventListener('change', function () {
        emailNotifFields.classList.toggle('d-none', !this.checked);
    });

    document.getElementById('email_subscription_enabled')?.addEventListener('change', function () {
        document.getElementById('subscription-template-field').classList.toggle('d-none', !this.checked);
    });

    document.getElementById('email_unsubscription_enabled')?.addEventListener('change', function () {
        document.getElementById('unsubscription-template-field').classList.toggle('d-none', !this.checked);
    });

    // Select2 para los selectores de plantillas
    $('.select2-template').select2({
        allowClear: true,
        width: '100%',
        placeholder: function () {
            return $(this).data('placeholder') || 'Selecciona un template...';
        },
        dropdownParent: $('body'),
    });

    @if(session('success'))
        toastr.success('{{ session('success') }}', 'Éxito');
    @endif
    @if(session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif
})();
</script>
@endpush
