@extends('layouts.theme')

@section('title', 'Canales de comunicación')

@section('content')

@include('core::components.alerts')

<div class="card">
    <!-- Header Section -->
    <div class="card-header p-4 border-bottom border-light">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-1 fw-bold">Canales de comunicación</h5>
                <p class="small mb-0 text-muted">Conecta y gestiona todos los canales desde donde tus clientes pueden contactarte</p>
            </div>
        </div>
    </div>

    <!-- Channels Grid -->
    <div class="card-body">
        @php
            $channelConfigs = [
                [
                    'name' => 'Chat web',
                    'description' => 'Widget de chat en vivo para tu sitio web',
                    'icon' => 'fas fa-comments',
                    'color' => 'primary',
                    'route' => route('settings.chat.channels.webs.index'),
                    'configured' => true,
                    'features' => [
                        'Chat en tiempo real',
                        'Personalización avanzada',
                        'Historial de conversaciones',
                        'Respuestas automáticas',
                        'Integración sencilla'
                    ],
                ],
                [
                    'name' => 'Email',
                    'description' => 'Gestiona correos desde Gmail, Outlook o IMAP/SMTP',
                    'icon' => 'fas fa-envelope',
                    'color' => 'info',
                    'route' => route('settings.chat.channels.emails.index'),
                    'configured' => true,
                    'features' => [
                        'IMAP/SMTP',
                        'Plantillas de email',
                        'Seguimiento de lectura',
                        'Adjuntos automáticos',
                        'Respuestas rápidas'
                    ],
                ],
                [
                    'name' => 'WhatsApp',
                    'description' => 'WhatsApp Business API para comunicación instantánea',
                    'icon' => 'fab fa-whatsapp',
                    'color' => 'success',
                    'route' => route('settings.chat.channels.whatsapps.index'),
                    'configured' => isset($channels['whatsapp']) && $channels['whatsapp'],
                    'features' => [
                        'Cloud API',
                        'Plantillas aprobadas',
                        'Mensajes multimedia',
                        'Estado de lectura',
                        'Envío masivo'
                    ],
                ],
                [
                    'name' => 'Facebook Messenger',
                    'description' => 'Responde mensajes de Facebook desde tu chat',
                    'icon' => 'fab fa-facebook-messenger',
                    'color' => 'primary',
                    'route' => route('settings.chat.channels.facebook-pages.index'),
                    'configured' => isset($channels['facebook']) && $channels['facebook'],
                    'features' => [
                        'Messenger integrado',
                        'Múltiples páginas',
                        'Respuestas automáticas',
                        'Mensajes multimedia',
                        'Botones interactivos'
                    ],
                ],
                [
                    'name' => 'Instagram',
                    'description' => 'Gestiona mensajes directos de Instagram Business',
                    'icon' => 'fab fa-instagram',
                    'color' => 'danger',
                    'route' => route('settings.chat.channels.instagrams.index'),
                    'configured' => isset($channels['instagram']) && $channels['instagram'],
                    'features' => [
                        'Instagram DM',
                        'Business account',
                        'Imágenes y videos',
                        'Stories interactivos',
                        'Respuestas rápidas'
                    ],
                ],
                [
                    'name' => 'SMS',
                    'description' => 'Envía y recibe SMS via Twilio o Bandwidth',
                    'icon' => 'fas fa-sms',
                    'color' => 'warning',
                    'route' => route('settings.chat.channels.sms.index'),
                    'configured' => isset($channels['sms']) && $channels['sms'],
                    'features' => [
                        'Twilio',
                        'Mensajes masivos',
                        'Notificaciones',
                        'Confirmaciones',
                        'Campañas SMS'
                    ],
                ],
                [
                    'name' => 'API',
                    'description' => 'Integraciones personalizadas con REST API',
                    'icon' => 'fas fa-code',
                    'color' => 'dark',
                    'route' => route('settings.chat.channels.api.index'),
                    'configured' => true,
                    'features' => [
                        'REST API',
                        'Webhooks',
                        'Documentación completa',
                        'Rate limiting',
                        'Autenticación JWT'
                    ],
                ],
            ];
        @endphp

        <div class="row g-4">
            @foreach($channelConfigs as $channel)
                <div class="col-lg-4 card p-10 border ">
                    @if($channel['configured'])
                        <span class="badge bg-success position-absolute top-0 end-0 m-3">
                                <i class="fas fa-check-circle me-1"></i> Activo
                            </span>
                    @else
                        <span class="badge bg-light-subtle text-dark position-absolute top-0 end-0 m-3">
                                Disponible
                            </span>
                    @endif

                    <div class="text-center mb-3">
                        <!-- Icon outside card -->
                        <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-light"
                             style="width: 80px; height: 80px;">
                            <i class="{{ $channel['icon'] }} text-dark" style="font-size: 2rem;"></i>
                        </div>
                    </div>

                    <div class="card position-relative overflow-hidden">
                        <!-- Status Badge -->


                        <div class="card-body p-4">
                            <!-- Title and Description -->
                            <div class="text-center mb-4">
                                <h5 class="fw-bold mb-2">{{ $channel['name'] }}</h5>
                                <p class="text-muted small mb-0">{{ $channel['description'] }}</p>
                            </div>

                            <!-- Features List -->
                            <ul class="list-unstyled mb-4">
                                @foreach($channel['features'] as $feature)
                                    <li class="mb-2 d-flex align-items-start">
                                        <i class="fas fa-check text-success me-2 mt-1" style="font-size: 0.75rem;"></i>
                                        <span class="small">{{ $feature }}</span>
                                    </li>
                                @endforeach
                            </ul>

                            <!-- Action Button -->
                            <div class="d-grid">
                                <a href="{{ $channel['route'] }}" class="btn btn-primary">
                                    @if($channel['configured'])
                                        Gestionar canal
                                    @else
                                        Configurar ahora
                                    @endif
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
.stat-card {
    border: none !important;
}

.card {
    border: 1px solid rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
}

.card:hover {
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
    transform: translateY(-2px);
}
</style>
@endpush
