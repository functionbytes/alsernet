@extends('layouts.theme')

@section('title', __('helpdesk.livechat.title'))

@section('content')

    @include('core::components.card', ['title' => __('helpdesk.livechat.title')])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <!-- Header Actions Card -->
        <div class="card mb-3">
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">{{ __('helpdesk.livechat.page_title') }}</h5>
                        <p class="small mb-0 text-muted">{{ __('helpdesk.livechat.page_description') }}</p>
                    </div>
                    <div class="d-flex gap-2 align-items-center">
                        <a href="{{ route('manager.backups') }}" class="btn btn-outline-secondary">
                            <i class="fa fa-arrow-left me-1"></i> {{ __('helpdesk.livechat.buttons.back') }}
                        </a>
                        <button type="submit" form="livechatForm" class="btn btn-primary" id="saveBtn" disabled>
                            <i class="fa fa-check me-1"></i> {{ __('helpdesk.livechat.buttons.save_changes') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <!-- Main Form (left side) -->
            <div class="col-lg-8">
                <form method="POST" action="{{ route('manager.helpdesk.backups.livechat.update') }}" id="livechatForm">
                    @csrf
                    @method('PUT')

                    <!-- Navigation Tabs -->
                    <div class="card mb-3">
                        <div class="card-body p-3">
                            <ul class="nav nav-pills nav-fill" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="widget-tab" data-bs-toggle="tab" data-bs-target="#widget" type="button" role="tab">
                                        <i class="fa fa-palette me-1"></i> {{ __('helpdesk.livechat.tabs.widget') }}
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="timeouts-tab" data-bs-toggle="tab" data-bs-target="#timeouts" type="button" role="tab">
                                        <i class="fa fa-clock me-1"></i> {{ __('helpdesk.livechat.tabs.timeouts') }}
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="install-tab" data-bs-toggle="tab" data-bs-target="#install" type="button" role="tab">
                                        <i class="fa fa-code me-1"></i> {{ __('helpdesk.livechat.tabs.install') }}
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="security-tab" data-bs-toggle="tab" data-bs-target="#security" type="button" role="tab">
                                        <i class="fa fa-shield me-1"></i> {{ __('helpdesk.livechat.tabs.security') }}
                                    </button>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Tab Content -->
                    <div class="tab-content">
                        <!-- Widget Tab -->
                        <div class="tab-pane fade show active" id="widget" role="tabpanel">
                            <!-- Widget Accordion -->
                            <div class="accordion" id="widgetAccordion">
                                <!-- Home Screen Section -->
                                <div class="accordion-item mb-2">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#homeScreen">
                                            <i class="fa fa-home me-2 text-primary"></i> {{ __('helpdesk.livechat.sections.home_screen') }}
                                        </button>
                                    </h2>
                                    <div id="homeScreen" class="accordion-collapse collapse show" data-bs-parent="#widgetAccordion">
                                        <div class="accordion-body">
                                            <!-- Show Avatars -->
                                            <div class="border-bottom pb-3 mb-3">
                                                <div class="form-check form-switch">
                                                    <input type="hidden" name="show_avatars" value="0">
                                                    <input type="checkbox" name="show_avatars" class="form-check-input" id="showAvatars" value="1"
                                                           {{ $settings['show_avatars'] ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="showAvatars">
                                                        <strong>{{ __('helpdesk.livechat.fields.show_avatars') }}</strong>
                                                        <small class="d-block text-muted">{{ __('helpdesk.livechat.fields.show_avatars_help') }}</small>
                                                    </label>
                                                </div>
                                            </div>

                                            <!-- Show Help Center Card -->
                                            <div class="border-bottom pb-3 mb-3">
                                                <div class="form-check form-switch">
                                                    <input type="hidden" name="show_help_center" value="0">
                                                    <input type="checkbox" name="show_help_center" class="form-check-input" id="showHelpCenter" value="1"
                                                           {{ $settings['show_help_center'] ?? true ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="showHelpCenter">
                                                        <strong>{{ __('helpdesk.livechat.fields.show_help_center') }}</strong>
                                                        <small class="d-block text-muted">{{ __('helpdesk.livechat.fields.show_help_center_help') }}</small>
                                                    </label>
                                                </div>
                                            </div>

                                            <!-- Hide Suggested Articles -->
                                            <div class="border-bottom pb-3 mb-3">
                                                <div class="form-check form-switch">
                                                    <input type="hidden" name="hide_suggested_articles" value="0">
                                                    <input type="checkbox" name="hide_suggested_articles" class="form-check-input" id="hideSuggestedArticles" value="1"
                                                           {{ $settings['hide_suggested_articles'] ?? false ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="hideSuggestedArticles">
                                                        <strong>{{ __('helpdesk.livechat.fields.hide_suggested_articles') }}</strong>
                                                        <small class="d-block text-muted">{{ __('helpdesk.livechat.fields.hide_suggested_articles_help') }}</small>
                                                    </label>
                                                </div>
                                            </div>

                                            <!-- Show Tickets Section -->
                                            <div class="border-bottom pb-3 mb-3">
                                                <div class="form-check form-switch">
                                                    <input type="hidden" name="show_tickets_section" value="0">
                                                    <input type="checkbox" name="show_tickets_section" class="form-check-input" id="showTicketsSection" value="1"
                                                           {{ $settings['show_tickets_section'] ?? true ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="showTicketsSection">
                                                        <strong>{{ __('helpdesk.livechat.fields.show_tickets_section') }}</strong>
                                                        <small class="d-block text-muted">{{ __('helpdesk.livechat.fields.show_tickets_section_help') }}</small>
                                                    </label>
                                                </div>
                                            </div>

                                            <!-- Enable Send Message -->
                                            <div class="border-bottom pb-3 mb-3">
                                                <div class="form-check form-switch">
                                                    <input type="hidden" name="enable_send_message" value="0">
                                                    <input type="checkbox" name="enable_send_message" class="form-check-input" id="enableSendMessage" value="1"
                                                           {{ $settings['enable_send_message'] ?? true ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="enableSendMessage">
                                                        <strong><i class="fa fa-envelope me-1"></i> {{ __('helpdesk.livechat.fields.enable_send_message') }}</strong>
                                                        <small class="d-block text-muted">{{ __('helpdesk.livechat.fields.enable_send_message_help') }}</small>
                                                    </label>
                                                </div>
                                            </div>

                                            <!-- Enable Create Ticket -->
                                            <div class="border-bottom pb-3 mb-3">
                                                <div class="form-check form-switch">
                                                    <input type="hidden" name="enable_create_ticket" value="0">
                                                    <input type="checkbox" name="enable_create_ticket" class="form-check-input" id="enableCreateTicket" value="1"
                                                           {{ $settings['enable_create_ticket'] ?? true ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="enableCreateTicket">
                                                        <strong><i class="fa fa-ticket me-1"></i> {{ __('helpdesk.livechat.fields.enable_create_ticket') }}</strong>
                                                        <small class="d-block text-muted">{{ __('helpdesk.livechat.fields.enable_create_ticket_help') }}</small>
                                                    </label>
                                                </div>
                                            </div>

                                            <!-- Enable Search Help -->
                                            <div class="pb-3">
                                                <div class="form-check form-switch">
                                                    <input type="hidden" name="enable_search_help" value="0">
                                                    <input type="checkbox" name="enable_search_help" class="form-check-input" id="enableSearchHelp" value="1"
                                                           {{ $settings['enable_search_help'] ?? true ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="enableSearchHelp">
                                                        <strong><i class="fa fa-search me-1"></i> {{ __('helpdesk.livechat.fields.enable_search_help') }}</strong>
                                                        <small class="d-block text-muted">{{ __('helpdesk.livechat.fields.enable_search_help_help') }}</small>
                                                    </label>
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                </div>

                                <!-- Chat Screen Section -->
                                <div class="accordion-item mb-2">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#chatScreen">
                                            <i class="fa fa-comments me-2 text-info"></i> {{ __('helpdesk.livechat.sections.chat_screen') }}
                                        </button>
                                    </h2>
                                    <div id="chatScreen" class="accordion-collapse collapse" data-bs-parent="#widgetAccordion">
                                        <div class="accordion-body">
                                            <!-- Welcome Message -->
                                            <div class="mb-4">
                                                <label class="form-label fw-semibold">Mensaje de Bienvenida</label>
                                                <input type="text" name="welcome_message" class="form-control"
                                                       value="{{ $settings['welcome_message'] }}" maxlength="200" required>
                                                <small class="text-muted">Primer mensaje que ve el cliente al iniciar el chat</small>
                                            </div>

                                            <!-- Input Placeholder -->
                                            <div class="mb-4">
                                                <label class="form-label fw-semibold">Placeholder del Input</label>
                                                <input type="text" name="input_placeholder" class="form-control"
                                                       value="{{ $settings['input_placeholder'] ?? 'Escribe tu mensaje...' }}" maxlength="100">
                                                <small class="text-muted">Texto de ayuda que aparece en el campo de entrada de mensajes</small>
                                            </div>

                                            <!-- No Agents Available -->
                                            <div class="mb-4">
                                                <label class="form-label fw-semibold">Mensaje: No hay agentes disponibles</label>
                                                <textarea name="no_agents_message" class="form-control" rows="3" maxlength="500">{{ $settings['offline_message'] }}</textarea>
                                                <small class="text-muted">Mensaje cuando todos los agentes están desconectados</small>
                                            </div>

                                            <!-- Customer in Queue -->
                                            <div class="mb-0">
                                                <label class="form-label fw-semibold">Mensaje: Cliente en Cola</label>
                                                <textarea name="queue_message" class="form-control" rows="3" maxlength="500">{{ $settings['queue_message'] ?? 'Uno de nuestros agentes estará contigo en breve. Eres el número :number en la cola.' }}</textarea>
                                                <small class="text-muted">Mensaje cuando el cliente está esperando en la cola (usa :number y :minutes como variables)</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Launcher Section -->
                                <div class="accordion-item mb-2">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#launcher">
                                            <i class="fa fa-rocket me-2 text-success"></i> Launcher
                                        </button>
                                    </h2>
                                    <div id="launcher" class="accordion-collapse collapse" data-bs-parent="#widgetAccordion">
                                        <div class="accordion-body">
                                            <div class="row">
                                                <!-- Position -->
                                                <div class="col-md-6 mb-4">
                                                    <label class="form-label fw-semibold">Posición del Widget</label>
                                                    <select name="position" class="form-select" required>
                                                        @foreach($positions as $value => $label)
                                                            <option value="{{ $value }}" {{ $settings['position'] == $value ? 'selected' : '' }}>
                                                                {{ $label }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <small class="text-muted">Esquina de la pantalla donde aparece el botón del chat</small>
                                                </div>

                                                <!-- Side Spacing -->
                                                <div class="col-md-6 mb-4">
                                                    <label class="form-label fw-semibold">Espaciado Lateral (px)</label>
                                                    <input type="number" name="side_spacing" class="form-control"
                                                           value="{{ $settings['side_spacing'] ?? 16 }}" min="0" max="100">
                                                    <small class="text-muted">Distancia desde el borde lateral de la pantalla</small>
                                                </div>

                                                <!-- Bottom Spacing -->
                                                <div class="col-md-6 mb-4">
                                                    <label class="form-label fw-semibold">Espaciado Inferior (px)</label>
                                                    <input type="number" name="bottom_spacing" class="form-control"
                                                           value="{{ $settings['bottom_spacing'] ?? 16 }}" min="0" max="100">
                                                    <small class="text-muted">Distancia desde el borde inferior de la pantalla</small>
                                                </div>
                                            </div>

                                            <!-- Hide Launcher -->
                                            <div class="pb-0">
                                                <div class="form-check form-switch">
                                                    <input type="hidden" name="hide_launcher" value="0">
                                                    <input type="checkbox" name="hide_launcher" class="form-check-input" id="hideLauncher" value="1"
                                                           {{ $settings['hide_launcher'] ?? false ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="hideLauncher">
                                                        <strong>Ocultar Launcher Por defecto</strong>
                                                        <small class="d-block text-muted">El botón del chat estará oculto Por defecto y deberá mostrarse manualmente via API</small>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Style Section -->
                                <div class="accordion-item mb-2">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#style">
                                            <i class="fa fa-palette me-2 text-warning"></i> {{ __('helpdesk.livechat.sections.style') }}
                                        </button>
                                    </h2>
                                    <div id="style" class="accordion-collapse collapse show" data-bs-parent="#widgetAccordion">
                                        <div class="accordion-body">
                                            <div class="alert alert-info mb-3">
                                                <small>
                                                    <i class="fa fa-info-circle me-1"></i>
                                                    <strong>{{ __('helpdesk.livechat.fields.secondary_color_note') }}</strong>
                                                </small>
                                            </div>

                                            <div class="row">
                                                <!-- Primary Color -->
                                                <div class="col-md-6 mb-4">
                                                    <label class="form-label fw-semibold">
                                                        <i class="fa fa-star text-warning me-1"></i> {{ __('helpdesk.livechat.fields.primary_color') }}
                                                    </label>
                                                    <div class="input-group">
                                                        <input type="color" name="primary_color" class="form-control form-control-color"
                                                               value="{{ $settings['primary_color'] }}" style="max-width: 60px;" required>
                                                        <input type="text" class="form-control color-text" value="{{ $settings['primary_color'] }}" readonly>
                                                    </div>
                                                    <small class="text-muted">{{ __('helpdesk.livechat.fields.primary_color_help') }}</small>
                                                </div>

                                                <!-- Secondary Color -->
                                                <div class="col-md-6 mb-4">
                                                    <label class="form-label fw-semibold">
                                                        <i class="fa fa-star-half-alt text-info me-1"></i> {{ __('helpdesk.livechat.fields.secondary_color') }}
                                                    </label>
                                                    <div class="input-group">
                                                        <input type="color" name="secondary_color" class="form-control form-control-color"
                                                               value="{{ $settings['secondary_color'] }}" style="max-width: 60px;" required id="secondaryColor">
                                                        <input type="text" class="form-control color-text" value="{{ $settings['secondary_color'] }}" readonly id="secondaryColorText">
                                                    </div>
                                                    <small class="text-muted d-block">{{ __('helpdesk.livechat.fields.secondary_color_help') }}</small>
                                                    <!-- Show Dark Mode Preview Toggle -->
                                                    <div class="border-top pt-3 mt-3">
                                                        <div class="form-check form-switch">
                                                            <input type="hidden" name="show_dark_mode_preview" value="0">
                                                            <input type="checkbox" name="show_dark_mode_preview" class="form-check-input" id="showDarkModePreview" value="1"
                                                                   {{ $settings['show_dark_mode_preview'] ?? true ? 'checked' : '' }}>
                                                            <label class="form-check-label" for="showDarkModePreview">
                                                                <strong><i class="fa fa-eye me-1"></i> {{ __('helpdesk.livechat.fields.secondary_color_preview') }}</strong>
                                                                <small class="d-block text-muted">{{ __('helpdesk.livechat.fields.secondary_color_preview_help') }}</small>
                                                            </label>
                                                        </div>
                                                    </div>
                                                    <!-- Dark Mode Preview Box -->
                                                    <div id="darkModePreviewBox" class="mt-3 p-3 rounded" style="background-color: #1f2937; color: {{ $settings['secondary_color'] }}; border: 2px solid {{ $settings['primary_color'] }}; {{ !($settings['show_dark_mode_preview'] ?? true) ? 'display: none;' : '' }}">
                                                        <small class="d-block text-center"><strong>{{ __('helpdesk.livechat.sections.chat_screen') }}</strong></small>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Header Title -->
                                            <div class="mb-0">
                                                <label class="form-label fw-semibold">{{ __('helpdesk.livechat.fields.header_title') }}</label>
                                                <input type="text" name="header_title" class="form-control"
                                                       value="{{ $settings['header_title'] }}" maxlength="100" required>
                                                <small class="text-muted">{{ __('helpdesk.livechat.fields.header_title_help') }}</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Additional Options Section -->
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#additionalOptions">
                                            <i class="fa fa-cog me-2 text-secondary"></i> Opciones Adicionales
                                        </button>
                                    </h2>
                                    <div id="additionalOptions" class="accordion-collapse collapse" data-bs-parent="#widgetAccordion">
                                        <div class="accordion-body">
                                            <!-- Show Timestamps -->
                                            <div class="border-bottom pb-3 mb-3">
                                                <div class="form-check form-switch">
                                                    <input type="hidden" name="show_timestamps" value="0">
                                                    <input type="checkbox" name="show_timestamps" class="form-check-input" id="showTimestamps" value="1"
                                                           {{ $settings['show_timestamps'] ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="showTimestamps">
                                                        <strong>Mostrar Timestamps</strong>
                                                        <small class="d-block text-muted">Muestra la hora de envío en cada mensaje del chat</small>
                                                    </label>
                                                </div>
                                            </div>

                                            <!-- Typing Indicator -->
                                            <div class="border-bottom pb-3 mb-3">
                                                <div class="form-check form-switch">
                                                    <input type="hidden" name="typing_indicator" value="0">
                                                    <input type="checkbox" name="typing_indicator" class="form-check-input" id="typingIndicator" value="1"
                                                           {{ $settings['typing_indicator'] ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="typingIndicator">
                                                        <strong>Indicador de Escritura</strong>
                                                        <small class="d-block text-muted">Muestra "agente está escribiendo..." cuando el agente responde</small>
                                                    </label>
                                                </div>
                                            </div>

                                            <!-- Sound Notifications -->
                                            <div class="border-bottom pb-3 mb-3">
                                                <div class="form-check form-switch">
                                                    <input type="hidden" name="sound_notifications" value="0">
                                                    <input type="checkbox" name="sound_notifications" class="form-check-input" id="soundNotifications" value="1"
                                                           {{ $settings['sound_notifications'] ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="soundNotifications">
                                                        <strong>Notificaciones de Sonido</strong>
                                                        <small class="d-block text-muted">Reproduce un sonido cuando llegan nuevos mensajes</small>
                                                    </label>
                                                </div>
                                            </div>

                                            <!-- Email Transcripts -->
                                            <div class="pb-0">
                                                <div class="form-check form-switch">
                                                    <input type="hidden" name="enable_email_transcripts" value="0">
                                                    <input type="checkbox" name="enable_email_transcripts" class="form-check-input" id="emailTranscripts" value="1"
                                                           {{ $settings['enable_email_transcripts'] ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="emailTranscripts">
                                                        <strong>Permitir Descargar Transcripción</strong>
                                                        <small class="d-block text-muted">Los clientes pueden recibir por email el historial completo del chat</small>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Timeouts Tab -->
                        <div class="tab-pane fade" id="timeouts" role="tabpanel">
                            <div class="card">
                                <div class="card-header p-4 border-bottom">
                                    <h6 class="mb-0 fw-bold">
                                        <i class="fa fa-clock text-primary me-2"></i>
                                        Configuración de Tiempos
                                    </h6>
                                </div>
                                <div class="card-body p-4">
                                    <!-- Auto Transfer -->
                                    <div class="border-bottom pb-4 mb-4">
                                        <div class="form-check mb-3">
                                            <input type="hidden" name="enable_auto_transfer" value="0">
                                            <input type="checkbox" name="enable_auto_transfer" class="form-check-input" id="enableAutoTransfer" value="1"
                                                   {{ $settings['enable_auto_transfer'] ?? false ? 'checked' : '' }}>
                                            <label class="form-check-label" for="enableAutoTransfer">
                                                <strong>Cuando el agente no responde por</strong>
                                            </label>
                                        </div>
                                        <div class="ms-4" id="autoTransferOptions" style="{{ $settings['enable_auto_transfer'] ?? false ? '' : 'display: none;' }}">
                                            <div class="input-group" style="max-width: 300px;">
                                                <input type="number" name="auto_transfer_minutes" class="form-control"
                                                       value="{{ $settings['auto_transfer_minutes'] ?? 5 }}" min="1" max="60">
                                                <span class="input-group-text">minutos</span>
                                            </div>
                                            <small class="text-muted d-block mt-2">Transferir el cliente a otro agente disponible. Si el chat está en un grupo con asignación manual, el chat se pondrá en cola en su lugar.</small>
                                        </div>
                                    </div>

                                    <!-- Auto Inactive -->
                                    <div class="border-bottom pb-4 mb-4">
                                        <div class="form-check mb-3">
                                            <input type="hidden" name="enable_auto_inactive" value="0">
                                            <input type="checkbox" name="enable_auto_inactive" class="form-check-input" id="enableAutoInactive" value="1"
                                                   {{ $settings['enable_auto_inactive'] ?? false ? 'checked' : '' }}>
                                            <label class="form-check-label" for="enableAutoInactive">
                                                <strong>Cuando no hay mensajes por</strong>
                                            </label>
                                        </div>
                                        <div class="ms-4" id="autoInactiveOptions" style="{{ $settings['enable_auto_inactive'] ?? false ? '' : 'display: none;' }}">
                                            <div class="input-group" style="max-width: 300px;">
                                                <input type="number" name="auto_inactive_minutes" class="form-control"
                                                       value="{{ $settings['auto_inactive_minutes'] ?? 10 }}" min="1" max="120">
                                                <span class="input-group-text">minutos</span>
                                            </div>
                                            <small class="text-muted d-block mt-2">Marcar el chat como inactivo. Los chats inactivos no se incluyen en el límite de chats concurrentes de los agentes.</small>
                                        </div>
                                    </div>

                                    <!-- Auto Close -->
                                    <div class="pb-0">
                                        <div class="form-check mb-3">
                                            <input type="hidden" name="enable_auto_close" value="0">
                                            <input type="checkbox" name="enable_auto_close" class="form-check-input" id="enableAutoClose" value="1"
                                                   {{ $settings['enable_auto_close'] ?? false ? 'checked' : '' }}>
                                            <label class="form-check-label" for="enableAutoClose">
                                                <strong>Cuando no hay mensajes por</strong>
                                            </label>
                                        </div>
                                        <div class="ms-4" id="autoCloseOptions" style="{{ $settings['enable_auto_close'] ?? false ? '' : 'display: none;' }}">
                                            <div class="input-group" style="max-width: 300px;">
                                                <input type="number" name="auto_close_minutes" class="form-control"
                                                       value="{{ $settings['auto_close_minutes'] ?? 15 }}" min="1" max="240">
                                                <span class="input-group-text">minutos</span>
                                            </div>
                                            <small class="text-muted d-block mt-2">Cerrar el chat automáticamente. Los clientes pueden reabrir chats cerrados enviando un nuevo mensaje a ese chat.</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Install Tab -->
                        <div class="tab-pane fade" id="install" role="tabpanel">
                            <div class="card">
                                <div class="card-header p-4 border-bottom">
                                    <h6 class="mb-0 fw-bold">
                                        <i class="fa fa-code text-primary me-2"></i>
                                        Instalación del Widget
                                    </h6>
                                </div>
                                <div class="card-body p-4">
                                    <div class="alert alert-light border-start border-4 border-primary" role="alert">
                                        <i class="fa fa-lightbulb text-primary me-2"></i>
                                        <strong>Instrucciones:</strong> Copia y pega este código antes de la etiqueta <code>&lt;/body&gt;</code> en cada página donde quieras que aparezca el widget.
                                    </div>

                                    <div class="mb-4">
                                        <label class="form-label fw-semibold mb-2">Código básico</label>
                                        <div class="bg-black text-white p-3 rounded">
                                            <pre class="mb-0 text-white"><code>&lt;script src="{{ url('/') }}/livechat-loader.js"&gt;&lt;/script&gt;</code></pre>
                                        </div>
                                    </div>

                                    <div class="alert alert-info border-start border-4 border-info" role="alert">
                                        <i class="fa fa-info-circle me-2"></i>
                                        <strong>Configuración avanzada:</strong> Si tu helpdesk está en un dominio diferente al del script loader, puedes especificarlo manualmente:
                                    </div>

                                    <div>
                                        <label class="form-label fw-semibold mb-2">Código con configuración personalizada</label>
                                        <div class="bg-black text-white p-3 rounded">
                                            <pre class="mb-0 text-white"><code>&lt;script&gt;
  window.AlsernetChatSettings = {
    widgetDomain: "{{ url('/') }}"
  };
&lt;/script&gt;
&lt;script src="{{ url('/') }}/livechat-loader.js"&gt;&lt;/script&gt;</code></pre>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Security Tab -->
                        <div class="tab-pane fade" id="security" role="tabpanel">
                            <div class="card">
                                <div class="card-header p-4 border-bottom">
                                    <h6 class="mb-0 fw-bold">
                                        <i class="fa fa-shield text-primary me-2"></i>
                                        Configuración de Seguridad
                                    </h6>
                                </div>
                                <div class="card-body p-4">
                                    <!-- Trusted Domains -->
                                    <div class="mb-4">
                                        <label class="form-label fw-semibold">Dominios de Confianza</label>
                                        <textarea name="trusted_domains" class="form-control" rows="4" placeholder="example.com, *.example.com, subdomain.example.com">{{ $settings['trusted_domains'] ?? '' }}</textarea>
                                        <small class="text-muted">Lista tus dominios y subdominios de confianza, separados por comas. Para marcar todos tus subdominios como confiables, usa un asterisco como comodín así: *.example.com. Si dejas este campo en blanco, tu widget de chat puede agregarse a cualquier dominio.</small>
                                    </div>

                                    <!-- Enforce Identity Verification -->
                                    <div class="border-bottom pb-4 mb-4">
                                        <div class="form-check form-switch">
                                            <input type="hidden" name="enforce_identity_verification" value="0">
                                            <input type="checkbox" name="enforce_identity_verification" class="form-check-input" id="enforceIdentity" value="1"
                                                   {{ $settings['enforce_identity_verification'] ?? false ? 'checked' : '' }}>
                                            <label class="form-check-label" for="enforceIdentity">
                                                <strong>Forzar Verificación de Identidad</strong>
                                                <small class="d-block text-muted">Al identificar usuarios logueados en el widget, siempre verificar identidad usando la clave secreta.</small>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Secret Key -->
                                    <div>
                                        <label class="form-label fw-semibold">Clave Secreta</label>
                                        <div class="input-group mb-2">
                                            <input type="text" class="form-control" value="{{ $settings['secret_key'] ?? Str::random(40) }}" readonly id="secretKey">
                                            <button type="button" class="btn btn-primary" onclick="copySecretKey()">
                                                <i class="fa fa-copy"></i> Copiar
                                            </button>
                                        </div>
                                        <small class="text-muted">Usa esta clave para verificar la identidad de usuarios logueados en tu aplicación.</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Preview Panel (right side) -->
            <div class="col-lg-4">
                <div class="card sticky-top" style="top: 20px;">
                    <div class="card-header p-4 border-bottom">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 fw-bold">
                                <i class="fa fa-eye text-primary me-2"></i>
                                Vista Previa
                            </h6>
                            <div class="d-flex gap-2 align-items-center">
                                <select id="previewMode" class="form-select form-select-sm" style="width: auto;">
                                    <option value="home">Home</option>
                                    <option value="conversation">Conversation</option>
                                    <option value="pre-chat">Pre-chat form</option>
                                    <option value="post-chat">Post-chat form</option>
                                    <option value="messages">Messages</option>
                                    <option value="chat-page">Chat page</option>
                                </select>
                                <button type="button" class="btn btn-sm btn-light" onclick="maximizePreview()" title="Maximizar vista">
                                    <i class="fa fa-expand"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0" style="background: #f5f5f5;">
                        <!-- Widget Container -->
                        <div class="d-flex justify-content-center align-items-center" style="min-height: 600px; padding: 20px;">
                            <!-- Widget Preview iframe -->
                            <iframe
                                id="widgetPreviewIframe"
                                src="{{ route('lc.widget') }}?settingsPreview=true"
                                class="rounded shadow-lg"
                                style="width: 360px; height: 560px; border: none; border-radius: 12px;">
                            </iframe>
                        </div>
                    </div>
                    <div class="card-footer border-top bg-light">
                        <small class="text-muted">
                            <i class="fa fa-info-circle me-1"></i>
                            Los cambios se visualizan en tiempo real. Guarda para aplicar.
                        </small>
                    </div>
                </div>
            </div>
        </div>

    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    var form = $('#livechatForm');
    var saveBtn = $('#saveBtn');
    var iframe = document.getElementById('widgetPreviewIframe');
    var iframeWindow = null;
    var originalFormData = form.serialize();

    // Wait for iframe to load
    $(iframe).on('load', function() {
        iframeWindow = iframe.contentWindow;
        console.log('Widget iframe loaded');
    });

    // Form Dirty Detection
    function checkFormDirty() {
        var currentFormData = form.serialize();
        var isDirty = originalFormData !== currentFormData;
        saveBtn.prop('disabled', !isDirty);
    }

    // Monitor all form inputs for changes
    form.on('change input', 'input, select, textarea', function() {
        checkFormDirty();
        sendSettingsToIframe();
    });

    // Send backups to iframe via postMessage
    function sendSettingsToIframe() {
        if (!iframeWindow) return;

        var settings = {
            // Widget - Home Screen
            show_avatars: $('input[name="show_avatars"]').is(':checked'),
            show_help_center: $('input[name="show_help_center"]').is(':checked'),
            hide_suggested_articles: $('input[name="hide_suggested_articles"]').is(':checked'),
            show_tickets_section: $('input[name="show_tickets_section"]').is(':checked'),
            enable_send_message: $('input[name="enable_send_message"]').is(':checked'),
            enable_create_ticket: $('input[name="enable_create_ticket"]').is(':checked'),
            enable_search_help: $('input[name="enable_search_help"]').is(':checked'),

            // Widget - Chat Screen
            welcome_message: $('input[name="welcome_message"]').val(),
            input_placeholder: $('input[name="input_placeholder"]').val(),
            offline_message: $('textarea[name="no_agents_message"]').val(),
            queue_message: $('textarea[name="queue_message"]').val(),

            // Widget - Launcher
            position: $('select[name="position"]').val(),
            side_spacing: parseInt($('input[name="side_spacing"]').val()) || 16,
            bottom_spacing: parseInt($('input[name="bottom_spacing"]').val()) || 16,
            hide_launcher: $('input[name="hide_launcher"]').is(':checked'),

            // Widget - Style
            primary_color: $('input[name="primary_color"]').val(),
            secondary_color: $('input[name="secondary_color"]').val(),
            header_title: $('input[name="header_title"]').val(),

            // Widget - Additional Options
            show_timestamps: $('input[name="show_timestamps"]').is(':checked'),
            typing_indicator: $('input[name="typing_indicator"]').is(':checked'),
            sound_notifications: $('input[name="sound_notifications"]').is(':checked'),
            enable_email_transcripts: $('input[name="enable_email_transcripts"]').is(':checked')
        };

        iframeWindow.postMessage({
            source: 'be-backups-editor',
            type: 'setValues',
            values: settings
        }, '*');
    }

    // Preview Mode Switcher
    $('#previewMode').on('change', function() {
        var mode = $(this).val();
        var baseUrl = '{{ route("lc.widget") }}';
        var newSrc = baseUrl + '?settingsPreview=true';

        // Append route based on mode
        if (mode === 'conversation') {
            newSrc += '#/conversation';
        } else if (mode === 'pre-chat') {
            newSrc += '#/pre-chat';
        } else if (mode === 'post-chat') {
            newSrc += '#/post-chat';
        } else if (mode === 'messages') {
            newSrc += '#/messages';
        } else if (mode === 'chat-page') {
            newSrc += '#/chat-page';
        }

        iframe.src = newSrc;
    });

    // Maximize preview
    window.maximizePreview = function() {
        var previewMode = $('#previewMode').val();
        var modalTitle = previewMode.charAt(0).toUpperCase() + previewMode.slice(1).replace('-', ' ');

        // Create fullscreen modal with jQuery
        var $modal = $('<div class="modal fade" id="previewModal" tabindex="-1">' +
            '<div class="modal-dialog modal-dialog-centered modal-lg">' +
                '<div class="modal-content" style="background: #f5f5f5; border: none;">' +
                    '<div class="modal-header border-bottom">' +
                        '<h5 class="modal-title fw-bold">Vista Previa - ' + modalTitle + '</h5>' +
                        '<button type="button" class="btn-close" data-bs-dismiss="modal"></button>' +
                    '</div>' +
                    '<div class="modal-body d-flex justify-content-center align-items-center" style="min-height: 600px;">' +
                        '<iframe src="' + iframe.src + '" style="width: 360px; height: 560px; border: none; border-radius: 12px;"></iframe>' +
                    '</div>' +
                '</div>' +
            '</div>' +
        '</div>');

        $('body').append($modal);
        $modal.modal('show');
        $modal.on('hidden.bs.modal', function() {
            $(this).remove();
        });
    };

    // Toggle timeout options
    $('#enableAutoTransfer').on('change', function() {
        $('#autoTransferOptions').toggle($(this).is(':checked'));
    });

    $('#enableAutoInactive').on('change', function() {
        $('#autoInactiveOptions').toggle($(this).is(':checked'));
    });

    $('#enableAutoClose').on('change', function() {
        $('#autoCloseOptions').toggle($(this).is(':checked'));
    });

    // Toggle dark mode preview visibility
    $('#showDarkModePreview').on('change', function() {
        $('#darkModePreviewBox').slideToggle(300);
    });

    // Copy secret key to clipboard
    window.copySecretKey = function() {
        var secretKey = $('#secretKey').val();
        navigator.clipboard.writeText(secretKey).then(() => {
            toastr.success('Clave secreta copiada al portapapeles', 'Copiado');
        }).catch(() => {
            toastr.error('Error al copiar al portapapeles', 'Error');
        });
    };

    // Sync color pickers with text inputs and update dark mode preview
    $('input[type="color"]').on('input change', function() {
        var colorValue = $(this).val();
        $(this).siblings('.color-text').val(colorValue);

        // Update dark mode preview for secondary color
        if ($(this).attr('id') === 'secondaryColor') {
            $(this).closest('.col-md-6').find('[style*="background-color"]').css('color', colorValue);
        }
    });

    // Reset form dirty state after successful save
    form.on('submit', function(e) {
        console.log('Form submitting...');
        console.log('Form data:', form.serialize());
        saveBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-1"></i> Guardando...');
    });

    @if (session('success'))
        toastr.success('{{ session('success') }}', 'Configuración actualizada');
        setTimeout(function() {
            originalFormData = form.serialize();
            checkFormDirty();
            sendSettingsToIframe();
        }, 100);
    @endif
});
</script>
@endpush
