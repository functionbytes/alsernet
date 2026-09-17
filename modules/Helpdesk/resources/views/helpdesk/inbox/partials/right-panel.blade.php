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
    $_rpLookupUrl    = \Illuminate\Support\Facades\Route::has('manager.engagement.customer-data.lookup')
        ? route('manager.engagement.customer-data.lookup')
        : '';
@endphp
<aside class="bv-right"
    data-customer-id="{{ $_rpConvoEarly?->customer_id ?? '' }}"
    data-inbox-id="{{ $_rpInboxId }}"
    data-has-ps="{{ $_rpHasPs ? '1' : '' }}"
    data-has-erp="{{ $_rpHasErp ? '1' : '' }}"
    data-lookup-email="{{ $_rpEmail }}"
    data-lookup-ps-id="{{ $_rpPsId }}"
    data-lookup-erp-id="{{ $_rpErpId }}"
    data-lookup-url="{{ $_rpLookupUrl }}"
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
    data-csrf="{{ csrf_token() }}"
    data-email-feature="{{ helpdesk_feature_enabled('email') ? '1' : '0' }}">
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

        // E-commerce sync (job dispatch), auto-vinculo de documento y el mapping
        // sesión→conversación del widget ya no viven aquí: son side effects sin
        // salida al render, movidos a
        // ConversationsController::dispatchConversationOpenedSideEffects()
        // (QUAL-03), invocado una vez por buildConversationPaneData() en vez de
        // en cada render de este partial.

        // customer.externalIds ya viene eager-loaded desde
        // buildConversationPaneData() — loadMissing() en vez de load() para no
        // repetir la consulta en cada cambio de conversación.
        $rpCust?->loadMissing('externalIds');

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

                    // syncLink() (crea/re-apunta el vínculo) ya no corre aquí:
                    // ver dispatchConversationOpenedSideEffects() en el controller.
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }

        // Activity events: cargado bajo demanda por RightPanelTabController@activity
        // (ver pestaña "Actividad" más abajo) — antes se consultaba en cada render.

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

            // El cache put del mapping sesión→conversación ya no corre aquí:
            // ver dispatchConversationOpenedSideEffects() en el controller.
        }

        // Backwards-compat alias for places that already check $rpHasWidgetData
        $rpHasWidgetData = $rpShowTechnologyTab;

        // Archivos: cargado bajo demanda por RightPanelTabController@files
        // (ver pestaña "Archivos" más abajo) — antes se consultaban hasta 60
        // ConversationItem por cliente en cada render del panel derecho.

        // Event icon map: movido a right-panel-tabs/activity.blade.php (pestaña
        // "Actividad" cargada bajo demanda).

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
        {{-- Tab "Carritos" retirado: dependía por completo de AssistedCartController
             (listar/crear/editar/generar pedido), cuyas rutas están comentadas en
             HelpdeskPrestashop/routes/managers.php porque el módulo Ecommerce del
             que depende no se incluye en este proyecto. Ningún botón de la pestaña
             podía funcionar — solo mostraba "No se pudieron cargar los carritos". --}}
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
                <div class="lbl">
                    <i class="fa-solid fa-circle-info"></i> {{ __('helpdesk::helpdesk.inbox.right.conversation_state') }}
                    @can('helpdesk.settings.view')
                        <i class="far fa-calendar add" role="button" data-bv-modal="business-hours" title="{{ __('helpdesk::helpdesk.inbox.modals.business_hours_title') }}" aria-label="{{ __('helpdesk::helpdesk.inbox.modals.business_hours_title') }}"></i>
                    @endcan
                </div>
                <div class="rsp-kv rsp-kv-ctrl">
                    <span class="k">{{ __('helpdesk::helpdesk.inbox.right.status_label') }}</span>
                    <span class="v">
                        <button type="button" class="r-tag r-tag-btn" data-bv-modal="status" data-bv-is-open="{{ ($rpConvo?->status?->is_open ?? true) ? '1' : '0' }}">
                            {{ $rpStatusName }}
                            <i class="fa-solid fa-chevron-down"></i>
                        </button>
                    </span>
                </div>
                <div class="rsp-kv rsp-kv-ctrl">
                    <span class="k">{{ __('helpdesk::helpdesk.inbox.right.priority_label') }}</span>
                    <span class="v">
                        @php $rpPriorityMod = $priorityColors[$rpPriority] ?? ''; @endphp
                        <button type="button" class="r-tag r-tag-btn{{ $rpPriorityMod ? ' r-tag-'.$rpPriorityMod : '' }}" data-bv-modal="priority" data-bv-value="{{ $rpPriority }}">
                            {{ $priorityLabels[$rpPriority] ?? 'Normal' }}
                            <i class="fa-solid fa-chevron-down"></i>
                        </button>
                    </span>
                </div>
                <div class="rsp-kv rsp-kv-ctrl">
                    <span class="k">{{ __('helpdesk::helpdesk.inbox.right.agent_label') }}</span>
                    <span class="v">
                        <button type="button" class="r-tag r-tag-btn @if(!$rpConvo?->assignee) r-tag-muted @endif" data-bv-modal="assign" data-bv-assignee-id="{{ $rpConvo?->assignee_id }}">
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
                <div class="rsp-kv rsp-kv-ctrl">
                    <span class="k">{{ __('helpdesk::helpdesk.inbox.right.team_label') }}</span>
                    <span class="v">
                        <button type="button" class="r-tag r-tag-btn @if(!$rpConvo?->group) r-tag-muted @endif" data-bv-modal="move-to-team">
                            {{ $rpConvo?->group?->name ?? __('helpdesk::helpdesk.inbox.right.no_team') }}
                            <i class="fa-solid fa-chevron-down"></i>
                        </button>
                    </span>
                </div>
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
                    <div class="rsp-tag-wrap" id="rsp-tag-wrap">
                        @foreach($rpConvo->conversationTags as $tag)
                            <span class="r-tag" data-tag-id="{{ $tag->id }}">{{ $tag->name }}</span>
                        @endforeach
                    </div>
                @else
                    <div class="rsp-empty" id="rsp-tag-wrap">{{ __('helpdesk::helpdesk.inbox.right.no_tags') }}</div>
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
                    // Catálogo cacheado (ver IntegrationProvider::allCached) — antes
                    // era una query SQL sin caché en cada apertura/cambio de
                    // conversación del inbox, duplicando además la misma consulta
                    // que ya hacía CustomerIntegrationService::buildPayload().
                    $rpProviders = \Modules\HelpdeskIntegration\Models\IntegrationProvider::allCached();

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
                            'platform'  => $rpProvider->platform,
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
                            'platform'  => $rpPlatform,
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
                            'platform'  => 'prestashop',
                        ];

                        $rpIntegrationsList[] = [
                            'icon'  => 'fas fa-clipboard-list',
                            'name'  => 'Gestión (ERP)',
                            'id'    => $rpExternalErpId ? 'ERP-'.$rpExternalErpId : 'sin vincular',
                            'connected' => (bool) $rpExternalErpId,
                            'platform'  => 'erp',
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
                        <button type="button" class="r-tag r-tag-btn r-tag-icon bv-identity-verify-trigger ms-auto"
                                data-customer-id="{{ $rpCust->id }}"
                                data-bs-toggle="tooltip" data-bs-placement="bottom"
                                data-bs-title="{{ __('helpdesk::helpdesk.inbox.right.verify_identity') }}">
                            <i class="fa-solid fa-user-check" aria-hidden="true"></i>
                            <span class="visually-hidden">{{ __('helpdesk::helpdesk.inbox.right.verify_identity') }}</span>
                        </button>
                        <button type="button" class="r-tag r-tag-btn r-tag-icon" data-bv-modal="link-customer"
                                data-bs-toggle="tooltip" data-bs-placement="bottom"
                                data-bs-title="{{ __('helpdesk::helpdesk.inbox.right.link_customer_short') }}">
                            <i class="fa-solid fa-link" aria-hidden="true"></i>
                            <span class="visually-hidden">{{ __('helpdesk::helpdesk.inbox.right.link_customer_short') }}</span>
                        </button>
                    @endif
                </div>
            </div>
            @endif
            @if(helpdesk_feature_enabled('rp_integrations'))
            @php
                // Mismo gate que el botón de "abrir integraciones" del encabezado —
                // si el modal no está disponible, las filas no deben parecer clicables.
                $rpIntegrationsModalAvailable = $rpCust && helpdesk_integration_enabled() && view()->exists('helpdeskintegration::modals.customer-integrations');
            @endphp
            <div class="rsp-section">
                <div class="lbl">
                    <i class="fa-solid fa-plug"></i> {{ __('helpdesk::helpdesk.inbox.right.integrations_heading') }}
                    @if($rpConvo?->id)
                        <button type="button" class="btn btn-sm btn-link p-0 ms-auto bv-sync-commerce"
                                data-conv-id="{{ $rpConvo->id }}"
                                data-customer-id="{{ $rpCust?->id }}"
                                data-bs-toggle="tooltip" data-bs-placement="bottom"
                                data-bs-title="{{ __('helpdesk::helpdesk.inbox.right.resync_title') }}"
                                aria-label="{{ __('helpdesk::helpdesk.inbox.right.resync_aria') }}">
                            <i class="fa-solid fa-arrows-rotate" aria-hidden="true"></i>
                        </button>
                    @endif
                    @if($rpIntegrationsModalAvailable)
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
                            <div class="rsp-integration @if(!$intg['connected']) is-disconnected @endif @if($rpIntegrationsModalAvailable) is-clickable @endif"
                                 @if($rpIntegrationsModalAvailable)
                                     role="button" tabindex="0"
                                     @if(! empty($intg['platform']))
                                         data-platform="{{ $intg['platform'] }}"
                                     @else
                                         data-bv-modal="customer-integrations"
                                     @endif
                                     title="{{ __('helpdesk::helpdesk.inbox.right.view_customer_integrations') }}"
                                 @endif>
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

                @php
                    // Aviso de "se buscó en gestión y no apareció". Solo se pinta
                    // cuando la búsqueda automática ya corrió y falló: un cliente
                    // que nunca se ha buscado no muestra nada, porque no hay nada
                    // que contar todavía. El vínculo manda sobre el estado — si
                    // alguien lo vinculó a mano después, el aviso sobra.
                    $rpErpLookupFailed = $rpCust
                        && helpdesk_erp_enabled()
                        && $rpCust->erpLookupFailed()
                        && ! $rpCust->externalIds->firstWhere('platform', 'erp');
                @endphp
                @if($rpErpLookupFailed)
                    <div class="rsp-erp-missing" data-customer-id="{{ $rpCust->id }}"
                         data-relink-url="{{ route('manager.helpdesk.erp.customers.relink', ['customerId' => $rpCust->id]) }}">
                        <div class="rsp-erp-missing-text">
                            <i class="fa-solid fa-circle-question" aria-hidden="true"></i>
                            <span>{{ $rpCust->erp_lookup_status === 'error'
                                ? 'Gestión no respondió al buscar este cliente.'
                                : 'Este remitente no está en gestión.' }}</span>
                        </div>
                        <div class="rsp-erp-missing-actions">
                            @if($rpIntegrationsModalAvailable)
                                <button type="button" class="rsp-erp-btn" data-bv-modal="customer-integrations" data-platform="erp">Buscar</button>
                            @endif
                            <button type="button" class="rsp-erp-btn" data-bv-erp-relink>Reintentar</button>
                        </div>
                    </div>
                @endif
            </div>
            @endif
        </div>
        @endif

        {{-- ── Tab: Carritos ── (contenido movido al módulo HelpdeskPrestashop,
             ver inbox-slots/right-panel-prestashop-tabs.blade.php) --}}

        {{-- ── Tab: Archivos — cargado bajo demanda (RightPanelTabController@files) ── --}}
        @if(helpdesk_feature_enabled('tab_files'))
        <div class="bv-right-tab-content bv-tab-hidden" data-bv-tab-content="files" id="bv-files-tab"
             data-conv-id="{{ $rpConvo?->id ?? '' }}">
            <div class="bv-em-loading"><i class="fas fa-spinner fa-spin"></i></div>
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

        {{-- ── Tab: Anteriores — cargado bajo demanda (RightPanelTabController@previous) ── --}}
        @if(helpdesk_feature_enabled('tab_previous'))
        <div class="bv-right-tab-content bv-tab-hidden" data-bv-tab-content="previous" id="bv-previous-tab"
             data-conv-id="{{ $rpConvo?->id ?? '' }}">
            <div class="bv-em-loading"><i class="fas fa-spinner fa-spin"></i></div>
        </div>
        @endif

        {{-- ── Tab: Actividad — cargado bajo demanda (RightPanelTabController@activity) ── --}}
        @if(helpdesk_feature_enabled('tab_activity'))
        <div class="bv-right-tab-content bv-tab-hidden" data-bv-tab-content="activity" id="bv-activity-tab"
             data-conv-id="{{ $rpConvo?->id ?? '' }}">
            <div class="bv-em-loading"><i class="fas fa-spinner fa-spin"></i></div>
        </div>
        @endif

        {{-- ── Tab: Tecnología ── --}}
        @if($rpShowTechnologyTab && helpdesk_feature_enabled('tab_technology'))
        <div class="bv-right-tab-content bv-tab-hidden" data-bv-tab-content="technology"
             data-conv-id="{{ $rpConvo?->id ?? '' }}">

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
             data-enable-screen-share="{{ $rpEnableScreenShare ? '1' : '0' }}"
             data-history-url="{{ route('manager.helpdesk.conversations.livestream.history', $rpConvo) }}"
             data-ice-url="{{ route('manager.helpdesk.conversations.webrtc.ice', $rpConvo) }}"
             data-answer-url="{{ route('manager.helpdesk.conversations.webrtc.answer', $rpConvo) }}">

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
                        {{ __('helpdesk::helpdesk.inbox.right.request_screen_button') }}
                    </button>
                    <button type="button"
                            class="btn btn-sm btn-outline-secondary"
                            id="hd-webrtc-end-{{ $rpConvo->id }}"
                            data-end-url="{{ route('manager.helpdesk.conversations.webrtc.end', $rpConvo) }}">
                        {{ __('helpdesk::helpdesk.inbox.right.end_screen_button') }}
                    </button>
                </div>
            </div>
            @endif
        </div>
        @endif

        {{-- ── Tab: Cliente 360 — cargado bajo demanda (RightPanelTabController@customer360) ── --}}
        @if($rpCust && helpdesk_feature_enabled('tab_customer360'))
        <div class="bv-right-tab-content bv-tab-hidden" data-bv-tab-content="customer-360" id="bv-customer-360-tab"
             data-conv-id="{{ $rpConvo?->id ?? '' }}">
            <div class="bv-em-loading"><i class="fas fa-spinner fa-spin"></i></div>
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
            <div class="bv-em-filter-row bv-hidden" id="rpEmFilterRow">
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

@if(($rpShowAssistTab ?? false) && helpdesk_feature_enabled('tab_assist') && $rpConvo)

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
@endif
