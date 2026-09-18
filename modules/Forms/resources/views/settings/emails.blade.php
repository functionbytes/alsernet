@extends('layouts.theme')

@section('title', 'Email y notificaciones: ' . $form->name)

@section('content')

    @include('core::components.card', ['title' => 'Emails'])

    <div class="widget-content searchable-container list">

        <div class="card mb-4">
            <div class="card-header border-bottom">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <a href="{{ route('settings.forms.index') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                        <div>
                            <h5 class="mb-0 fw-bold">{{ $form->name }}</h5>
                            <small class="text-muted">{{ $form->slug }}</small>
                        </div>
                        @if ($form->is_active)
                            <span class="badge bg-light-success text-success">Activo</span>
                        @else
                            <span class="badge bg-light-danger text-danger">Inactivo</span>
                        @endif
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('settings.forms.preview', $form) }}" target="_blank" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-desktop me-1"></i> Preview
                        </a>
                        <a href="{{ route('settings.forms.submissions.index', $form) }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-inbox me-1"></i> Submissions
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 align-items-start">
            <div class="col-lg-9">
                <form action="{{ route('settings.forms.settings.emails.update', $form) }}" method="POST" id="emailsForm">
                    @csrf
                    @method('PATCH')

                    <div class="card">

                        <div class="card-header p-4 border-bottom">
                            <h5 class="mb-1 fw-bold">Emails y notificaciones</h5>
                            <p class="small mb-0 text-muted">Configura los destinatarios de notificaciones y el email de confirmación al cliente</p>
                        </div>

                        {{-- Notificación al admin --}}
                        <div class="card-body">
                            <h6 class="fw-bold mb-1">Notificación al administrador</h6>
                            <p class="text-muted mb-3">Configura a quién se notifica cuando llega una nueva respuesta.</p>

                            <div class="row g-3">
                                <div class="col-12">
                                    <label for="admin_notification_email" class="form-label">Emails de notificación</label>
                                    <input type="text" id="admin_notification_email" name="admin_notification_email"
                                           class="form-control @error('admin_notification_email') is-invalid @enderror"
                                           value="{{ old('admin_notification_email', $form->admin_notification_email) }}"
                                           placeholder="admin@empresa.com, otro@empresa.com">
                                    <div class="form-text">Separar múltiples emails con coma.</div>
                                    @error('admin_notification_email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12">
                                    <label for="admin_template_id" class="form-label">Plantilla para el administrador</label>
                                    <select id="admin_template_id" name="admin_template_id" class="form-select select2">
                                        <option value="">Sin plantilla (notificación estándar)</option>
                                        @foreach ($mailerTemplates as $template)
                                            <option value="{{ $template->id }}" {{ old('admin_template_id', $form->admin_template_id) == $template->id ? 'selected' : '' }}>
                                                {{ $template->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        {{-- Confirmación al cliente --}}
                        <div class="card-body">
                            <h6 class="fw-bold mb-1">Confirmación al cliente</h6>
                            <p class="text-muted mb-3">Envía un email automático de confirmación al usuario que completó el formulario.</p>

                            <div class="row g-3">
                                <div class="col-12">
                                    <div class="mb-2">
                                        <label class="form-label" for="send_confirmation">Email de confirmación al cliente</label>
                                        <select class="form-select" name="send_confirmation" id="send_confirmation">
                                            <option value="1" {{ old('send_confirmation', $form->send_confirmation) == 1 ? 'selected' : '' }}>Activado</option>
                                            <option value="0" {{ old('send_confirmation', $form->send_confirmation) == 0 ? 'selected' : '' }}>Desactivado</option>
                                        </select>
                                    </div>
                                </div>

                                <div id="confirmationFields" class="{{ old('send_confirmation', $form->send_confirmation) ? '' : 'd-none' }}">
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label for="confirmation_subject" class="form-label">Asunto del email</label>
                                            <input type="text" id="confirmation_subject" name="confirmation_subject"
                                                   class="form-control"
                                                   value="{{ old('confirmation_subject', $form->confirmation_subject) }}"
                                                   placeholder="Hemos recibido tu mensaje">
                                        </div>

                                        <div class="col-12">
                                            <label for="confirmation_message" class="form-label">Mensaje de confirmación</label>
                                            <textarea id="confirmation_message" name="confirmation_message"
                                                class="form-control" rows="4"
                                                placeholder="Escriba el mensaje de confirmación que recibirá el cliente...">{{ old('confirmation_message', $form->confirmation_message) }}</textarea>
                                            <div class="form-text">Se usa cuando no hay plantilla de Mailer seleccionada.</div>
                                        </div>

                                        <div class="col-12">
                                            <label for="confirmation_template_id" class="form-label">Plantilla de confirmación</label>
                                            <select id="confirmation_template_id" name="confirmation_template_id" class="form-select select2">
                                                <option value="">Sin plantilla (mensaje estándar)</option>
                                                @foreach ($mailerTemplates as $template)
                                                    <option value="{{ $template->id }}" {{ old('confirmation_template_id', $form->confirmation_template_id) == $template->id ? 'selected' : '' }}>
                                                        {{ $template->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="col-12">
                                            <label for="email_field_key" class="form-label">Campo email del formulario</label>
                                            <select id="email_field_key" name="email_field_key" class="form-select select2">
                                                <option value="">Seleccionar campo...</option>
                                                @foreach ($form->fields->where('type', 'email') as $field)
                                                    <option value="{{ $field->name }}" {{ old('email_field_key', $form->email_field_key) === $field->name ? 'selected' : '' }}>
                                                        {{ $field->label }} ({{ $field->name }})
                                                    </option>
                                                @endforeach
                                            </select>
                                            <div class="form-text">Campo del formulario donde el usuario ingresa su email.</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        {{-- Anti-spam --}}
                        <div class="card-body">
                            <h6 class="fw-bold mb-1">Anti-spam</h6>
                            <p class="text-muted mb-3">Protecciones para evitar envíos automatizados.</p>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="mb-2">
                                        <label class="form-label" for="honeypot_enabled">Honeypot anti-spam</label>
                                        <select class="form-select" name="honeypot_enabled" id="honeypot_enabled">
                                            <option value="1" {{ old('honeypot_enabled', $form->honeypot_enabled) == 1 ? 'selected' : '' }}>Activado</option>
                                            <option value="0" {{ old('honeypot_enabled', $form->honeypot_enabled) == 0 ? 'selected' : '' }}>Desactivado</option>
                                        </select>
                                    </div>
                                    <div class="form-text small">Campo oculto para detectar bots.</div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-2">
                                        <label class="form-label" for="captcha_enabled">CAPTCHA</label>
                                        <select class="form-select" name="captcha_enabled" id="captcha_enabled">
                                            <option value="1" {{ old('captcha_enabled', $form->captcha_enabled) == 1 ? 'selected' : '' }}>Activado</option>
                                            <option value="0" {{ old('captcha_enabled', $form->captcha_enabled) == 0 ? 'selected' : '' }}>Desactivado</option>
                                        </select>
                                    </div>
                                    <div class="form-text small">Requiere resolución de CAPTCHA antes de enviar.</div>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        {{-- Webhook --}}
                        <div class="card-body">
                            <h6 class="fw-bold mb-1">Webhook</h6>
                            <p class="text-muted mb-3">Recibe una notificación HTTP en tu servidor al recibir cada envío.</p>

                            <div class="row g-3">
                                <div class="col-12">
                                    <label for="webhook_url" class="form-label">URL del webhook</label>
                                    <input type="url" id="webhook_url" name="webhook_url" class="form-control"
                                           value="{{ old('webhook_url', $form->webhook_url) }}"
                                           placeholder="https://hooks.ejemplo.com/forms">
                                    <div class="form-text">Se llamará con un POST JSON al recibir cada envío.</div>
                                </div>
                                <div class="col-12">
                                    <label for="webhook_secret" class="form-label">Webhook secret</label>
                                    <input type="password" id="webhook_secret" name="webhook_secret" class="form-control"
                                           value="{{ old('webhook_secret', $form->webhook_secret) }}"
                                           placeholder="••••••••••••">
                                    <div class="form-text">Se incluye como cabecera <code>X-Webhook-Secret</code> para verificar la autenticidad.</div>
                                </div>
                            </div>
                        </div>

                        <div class="card-footer p-4">
                            <button type="submit" class="btn btn-primary w-100">
                                Guardar cambios
                            </button>
                        </div>

                    </div>
                </form>
            </div>

            <div class="col-lg-3">
                @include('forms::settings.partials.tabs', ['active' => 'emails'])

                <div class="card mt-3">
                    <div class="card-header border-bottom">
                        <h6 class="mb-0 fw-bold">Sobre las notificaciones</h6>
                    </div>
                    <div class="card-body">
                        <ul class="text-muted ps-3 mb-0 small">
                            <li class="mb-1">El email de administrador se envía a ti cuando alguien envía el formulario.</li>
                            <li class="mb-1">El email de confirmación se envía al usuario que envió el formulario.</li>
                            <li>Usa <code>{{ "{{" }} field_name }}</code> para insertar valores de campos en los mensajes.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

    </div>

@endsection

@push('scripts')
<script>
    $('#send_confirmation').on('change', function () {
        $('#confirmationFields').toggleClass('d-none', this.value !== '1');
    });
</script>
@endpush
