@extends('layouts.theme')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header bg-info-subtle">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="card-title mb-1">{{ $email->email }}</h5>
                    <p class="card-subtitle mb-0 text-muted">Detalles del canal de correo electrónico</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('settings.chat.channels.emails.edit', $email) }}" class="btn btn-primary">
                        <i class="fas fa-edit"></i> Editar
                    </a>
                    <a href="{{ route('settings.chat.channels.emails.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body">
            @include('core::components.alerts')

            <div class="row g-4">
                <!-- General Information -->
                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-header bg-light">
                            <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Información general</h6>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-5 text-muted">Email:</div>
                                <div class="col-7"><strong>{{ $email->email }}</strong></div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-5 text-muted">Proveedor:</div>
                                <div class="col-7">
                                    @php
                                        $providerBadges = [
                                            'google' => 'bg-info-subtle text-info',
                                            'microsoft' => 'bg-primary-subtle text-primary',
                                            'zoho' => 'bg-warning-subtle text-warning',
                                            'custom' => 'bg-secondary-subtle text-secondary',
                                        ];
                                        $providerClass = $providerBadges[$email->provider ?? 'custom'];
                                    @endphp
                                    <span class="badge {{ $providerClass }}">
                                        {{ ucfirst($email->provider ?? 'custom') }}
                                    </span>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-5 text-muted">Email de reenvío:</div>
                                <div class="col-7">
                                    <div class="input-group input-group-sm">
                                        <input type="text"
                                               class="form-control"
                                               id="forward-email"
                                               value="{{ $email->forward_to_email }}"
                                               readonly>
                                        <button class="btn btn-secondary"
                                                type="button"
                                                onclick="copyForwardEmail()"
                                                title="Copiar">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </div>
                                    <small class="text-muted">Usa esta dirección para reenviar correos a esta bandeja</small>
                                </div>
                            </div>
                            @if($email->inbox)
                                <div class="row">
                                    <div class="col-5 text-muted">Nombre bandeja:</div>
                                    <div class="col-7">{{ $email->inbox->name }}</div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- IMAP Configuration -->
                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center">
                            <h6 class="mb-0"><i class="fas fa-download me-2"></i>Configuración IMAP</h6>
                            @if($email->imap_enabled)
                                <span class="badge bg-success-subtle text-success">Activo</span>
                            @else
                                <span class="badge bg-info-subtle text-info">Inactivo</span>
                            @endif
                        </div>
                        <div class="card-body">
                            @if($email->imap_enabled)
                                <div class="row mb-3">
                                    <div class="col-5 text-muted">Servidor:</div>
                                    <div class="col-7">{{ $email->imap_address }}</div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-5 text-muted">Puerto:</div>
                                    <div class="col-7">{{ $email->imap_port }}</div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-5 text-muted">Usuario:</div>
                                    <div class="col-7">{{ $email->imap_login }}</div>
                                </div>
                                <div class="row">
                                    <div class="col-5 text-muted">SSL/TLS:</div>
                                    <div class="col-7">
                                        @if($email->imap_enable_ssl)
                                            <span class="badge bg-success-subtle text-success">Habilitado</span>
                                        @else
                                            <span class="badge bg-info-subtle text-info">Deshabilitado</span>
                                        @endif
                                    </div>
                                </div>
                            @else
                                <p class="text-muted mb-0">
                                    <i class="fas fa-info-circle me-2"></i>
                                    IMAP está deshabilitado. Edita este canal para habilitar la recepción de correos.
                                </p>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- SMTP Configuration -->
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center">
                            <h6 class="mb-0"><i class="fas fa-upload me-2"></i>Configuración SMTP</h6>
                            <div>
                                @if($email->smtp_enabled)
                                    <span class="badge bg-success-subtle text-success">Activo</span>
                                @else
                                    <span class="badge bg-info-subtle text-info">Inactivo</span>
                                @endif
                            </div>
                        </div>
                        <div class="card-body">
                            @if($email->smtp_enabled)
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="row mb-3">
                                            <div class="col-5 text-muted">Servidor:</div>
                                            <div class="col-7">{{ $email->smtp_address }}</div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-5 text-muted">Puerto:</div>
                                            <div class="col-7">{{ $email->smtp_port }}</div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-5 text-muted">Usuario:</div>
                                            <div class="col-7">{{ $email->smtp_login }}</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        @if($email->smtp_domain)
                                            <div class="row mb-3">
                                                <div class="col-5 text-muted">Dominio:</div>
                                                <div class="col-7">{{ $email->smtp_domain }}</div>
                                            </div>
                                        @endif
                                        <div class="row mb-3">
                                            <div class="col-5 text-muted">Autenticación:</div>
                                            <div class="col-7">{{ strtoupper($email->smtp_authentication ?? 'login') }}</div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-5 text-muted">Encriptación:</div>
                                            <div class="col-7">
                                                @if($email->smtp_enable_ssl_tls)
                                                    <span class="badge bg-success-subtle text-success">SSL/TLS</span>
                                                @elseif($email->smtp_enable_starttls_auto)
                                                    <span class="badge bg-success-subtle text-success">STARTTLS</span>
                                                @else
                                                    <span class="badge bg-info-subtle text-info">Ninguna</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-3">
                                    <form action="{{ route('settings.chat.channels.emails.test-smtp', $email) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-vial"></i> Probar conexión SMTP
                                        </button>
                                    </form>
                                </div>
                            @else
                                <p class="text-muted mb-0">
                                    <i class="fas fa-info-circle me-2"></i>
                                    SMTP está deshabilitado. Edita este canal para habilitar el envío de correos.
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function copyForwardEmail() {
    const emailField = document.getElementById('forward-email');
    emailField.select();
    emailField.setSelectionRange(0, 99999);

    navigator.clipboard.writeText(emailField.value).then(function() {
        toastr.success('Email de reenvío copiado al portapapeles');
    }, function() {
        const tempInput = document.createElement('input');
        tempInput.value = emailField.value;
        document.body.appendChild(tempInput);
        tempInput.select();
        document.execCommand('copy');
        document.body.removeChild(tempInput);
        toastr.success('Email de reenvío copiado al portapapeles');
    });
}
</script>
@endpush
@endsection
