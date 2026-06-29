@extends('layouts.theme')

@section('title', 'Configuracion de email')

@section('content')

<div class="row g-4 align-items-start">

    {{-- Columna principal --}}
    <div class="col-lg-8">
        <form method="POST" action="{{ route('settings.helpdesk.email.update') }}" id="emailSettingsForm">
            @csrf
            @method('PUT')

            @include('core::components.alerts')

            {{-- Remitente --}}
            <div class="card mb-4">
                <div class="card-body">
                    <h6 class="fw-bold text-dark mb-1">Remitente</h6>
                    <p class="text-muted mb-3">Nombre y dirección que verán los destinatarios al recibir un email del helpdesk.</p>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="email_from_name">Nombre del remitente</label>
                            <input type="text" class="form-control @error('email_from_name') is-invalid @enderror"
                                id="email_from_name" name="email_from_name"
                                value="{{ old('email_from_name', $backups['email_from_name'] ?? '') }}"
                                placeholder="Soporte Tecnico" maxlength="100">
                            @error('email_from_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="email_from_address">Correo del remitente</label>
                            <input type="email" class="form-control @error('email_from_address') is-invalid @enderror"
                                id="email_from_address" name="email_from_address"
                                value="{{ old('email_from_address', $backups['email_from_address'] ?? '') }}"
                                placeholder="soporte@empresa.com" maxlength="150">
                            @error('email_from_address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="email_reply_to">Responder a (reply-to)</label>
                            <input type="email" class="form-control @error('email_reply_to') is-invalid @enderror"
                                id="email_reply_to" name="email_reply_to"
                                value="{{ old('email_reply_to', $backups['email_reply_to'] ?? '') }}"
                                placeholder="respuestas@empresa.com" maxlength="150">
                            <small class="text-muted">Opcional. Si está vacío se usa el correo del remitente.</small>
                            @error('email_reply_to')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold" for="email_signature">Firma de email</label>
                            <textarea class="form-control @error('email_signature') is-invalid @enderror"
                                id="email_signature" name="email_signature"
                                rows="4" maxlength="2000"
                                placeholder="El equipo de soporte&#10;empresa.com">{{ old('email_signature', $backups['email_signature'] ?? '') }}</textarea>
                            <small class="text-muted">Se añadirá al pie de cada email enviado.</small>
                            @error('email_signature')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <hr class="my-0">

                {{-- SMTP --}}
                <div class="card-body">
                    <h6 class="fw-bold text-dark mb-1">Email saliente (SMTP)</h6>
                    <p class="text-muted mb-3">Servidor SMTP para enviar emails desde el helpdesk. Si está deshabilitado se usa la configuración del sistema.</p>

                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" id="outbound_enabled"
                            name="outbound_enabled" value="1"
                            {{ old('outbound_enabled', $backups['outbound_enabled'] ?? false) ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="outbound_enabled">
                            Habilitar email saliente
                        </label>
                    </div>

                    <div id="smtp-fields" class="{{ old('outbound_enabled', $backups['outbound_enabled'] ?? false) ? '' : 'd-none' }}">
                        <div class="row g-3 mt-1">
                            <div class="col-md-8">
                                <label class="form-label fw-semibold" for="smtp_host">Servidor SMTP</label>
                                <input type="text" class="form-control @error('smtp_host') is-invalid @enderror"
                                    id="smtp_host" name="smtp_host"
                                    value="{{ old('smtp_host', $backups['smtp_host'] ?? '') }}"
                                    placeholder="smtp.gmail.com" maxlength="255">
                                @error('smtp_host')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold" for="smtp_port">Puerto</label>
                                <input type="number" class="form-control @error('smtp_port') is-invalid @enderror"
                                    id="smtp_port" name="smtp_port"
                                    value="{{ old('smtp_port', $backups['smtp_port'] ?? 587) }}"
                                    min="1" max="65535">
                                @error('smtp_port')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="smtp_username">Usuario SMTP</label>
                                <input type="text" class="form-control @error('smtp_username') is-invalid @enderror"
                                    id="smtp_username" name="smtp_username"
                                    value="{{ old('smtp_username', $backups['smtp_username'] ?? '') }}"
                                    placeholder="usuario@gmail.com" maxlength="255" autocomplete="off">
                                @error('smtp_username')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="smtp_password">Contraseña SMTP</label>
                                <div class="input-group">
                                    <input type="password" class="form-control @error('smtp_password') is-invalid @enderror"
                                        id="smtp_password" name="smtp_password"
                                        placeholder="{{ $hasSmtpPassword ? '••••••••' : 'Contraseña' }}"
                                        maxlength="255" autocomplete="new-password">
                                    <button type="button" class="btn btn-outline-secondary toggle-password"
                                        data-target="smtp_password" tabindex="-1">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    @error('smtp_password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                @if($hasSmtpPassword)
                                    <small class="text-muted">Dejar vacío para conservar la contraseña guardada.</small>
                                @endif
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold" for="smtp_encryption">Cifrado</label>
                                <select class="form-select @error('smtp_encryption') is-invalid @enderror"
                                    id="smtp_encryption" name="smtp_encryption">
                                    @foreach(['tls' => 'TLS (recomendado)', 'ssl' => 'SSL', 'none' => 'Sin cifrado'] as $value => $label)
                                        <option value="{{ $value }}"
                                            {{ old('smtp_encryption', $backups['smtp_encryption'] ?? 'tls') === $value ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('smtp_encryption')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12">
                                <button type="button" class="btn btn-outline-primary" id="testSmtpBtn">
                                    <i class="fas fa-plug me-1"></i> Probar conexion SMTP
                                </button>
                                <span id="smtp-test-result" class="ms-2 small"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <hr class="my-0">

                {{-- IMAP --}}
                <div class="card-body">
                    <h6 class="fw-bold text-dark mb-1">Email entrante (IMAP)</h6>
                    <p class="text-muted mb-3">Cuenta IMAP para leer emails y convertirlos en tickets automáticamente.</p>

                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" id="inbound_enabled"
                            name="inbound_enabled" value="1"
                            {{ old('inbound_enabled', $backups['inbound_enabled'] ?? false) ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="inbound_enabled">
                            Habilitar email entrante
                        </label>
                    </div>
                    <small class="text-muted d-block mb-1">Requiere un trabajo programado para revisar el buzón periódicamente.</small>

                    <div id="imap-fields" class="{{ old('inbound_enabled', $backups['inbound_enabled'] ?? false) ? '' : 'd-none' }}">
                        <div class="row g-3 mt-1">
                            <div class="col-md-8">
                                <label class="form-label fw-semibold" for="imap_host">Servidor IMAP</label>
                                <input type="text" class="form-control @error('imap_host') is-invalid @enderror"
                                    id="imap_host" name="imap_host"
                                    value="{{ old('imap_host', $backups['imap_host'] ?? '') }}"
                                    placeholder="imap.gmail.com" maxlength="255">
                                @error('imap_host')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold" for="imap_port">Puerto</label>
                                <input type="number" class="form-control @error('imap_port') is-invalid @enderror"
                                    id="imap_port" name="imap_port"
                                    value="{{ old('imap_port', $backups['imap_port'] ?? 993) }}"
                                    min="1" max="65535">
                                @error('imap_port')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="imap_username">Usuario IMAP</label>
                                <input type="text" class="form-control @error('imap_username') is-invalid @enderror"
                                    id="imap_username" name="imap_username"
                                    value="{{ old('imap_username', $backups['imap_username'] ?? '') }}"
                                    placeholder="soporte@empresa.com" maxlength="255" autocomplete="off">
                                @error('imap_username')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="imap_password">Contraseña IMAP</label>
                                <div class="input-group">
                                    <input type="password" class="form-control @error('imap_password') is-invalid @enderror"
                                        id="imap_password" name="imap_password"
                                        placeholder="{{ $hasImapPassword ? '••••••••' : 'Contraseña' }}"
                                        maxlength="255" autocomplete="new-password">
                                    <button type="button" class="btn btn-outline-secondary toggle-password"
                                        data-target="imap_password" tabindex="-1">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    @error('imap_password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                @if($hasImapPassword)
                                    <small class="text-muted">Dejar vacío para conservar la contraseña guardada.</small>
                                @endif
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold" for="imap_encryption">Cifrado</label>
                                <select class="form-select @error('imap_encryption') is-invalid @enderror"
                                    id="imap_encryption" name="imap_encryption">
                                    @foreach(['ssl' => 'SSL (recomendado)', 'tls' => 'TLS', 'none' => 'Sin cifrado'] as $value => $label)
                                        <option value="{{ $value }}"
                                            {{ old('imap_encryption', $backups['imap_encryption'] ?? 'ssl') === $value ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('imap_encryption')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold" for="imap_folder">Carpeta IMAP</label>
                                <input type="text" class="form-control @error('imap_folder') is-invalid @enderror"
                                    id="imap_folder" name="imap_folder"
                                    value="{{ old('imap_folder', $backups['imap_folder'] ?? 'INBOX') }}"
                                    placeholder="INBOX" maxlength="100">
                                @error('imap_folder')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="auto_create_tickets"
                                        name="auto_create_tickets" value="1"
                                        {{ old('auto_create_tickets', $backups['auto_create_tickets'] ?? true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="auto_create_tickets">
                                        Crear tickets automáticamente de emails entrantes
                                    </label>
                                </div>
                                <small class="text-muted">Cada email nuevo en el buzón generará un ticket automáticamente.</small>
                            </div>
                            <div class="col-12">
                                <button type="button" class="btn btn-outline-primary" id="testImapBtn">
                                    <i class="fas fa-plug me-1"></i> Probar conexion IMAP
                                </button>
                                <span id="imap-test-result" class="ms-2 small"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-footer">
                    <button type="submit" class="btn btn-primary w-100" id="saveBtn">
                        Guardar configuracion
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- Sidebar --}}
    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header border-bottom">
                <h6 class="mb-0 fw-bold">Puertos y cifrado recomendados</h6>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-bordered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Protocolo</th>
                            <th>Puerto</th>
                            <th>Cifrado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="ps-3">SMTP</td>
                            <td>587</td>
                            <td><span class="badge bg-success-subtle text-success">TLS</span></td>
                        </tr>
                        <tr>
                            <td class="ps-3">SMTP</td>
                            <td>465</td>
                            <td><span class="badge bg-info-subtle text-info">SSL</span></td>
                        </tr>
                        <tr>
                            <td class="ps-3">IMAP</td>
                            <td>993</td>
                            <td><span class="badge bg-info-subtle text-info">SSL</span></td>
                        </tr>
                        <tr>
                            <td class="ps-3">IMAP</td>
                            <td>143</td>
                            <td><span class="badge bg-success-subtle text-success">TLS</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header border-bottom">
                <h6 class="mb-0 fw-bold">Gmail</h6>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-2">Gmail requiere una <strong>contraseña de aplicación</strong>, no la contraseña normal de la cuenta.</p>
                <ol class="text-muted small ps-3 mb-0">
                    <li class="mb-1">Activa la verificación en 2 pasos en tu cuenta Google</li>
                    <li class="mb-1">Ve a <strong>Cuenta Google → Seguridad → Contraseñas de aplicaciones</strong></li>
                    <li class="mb-1">Selecciona "Correo" y genera la contraseña</li>
                    <li>Usa esa contraseña de 16 caracteres aquí</li>
                </ol>
                <hr class="my-2">
                <div class="row g-1 small">
                    <div class="col-12"><strong>SMTP:</strong> smtp.gmail.com : 587 (TLS)</div>
                    <div class="col-12"><strong>IMAP:</strong> imap.gmail.com : 993 (SSL)</div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header border-bottom">
                <h6 class="mb-0 fw-bold">Microsoft 365 / Outlook</h6>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-2">Requiere autenticación moderna (OAuth2) o habilitar SMTP AUTH en el panel de administración.</p>
                <div class="row g-1 small">
                    <div class="col-12"><strong>SMTP:</strong> smtp.office365.com : 587 (TLS)</div>
                    <div class="col-12"><strong>IMAP:</strong> outlook.office365.com : 993 (SSL)</div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header border-bottom">
                <h6 class="mb-0 fw-bold">Otros proveedores</h6>
            </div>
            <div class="card-body">
                <div class="small text-muted">
                    <div class="mb-2">
                        <strong>Yahoo Mail</strong><br>
                        SMTP: smtp.mail.yahoo.com : 587 (TLS)<br>
                        IMAP: imap.mail.yahoo.com : 993 (SSL)
                    </div>
                    <div class="mb-2">
                        <strong>Zoho Mail</strong><br>
                        SMTP: smtp.zoho.com : 587 (TLS)<br>
                        IMAP: imap.zoho.com : 993 (SSL)
                    </div>
                    <div>
                        <strong>SendGrid / Mailgun</strong><br>
                        Solo SMTP saliente. No soportan IMAP.
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

@endsection

@push('scripts')
<script>
(function () {
    const csrf = document.querySelector('meta[name="csrf-token"]').content;

    // Toggle visibility smtp/imap fields
    $('#outbound_enabled').on('change', function () {
        $('#smtp-fields').toggleClass('d-none', !this.checked);
    });

    $('#inbound_enabled').on('change', function () {
        $('#imap-fields').toggleClass('d-none', !this.checked);
    });

    // Toggle password visibility
    $(document).on('click', '.toggle-password', function () {
        const $input = $('#' + $(this).data('target'));
        const isPassword = $input.attr('type') === 'password';
        $input.attr('type', isPassword ? 'text' : 'password');
        $(this).find('i').toggleClass('fa-eye', !isPassword).toggleClass('fa-eye-slash', isPassword);
    });

    // Save form via AJAX
    $('#emailSettingsForm').on('submit', function (e) {
        e.preventDefault();
        const $btn = $('#saveBtn').prop('disabled', true).text('Guardando...');

        fetch(this.action, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body: new FormData(this),
        })
        .then(r => r.json())
        .then(data => {
            if (data.status) {
                toastr.success(data.message, 'Guardado');
            } else {
                toastr.error(data.message || 'Error al guardar', 'Error');
            }
        })
        .catch(() => toastr.error('Error al guardar la configuración', 'Error'))
        .finally(() => $btn.prop('disabled', false).text('Guardar configuracion'));
    });

    // Test SMTP connection
    $('#testSmtpBtn').on('click', function () {
        const $btn = $(this).prop('disabled', true);
        const $result = $('#smtp-test-result').text('Probando...').removeClass('text-success text-danger');

        fetch('{{ route("settings.helpdesk.email.test-smtp") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify({
                host:       $('#smtp_host').val(),
                port:       $('#smtp_port').val(),
                username:   $('#smtp_username').val(),
                password:   $('#smtp_password').val(),
                encryption: $('#smtp_encryption').val(),
            }),
        })
        .then(r => r.json())
        .then(data => {
            if (data.status) {
                $result.addClass('text-success').text(data.message);
                toastr.success(data.message, 'SMTP');
            } else {
                $result.addClass('text-danger').text(data.message);
                toastr.error(data.message, 'SMTP');
            }
        })
        .catch(() => {
            $result.addClass('text-danger').text('Error al ejecutar la prueba');
            toastr.error('Error al probar la conexión SMTP', 'Error');
        })
        .finally(() => $btn.prop('disabled', false));
    });

    // Test IMAP connection
    $('#testImapBtn').on('click', function () {
        const $btn = $(this).prop('disabled', true);
        const $result = $('#imap-test-result').text('Probando...').removeClass('text-success text-danger');

        fetch('{{ route("settings.helpdesk.email.test-imap") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify({
                host:       $('#imap_host').val(),
                port:       $('#imap_port').val(),
                username:   $('#imap_username').val(),
                password:   $('#imap_password').val(),
                encryption: $('#imap_encryption').val(),
                folder:     $('#imap_folder').val(),
            }),
        })
        .then(r => r.json())
        .then(data => {
            if (data.status) {
                $result.addClass('text-success').text(data.message);
                toastr.success(data.message, 'IMAP');
            } else {
                $result.addClass('text-danger').text(data.message);
                toastr.error(data.message, 'IMAP');
            }
        })
        .catch(() => {
            $result.addClass('text-danger').text('Error al ejecutar la prueba');
            toastr.error('Error al probar la conexión IMAP', 'Error');
        })
        .finally(() => $btn.prop('disabled', false));
    });
})();
</script>
@endpush
