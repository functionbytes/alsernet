@extends('layouts.theme')

@section('title', 'Configuración de email saliente')

@section('page_header')
    @include('core::components.card', ['title' => 'Email saliente'])
@endsection

@section('content')

    @include('core::components.alerts')

    <div class="row g-4 align-items-start">

        {{-- Formulario --}}
        <div class="col-lg-8">
            <div class="card">
                <form id="formEmail" method="POST" action="{{ route('settings.outgoing-email.update') }}">
                    @csrf

                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Configuración SMTP</h5>
                        <small class="text-muted">Parámetros del servidor de correo saliente</small>
                    </div>

                    <div class="card-body">

                        <div class="row g-3">

                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold">Tipo de mailer</label>
                                <select class="form-select select2 @error('mail_mailer') is-invalid @enderror" id="mailMailer" name="mail_mailer" required>
                                    <option value="">Seleccionar...</option>
                                    <option value="smtp" {{ old('mail_mailer', $settings['mail_mailer']) === 'smtp' ? 'selected' : '' }}>SMTP</option>
                                    <option value="mailgun" {{ old('mail_mailer', $settings['mail_mailer']) === 'mailgun' ? 'selected' : '' }}>Mailgun</option>
                                    <option value="sendmail" {{ old('mail_mailer', $settings['mail_mailer']) === 'sendmail' ? 'selected' : '' }}>Sendmail</option>
                                </select>
                                @error('mail_mailer')<small class="text-danger">{{ $message }}</small>@enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold">Servidor SMTP</label>
                                <input type="text" class="form-control @error('mail_host') is-invalid @enderror"
                                       name="mail_host" value="{{ old('mail_host', $settings['mail_host']) }}"
                                       required placeholder="smtp.gmail.com">
                                @error('mail_host')<small class="text-danger">{{ $message }}</small>@enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold">Puerto SMTP</label>
                                <input type="number" class="form-control @error('mail_port') is-invalid @enderror"
                                       name="mail_port" value="{{ old('mail_port', $settings['mail_port']) }}"
                                       required placeholder="587">
                                @error('mail_port')<small class="text-danger">{{ $message }}</small>@enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold">Encriptación</label>
                                <select class="form-select select2 @error('mail_encryption') is-invalid @enderror" name="mail_encryption" required>
                                    <option value="">Sin encriptación</option>
                                    <option value="tls" {{ old('mail_encryption', $settings['mail_encryption']) === 'tls' ? 'selected' : '' }}>TLS</option>
                                    <option value="ssl" {{ old('mail_encryption', $settings['mail_encryption']) === 'ssl' ? 'selected' : '' }}>SSL</option>
                                </select>
                                @error('mail_encryption')<small class="text-danger">{{ $message }}</small>@enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold">Usuario SMTP</label>
                                <input type="text" class="form-control @error('mail_username') is-invalid @enderror"
                                       name="mail_username" value="{{ old('mail_username', $settings['mail_username']) }}"
                                       placeholder="tu@email.com">
                                @error('mail_username')<small class="text-danger">{{ $message }}</small>@enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold">Contraseña SMTP</label>
                                <input type="password" class="form-control @error('mail_password') is-invalid @enderror"
                                       name="mail_password" value="{{ old('mail_password', $settings['mail_password']) }}"
                                       placeholder="Dejar en blanco para mantener la contraseña actual" autocomplete="new-password">
                                @error('mail_password')<small class="text-danger">{{ $message }}</small>@enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold">Dirección del remitente</label>
                                <input type="email" class="form-control @error('mail_from_address') is-invalid @enderror"
                                       name="mail_from_address" value="{{ old('mail_from_address', $settings['mail_from_address']) }}"
                                       required placeholder="noreply@ejemplo.com">
                                @error('mail_from_address')<small class="text-danger">{{ $message }}</small>@enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold">Nombre del remitente</label>
                                <input type="text" class="form-control @error('mail_from_name') is-invalid @enderror"
                                       name="mail_from_name" value="{{ old('mail_from_name', $settings['mail_from_name']) }}"
                                       required placeholder="Mi sistema">
                                @error('mail_from_name')<small class="text-danger">{{ $message }}</small>@enderror
                            </div>

                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-1">
                            Guardar configuración
                        </button>
                        <a href="{{ route('settings.outgoing-email.index') }}" class="btn btn-light w-100">
                            Cancelar
                        </a>
                    </div>

                </form>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="col-lg-4">

            <div class="card mb-3">
                <div class="card-header p-3 bg-white border-bottom">
                    <h5 class="mb-0 fw-bold">¿Qué es SMTP?</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-0">SMTP (Simple Mail Transfer Protocol) es el protocolo estándar para el envío de correos electrónicos. Estos parámetros permiten que el sistema envíe correos usando tu proveedor de email.</p>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header p-3 bg-white border-bottom">
                    <h5 class="mb-0 fw-bold">Proveedores comunes</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h6 class="fw-semibold mb-2">Gmail</h6>
                        <ul class="text-muted  mb-0">
                            <li>Servidor: <code>smtp.gmail.com</code></li>
                            <li>Puerto: <code>587</code> (TLS) / <code>465</code> (SSL)</li>
                            <li>Requiere contraseña de aplicación</li>
                        </ul>
                    </div>
                    <hr class="my-3">
                    <div class="mb-3">
                        <h6 class="fw-semibold mb-2">Outlook / Office 365</h6>
                        <ul class="text-muted  mb-0">
                            <li>Servidor: <code>smtp.office365.com</code></li>
                            <li>Puerto: <code>587</code> (TLS)</li>
                        </ul>
                    </div>
                    <hr class="my-3">
                    <div>
                        <h6 class="fw-semibold mb-2">Mailgun</h6>
                        <ul class="text-muted  mb-0">
                            <li>Servidor: <code>smtp.mailgun.org</code></li>
                            <li>Puerto: <code>587</code> o <code>25</code></li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header p-3 bg-white border-bottom">
                    <h5 class="mb-0 fw-bold">Encriptación</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted fw-semibold">TLS</span>
                            <span class="badge bg-success-subtle text-success">Recomendado</span>
                        </div>
                        <small class="text-muted">Usa puerto 587. Estándar moderno y seguro.</small>
                    </div>
                    <hr class="my-3">
                    <div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted fw-semibold">SSL</span>
                            <span class="badge bg-warning-subtle text-warning">Legacy</span>
                        </div>
                        <small class="text-muted">Usa puerto 465. Versión anterior, aún compatible.</small>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header p-3 bg-white border-bottom">
                    <h5 class="mb-0 fw-bold">Seguridad</h5>
                </div>
                <div class="card-body">
                    <ul class="text-muted  mb-0">
                        <li class="mb-2">La contraseña SMTP se almacena en el archivo <code>.env</code> del servidor.</li>
                        <li class="mb-2">Para Gmail, usa una <strong>contraseña de aplicación</strong>, no tu contraseña de cuenta.</li>
                        <li>Nunca compartas estas credenciales.</li>
                    </ul>
                </div>
            </div>

        </div>

    </div>

@endsection
