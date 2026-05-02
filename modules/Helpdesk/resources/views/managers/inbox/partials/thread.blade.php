{{-- Hilo de chat — Refined v4 --}}
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
        $priorityColors = ['low' => 'muted', 'normal' => 'info', 'high' => 'warning', 'urgent' => 'danger'];
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
            <button class="bv-th-pill" data-bv-modal="status">
                <span class="dot" style="background:{{ $statusColor }}"></span>
                {{ $statusName }}
                <i class="fas fa-chevron-down bv-pill-chevron"></i>
            </button>
            <button class="bv-th-pill" data-bv-modal="priority">
                <span class="dot bv-dot-{{ $priorityColors[$priority] ?? 'muted' }}"></span>
                {{ $priorityLabels[$priority] ?? 'Normal' }}
                <i class="fas fa-chevron-down bv-pill-chevron"></i>
            </button>
            <span class="bv-th-sep"></span>
            <button class="bv-th-action" id="bv-th-search-btn" data-bv-tip="Buscar en la conversación">
                <i class="fas fa-magnifying-glass"></i>
            </button>
            <button class="bv-th-action" data-bv-modal="email" data-bv-tip="Email">
                <i class="far fa-envelope"></i>
            </button>
            <button class="bv-th-action" data-bv-modal="schedule" data-bv-tip="Agendar">
                <i class="far fa-calendar-plus"></i>
            </button>
            <button class="bv-th-action" data-bv-tip="Posponer" onclick="openSnoozeModal()">
                <i class="far fa-clock"></i>
            </button>
            <button class="bv-th-action" data-bv-modal="assign" data-bv-tip="Asignar">
                <i class="far fa-user-plus"></i>
            </button>
            <button class="bv-th-action" data-bv-modal="tags" data-bv-tip="Etiquetar">
                <i class="fas fa-tag"></i>
            </button>
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
                    <button data-bv-modal="merge"><i class="fas fa-code-merge"></i>Fusionar conversación</button>
                    <button data-bv-modal="move-to-team"><i class="fas fa-arrow-right-arrow-left"></i>Mover a equipo</button>
                    <button><i class="fas fa-forward"></i>Reenviar</button>
                    <div class="sep"></div>
                    <button data-bv-modal="preview-conv"><i class="far fa-clock-rotate-left"></i>Conversaciones anteriores</button>
                    <button data-bv-modal="note"><i class="far fa-note-sticky"></i>Añadir nota</button>
                    <button id="bv-btn-send-csat"
                            data-csat-url="{{ $convo ? route('manager.helpdesk.conversations.send-csat', $convo) : '' }}">
                        <i class="far fa-star"></i>Enviar encuesta CSAT
                    </button>
                    @if(\Illuminate\Support\Facades\Route::has('manager.helpdesk.conversations.ticket'))
                    <button id="bv-btn-create-ticket"
                            data-ticket-url="{{ $convo ? route('manager.helpdesk.conversations.ticket', $convo) : '' }}">
                        <i class="fas fa-ticket-simple"></i>Crear ticket
                    </button>
                    @endif
                    <div class="sep"></div>
                    <button class="danger"><i class="fas fa-ban"></i>Spam</button>
                    <button class="danger"><i class="fas fa-user-slash"></i>Bloquear contacto</button>
                    <button class="danger"><i class="far fa-trash-can"></i>Eliminar</button>
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

            @forelse($items as $item)
                @php
                    $itemDay = optional($item->created_at)->format('Y-m-d');
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
                                        $attachType = $meta['type'] ?? (in_array($ext, ['jpg','jpeg','png','gif','webp']) ? 'image' : (in_array($ext, ['mp4','mov','webm']) ? 'video' : (in_array($ext, ['mp3','ogg','wav','webm','oga']) ? 'audio' : 'document')));
                                        $fileName = $meta['name'] ?? basename(parse_url($url, PHP_URL_PATH));
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
                                            <img src="{{ $url }}" alt="{{ $fileName }}" loading="lazy" width="200">
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
                <div class="bv-hsm-list">
                    <div class="bv-panel-search">
                        <i class="fas fa-magnifying-glass"></i>
                        <input type="text" placeholder="Buscar plantilla…">
                    </div>
                    <div class="bv-hsm-row on" data-tpl="bienvenida">
                        <div class="nm">Bienvenida</div>
                        <div class="meta"><span class="bv-hsm-badge-approved">APPROVED</span></div>
                    </div>
                    <div class="bv-hsm-row" data-tpl="seguimiento">
                        <div class="nm">Seguimiento de pedido</div>
                        <div class="meta"><span class="bv-hsm-badge-approved">APPROVED</span></div>
                    </div>
                    <div class="bv-hsm-row" data-tpl="recordatorio">
                        <div class="nm">Recordatorio cita</div>
                        <div class="meta"><span class="bv-hsm-badge-approved">APPROVED</span></div>
                    </div>
                    <div class="bv-hsm-row" data-tpl="promo">
                        <div class="nm">Promoción especial</div>
                        <div class="meta"><span class="bv-hsm-badge-pending">PENDING</span></div>
                    </div>
                </div>
                <div class="bv-hsm-detail">
                    <div class="bv-hsm-preview-label">Vista previa</div>
                    <div class="bv-hsm-chat">
                        <div class="bv-hsm-chat-bubble">
                            Hola <span class="bv-hsm-var-ph">{{1}}</span>, te contactamos desde <span class="bv-hsm-var-ph">{{2}}</span>. ¿En qué podemos ayudarte?
                            <div class="bv-hsm-chat-time">09:42 ✓✓</div>
                        </div>
                    </div>
                    <div class="bv-hsm-vars">
                        <div class="bv-hsm-vars-title">Variables</div>
                        <div class="bv-hsm-var-row">
                            <span class="bv-hsm-var-lbl">{{1}}</span>
                            <input type="text" placeholder="Nombre del cliente" value="Carmen" class="bv-hsm-var-input">
                        </div>
                        <div class="bv-hsm-var-row">
                            <span class="bv-hsm-var-lbl">{{2}}</span>
                            <input type="text" placeholder="Nombre empresa" value="Functionbytes" class="bv-hsm-var-input">
                        </div>
                    </div>
                    <div class="bv-hsm-foot">
                        <button class="bv-panel-btn bv-panel-btn-cancel" id="bv-hsm-close-2"><i class="fas fa-xmark"></i> Cancelar</button>
                        <button class="bv-panel-btn bv-panel-btn-confirm"><i class="fas fa-check"></i> Insertar plantilla</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Panel Traducción (se muestra/oculta con el tab) --}}
        <div class="bv-translate-panel" id="bv-translate-panel">
            <div class="bv-panel-head">
                <i class="fas fa-language"></i>
                <span>Traducción de conversación</span>
                <button id="bv-translate-close"><i class="fas fa-xmark"></i></button>
            </div>
            <div class="bv-tp-body">
                <div>
                    <div class="bv-tp-lbl">Modo de traducción</div>
                    <div class="bv-tp-mode-list">
                        <div class="bv-tp-mode on" data-mode="incoming">
                            <i class="fas fa-arrow-down"></i>
                            <div>
                                <div class="t">Entrantes</div>
                                <div class="s">Traducir mensajes del cliente</div>
                            </div>
                        </div>
                        <div class="bv-tp-mode" data-mode="outgoing">
                            <i class="fas fa-arrow-up"></i>
                            <div>
                                <div class="t">Salientes</div>
                                <div class="s">Traducir mis mensajes antes de enviar</div>
                            </div>
                        </div>
                        <div class="bv-tp-mode" data-mode="both">
                            <i class="fas fa-arrows-up-down"></i>
                            <div>
                                <div class="t">Ambos</div>
                                <div class="s">Traducción bidireccional</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bv-tp-lang-row">
                    <div class="bv-tp-lang-col">
                        <div class="bv-tp-lbl">Idioma origen</div>
                        <select class="bv-tp-sel" id="bv-tp-from">
                            <option value="auto">Detectar automáticamente</option>
                            <option value="es">Español</option>
                            <option value="en">Inglés</option>
                            <option value="fr">Francés</option>
                            <option value="pt">Portugués</option>
                        </select>
                    </div>
                    <i class="fas fa-arrow-right bv-tp-lang-arrow"></i>
                    <div class="bv-tp-lang-col">
                        <div class="bv-tp-lbl">Idioma destino</div>
                        <select class="bv-tp-sel" id="bv-tp-to">
                            <option value="es" selected>Español</option>
                            <option value="en">Inglés</option>
                            <option value="fr">Francés</option>
                            <option value="pt">Portugués</option>
                            <option value="de">Alemán</option>
                            <option value="it">Italiano</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="bv-tp-foot">
                <button class="bv-panel-btn bv-panel-btn-cancel" id="bv-translate-close-2"><i class="fas fa-power-off"></i> Desactivar</button>
                <button class="bv-panel-btn bv-panel-btn-confirm"><i class="fas fa-language"></i> Activar traducción</button>
            </div>
        </div>

        {{-- Tabs del composer --}}
        <div class="bv-composer-tabs">
            <button class="bv-composer-tab on" data-bv-tab="reply">
                <i class="far fa-reply bv-tab-icon"></i>Respuesta
            </button>
            <button class="bv-composer-tab note" data-bv-tab="note">
                <i class="fas fa-lock bv-tab-icon"></i>Nota interna
            </button>
            <button class="bv-composer-tab" data-bv-tab="hsm">
                <i class="fab fa-whatsapp bv-tab-icon"></i>Plantillas HSM
            </button>
            <button class="bv-composer-tab" data-bv-tab="translate">
                <i class="fas fa-language bv-tab-icon"></i>Traducir
            </button>
        </div>

        {{-- Área de texto --}}
        <div class="bv-composer-box" id="bv-composer-box">
            <textarea class="bv-composer-input" placeholder="Escribe tu respuesta… (/ para respuestas rápidas, @ para mencionar)" rows="2"></textarea>
            <div class="bv-composer-toolbar">
                {{-- Adjuntar con menú --}}
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
                <button class="btn-ico" id="bv-btn-emoji" type="button" data-bv-tip="Emoji" aria-label="Emoji">
                    <i class="far fa-face-smile"></i>
                </button>
                <button class="btn-ico" id="bv-btn-mention" type="button" data-bv-modal="mention" data-bv-tip="Mencionar agente" aria-label="Mencionar agente">
                    <i class="fas fa-at"></i>
                </button>
                <button class="btn-ico" data-bv-tip="Respuesta rápida" onclick="openCannedModal()">
                    <i class="fas fa-bolt"></i>
                </button>
                <button class="btn-ico" id="bv-btn-record" data-bv-tip="Grabar audio" data-bv-attach-type="record">
                    <i class="fas fa-microphone"></i>
                </button>
                <button class="btn-ico" data-bv-tip="Sugerencia IA">
                    <i class="fas fa-sparkles"></i>
                </button>
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
            <div class="modal-icon"><i class="fa-solid fa-bolt"></i></div>
            <div class="modal-title-wrap">
                <span class="modal-label">HELPDESK · PLANTILLAS</span>
                <span class="modal-title">Respuesta rápida</span>
            </div>
            <button class="modal-close" onclick="closeCannedModal()"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <div class="search-field">
                <i class="fa-solid fa-magnifying-glass sf-ic"></i>
                <input class="finput" id="hdCannedSearch" placeholder="Buscar plantilla… (escribe / para atajos)" autocomplete="off">
            </div>
            <div class="seg" id="hdCannedSeg">
                <button class="active" data-cat="" onclick="hdCannedFilter('')">Todas <span class="kbd" id="hdCannedCount">0</span></button>
            </div>
            <div class="flat-col" id="hdCannedList">
                <div style="text-align:center;padding:16px;color:#9aa0ab;font-size:13px">Cargando…</div>
            </div>
            <div class="field">
                <label class="flabel">Vista previa <span class="hint">Editable antes de enviar</span></label>
                <textarea class="finput" id="hdCannedPreview" rows="2" placeholder="Selecciona una plantilla…"></textarea>
            </div>
        </div>
        <div class="modal-foot">
            <div class="hint-txt">
                <span class="kbd">↑↓</span> navegar &nbsp;
                <span class="kbd">↵</span> insertar
            </div>
            <div class="ml"></div>
            <button class="btn btn-ghost btn-sm" onclick="closeCannedModal()">Cancelar</button>
            <button class="btn btn-primary btn-sm" onclick="hdInsertCanned()">
                <i class="fa-solid fa-arrow-right-to-bracket"></i> Insertar
            </button>
        </div>
    </div>
</div>

<div class="hd-overlay" id="hdSnoozeOverlay">
    <div class="hd-modal w-sm">
        <div class="modal-head">
            <div class="modal-icon"><i class="fa-regular fa-clock"></i></div>
            <div class="modal-title-wrap">
                <span class="modal-label">HELPDESK · BANDEJA</span>
                <span class="modal-title">Posponer conversación</span>
            </div>
            <button class="modal-close" onclick="closeSnoozeModal()"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <div style="font-size:11.5px;color:#9aa0ab">Vuelve a la bandeja cuando…</div>
            <div class="opt-list" id="hdSnoozeOpts">
                <div class="opt-row on" data-value="1h"><i class="fa-solid fa-clock ico"></i> En 1 hora<span class="c" id="snz-1h">--:--</span></div>
                <div class="opt-row" data-value="3h"><i class="fa-solid fa-clock ico"></i> En 3 horas<span class="c" id="snz-3h">--:--</span></div>
                <div class="opt-row" data-value="tomorrow"><i class="fa-solid fa-sun ico"></i> Mañana por la mañana<span class="c">09:00</span></div>
                <div class="opt-row" data-value="next-monday"><i class="fa-solid fa-calendar-week ico"></i> Próximo lunes<span class="c" id="snz-monday">--</span></div>
                <div class="opt-row" data-value="custom"><i class="fa-regular fa-calendar-plus ico"></i> Elegir fecha…</div>
            </div>
            <div class="hr-divide"></div>
            <div class="date-row" id="hdSnoozeDateRow">
                <i class="fa-regular fa-calendar ico" style="color:#9aa0ab;font-size:13px;flex-shrink:0"></i>
                <input type="datetime-local" id="hdSnoozeCustomDate">
            </div>
            <label class="check">
                <input type="checkbox" id="hdSnoozeReopen"> Reabrir si el cliente responde antes
            </label>
        </div>
        <div class="modal-foot">
            <button class="btn btn-ghost btn-sm" onclick="closeSnoozeModal()">Cancelar</button>
            <div class="ml"></div>
            <button class="btn btn-primary btn-sm" onclick="hdSnoozeSubmit()">
                <i class="fa-regular fa-clock"></i> Posponer
            </button>
        </div>
    </div>
</div>

@push('css')
<style>
.hd-overlay{display:none;position:fixed;inset:0;z-index:1060;background:rgba(0,0,0,.45);backdrop-filter:blur(2px);align-items:center;justify-content:center}
.hd-overlay.open{display:flex}
.hd-modal{background:#fff;border-radius:12px;box-shadow:0 8px 32px rgba(0,0,0,.18);display:flex;flex-direction:column;max-height:82vh;overflow:hidden;animation:hd-in .18s ease}
.hd-modal.w-md{width:520px;max-width:95vw}
.hd-modal.w-sm{width:340px;max-width:95vw}
@keyframes hd-in{from{opacity:0;transform:translateY(10px) scale(.97)}to{opacity:1;transform:none}}
.hd-modal .modal-head{display:flex;align-items:center;gap:10px;padding:14px 16px 12px;border-bottom:1px solid #f0f0f0;flex-shrink:0}
.hd-modal .modal-icon{width:32px;height:32px;background:#fce7e7;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:14px;color:#b10100}
.hd-modal .modal-title-wrap{flex:1;display:flex;flex-direction:column;gap:1px}
.hd-modal .modal-label{font-size:10px;font-weight:600;letter-spacing:.06em;color:#9aa0ab;text-transform:uppercase}
.hd-modal .modal-title{font-size:14px;font-weight:600;color:#1a1a2e}
.hd-modal .modal-close{background:none;border:none;width:28px;height:28px;border-radius:6px;display:flex;align-items:center;justify-content:center;color:#9aa0ab;cursor:pointer;font-size:14px;transition:background .15s,color .15s;padding:0}
.hd-modal .modal-close:hover{background:#f3f4f6;color:#374151}
.hd-modal .modal-body{flex:1;overflow-y:auto;padding:14px 16px;display:flex;flex-direction:column;gap:10px;min-height:0}
.hd-modal .modal-foot{display:flex;align-items:center;gap:8px;padding:10px 16px;border-top:1px solid #f0f0f0;flex-shrink:0}
.hd-modal .ml{margin-left:auto}
.hd-modal .search-field{display:flex;align-items:center;gap:8px;background:#f8f9fa;border:1px solid #e5e7eb;border-radius:8px;padding:7px 12px;flex-shrink:0}
.hd-modal .sf-ic{color:#9aa0ab;font-size:13px;flex-shrink:0}
.hd-modal .finput{border:none;background:none;outline:none;width:100%;font-size:13px;color:#374151}
.hd-modal .finput::placeholder{color:#b0b7c3}
.hd-modal textarea.finput{resize:none;border:1px solid #e5e7eb;border-radius:8px;background:#f8f9fa;padding:8px 10px}
.hd-modal .seg{display:flex;gap:4px;overflow-x:auto;scrollbar-width:none;flex-shrink:0}
.hd-modal .seg::-webkit-scrollbar{display:none}
.hd-modal .seg button{background:none;border:1px solid #e5e7eb;border-radius:6px;padding:4px 12px;font-size:12px;color:#6b7280;cursor:pointer;white-space:nowrap;display:flex;align-items:center;gap:5px;transition:all .15s}
.hd-modal .seg button.active{background:#b10100;border-color:#b10100;color:#fff}
.hd-modal .seg button:hover:not(.active){background:#f3f4f6}
.hd-modal .flat-col{display:flex;flex-direction:column;gap:2px;overflow-y:auto;flex:1;min-height:80px;max-height:200px}
.hd-modal .row-pick{display:flex;align-items:flex-start;gap:10px;padding:8px 10px;border-radius:8px;cursor:pointer;transition:background .12s;flex-shrink:0}
.hd-modal .row-pick:hover{background:#f3f4f6}
.hd-modal .row-pick.on{background:#fce7e7}
.hd-modal .row-pick .rp-mono{flex-shrink:0;margin-top:2px;font-size:10px;background:#f3f4f6;padding:2px 6px;border-radius:3px;color:#9aa0ab;font-family:monospace}
.hd-modal .row-pick.on .rp-mono{background:#f9d0d0;color:#b10100}
.hd-modal .row-pick .body{display:flex;flex-direction:column;gap:2px;min-width:0}
.hd-modal .row-pick .t{font-size:13px;font-weight:500;color:#1a1a2e;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;display:block}
.hd-modal .row-pick .s{font-size:11px;color:#9aa0ab}
.hd-modal .field{display:flex;flex-direction:column;gap:4px;flex-shrink:0}
.hd-modal .flabel{font-size:12px;font-weight:500;color:#6b7280;display:flex;align-items:center;gap:6px}
.hd-modal .hint{font-size:11px;color:#b0b7c3;font-weight:400}
.hd-modal .hint-txt{font-size:11px;color:#9aa0ab;display:flex;align-items:center;gap:4px}
.hd-modal .kbd{display:inline-flex;align-items:center;background:#e5e7eb;border-radius:3px;padding:1px 5px;font-size:10px;color:#6b7280;font-family:monospace}
.hd-modal .opt-list{display:flex;flex-direction:column;gap:2px}
.hd-modal .opt-row{display:flex;align-items:center;gap:10px;padding:9px 12px;border-radius:8px;cursor:pointer;font-size:13px;color:#374151;transition:background .12s}
.hd-modal .opt-row:hover,.hd-modal .opt-row.on{background:#fce7e7;color:#b10100}
.hd-modal .opt-row .ico{color:#9aa0ab;font-size:13px}
.hd-modal .opt-row:hover .ico,.hd-modal .opt-row.on .ico{color:#b10100}
.hd-modal .opt-row .c{margin-left:auto;font-size:12px;color:#9aa0ab;font-weight:500}
.hd-modal .opt-row.on .c{color:#b10100}
.hd-modal .hr-divide{border:none;border-top:1px solid #f0f0f0;margin:2px 0;flex-shrink:0}
.hd-modal .check{display:flex;align-items:center;gap:8px;font-size:13px;color:#374151;cursor:pointer;flex-shrink:0}
.hd-modal .date-row{padding:4px 0;display:none;align-items:center;gap:8px;flex-shrink:0}
.hd-modal .date-row.visible{display:flex}
.hd-modal .date-row input[type="datetime-local"]{border:1px solid #e5e7eb;border-radius:6px;padding:5px 8px;font-size:13px;color:#374151;flex:1;outline:none}
.hd-modal .btn{display:inline-flex;align-items:center;gap:6px;border-radius:7px;font-size:13px;font-weight:500;cursor:pointer;transition:all .15s;border:none;padding:6px 14px;line-height:1.4;text-decoration:none}
.hd-modal .btn.btn-sm{padding:5px 12px;font-size:12px}
.hd-modal .btn.btn-ghost{background:none;color:#6b7280;border:1px solid #e5e7eb}
.hd-modal .btn.btn-ghost:hover{background:#f3f4f6}
.hd-modal .btn.btn-primary{background:#b10100;color:#fff}
.hd-modal .btn.btn-primary:hover{background:#8f0000}
</style>
@endpush

@push('scripts')

<script>
(function() {
    var hdSnoozeRoute = '{{ route("manager.helpdesk.conversations.snooze", $convo) }}';
    var hdCsrf        = document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').content : '';

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
    document.getElementById('hdSnoozeOverlay').addEventListener('click', function(e) {
        if (e.target === this) closeSnoozeModal();
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
        document.querySelectorAll('#hdCannedSeg button').forEach(function(b) {
            b.classList.toggle('active', b.dataset.cat === cat);
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
        var html = '<button class="' + (!hdCannedCat ? 'active' : '') + '" data-cat="" onclick="hdCannedFilter(\'\')">'
            + 'Todas <span class="kbd">' + hdCannedAll.length + '</span></button>';
        cats.forEach(function(cat) {
            var cnt = hdCannedAll.filter(function(r){ return r.category === cat; }).length;
            html += '<button class="' + (hdCannedCat === cat ? 'active' : '') + '" data-cat="' + hdEscape(cat)
                + '" onclick="hdCannedFilter(\'' + cat.replace(/'/g,"\\'") + '\')">'
                + hdEscape(cat) + ' <span class="kbd">' + cnt + '</span></button>';
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
            el.innerHTML = '<div style="text-align:center;padding:16px;color:#9aa0ab;font-size:13px">Sin resultados</div>';
            document.getElementById('hdCannedPreview').value = '';
            return;
        }
        el.innerHTML = list.map(function(r, i) {
            return '<div class="row-pick ' + (i === 0 ? 'on' : '') + '" data-idx="' + i + '" onclick="hdCannedSelect(' + i + ')">'
                + (r.shortcut ? '<span class="rp-mono">/' + hdEscape(r.shortcut) + '</span>' : '')
                + '<div class="body"><span class="t">' + hdEscape(r.name) + '</span>'
                + '<span class="s">' + (r.category ? hdEscape(r.category) + ' · ' : '') + 'usada ' + (r.usage_count || 0) + ' veces</span>'
                + '</div></div>';
        }).join('');
        document.getElementById('hdCannedPreview').value = hdReplace(list[0].body || '');
    }

    window.hdCannedSelect = function(idx) {
        hdCannedActive = idx;
        hdCannedSelId  = hdCannedFiltered[idx] ? hdCannedFiltered[idx].id : null;
        document.querySelectorAll('#hdCannedList .row-pick').forEach(function(el, i) {
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

    // ── SNOOZE ─────────────────────────────────────────────
    var hdSnoozeSelected = '1h';

    window.openSnoozeModal = function() {
        var now = new Date();
        document.getElementById('snz-1h').textContent  = new Date(now.getTime() + 3600000).toLocaleTimeString('es', {hour:'2-digit',minute:'2-digit'});
        document.getElementById('snz-3h').textContent  = new Date(now.getTime() + 10800000).toLocaleTimeString('es', {hour:'2-digit',minute:'2-digit'});
        var nextMon = new Date(now);
        nextMon.setDate(now.getDate() + (now.getDay() === 0 ? 1 : 8 - now.getDay()));
        document.getElementById('snz-monday').textContent = nextMon.toLocaleDateString('es', {day:'numeric',month:'short'});
        hdSnoozeSelected = '1h';
        document.querySelectorAll('#hdSnoozeOpts .opt-row').forEach(function(r) {
            r.classList.toggle('on', r.dataset.value === '1h');
        });
        document.getElementById('hdSnoozeDateRow').classList.remove('visible');
        document.getElementById('hdSnoozeOverlay').classList.add('open');
    };
    window.closeSnoozeModal = function() {
        document.getElementById('hdSnoozeOverlay').classList.remove('open');
    };

    document.querySelectorAll('#hdSnoozeOpts .opt-row').forEach(function(row) {
        row.addEventListener('click', function() {
            hdSnoozeSelected = this.dataset.value;
            document.querySelectorAll('#hdSnoozeOpts .opt-row').forEach(function(r){ r.classList.remove('on'); });
            this.classList.add('on');
            document.getElementById('hdSnoozeDateRow').classList.toggle('visible', hdSnoozeSelected === 'custom');
        });
    });

    window.hdSnoozeSubmit = function() {
        var now = new Date();
        var until = null;
        if (hdSnoozeSelected === '1h')   until = new Date(now.getTime() + 3600000);
        else if (hdSnoozeSelected === '3h') until = new Date(now.getTime() + 10800000);
        else if (hdSnoozeSelected === 'tomorrow') { until = new Date(now); until.setDate(until.getDate() + 1); until.setHours(9,0,0,0); }
        else if (hdSnoozeSelected === 'next-monday') { until = new Date(now); until.setDate(until.getDate() + (now.getDay() === 0 ? 1 : 8 - now.getDay())); until.setHours(9,0,0,0); }
        else if (hdSnoozeSelected === 'custom') { var v = document.getElementById('hdSnoozeCustomDate').value; until = v ? new Date(v) : null; }
        if (!until) { if (typeof toastr !== 'undefined') toastr.warning('Selecciona una fecha válida'); return; }
        fetch(hdSnoozeRoute, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': hdCsrf },
            body: JSON.stringify({ until: until.toISOString() })
        })
        .then(function(r){ return r.json(); })
        .then(function(data) {
            if (data.success) { closeSnoozeModal(); }
            else { if (typeof toastr !== 'undefined') toastr.error('Error al posponer la conversación'); }
        })
        .catch(function() { if (typeof toastr !== 'undefined') toastr.error('Error al conectar con el servidor'); });
    };

    document.addEventListener('keydown', function(e) {
        if (e.key !== 'Escape') return;
        if (document.getElementById('hdCannedOverlay').classList.contains('open')) { closeCannedModal(); return; }
        if (document.getElementById('hdSnoozeOverlay').classList.contains('open')) { closeSnoozeModal(); return; }
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
@endpush
@endif
