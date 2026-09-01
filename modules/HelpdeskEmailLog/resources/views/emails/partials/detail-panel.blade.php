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
            <p>{{ __('helpdeskemaillog::emaillog.preview.empty_selection.title') }}</p>
            <p class="small mb-0">{{ __('helpdeskemaillog::emaillog.preview.empty_selection.hint') }}</p>
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
                <span class="evx-label">{{ __('helpdeskemaillog::emaillog.preview.title') }}</span>
                @if($selectedPosition)
                    <span class="evx-ph-position">{{ __('helpdeskemaillog::emaillog.preview.position', ['position' => number_format($selectedPosition), 'total' => number_format($selectedTotal)]) }}</span>
                @endif
                <span class="evx-ph-nav">
                    <button type="button" class="evx-nav-btn js-detail-prev"
                            data-href="{{ $prevUid ? route('helpdeskemaillog.show', $prevUid) : '' }}"
                            @disabled(! $prevUid)
                            title="{{ __('helpdeskemaillog::emaillog.preview.prev') }}"
                            aria-label="{{ __('helpdeskemaillog::emaillog.preview.prev') }}">
                        <i class="fa-solid fa-chevron-up" aria-hidden="true"></i>
                    </button>
                    <button type="button" class="evx-nav-btn js-detail-next"
                            data-href="{{ $nextUid ? route('helpdeskemaillog.show', $nextUid) : '' }}"
                            @disabled(! $nextUid)
                            title="{{ __('helpdeskemaillog::emaillog.preview.next') }}"
                            aria-label="{{ __('helpdeskemaillog::emaillog.preview.next') }}">
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
                            <span class="evx-tag" title="{{ __('helpdeskemaillog::emaillog.preview.field.attachments') }}">
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
                                data-url="{{ route('helpdeskemaillog.resolve-bounce', $log->uid) }}"
                                data-old-address="{{ $log->to_addresses[0] ?? '' }}"
                                data-error="{{ $log->error_message }}"
                                data-hard="{{ $log->bounceType() === 'hard' ? '1' : '0' }}">
                            <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
                            {{ __('helpdeskemaillog::emaillog.bounce_triage.cta') }}
                        </button>
                    @endif
                    @if($canManage)
                        <button type="button" class="evx-btn evx-btn-primary evx-btn-inline js-resend"
                                data-url="{{ route('helpdeskemaillog.resend', $log->uid) }}">
                            <i class="fa-solid fa-paper-plane" aria-hidden="true"></i>
                            {{ __('helpdeskemaillog::emaillog.actions.resend') }}
                        </button>
                    @endif
                    @if($log->raw_headers || $log->body_html || $log->body_text)
                        <a href="{{ route('helpdeskemaillog.raw', $log->uid) }}" class="evx-icon-btn"
                           aria-label="{{ __('helpdeskemaillog::emaillog.actions.download_eml') }}"
                           title="{{ __('helpdeskemaillog::emaillog.actions.download_eml') }}">
                            <i class="fa-solid fa-file-arrow-down" aria-hidden="true"></i>
                        </a>
                    @endif
                    @if($log->message_id)
                        <button type="button" class="evx-icon-btn evx-copy-btn" data-copy="{{ $log->message_id }}"
                                aria-label="{{ __('helpdeskemaillog::emaillog.actions.copy_id') }}"
                                title="{{ __('helpdeskemaillog::emaillog.actions.copy_id') }}">
                            <i class="fa-regular fa-copy" aria-hidden="true"></i>
                        </button>
                    @endif
                    @if($canManage)
                        <button type="button" class="evx-icon-btn is-danger js-delete"
                                data-url="{{ route('helpdeskemaillog.destroy', $log->uid) }}"
                                aria-label="{{ __('helpdeskemaillog::emaillog.actions.delete') }}"
                                title="{{ __('helpdeskemaillog::emaillog.actions.delete') }}">
                            <i class="fa-solid fa-trash" aria-hidden="true"></i>
                        </button>
                    @endif
                </div>
            </div>

            {{-- Línea de contexto del mockup: destinatario · cuándo se registró ·
                 quién lo envió. El "por X" solo aparece si hubo un usuario detrás
                 (los envíos automáticos no tienen causer y no se inventa uno). --}}
            <div class="evx-ph-meta">
                <i class="fa-regular fa-user evx-ph-meta-icon" aria-hidden="true"></i>
                <span class="evx-mono">
                    @forelse($log->to_addresses ?? [] as $addr)
                        {{ $addr }}@if(!$loop->last),@endif
                    @empty
                        —
                    @endforelse
                </span>
                <span class="evx-muted">·</span>
                <span>{{ __('helpdeskemaillog::emaillog.preview.field.created_at') }} {{ $log->created_at?->format('d/m/Y H:i') }}</span>
                @if($log->causer)
                    <span class="evx-muted">·</span>
                    <span>{{ __('helpdeskemaillog::emaillog.preview.by', ['name' => $log->causer->name ?? ($log->causer->email ?? '#'.$log->causer_id)]) }}</span>
                @endif
                <span class="evx-muted">· {{ $log->display_date->diffForHumans() }}</span>
            </div>

            {{-- Etiquetas cortas + icono por pestaña, igual que el mockup
                 (Detalle / Traza / Aperturas / Original); los nombres largos
                 siguen usándose como títulos dentro de cada panel. --}}
            <div class="evx-tabs" role="tablist">
                <button type="button" class="evx-tab on" data-evx-tab="detail">
                    <i class="fa-regular fa-envelope" aria-hidden="true"></i>
                    {{ __('helpdeskemaillog::emaillog.preview.tabs.detail') }}
                </button>
                <button type="button" class="evx-tab" data-evx-tab="trace">
                    <i class="fa-solid fa-route" aria-hidden="true"></i>
                    {{ __('helpdeskemaillog::emaillog.preview.tabs.trace') }}
                    <span class="evx-tab-count">{{ $traceStepsCount }}</span>
                </button>
                @if($hasInteractions)
                    <button type="button" class="evx-tab" data-evx-tab="opens">
                        <i class="fa-regular fa-envelope-open" aria-hidden="true"></i>
                        {{ __('helpdeskemaillog::emaillog.preview.tabs.opens') }}
                        <span class="evx-tab-count">{{ $interactionsCount }}</span>
                    </button>
                @endif
                <button type="button" class="evx-tab" data-evx-tab="raw">
                    <i class="fa-solid fa-code" aria-hidden="true"></i>
                    {{ __('helpdeskemaillog::emaillog.preview.tabs.raw') }}
                </button>
                {{-- Bitácora de ESTE email (EmailLogController::logActivity()) —
                     siempre visible, incluso vacía: un email sin ninguna acción
                     registrada todavía es un dato real (nunca reenviado/descargado/
                     borrado), no algo que ocultar. --}}
                <button type="button" class="evx-tab" data-evx-tab="activity">
                    <i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i>
                    {{ __('helpdeskemaillog::emaillog.preview.tabs.activity') }}
                    @if($activityLog->isNotEmpty())
                        <span class="evx-tab-count">{{ $activityLog->count() }}</span>
                    @endif
                </button>
            </div>
        </div>

        {{-- Cuerpo: pestañas (columna principal) + sidebar de acciones/contexto --}}
        <div class="evx-body-cols">

            <div class="evx-main">

                {{-- Pestaña: Detalle --}}
                <div class="evx-tabpanel" data-evx-panel="detail">

                    <div class="evx-kv-grid">

                        <div class="evx-field">
                            <span class="k">{{ __('helpdeskemaillog::emaillog.preview.field.from') }}</span>
                            <span class="v">
                                @if($log->from_name){{ $log->from_name }} @endif
                                <span class="mono">{{ $log->from_address }}</span>
                            </span>
                        </div>

                        <div class="evx-field">
                            <span class="k">{{ __('helpdeskemaillog::emaillog.preview.field.to') }}</span>
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
                                <span class="k">{{ __('helpdeskemaillog::emaillog.preview.field.cc') }}</span>
                                <span class="v mono">
                                    @foreach($log->cc_addresses as $addr)
                                        {{ $addr }}@if(!$loop->last)<br>@endif
                                    @endforeach
                                </span>
                            </div>
                        @endif

                        @if($log->bcc_addresses)
                            <div class="evx-field">
                                <span class="k">{{ __('helpdeskemaillog::emaillog.preview.field.bcc') }}</span>
                                <span class="v mono">
                                    @foreach($log->bcc_addresses as $addr)
                                        {{ $addr }}@if(!$loop->last)<br>@endif
                                    @endforeach
                                </span>
                            </div>
                        @endif

                        @if($log->reply_to)
                            <div class="evx-field">
                                <span class="k">{{ __('helpdeskemaillog::emaillog.preview.field.reply_to') }}</span>
                                <span class="v mono">
                                    @foreach($log->reply_to as $addr)
                                        {{ $addr }}@if(!$loop->last)<br>@endif
                                    @endforeach
                                </span>
                            </div>
                        @endif

                        @if($log->message_id)
                            <div class="evx-field">
                                <span class="k">{{ __('helpdeskemaillog::emaillog.preview.field.message_id') }}</span>
                                <span class="v mono evx-copy-row">
                                    <span class="evx-copy-text">{{ $log->message_id }}</span>
                                    <button type="button" class="evx-copy-btn" data-copy="{{ $log->message_id }}"
                                            aria-label="{{ __('helpdeskemaillog::emaillog.actions.copy_id') }}"
                                            title="{{ __('helpdeskemaillog::emaillog.actions.copy_id') }}">
                                        <i class="fa-regular fa-copy" aria-hidden="true"></i>
                                    </button>
                                </span>
                            </div>
                        @endif

                        @if($log->module)
                            <div class="evx-field">
                                <span class="k">{{ __('helpdeskemaillog::emaillog.preview.field.module') }}</span>
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
                                <span class="k">{{ __('helpdeskemaillog::emaillog.preview.field.causer') }}</span>
                                <span class="v">{{ $log->causer->name ?? ($log->causer->email ?? ('#'.$log->causer_id)) }}</span>
                            </div>
                        @endif

                        @if($log->attachments)
                            <div class="evx-field">
                                <span class="k">{{ __('helpdeskemaillog::emaillog.preview.field.attachments') }}</span>
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
                            <span class="k">{{ __('helpdeskemaillog::emaillog.preview.field.created_at') }}</span>
                            <span class="v mono">{{ $log->created_at?->format('d/m/Y H:i:s') }}</span>
                        </div>

                        <div class="evx-field">
                            <span class="k">{{ __('helpdeskemaillog::emaillog.preview.field.sent_at') }}</span>
                            <span class="v mono">
                                @if($log->sent_at)
                                    {{ $log->sent_at->format('d/m/Y H:i:s') }}
                                @else
                                    <span class="muted">{{ __('helpdeskemaillog::emaillog.preview.trace.pending') }}</span>
                                @endif
                            </span>
                        </div>

                        @if($log->error_message)
                            <div class="evx-field">
                                <span class="k">{{ __('helpdeskemaillog::emaillog.preview.field.error') }}</span>
                                <div class="evx-alert">{{ $log->error_message }}</div>
                            </div>
                        @endif

                    </div>

                    <div class="evx-block evx-content-card">
                        <div class="evx-content-head">
                            <span class="evx-content-head-label">{{ __('helpdeskemaillog::emaillog.preview.heading') }}</span>
                            @if($log->attachments)
                                <span class="evx-attach-chip" title="{{ __('helpdeskemaillog::emaillog.preview.field.attachments') }}">
                                    <i class="fa-solid fa-paperclip" aria-hidden="true"></i>
                                    <span>{{ count($log->attachments) }}</span>
                                </span>
                            @endif
                            <div class="evx-device-toggle" role="group" aria-label="{{ __('helpdeskemaillog::emaillog.preview.heading') }}">
                                <button type="button" class="on" id="btnDesktopView" aria-pressed="true"
                                        aria-label="{{ __('helpdeskemaillog::emaillog.preview.desktop') }}"
                                        title="{{ __('helpdeskemaillog::emaillog.preview.desktop') }}">
                                    <i class="fa-regular fa-window-maximize" aria-hidden="true"></i>
                                </button>
                                <button type="button" id="btnMobileView" aria-pressed="false"
                                        aria-label="{{ __('helpdeskemaillog::emaillog.preview.mobile') }}"
                                        title="{{ __('helpdeskemaillog::emaillog.preview.mobile') }}">
                                    <i class="fa-solid fa-mobile-screen" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>
                        <div class="evx-preview-body">
                            @if($log->body_html)
                                {{-- Renderizado dentro de un iframe con sandbox: los scripts no se ejecutan y
                                     el contenido vive en un origen opaco, mostrando el HTML original con fidelidad
                                     pero sin riesgo de XSS para el panel de administración. --}}
                                <iframe id="previewFrame" class="evx-frame"
                                        title="{{ __('helpdeskemaillog::emaillog.preview.heading') }}"
                                        sandbox="allow-popups allow-popups-to-escape-sandbox"
                                        referrerpolicy="no-referrer"
                                        srcdoc="{{ $log->body_html }}"></iframe>
                            @elseif($log->body_text)
                                <pre class="evx-preview-text">{{ $log->body_text }}</pre>
                            @else
                                <div class="evx-empty">
                                    <i class="fa-regular fa-envelope" aria-hidden="true"></i>
                                    {{ ($log->metadata['redacted'] ?? false)
                                        ? __('helpdeskemaillog::emaillog.preview.purged_note')
                                        : __('helpdeskemaillog::emaillog.preview.no_content') }}
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
                            $log->status?->value === 'sent' => __('helpdeskemaillog::emaillog.preview.trace.smtp'),
                            $log->status?->value === 'bounced' => match ($log->bounceType()) {
                                'hard' => __('helpdeskemaillog::emaillog.preview.trace.bounce_hard'),
                                'soft' => __('helpdeskemaillog::emaillog.preview.trace.bounce_soft'),
                                default => __('helpdeskemaillog::emaillog.preview.trace.bounce_unknown'),
                            },
                            $log->status?->value === 'complained' => __('helpdeskemaillog::emaillog.preview.trace.complained_context'),
                            $log->status?->value === 'suppressed' => __('helpdeskemaillog::emaillog.preview.trace.suppressed_context'),
                            $log->status?->value === 'failed' => Str::limit($log->error_message ?: __('helpdeskemaillog::emaillog.preview.trace.failed'), 90),
                            $isStaleQueued => __('helpdeskemaillog::emaillog.preview.trace.stale', ['hours' => $staleHours]),
                            default => __('helpdeskemaillog::emaillog.preview.trace.pending'),
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
                            <span class="lbl">{{ __('helpdeskemaillog::emaillog.preview.trace.total') }}</span>
                        </span>
                    </div>

                    {{-- b) "Recorrido del envío": solo un encabezado, la timeline de
                         abajo ya existía. --}}
                    <div class="evx-trace-timeline-group">
                    <span class="evx-trace-section-label">{{ __('helpdeskemaillog::emaillog.preview.trace.journey') }}</span>
                    <div class="evx-block">
                        <div class="evx-block-head">
                            <span class="s">{{ __('helpdeskemaillog::emaillog.preview.trace.hint') }}</span>
                        </div>

                        {{-- Paso 1: Encolado --}}
                        <div class="evx-trace-step">
                            <span class="evx-trace-icon ok"><i class="fa-solid fa-inbox" aria-hidden="true"></i></span>
                            <div class="evx-trace-main">
                                <div class="evx-trace-title">{{ __('helpdeskemaillog::emaillog.preview.trace.queued') }}</div>
                                <div class="evx-trace-meta">{{ __('helpdeskemaillog::emaillog.preview.trace.done') }}</div>
                            </div>
                            <div class="evx-trace-time">{{ $log->created_at?->format('d/m/Y H:i:s') }}</div>
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
                                <div class="evx-trace-title">{{ __('helpdeskemaillog::emaillog.preview.trace.smtp') }}</div>
                                <div class="evx-trace-meta">
                                    @if($log->sent_at)
                                        {{ __('helpdeskemaillog::emaillog.preview.trace.done') }}
                                    @elseif($log->status?->value === 'failed')
                                        {{ __('helpdeskemaillog::emaillog.preview.trace.failed') }}
                                    @else
                                        {{ __('helpdeskemaillog::emaillog.preview.trace.pending') }}
                                        @if($isStaleQueued)
                                            · {{ __('helpdeskemaillog::emaillog.preview.trace.stale', ['hours' => $staleHours]) }}
                                        @endif
                                    @endif
                                </div>
                            </div>
                            <div class="evx-trace-time">
                                {{ ($log->sent_at ?? $log->failed_at)?->format('d/m/Y H:i:s') }}
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
                                <div class="evx-trace-title">{{ __('helpdeskemaillog::emaillog.preview.trace.delivery') }}</div>
                                <div class="evx-trace-meta">
                                    @if($log->bounced_at)
                                        {{ __('helpdeskemaillog::emaillog.status.bounced') }}
                                    @elseif($log->complained_at)
                                        {{ __('helpdeskemaillog::emaillog.status.complained') }}
                                    @else
                                        {{ __('helpdeskemaillog::emaillog.preview.trace.no_delivery_data') }}
                                    @endif
                                </div>
                            </div>
                            <div class="evx-trace-time">
                                {{ ($log->bounced_at ?? $log->complained_at)?->format('d/m/Y H:i:s') }}
                            </div>
                        </div>

                        {{-- Paso 4: Apertura (solo si este envío tuvo píxel) --}}
                        @if($opensSummary !== null)
                            <div class="evx-trace-step">
                                <span class="evx-trace-icon {{ $opensSummary['count'] > 0 ? 'open' : '' }}">
                                    <i class="fa-solid fa-eye" aria-hidden="true"></i>
                                </span>
                                <div class="evx-trace-main">
                                    <div class="evx-trace-title">{{ __('helpdeskemaillog::emaillog.preview.trace.opened') }}</div>
                                    <div class="evx-trace-meta">
                                        @if($opensSummary['count'] > 0)
                                            {{ trans_choice('helpdeskemaillog::emaillog.preview.trace.opened_count', $opensSummary['count'], ['count' => $opensSummary['count']]) }}
                                        @else
                                            {{ __('helpdeskemaillog::emaillog.preview.trace.not_opened_yet') }}
                                        @endif
                                    </div>
                                </div>
                                <div class="evx-trace-time">
                                    {{ $opensSummary['count'] > 0 ? $opensSummary['first']->format('d/m/Y H:i') : '' }}
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
                                    <div class="evx-trace-title">{{ __('helpdeskemaillog::emaillog.preview.trace.clicked') }}</div>
                                    <div class="evx-trace-meta">
                                        @if($clicksSummary['count'] > 0)
                                            {{ trans_choice('helpdeskemaillog::emaillog.preview.trace.clicked_count', $clicksSummary['count'], ['count' => $clicksSummary['count']]) }}
                                        @else
                                            {{ __('helpdeskemaillog::emaillog.preview.trace.not_clicked_yet') }}
                                        @endif
                                    </div>
                                </div>
                                <div class="evx-trace-time">
                                    {{ $clicksSummary['count'] > 0 ? $clicksSummary['first']->format('d/m/Y H:i') : '' }}
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
                            <span class="evx-trace-card-title">{{ __('helpdeskemaillog::emaillog.preview.trace.transport') }}</span>
                            <div class="evx-trace-kv">
                                <span class="k">{{ __('helpdeskemaillog::emaillog.preview.trace.transport_server') }}</span>
                                <span class="v">
                                    @if($transport['isSmtp'])
                                        {{ $transport['host'] }}:{{ $transport['port'] }}
                                    @else
                                        {{ $transport['mailer'] }}
                                    @endif
                                </span>
                                <span class="k">{{ __('helpdeskemaillog::emaillog.preview.trace.transport_queue') }}</span>
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
                            <span class="evx-trace-card-title">{{ __('helpdeskemaillog::emaillog.preview.trace.auth') }}</span>
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
                                {{ __('helpdeskemaillog::emaillog.preview.trace.auth_note', ['domain' => $domainAuth['domain'] ?: '—']) }}
                                @if(! $domainAuth['spf'] && ! $domainAuth['dkim'] && ! $domainAuth['dmarc'])
                                    <a href="{{ route('helpdeskemaillog.reputation.index') }}">{{ __('helpdeskemaillog::emaillog.preview.trace.auth_check_link') }}</a>
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

                {{-- Pestaña: Aperturas + Clics (solo si este envío tuvo seguimiento) --}}
                @if($hasInteractions)
                    <div class="evx-tabpanel" data-evx-panel="opens" hidden>

                        @if($opensSummary !== null)
                            <div class="evx-block">
                                <div class="evx-block-head">
                                    <div>
                                        <span class="t">{{ __('helpdeskemaillog::emaillog.preview.opens.title') }}</span>
                                        <span class="s">{{ __('helpdeskemaillog::emaillog.preview.opens.hint') }}</span>
                                    </div>
                                    <span class="evx-badge {{ $opensSummary['count'] > 0 ? 'ok' : 'neutral' }}">
                                        {{ number_format($opensSummary['count']) }}
                                    </span>
                                </div>
                                <div class="evx-block-body">
                                    @if($opensSummary['likely_bot_count'] > 0)
                                        <div class="evx-field">
                                            <span class="v">
                                                <span class="evx-tag" title="{{ __('helpdeskemaillog::emaillog.preview.opens.likely_bot_hint') }}">
                                                    {{ trans_choice('helpdeskemaillog::emaillog.preview.opens.likely_bot_count', $opensSummary['likely_bot_count'], ['count' => $opensSummary['likely_bot_count']]) }}
                                                </span>
                                            </span>
                                        </div>
                                    @endif

                                    @if($opensSummary['count'] > 0)
                                        <div class="evx-field">
                                            <span class="k">{{ __('helpdeskemaillog::emaillog.preview.opens.first_last') }}</span>
                                            <span class="v mono">
                                                {{ $opensSummary['first']->format('d/m/Y H:i') }}
                                                @if(!$opensSummary['last']->equalTo($opensSummary['first']))
                                                    — {{ $opensSummary['last']->format('d/m/Y H:i') }}
                                                @endif
                                            </span>
                                        </div>

                                        <div class="evx-field">
                                            <span class="k">{{ __('helpdeskemaillog::emaillog.preview.opens.detail') }}</span>
                                            <span class="v">
                                                @foreach($opensSummary['recent'] as $open)
                                                    <div class="mono">
                                                        {{ $open->opened_at->format('d/m/Y H:i') }} · {{ $open->ip }} · <span class="muted">{{ Str::limit($open->user_agent, 60) }}</span>
                                                        @if($open->likely_bot)
                                                            <span class="evx-tag" title="{{ __('helpdeskemaillog::emaillog.preview.opens.likely_bot_hint') }}">{{ __('helpdeskemaillog::emaillog.preview.likely_bot_badge') }}</span>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </span>
                                        </div>
                                    @endif
                                </div>
                                <div class="evx-bottom-note">
                                    <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                                    {{ __('helpdeskemaillog::emaillog.preview.opens.honesty_note') }}
                                </div>
                            </div>
                        @endif

                        @if($clicksSummary !== null)
                            <div class="evx-block">
                                <div class="evx-block-head">
                                    <div>
                                        <span class="t">{{ __('helpdeskemaillog::emaillog.preview.clicks.title') }}</span>
                                        <span class="s">{{ __('helpdeskemaillog::emaillog.preview.clicks.hint') }}</span>
                                    </div>
                                    <span class="evx-badge {{ $clicksSummary['count'] > 0 ? 'ok' : 'neutral' }}">
                                        {{ number_format($clicksSummary['count']) }}
                                    </span>
                                </div>
                                <div class="evx-block-body">
                                    @if($clicksSummary['likely_bot_count'] > 0)
                                        <div class="evx-field">
                                            <span class="v">
                                                <span class="evx-tag" title="{{ __('helpdeskemaillog::emaillog.preview.clicks.likely_bot_hint') }}">
                                                    {{ trans_choice('helpdeskemaillog::emaillog.preview.clicks.likely_bot_count', $clicksSummary['likely_bot_count'], ['count' => $clicksSummary['likely_bot_count']]) }}
                                                </span>
                                            </span>
                                        </div>
                                    @endif

                                    @if($clicksSummary['count'] > 0)
                                        <div class="evx-field">
                                            <span class="k">{{ __('helpdeskemaillog::emaillog.preview.clicks.unique_links') }}</span>
                                            <span class="v">{{ number_format($clicksSummary['unique_links']) }}</span>
                                        </div>

                                        <div class="evx-field">
                                            <span class="k">{{ __('helpdeskemaillog::emaillog.preview.clicks.first_last') }}</span>
                                            <span class="v mono">
                                                {{ $clicksSummary['first']->format('d/m/Y H:i') }}
                                                @if(!$clicksSummary['last']->equalTo($clicksSummary['first']))
                                                    — {{ $clicksSummary['last']->format('d/m/Y H:i') }}
                                                @endif
                                            </span>
                                        </div>

                                        <div class="evx-field">
                                            <span class="k">{{ __('helpdeskemaillog::emaillog.preview.clicks.detail') }}</span>
                                            <span class="v">
                                                @foreach($clicksSummary['recent'] as $click)
                                                    <div class="mono">
                                                        {{ $click->clicked_at->format('d/m/Y H:i') }} · {{ $click->ip }} ·
                                                        <a href="{{ $click->link_url }}" target="_blank" rel="noopener">{{ Str::limit($click->link_url, 50) }}</a>
                                                        · <span class="muted">{{ Str::limit($click->user_agent, 40) }}</span>
                                                        @if($click->likely_bot)
                                                            <span class="evx-tag" title="{{ __('helpdeskemaillog::emaillog.preview.clicks.likely_bot_hint') }}">{{ __('helpdeskemaillog::emaillog.preview.likely_bot_badge') }}</span>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </span>
                                        </div>
                                    @endif
                                </div>
                                <div class="evx-bottom-note">
                                    <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                                    {{ __('helpdeskemaillog::emaillog.preview.clicks.honesty_note') }}
                                </div>
                            </div>
                        @endif

                    </div>
                @endif

                {{-- Pestaña: Mensaje original (cabeceras) --}}
                <div class="evx-tabpanel" data-evx-panel="raw" hidden>
                    <div class="evx-block">
                        <div class="evx-block-head">
                            <span class="s">{{ __('helpdeskemaillog::emaillog.preview.raw.hint') }}</span>
                            @if($log->raw_headers || $log->body_html || $log->body_text)
                                <a href="{{ route('helpdeskemaillog.raw', $log->uid) }}" class="evx-icon-btn"
                                   aria-label="{{ __('helpdeskemaillog::emaillog.actions.download_eml') }}"
                                   title="{{ __('helpdeskemaillog::emaillog.actions.download_eml') }}">
                                    <i class="fa-solid fa-file-arrow-down" aria-hidden="true"></i>
                                </a>
                            @endif
                        </div>
                        <div class="evx-block-body">
                            @if($log->raw_headers)
                                <pre class="evx-preview-text evx-raw-headers">{{ $log->raw_headers }}</pre>
                            @else
                                <div class="evx-field">
                                    <span class="v"><span class="muted">{{ __('helpdeskemaillog::emaillog.preview.raw.not_captured') }}</span></span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Pestaña: Bitácora de este email — todo lo que
                     EmailLogController::logActivity() ya registraba sobre este
                     registro (activity('email-log') con performedOn($log)),
                     ahora visible desde el propio módulo. --}}
                <div class="evx-tabpanel" data-evx-panel="activity" hidden>
                    <div class="evx-block">
                        <div class="evx-block-head">
                            <div>
                                <span class="t">{{ __('helpdeskemaillog::emaillog.activity_log.title') }}</span>
                                <span class="s">{{ __('helpdeskemaillog::emaillog.activity_log.hint') }}</span>
                            </div>
                            {{-- Si el módulo Activity tiene su propia UI de bitácora
                                 completa (filtrable por log_name/evento/fecha), se
                                 enlaza en vez de duplicarla aquí — ver
                                 Modules\Activity\Http\Controllers\ActivityController::audit(). --}}
                            @can('Activity.audit.index')
                                <a href="{{ route('activity.audit', ['log_name' => 'email-log', 'search' => $log->uid]) }}"
                                   class="evx-btn evx-btn-outline evx-btn-inline" target="_blank" rel="noopener">
                                    {{ __('helpdeskemaillog::emaillog.activity_log.view_full_audit') }}
                                </a>
                            @endcan
                        </div>
                        <div class="evx-block-body">
                            @forelse($activityLog as $entry)
                                @php
                                    $eventKey = 'helpdeskemaillog::emaillog.activity_log.events.'.$entry->event;
                                    $eventLabel = __($eventKey);
                                    $eventLabel = $eventLabel === $eventKey ? ($entry->description ?: $entry->event) : $eventLabel;
                                    $causerName = $entry->causer?->name ?? $entry->causer?->email;
                                    $extraProps = ($entry->properties ?? collect())
                                        ->except(['ip'])
                                        ->filter(fn ($value) => $value !== null && $value !== '');
                                @endphp
                                <div class="evx-field">
                                    <span class="v">
                                        <div class="mono">
                                            {{ $entry->created_at->format('d/m/Y H:i:s') }} ·
                                            <strong>{{ $eventLabel }}</strong> ·
                                            <span class="muted">{{ $causerName ?? __('helpdeskemaillog::emaillog.activity_log.system') }}</span>
                                        </div>
                                        @if($extraProps->isNotEmpty())
                                            <div class="muted small">
                                                {{ $extraProps->map(fn ($value, $key) => $key.': '.(is_scalar($value) ? $value : json_encode($value)))->implode(' · ') }}
                                            </div>
                                        @endif
                                    </span>
                                </div>
                            @empty
                                <div class="evx-field">
                                    <span class="v"><span class="muted">{{ __('helpdeskemaillog::emaillog.activity_log.empty') }}</span></span>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

            </div>

            {{-- Sidebar: acciones + contexto --}}
            <aside class="evx-sidebar">

                {{-- Acciones rápidas --}}
                <div class="evx-block">
                    <div class="evx-block-head">
                        <div>
                            <span class="t">{{ __('helpdeskemaillog::emaillog.preview.quick_actions') }}</span>
                            <span class="s">{{ __('helpdeskemaillog::emaillog.preview.quick_actions_hint') }}</span>
                        </div>
                        {{-- uid a la derecha de la cabecera, como el mockup: identifica
                             de un vistazo sobre qué registro actúan estas acciones. --}}
                        <span class="evx-block-head-tag mono">{{ Str::substr($log->uid, 0, 8) }}</span>
                    </div>
                    <div class="evx-list">

                        @if($canManage)
                            <div class="evx-list-group-title">{{ __('helpdeskemaillog::emaillog.preview.groups.resend') }}</div>

                            <button type="button" class="evx-list-row evx-list-row-btn js-resend"
                                    data-url="{{ route('helpdeskemaillog.resend', $log->uid) }}">
                                <span class="evx-option-icon"><i class="fa-solid fa-paper-plane" aria-hidden="true"></i></span>
                                <span class="evx-list-main">
                                    <span class="evx-list-title">{{ __('helpdeskemaillog::emaillog.actions.resend_recipient') }}</span>
                                    <span class="evx-list-sub">{{ __('helpdeskemaillog::emaillog.resend.recipient_hint') }}</span>
                                </span>
                            </button>

                            <button type="button" class="evx-list-row evx-list-row-btn" id="btnResendTo"
                                    data-url="{{ route('helpdeskemaillog.resend', $log->uid) }}">
                                <span class="evx-option-icon"><i class="fa-solid fa-share" aria-hidden="true"></i></span>
                                <span class="evx-list-main">
                                    <span class="evx-list-title">{{ __('helpdeskemaillog::emaillog.actions.resend_to') }}</span>
                                    <span class="evx-list-sub">{{ __('helpdeskemaillog::emaillog.resend.to_hint') }}</span>
                                </span>
                            </button>

                            {{-- Copia de prueba al correo del propio usuario — reutiliza
                                 EXACTAMENTE el endpoint de reenvío (ResendEmailLogRequest
                                 ya acepta 'to' y ahora también 'test'), sin ruta nueva. --}}
                            @if(auth()->user()?->email)
                                <button type="button" class="evx-list-row evx-list-row-btn js-resend-test"
                                        data-url="{{ route('helpdeskemaillog.resend', $log->uid) }}"
                                        data-to="{{ auth()->user()->email }}">
                                    <span class="evx-option-icon"><i class="fa-solid fa-flask" aria-hidden="true"></i></span>
                                    <span class="evx-list-main">
                                        <span class="evx-list-title">{{ __('helpdeskemaillog::emaillog.actions.resend_test') }}</span>
                                        <span class="evx-list-sub">{{ __('helpdeskemaillog::emaillog.resend.test_hint') }}</span>
                                    </span>
                                </button>
                            @endif
                        @endif

                        <div class="evx-list-group-title">{{ __('helpdeskemaillog::emaillog.preview.groups.retrieve') }}</div>

                        @if($log->body_html || $log->body_text)
                            <a href="{{ route('helpdeskemaillog.download', $log->uid) }}" class="evx-list-row evx-list-row-btn">
                                <span class="evx-option-icon"><i class="fa-solid fa-download" aria-hidden="true"></i></span>
                                <span class="evx-list-main">
                                    <span class="evx-list-title">{{ __('helpdeskemaillog::emaillog.actions.download') }}</span>
                                </span>
                            </a>
                        @endif

                        @if($log->raw_headers || $log->body_html || $log->body_text)
                            <a href="{{ route('helpdeskemaillog.raw', $log->uid) }}" class="evx-list-row evx-list-row-btn">
                                <span class="evx-option-icon"><i class="fa-solid fa-file-arrow-down" aria-hidden="true"></i></span>
                                <span class="evx-list-main">
                                    <span class="evx-list-title">{{ __('helpdeskemaillog::emaillog.actions.download_eml') }}</span>
                                    {{-- Tamaño REAL del .eml (ver EmailLogController::emlSizeLabel()) —
                                         se omite el subtítulo si no hay cuerpo/cabeceras que pesar. --}}
                                    @if($emlSizeLabel)
                                        <span class="evx-list-sub">{{ $emlSizeLabel }}</span>
                                    @endif
                                </span>
                            </a>
                        @endif

                        @if($log->message_id)
                            <button type="button" class="evx-list-row evx-list-row-btn evx-copy-btn" data-copy="{{ $log->message_id }}">
                                <span class="evx-option-icon"><i class="fa-regular fa-copy" aria-hidden="true"></i></span>
                                <span class="evx-list-main">
                                    <span class="evx-list-title">{{ __('helpdeskemaillog::emaillog.actions.copy_id') }}</span>
                                </span>
                            </button>
                        @endif

                        <button type="button" class="evx-list-row evx-list-row-btn" id="btnPrint">
                            <span class="evx-option-icon"><i class="fa-solid fa-print" aria-hidden="true"></i></span>
                            <span class="evx-list-main">
                                <span class="evx-list-title">{{ __('helpdeskemaillog::emaillog.actions.print') }}</span>
                            </span>
                        </button>

                        @if($canManage)
                            <div class="evx-list-group-title">{{ __('helpdeskemaillog::emaillog.preview.groups.lifecycle') }}</div>

                            {{-- Purgar antes que papelera, como el mockup: purgar es
                                 la acción menos destructiva de las dos (conserva los
                                 metadatos y el registro sigue en el listado). --}}
                            @if($log->body_html || $log->body_text)
                                <button type="button" class="evx-list-row evx-list-row-btn js-purge"
                                        data-url="{{ route('helpdeskemaillog.purge-body', $log->uid) }}">
                                    <span class="evx-option-icon is-danger"><i class="fa-solid fa-eraser" aria-hidden="true"></i></span>
                                    <span class="evx-list-main">
                                        <span class="evx-list-title">{{ __('helpdeskemaillog::emaillog.actions.purge') }}</span>
                                        <span class="evx-list-sub">{{ __('helpdeskemaillog::emaillog.purge.hint') }}</span>
                                    </span>
                                </button>
                            @endif

                            {{-- Ya no es un borrado definitivo: desde que existe la
                                 papelera el registro es recuperable, y el texto debe
                                 decirlo para que nadie dude en usarlo. --}}
                            <button type="button" class="evx-list-row evx-list-row-btn js-delete"
                                    data-url="{{ route('helpdeskemaillog.destroy', $log->uid) }}">
                                <span class="evx-option-icon is-danger"><i class="fa-regular fa-trash-can" aria-hidden="true"></i></span>
                                <span class="evx-list-main">
                                    <span class="evx-list-title">{{ __('helpdeskemaillog::emaillog.actions.move_to_trash') }}</span>
                                    <span class="evx-list-sub">{{ __('helpdeskemaillog::emaillog.trash.recoverable_hint', ['days' => $trashRetentionDays ?? 30]) }}</span>
                                </span>
                            </button>
                        @endif

                    </div>
                </div>

                {{-- Entidad relacionada --}}
                @if($log->entity_type)
                    <div class="evx-block">
                        <div class="evx-block-head">
                            <div>
                                <span class="t">{{ __('helpdeskemaillog::emaillog.preview.related_entity') }}</span>
                                <span class="s">{{ __('helpdeskemaillog::emaillog.preview.related_entity_hint') }}</span>
                            </div>
                        </div>
                        <div class="evx-block-body">
                            {{-- Grid con lo que SÍ se sabe de la entidad (tipo + ID, ver
                                 EmailLog::entityLabel()) — este módulo no guarda nada más
                                 propio de la entidad, así que no se inventa nada extra. --}}
                            <div class="evx-kv-mini">
                                <span class="k">{{ __('helpdeskemaillog::emaillog.preview.field.entity') }}</span>
                                <span class="v">{{ $log->entity_label }}</span>
                                <span class="k">{{ __('helpdeskemaillog::emaillog.preview.related_entity_id_label') }}</span>
                                <span class="v mono">#{{ $log->entity_id }}</span>
                            </div>
                            <div class="evx-entity-actions {{ $log->entity_url ? '' : 'is-single' }}">
                                @if($log->entity_url)
                                    <a href="{{ $log->entity_url }}" target="_blank" rel="noopener"
                                       class="evx-btn evx-btn-outline evx-btn-inline">
                                        {{ __('helpdeskemaillog::emaillog.preview.related_entity_open') }}
                                    </a>
                                @endif
                                <a href="{{ route('helpdeskemaillog.index', ['entity_type' => $log->entity_type, 'entity_id' => $log->entity_id]) }}"
                                   class="evx-btn evx-btn-outline evx-btn-inline">
                                    {{ __('helpdeskemaillog::emaillog.preview.related_entity_filter') }}
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
                                <span class="t">{{ __('helpdeskemaillog::emaillog.preview.related_entity') }}</span>
                                <span class="s">{{ __('helpdeskemaillog::emaillog.preview.related_entity_hint') }}</span>
                            </div>
                        </div>
                        <div class="evx-block-body">
                            <div class="evx-field">
                                <span class="v"><span class="muted">{{ __('helpdeskemaillog::emaillog.preview.related_entity_none') }}</span></span>
                            </div>
                            <button type="button" class="evx-btn evx-btn-outline evx-btn-inline w-100" id="btnLinkEntity"
                                    data-url="{{ route('helpdeskemaillog.link-entity', $log->uid) }}"
                                    data-search-url="{{ route('helpdeskemaillog.tickets.search') }}">
                                {{ __('helpdeskemaillog::emaillog.preview.related_entity_link_cta') }}
                            </button>
                        </div>
                    </div>
                @endif

                {{-- Panel de entidad inyectado por el módulo dueño (p. ej. HelpdeskTickets
                     pintando el hilo del ticket) — ver EntityPanelRegistry. No confundir con
                     la tarjeta "Entidad relacionada" de arriba: aquella es el enlace genérico
                     entity_type/entity_id que ya conoce este módulo; esta es HTML propio del
                     módulo satélite, que HelpdeskEmailLog nunca interpreta ni valida. --}}
                @if($entityPanel)
                    <div class="evx-block">
                        <div class="evx-block-head">
                            <span class="t">{{ __('helpdeskemaillog::emaillog.preview.entity_panel_title') }}</span>
                        </div>
                        <div class="evx-block-body">
                            {!! $entityPanel !!}
                        </div>
                    </div>
                @endif

                {{-- Ficha del destinatario. Nombre y empresa van deliberadamente
                     vacíos: este módulo no tiene ninguna tabla local de contactos
                     por email (ver EmailLogController::resolveDetailData()), y no
                     se inventan. Lo que sí es real: cuántos emails ha recibido,
                     su última apertura registrada y su tasa de entrega, calculada
                     con la misma fórmula que el KPI global (enviados/total). --}}
                @if($recipientStats)
                    <div class="evx-block">
                        <div class="evx-block-head">
                            <div>
                                <span class="t">{{ __('helpdeskemaillog::emaillog.preview.recipient.title') }}</span>
                            </div>
                            {{-- Dominio del destinatario a la derecha, como el mockup:
                                 dice de un vistazo si es un buzón corporativo o de
                                 consumo, que es lo que condiciona la entregabilidad. --}}
                            <span class="evx-block-head-tag mono">&#64;{{ Str::after($recipientStats['email'], '@') }}</span>
                        </div>
                        <div class="evx-block-body">
                            <div class="evx-recipient-card">
                                <span class="evx-avatar">{{ Str::upper(Str::substr($recipientStats['email'], 0, 2)) }}</span>
                                <span class="evx-recipient-mail mono">{{ $recipientStats['email'] }}</span>
                            </div>
                            <div class="evx-kv-mini">
                                <span class="k">{{ __('helpdeskemaillog::emaillog.preview.recipient.received') }}</span>
                                <span class="v mono">{{ number_format($recipientStats['total_received']) }}</span>
                                <span class="k">{{ __('helpdeskemaillog::emaillog.preview.recipient.last_open') }}</span>
                                <span class="v mono">{{ $recipientStats['last_opened_at']?->format('d/m/Y H:i') ?? '—' }}</span>
                                <span class="k">{{ __('helpdeskemaillog::emaillog.preview.recipient.delivery_rate') }}</span>
                                <span class="v mono">{{ $recipientStats['delivery_rate'] !== null ? $recipientStats['delivery_rate'].'%' : '—' }}</span>
                            </div>
                            <a href="{{ route('helpdeskemaillog.index', ['search' => $recipientStats['email']]) }}"
                               class="evx-btn evx-btn-outline evx-btn-inline w-100">
                                {{ __('helpdeskemaillog::emaillog.preview.recipient.filter') }}
                            </a>

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
                                        <span class="evx-list-title">{{ __('helpdeskemaillog::emaillog.preview.recipient.sent_by', ['name' => $causerName]) }}</span>
                                        @if($log->causer->email ?? null)
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
                    $relatedRest = max(0, $related->count() - $relatedShown->count());
                    $relatedFilterUrl = $log->entity_type && $log->entity_id
                        ? route('helpdeskemaillog.index', ['entity_type' => $log->entity_type, 'entity_id' => $log->entity_id])
                        : route('helpdeskemaillog.index', ['search' => $log->to_addresses[0] ?? '']);
                @endphp
                <div class="evx-block">
                    <div class="evx-block-head">
                        <div>
                            <span class="t">{{ __('helpdeskemaillog::emaillog.preview.related_emails') }}</span>
                            <span class="s">{{ __('helpdeskemaillog::emaillog.preview.related_emails_hint') }}</span>
                        </div>
                        @if($related->isNotEmpty())
                            <span class="evx-block-head-tag mono">{{ $related->count() }}</span>
                        @endif
                    </div>
                    <div class="evx-block-body">
                        @forelse($relatedShown as $rel)
                            @php $rv = $rel->status?->value; @endphp
                            {{-- Fila del hilo como en el mockup: punto de estado,
                                 asunto y "estado · fecha" en una línea apagada, con
                                 chevron. Antes cada una era una tarjeta con borde y
                                 el sidebar acababa siendo más alto que el detalle. --}}
                            <a href="{{ route('helpdeskemaillog.show', $rel->uid) }}" class="evx-related">
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
                            <div class="evx-field"><span class="v"><span class="muted">{{ __('helpdeskemaillog::emaillog.preview.no_related') }}</span></span></div>
                        @endforelse
                    </div>
                    @if($related->isNotEmpty())
                        <div class="evx-block-foot">
                            <a href="{{ $relatedFilterUrl }}">
                                {{ $relatedRest > 0
                                    ? __('helpdeskemaillog::emaillog.preview.related_see_all', ['count' => $related->count()])
                                    : __('helpdeskemaillog::emaillog.preview.related_see_filtered') }} →
                            </a>
                        </div>
                    @endif
                </div>

            </aside>

        </div>

        {{-- Nota inferior --}}
        <div class="evx-bottom-note">
            <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
            {{ __('helpdeskemaillog::emaillog.preview.footer_note') }}
        </div>

    </div>

@endif
