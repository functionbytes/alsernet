@extends('layouts.theme')

@section('title', 'Funcionalidades')

@section('content')

@include('core::components.alerts')

@php
$sections = [
    'barra' => [
        'title' => 'Barra de acciones del hilo',
        'desc'  => 'Botones que aparecen en la barra superior de la conversación.',
        'items' => [
            'feature_email_enabled'    => ['label' => 'Enviar email',           'icon' => 'fas fa-envelope'],
            'feature_tickets_enabled'  => ['label' => 'Crear ticket',           'icon' => 'fas fa-ticket'],
            'feature_schedule_enabled' => ['label' => 'Agendar mensaje',        'icon' => 'fas fa-calendar-clock'],
            'feature_snooze_enabled'   => ['label' => 'Posponer conversación',  'icon' => 'fas fa-clock'],
            'feature_assign_enabled'   => ['label' => 'Asignar agente',         'icon' => 'fas fa-user-check'],
            'feature_tags_enabled'     => ['label' => 'Etiquetar',              'icon' => 'fas fa-tag'],
            'feature_search_enabled'   => ['label' => 'Buscar en conversación', 'icon' => 'fas fa-magnifying-glass'],
        ],
    ],
    'mas' => [
        'title' => 'Menú "Más" (dropdown)',
        'desc'  => 'Opciones que aparecen en el menú desplegable de acciones adicionales.',
        'items' => [
            'feature_note_enabled'          => ['label' => 'Añadir nota interna',       'icon' => 'fas fa-note-sticky'],
            'feature_csat_enabled'          => ['label' => 'Enviar encuesta CSAT',      'icon' => 'fas fa-star'],
            'feature_merge_enabled'         => ['label' => 'Fusionar conversación',     'icon' => 'fas fa-code-merge'],
            'feature_move_team_enabled'     => ['label' => 'Mover a equipo',            'icon' => 'fas fa-people-arrows'],
            'feature_spam_enabled'          => ['label' => 'Marcar como spam',          'icon' => 'fas fa-ban'],
            'feature_block_contact_enabled' => ['label' => 'Bloquear contacto',         'icon' => 'fas fa-user-slash'],
            'feature_delete_conv_enabled'   => ['label' => 'Eliminar conversación',     'icon' => 'far fa-trash-can'],
            'feature_forward_enabled'       => ['label' => 'Reenviar',                  'icon' => 'fas fa-forward'],
            'feature_preview_conv_enabled'  => ['label' => 'Conversaciones anteriores', 'icon' => 'fas fa-clock-rotate-left'],
        ],
    ],
    'panel' => [
        'title' => 'Panel derecho — botones',
        'desc'  => 'Botones de acción rápida que aparecen en el panel lateral derecho.',
        'items' => [
            'feature_rp_email_enabled'        => ['label' => 'Botón Email',                                        'icon' => 'fas fa-envelope'],
            'feature_rp_schedule_enabled'     => ['label' => 'Botón Agendar',                                      'icon' => 'fas fa-calendar-plus'],
            'feature_rp_note_enabled'         => ['label' => 'Botón Nota interna',                                 'icon' => 'fas fa-note-sticky'],
            'feature_rp_stats_enabled'        => ['label' => 'Estadísticas (LTV · Conversaciones · Últ. visita)', 'icon' => 'fas fa-chart-bar'],
            'feature_rp_status_enabled'       => ['label' => 'Estado de la conversación (Estado, Prioridad, Agente)', 'icon' => 'fas fa-circle-info'],
            'feature_rp_tags_section_enabled' => ['label' => 'Sección Etiquetas',                                  'icon' => 'fas fa-tags'],
            'feature_rp_integrations_enabled' => ['label' => 'Sección Integraciones',                              'icon' => 'fas fa-plug'],
        ],
    ],
    'tabs' => [
        'title' => 'Panel derecho — pestañas',
        'desc'  => 'Pestañas que aparecen en el panel lateral de información del cliente.',
        'items' => [
            'feature_tab_general_enabled'     => ['label' => 'General (información de contacto)', 'icon' => 'far fa-circle-user'],
            'feature_tab_files_enabled'       => ['label' => 'Archivos',                          'icon' => 'far fa-folder'],
            'feature_tab_tickets_enabled'     => ['label' => 'Tickets',                           'icon' => 'fas fa-ticket'],
            'feature_tab_previous_enabled'    => ['label' => 'Anteriores (conversaciones previas)','icon' => 'fas fa-clock-rotate-left'],
            'feature_tab_activity_enabled'    => ['label' => 'Actividad',                         'icon' => 'fas fa-bolt-lightning'],
            'feature_tab_technology_enabled'  => ['label' => 'Tecnología (sesión widget)',         'icon' => 'fas fa-laptop'],
            'feature_tab_orders_enabled'      => ['label' => 'Pedidos (Ecommerce)',                'icon' => 'fas fa-bag-shopping'],
            'feature_tab_customer360_enabled' => ['label' => 'Cliente 360',                        'icon' => 'fas fa-chart-pie'],
            'feature_tab_assist_enabled'      => ['label' => 'Asistir / Pantalla (live assistance)','icon' => 'fas fa-eye'],
        ],
    ],
    'composer' => [
        'title' => 'Barra de redacción',
        'desc'  => 'Pestañas y herramientas disponibles en el área de redacción de mensajes.',
        'items' => [
            'feature_composer_hsm_enabled'     => ['label' => 'Plantillas HSM (WhatsApp)',        'icon' => 'fab fa-whatsapp'],
            'feature_composer_note_enabled'    => ['label' => 'Nota interna (pestaña redactor)',  'icon' => 'fas fa-lock'],
            'feature_composer_attach_enabled'  => ['label' => 'Adjuntar archivos',                'icon' => 'fas fa-paperclip'],
            'feature_composer_emoji_enabled'   => ['label' => 'Emoji',                            'icon' => 'far fa-face-smile'],
            'feature_composer_mention_enabled' => ['label' => 'Mencionar agente (@)',             'icon' => 'fas fa-at'],
            'feature_composer_canned_enabled'  => ['label' => 'Respuesta rápida (⚡)',            'icon' => 'fas fa-bolt'],
            'feature_composer_record_enabled'  => ['label' => 'Grabar audio',                     'icon' => 'fas fa-microphone'],
            'feature_composer_ai_enabled'      => ['label' => 'Sugerencia IA (✦)',                'icon' => 'fas fa-sparkles'],
        ],
    ],
];
@endphp

<div class="card">
    <div class="card-header p-4 border-bottom border-light">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-1 fw-bold">Funcionalidades de conversaciones</h5>
                <p class="small mb-0 text-muted">Habilita o deshabilita las acciones disponibles en la vista de conversaciones</p>
            </div>
            <div class="form-check form-switch mb-0 ms-4">
                <input class="form-check-input fs-5" type="checkbox" id="master-toggle" role="switch">
                <label class="visually-hidden" for="master-toggle">Activar todo</label>
            </div>
        </div>
    </div>

    <div class="card-body">
        <form method="POST" action="{{ route('settings.helpdesk.features.update') }}">
            @csrf
            @method('PUT')

            @foreach($sections as $sectionKey => $section)

            <div class="d-flex justify-content-between align-items-start mb-2 {{ !$loop->first ? 'mt-1' : '' }}">
                <div>
                    <h6 class="fw-semibold mb-0">{{ $section['title'] }}</h6>
                    <p class="text-muted small mb-0">{{ $section['desc'] }}</p>
                </div>
                <div class="d-flex align-items-center gap-2 ms-3 flex-shrink-0">
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input section-toggle"
                               type="checkbox"
                               id="toggle-{{ $sectionKey }}"
                               data-target="section-{{ $sectionKey }}"
                               role="switch">
                        <label class="visually-hidden" for="toggle-{{ $sectionKey }}">Activar sección</label>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-4" id="section-{{ $sectionKey }}">
                @foreach($section['items'] as $field => $meta)
                <div class="col-md-6">
                    <div class="form-check form-switch">
                        <input class="form-check-input feature-toggle" type="checkbox"
                               id="{{ $field }}" name="{{ $field }}" value="1"
                               {{ ($settings[$field] ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="{{ $field }}">
                            <i class="{{ $meta['icon'] }} me-1 text-muted"></i> {{ $meta['label'] }}
                        </label>
                    </div>
                </div>
                @endforeach
            </div>

            @if(!$loop->last)<hr>@endif

            @endforeach

            <div class="d-flex justify-content-end mt-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i> Guardar configuracion
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
$(function () {

    function syncSectionToggle(sectionId) {
        var $inputs = $('#' + sectionId).find('.feature-toggle');
        var total   = $inputs.length;
        var checked = $inputs.filter(':checked').length;
        var $t = $('[data-target="' + sectionId + '"]');
        $t.prop('checked', checked === total);
        $t[0].indeterminate = checked > 0 && checked < total;
    }

    function syncMasterToggle() {
        var $all    = $('.feature-toggle');
        var total   = $all.length;
        var checked = $all.filter(':checked').length;
        var $m = $('#master-toggle');
        $m.prop('checked', checked === total);
        $m[0].indeterminate = checked > 0 && checked < total;
    }

    // Init on load
    $('.section-toggle').each(function () {
        syncSectionToggle($(this).data('target'));
    });
    syncMasterToggle();

    // Section toggle clicked
    $(document).on('change', '.section-toggle', function () {
        var target  = $(this).data('target');
        var checked = $(this).is(':checked');
        $('#' + target).find('.feature-toggle').prop('checked', checked);
        this.indeterminate = false;
        syncMasterToggle();
    });

    // Master toggle clicked
    $('#master-toggle').on('change', function () {
        var checked = $(this).is(':checked');
        $('.feature-toggle').prop('checked', checked);
        $('.section-toggle').prop('checked', checked).each(function () {
            this.indeterminate = false;
        });
    });

    // Individual feature checkbox changed
    $(document).on('change', '.feature-toggle', function () {
        var sectionId = $(this).closest('[id^="section-"]').attr('id');
        if (sectionId) syncSectionToggle(sectionId);
        syncMasterToggle();
    });

    @if(session('success'))
        toastr.success('{{ session('success') }}', 'Éxito');
    @endif
    @if(session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif
});
</script>
@endpush
