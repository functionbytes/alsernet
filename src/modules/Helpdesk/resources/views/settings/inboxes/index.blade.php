@extends('layouts.theme')

@section('title', 'Bandejas')

@push('styles')
<style>
.hd-icon-circle-lg { width: 80px; height: 80px; }
.hd-icon-2rem { font-size: 2rem; }
.hd-icon-xs { font-size: 0.75rem; }
</style>
@endpush

@section('content')

@include('core::components.alerts')

<div class="card">
    <div class="card-header p-4 border-bottom border-light">
        <div>
            <h5 class="mb-1 fw-bold">Bandejas de entrada</h5>
            <p class="small mb-0 text-muted">Conecta y gestiona los canales desde donde tus clientes pueden contactarte</p>
        </div>
    </div>

    <div class="card-body">
        <div class="row g-4">
            @foreach($channelLabels as $type => $label)
                @php
                    $count = $inboxCounts[$type] ?? null;
                    $total = $count ? $count->total : 0;
                    $active = $count ? $count->active : 0;
                    $icon = $channelIcons[$type] ?? 'fas fa-inbox';

                    $descriptions = [
                        'whatsapp'  => 'WhatsApp Business API para atención directa con tus clientes',
                        'email'     => 'Centraliza correos entrantes en tickets con IMAP/SMTP propio',
                        'facebook'  => 'Atiende mensajes de Facebook Messenger desde el helpdesk',
                        'instagram' => 'Gestiona mensajes directos de Instagram Business',
                        'web'       => 'Widget de chat en vivo para tu sitio web',
                    ];

                    $features = [
                        'whatsapp'  => ['Cloud API oficial de Meta', 'Plantillas de mensaje aprobadas', 'Mensajes multimedia', 'Estado de lectura', 'Notificaciones proactivas'],
                        'email'     => ['IMAP/SMTP personalizados', 'Múltiples proveedores', 'Conversiones a ticket automáticas', 'Respuestas rápidas', 'Adjuntos soportados'],
                        'facebook'  => ['Messenger integrado', 'Múltiples páginas', 'Respuestas automáticas', 'Mensajes multimedia', 'Botones interactivos'],
                        'instagram' => ['Instagram DM', 'Business account', 'Imágenes y videos', 'Respuestas rápidas', 'Sincronización en tiempo real'],
                        'web'       => ['Chat en tiempo real', 'Personalización visual', 'Formulario previo al chat', 'Horarios de atención', 'Integración con una línea de código'],
                    ];
                @endphp

                <div class="col-lg-4">
                    <div class="text-center mb-3">
                        <div class="hd-icon-circle-lg d-inline-flex align-items-center justify-content-center rounded-circle bg-light">
                            <i class="{{ $icon }} hd-icon-2rem text-dark"></i>
                        </div>
                    </div>

                    <div class="card inbox-channel-card position-relative overflow-hidden">
                        @if($active > 0)
                            <span class="badge bg-success position-absolute top-0 end-0 m-3">
                                <i class="fas fa-check-circle me-1"></i> {{ $active }} activa{{ $active !== 1 ? 's' : '' }}
                            </span>
                        @else
                            <span class="badge bg-light-subtle text-dark position-absolute top-0 end-0 m-3">
                                Sin configurar
                            </span>
                        @endif

                        <div class="card-body p-4">
                            <div class="text-center mb-4">
                                <h5 class="fw-bold mb-2">{{ $label }}</h5>
                                <p class="text-muted small mb-0">{{ $descriptions[$type] ?? '' }}</p>
                            </div>

                            <ul class="list-unstyled mb-4">
                                @foreach($features[$type] ?? [] as $feature)
                                    <li class="mb-2 d-flex align-items-start">
                                        <i class="fas fa-check hd-icon-xs text-success me-2 mt-1"></i>
                                        <span class="small">{{ $feature }}</span>
                                    </li>
                                @endforeach
                            </ul>

                            <div class="d-grid gap-2">
                                <a href="{{ route('settings.helpdesk.inboxes.channel', $type) }}"
                                   class="btn btn-primary">
                                    {{ $total > 0 ? 'Gestionar bandejas' : 'Configurar canal' }}
                                </a>
                                <a href="{{ route('settings.helpdesk.inboxes.create', ['channel' => $type]) }}"
                                   class="btn btn-outline-secondary btn-sm">
                                    <i class="fas fa-plus me-1"></i> Añadir bandeja
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
.inbox-channel-card {
    transition: box-shadow 0.2s ease, transform 0.2s ease;
}
.inbox-channel-card:hover {
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
    transform: translateY(-2px);
}
</style>
@endpush
