{{-- Panel derecho — Refined v4 / right-panel-v3 --}}
@php
    // Pre-compute engagement data attributes before <aside> renders
    $_rpConvoEarly = $selectedConversation ?? null;
    $_rpCustEarly  = $_rpConvoEarly?->customer;
    $_rpInboxId    = $_rpConvoEarly?->inbox_id ?? '';
    $_rpIntegrations = collect();
    if ($_rpInboxId && class_exists(\Modules\Engagement\Models\PlatformIntegration::class)) {
        $_rpIntegrations = \Modules\Engagement\Models\PlatformIntegration::query()
            ->where('inbox_id', $_rpInboxId)
            ->where('is_active', true)
            ->get(['id', 'platform', 'store_url']);
    }
    $_rpPsId         = $_rpCustEarly?->externalIdFor('prestashop') ?? '';
    $_rpErpId        = $_rpCustEarly?->externalIdFor('erp') ?? '';
    // ERP es un sistema único (Oracle Interges), no requiere integración por
    // inbox: basta con que el customer tenga IDCLIENTE vinculado. PrestaShop sí
    // varía por tienda, por eso mantiene el chequeo de PlatformIntegration.
    $_rpHasPs        = $_rpIntegrations->contains('platform', 'prestashop') || ! empty($_rpPsId);
    $_rpHasErp       = $_rpIntegrations->contains('platform', 'erp') || ! empty($_rpErpId);
    $_rpEmail        = $_rpCustEarly?->email ?? '';
    $_rpPsStoreUrl   = $_rpIntegrations->firstWhere('platform', 'prestashop')?->store_url ?? '';
@endphp
<aside class="bv-right"
    data-customer-id="{{ $_rpConvoEarly?->customer_id ?? '' }}"
    data-inbox-id="{{ $_rpInboxId }}"
    data-has-ps="{{ $_rpHasPs ? '1' : '' }}"
    data-has-erp="{{ $_rpHasErp ? '1' : '' }}"
    data-lookup-email="{{ $_rpEmail }}"
    data-lookup-ps-id="{{ $_rpPsId }}"
    data-lookup-erp-id="{{ $_rpErpId }}"
    data-lookup-url="{{ route('manager.engagement.customer-data.lookup') }}"
    data-ps-store-url="{{ $_rpPsStoreUrl }}"
    data-csrf="{{ csrf_token() }}">
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

        // Tickets (HelpdeskTickets module - optional)
        $rpTickets = collect();
        $rpTicketsEnabled = helpdesk_tickets_enabled();
        if ($rpCust && $rpTicketsEnabled) {
            $rpTickets = app(\Modules\Helpdesk\Contracts\TicketServiceContract::class)
                ->getCustomerTickets($rpCust, 5);
        }

        // Activity events
        $rpEvents = $rpConvo ? $rpConvo->events()->latest()->limit(20)->get() : collect();

        // Widget technology + visited pages (HelpdeskLivechat module).
        // Show the tab for web-channel conversations (and as fallback for any
        // conversation whose customer has a linked WidgetSession). Empty state
        // is rendered inside the tab when no session is recorded yet.
        $rpWidgetSession = null;
        $rpVisitedPages = collect();
        $rpShowTechnologyTab = false;

        // Live assistance flags from Web channel
        $rpEnableLiveView = false;
        $rpEnableScreenShare = false;
        $rpShowAssistTab = false;

        if ($rpConvo && class_exists(\Modules\HelpdeskLivechat\Models\WidgetSession::class)) {
            $rpIsWebChannel = $rpConvo->channel === 'web'
                || ($rpConvo->inbox?->channel_type ?? null) === 'web';

            if ($rpIsWebChannel) {
                $rpShowTechnologyTab = true;

                $rpWebChannel = $rpConvo->inbox?->channel;
                if ($rpWebChannel instanceof \Modules\HelpdeskLivechat\Models\Channels\Web) {
                    $rpEnableLiveView = (bool) $rpWebChannel->enable_live_view;
                    $rpEnableScreenShare = (bool) $rpWebChannel->enable_screen_share;
                    $rpShowAssistTab = $rpEnableLiveView || $rpEnableScreenShare;
                }
            }

            $rpMeta = is_array($rpConvo->metadata)
                ? $rpConvo->metadata
                : (json_decode((string) ($rpConvo->metadata ?? '{}'), true) ?? []);
            $rpSessionToken = $rpMeta['widget_session_token'] ?? null;

            if ($rpSessionToken) {
                $rpWidgetSession = \Modules\HelpdeskLivechat\Models\WidgetSession::query()
                    ->where('session_token', $rpSessionToken)
                    ->first();
            }

            // Fallback: link by customer_id if session token wasn't recorded.
            if (! $rpWidgetSession && $rpCust) {
                $rpWidgetSession = \Modules\HelpdeskLivechat\Models\WidgetSession::query()
                    ->where('customer_id', $rpCust->id)
                    ->orderByDesc('last_activity_at')
                    ->first();
            }

            if ($rpWidgetSession) {
                $rpShowTechnologyTab = true;
                $rpVisitedPages = $rpWidgetSession->pageViews()
                    ->orderByDesc('viewed_at')
                    ->limit(50)
                    ->get();
            }

            // Cache session→conversation mapping so heartbeat broadcasts know the conversation_id.
            if ($rpSessionToken && $rpConvo) {
                \Illuminate\Support\Facades\Cache::put(
                    'helpdesklivechat:session_conv:'.$rpSessionToken,
                    $rpConvo->id,
                    now()->addDay()
                );
            }
        }

        // Backwards-compat alias for places that already check $rpHasWidgetData
        $rpHasWidgetData = $rpShowTechnologyTab;

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
                ->with(['user:id,firstname,lastname'])
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
                    $mimeMain = isset($urlEntry['mime_type']) ? explode('/', $urlEntry['mime_type'])[0] : null;
                    $meta = array_merge([
                        'name' => $urlEntry['name'] ?? null,
                        'size' => $urlEntry['size'] ?? null,
                        'type' => $mimeMain && in_array($mimeMain, ['image', 'video', 'audio']) ? $mimeMain : null,
                    ], $meta ?: []);
                    $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
                    $type = $meta['type'] ?? (
                        in_array($ext, ['jpg','jpeg','png','gif','webp']) ? 'image'
                        : (in_array($ext, ['mp4','mov','webm']) ? 'video'
                        : (in_array($ext, ['mp3','ogg','wav','oga','m4a']) ? 'audio'
                        : 'document'))
                    );
                    // Determine author display:
                    // - if item has user_id (agent), use agent name
                    // - else if author_id matches customer, use customer name
                    // - else "Sistema"
                    $authorName = 'Sistema';
                    $authorIsAgent = false;
                    if ($item->user) {
                        $authorName = trim(($item->user->firstname ?? '').' '.($item->user->lastname ?? '')) ?: 'Agente';
                        $authorIsAgent = true;
                    } elseif ($item->author_id && $rpCust && $item->author_id === $rpCust->id) {
                        $authorName = $rpCust->name ?? 'Cliente';
                    }
                    $rpFiles->push((object) [
                        'url' => $url,
                        'name' => $meta['name'] ?? basename(parse_url($url, PHP_URL_PATH)),
                        'size' => $meta['size'] ?? null,
                        'type' => $type,
                        'ext' => $ext,
                        'created_at' => $item->created_at,
                        'conversation_id' => $item->conversation_id,
                        'author_name' => $authorName,
                        'author_is_agent' => $authorIsAgent,
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

        // Platform integrations (Engagement module)
        $rpIntegrations = collect();
        if ($rpConvo && class_exists(\Modules\Engagement\Models\PlatformIntegration::class)) {
            $rpIntegrations = \Modules\Engagement\Models\PlatformIntegration::query()
                ->where('inbox_id', $rpConvo->inbox_id)
                ->where('is_active', true)
                ->get(['id', 'platform']);
        }
        $rpExternalEmail = $rpCust?->email;
        $rpExternalPsId  = $rpCust?->externalIdFor('prestashop') ?? null;
        $rpExternalErpId = $rpCust?->externalIdFor('erp') ?? null;
        // Idem nota arriba: ERP es global, basta con que el customer esté vinculado.
        $rpHasPs  = $rpIntegrations->contains('platform', 'prestashop') || ! empty($rpExternalPsId);
        $rpHasErp = $rpIntegrations->contains('platform', 'erp') || ! empty($rpExternalErpId);
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
            @if(helpdesk_feature_enabled('rp_email'))
            <button class="bv-right-action" data-bv-modal="email">
                <i class="far fa-envelope"></i>Email
            </button>
            @endif
            @if(helpdesk_feature_enabled('rp_schedule'))
            <button class="bv-right-action" data-bv-modal="schedule">
                <i class="far fa-calendar-plus"></i>Agendar
            </button>
            @endif
            @if(helpdesk_feature_enabled('rp_note'))
            <button class="bv-right-action" data-bv-modal="note">
                <i class="far fa-pen-to-square"></i>Nota
            </button>
            @endif
            <button class="bv-right-action">
                <i class="fas fa-ellipsis"></i>Más
            </button>
        </div>
    </div>

    @if(helpdesk_feature_enabled('rp_stats'))
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
    @endif

    {{-- Tabs --}}
    <div class="bv-right-tabs">
        @if(helpdesk_feature_enabled('tab_general'))
        <button class="bv-right-tab on" data-bv-tab="general" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="General"><i class="far fa-circle-user"></i><span class="bv-tab-lbl">General</span></button>
        @endif
        @if(helpdesk_feature_enabled('tab_orders'))
        <button class="bv-right-tab" data-bv-tab="orders" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="Pedidos"><i class="fas fa-bag-shopping"></i><span class="bv-tab-lbl">Pedidos</span></button>
        @endif
        @if(helpdesk_feature_enabled('tab_files'))
        <button class="bv-right-tab" data-bv-tab="files" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="Archivos"><i class="far fa-folder"></i><span class="bv-tab-lbl">Archivos</span></button>
        @endif
        @if($rpTicketsEnabled && helpdesk_feature_enabled('tab_tickets'))
            <button class="bv-right-tab" data-bv-tab="tickets" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="Tickets"><i class="fas fa-ticket"></i><span class="bv-tab-lbl">Tickets</span></button>
        @endif
        @if(helpdesk_feature_enabled('tab_previous'))
        <button class="bv-right-tab" data-bv-tab="previous" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="Anteriores"><i class="fas fa-clock-rotate-left"></i><span class="bv-tab-lbl">Anteriores</span></button>
        @endif
        @if(helpdesk_feature_enabled('tab_activity'))
        <button class="bv-right-tab" data-bv-tab="activity" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="Actividad"><i class="fas fa-bolt-lightning"></i><span class="bv-tab-lbl">Actividad</span></button>
        @endif
        @if(helpdesk_feature_enabled('email'))
        <button class="bv-right-tab" data-bv-tab="emails" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="Emails"><i class="far fa-envelope-open"></i><span class="bv-tab-lbl">Emails</span></button>
        @endif
        @if($rpHasWidgetData && helpdesk_feature_enabled('tab_technology'))
            <button class="bv-right-tab" data-bv-tab="technology" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="Tecnología"><i class="fas fa-laptop"></i><span class="bv-tab-lbl">Tecnología</span></button>
        @endif
        @if(($rpShowAssistTab ?? false) && helpdesk_feature_enabled('tab_assist'))
            <button class="bv-right-tab" data-bv-tab="assist" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="Pantalla"><i class="fas fa-eye"></i><span class="bv-tab-lbl">Pantalla</span></button>
        @endif
        @if($rpCust && helpdesk_feature_enabled('tab_customer360'))
            <button class="bv-right-tab" data-bv-tab="customer-360" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="Cliente 360"><i class="fas fa-chart-pie"></i><span class="bv-tab-lbl">Cliente 360</span></button>
        @endif
        @if($rpHasPs || $rpHasErp)
            <span class="bv-right-tab-sep"></span>
        @endif
        @if($rpHasPs)
            <button class="bv-right-tab" data-bv-tab="ps-orders" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="Tienda"><i class="fas fa-store"></i><span class="bv-tab-lbl">Tienda</span></button>
            <button class="bv-right-tab" data-bv-tab="ps-returns" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="Devoluciones"><i class="fas fa-rotate-left"></i><span class="bv-tab-lbl">Devoluciones</span></button>
            <button class="bv-right-tab" data-bv-tab="ps-vouchers" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="Cupones"><i class="fas fa-tag"></i><span class="bv-tab-lbl">Cupones</span></button>
            <button class="bv-right-tab" data-bv-tab="ps-addresses" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="Direcciones"><i class="fas fa-location-dot"></i><span class="bv-tab-lbl">Direcciones</span></button>
        @endif
        @if($rpHasErp)
            <button class="bv-right-tab" data-bv-tab="erp-orders" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="Gestión"><i class="fas fa-clipboard-list"></i><span class="bv-tab-lbl">Gestión</span></button>
            <button class="bv-right-tab" data-bv-tab="erp-finance" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="Finanzas"><i class="fas fa-coins"></i><span class="bv-tab-lbl">Finanzas</span></button>
            <button class="bv-right-tab" data-bv-tab="erp-loyalty" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="Fidelización"><i class="fas fa-star"></i><span class="bv-tab-lbl">Fidelización</span></button>
        @endif
    </div>

    <div class="bv-right-body">

        {{-- ── Tab: General ── --}}
        <div class="bv-right-tab-content" data-bv-tab-content="general">

            {{-- Información de contacto --}}
            <div class="bv-right-section">
                <div class="bv-right-section-head">
                    <span class="bv-right-section-title">Información de contacto</span>
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
                @php
                    $rpLangCode = strtolower(substr($rpCust->language, 0, 2));
                    $rpLangFlags = [
                        'es' => '🇪🇸', 'en' => '🇬🇧', 'fr' => '🇫🇷', 'de' => '🇩🇪',
                        'it' => '🇮🇹', 'pt' => '🇵🇹', 'nl' => '🇳🇱', 'ja' => '🇯🇵',
                        'zh' => '🇨🇳', 'ru' => '🇷🇺', 'ar' => '🇸🇦', 'ko' => '🇰🇷',
                    ];
                    $rpLangNames = [
                        'es' => 'Español', 'en' => 'Inglés', 'fr' => 'Francés', 'de' => 'Alemán',
                        'it' => 'Italiano', 'pt' => 'Portugués', 'nl' => 'Neerlandés', 'ja' => 'Japonés',
                        'zh' => 'Chino', 'ru' => 'Ruso', 'ar' => 'Árabe', 'ko' => 'Coreano',
                    ];
                    $rpFlag = $rpLangFlags[$rpLangCode] ?? '🌐';
                    $rpLangLabel = $rpLangNames[$rpLangCode] ?? strtoupper($rpCust->language);
                @endphp
                <div class="bv-right-row">
                    <span class="lbl">Idioma</span>
                    <span class="val">{{ $rpFlag }} {{ $rpLangLabel }} <small class="text-muted">({{ strtoupper($rpCust->language) }})</small></span>
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

            @if(helpdesk_feature_enabled('rp_status'))
            {{-- Estado de la conversación --}}
            <div class="bv-right-section">
                <div class="bv-right-section-head">
                    <span class="bv-right-section-title">Estado de la conversación</span>
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
            @endif

            @if(helpdesk_feature_enabled('rp_tags_section'))
            {{-- Etiquetas --}}
            <div class="bv-right-section">
                <div class="bv-right-section-head">
                    <span class="bv-right-section-title">Etiquetas</span>
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
            @endif

            {{-- Integraciones reales detectadas desde custom_attributes --}}
            @php
                $platform = $rpCust?->custom_attributes['platform'] ?? null;
                $platformMap = [
                    'prestashop' => ['name' => 'PrestaShop', 'icon' => 'P', 'class' => 'bv-integration-logo-prestashop', 'color' => '#df0067'],
                    'shopify'    => ['name' => 'Shopify',    'icon' => 'S', 'class' => 'bv-integration-logo-shopify',    'color' => '#95bf47'],
                    'woocommerce'=> ['name' => 'WooCommerce','icon' => 'W', 'class' => 'bv-integration-logo-woocommerce','color' => '#96588a'],
                    'magento'    => ['name' => 'Magento',    'icon' => 'M', 'class' => 'bv-integration-logo-magento',    'color' => '#f26322'],
                    'bigcommerce'=> ['name' => 'BigCommerce','icon' => 'B', 'class' => 'bv-integration-logo-bigcommerce','color' => '#34313f'],
                ];
                $detected = $platform ? ($platformMap[$platform] ?? null) : null;
            @endphp
            @if(helpdesk_feature_enabled('rp_integrations'))
            <div class="bv-right-section bv-right-section-last">
                <div class="bv-right-section-head">
                    <span class="bv-right-section-title"><i class="fas fa-plug bv-section-icon"></i> Integraciones</span>
                </div>
                <div class="bv-integrations-list">
                    @if($detected)
                        <div class="bv-integration-row">
                            <span class="bv-integration-logo {{ $detected['class'] }}" style="background:{{ $detected['color'] }}">{{ $detected['icon'] }}</span>
                            <span class="bv-integration-name">{{ $detected['name'] }}</span>
                            <span class="bv-integration-status">
                                <span class="bv-dot-status bv-dot-status-success"></span>Conectado
                            </span>
                        </div>
                    @else
                        <div class="bv-integration-row">
                            <span class="bv-integration-logo bv-integration-logo-generic" >G</span>
                            <span class="bv-integration-name">Widget Web</span>
                            <span class="bv-integration-status">
                                <span class="bv-dot-status bv-dot-status-success"></span>Activo
                            </span>
                        </div>
                    @endif
                </div>
            </div>
            @endif
        </div>

        {{-- ── Tab: Pedidos ── --}}
        <div class="bv-right-tab-content bv-tab-hidden" data-bv-tab-content="orders" id="bv-orders-tab">
            @php
                $rawAttrs = $rpCust?->custom_attributes ?? [];
                // Support nested custom_attributes (PrestaShop widget sends custom_attributes inside custom_attributes)
                $nestedAttrs = is_array($rawAttrs['custom_attributes'] ?? null) ? $rawAttrs['custom_attributes'] : [];
                $externalOrders = $nestedAttrs['orders'] ?? $rawAttrs['orders'] ?? [];
                $cartData = $nestedAttrs['cart'] ?? $rawAttrs['cart'] ?? null;
                $allOrders = $rpOrders->isNotEmpty() || !empty($externalOrders);
                $totalOrders = $rpOrders->count() + count($externalOrders);
            @endphp
            @if($rpHasPs || $rpHasErp)
            <div class="bv-source-actions bv-hidden" id="bv-orders-source-actions">
                <button class="btn btn-sm btn-link bv-refresh-source" data-bv-refresh-source="orders">
                    <i class="fas fa-arrows-rotate"></i> Actualizar
                </button>
                <span class="bv-source-meta" data-bv-source-meta="orders"></span>
            </div>
            @endif
            @if(!$allOrders && empty($cartData))
                <div class="bv-tab-empty">
                    <i class="far fa-cart-shopping"></i>
                    <div class="bv-tab-empty-title">Sin pedidos vinculados</div>
                    <div class="bv-tab-empty-sub">No hay pedidos asociados a este cliente</div>
                </div>
            @else
                <div class="rp3-scroll">
                    @if($allOrders)
                    <div class="rp3-section">
                        <div class="rp3-sec-head">
                            Historial de pedidos
                            <span class="count">· {{ $totalOrders }}</span>
                            <span class="spacer"></span>
                        </div>
                        {{-- Pedidos locales (módulo Ecommerce) --}}
                        @foreach($rpOrders as $order)
                            @php
                                $orderTotal = $order->total ?? $order->grand_total ?? 0;
                                $orderStatus = $order->status?->name ?? $order->status_name ?? $order->status ?? 'Pendiente';
                                $orderStatusColor = match(strtolower($orderStatus)) {
                                    'entregado', 'completed', 'complete' => 'var(--success)',
                                    'enviado', 'shipped' => 'var(--info)',
                                    'cancelado', 'cancelled', 'canceled' => 'var(--danger)',
                                    default => 'var(--warning)',
                                };
                                $orderDate = $order->created_at?->translatedFormat('d M') ?? '—';
                            @endphp
                            <div class="rp3-order" data-bv-modal="order" data-order-type="local"
                                 data-order-id="{{ $order->id }}"
                                 data-order-ref="#{{ $order->order_number ?? $order->reference ?? $order->id }}"
                                 data-order-status="{{ $orderStatus }}"
                                 data-order-status-color="{{ $orderStatusColor }}"
                                 data-order-date="{{ $order->created_at?->translatedFormat('d M Y') ?? '—' }}"
                                 data-order-total="{{ number_format((float) $orderTotal, 2, ',', '.') }}"
                                 data-order-products="{{ json_encode([['name' => $order->title ?? 'Producto', 'qty' => 1, 'price' => $orderTotal]]) }}"
                                >
                                <div class="thumb"><i class="fas fa-box"></i></div>
                                <div class="body">
                                    <div class="id">#{{ $order->order_number ?? $order->reference ?? $order->id }}</div>
                                    <div class="t">{{ $order->title ?? 'Pedido #' . $order->id }}</div>
                                    <div class="meta">
                                        <b>{{ number_format((float) $orderTotal, 2, ',', '.') }} €</b>
                                        <span style="color: {{ $orderStatusColor }}; font-weight: 600;">●</span>
                                        <span>{{ $orderStatus }}</span>
                                        <span>·</span>
                                        <span>{{ $orderDate }}</span>
                                    </div>
                                </div>
                            </div>
                        @endforeach

                        {{-- Pedidos externos (PrestaShop, Shopify, etc.) --}}
                        @foreach($externalOrders as $extOrder)
                            @php
                                $extStatus = $extOrder['status'] ?? 'Pendiente';
                                $extStatusColor = match(strtolower($extStatus)) {
                                    'entregado', 'completed', 'complete' => 'var(--success)',
                                    'enviado', 'shipped' => 'var(--info)',
                                    'cancelado', 'cancelled', 'canceled' => 'var(--danger)',
                                    default => 'var(--warning)',
                                };
                                $extDateRaw = $extOrder['date'] ?? null;
                                try {
                                    $extDate = $extDateRaw ? \Carbon\Carbon::parse($extDateRaw)->translatedFormat('d M') : '—';
                                    $extDateFull = $extDateRaw ? \Carbon\Carbon::parse($extDateRaw)->translatedFormat('d M Y') : '—';
                                } catch (\Throwable $e) {
                                    $extDate = '—';
                                    $extDateFull = '—';
                                }
                                $extTotal = (float) ($extOrder['total'] ?? 0);
                                $extRef = $extOrder['reference'] ?? $extOrder['id'] ?? '—';
                                $extUrl = $extOrder['url'] ?? null;
                                $extProducts = [];
                                if (!empty($extOrder['products']) && is_array($extOrder['products'])) {
                                    foreach ($extOrder['products'] as $p) {
                                        $extProducts[] = ['name' => $p['name'] ?? 'Producto', 'qty' => $p['quantity'] ?? 1, 'price' => $p['price'] ?? 0];
                                    }
                                }
                            @endphp
                            <div class="rp3-order" data-bv-modal="order" data-order-type="external"
                                 data-order-id="{{ $extOrder['id'] ?? '' }}"
                                 data-order-ref="#{{ $extRef }}"
                                 data-order-status="{{ $extStatus }}"
                                 data-order-status-color="{{ $extStatusColor }}"
                                 data-order-date="{{ $extDateFull }}"
                                 data-order-total="{{ number_format($extTotal, 2, ',', '.') }}"
                                 data-order-products="{{ json_encode($extProducts) }}"
                                 data-order-url="{{ $extUrl }}"
                                 data-order-platform="{{ $rpCust?->custom_attributes['platform'] ?? '' }}"
                                >
                                <div class="thumb"><i class="fas fa-box"></i></div>
                                <div class="body">
                                    <div class="id">#{{ $extRef }}</div>
                                    <div class="t">Pedido #{{ $extRef }}</div>
                                    <div class="meta">
                                        <b>{{ number_format($extTotal, 2, ',', '.') }} €</b>
                                        <span style="color: {{ $extStatusColor }}; font-weight: 600;">●</span>
                                        <span>{{ $extStatus }}</span>
                                        <span>·</span>
                                        <span>{{ $extDate }}</span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @endif

                    {{-- Carrito abandonado --}}
                    @if(!empty($cartData) && is_array($cartData))
                    @php
                        $cartItemCount = count($cartData['products'] ?? []);
                        $cartTotal     = (float) ($cartData['total'] ?? 0);
                        $cartId        = $cartData['id'] ?? null;
                        $cartAdminUrl  = $cartId && $_rpPsStoreUrl
                            ? rtrim($_rpPsStoreUrl, '/') . '/index.php?controller=AdminCarts&id_cart=' . (int) $cartId . '&viewcart=1'
                            : null;
                    @endphp
                    <div class="rp3-section">
                        <div class="rp3-sec-head">
                            Carrito abandonado
                            @if($cartAdminUrl)
                                <a href="{{ $cartAdminUrl }}" target="_blank" rel="noopener"
                                   class="rp3-cart-ext-link" title="Ver carrito en PrestaShop">
                                    <i class="fas fa-arrow-up-right-from-square"></i>
                                </a>
                            @endif
                        </div>
                        <div class="rp3-cart" @if($cartId) data-cart-id="{{ $cartId }}" @endif>
                            <div class="hd">
                                <span class="dot"></span>
                                <i class="fas fa-cart-shopping"></i>
                                {{ $cartItemCount }} item{{ $cartItemCount === 1 ? '' : 's' }}
                            </div>
                            <div class="rp3-cart-items">
                                @foreach($cartData['products'] ?? [] as $product)
                                <div class="rp3-cart-item">
                                    <div class="th"></div>
                                    <div class="n">{{ $product['name'] ?? 'Producto' }}</div>
                                    <div class="p">{{ number_format((float) ($product['price'] ?? 0), 2, ',', '.') }} €</div>
                                </div>
                                @endforeach
                            </div>
                            <div class="rp3-cart-total">
                                <span>Total</span>
                                <span>{{ number_format($cartTotal, 2, ',', '.') }} €</span>
                            </div>
                            <div class="rp3-cart-acts">
                                <button type="button"><i class="fas fa-tag"></i> Cupón 10%</button>
                                @if($cartAdminUrl)
                                <button type="button" class="rp3-cart-act-primary"
                                    onclick="window.open('{{ $cartAdminUrl }}', '_blank')">
                                    <i class="fas fa-link"></i> Recuperar
                                </button>
                                @else
                                <button type="button" class="rp3-cart-act-primary" disabled>
                                    <i class="fas fa-link"></i> Recuperar
                                </button>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            @endif
        </div>

        {{-- ── Tab: Archivos ── --}}
        @php
            $rpFileCounts = [
                'all'      => $rpFiles->count(),
                'image'    => $rpFiles->where('type', 'image')->count(),
                'audio'    => $rpFiles->where('type', 'audio')->count(),
                'video'    => $rpFiles->where('type', 'video')->count(),
                'document' => $rpFiles->where('type', 'document')->count(),
            ];
            $rpFileSizes = [
                'all'      => $rpFiles->sum('size'),
                'image'    => $rpFiles->where('type', 'image')->sum('size'),
                'audio'    => $rpFiles->where('type', 'audio')->sum('size'),
                'video'    => $rpFiles->where('type', 'video')->sum('size'),
                'document' => $rpFiles->where('type', 'document')->sum('size'),
            ];
            $rpFormatSize = function ($bytes) {
                if (! $bytes) { return '0 B'; }
                if ($bytes < 1024) { return $bytes.' B'; }
                if ($bytes < 1048576) { return round($bytes / 1024, 1).' KB'; }
                if ($bytes < 1073741824) { return round($bytes / 1048576, 1).' MB'; }
                return round($bytes / 1073741824, 1).' GB';
            };
            $rpDocIcons = [
                'pdf'  => ['icon' => 'fa-file-pdf',        'color' => '#90bb13'],
                'doc'  => ['icon' => 'fa-file-word',       'color' => '#2563eb'],
                'docx' => ['icon' => 'fa-file-word',       'color' => '#2563eb'],
                'xls'  => ['icon' => 'fa-file-excel',      'color' => '#059669'],
                'xlsx' => ['icon' => 'fa-file-excel',      'color' => '#059669'],
                'ppt'  => ['icon' => 'fa-file-powerpoint', 'color' => '#e67000'],
                'pptx' => ['icon' => 'fa-file-powerpoint', 'color' => '#e67000'],
                'zip'  => ['icon' => 'fa-file-zipper',     'color' => '#71717a'],
                'rar'  => ['icon' => 'fa-file-zipper',     'color' => '#71717a'],
                'csv'  => ['icon' => 'fa-file-csv',        'color' => '#059669'],
                'txt'  => ['icon' => 'fa-file-lines',      'color' => '#71717a'],
            ];
            $rpTypeColors = [
                'image'    => '#e67000',
                'video'    => '#90bb13',
                'audio'    => '#059669',
                'document' => '#71717a',
            ];
            $rpTypeLabels = [
                'image'    => 'Imágenes',
                'video'    => 'Vídeo',
                'audio'    => 'Audio',
                'document' => 'Docs',
            ];
        @endphp
        <div class="bv-right-tab-content bv-tab-hidden" data-bv-tab-content="files">

            @if($rpFiles->isEmpty())
                <div class="bv-tab-empty">
                    <i class="far fa-folder-open"></i>
                    <div class="bv-tab-empty-title">Sin archivos compartidos</div>
                    <div class="bv-tab-empty-sub">Los archivos enviados en las conversaciones aparecerán aquí</div>
                </div>
            @else

                {{-- Section header --}}
                <div class="media-sec-head">
                    <div class="media-section-title"><i class="fa-solid fa-folder-open"></i> Archivos</div>
                    @if($rpFileSizes['all'])
                        <div class="media-size">{{ $rpFormatSize($rpFileSizes['all']) }}</div>
                    @endif
                </div>

                {{-- Progress bar --}}
                @if($rpFileSizes['all'] > 0)
                    <div class="media-progress">
                        @foreach($rpTypeColors as $t => $color)
                            @if($rpFileSizes[$t] > 0)
                                @php $pct = ($rpFileSizes[$t] / $rpFileSizes['all']) * 100; @endphp
                                <div class="seg {{ $t }}"
                                     style="width:{{ round($pct, 2) }}%;background:{{ $color }};"
                                     data-tooltip="{{ $rpTypeLabels[$t] }}: {{ $rpFileCounts[$t] }} · {{ $rpFormatSize($rpFileSizes[$t]) }}"
                                     aria-label="{{ $rpTypeLabels[$t] }}: {{ $rpFileCounts[$t] }} · {{ $rpFormatSize($rpFileSizes[$t]) }}"></div>
                            @endif
                        @endforeach
                    </div>
                    <div class="media-legend">
                        @foreach($rpTypeColors as $t => $color)
                            @if($rpFileCounts[$t] > 0)
                                <span class="item">
                                    <span class="d {{ $t }}" style="background:{{ $color }};"></span>
                                    {{ $rpTypeLabels[$t] }}
                                    <strong>{{ $rpFormatSize($rpFileSizes[$t]) }}</strong>
                                </span>
                            @endif
                        @endforeach
                    </div>
                @endif

                <div class="media-divider"></div>

                {{-- Filter + sort + view toolbar --}}
                <div class="media-filter-row">
                    <span class="media-pill bv-files-filter on" data-bv-files-filter="all">
                        Todos <span class="c">{{ $rpFileCounts['all'] }}</span>
                    </span>
                    @if($rpFileCounts['image'] > 0)
                        <span class="media-pill bv-files-filter" data-bv-files-filter="image">
                            <i class="far fa-image"></i> <span class="c">{{ $rpFileCounts['image'] }}</span>
                        </span>
                    @endif
                    @if($rpFileCounts['audio'] > 0)
                        <span class="media-pill bv-files-filter" data-bv-files-filter="audio">
                            <i class="fas fa-volume-high"></i> <span class="c">{{ $rpFileCounts['audio'] }}</span>
                        </span>
                    @endif
                    @if($rpFileCounts['video'] > 0)
                        <span class="media-pill bv-files-filter" data-bv-files-filter="video">
                            <i class="far fa-film"></i> <span class="c">{{ $rpFileCounts['video'] }}</span>
                        </span>
                    @endif
                    @if($rpFileCounts['document'] > 0)
                        <span class="media-pill bv-files-filter" data-bv-files-filter="document">
                            <i class="far fa-file-lines"></i> <span class="c">{{ $rpFileCounts['document'] }}</span>
                        </span>
                    @endif

                    <span class="spacer"></span>

                    <select class="fselect bv-files-sort" id="bv-files-sort" aria-label="Ordenar">
                        <option value="recent">Recientes</option>
                        <option value="oldest">Antiguos</option>
                        <option value="size-desc">Mayor tamaño</option>
                        <option value="size-asc">Menor tamaño</option>
                        <option value="name">Nombre A-Z</option>
                    </select>

                    <div class="media-view-toggle">
                        <button class="bv-files-vt on" data-bv-view="grid" title="Cuadrícula">
                            <i class="fas fa-grip"></i>
                        </button>
                        <button class="bv-files-vt" data-bv-view="list" title="Lista">
                            <i class="fas fa-list"></i>
                        </button>
                    </div>
                </div>

                {{-- File grid --}}
                <div class="media-grid bv-files-grid" id="bv-files-grid" data-view="grid">
                    @foreach($rpFiles as $f)
                        @php
                            $fileMeta    = $rpDocIcons[$f->ext] ?? ['icon' => 'fa-file', 'color' => '#71717a'];
                            $fileTooltip = $f->name.($f->size ? ' · '.$rpFormatSize($f->size) : '');
                        @endphp
                        <a href="{{ $f->url }}"
                           target="_blank" rel="noopener"
                           class="media-card bv-file-card"
                           data-bv-file-type="{{ $f->type }}"
                           data-bv-file-size="{{ $f->size ?: 0 }}"
                           data-bv-file-name="{{ strtolower($f->name) }}"
                           data-bv-file-ts="{{ $f->created_at?->timestamp ?? 0 }}"
                           data-bv-file-url="{{ $f->url }}"
                           data-tooltip="{{ $fileTooltip }}"
                           aria-label="{{ $fileTooltip }}">
                            <div class="media-thumb">
                                @if($f->type === 'image')
                                    <img src="{{ $f->url }}" alt="{{ $f->name }}" loading="lazy">
                                    <span class="bv-file-overlay"><i class="fas fa-magnifying-glass-plus"></i></span>
                                @elseif($f->type === 'video')
                                    <div class="media-thumb video">
                                        <div class="play"><i class="fas fa-play"></i></div>
                                    </div>
                                @elseif($f->type === 'audio')
                                    <div class="bv-file-icon-wrap" style="color:#059669;">
                                        <i class="fas fa-volume-high"></i>
                                    </div>
                                @else
                                    <div class="bv-file-icon-wrap" style="color:{{ $fileMeta['color'] }};">
                                        <i class="far {{ $fileMeta['icon'] }}"></i>
                                    </div>
                                @endif
                                @if($f->ext)
                                    <span class="type-badge">{{ strtoupper($f->ext) }}</span>
                                @endif
                            </div>
                            <div class="media-info">
                                <span class="name">{{ \Illuminate\Support\Str::limit($f->name, 24) }}</span>
                                <span class="author" title="{{ $f->author_name }}">
                                    {{ \Illuminate\Support\Str::limit($f->author_name, 16) }}
                                </span>
                                <div class="row">
                                    @if($f->size)<span class="size">{{ $rpFormatSize($f->size) }}</span>@endif
                                    @if($f->created_at)<span class="date">{{ $f->created_at->diffForHumans(['short' => true]) }}</span>@endif
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>

                {{-- Footer: descarga y cierre --}}
                <div class="bv-files-footer">
                    <button type="button" class="bv-files-dl-btn" id="bv-files-dl-btn">
                        <i class="fas fa-download me-1"></i>Descargar selección
                    </button>
                    <button type="button" class="bv-files-close-btn" id="bv-files-close-btn">
                        Cerrar
                    </button>
                </div>

            @endif
        </div>

        {{-- ── Tab: Tickets (slot del módulo HelpdeskTickets) ── --}}
        @if($rpTicketsEnabled)
            @include('helpdesktickets::inbox-slots.right-panel-tickets-tab', ['rpTickets' => $rpTickets])
        @endif

        {{-- ── Tab: Anteriores ── --}}
        <div class="bv-right-tab-content bv-tab-hidden" data-bv-tab-content="previous">
            @php
                $rpPrevious = collect();
                if ($rpCust) {
                    $rpPrevious = \Modules\Helpdesk\Models\Conversation::where('customer_id', $rpCust->id)
                        ->where('id', '!=', $rpConvo?->id)
                        ->with(['status', 'assignee', 'inbox'])
                        ->latest('last_message_at')
                        ->limit(20)
                        ->get();
                }
                $prevChannelIcons = [
                    'whatsapp'  => ['icon' => 'fab fa-whatsapp',     'color' => '#25d366'],
                    'facebook'  => ['icon' => 'fab fa-facebook-f',   'color' => '#1877f2'],
                    'instagram' => ['icon' => 'fab fa-instagram',    'color' => '#e4405f'],
                    'email'     => ['icon' => 'far fa-envelope',     'color' => '#52525b'],
                    'twitter'   => ['icon' => 'fab fa-twitter',      'color' => '#1da1f2'],
                    'web'       => ['icon' => 'far fa-comment-dots', 'color' => '#6366f1'],
                ];
            @endphp

            @if($rpPrevious->isEmpty())
                <div class="bv-tab-empty">
                    <i class="fas fa-clock-rotate-left"></i>
                    <div class="bv-tab-empty-title">Sin conversaciones anteriores</div>
                    <div class="bv-tab-empty-sub">Este es el primer contacto del cliente</div>
                </div>
            @else
                {{-- Search --}}
                <div class="bv-prev-search">
                    <i class="fas fa-magnifying-glass"></i>
                    <input type="text" class="bv-prev-search-input" placeholder="Buscar en historial…">
                </div>

                {{-- Filter pills --}}
                @php
                    $prevAll    = $rpPrevious->count();
                    $prevOpen   = $rpPrevious->filter(fn($c) => (bool)($c->status?->is_open ?? true))->count();
                    $prevClosed = $prevAll - $prevOpen;
                @endphp
                <div class="bv-prev-filter-row">
                    <span class="bv-media-pill bv-prev-pill on" data-bv-prev-filter="all">
                        Todas <span class="c">{{ $prevAll }}</span>
                    </span>
                    <span class="bv-media-pill bv-prev-pill" data-bv-prev-filter="open">
                        Abiertas <span class="c">{{ $prevOpen }}</span>
                    </span>
                    <span class="bv-media-pill bv-prev-pill" data-bv-prev-filter="closed">
                        Cerradas <span class="c">{{ $prevClosed }}</span>
                    </span>
                </div>

                {{-- Conversation cards --}}
                <div class="bv-prev-list" id="bvPrevList">
                    @foreach($rpPrevious as $prev)
                        @php
                            $ch         = $prevChannelIcons[$prev->channel ?? 'web'] ?? $prevChannelIcons['web'];
                            $isOpen     = (bool)($prev->status?->is_open ?? true);
                            $statusName = $prev->status?->name ?? 'Abierta';
                            $custName   = $rpCust?->name ?? 'Cliente';
                            $initials   = mb_strtoupper(
                                collect(preg_split('/\s+/', trim($custName)))
                                    ->take(2)->map(fn($w) => mb_substr($w,0,1))->implode('')
                            );
                            $msgCount   = $prev->messages_count ?? 0;
                            $dateLabel  = optional($prev->last_message_at ?? $prev->created_at)->diffForHumans(['short' => true]) ?? '—';
                            $preview    = $prev->last_message ?? '';
                            if (!$preview && $prev->subject) { $preview = $prev->subject; }
                        @endphp
                        <button class="bv-conv-card"
                                data-bv-prev-open="{{ $isOpen ? '1' : '0' }}"
                                data-bv-prev-text="{{ strtolower($statusName . ' ' . ($prev->subject ?? '')) }}"
                                data-conv-id="{{ $prev->id }}"
                                data-conv-subject="{{ e($prev->subject ?? 'Conversación') }}"
                                data-conv-status="{{ e($statusName) }}"
                                data-conv-is-open="{{ $isOpen ? '1' : '0' }}"
                                data-conv-channel="{{ $prev->channel ?? 'web' }}"
                                data-viewer-url="{{ route('manager.helpdesk.conversations.viewer-items', $prev->id) }}">
                            <div class="bv-conv-av">
                                {{ $initials ?: '?' }}
                                <span class="bv-ch-badge">
                                    <i class="{{ $ch['icon'] }}"></i>
                                </span>
                            </div>
                            <div class="bv-conv-body">
                                <div class="bv-conv-head">
                                    <span class="bv-conv-nm">{{ $custName }}</span>
                                    <span class="bv-conv-time">{{ $dateLabel }}</span>
                                </div>
                                @if($preview)
                                    <div class="bv-conv-preview">{{ \Illuminate\Support\Str::limit($preview, 80) }}</div>
                                @endif
                                <div class="bv-conv-foot">
                                    @if($msgCount > 0)
                                        <span class="bv-conv-seg">
                                            <i class="fas fa-message"></i> {{ $msgCount }} mensaje{{ $msgCount !== 1 ? 's' : '' }}
                                        </span>
                                    @endif
                                    <span class="bv-conv-status-badge {{ $isOpen ? 'open' : '' }}">
                                        {{ $statusName }}
                                    </span>
                                </div>
                            </div>
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- ── Tab: Actividad ── --}}
        <div class="bv-right-tab-content bv-tab-hidden" data-bv-tab-content="activity">
            @if($rpEvents->isEmpty())
                <div class="bv-tab-empty">
                    <i class="fas fa-clock-rotate-left"></i>
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

        {{-- ── Tab: Tecnología ── --}}
        @if($rpShowTechnologyTab)
        <div class="bv-right-tab-content bv-tab-hidden" data-bv-tab-content="technology">

            @if(! $rpWidgetSession)
                {{-- Empty state — web channel but no session recorded yet --}}
                <div class="bv-tab-empty">
                    <i class="fas fa-laptop"></i>
                    <div class="bv-tab-empty-title">Sin datos de sesión</div>
                    <div class="bv-tab-empty-sub">
                        Los datos del dispositivo (IP, navegador, sistema operativo) y las páginas visitadas aparecerán aquí cuando el visitante navegue por tu sitio con el widget cargado.
                    </div>
                </div>
            @else

            {{-- Información del dispositivo --}}
            <div class="bv-right-section">
                <div class="bv-right-section-head">
                    <span class="bv-right-section-title"><i class="fas fa-laptop bv-section-icon"></i> Información del dispositivo</span>
                </div>

                @php
                    $rpDevice = $rpWidgetSession?->device ?? [];
                    $rpBrowser = $rpDevice['browser'] ?? null;
                    $rpOs = $rpDevice['os'] ?? null;
                    $rpUserAgent = $rpDevice['user_agent'] ?? null;
                    $rpDeviceType = match (true) {
                        is_string($rpUserAgent) && (str_contains($rpUserAgent, 'iPhone') || str_contains($rpUserAgent, 'Android')) => 'mobile',
                        is_string($rpUserAgent) && str_contains($rpUserAgent, 'iPad') => 'tablet',
                        default => 'desktop',
                    };
                @endphp

                <div class="bv-right-row">
                    <span class="lbl">IP address</span>
                    <span class="val">
                        {{ $rpWidgetSession->ip_address ?? '—' }}
                        @if($rpWidgetSession->ip_address)
                            <small class="text-muted ms-1">(anonimizada)</small>
                        @endif
                    </span>
                </div>

                @if($rpWidgetSession->country_code)
                <div class="bv-right-row">
                    <span class="lbl">País</span>
                    <span class="val">{{ $rpWidgetSession->country_code }}</span>
                </div>
                @endif

                <div class="bv-right-row">
                    <span class="lbl">Plataforma</span>
                    <span class="val">{{ $rpOs ?? 'Unknown' }}</span>
                </div>

                <div class="bv-right-row">
                    <span class="lbl">Navegador</span>
                    <span class="val">{{ $rpBrowser ?? 'Unknown' }}</span>
                </div>

                <div class="bv-right-row">
                    <span class="lbl">Dispositivo</span>
                    <span class="val">
                        @if($rpDeviceType === 'mobile')
                            <i class="fas fa-mobile-screen me-1"></i> Móvil
                        @elseif($rpDeviceType === 'tablet')
                            <i class="fas fa-tablet-screen-button me-1"></i> Tablet
                        @else
                            <i class="fas fa-desktop me-1"></i> Desktop
                        @endif
                    </span>
                </div>

                @if($rpWidgetSession->started_at)
                <div class="bv-right-row">
                    <span class="lbl">Sesión iniciada</span>
                    <span class="val">{{ $rpWidgetSession->started_at->diffForHumans() }}</span>
                </div>
                @endif

                @if($rpWidgetSession->last_activity_at)
                <div class="bv-right-row">
                    <span class="lbl">Últ. actividad</span>
                    <span class="val">{{ $rpWidgetSession->last_activity_at->diffForHumans() }}</span>
                </div>
                @endif

                @if($rpWidgetSession->time_on_site ?? null)
                <div class="bv-right-row">
                    <span class="lbl">Tiempo en sitio</span>
                    <span class="val">{{ \Carbon\CarbonInterval::seconds($rpWidgetSession->time_on_site)->cascade()->forHumans(['short' => true]) }}</span>
                </div>
                @endif

                @if($rpWidgetSession->referrer)
                <div class="bv-right-row">
                    <span class="lbl">Referrer</span>
                    <span class="val bv-right-val-sm" title="{{ $rpWidgetSession->referrer }}">
                        {{ \Illuminate\Support\Str::limit($rpWidgetSession->referrer, 40) }}
                    </span>
                </div>
                @endif
            </div>

            {{-- Página actual — URL en la que está el visitante ahora --}}
            @php
                $rpCurrentUrl = $rpWidgetSession->current_url ?? null;
                $rpLastActivity = $rpWidgetSession->last_activity_at;
                $rpIsLive = $rpLastActivity && $rpLastActivity->gt(now()->subMinutes(2));
                $rpCurrentPath = null;
                $rpCurrentHost = null;
                if ($rpCurrentUrl) {
                    $rpParsed = parse_url($rpCurrentUrl);
                    $rpCurrentHost = $rpParsed['host'] ?? null;
                    $rpCurrentPath = ($rpParsed['path'] ?? '/').(isset($rpParsed['query']) ? '?'.$rpParsed['query'] : '');
                }
            @endphp
            @if($rpCurrentUrl)
            <div class="bv-right-section bv-current-page-section">
                <div class="bv-right-section-head">
                    <span class="bv-right-section-title">
                        <i class="fas fa-location-dot bv-section-icon"></i>
                        Página actual
                        @if($rpIsLive)
                            <span class="bv-current-page-pulse" title="Visitante activo ahora">
                                <span class="bv-pulse-dot"></span>
                                Viendo ahora
                            </span>
                        @else
                            <span class="bv-current-page-idle" title="Última actividad {{ $rpLastActivity?->diffForHumans() }}">
                                Última vista {{ $rpLastActivity?->diffForHumans() }}
                            </span>
                        @endif
                    </span>
                </div>

                <div class="bv-current-page-card">
                    @if($rpCurrentHost)
                        <div class="bv-current-page-host">
                            <i class="fas fa-globe"></i>
                            {{ $rpCurrentHost }}
                        </div>
                    @endif
                    <a href="{{ $rpCurrentUrl }}" target="_blank" rel="noopener noreferrer"
                       class="bv-current-page-path"
                       title="{{ $rpCurrentUrl }}">
                        {{ \Illuminate\Support\Str::limit($rpCurrentPath ?? $rpCurrentUrl, 80) }}
                        <i class="fas fa-arrow-up-right-from-square bv-current-page-ext"></i>
                    </a>
                </div>
            </div>
            @endif

            {{-- Páginas visitadas — agrupadas por fecha + duración entre visitas --}}
            @php
                // Group page views by date label (Hoy / Ayer / fecha) and compute
                // duration between consecutive views so the agent sees how long
                // the visitor stayed on each page.
                $rpPagesByDate = $rpVisitedPages->groupBy(function ($page) {
                    if (! $page->viewed_at) {
                        return 'Sin fecha';
                    }
                    if ($page->viewed_at->isToday()) {
                        return 'Hoy';
                    }
                    if ($page->viewed_at->isYesterday()) {
                        return 'Ayer';
                    }
                    return $page->viewed_at->translatedFormat('D, d M');
                });

                // Reverse-engineer duration: the chronologically next page view
                // (i.e., the page view BEFORE this one in our DESC list) marks
                // when the visitor left this page. Since list is DESC by viewed_at,
                // duration[i] = viewed_at[i-1] - viewed_at[i].
                $rpPagesWithDuration = $rpVisitedPages->map(function ($page, $i) use ($rpVisitedPages) {
                    $page->_duration_seconds = null;
                    if ($i > 0 && $page->viewed_at && $rpVisitedPages[$i - 1]->viewed_at) {
                        $page->_duration_seconds = $rpVisitedPages[$i - 1]->viewed_at->diffInSeconds($page->viewed_at);
                    }
                    return $page;
                });

                $rpHostName = $rpVisitedPages->isNotEmpty()
                    ? parse_url($rpVisitedPages->first()->url ?? '', PHP_URL_HOST)
                    : null;
            @endphp

            <div class="bv-right-section">
                <div class="bv-right-section-head">
                    <span class="bv-right-section-title">
                        <i class="fas fa-route bv-section-icon"></i> Páginas visitadas
                        <span class="badge bg-light-secondary text-secondary ms-2">{{ $rpVisitedPages->count() }}</span>
                    </span>
                    @if($rpHostName)
                        <span class="bv-pages-host" title="{{ $rpHostName }}">
                            <img src="https://www.google.com/s2/favicons?domain={{ $rpHostName }}&sz=32" alt="" width="14" height="14" loading="lazy">
                            <span>{{ $rpHostName }}</span>
                        </span>
                    @endif
                    <button type="button"
                            class="bv-right-section-edit ms-2"
                            id="bv-pages-refresh"
                            title="Refrescar páginas visitadas"
                            data-conv-id="{{ $rpConvo->id }}">
                        <i class="fas fa-arrows-rotate"></i>
                    </button>
                </div>

                @if($rpVisitedPages->isEmpty())
                    <div class="bv-tab-empty bv-tab-empty-sm">
                        <i class="fas fa-route"></i>
                        <div class="bv-tab-empty-sub">Sin páginas registradas</div>
                    </div>
                @else
                    @php $rpPageTotal = $rpVisitedPages->count(); $rpPageIdx = 0; @endphp
                    <div class="bv-pages-timeline" id="bv-pages-timeline">
                        @foreach($rpPagesByDate as $dateLabel => $pagesGroup)
                            <div class="bv-pages-day-label" data-bv-day-start="{{ $rpPageIdx }}">{{ $dateLabel }}</div>
                            @foreach($pagesGroup as $page)
                                @php
                                    $pageHost = parse_url($page->url ?? '', PHP_URL_HOST);
                                    $pagePath = parse_url($page->url ?? '', PHP_URL_PATH) ?: '/';
                                    $pageQuery = parse_url($page->url ?? '', PHP_URL_QUERY);
                                    $pageHash  = parse_url($page->url ?? '', PHP_URL_FRAGMENT);
                                    $pageDisplayPath = $pagePath.($pageQuery ? '?'.$pageQuery : '').($pageHash ? '#'.$pageHash : '');
                                    $rpVisitNum = $rpPageTotal - $rpPageIdx;
                                @endphp
                                <a href="{{ $page->url }}" target="_blank" rel="noopener"
                                   class="bv-page-item{{ $rpPageIdx >= 10 ? ' bv-page-collapsed' : '' }}"
                                   data-bv-page-idx="{{ $rpPageIdx }}"
                                   title="{{ $page->url }}">
                                    <div class="bv-page-num">{{ $rpVisitNum }}</div>
                                    <div class="bv-page-icon">
                                        <img src="https://www.google.com/s2/favicons?domain={{ $pageHost }}&sz=32" alt="" width="16" height="16" loading="lazy" onerror="this.style.display='none'">
                                    </div>
                                    <div class="bv-page-body">
                                        @if($page->title)
                                            <div class="bv-page-title">{{ \Illuminate\Support\Str::limit($page->title, 55) }}</div>
                                        @endif
                                        <div class="bv-page-path">{{ \Illuminate\Support\Str::limit($pageDisplayPath, 55) }}</div>
                                        <div class="bv-page-meta">
                                            @if($page->viewed_at)
                                                <span class="bv-page-time">
                                                    <i class="far fa-clock"></i> {{ $page->viewed_at->translatedFormat('H:i') }}
                                                </span>
                                            @endif
                                            @if($page->_duration_seconds !== null && $page->_duration_seconds > 0)
                                                <span class="bv-page-duration" title="Tiempo en esta página">
                                                    <i class="fas fa-stopwatch"></i>
                                                    @if($page->_duration_seconds < 60)
                                                        {{ $page->_duration_seconds }}s
                                                    @elseif($page->_duration_seconds < 3600)
                                                        {{ floor($page->_duration_seconds / 60) }}m {{ $page->_duration_seconds % 60 }}s
                                                    @else
                                                        {{ floor($page->_duration_seconds / 3600) }}h {{ floor(($page->_duration_seconds % 3600) / 60) }}m
                                                    @endif
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                    <i class="fas fa-arrow-up-right-from-square bv-page-ext"></i>
                                </a>
                                @php $rpPageIdx++; @endphp
                            @endforeach
                        @endforeach

                        @if($rpPageTotal > 10)
                            <button type="button" class="bv-pages-show-more" id="bv-pages-show-more"
                                    data-shown="10" data-total="{{ $rpPageTotal }}">
                                <i class="fas fa-chevron-down"></i>
                                Mostrar <span id="bv-pages-show-more-count">{{ min(100, $rpPageTotal - 10) }}</span> más
                                <span class="bv-pages-show-more-total">({{ $rpPageTotal - 10 }} restantes)</span>
                            </button>
                        @endif
                    </div>
                @endif
            </div>

            @endif {{-- /$rpWidgetSession --}}

        </div>
        @endif

        {{-- ── Tab: Pantalla (live view + screen share) ── --}}
        @if($rpShowAssistTab ?? false)
        <div class="bv-right-tab-content bv-tab-hidden" data-bv-tab-content="assist"
             data-conversation-id="{{ $rpConvo->id }}"
             data-enable-live-view="{{ $rpEnableLiveView ? '1' : '0' }}"
             data-enable-screen-share="{{ $rpEnableScreenShare ? '1' : '0' }}">

            @if($rpEnableLiveView)
            <div class="bv-right-section">
                <div class="bv-right-section-head">
                    <span class="bv-right-section-title"><i class="fas fa-eye bv-section-icon"></i> Live view</span>
                    <span class="bv-assist-status badge bg-secondary" id="hd-liveview-status-{{ $rpConvo->id }}">Esperando…</span>
                </div>
                <div class="hd-liveview-frame">
                    <div id="hd-liveview-player-{{ $rpConvo->id }}" class="hd-liveview-player">
                        <div class="hd-liveview-empty text-muted small p-3 text-center">
                            El visitante aún no ha aceptado compartir su actividad.
                        </div>
                    </div>
                    <button type="button"
                            class="hd-liveview-expand"
                            id="hd-liveview-expand-{{ $rpConvo->id }}"
                            title="Ver en pantalla completa">
                        <i class="fas fa-expand"></i>
                    </button>
                </div>
                <div class="small text-muted mt-2">
                    Las contraseñas y campos sensibles se enmascaran automáticamente.
                </div>
            </div>
            @endif

            @if($rpEnableScreenShare)
            <div class="bv-right-section">
                <div class="bv-right-section-head">
                    <span class="bv-right-section-title"><i class="fas fa-display bv-section-icon"></i> Pantalla compartida</span>
                </div>
                <div class="hd-webrtc-wrap">
                    <video id="hd-webrtc-video-{{ $rpConvo->id }}" class="hd-webrtc-video" autoplay muted playsinline></video>
                    <div class="hd-webrtc-empty text-muted small p-3 text-center" id="hd-webrtc-empty-{{ $rpConvo->id }}">
                        Esperando que el visitante comparta su pantalla.
                    </div>
                    <button type="button"
                            class="hd-liveview-expand"
                            id="hd-webrtc-expand-{{ $rpConvo->id }}"
                            title="Ver pantalla completa">
                        <i class="fas fa-expand"></i>
                    </button>
                </div>
                <div class="d-flex gap-2 mt-2">
                    <button type="button"
                            class="btn btn-sm btn-primary"
                            id="hd-webrtc-request-{{ $rpConvo->id }}"
                            data-request-url="{{ route('manager.helpdesk.conversations.webrtc.request', $rpConvo) }}">
                        <i class="fas fa-hand-pointer me-1"></i> Solicitar pantalla
                    </button>
                    <button type="button"
                            class="btn btn-sm btn-outline-secondary"
                            id="hd-webrtc-end-{{ $rpConvo->id }}"
                            data-end-url="{{ route('manager.helpdesk.conversations.webrtc.end', $rpConvo) }}">
                        <i class="fas fa-circle-stop me-1"></i> Finalizar pantalla
                    </button>
                </div>
            </div>
            @endif
        </div>
        @endif

        {{-- ── Tab: Cliente 360 ── --}}
        @if($rpCust)
        <div class="bv-right-tab-content bv-tab-hidden" data-bv-tab-content="customer-360">
            @include('helpdesk::helpdesk.conversations.partials._customer-360', ['conversation' => $rpConvo])
        </div>
        @endif

        @if($rpHasPs)
        <div class="bv-right-tab-content bv-tab-hidden" data-bv-tab-content="ps-orders" id="bv-ps-orders">
            <div class="bv-tab-loading"><i class="fas fa-spinner fa-spin"></i> Cargando pedidos...</div>
        </div>
        <div class="bv-right-tab-content bv-tab-hidden" data-bv-tab-content="ps-returns" id="bv-ps-returns">
            <div class="bv-tab-loading"><i class="fas fa-spinner fa-spin"></i> Cargando devoluciones...</div>
        </div>
        <div class="bv-right-tab-content bv-tab-hidden" data-bv-tab-content="ps-vouchers" id="bv-ps-vouchers">
            <div class="bv-tab-loading"><i class="fas fa-spinner fa-spin"></i> Cargando cupones...</div>
        </div>
        <div class="bv-right-tab-content bv-tab-hidden" data-bv-tab-content="ps-addresses" id="bv-ps-addresses">
            <div class="bv-tab-loading"><i class="fas fa-spinner fa-spin"></i> Cargando direcciones...</div>
        </div>
        @endif

        @if($rpHasErp)
        <div class="bv-right-tab-content bv-tab-hidden" data-bv-tab-content="erp-orders" id="bv-erp-orders">
            <div class="bv-tab-loading"><i class="fas fa-spinner fa-spin"></i> Cargando gestión...</div>
        </div>
        <div class="bv-right-tab-content bv-tab-hidden" data-bv-tab-content="erp-finance" id="bv-erp-finance">
            <div class="bv-tab-loading"><i class="fas fa-spinner fa-spin"></i> Cargando finanzas...</div>
        </div>
        <div class="bv-right-tab-content bv-tab-hidden" data-bv-tab-content="erp-loyalty" id="bv-erp-loyalty">
            <div class="bv-tab-loading"><i class="fas fa-spinner fa-spin"></i> Cargando fidelización...</div>
        </div>
        @endif

        {{-- ── Tab: Emails enviados ── --}}
        @if(helpdesk_feature_enabled('email'))
        <div class="bv-right-tab-content bv-tab-hidden" data-bv-tab-content="emails" id="bv-emails-tab"
             data-conv-id="{{ $rpConvo?->id ?? '' }}">

            {{-- Header panel --}}
            <div class="bv-em-tab-head">
                <div class="bv-tk-panel-head">
                    <span class="bv-tk-num" id="rpEmCount">—</span>
                    <div class="bv-tk-meta">
                        <span class="bv-tk-lbl">Emails</span>
                        <span class="bv-tk-sub" id="rpEmSub">—</span>
                    </div>
                    <button class="bv-tk-add-btn tt" data-tt="Nuevo email" data-bv-modal="email">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
            </div>

            {{-- Filter pills --}}
            <div class="bv-em-filter-row" id="rpEmFilterRow" style="display:none">
                <span class="bv-media-pill bv-em-tab-pill on" data-rp-em-filter="all">
                    Todos <span class="c" id="rpEmCountAll">0</span>
                </span>
                <span class="bv-media-pill bv-em-tab-pill" data-rp-em-filter="sent">
                    Enviados <span class="c" id="rpEmCountSent">0</span>
                </span>
                <span class="bv-media-pill bv-em-tab-pill" data-rp-em-filter="failed">
                    Fallidos <span class="c" id="rpEmCountFailed">0</span>
                </span>
            </div>

            {{-- List --}}
            <div class="bv-em-tab-list" id="rpEmList">
                <div class="bv-em-loading"><i class="fas fa-spinner fa-spin"></i></div>
            </div>

        </div>
        @endif

    </div>
@endif
</aside>

<script>
(function () {
    var btn = document.getElementById('bv-pages-show-more');
    if (!btn) return;

    btn.addEventListener('click', function () {
        var shown  = parseInt(btn.dataset.shown, 10);
        var total  = parseInt(btn.dataset.total, 10);
        var reveal = Math.min(100, total - shown);
        var items  = document.querySelectorAll('#bv-pages-timeline .bv-page-collapsed');
        var revealed = 0;

        for (var i = 0; i < items.length && revealed < reveal; i++) {
            items[i].classList.remove('bv-page-collapsed');
            revealed++;
        }

        shown += revealed;
        btn.dataset.shown = shown;
        var remaining = total - shown;

        if (remaining <= 0) {
            btn.remove();
        } else {
            var next = Math.min(100, remaining);
            document.getElementById('bv-pages-show-more-count').textContent = next;
            btn.querySelector('.bv-pages-show-more-total').textContent = '(' + remaining + ' restantes)';
        }

        // Hide day labels whose items are all still collapsed
        document.querySelectorAll('#bv-pages-timeline .bv-pages-day-label').forEach(function (label) {
            var next = label.nextElementSibling;
            var hasVisible = false;
            while (next && !next.classList.contains('bv-pages-day-label') && !next.classList.contains('bv-pages-show-more')) {
                if (!next.classList.contains('bv-page-collapsed')) { hasVisible = true; break; }
                next = next.nextElementSibling;
            }
            label.style.display = hasVisible ? '' : 'none';
        });
    });

    // Initial pass: hide day labels that have no visible items (all collapsed)
    document.querySelectorAll('#bv-pages-timeline .bv-pages-day-label').forEach(function (label) {
        var next = label.nextElementSibling;
        var hasVisible = false;
        while (next && !next.classList.contains('bv-pages-day-label') && !next.classList.contains('bv-pages-show-more')) {
            if (!next.classList.contains('bv-page-collapsed')) { hasVisible = true; break; }
            next = next.nextElementSibling;
        }
        if (!hasVisible) label.style.display = 'none';
    });

}());

(function () {
    // Refresh button — re-fetches the full Technology tab content (device info,
    // current page and visited pages) without a full page reload.
    // Uses event delegation on the aside so the handler survives innerHTML replacement.
    var bvAside = document.querySelector('.bv-right');
    if (!bvAside) return;

    bvAside.addEventListener('click', async function (e) {
        var btn = e.target.closest('#bv-pages-refresh');
        if (!btn) return;

        var icon = btn.querySelector('i');
        btn.disabled = true;
        if (icon) icon.classList.add('fa-spin');
        try {
            var res = await fetch(window.location.href, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'text/html' },
                credentials: 'same-origin',
            });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            var html = await res.text();
            var doc = new DOMParser().parseFromString(html, 'text/html');

            var freshTab = doc.querySelector('[data-bv-tab-content="technology"]');
            var oldTab = document.querySelector('[data-bv-tab-content="technology"]');
            if (freshTab && oldTab) {
                oldTab.innerHTML = freshTab.innerHTML;
            }

            if (typeof window.toastr !== 'undefined') {
                window.toastr.success('Datos de sesión actualizados');
            }
        } catch (e) {
            if (typeof window.toastr !== 'undefined') {
                window.toastr.error('No se pudo refrescar');
            }
        } finally {
            btn.disabled = false;
            if (icon) icon.classList.remove('fa-spin');
        }
    });
}());
</script>

@if(($rpShowTechnologyTab ?? false) && $rpConvo)
<script>
(function () {
    var convId = {{ (int) $rpConvo->id }};

    // Resolve Echo asynchronously (it may load after this script runs).
    function waitForEcho(cb) {
        if (typeof window.Echo !== 'undefined' && window.Echo) {
            return cb();
        }
        var tries = 0;
        var iv = setInterval(function () {
            tries++;
            if (typeof window.Echo !== 'undefined' && window.Echo) {
                clearInterval(iv);
                cb();
            } else if (tries > 60) {
                clearInterval(iv);
            }
        }, 250);
    }

    waitForEcho(function () {
        window.Echo.private('helpdesk.conversation.' + convId)
            .listen('.widget.session.updated', function (data) {
                // Update "Página actual" section in real time.
                var section = document.querySelector('.bv-current-page-section');

                var url = data.current_url;
                if (!url) return;

                // Parse host + path from the new URL.
                var parsed;
                try { parsed = new URL(url); } catch (e) { return; }
                var host = parsed.hostname;
                var path = parsed.pathname + (parsed.search || '');

                if (section) {
                    // Update host label.
                    var hostEl = section.querySelector('.bv-current-page-host');
                    if (hostEl) { hostEl.lastChild.textContent = host; }

                    // Update link: href + visible text.
                    var linkEl = section.querySelector('.bv-current-page-path');
                    if (linkEl) {
                        linkEl.href = url;
                        linkEl.title = url;
                        var textNode = linkEl.firstChild;
                        var truncated = path.length > 80 ? path.slice(0, 77) + '...' : path;
                        if (textNode && textNode.nodeType === Node.TEXT_NODE) {
                            textNode.textContent = truncated + ' ';
                        }
                    }

                    // Switch pulse indicator to "Viendo ahora".
                    var idle = section.querySelector('.bv-current-page-idle');
                    if (idle) {
                        idle.className = 'bv-current-page-pulse';
                        idle.title = 'Visitante activo ahora';
                        idle.innerHTML = '<span class="bv-pulse-dot"></span>Viendo ahora';
                    }
                } else {
                    // Section doesn't exist yet (no current_url on initial load) — do a
                    // lightweight fetch-replace so the full section renders server-side.
                    var techTab = document.querySelector('[data-bv-tab-content="technology"]');
                    if (!techTab) return;

                    fetch(window.location.href, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'text/html' },
                        credentials: 'same-origin',
                    }).then(function (res) {
                        return res.ok ? res.text() : Promise.reject(res.status);
                    }).then(function (html) {
                        var doc = new DOMParser().parseFromString(html, 'text/html');
                        var fresh = doc.querySelector('[data-bv-tab-content="technology"]');
                        if (fresh) { techTab.innerHTML = fresh.innerHTML; }
                    }).catch(function () {});
                }
            });
    });
}());
</script>
@endif

@if(($rpShowAssistTab ?? false) && $rpConvo)
<style>
.hd-liveview-frame {
    position: relative;
}
.hd-liveview-player {
    background: #1a1a1a;
    border-radius: 6px;
    overflow: hidden;
    min-height: 240px;
    position: relative;
}
.hd-liveview-player .replayer-wrapper {
    background: #fff !important;
}
.hd-liveview-empty,
.hd-webrtc-empty {
    background: #f8f9fa;
    border-radius: 6px;
}
.hd-webrtc-wrap {
    position: relative;
    background: #1a1a1a;
    border-radius: 6px;
    overflow: hidden;
    min-height: 200px;
}
.hd-webrtc-video {
    width: 100%;
    display: block;
    background: #000;
    max-height: 320px;
}
.hd-webrtc-video:not([srcObject]) + .hd-webrtc-empty,
.hd-webrtc-empty {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
}
.hd-webrtc-video[data-streaming="1"] + .hd-webrtc-empty {
    display: none;
}
.hd-liveview-expand {
    position: absolute;
    top: 8px;
    right: 8px;
    width: 32px;
    height: 32px;
    border-radius: 6px;
    border: 0;
    background: rgba(0, 0, 0, 0.55);
    color: #fff;
    font-size: 12px;
    cursor: pointer;
    backdrop-filter: blur(4px);
    transition: background 0.15s;
    z-index: 5;
}
.hd-liveview-expand:hover {
    background: rgba(0, 0, 0, 0.8);
}

/* ── Fullscreen modal ───────────────────────────────────────── */
.hd-liveview-modal {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.85);
    backdrop-filter: blur(6px);
    -webkit-backdrop-filter: blur(6px);
    z-index: 10800;
    display: none;
    flex-direction: column;
    padding: 32px;
    animation: hd-fade-in 0.18s ease-out;
}
.hd-liveview-modal.is-open {
    display: flex;
}
@keyframes hd-fade-in {
    from { opacity: 0; }
    to { opacity: 1; }
}
.hd-liveview-modal-head {
    display: flex;
    align-items: center;
    gap: 12px;
    color: #fff;
    margin-bottom: 16px;
}
.hd-liveview-modal-head .title {
    font-weight: 600;
    font-size: 16px;
}
.hd-liveview-modal-head .ml {
    flex: 1;
}
.hd-liveview-modal-close {
    background: rgba(255, 255, 255, 0.12);
    border: 0;
    color: #fff;
    width: 36px;
    height: 36px;
    border-radius: 8px;
    cursor: pointer;
    transition: background 0.15s;
}
.hd-liveview-modal-close:hover {
    background: rgba(255, 255, 255, 0.22);
}
.hd-liveview-modal-body {
    flex: 1;
    background: #fff;
    border-radius: 12px;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 25px 60px rgba(0, 0, 0, 0.45);
}
.hd-liveview-modal-body video {
    max-width: 100%;
    max-height: 100%;
    display: block;
}
.hd-liveview-modal-body .rr-player {
    max-width: 100%;
    max-height: 100%;
}
</style>

<div class="hd-liveview-modal" id="hd-liveview-modal-{{ $rpConvo->id }}" role="dialog" aria-modal="true">
    <div class="hd-liveview-modal-head">
        <i class="fas fa-eye"></i>
        <span class="title" id="hd-liveview-modal-title-{{ $rpConvo->id }}">Vista del visitante</span>
        <span class="ml"></span>
        <span class="bv-assist-status badge bg-secondary" id="hd-liveview-modal-status-{{ $rpConvo->id }}">Cargando…</span>
        <button type="button" class="hd-liveview-modal-close" id="hd-liveview-modal-close-{{ $rpConvo->id }}" title="Cerrar">
            <i class="fas fa-xmark"></i>
        </button>
    </div>
    <div class="hd-liveview-modal-body" id="hd-liveview-modal-body-{{ $rpConvo->id }}"></div>
</div>
<script>
(function () {
    var conversationId = {{ (int) $rpConvo->id }};
    var liveViewEnabled = {{ $rpEnableLiveView ? 'true' : 'false' }};
    var screenShareEnabled = {{ $rpEnableScreenShare ? 'true' : 'false' }};

    // window.Echo can load asynchronously after this script runs.
    // Poll until it appears (cap at 15s) so we don't miss the bind window.
    function waitForEcho(cb) {
        if (typeof window.Echo !== 'undefined' && window.Echo) {
            return cb();
        }
        var tries = 0;
        var iv = setInterval(function () {
            tries++;
            if (typeof window.Echo !== 'undefined' && window.Echo) {
                clearInterval(iv);
                cb();
            } else if (tries > 60) {
                clearInterval(iv);
                console.warn('[hd-assist] Echo never initialized — live view disabled.');
            }
        }, 250);
    }

    waitForEcho(function () {

    // ── Live view (rrweb player) ─────────────────────────────────
    if (liveViewEnabled) {
        var playerEl = document.getElementById('hd-liveview-player-' + conversationId);
        var statusEl = document.getElementById('hd-liveview-status-' + conversationId);
        var emptyEl = playerEl ? playerEl.querySelector('.hd-liveview-empty') : null;
        var player = null;
        var bufferedEvents = [];

        function setStatus(text, cls) {
            if (statusEl) {
                statusEl.textContent = text;
                statusEl.className = 'bv-assist-status badge ' + cls;
            }
        }

        function ensurePlayer() {
            if (player || !playerEl) {
                return Promise.resolve(player);
            }
            // Load rrweb-player from CDN (no bundler step required for the
            // admin panel — the script is small enough to fetch on demand
            // and only loads when an agent opens the Pantalla tab).
            var cssUrl = 'https://cdn.jsdelivr.net/npm/rrweb-player@1.0.0-alpha.4/dist/style.css';
            var jsUrl = 'https://cdn.jsdelivr.net/npm/rrweb-player@1.0.0-alpha.4/dist/index.mjs';
            if (! document.querySelector('link[data-hd="rrweb-player"]')) {
                var link = document.createElement('link');
                link.rel = 'stylesheet';
                link.href = cssUrl;
                link.dataset.hd = 'rrweb-player';
                document.head.appendChild(link);
            }
            return import(jsUrl).then(function (mod) {
                if (emptyEl) emptyEl.remove();
                var Player = mod.default || mod.Player || mod;
                player = new Player({
                    target: playerEl,
                    props: {
                        events: bufferedEvents.slice(),
                        autoPlay: true,
                        showController: false,
                        liveMode: true,
                    },
                });
                bufferedEvents = [];
                return player;
            }).catch(function (e) {
                console.warn('[hd-assist] rrweb-player load failed', e);
                if (playerEl) {
                    playerEl.innerHTML = '<div class="text-warning small p-3 text-center">No se pudo cargar el reproductor (rrweb-player no disponible).</div>';
                }
            });
        }

        // Fetch backlog first — rrweb requires the initial Meta + FullSnapshot
        // events to render anything. Live mode alone shows a blank frame for
        // any agent that joins after the visitor started recording.
        var historyUrl = "{{ route('manager.helpdesk.conversations.livestream.history', $rpConvo) }}";
        var csrfMeta = document.querySelector('meta[name="csrf-token"]');
        fetch(historyUrl, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfMeta ? csrfMeta.content : '',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        })
            .then(function (r) { return r.ok ? r.json() : { events: [] }; })
            .then(function (data) {
                var historyEvents = data.events || [];
                if (historyEvents.length > 0) {
                    bufferedEvents = bufferedEvents.concat(historyEvents);
                    setStatus('Reproduciendo', 'bg-info');
                    ensurePlayer();
                }
            })
            .catch(function () { /* silent — live mode still works without backlog */ });

        try {
            window.Echo.private('livestream.conversation.' + conversationId)
                .listen('.livestream.batch', function (data) {
                    setStatus('En vivo', 'bg-success');
                    if (player) {
                        (data.events || []).forEach(function (e) { player.addEvent(e); });
                    } else {
                        bufferedEvents = bufferedEvents.concat(data.events || []);
                        ensurePlayer();
                    }
                });
        } catch (e) {
            setStatus('Sin conexión', 'bg-warning');
        }
    }

    // ── WebRTC screen share (agent answers visitor offer) ─────────
    if (screenShareEnabled) {
        var videoEl = document.getElementById('hd-webrtc-video-' + conversationId);
        var emptyWebrtc = document.getElementById('hd-webrtc-empty-' + conversationId);
        var endBtn = document.getElementById('hd-webrtc-end-' + conversationId);
        var peer = null;

        var STUN = [
            { urls: 'stun:stun.l.google.com:19302' },
            { urls: 'stun:stun1.l.google.com:19302' },
        ];

        function postJson(url, data) {
            var token = document.querySelector('meta[name="csrf-token"]');
            return fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token ? token.content : '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(data),
                credentials: 'same-origin',
            });
        }

        function tearDown() {
            try { peer && peer.close(); } catch (e) {}
            peer = null;
            if (videoEl) {
                videoEl.srcObject = null;
                videoEl.removeAttribute('data-streaming');
            }
            if (emptyWebrtc) emptyWebrtc.style.display = '';
        }

        try {
            window.Echo.private('webrtc.conversation.' + conversationId)
                .listen('.webrtc.offer', async function (data) {
                    if (!data || !data.payload || !data.payload.sdp) return;
                    if (peer) tearDown();

                    peer = new RTCPeerConnection({ iceServers: STUN });

                    peer.ontrack = function (event) {
                        if (videoEl && event.streams && event.streams[0]) {
                            videoEl.srcObject = event.streams[0];
                            videoEl.setAttribute('data-streaming', '1');
                            if (emptyWebrtc) emptyWebrtc.style.display = 'none';
                        }
                    };

                    peer.onicecandidate = function (event) {
                        if (event.candidate) {
                            postJson(
                                "{{ route('manager.helpdesk.conversations.webrtc.ice', $rpConvo) }}",
                                { candidate: event.candidate.toJSON() }
                            );
                        }
                    };

                    await peer.setRemoteDescription({ type: 'offer', sdp: data.payload.sdp });
                    var answer = await peer.createAnswer();
                    await peer.setLocalDescription(answer);
                    postJson(
                        "{{ route('manager.helpdesk.conversations.webrtc.answer', $rpConvo) }}",
                        { sdp: answer.sdp || '', type: 'answer' }
                    );
                })
                .listen('.webrtc.ice', function (data) {
                    if (peer && data && data.payload && data.payload.candidate) {
                        try { peer.addIceCandidate(new RTCIceCandidate(data.payload.candidate)); } catch (e) {}
                    }
                })
                .listen('.webrtc.end', function () {
                    tearDown();
                });
        } catch (e) {}

        if (endBtn) {
            endBtn.addEventListener('click', function () {
                postJson(endBtn.dataset.endUrl, {});
                tearDown();
            });
        }

        var requestBtn = document.getElementById('hd-webrtc-request-' + conversationId);
        if (requestBtn) {
            requestBtn.addEventListener('click', function () {
                requestBtn.disabled = true;
                var label = requestBtn.innerHTML;
                requestBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Solicitando…';
                postJson(requestBtn.dataset.requestUrl, {})
                    .then(function () {
                        setTimeout(function () {
                            requestBtn.disabled = false;
                            requestBtn.innerHTML = label;
                        }, 5000);
                    })
                    .catch(function () {
                        requestBtn.disabled = false;
                        requestBtn.innerHTML = label;
                    });
            });
        }
    }

    // ── Fullscreen modal: hosts the player or the WebRTC video ───
    var modalEl = document.getElementById('hd-liveview-modal-' + conversationId);
    var modalBody = document.getElementById('hd-liveview-modal-body-' + conversationId);
    var modalClose = document.getElementById('hd-liveview-modal-close-' + conversationId);
    var modalTitle = document.getElementById('hd-liveview-modal-title-' + conversationId);
    var modalStatus = document.getElementById('hd-liveview-modal-status-' + conversationId);
    var liveExpand = document.getElementById('hd-liveview-expand-' + conversationId);
    var webrtcExpand = document.getElementById('hd-webrtc-expand-' + conversationId);

    var modalOriginalParent = null;
    var modalMovedNode = null;

    function triggerPlayerResize() {
        // rrweb-player listens to window resize internally (Svelte component).
        // Dispatch the event AFTER the move so the canvas re-scales to the
        // new container dimensions.
        try {
            window.dispatchEvent(new Event('resize'));
        } catch (e) { /* noop */ }
        if (player && typeof player.triggerResize === 'function') {
            player.triggerResize();
        }
    }

    function openModal(node, title, statusEl) {
        if (! modalEl || ! modalBody || ! node) return;
        modalOriginalParent = node.parentElement;
        modalMovedNode = node;
        modalBody.innerHTML = '';
        modalBody.appendChild(node);
        if (modalTitle) modalTitle.textContent = title;
        if (modalStatus && statusEl) {
            modalStatus.textContent = statusEl.textContent;
            modalStatus.className = 'bv-assist-status badge ' + (statusEl.className.match(/bg-\w+/)?.[0] || 'bg-secondary');
        }
        modalEl.classList.add('is-open');
        // The player computes scale on mount; force a resize tick so the
        // visitor viewport rescales to the new (larger) container.
        setTimeout(triggerPlayerResize, 60);
        setTimeout(triggerPlayerResize, 250);
    }

    function closeModal() {
        if (! modalEl || ! modalMovedNode || ! modalOriginalParent) {
            modalEl?.classList.remove('is-open');
            return;
        }
        modalOriginalParent.appendChild(modalMovedNode);
        modalEl.classList.remove('is-open');
        modalMovedNode = null;
        modalOriginalParent = null;
        setTimeout(triggerPlayerResize, 60);
    }

    if (liveExpand) {
        liveExpand.addEventListener('click', function () {
            var playerEl = document.getElementById('hd-liveview-player-' + conversationId);
            var statusEl = document.getElementById('hd-liveview-status-' + conversationId);
            openModal(playerEl, 'Live view del visitante', statusEl);
        });
    }
    if (webrtcExpand) {
        webrtcExpand.addEventListener('click', function () {
            var wrap = document.getElementById('hd-webrtc-video-' + conversationId)?.parentElement;
            openModal(wrap, 'Pantalla del visitante', null);
        });
    }
    if (modalClose) {
        modalClose.addEventListener('click', closeModal);
    }
    if (modalEl) {
        modalEl.addEventListener('click', function (e) {
            if (e.target === modalEl) closeModal();
        });
    }
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modalEl && modalEl.classList.contains('is-open')) {
            closeModal();
        }
    });
    });
}());
</script>
@endif

@if(helpdesk_feature_enabled('email'))
@once
@push('scripts')
<script>
(function () {
    var _rpEmAll    = [];
    var _rpEmFilter = 'all';
    var _rpEmLoaded = false;
    var _rpEmConvId = null;

    function listEl() { return document.getElementById('rpEmList'); }

    function renderCards(filter) {
        _rpEmFilter = filter;
        document.querySelectorAll('.bv-em-tab-pill').forEach(function (p) {
            p.classList.toggle('on', p.dataset.rpEmFilter === filter);
        });

        var emails = _rpEmAll.filter(function (e) {
            return filter === 'all' || e.status === filter;
        });

        if (!emails.length) {
            listEl().innerHTML = '<div class="bv-em-empty">' +
                (filter !== 'all' ? 'Sin emails en este estado.' : 'Sin emails enviados.') +
                '</div>';
            return;
        }

        listEl().innerHTML = emails.map(function (e) {
            var sc  = e.status === 'sent' ? 'sent' : (e.status === 'failed' ? 'failed' : 'queued');
            var sl  = e.status_label || e.status;
            var att = e.attachments_count > 0
                ? '<span class="bv-em-att"><i class="fas fa-paperclip"></i> ' + e.attachments_count + '</span>'
                : '';
            var preview = e.preview
                ? '<div class="bv-em-preview">' + $('<span>').text(e.preview).html() + '</div>'
                : '';
            return '<button class="bv-em-card" data-em-uid="' + e.uid + '">' +
                '<div class="bv-em-head">' +
                '<i class="far fa-envelope-open" style="font-size:11px;color:var(--bv-text-muted)"></i>' +
                '<span class="bv-em-to">' + $('<span>').text(e.to).html() + '</span>' +
                '<span class="bv-em-status ' + sc + '">' + $('<span>').text(sl).html() + '</span>' +
                '</div>' +
                '<div class="bv-em-subject">' + $('<span>').text(e.subject).html() + '</div>' +
                preview +
                '<div class="bv-em-foot">' + att +
                '<span class="bv-em-date">' + (e.date_human || '') + '</span>' +
                '</div>' +
            '</button>';
        }).join('');
    }

    function loadEmails(convId) {
        _rpEmConvId = String(convId);
        _rpEmLoaded = false;
        listEl().innerHTML = '<div class="bv-em-loading"><i class="fas fa-spinner fa-spin"></i></div>';
        document.getElementById('rpEmCount').textContent = '—';
        document.getElementById('rpEmSub').textContent   = '—';
        document.getElementById('rpEmFilterRow').style.display = 'none';

        $.ajax({
            url: '/panel/helpdesk/conversations/' + convId + '/emails',
            method: 'GET', dataType: 'json',
            headers: { 'Accept': 'application/json' },
        }).done(function (resp) {
            _rpEmAll = resp.emails || [];
            var counts = resp.counts || {};
            var sent   = counts.sent   || 0;
            var failed = counts.failed || 0;

            document.getElementById('rpEmCount').textContent      = _rpEmAll.length;
            document.getElementById('rpEmCountAll').textContent   = _rpEmAll.length;
            document.getElementById('rpEmCountSent').textContent  = sent;
            document.getElementById('rpEmCountFailed').textContent = failed;

            var queued = (_rpEmAll.length - sent - failed);
            var parts = [];
            if (sent > 0)   { parts.push(sent   + ' enviado'  + (sent   !== 1 ? 's' : '')); }
            if (queued > 0) { parts.push(queued + ' en cola'); }
            if (failed > 0) { parts.push(failed + ' fallido'  + (failed !== 1 ? 's' : '')); }
            document.getElementById('rpEmSub').textContent = parts.length ? parts.join(' · ') : 'ninguno aún';

            if (_rpEmAll.length) {
                document.getElementById('rpEmFilterRow').style.display = '';
            }
            renderCards(_rpEmFilter);
            _rpEmLoaded = true;
        }).fail(function () {
            listEl().innerHTML = '<div class="bv-em-empty">No se pudieron cargar los emails.</div>';
        });
    }

    // Activar tab "emails" → cargar
    $(document).on('click', '[data-bv-tab="emails"]', function () {
        var convId = document.getElementById('bv-emails-tab')?.dataset.convId
            || $('.bv-composer').data('bv-conversation-id');
        if (!convId) { return; }
        if (!_rpEmLoaded || _rpEmConvId !== String(convId)) {
            loadEmails(convId);
        }
    });

    // Recargar al cambiar de conversación
    var tabNode = document.getElementById('bv-emails-tab');
    if (tabNode) {
        (new MutationObserver(function () { _rpEmLoaded = false; }))
            .observe(tabNode, { attributes: true, attributeFilter: ['data-conv-id'] });
    }

    // Click en em-card → abrir viewer
    $(document).on('click', '#rpEmList .bv-em-card', function () {
        var uid = $(this).data('em-uid');
        if (!uid) { return; }
        if (typeof window.openEmailViewer === 'function') {
            window.openEmailViewer(uid);
        }
    });

    // Pills de filtro
    $(document).on('click', '.bv-em-tab-pill', function () {
        renderCards($(this).data('rp-em-filter'));
    });

    // Recargar desde fuera (tras enviar email nuevo)
    window.rpEmReload = function () {
        _rpEmLoaded = false;
        var tab = document.getElementById('bv-emails-tab');
        if (tab && !tab.classList.contains('bv-tab-hidden')) {
            var convId = tab.dataset.convId || $('.bv-composer').data('bv-conversation-id');
            if (convId) { loadEmails(convId); }
        }
    };
}());
</script>
@endpush
@endonce

@once
@push('scripts')
<script>
(function () {
    // ── Previous tab: search + filter ──────────────────────────────
    $(document).on('input', '.bv-prev-search-input', function () {
        var q = $(this).val().toLowerCase();
        $('#bvPrevList .bv-conv-card').each(function () {
            var text = ($(this).data('bv-prev-text') || '') + ' ' + $(this).find('.bv-conv-nm').text().toLowerCase();
            $(this).toggleClass('bv-hidden', q.length > 0 && !text.includes(q));
        });
    });

    $(document).on('click', '.bv-prev-pill', function () {
        var filter = $(this).data('bv-prev-filter');
        $('.bv-prev-pill').removeClass('on');
        $(this).addClass('on');
        $('#bvPrevList .bv-conv-card').each(function () {
            var isOpen = $(this).data('bv-prev-open') === 1 || $(this).data('bv-prev-open') === '1';
            var show = filter === 'all' || (filter === 'open' && isOpen) || (filter === 'closed' && !isOpen);
            $(this).toggleClass('bv-hidden', !show);
        });
    });

    // ── Click conv-card → open conversation viewer ─────────────────
    $(document).on('click', '.bv-conv-card', function () {
        var convId = $(this).data('conv-id');
        if (!convId) { return; }
        window._cvConvId = convId;
        $('[data-bv-modal-name="conversation-viewer"]').addClass('on');
        $('body').css('overflow', 'hidden');
        if (typeof window.loadConversationViewer === 'function') {
            window.loadConversationViewer($(this).data('viewer-url'));
        }
    });

    // ── History modal pills filter ─────────────────────────────────
    $(document).on('click', '.bv-hist-pill', function () {
        var filter = $(this).data('bv-hist-filter');
        $('.bv-hist-pill').removeClass('on');
        $(this).addClass('on');
        $('#histList .bv-conv-card').each(function () {
            var isOpen = $(this).data('bv-prev-open') === 1 || $(this).data('bv-prev-open') === '1';
            var show = filter === 'all' || (filter === 'open' && isOpen) || (filter === 'closed' && !isOpen);
            $(this).toggleClass('bv-hidden', !show);
        });
    });

    $(document).on('input', '#histSearchInput', function () {
        var q = $(this).val().toLowerCase();
        $('#histList .bv-conv-card').each(function () {
            var text = $(this).find('.bv-conv-nm, .bv-conv-preview').text().toLowerCase();
            $(this).toggleClass('bv-hidden', q.length > 0 && !text.includes(q));
        });
    });

    // ── Conversation viewer loader ─────────────────────────────────
    window.loadConversationViewer = function (viewerUrl) {
        $('#cvLoading').removeClass('bv-hidden');
        $('#cvCtxBar, #cvMessages').addClass('bv-hidden');
        $('#cvMessages').empty();

        $.ajax({
            url: viewerUrl,
            method: 'GET',
            dataType: 'json',
            headers: { 'Accept': 'application/json' },
        }).done(function (data) {
            var conv  = data.conversation || {};
            var items = data.items || [];

            var cvSubjectHtml = $('<span>').text(conv.subject || 'Conversación').html();
            var cvChip = conv.id ? '<span class="bv-cv-id-chip">#' + conv.id + '</span>' : '';
            $('#cvModalTitle').html(cvSubjectHtml + cvChip);
            $('#cvCtxAv').text(conv.customer_initials || '?');
            $('#cvCtxNm').text(conv.customer_name || '—');

            var ch = conv.channel_icon ? '<i class="' + conv.channel_icon + '"></i> ' : '';
            $('#cvCtxSub').html(ch + (conv.channel || 'web') + ' · ' + (conv.message_count || 0) + ' mensajes · iniciado el ' + (conv.started_at_formatted || ''));

            var statusCls = conv.is_open ? 'open' : '';
            $('#cvCtxStatus').attr('class', 'bv-cv-ctx-status ' + statusCls).text(conv.status_name || '—');
            $('#cvCtxBar').removeClass('bv-hidden');

            if (window._cvConvId) {
                $('#cvBtnOpen').off('click.cv').on('click.cv', function () {
                    window.open('/panel/helpdesk/conversations?selected=' + window._cvConvId, '_self');
                });
            }

            var html = '';
            items.forEach(function (item) {
                if (item.type === 'day_separator') {
                    html += '<div class="bv-cv-day">' + $('<span>').text(item.label).html() + '</div>';
                    return;
                }
                if (item.is_internal) {
                    html += '<div class="bv-cv-system">' + $('<span>').text(item.body || '').html() + '</div>';
                    return;
                }
                var dirClass = item.is_agent ? 'bv-out' : 'bv-in';
                var avText   = $('<span>').text(item.author_initials || '?').html();
                var bodyHtml = $('<span>').text(item.body || '').html().replace(/\n/g, '<br>');
                html += '<div class="bv-cv-bubble-row ' + dirClass + '">' +
                    '<div class="bv-cv-av-sm">' + avText + '</div>' +
                    '<div class="bv-cv-bubble">' + bodyHtml +
                    '<span class="bv-cv-ts">' + $('<span>').text(item.time_formatted || '').html() + '</span>' +
                    '</div></div>';
            });

            if (!html) {
                html = '<div class="bv-cv-loading-msg">Sin mensajes registrados.</div>';
            }

            $('#cvMessages').html(html).removeClass('bv-hidden');

            var msgs = document.getElementById('cvMessages');
            if (msgs) { msgs.scrollTop = msgs.scrollHeight; }
        }).fail(function () {
            $('#cvLoading').html('<i class="fas fa-triangle-exclamation"></i> No se pudo cargar la conversación.');
        }).always(function () {
            $('#cvLoading').addClass('bv-hidden');
        });
    };

    // ── MutationObserver: reset viewer when modal closes ──────────
    var cvModal = document.querySelector('[data-bv-modal-name="conversation-viewer"]');
    if (cvModal) {
        (new MutationObserver(function (mutations) {
            mutations.forEach(function (m) {
                if (m.attributeName !== 'class') { return; }
                if (!$(m.target).hasClass('on')) {
                    window._cvConvId = null;
                    $('#cvMessages').empty();
                    $('#cvCtxBar, #cvMessages').addClass('bv-hidden');
                    $('#cvLoading').removeClass('bv-hidden').html('<i class="fas fa-spinner fa-spin"></i> Cargando…');
                }
            });
        })).observe(cvModal, { attributes: true });
    }
}());
</script>
@endpush
@endonce
@endif
