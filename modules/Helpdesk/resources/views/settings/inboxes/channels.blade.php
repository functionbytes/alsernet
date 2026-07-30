@extends('layouts.theme')

@section('title', 'Nueva bandeja — Seleccionar canal')

@push('styles')
<style>
.hd-icon-circle-md { width: 72px; height: 72px; }
.hd-icon-xl { font-size: 1.8rem; }
.hd-icon-xxs { font-size: .7rem; flex-shrink: 0; }
</style>
@endpush

@section('content')

@include('core::components.alerts')

@php
$channelConfigs = [
    'whatsapp' => [
        'description' => 'WhatsApp Business API para atención directa con tus clientes',
        'color' => 'success',
        'features' => [
            'Cloud API oficial de Meta',
            'Plantillas de mensaje aprobadas',
            'Mensajes multimedia',
            'Estado de lectura',
            'Notificaciones proactivas',
        ],
    ],
    'email' => [
        'description' => 'Centraliza correos entrantes en tickets con IMAP/SMTP propio',
        'color' => 'info',
        'features' => [
            'IMAP/SMTP personalizados',
            'Múltiples proveedores',
            'Conversiones a ticket automáticas',
            'Respuestas rápidas',
            'Adjuntos soportados',
        ],
    ],
    'facebook' => [
        'description' => 'Atiende mensajes de Facebook Messenger desde el helpdesk',
        'color' => 'primary',
        'features' => [
            'Messenger integrado',
            'Múltiples páginas',
            'Respuestas automáticas',
            'Mensajes multimedia',
            'Botones interactivos',
        ],
    ],
    'instagram' => [
        'description' => 'Gestiona mensajes directos de Instagram Business',
        'color' => 'danger',
        'features' => [
            'Instagram DM',
            'Business account',
            'Imágenes y videos',
            'Respuestas rápidas',
            'Sincronización en tiempo real',
        ],
    ],
    'web' => [
        'description' => 'Widget de chat en vivo para tu sitio web',
        'color' => 'warning',
        'features' => [
            'Chat en tiempo real',
            'Personalización visual',
            'Formulario previo al chat',
            'Horarios de atención',
            'Integración con una línea de código',
        ],
    ],
];
@endphp

<div class="card">
    <div class="card-header p-4 border-bottom">
        <div class="d-flex align-items-center gap-3">
            <a href="{{ route('settings.helpdesk.inboxes.index') }}" class="btn btn-sm btn-light">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h5 class="mb-1 fw-bold">Seleccionar canal</h5>
                <p class="small mb-0 text-muted">Elige el tipo de canal para crear una nueva bandeja</p>
            </div>
        </div>
    </div>

    <div class="card-body p-4">
        <div class="row g-4">
            @foreach($channelLabels as $type => $label)
                @php $config = $channelConfigs[$type] ?? [] @endphp
                <div class="col-lg-4 col-md-6">
                    <div class="card h-100 channel-card position-relative overflow-hidden">
                        <div class="card-body p-4 d-flex flex-column">
                            <div class="text-center mb-4">
                                <div class="hd-icon-circle-md d-inline-flex align-items-center justify-content-center rounded-circle bg-light mb-3">
                                    <i class="{{ $channelIcons[$type] ?? 'fas fa-inbox' }} hd-icon-xl"></i>
                                </div>
                                <h5 class="fw-bold mb-1">{{ $label }}</h5>
                                <p class="text-muted small mb-0">{{ $config['description'] ?? '' }}</p>
                            </div>

                            @if(!empty($config['features']))
                                <ul class="list-unstyled mb-4 flex-grow-1">
                                    @foreach($config['features'] as $feature)
                                        <li class="mb-2 d-flex align-items-start gap-2">
                                            <i class="fas fa-check hd-icon-xxs text-success mt-1"></i>
                                            <span class="small">{{ $feature }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif

                            <div class="d-grid mt-auto">
                                <a href="{{ route('settings.helpdesk.inboxes.create', ['channel' => $type]) }}"
                                   class="btn btn-primary">
                                    Configurar canal
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
.channel-card {
    border: 1px solid rgba(0,0,0,.1);
    transition: box-shadow .2s, transform .2s;
}
.channel-card:hover {
    box-shadow: 0 .5rem 1rem rgba(0,0,0,.1);
    transform: translateY(-2px);
}
</style>
@endpush
