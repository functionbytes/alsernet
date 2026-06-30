{{-- Hilo de chat — Refined v4 --}}
@includeIf('helpdeskhelpcenter::partials.thread-extension')
@php
    $convo = $selectedConversation ?? null;
    $cust = $convo?->customer;
@endphp
<div class="bv-thread">
@if(empty($selectedConversationId) || !$convo)
    <div class="bv-thread-empty">
        <div class="bv-thread-empty-icon">
            <i class="far fa-comments"></i>
        </div>
        <div class="bv-thread-empty-title">Selecciona una conversación</div>
        <div class="bv-thread-empty-sub">Elige una conversación del panel izquierdo para ver el hilo y responder</div>
    </div>
@else
    @php
        $custName = $cust?->name ?? 'Sin nombre';
        $custInitials = mb_strtoupper(collect(preg_split('/\s+/', trim($custName)))->take(2)->map(fn($w) => mb_substr($w,0,1))->implode(''));
        $colorIdx = (($cust?->id ?? $convo?->id ?? 1) - 1) % 8 + 1;
        $channelMap = [
            'whatsapp' => ['code' => 'wa', 'icon' => 'fab fa-whatsapp', 'label' => 'WhatsApp'],
            'facebook' => ['code' => 'fb', 'icon' => 'fab fa-facebook-messenger', 'label' => 'Facebook'],
            'instagram' => ['code' => 'ig', 'icon' => 'fab fa-instagram', 'label' => 'Instagram'],
            'email' => ['code' => 'email', 'icon' => 'far fa-envelope', 'label' => 'Email'],
            'widget' => ['code' => 'web', 'icon' => 'far fa-comment-dots', 'label' => 'Widget'],
        ];
        $ch = $channelMap[$convo?->channel ?? 'widget'] ?? $channelMap['widget'];
        $statusName = $convo?->status?->name ?? 'Abierta';
        $statusColor = $convo?->status?->color ?? '#6c757d';
        $priority = $convo?->priority ?? 'normal';
        $priorityLabels = ['low' => 'Baja', 'normal' => 'Normal', 'high' => 'Alta', 'urgent' => 'Urgente'];
        $priorityColors = ['low' => 'muted', 'normal' => '', 'high' => 'warning', 'urgent' => 'danger'];
    @endphp

    {{-- Cabecera del hilo --}}
    <div class="bv-th-head" data-bv-conv-created="{{ $convo?->created_at?->toIso8601String() }}">
        <div class="who">
            <div class="av bv-th-av-c{{ $colorIdx }}">
                {{ $custInitials ?: '?' }}
                <span class="badge-ch {{ $ch['code'] }} bv-th-badge-ch">
                    <i class="{{ $ch['icon'] }}"></i>
                </span>
            </div>
            <div>
                <div class="nm">{{ $custName }}</div>
                <div class="sub">
                    {{ $ch['label'] }}
                    @if($cust?->email) · {{ $cust->email }}@endif
                    @if($cust?->phone) · {{ $cust->phone }}@endif
                </div>
            </div>
        </div>
        <div class="actions">
            @if($convo && ($convo->metadata['handled_by_bot'] ?? false))
            <button class="bv-th-action bv-th-action--takeover" id="bv-btn-takeover"
                    data-takeover-url="{{ route('chatflow.takeover', $convo->id) }}"
                    data-bv-tip="El bot está atendiendo · Toma el control">
                <i class="fas fa-robot"></i> Tomar control
            </button>
            @endif
            @if(helpdesk_feature_enabled('search'))
            <button class="bv-th-action" id="bv-th-search-btn" data-bv-tip="Buscar en la conversación">
                <i class="fas fa-magnifying-glass"></i>
            </button>
            @endif
            @if(helpdesk_feature_enabled('email'))
            <button class="bv-th-action" data-bv-modal="email" data-bv-tip="Enviar email">
                <i class="far fa-envelope"></i>
            </button>
            @endif
            @if(helpdesk_tickets_enabled() && helpdesk_feature_enabled('tickets'))
            <button class="bv-th-action" data-bv-modal="create-ticket" data-bv-tip="Crear ticket">
                <i class="fas fa-ticket"></i>
            </button>
            @endif
            @if(helpdesk_feature_enabled('schedule'))
            <button class="bv-th-action" data-bv-modal="schedule" data-bv-tip="Agendar">
                <i class="far fa-calendar-plus"></i>
            </button>
            @endif
            @if(helpdesk_feature_enabled('snooze'))
            <button class="bv-th-action" data-bv-modal="snooze" data-bv-tip="Posponer">
                <i class="far fa-clock"></i>
            </button>
            @endif
            @if(helpdesk_feature_enabled('assign'))
            <button class="bv-th-action" data-bv-modal="assign" data-bv-tip="Asignar">
                <i class="fas fa-user-plus"></i>
            </button>
            @endif
            @if(helpdesk_feature_enabled('tags'))
            <button class="bv-th-action" data-bv-modal="tags" data-bv-tip="Etiquetar">
                <i class="fas fa-tag"></i>
            </button>
            @endif
            @if($convo?->closed_at)
                <button class="bv-th-action bv-th-action--reopen" id="bv-btn-reopen" data-bv-tip="Reabrir conversación"
                        data-reopen-url="{{ route('manager.helpdesk.conversations.reopen', $convo) }}">
                    <i class="fas fa-rotate-left"></i>
                </button>
            @elseif($convo)
                <button class="bv-th-action" data-bv-modal="close-conv" data-bv-tip="Cerrar conversación">
                    <i class="fas fa-check"></i>
                </button>
            @endif
            {{-- Botón "más" con dropdown --}}
            <div class="bv-th-more-wrap">
                <button class="bv-th-action" id="bv-btn-more" data-bv-tip="Más">
                    <i class="fas fa-ellipsis-vertical"></i>
                </button>
                <div class="bv-more-menu" id="bv-more-menu">
                    @if(helpdesk_feature_enabled('merge'))
                    <button data-bv-modal="merge"><i class="fas fa-code-merge"></i>Fusionar conversación</button>
                    @endif
                    @if(helpdesk_feature_enabled('move_team'))
                    <button data-bv-modal="move-to-team"><i class="fas fa-arrow-right-arrow-left"></i>Mover a equipo</button>
                    @endif
                    @if(helpdesk_feature_enabled('forward'))
                    <button id="bv-btn-forward-conv"><i class="fas fa-share"></i>Reenviar</button>
                    @endif
                    <div class="sep"></div>
                    @if(helpdesk_feature_enabled('preview_conv'))
                    <button data-bv-modal="history"><i class="fas fa-clock-rotate-left"></i>Conversaciones anteriores</button>
                    @endif
                    @if(helpdesk_feature_enabled('note'))
                    <button data-bv-modal="note"><i class="far fa-note-sticky"></i>Añadir nota</button>
                    @endif
                    @if(helpdesk_feature_enabled('csat'))
                    <button id="bv-btn-send-csat"
                            data-csat-url="{{ $convo ? route('manager.helpdesk.conversations.send-csat', $convo) : '' }}">
                        <i class="far fa-star"></i>Enviar encuesta CSAT
                    </button>
                    @endif

                    <button data-bv-modal="audit-log"><i class="fas fa-clock-rotate-left"></i>Actividad de la conversación</button>
                    <button data-bv-modal="help-center"><i class="far fa-circle-question"></i>Centro de ayuda</button>
                    <div class="sep"></div>
                    @if(helpdesk_feature_enabled('spam'))
                    <button  id="bv-btn-mark-spam"
                            data-spam-url="{{ $convo ? route('manager.helpdesk.conversations.mark-spam', $convo) : '' }}">
                        <i class="fas fa-ban"></i>Spam
                    </button>
                    @endif
                    @if(helpdesk_feature_enabled('block_contact'))
                    <button  id="bv-btn-block-contact"
                            data-block-url="{{ $convo ? route('manager.helpdesk.conversations.block-contact', $convo) : '' }}">
                        <i class="fas fa-user-slash"></i>Bloquear contacto
                    </button>
                    @endif
                    @if(helpdesk_feature_enabled('delete_conv'))
                    <button  id="bv-btn-delete-conv"
                            data-delete-url="{{ $convo ? route('manager.helpdesk.conversations.destroy', $convo) : '' }}">
                        <i class="far fa-trash-can"></i>Eliminar
                    </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Barra de búsqueda en el thread --}}
    <div class="bv-th-search bv-hidden" id="bv-th-search">
        <i class="fas fa-magnifying-glass bv-th-search-icon"></i>
        <input type="text" id="bv-th-search-input" class="bv-th-search-input" placeholder="Buscar en la conversación…" autocomplete="off">
        <span class="bv-th-search-count" id="bv-th-search-count"></span>
        <button class="bv-th-search-nav" id="bv-th-search-prev" data-bv-tip="Anterior" disabled><i class="fas fa-chevron-up"></i></button>
        <button class="bv-th-search-nav" id="bv-th-search-next" data-bv-tip="Siguiente" disabled><i class="fas fa-chevron-down"></i></button>
        <button class="bv-th-search-close" id="bv-th-search-close" data-bv-tip="Cerrar"><i class="fas fa-xmark"></i></button>
    </div>

    {{-- Cuerpo del hilo --}}
    <div class="bv-th-body">
        <div class="bv-th-inner">
            @php
                $items = $convo?->items ?? collect();
                $currentDay = null;
            @endphp

            {{-- perf-04: cargar mensajes anteriores (paginación hacia arriba). Degrada sin romper si el endpoint no existe. --}}
            @if($convo && $items->count() >= 50)
                <div class="bv-load-older text-center my-2" id="bv-load-older">
                    <button type="button" class="bv-load-older-btn btn btn-sm btn-light"
                            data-url="{{ url('panel/helpdesk/conversations/'.$convo->id.'/items') }}"
                            data-oldest-id="{{ $items->first()?->id }}">
                        <i class="fas fa-arrow-up"></i> Cargar anteriores
                    </button>
                </div>
            @endif

            @forelse($items as $item)
                @if($item->type === 'email_sent') @continue @endif
                @php
                    $itemDay = optional($item->created_at)->format('Y-m-d');
                    $isActivity = $item->type === 'activity';
                    $isOut = ! empty($item->user_id);
                    $isInternal = (bool) $item->is_internal;
                    $bubbleClass = $isInternal ? 'bv-bubble note' : 'bv-bubble';
                    $msgClass = ($isOut && ! $isInternal) ? 'bv-msg out' : 'bv-msg in';
                    $authorLabel = $isOut
                        ? ($item->user?->name ?? 'Agente')
                        : ($cust?->name ?? 'Cliente');
                    $time = optional($item->created_at)?->format('H:i');
                @endphp

                @if($itemDay && $itemDay !== $currentDay)
                    @php $currentDay = $itemDay; @endphp
                    <div class="bv-day-sep"><span>{{ optional($item->created_at)->translatedFormat('d M') }}</span></div>
                @endif

                @if($isActivity)
                    <div class="bv-event-pill"><span>{{ $item->body }}</span></div>
                @else
                <div class="{{ $msgClass }}">
                    @if(! $isOut || $isInternal)
                        <div class="av-sm bv-th-av-c{{ $colorIdx }}">{{ mb_strtoupper(mb_substr($authorLabel, 0, 1)) }}{{ mb_strtoupper(mb_substr(explode(' ', $authorLabel)[1] ?? '', 0, 1)) }}</div>
                    @endif
                    <div class="{{ $bubbleClass }}"
                         data-bv-item-id="{{ $item->id }}"
                         data-bv-react-url="{{ route('manager.helpdesk.conversation-items.react', $item) }}"
                         data-bv-author="{{ $authorLabel }}"
                         data-bv-is-internal="{{ $isInternal ? '1' : '0' }}"
                         data-bv-is-out="{{ $isOut ? '1' : '0' }}"
                         data-bv-body="{{ $item->body }}"
                         data-bv-body-preview="{{ \Illuminate\Support\Str::limit($item->body ?? '', 80) }}">
                        @if($isInternal)
                            <div class="note-badge"><i class="fas fa-lock"></i> Nota interna</div>
                        @endif
                        @php
                            $replyToId = $item->metadata['reply_to_id'] ?? null;
                            $replyTo = null;
                            if ($replyToId) {
                                $replyTo = $items->firstWhere('id', $replyToId)
                                    ?? \Modules\Helpdesk\Models\ConversationItem::find($replyToId);
                            }
                        @endphp
                        @if($replyTo)
                            <div class="bv-quoted-msg" data-bv-jump-to="{{ $replyTo->id }}">
                                <div class="bv-quoted-author">
                                    {{ $replyTo->user?->name ?? $cust?->name ?? 'Cliente' }}
                                </div>
                                <div class="bv-quoted-body">
                                    {{ \Illuminate\Support\Str::limit($replyTo->body, 80) }}
                                </div>
                            </div>
                        @endif
                        @php
                            $linkPreview = $item->metadata['link_preview'] ?? null;
                            $previewUrlNorm = rtrim((string) ($linkPreview['url'] ?? ''), '/');
                            $bodyTrimmed = trim((string) $item->body);
                            $bodyTrimmedNorm = rtrim($bodyTrimmed, '/');
                            $hideBody = $linkPreview && $previewUrlNorm !== '' && (
                                $bodyTrimmed === $linkPreview['url']
                                || $bodyTrimmedNorm === $previewUrlNorm
                            );
                        @endphp
                        @if($item->body && ! $hideBody)
                            {!! $item->body_html !!}
                        @endif

                        @if(! empty($item->translated_body))
                            <div class="bv-bubble-translation"
                                 data-bv-original="{{ $item->body }}"
                                 data-bv-translated="{{ $item->translated_body }}">
                                <span class="bv-bubble-translation-lbl">&#8627; </span>{{ $item->translated_body }}
                                <button type="button" class="bv-bubble-translation-toggle" title="Ver original">
                                    <i class="fas fa-arrows-rotate"></i>
                                </button>
                            </div>
                        @endif

                        @if(! empty($item->outgoing_translated_body))
                            <div class="bv-bubble-translation bv-outgoing-translation"
                                 data-bv-original="{{ $item->body }}"
                                 data-bv-translated="{{ $item->outgoing_translated_body }}">
                                <span class="bv-bubble-translation-lbl">&#8627; </span>{{ $item->outgoing_translated_body }}
                                <button type="button" class="bv-bubble-translation-toggle" title="Ver original">
                                    <i class="fas fa-arrows-rotate"></i>
                                </button>
                            </div>
                        @endif

                        @if(! $isOut && ! $isInternal && empty($item->translated_body) && \Nwidart\Modules\Facades\Module::find('HelpdeskTranslate')?->isEnabled() && auth()->user()?->can('helpdesk-translate.use'))
                            <button type="button"
                                    class="bv-translate-item-btn btn btn-sm btn-link text-muted px-0 py-0"
                                    data-item-id="{{ $item->id }}"
                                    title="{{ __('helpdesktranslate::messages.thread.btn_translate_title') }}">
                                <i class="fas fa-language"></i> {{ __('helpdesktranslate::messages.thread.btn_translate') }}
                            </button>
                        @endif

                        @if($linkPreview && (! empty($linkPreview['title']) || ! empty($linkPreview['description']) || ! empty($linkPreview['image'])))
                            <a href="{{ $linkPreview['url'] }}" target="_blank" rel="noopener noreferrer" class="bv-link-preview">
                                @if(! empty($linkPreview['image']))
                                    <img src="{{ $linkPreview['image'] }}" alt="{{ $linkPreview['title'] ?? '' }}" loading="lazy" class="bv-lp-img">
                                @endif
                                <div class="bv-lp-body">
                                    @if(! empty($linkPreview['title']))
                                        <p class="bv-lp-title">{{ $linkPreview['title'] }}</p>
                                    @endif
                                    @if(! empty($linkPreview['description']))
                                        <p class="bv-lp-desc">{{ $linkPreview['description'] }}</p>
                                    @endif
                                    <div class="bv-lp-meta">
                                        <i class="fas fa-link"></i>
                                        <span>{{ $linkPreview['site'] ?? parse_url($linkPreview['url'], PHP_URL_HOST) }}</span>
                                    </div>
                                </div>
                            </a>
                        @endif
                        @if($item->hasAttachments())
                            @php
                                $metaAttachments = collect($item->metadata['attachments'] ?? []);
                            @endphp
                            <div class="bv-attachment-gallery">
                                @foreach($item->attachment_urls as $idx => $url)
                                    @php
                                        // attachment_urls may be a plain URL string or an object {url, name, size, mime_type}
                                        $urlEntry = is_array($url) ? $url : ['url' => $url];
                                        $url      = $urlEntry['url'] ?? $url;
                                        $meta = $metaAttachments->get($idx) ?? [];
                                        $meta = array_merge([
                                            'name' => $urlEntry['name'] ?? null,
                                            'size' => $urlEntry['size'] ?? null,
                                            'type' => isset($urlEntry['mime_type']) ? explode('/', $urlEntry['mime_type'])[0] : null,
                                        ], $meta ?: []);
                                        $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
                                        $fileName = $meta['name'] ?? basename(parse_url($url, PHP_URL_PATH));
                                        // .webm from the widget voice recorder (voz-*.webm) is always audio,
                                        // even if the stored mime_type says "video/webm".
                                        $isVoiceWebm = $ext === 'webm' && str_starts_with($fileName, 'voz-');
                                        $rawType = $meta['type'] ?? null;
                                        if ($isVoiceWebm) {
                                            $attachType = 'audio';
                                        } elseif ($rawType && !($rawType === 'video' && $ext === 'webm')) {
                                            $attachType = $rawType;
                                        } else {
                                            $attachType = in_array($ext, ['jpg','jpeg','png','gif','webp']) ? 'image'
                                                : (in_array($ext, ['mp4','mov']) ? 'video'
                                                : (in_array($ext, ['mp3','ogg','wav','webm','oga']) ? 'audio'
                                                : 'document'));
                                        }
                                        $fileSize = isset($meta['size']) ? round($meta['size'] / 1024) . ' KB' : '';
                                        $docIcons = ['pdf' => 'fa-file-pdf', 'doc' => 'fa-file-word', 'docx' => 'fa-file-word', 'xls' => 'fa-file-excel', 'xlsx' => 'fa-file-excel', 'ppt' => 'fa-file-powerpoint', 'pptx' => 'fa-file-powerpoint', 'zip' => 'fa-file-zipper', 'csv' => 'fa-file-csv', 'txt' => 'fa-file-lines'];
                                        $docIcon = $docIcons[$ext] ?? 'fa-file';
                                    @endphp
                                    @if($attachType === 'image')
                                        <a href="{{ $url }}"
                                           class="bv-attach-thumb"
                                           data-bv-modal="file-preview"
                                           data-bv-preview-src="{{ $url }}"
                                           data-bv-preview-type="image"
                                           data-bv-name="{{ $fileName }}">
                                            <img src="{{ $url }}" alt="{{ $fileName }}" loading="lazy" width="200" height="200">
                                        </a>
                                    @elseif($attachType === 'video')
                                        <div class="bv-video-bubble">
                                            <video controls preload="metadata" class="bv-video-player">
                                                <source src="{{ $url }}">
                                                Tu navegador no soporta video.
                                            </video>
                                        </div>
                                    @elseif($attachType === 'audio')
                                        @php
                                            $seed = crc32($url);
                                            mt_srand($seed);
                                            $bars = [];
                                            for ($b = 0; $b < 32; $b++) {
                                                $bars[] = mt_rand(25, 100);
                                            }
                                            mt_srand();
                                            $audioInitials = mb_strtoupper(mb_substr($authorLabel, 0, 1)).mb_strtoupper(mb_substr(explode(' ', $authorLabel)[1] ?? '', 0, 1));
                                        @endphp
                                        <div class="bv-audio-msg" data-bv-audio-src="{{ $url }}">
                                            <div class="bv-audio-avatar bv-th-av-c{{ $colorIdx }}">{{ $audioInitials }}<span class="bv-audio-mic"><i class="fas fa-microphone"></i></span></div>
                                            <button type="button" class="bv-audio-play" aria-label="Reproducir">
                                                <i class="fas fa-play"></i>
                                            </button>
                                            <div class="bv-audio-wave" role="slider" tabindex="0" aria-label="Progreso del audio">
                                                @foreach($bars as $h)
                                                    <span class="bv-audio-bar" style="--bv-audio-bar-h:{{ $h }}%"></span>
                                                @endforeach
                                                <span class="bv-audio-progress-dot"></span>
                                            </div>
                                            <span class="bv-audio-time">0:00</span>
                                            <button type="button" class="bv-audio-speed" data-bv-speed="1" title="Velocidad">1x</button>
                                            <audio preload="metadata" class="bv-audio-el">
                                                <source src="{{ $url }}">
                                            </audio>
                                        </div>
                                    @else
                                        <a href="{{ $url }}" target="_blank" rel="noopener" class="bv-attach-file">
                                            <i class="far {{ $docIcon }}"></i>
                                            <div class="bv-attach-file-info">
                                                <span class="bv-attach-file-name">{{ $fileName }}</span>
                                                @if($fileSize)
                                                    <span class="bv-attach-file-size">{{ $fileSize }}</span>
                                                @endif
                                            </div>
                                        </a>
                                    @endif
                                @endforeach
                            </div>
                        @endif
                        @if($item->type === 'contact')
                            @php $contactMeta = $item->metadata ?? []; @endphp
                            <div class="bv-contact-card">
                                <div class="bv-contact-card-icon"><i class="far fa-address-card"></i></div>
                                <div class="bv-contact-card-info">
                                    <div class="bv-contact-card-name">{{ $contactMeta['name'] ?? '—' }}</div>
                                    @if(!empty($contactMeta['phone']))
                                        <div class="bv-contact-card-detail"><i class="fas fa-phone"></i> {{ $contactMeta['phone'] }}</div>
                                    @endif
                                    @if(!empty($contactMeta['email']))
                                        <div class="bv-contact-card-detail"><i class="far fa-envelope"></i> {{ $contactMeta['email'] }}</div>
                                    @endif
                                </div>
                            </div>
                        @endif
                        @if($item->type === 'location')
                            @php
                                $locMeta = $item->metadata ?? [];
                                $lat = $locMeta['lat'] ?? 0;
                                $lng = $locMeta['lng'] ?? 0;
                                $address = $locMeta['address'] ?? "{$lat}, {$lng}";
                                $mapUrl = "https://www.openstreetmap.org/?mlat={$lat}&mlon={$lng}&zoom=15";
                                $mapImg = "https://staticmap.openstreetmap.de/staticmap.php?center={$lat},{$lng}&zoom=14&size=280x140&markers={$lat},{$lng},red";
                            @endphp
                            <div class="bv-location-bubble">
                                <a href="{{ $mapUrl }}" target="_blank" rel="noopener" class="bv-location-map-link">
                                    <img src="{{ $mapImg }}" alt="Mapa" loading="lazy" class="bv-location-map-img" width="280" height="140">
                                </a>
                                <div class="bv-location-address">
                                    <i class="fas fa-location-dot"></i>
                                    <span>{{ $address }}</span>
                                </div>
                            </div>
                        @endif
                        @php
                            $reactions = collect($item->metadata['reactions'] ?? [])->groupBy('emoji')->map->count();
                        @endphp
                        @if($reactions->isNotEmpty())
                            <div class="bv-bubble-reactions">
                                @foreach($reactions as $emoji => $count)
                                    <button class="bv-reaction"
                                            data-bv-react="{{ $emoji }}"
                                            data-bv-item="{{ $item->id }}">
                                        {{ $emoji }} <span class="c">{{ $count }}</span>
                                    </button>
                                @endforeach
                            </div>
                        @endif
                        <div class="meta">
                            <span>{{ $isOut ? $authorLabel.' · ' : '' }}{{ $time }}</span>
                            @if($isOut && ! $isInternal)
                                <span class="chk read bv-chk-read">✓✓</span>
                            @endif
                        </div>
                    </div>
                </div>
                @endif
            @empty
                <div class="bv-th-no-msgs">
                    <i class="far fa-comment-dots"></i>
                    Sin mensajes en esta conversación
                </div>
            @endforelse
        </div>
    </div>

    {{-- Composer --}}
    <div class="bv-composer"
         data-bv-conversation-id="{{ $convo?->id }}"
         data-bv-send-url="{{ $convo ? route('manager.helpdesk.conversations.messages.store', $convo) : '' }}"
         data-bv-update-url="{{ $convo ? route('manager.helpdesk.conversations.update', $convo) : '' }}"
         data-bv-reopen-url="{{ $convo ? route('manager.helpdesk.conversations.reopen', $convo) : '' }}"
         data-bv-send-email-url="{{ $convo ? route('manager.helpdesk.conversations.send-email', $convo) : '' }}"
         data-bv-send-hsm-url="{{ $convo ? route('manager.helpdesk.conversations.send-hsm', $convo) : '' }}"
         data-bv-attach-url="{{ $convo ? route('manager.helpdesk.conversations.attachments.store', $convo) : '' }}"
         data-bv-contact-url="{{ $convo ? route('manager.helpdesk.conversations.contact.store', $convo) : '' }}"
         data-bv-location-url="{{ $convo ? route('manager.helpdesk.conversations.location.store', $convo) : '' }}">

        {{-- Panel HSM (se muestra/oculta con el tab) --}}
        <div class="bv-hsm-picker" id="bv-hsm-picker">
            <div class="bv-panel-head">
                <i class="fab fa-whatsapp bv-hsm-wa-icon"></i>
                <span>Plantillas HSM</span>
                <button id="bv-hsm-close"><i class="fas fa-xmark"></i></button>
            </div>
            <div class="bv-hsm-body">
                <div class="bv-hsm-list" id="bv-hsm-list">
                    <div class="bv-panel-search">
                        <i class="fas fa-magnifying-glass"></i>
                        <input type="text" id="bv-hsm-search" placeholder="Buscar plantilla…">
                    </div>
                    {{-- Templates cargados dinámicamente vía JS --}}
                </div>
                <div class="bv-hsm-detail">
                    <div class="bv-hsm-preview-label">Vista previa</div>
                    <div class="bv-hsm-chat" id="bv-hsm-preview">
                        <div class="bv-hsm-chat-bubble" id="bv-hsm-preview-bubble">
                            <span id="bv-hsm-preview-text">Selecciona una plantilla…</span>
                            <div class="bv-hsm-chat-time">09:42 ✓✓</div>
                        </div>
                    </div>
                    <div class="bv-hsm-vars" id="bv-hsm-vars">
                        <div class="bv-hsm-vars-title">Variables</div>
                        <div id="bv-hsm-vars-list">
                            {{-- Variables cargadas dinámicamente --}}
                        </div>
                    </div>
                    <div class="bv-hsm-foot">
                        <button class="bv-panel-btn bv-panel-btn-cancel" id="bv-hsm-close-2"><i class="fas fa-xmark"></i> Cancelar</button>
                        <button class="bv-panel-btn bv-panel-btn-confirm" id="bv-hsm-insert"><i class="fas fa-check"></i> Insertar plantilla</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Panel Traducción — provided by HelpdeskTranslate module (no-op when disabled) --}}
        @includeIf('helpdesktranslate::partials.translate-panel')

        {{-- Tabs del composer --}}
        <div class="bv-composer-tabs">
            <button class="bv-composer-tab on" data-bv-tab="reply">
                <i class="fas fa-reply bv-tab-icon"></i>Respuesta
            </button>
            @if(helpdesk_feature_enabled('composer_note'))
            <button class="bv-composer-tab note" data-bv-tab="note">
                <i class="fas fa-lock bv-tab-icon"></i>Nota interna
            </button>
            @endif
            @if(helpdesk_feature_enabled('composer_hsm'))
            <button class="bv-composer-tab" data-bv-tab="hsm">
                <i class="fab fa-whatsapp bv-tab-icon"></i>Plantillas HSM
            </button>
            @endif
            {{-- Pestaña Traducir — provided by HelpdeskTranslate module --}}
            @includeIf('helpdesktranslate::partials.composer-tab')
        </div>

        {{-- Área de texto --}}
        <div class="bv-composer-box" id="bv-composer-box">
            <textarea class="bv-composer-input" placeholder="Escribe tu respuesta… (/ para respuestas rápidas, @ para mencionar)" rows="2"></textarea>
            <div class="bv-composer-toolbar">
                {{-- Adjuntar con menú --}}
                @if(helpdesk_feature_enabled('composer_attach'))
                <div class="bv-attach-wrap">
                    <button class="btn-ico" id="bv-btn-attach" data-bv-tip="Adjuntar">
                        <i class="fas fa-paperclip"></i>
                    </button>
                    <div class="bv-attach-menu" id="bv-attach-menu">
                        <button class="bv-attach-row" data-bv-attach-type="document">
                            <div class="ico"><i class="far fa-file"></i></div>
                            <div class="body">
                                <div class="t">Documento</div>
                                <div class="s">PDF, Word, Excel, PPT</div>
                            </div>
                        </button>
                        <button class="bv-attach-row" data-bv-attach-type="image">
                            <div class="ico bv-attach-ico-blue"><i class="far fa-image"></i></div>
                            <div class="body">
                                <div class="t">Imagen</div>
                                <div class="s">JPG, PNG, WEBP, GIF</div>
                            </div>
                        </button>
                        <button class="bv-attach-row" data-bv-attach-type="audio">
                            <div class="ico bv-attach-ico-purple"><i class="fas fa-music"></i></div>
                            <div class="body">
                                <div class="t">Subir audio</div>
                                <div class="s">MP3, OGG, WAV, M4A</div>
                            </div>
                        </button>
                        <button class="bv-attach-row" data-bv-attach-type="record">
                            <div class="ico bv-attach-ico-purple"><i class="fas fa-microphone"></i></div>
                            <div class="body">
                                <div class="t">Grabar audio</div>
                                <div class="s">Mensaje de voz</div>
                            </div>
                        </button>
                        <button class="bv-attach-row" data-bv-attach-type="video">
                            <div class="ico bv-attach-ico-red"><i class="fas fa-video"></i></div>
                            <div class="body">
                                <div class="t">Video</div>
                                <div class="s">MP4, MOV</div>
                            </div>
                        </button>
                        <button class="bv-attach-row" data-bv-attach-type="store" data-bv-modal="store-picker">
                            <div class="ico bv-attach-ico-green"><i class="fas fa-store"></i></div>
                            <div class="body">
                                <div class="t">Tienda</div>
                                <div class="s">Compartir tienda de la empresa</div>
                            </div>
                        </button>
                        <button class="bv-attach-row" data-bv-attach-type="contact" data-bv-modal="attach-contact">
                            <div class="ico bv-attach-ico-blue"><i class="fas fa-address-card"></i></div>
                            <div class="body">
                                <div class="t">Contacto</div>
                                <div class="s">Enviar tarjeta vCard</div>
                            </div>
                        </button>
                        <button class="bv-attach-row" data-bv-attach-type="location" data-bv-modal="attach-location">
                            <div class="ico bv-attach-ico-amber"><i class="fas fa-location-dot"></i></div>
                            <div class="body">
                                <div class="t">Ubicación</div>
                                <div class="s">Compartir mapa</div>
                            </div>
                        </button>
                        <div class="bv-attach-limit">
                            <i class="fas fa-circle-info"></i>
                            Máximo 16 MB por archivo
                        </div>
                    </div>
                </div>
                @endif
                {{-- Voice recorder (shown when audio attach chosen and user clicks record) --}}
                <div class="bv-voice-recorder bv-hidden" id="bv-voice-recorder">
                    <button class="bv-voice-btn" id="bv-voice-record" data-bv-tip="Grabar">
                        <i class="fas fa-microphone"></i>
                    </button>
                    <span class="bv-voice-time" id="bv-voice-time">0:00</span>
                    <button class="bv-voice-btn bv-voice-btn-stop bv-hidden" id="bv-voice-stop" data-bv-tip="Detener">
                        <i class="fas fa-stop"></i>
                    </button>
                    <button class="bv-voice-btn bv-voice-btn-cancel bv-hidden" id="bv-voice-cancel" data-bv-tip="Cancelar">
                        <i class="fas fa-xmark"></i>
                    </button>
                </div>
                {{-- Upload progress bar --}}
                <div class="bv-upload-progress bv-hidden" id="bv-upload-progress">
                    <div class="bv-upload-bar" id="bv-upload-bar"></div>
                </div>
                @if(helpdesk_feature_enabled('composer_emoji'))
                <button class="btn-ico" id="bv-btn-emoji" type="button" data-bv-tip="Emoji" aria-label="Emoji">
                    <i class="far fa-face-smile"></i>
                </button>
                @endif
                @if(helpdesk_feature_enabled('composer_mention'))
                <button class="btn-ico" id="bv-btn-mention" type="button" data-bv-modal="mention" data-bv-tip="Mencionar agente" aria-label="Mencionar agente">
                    <i class="fas fa-at"></i>
                </button>
                @endif
                @if(helpdesk_feature_enabled('composer_canned'))
                <button class="btn-ico" data-bv-tip="Respuesta rápida" onclick="openCannedModal()">
                    <i class="fas fa-bolt"></i>
                </button>
                @endif
                @stack('hd-composer-toolbar-buttons')
                @if(helpdesk_feature_enabled('composer_record'))
                <button class="btn-ico" id="bv-btn-record" data-bv-tip="Grabar audio" data-bv-attach-type="record">
                    <i class="fas fa-microphone"></i>
                </button>
                @endif
                @if(helpdesk_feature_enabled('composer_ai'))
                <button class="btn-ico" data-bv-tip="Sugerencia IA">
                    <i class="fas fa-sparkles"></i>
                </button>
                @endif
                <div class="bv-send-group">
                    <button class="btn-send" type="button">
                        <i class="far fa-paper-plane"></i>Enviar
                        <kbd class="bv-kbd-send" id="bv-kbd-send">⌘↵</kbd>
                    </button>
                    <button class="btn-send-config" type="button" id="bv-send-config" data-bv-tip="Atajo de envío" aria-label="Configurar atajo de envío" aria-haspopup="menu" aria-expanded="false">
                        <i class="fas fa-chevron-up"></i>
                    </button>
                </div>
                <div class="bv-send-menu bv-hidden" id="bv-send-menu" role="menu">
                    <div class="bv-send-menu-head">Atajo para enviar</div>
                    <button class="bv-send-menu-opt" type="button" role="menuitemradio" data-bv-send-shortcut="ctrl-enter">
                        <i class="fas fa-check bv-send-menu-check"></i>
                        <div class="bv-send-menu-text">
                            <div class="bv-send-menu-title">⌘ / Ctrl + Enter <span class="bv-send-menu-default">(predeterminado)</span></div>
                            <div class="bv-send-menu-sub">Enter inserta salto de línea</div>
                        </div>
                    </button>
                    <button class="bv-send-menu-opt" type="button" role="menuitemradio" data-bv-send-shortcut="enter">
                        <i class="fas fa-check bv-send-menu-check"></i>
                        <div class="bv-send-menu-text">
                            <div class="bv-send-menu-title">Enter</div>
                            <div class="bv-send-menu-sub">Shift + Enter inserta salto de línea</div>
                        </div>
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif
</div>

{{-- ── Custom HD Modals (Respuesta rápida + Posponer) ──────────────── --}}
@if($convo)
<div class="hd-overlay" id="hdCannedOverlay">
    <div class="hd-modal w-md">
        <div class="modal-head">
            <div class="modal-icon"><i class="fas fa-bolt"></i></div>
            <div class="modal-title-wrap">
                <span class="modal-label">HELPDESK · PLANTILLAS</span>
                <span class="modal-title">Respuesta rápida</span>
            </div>
            <button class="modal-close" onclick="closeCannedModal()"><i class="fas fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <div class="search-field">
                <i class="fas fa-magnifying-glass sf-ic"></i>
                <input class="finput" id="hdCannedSearch" placeholder="Buscar plantilla… (escribe / para atajos)" autocomplete="off">
            </div>
            <div class="hd-canned-pills" id="hdCannedSeg">
                <span class="media-pill on" data-cat="" onclick="hdCannedFilter('')">Todas <span class="c" id="hdCannedCount">0</span></span>
            </div>
            <div class="hd-canned-list" id="hdCannedList">
                <div class="bv-list-state">Cargando…</div>
            </div>
            <div class="field">
                <label class="flabel">Vista previa <span class="hint">Editable antes de enviar</span></label>
                <textarea class="finput" id="hdCannedPreview" rows="3" placeholder="Selecciona una plantilla…"></textarea>
            </div>
        </div>
        <div class="modal-foot modal-foot--stack">
            <div class="foot-hint">
                <span class="kbd">↑↓</span> navegar &nbsp; <span class="kbd">↵</span> insertar
            </div>
            <button class="btn btn-primary w-100" onclick="hdInsertCanned()">Insertar plantilla</button>
            <button class="btn btn-secondary w-100" onclick="closeCannedModal()">Cancelar</button>
        </div>
    </div>
</div>

@stack('hd-thread-modals')

@once
@push('scripts')

<script>
(function() {
    var hdCsrf = document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').content : '';

    // Contexto para reemplazar placeholders en plantillas
    var hdCtx = {
        'contact.name':    '{{ addslashes($cust?->name ?? '') }}',
        'contact.email':   '{{ addslashes($cust?->email ?? '') }}',
        'contact.phone':   '{{ addslashes($cust?->phone ?? '') }}',
        'agent.name':      '{{ addslashes(auth()->user()->name ?? '') }}',
        'agent.email':     '{{ addslashes(auth()->user()->email ?? '') }}',
        'company.name':    '{{ addslashes(config("app.name")) }}',
        'conversation.id': '{{ $convo?->id ?? '' }}',
    };

    function hdReplace(text) {
        if (!text) { return text; }
        return text.replace(/\{\{([^}]+)\}\}/g, function(match, key) {
            var k = key.trim();
            return hdCtx.hasOwnProperty(k) ? hdCtx[k] : match;
        });
    }

    function hdEscape(str) {
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    document.getElementById('hdCannedOverlay').addEventListener('click', function(e) {
        if (e.target === this) closeCannedModal();
    });

    // ── CANNED REPLIES ─────────────────────────────────────
    var hdCannedAll = [], hdCannedFiltered = [], hdCannedActive = -1, hdCannedSelId = null, hdCannedTimer = null, hdCannedCat = '';

    window.openCannedModal = function() {
        document.getElementById('hdCannedOverlay').classList.add('open');
        var inp = document.getElementById('hdCannedSearch');
        inp.value = ''; inp.focus();
        if (!hdCannedAll.length) { hdCannedFetch(''); }
        else { hdCannedRender(hdCannedApplyFilters(hdCannedAll, hdCannedCat)); }
    };
    window.closeCannedModal = function() {
        document.getElementById('hdCannedOverlay').classList.remove('open');
    };
    window.hdCannedFilter = function(cat) {
        hdCannedCat = cat;
        document.querySelectorAll('#hdCannedSeg .media-pill').forEach(function(b) {
            b.classList.toggle('on', b.dataset.cat === cat);
        });
        hdCannedRender(hdCannedApplyFilters(hdCannedAll, cat));
    };

    function hdCannedFetch(q) {
        var url = '{{ route("manager.helpdesk.canned-replies.search") }}' + (q ? '?q=' + encodeURIComponent(q) : '');
        fetch(url, { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': hdCsrf } })
            .then(function(r){ return r.json(); })
            .then(function(data) {
                if (!q) { hdCannedAll = data; hdCannedBuildSeg(); }
                hdCannedRender(hdCannedApplyFilters(data, hdCannedCat));
            });
    }

    function hdCannedBuildSeg() {
        var cats = [];
        hdCannedAll.forEach(function(r) { if (r.category && cats.indexOf(r.category) === -1) cats.push(r.category); });
        var seg = document.getElementById('hdCannedSeg');
        var html = '<span class="media-pill ' + (!hdCannedCat ? 'on' : '') + '" data-cat="" onclick="hdCannedFilter(\'\')">'
            + 'Todas <span class="c">' + hdCannedAll.length + '</span></span>';
        cats.forEach(function(cat) {
            var cnt = hdCannedAll.filter(function(r){ return r.category === cat; }).length;
            html += '<span class="media-pill ' + (hdCannedCat === cat ? 'on' : '') + '" data-cat="' + hdEscape(cat)
                + '" onclick="hdCannedFilter(\'' + cat.replace(/'/g,"\\'") + '\')">'
                + hdEscape(cat) + ' <span class="c">' + cnt + '</span></span>';
        });
        seg.innerHTML = html;
    }

    function hdCannedApplyFilters(list, cat) {
        var q = document.getElementById('hdCannedSearch').value.toLowerCase();
        return list.filter(function(r) {
            var matchCat = !cat || r.category === cat;
            var matchQ   = !q || (r.name && r.name.toLowerCase().includes(q))
                               || (r.shortcut && r.shortcut.toLowerCase().includes(q))
                               || (r.body && r.body.toLowerCase().includes(q));
            return matchCat && matchQ;
        });
    }

    function hdCannedRender(list) {
        hdCannedFiltered = list;
        hdCannedActive   = list.length ? 0 : -1;
        hdCannedSelId    = list.length ? list[0].id : null;
        var el = document.getElementById('hdCannedList');
        if (!list.length) {
            el.innerHTML = '<div class="bv-list-state">Sin resultados</div>';
            document.getElementById('hdCannedPreview').value = '';
            return;
        }
        el.innerHTML = list.map(function(r, i) {
            return '<button class="list-item ' + (i === 0 ? 'on' : '') + '" data-idx="' + i + '" onclick="hdCannedSelect(' + i + ')">'
                + (r.shortcut ? '<span class="kbd">/' + hdEscape(r.shortcut) + '</span>' : '<span class="kbd"></span>')
                + '<div class="body"><span class="t">' + hdEscape(r.name) + '</span>'
                + '<span class="s">' + (r.category ? hdEscape(r.category) + ' · ' : '') + 'usada ' + (r.usage_count || 0) + ' veces</span>'
                + '</div></button>';
        }).join('');
        document.getElementById('hdCannedPreview').value = hdReplace(list[0].body || '');
    }

    window.hdCannedSelect = function(idx) {
        hdCannedActive = idx;
        hdCannedSelId  = hdCannedFiltered[idx] ? hdCannedFiltered[idx].id : null;
        document.querySelectorAll('#hdCannedList .list-item').forEach(function(el, i) {
            el.classList.toggle('on', i === idx);
        });
        document.getElementById('hdCannedPreview').value = hdCannedFiltered[idx] ? hdReplace(hdCannedFiltered[idx].body || '') : '';
    };

    window.hdInsertCanned = function() {
        var txt = document.getElementById('hdCannedPreview').value;
        if (!txt.trim()) { return; }
        var ta = document.querySelector('.bv-composer-input');
        if (ta) { ta.value = txt; ta.focus(); ta.dispatchEvent(new Event('input')); }
        if (hdCannedSelId) {
            fetch('/panel/helpdesk/canned-replies/' + hdCannedSelId + '/use', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': hdCsrf }
            }).catch(function(){});
        }
        closeCannedModal();
    };

    var hdCannedSearchInp = document.getElementById('hdCannedSearch');
    hdCannedSearchInp.addEventListener('input', function() {
        clearTimeout(hdCannedTimer);
        var q = this.value.trim();
        hdCannedTimer = setTimeout(function() {
            if (q) { hdCannedFetch(q); }
            else { hdCannedRender(hdCannedApplyFilters(hdCannedAll, hdCannedCat)); }
        }, 250);
    });
    hdCannedSearchInp.addEventListener('keydown', function(e) {
        if (e.key === 'ArrowDown') { e.preventDefault(); if (hdCannedActive < hdCannedFiltered.length - 1) hdCannedSelect(hdCannedActive + 1); }
        else if (e.key === 'ArrowUp') { e.preventDefault(); if (hdCannedActive > 0) hdCannedSelect(hdCannedActive - 1); }
        else if (e.key === 'Enter') { e.preventDefault(); hdInsertCanned(); }
        else if (e.key === 'Escape') { closeCannedModal(); }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key !== 'Escape') return;
        if (document.getElementById('hdCannedOverlay').classList.contains('open')) { closeCannedModal(); return; }
    });

    // ── CSAT ───────────────────────────────────────────────
    $(document).on('click', '#bv-btn-send-csat', function() {
        var url = $(this).data('csat-url');
        if (!url) { toastr.error('No hay conversación seleccionada'); return; }
        var $btn = $(this).prop('disabled', true);
        $.ajax({
            url: url,
            method: 'POST',
            dataType: 'json',
            headers: { 'X-CSRF-TOKEN': hdCsrf, 'Accept': 'application/json' },
        }).done(function(resp) {
        }).fail(function(xhr) {
            var msg = xhr?.responseJSON?.message || 'No se pudo enviar la encuesta';
            toastr.error(msg);
        }).always(function() {
            $btn.prop('disabled', false);
            $('#bv-more-menu').removeClass('open');
        });
    });

    // ── CREAR TICKET ────────────────────────────────────────
    $(document).on('click', '#bv-btn-create-ticket', function() {
        var url = $(this).data('ticket-url');
        if (!url) { toastr.error('No hay conversación seleccionada'); return; }
        var $btn = $(this).prop('disabled', true);
        $.ajax({
            url: url,
            method: 'POST',
            dataType: 'json',
            headers: { 'X-CSRF-TOKEN': hdCsrf, 'Accept': 'application/json' },
        }).done(function(resp) {
            if (resp.ticket_url) {
                window.open(resp.ticket_url, '_blank');
            }
        }).fail(function(xhr) {
            var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'No se pudo crear el ticket';
            toastr.error(msg);
        }).always(function() {
            $btn.prop('disabled', false);
            $('#bv-more-menu').removeClass('open');
        });
    });

    // ── Integración con el slash-menu de conversations.js ───────
    // conversations.js ya incluye su propio slash menu (#bv-slash-menu).
    // Aquí solo configuramos la URL de búsqueda y aplicamos
    // reemplazo de placeholders cuando se inserta una plantilla.
    window.bvCannedRepliesUrl = '{{ route("manager.helpdesk.canned-replies.search") }}';

    // Reemplazar marcadores de posición en el composer después de insertar una plantilla.
    // Usamos jQuery .on() porque conversations.js dispara $textarea.trigger('input'),
    // que no siempre propaga al addEventListener nativo.
    $(document).on('input', '.bv-composer-input', function() {
        var ta = this;
        var val = ta.value;
        if (!/\{\{/.test(val)) return;
        var replaced = hdReplace(val);
        if (replaced !== val) {
            var pos = ta.selectionStart;
            ta.value = replaced;
            ta.setSelectionRange(pos, pos);
            // Evento nativo para auto-resize (ya sin {{}} no vuelve a procesar)
            ta.dispatchEvent(new Event('input', { bubbles: true }));
        }
    });
})();
</script>

@stack('hd-thread-scripts')

@endpush
@endonce
@endif
