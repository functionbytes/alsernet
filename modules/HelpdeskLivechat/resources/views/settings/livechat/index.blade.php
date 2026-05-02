@extends('layouts.theme')

@section('title', 'Configuración de LiveChat')

@push('css')
<style>
    /* Fix: some theme CSS sets visibility:collapse on .collapse, breaking accordion content */
    #widgetAccordion .accordion-collapse.show,
    #widgetAccordion .accordion-collapse.show .accordion-body,
    .tab-content > .tab-pane.show { visibility: visible !important; }
    .lc-settings-header {
        background: linear-gradient(135deg, #b10100 0%, #7b0000 100%);
        color: white;
        padding: 1.5rem 2rem;
        border-radius: 12px;
        margin-bottom: 1.5rem;
    }
    .lc-settings-header h4 { font-weight: 700; margin: 0 0 .25rem; }
    .lc-settings-header p  { opacity: .9; margin: 0; font-size: .9rem; }

    .lc-section-title {
        font-size: .8rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .5px;
        color: #555;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: .5rem;
    }
    .lc-section-title i { color: #b10100; }

    .lc-preview-wrap {
        position: sticky;
        top: 20px;
    }

    .lc-phone-frame {
        background: #1a1a1a;
        border-radius: 36px;
        padding: 12px;
        box-shadow: 0 12px 40px rgba(0,0,0,.35);
        width: 340px;
        margin: 0 auto;
    }
    .lc-phone-notch {
        background: #333;
        height: 24px;
        border-radius: 12px;
        margin: 0 auto 10px;
        width: 100px;
    }
    .lc-phone-screen {
        border-radius: 24px;
        overflow: hidden;
        height: 560px;
        background: #fff;
        position: relative;
    }
    .lc-phone-screen iframe {
        width: 100%;
        height: 100%;
        border: none;
        display: block;
    }
    .lc-phone-home-bar {
        background: #333;
        height: 4px;
        border-radius: 2px;
        width: 100px;
        margin: 10px auto 0;
    }

    .preview-screen-tabs .nav-link {
        font-size: .8rem;
        padding: .35rem .75rem;
        color: #666;
        border-radius: 6px;
    }
    .preview-screen-tabs .nav-link.active {
        background: #b10100;
        color: #fff;
    }

    /* Main settings tabs */
    .lc-nav-tabs .nav-link {
        color: #555;
        border-bottom: 3px solid transparent;
        border-top: none;
        border-left: none;
        border-right: none;
        border-radius: 0;
        padding: .6rem 1rem;
        font-weight: 500;
    }
    .lc-nav-tabs .nav-link:hover { color: #b10100; }
    .lc-nav-tabs .nav-link.active {
        color: #b10100;
        border-bottom-color: #b10100;
        background: transparent;
    }

    /* Timeout toggles */
    .timeout-row {
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: .75rem;
        cursor: pointer;
        transition: border-color .15s;
    }
    .timeout-row:hover { border-color: #b10100; }
    .timeout-row.active { border-color: #b10100; background: #fff8f8; }

    /* Code block */
    .lc-code-block {
        background: #1e1e2e;
        color: #cdd6f4;
        border-radius: 8px;
        padding: 1rem 1.25rem;
        font-family: 'Courier New', monospace;
        font-size: .85rem;
        position: relative;
    }
    .lc-code-copy {
        position: absolute;
        top: .5rem;
        right: .5rem;
    }

    /* Toggle list items */
    .lc-toggle-item {
        display: flex;
        align-items: flex-start;
        gap: .75rem;
        padding: .6rem .75rem;
        border-radius: 8px;
        border: 1px solid #eee;
        background: #fafafa;
        margin-bottom: .5rem;
    }
</style>
@endpush

@section('content')

<div class="container-fluid">

    {{-- Breadcrumb --}}
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('core.dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('manager.helpdesk.tickets.index') }}">Helpdesk</a></li>
            <li class="breadcrumb-item active">LiveChat</li>
        </ol>
    </nav>

    {{-- Header --}}
    <div class="lc-settings-header">
        <div class="d-flex align-items-start justify-content-between">
            <div>
                <h4><i class="fas fa-comments me-2"></i>Configuración de LiveChat</h4>
                <p>Personaliza el widget de chat en vivo y sus comportamientos</p>
            </div>
            <button type="submit" form="livechatForm" class="btn btn-light fw-semibold" id="saveBtn">
                <i class="fas fa-save me-2"></i>Guardar cambios
            </button>
        </div>
    </div>

    @if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
        <ul class="mb-0 ps-3">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    {{-- Quick access cards --}}
    <div class="row g-3 mb-3">
        @can('engagement.triggers.view')
        <div class="col-md-4">
            <a href="{{ route('settings.engagement.triggers.page') }}" class="card text-decoration-none h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="bg-danger bg-opacity-10 rounded-3 p-3">
                        <i class="fas fa-bolt text-danger fs-4"></i>
                    </div>
                    <div>
                        <div class="fw-semibold">Reglas de activación</div>
                        <small class="text-muted">Abrir chat, banners, redirects según comportamiento</small>
                    </div>
                    <i class="fas fa-chevron-right text-muted ms-auto"></i>
                </div>
            </a>
        </div>
        @endcan
        @can('engagement.personalizations.view')
        <div class="col-md-4">
            <a href="{{ route('settings.engagement.personalizations.page') }}" class="card text-decoration-none h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="bg-primary bg-opacity-10 rounded-3 p-3">
                        <i class="fas fa-paint-brush text-primary fs-4"></i>
                    </div>
                    <div>
                        <div class="fw-semibold">Personalización DOM</div>
                        <small class="text-muted">Modificar textos, atributos y clases según segmento</small>
                    </div>
                    <i class="fas fa-chevron-right text-muted ms-auto"></i>
                </div>
            </a>
        </div>
        @endcan
        @can('engagement.platforms.view')
        <div class="col-md-4">
            <a href="{{ route('settings.engagement.platforms.page') }}" class="card text-decoration-none h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="bg-success bg-opacity-10 rounded-3 p-3">
                        <i class="fas fa-plug text-success fs-4"></i>
                    </div>
                    <div>
                        <div class="fw-semibold">Integraciones</div>
                        <small class="text-muted">PrestaShop, Shopify, WooCommerce, custom</small>
                    </div>
                    <i class="fas fa-chevron-right text-muted ms-auto"></i>
                </div>
            </a>
        </div>
        @endcan
        @can('engagement.automation.view')
        <div class="col-md-4">
            <a href="{{ route('settings.engagement.automation.page') }}" class="card text-decoration-none h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="bg-warning bg-opacity-10 rounded-3 p-3">
                        <i class="fas fa-robot text-warning fs-4"></i>
                    </div>
                    <div>
                        <div class="fw-semibold">Automation</div>
                        <small class="text-muted">Chatbot flows con condiciones y acciones</small>
                    </div>
                    <i class="fas fa-chevron-right text-muted ms-auto"></i>
                </div>
            </a>
        </div>
        @endcan
        @can('engagement.goals.view')
        <div class="col-md-4">
            <a href="{{ route('settings.engagement.goals.page') }}" class="card text-decoration-none h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="bg-info bg-opacity-10 rounded-3 p-3">
                        <i class="fas fa-bullseye text-info fs-4"></i>
                    </div>
                    <div>
                        <div class="fw-semibold">Objetivos</div>
                        <small class="text-muted">Conversion goals y funnels</small>
                    </div>
                    <i class="fas fa-chevron-right text-muted ms-auto"></i>
                </div>
            </a>
        </div>
        @endcan
        @can('engagement.platforms.view')
        <div class="col-md-4">
            <a href="{{ route('settings.engagement.webhook-logs.page') }}" class="card text-decoration-none h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="bg-secondary bg-opacity-10 rounded-3 p-3">
                        <i class="fas fa-list-check text-secondary fs-4"></i>
                    </div>
                    <div>
                        <div class="fw-semibold">Webhook logs</div>
                        <small class="text-muted">Auditoría y reintento manual</small>
                    </div>
                    <i class="fas fa-chevron-right text-muted ms-auto"></i>
                </div>
            </a>
        </div>
        @endcan
        @can('engagement.manage')
        <div class="col-md-4">
            <a href="{{ route('settings.engagement.audit-logs.page') }}" class="card text-decoration-none h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="bg-dark bg-opacity-10 rounded-3 p-3">
                        <i class="fas fa-clipboard-list text-dark fs-4"></i>
                    </div>
                    <div>
                        <div class="fw-semibold">Audit log</div>
                        <small class="text-muted">Quién hizo qué y cuándo</small>
                    </div>
                    <i class="fas fa-chevron-right text-muted ms-auto"></i>
                </div>
            </a>
        </div>
        @endcan
    </div>

    <div class="row g-3 align-items-start">

        {{-- ── Left: Form ── --}}
        <div class="col-xl-7 col-lg-6">

            {{-- Settings tabs --}}
            <ul class="nav lc-nav-tabs border-bottom mb-3" id="settingsTabs" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-widget" type="button">
                        <i class="fas fa-puzzle-piece me-1"></i>Widget
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-timeouts" type="button">
                        <i class="fas fa-clock me-1"></i>Tiempos
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-install" type="button">
                        <i class="fas fa-code me-1"></i>Instalar
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-security" type="button">
                        <i class="fas fa-shield-halved me-1"></i>Seguridad
                    </button>
                </li>
            </ul>

            <form method="POST" action="{{ route('settings.helpdesk-livechat.update') }}" id="livechatForm">
                @csrf
                @method('PUT')

                <div class="tab-content">

                    {{-- ═══════════════════════════════════════════ --}}
                    {{-- TAB: WIDGET                                  --}}
                    {{-- ═══════════════════════════════════════════ --}}
                    <div class="tab-pane fade show active" id="tab-widget">

                        {{-- Accordion for widget sub-sections --}}
                        <div class="accordion" id="widgetAccordion">

                            {{-- 1. Pantalla principal --}}
                            <div class="accordion-item mb-2 border rounded">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed fw-semibold" type="button"
                                        data-bs-toggle="collapse" data-bs-target="#acc-home">
                                        <i class="fas fa-home me-2 text-danger"></i>Pantalla principal
                                    </button>
                                </h2>
                                <div id="acc-home" class="accordion-collapse collapse">
                                    <div class="accordion-body">
                                        <p class="text-muted small mb-3">Configura los elementos visibles en la pantalla de inicio del widget</p>
                                        @php
                                            $homeToggles = [
                                                'show_avatars'           => ['label' => 'Mostrar avatares de agentes',     'icon' => 'fa-user-circle',  'help' => 'Muestra la foto de perfil de los agentes'],
                                                'show_help_center'       => ['label' => 'Mostrar centro de ayuda',         'icon' => 'fa-book-open',    'help' => 'Incluye una tarjeta de enlace al centro de ayuda'],
                                                'hide_suggested_articles'=> ['label' => 'Ocultar artículos sugeridos',     'icon' => 'fa-file-lines',   'help' => 'No muestra artículos del helpcenter en home'],
                                                'show_tickets_section'   => ['label' => 'Mostrar sección de tickets',      'icon' => 'fa-ticket',       'help' => 'Permite crear y ver tickets desde el widget'],
                                                'enable_send_message'    => ['label' => 'Habilitar envío de mensajes',     'icon' => 'fa-paper-plane',  'help' => 'Muestra el botón "Envíanos un mensaje"'],
                                                'enable_create_ticket'   => ['label' => 'Habilitar crear ticket',          'icon' => 'fa-plus-circle',  'help' => 'Muestra la opción de crear un ticket nuevo'],
                                                'enable_search_help'     => ['label' => 'Habilitar buscar ayuda',          'icon' => 'fa-magnifying-glass', 'help' => 'Muestra el acceso rápido a buscar en helpcenter'],
                                            ];
                                        @endphp
                                        @foreach($homeToggles as $field => $meta)
                                        <div class="lc-toggle-item">
                                            <div class="form-check form-switch mb-0 pt-1">
                                                <input type="hidden" name="{{ $field }}" value="0">
                                                <input class="form-check-input lc-sync" type="checkbox" id="{{ $field }}" name="{{ $field }}" value="1"
                                                    {{ ($backups[$field] ?? true) ? 'checked' : '' }}>
                                            </div>
                                            <label class="form-check-label mb-0 w-100" for="{{ $field }}">
                                                <span class="fw-semibold d-block">
                                                    <i class="fas {{ $meta['icon'] }} text-muted me-1" style="width:14px"></i>{{ $meta['label'] }}
                                                </span>
                                                <small class="text-muted">{{ $meta['help'] }}</small>
                                            </label>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>

                            {{-- 2. Pantalla de chat --}}
                            <div class="accordion-item mb-2 border rounded">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed fw-semibold" type="button"
                                        data-bs-toggle="collapse" data-bs-target="#acc-chat">
                                        <i class="fas fa-message me-2 text-danger"></i>Pantalla de chat
                                    </button>
                                </h2>
                                <div id="acc-chat" class="accordion-collapse collapse">
                                    <div class="accordion-body">
                                        <p class="text-muted small mb-3">Mensajes que ven los visitantes en la ventana de conversación</p>
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold" for="welcome_message">Mensaje de bienvenida <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control lc-sync" id="welcome_message" name="welcome_message"
                                                value="{{ old('welcome_message', $backups['welcome_message'] ?? 'Hola! ¿Cómo podemos ayudarte?') }}"
                                                maxlength="200" required>
                                            <div class="form-text">Primer mensaje que aparece al abrir el chat</div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold" for="input_placeholder">Placeholder del campo de mensaje</label>
                                            <input type="text" class="form-control lc-sync" id="input_placeholder" name="input_placeholder"
                                                value="{{ old('input_placeholder', $backups['input_placeholder'] ?? 'Escribe tu mensaje...') }}"
                                                maxlength="100">
                                            <div class="form-text">Texto de ayuda dentro del campo de escritura</div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold" for="no_agents_message">Mensaje sin agentes disponibles</label>
                                            <textarea class="form-control lc-sync" id="no_agents_message" name="no_agents_message"
                                                rows="3" maxlength="500">{{ old('no_agents_message', $backups['offline_message'] ?? 'Nuestros agentes no están disponibles en este momento...') }}</textarea>
                                            <div class="form-text">Mostrado cuando ningún agente está en línea</div>
                                        </div>
                                        <div class="mb-0">
                                            <label class="form-label fw-semibold" for="queue_message">Mensaje en cola de espera</label>
                                            <textarea class="form-control lc-sync" id="queue_message" name="queue_message"
                                                rows="3" maxlength="500">{{ old('queue_message', $backups['queue_message'] ?? 'Uno de nuestros agentes estará contigo en breve.') }}</textarea>
                                            <div class="form-text">Mostrado mientras el visitante espera un agente</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- 3. Lanzador --}}
                            <div class="accordion-item mb-2 border rounded">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed fw-semibold" type="button"
                                        data-bs-toggle="collapse" data-bs-target="#acc-launcher">
                                        <i class="fas fa-rocket me-2 text-danger"></i>Lanzador
                                    </button>
                                </h2>
                                <div id="acc-launcher" class="accordion-collapse collapse">
                                    <div class="accordion-body">
                                        <p class="text-muted small mb-3">Posición y visibilidad del botón flotante del chat</p>
                                        <div class="row g-3 mb-3">
                                            <div class="col-md-4">
                                                <label class="form-label fw-semibold" for="position">Posición</label>
                                                <select class="form-select lc-sync" id="position" name="position">
                                                    @foreach($positions ?? [] as $value => $label)
                                                        <option value="{{ $value }}" {{ ($backups['position'] ?? 'bottom-right') === $value ? 'selected' : '' }}>
                                                            {{ $label }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label fw-semibold" for="side_spacing">Espacio lateral (px)</label>
                                                <input type="number" class="form-control lc-sync" id="side_spacing" name="side_spacing"
                                                    value="{{ old('side_spacing', $backups['side_spacing'] ?? 16) }}" min="0" max="100">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label fw-semibold" for="bottom_spacing">Espacio inferior (px)</label>
                                                <input type="number" class="form-control lc-sync" id="bottom_spacing" name="bottom_spacing"
                                                    value="{{ old('bottom_spacing', $backups['bottom_spacing'] ?? 16) }}" min="0" max="100">
                                            </div>
                                        </div>
                                        <div class="lc-toggle-item">
                                            <div class="form-check form-switch mb-0 pt-1">
                                                <input type="hidden" name="hide_launcher" value="0">
                                                <input class="form-check-input lc-sync" type="checkbox" id="hide_launcher" name="hide_launcher" value="1"
                                                    {{ ($backups['hide_launcher'] ?? false) ? 'checked' : '' }}>
                                            </div>
                                            <label class="form-check-label mb-0 w-100" for="hide_launcher">
                                                <span class="fw-semibold d-block"><i class="fas fa-eye-slash text-muted me-1" style="width:14px"></i>Ocultar lanzador</span>
                                                <small class="text-muted">El botón flotante estará oculto por defecto y debe mostrarse vía API</small>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- 4. Estilo --}}
                            <div class="accordion-item mb-2 border rounded">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed fw-semibold" type="button"
                                        data-bs-toggle="collapse" data-bs-target="#acc-style">
                                        <i class="fas fa-palette me-2 text-danger"></i>Estilo
                                    </button>
                                </h2>
                                <div id="acc-style" class="accordion-collapse collapse show">
                                    <div class="accordion-body">
                                        <p class="text-muted small mb-3">Colores, título y apariencia del widget</p>
                                        <div class="row g-3 mb-3">
                                            <div class="col-md-4">
                                                <label class="form-label fw-semibold" for="primary_color">Color primario</label>
                                                <div class="input-group">
                                                    <input type="color" class="form-control form-control-color lc-color-picker" id="primary_color" name="primary_color"
                                                        value="{{ old('primary_color', $backups['primary_color'] ?? '#b10100') }}" style="max-width:50px;">
                                                    <input type="text" class="form-control color-hex-input" id="primary_color_hex"
                                                        value="{{ old('primary_color', $backups['primary_color'] ?? '#b10100') }}" readonly>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label fw-semibold" for="secondary_color">Color secundario</label>
                                                <div class="input-group">
                                                    <input type="color" class="form-control form-control-color lc-color-picker" id="secondary_color" name="secondary_color"
                                                        value="{{ old('secondary_color', $backups['secondary_color'] ?? '#ffffff') }}" style="max-width:50px;">
                                                    <input type="text" class="form-control color-hex-input" id="secondary_color_hex"
                                                        value="{{ old('secondary_color', $backups['secondary_color'] ?? '#ffffff') }}" readonly>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label fw-semibold" for="header_title">Título del chat <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control lc-sync" id="header_title" name="header_title"
                                                    value="{{ old('header_title', $backups['header_title'] ?? 'Chat de Soporte') }}" maxlength="100" required>
                                            </div>
                                        </div>
                                        <div class="lc-toggle-item">
                                            <div class="form-check form-switch mb-0 pt-1">
                                                <input type="hidden" name="show_dark_mode_preview" value="0">
                                                <input class="form-check-input lc-sync" type="checkbox" id="show_dark_mode_preview" name="show_dark_mode_preview" value="1"
                                                    {{ ($backups['show_dark_mode_preview'] ?? true) ? 'checked' : '' }}>
                                            </div>
                                            <label class="form-check-label mb-0 w-100" for="show_dark_mode_preview">
                                                <span class="fw-semibold d-block"><i class="fas fa-moon text-muted me-1" style="width:14px"></i>Vista previa en modo oscuro</span>
                                                <small class="text-muted">Previsualiza el widget con el tema oscuro activado</small>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- 5. Pantallas activas --}}
                            <div class="accordion-item mb-2 border rounded">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed fw-semibold" type="button"
                                        data-bs-toggle="collapse" data-bs-target="#acc-screens">
                                        <i class="fas fa-table-columns me-2 text-danger"></i>Pantallas activas
                                    </button>
                                </h2>
                                <div id="acc-screens" class="accordion-collapse collapse">
                                    <div class="accordion-body">
                                        <p class="text-muted small mb-3">Qué pantallas están disponibles en la barra de navegación del widget</p>
                                        @php
                                            $screenToggles = [
                                                'sound_notifications'    => ['label' => 'Notificaciones de sonido',  'icon' => 'fa-bell',          'help' => 'Reproduce sonido al recibir mensajes nuevos'],
                                                'show_timestamps'        => ['label' => 'Mostrar hora de envío',     'icon' => 'fa-clock',         'help' => 'Muestra el timestamp en cada mensaje'],
                                                'typing_indicator'       => ['label' => 'Indicador de escritura',    'icon' => 'fa-ellipsis',      'help' => 'Muestra cuando el agente está escribiendo'],
                                                'enable_email_transcripts' => ['label' => 'Descargar transcripción', 'icon' => 'fa-file-lines',    'help' => 'Permite al visitante recibir el historial por email'],
                                            ];
                                        @endphp
                                        @foreach($screenToggles as $field => $meta)
                                        <div class="lc-toggle-item">
                                            <div class="form-check form-switch mb-0 pt-1">
                                                <input type="hidden" name="{{ $field }}" value="0">
                                                <input class="form-check-input lc-sync" type="checkbox" id="{{ $field }}" name="{{ $field }}" value="1"
                                                    {{ ($backups[$field] ?? true) ? 'checked' : '' }}>
                                            </div>
                                            <label class="form-check-label mb-0 w-100" for="{{ $field }}">
                                                <span class="fw-semibold d-block">
                                                    <i class="fas {{ $meta['icon'] }} text-muted me-1" style="width:14px"></i>{{ $meta['label'] }}
                                                </span>
                                                <small class="text-muted">{{ $meta['help'] }}</small>
                                            </label>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>

                            {{-- 6. Formularios --}}
                            <div class="accordion-item mb-2 border rounded">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed fw-semibold" type="button"
                                        data-bs-toggle="collapse" data-bs-target="#acc-forms">
                                        <i class="fas fa-wpforms me-2 text-danger"></i>Formularios
                                    </button>
                                </h2>
                                <div id="acc-forms" class="accordion-collapse collapse">
                                    <div class="accordion-body">
                                        <p class="text-muted small mb-3">Formulario pre-chat que recopila datos antes de iniciar conversación</p>
                                        <div class="d-flex gap-2 mb-3">
                                            <button type="button" class="btn btn-sm btn-outline-secondary active" id="btnPreChat" onclick="switchForm('pre')">
                                                Formulario pre-chat
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-secondary" id="btnPostChat" onclick="switchForm('post')">
                                                Formulario post-chat
                                            </button>
                                        </div>

                                        {{-- Pre-chat form --}}
                                        <div id="formPreChat">
                                            <div class="lc-toggle-item mb-3">
                                                <div class="form-check form-switch mb-0 pt-1">
                                                    <input type="hidden" name="pre_chat_form_enabled" value="0">
                                                    <input class="form-check-input lc-sync" type="checkbox" id="pre_chat_form_enabled" name="pre_chat_form_enabled" value="1"
                                                        {{ ($backups['pre_chat_form_enabled'] ?? false) ? 'checked' : '' }}>
                                                </div>
                                                <label class="form-check-label mb-0 w-100" for="pre_chat_form_enabled">
                                                    <span class="fw-semibold d-block">Activar formulario pre-chat</span>
                                                    <small class="text-muted">Solicita nombre y email antes de iniciar el chat</small>
                                                </label>
                                            </div>
                                            <div class="mb-0">
                                                <label class="form-label fw-semibold" for="pre_chat_info">Mensaje informativo</label>
                                                <textarea class="form-control" id="pre_chat_info" name="pre_chat_info" rows="2"
                                                    placeholder="Ej: Por favor comparte tus datos para poder ayudarte mejor">{{ old('pre_chat_info', $backups['pre_chat_info'] ?? '') }}</textarea>
                                            </div>
                                        </div>

                                        {{-- Post-chat form --}}
                                        <div id="formPostChat" class="d-none">
                                            <div class="lc-toggle-item mb-3">
                                                <div class="form-check form-switch mb-0 pt-1">
                                                    <input type="hidden" name="post_chat_form_enabled" value="0">
                                                    <input class="form-check-input lc-sync" type="checkbox" id="post_chat_form_enabled" name="post_chat_form_enabled" value="1"
                                                        {{ ($backups['post_chat_form_enabled'] ?? false) ? 'checked' : '' }}>
                                                </div>
                                                <label class="form-check-label mb-0 w-100" for="post_chat_form_enabled">
                                                    <span class="fw-semibold d-block">Activar formulario post-chat</span>
                                                    <small class="text-muted">Muestra una encuesta de satisfacción al cerrar el chat</small>
                                                </label>
                                            </div>
                                            <div class="mb-0">
                                                <label class="form-label fw-semibold" for="post_chat_info">Mensaje informativo</label>
                                                <textarea class="form-control" id="post_chat_info" name="post_chat_info" rows="2"
                                                    placeholder="Ej: ¿Cómo calificarías la atención recibida?">{{ old('post_chat_info', $backups['post_chat_info'] ?? '') }}</textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- 7. Página de chat --}}
                            <div class="accordion-item mb-2 border rounded">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed fw-semibold" type="button"
                                        data-bs-toggle="collapse" data-bs-target="#acc-chatpage">
                                        <i class="fas fa-window-maximize me-2 text-danger"></i>Página de chat
                                    </button>
                                </h2>
                                <div id="acc-chatpage" class="accordion-collapse collapse">
                                    <div class="accordion-body">
                                        <p class="text-muted small mb-3">Comparte un enlace directo al chat sin instalar el widget en tu web</p>
                                        <div class="input-group mb-3">
                                            <input type="text" class="form-control bg-light" id="chatPageUrl"
                                                value="{{ url('/hd/widget') }}" readonly>
                                            <button type="button" class="btn btn-outline-secondary" onclick="copyChatLink()">
                                                <i class="fas fa-copy me-1"></i><span id="copyLinkLabel">Copiar enlace</span>
                                            </button>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold" for="chat_page_title">Título de la página</label>
                                            <input type="text" class="form-control" id="chat_page_title" name="chat_page_title"
                                                value="{{ old('chat_page_title', $backups['chat_page_title'] ?? '') }}"
                                                placeholder="Ej: Chat de soporte" maxlength="100">
                                        </div>
                                        <div class="mb-0">
                                            <label class="form-label fw-semibold" for="chat_page_subtitle">Subtítulo</label>
                                            <textarea class="form-control" id="chat_page_subtitle" name="chat_page_subtitle"
                                                rows="2" maxlength="200"
                                                placeholder="Ej: Estamos aquí para ayudarte">{{ old('chat_page_subtitle', $backups['chat_page_subtitle'] ?? '') }}</textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>{{-- end accordion --}}
                    </div>

                    {{-- ═══════════════════════════════════════════ --}}
                    {{-- TAB: TIEMPOS                                --}}
                    {{-- ═══════════════════════════════════════════ --}}
                    <div class="tab-pane fade" id="tab-timeouts">
                        <div class="card">
                            <div class="card-body">
                                <p class="lc-section-title"><i class="fas fa-clock"></i>Tiempos de espera automáticos</p>
                                <p class="text-muted small mb-4">Define umbrales que accionan transferencias, inactivación o cierre de conversaciones automáticamente</p>

                                {{-- Transferencia de agente --}}
                                <div class="timeout-row {{ ($backups['enable_auto_transfer'] ?? false) ? 'active' : '' }}" id="row-transfer" onclick="toggleTimeout('transfer')">
                                    <div class="d-flex align-items-start gap-3">
                                        <div class="form-check mb-0 pt-1">
                                            <input type="hidden" name="enable_auto_transfer" value="0">
                                            <input class="form-check-input" type="checkbox" id="enable_auto_transfer" name="enable_auto_transfer" value="1"
                                                {{ ($backups['enable_auto_transfer'] ?? false) ? 'checked' : '' }}
                                                onclick="event.stopPropagation()">
                                        </div>
                                        <div class="flex-grow-1 {{ !($backups['enable_auto_transfer'] ?? false) ? 'opacity-50' : '' }}" id="body-transfer">
                                            <div class="mb-1">
                                                Si el agente no responde en
                                                <input type="number" class="form-control form-control-sm d-inline-block" id="auto_transfer_minutes" name="auto_transfer_minutes"
                                                    value="{{ $backups['auto_transfer_minutes'] ?? 5 }}" min="1" max="60"
                                                    style="width:65px" onclick="event.stopPropagation()">
                                                minutos, transferir la conversación a otro agente disponible.
                                            </div>
                                            <small class="text-muted">Si el chat está en un grupo con asignación manual, pasará a la cola en su lugar.</small>
                                        </div>
                                    </div>
                                </div>

                                {{-- Inactividad --}}
                                <div class="timeout-row {{ ($backups['enable_auto_inactive'] ?? false) ? 'active' : '' }}" id="row-inactive" onclick="toggleTimeout('inactive')">
                                    <div class="d-flex align-items-start gap-3">
                                        <div class="form-check mb-0 pt-1">
                                            <input type="hidden" name="enable_auto_inactive" value="0">
                                            <input class="form-check-input" type="checkbox" id="enable_auto_inactive" name="enable_auto_inactive" value="1"
                                                {{ ($backups['enable_auto_inactive'] ?? false) ? 'checked' : '' }}
                                                onclick="event.stopPropagation()">
                                        </div>
                                        <div class="flex-grow-1 {{ !($backups['enable_auto_inactive'] ?? false) ? 'opacity-50' : '' }}" id="body-inactive">
                                            <div class="mb-1">
                                                Si no hay mensajes durante
                                                <input type="number" class="form-control form-control-sm d-inline-block" id="auto_inactive_minutes" name="auto_inactive_minutes"
                                                    value="{{ $backups['auto_inactive_minutes'] ?? 10 }}" min="1" max="120"
                                                    style="width:65px" onclick="event.stopPropagation()">
                                                minutos, marcar el chat como inactivo.
                                            </div>
                                            <small class="text-muted">Los chats inactivos no cuentan en el límite de chats concurrentes del agente.</small>
                                        </div>
                                    </div>
                                </div>

                                {{-- Cierre automático --}}
                                <div class="timeout-row {{ ($backups['enable_auto_close'] ?? false) ? 'active' : '' }}" id="row-close" onclick="toggleTimeout('close')">
                                    <div class="d-flex align-items-start gap-3">
                                        <div class="form-check mb-0 pt-1">
                                            <input type="hidden" name="enable_auto_close" value="0">
                                            <input class="form-check-input" type="checkbox" id="enable_auto_close" name="enable_auto_close" value="1"
                                                {{ ($backups['enable_auto_close'] ?? false) ? 'checked' : '' }}
                                                onclick="event.stopPropagation()">
                                        </div>
                                        <div class="flex-grow-1 {{ !($backups['enable_auto_close'] ?? false) ? 'opacity-50' : '' }}" id="body-close">
                                            <div class="mb-1">
                                                Si no hay mensajes durante
                                                <input type="number" class="form-control form-control-sm d-inline-block" id="auto_close_minutes" name="auto_close_minutes"
                                                    value="{{ $backups['auto_close_minutes'] ?? 15 }}" min="1" max="240"
                                                    style="width:65px" onclick="event.stopPropagation()">
                                                minutos, cerrar el chat automáticamente.
                                            </div>
                                            <small class="text-muted">Los visitantes pueden reabrir un chat cerrado enviando un nuevo mensaje.</small>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                    {{-- ═══════════════════════════════════════════ --}}
                    {{-- TAB: INSTALAR                               --}}
                    {{-- ═══════════════════════════════════════════ --}}
                    <div class="tab-pane fade" id="tab-install">
                        <div class="card">
                            <div class="card-body">
                                <p class="lc-section-title"><i class="fas fa-code"></i>Instalar widget en tu sitio</p>
                                <p class="text-muted small mb-4">
                                    Copia y pega este fragmento de código justo antes de la etiqueta <code>&lt;/body&gt;</code> en todas las páginas donde quieras mostrar el chat.
                                </p>

                                @php
                                    $websiteToken = $backups['website_token'] ?? null;
                                    $installCode = "<script>\n  window.HelpdeskSettings = {\n    websiteToken: \"{{ $websiteToken ?? 'TU_WEBSITE_TOKEN' }}\",\n    baseUrl: \"{{ url('/') }}\"\n  };\n</script>\n<script src=\"{{ url('/hd/widget-loader.js') }}\"></script>";
                                @endphp

                                <div class="position-relative mb-4">
                                    <div class="lc-code-block" id="installCodeBlock"><pre class="mb-0" style="color:inherit;background:transparent;padding:0">&lt;script&gt;
  window.HelpdeskSettings = {
    websiteToken: "{{ $websiteToken ?? 'TU_WEBSITE_TOKEN' }}",
    baseUrl: "{{ url('/') }}"
  };
&lt;/script&gt;
&lt;script src="{{ url('/hd/widget-loader.js') }}"&gt;&lt;/script&gt;</pre>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-light lc-code-copy" onclick="copyInstallCode()">
                                        <i class="fas fa-copy me-1"></i><span id="copyCodeLabel">Copiar</span>
                                    </button>
                                </div>

                                <div class="alert alert-info d-flex gap-2 align-items-start">
                                    <i class="fas fa-circle-info mt-1"></i>
                                    <div>
                                        <strong>Dominio personalizado:</strong> Si tu Helpdesk está en un dominio diferente al de tu sitio web,
                                        puedes especificarlo en la opción <code>widgetDomain</code>:
                                        <div class="lc-code-block mt-2 position-relative" style="font-size:.8rem">
<pre class="mb-0" style="color:inherit;background:transparent;padding:0">&lt;script&gt;
  window.HelpdeskSettings = {
    websiteToken: "{{ $websiteToken ?? 'TU_WEBSITE_TOKEN' }}",
    widgetDomain: "https://soporte.tu-empresa.com"
  };
&lt;/script&gt;</pre>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                    {{-- ═══════════════════════════════════════════ --}}
                    {{-- TAB: SEGURIDAD                              --}}
                    {{-- ═══════════════════════════════════════════ --}}
                    <div class="tab-pane fade" id="tab-security">
                        <div class="card">
                            <div class="card-body">
                                <p class="lc-section-title"><i class="fas fa-shield-halved"></i>Seguridad del widget</p>

                                {{-- Trusted domains --}}
                                <div class="mb-4">
                                    <label class="form-label fw-semibold" for="trusted_domains">Dominios de confianza</label>
                                    <textarea class="form-control" id="trusted_domains" name="trusted_domains"
                                        rows="4" placeholder="ejemplo.com, *.mi-empresa.com">{{ old('trusted_domains', $backups['trusted_domains'] ?? '') }}</textarea>
                                    <div class="form-text">
                                        Lista tus dominios y subdominios de confianza separados por comas.
                                        Usa <code>*</code> como comodín: <code>*.ejemplo.com</code>.
                                        Si lo dejas vacío, el widget puede insertarse en cualquier dominio.
                                    </div>
                                </div>

                                <div class="border-top pt-4 mb-4">
                                    <div class="lc-toggle-item">
                                        <div class="form-check form-switch mb-0 pt-1">
                                            <input type="hidden" name="enforce_identity_verification" value="0">
                                            <input class="form-check-input" type="checkbox" id="enforce_identity_verification"
                                                name="enforce_identity_verification" value="1"
                                                {{ ($backups['enforce_identity_verification'] ?? false) ? 'checked' : '' }}>
                                        </div>
                                        <label class="form-check-label mb-0 w-100" for="enforce_identity_verification">
                                            <span class="fw-semibold d-block"><i class="fas fa-user-shield text-muted me-1" style="width:14px"></i>Forzar verificación de identidad</span>
                                            <small class="text-muted">Al identificar usuarios autenticados en el widget, siempre verificar la identidad con la clave secreta (HMAC)</small>
                                        </label>
                                    </div>
                                </div>

                                {{-- Secret key --}}
                                <div class="border-top pt-4">
                                    <label class="form-label fw-semibold">Clave secreta HMAC</label>
                                    <p class="text-muted small mb-2">Usa esta clave para firmar la identidad de los usuarios autenticados en tu backend</p>
                                    <div class="input-group mb-3">
                                        <input type="text" class="form-control bg-light font-monospace" id="secretKeyDisplay"
                                            value="{{ $backups['secret_key'] ?? '(no disponible)' }}" readonly>
                                        <button type="button" class="btn btn-outline-secondary" onclick="copySecretKey()">
                                            <i class="fas fa-copy me-1"></i><span id="copyKeyLabel">Copiar clave</span>
                                        </button>
                                    </div>
                                    <div class="d-flex gap-2 flex-wrap">
                                        <a href="https://support.vebto.com/hc/articles/42/71/239/identifying-logged-in-users-in-livechat-widget"
                                            class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener">
                                            <i class="fas fa-user me-1"></i>Identificar usuarios autenticados
                                        </a>
                                        <a href="https://support.vebto.com/hc/articles/42/71/238/enforcing-identity-verification-in-livechat-widget"
                                            class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener">
                                            <i class="fas fa-shield-halved me-1"></i>Forzar verificación de identidad
                                        </a>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                </div>{{-- end tab-content --}}
            </form>
        </div>

        {{-- ── Right: Preview ── --}}
        <div class="col-xl-5 col-lg-6">
            <div class="lc-preview-wrap">

                {{-- Preview header --}}
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="fw-semibold text-muted small">
                        <i class="fas fa-eye me-1"></i>Vista previa en tiempo real
                    </span>
                    <ul class="nav preview-screen-tabs gap-1" id="previewTabs">
                        <li class="nav-item">
                            <a class="nav-link active" href="#" data-screen="home">Home</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#" data-screen="conversation">Chat</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#" data-screen="help">Ayuda</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#" data-screen="messages">Mensajes</a>
                        </li>
                    </ul>
                </div>

                {{-- Phone mockup --}}
                <div class="lc-phone-frame">
                    <div class="lc-phone-notch"></div>
                    <div class="lc-phone-screen">
                        <iframe
                            id="widgetPreviewIframe"
                            src="{{ route('helpdesk-livechat.widget.spa.index') }}?preview=true"
                            title="Vista previa del widget">
                        </iframe>
                    </div>
                    <div class="lc-phone-home-bar"></div>
                </div>

                <p class="text-center text-muted small mt-2 mb-0">
                    <i class="fas fa-circle-info me-1"></i>Puedes navegar dentro del preview
                </p>
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function () {
    var iframe      = document.getElementById('widgetPreviewIframe');
    var iframeReady = false;
    var pendingSettings = null;

    // ── Widget ready signal ────────────────────────────────────────────────
    window.addEventListener('message', function (event) {
        if (event.origin !== window.location.origin) return;
        if (event.data?.source === 'be-backups-preview' && event.data?.type === 'appLoaded') {
            iframeReady = true;
            if (pendingSettings) {
                postSettings(pendingSettings);
                pendingSettings = null;
            }
        }
    });

    // ── Collect and post settings ─────────────────────────────────────────
    function collectSettings() {
        return {
            primary_color:          $('#primary_color').val(),
            secondary_color:        $('#secondary_color').val(),
            header_title:           $('#header_title').val(),
            welcome_message:        $('#welcome_message').val(),
            input_placeholder:      $('#input_placeholder').val(),
            show_avatars:           $('#show_avatars').is(':checked'),
            show_help_center:       $('#show_help_center').is(':checked'),
            typing_indicator:       $('#typing_indicator').is(':checked'),
            sound_notifications:    $('#sound_notifications').is(':checked'),
            show_timestamps:        $('#show_timestamps').is(':checked'),
            enable_email_transcripts: $('#enable_email_transcripts').is(':checked'),
            hide_launcher:          $('#hide_launcher').is(':checked'),
            position:               $('#position').val(),
            side_spacing:           parseInt($('#side_spacing').val()) || 16,
            bottom_spacing:         parseInt($('#bottom_spacing').val()) || 16,
        };
    }

    function postSettings(settings) {
        if (!iframe.contentWindow) return;
        iframe.contentWindow.postMessage({
            source: 'be-backups-editor',
            type:   'setValues',
            values: settings
        }, window.location.origin);
    }

    function syncPreview() {
        var s = collectSettings();
        if (iframeReady) {
            postSettings(s);
        } else {
            pendingSettings = s;
        }
    }

    // ── Sync color pickers ─────────────────────────────────────────────────
    $(document).on('input', '.lc-color-picker', function () {
        $(this).closest('.input-group').find('.color-hex-input').val($(this).val());
        syncPreview();
    });

    // ── Sync all other form inputs ─────────────────────────────────────────
    $('#livechatForm').on('change input', '.lc-sync', function () {
        syncPreview();
    });

    // ── Screen tabs ────────────────────────────────────────────────────────
    $('#previewTabs').on('click', 'a.nav-link', function (e) {
        e.preventDefault();
        $('#previewTabs a.nav-link').removeClass('active');
        $(this).addClass('active');
        iframeReady = false;
        iframe.src = '{{ route("helpdesk-livechat.widget.spa.index") }}?preview=true';
        pendingSettings = collectSettings();
    });

    // ── Send initial settings once iframe is ready ─────────────────────────
    pendingSettings = collectSettings();

    @if(session('success'))
        toastr.success('{{ session('success') }}', 'Configuración guardada');
    @endif
});

// ── Timeout toggles ────────────────────────────────────────────────────────
function toggleTimeout(type) {
    var checkbox = document.getElementById('enable_auto_' + type);
    var body     = document.getElementById('body-' + type);
    var row      = document.getElementById('row-' + type);
    checkbox.checked = !checkbox.checked;
    body.classList.toggle('opacity-50', !checkbox.checked);
    row.classList.toggle('active', checkbox.checked);
}

// ── Forms switcher ─────────────────────────────────────────────────────────
function switchForm(type) {
    document.getElementById('formPreChat').classList.toggle('d-none', type !== 'pre');
    document.getElementById('formPostChat').classList.toggle('d-none', type !== 'post');
    document.getElementById('btnPreChat').classList.toggle('active', type === 'pre');
    document.getElementById('btnPostChat').classList.toggle('active', type === 'post');
}

// ── Copy helpers ───────────────────────────────────────────────────────────
function copyInstallCode() {
    var block = document.getElementById('installCodeBlock').innerText;
    navigator.clipboard.writeText(block).then(function () {
        var el = document.getElementById('copyCodeLabel');
        el.textContent = '¡Copiado!';
        setTimeout(function () { el.textContent = 'Copiar'; }, 2000);
    });
}

function copyChatLink() {
    var url = document.getElementById('chatPageUrl').value;
    navigator.clipboard.writeText(url).then(function () {
        var el = document.getElementById('copyLinkLabel');
        el.textContent = '¡Enlace copiado!';
        setTimeout(function () { el.textContent = 'Copiar enlace'; }, 2000);
    });
}

function copySecretKey() {
    var key = document.getElementById('secretKeyDisplay').value;
    navigator.clipboard.writeText(key).then(function () {
        var el = document.getElementById('copyKeyLabel');
        el.textContent = '¡Copiada!';
        setTimeout(function () { el.textContent = 'Copiar clave'; }, 2000);
    });
}
</script>
@endpush
