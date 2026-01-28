<form method="POST"
      action="{{ isset($server) ? route('settings.mailing.sending-servers.update', $server->uid) : route('settings.mailing.sending-servers.store') }}"
      id="formSendingServer">
    @method(isset($server) ? 'PATCH' : 'POST')
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
                               value="{{ old('name', $server->name ?? '') }}"
                               placeholder="Ej: Servidor principal SMTP"
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
                            <option value="smtp" {{ old('type', $server->type ?? '') === 'smtp' ? 'selected' : '' }}>SMTP</option>
                            <option value="sendgrid" {{ old('type', $server->type ?? '') === 'sendgrid' ? 'selected' : '' }}>SendGrid</option>
                            <option value="mailgun" {{ old('type', $server->type ?? '') === 'mailgun' ? 'selected' : '' }}>Mailgun</option>
                            <option value="ses" {{ old('type', $server->type ?? '') === 'ses' ? 'selected' : '' }}>Amazon SES</option>
                            <option value="postmark" {{ old('type', $server->type ?? '') === 'postmark' ? 'selected' : '' }}>Postmark</option>
                            <option value="sparkpost" {{ old('type', $server->type ?? '') === 'sparkpost' ? 'selected' : '' }}>SparkPost</option>
                            <option value="mailjet" {{ old('type', $server->type ?? '') === 'mailjet' ? 'selected' : '' }}>Mailjet</option>
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
                            <option value="active" {{ old('status', $server->status ?? 'inactive') === 'active' ? 'selected' : '' }}>Activo</option>
                            <option value="inactive" {{ old('status', $server->status ?? 'inactive') === 'inactive' ? 'selected' : '' }}>Inactivo</option>
                            <option value="error" {{ old('status', $server->status ?? '') === 'error' ? 'selected' : '' }}>Error</option>
                        </select>
                        @error('status')
                            <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                        @enderror
                    </div>
                </div>

                {{-- From Email --}}
                <div class="col-12 col-md-6">
                    <div class="mb-3">
                        <label for="from_email" class="form-label">
                            Email remitente <span class="text-danger">*</span>
                        </label>
                        <input type="email"
                               class="form-control @error('from_email') is-invalid @enderror"
                               id="from_email"
                               name="from_email"
                               value="{{ old('from_email', $server->from_email ?? '') }}"
                               placeholder="noreply@example.com"
                               required>
                        @error('from_email')
                            <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                        @enderror
                    </div>
                </div>

                {{-- From Name --}}
                <div class="col-12 col-md-6">
                    <div class="mb-3">
                        <label for="from_name" class="form-label">
                            Nombre remitente
                        </label>
                        <input type="text"
                               class="form-control @error('from_name') is-invalid @enderror"
                               id="from_name"
                               name="from_name"
                               value="{{ old('from_name', $server->from_name ?? '') }}"
                               placeholder="Mi empresa">
                        @error('from_name')
                            <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                        @enderror
                    </div>
                </div>

                {{-- Reply To Email --}}
                <div class="col-12 col-md-6">
                    <div class="mb-3">
                        <label for="reply_to_email" class="form-label">
                            Email de respuesta
                        </label>
                        <input type="email"
                               class="form-control @error('reply_to_email') is-invalid @enderror"
                               id="reply_to_email"
                               name="reply_to_email"
                               value="{{ old('reply_to_email', $server->reply_to_email ?? '') }}"
                               placeholder="support@example.com">
                        @error('reply_to_email')
                            <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- SMTP Configuration Card (shown when type = smtp) --}}
    <div class="card mb-3" id="smtpConfigCard" style="display: none;">
        <div class="card-header p-4 border-bottom border-light">
            <h6 class="mb-0 fw-bold">Configuración SMTP</h6>
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
                               value="{{ old('host', $server->host ?? '') }}"
                               placeholder="smtp.gmail.com">
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
                               value="{{ old('port', $server->port ?? '587') }}"
                               placeholder="587">
                        @error('port')
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
                            <option value="tls" {{ old('encryption', $server->encryption ?? '') === 'tls' ? 'selected' : '' }}>TLS</option>
                            <option value="ssl" {{ old('encryption', $server->encryption ?? '') === 'ssl' ? 'selected' : '' }}>SSL</option>
                        </select>
                        @error('encryption')
                            <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                        @enderror
                    </div>
                </div>

                {{-- Username --}}
                <div class="col-12 col-md-6">
                    <div class="mb-3">
                        <label for="username" class="form-label">
                            Usuario
                        </label>
                        <input type="text"
                               class="form-control @error('username') is-invalid @enderror"
                               id="username"
                               name="username"
                               value="{{ old('username', $server->username ?? '') }}"
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
                            Contraseña
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
                        @if(isset($server))
                            <small class="text-muted">Deja en blanco para mantener la contraseña actual</small>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- API Configuration Card (shown when type != smtp) --}}
    <div class="card mb-3" id="apiConfigCard" style="display: none;">
        <div class="card-header p-4 border-bottom border-light">
            <h6 class="mb-0 fw-bold">Configuración API</h6>
        </div>

        <div class="card-body">
            <div class="row">
                {{-- API Key --}}
                <div class="col-12 col-md-6">
                    <div class="mb-3">
                        <label for="api_key" class="form-label">
                            API Key <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <input type="password"
                                   class="form-control @error('api_key') is-invalid @enderror"
                                   id="api_key"
                                   name="api_key"
                                   placeholder="API Key">
                            <button class="btn btn-outline-secondary" type="button" id="toggleApiKey">
                                <i class="fa fa-eye"></i>
                            </button>
                            @error('api_key')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                        @if(isset($server))
                            <small class="text-muted">Deja en blanco para mantener la API Key actual</small>
                        @endif
                    </div>
                </div>

                {{-- API Secret --}}
                <div class="col-12 col-md-6">
                    <div class="mb-3">
                        <label for="api_secret" class="form-label">
                            API Secret
                        </label>
                        <div class="input-group">
                            <input type="password"
                                   class="form-control @error('api_secret') is-invalid @enderror"
                                   id="api_secret"
                                   name="api_secret"
                                   placeholder="API Secret">
                            <button class="btn btn-outline-secondary" type="button" id="toggleApiSecret">
                                <i class="fa fa-eye"></i>
                            </button>
                            @error('api_secret')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                        @if(isset($server))
                            <small class="text-muted">Deja en blanco para mantener el API Secret actual</small>
                        @endif
                    </div>
                </div>

                {{-- API Region --}}
                <div class="col-12 col-md-6">
                    <div class="mb-3">
                        <label for="api_region" class="form-label">
                            Región (para AWS SES, etc.)
                        </label>
                        <input type="text"
                               class="form-control @error('api_region') is-invalid @enderror"
                               id="api_region"
                               name="api_region"
                               value="{{ old('api_region', $server->api_region ?? '') }}"
                               placeholder="us-east-1">
                        @error('api_region')
                            <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Rate Limiting Card --}}
    <div class="card mb-3">
        <div class="card-header p-4 border-bottom border-light">
            <h6 class="mb-0 fw-bold">Límites de envío</h6>
        </div>

        <div class="card-body">
            <div class="row">
                {{-- Sending Limit Per Minute --}}
                <div class="col-12 col-md-4">
                    <div class="mb-3">
                        <label for="sending_limit_per_minute" class="form-label">
                            Límite por minuto
                        </label>
                        <input type="number"
                               class="form-control @error('sending_limit_per_minute') is-invalid @enderror"
                               id="sending_limit_per_minute"
                               name="sending_limit_per_minute"
                               value="{{ old('sending_limit_per_minute', $server->sending_limit_per_minute ?? 10) }}"
                               placeholder="10"
                               min="1">
                        @error('sending_limit_per_minute')
                            <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                        @enderror
                    </div>
                </div>

                {{-- Sending Limit Per Hour --}}
                <div class="col-12 col-md-4">
                    <div class="mb-3">
                        <label for="sending_limit_per_hour" class="form-label">
                            Límite por hora
                        </label>
                        <input type="number"
                               class="form-control @error('sending_limit_per_hour') is-invalid @enderror"
                               id="sending_limit_per_hour"
                               name="sending_limit_per_hour"
                               value="{{ old('sending_limit_per_hour', $server->sending_limit_per_hour ?? 100) }}"
                               placeholder="100"
                               min="1">
                        @error('sending_limit_per_hour')
                            <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                        @enderror
                    </div>
                </div>

                {{-- Allow Custom Return Path --}}
                <div class="col-12 col-md-4">
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input"
                                   type="checkbox"
                                   id="allow_custom_return_path"
                                   name="allow_custom_return_path"
                                   value="1"
                                   {{ old('allow_custom_return_path', $server->allow_custom_return_path ?? false) ? 'checked' : '' }}>
                            <label class="form-check-label" for="allow_custom_return_path">
                                Permitir Return Path personalizado
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Advanced Options Card --}}
    <div class="card mb-3">
        <div class="card-header p-4 border-bottom border-light">
            <h6 class="mb-0 fw-bold">Opciones avanzadas</h6>
        </div>

        <div class="card-body">
            <div class="row">
                {{-- Allow Verify Domain --}}
                <div class="col-12 col-md-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input"
                               type="checkbox"
                               id="allow_verify_domain"
                               name="allow_verify_domain"
                               value="1"
                               {{ old('allow_verify_domain', $server->allow_verify_domain ?? false) ? 'checked' : '' }}>
                        <label class="form-check-label" for="allow_verify_domain">
                            Permitir verificación de dominio
                        </label>
                    </div>
                </div>

                {{-- Allow Verify Email --}}
                <div class="col-12 col-md-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input"
                               type="checkbox"
                               id="allow_verify_email"
                               name="allow_verify_email"
                               value="1"
                               {{ old('allow_verify_email', $server->allow_verify_email ?? false) ? 'checked' : '' }}>
                        <label class="form-check-label" for="allow_verify_email">
                            Permitir verificación de email
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Action Buttons --}}
    <div class="card">
        <div class="card-body d-flex gap-2 justify-content-end">
            <a href="{{ route('settings.mailing.sending-servers.index') }}" class="btn btn-outline-secondary">
                <i class="fa fa-times me-2"></i>Cancelar
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="fa fa-save me-2"></i>{{ isset($server) ? 'Actualizar' : 'Crear' }} servidor
            </button>
        </div>
    </div>

</form>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const typeSelect = document.getElementById('type');
    const smtpCard = document.getElementById('smtpConfigCard');
    const apiCard = document.getElementById('apiConfigCard');
    const passwordToggle = document.getElementById('togglePassword');
    const apiKeyToggle = document.getElementById('toggleApiKey');
    const apiSecretToggle = document.getElementById('toggleApiSecret');

    // Show/hide configuration based on type
    function toggleConfigCards() {
        if (typeSelect.value === 'smtp') {
            smtpCard.style.display = 'block';
            apiCard.style.display = 'none';
        } else if (typeSelect.value) {
            smtpCard.style.display = 'none';
            apiCard.style.display = 'block';
        } else {
            smtpCard.style.display = 'none';
            apiCard.style.display = 'none';
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

    // Toggle API Key visibility
    if (apiKeyToggle) {
        apiKeyToggle.addEventListener('click', function() {
            const apiKeyInput = document.getElementById('api_key');
            const icon = this.querySelector('i');
            if (apiKeyInput.type === 'password') {
                apiKeyInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                apiKeyInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    }

    // Toggle API Secret visibility
    if (apiSecretToggle) {
        apiSecretToggle.addEventListener('click', function() {
            const apiSecretInput = document.getElementById('api_secret');
            const icon = this.querySelector('i');
            if (apiSecretInput.type === 'password') {
                apiSecretInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                apiSecretInput.type = 'password';
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
