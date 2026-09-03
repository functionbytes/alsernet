{{--
    Columnas 2+3 del workspace de 3 columnas (ver emails/index.blade.php):
    cabecera con pestañas + panel principal + sidebar de acciones/contexto.

    EmailLogController::renderWorkspace() sirve ESTE mismo archivo tal cual
    como respuesta AJAX (fragmento, sin layout) cuando la petición a la ruta
    "show" llega con $request->ajax()/wantsJson() — nunca debe llevar
    @extends/@section de layouts.theme ni <script> propio: todo el JS que
    actúa sobre este contenido vive delegado en document dentro del
    @push('scripts') de emails/index.blade.php, para sobrevivir a que este
    fragmento se reemplace por AJAX en cada clic de fila.

    Variables (ver EmailLogController::resolveDetailData()/emptyDetailData()):
    - log: EmailLog|null — null solo cuando el listado no tiene ninguna fila
      que autoseleccionar (ver renderWorkspace()).
    - opensSummary, clicksSummary: array|null
    - related: Collection<EmailLog>
    - entityPanel: string|null (HTML del módulo dueño de la entidad)
    - canManage: bool
    - staleHours: int
    - isStaleQueued: bool
    - statusIcon: string|null (clase fa-solid, p. ej. "fa-check")
--}}

@php
    $hasInteractions = $log && ($opensSummary !== null || $clicksSummary !== null);
    $interactionsCount = $hasInteractions ? (($opensSummary['count'] ?? 0) + ($clicksSummary['count'] ?? 0)) : 0;

    // Contador de la pestaña "Traza": los 3 pasos que siempre se pintan
    // (encolado, aceptado por SMTP, confirmación de entrega) más los de
    // apertura/clic, que solo existen si ese envío llevaba seguimiento —
    // debe coincidir con lo que realmente se renderiza en el panel "trace".
    $traceStepsCount = 3
        + ($opensSummary !== null ? 1 : 0)
        + ($clicksSummary !== null ? 1 : 0);
@endphp

@if(! $log)

    <div class="evx-detail-panel evx-empty-detail">
        <div class="evx-empty-row">
            <i class="fa-regular fa-envelope-open" aria-hidden="true"></i>
            <p>{{ __('helpdeskemailactivity::emaillog.preview.empty_selection.title') }}</p>
            <p class="small mb-0">{{ __('helpdeskemailactivity::emaillog.preview.empty_selection.hint') }}</p>
        </div>
    </div>

@else

    <div class="evx-detail-panel">

        {{-- Cabecera con pestañas --}}
        <div class="evx-preview-header">

            {{-- Eyebrow + posición dentro del listado filtrado + navegación al
                 anterior/siguiente (también con J/K, ver @push('scripts') de
                 index.blade.php). prevUid/nextUid respetan el filtro y el orden
                 activos, no solo la página actual — los calcula
                 EmailLogController::resolveDetailData() por keyset. --}}
            <div class="evx-ph-top">
                <span class="evx-label">{{ __('helpdeskemailactivity::emaillog.preview.title') }}</span>
                @if($selectedPosition)
                    <span class="evx-ph-position">{{ __('helpdeskemailactivity::emaillog.preview.position', ['position' => number_format($selectedPosition), 'total' => number_format($selectedTotal)]) }}</span>
                @endif
                <span class="evx-ph-nav">
                    <button type="button" class="evx-nav-btn js-detail-prev"
                            data-href="{{ $prevUid ? route('helpdeskemailactivity.show', $prevUid) : '' }}"
                            @disabled(! $prevUid)
                            title="{{ __('helpdeskemailactivity::emaillog.preview.prev') }}"
                            aria-label="{{ __('helpdeskemailactivity::emaillog.preview.prev') }}">
                        <i class="fa-solid fa-chevron-up" aria-hidden="true"></i>
                    </button>
                    <button type="button" class="evx-nav-btn js-detail-next"
                            data-href="{{ $nextUid ? route('helpdeskemailactivity.show', $nextUid) : '' }}"
                            @disabled(! $nextUid)
                            title="{{ __('helpdeskemailactivity::emaillog.preview.next') }}"
                            aria-label="{{ __('helpdeskemailactivity::emaillog.preview.next') }}">
                        <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
                    </button>
                </span>
            </div>

            {{-- Como el mockup: el asunto manda solo en su línea y los chips
                 (estado, uid, módulo, adjuntos) van debajo. Antes iban todos en
                 la misma fila y el asunto competía con ellos. --}}
            <div class="evx-ph-subject-row">
                <div class="evx-ph-subject-wrap">
                    <span class="evx-subject-lg">{{ $log->subject ?: '—' }}</span>
                    <span class="evx-ph-chips">
                        <span class="evx-status {{ $log->status?->value }}">
                            <i class="fa-solid {{ $statusIcon }}" aria-hidden="true"></i>{{ $log->status_label }}
                        </span>
                        <span class="evx-chip">{{ Str::substr($log->uid, 0, 8) }}</span>
                        @if($log->module)
                            <span class="evx-tag mono">{{ $log->module }}</span>
                        @endif
                        @if($log->attachments)
                            <span class="evx-tag" title="{{ __('helpdeskemailactivity::emaillog.preview.field.attachments') }}">
                                <i class="fa-solid fa-paperclip" aria-hidden="true"></i>{{ count($log->attachments) }}
                            </span>
                        @endif
                    </span>
                </div>

                <div class="evx-ph-actions">
                    {{-- Triaje de rebotes (mockup, el hueco de más valor): acceso
                         claro y de un solo paso — corregir destinatario + reenviar
                         + supresión opcional, ver EmailLogController::resolveBounce().
                         Solo para rebotes (nunca para "failed"/otros estados): un
                         fallo genérico de transporte no tiene una "dirección mala"
                         que corregir. Primero en la fila: es la acción más urgente
                         cuando la hay. --}}
                    @if($canManage && $log->status?->value === 'bounced')
                        <button type="button" class="evx-btn evx-btn-danger evx-btn-inline" id="btnBounceTriage"
                                data-url="{{ route('helpdeskemailactivity.resolve-bounce', $log->uid) }}"
                                data-old-address="{{ $log->to_addresses[0] ?? '' }}"
                                data-error="{{ $log->error_message }}"
                                data-hard="{{ $log->bounceType() === 'hard' ? '1' : '0' }}">
                            <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
                            {{ __('helpdeskemailactivity::emaillog.bounce_triage.cta') }}
                        </button>
                    @endif
                    @if($canManage)
                        <button type="button" class="evx-btn evx-btn-primary evx-btn-inline js-resend"
                                data-url="{{ route('helpdeskemailactivity.resend', $log->uid) }}">
                            <i class="fa-solid fa-rotate-right" aria-hidden="true"></i>
                            {{ __('helpdeskemailactivity::emaillog.actions.resend') }}
                        </button>
                    @endif
                    @if($log->raw_headers || $log->body_html || $log->body_text)
                        <a href="{{ route('helpdeskemailactivity.raw', $log->uid) }}" class="evx-icon-btn"
                           aria-label="{{ __('helpdeskemailactivity::emaillog.actions.download_eml') }}"
                           title="{{ __('helpdeskemailactivity::emaillog.actions.download_eml') }}">
                            <i class="fa-solid fa-download" aria-hidden="true"></i>
                        </a>
                    @endif
                    @if($log->message_id)
                        <button type="button" class="evx-icon-btn evx-copy-btn" data-copy="{{ $log->message_id }}"
                                aria-label="{{ __('helpdeskemailactivity::emaillog.actions.copy_id') }}"
                                title="{{ __('helpdeskemailactivity::emaillog.actions.copy_id') }}">
                            <i class="fa-regular fa-copy" aria-hidden="true"></i>
                        </button>
                    @endif
                    @if($canManage)
                        <button type="button" class="evx-icon-btn js-delete"
                                data-url="{{ route('helpdeskemailactivity.destroy', $log->uid) }}"
                                aria-label="{{ __('helpdeskemailactivity::emaillog.actions.move_to_trash') }}"
                                title="{{ __('helpdeskemailactivity::emaillog.actions.move_to_trash') }}">
                            <i class="fa-regular fa-trash-can" aria-hidden="true"></i>
                        </button>
                    @endif
                </div>
            </div>

            {{-- Línea de contexto del mockup: destinatario · cuándo se registró ·
                 quién lo envió. Cuando no hay causer se dice "por el sistema" en
                 vez de callar: en blanco no se distingue un envío automático de
                 un dato que falta. --}}
            <div class="evx-ph-meta">
                <i class="fa-regular fa-user evx-ph-meta-icon" aria-hidden="true"></i>
                {{-- Nombre del destinatario cuando esa dirección es de un cliente
                     registrado (ver EmailLogController::resolveRecipientName());
                     si no lo es, la línea arranca directamente por el correo. --}}
                @if($recipientStats['name'] ?? null)
                    <span>{{ $recipientStats['name'] }}</span>
                    <span class="evx-muted">·</span>
                @endif
                <span class="evx-mono">
                    @forelse($log->to_addresses ?? [] as $addr)
                        {{ $addr }}@if(!$loop->last),@endif
                    @empty
                        —
                    @endforelse
                </span>
                <span class="evx-muted">·</span>
                {{-- Fecha en el formato corto del mockup ("01 sep 2026, 11:20").
                     El "hace X" ya no se pinta: el mockup no lo lleva y en una
                     línea de cuatro datos era el más prescindible; queda en el
                     title, junto a la fecha con segundos. --}}
                <span title="{{ $log->created_at?->format('d/m/Y H:i:s') }} · {{ $log->display_date->diffForHumans() }}">
                    {{ __('helpdeskemailactivity::emaillog.preview.field.created_at') }}
                    {{ str_replace('.', '', $log->created_at?->translatedFormat('d M Y, H:i') ?? '') }}
                </span>
                <span class="evx-muted">·</span>
                <span>{{ __('helpdeskemailactivity::emaillog.preview.by', [
                    'name' => $log->causer?->name
                        ?? $log->causer?->email
                        ?? ($log->causer_id ? '#'.$log->causer_id : __('helpdeskemailactivity::emaillog.preview.by_system')),
                ]) }}</span>
            </div>

            {{-- Etiquetas cortas + icono por pestaña, igual que el mockup
                 (Detalle / Traza / Aperturas / Original); los nombres largos
                 siguen usándose como títulos dentro de cada panel. --}}
            <div class="evx-tabs" role="tablist">
                <button type="button" class="evx-tab on" data-evx-tab="detail">
                    <i class="fa-regular fa-envelope" aria-hidden="true"></i>
                    {{ __('helpdeskemailactivity::emaillog.preview.tabs.detail') }}
                </button>
                <button type="button" class="evx-tab" data-evx-tab="trace">
                    <i class="fa-solid fa-route" aria-hidden="true"></i>
                    {{ __('helpdeskemailactivity::emaillog.preview.tabs.trace') }}
                    <span class="evx-tab-count">{{ $traceStepsCount }}</span>
                </button>
                @if($hasInteractions)
                    <button type="button" class="evx-tab" data-evx-tab="opens">
                        <i class="fa-regular fa-envelope-open" aria-hidden="true"></i>
                        {{ __('helpdeskemailactivity::emaillog.preview.tabs.opens') }}
                        <span class="evx-tab-count">{{ $interactionsCount }}</span>
                    </button>
                @endif
                <button type="button" class="evx-tab" data-evx-tab="raw">
                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                    {{ __('helpdeskemailactivity::emaillog.preview.tabs.raw') }}
                </button>
                {{-- Bitácora de ESTE email (EmailLogController::logActivity()) —
                     siempre visible, incluso vacía: un email sin ninguna acción
                     registrada todavía es un dato real (nunca reenviado/descargado/
                     borrado), no algo que ocultar. --}}
                <button type="button" class="evx-tab" data-evx-tab="activity">
                    <i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i>
                    {{ __('helpdeskemailactivity::emaillog.preview.tabs.activity') }}
                    @if($activityLog->isNotEmpty())
                        <span class="evx-tab-count">{{ $activityLog->count() }}</span>
                    @endif
                </button>
            </div>
        </div>

        {{-- Columna principal: las pestañas. El sidebar ya NO cuelga de
             aquí — es la tercera columna del grid, hermana de esta (ver
             #evx-detail-cols con display:contents en emaillog.css): en el
             mockup la cabecera del detalle termina donde empieza el
             sidebar, no pasa por encima de él. --}}
        <div class="evx-main">

            {{-- Pestaña: Detalle --}}
            <div class="evx-tabpanel" data-evx-panel="detail">

                <div class="evx-kv-grid">

                    <div class="evx-field">
                        <span class="k">{{ __('helpdeskemailactivity::emaillog.preview.field.from') }}</span>
                        <span class="v">
                            @if($log->from_name){{ $log->from_name }} @endif
                            <span class="mono">{{ $log->from_address }}</span>
                        </span>
                    </div>

                    <div class="evx-field">
                        <span class="k">{{ __('helpdeskemailactivity::emaillog.preview.field.to') }}</span>
                        <span class="v mono">
                            @forelse($log->to_addresses ?? [] as $addr)
                                {{ $addr }}@if(!$loop->last)<br>@endif
                            @empty
                                —
                            @endforelse
                        </span>
                    </div>

                    @if($log->cc_addresses)
                        <div class="evx-field">
                            <span class="k">{{ __('helpdeskemailactivity::emaillog.preview.field.cc') }}</span>
                            <span class="v mono">
                                @foreach($log->cc_addresses as $addr)
                                    {{ $addr }}@if(!$loop->last)<br>@endif
                                @endforeach
                            </span>
                        </div>
                    @endif

                    @if($log->bcc_addresses)
                        <div class="evx-field">
                            <span class="k">{{ __('helpdeskemailactivity::emaillog.preview.field.bcc') }}</span>
                            <span class="v mono">
                                @foreach($log->bcc_addresses as $addr)
                                    {{ $addr }}@if(!$loop->last)<br>@endif
                                @endforeach
                            </span>
                        </div>
                    @endif

                    @if($log->reply_to)
                        <div class="evx-field">
                            <span class="k">{{ __('helpdeskemailactivity::emaillog.preview.field.reply_to') }}</span>
                            <span class="v mono">
                                @foreach($log->reply_to as $addr)
                                    {{ $addr }}@if(!$loop->last)<br>@endif
                                @endforeach
                            </span>
                        </div>
                    @endif

                    @if($log->message_id)
                        <div class="evx-field">
                            <span class="k">{{ __('helpdeskemailactivity::emaillog.preview.field.message_id') }}</span>
                            <span class="v mono evx-copy-row">
                                <span class="evx-copy-text">{{ $log->message_id }}</span>
                                <button type="button" class="evx-copy-btn" data-copy="{{ $log->message_id }}"
                                        aria-label="{{ __('helpdeskemailactivity::emaillog.actions.copy_id') }}"
                                        title="{{ __('helpdeskemailactivity::emaillog.actions.copy_id') }}">
                                    <i class="fa-regular fa-copy" aria-hidden="true"></i>
                                </button>
                            </span>
                        </div>
                    @endif

                    @if($log->module)
                        <div class="evx-field">
                            <span class="k">{{ __('helpdeskemailactivity::emaillog.preview.field.module') }}</span>
                            <span class="v">
                                <span class="evx-tag">{{ $log->module }}</span>
                                @if($log->mailable_class)
                                    <span class="evx-tag mono">{{ class_basename($log->mailable_class) }}</span>
                                @endif
                            </span>
                        </div>
                    @endif

                    @if($log->causer)
                        <div class="evx-field">
                            <span class="k">{{ __('helpdeskemailactivity::emaillog.preview.field.causer') }}</span>
                            <span class="v">{{ $log->causer->name ?? ($log->causer->email ?? ('#'.$log->causer_id)) }}</span>
                        </div>
                    @endif

                    @if($log->attachments)
                        <div class="evx-field">
                            <span class="k">{{ __('helpdeskemailactivity::emaillog.preview.field.attachments') }}</span>
                            <span class="v">
                                <ul class="evx-attachments">
                                    @foreach($log->attachments as $att)
                                        <li>
                                            <i class="fa-solid fa-paperclip"></i>{{ $att['name'] ?? 'archivo' }}
                                            @if(!empty($att['size']))
                                                <span class="muted">({{ number_format(($att['size'] ?? 0) / 1024, 1) }} KB)</span>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            </span>
                        </div>
                    @endif

                    <div class="evx-field">
                        <span class="k">{{ __('helpdeskemailactivity::emaillog.preview.field.created_at') }}</span>
                        <span class="v mono">{{ $log->created_at?->format('d/m/Y H:i:s') }}</span>
                    </div>

                    <div class="evx-field">
                        <span class="k">{{ __('helpdeskemailactivity::emaillog.preview.field.sent_at') }}</span>
                        <span class="v mono">
                            @if($log->sent_at)
                                {{ $log->sent_at->format('d/m/Y H:i:s') }}
                            @else
                                <span class="muted">{{ __('helpdeskemailactivity::emaillog.preview.trace.pending') }}</span>
                            @endif
                        </span>
                    </div>

                    @if($log->error_message)
                        <div class="evx-field">
                            <span class="k">{{ __('helpdeskemailactivity::emaillog.preview.field.error') }}</span>
                            <div class="evx-alert">{{ $log->error_message }}</div>
                        </div>
                    @endif

                </div>

                <div class="evx-block evx-content-card">
                    <div class="evx-content-head">
                        <span class="evx-content-head-label">{{ __('helpdeskemailactivity::emaillog.preview.heading') }}</span>
                        @if($log->attachments)
                            <span class="evx-attach-chip" title="{{ __('helpdeskemailactivity::emaillog.preview.field.attachments') }}">
                                <i class="fa-solid fa-paperclip" aria-hidden="true"></i>
                                <span>{{ count($log->attachments) }}</span>
                            </span>
                        @endif
                        {{-- Etiquetas de texto, no iconos: en el mockup el
                             conmutador dice "Escritorio"/"Móvil" y un icono de
                             ventana no distingue bien un ancho de otro. --}}
                        <div class="evx-device-toggle" role="group" aria-label="{{ __('helpdeskemailactivity::emaillog.preview.heading') }}">
                            <button type="button" class="on" id="btnDesktopView" aria-pressed="true">
                                {{ __('helpdeskemailactivity::emaillog.preview.desktop') }}
                            </button>
                            <button type="button" id="btnMobileView" aria-pressed="false">
                                {{ __('helpdeskemailactivity::emaillog.preview.mobile') }}
                            </button>
                        </div>
                    </div>
                    <div class="evx-preview-body">
                        @if($log->body_html)
                            {{-- Renderizado dentro de un iframe con sandbox: los scripts no se ejecutan y
                                 el contenido vive en un origen opaco, mostrando el HTML original con fidelidad
                                 pero sin riesgo de XSS para el panel de administración. --}}
                            <iframe id="previewFrame" class="evx-frame"
                                    title="{{ __('helpdeskemailactivity::emaillog.preview.heading') }}"
                                    sandbox="allow-popups allow-popups-to-escape-sandbox"
                                    referrerpolicy="no-referrer"
                                    srcdoc="{{ $log->body_html }}"></iframe>
                        @elseif($log->body_text)
                            <pre class="evx-preview-text">{{ $log->body_text }}</pre>
                        @else
                            <div class="evx-empty">
                                <i class="fa-regular fa-envelope" aria-hidden="true"></i>
                                {{ ($log->metadata['redacted'] ?? false)
                                    ? __('helpdeskemailactivity::emaillog.preview.purged_note')
                                    : __('helpdeskemailactivity::emaillog.preview.no_content') }}
                            </div>
                        @endif
                    </div>
                </div>

            </div>

            {{-- Pestaña: Traza de envío --}}
            <div class="evx-tabpanel" data-evx-panel="trace" hidden>

                @php
                    // Frase corta de contexto de la tarjeta resumen — se deriva de
                    // datos que $log ya trae cargados (status/error/tipo de rebote),
                    // sin ninguna consulta nueva.
                    $traceContext = match (true) {
                        $log->status?->value === 'sent' => __('helpdeskemailactivity::emaillog.preview.trace.smtp'),
                        $log->status?->value === 'bounced' => match ($log->bounceType()) {
                            'hard' => __('helpdeskemailactivity::emaillog.preview.trace.bounce_hard'),
                            'soft' => __('helpdeskemailactivity::emaillog.preview.trace.bounce_soft'),
                            default => __('helpdeskemailactivity::emaillog.preview.trace.bounce_unknown'),
                        },
                        $log->status?->value === 'complained' => __('helpdeskemailactivity::emaillog.preview.trace.complained_context'),
                        $log->status?->value === 'suppressed' => __('helpdeskemailactivity::emaillog.preview.trace.suppressed_context'),
                        $log->status?->value === 'failed' => Str::limit($log->error_message ?: __('helpdeskemailactivity::emaillog.preview.trace.failed'), 90),
                        $isStaleQueued => __('helpdeskemailactivity::emaillog.preview.trace.stale', ['hours' => $staleHours]),
                        default => __('helpdeskemailactivity::emaillog.preview.trace.pending'),
                    };
                @endphp

                {{-- a) Tarjeta resumen: estado + contexto + tiempo total real
                     (sent_at - created_at; "—" si el envío aún no se confirmó,
                     nunca una duración inventada). --}}
                <div class="evx-trace-summary">
                    <div class="evx-trace-summary-main">
                        <span class="evx-trace-summary-icon {{ $log->status?->value }}">
                            <i class="fa-solid {{ $statusIcon }}" aria-hidden="true"></i>
                        </span>
                        <span class="evx-trace-summary-text">
                            <span class="evx-trace-summary-title">{{ $log->status_label }}</span>
                            <span class="evx-trace-summary-context">{{ $traceContext }}</span>
                        </span>
                    </div>
                    <span class="evx-trace-summary-time">
                        <span class="val">{{ $traceElapsedLabel ?? '—' }}</span>
                        <span class="lbl">{{ __('helpdeskemailactivity::emaillog.preview.trace.total') }}</span>
                    </span>
                </div>

                {{-- b) "Recorrido del envío": solo un encabezado, la timeline de
                     abajo ya existía. --}}
                <div class="evx-trace-timeline-group">
                <span class="evx-trace-section-label">{{ __('helpdeskemailactivity::emaillog.preview.trace.journey') }}</span>
                <div class="evx-block">
                    {{-- Sin cabecera: el eyebrow "Recorrido del envío" de arriba ya
                         dice lo que es, y el mockup entra directo al primer paso. --}}

                    {{-- Paso 1: Encolado --}}
                    <div class="evx-trace-step">
                        <span class="evx-trace-icon ok"><i class="fa-solid fa-inbox" aria-hidden="true"></i></span>
                        <div class="evx-trace-main">
                            <div class="evx-trace-title">{{ __('helpdeskemailactivity::emaillog.preview.trace.queued') }}</div>
                            {{-- Como el mockup: el paso cuenta la fecha completa y el
                                 dato que lo identifica (aquí, en qué cola entró), y la
                                 columna de la derecha se queda solo con la hora. --}}
                            <div class="evx-trace-meta">
                                {{ $log->created_at?->format('d/m/Y H:i:s') }}
                                · {{ __('helpdeskemailactivity::emaillog.preview.trace.queued_meta', ['queue' => $transport['queue']]) }}
                            </div>
                        </div>
                        <div class="evx-trace-time">{{ $log->created_at?->format('H:i:s') }}</div>
                    </div>

                    {{-- Paso 2: Aceptado por SMTP --}}
                    <div class="evx-trace-step">
                        @if($log->sent_at)
                            <span class="evx-trace-icon ok"><i class="fa-solid fa-check" aria-hidden="true"></i></span>
                        @elseif($log->status?->value === 'failed')
                            <span class="evx-trace-icon err"><i class="fa-solid fa-xmark" aria-hidden="true"></i></span>
                        @else
                            <span class="evx-trace-icon warn"><i class="fa-solid fa-clock" aria-hidden="true"></i></span>
                        @endif
                        <div class="evx-trace-main">
                            <div class="evx-trace-title">{{ __('helpdeskemailactivity::emaillog.preview.trace.smtp') }}</div>
                            <div class="evx-trace-meta">
                                @if($log->sent_at)
                                    {{ $log->sent_at->format('d/m/Y H:i:s') }}
                                    · {{ $transport['isSmtp'] ? $transport['host'].':'.$transport['port'] : $transport['mailer'] }}
                                @elseif($log->status?->value === 'failed')
                                    @if($log->failed_at){{ $log->failed_at->format('d/m/Y H:i:s') }} · @endif
                                    {{ __('helpdeskemailactivity::emaillog.preview.trace.failed') }}
                                @else
                                    {{ __('helpdeskemailactivity::emaillog.preview.trace.pending') }}
                                    @if($isStaleQueued)
                                        · {{ __('helpdeskemailactivity::emaillog.preview.trace.stale', ['hours' => $staleHours]) }}
                                    @endif
                                @endif
                            </div>
                        </div>
                        <div class="evx-trace-time">
                            {{ ($log->sent_at ?? $log->failed_at)?->format('H:i:s') }}
                        </div>
                    </div>

                    {{-- Paso 3: Confirmación de entrega --}}
                    <div class="evx-trace-step">
                        @if($log->bounced_at)
                            <span class="evx-trace-icon err"><i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i></span>
                        @elseif($log->complained_at)
                            <span class="evx-trace-icon spam"><i class="fa-solid fa-flag" aria-hidden="true"></i></span>
                        @else
                            <span class="evx-trace-icon"><i class="fa-regular fa-circle-question" aria-hidden="true"></i></span>
                        @endif
                        <div class="evx-trace-main">
                            <div class="evx-trace-title">{{ __('helpdeskemailactivity::emaillog.preview.trace.delivery') }}</div>
                            <div class="evx-trace-meta">
                                @if($log->bounced_at)
                                    {{ $log->bounced_at->format('d/m/Y H:i:s') }} · {{ __('helpdeskemailactivity::emaillog.status.bounced') }}
                                @elseif($log->complained_at)
                                    {{ $log->complained_at->format('d/m/Y H:i:s') }} · {{ __('helpdeskemailactivity::emaillog.status.complained') }}
                                @else
                                    {{ __('helpdeskemailactivity::emaillog.preview.trace.no_delivery_data') }}
                                @endif
                            </div>
                        </div>
                        <div class="evx-trace-time">
                            {{ ($log->bounced_at ?? $log->complained_at)?->format('H:i:s') }}
                        </div>
                    </div>

                    {{-- Paso 4: Apertura (solo si este envío tuvo píxel) --}}
                    @if($opensSummary !== null)
                        <div class="evx-trace-step">
                            <span class="evx-trace-icon {{ $opensSummary['count'] > 0 ? 'open' : '' }}">
                                <i class="fa-solid fa-eye" aria-hidden="true"></i>
                            </span>
                            <div class="evx-trace-main">
                                <div class="evx-trace-title">{{ __('helpdeskemailactivity::emaillog.preview.trace.opened') }}</div>
                                <div class="evx-trace-meta">
                                    @if($opensSummary['count'] > 0)
                                        {{ trans_choice('helpdeskemailactivity::emaillog.preview.trace.opened_count', $opensSummary['count'], ['count' => $opensSummary['count']]) }}
                                        {{-- El rango solo aporta cuando hay más de una:
                                             con una sola apertura, "primera X — última X"
                                             repite dos veces la misma hora. --}}
                                        @if($opensSummary['first'] && $opensSummary['last'] && ! $opensSummary['last']->equalTo($opensSummary['first']))
                                            · {{ __('helpdeskemailactivity::emaillog.preview.trace.range', [
                                                'first' => $opensSummary['first']->format('d/m H:i'),
                                                'last' => $opensSummary['last']->format('d/m H:i'),
                                            ]) }}
                                        @endif
                                    @else
                                        {{ __('helpdeskemailactivity::emaillog.preview.trace.not_opened_yet') }}
                                    @endif
                                </div>
                            </div>
                            <div class="evx-trace-time">
                                {{ $opensSummary['count'] > 0 ? $opensSummary['first']->format('H:i') : '' }}
                            </div>
                        </div>
                    @endif

                    {{-- Paso 5: Clic (solo si este envío tuvo sus enlaces reescritos) --}}
                    @if($clicksSummary !== null)
                        <div class="evx-trace-step">
                            <span class="evx-trace-icon {{ $clicksSummary['count'] > 0 ? 'open' : '' }}">
                                <i class="fa-solid fa-arrow-pointer" aria-hidden="true"></i>
                            </span>
                            <div class="evx-trace-main">
                                <div class="evx-trace-title">{{ __('helpdeskemailactivity::emaillog.preview.trace.clicked') }}</div>
                                <div class="evx-trace-meta">
                                    @if($clicksSummary['count'] > 0)
                                        {{ trans_choice('helpdeskemailactivity::emaillog.preview.trace.clicked_count', $clicksSummary['count'], ['count' => $clicksSummary['count']]) }}
                                        @if($clicksSummary['first'] && $clicksSummary['last'] && ! $clicksSummary['last']->equalTo($clicksSummary['first']))
                                            · {{ __('helpdeskemailactivity::emaillog.preview.trace.range', [
                                                'first' => $clicksSummary['first']->format('d/m H:i'),
                                                'last' => $clicksSummary['last']->format('d/m H:i'),
                                            ]) }}
                                        @endif
                                    @else
                                        {{ __('helpdeskemailactivity::emaillog.preview.trace.not_clicked_yet') }}
                                    @endif
                                </div>
                            </div>
                            <div class="evx-trace-time">
                                {{ $clicksSummary['count'] > 0 ? $clicksSummary['first']->format('H:i') : '' }}
                            </div>
                        </div>
                    @endif

                    @if($log->error_message)
                        <div class="evx-alert evx-trace-error">{{ $log->error_message }}</div>
                    @endif
                </div>
                </div>

                {{-- c) Transporte + Autenticación, en fila. --}}
                <div class="evx-trace-cards">

                    <div class="evx-trace-card">
                        <span class="evx-trace-card-title">{{ __('helpdeskemailactivity::emaillog.preview.trace.transport') }}</span>
                        <div class="evx-trace-kv">
                            <span class="k">{{ __('helpdeskemailactivity::emaillog.preview.trace.transport_server') }}</span>
                            <span class="v">
                                @if($transport['isSmtp'])
                                    {{ $transport['host'] }}:{{ $transport['port'] }}
                                @else
                                    {{ $transport['mailer'] }}
                                @endif
                            </span>
                            <span class="k">{{ __('helpdeskemailactivity::emaillog.preview.trace.transport_queue') }}</span>
                            <span class="v">{{ $transport['queue'] }}</span>
                        </div>
                    </div>

                    {{-- SPF/DKIM/DMARC del DOMINIO REMITENTE (no de este envío
                         concreto) — solo se leen de la caché que ya puebla la
                         pantalla de Reputación (ver
                         EmailLogController::domainAuthStatus()); nunca se
                         dispara aquí una resolución DNS en caliente. Sin caché,
                         las 3 pills quedan en gris "sin datos" con enlace a
                         Reputación. --}}
                    <div class="evx-trace-card">
                        <span class="evx-trace-card-title">{{ __('helpdeskemailactivity::emaillog.preview.trace.auth') }}</span>
                        <div class="evx-trace-auth-pills">
                            @foreach(['spf' => 'SPF', 'dkim' => 'DKIM', 'dmarc' => 'DMARC'] as $authKey => $authLabel)
                                @php $authPass = ($domainAuth[$authKey]['status'] ?? null) === 'pass'; @endphp
                                <span class="evx-auth-pill {{ $authPass ? 'is-pass' : 'is-unknown' }}">
                                    @if($authPass)<i class="fa-solid fa-check" aria-hidden="true"></i>@endif
                                    {{ $authLabel }}
                                </span>
                            @endforeach
                        </div>
                        <p class="evx-trace-auth-note">
                            {{ __('helpdeskemailactivity::emaillog.preview.trace.auth_note', ['domain' => $domainAuth['domain'] ?: '—']) }}
                            @if(! $domainAuth['spf'] && ! $domainAuth['dkim'] && ! $domainAuth['dmarc'])
                                <a href="{{ route('helpdeskemailactivity.reputation.index') }}">{{ __('helpdeskemailactivity::emaillog.preview.trace.auth_check_link') }}</a>
                            @endif
                        </p>
                    </div>

                </div>

                {{-- d) "Evento del proveedor": OMITIDO A PROPÓSITO.
                     ProviderWebhookEvent (ver migración
                     create_email_provider_events_table) solo guarda
                     provider + provider_event_id para deduplicar reintentos
                     del webhook — nunca el payload crudo ni ningún FK hacia
                     email_logs. No existe ninguna forma de recuperar "el
                     evento de proveedor de ESTE email" ni su JSON una vez
                     procesado, así que no hay nada real que mostrar aquí.
                     Para poder pintar esta pieza haría falta: (1) añadir una
                     columna payload (JSON) a email_provider_events, y (2)
                     que EmailProviderWebhookController::receive() guarde
                     también el email_log_id que
                     EmailBounceCorrelatorService/
                     EmailDeliveryEventCorrelatorService ya resuelven al
                     correlacionar cada evento. --}}

            </div>

            {{-- Pestaña: Aperturas + Clics (solo si este envío tuvo seguimiento).
                 Cada sección lleva su encabezado FUERA de la tarjeta (título +
                 de dónde sale el dato + recuento), como el mockup: así las dos
                 listas y la advertencia final se leen como tres piezas de una
                 misma pantalla, no como tres tarjetas independientes. --}}
            @if($hasInteractions)
                <div class="evx-tabpanel evx-tabpanel-stack" data-evx-panel="opens" hidden>

                    @if($opensSummary !== null)
                        <div class="evx-section-head">
                            <span class="t">{{ __('helpdeskemailactivity::emaillog.preview.opens.title') }}</span>
                            <span class="s">{{ __('helpdeskemailactivity::emaillog.preview.opens.hint') }}</span>
                            <span class="n">{{ trans_choice('helpdeskemailactivity::emaillog.preview.opens.count', $opensSummary['count'], ['count' => number_format($opensSummary['count'])]) }}</span>
                        </div>

                        @if($opensSummary['count'] > 0)
                            <div class="evx-event-list">
                                @foreach($opensSummary['recent'] as $open)
                                    <div class="evx-event-row">
                                        <span class="evx-event-icon is-open">
                                            <i class="fa-regular fa-envelope-open" aria-hidden="true"></i>
                                        </span>
                                        <span class="evx-event-when">{{ $open->opened_at->format('d/m/Y H:i') }}</span>
                                        {{-- De dónde salió la apertura: el píxel propio o
                                             un webhook del proveedor (ver EmailOpenSource). --}}
                                        @if($open->source)
                                            <span class="evx-event-tag">{{ $open->source->label() }}</span>
                                        @endif
                                        @if($open->likely_bot)
                                            <span class="evx-event-tag" title="{{ __('helpdeskemailactivity::emaillog.preview.opens.likely_bot_hint') }}">
                                                {{ __('helpdeskemailactivity::emaillog.preview.likely_bot_badge') }}
                                            </span>
                                        @endif
                                        <span class="evx-event-meta">{{ $open->ip }} · {{ Str::limit($open->user_agent, 40) }}</span>
                                    </div>
                                @endforeach

                                {{-- El listado solo trae las últimas: si hay más, se dice
                                     en vez de dejar creer que eso es todo. --}}
                                @if($opensSummary['count'] > $opensSummary['recent']->count())
                                    <div class="evx-event-more">
                                        {{ __('helpdeskemailactivity::emaillog.preview.opens.showing_recent', [
                                            'shown' => $opensSummary['recent']->count(),
                                            'total' => number_format($opensSummary['count']),
                                        ]) }}
                                    </div>
                                @endif
                            </div>
                        @else
                            <div class="evx-empty-card">{{ __('helpdeskemailactivity::emaillog.preview.trace.not_opened_yet') }}</div>
                        @endif
                    @endif

                    @if($clicksSummary !== null)
                        <div class="evx-section-head">
                            <span class="t">{{ __('helpdeskemailactivity::emaillog.preview.clicks.title') }}</span>
                            <span class="s">{{ __('helpdeskemailactivity::emaillog.preview.clicks.hint') }}</span>
                            <span class="n">{{ trans_choice('helpdeskemailactivity::emaillog.preview.clicks.count', $clicksSummary['count'], ['count' => number_format($clicksSummary['count'])]) }}</span>
                        </div>

                        @if($clicksSummary['count'] > 0)
                            <div class="evx-event-list">
                                @foreach($clicksSummary['recent'] as $click)
                                    <div class="evx-event-row is-stacked">
                                        <span class="evx-event-icon">
                                            <i class="fa-solid fa-arrow-pointer" aria-hidden="true"></i>
                                        </span>
                                        <span class="evx-event-main">
                                            <a href="{{ $click->link_url }}" target="_blank" rel="noopener" class="evx-event-url mono">{{ $click->link_url }}</a>
                                            <span class="evx-event-sub">
                                                {{ $click->clicked_at->format('d/m/Y H:i') }} · {{ $click->ip }} · {{ Str::limit($click->user_agent, 40) }}
                                            </span>
                                        </span>
                                        @if($click->likely_bot)
                                            <span class="evx-event-tag" title="{{ __('helpdeskemailactivity::emaillog.preview.clicks.likely_bot_hint') }}">
                                                {{ __('helpdeskemailactivity::emaillog.preview.likely_bot_badge') }}
                                            </span>
                                        @endif
                                    </div>
                                @endforeach

                                @if($clicksSummary['count'] > $clicksSummary['recent']->count())
                                    <div class="evx-event-more">
                                        {{ __('helpdeskemailactivity::emaillog.preview.clicks.showing_recent', [
                                            'shown' => $clicksSummary['recent']->count(),
                                            'total' => number_format($clicksSummary['count']),
                                            'links' => number_format($clicksSummary['unique_links']),
                                        ]) }}
                                    </div>
                                @endif
                            </div>
                        @else
                            <div class="evx-empty-card">{{ __('helpdeskemailactivity::emaillog.preview.trace.not_clicked_yet') }}</div>
                        @endif
                    @endif

                    {{-- Una sola advertencia al pie para las dos listas: antes iba
                         repetida dentro de cada tarjeta. --}}
                    <div class="evx-note-card">
                        <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                        <span>{{ __('helpdeskemailactivity::emaillog.preview.opens.honesty_note') }}</span>
                    </div>

                </div>
            @endif

            {{-- Pestaña: Inspector del mensaje.
                 Siete vistas del mismo correo, el mismo juego que ofrece un
                 servidor de pruebas tipo Mailpit, pero sobre el envío REAL que
                 recibió el cliente: cómo se ve, su código, su versión en texto,
                 sus cabeceras, el mensaje entero, con qué clientes de correo es
                 compatible y a dónde llevan hoy sus enlaces.

                 Las cinco primeras se pintan aquí con lo que ya trae $log; las
                 dos últimas se piden por AJAX (ver EmailInspectionController):
                 una cuesta un análisis de ~190 comprobaciones y la otra abre
                 peticiones salientes reales. --}}
            <div class="evx-tabpanel evx-tabpanel-stack" data-evx-panel="raw" hidden>
                <div class="evx-section-head">
                    <span class="t">{{ __('helpdeskemailactivity::emaillog.preview.inspector.title') }}</span>
                    <span class="s">{{ __('helpdeskemailactivity::emaillog.preview.inspector.hint') }}</span>
                    @if($emlContent)
                        {{-- Menú y no un botón suelto: además del .eml completo
                             se puede bajar cada parte por separado, que es lo
                             que se pega en una plantilla al corregirla. --}}
                        <div class="dropdown evx-section-head-btn">
                            <button type="button" class="evx-btn evx-btn-outline evx-btn-inline" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fa-solid fa-download" aria-hidden="true"></i>
                                {{ __('helpdeskemailactivity::emaillog.preview.inspector.download.label') }}
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item" href="{{ route('helpdeskemailactivity.raw', $log->uid) }}">
                                        {{ __('helpdeskemailactivity::emaillog.preview.inspector.download.eml') }}
                                    </a>
                                </li>
                                @if($log->body_html)
                                    <li>
                                        <a class="dropdown-item" href="{{ route('helpdeskemailactivity.download', $log->uid) }}">
                                            {{ __('helpdeskemailactivity::emaillog.preview.inspector.download.html') }}
                                        </a>
                                    </li>
                                @endif
                                @if($log->body_text)
                                    <li>
                                        <a class="dropdown-item" href="{{ route('helpdeskemailactivity.download-text', $log->uid) }}">
                                            {{ __('helpdeskemailactivity::emaillog.preview.inspector.download.text') }}
                                        </a>
                                    </li>
                                @endif
                            </ul>
                        </div>
                    @endif
                </div>

                <div class="evx-inspector"
                     data-html-check-url="{{ route('helpdeskemailactivity.html-check', $log->uid) }}"
                     data-link-check-url="{{ route('helpdeskemailactivity.link-check', $log->uid) }}">

                    <div class="evx-subtabs" role="tablist">
                        <button type="button" class="evx-subtab on" data-evx-subtab="html"
                                role="tab" aria-selected="true" aria-controls="evx-subpanel-html" id="evx-subtab-html">
                            {{ __('helpdeskemailactivity::emaillog.preview.inspector.tabs.html') }}
                        </button>
                        <button type="button" class="evx-subtab" data-evx-subtab="source"
                                role="tab" aria-selected="false" aria-controls="evx-subpanel-source" id="evx-subtab-source">
                            {{ __('helpdeskemailactivity::emaillog.preview.inspector.tabs.source') }}
                        </button>
                        <button type="button" class="evx-subtab" data-evx-subtab="text"
                                role="tab" aria-selected="false" aria-controls="evx-subpanel-text" id="evx-subtab-text">
                            {{ __('helpdeskemailactivity::emaillog.preview.inspector.tabs.text') }}
                        </button>
                        <button type="button" class="evx-subtab" data-evx-subtab="headers"
                                role="tab" aria-selected="false" aria-controls="evx-subpanel-headers" id="evx-subtab-headers">
                            {{ __('helpdeskemailactivity::emaillog.preview.inspector.tabs.headers') }}
                            @if($messageHeaders)
                                <span class="evx-subtab-count">{{ count($messageHeaders) }}</span>
                            @endif
                        </button>
                        <button type="button" class="evx-subtab" data-evx-subtab="raw"
                                role="tab" aria-selected="false" aria-controls="evx-subpanel-raw" id="evx-subtab-raw">
                            {{ __('helpdeskemailactivity::emaillog.preview.inspector.tabs.raw') }}
                        </button>
                        <button type="button" class="evx-subtab" data-evx-subtab="html-check"
                                role="tab" aria-selected="false" aria-controls="evx-subpanel-html-check" id="evx-subtab-html-check">
                            {{ __('helpdeskemailactivity::emaillog.preview.inspector.tabs.html_check') }}
                            <span class="evx-subtab-score" data-evx-score hidden></span>
                        </button>
                        <button type="button" class="evx-subtab" data-evx-subtab="link-check"
                                role="tab" aria-selected="false" aria-controls="evx-subpanel-link-check" id="evx-subtab-link-check">
                            {{ __('helpdeskemailactivity::emaillog.preview.inspector.tabs.link_check') }}
                            <span class="evx-subtab-count is-alert" data-evx-link-errors hidden></span>
                        </button>
                    </div>

                    {{-- HTML: el correo renderizado. Mismo aislamiento que la
                         vista previa de la pestaña Detalle (iframe con sandbox,
                         sin scripts y en origen opaco) — el HTML de un correo
                         es contenido no confiable dentro del panel. --}}
                    <div class="evx-subpanel" data-evx-subpanel="html"
                         role="tabpanel" id="evx-subpanel-html" aria-labelledby="evx-subtab-html">
                        @if($log->body_html)
                            <div class="evx-inspector-toolbar">
                                {{-- Tres anchos, no dos: el salto de tableta es
                                     donde más plantillas de correo se rompen
                                     (media queries pensadas solo para 320-400 px
                                     y tablas de 600 px que ahí ya no caben). --}}
                                <div class="evx-device-toggle" role="group">
                                    <button type="button" class="on js-inspector-device" data-width="" aria-pressed="true">
                                        {{ __('helpdeskemailactivity::emaillog.preview.desktop') }}
                                    </button>
                                    <button type="button" class="js-inspector-device" data-width="tablet" aria-pressed="false">
                                        {{ __('helpdeskemailactivity::emaillog.preview.inspector.tablet') }}
                                    </button>
                                    <button type="button" class="js-inspector-device" data-width="mobile" aria-pressed="false">
                                        {{ __('helpdeskemailactivity::emaillog.preview.mobile') }}
                                    </button>
                                </div>
                            </div>
                            <iframe id="inspectorFrame" class="evx-frame"
                                    title="{{ __('helpdeskemailactivity::emaillog.preview.inspector.tabs.html') }}"
                                    sandbox="allow-popups allow-popups-to-escape-sandbox"
                                    referrerpolicy="no-referrer"
                                    srcdoc="{{ $log->body_html }}"></iframe>
                        @else
                            {{-- Tres motivos distintos para no tener HTML, y no
                                 dan el mismo diagnóstico: purgado a posteriori,
                                 descartado por configuración al enviar, o que el
                                 correo nunca llevó parte HTML. --}}
                            <div class="evx-empty-card">
                                @if($log->isBodyRedacted())
                                    {{ __('helpdeskemailactivity::emaillog.preview.inspector.html.purged') }}
                                @elseif($log->htmlBodyWasDiscarded())
                                    {{ __('helpdeskemailactivity::emaillog.preview.inspector.html.discarded') }}
                                    @can('helpdeskemailactivity.manage')
                                        <a href="{{ route('settings.helpdeskemailactivity.index') }}">
                                            {{ __('helpdeskemailactivity::emaillog.preview.inspector.html.discarded_hint') }}
                                        </a>
                                    @endcan
                                @else
                                    {{ __('helpdeskemailactivity::emaillog.preview.inspector.html.empty') }}
                                @endif
                            </div>
                        @endif
                    </div>

                    {{-- Código HTML --}}
                    <div class="evx-subpanel" data-evx-subpanel="source" hidden
                         role="tabpanel" id="evx-subpanel-source" aria-labelledby="evx-subtab-source">
                        @if($log->body_html)
                            <div class="evx-inspector-toolbar">
                                <button type="button" class="evx-btn evx-btn-outline evx-btn-inline js-inspector-copy"
                                        data-target="#inspectorSource"
                                        data-toast="{{ __('helpdeskemailactivity::emaillog.preview.inspector.source.copied') }}">
                                    <i class="fa-regular fa-copy" aria-hidden="true"></i>
                                    {{ __('helpdeskemailactivity::emaillog.preview.inspector.source.copy') }}
                                </button>
                            </div>
                            {{-- js-highlight-html: el resaltado lo aplica el JS
                                 del listado sobre el texto ya escapado por Blade
                                 (ver highlightHtml()) — nunca se inyecta HTML del
                                 correo en el DOM del panel. --}}
                            <pre class="evx-code js-highlight-html" id="inspectorSource">{{ $log->body_html }}</pre>
                        @else
                            <div class="evx-empty-card">
                                {{ $log->htmlBodyWasDiscarded()
                                    ? __('helpdeskemailactivity::emaillog.preview.inspector.source.discarded')
                                    : __('helpdeskemailactivity::emaillog.preview.inspector.source.empty') }}
                            </div>
                        @endif
                    </div>

                    {{-- Texto plano: su ausencia no es un detalle estético —
                         los clientes que no pintan HTML no verán nada y algunos
                         filtros antispam penalizan el correo solo-HTML. --}}
                    <div class="evx-subpanel" data-evx-subpanel="text" hidden
                         role="tabpanel" id="evx-subpanel-text" aria-labelledby="evx-subtab-text">
                        @if($log->body_text)
                            <div class="evx-inspector-toolbar">
                                <button type="button" class="evx-btn evx-btn-outline evx-btn-inline js-inspector-copy"
                                        data-target="#inspectorText"
                                        data-toast="{{ __('helpdeskemailactivity::emaillog.preview.inspector.text.copied') }}">
                                    <i class="fa-regular fa-copy" aria-hidden="true"></i>
                                    {{ __('helpdeskemailactivity::emaillog.preview.inspector.text.copy') }}
                                </button>
                            </div>
                            <pre class="evx-preview-text" id="inspectorText">{{ $log->body_text }}</pre>
                        @else
                            <div class="evx-empty-card">
                                {{ $log->textBodyWasDiscarded()
                                    ? __('helpdeskemailactivity::emaillog.preview.inspector.text.discarded')
                                    : __('helpdeskemailactivity::emaillog.preview.inspector.text.empty') }}
                            </div>
                        @endif
                    </div>

                    {{-- Cabeceras, ya separadas en pares por
                         EmailMessageAssembler::headers() (respeta las
                         continuaciones de línea y agrupa las repetidas). --}}
                    <div class="evx-subpanel" data-evx-subpanel="headers" hidden
                         role="tabpanel" id="evx-subpanel-headers" aria-labelledby="evx-subtab-headers">
                        @if($messageHeaders)
                            <div class="evx-inspector-toolbar">
                                <input type="search" class="evx-input js-headers-filter"
                                       placeholder="{{ __('helpdeskemailactivity::emaillog.preview.inspector.headers.filter') }}"
                                       aria-label="{{ __('helpdeskemailactivity::emaillog.preview.inspector.headers.filter') }}">
                                <span class="evx-inspector-toolbar-meta">
                                    {{ trans_choice('helpdeskemailactivity::emaillog.preview.inspector.headers.count', count($messageHeaders), ['count' => count($messageHeaders)]) }}
                                </span>
                            </div>
                            <div class="evx-headers">
                                @foreach($messageHeaders as $header)
                                    <div class="evx-header-row" data-header-name="{{ Str::lower($header['name']) }}">
                                        <span class="k">{{ $header['name'] }}</span>
                                        <span class="v">{{ $header['value'] }}</span>
                                    </div>
                                @endforeach
                                <div class="evx-empty-card js-headers-empty" hidden>
                                    {{ __('helpdeskemailactivity::emaillog.preview.inspector.headers.no_matches') }}
                                </div>
                            </div>
                        @else
                            <div class="evx-empty-card">{{ __('helpdeskemailactivity::emaillog.preview.inspector.headers.empty') }}</div>
                        @endif
                    </div>

                    {{-- Mensaje completo, el mismo contenido byte a byte que se
                         descarga como .eml (ver EmailMessageAssembler::eml). --}}
                    <div class="evx-subpanel" data-evx-subpanel="raw" hidden
                         role="tabpanel" id="evx-subpanel-raw" aria-labelledby="evx-subtab-raw">
                        @if($emlContent)
                            <div class="evx-inspector-toolbar">
                                <span class="evx-inspector-toolbar-meta">
                                    {{ __('helpdeskemailactivity::emaillog.preview.inspector.raw.hint') }}
                                </span>
                                <button type="button" class="evx-btn evx-btn-outline evx-btn-inline js-inspector-copy"
                                        data-target="#inspectorRaw"
                                        data-toast="{{ __('helpdeskemailactivity::emaillog.preview.inspector.raw.copied') }}">
                                    <i class="fa-regular fa-copy" aria-hidden="true"></i>
                                    {{ __('helpdeskemailactivity::emaillog.preview.inspector.raw.copy') }}
                                </button>
                            </div>
                            @if($emlReconstructed)
                                <div class="evx-inspector-note">
                                    <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                                    {{ __('helpdeskemailactivity::emaillog.preview.inspector.raw.reconstructed') }}
                                </div>
                            @endif
                            <pre class="evx-raw-headers js-highlight-mime" id="inspectorRaw">{{ $emlContent }}</pre>
                        @else
                            <div class="evx-empty-card">{{ __('helpdeskemailactivity::emaillog.preview.raw.not_captured') }}</div>
                        @endif
                    </div>

                    {{-- Compatibilidad: se pide al abrir la sub-pestaña y se
                         guarda en el propio nodo, para no repetir el análisis
                         cada vez que se vuelve a ella. --}}
                    <div class="evx-subpanel" data-evx-subpanel="html-check" hidden
                         role="tabpanel" id="evx-subpanel-html-check" aria-labelledby="evx-subtab-html-check">
                        <div class="js-html-check-body">
                            <div class="evx-inspector-loading">
                                <i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i>
                                {{ __('helpdeskemailactivity::emaillog.preview.inspector.html_check.loading') }}
                            </div>
                        </div>
                    </div>

                    {{-- Enlaces: nunca automático. Ver la nota de
                         EmailLinkCheckService. --}}
                    <div class="evx-subpanel" data-evx-subpanel="link-check" hidden
                         role="tabpanel" id="evx-subpanel-link-check" aria-labelledby="evx-subtab-link-check">
                        @if(! $log->body_html && ! $log->body_text)
                            {{-- Sin cuerpo guardado no hay enlaces que sacar:
                                 ofrecer el botón daría un "0 enlaces" que se
                                 leería como "el correo no tenía ninguno". --}}
                            <div class="evx-empty-card">
                                {{ ($log->htmlBodyWasDiscarded() || $log->textBodyWasDiscarded())
                                    ? __('helpdeskemailactivity::emaillog.preview.inspector.link_check.discarded')
                                    : __('helpdeskemailactivity::emaillog.preview.inspector.link_check.empty') }}
                            </div>
                        @else
                        <div class="evx-linkcheck-intro">
                            <p>{{ __('helpdeskemailactivity::emaillog.preview.inspector.link_check.intro') }}</p>
                            <div class="evx-inspector-note">
                                <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                                {{ __('helpdeskemailactivity::emaillog.preview.inspector.link_check.warning') }}
                            </div>
                            <div class="evx-linkcheck-actions">
                                <button type="button" class="evx-btn evx-btn-primary evx-btn-inline js-link-check-run">
                                    <i class="fa-solid fa-link" aria-hidden="true"></i>
                                    {{ __('helpdeskemailactivity::emaillog.preview.inspector.link_check.run') }}
                                </button>
                                <label class="evx-check">
                                    <input type="checkbox" class="js-link-check-follow">
                                    <span>{{ __('helpdeskemailactivity::emaillog.preview.inspector.link_check.follow') }}</span>
                                </label>
                            </div>
                        </div>
                        <div class="js-link-check-body"
                             data-history-url="{{ route('helpdeskemailactivity.link-check-history', $log->uid) }}"></div>
                        @endif
                    </div>

                </div>
            </div>

            {{-- Pestaña: Bitácora de este email — todo lo que
                 EmailLogController::logActivity() ya registraba sobre este
                 registro (activity('email-log') con performedOn($log)),
                 ahora visible desde el propio módulo. --}}
            <div class="evx-tabpanel evx-tabpanel-stack" data-evx-panel="activity" hidden>
                <div class="evx-section-head">
                    <span class="t">{{ __('helpdeskemailactivity::emaillog.activity_log.title') }}</span>
                    <span class="s">{{ __('helpdeskemailactivity::emaillog.activity_log.hint') }}</span>
                    {{-- Si el módulo Activity tiene su propia UI de bitácora
                         completa (filtrable por log_name/evento/fecha), se
                         enlaza en vez de duplicarla aquí — ver
                         Modules\Activity\Http\Controllers\ActivityController::audit(). --}}
                    @can('Activity.audit.index')
                        <a href="{{ route('activity.audit', ['log_name' => 'email-log', 'search' => $log->uid]) }}"
                           class="evx-btn evx-btn-outline evx-btn-inline evx-section-head-btn" target="_blank" rel="noopener">
                            {{ __('helpdeskemailactivity::emaillog.activity_log.view_full_audit') }}
                        </a>
                    @endcan
                </div>

                <div class="evx-event-list">
                    @forelse($activityLog as $entry)
                            @php
                                $eventKey = 'helpdeskemailactivity::emaillog.activity_log.events.'.$entry->event;
                                $eventLabel = __($eventKey);
                                $eventLabel = $eventLabel === $eventKey ? ($entry->description ?: $entry->event) : $eventLabel;
                                $causerName = $entry->causer?->name ?? $entry->causer?->email;
                                $extraProps = ($entry->properties ?? collect())
                                    ->except(['ip'])
                                    ->filter(fn ($value) => $value !== null && $value !== '');
                            @endphp
                        <div class="evx-event-row is-stacked">
                            <span class="evx-event-icon">
                                <i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i>
                            </span>
                            <span class="evx-event-main">
                                <span class="evx-event-url">{{ $eventLabel }}</span>
                                <span class="evx-event-sub">
                                    {{ $entry->created_at->format('d/m/Y H:i:s') }}
                                    · {{ $causerName ?? __('helpdeskemailactivity::emaillog.activity_log.system') }}
                                    @if($extraProps->isNotEmpty())
                                        · {{ $extraProps->map(fn ($value, $key) => $key.': '.(is_scalar($value) ? $value : json_encode($value)))->implode(' · ') }}
                                    @endif
                                </span>
                            </span>
                        </div>
                    @empty
                        <div class="evx-event-empty">{{ __('helpdeskemailactivity::emaillog.activity_log.empty') }}</div>
                    @endforelse
                </div>
            </div>

        </div>

        {{-- Nota inferior --}}
        <div class="evx-bottom-note">
            <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
            {{ __('helpdeskemailactivity::emaillog.preview.footer_note') }}
        </div>

    </div>

    {{-- Tercera columna del workspace: acciones + contexto. --}}
    <aside class="evx-sidebar">

        {{-- Registro en papelera: el panel entra en solo lectura (canManage ya
             llega en false desde el controlador) y las únicas dos acciones que
             siguen teniendo sentido se ofrecen aquí arriba. --}}
        @if($isTrashed)
            <div class="evx-block evx-trashed-notice">
                <div class="evx-inspector-note">
                    <i class="fa-regular fa-trash-can" aria-hidden="true"></i>
                    {{ __('helpdeskemailactivity::emaillog.trash.detail_notice', [
                        'date' => $log->deleted_at?->format('d/m/Y H:i'),
                    ]) }}
                </div>
                @can('helpdeskemailactivity.manage')
                    <div class="evx-trashed-actions">
                        <button type="button" class="evx-btn evx-btn-primary js-trash-restore"
                                data-url="{{ route('helpdeskemailactivity.trash.restore', $log->uid) }}">
                            {{ __('helpdeskemailactivity::emaillog.trash.restore') }}
                        </button>
                        <button type="button" class="evx-btn evx-btn-danger js-trash-force-delete"
                                data-url="{{ route('helpdeskemailactivity.trash.force-destroy', $log->uid) }}">
                            {{ __('helpdeskemailactivity::emaillog.trash.force_delete') }}
                        </button>
                    </div>
                    <div class="evx-trashed-actions">
                        <a href="{{ route('helpdeskemailactivity.trash.index') }}" class="evx-btn evx-btn-outline">
                            {{ __('helpdeskemailactivity::emaillog.trash.back_to_trash') }}
                        </a>
                    </div>
                @endcan
            </div>
        @endif

        {{-- Acciones rápidas --}}
        <div class="evx-block">
            <div class="evx-block-head">
                <div>
                    <span class="t">{{ __('helpdeskemailactivity::emaillog.preview.quick_actions') }}</span>
                    <span class="s">{{ __('helpdeskemailactivity::emaillog.preview.quick_actions_hint') }}</span>
                </div>
                {{-- uid a la derecha de la cabecera, como el mockup: identifica
                     de un vistazo sobre qué registro actúan estas acciones. --}}
                <span class="evx-block-head-tag mono">{{ Str::substr($log->uid, 0, 8) }}</span>
            </div>
            <div class="evx-list">

                @if($canManage)
                    <div class="evx-list-group-title">{{ __('helpdeskemailactivity::emaillog.preview.groups.resend') }}</div>

                    <button type="button" class="evx-list-row evx-list-row-btn js-resend"
                            data-url="{{ route('helpdeskemailactivity.resend', $log->uid) }}">
                        <span class="evx-option-icon"><i class="fa-solid fa-rotate-right" aria-hidden="true"></i></span>
                        <span class="evx-list-main">
                            <span class="evx-list-title">{{ __('helpdeskemailactivity::emaillog.actions.resend_recipient') }}</span>
                            <span class="evx-list-sub">{{ __('helpdeskemailactivity::emaillog.resend.recipient_hint') }}</span>
                        </span>
                    </button>

                    <button type="button" class="evx-list-row evx-list-row-btn" id="btnResendTo"
                            data-url="{{ route('helpdeskemailactivity.resend', $log->uid) }}">
                        <span class="evx-option-icon"><i class="fa-solid fa-share" aria-hidden="true"></i></span>
                        <span class="evx-list-main">
                            <span class="evx-list-title">{{ __('helpdeskemailactivity::emaillog.actions.resend_to') }}</span>
                            <span class="evx-list-sub">{{ __('helpdeskemailactivity::emaillog.resend.to_short_hint') }}</span>
                        </span>
                    </button>

                    {{-- Copia de prueba al correo del propio usuario — reutiliza
                         EXACTAMENTE el endpoint de reenvío (ResendEmailLogRequest
                         ya acepta 'to' y ahora también 'test'), sin ruta nueva. --}}
                    @if(auth()->user()?->email)
                        <button type="button" class="evx-list-row evx-list-row-btn js-resend-test"
                                data-url="{{ route('helpdeskemailactivity.resend', $log->uid) }}"
                                data-to="{{ auth()->user()->email }}">
                            <span class="evx-option-icon"><i class="fa-solid fa-vial" aria-hidden="true"></i></span>
                            <span class="evx-list-main">
                                <span class="evx-list-title">{{ __('helpdeskemailactivity::emaillog.actions.resend_test') }}</span>
                                <span class="evx-list-sub">{{ __('helpdeskemailactivity::emaillog.resend.test_hint') }}</span>
                            </span>
                        </button>
                    @endif
                @endif

                <div class="evx-list-group-title">{{ __('helpdeskemailactivity::emaillog.preview.groups.retrieve') }}</div>

                {{-- Una sola descarga, como el mockup: el .eml ya es la copia
                     íntegra (cabeceras + cuerpo), así que bajar el HTML suelto
                     por separado no aportaba nada. --}}
                @if($log->raw_headers || $log->body_html || $log->body_text)
                    <a href="{{ route('helpdeskemailactivity.raw', $log->uid) }}" class="evx-list-row evx-list-row-btn">
                        <span class="evx-option-icon"><i class="fa-solid fa-download" aria-hidden="true"></i></span>
                        <span class="evx-list-main">
                            <span class="evx-list-title">{{ __('helpdeskemailactivity::emaillog.actions.download_eml') }}</span>
                            {{-- Tamaño REAL del .eml (ver EmailLogController::emlSizeLabel()) —
                                 sin él el subtítulo se queda solo en "copia íntegra". --}}
                            <span class="evx-list-sub">
                                {{ $emlSizeLabel
                                    ? __('helpdeskemailactivity::emaillog.actions.download_eml_hint_size', ['size' => $emlSizeLabel])
                                    : __('helpdeskemailactivity::emaillog.actions.download_eml_hint') }}
                            </span>
                        </span>
                    </a>
                @endif

                @if($log->message_id)
                    <button type="button" class="evx-list-row evx-list-row-btn evx-copy-btn" data-copy="{{ $log->message_id }}">
                        <span class="evx-option-icon"><i class="fa-regular fa-copy" aria-hidden="true"></i></span>
                        <span class="evx-list-main">
                            <span class="evx-list-title">{{ __('helpdeskemailactivity::emaillog.actions.copy_id') }}</span>
                            <span class="evx-list-sub">{{ __('helpdeskemailactivity::emaillog.actions.copy_id_hint') }}</span>
                        </span>
                    </button>
                @endif

                <button type="button" class="evx-list-row evx-list-row-btn" id="btnPrint">
                    <span class="evx-option-icon"><i class="fa-solid fa-print" aria-hidden="true"></i></span>
                    <span class="evx-list-main">
                        <span class="evx-list-title">{{ __('helpdeskemailactivity::emaillog.actions.print') }}</span>
                        <span class="evx-list-sub">{{ __('helpdeskemailactivity::emaillog.actions.print_hint') }}</span>
                    </span>
                </button>

                @if($canManage)
                    <div class="evx-list-group-title">{{ __('helpdeskemailactivity::emaillog.preview.groups.lifecycle') }}</div>

                    {{-- Purgar antes que papelera, como el mockup: purgar es
                         la acción menos destructiva de las dos (conserva los
                         metadatos y el registro sigue en el listado). --}}
                    @if($log->body_html || $log->body_text)
                        <button type="button" class="evx-list-row evx-list-row-btn js-purge"
                                data-url="{{ route('helpdeskemailactivity.purge-body', $log->uid) }}">
                            <span class="evx-option-icon is-strong"><i class="fa-solid fa-eraser" aria-hidden="true"></i></span>
                            <span class="evx-list-main">
                                <span class="evx-list-title">{{ __('helpdeskemailactivity::emaillog.actions.purge') }}</span>
                                <span class="evx-list-sub">{{ __('helpdeskemailactivity::emaillog.purge.hint') }}</span>
                            </span>
                        </button>
                    @endif

                    {{-- Ya no es un borrado definitivo: desde que existe la
                         papelera el registro es recuperable, y el texto debe
                         decirlo para que nadie dude en usarlo. --}}
                    <button type="button" class="evx-list-row evx-list-row-btn js-delete"
                            data-url="{{ route('helpdeskemailactivity.destroy', $log->uid) }}">
                        <span class="evx-option-icon is-strong"><i class="fa-regular fa-trash-can" aria-hidden="true"></i></span>
                        <span class="evx-list-main">
                            <span class="evx-list-title">{{ __('helpdeskemailactivity::emaillog.actions.move_to_trash') }}</span>
                            <span class="evx-list-sub">{{ __('helpdeskemailactivity::emaillog.trash.recoverable_hint', ['days' => $trashRetentionDays ?? 30]) }}</span>
                        </span>
                    </button>

                    {{-- Ir a la papelera: baja aquí desde la barra de filtros,
                         junto a la acción que manda registros a ella. Es
                         navegación, no una acción sobre este email, pero es
                         donde se busca después de enviar algo a la papelera. --}}
                    <a href="{{ route('helpdeskemailactivity.trash.index') }}" class="evx-list-row">
                        <span class="evx-option-icon"><i class="fa-solid fa-box-archive" aria-hidden="true"></i></span>
                        <span class="evx-list-main">
                            <span class="evx-list-title">{{ __('helpdeskemailactivity::emaillog.trash.link') }}</span>
                            <span class="evx-list-sub">{{ __('helpdeskemailactivity::emaillog.trash.link_hint') }}</span>
                        </span>
                    </a>
                @endif

            </div>
        </div>

        {{-- Entidad relacionada --}}
        @if($log->entity_type)
            <div class="evx-block">
                <div class="evx-block-head">
                    <div>
                        <span class="t">{{ __('helpdeskemailactivity::emaillog.preview.related_entity') }}</span>
                        <span class="s">{{ __('helpdeskemailactivity::emaillog.preview.related_entity_hint') }}</span>
                    </div>
                </div>
                <div class="evx-block-body">
                    {{-- Ficha que da el módulo dueño de la entidad, si sabe darla
                         (EmailLogEntitySummaryProvider): título, estado y sus dos
                         o tres datos con nombre. Cuando no la hay, la tarjeta cae
                         al tipo + ID genéricos, que es lo único que este módulo
                         conoce por sí solo. --}}
                    @if($entitySummary)
                        <div class="evx-entity-card">
                            <span class="evx-entity-icon">
                                <i class="fa-solid {{ $entitySummary['icon'] ?? 'fa-link' }}" aria-hidden="true"></i>
                            </span>
                            <span class="evx-entity-main">
                                <span class="evx-entity-title-row">
                                    <span class="evx-entity-title">{{ $log->entity_label }} {{ $entitySummary['title'] }}</span>
                                    @if($entitySummary['badge'] ?? null)
                                        <span class="evx-tag">{{ $entitySummary['badge'] }}</span>
                                    @endif
                                </span>
                                @if($entitySummary['subtitle'] ?? null)
                                    <span class="evx-entity-subtitle">{{ $entitySummary['subtitle'] }}</span>
                                @endif
                            </span>
                        </div>
                    @endif

                    <div class="evx-kv-mini">
                        @forelse($entitySummary['rows'] ?? [] as $row)
                            <span class="k">{{ $row['label'] }}</span>
                            <span class="v">{{ $row['value'] }}</span>
                        @empty
                            {{-- Sin ficha del satélite: lo único que este módulo
                                 sabe de la entidad es su tipo y su ID. --}}
                            <span class="k">{{ __('helpdeskemailactivity::emaillog.preview.field.entity') }}</span>
                            <span class="v">{{ $log->entity_label }}</span>
                            <span class="k">{{ __('helpdeskemailactivity::emaillog.preview.related_entity_id_label') }}</span>
                            <span class="v mono">#{{ $log->entity_id }}</span>
                        @endforelse
                        {{-- Cuántos emails tiene el hilo SÍ es dato de este módulo,
                             no del satélite: lo añade aquí, no el renderer. --}}
                        @if($related->isNotEmpty())
                            <span class="k">{{ __('helpdeskemailactivity::emaillog.preview.related_entity_emails_label') }}</span>
                            <span class="v mono">{{ trans_choice('helpdeskemailactivity::emaillog.preview.thread_count', $related->count() + 1, ['count' => $related->count() + 1]) }}</span>
                        @endif
                    </div>
                    <div class="evx-entity-actions {{ $log->entity_url ? '' : 'is-single' }}">
                        @if($log->entity_url)
                            <a href="{{ $log->entity_url }}" target="_blank" rel="noopener"
                               class="evx-btn evx-btn-outline evx-btn-inline">
                                <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>
                                {{ __('helpdeskemailactivity::emaillog.preview.related_entity_open') }}
                            </a>
                        @endif
                        <a href="{{ route('helpdeskemailactivity.index', ['entity_type' => $log->entity_type, 'entity_id' => $log->entity_id]) }}"
                           class="evx-btn evx-btn-outline evx-btn-inline">
                            <i class="fa-solid fa-filter" aria-hidden="true"></i>
                            {{ __('helpdeskemailactivity::emaillog.preview.related_entity_filter') }}
                        </a>
                    </div>
                </div>
            </div>
        @elseif($canManage && $ticketsModuleEnabled)
            {{-- Sin entidad vinculada: ofrece "Vincular" en vez de dejar el
                 hueco vacío (mockup) — ver EmailLogController::linkEntity()/
                 searchTickets(). Solo se ofrece cuando HelpdeskTickets está
                 activo: hoy es el único buscador de entidades implementado
                 (ver LinkEmailLogEntityRequest::ALLOWED_ENTITY_TYPES). --}}
            <div class="evx-block">
                <div class="evx-block-head">
                    <div>
                        <span class="t">{{ __('helpdeskemailactivity::emaillog.preview.related_entity') }}</span>
                        <span class="s">{{ __('helpdeskemailactivity::emaillog.preview.related_entity_hint') }}</span>
                    </div>
                </div>
                <div class="evx-block-body">
                    <div class="evx-field">
                        <span class="v"><span class="muted">{{ __('helpdeskemailactivity::emaillog.preview.related_entity_none') }}</span></span>
                    </div>
                    <button type="button" class="evx-btn evx-btn-outline evx-btn-inline w-100" id="btnLinkEntity"
                            data-url="{{ route('helpdeskemailactivity.link-entity', $log->uid) }}"
                            data-search-url="{{ route('helpdeskemailactivity.tickets.search') }}">
                        {{ __('helpdeskemailactivity::emaillog.preview.related_entity_link_cta') }}
                    </button>
                </div>
            </div>
        @endif

        {{-- Panel de entidad inyectado por el módulo dueño (p. ej. HelpdeskTickets
             pintando el hilo del ticket) — ver EntityPanelRegistry. No confundir con
             la tarjeta "Entidad relacionada" de arriba: aquella es el enlace genérico
             entity_type/entity_id que ya conoce este módulo; esta es HTML propio del
             módulo satélite, que HelpdeskEmailActivity nunca interpreta ni valida. --}}
        @if($entityPanel)
            <div class="evx-block">
                <div class="evx-block-head">
                    <span class="t">{{ __('helpdeskemailactivity::emaillog.preview.entity_panel_title') }}</span>
                </div>
                <div class="evx-block-body">
                    {!! $entityPanel !!}
                </div>
            </div>
        @endif

        {{-- Ficha del destinatario. Nombre, empresa y estado salen de su ficha
             de cliente del core cuando esa dirección es de un cliente
             registrado (ver EmailLogController::resolveRecipientCustomer());
             si no lo es, ninguna de las tres se pinta — no se deducen del
             correo. Lo que sí es siempre real: cuántos emails ha recibido, su
             última apertura registrada y su tasa de entrega, calculada con la
             misma fórmula que el KPI global (enviados/total). --}}
        @if($recipientStats)
            <div class="evx-block">
                <div class="evx-block-head">
                    <div>
                        <span class="t">{{ __('helpdeskemailactivity::emaillog.preview.recipient.title') }}</span>
                    </div>
                    {{-- Dominio del destinatario a la derecha, como el mockup:
                         dice de un vistazo si es un buzón corporativo o de
                         consumo, que es lo que condiciona la entregabilidad. --}}
                    <span class="evx-block-head-tag mono">&#64;{{ Str::after($recipientStats['email'], '@') }}</span>
                </div>
                <div class="evx-block-body">
                    {{-- Las iniciales salen del nombre cuando lo hay; si esa
                         dirección no es de ningún cliente, del propio correo. --}}
                    <div class="evx-recipient-card">
                        <span class="evx-avatar">{{ Str::upper(Str::substr($recipientStats['name'] ?: $recipientStats['email'], 0, 2)) }}</span>
                        <span class="evx-recipient-ident">
                            @if($recipientStats['name'])
                                <span class="evx-recipient-name">{{ $recipientStats['name'] }}</span>
                            @endif
                            <span class="evx-recipient-mail mono">{{ $recipientStats['email'] }}</span>
                            {{-- Activo/bloqueado solo si esa dirección tiene ficha
                                 de cliente: is_banned null significa "no se sabe",
                                 no "está activo". --}}
                            @if($recipientStats['is_banned'] !== null)
                                <span class="evx-recipient-badges">
                                    <span class="evx-status {{ $recipientStats['is_banned'] ? 'suppressed' : 'sent' }}">
                                        {{ $recipientStats['is_banned']
                                            ? __('helpdeskemailactivity::emaillog.preview.recipient.state_banned')
                                            : __('helpdeskemailactivity::emaillog.preview.recipient.state_active') }}
                                    </span>
                                </span>
                            @endif
                        </span>
                    </div>
                    <div class="evx-kv-mini">
                        @if($recipientStats['company'])
                            <span class="k">{{ __('helpdeskemailactivity::emaillog.preview.recipient.company') }}</span>
                            <span class="v">{{ $recipientStats['company'] }}</span>
                        @endif
                        <span class="k">{{ __('helpdeskemailactivity::emaillog.preview.recipient.received') }}</span>
                        <span class="v mono">{{ number_format($recipientStats['total_received']) }}</span>
                        <span class="k">{{ __('helpdeskemailactivity::emaillog.preview.recipient.last_open') }}</span>
                        <span class="v mono">{{ $recipientStats['last_opened_at']?->format('d/m/Y H:i') ?? '—' }}</span>
                        <span class="k">{{ __('helpdeskemailactivity::emaillog.preview.recipient.delivery_rate') }}</span>
                        <span class="v mono">{{ $recipientStats['delivery_rate'] !== null ? $recipientStats['delivery_rate'].'%' : '—' }}</span>
                    </div>
                    {{-- "Ficha" solo cuando esa dirección tiene una ficha real a la
                         que ir (ver EmailLogController::recipientProfileUrl()); si
                         no, "Sus emails" ocupa la fila entera en vez de dejar un
                         botón muerto al lado. --}}
                    <div class="evx-entity-actions {{ $recipientStats['profile_url'] ? '' : 'is-single' }}">
                        @if($recipientStats['profile_url'])
                            <a href="{{ $recipientStats['profile_url'] }}"
                               class="evx-btn evx-btn-outline evx-btn-inline">
                                <i class="fa-regular fa-address-card" aria-hidden="true"></i>
                                {{ __('helpdeskemailactivity::emaillog.preview.recipient.profile') }}
                            </a>
                        @endif
                        <a href="{{ route('helpdeskemailactivity.index', ['search' => $recipientStats['email']]) }}"
                           class="evx-btn evx-btn-outline evx-btn-inline">
                            <i class="fa-solid fa-filter" aria-hidden="true"></i>
                            {{ __('helpdeskemailactivity::emaillog.preview.recipient.filter') }}
                        </a>
                    </div>

                    {{-- "Enviado por" — el causer real del envío. El mockup
                         lo pone al pie de esta ficha; se omite entero si el
                         envío fue automático y no hay usuario detrás. --}}
                    @if($log->causer)
                        @php
                            $causerName = $log->causer->name ?? ($log->causer->email ?? ('#'.$log->causer_id));
                        @endphp
                        <div class="evx-recipient-causer">
                            <span class="evx-avatar sm">{{ Str::upper(Str::substr($causerName, 0, 2)) }}</span>
                            <span class="evx-list-main">
                                <span class="evx-list-title">{{ __('helpdeskemailactivity::emaillog.preview.recipient.sent_by', ['name' => $causerName]) }}</span>
                                {{-- Rol si lo tiene (mockup: "Agente · Soporte"); si
                                     no, el correo, que al menos identifica a quién
                                     fue. Nunca los dos: el pie es de una línea. --}}
                                @if($causerRole)
                                    <span class="evx-list-sub">{{ $causerRole }}</span>
                                @elseif($log->causer->email ?? null)
                                    <span class="evx-list-sub mono">{{ $log->causer->email }}</span>
                                @endif
                            </span>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        {{-- Emails relacionados. Se muestran solo los primeros y el resto
             va tras un enlace al listado filtrado, como el "hilo del
             cliente" del mockup: con la lista completa el sidebar
             acababa midiendo más que el propio detalle. --}}
        @php
            $relatedShown = $related->take(4);
            $relatedFilterUrl = $log->entity_type && $log->entity_id
                ? route('helpdeskemailactivity.index', ['entity_type' => $log->entity_type, 'entity_id' => $log->entity_id])
                : route('helpdeskemailactivity.index', ['search' => $log->to_addresses[0] ?? '']);
        @endphp
        <div class="evx-block">
            <div class="evx-block-head">
                <div>
                    <span class="t">{{ __('helpdeskemailactivity::emaillog.preview.related_emails') }}</span>
                    <span class="s">{{ __('helpdeskemailactivity::emaillog.preview.related_emails_hint') }}</span>
                </div>
                {{-- El hilo cuenta también el email abierto, no solo los otros:
                     si el sidebar dice "3 emails" tienen que verse 3 filas. --}}
                <span class="evx-block-head-tag mono">{{ trans_choice('helpdeskemailactivity::emaillog.preview.thread_count', $related->count() + 1, ['count' => $related->count() + 1]) }}</span>
            </div>
            <div class="evx-block-body">
                {{-- El email abierto encabeza el hilo, marcado y sin enlace: es
                     el que ya se está mirando (mockup: "· este email"). --}}
                <span class="evx-related is-current" aria-current="true">
                    <span class="evx-row-dot {{ $log->status?->value }}" aria-hidden="true"></span>
                    <span class="evx-list-main">
                        <span class="evx-related-subject">{{ Str::limit($log->subject, 42) ?: '—' }}</span>
                        <span class="evx-related-meta">
                            {{ $log->status_label }} · {{ __('helpdeskemailactivity::emaillog.preview.this_email') }}
                        </span>
                    </span>
                </span>

                @forelse($relatedShown as $rel)
                    @php $rv = $rel->status?->value; @endphp
                    {{-- Fila del hilo como en el mockup: punto de estado,
                         asunto y "estado · fecha" en una línea apagada, con
                         chevron. Antes cada una era una tarjeta con borde y
                         el sidebar acababa siendo más alto que el detalle. --}}
                    <a href="{{ route('helpdeskemailactivity.show', $rel->uid) }}" class="evx-related">
                        <span class="evx-row-dot {{ $rv }}" aria-hidden="true"></span>
                        <span class="evx-list-main">
                            <span class="evx-related-subject">{{ Str::limit($rel->subject, 42) ?: '—' }}</span>
                            <span class="evx-related-meta">
                                {{ $rel->status_label }} · {{ $rel->display_date->format('d/m/Y H:i') }}
                            </span>
                        </span>
                        <i class="fa-solid fa-chevron-right evx-related-chevron" aria-hidden="true"></i>
                    </a>
                @empty
                    {{-- Solo está el email abierto: el hilo no tiene nada más. --}}
                @endforelse
            </div>
            @if($related->isNotEmpty())
                <div class="evx-block-foot">
                    <a href="{{ $relatedFilterUrl }}">
                        {{ __('helpdeskemailactivity::emaillog.preview.thread_see_all', ['count' => $related->count() + 1]) }} →
                    </a>
                </div>
            @endif
        </div>

    </aside>

@endif
