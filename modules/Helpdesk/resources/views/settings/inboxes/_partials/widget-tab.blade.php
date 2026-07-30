@push('styles')
<style>
.hd-feature-row { background: #f5f6f8; }
.hd-num-input { width: 80px; }
</style>
@endpush

@php
    $livechatEnabled = \Nwidart\Modules\Facades\Module::find('HelpdeskLivechat')?->isEnabled() === true;
    $engagementEnabled = \Nwidart\Modules\Facades\Module::find('Engagement')?->isEnabled() === true;

    /** @var \Modules\HelpdeskLivechat\Models\Channels\Web|null $web */
    $web = null;
    if ($livechatEnabled
        && $inbox->channel_type === 'web'
        && class_exists(\Modules\HelpdeskLivechat\Models\Channels\Web::class)
        && $channel instanceof \Modules\HelpdeskLivechat\Models\Channels\Web) {
        $web = $channel;
    }

    // Helper: return old() value first, then Web model value, then default.
    $w = fn (string $field, mixed $default = '') => old("widget.{$field}", $web?->{$field} ?? $default);

    $cmsTypes = $livechatEnabled && class_exists(\Modules\HelpdeskLivechat\Models\Channels\Web::class)
        ? \Modules\HelpdeskLivechat\Models\Channels\Web::CMS_TYPES
        : ['custom' => 'Custom (HTML/JS)'];
    $currentCmsType = $w('cms_type', 'custom');
    $currentIntegrationId = $w('platform_integration_id', null);
@endphp

@unless($livechatEnabled)
    <div class="alert alert-warning d-flex align-items-start gap-2 mb-0">
        <i class="fas fa-triangle-exclamation mt-1"></i>
        <div>
            <strong>Módulo HelpdeskLivechat deshabilitado.</strong><br>
            <span class="small">El canal Web requiere el módulo HelpdeskLivechat para funcionar. Actívalo desde la gestión de módulos para configurar el widget.</span>
        </div>
    </div>
@else

{{-- Sub-tab nav --}}
<ul class="nav nav-pills nav-fill flex-wrap gap-1 mb-3" id="widgetSubTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#wt-messages" type="button" role="tab">
            <i class="fas fa-comment me-1"></i>Mensajes
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" data-bs-toggle="pill" data-bs-target="#wt-appearance" type="button" role="tab">
            <i class="fas fa-palette me-1"></i>Apariencia
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" data-bs-toggle="pill" data-bs-target="#wt-features" type="button" role="tab">
            <i class="fas fa-sliders me-1"></i>Funciones
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" data-bs-toggle="pill" data-bs-target="#wt-forms" type="button" role="tab">
            <i class="fas fa-wpforms me-1"></i>Formularios
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" data-bs-toggle="pill" data-bs-target="#wt-timeouts" type="button" role="tab">
            <i class="fas fa-clock me-1"></i>Tiempos
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" data-bs-toggle="pill" data-bs-target="#wt-security" type="button" role="tab">
            <i class="fas fa-shield-halved me-1"></i>Seguridad
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" data-bs-toggle="pill" data-bs-target="#wt-assistance" type="button" role="tab">
            <i class="fas fa-eye me-1"></i>Asistencia
        </button>
    </li>
    @if($web?->website_token)
    <li class="nav-item" role="presentation">
        <button class="nav-link" data-bs-toggle="pill" data-bs-target="#wt-install" type="button" role="tab">
            <i class="fas fa-code me-1"></i>Instalación
        </button>
    </li>
    @endif
</ul>

<div class="tab-content" id="widgetSubTabsContent">

    {{-- ── Mensajes ────────────────────────────────────────────────── --}}
    <div class="tab-pane fade show active" id="wt-messages" role="tabpanel">
        <div class="card">
            <div class="card-header p-4 border-bottom border-light">
                <h6 class="mb-1 fw-bold">Mensajes del widget</h6>
                <p class="small mb-0 text-muted">Textos que ven los visitantes al abrir el chat</p>
            </div>
            <div class="card-body p-4">
                <div class="mb-3">
                    <label class="form-label" for="wt_header_title">Título del chat</label>
                    <input type="text" id="wt_header_title" name="widget[header_title]" class="form-control"
                           value="{{ $w('header_title', 'Chat de Soporte') }}" maxlength="100">
                    <div class="form-text">Aparece en la cabecera del widget.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="wt_welcome_message">Mensaje de bienvenida</label>
                    <input type="text" id="wt_welcome_message" name="widget[welcome_message]" class="form-control"
                           value="{{ $w('welcome_message', '¡Hola! ¿Cómo podemos ayudarte?') }}" maxlength="200">
                    <div class="form-text">Primer mensaje que aparece al abrir el chat.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="wt_input_placeholder">Placeholder del campo de mensaje</label>
                    <input type="text" id="wt_input_placeholder" name="widget[input_placeholder]" class="form-control"
                           value="{{ $w('input_placeholder', 'Escribe tu mensaje...') }}" maxlength="100">
                    <div class="form-text">Texto de ayuda dentro del campo de escritura.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="wt_queue_message">Mensaje en cola de espera</label>
                    <textarea id="wt_queue_message" name="widget[queue_message]" class="form-control" rows="2"
                              maxlength="500" placeholder="Uno de nuestros agentes estará contigo en breve.">{{ $w('queue_message') }}</textarea>
                    <div class="form-text">Mostrado mientras el visitante espera un agente.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="wt_offline_message">Mensaje sin agentes disponibles</label>
                    <textarea id="wt_offline_message" name="widget[offline_message]" class="form-control" rows="2"
                              maxlength="500" placeholder="Nuestros agentes no están disponibles en este momento.">{{ $w('offline_message') }}</textarea>
                    <div class="form-text">Mostrado cuando ningún agente está en línea.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="wt_welcome_title">Título de bienvenida (home)</label>
                    <input type="text" id="wt_welcome_title" name="widget[welcome_title]" class="form-control"
                           value="{{ $w('welcome_title') }}" maxlength="120">
                </div>
                <div class="mb-0">
                    <label class="form-label" for="wt_welcome_tagline">Tagline de bienvenida (home)</label>
                    <input type="text" id="wt_welcome_tagline" name="widget[welcome_tagline]" class="form-control"
                           value="{{ $w('welcome_tagline') }}" maxlength="200">
                </div>
            </div>
        </div>
    </div>

    {{-- ── Apariencia ──────────────────────────────────────────────── --}}
    <div class="tab-pane fade" id="wt-appearance" role="tabpanel">
        <div class="card">
            <div class="card-header p-4 border-bottom border-light">
                <h6 class="mb-1 fw-bold">Apariencia del widget</h6>
                <p class="small mb-0 text-muted">Colores, posición y visibilidad del lanzador</p>
            </div>
            <div class="card-body p-4">
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label" for="wt_primary_color">Color primario</label>
                        <div class="input-group">
                            <input type="color" id="wt_primary_color" name="widget[widget_color]"
                                   class="form-control form-control-color wt-color-picker"
                                   value="{{ $w('widget_color', '#90bb13') }}"
                                   data-hex-target="#wt_primary_color_hex">
                            <input type="text" id="wt_primary_color_hex" class="form-control"
                                   value="{{ $w('widget_color', '#90bb13') }}" readonly>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="wt_secondary_color">Color secundario</label>
                        <div class="input-group">
                            <input type="color" id="wt_secondary_color" name="widget[secondary_color]"
                                   class="form-control form-control-color wt-color-picker"
                                   value="{{ $w('secondary_color', '#ffffff') }}"
                                   data-hex-target="#wt_secondary_color_hex">
                            <input type="text" id="wt_secondary_color_hex" class="form-control"
                                   value="{{ $w('secondary_color', '#ffffff') }}" readonly>
                        </div>
                    </div>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label" for="wt_position">Posición</label>
                        <select id="wt_position" name="widget[widget_position]" class="form-select">
                            <option value="bottom-right" @selected($w('widget_position', 'bottom-right') === 'bottom-right')>Inferior derecha</option>
                            <option value="bottom-left"  @selected($w('widget_position') === 'bottom-left')>Inferior izquierda</option>
                            <option value="top-right"    @selected($w('widget_position') === 'top-right')>Superior derecha</option>
                            <option value="top-left"     @selected($w('widget_position') === 'top-left')>Superior izquierda</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="wt_side_spacing">Espacio lateral (px)</label>
                        <input type="number" id="wt_side_spacing" name="widget[side_spacing]" class="form-control"
                               value="{{ $w('side_spacing', 16) }}" min="0" max="200">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="wt_bottom_spacing">Espacio inferior (px)</label>
                        <input type="number" id="wt_bottom_spacing" name="widget[bottom_spacing]" class="form-control"
                               value="{{ $w('bottom_spacing', 16) }}" min="0" max="200">
                    </div>
                </div>
                <div class="form-check form-switch mb-0">
                    <input type="hidden" name="widget[hide_launcher]" value="0">
                    <input class="form-check-input" type="checkbox" id="wt_hide_launcher"
                           name="widget[hide_launcher]" value="1" @checked($w('hide_launcher', false))>
                    <label class="form-check-label" for="wt_hide_launcher">
                        <span class="fw-semibold">Ocultar lanzador</span>
                        <small class="d-block text-muted">El botón flotante estará oculto y debe mostrarse vía API</small>
                    </label>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Funciones ───────────────────────────────────────────────── --}}
    <div class="tab-pane fade" id="wt-features" role="tabpanel">
        <div class="card">
            <div class="card-header p-4 border-bottom border-light">
                <h6 class="mb-1 fw-bold">Funciones del widget</h6>
                <p class="small mb-0 text-muted">Activa o desactiva las secciones y comportamientos del chat</p>
            </div>
            <div class="card-body p-4">
                @php
                    $features = [
                        'show_avatars'             => ['label' => 'Mostrar avatares de agentes',      'help' => 'Muestra la foto de perfil de los agentes en el widget', 'default' => true],
                        'show_help_center'         => ['label' => 'Mostrar centro de ayuda',          'help' => 'Incluye una tarjeta de enlace al centro de ayuda', 'default' => true],
                        'hide_suggested_articles'  => ['label' => 'Ocultar artículos sugeridos',      'help' => 'No muestra artículos del helpcenter en home', 'default' => false],
                        'show_tickets_section'     => ['label' => 'Mostrar sección de tickets',       'help' => 'Permite crear y ver tickets desde el widget', 'default' => true],
                        'enable_send_message'      => ['label' => 'Habilitar envío de mensajes',      'help' => 'Muestra el botón "Envíanos un mensaje"', 'default' => true],
                        'enable_create_ticket'     => ['label' => 'Habilitar crear ticket',           'help' => 'Muestra la opción de crear un ticket nuevo', 'default' => true],
                        'enable_search_help'       => ['label' => 'Habilitar buscar ayuda',           'help' => 'Muestra el acceso rápido a buscar en el helpcenter', 'default' => true],
                        'show_timestamps'          => ['label' => 'Mostrar hora de envío',            'help' => 'Muestra el timestamp en cada mensaje', 'default' => true],
                        'typing_indicator'         => ['label' => 'Indicador de escritura',           'help' => 'Muestra cuando el agente está escribiendo', 'default' => true],
                        'sound_notifications'      => ['label' => 'Notificaciones de sonido',         'help' => 'Reproduce sonido al recibir mensajes nuevos', 'default' => true],
                        'enable_email_transcripts' => ['label' => 'Descargar transcripción',          'help' => 'Permite al visitante recibir el historial por email', 'default' => false],
                        'show_dark_mode_preview'   => ['label' => 'Soporte para modo oscuro',         'help' => 'El widget se adapta al modo oscuro del navegador', 'default' => true],
                    ];
                @endphp
                @foreach($features as $field => $meta)
                    <div class="hd-feature-row d-flex align-items-start gap-3 p-2 rounded border mb-2">
                        <div class="form-check form-switch mb-0 pt-1 flex-shrink-0">
                            <input type="hidden" name="widget[{{ $field }}]" value="0">
                            <input class="form-check-input" type="checkbox"
                                   id="wt_{{ $field }}" name="widget[{{ $field }}]" value="1"
                                   @checked((bool) $w($field, $meta['default']))>
                        </div>
                        <label class="form-check-label mb-0 w-100" for="wt_{{ $field }}">
                            <span class="fw-semibold d-block">{{ $meta['label'] }}</span>
                            <small class="text-muted">{{ $meta['help'] }}</small>
                        </label>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ── Formularios ─────────────────────────────────────────────── --}}
    <div class="tab-pane fade" id="wt-forms" role="tabpanel">
        <div class="card">
            <div class="card-header p-4 border-bottom border-light">
                <h6 class="mb-1 fw-bold">Formularios</h6>
                <p class="small mb-0 text-muted">Formularios pre y post chat, y configuración de página de chat</p>
            </div>
            <div class="card-body p-4">
                {{-- Pre-chat form --}}
                <h6 class="fw-bold border-bottom pb-2 mb-3">Formulario pre-chat</h6>
                <div class="form-check form-switch mb-2">
                    <input type="hidden" name="widget[pre_chat_form_enabled]" value="0">
                    <input class="form-check-input" type="checkbox" id="wt_pre_chat_form_enabled"
                           name="widget[pre_chat_form_enabled]" value="1"
                           @checked($w('pre_chat_form_enabled', false))>
                    <label class="form-check-label" for="wt_pre_chat_form_enabled">
                        <span class="fw-semibold">Activar formulario pre-chat</span>
                        <small class="d-block text-muted">Solicita nombre y email antes de iniciar el chat</small>
                    </label>
                </div>
                <div class="mb-4">
                    <label class="form-label" for="wt_pre_chat_info">Mensaje informativo</label>
                    <textarea id="wt_pre_chat_info" name="widget[pre_chat_info]" class="form-control" rows="2"
                              placeholder="Ej: Por favor comparte tus datos para poder ayudarte mejor">{{ $w('pre_chat_info') }}</textarea>
                </div>

                {{-- Post-chat form --}}
                <h6 class="fw-bold border-bottom pb-2 mb-3">Formulario post-chat</h6>
                <div class="form-check form-switch mb-2">
                    <input type="hidden" name="widget[post_chat_form_enabled]" value="0">
                    <input class="form-check-input" type="checkbox" id="wt_post_chat_form_enabled"
                           name="widget[post_chat_form_enabled]" value="1"
                           @checked($w('post_chat_form_enabled', false))>
                    <label class="form-check-label" for="wt_post_chat_form_enabled">
                        <span class="fw-semibold">Activar formulario post-chat</span>
                        <small class="d-block text-muted">Muestra una encuesta de satisfacción al cerrar el chat</small>
                    </label>
                </div>
                <div class="mb-4">
                    <label class="form-label" for="wt_post_chat_info">Mensaje informativo</label>
                    <textarea id="wt_post_chat_info" name="widget[post_chat_info]" class="form-control" rows="2"
                              placeholder="Ej: ¿Cómo calificarías la atención recibida?">{{ $w('post_chat_info') }}</textarea>
                </div>

                {{-- Chat page --}}
                <h6 class="fw-bold border-bottom pb-2 mb-3">Página de chat</h6>
                <div class="mb-3">
                    <label class="form-label" for="wt_chat_page_title">Título de la página</label>
                    <input type="text" id="wt_chat_page_title" name="widget[chat_page_title]" class="form-control"
                           value="{{ $w('chat_page_title') }}" maxlength="200"
                           placeholder="Ej: Chat de soporte">
                </div>
                <div class="mb-0">
                    <label class="form-label" for="wt_chat_page_subtitle">Subtítulo</label>
                    <textarea id="wt_chat_page_subtitle" name="widget[chat_page_subtitle]" class="form-control" rows="2"
                              placeholder="Ej: Estamos aquí para ayudarte">{{ $w('chat_page_subtitle') }}</textarea>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Tiempos ─────────────────────────────────────────────────── --}}
    <div class="tab-pane fade" id="wt-timeouts" role="tabpanel">
        <div class="card">
            <div class="card-header p-4 border-bottom border-light">
                <h6 class="mb-1 fw-bold">Tiempos de espera automáticos</h6>
                <p class="small mb-0 text-muted">Define umbrales para transferir, inactivar o cerrar conversaciones automáticamente</p>
            </div>
            <div class="card-body p-4">

                {{-- Auto transfer --}}
                <div class="border rounded p-3 mb-3">
                    <div class="form-check form-switch mb-2">
                        <input type="hidden" name="widget[enable_auto_transfer]" value="0">
                        <input class="form-check-input wt-timeout-toggle" type="checkbox"
                               id="wt_enable_auto_transfer" name="widget[enable_auto_transfer]" value="1"
                               data-target="#wt_auto_transfer_wrap"
                               @checked($w('enable_auto_transfer', false))>
                        <label class="form-check-label fw-semibold" for="wt_enable_auto_transfer">
                            Transferencia automática de agente
                        </label>
                    </div>
                    <div id="wt_auto_transfer_wrap" @class(['d-none' => ! $w('enable_auto_transfer', false)])>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span class="text-muted small">Si el agente no responde en</span>
                            <input type="number" name="widget[auto_transfer_minutes]" class="form-control form-control-sm hd-num-input"
                                   value="{{ $w('auto_transfer_minutes', 5) }}" min="1" max="60">
                            <span class="text-muted small">minutos, transferir a otro agente disponible.</span>
                        </div>
                    </div>
                </div>

                {{-- Auto inactive --}}
                <div class="border rounded p-3 mb-3">
                    <div class="form-check form-switch mb-2">
                        <input type="hidden" name="widget[enable_auto_inactive]" value="0">
                        <input class="form-check-input wt-timeout-toggle" type="checkbox"
                               id="wt_enable_auto_inactive" name="widget[enable_auto_inactive]" value="1"
                               data-target="#wt_auto_inactive_wrap"
                               @checked($w('enable_auto_inactive', false))>
                        <label class="form-check-label fw-semibold" for="wt_enable_auto_inactive">
                            Marcar como inactivo por inactividad
                        </label>
                    </div>
                    <div id="wt_auto_inactive_wrap" @class(['d-none' => ! $w('enable_auto_inactive', false)])>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span class="text-muted small">Si no hay mensajes durante</span>
                            <input type="number" name="widget[auto_inactive_minutes]" class="form-control form-control-sm hd-num-input"
                                   value="{{ $w('auto_inactive_minutes', 10) }}" min="1" max="120">
                            <span class="text-muted small">minutos, marcar el chat como inactivo.</span>
                        </div>
                    </div>
                </div>

                {{-- Auto close --}}
                <div class="border rounded p-3 mb-0">
                    <div class="form-check form-switch mb-2">
                        <input type="hidden" name="widget[enable_auto_close]" value="0">
                        <input class="form-check-input wt-timeout-toggle" type="checkbox"
                               id="wt_enable_auto_close" name="widget[enable_auto_close]" value="1"
                               data-target="#wt_auto_close_wrap"
                               @checked($w('enable_auto_close', false))>
                        <label class="form-check-label fw-semibold" for="wt_enable_auto_close">
                            Cerrar conversaciones inactivas automáticamente
                        </label>
                    </div>
                    <div id="wt_auto_close_wrap" @class(['d-none' => ! $w('enable_auto_close', false)])>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span class="text-muted small">Si no hay mensajes durante</span>
                            <input type="number" name="widget[auto_close_minutes]" class="form-control form-control-sm hd-num-input"
                                   value="{{ $w('auto_close_minutes', 15) }}" min="1" max="240">
                            <span class="text-muted small">minutos, cerrar el chat automáticamente.</span>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- ── Seguridad ───────────────────────────────────────────────── --}}
    <div class="tab-pane fade" id="wt-security" role="tabpanel">
        <div class="card">
            <div class="card-header p-4 border-bottom border-light">
                <h6 class="mb-1 fw-bold">Seguridad del widget</h6>
                <p class="small mb-0 text-muted">Restricciones de dominio y verificación de identidad</p>
            </div>
            <div class="card-body p-4">
                <div class="mb-4">
                    <label class="form-label" for="wt_trusted_domains">Dominios permitidos (CORS)</label>
                    <textarea id="wt_trusted_domains" name="widget[trusted_domains]" class="form-control" rows="4"
                              placeholder="ejemplo.com, *.mi-empresa.com">{{ $w('trusted_domains') }}</textarea>
                    <div class="form-text">
                        Lista tus dominios separados por comas. Usa <code>*</code> como comodín: <code>*.ejemplo.com</code>.
                        Si lo dejas vacío, el widget puede insertarse en cualquier dominio.
                    </div>
                </div>
                <div class="border-top pt-4 mb-4">
                    <div class="form-check form-switch">
                        <input type="hidden" name="widget[enforce_identity_verification]" value="0">
                        <input class="form-check-input" type="checkbox" id="wt_enforce_identity_verification"
                               name="widget[enforce_identity_verification]" value="1"
                               @checked($w('enforce_identity_verification', false))>
                        <label class="form-check-label" for="wt_enforce_identity_verification">
                            <span class="fw-semibold">Forzar verificación de identidad</span>
                            <small class="d-block text-muted">
                                Al identificar usuarios autenticados en el widget, siempre verificar la identidad con la clave secreta (HMAC)
                            </small>
                        </label>
                    </div>
                </div>
                @if($web?->hmac_token)
                <div class="border-top pt-4">
                    <label class="form-label fw-semibold">Clave secreta HMAC</label>
                    <p class="text-muted small mb-2">Usa esta clave para firmar la identidad de los usuarios autenticados en tu backend</p>
                    <div class="input-group">
                        <input type="text" id="wt_hmac_token" class="form-control font-monospace bg-light"
                               value="{{ $web->hmac_token }}" readonly>
                        <button type="button" class="btn btn-outline-secondary" onclick="copyWidgetHmacToken()">
                            <i class="fas fa-copy me-1"></i><span id="wt_copy_hmac_label">Copiar</span>
                        </button>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ── Instalación (solo en edición) ──────────────────────────── --}}
    @if($web?->website_token)
    {{-- ── Asistencia (live view + screen share) ─────────────────── --}}
    <div class="tab-pane fade" id="wt-assistance" role="tabpanel">
        <div class="card">
            <div class="card-header p-4 border-bottom border-light">
                <h6 class="mb-1 fw-bold">Asistencia en tiempo real</h6>
                <p class="small mb-0 text-muted">Permite al agente ver la actividad del visitante o recibir su pantalla compartida durante una conversación.</p>
            </div>
            <div class="card-body p-4">

                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" role="switch" id="wt_enable_live_view"
                           name="widget[enable_live_view]" value="1"
                           @checked($w('enable_live_view', false))>
                    <label class="form-check-label" for="wt_enable_live_view">
                        <strong>Permitir live view del visitante</strong>
                    </label>
                    <div class="small text-muted mt-1">
                        El agente verá la actividad del navegador del visitante (DOM, scroll, clics) en tiempo real.
                        Las contraseñas y campos marcados con <code>data-private</code> se enmascaran automáticamente.
                        El visitante debe dar consentimiento explícito desde el widget.
                    </div>
                </div>

                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" role="switch" id="wt_enable_screen_share"
                           name="widget[enable_screen_share]" value="1"
                           @checked($w('enable_screen_share', false))>
                    <label class="form-check-label" for="wt_enable_screen_share">
                        <strong>Permitir compartir pantalla (WebRTC)</strong>
                    </label>
                    <div class="small text-muted mt-1">
                        El visitante podrá compartir su pantalla con el agente vía WebRTC.
                        No disponible en Safari iOS. Requiere conexión HTTPS.
                    </div>
                </div>

                <div class="alert alert-light border d-flex align-items-start gap-2 mb-0">
                    <i class="fas fa-shield-halved text-warning mt-1"></i>
                    <div class="small mb-0">
                        <strong>Aviso de privacidad (GDPR/LOPD):</strong>
                        Estas funciones procesan datos personales del visitante. Asegúrate de informar en tu política
                        de privacidad y obtener consentimiento explícito antes de activar la grabación.
                    </div>
                </div>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header p-4 border-bottom border-light">
                <h6 class="mb-1 fw-bold">Catálogo de producto (coviewer)</h6>
                <p class="small mb-0 text-muted">Muestra productos de tu catálogo dentro del chat: el agente puede compartirlos y el bot puede recomendarlos a partir de la pregunta del visitante.</p>
            </div>
            <div class="card-body p-4">
                <div class="mb-3">
                    <label class="form-label fw-semibold" for="wt_product_feed_url">URL del product feed (JSON)</label>
                    <input type="url" id="wt_product_feed_url" name="widget[product_feed_url]" class="form-control"
                           value="{{ $w('product_feed_url', '') }}" placeholder="https://tutienda.com/feed.json">
                    <div class="small text-muted mt-1">
                        Array JSON de productos con <code>id</code>, <code>title</code>, <code>image_url</code>,
                        <code>url</code>, <code>price</code>, <code>currency</code>. Se descarga y cachea automáticamente.
                    </div>
                </div>

                <div class="form-check form-switch mb-0">
                    <input type="hidden" name="widget[enable_product_search]" value="0">
                    <input class="form-check-input" type="checkbox" role="switch" id="wt_enable_product_search"
                           name="widget[enable_product_search]" value="1"
                           @checked($w('enable_product_search', false))>
                    <label class="form-check-label" for="wt_enable_product_search">
                        <strong>Activar recomendación por bot</strong>
                    </label>
                    <div class="small text-muted mt-1">
                        Cuando el visitante escribe, el bot busca en el catálogo y responde con un carrusel de productos.
                        Requiere una URL de feed configurada.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="wt-install" role="tabpanel">
        <div class="card">
            <div class="card-header p-4 border-bottom border-light">
                <h6 class="mb-1 fw-bold">Instalar widget en tu sitio</h6>
                <p class="small mb-0 text-muted">Selecciona la plataforma donde se instalará el widget para obtener el código adaptado</p>
            </div>
            <div class="card-body p-4">

                {{-- CMS platform selector --}}
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" for="wt_cms_type">Tipo de sitio</label>
                        <select name="widget[cms_type]" id="wt_cms_type" class="form-select">
                            @foreach($cmsTypes as $key => $label)
                                <option value="{{ $key }}" @selected($currentCmsType === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">El snippet se adapta a la plataforma seleccionada.</small>
                    </div>

                    @if($engagementEnabled)
                        <div class="col-md-6 {{ $currentCmsType === 'custom' ? 'd-none' : '' }}" id="wt_integration_wrapper">
                            <label class="form-label fw-semibold" for="wt_platform_integration">
                                Vincular con integración Engagement
                                <span class="text-muted small">(opcional)</span>
                            </label>
                            <select name="widget[platform_integration_id]" id="wt_platform_integration" class="form-select"
                                    data-current-id="{{ $currentIntegrationId }}"
                                    data-url="{{ route('settings.helpdesk-livechat.platform-integrations') }}">
                                <option value="">Sin vincular</option>
                            </select>
                            <small class="text-muted">Sincroniza customer/cart/orders desde el CMS.</small>
                        </div>
                    @endif
                </div>

                {{-- Per-platform install snippets — only one is visible at a time --}}
                @php
                    $snippetSamples = [];
                    if ($web) {
                        $original = $web->cms_type;
                        foreach (array_keys($cmsTypes) as $cms) {
                            $web->cms_type = $cms;
                            $snippetSamples[$cms] = $web->getInstallSnippet();
                        }
                        $web->cms_type = $original;
                    }
                @endphp

                @foreach($snippetSamples as $cms => $snippet)
                    <div class="position-relative mb-3 wt-install-snippet @if($cms !== $currentCmsType) d-none @endif"
                         data-cms="{{ $cms }}">
                        <pre class="bg-dark text-light rounded p-3 small mb-0 wt-install-code">{{ $snippet }}</pre>
                        <button type="button"
                                class="btn btn-sm btn-light position-absolute top-0 end-0 m-2 wt-copy-btn">
                            <i class="fas fa-copy me-1"></i><span class="wt-copy-label">Copiar</span>
                        </button>
                    </div>
                @endforeach

                @if(in_array($currentCmsType, ['shopify', 'magento', 'wordpress'], true))
                    <div class="alert alert-info d-flex align-items-start gap-2 mb-3">
                        <i class="fas fa-info-circle mt-1"></i>
                        <div class="small">
                            Próximamente publicaremos un módulo nativo para esta plataforma. Por ahora puedes usar el snippet genérico — funciona igual.
                        </div>
                    </div>
                @endif

                <div class="alert alert-light border mb-0">
                    <strong>Token del widget:</strong> <code class="user-select-all">{{ $web->website_token }}</code>
                </div>
            </div>
        </div>
    </div>
    @endif

</div>{{-- /tab-content --}}

<script>
(function () {
    // Show/hide dependent timeout fields
    document.querySelectorAll('.wt-timeout-toggle').forEach(function (cb) {
        cb.addEventListener('change', function () {
            var target = document.querySelector(cb.dataset.target);
            if (target) {
                target.style.display = cb.checked ? '' : 'none';
            }
        });
    });

    // Sync color picker hex display
    document.querySelectorAll('.wt-color-picker').forEach(function (picker) {
        picker.addEventListener('input', function () {
            var hexInput = document.querySelector(picker.dataset.hexTarget);
            if (hexInput) {
                hexInput.value = picker.value;
            }
        });
    });

    // CMS type selector → toggle which install snippet is visible
    var cmsSelect = document.getElementById('wt_cms_type');
    var integrationWrapper = document.getElementById('wt_integration_wrapper');
    var integrationSelect = document.getElementById('wt_platform_integration');

    function showSnippetFor(cms) {
        document.querySelectorAll('.wt-install-snippet').forEach(function (el) {
            el.classList.toggle('d-none', el.dataset.cms !== cms);
        });
    }

    function fetchIntegrations(cms) {
        if (! integrationSelect) {
            return;
        }
        var url = integrationSelect.dataset.url;
        var currentId = integrationSelect.dataset.currentId;
        integrationSelect.innerHTML = '<option value="">Sin vincular</option>';

        if (cms === 'custom' || ! url) {
            return;
        }

        fetch(url + '?platform=' + encodeURIComponent(cms), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        })
            .then(function (r) { return r.ok ? r.json() : []; })
            .then(function (rows) {
                if (! Array.isArray(rows) || rows.length === 0) {
                    return;
                }
                rows.forEach(function (row) {
                    var opt = document.createElement('option');
                    opt.value = row.id;
                    opt.textContent = row.name + (row.store_url ? ' — ' + row.store_url : '');
                    if (String(row.id) === String(currentId)) {
                        opt.selected = true;
                    }
                    integrationSelect.appendChild(opt);
                });
            })
            .catch(function () { /* silent — endpoint optional */ });
    }

    if (cmsSelect) {
        cmsSelect.addEventListener('change', function () {
            var val = cmsSelect.value;
            showSnippetFor(val);

            if (integrationWrapper) {
                integrationWrapper.classList.toggle('d-none', val === 'custom');
            }
            fetchIntegrations(val);
        });

        // Initial population on page load (only if Engagement is wired)
        if (integrationSelect) {
            fetchIntegrations(cmsSelect.value);
        }
    }

    // Copy buttons for install snippets (event delegation)
    document.querySelectorAll('.wt-copy-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var pre = btn.parentElement.querySelector('.wt-install-code');
            var label = btn.querySelector('.wt-copy-label');
            if (! pre || ! navigator.clipboard) {
                return;
            }
            navigator.clipboard.writeText(pre.textContent).then(function () {
                if (label) {
                    label.textContent = '¡Copiado!';
                    setTimeout(function () { label.textContent = 'Copiar'; }, 2000);
                }
            });
        });
    });
})();

function copyWidgetHmacToken() {
    var val = document.getElementById('wt_hmac_token').value;
    navigator.clipboard.writeText(val).then(function () {
        var el = document.getElementById('wt_copy_hmac_label');
        el.textContent = '¡Copiada!';
        setTimeout(function () { el.textContent = 'Copiar'; }, 2000);
    });
}
</script>

@endunless
