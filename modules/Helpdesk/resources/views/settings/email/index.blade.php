@extends('layouts.theme')

@section('title', 'Configuracion de email')

@section('content')

@include('core::components.alerts')

<div class="card">
    <div class="card-header p-4 border-bottom border-light">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-1 fw-bold">Configuracion de email</h5>
                <p class="small mb-0 text-muted">Configura el canal de email para enviar y recibir mensajes del helpdesk</p>
            </div>
        </div>
    </div>

    <div class="card-body">
        <form method="POST" action="{{ route('settings.helpdesk.email.update') }}">
            @csrf
            @method('PUT')

            {{-- Email del remitente --}}
            <h6 class="fw-semibold mb-1">Email del remitente</h6>
            <p class="text-muted small mb-3">Configura el nombre y direccion que veran los destinatarios cuando reciban un email del helpdesk.</p>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label" for="email_from_name">Nombre del remitente</label>
                    <input type="text" class="form-control @error('email_from_name') is-invalid @enderror"
                        id="email_from_name" name="email_from_name"
                        value="{{ old('email_from_name', $backups['email_from_name'] ?? '') }}"
                        placeholder="Soporte Tecnico" maxlength="100">
                    @error('email_from_name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="email_from_address">Correo del remitente</label>
                    <input type="email" class="form-control @error('email_from_address') is-invalid @enderror"
                        id="email_from_address" name="email_from_address"
                        value="{{ old('email_from_address', $backups['email_from_address'] ?? '') }}"
                        placeholder="soporte@empresa.com" maxlength="150">
                    @error('email_from_address')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="email_reply_to">Responder a (reply-to)</label>
                    <input type="email" class="form-control @error('email_reply_to') is-invalid @enderror"
                        id="email_reply_to" name="email_reply_to"
                        value="{{ old('email_reply_to', $backups['email_reply_to'] ?? '') }}"
                        placeholder="respuestas@empresa.com" maxlength="150">
                    <small class="form-text text-muted">Opcional. Si esta vacio se usa el correo del remitente.</small>
                    @error('email_reply_to')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-12">
                    <label class="form-label" for="email_signature">Firma de email</label>
                    <textarea class="form-control @error('email_signature') is-invalid @enderror"
                        id="email_signature" name="email_signature"
                        rows="4" maxlength="2000"
                        placeholder="El equipo de soporte&#10;empresa.com">{{ old('email_signature', $backups['email_signature'] ?? '') }}</textarea>
                    <small class="form-text text-muted">Se anadira automaticamente al pie de cada email enviado.</small>
                    @error('email_signature')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <hr>

            {{-- Email saliente (SMTP) --}}
            <h6 class="fw-semibold mb-1">Email saliente (SMTP)</h6>
            <p class="text-muted small mb-3">Configura el servidor SMTP para enviar emails desde el helpdesk.</p>
            <div class="row g-3 mb-3">
                <div class="col-12">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="outbound_enabled"
                            name="outbound_enabled" value="1"
                            {{ old('outbound_enabled', $backups['outbound_enabled'] ?? false) ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="outbound_enabled">
                            Habilitar email saliente
                        </label>
                    </div>
                    <small class="form-text text-muted">Cuando esta deshabilitado se usa la configuracion SMTP del sistema.</small>
                </div>
            </div>

            <div id="smtp-fields" class="{{ old('outbound_enabled', $backups['outbound_enabled'] ?? false) ? '' : 'd-none' }} mb-4">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label" for="smtp_host">Servidor SMTP</label>
                        <input type="text" class="form-control @error('smtp_host') is-invalid @enderror"
                            id="smtp_host" name="smtp_host"
                            value="{{ old('smtp_host', $backups['smtp_host'] ?? '') }}"
                            placeholder="smtp.gmail.com" maxlength="255">
                        @error('smtp_host')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="smtp_port">Puerto</label>
                        <input type="number" class="form-control @error('smtp_port') is-invalid @enderror"
                            id="smtp_port" name="smtp_port"
                            value="{{ old('smtp_port', $backups['smtp_port'] ?? 587) }}"
                            min="1" max="65535">
                        @error('smtp_port')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="smtp_username">Usuario SMTP</label>
                        <input type="text" class="form-control @error('smtp_username') is-invalid @enderror"
                            id="smtp_username" name="smtp_username"
                            value="{{ old('smtp_username', $backups['smtp_username'] ?? '') }}"
                            placeholder="usuario@gmail.com" maxlength="255" autocomplete="off">
                        @error('smtp_username')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="smtp_password">Contrasena SMTP</label>
                        <div class="input-group">
                            <input type="password" class="form-control @error('smtp_password') is-invalid @enderror"
                                id="smtp_password" name="smtp_password"
                                placeholder="{{ $hasSmtpPassword ? '••••••••' : 'Contrasena' }}"
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
                            <small class="form-text text-muted">Dejar vacio para mantener la contrasena guardada.</small>
                        @endif
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="smtp_encryption">Cifrado</label>
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
                </div>
            </div>

            <hr>

            {{-- Email entrante (IMAP) --}}
            <h6 class="fw-semibold mb-1">Email entrante (IMAP)</h6>
            <p class="text-muted small mb-3">Configura la cuenta IMAP para leer emails entrantes y convertirlos en tickets.</p>
            <div class="row g-3 mb-3">
                <div class="col-12">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="inbound_enabled"
                            name="inbound_enabled" value="1"
                            {{ old('inbound_enabled', $backups['inbound_enabled'] ?? false) ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="inbound_enabled">
                            Habilitar email entrante
                        </label>
                    </div>
                    <small class="form-text text-muted">Requiere configurar un trabajo programado para revisar el buzon periodicamente.</small>
                </div>
            </div>

            <div id="imap-fields" class="{{ old('inbound_enabled', $backups['inbound_enabled'] ?? false) ? '' : 'd-none' }} mb-4">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label" for="imap_host">Servidor IMAP</label>
                        <input type="text" class="form-control @error('imap_host') is-invalid @enderror"
                            id="imap_host" name="imap_host"
                            value="{{ old('imap_host', $backups['imap_host'] ?? '') }}"
                            placeholder="imap.gmail.com" maxlength="255">
                        @error('imap_host')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="imap_port">Puerto</label>
                        <input type="number" class="form-control @error('imap_port') is-invalid @enderror"
                            id="imap_port" name="imap_port"
                            value="{{ old('imap_port', $backups['imap_port'] ?? 993) }}"
                            min="1" max="65535">
                        @error('imap_port')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="imap_username">Usuario IMAP</label>
                        <input type="text" class="form-control @error('imap_username') is-invalid @enderror"
                            id="imap_username" name="imap_username"
                            value="{{ old('imap_username', $backups['imap_username'] ?? '') }}"
                            placeholder="soporte@empresa.com" maxlength="255" autocomplete="off">
                        @error('imap_username')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="imap_password">Contrasena IMAP</label>
                        <div class="input-group">
                            <input type="password" class="form-control @error('imap_password') is-invalid @enderror"
                                id="imap_password" name="imap_password"
                                placeholder="{{ $hasImapPassword ? '••••••••' : 'Contrasena' }}"
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
                            <small class="form-text text-muted">Dejar vacio para mantener la contrasena guardada.</small>
                        @endif
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="imap_encryption">Cifrado</label>
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
                        <label class="form-label" for="imap_folder">Carpeta IMAP</label>
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
                                Crear tickets automaticamente de emails entrantes
                            </label>
                        </div>
                        <small class="form-text text-muted">Cada email nuevo en el buzon generara un ticket automaticamente.</small>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('settings.helpdesk.tickets') }}" class="btn btn-light">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i> Guardar configuracion
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    $('#outbound_enabled').on('change', function () {
        $('#smtp-fields').toggleClass('d-none', !this.checked);
    });

    $('#inbound_enabled').on('change', function () {
        $('#imap-fields').toggleClass('d-none', !this.checked);
    });

    $(document).on('click', '.toggle-password', function () {
        var targetId = $(this).data('target');
        var $input = $('#' + targetId);
        var $icon = $(this).find('i');

        if ($input.attr('type') === 'password') {
            $input.attr('type', 'text');
            $icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            $input.attr('type', 'password');
            $icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });

    @if(session('success'))
        toastr.success('{{ session('success') }}', 'Exito');
    @endif

    @if(session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif
});
</script>
@endpush
