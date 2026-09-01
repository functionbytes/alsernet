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

            <div class="evx-ph-subject-row">
                <div class="evx-ph-subject-wrap">
                    <span class="evx-subject-lg">{{ $log->subject ?: '—' }}</span>
                    <span class="evx-status {{ $log->status?->value }}">
                        <i class="fa-solid {{ $statusIcon }}" aria-hidden="true"></i>{{ $log->status_label }}
                    </span>
                    <span class="evx-chip">#{{ Str::upper(Str::substr($log->uid, 0, 8)) }}</span>
                    @if($log->module)
                        <span class="evx-tag mono">{{ $log->module }}</span>
                    @endif
                    @if($log->attachments)
                        <span class="evx-tag" title="{{ __('helpdeskemaillog::emaillog.preview.field.attachments') }}">
                            <i class="fa-solid fa-paperclip" aria-hidden="true"></i>{{ count($log->attachments) }}
                        </span>
                    @endif
                </div>

                <div class="evx-ph-actions">
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

            <div class="evx-ph-meta">
                <span class="evx-ph-meta-label">{{ __('helpdeskemaillog::emaillog.preview.field.to') }}</span>
                <span class="evx-mono">
                    @forelse($log->to_addresses ?? [] as $addr)
                        {{ $addr }}@if(!$loop->last),@endif
                    @empty
                        —
                    @endforelse
                </span>
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
                    </div>
                    <div class="evx-list">

                        @if($canManage)
                            <div class="evx-list-group-title">{{ __('helpdeskemaillog::emaillog.preview.groups.resend') }}</div>

                            <button type="button" class="evx-list-row evx-list-row-btn js-resend"
                                    data-url="{{ route('helpdeskemaillog.resend', $log->uid) }}">
                                <span class="evx-option-icon"><i class="fa-solid fa-paper-plane" aria-hidden="true"></i></span>
                                <span class="evx-list-main">
                                    <span class="evx-list-title">{{ __('helpdeskemaillog::emaillog.actions.resend') }}</span>
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

                            <button type="button" class="evx-list-row evx-list-row-btn js-delete"
                                    data-url="{{ route('helpdeskemaillog.destroy', $log->uid) }}">
                                <span class="evx-option-icon is-danger"><i class="fa-solid fa-trash" aria-hidden="true"></i></span>
                                <span class="evx-list-main">
                                    <span class="evx-list-title">{{ __('helpdeskemaillog::emaillog.actions.delete') }}</span>
                                </span>
                            </button>

                            @if($log->body_html || $log->body_text)
                                <button type="button" class="evx-list-row evx-list-row-btn js-purge"
                                        data-url="{{ route('helpdeskemaillog.purge-body', $log->uid) }}">
                                    <span class="evx-option-icon is-danger"><i class="fa-solid fa-eraser" aria-hidden="true"></i></span>
                                    <span class="evx-list-main">
                                        <span class="evx-list-title">{{ __('helpdeskemaillog::emaillog.actions.purge') }}</span>
                                    </span>
                                </button>
                            @endif
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
                            <div class="evx-field">
                                <span class="k">{{ $log->entity_label }}</span>
                                <span class="v">
                                    @if($log->entity_url)
                                        <a href="{{ $log->entity_url }}" target="_blank" rel="noopener">
                                            {{ $log->entity_label }} #{{ $log->entity_id }}
                                            <i class="fa-solid fa-arrow-up-right-from-square fa-xs" aria-hidden="true"></i>
                                        </a>
                                    @else
                                        {{ $log->entity_label }} #{{ $log->entity_id }}
                                    @endif
                                </span>
                            </div>
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
                                <span class="s">{{ Str::after($recipientStats['email'], '@') }}</span>
                            </div>
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
                        </div>
                    </div>
                @endif

                {{-- Emails relacionados --}}
                <div class="evx-block">
                    <div class="evx-block-head">
                        <div>
                            <span class="t">{{ __('helpdeskemaillog::emaillog.preview.related_emails') }}</span>
                            <span class="s">{{ __('helpdeskemaillog::emaillog.preview.related_emails_hint') }}</span>
                        </div>
                    </div>
                    <div class="evx-block-body">
                        @forelse($related as $rel)
                            @php $rv = $rel->status?->value; @endphp
                            <a href="{{ route('helpdeskemaillog.show', $rel->uid) }}" class="evx-related">
                                <span class="evx-related-subject">{{ Str::limit($rel->subject, 42) ?: '—' }}</span>
                                <span class="evx-related-meta">
                                    <span class="evx-status {{ $rv }}">
                                        <i class="fa-solid {{ ['sent' => 'fa-check', 'failed' => 'fa-xmark', 'queued' => 'fa-clock'][$rv] ?? 'fa-circle' }}" aria-hidden="true"></i>{{ $rel->status_label }}
                                    </span>
                                    <span class="evx-related-date">{{ $rel->display_date->format('d/m/Y H:i') }}</span>
                                </span>
                            </a>
                        @empty
                            <div class="evx-field"><span class="v"><span class="muted">{{ __('helpdeskemaillog::emaillog.preview.no_related') }}</span></span></div>
                        @endforelse
                    </div>
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
