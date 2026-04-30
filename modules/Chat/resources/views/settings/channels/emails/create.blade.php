@extends('layouts.theme')

@section('content')

    @include('core::components.card', ['title' => 'Crear canal de email'])

    @include('core::components.alerts')

    <div class="widget-content searchable-container list">

        <div class="row g-4 align-items-start">

            {{-- Columna izquierda --}}
            <div class="col-lg-8">
                <form action="{{ route('settings.chat.channels.emails.store') }}" method="POST" id="email-form">
                    @csrf

                    <div class="card">

                        {{-- Información general --}}
                        <div class="card-body">
                            <h6 class="fw-bold text-dark mb-1">Información general</h6>
                            <p class="text-muted mb-3">Configura el nombre del canal, la dirección de correo y el proveedor de email.</p>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="inbox_name" class="form-label fw-semibold">Nombre de la bandeja <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('inbox_name') is-invalid @enderror"
                                           id="inbox_name" name="inbox_name" value="{{ old('inbox_name') }}" required>
                                    @error('inbox_name') <small class="text-danger">{{ $message }}</small> @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="email" class="form-label fw-semibold">Dirección de email <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control @error('email') is-invalid @enderror"
                                           id="email" name="email" value="{{ old('email') }}" required>
                                    @error('email') <small class="text-danger">{{ $message }}</small> @enderror
                                </div>

                                <div class="col-12">
                                    <label for="provider" class="form-label fw-semibold">Proveedor</label>
                                    <select class="form-control select2 @error('provider') is-invalid @enderror" id="provider" name="provider">
                                        <option value="custom" {{ old('provider') == 'custom' ? 'selected' : '' }}>Personalizado (IMAP/SMTP genérico)</option>
                                        <option value="google" {{ old('provider') == 'google' ? 'selected' : '' }}>Google / Gmail</option>
                                        <option value="microsoft" {{ old('provider') == 'microsoft' ? 'selected' : '' }}>Microsoft / Outlook</option>
                                        <option value="zoho" {{ old('provider') == 'zoho' ? 'selected' : '' }}>Zoho Mail</option>
                                    </select>
                                    <small class="text-muted d-block mt-1">Selecciona un proveedor para rellenar los servidores automáticamente.</small>
                                    @error('provider') <small class="text-danger">{{ $message }}</small> @enderror
                                </div>
                            </div>
                        </div>

                        <hr class="my-0">

                        {{-- IMAP --}}
                        <div class="card-body">
                            <h6 class="fw-bold text-dark mb-1">Recepción de correos (IMAP)</h6>
                            <p class="text-muted mb-3">Configura el servidor IMAP para recibir correos entrantes en el chat.</p>

                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" id="imap_enabled" name="imap_enabled" value="1"
                                       {{ old('imap_enabled') ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="imap_enabled">Habilitar IMAP</label>
                            </div>
                            <small class="text-muted d-block mb-3">Activa para recibir correos entrantes automáticamente.</small>

                            <div id="imap-settings" class="{{ old('imap_enabled') ? '' : 'd-none' }}">
                                <div class="row g-3">
                                    <div class="col-md-8">
                                        <label for="imap_address" class="form-label fw-semibold">Servidor IMAP</label>
                                        <input type="text" class="form-control @error('imap_address') is-invalid @enderror"
                                               id="imap_address" name="imap_address" value="{{ old('imap_address') }}"
                                               placeholder="imap.example.com">
                                        @error('imap_address') <small class="text-danger">{{ $message }}</small> @enderror
                                    </div>

                                    <div class="col-md-4">
                                        <label for="imap_port" class="form-label fw-semibold">Puerto</label>
                                        <input type="number" class="form-control @error('imap_port') is-invalid @enderror"
                                               id="imap_port" name="imap_port" value="{{ old('imap_port', 993) }}" placeholder="993">
                                        @error('imap_port') <small class="text-danger">{{ $message }}</small> @enderror
                                    </div>

                                    <div class="col-md-6">
                                        <label for="imap_login" class="form-label fw-semibold">Usuario</label>
                                        <input type="text" class="form-control @error('imap_login') is-invalid @enderror"
                                               id="imap_login" name="imap_login" value="{{ old('imap_login') }}">
                                        @error('imap_login') <small class="text-danger">{{ $message }}</small> @enderror
                                    </div>

                                    <div class="col-md-6">
                                        <label for="imap_password" class="form-label fw-semibold">Contraseña</label>
                                        <input type="password" class="form-control @error('imap_password') is-invalid @enderror"
                                               id="imap_password" name="imap_password">
                                        @error('imap_password') <small class="text-danger">{{ $message }}</small> @enderror
                                    </div>

                                    <div class="col-md-6">
                                        <label for="imap_enable_ssl" class="form-label fw-semibold">Habilitar SSL/TLS</label>
                                        <select class="form-control select2 @error('imap_enable_ssl') is-invalid @enderror"
                                                id="imap_enable_ssl" name="imap_enable_ssl">
                                            <option value="1" {{ old('imap_enable_ssl', '1') == '1' ? 'selected' : '' }}>Sí</option>
                                            <option value="0" {{ old('imap_enable_ssl', '1') == '0' ? 'selected' : '' }}>No</option>
                                        </select>
                                        @error('imap_enable_ssl') <small class="text-danger">{{ $message }}</small> @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr class="my-0">

                        {{-- SMTP --}}
                        <div class="card-body">
                            <h6 class="fw-bold text-dark mb-1">Envío de correos (SMTP)</h6>
                            <p class="text-muted mb-3">Configura el servidor SMTP para enviar correos desde las conversaciones.</p>

                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" id="smtp_enabled" name="smtp_enabled" value="1"
                                       {{ old('smtp_enabled') ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="smtp_enabled">Habilitar SMTP</label>
                            </div>
                            <small class="text-muted d-block mb-3">Activa para enviar respuestas directamente desde el chat.</small>

                            <div id="smtp-settings" class="{{ old('smtp_enabled') ? '' : 'd-none' }}">
                                <div class="row g-3">
                                    <div class="col-md-8">
                                        <label for="smtp_address" class="form-label fw-semibold">Servidor SMTP</label>
                                        <input type="text" class="form-control @error('smtp_address') is-invalid @enderror"
                                               id="smtp_address" name="smtp_address" value="{{ old('smtp_address') }}"
                                               placeholder="smtp.example.com">
                                        @error('smtp_address') <small class="text-danger">{{ $message }}</small> @enderror
                                    </div>

                                    <div class="col-md-4">
                                        <label for="smtp_port" class="form-label fw-semibold">Puerto</label>
                                        <input type="number" class="form-control @error('smtp_port') is-invalid @enderror"
                                               id="smtp_port" name="smtp_port" value="{{ old('smtp_port', 587) }}" placeholder="587">
                                        @error('smtp_port') <small class="text-danger">{{ $message }}</small> @enderror
                                    </div>

                                    <div class="col-md-6">
                                        <label for="smtp_login" class="form-label fw-semibold">Usuario</label>
                                        <input type="text" class="form-control @error('smtp_login') is-invalid @enderror"
                                               id="smtp_login" name="smtp_login" value="{{ old('smtp_login') }}">
                                        @error('smtp_login') <small class="text-danger">{{ $message }}</small> @enderror
                                    </div>

                                    <div class="col-md-6">
                                        <label for="smtp_password" class="form-label fw-semibold">Contraseña</label>
                                        <input type="password" class="form-control @error('smtp_password') is-invalid @enderror"
                                               id="smtp_password" name="smtp_password">
                                        @error('smtp_password') <small class="text-danger">{{ $message }}</small> @enderror
                                    </div>

                                    <div class="col-md-6">
                                        <label for="smtp_domain" class="form-label fw-semibold">Dominio <span class="text-muted fw-normal">(opcional)</span></label>
                                        <input type="text" class="form-control @error('smtp_domain') is-invalid @enderror"
                                               id="smtp_domain" name="smtp_domain" value="{{ old('smtp_domain') }}">
                                        @error('smtp_domain') <small class="text-danger">{{ $message }}</small> @enderror
                                    </div>

                                    <div class="col-md-6">
                                        <label for="smtp_authentication" class="form-label fw-semibold">Autenticación</label>
                                        <select class="form-control select2 @error('smtp_authentication') is-invalid @enderror"
                                                id="smtp_authentication" name="smtp_authentication">
                                            <option value="plain" {{ old('smtp_authentication') == 'plain' ? 'selected' : '' }}>Plain</option>
                                            <option value="login" {{ old('smtp_authentication', 'login') == 'login' ? 'selected' : '' }}>Login</option>
                                            <option value="cram_md5" {{ old('smtp_authentication') == 'cram_md5' ? 'selected' : '' }}>CRAM-MD5</option>
                                        </select>
                                        @error('smtp_authentication') <small class="text-danger">{{ $message }}</small> @enderror
                                    </div>

                                    <div class="col-md-6">
                                        <label for="smtp_enable_starttls_auto" class="form-label fw-semibold">Habilitar STARTTLS</label>
                                        <select class="form-control select2 @error('smtp_enable_starttls_auto') is-invalid @enderror"
                                                id="smtp_enable_starttls_auto" name="smtp_enable_starttls_auto">
                                            <option value="1" {{ old('smtp_enable_starttls_auto', '1') == '1' ? 'selected' : '' }}>Sí</option>
                                            <option value="0" {{ old('smtp_enable_starttls_auto', '1') == '0' ? 'selected' : '' }}>No</option>
                                        </select>
                                        @error('smtp_enable_starttls_auto') <small class="text-danger">{{ $message }}</small> @enderror
                                    </div>

                                    <div class="col-md-6">
                                        <label for="smtp_enable_ssl_tls" class="form-label fw-semibold">Habilitar SSL/TLS</label>
                                        <select class="form-control select2 @error('smtp_enable_ssl_tls') is-invalid @enderror"
                                                id="smtp_enable_ssl_tls" name="smtp_enable_ssl_tls">
                                            <option value="1" {{ old('smtp_enable_ssl_tls', '0') == '1' ? 'selected' : '' }}>Sí</option>
                                            <option value="0" {{ old('smtp_enable_ssl_tls', '0') == '0' ? 'selected' : '' }}>No</option>
                                        </select>
                                        @error('smtp_enable_ssl_tls') <small class="text-danger">{{ $message }}</small> @enderror
                                    </div>

                                    <div class="col-12">
                                        <label for="smtp_openssl_verify_mode" class="form-label fw-semibold">Modo de verificación OpenSSL</label>
                                        <select class="form-control select2 @error('smtp_openssl_verify_mode') is-invalid @enderror"
                                                id="smtp_openssl_verify_mode" name="smtp_openssl_verify_mode">
                                            <option value="none" {{ old('smtp_openssl_verify_mode', 'none') == 'none' ? 'selected' : '' }}>Ninguno</option>
                                            <option value="peer" {{ old('smtp_openssl_verify_mode') == 'peer' ? 'selected' : '' }}>Peer</option>
                                            <option value="client_once" {{ old('smtp_openssl_verify_mode') == 'client_once' ? 'selected' : '' }}>Client Once</option>
                                            <option value="fail_if_no_peer_cert" {{ old('smtp_openssl_verify_mode') == 'fail_if_no_peer_cert' ? 'selected' : '' }}>Fail if No Peer Cert</option>
                                        </select>
                                        @error('smtp_openssl_verify_mode') <small class="text-danger">{{ $message }}</small> @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary w-100">Crear canal</button>
                            <a href="{{ route('settings.chat.channels.emails.index') }}" class="btn btn-outline-secondary w-100 mt-2">Cancelar</a>
                        </div>

                    </div>
                </form>
            </div>

            {{-- Columna derecha --}}
            <div class="col-lg-4">

                <div class="card mb-3">
                    <div class="card-body">
                        <h6 class="fw-bold mb-1">Configuración por proveedor</h6>
                        <p class="text-muted mb-3">Selecciona el proveedor para rellenar los servidores automáticamente.</p>
                        <div class="mb-3">
                            <strong class="small">Google / Gmail</strong>
                            <ul class="small text-muted mb-0 ps-3 mt-1">
                                <li>IMAP: imap.gmail.com:993</li>
                                <li>SMTP: smtp.gmail.com:587</li>
                                <li>Requiere contraseña de aplicación</li>
                            </ul>
                        </div>
                        <hr class="my-2">
                        <div class="mb-3">
                            <strong class="small">Microsoft / Outlook</strong>
                            <ul class="small text-muted mb-0 ps-3 mt-1">
                                <li>IMAP: outlook.office365.com:993</li>
                                <li>SMTP: smtp.office365.com:587</li>
                            </ul>
                        </div>
                        <hr class="my-2">
                        <div>
                            <strong class="small">Zoho Mail</strong>
                            <ul class="small text-muted mb-0 ps-3 mt-1">
                                <li>IMAP: imap.zoho.com:993</li>
                                <li>SMTP: smtp.zoho.com:587</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-body">
                        <h6 class="fw-bold mb-1">Información importante</h6>
                        <p class="text-muted mb-3">Ten en cuenta estos aspectos al configurar el canal.</p>
                        <ul class="text-muted ps-3 mb-3">
                            <li class="mb-1">Las credenciales se <strong>cifran</strong> en la base de datos</li>
                            <li class="mb-1">Para Gmail usa una <strong>contraseña de aplicación</strong></li>
                            <li>Activa IMAP y SMTP para recibir y enviar correos</li>
                        </ul>
                        <div class="alert alert-info border-0 mb-0">
                            <small>Puedes probar la conexión desde la página de detalle del canal una vez creado.</small>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header border-bottom">
                        <h6 class="mb-0 fw-bold">Puertos estándar</h6>
                    </div>
                    <div class="card-body">
                        <code class="d-block mb-2"><strong>IMAP SSL:</strong> 993</code>
                        <code class="d-block mb-2"><strong>IMAP STARTTLS:</strong> 143</code>
                        <code class="d-block mb-2"><strong>SMTP STARTTLS:</strong> 587</code>
                        <code class="d-block"><strong>SMTP SSL:</strong> 465</code>
                    </div>
                </div>

            </div>

        </div>

    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    $('#imap_enabled').on('change', function () {
        $('#imap-settings').toggleClass('d-none', !$(this).is(':checked'));
    });

    $('#smtp_enabled').on('change', function () {
        $('#smtp-settings').toggleClass('d-none', !$(this).is(':checked'));
    });

    $('#provider').on('change', function () {
        const presets = {
            google:    { imap_address: 'imap.gmail.com',        imap_port: '993', smtp_address: 'smtp.gmail.com',     smtp_port: '587' },
            microsoft: { imap_address: 'outlook.office365.com', imap_port: '993', smtp_address: 'smtp.office365.com', smtp_port: '587' },
            zoho:      { imap_address: 'imap.zoho.com',         imap_port: '993', smtp_address: 'smtp.zoho.com',      smtp_port: '587' },
        };
        const config = presets[$(this).val()];
        if (config) {
            $.each(config, (key, value) => $('#' + key).val(value).trigger('change'));
        }
    });

    @if(session('success'))
        toastr.success('{{ session('success') }}');
    @endif
    @if(session('error'))
        toastr.error('{{ session('error') }}');
    @endif
});
</script>
@endpush
