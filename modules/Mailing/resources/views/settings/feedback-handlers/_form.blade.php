<form method="POST"
      action="{{ isset($handler) ? route('settings.mailing.feedback-handlers.update', $handler->uid) : route('settings.mailing.feedback-handlers.store') }}"
      id="formFeedbackHandler">
    @method(isset($handler) ? 'PATCH' : 'POST')
    @csrf

    {{-- General Information Card --}}
    <div class="card mb-3">
        <div class="card-header p-4 border-bottom border-light">
            <h6 class="mb-0 fw-bold">Información general</h6>
        </div>

        <div class="card-body">
            <div class="row">
                {{-- Name --}}
                <div class="col-12 col-md-6">
                    <div class="mb-3">
                        <label for="name" class="form-label">
                            Nombre <span class="text-danger">*</span>
                        </label>
                        <input type="text"
                               class="form-control @error('name') is-invalid @enderror"
                               id="name"
                               name="name"
                               value="{{ old('name', $handler->name ?? '') }}"
                               placeholder="Ej: Retroalimentación Gmail"
                               required>
                        @error('name')
                            <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                        @enderror
                    </div>
                </div>

                {{-- Type --}}
                <div class="col-12 col-md-6">
                    <div class="mb-3">
                        <label for="type" class="form-label">
                            Tipo <span class="text-danger">*</span>
                        </label>
                        <select class="form-select @error('type') is-invalid @enderror"
                                id="type"
                                name="type"
                                required>
                            <option value="">Selecciona un tipo</option>
                            <option value="imap" {{ old('type', $handler->type ?? '') === 'imap' ? 'selected' : '' }}>IMAP</option>
                            <option value="pop3" {{ old('type', $handler->type ?? '') === 'pop3' ? 'selected' : '' }}>POP3</option>
                            <option value="webhook" {{ old('type', $handler->type ?? '') === 'webhook' ? 'selected' : '' }}>Webhook</option>
                            <option value="api" {{ old('type', $handler->type ?? '') === 'api' ? 'selected' : '' }}>API</option>
                        </select>
                        @error('type')
                            <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                        @enderror
                    </div>
                </div>

                {{-- Status --}}
                <div class="col-12 col-md-6">
                    <div class="mb-3">
                        <label for="status" class="form-label">
                            Estado <span class="text-danger">*</span>
                        </label>
                        <select class="form-select @error('status') is-invalid @enderror"
                                id="status"
                                name="status"
                                required>
                            <option value="active" {{ old('status', $handler->status ?? 'inactive') === 'active' ? 'selected' : '' }}>Activo</option>
                            <option value="inactive" {{ old('status', $handler->status ?? 'inactive') === 'inactive' ? 'selected' : '' }}>Inactivo</option>
                            <option value="error" {{ old('status', $handler->status ?? '') === 'error' ? 'selected' : '' }}>Error</option>
                        </select>
                        @error('status')
                            <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                        @enderror
                    </div>
                </div>

                {{-- Provider --}}
                <div class="col-12 col-md-6">
                    <div class="mb-3">
                        <label for="provider" class="form-label">
                            Proveedor
                        </label>
                        <select class="form-select @error('provider') is-invalid @enderror"
                                id="provider"
                                name="provider">
                            <option value="">Personalizado</option>
                            <option value="gmail" {{ old('provider', $handler->provider ?? '') === 'gmail' ? 'selected' : '' }}>Gmail</option>
                            <option value="yahoo" {{ old('provider', $handler->provider ?? '') === 'yahoo' ? 'selected' : '' }}>Yahoo</option>
                            <option value="aol" {{ old('provider', $handler->provider ?? '') === 'aol' ? 'selected' : '' }}>AOL</option>
                            <option value="outlook" {{ old('provider', $handler->provider ?? '') === 'outlook' ? 'selected' : '' }}>Outlook</option>
                        </select>
                        @error('provider')
                            <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Email Connection Card (IMAP/POP3) --}}
    <div class="card mb-3" id="emailConnectionCard" style="display: none;">
        <div class="card-header p-4 border-bottom border-light">
            <h6 class="mb-0 fw-bold">Configuración de conexión de correo</h6>
        </div>

        <div class="card-body">
            <div class="row">
                {{-- Host --}}
                <div class="col-12 col-md-6">
                    <div class="mb-3">
                        <label for="host" class="form-label">
                            Host <span class="text-danger">*</span>
                        </label>
                        <input type="text"
                               class="form-control @error('host') is-invalid @enderror"
                               id="host"
                               name="host"
                               value="{{ old('host', $handler->host ?? '') }}"
                               placeholder="imap.gmail.com">
                        @error('host')
                            <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                        @enderror
                    </div>
                </div>

                {{-- Port --}}
                <div class="col-12 col-md-6">
                    <div class="mb-3">
                        <label for="port" class="form-label">
                            Puerto <span class="text-danger">*</span>
                        </label>
                        <input type="number"
                               class="form-control @error('port') is-invalid @enderror"
                               id="port"
                               name="port"
                               value="{{ old('port', $handler->port ?? '993') }}"
                               placeholder="993">
                        @error('port')
                            <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                        @enderror
                    </div>
                </div>

                {{-- Protocol --}}
                <div class="col-12 col-md-6">
                    <div class="mb-3">
                        <label for="protocol" class="form-label">
                            Protocolo
                        </label>
                        <select class="form-select @error('protocol') is-invalid @enderror"
                                id="protocol"
                                name="protocol">
                            <option value="">Selecciona protocolo</option>
                            <option value="imap" {{ old('protocol', $handler->protocol ?? '') === 'imap' ? 'selected' : '' }}>IMAP</option>
                            <option value="pop3" {{ old('protocol', $handler->protocol ?? '') === 'pop3' ? 'selected' : '' }}>POP3</option>
                        </select>
                        @error('protocol')
                            <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                        @enderror
                    </div>
                </div>

                {{-- Encryption --}}
                <div class="col-12 col-md-6">
                    <div class="mb-3">
                        <label for="encryption" class="form-label">
                            Encriptación
                        </label>
                        <select class="form-select @error('encryption') is-invalid @enderror"
                                id="encryption"
                                name="encryption">
                            <option value="">Sin encriptación</option>
                            <option value="ssl" {{ old('encryption', $handler->encryption ?? '') === 'ssl' ? 'selected' : '' }}>SSL</option>
                            <option value="tls" {{ old('encryption', $handler->encryption ?? '') === 'tls' ? 'selected' : '' }}>TLS</option>
                        </select>
                        @error('encryption')
                            <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                        @enderror
                    </div>
                </div>

                {{-- Email --}}
                <div class="col-12 col-md-6">
                    <div class="mb-3">
                        <label for="email" class="form-label">
                            Email <span class="text-danger">*</span>
                        </label>
                        <input type="email"
                               class="form-control @error('email') is-invalid @enderror"
                               id="email"
                               name="email"
                               value="{{ old('email', $handler->email ?? '') }}"
                               placeholder="feedback@example.com">
                        @error('email')
                            <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                        @enderror
                    </div>
                </div>

                {{-- Username --}}
                <div class="col-12 col-md-6">
                    <div class="mb-3">
                        <label for="username" class="form-label">
                            Usuario <span class="text-danger">*</span>
                        </label>
                        <input type="text"
                               class="form-control @error('username') is-invalid @enderror"
                               id="username"
                               name="username"
                               value="{{ old('username', $handler->username ?? '') }}"
                               placeholder="usuario@example.com">
                        @error('username')
                            <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                        @enderror
                    </div>
                </div>

                {{-- Password --}}
                <div class="col-12 col-md-6">
                    <div class="mb-3">
                        <label for="password" class="form-label">
                            Contraseña <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <input type="password"
                                   class="form-control @error('password') is-invalid @enderror"
                                   id="password"
                                   name="password"
                                   placeholder="Contraseña">
                            <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                <i class="fa fa-eye"></i>
                            </button>
                            @error('password')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                        @if(isset($handler))
                            <small class="text-muted">Deja en blanco para mantener la contraseña actual</small>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Webhook Configuration Card --}}
    <div class="card mb-3" id="webhookConfigCard" style="display: none;">
        <div class="card-header p-4 border-bottom border-light">
            <h6 class="mb-0 fw-bold">Configuración Webhook</h6>
        </div>

        <div class="card-body">
            <div class="row">
                {{-- Webhook Token --}}
                <div class="col-12 col-md-6">
                    <div class="mb-3">
                        <label for="webhook_token" class="form-label">
                            Token de webhook
                        </label>
                        <input type="text"
                               class="form-control @error('webhook_token') is-invalid @enderror"
                               id="webhook_token"
                               name="webhook_token"
                               value="{{ old('webhook_token', $handler->webhook_token ?? '') }}"
                               placeholder="token_aleatorio">
                        @error('webhook_token')
                            <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                        @enderror
                    </div>
                </div>

                {{-- Webhook Secret --}}
                <div class="col-12 col-md-6">
                    <div class="mb-3">
                        <label for="webhook_secret" class="form-label">
                            Secreto de webhook
                        </label>
                        <div class="input-group">
                            <input type="password"
                                   class="form-control @error('webhook_secret') is-invalid @enderror"
                                   id="webhook_secret"
                                   name="webhook_secret"
                                   placeholder="secreto_seguro">
                            <button class="btn btn-outline-secondary" type="button" id="toggleWebhookSecret">
                                <i class="fa fa-eye"></i>
                            </button>
                            @error('webhook_secret')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Feedback Type --}}
                <div class="col-12 col-md-6">
                    <div class="mb-3">
                        <label for="feedback_type" class="form-label">
                            Tipo de retroalimentación
                        </label>
                        <select class="form-select @error('feedback_type') is-invalid @enderror"
                                id="feedback_type"
                                name="feedback_type">
                            <option value="">Selecciona tipo</option>
                            <option value="abuse" {{ old('feedback_type', $handler->feedback_type ?? '') === 'abuse' ? 'selected' : '' }}>Abuso</option>
                            <option value="arf" {{ old('feedback_type', $handler->feedback_type ?? '') === 'arf' ? 'selected' : '' }}>ARF</option>
                        </select>
                        @error('feedback_type')
                            <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Processing Configuration Card --}}
    <div class="card mb-3">
        <div class="card-header p-4 border-bottom border-light">
            <h6 class="mb-0 fw-bold">Configuración de procesamiento</h6>
        </div>

        <div class="card-body">
            <div class="row">
                {{-- Auto Check --}}
                <div class="col-12 col-md-6">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input"
                               type="checkbox"
                               id="auto_check"
                               name="auto_check"
                               value="1"
                               {{ old('auto_check', $handler->auto_check ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="auto_check">
                            Verificación automática
                        </label>
                    </div>
                </div>

                {{-- Check Interval --}}
                <div class="col-12 col-md-6">
                    <div class="mb-3">
                        <label for="check_interval" class="form-label">
                            Intervalo de verificación (minutos)
                        </label>
                        <input type="number"
                               class="form-control @error('check_interval') is-invalid @enderror"
                               id="check_interval"
                               name="check_interval"
                               value="{{ old('check_interval', $handler->check_interval ?? 60) }}"
                               placeholder="60"
                               min="5">
                        @error('check_interval')
                            <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                        @enderror
                    </div>
                </div>

                {{-- Delete After Process --}}
                <div class="col-12 col-md-6">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input"
                               type="checkbox"
                               id="delete_after_process"
                               name="delete_after_process"
                               value="1"
                               {{ old('delete_after_process', $handler->delete_after_process ?? false) ? 'checked' : '' }}>
                        <label class="form-check-label" for="delete_after_process">
                            Eliminar correos después de procesar
                        </label>
                    </div>
                </div>

                {{-- Auto Unsubscribe --}}
                <div class="col-12 col-md-6">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input"
                               type="checkbox"
                               id="auto_unsubscribe"
                               name="auto_unsubscribe"
                               value="1"
                               {{ old('auto_unsubscribe', $handler->auto_unsubscribe ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="auto_unsubscribe">
                            Desuscribir automáticamente
                        </label>
                    </div>
                </div>

                {{-- Notify Admin --}}
                <div class="col-12 col-md-6">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input"
                               type="checkbox"
                               id="notify_admin"
                               name="notify_admin"
                               value="1"
                               {{ old('notify_admin', $handler->notify_admin ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="notify_admin">
                            Notificar administrador
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Action Buttons --}}
    <div class="card">
        <div class="card-body d-flex gap-2 justify-content-end">
            <a href="{{ route('settings.mailing.feedback-handlers.index') }}" class="btn btn-outline-secondary">
                <i class="fa fa-times me-2"></i>Cancelar
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="fa fa-save me-2"></i>{{ isset($handler) ? 'Actualizar' : 'Crear' }} manejador
            </button>
        </div>
    </div>

</form>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const typeSelect = document.getElementById('type');
    const emailCard = document.getElementById('emailConnectionCard');
    const webhookCard = document.getElementById('webhookConfigCard');
    const passwordToggle = document.getElementById('togglePassword');
    const webhookSecretToggle = document.getElementById('toggleWebhookSecret');

    // Show/hide configuration based on type
    function toggleConfigCards() {
        if (typeSelect.value === 'imap' || typeSelect.value === 'pop3') {
            emailCard.style.display = 'block';
            webhookCard.style.display = 'none';
        } else if (typeSelect.value === 'webhook' || typeSelect.value === 'api') {
            emailCard.style.display = 'none';
            webhookCard.style.display = 'block';
        } else {
            emailCard.style.display = 'none';
            webhookCard.style.display = 'none';
        }
    }

    // Toggle password visibility
    if (passwordToggle) {
        passwordToggle.addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this.querySelector('i');
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    }

    // Toggle webhook secret visibility
    if (webhookSecretToggle) {
        webhookSecretToggle.addEventListener('click', function() {
            const webhookSecretInput = document.getElementById('webhook_secret');
            const icon = this.querySelector('i');
            if (webhookSecretInput.type === 'password') {
                webhookSecretInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                webhookSecretInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    }

    typeSelect.addEventListener('change', toggleConfigCards);
    toggleConfigCards(); // Initial call
});
</script>
@endpush
