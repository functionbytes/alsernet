@extends('layouts.theme')

@section('title', 'Email y notificaciones: ' . $form->name)

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">

            {{-- Header --}}
            <div class="d-flex align-items-center gap-2 mb-4">
                <a href="{{ route('settings.forms.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <div>
                    <h5 class="mb-0 fw-bold">{{ $form->name }}</h5>
                    <span class="text-muted">{{ $form->slug }}</span>
                </div>
            </div>

            @include('forms::settings.partials.tabs', ['active' => 'emails'])

            <div class="row">
                <div class="col-lg-8">
                    <form action="{{ route('settings.forms.settings.emails.update', $form) }}" method="POST" id="emailsForm">
                        @csrf
                        @method('PATCH')

                        {{-- Notificación al admin --}}
                        <div class="card mb-4">
                            <div class="card-body">
                                <h6 class="fw-bold mb-3 border-bottom pb-2">Notificación al administrador</h6>
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
                        </div>

                        {{-- Confirmación al cliente --}}
                        <div class="card mb-4">
                            <div class="card-body">
                                <h6 class="fw-bold mb-3 border-bottom pb-2">Confirmación al cliente</h6>
                                <div class="row g-3">

                                    <div class="col-12">
                                        <div class="form-check form-switch">
                                            <input type="hidden" name="send_confirmation" value="0">
                                            <input class="form-check-input" type="checkbox" id="send_confirmation"
                                                   name="send_confirmation" value="1"
                                                   {{ old('send_confirmation', $form->send_confirmation) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="send_confirmation">
                                                Enviar email de confirmación al cliente
                                            </label>
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
                        </div>

                        {{-- Anti-spam --}}
                        <div class="card mb-4">
                            <div class="card-body">
                                <h6 class="fw-bold mb-3 border-bottom pb-2">Anti-spam</h6>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="form-check form-switch">
                                            <input type="hidden" name="honeypot_enabled" value="0">
                                            <input class="form-check-input" type="checkbox" id="honeypot_enabled"
                                                   name="honeypot_enabled" value="1"
                                                   {{ old('honeypot_enabled', $form->honeypot_enabled) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="honeypot_enabled">
                                                Honeypot anti-spam
                                            </label>
                                        </div>
                                        <div class="form-text small">Campo oculto para detectar bots.</div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check form-switch">
                                            <input type="hidden" name="captcha_enabled" value="0">
                                            <input class="form-check-input" type="checkbox" id="captcha_enabled"
                                                   name="captcha_enabled" value="1"
                                                   {{ old('captcha_enabled', $form->captcha_enabled) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="captcha_enabled">
                                                CAPTCHA
                                            </label>
                                        </div>
                                        <div class="form-text small">Requiere resolución de CAPTCHA antes de enviar.</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Webhook --}}
                        <div class="card mb-4">
                            <div class="card-body">
                                <h6 class="fw-bold mb-3 border-bottom pb-2">Webhook</h6>
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
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Guardar cambios
                            </button>
                        </div>

                    </form>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $('#send_confirmation').on('change', function () {
        $('#confirmationFields').toggleClass('d-none', !this.checked);
    });
</script>
@endpush
