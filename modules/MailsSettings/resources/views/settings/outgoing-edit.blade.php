@extends('layouts.theme')

@section('content')


            <div class="card">

                <form id="formEmail" method="POST" action="{{ route('settings.email.update') }}">

                    {{ csrf_field() }}
                    @method('PUT')

                    <div class="card-body">
                        <div class="row">
                            <h5 class="mb-2">Configuración de Email/SMTP</h5>
                            <p class="card-subtitle mb-3 ">
                                Este espacio está diseñado para que configures los parámetros de tu servidor SMTP. Estos datos serán utilizados para enviar correos electrónicos desde la aplicación.
                            </p>
                        </div>

                        <div class="row">

                            <!-- Mailer Type -->
                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Tipo de Mailer</label>
                                    <select class="form-control select2 @error('mail_mailer') is-invalid @enderror " id="mailMailer" name="mail_mailer" required>
                                        <option value="">Seleccionar...</option>
                                        <option value="smtp" {{ old('mail_mailer', $settings['mail_mailer']) === 'smtp' ? 'selected' : '' }}>SMTP</option>
                                        <option value="mailgun" {{ old('mail_mailer', $settings['mail_mailer']) === 'mailgun' ? 'selected' : '' }}>Mailgun</option>
                                        <option value="sendmail" {{ old('mail_mailer', $settings['mail_mailer']) === 'sendmail' ? 'selected' : '' }}>Sendmail</option>
                                    </select>
                                    @error('mail_mailer')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>

                            <!-- SMTP Host -->
                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Servidor SMTP</label>
                                    <input type="text" class="form-control @error('mail_host') is-invalid @enderror" id="mailHost" name="mail_host" value="{{ old('mail_host', $settings['mail_host']) }}" required placeholder="smtp.gmail.com">
                                    @error('mail_host')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>

                            <!-- SMTP Port -->
                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Puerto SMTP</label>
                                    <input type="number" class="form-control @error('mail_port') is-invalid @enderror" id="mailPort" name="mail_port" value="{{ old('mail_port', $settings['mail_port']) }}" required placeholder="587">
                                    @error('mail_port')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>

                            <!-- SMTP Encryption -->
                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Encriptación</label>
                                    <select class="form-control @error('mail_encryption') is-invalid @enderror" id="mailEncryption" name="mail_encryption" required>
                                        <option value="">Sin encriptación</option>
                                        <option value="tls" {{ old('mail_encryption', $settings['mail_encryption']) === 'tls' ? 'selected' : '' }}>TLS</option>
                                        <option value="ssl" {{ old('mail_encryption', $settings['mail_encryption']) === 'ssl' ? 'selected' : '' }}>SSL</option>
                                    </select>
                                    @error('mail_encryption')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>

                            <!-- SMTP Username -->
                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Usuario SMTP</label>
                                    <input type="text" class="form-control @error('mail_username') is-invalid @enderror" id="mailUsername" name="mail_username" value="{{ old('mail_username', $settings['mail_username']) }}" placeholder="tu@email.com">
                                    @error('mail_username')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>

                            <!-- SMTP Password -->
                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Contraseña SMTP</label>
                                    <input type="password" class="form-control @error('mail_password') is-invalid @enderror" id="mailPassword" name="mail_password" value="{{ old('mail_password', $settings['mail_password']) }}" placeholder="Dejar vacío si no se requiere">
                                    @error('mail_password')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>

                            <!-- From Address -->
                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Dirección de Remitente</label>
                                    <input type="email" class="form-control @error('mail_from_address') is-invalid @enderror" id="mailFromAddress" name="mail_from_address" value="{{ old('mail_from_address', $settings['mail_from_address']) }}" required placeholder="noreply@ejemplo.com">
                                    @error('mail_from_address')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>

                            <!-- From Name -->
                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Nombre del Remitente</label>
                                    <input type="text" class="form-control @error('mail_from_name') is-invalid @enderror" id="mailFromName" name="mail_from_name" value="{{ old('mail_from_name', $settings['mail_from_name']) }}" required placeholder="Alsernet">
                                    @error('mail_from_name')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>

                        </div>

                    </div>


                    <div class="card-footer">
                            <button type="submit" class="btn btn-info px-4 waves-effect waves-light mt-2 w-100">
                                Guardar
                            </button>
                            <a href="{{ route('settings.email.index') }}" class="btn btn-secondary px-4 waves-effect waves-light mt-2 w-100">
                                Volver
                            </a>
                    </div>

                </form>
            </div>


@endsection
