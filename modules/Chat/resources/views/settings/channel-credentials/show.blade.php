@extends('layouts.theme')

@section('content')
@include('core::components.alerts')

<div class="card bg-info-subtle mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h5 class="card-title mb-2">Configuración de {{ ucfirst($channel) }}</h5>
                <p class="card-text text-muted mb-0">
                    Guía de configuración y estado de credenciales
                </p>
            </div>
            <a href="{{ route('settings.chat.channel-credentials.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-body">
                <h6 class="fw-bold mb-3 border-bottom pb-2">Estado de configuración</h6>
                @if($isConfigured)
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <strong>Configurado correctamente</strong>
                        <p class="mb-0 mt-2 small">Este canal está correctamente configurado y listo para usar.</p>
                    </div>
                @else
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Configuración incompleta</strong>
                        <p class="mb-0 mt-2 small">
                            Credenciales faltantes: <code>{{ implode(', ', $missing) }}</code>
                        </p>
                    </div>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h6 class="fw-bold mb-3 border-bottom pb-2">Instrucciones de configuración</h6>
                @switch($channel)
                    @case('facebook')
                        <h6 class="mt-3">Paso 1: crear una aplicación de Facebook</h6>
                        <ol class="small">
                            <li>Ve a <a href="https://developers.facebook.com/apps/" target="_blank">Facebook Developers</a></li>
                            <li>Haz clic en "Create App"</li>
                            <li>Elige "Consumer" como tipo de aplicación</li>
                            <li>Completa los detalles de la aplicación</li>
                        </ol>

                        <h6 class="mt-4">Paso 2: obtener credenciales</h6>
                        <ol class="small">
                            <li>En App Dashboard, ve a Settings → Basic</li>
                            <li>Copia tu <strong>App ID</strong> a <code>FACEBOOK_APP_ID</code></li>
                            <li>Copia tu <strong>App Secret</strong> a <code>FACEBOOK_APP_SECRET</code></li>
                        </ol>

                        <h6 class="mt-4">Paso 3: configurar producto Messenger</h6>
                        <ol class="small">
                            <li>Ve a tu App Dashboard</li>
                            <li>Añade el producto "Messenger"</li>
                            <li>Configura tu webhook URL: <code>{{ url('/webhooks/facebook') }}</code></li>
                            <li>Establece <code>FACEBOOK_VERIFY_TOKEN</code> a cualquier cadena segura</li>
                        </ol>

                        <div class="alert alert-info mt-4">
                            <strong>Documentación:</strong>
                            <a href="https://developers.facebook.com/docs/messenger-platform/quickstart" target="_blank">
                                Facebook Messenger Platform
                            </a>
                        </div>
                        @break

                    @case('instagram')
                        <h6 class="mt-3">Paso 1: usar tu aplicación de Facebook</h6>
                        <p class="small">Instagram usa la misma aplicación de Meta que Facebook. Asegúrate de tener Facebook configurado primero.</p>

                        <h6 class="mt-4">Paso 2: configurar producto Instagram</h6>
                        <ol class="small">
                            <li>Ve a tu App Dashboard</li>
                            <li>Añade el producto "Instagram"</li>
                            <li>En Instagram Messaging, haz clic en "Add or remove pages"</li>
                            <li>Conecta tu cuenta de Instagram Business</li>
                        </ol>

                        <div class="alert alert-info mt-4">
                            <strong>Documentación:</strong>
                            <a href="https://developers.facebook.com/docs/instagram-api/getting-started" target="_blank">
                                Instagram Graph API
                            </a>
                        </div>
                        @break

                    @case('whatsapp')
                        <h6 class="mt-3">Paso 1: elegir tu proveedor de WhatsApp</h6>
                        <p class="small">Soportamos dos proveedores:</p>
                        <ul class="small">
                            <li><strong>WhatsApp Cloud API</strong> (Meta) - Recomendado</li>
                            <li><strong>360Dialog</strong> - Proveedor alternativo</li>
                        </ul>

                        <h6 class="mt-4">Paso 2: obtener credenciales</h6>
                        <p class="small">Dependiendo de tu proveedor:</p>
                        <ul class="small">
                            <li><strong>Cloud API:</strong> Ve a <a href="https://developers.facebook.com/" target="_blank">Meta for Developers</a></li>
                            <li><strong>360Dialog:</strong> Ve a <a href="https://www.360dialog.com/" target="_blank">360Dialog Dashboard</a></li>
                        </ul>

                        <h6 class="mt-4">Paso 3: configurar variables de entorno</h6>
                        <ol class="small">
                            <li>Añade <code>WHATSAPP_VERIFY_TOKEN</code> - cualquier cadena segura</li>
                            <li>Añade <code>WHATSAPP_APP_SECRET</code> - desde tu panel de proveedor</li>
                        </ol>

                        <div class="alert alert-info mt-4">
                            <strong>Documentación:</strong>
                            <a href="https://developers.facebook.com/docs/whatsapp/cloud-api/get-started" target="_blank">
                                WhatsApp Cloud API
                            </a>
                        </div>
                        @break

                    @case('email')
                        <h6 class="mt-3">Paso 1: elegir tu proveedor de email</h6>
                        <p class="small">Soportamos Gmail y Outlook con OAuth 2.0</p>

                        <h6 class="mt-4">Configuración de Gmail</h6>
                        <ol class="small">
                            <li>Ve a <a href="https://console.cloud.google.com/" target="_blank">Google Cloud Console</a></li>
                            <li>Crea un nuevo proyecto</li>
                            <li>Habilita "Gmail API"</li>
                            <li>Crea credenciales OAuth 2.0 (Desktop app)</li>
                            <li>Añade a <code>GMAIL_CLIENT_ID</code> y <code>GMAIL_CLIENT_SECRET</code></li>
                        </ol>

                        <h6 class="mt-4">Configuración de Outlook</h6>
                        <ol class="small">
                            <li>Ve a <a href="https://portal.azure.com/" target="_blank">Azure Portal</a></li>
                            <li>Registra una nueva aplicación</li>
                            <li>Crea credenciales de cliente</li>
                            <li>Añade a <code>OUTLOOK_CLIENT_ID</code> y <code>OUTLOOK_CLIENT_SECRET</code></li>
                        </ol>

                        <div class="alert alert-info mt-4">
                            <strong>Documentación:</strong>
                            <a href="https://support.google.com/accounts/answer/185833" target="_blank">
                                Gmail Security
                            </a>
                        </div>
                        @break

                    @default
                        <p class="text-muted small">No hay instrucciones de configuración disponibles para este canal.</p>
                @endswitch
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card mb-4 @if($isConfigured) border-success @else border-warning @endif">
            <div class="card-header @if($isConfigured) bg-success-subtle @else bg-warning-subtle @endif">
                <h6 class="mb-0">
                    @if($isConfigured)
                        <i class="fas fa-check-circle text-success"></i> Listo para usar
                    @else
                        <i class="fas fa-exclamation-triangle text-warning"></i> Acción requerida
                    @endif
                </h6>
            </div>
            <div class="card-body">
                @if($isConfigured)
                    <p class="text-success mb-2 small">
                        <i class="fas fa-check-circle"></i>
                        Todas las credenciales están configuradas correctamente.
                    </p>
                    <p class="small text-muted mb-0">
                        Ahora puedes crear canales {{ $channelStatus['name'] ?? ucfirst($channel) }} en la configuración de tu bandeja de entrada.
                    </p>
                @else
                    <p class="mb-3 small">
                        <strong>Credenciales faltantes:</strong>
                    </p>
                    <ul class="small mb-3">
                        @foreach($missing as $credential)
                            <li><code>{{ $credential }}</code></li>
                        @endforeach
                    </ul>
                    <p class="small text-muted mb-0">
                        Sigue las instrucciones de configuración para añadir estas credenciales.
                    </p>
                @endif
            </div>
        </div>

        <div class="card bg-light">
            <div class="card-body">
                <h6 class="mb-3"><i class="fas fa-shield-alt"></i> Consejos de seguridad</h6>
                <ul class="small mb-0">
                    <li>Nunca compartas tu App Secret</li>
                    <li>Usa tokens únicos y seguros</li>
                    <li>Rota las credenciales periódicamente</li>
                    <li>Usa configuraciones específicas por entorno</li>
                    <li>Monitorea la actividad del webhook</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
