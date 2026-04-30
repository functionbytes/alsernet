{{-- Hilo de chat — Refined v4 --}}
<div class="bv-thread">
@if(empty($selectedConversationId))
    <div class="bv-thread-empty">
        <div class="bv-thread-empty-icon">
            <i class="far fa-comments"></i>
        </div>
        <div class="bv-thread-empty-title">Selecciona una conversación</div>
        <div class="bv-thread-empty-sub">Elige una conversación del panel izquierdo para ver el hilo y responder</div>
    </div>
@else
    @php
        $convo = $selectedConversation;
        $cust = $convo?->customer;
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
        $statusColor = $convo?->status?->color ?? 'success';
        $priority = $convo?->priority ?? 'normal';
        $priorityLabels = ['low' => 'Baja', 'normal' => 'Normal', 'high' => 'Alta', 'urgent' => 'Urgente'];
        $priorityColors = ['low' => 'muted', 'normal' => 'info', 'high' => 'warning', 'urgent' => 'danger'];
    @endphp

    {{-- Cabecera del hilo --}}
    <div class="bv-th-head">
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
                <span class="dot bv-dot-{{ $statusColor }}"></span>
                {{ $statusName }}
                <i class="fas fa-chevron-down bv-pill-chevron"></i>
            </button>
            <button class="bv-th-pill" data-bv-modal="priority">
                <span class="dot bv-dot-{{ $priorityColors[$priority] ?? 'muted' }}"></span>
                {{ $priorityLabels[$priority] ?? 'Normal' }}
                <i class="fas fa-chevron-down bv-pill-chevron"></i>
            </button>
            <span class="bv-th-sep"></span>
            <button class="bv-th-action" id="bv-th-search-btn" title="Buscar en la conversación">
                <i class="fas fa-magnifying-glass"></i>
            </button>
            <button class="bv-th-action" data-bv-modal="email" title="Email">
                <i class="far fa-envelope"></i>
            </button>
            <button class="bv-th-action" data-bv-modal="schedule" title="Agendar">
                <i class="far fa-calendar-plus"></i>
            </button>
            <button class="bv-th-action" data-bv-modal="snooze" title="Snooze">
                <i class="far fa-clock"></i>
            </button>
            <button class="bv-th-action" data-bv-modal="assign" title="Asignar">
                <i class="far fa-user-plus"></i>
            </button>
            <button class="bv-th-action" data-bv-modal="tags" title="Etiquetar">
                <i class="fas fa-tag"></i>
            </button>
            <button class="bv-th-action" data-bv-modal="close-conv" title="Cerrar">
                <i class="fas fa-check"></i>
            </button>
            {{-- Botón "más" con dropdown --}}
            <div class="bv-th-more-wrap">
                <button class="bv-th-action" id="bv-btn-more" title="Más">
                    <i class="fas fa-ellipsis-vertical"></i>
                </button>
                <div class="bv-more-menu" id="bv-more-menu">
                    <button data-bv-modal="merge"><i class="fas fa-code-merge"></i>Fusionar conversación</button>
                    <button data-bv-modal="move-to-team"><i class="fas fa-arrow-right-arrow-left"></i>Mover a equipo</button>
                    <button><i class="fas fa-forward"></i>Reenviar</button>
                    <div class="sep"></div>
                    <button data-bv-modal="preview-conv"><i class="far fa-clock-rotate-left"></i>Conversaciones anteriores</button>
                    <button data-bv-modal="note"><i class="far fa-note-sticky"></i>Añadir nota</button>
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
        <button class="bv-th-search-nav" id="bv-th-search-prev" title="Anterior" disabled><i class="fas fa-chevron-up"></i></button>
        <button class="bv-th-search-nav" id="bv-th-search-next" title="Siguiente" disabled><i class="fas fa-chevron-down"></i></button>
        <button class="bv-th-search-close" id="bv-th-search-close" title="Cerrar"><i class="fas fa-xmark"></i></button>
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
                         data-bv-react-url="{{ route('manager.helpdesk.conversation-items.react', $item) }}">
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
                        @if($item->body)
                            {!! nl2br(e($item->body)) !!}
                        @endif
                        @if($item->hasAttachments())
                            @php
                                $metaAttachments = collect($item->metadata['attachments'] ?? []);
                            @endphp
                            <div class="bv-attachment-gallery">
                                @foreach($item->attachment_urls as $idx => $url)
                                    @php
                                        $meta = $metaAttachments->get($idx);
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
                                           data-bv-preview-type="image">
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
                                            // Waveform pseudo-aleatorio determinista basado en la URL
                                            $seed = crc32($url);
                                            mt_srand($seed);
                                            $bars = [];
                                            for ($b = 0; $b < 32; $b++) {
                                                $bars[] = mt_rand(25, 100);
                                            }
                                            mt_srand();
                                        @endphp
                                        <div class="bv-audio-msg" data-bv-audio-src="{{ $url }}">
                                            <button type="button" class="bv-audio-play" aria-label="Reproducir">
                                                <i class="fas fa-play"></i>
                                            </button>
                                            <div class="bv-audio-wave" role="slider" tabindex="0" aria-label="Progreso del audio">
                                                @foreach($bars as $h)
                                                    <span class="bv-audio-bar" style="--bv-audio-bar-h:{{ $h }}%"></span>
                                                @endforeach
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
                        @if($item->body && ! $isInternal)
                            <button class="bv-bubble-translate"
                                    data-bv-translate-text="{{ $item->body }}">
                                <i class="fas fa-language"></i> Traducir
                            </button>
                            <button class="bv-bubble-reply"
                                    data-bv-reply-id="{{ $item->id }}"
                                    data-bv-reply-author="{{ $authorLabel }}"
                                    data-bv-reply-body="{{ \Illuminate\Support\Str::limit($item->body, 80) }}">
                                <i class="fas fa-reply"></i> Responder
                            </button>
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
                    <button class="btn-ico" id="bv-btn-attach" title="Adjuntar">
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
                    <button class="bv-voice-btn" id="bv-voice-record" title="Grabar">
                        <i class="fas fa-microphone"></i>
                    </button>
                    <span class="bv-voice-time" id="bv-voice-time">0:00</span>
                    <button class="bv-voice-btn bv-voice-btn-stop bv-hidden" id="bv-voice-stop" title="Detener">
                        <i class="fas fa-stop"></i>
                    </button>
                    <button class="bv-voice-btn bv-voice-btn-cancel bv-hidden" id="bv-voice-cancel" title="Cancelar">
                        <i class="fas fa-xmark"></i>
                    </button>
                </div>
                {{-- Upload progress bar --}}
                <div class="bv-upload-progress bv-hidden" id="bv-upload-progress">
                    <div class="bv-upload-bar" id="bv-upload-bar"></div>
                </div>
                <button class="btn-ico" title="Emoji">
                    <i class="far fa-face-smile"></i>
                </button>
                <button class="btn-ico" title="Mención">
                    <i class="fas fa-at"></i>
                </button>
                <button class="btn-ico" title="Respuesta rápida">
                    <i class="fas fa-bolt"></i>
                </button>
                <button class="btn-ico" id="bv-btn-record" title="Grabar audio" data-bv-attach-type="record">
                    <i class="fas fa-microphone"></i>
                </button>
                <button class="btn-ico" title="Sugerencia IA">
                    <i class="fas fa-sparkles"></i>
                </button>
                <button class="btn-send">
                    <i class="far fa-paper-plane"></i>Enviar
                    <kbd class="bv-kbd-send">⌘↵</kbd>
                </button>
            </div>
        </div>
    </div>
@endif
</div>
