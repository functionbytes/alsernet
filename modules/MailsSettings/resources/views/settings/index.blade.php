@extends('layouts.theme')

@section('content')

    @include('core::components.card', ['title' => 'Configuración de Email'])

        @if ($message = session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fa fa-check-circle me-2"></i> {{ $message }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if ($message = session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fa fa-circle-exclamation me-2"></i> {{ $message }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="row g-3">

            <!-- Opción 1: Correo Saliente (SMTP) -->
            <div class="col-md-6">
                <div class="card h-100 shadow-sm">
                    <div class="card-header border-bottom py-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <h6 class="mb-0 fw-bold text-dark">
                                <i class="fa fa-paper-plane me-2"></i> Correo saliente
                            </h6>
                            <span class="badge bg-primary">SMTP</span>
                        </div>
                    </div>

                    <div class="card-body pb-0">
                        <p class="text-muted mb-3">
                            Configure el servidor SMTP para <strong>enviar correos electrónicos salientes</strong> desde la aplicación (notificaciones, alertas, respuestas automáticas, etc.).
                        </p>

                        <div class="alert alert-info alert-sm py-2 px-3 mb-3" role="alert">
                            <strong>¿Qué configuras aquí?</strong>
                        </div>

                        <ul class="list-unstyled ms-3 mb-4">
                            <li class="mb-2">
                                <strong>Servidor SMTP</strong>
                                <br>
                                <small class="text-muted">Configurar host, puerto, encriptación y credenciales del servidor de correo saliente.</small>
                            </li>
                            <li class="mb-2">
                                <strong>Remitente predeterminado</strong>
                                <br>
                                <small class="text-muted">Definir la dirección de email y nombre que aparecerán como remitente en todos los correos.</small>
                            </li>
                            <li class="mb-2">
                                <strong>Pruebas de conexión</strong>
                                <br>
                                <small class="text-muted">Verificar que la configuración funcione correctamente enviando emails de prueba.</small>
                            </li>
                        </ul>

                        @if(isset($outgoingSettings['mail_host']))
                            <div class="bg-light-secondary p-3 rounded mb-3">
                                <small class="text-muted">
                                    <strong>Configuración actual:</strong><br>
                                    Servidor: {{ $outgoingSettings['mail_host'] ?? 'N/A' }}:{{ $outgoingSettings['mail_port'] ?? 'N/A' }}<br>
                                    Remitente: {{ $outgoingSettings['mail_from_address'] ?? 'N/A' }}
                                </small>
                            </div>
                        @endif

                        <p class="text-muted small border-top pt-3">
                            Esta configuración se aplica a todos los correos electrónicos salientes del sistema.
                        </p>
                    </div>

                    <div class="card-footer border-top">
                        <a href="{{ route('settings.outgoing-email.index') }}" class="btn btn-primary w-100">
                            <i class="fa fa-arrow-right me-2"></i> Configurar correo saliente
                        </a>
                    </div>
                </div>
            </div>

            <!-- Opción 2: Correo Entrante (Handlers) -->
            <div class="col-md-6">
                <div class="card h-100 shadow-sm">
                    <div class="card-header border-bottom py-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <h6 class="mb-0 fw-bold text-dark">
                                <i class="fa fa-inbox me-2"></i> Correo entrante
                            </h6>
                            <span class="badge bg-light text-black">Handlers</span>
                        </div>
                    </div>

                    <div class="card-body pb-0">
                        <p class="text-muted mb-3">
                            Configure los <strong>manejadores de correo entrante</strong> para convertir emails recibidos en tickets y respuestas automáticamente.
                        </p>

                        <div class="alert alert-info alert-sm py-2 px-3 mb-3" role="alert">
                            <strong>¿Qué configuras aquí?</strong>
                        </div>

                        <ul class="list-unstyled ms-3 mb-4">
                            <li class="mb-2">
                                <strong>IMAP</strong>
                                <br>
                                <small class="text-muted">Conectar cuentas de correo existentes usando el protocolo IMAP.</small>
                            </li>
                            <li class="mb-2">
                                <strong>Pipe Script</strong>
                                <br>
                                <small class="text-muted">Recibir correos desde cPanel o paneles de control de hosting.</small>
                            </li>
                            <li class="mb-2">
                                <strong>REST API</strong>
                                <br>
                                <small class="text-muted">Integración con aplicaciones externas mediante API REST.</small>
                            </li>
                            <li class="mb-2">
                                <strong>Gmail API & Mailgun</strong>
                                <br>
                                <small class="text-muted">Conectar directamente con servicios de correo especializados.</small>
                            </li>
                        </ul>

                        @if(isset($incomingSettings['imap']['connections']) && count($incomingSettings['imap']['connections']) > 0)
                            <div class="bg-light-info p-3 rounded mb-3">
                                <small class="text-info">
                                    <strong>Conexiones activas:</strong><br>
                                    {{ count($incomingSettings['imap']['connections']) }} conexión(es) IMAP configurada(s)
                                </small>
                            </div>
                        @endif

                        <p class="text-muted small border-top pt-3">
                            Puede habilitar múltiples manejadores simultáneamente según sus necesidades.
                        </p>
                    </div>

                    <div class="card-footer border-top">
                        <a href="{{ route('settings.incoming-email.index') }}" class="btn btn-primary w-100">
                            <i class="fa fa-arrow-right me-2"></i> Configurar correo entrante
                        </a>
                    </div>
                </div>
            </div>

        </div>


@endsection
