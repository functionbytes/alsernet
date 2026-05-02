@extends('layouts.theme')

@section('title', isset($account) ? 'Editar cuenta: ' . $account->name : 'Nueva cuenta de email')

@section('page_header')
    @include('core::components.card', ['title' => isset($account) ? 'Editar cuenta de email' : 'Nueva cuenta de email'])
@endsection

@section('content')

    <div class="row g-3">

        {{-- Form --}}
        <div class="col-12 col-lg-8">
            <div class="card">
                <form action="{{ isset($account) ? route('settings.helpdesk.email-accounts.update', $account->id) : route('settings.helpdesk.email-accounts.store') }}"
                      method="POST">
                    @csrf
                    @if(isset($account))
                        @method('PUT')
                    @endif

                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">{{ isset($account) ? 'Editar: ' . $account->name : 'Nueva cuenta de email' }}</h5>
                        <small class="text-muted">{{ isset($account) ? 'Modifica la configuracion de la cuenta de email' : 'Conecta una cuenta IMAP/SMTP al helpdesk' }}</small>
                    </div>

                    <div class="card-body">
                        @include('core::components.alerts')

                        <h6 class="fw-semibold mb-1 border-bottom pb-2">Informacion de la cuenta</h6>
                        <p class="text-muted small mb-3">Nombre de identificacion y direccion de email</p>
                        <div class="row g-3 mb-4">

                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Nombre <span class="text-danger">*</span></label>
                                    <input type="text" name="name"
                                           class="form-control @error('name') is-invalid @enderror"
                                           value="{{ old('name', $account->name ?? '') }}"
                                           placeholder="Ej: Soporte principal"
                                           required>
                                    @error('name')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Email <span class="text-danger">*</span></label>
                                    <input type="email" name="email"
                                           class="form-control @error('email') is-invalid @enderror"
                                           value="{{ old('email', $account->email ?? '') }}"
                                           placeholder="soporte@empresa.com"
                                           required>
                                    @error('email')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12 col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Estado</label>
                                    <select name="is_active" class="form-select">
                                        <option value="1" {{ old('is_active', $account->is_active ?? true) ? 'selected' : '' }}>Activa</option>
                                        <option value="0" {{ !old('is_active', $account->is_active ?? true) ? 'selected' : '' }}>Inactiva</option>
                                    </select>
                                </div>
                            </div>

                        </div>

                        <h6 class="fw-semibold mb-1 border-bottom pb-2">Configuracion IMAP (recepcion)</h6>
                        <p class="text-muted small mb-3">Servidor IMAP para recibir correos entrantes como tickets</p>
                        <div class="row g-3 mb-4">

                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Servidor IMAP</label>
                                    <input type="text" name="imap_host"
                                           class="form-control @error('imap_host') is-invalid @enderror"
                                           value="{{ old('imap_host', $account->imap_host ?? '') }}"
                                           placeholder="imap.gmail.com">
                                    @error('imap_host')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12 col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Puerto</label>
                                    <input type="number" name="imap_port"
                                           class="form-control @error('imap_port') is-invalid @enderror"
                                           value="{{ old('imap_port', $account->imap_port ?? 993) }}"
                                           min="1" max="65535">
                                    @error('imap_port')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12 col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Encriptacion</label>
                                    <select name="imap_encryption" class="form-select">
                                        <option value="ssl" {{ old('imap_encryption', $account->imap_encryption ?? 'ssl') === 'ssl' ? 'selected' : '' }}>SSL</option>
                                        <option value="tls" {{ old('imap_encryption', $account->imap_encryption ?? 'ssl') === 'tls' ? 'selected' : '' }}>TLS</option>
                                        <option value="none" {{ old('imap_encryption', $account->imap_encryption ?? 'ssl') === 'none' ? 'selected' : '' }}>Ninguna</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Usuario IMAP</label>
                                    <input type="text" name="imap_username"
                                           class="form-control @error('imap_username') is-invalid @enderror"
                                           value="{{ old('imap_username', $account->imap_username ?? '') }}"
                                           placeholder="usuario@empresa.com">
                                    @error('imap_username')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Contrasena IMAP</label>
                                    <input type="password" name="imap_password"
                                           class="form-control @error('imap_password') is-invalid @enderror"
                                           placeholder="{{ isset($account) && $account->hasImap() ? '(mantener actual — dejar vacio)' : '' }}"
                                           autocomplete="new-password">
                                    @error('imap_password')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12 col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Carpeta IMAP</label>
                                    <input type="text" name="imap_folder"
                                           class="form-control"
                                           value="{{ old('imap_folder', $account->imap_folder ?? 'INBOX') }}"
                                           placeholder="INBOX">
                                </div>
                            </div>

                        </div>

                        <h6 class="fw-semibold mb-1 border-bottom pb-2">Configuracion SMTP (envio)</h6>
                        <p class="text-muted small mb-3">Servidor SMTP para enviar correos de respuesta</p>
                        <div class="row g-3">

                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Servidor SMTP</label>
                                    <input type="text" name="smtp_host"
                                           class="form-control @error('smtp_host') is-invalid @enderror"
                                           value="{{ old('smtp_host', $account->smtp_host ?? '') }}"
                                           placeholder="smtp.gmail.com">
                                    @error('smtp_host')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12 col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Puerto SMTP</label>
                                    <input type="number" name="smtp_port"
                                           class="form-control @error('smtp_port') is-invalid @enderror"
                                           value="{{ old('smtp_port', $account->smtp_port ?? 587) }}"
                                           min="1" max="65535">
                                    @error('smtp_port')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12 col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Encriptacion SMTP</label>
                                    <select name="smtp_encryption" class="form-select">
                                        <option value="tls" {{ old('smtp_encryption', $account->smtp_encryption ?? 'tls') === 'tls' ? 'selected' : '' }}>TLS</option>
                                        <option value="ssl" {{ old('smtp_encryption', $account->smtp_encryption ?? 'tls') === 'ssl' ? 'selected' : '' }}>SSL</option>
                                        <option value="none" {{ old('smtp_encryption', $account->smtp_encryption ?? 'tls') === 'none' ? 'selected' : '' }}>Ninguna</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Nombre del remitente</label>
                                    <input type="text" name="smtp_from_name"
                                           class="form-control"
                                           value="{{ old('smtp_from_name', $account->smtp_from_name ?? '') }}"
                                           placeholder="Soporte Tecnico">
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Usuario SMTP</label>
                                    <input type="text" name="smtp_username"
                                           class="form-control @error('smtp_username') is-invalid @enderror"
                                           value="{{ old('smtp_username', $account->smtp_username ?? '') }}"
                                           placeholder="usuario@empresa.com">
                                    @error('smtp_username')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Contrasena SMTP</label>
                                    <input type="password" name="smtp_password"
                                           class="form-control @error('smtp_password') is-invalid @enderror"
                                           placeholder="{{ isset($account) && $account->hasSmtp() ? '(mantener actual — dejar vacio)' : '' }}"
                                           autocomplete="new-password">
                                    @error('smtp_password')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-1">
                            {{ isset($account) ? 'Guardar cambios' : 'Crear cuenta' }}
                        </button>
                        <a href="{{ route('settings.helpdesk.email-accounts.index') }}" class="btn btn-light w-100">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Help panel --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-3">Sobre las cuentas de email</h6>
                    <p class="card-text text-muted">
                        Cada cuenta de email conectada puede recibir correos entrantes como tickets y responder directamente desde el helpdesk.
                    </p>
                </div>
                <hr class="my-0">
                <div class="card-body">
                    <h6 class="card-title mb-3">Configuracion recomendada</h6>
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2 text-muted small"><span class="fw-semibold">Gmail:</span> IMAP 993 SSL, SMTP 587 TLS</li>
                        <li class="mb-2 text-muted small"><span class="fw-semibold">Outlook:</span> IMAP 993 SSL, SMTP 587 TLS</li>
                        <li class="text-muted small">Las contrasenas se almacenan encriptadas</li>
                    </ul>
                </div>
                @if(isset($account))
                    <hr class="my-0">
                    <div class="card-body">
                        <h6 class="card-title mb-3">Informacion del registro</h6>
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2 text-muted small">
                                <span class="fw-semibold">Creada:</span> {{ $account->created_at->format('d/m/Y H:i') }}
                            </li>
                            <li class="mb-2 text-muted small">
                                <span class="fw-semibold">Actualizada:</span> {{ $account->updated_at->format('d/m/Y H:i') }}
                            </li>
                            @if($account->last_sync_at)
                                <li class="text-muted small">
                                    <span class="fw-semibold">Ultima sync:</span> {{ $account->last_sync_at->format('d/m/Y H:i') }}
                                </li>
                            @endif
                        </ul>
                    </div>
                @endif
            </div>
        </div>

    </div>

@endsection
