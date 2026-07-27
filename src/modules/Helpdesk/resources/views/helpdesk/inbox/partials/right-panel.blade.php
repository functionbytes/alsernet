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
    $_rpHasPs        = helpdesk_prestashop_enabled() && ($_rpIntegrations->contains('platform', 'prestashop') || ! empty($_rpPsId));
    $_rpHasErp       = helpdesk_erp_enabled() && ($_rpIntegrations->contains('platform', 'erp') || ! empty($_rpErpId));
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
    data-customer-name="{{ $_rpCustEarly?->name }}"
    data-customer-email="{{ $_rpEmail }}"
    data-customer-phone="{{ $_rpCustEarly?->phone ?: $_rpCustEarly?->whatsapp_phone }}"
    data-customer-city="{{ $_rpCustEarly?->city }}"
    data-customer-state="{{ $_rpCustEarly?->state }}"
    data-customer-country="{{ $_rpCustEarly?->country }}"
    data-customer-zip="{{ $_rpCustEarly?->postal_code }}"
    data-customer-language="{{ $_rpCustEarly?->language }}"
    data-customer-timezone="{{ $_rpCustEarly?->timezone }}"
    data-customer-notes="{{ $_rpCustEarly?->internal_notes }}"
    data-update-url="{{ $_rpCustEarly ? route('manager.helpdesk.customers.update', $_rpCustEarly) : '' }}"
    data-csrf="{{ csrf_token() }}">
@if(empty($selectedConversationId))
    <div class="bv-right-empty">
        <div class="bv-right-empty-icon">
            <i class="far fa-id-card"></i>
        </div>
        <div class="bv-right-empty-title">{{ __('helpdesk::helpdesk.inbox.right.no_contact') }}</div>
        <div class="bv-right-empty-sub">{{ __('helpdesk::helpdesk.inbox.right.no_contact_hint') }}</div>
    </div>
@else
    @php
        $rpCust   = $selectedConversation?->customer;
        $rpConvo  = $selectedConversation;

        // Auto-deteccion y guardado del vinculo de e-commerce (PrestaShop + gestion).
        // Se saca del camino critico del render: en lugar de llamar a la API externa
        // sincronamente en cada repintado, se despacha un job en cola protegido por un
        // guard de cache para que el sync real corra ~1 vez/hora por cliente.
        if ($rpCust && $rpCust->email
            && \Illuminate\Support\Facades\Cache::add('hd:commerce-sync:'.$rpCust->id, true, 3600)) {
            \Modules\Helpdesk\Jobs\SyncCustomerCommerceJob::dispatch($rpCust);
        }

        // Vinculos ya persistidos (consulta local barata): el panel los muestra de
        // inmediato; el job de arriba refresca en background para la proxima apertura.
        $rpCust?->load('externalIds');

        $rpName   = $rpCust?->name ?? 'Sin nombre';
        $rpInitials = mb_strtoupper(collect(preg_split('/\s+/', trim($rpName)))->take(2)->map(fn($w) => mb_substr($w,0,1))->implode(''));
        $rpSince  = $rpCust?->created_at?->translatedFormat('Y') ?? '—';
        $rpTotal  = (int) ($rpCust?->total_conversations ?? 0);

        // Priority map (same as thread.blade.php)
        $priorityLabels = ['low' => 'Baja', 'normal' => 'Normal', 'high' => 'Alta', 'urgent' => 'Urgente'];
        $priorityColors = ['low' => 'muted', 'normal' => '', 'high' => 'warning', 'urgent' => 'danger'];
        $rpPriority = $rpConvo?->priority ?? 'normal';

        // Status
        $rpStatusName  = $rpConvo?->status?->name  ?? 'Abierta';

        // Tickets (HelpdeskTickets module - optional)
        $rpTickets = collect();
        $rpTicketsEnabled = helpdesk_tickets_enabled();
        if ($rpCust && $rpTicketsEnabled) {
            $rpTickets = app(\Modules\Helpdesk\Contracts\TicketServiceContract::class)
                ->getCustomerTickets($rpCust, 5);
        }

        // Document (Document module - opcional) — LISTA de expedientes del cliente.
        // La lista (cards) se hidrata ligera aqui; el detalle de cada expediente se
        // carga bajo demanda via AJAX desde el tab (DocumentPanelController). El
        // linker mantiene metadata.document_id apuntando al expediente primario
        // para que la importacion desde la galeria del chat siga funcionando.
        $rpDocuments = [];
        $rpHasDocument = false;
        // Disponibilidad del módulo (independiente de si YA hay expedientes
        // vinculados) — gate real de la tab, para poder asignar/crear un
        // expediente nuevo desde una conversación que todavía no tiene ninguno.
        $rpDocumentModuleAvailable = helpdesk_document_enabled() && $rpConvo
            && class_exists(\Modules\HelpdeskDocument\Services\ConversationDocumentLinker::class);
        if ($rpDocumentModuleAvailable) {
            try {
                $rpLinker = app(\Modules\HelpdeskDocument\Services\ConversationDocumentLinker::class);
                $rpDocs = $rpLinker->documentsForConversation($rpConvo);

                if ($rpDocs->isNotEmpty()) {
                    $rpDocuments = app(\Modules\HelpdeskDocument\Services\DocumentPanelPresenter::class)->list($rpDocs);
                    $rpHasDocument = true;

                    // Crea el vínculo si falta, lo re-apunta si quedó roto y
                    // refresca el snapshot informativo si el estado cambió.
                    $rpLinker->syncLink($rpConvo, $rpDocs);
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }

        // Activity events
        $rpEvents = $rpConvo ? $rpConvo->events()->with(['author', 'user'])->latest()->limit(20)->get() : collect();

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

            // Fallback: link by customer_id (solo canal web). Evita una query de
            // WidgetSession por cada cambio de conversación en canales sin widget
            // (WhatsApp/Facebook/Instagram), que es la mayoría en un inbox social.
            if ($rpIsWebChannel && ! $rpWidgetSession && $rpCust) {
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
                        in_array($ext, ['jpg','jpeg','png','gif','webp','svg','bmp','tiff','tif','heic','heif','avif','ico','jfif']) ? 'image'
                        : (in_array($ext, ['mp4','mov','webm','avi','mkv','ogv','3gp','flv','wmv','m4v']) ? 'video'
                        : (in_array($ext, ['mp3','ogg','wav','oga','m4a','aac','flac','opus','wma','aiff']) ? 'audio'
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

        // Platform integrations (Engagement module) — reutiliza la consulta ya
        // hecha en la cabecera del panel ($_rpIntegrations): mismo inbox, mismo
        // is_active, y trae un superset de columnas (id, platform, store_url).
        $rpIntegrations = $_rpIntegrations ?? collect();
        $rpExternalEmail = $rpCust?->email;
        $rpExternalPsId  = $rpCust?->externalIdFor('prestashop') ?? null;
        $rpExternalErpId = $rpCust?->externalIdFor('erp') ?? null;
        // Idem nota arriba: ERP es global, basta con que el customer esté vinculado.
        $rpHasPs  = helpdesk_prestashop_enabled() && ($rpIntegrations->contains('platform', 'prestashop') || ! empty($rpExternalPsId));
        $rpHasErp = helpdesk_erp_enabled() && ($rpIntegrations->contains('platform', 'erp') || ! empty($rpExternalErpId));
    @endphp

    {{-- Banner + Avatar + nombre --}}
    <div class="rsp-banner">
        <div class="av">{{ $rpInitials ?: '?' }}</div>
    </div>
    <div class="rsp-head">
        @if($rpCust)
            <button type="button" class="nm bv-cp-name-btn" data-bv-modal="profile-customer" title="{{ __('helpdesk::helpdesk.inbox.right.view_customer_profile') }}">{{ $rpName }}</button>
        @else
            <div class="nm">{{ $rpName }}</div>
        @endif
        <div class="since">@if($rpTotal >= 5){{ __('helpdesk::helpdesk.inbox.right.vip_prefix') }} @endif{{ __('helpdesk::helpdesk.inbox.right.customer_since', ['year' => $rpSince]) }}</div>
    </div>

    {{-- Acciones rápidas --}}
    <div class="rsp-actions">
        @if(helpdesk_feature_enabled('rp_email'))
        <button type="button" data-bv-modal="email">
            <i class="fa-regular fa-envelope"></i> {{ __('helpdesk::helpdesk.inbox.right.action_email') }}
        </button>
        @endif
        @if(helpdesk_feature_enabled('rp_schedule'))
        <button type="button" data-bv-modal="schedule">
            <i class="fa-regular fa-calendar"></i> {{ __('helpdesk::helpdesk.inbox.right.action_schedule') }}
        </button>
        @endif
        @if(helpdesk_feature_enabled('rp_note'))
        <button type="button" data-bv-modal="note">
            <i class="fa-regular fa-pen-to-square"></i> {{ __('helpdesk::helpdesk.inbox.right.action_note') }}
        </button>
        @endif
        <div class="rsp-more">
            <button type="button" class="rsp-more-toggle" aria-label="{{ __('helpdesk::helpdesk.inbox.right.more_actions') }}" aria-haspopup="menu" aria-expanded="false">
                <i class="fa-solid fa-ellipsis" aria-hidden="true"></i> {{ __('helpdesk::helpdesk.inbox.right.more_label') }}
            </button>
            <div class="bv-more-menu rsp-more-menu" role="menu" aria-label="{{ __('helpdesk::helpdesk.inbox.right.more_actions') }}">
                <button type="button" role="menuitem" data-bv-modal="profile-customer"><i class="fa-regular fa-id-card" aria-hidden="true"></i>{{ __('helpdesk::helpdesk.inbox.right.view_profile') }}</button>
                <button type="button" role="menuitem" data-bv-modal="edit-contact"><i class="fa-solid fa-pen" aria-hidden="true"></i>{{ __('helpdesk::helpdesk.inbox.right.edit_contact') }}</button>
                <button type="button" role="menuitem" data-bv-modal="history"><i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i>{{ __('helpdesk::helpdesk.inbox.right.previous_conversations') }}</button>
                @if(helpdesk_feature_enabled('merge'))
                <button type="button" role="menuitem" data-bv-modal="merge"><i class="fa-solid fa-code-merge" aria-hidden="true"></i>{{ __('helpdesk::helpdesk.inbox.right.merge_conversation') }}</button>
                @endif
                <button type="button" role="menuitem" data-bv-modal="link-customer"><i class="fa-solid fa-link" aria-hidden="true"></i>{{ __('helpdesk::helpdesk.inbox.right.link_customer') }}</button>
                <div class="sep"></div>
                <button type="button" role="menuitem" class="danger" data-bv-modal="block-contact"><i class="fa-solid fa-ban" aria-hidden="true"></i>{{ __('helpdesk::helpdesk.inbox.right.block_contact') }}</button>
            </div>
        </div>
    </div>

    {{-- Stats: Conversaciones / Última visita --}}
    @php
        $rpLastSeen = $rpCust?->last_seen_at?->diffForHumans();
    @endphp
    @if(helpdesk_feature_enabled('rp_stats'))
    <div class="rsp-stats">
        <div class="stat">
            <div class="v @if($rpTotal === 0) muted @endif">{{ $rpTotal }}</div>
            <div class="k">{{ __('helpdesk::helpdesk.inbox.right.conversations_stat') }}</div>
        </div>
        <div class="stat">
            <div class="v @if(!$rpLastSeen) muted @endif">{{ $rpLastSeen ?? '—' }}</div>
            <div class="k">{{ __('helpdesk::helpdesk.inbox.right.last_seen_stat') }}</div>
        </div>
    </div>
    @endif

    {{-- Tabs --}}
    @php
        $rpTabGeneralOn = helpdesk_feature_enabled('tab_general');
    @endphp
    <div class="rsp-tabs">
        @if($rpTabGeneralOn)
        <button type="button" class="tab bv-right-tab on" data-bv-tab="general" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="{{ __('helpdesk::helpdesk.inbox.right.tab_general') }}" aria-label="{{ __('helpdesk::helpdesk.inbox.right.tab_general') }}">
            <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
        </button>
        @endif
        {{-- Tab "Pedidos" genérico eliminado: PrestaShop → tab "Tienda", ERP → tab "Gestión".
             "Carritos" es exclusivo de PrestaShop (contenido real vive en el
             módulo HelpdeskPrestashop, ver inbox-slots/right-panel-prestashop-tabs). --}}
        @if($rpCust && $rpHasPs && helpdesk_feature_enabled('tab_carts'))
        <button type="button" class="tab bv-right-tab" data-bv-tab="carts" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="{{ __('helpdesk::helpdesk.inbox.right.tab_carts') }}" aria-label="{{ __('helpdesk::helpdesk.inbox.right.tab_carts') }}">
            <i class="fa-solid fa-cart-shopping" aria-hidden="true"></i>
        </button>
        @endif
        @if(helpdesk_feature_enabled('tab_files'))
        <button type="button" class="tab bv-right-tab" data-bv-tab="files" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="{{ __('helpdesk::helpdesk.inbox.right.tab_files') }}" aria-label="{{ __('helpdesk::helpdesk.inbox.right.tab_files') }}">
            <i class="fa-regular fa-folder" aria-hidden="true"></i>
        </button>
        @endif
        @if($rpTicketsEnabled && helpdesk_feature_enabled('tab_tickets'))
        <button type="button" class="tab bv-right-tab" data-bv-tab="tickets" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="{{ __('helpdesk::helpdesk.inbox.right.tab_tickets') }}" aria-label="{{ __('helpdesk::helpdesk.inbox.right.tab_tickets') }}">
            <i class="fa-solid fa-ticket" aria-hidden="true"></i>
        </button>
        @endif
        @if($rpDocumentModuleAvailable && helpdesk_feature_enabled('tab_document'))
        <button type="button" class="tab bv-right-tab" data-bv-tab="document" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="{{ __('helpdesk::helpdesk.inbox.right.tab_document') }}" aria-label="{{ __('helpdesk::helpdesk.inbox.right.tab_document') }}">
            <i class="fa-regular fa-folder-open" aria-hidden="true"></i>
        </button>
        @endif
        @if(helpdesk_feature_enabled('tab_previous'))
        <button type="button" class="tab bv-right-tab" data-bv-tab="previous" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="{{ __('helpdesk::helpdesk.inbox.right.tab_previous') }}" aria-label="{{ __('helpdesk::helpdesk.inbox.right.previous_conversations') }}">
            <i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i>
        </button>
        @endif
        @if(helpdesk_feature_enabled('tab_activity'))
        <button type="button" class="tab bv-right-tab" data-bv-tab="activity" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="{{ __('helpdesk::helpdesk.inbox.right.tab_activity') }}" aria-label="{{ __('helpdesk::helpdesk.inbox.right.tab_activity') }}">
            <i class="fa-solid fa-bolt" aria-hidden="true"></i>
        </button>
        @endif
        @if(helpdesk_feature_enabled('email'))
        <button type="button" class="tab bv-right-tab" data-bv-tab="emails" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="{{ __('helpdesk::helpdesk.inbox.right.tab_emails') }}" aria-label="{{ __('helpdesk::helpdesk.inbox.right.tab_emails') }}">
            <i class="fa-regular fa-envelope-open" aria-hidden="true"></i>
        </button>
        @endif
        @if($rpHasWidgetData && helpdesk_feature_enabled('tab_technology'))
        <button type="button" class="tab bv-right-tab" data-bv-tab="technology" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="{{ __('helpdesk::helpdesk.inbox.right.tab_technology') }}" aria-label="{{ __('helpdesk::helpdesk.inbox.right.tab_technology') }}">
            <i class="fa-regular fa-window-maximize" aria-hidden="true"></i>
        </button>
        @endif
        @if(($rpShowAssistTab ?? false) && helpdesk_feature_enabled('tab_assist'))
        <button type="button" class="tab bv-right-tab" data-bv-tab="assist" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="{{ __('helpdesk::helpdesk.inbox.right.tab_screen') }}" aria-label="{{ __('helpdesk::helpdesk.inbox.right.screen_share_label') }}">
            <i class="fa-regular fa-eye" aria-hidden="true"></i>
        </button>
        @endif
        @if($rpCust && helpdesk_feature_enabled('tab_customer360'))
        <button type="button" class="tab bv-right-tab" data-bv-tab="customer-360" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="{{ __('helpdesk::helpdesk.inbox.right.tab_customer360') }}" aria-label="{{ __('helpdesk::helpdesk.inbox.right.tab_customer360') }}">
            <i class="fa-solid fa-chart-pie" aria-hidden="true"></i>
        </button>
        @endif
        @if($rpHasPs || $rpHasErp)
            <span class="rsp-tabs-sep"></span>
        @endif
        @if($rpHasPs)
            <button type="button" class="tab bv-right-tab" data-bv-tab="ps-orders" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="{{ __('helpdesk::helpdesk.inbox.right.tab_store') }}" aria-label="{{ __('helpdesk::helpdesk.inbox.right.store_orders_label') }}">
                <i class="fa-solid fa-store" aria-hidden="true"></i>
            </button>
            <button type="button" class="tab bv-right-tab" data-bv-tab="ps-returns" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="{{ __('helpdesk::helpdesk.inbox.right.tab_returns') }}" aria-label="{{ __('helpdesk::helpdesk.inbox.right.tab_returns') }}">
                <i class="fa-solid fa-rotate-left" aria-hidden="true"></i>
            </button>
            <button type="button" class="tab bv-right-tab" data-bv-tab="ps-vouchers" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="{{ __('helpdesk::helpdesk.inbox.right.tab_vouchers') }}" aria-label="{{ __('helpdesk::helpdesk.inbox.right.tab_vouchers') }}">
                <i class="fa-solid fa-tag" aria-hidden="true"></i>
            </button>
            <button type="button" class="tab bv-right-tab" data-bv-tab="ps-addresses" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="{{ __('helpdesk::helpdesk.inbox.right.tab_addresses') }}" aria-label="{{ __('helpdesk::helpdesk.inbox.right.tab_addresses') }}">
                <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
            </button>
        @endif
        @if($rpHasErp)
            <button type="button" class="tab bv-right-tab" data-bv-tab="erp-orders" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="{{ __('helpdesk::helpdesk.inbox.right.tab_management') }}" aria-label="{{ __('helpdesk::helpdesk.inbox.right.management_orders_label') }}">
                <i class="fa-solid fa-clipboard-list" aria-hidden="true"></i>
            </button>
            <button type="button" class="tab bv-right-tab" data-bv-tab="erp-finance" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="{{ __('helpdesk::helpdesk.inbox.right.tab_finance') }}" aria-label="{{ __('helpdesk::helpdesk.inbox.right.tab_finance') }}">
                <i class="fa-solid fa-coins" aria-hidden="true"></i>
            </button>
            <button type="button" class="tab bv-right-tab" data-bv-tab="erp-loyalty" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="{{ __('helpdesk::helpdesk.inbox.right.tab_loyalty') }}" aria-label="{{ __('helpdesk::helpdesk.inbox.right.tab_loyalty') }}">
                <i class="fa-solid fa-star" aria-hidden="true"></i>
            </button>
        @endif
    </div>

    <div class="bv-right-body rsp-body">

        {{-- ── Tab: General ── --}}
        @if($rpTabGeneralOn)
        <div class="bv-right-tab-content" data-bv-tab-content="general">

            {{-- Información de contacto --}}
            @php
                $rpLangCode = $rpCust?->language ? strtolower(substr($rpCust->language, 0, 2)) : null;
                $rpLangNames = [
                    'es' => 'Español', 'en' => 'Inglés', 'fr' => 'Francés', 'de' => 'Alemán',
                    'it' => 'Italiano', 'pt' => 'Portugués', 'nl' => 'Neerlandés', 'ja' => 'Japonés',
                    'zh' => 'Chino', 'ru' => 'Ruso', 'ar' => 'Árabe', 'ko' => 'Coreano',
                ];
                $rpLangLabel = $rpLangCode ? ($rpLangNames[$rpLangCode] ?? strtoupper($rpCust->language)) : null;
                $rpCompany = $rpCust?->custom_attributes['company'] ?? null;
                $rpLocation = $rpCust?->country || $rpCust?->city
                    ? implode(', ', array_filter([$rpCust->city, $rpCust->state, $rpCust->country]))
                    : null;
                // Customers created from a channel webhook (WhatsApp/Facebook/Instagram)
                // only have their platform-specific identifier filled in — `phone` stays
                // null. Fall back to whichever one the customer actually has so the panel
                // isn't empty for every social-channel contact.
                $rpPhone = $rpCust?->phone ?: $rpCust?->whatsapp_phone;
                $rpPhoneLabel = $rpCust?->phone
                    ? __('helpdesk::helpdesk.inbox.right.phone_label')
                    : __('helpdesk::helpdesk.inbox.right.whatsapp_label');
                $rpHasContactData = $rpCust?->email || $rpPhone || $rpCust?->facebook_psid || $rpCust?->instagram_id || $rpCompany || $rpCust?->language || $rpCust?->timezone || $rpLocation;
            @endphp
            <div class="rsp-section">
                <div class="lbl">
                    <i class="fa-regular fa-address-card"></i> {{ __('helpdesk::helpdesk.inbox.right.contact_info') }}
                    <i class="fa-solid fa-pen add" role="button" data-bv-modal="edit-contact" title="{{ __('helpdesk::helpdesk.inbox.right.edit_title') }}"></i>
                </div>
                @if($rpCust?->email)
                    <div class="rsp-kv"><span class="k">{{ __('helpdesk::helpdesk.inbox.right.email_label') }}</span><span class="v mono" title="{{ $rpCust->email }}">{{ $rpCust->email }}</span></div>
                @endif
                @if($rpPhone)
                    <div class="rsp-kv"><span class="k">{{ $rpPhoneLabel }}</span><span class="v">{{ $rpPhone }}</span></div>
                @endif
                @if($rpCust?->facebook_psid)
                    <div class="rsp-kv"><span class="k">{{ __('helpdesk::helpdesk.inbox.right.facebook_label') }}</span><span class="v mono">{{ $rpCust->facebook_psid }}</span></div>
                @endif
                @if($rpCust?->instagram_id)
                    <div class="rsp-kv"><span class="k">{{ __('helpdesk::helpdesk.inbox.right.instagram_label') }}</span><span class="v mono">{{ $rpCust->instagram_id }}</span></div>
                @endif
                @if($rpCompany)
                    <div class="rsp-kv"><span class="k">{{ __('helpdesk::helpdesk.inbox.right.company_label') }}</span><span class="v">{{ $rpCompany }}</span></div>
                @endif
                @if($rpLangCode)
                    <div class="rsp-kv"><span class="k">{{ __('helpdesk::helpdesk.inbox.right.language_label') }}</span><span class="v">{{ $rpLangLabel }} ({{ strtoupper($rpCust->language) }})</span></div>
                @endif
                @if($rpCust?->timezone)
                    <div class="rsp-kv"><span class="k">{{ __('helpdesk::helpdesk.inbox.right.timezone_label') }}</span><span class="v">{{ $rpCust->timezone }}</span></div>
                @endif
                @if($rpLocation)
                    <div class="rsp-kv"><span class="k">{{ __('helpdesk::helpdesk.inbox.right.location_label') }}</span><span class="v">{{ $rpLocation }}</span></div>
                @endif
                @if(!$rpHasContactData)
                    <div class="rsp-empty">{{ __('helpdesk::helpdesk.inbox.right.no_contact_data') }}</div>
                @endif
            </div>

            {{-- Estado de la conversación --}}
            @if(helpdesk_feature_enabled('rp_status'))
            <div class="rsp-section">
                <div class="lbl"><i class="fa-solid fa-circle-info"></i> {{ __('helpdesk::helpdesk.inbox.right.conversation_state') }}</div>
                <div class="rsp-kv rsp-kv-ctrl">
                    <span class="k">{{ __('helpdesk::helpdesk.inbox.right.status_label') }}</span>
                    <span class="v">
                        <button type="button" class="r-tag r-tag-btn" data-bv-modal="status">
                            {{ $rpStatusName }}
                            <i class="fa-solid fa-chevron-down"></i>
                        </button>
                    </span>
                </div>
                <div class="rsp-kv rsp-kv-ctrl">
                    <span class="k">{{ __('helpdesk::helpdesk.inbox.right.priority_label') }}</span>
                    <span class="v">
                        @php $rpPriorityMod = $priorityColors[$rpPriority] ?? ''; @endphp
                        <button type="button" class="r-tag r-tag-btn{{ $rpPriorityMod ? ' r-tag-'.$rpPriorityMod : '' }}" data-bv-modal="priority">
                            {{ $priorityLabels[$rpPriority] ?? 'Normal' }}
                            <i class="fa-solid fa-chevron-down"></i>
                        </button>
                    </span>
                </div>
                <div class="rsp-kv rsp-kv-ctrl">
                    <span class="k">{{ __('helpdesk::helpdesk.inbox.right.agent_label') }}</span>
                    <span class="v">
                        <button type="button" class="r-tag r-tag-btn @if(!$rpConvo?->assignee) r-tag-muted @endif" data-bv-modal="assign">
                            {{ $rpConvo?->assignee?->full_name ?? 'Sin asignar' }}
                            <i class="fa-solid fa-chevron-down"></i>
                        </button>
                        @if($rpConvo?->assignee)
                            <button type="button" class="r-tag r-tag-btn bv-ap-trigger" data-bv-modal="agent-profile" data-agent-id="{{ $rpConvo->assignee->id }}" title="{{ __('helpdesk::helpdesk.inbox.right.view_agent_profile') }}" aria-label="{{ __('helpdesk::helpdesk.inbox.right.view_agent_profile') }}">
                                <i class="fa-solid fa-headset" aria-hidden="true"></i>
                            </button>
                        @endif
                    </span>
                </div>
                @if($rpConvo?->group)
                <div class="rsp-kv">
                    <span class="k">{{ __('helpdesk::helpdesk.inbox.right.team_label') }}</span>
                    <span class="v">
                        <span class="r-tag"><i class="fa-regular fa-users"></i> {{ $rpConvo->group->name }}</span>
                    </span>
                </div>
                @endif
            </div>
            @endif

            {{-- Etiquetas --}}
            @if(helpdesk_feature_enabled('rp_tags_section'))
            <div class="rsp-section">
                <div class="lbl">
                    <i class="fa-solid fa-tag"></i> {{ __('helpdesk::helpdesk.inbox.right.tags_heading') }}
                    <i class="fa-solid fa-plus add" role="button" data-bv-modal="tags" title="{{ __('helpdesk::helpdesk.inbox.right.add_tag') }}" aria-label="{{ __('helpdesk::helpdesk.inbox.right.add_tag') }}" aria-hidden="false"></i>
                </div>
                @if($rpConvo?->conversationTags?->isNotEmpty())
                    <div class="rsp-tag-wrap">
                        @foreach($rpConvo->conversationTags as $tag)
                            <span class="r-tag">{{ $tag->name }}</span>
                        @endforeach
                    </div>
                @else
                    <div class="rsp-empty">{{ __('helpdesk::helpdesk.inbox.right.no_tags') }}</div>
                @endif
            </div>
            @endif

            {{-- Integraciones --}}
            @php
                $rpIntegrationsList = [];

                // Widget Web (siempre presente para conversaciones de web)
                $rpWidgetId = null;
                if (isset($rpWidgetSession) && $rpWidgetSession) {
                    $rpWidgetId = 'WW-#'.($rpWidgetSession->id ?? $rpWidgetSession->session_token ?? '—');
                }
                if ($rpWidgetId || ($rpConvo?->channel ?? null) === 'web') {
                    $rpIntegrationsList[] = [
                        'icon'  => 'fa-regular fa-comment',
                        'name'  => 'Widget Web',
                        'id'    => $rpWidgetId ?: 'sin sesión',
                        'connected' => (bool) $rpWidgetId,
                    ];
                }

                if ($rpCust && helpdesk_integration_enabled()
                    && class_exists(\Modules\HelpdeskIntegration\Models\IntegrationProvider::class)) {
                    // Fuente única: el catálogo de proveedores + vínculos del
                    // cliente (los mismos datos que el modal de integraciones),
                    // en vez del mapa estático PS/ERP/Shopify que divergía del
                    // catálogo dinámico.
                    $rpCust->loadMissing('externalIds');
                    $rpLinkedByPlatform = $rpCust->externalIds->keyBy('platform');
                    $rpProviders = \Modules\HelpdeskIntegration\Models\IntegrationProvider::query()
                        ->orderBy('sort_order')
                        ->get();

                    foreach ($rpProviders as $rpProvider) {
                        $rpLink = $rpLinkedByPlatform->get($rpProvider->platform);

                        if (! $rpLink && ! ($rpProvider->is_active && $rpProvider->is_linkable)) {
                            continue;
                        }

                        $rpIntegrationsList[] = [
                            'icon'      => $rpProvider->icon ?: 'fas fa-plug',
                            'color'     => $rpProvider->color,
                            'name'      => $rpProvider->label ?: ucfirst($rpProvider->platform),
                            'id'        => $rpLink?->external_id ?: 'sin vincular',
                            'connected' => (bool) $rpLink,
                        ];
                    }

                    // Vínculos legacy cuyo proveedor ya no está en el catálogo.
                    foreach ($rpLinkedByPlatform as $rpPlatform => $rpLink) {
                        if ($rpProviders->contains('platform', $rpPlatform)) {
                            continue;
                        }

                        $rpIntegrationsList[] = [
                            'icon'      => 'fas fa-plug',
                            'name'      => ucfirst($rpPlatform),
                            'id'        => (string) $rpLink->external_id,
                            'connected' => true,
                        ];
                    }
                } else {
                    // Fallback sin el módulo HelpdeskIntegration: mapa estático
                    // PS/ERP como antes.
                    if ($rpHasPs || $rpHasErp) {
                        // Sin 'color': el icono de .rsp-integration ya es neutro por
                        // CSS (var(--bv-bg-subtle)/var(--bv-text)) — esta clave nunca
                        // se leía en el render, era ruido de marca sin efecto.
                        $rpIntegrationsList[] = [
                            'icon'  => 'fas fa-cart-shopping',
                            'name'  => 'PrestaShop',
                            'id'    => $rpExternalPsId ? 'PS-#'.$rpExternalPsId : 'sin vincular',
                            'connected' => (bool) $rpExternalPsId,
                        ];

                        $rpIntegrationsList[] = [
                            'icon'  => 'fas fa-clipboard-list',
                            'name'  => 'Gestión (ERP)',
                            'id'    => $rpExternalErpId ? 'ERP-'.$rpExternalErpId : 'sin vincular',
                            'connected' => (bool) $rpExternalErpId,
                        ];
                    }

                    $rpDetectedPlatform = $rpCust?->custom_attributes['platform'] ?? null;
                    $rpPlatformMap = [
                        'shopify'    => ['name' => 'Shopify',    'icon' => 'fa-brands fa-shopify'],
                        'woocommerce'=> ['name' => 'WooCommerce','icon' => 'fa-brands fa-wordpress'],
                        'magento'    => ['name' => 'Magento',    'icon' => 'fa-solid fa-store'],
                        'bigcommerce'=> ['name' => 'BigCommerce','icon' => 'fa-solid fa-store'],
                    ];
                    if ($rpDetectedPlatform && isset($rpPlatformMap[$rpDetectedPlatform])) {
                        $rpPlat = $rpPlatformMap[$rpDetectedPlatform];
                        $rpIntegrationsList[] = [
                            'icon' => $rpPlat['icon'],
                            'name' => $rpPlat['name'],
                            'id'   => $rpCust?->custom_attributes['platform_id'] ?? 'conectado',
                            'connected' => true,
                        ];
                    }
                }

                $rpConnectedCount = collect($rpIntegrationsList)->where('connected', true)->count();

                // Identidad del cliente verificada (gate OTP) — vive en el
                // modulo HelpdeskIntegration; se degrada con gracia (bloque
                // oculto) si el modulo esta desactivado.
                $rpIdentityVerified = null;
                if ($rpCust && view()->exists('helpdeskintegration::modals.verify-customer-identity')) {
                    $rpIdentityService = app(\Modules\HelpdeskIntegration\Services\CustomerIdentityVerificationService::class);
                    $rpIdentityVerified = $rpIdentityService->isVerified($rpCust);
                    $rpIdentitySummary = $rpIdentityVerified ? $rpIdentityService->summary($rpCust) : null;
                }
            @endphp
            @if($rpIdentityVerified !== null)
            <div class="rsp-section">
                <div class="lbl">
                    <i class="fa-solid fa-shield-halved"></i> {{ __('helpdesk::helpdesk.inbox.right.identity_heading') }}
                    @if($rpIdentityVerified)
                        <span class="r-tag" data-bs-toggle="tooltip" data-bs-placement="bottom"
                            data-bs-title="{{ $rpIdentitySummary['verified_by'] ?? 'Sistema' }} · {{ \Illuminate\Support\Carbon::parse($rpIdentitySummary['verified_at'])->diffForHumans() }}">
                            <i class="fa-solid fa-circle-check"></i> {{ __('helpdesk::helpdesk.inbox.right.verified_label') }}
                        </span>
                    @else
                        <button type="button" class="r-tag r-tag-btn bv-identity-verify-trigger ms-auto" data-customer-id="{{ $rpCust->id }}">
                            {{ __('helpdesk::helpdesk.inbox.right.verify_identity') }}
                        </button>
                        <button type="button" class="r-tag r-tag-btn" data-bv-modal="link-customer">
                            {{ __('helpdesk::helpdesk.inbox.right.link_customer_short') }}
                        </button>
                    @endif
                </div>
            </div>
            @endif
            @if(helpdesk_feature_enabled('rp_integrations'))
            <div class="rsp-section">
                <div class="lbl">
                    <i class="fa-solid fa-plug"></i> {{ __('helpdesk::helpdesk.inbox.right.integrations_heading') }}
                    @if($rpConvo?->id)
                        <button type="button" class="btn btn-sm btn-link p-0 ms-auto bv-sync-commerce"
                                data-conv-id="{{ $rpConvo->id }}"
                                data-bs-toggle="tooltip" data-bs-placement="bottom"
                                data-bs-title="{{ __('helpdesk::helpdesk.inbox.right.resync_title') }}"
                                aria-label="{{ __('helpdesk::helpdesk.inbox.right.resync_aria') }}">
                            <i class="fa-solid fa-arrows-rotate" aria-hidden="true"></i>
                        </button>
                    @endif
                    @if($rpCust && helpdesk_integration_enabled() && view()->exists('helpdeskintegration::modals.customer-integrations'))
                        <button type="button" class="btn btn-sm btn-link p-0 @if(! $rpConvo?->id) ms-auto @endif bv-integrations-trigger"
                                data-bv-modal="customer-integrations"
                                data-bs-toggle="tooltip" data-bs-placement="bottom"
                                data-bs-title="{{ __('helpdesk::helpdesk.inbox.right.view_customer_integrations') }}"
                                aria-label="{{ __('helpdesk::helpdesk.inbox.right.view_customer_integrations') }}">
                            <i class="fa-solid fa-up-right-from-square" aria-hidden="true"></i>
                        </button>
                    @endif
                    @if(! empty($rpIntegrationsList))
                        <span class="r-tag">{{ $rpConnectedCount }}</span>
                    @endif
                </div>
                @if(! empty($rpIntegrationsList))
                    <div class="rsp-integrations">
                        @foreach($rpIntegrationsList as $intg)
                            <div class="rsp-integration @if(!$intg['connected']) is-disconnected @endif">
                                <div class="ico"><i class="{{ $intg['icon'] }}"></i></div>
                                <div class="meta">
                                    <span class="name">{{ $intg['name'] }}</span>
                                    <span class="id">{{ __('helpdesk::helpdesk.inbox.right.id_with_value', ['id' => $intg['id']]) }}</span>
                                </div>
                                <span class="status">{{ $intg['connected'] ? 'Conectado' : 'Desconectado' }}</span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="rsp-empty">{{ __('helpdesk::helpdesk.inbox.right.no_integrations') }}</div>
                @endif
            </div>
            @endif
        </div>
        @endif

        {{-- ── Tab: Carritos ── (contenido movido al módulo HelpdeskPrestashop,
             ver inbox-slots/right-panel-prestashop-tabs.blade.php) --}}

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
                // PDF
                'pdf'  => ['icon' => 'fa-file-pdf',        'color' => '#dc2626'],
                // Word
                'doc'  => ['icon' => 'fa-file-word',       'color' => '#475569'],
                'docx' => ['icon' => 'fa-file-word',       'color' => '#475569'],
                'rtf'  => ['icon' => 'fa-file-word',       'color' => '#64748b'],
                'odt'  => ['icon' => 'fa-file-word',       'color' => '#64748b'],
                // Excel
                'xls'  => ['icon' => 'fa-file-excel',      'color' => '#059669'],
                'xlsx' => ['icon' => 'fa-file-excel',      'color' => '#059669'],
                'ods'  => ['icon' => 'fa-file-excel',      'color' => '#059669'],
                // PowerPoint
                'ppt'  => ['icon' => 'fa-file-powerpoint', 'color' => '#e67000'],
                'pptx' => ['icon' => 'fa-file-powerpoint', 'color' => '#e67000'],
                'odp'  => ['icon' => 'fa-file-powerpoint', 'color' => '#e67000'],
                // Archives
                'zip'  => ['icon' => 'fa-file-zipper',     'color' => '#71717a'],
                'rar'  => ['icon' => 'fa-file-zipper',     'color' => '#71717a'],
                '7z'   => ['icon' => 'fa-file-zipper',     'color' => '#71717a'],
                'gz'   => ['icon' => 'fa-file-zipper',     'color' => '#71717a'],
                'tar'  => ['icon' => 'fa-file-zipper',     'color' => '#71717a'],
                'bz2'  => ['icon' => 'fa-file-zipper',     'color' => '#71717a'],
                // Text / data
                'csv'  => ['icon' => 'fa-file-csv',        'color' => '#059669'],
                'txt'  => ['icon' => 'fa-file-lines',      'color' => '#71717a'],
                'md'   => ['icon' => 'fa-file-lines',      'color' => '#71717a'],
                // Code / markup
                'json' => ['icon' => 'fa-file-code',       'color' => '#f59e0b'],
                'xml'  => ['icon' => 'fa-file-code',       'color' => '#f59e0b'],
                'html' => ['icon' => 'fa-file-code',       'color' => '#f97316'],
                'htm'  => ['icon' => 'fa-file-code',       'color' => '#f97316'],
                'css'  => ['icon' => 'fa-file-code',       'color' => '#06b6d4'],
                'js'   => ['icon' => 'fa-file-code',       'color' => '#eab308'],
                'php'  => ['icon' => 'fa-file-code',       'color' => '#8b5cf6'],
                // Images shown as doc (svg, bmp, tif when not renderable)
                'svg'  => ['icon' => 'fa-file-image',      'color' => '#10b981'],
                'bmp'  => ['icon' => 'fa-file-image',      'color' => '#71717a'],
                'tiff' => ['icon' => 'fa-file-image',      'color' => '#71717a'],
                'tif'  => ['icon' => 'fa-file-image',      'color' => '#71717a'],
                // Audio (fallback if typed as document)
                'mp3'  => ['icon' => 'fa-file-audio',      'color' => '#7c3aed'],
                'wav'  => ['icon' => 'fa-file-audio',      'color' => '#7c3aed'],
                'ogg'  => ['icon' => 'fa-file-audio',      'color' => '#7c3aed'],
                'aac'  => ['icon' => 'fa-file-audio',      'color' => '#7c3aed'],
                'flac' => ['icon' => 'fa-file-audio',      'color' => '#7c3aed'],
                'm4a'  => ['icon' => 'fa-file-audio',      'color' => '#7c3aed'],
                'opus' => ['icon' => 'fa-file-audio',      'color' => '#7c3aed'],
                // Video (fallback if typed as document)
                'avi'  => ['icon' => 'fa-file-video',      'color' => '#ef4444'],
                'mkv'  => ['icon' => 'fa-file-video',      'color' => '#ef4444'],
                'flv'  => ['icon' => 'fa-file-video',      'color' => '#ef4444'],
                'wmv'  => ['icon' => 'fa-file-video',      'color' => '#ef4444'],
            ];
            $rpTypeColors = [
                'image'    => '#b10100',
                'video'    => '#dc2626',
                'audio'    => '#f87171',
                'document' => '#7b0000',
            ];
            $rpTypeLabels = [
                'image'    => 'Imágenes',
                'video'    => 'Vídeo',
                'audio'    => 'Audio',
                'document' => 'Docs',
            ];
        @endphp
        @if(helpdesk_feature_enabled('tab_files'))
        <div class="bv-right-tab-content bv-tab-hidden" data-bv-tab-content="files">

            @if($rpFiles->isEmpty())
                <div class="bv-tab-empty">
                    <i class="far fa-folder-open"></i>
                    <div class="bv-tab-empty-title">{{ __('helpdesk::helpdesk.inbox.right.no_files_title') }}</div>
                    <div class="bv-tab-empty-sub">{{ __('helpdesk::helpdesk.inbox.right.no_files_sub') }}</div>
                </div>
            @else

                {{-- Section header --}}
                <div class="media-sec-head">
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
                        {{ __('helpdesk::helpdesk.inbox.right.all_label') }} <span class="c">{{ $rpFileCounts['all'] }}</span>
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
                            <i class="fas fa-video"></i> <span class="c">{{ $rpFileCounts['video'] }}</span>
                        </span>
                    @endif
                    @if($rpFileCounts['document'] > 0)
                        <span class="media-pill bv-files-filter" data-bv-files-filter="document">
                            <i class="far fa-file-lines"></i> <span class="c">{{ $rpFileCounts['document'] }}</span>
                        </span>
                    @endif

                    <span class="spacer"></span>

                    <select class="fselect bv-files-sort" id="bv-files-sort" aria-label="{{ __('helpdesk::helpdesk.inbox.right.sort_aria_label') }}">
                        <option value="recent">{{ __('helpdesk::helpdesk.inbox.right.sort_recent') }}</option>
                        <option value="oldest">{{ __('helpdesk::helpdesk.inbox.right.sort_oldest') }}</option>
                        <option value="size-desc">{{ __('helpdesk::helpdesk.inbox.right.sort_size_desc') }}</option>
                        <option value="size-asc">{{ __('helpdesk::helpdesk.inbox.right.sort_size_asc') }}</option>
                        <option value="name">{{ __('helpdesk::helpdesk.inbox.right.sort_name') }}</option>
                    </select>

                    <div class="media-view-toggle">
                        <button class="bv-files-vt on" data-bv-view="grid" title="{{ __('helpdesk::helpdesk.inbox.right.view_grid_title') }}">
                            <i class="fas fa-grip"></i>
                        </button>
                        <button class="bv-files-vt" data-bv-view="list" title="{{ __('helpdesk::helpdesk.inbox.right.view_list_title') }}">
                            <i class="fas fa-list"></i>
                        </button>
                    </div>
                </div>

                <hr class="bv-files-divider">

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
                            <label class="bv-file-select" onclick="event.stopPropagation();">
                                <input type="checkbox" class="bv-file-cb" onclick="event.stopPropagation();">
                                <span class="bv-file-check"></span>
                            </label>
                            <div class="media-thumb{{ $f->type === 'video' ? ' video' : '' }}">
                                @if($f->type === 'image')
                                    <img src="{{ $f->url }}" alt="{{ $f->name }}" loading="lazy"
                                         onerror="this.parentElement.classList.add('placeholder'); this.style.display='none';">
                                    <i class="fa-regular fa-image bv-img-placeholder"></i>
                                    <span class="bv-file-overlay"><i class="fas fa-magnifying-glass-plus"></i></span>
                                @elseif($f->type === 'video')
                                    <div class="play"><i class="fas fa-play"></i></div>
                                @elseif($f->type === 'audio')
                                    <div class="bv-file-icon-wrap bv-x25">
                                        <i class="fas fa-volume-high"></i>
                                    </div>
                                    <span class="bv-file-overlay"><i class="fas fa-play"></i></span>
                                @else
                                    <div class="bv-file-icon-wrap" style="color:{{ $fileMeta['color'] }};">
                                        <i class="fas {{ $fileMeta['icon'] }}"></i>
                                    </div>
                                    <span class="bv-file-overlay"><i class="fas fa-download"></i></span>
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
                                <div class="bv-file-row">
                                    @if($f->size)<span class="size">{{ $rpFormatSize($f->size) }}</span>@endif
                                    @if($f->created_at)<span class="date">{{ $f->created_at->diffForHumans(['short' => true]) }}</span>@endif
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>

                {{-- Footer: descarga y cierre (solo visible con selección activa) --}}
                <div class="bv-files-footer" id="bv-files-footer" style="display:none;">
                    <button type="button" class="bv-files-dl-btn" id="bv-files-dl-btn">
                        {{ __('helpdesk::helpdesk.inbox.right.download_selection') }}
                    </button>
                    <button type="button" class="bv-files-close-btn" id="bv-files-close-btn">
                        {{ __('helpdesk::helpdesk.inbox.right.cancel_button') }}
                    </button>
                </div>

            @endif
        </div>
        @endif

        {{-- ── Tab: Tickets (slot del módulo HelpdeskTickets) ── --}}
        @if($rpTicketsEnabled && helpdesk_feature_enabled('tab_tickets'))
            @include('helpdesktickets::inbox-slots.right-panel-tickets-tab', ['rpTickets' => $rpTickets, 'rpConversationId' => $rpConvo?->id])
        @endif

        {{-- ── Tab: Documentacion (slot del módulo HelpdeskDocument) ── --}}
        @if($rpDocumentModuleAvailable)
            @include('helpdeskdocument::inbox-slots.right-panel-document-tab', [
                'rpDocuments' => $rpDocuments,
                'rpConvo'     => $rpConvo,
            ])
        @endif

        {{-- ── Tab: Anteriores ── --}}
        @if(helpdesk_feature_enabled('tab_previous'))
        <div class="bv-right-tab-content bv-tab-hidden" data-bv-tab-content="previous">
            @php
                $rpPrevious = collect();
                if ($rpCust) {
                    $rpPrevious = \Modules\Helpdesk\Models\Conversation::where('customer_id', $rpCust->id)
                        ->where('id', '!=', $rpConvo?->id)
                        ->with(['status', 'assignee', 'inbox', 'lastMessage'])
                        ->withCount(['items as messages_count'])
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
                    'web'       => ['icon' => 'far fa-comment-dots', 'color' => '#14b8a6'],
                ];
            @endphp

            @if($rpPrevious->isEmpty())
                <div class="bv-tab-empty">
                    <i class="fas fa-clock-rotate-left"></i>
                    <div class="bv-tab-empty-title">{{ __('helpdesk::helpdesk.inbox.right.no_previous_title') }}</div>
                    <div class="bv-tab-empty-sub">{{ __('helpdesk::helpdesk.inbox.right.no_previous_sub') }}</div>
                </div>
            @else
                {{-- Search --}}
                <div class="bv-prev-search">
                    <i class="fas fa-magnifying-glass"></i>
                    <input type="text" class="bv-prev-search-input" placeholder="{{ __('helpdesk::helpdesk.inbox.right.search_history_placeholder') }}">
                </div>

                {{-- Filter pills --}}
                @php
                    $prevAll    = $rpPrevious->count();
                    $prevOpen   = $rpPrevious->filter(fn($c) => (bool)($c->status?->is_open ?? true))->count();
                    $prevClosed = $prevAll - $prevOpen;
                @endphp
                <div class="bv-prev-filter-row">
                    <span class="bv-media-pill bv-prev-pill on" data-bv-prev-filter="all">
                        {{ __('helpdesk::helpdesk.inbox.right.filter_all_fem') }} <span class="c">{{ $prevAll }}</span>
                    </span>
                    <span class="bv-media-pill bv-prev-pill" data-bv-prev-filter="open">
                        {{ __('helpdesk::helpdesk.inbox.right.filter_open') }} <span class="c">{{ $prevOpen }}</span>
                    </span>
                    <span class="bv-media-pill bv-prev-pill" data-bv-prev-filter="closed">
                        {{ __('helpdesk::helpdesk.inbox.right.filter_closed') }} <span class="c">{{ $prevClosed }}</span>
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
                            $preview    = $prev->lastMessage?->body ?? '';
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
        @endif

        {{-- ── Tab: Actividad ── --}}
        @if(helpdesk_feature_enabled('tab_activity'))
        <div class="bv-right-tab-content bv-tab-hidden" data-bv-tab-content="activity">
            @if($rpEvents->isEmpty())
                <div class="bv-tab-empty">
                    <i class="fas fa-clock-rotate-left"></i>
                    <div class="bv-tab-empty-title">{{ __('helpdesk::helpdesk.inbox.right.no_activity_title') }}</div>
                    <div class="bv-tab-empty-sub">{{ __('helpdesk::helpdesk.inbox.right.no_activity_sub') }}</div>
                </div>
            @else
                <div class="rsp-section bv-x49">
                    <div class="lbl"><i class="fas fa-bolt-lightning"></i> {{ __('helpdesk::helpdesk.inbox.right.activity_timeline') }}</div>
                    <div class="rsp-timeline">
                        @foreach($rpEvents as $event)
                        <div class="rsp-tl-item">
                            <div class="ic">
                                <i class="{{ $rpEventIcons[$event->type] ?? 'fas fa-circle-info' }}"></i>
                            </div>
                            <div class="body">
                                <div class="t">{{ $event->event_label }}</div>
                                <div class="s">
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
        @endif

        {{-- ── Tab: Tecnología ── --}}
        @if($rpShowTechnologyTab && helpdesk_feature_enabled('tab_technology'))
        <div class="bv-right-tab-content bv-tab-hidden" data-bv-tab-content="technology">

            @if(! $rpWidgetSession)
                {{-- Empty state — web channel but no session recorded yet --}}
                <div class="bv-tab-empty">
                    <i class="fas fa-laptop"></i>
                    <div class="bv-tab-empty-title">{{ __('helpdesk::helpdesk.inbox.right.no_session_title') }}</div>
                    <div class="bv-tab-empty-sub">
                        {{ __('helpdesk::helpdesk.inbox.right.no_session_sub') }}
                    </div>
                </div>
            @else

            {{-- Información del dispositivo --}}
            <div class="rsp-section bv-x76">
                <div class="lbl"><i class="fas fa-display"></i> {{ __('helpdesk::helpdesk.inbox.right.device_info_heading') }}</div>

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

                <div class="rsp-kv">
                    <span class="k">IP address</span>
                    <span class="v mono">
                        {{ $rpWidgetSession->ip_address ?? '—' }}
                        @if($rpWidgetSession->ip_address)
                            <span class="bv-x77">{{ __('helpdesk::helpdesk.inbox.right.anonymized_label') }}</span>
                        @endif
                    </span>
                </div>

                @if($rpWidgetSession->country_code)
                <div class="rsp-kv">
                    <span class="k">{{ __('helpdesk::helpdesk.inbox.right.country_label') }}</span>
                    <span class="v">{{ $rpWidgetSession->country_code }}</span>
                </div>
                @endif

                <div class="rsp-kv">
                    <span class="k">{{ __('helpdesk::helpdesk.inbox.right.platform_label') }}</span>
                    <span class="v">{{ $rpOs ?? 'Unknown' }}</span>
                </div>

                <div class="rsp-kv">
                    <span class="k">{{ __('helpdesk::helpdesk.inbox.right.browser_label') }}</span>
                    <span class="v">{{ $rpBrowser ?? 'Unknown' }}</span>
                </div>

                <div class="rsp-kv">
                    <span class="k">{{ __('helpdesk::helpdesk.inbox.right.device_label') }}</span>
                    <span class="v">
                        @if($rpDeviceType === 'mobile')
                            <i class="fas fa-mobile-screen"></i> {{ __('helpdesk::helpdesk.inbox.right.device_mobile') }}
                        @elseif($rpDeviceType === 'tablet')
                            <i class="fas fa-tablet-screen-button"></i> {{ __('helpdesk::helpdesk.inbox.right.device_tablet') }}
                        @else
                            <i class="fas fa-desktop"></i> {{ __('helpdesk::helpdesk.inbox.right.device_desktop') }}
                        @endif
                    </span>
                </div>

                @if($rpWidgetSession->started_at)
                <div class="rsp-kv">
                    <span class="k">{{ __('helpdesk::helpdesk.inbox.right.session_started_label') }}</span>
                    <span class="v">{{ $rpWidgetSession->started_at->diffForHumans() }}</span>
                </div>
                @endif

                @if($rpWidgetSession->last_activity_at)
                <div class="rsp-kv">
                    <span class="k">{{ __('helpdesk::helpdesk.inbox.right.last_activity_label') }}</span>
                    <span class="v">{{ $rpWidgetSession->last_activity_at->diffForHumans() }}</span>
                </div>
                @endif

                @if($rpWidgetSession->time_on_site ?? null)
                <div class="rsp-kv">
                    <span class="k">{{ __('helpdesk::helpdesk.inbox.right.time_on_site_label') }}</span>
                    <span class="v mono">{{ \Carbon\CarbonInterval::seconds($rpWidgetSession->time_on_site)->cascade()->forHumans(['short' => true]) }}</span>
                </div>
                @endif

                @if($rpWidgetSession->referrer)
                <div class="rsp-kv">
                    <span class="k">Referrer</span>
                    <span class="v mono bv-x36" title="{{ $rpWidgetSession->referrer }}">
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
            <div class="rsp-section bv-x78">
                <div class="lbl">
                    <i class="fas fa-location-dot"></i>
                    {{ __('helpdesk::helpdesk.inbox.right.current_page_heading') }}
                    @if($rpIsLive)
                        <span class="bv-current-page-pulse bv-x69" title="{{ __('helpdesk::helpdesk.inbox.right.viewing_now_title') }}">
                            <span class="bv-pulse-dot"></span>
                            {{ __('helpdesk::helpdesk.inbox.right.viewing_now_label') }}
                        </span>
                    @endif
                </div>
                @if(!$rpIsLive && $rpLastActivity)
                    <div class="bv-current-page-idle">{{ __('helpdesk::helpdesk.inbox.right.last_view', ['time' => $rpLastActivity->diffForHumans()]) }}</div>
                @endif

                <a href="{{ $rpCurrentUrl }}" target="_blank" rel="noopener noreferrer"
                   class="bv-current-page-card"
                   title="{{ $rpCurrentUrl }}">
                    <i class="fas fa-circle bv-current-page-dot"></i>
                    @if($rpCurrentHost)<span class="bv-current-page-host">{{ $rpCurrentHost }}</span>@endif
                    <span class="bv-current-page-path">{{ \Illuminate\Support\Str::limit($rpCurrentPath ?? '/', 60) }}</span>
                    <i class="fas fa-arrow-up-right-from-square bv-current-page-ext"></i>
                </a>
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

            <div class="rsp-section bv-x78">
                <div class="lbl">
                    <i class="fas fa-route"></i> {{ __('helpdesk::helpdesk.inbox.right.visited_pages_heading') }}
                    <span class="r-tag bv-x69">{{ $rpVisitedPages->count() }}</span>
                    @if($rpHostName)
                        <span class="bv-pages-host" title="{{ $rpHostName }}">
                            <img src="https://www.google.com/s2/favicons?domain={{ $rpHostName }}&sz=32" alt="" width="14" height="14" loading="lazy">
                            <span>{{ $rpHostName }}</span>
                        </span>
                    @endif
                    <button type="button"
                            class="bv-right-section-edit"
                            id="bv-pages-refresh"
                            title="{{ __('helpdesk::helpdesk.inbox.right.refresh_pages_title') }}"
                            data-conv-id="{{ $rpConvo->id }}">
                        <i class="fas fa-arrows-rotate"></i>
                    </button>
                </div>

                @if($rpVisitedPages->isEmpty())
                    <div class="bv-tab-empty bv-tab-empty-sm">
                        <i class="fas fa-route"></i>
                        <div class="bv-tab-empty-sub">{{ __('helpdesk::helpdesk.inbox.right.no_pages_registered') }}</div>
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
                                                <span class="bv-page-duration" title="{{ __('helpdesk::helpdesk.inbox.right.time_on_page_title') }}">
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
                                {{ __('helpdesk::helpdesk.inbox.right.show_more_prefix') }} <span id="bv-pages-show-more-count">{{ min(100, $rpPageTotal - 10) }}</span> {{ __('helpdesk::helpdesk.inbox.right.show_more_suffix') }}
                                <span class="bv-pages-show-more-total">{{ __('helpdesk::helpdesk.inbox.right.remaining_count', ['count' => $rpPageTotal - 10]) }}</span>
                            </button>
                        @endif
                    </div>
                @endif
            </div>

            @endif {{-- /$rpWidgetSession --}}

        </div>
        @endif

        {{-- ── Tab: Pantalla (live view + screen share) ── --}}
        @if(($rpShowAssistTab ?? false) && helpdesk_feature_enabled('tab_assist'))
        <div class="bv-right-tab-content bv-tab-hidden" data-bv-tab-content="assist"
             data-conversation-id="{{ $rpConvo->id }}"
             data-enable-live-view="{{ $rpEnableLiveView ? '1' : '0' }}"
             data-enable-screen-share="{{ $rpEnableScreenShare ? '1' : '0' }}">

            @if($rpEnableLiveView)
            <div class="bv-right-section">
                <div class="bv-right-section-head">
                    <span class="bv-right-section-title"><i class="fas fa-eye bv-section-icon"></i> Live view</span>
                    <span class="bv-assist-status badge bg-secondary" id="hd-liveview-status-{{ $rpConvo->id }}">{{ __('helpdesk::helpdesk.inbox.right.waiting_label') }}</span>
                </div>
                <div class="hd-liveview-frame">
                    <div id="hd-liveview-player-{{ $rpConvo->id }}" class="hd-liveview-player">
                        <div class="hd-liveview-empty text-muted small p-3 text-center">
                            {{ __('helpdesk::helpdesk.inbox.right.awaiting_screen_share') }}
                        </div>
                    </div>
                    <button type="button"
                            class="hd-liveview-expand"
                            id="hd-liveview-expand-{{ $rpConvo->id }}"
                            title="{{ __('helpdesk::helpdesk.inbox.right.fullscreen_title') }}">
                        <i class="fas fa-expand"></i>
                    </button>
                </div>
                <div class="small text-muted mt-2">
                    {{ __('helpdesk::helpdesk.inbox.right.passwords_masked_notice') }}
                </div>
            </div>
            @endif

            @if($rpEnableScreenShare)
            <div class="bv-right-section">
                <div class="bv-right-section-head">
                    <span class="bv-right-section-title"><i class="fas fa-display bv-section-icon"></i> {{ __('helpdesk::helpdesk.inbox.right.screen_share_label') }}</span>
                </div>
                <div class="hd-webrtc-wrap">
                    <video id="hd-webrtc-video-{{ $rpConvo->id }}" class="hd-webrtc-video" autoplay muted playsinline></video>
                    <div class="hd-webrtc-empty text-muted small p-3 text-center" id="hd-webrtc-empty-{{ $rpConvo->id }}">
                        {{ __('helpdesk::helpdesk.inbox.right.waiting_screen_share') }}
                    </div>
                    <button type="button"
                            class="hd-liveview-expand"
                            id="hd-webrtc-expand-{{ $rpConvo->id }}"
                            title="{{ __('helpdesk::helpdesk.inbox.right.fullscreen_title_screen') }}">
                        <i class="fas fa-expand"></i>
                    </button>
                </div>
                <div class="d-flex gap-2 mt-2">
                    <button type="button"
                            class="btn btn-sm btn-primary"
                            id="hd-webrtc-request-{{ $rpConvo->id }}"
                            data-request-url="{{ route('manager.helpdesk.conversations.webrtc.request', $rpConvo) }}">
                        <i class="fas fa-hand-pointer me-1"></i> {{ __('helpdesk::helpdesk.inbox.right.request_screen_button') }}
                    </button>
                    <button type="button"
                            class="btn btn-sm btn-outline-secondary"
                            id="hd-webrtc-end-{{ $rpConvo->id }}"
                            data-end-url="{{ route('manager.helpdesk.conversations.webrtc.end', $rpConvo) }}">
                        <i class="fas fa-circle-stop me-1"></i> {{ __('helpdesk::helpdesk.inbox.right.end_screen_button') }}
                    </button>
                </div>
            </div>
            @endif
        </div>
        @endif

        {{-- ── Tab: Cliente 360 ── --}}
        @if($rpCust && helpdesk_feature_enabled('tab_customer360'))
        <div class="bv-right-tab-content bv-tab-hidden" data-bv-tab-content="customer-360">
            @include('helpdesk::helpdesk.conversations.partials._customer-360', ['conversation' => $rpConvo])
        </div>
        @endif

        {{-- ── Tabs PrestaShop (slot del módulo HelpdeskPrestashop) ── --}}
        @if($rpHasPs)
            @include('helpdeskprestashop::inbox-slots.right-panel-prestashop-tabs', ['rpCust' => $rpCust])
        @endif

        {{-- ── Tabs ERP (slot del módulo HelpdeskErp) ── --}}
        @if($rpHasErp)
            @include('helpdeskerp::inbox-slots.right-panel-erp-tabs', ['rpCust' => $rpCust])
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
                        <span class="bv-tk-lbl">{{ __('helpdesk::helpdesk.inbox.right.emails_label') }}</span>
                        <span class="bv-tk-sub" id="rpEmSub">—</span>
                    </div>
                    <button class="bv-tk-add-btn tt" data-tt="{{ __('helpdesk::helpdesk.inbox.right.new_email_tooltip') }}" data-bv-modal="email">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
            </div>

            {{-- Filter pills --}}
            <div class="bv-em-filter-row" id="rpEmFilterRow" style="display:none">
                <span class="bv-media-pill bv-em-tab-pill on" data-rp-em-filter="all">
                    {{ __('helpdesk::helpdesk.inbox.right.all_label') }} <span class="c" id="rpEmCountAll">0</span>
                </span>
                <span class="bv-media-pill bv-em-tab-pill" data-rp-em-filter="sent">
                    {{ __('helpdesk::helpdesk.inbox.right.emails_filter_sent') }} <span class="c" id="rpEmCountSent">0</span>
                </span>
                <span class="bv-media-pill bv-em-tab-pill" data-rp-em-filter="failed">
                    {{ __('helpdesk::helpdesk.inbox.right.emails_filter_failed') }} <span class="c" id="rpEmCountFailed">0</span>
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

@if(($rpShowTechnologyTab ?? false) && helpdesk_feature_enabled('tab_technology') && $rpConvo)
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

@if(($rpShowAssistTab ?? false) && helpdesk_feature_enabled('tab_assist') && $rpConvo)
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
/* "Solicitar pantalla" usa .btn-primary sin ningún override en este panel,
   así que caía en el azul por defecto de Bootstrap (#0d6efd) en vez del
   neutro que usa el resto de botones primarios del panel. */
.bv-right .btn-primary {
    background: var(--bv-text, #18181b);
    border-color: var(--bv-text, #18181b);
    color: #fff;
}
.bv-right .btn-primary:hover {
    background: #333;
    border-color: #333;
    color: #fff;
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
        <span class="title" id="hd-liveview-modal-title-{{ $rpConvo->id }}">{{ __('helpdesk::helpdesk.inbox.right.visitor_view_label') }}</span>
        <span class="ml"></span>
        <span class="bv-assist-status badge bg-secondary" id="hd-liveview-modal-status-{{ $rpConvo->id }}">{{ __('helpdesk::helpdesk.inbox.right.loading_label') }}</span>
        <button type="button" class="hd-liveview-modal-close" id="hd-liveview-modal-close-{{ $rpConvo->id }}" title="{{ __('helpdesk::helpdesk.inbox.right.close_title') }}">
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

@once
@push('scripts')
<script>
// Badge/boton de identidad del panel: abre el modal reutilizable de
// verificacion (definido en HelpdeskIntegration) y recarga el panel al
// validar, para reflejar el badge "Verificada" sin duplicar el render.
$(document).on('click', '.bv-identity-verify-trigger', function () {
    var customerId = $(this).data('customer-id');
    if (!customerId || typeof window.openCustomerIdentityVerification !== 'function') { return; }

    window.openCustomerIdentityVerification(customerId, function () {
        window.location.reload();
    });
});
</script>
@endpush
@endonce

@once
@push('scripts')
<script>
// Botón "re-sincronizar": redetecta el vínculo PrestaShop/gestión del cliente
// y recarga el panel para reflejar integraciones y pedidos actualizados.
$(document).on('click', '.bv-sync-commerce', function () {
    var convId = $(this).data('conv-id');
    if (!convId) { return; }
    var $btn = $(this).prop('disabled', true);
    $btn.find('i').addClass('fa-spin');
    $.ajax({
        url: '/panel/helpdesk/conversations/' + convId + '/sync-commerce',
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
    }).done(function (res) {
        if (window.toastr) { toastr.success((res && res.message) || 'Cliente sincronizado.'); }
        setTimeout(function () { window.location.reload(); }, 600);
    }).fail(function (xhr) {
        var msg = (xhr.responseJSON && (xhr.responseJSON.error || xhr.responseJSON.message)) || 'No se pudo sincronizar.';
        if (window.toastr) { toastr.error(msg); } else { alert(msg); }
        $btn.prop('disabled', false).find('i').removeClass('fa-spin');
    });
});
</script>
@endpush
@endonce

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
                '<i class="far fa-envelope-open bv-x79"></i>' +
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
