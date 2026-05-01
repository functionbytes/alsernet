{{-- Panel derecho — Refined v4 / right-panel-v3 --}}
<aside class="bv-right" data-customer-id="{{ $selectedConversation?->customer_id ?? '' }}">
@if(empty($selectedConversationId))
    <div class="bv-right-empty">
        <div class="bv-right-empty-icon">
            <i class="far fa-id-card"></i>
        </div>
        <div class="bv-right-empty-title">Sin contacto</div>
        <div class="bv-right-empty-sub">La información del contacto aparecerá aquí</div>
    </div>
@else
    @php
        $rpCust   = $selectedConversation?->customer;
        $rpConvo  = $selectedConversation;
        $rpName   = $rpCust?->name ?? 'Sin nombre';
        $rpInitials = mb_strtoupper(collect(preg_split('/\s+/', trim($rpName)))->take(2)->map(fn($w) => mb_substr($w,0,1))->implode(''));
        $rpSince  = $rpCust?->created_at?->translatedFormat('Y') ?? '—';
        $rpTotal  = (int) ($rpCust?->total_conversations ?? 0);

        // Priority map (same as thread.blade.php)
        $priorityLabels = ['low' => 'Baja', 'normal' => 'Normal', 'high' => 'Alta', 'urgent' => 'Urgente'];
        $priorityColors = ['low' => 'muted', 'normal' => 'info', 'high' => 'warning', 'urgent' => 'danger'];
        $rpPriority = $rpConvo?->priority ?? 'normal';

        // Status
        $rpStatusName  = $rpConvo?->status?->name  ?? 'Abierta';
        $rpStatusColor = $rpConvo?->status?->color ?? '#6c757d';

        // Tickets (HelpdeskTickets module)
        $rpTickets = collect();
        if ($rpCust && class_exists(\Modules\HelpdeskTickets\Models\Ticket::class)) {
            $rpTickets = \Modules\HelpdeskTickets\Models\Ticket::where('customer_id', $rpCust->id)
                ->with('status')
                ->latest()
                ->limit(5)
                ->get();
        }

        // Activity events
        $rpEvents = $rpConvo ? $rpConvo->events()->latest()->limit(20)->get() : collect();

        // Pedidos del cliente (Ecommerce module)
        $rpOrders = collect();
        if ($rpCust && class_exists(\Modules\Ecommerce\Models\Order::class)) {
            try {
                $rpOrders = \Modules\Ecommerce\Models\Order::query()
                    ->where('customer_id', $rpCust->id)
                    ->latest('created_at')
                    ->limit(20)
                    ->get();
            } catch (\Throwable $e) {
                $rpOrders = collect();
            }
        }

        // Archivos: extraer attachments de los items de TODAS las conversaciones del cliente
        $rpFiles = collect();
        if ($rpCust) {
            $convIds = \Modules\Helpdesk\Models\Conversation::where('customer_id', $rpCust->id)->pluck('id');
            $items = \Modules\Helpdesk\Models\ConversationItem::query()
                ->whereIn('conversation_id', $convIds)
                ->whereNotNull('attachment_urls')
                ->latest('created_at')
                ->limit(60)
                ->get();
            foreach ($items as $item) {
                $urls = $item->attachment_urls ?? [];
                $metas = $item->metadata['attachments'] ?? [];
                foreach ($urls as $idx => $url) {
                    // attachment_urls may be a plain URL string or an object {url, name, size, mime_type}
                    $urlEntry = is_array($url) ? $url : ['url' => $url];
                    $url      = $urlEntry['url'] ?? $url;
                    $meta = $metas[$idx] ?? [];
                    $meta = array_merge([
                        'name' => $urlEntry['name'] ?? null,
                        'size' => $urlEntry['size'] ?? null,
                        'type' => isset($urlEntry['mime_type']) ? explode('/', $urlEntry['mime_type'])[0] : null,
                    ], $meta ?: []);
                    $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
                    $type = $meta['type'] ?? (
                        in_array($ext, ['jpg','jpeg','png','gif','webp']) ? 'image'
                        : (in_array($ext, ['mp4','mov','webm']) ? 'video'
                        : (in_array($ext, ['mp3','ogg','wav','oga','m4a']) ? 'audio'
                        : 'document'))
                    );
                    $rpFiles->push((object) [
                        'url' => $url,
                        'name' => $meta['name'] ?? basename(parse_url($url, PHP_URL_PATH)),
                        'size' => $meta['size'] ?? null,
                        'type' => $type,
                        'ext' => $ext,
                        'created_at' => $item->created_at,
                    ]);
                }
            }
        }

        // Event icon map
        $rpEventIcons = [
            'status_change'   => 'fas fa-circle-dot',
            'assigned'        => 'fas fa-user-check',
            'unassigned'      => 'fas fa-user-minus',
            'closed'          => 'fas fa-circle-xmark',
            'reopened'        => 'fas fa-rotate-left',
            'archived'        => 'fas fa-box-archive',
            'unarchived'      => 'fas fa-box-open',
            'priority_changed'=> 'fas fa-flag',
            'internal_note'   => 'fas fa-note-sticky',
            'attachment_added'=> 'fas fa-paperclip',
            'customer_replied'=> 'fas fa-reply',
        ];

        // Ticket priority / status helpers
        $rpTicketPriorityColors = ['low' => 'muted', 'normal' => 'info', 'high' => 'warning', 'urgent' => 'danger'];
    @endphp

    {{-- Hero: cover + avatar + nombre --}}
    <div class="bv-right-hero">
        <div class="bv-right-cover"></div>
        <div class="bv-right-avatar">{{ $rpInitials ?: '?' }}</div>
        <div class="bv-right-name">{{ $rpName }}</div>
        <div class="bv-right-sub">
            @if($rpTotal >= 5) VIP · @endif
            Cliente desde {{ $rpSince }}
        </div>
        <div class="bv-right-actions">
            <button class="bv-right-action" data-bv-modal="email">
                <i class="far fa-envelope"></i>Email
            </button>
            <button class="bv-right-action" data-bv-modal="schedule">
                <i class="far fa-calendar-plus"></i>Agendar
            </button>
            <button class="bv-right-action" data-bv-modal="note">
                <i class="far fa-pen-to-square"></i>Nota
            </button>
            <button class="bv-right-action">
                <i class="fas fa-ellipsis"></i>Más
            </button>
        </div>
    </div>

    {{-- Stats LTV / Conversaciones / Última visita --}}
    <div class="bv-right-stats">
        <div class="bv-right-stat">
            <div class="val">€{{ number_format(($rpTotal * 175), 0, ',', '.') }}</div>
            <div class="lbl">LTV</div>
        </div>
        <div class="bv-right-stat">
            <div class="val">{{ $rpTotal }}</div>
            <div class="lbl">Conversaciones</div>
        </div>
        <div class="bv-right-stat">
            <div class="val">{{ $rpCust?->last_seen_at?->diffForHumans() ?? '—' }}</div>
            <div class="lbl">Últ. visita</div>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="bv-right-tabs">
        <button class="bv-right-tab on" data-bv-tab="general"><i class="far fa-circle-user"></i> General</button>
        <button class="bv-right-tab" data-bv-tab="orders"><i class="fas fa-bag-shopping"></i> Pedidos</button>
        <button class="bv-right-tab" data-bv-tab="files"><i class="far fa-folder"></i> Archivos</button>
        <button class="bv-right-tab" data-bv-tab="tickets"><i class="fas fa-ticket"></i> Tickets</button>
        <button class="bv-right-tab" data-bv-tab="previous"><i class="far fa-clock-rotate-left"></i> Anteriores</button>
        <button class="bv-right-tab" data-bv-tab="activity"><i class="fas fa-bolt-lightning"></i> Actividad</button>
    </div>

    <div class="bv-right-body">

        {{-- ── Tab: General ── --}}
        <div class="bv-right-tab-content" data-bv-tab-content="general">

            {{-- Información de contacto --}}
            <div class="bv-right-section">
                <div class="bv-right-section-head">
                    <span class="bv-right-section-title"><i class="far fa-id-card bv-section-icon"></i> Información de contacto</span>
                    <button class="bv-right-section-edit" data-bv-modal="edit-contact" title="Editar">
                        <i class="fas fa-pen"></i>
                    </button>
                </div>

                @if($rpCust?->email)
                <div class="bv-right-row">
                    <span class="lbl">Email</span>
                    <span class="val bv-right-val-sm">{{ $rpCust->email }}</span>
                </div>
                @endif

                @if($rpCust?->phone)
                <div class="bv-right-row">
                    <span class="lbl">Teléfono</span>
                    <span class="val">{{ $rpCust->phone }}</span>
                </div>
                @endif

                @if($rpCust?->custom_attributes['company'] ?? null)
                <div class="bv-right-row">
                    <span class="lbl">Empresa</span>
                    <span class="val">{{ $rpCust->custom_attributes['company'] }}</span>
                </div>
                @endif

                @if($rpCust?->language)
                <div class="bv-right-row">
                    <span class="lbl">Idioma</span>
                    <span class="val">{{ $rpCust->language }}</span>
                </div>
                @endif

                @if($rpCust?->timezone)
                <div class="bv-right-row">
                    <span class="lbl">Zona horaria</span>
                    <span class="val">{{ $rpCust->timezone }}</span>
                </div>
                @endif

                @if($rpCust?->country || $rpCust?->city)
                <div class="bv-right-row">
                    <span class="lbl">Ubicación</span>
                    <span class="val">{{ implode(', ', array_filter([$rpCust->city, $rpCust->state, $rpCust->country])) }}</span>
                </div>
                @endif

                @if(!$rpCust?->email && !$rpCust?->phone && !$rpCust?->language && !$rpCust?->timezone && !$rpCust?->country)
                <div class="bv-tab-empty-inline">Sin información de contacto registrada</div>
                @endif
            </div>

            {{-- Estado de la conversación --}}
            <div class="bv-right-section">
                <div class="bv-right-section-head">
                    <span class="bv-right-section-title"><i class="fas fa-circle-info bv-section-icon"></i> Estado de la conversación</span>
                </div>
                <div class="bv-right-row">
                    <span class="lbl">Estado</span>
                    <button class="bv-th-pill" data-bv-modal="status">
                        <span class="dot" style="background:{{ $rpStatusColor }}"></span>{{ $rpStatusName }}
                        <i class="fas fa-chevron-down bv-pill-chev"></i>
                    </button>
                </div>
                <div class="bv-right-row">
                    <span class="lbl">Prioridad</span>
                    <button class="bv-th-pill" data-bv-modal="priority">
                        <span class="dot bv-dot-{{ $priorityColors[$rpPriority] ?? 'muted' }}"></span>{{ $priorityLabels[$rpPriority] ?? 'Normal' }}
                        <i class="fas fa-chevron-down bv-pill-chev"></i>
                    </button>
                </div>
                <div class="bv-right-row">
                    <span class="lbl">Agente</span>
                    <button class="bv-th-pill" data-bv-modal="assign">
                        <span class="dot bv-dot-agent"></span>{{ $rpConvo?->assignee?->name ?? 'Sin asignar' }}
                        <i class="fas fa-chevron-down bv-pill-chev"></i>
                    </button>
                </div>
                @if($rpConvo?->group)
                <div class="bv-right-row">
                    <span class="lbl">Equipo</span>
                    <button class="bv-th-pill">
                        <i class="far fa-users bv-pill-icon-sm"></i>{{ $rpConvo->group->name }}
                        <i class="fas fa-chevron-down bv-pill-chev"></i>
                    </button>
                </div>
                @endif
            </div>

            {{-- Etiquetas --}}
            <div class="bv-right-section">
                <div class="bv-right-section-head">
                    <span class="bv-right-section-title"><i class="fas fa-tags bv-section-icon"></i> Etiquetas</span>
                    <button class="bv-right-section-edit" data-bv-modal="tags" title="Añadir etiqueta">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
                @if($rpConvo?->conversationTags?->isNotEmpty())
                <div class="bv-tags-wrap">
                    @foreach($rpConvo->conversationTags as $tag)
                        <span class="bv-tag-pill bv-tag-pill--dynamic" style="--bv-tag-color:{{ $tag->color ?? '#6c757d' }}">
                            {{ $tag->name }}
                        </span>
                    @endforeach
                </div>
                @else
                <div class="bv-tab-empty-inline">Sin etiquetas asignadas</div>
                @endif
            </div>

            {{-- Integraciones (mock — no hay módulos reales) --}}
            <div class="bv-right-section bv-right-section-last">
                <div class="bv-right-section-head">
                    <span class="bv-right-section-title"><i class="fas fa-plug bv-section-icon"></i> Integraciones</span>
                </div>
                <div class="bv-integrations-list">
                    <div class="bv-integration-row">
                        <span class="bv-integration-logo bv-integration-logo-shopify">S</span>
                        <span class="bv-integration-name">Shopify</span>
                        <span class="bv-integration-status">
                            <span class="bv-dot-status bv-dot-status-success"></span>Conectado
                        </span>
                    </div>
                    <div class="bv-integration-row">
                        <span class="bv-integration-logo bv-integration-logo-hubspot">H</span>
                        <span class="bv-integration-name">HubSpot</span>
                        <span class="bv-integration-status">
                            <span class="bv-dot-status bv-dot-status-success"></span>Conectado
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Tab: Pedidos ── --}}
        <div class="bv-right-tab-content bv-tab-hidden" data-bv-tab-content="orders">
            @if($rpOrders->isEmpty())
                <div class="bv-tab-empty">
                    <i class="far fa-cart-shopping"></i>
                    <div class="bv-tab-empty-title">Sin pedidos vinculados</div>
                    <div class="bv-tab-empty-sub">No hay pedidos asociados a este cliente</div>
                </div>
            @else
                <div class="bv-orders-list">
                    @foreach($rpOrders as $order)
                        @php
                            $orderTotal = $order->total ?? $order->grand_total ?? 0;
                            $orderStatus = $order->status?->name ?? $order->status_name ?? $order->status ?? 'Pendiente';
                            $orderStatusColor = is_object($order->status ?? null) ? ($order->status->color ?? 'secondary') : 'secondary';
                            $itemsCount = method_exists($order, 'items') ? $order->items()->count() : ($order->items_count ?? 0);
                        @endphp
                        <div class="bv-order-card">
                            <div class="bv-card-head-row">
                                <span class="bv-id-mono-sm">#{{ $order->order_number ?? $order->reference ?? $order->id }}</span>
                                <span class="bv-ticket-badge bv-ticket-badge-{{ $orderStatusColor }}">{{ $orderStatus }}</span>
                            </div>
                            <div class="bv-ticket-title">{{ $order->title ?? ($itemsCount . ' producto' . ($itemsCount === 1 ? '' : 's')) }}</div>
                            <div class="bv-ticket-meta">
                                {{ $order->created_at?->translatedFormat('d M Y') }} ·
                                <strong>{{ number_format((float) $orderTotal, 2, ',', '.') }} €</strong>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- ── Tab: Archivos ── --}}
        <div class="bv-right-tab-content bv-tab-hidden" data-bv-tab-content="files">
            <div class="bv-files-toolbar">
                <button class="bv-files-filter on" data-bv-files-filter="all"><i class="fas fa-table-cells"></i> Todos</button>
                <button class="bv-files-filter" data-bv-files-filter="image"><i class="far fa-image"></i> Imágenes</button>
                <button class="bv-files-filter" data-bv-files-filter="audio"><i class="fas fa-volume-high"></i> Audio</button>
                <button class="bv-files-filter" data-bv-files-filter="video"><i class="far fa-film"></i> Video</button>
                <button class="bv-files-filter" data-bv-files-filter="document"><i class="far fa-file-lines"></i> Documentos</button>
            </div>
            <div class="bv-files-grid" id="bv-files-grid">
                @if($rpFiles->isEmpty())
                    <div class="bv-tab-empty">
                        <i class="far fa-folder-open"></i>
                        <div class="bv-tab-empty-title">Sin archivos</div>
                    </div>
                @else
                    @php
                        $rpDocIcons = ['pdf' => 'fa-file-pdf', 'doc' => 'fa-file-word', 'docx' => 'fa-file-word', 'xls' => 'fa-file-excel', 'xlsx' => 'fa-file-excel', 'ppt' => 'fa-file-powerpoint', 'pptx' => 'fa-file-powerpoint', 'zip' => 'fa-file-zipper', 'csv' => 'fa-file-csv', 'txt' => 'fa-file-lines'];
                    @endphp
                    @foreach($rpFiles as $f)
                        <a href="{{ $f->url }}" target="_blank" rel="noopener"
                           class="bv-file-card"
                           data-bv-file-type="{{ $f->type }}"
                           title="{{ $f->name }}">
                            @if($f->type === 'image')
                                <img src="{{ $f->url }}" alt="{{ $f->name }}" loading="lazy">
                            @elseif($f->type === 'video')
                                <i class="far fa-circle-play"></i>
                            @elseif($f->type === 'audio')
                                <i class="fas fa-volume-high"></i>
                            @else
                                <i class="far {{ $rpDocIcons[$f->ext] ?? 'fa-file' }}"></i>
                            @endif
                            <div class="bv-file-name">{{ \Illuminate\Support\Str::limit($f->name, 18) }}</div>
                            <div class="bv-file-meta">
                                @if($f->size)
                                    {{ round($f->size / 1024) }} KB
                                @endif
                            </div>
                        </a>
                    @endforeach
                @endif
            </div>
        </div>

        {{-- ── Tab: Tickets ── --}}
        <div class="bv-right-tab-content bv-tab-hidden" data-bv-tab-content="tickets">
            @if($rpTickets->isEmpty())
                <div class="bv-tab-empty">
                    <i class="far fa-ticket"></i>
                    <div class="bv-tab-empty-title">Sin tickets relacionados</div>
                    <div class="bv-tab-empty-sub">No hay tickets asociados a este cliente</div>
                </div>
            @else
                <div class="bv-right-section">
                    <div class="bv-right-section-head">
                        <span class="bv-right-section-title"><i class="fas fa-ticket bv-section-icon"></i> Tickets relacionados</span>
                    </div>
                    <div class="bv-ticket-list">
                        @foreach($rpTickets as $ticket)
                        <div class="bv-ticket-card">
                            <div class="bv-card-head-row">
                                <span class="bv-id-mono-sm">#{{ $ticket->ticket_number }}</span>
                                @if($ticket->status)
                                <span class="bv-ticket-badge bv-ticket-badge-{{ $ticket->status->color ?? 'secondary' }}">
                                    {{ $ticket->status->name }}
                                </span>
                                @endif
                            </div>
                            <div class="bv-ticket-title">{{ $ticket->subject ?? $ticket->title ?? 'Sin título' }}</div>
                            <div class="bv-ticket-meta">
                                {{ $ticket->created_at?->translatedFormat('d M Y') }}
                                @if($priorityLabels[$ticket->priority ?? ''] ?? null)
                                    · {{ $priorityLabels[$ticket->priority] }}
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        {{-- ── Tab: Anteriores ── --}}
        <div class="bv-right-tab-content bv-tab-hidden" data-bv-tab-content="previous">
            @php
                $rpPrevious = collect();
                if ($rpCust) {
                    $rpPrevious = \Modules\Helpdesk\Models\Conversation::where('customer_id', $rpCust->id)
                        ->where('id', '!=', $rpConvo?->id)
                        ->with(['status', 'assignee'])
                        ->latest('last_message_at')
                        ->limit(20)
                        ->get();
                }
                $prevChannelMap = [
                    'whatsapp' => ['code' => 'wa',    'icon' => 'fab fa-whatsapp'],
                    'facebook' => ['code' => 'fb',    'icon' => 'fab fa-facebook-messenger'],
                    'instagram'=> ['code' => 'ig',    'icon' => 'fab fa-instagram'],
                    'email'    => ['code' => 'email', 'icon' => 'far fa-envelope'],
                    'widget'   => ['code' => 'web',   'icon' => 'far fa-comment-dots'],
                ];
            @endphp

            @if($rpPrevious->isEmpty())
                <div class="bv-tab-empty">
                    <i class="far fa-clock-rotate-left"></i>
                    <div class="bv-tab-empty-title">Sin conversaciones anteriores</div>
                    <div class="bv-tab-empty-sub">Este es el primer contacto del cliente</div>
                </div>
            @else
                <div class="bv-previous-list">
                    @foreach($rpPrevious as $prev)
                        @php
                            $prevCh = $prevChannelMap[$prev->channel ?? 'widget'] ?? $prevChannelMap['widget'];
                        @endphp
                        <a href="{{ route('manager.helpdesk.conversations.index', ['selected' => $prev->id]) }}"
                           class="bv-previous-item">
                            <span class="bv-previous-channel bv-previous-channel-{{ $prevCh['code'] }}">
                                <i class="{{ $prevCh['icon'] }}"></i>
                            </span>
                            <div class="bv-previous-body">
                                <div class="bv-previous-subject">{{ $prev->subject ?? 'Conversación' }}</div>
                                <div class="bv-previous-meta">
                                    <span class="bv-previous-status {{ $prev->status?->is_open ? 'open' : 'closed' }}">
                                        {{ $prev->status?->name ?? 'Abierta' }}
                                    </span>
                                    <span class="bv-previous-date">
                                        {{ optional($prev->last_message_at)->translatedFormat('d M Y') }}
                                    </span>
                                </div>
                            </div>
                            <i class="fas fa-chevron-right bv-previous-chevron"></i>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- ── Tab: Actividad ── --}}
        <div class="bv-right-tab-content bv-tab-hidden" data-bv-tab-content="activity">
            @if($rpEvents->isEmpty())
                <div class="bv-tab-empty">
                    <i class="far fa-clock-rotate-left"></i>
                    <div class="bv-tab-empty-title">Sin actividad registrada</div>
                    <div class="bv-tab-empty-sub">Los eventos de esta conversación aparecerán aquí</div>
                </div>
            @else
                <div class="bv-right-section bv-right-section-nb">
                    <div class="bv-right-section-head">
                        <span class="bv-right-section-title"><i class="fas fa-bolt-lightning bv-section-icon"></i> Timeline de actividad</span>
                    </div>
                    <div class="bv-timeline-list">
                        @foreach($rpEvents as $event)
                        <div class="bv-timeline-item">
                            @if(!$loop->last)
                            <div class="bv-timeline-line"></div>
                            @endif
                            <div class="bv-timeline-icon bv-timeline-icon-{{ $event->event_color }}">
                                <i class="{{ $rpEventIcons[$event->type] ?? 'fas fa-circle-info' }}"></i>
                            </div>
                            <div>
                                <div class="bv-timeline-title">{{ $event->event_label }}</div>
                                <div class="bv-timeline-sub">
                                    {{ $event->created_at?->diffForHumans() }}
                                    @if($event->sender_name !== 'Sistema') · {{ $event->sender_name }} @endif
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

    </div>
@endif
</aside>
