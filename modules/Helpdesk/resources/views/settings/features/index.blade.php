@extends('layouts.theme')

@section('title', 'Funcionalidades')

@section('page_header')
    @include('core::components.card', ['title' => 'Funcionalidades'])
@endsection

@section('content')

@php
$sections = [
    'barra' => [
        'title' => 'Barra de acciones del hilo',
        'desc'  => 'Botones que aparecen en la barra superior de la conversación.',
        'items' => [
            'feature_email_enabled'    => ['label' => 'Enviar email (botón + pestaña Emails)'],
            'feature_tickets_enabled'  => ['label' => 'Crear ticket'],
            'feature_schedule_enabled' => ['label' => 'Agendar mensaje'],
            'feature_snooze_enabled'   => ['label' => 'Posponer conversación'],
            'feature_assign_enabled'   => ['label' => 'Asignar agente'],
            'feature_tags_enabled'     => ['label' => 'Etiquetar'],
            'feature_search_enabled'   => ['label' => 'Buscar en conversación'],
        ],
    ],
    'mas' => [
        'title' => 'Menú "Más" (dropdown)',
        'desc'  => 'Opciones que aparecen en el menú desplegable de acciones adicionales.',
        'items' => [
            'feature_note_enabled'          => ['label' => 'Añadir nota interna'],
            'feature_csat_enabled'          => ['label' => 'Enviar encuesta CSAT'],
            'feature_merge_enabled'         => ['label' => 'Fusionar conversación'],
            'feature_move_team_enabled'     => ['label' => 'Mover a equipo'],
            'feature_spam_enabled'          => ['label' => 'Marcar como spam'],
            'feature_block_contact_enabled' => ['label' => 'Bloquear contacto'],
            'feature_delete_conv_enabled'   => ['label' => 'Eliminar conversación'],
            'feature_forward_enabled'       => ['label' => 'Reenviar'],
            'feature_preview_conv_enabled'  => ['label' => 'Conversaciones anteriores'],
        ],
    ],
    'panel' => [
        'title' => 'Panel derecho — botones',
        'desc'  => 'Botones de acción rápida que aparecen en el panel lateral derecho.',
        'items' => [
            'feature_rp_email_enabled'        => ['label' => 'Botón Email'],
            'feature_rp_schedule_enabled'     => ['label' => 'Botón Agendar'],
            'feature_rp_note_enabled'         => ['label' => 'Botón Nota interna'],
            'feature_rp_stats_enabled'        => ['label' => 'Estadísticas (LTV · Conversaciones · Últ. visita)'],
            'feature_rp_status_enabled'       => ['label' => 'Estado de la conversación (Estado, Prioridad, Agente)'],
            'feature_rp_tags_section_enabled' => ['label' => 'Sección Etiquetas'],
            'feature_rp_integrations_enabled' => ['label' => 'Sección Integraciones'],
        ],
    ],
    'tabs' => [
        'title' => 'Panel derecho — pestañas',
        'desc'  => 'Pestañas que aparecen en el panel lateral de información del cliente.',
        'items' => [
            'feature_tab_general_enabled'     => ['label' => 'General (información de contacto)'],
            'feature_tab_carts_enabled'       => ['label' => 'Carritos (requiere módulo PrestaShop)'],
            'feature_tab_files_enabled'       => ['label' => 'Archivos'],
            'feature_tab_tickets_enabled'     => ['label' => 'Tickets'],
            'feature_tab_document_enabled'    => ['label' => 'Documentación (requiere módulo Document)'],
            'feature_tab_previous_enabled'    => ['label' => 'Anteriores (conversaciones previas)'],
            'feature_tab_activity_enabled'    => ['label' => 'Actividad'],
            'feature_tab_technology_enabled'  => ['label' => 'Tecnología (sesión widget)'],
            'feature_tab_customer360_enabled' => ['label' => 'Cliente 360'],
            'feature_tab_assist_enabled'      => ['label' => 'Asistir / Pantalla (live assistance)'],
        ],
    ],
    'composer' => [
        'title' => 'Barra de redacción',
        'desc'  => 'Pestañas y herramientas disponibles en el área de redacción de mensajes.',
        'items' => [
            'feature_composer_hsm_enabled'     => ['label' => 'Plantillas HSM (WhatsApp)'],
            'feature_composer_note_enabled'    => ['label' => 'Nota interna (pestaña redactor)'],
            'feature_composer_attach_enabled'  => ['label' => 'Adjuntar archivos'],
            'feature_composer_emoji_enabled'   => ['label' => 'Emoji'],
            'feature_composer_mention_enabled' => ['label' => 'Mencionar agente (@)'],
            'feature_composer_canned_enabled'  => ['label' => 'Respuesta rápida (⚡)'],
            'feature_composer_record_enabled'  => ['label' => 'Grabar audio'],
            'feature_composer_ai_enabled'      => ['label' => 'Sugerencia IA (✦)'],
        ],
    ],
];
@endphp

    <div class="row g-3">

        {{-- Form --}}
        <div class="col-12 col-lg-8">
            <div class="card">
                <form action="{{ route('settings.helpdesk.features.update') }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Funcionalidades de conversaciones</h5>
                        <small class="text-muted">Habilita o deshabilita las acciones disponibles en la vista de conversaciones</small>
                    </div>

                    <div class="card-body">
                        @include('core::components.alerts')

                        @foreach($sections as $sectionKey => $section)

                        <h6 class="fw-semibold mb-1">{{ $section['title'] }}</h6>
                        <p class="text-muted small mb-3">{{ $section['desc'] }}</p>
                        <div class="row g-3 {{ !$loop->last ? 'mb-4' : '' }}">
                            @foreach($section['items'] as $field => $meta)
                                @include('helpdesk::settings.features._toggle', [
                                    'field' => $field,
                                    'label' => $meta['label'],
                                    'default' => true,
                                ])
                            @endforeach
                        </div>

                        @endforeach
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-1">Guardar configuración</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Help panel --}}
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">Sobre esta configuración</h6>
                </div>
                <div class="card-body">
                    <p class="card-text text-muted">
                        Estas opciones controlan qué acciones ven los agentes en la vista de conversaciones: la
                        barra superior, el menú "Más", el panel derecho con sus pestañas y la barra de redacción.
                    </p>
                </div>
            </div>
            <div class="card">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">Buenas prácticas</h6>
                </div>
                <div class="card-body">
                    <ul class="text-muted mb-0">
                        <li class="mb-2">Desactiva solo lo que el equipo no use para simplificar la interfaz</li>
                        <li class="mb-2">Algunas pestañas requieren que el módulo relacionado esté activo (PrestaShop, Document)</li>
                        <li class="mb-0">Los cambios aplican a todos los agentes de inmediato</li>
                    </ul>
                </div>
            </div>
        </div>

    </div>

@endsection

@push('scripts')
<script src="{{ asset('vendor/helpdesk/settings/settings-common.js') }}?v={{ @filemtime(public_path('vendor/helpdesk/settings/settings-common.js')) }}" defer></script>
@endpush
