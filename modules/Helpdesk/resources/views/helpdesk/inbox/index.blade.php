@extends('layouts.theme')

@section('title', 'Bandeja · Línea 2025')

@push('css')
    <link rel="preconnect" href="https://fonts.googleapis.com"/>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
    {{-- FE-10: cargar la fuente sin bloquear el render (media=print + onload); fallback sin JS --}}
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet" media="print" onload="this.media='all'"/>
    <noscript><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet"/></noscript>
    {{-- Identidad visual (tokens: colores, fonts, radius, shadows) --}}
    <link rel="stylesheet" href="{{ asset('vendor/helpdesk/conversations-identity.css') }}?v={{ @filemtime(public_path('vendor/helpdesk/conversations-identity.css')) }}"/>
    {{-- Componentes y layout --}}
    <link rel="stylesheet" href="{{ asset('vendor/helpdesk/conversations.css') }}?v={{ @filemtime(public_path('vendor/helpdesk/conversations.css')) }}"/>
    {{-- Pedidos y carritos (commerce) --}}
    <link rel="stylesheet" href="{{ asset('vendor/helpdesk/conversations-commerce.css') }}?v={{ @filemtime(public_path('vendor/helpdesk/conversations-commerce.css')) }}"/>
    {{-- a11y: color contrast fixes — darken muted grays from #71717a (4.39:1) to #636369 (4.64:1) on #f4f4f5 --}}
    <style>
    .bv-day-sep span,
    .bv-conv .preview,
    .r-tag.r-tag-muted { color: #636369; }

    /* Toastr: el CSS del tema no aplica background-color a los tipos de toast, así
       que salían con fondo blanco y texto ilegible. Forzamos fondo oscuro + texto
       blanco + un color por tipo, de forma independiente al CSS del tema. */
    #toast-container > div {
        background-color: #18181b !important;
        color: #fff !important;
        opacity: 1 !important;
        border-radius: 10px !important;
        box-shadow: 0 8px 30px rgba(0, 0, 0, .28) !important;
    }
    #toast-container > .toast-success { background-color: #15803d !important; }
    #toast-container > .toast-error   { background-color: #b91c1c !important; }
    #toast-container > .toast-warning { background-color: #b45309 !important; }
    #toast-container > .toast-info    { background-color: #1d4ed8 !important; }
    #toast-container > div .toast-title,
    #toast-container > div .toast-message,
    #toast-container > div .toast-close-button { color: #fff !important; }
    </style>
@endpush

@section('content_full_width', true)

@section('content')
<div class="conversations" data-theme="light" data-right="on" data-bv-mobile-tab="list">

    {{-- NAV --}}
    <nav class="bv-nav">
        @php
            $isUnread   = request()->boolean('unread');
            $isMine     = request()->boolean('mine');
            $isUrgent   = request()->boolean('urgent');
            $isBot      = request()->boolean('bot');
            $isPending  = request()->input('status') === 'pending';
            $isClosed   = request()->input('status') === 'closed';
            $isArchived = request()->boolean('archived') || request()->input('archived') === '1';
            $activeChannel = request()->input('channel');
            $activeInboxFilter = request()->input('inbox');
            $activeTagFilter = request()->input('tag');
            $activeGroupFilter = request()->input('group');
            $isAll = ! $isUnread && ! $isMine && ! $isUrgent && ! $isBot && ! $isPending && ! $isClosed && ! $isArchived
                && ! $activeChannel && ! $activeInboxFilter
                && ! $activeTagFilter && ! $activeGroupFilter;

            $authUser = auth()->user();
            $userName = $authUser?->name ?? 'Usuario';
            $userInitials = collect(explode(' ', trim($userName)))
                ->filter()->take(2)
                ->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))
                ->implode('');
            $activeCount = $totalConversations ?? 0;
        @endphp

        {{-- Header del nav --}}
        <div class="bv-nav-head">
            <div>
                <div class="bv-nav-head-title">{{ __('helpdesk::helpdesk.inbox.title') }}</div>
                <div class="bv-nav-head-sub">{{ __('helpdesk::helpdesk.inbox.team_inbox') }}</div>
            </div>
            <button type="button"
                    class="bv-nav-head-btn"
                    data-bv-modal="newconv"
                    title="{{ __('helpdesk::helpdesk.inbox.new_conversation') }}"
                    aria-label="{{ __('helpdesk::helpdesk.inbox.new_conversation') }}">
                <i class="fas fa-plus"></i>
            </button>
        </div>

        {{-- Tarjeta del usuario activo — abre el modal de disponibilidad (#60 away-mode) --}}
        <div class="bv-nav-user-card" data-bv-modal="away-mode" role="button" tabindex="0"
             title="{{ __('helpdesk::helpdesk.inbox.change_availability') }}" aria-label="{{ __('helpdesk::helpdesk.inbox.change_availability') }}">
            <div class="bv-nav-user-av">{{ $userInitials ?: 'U' }}</div>
            <div class="bv-nav-user-body">
                <div class="bv-nav-user-name">{{ $userName }}</div>
                <div class="bv-nav-user-status">
                    <span class="bv-nav-user-dot is-available"></span>{{ __('helpdesk::helpdesk.inbox.online_active', ['count' => $activeCount]) }}
                </div>
            </div>
            <i class="fas fa-chevron-down bv-nav-user-chevron"></i>
        </div>

        <div class="bv-nav-scroll">
        <div class="bv-nav-section">
            <div class="bv-nav-label">
                {{ __('helpdesk::helpdesk.inbox.views') }}
                <span class="bv-x1">
                    <a class="bv-x2" href="{{ route('manager.helpdesk.conversations.kanban') }}"
                       title="{{ __('helpdesk::helpdesk.inbox.kanban_view') }}"
                       aria-label="{{ __('helpdesk::helpdesk.inbox.kanban_view') }}">
                        <i class="fas fa-table-columns"></i>
                    </a>
                    <a href="#" class="bv-nav-label-add" id="bv-save-view-btn" title="{{ __('helpdesk::helpdesk.inbox.save_current_view') }}" aria-label="{{ __('helpdesk::helpdesk.inbox.save_current_view') }}">
                        <i class="fas fa-plus" aria-hidden="true"></i>
                    </a>
                </span>
            </div>
            <a href="{{ route('manager.helpdesk.conversations.index', ['unread' => 1]) }}"
               class="bv-nav-item {{ $isUnread ? 'on' : '' }}" title="{{ __('helpdesk::helpdesk.inbox.unread') }}">
                <i class="far fa-envelope bv-vi-unread"></i>
                <span class="bv-nav-item-label">{{ __('helpdesk::helpdesk.inbox.unread') }}</span>
                <span class="c" data-counter="unread">{{ $sidebarCounters['unread'] ?? 0 }}</span>
            </a>
            <a href="{{ route('manager.helpdesk.conversations.index') }}"
               class="bv-nav-item {{ $isAll ? 'on' : '' }}" title="{{ __('helpdesk::helpdesk.inbox.all') }}">
                <i class="fas fa-inbox bv-vi-all"></i>
                <span class="bv-nav-item-label">{{ __('helpdesk::helpdesk.inbox.all') }}</span>
                <span class="c" data-counter="total">{{ $totalConversations ?? 0 }}</span>
            </a>
            <a href="{{ route('manager.helpdesk.conversations.index', ['mine' => 1]) }}"
               class="bv-nav-item {{ $isMine ? 'on' : '' }}" title="{{ __('helpdesk::helpdesk.inbox.mine') }}">
                <i class="fas fa-user bv-vi-mine"></i>
                <span class="bv-nav-item-label">{{ __('helpdesk::helpdesk.inbox.mine') }}</span>
                <span class="c" data-counter="mine">{{ $sidebarCounters['mine'] ?? 0 }}</span>
            </a>
            <a href="{{ route('manager.helpdesk.conversations.index', ['urgent' => 1]) }}"
               class="bv-nav-item {{ $isUrgent ? 'on' : '' }}" title="{{ __('helpdesk::helpdesk.inbox.urgent') }}">
                <i class="fas fa-fire bv-vi-urgent"></i>
                <span class="bv-nav-item-label">{{ __('helpdesk::helpdesk.inbox.urgent') }}</span>
                <span class="c" data-counter="urgent">{{ $sidebarCounters['urgent'] ?? 0 }}</span>
            </a>
            @if($isBot || ($sidebarCounters['bot'] ?? 0) > 0)
            <a href="{{ route('manager.helpdesk.conversations.index', ['bot' => 1]) }}"
               class="bv-nav-item {{ $isBot ? 'on' : '' }}" title="{{ __('helpdesk::helpdesk.inbox.in_bot_title') }}">
                <i class="fas fa-robot bv-vi-bot"></i>
                <span class="bv-nav-item-label">{{ __('helpdesk::helpdesk.inbox.in_bot') }}</span>
                <span class="c">{{ $sidebarCounters['bot'] ?? 0 }}</span>
            </a>
            @endif
            <a href="{{ route('manager.helpdesk.conversations.index', ['status' => 'pending']) }}"
               class="bv-nav-item {{ $isPending ? 'on' : '' }}" title="{{ __('helpdesk::helpdesk.inbox.pending') }}">
                <i class="far fa-clock bv-vi-pending"></i>
                <span class="bv-nav-item-label">{{ __('helpdesk::helpdesk.inbox.pending') }}</span>
                <span class="c">{{ $sidebarCounters['pending'] ?? 0 }}</span>
            </a>
            <a href="{{ route('manager.helpdesk.conversations.index', ['status' => 'closed']) }}"
               class="bv-nav-item {{ $isClosed ? 'on' : '' }}" title="{{ __('helpdesk::helpdesk.inbox.closed') }}">
                <i class="fas fa-circle-check bv-vi-closed"></i>
                <span class="bv-nav-item-label">{{ __('helpdesk::helpdesk.inbox.closed') }}</span>
                <span class="c">{{ $sidebarCounters['closed'] ?? 0 }}</span>
            </a>
            @php
                $isBlocked = request('view') === 'blocked';
                $isSpam    = request('view') === 'spam';
                $isDeleted = request('view') === 'deleted';
            @endphp
            <a href="{{ route('manager.helpdesk.conversations.index', ['view' => 'blocked']) }}"
               class="bv-nav-item {{ $isBlocked ? 'on' : '' }}" title="{{ __('helpdesk::helpdesk.inbox.blocked_title') }}">
                <i class="fas fa-ban bv-vi-blocked"></i>
                <span class="bv-nav-item-label">{{ __('helpdesk::helpdesk.inbox.blocked') }}</span>
                <span class="c">{{ $sidebarCounters['blocked'] ?? 0 }}</span>
            </a>
            <a href="{{ route('manager.helpdesk.conversations.index', ['view' => 'spam']) }}"
               class="bv-nav-item {{ $isSpam ? 'on' : '' }}" title="{{ __('helpdesk::helpdesk.inbox.spam_title') }}">
                <i class="fas fa-shield-halved bv-vi-spam"></i>
                <span class="bv-nav-item-label">{{ __('helpdesk::helpdesk.inbox.spam') }}</span>
                <span class="c">{{ $sidebarCounters['spam'] ?? 0 }}</span>
            </a>
            <a href="{{ route('manager.helpdesk.conversations.index', ['view' => 'deleted']) }}"
               class="bv-nav-item {{ $isDeleted ? 'on' : '' }}" title="{{ __('helpdesk::helpdesk.inbox.deleted_title') }}">
                <i class="fas fa-trash-can bv-vi-deleted"></i>
                <span class="bv-nav-item-label">{{ __('helpdesk::helpdesk.inbox.deleted') }}</span>
                <span class="c">{{ $sidebarCounters['deleted'] ?? 0 }}</span>
            </a>
        </div>

        @if(! empty($sidebarInboxes) && $sidebarInboxes->count() > 0)
        @php
            $activeInboxId = (int) request('inbox');
            // No brand colors here (Facebook blue, Instagram pink, etc.) —
            // every sidebar icon shares the same neutral color, matching
            // Vistas/Equipos/Etiquetas. Brand logos are kept (still the
            // clearest way to recognize the channel at a glance), just
            // uncolored.
            $channelIconMap = [
                'whatsapp'   => 'fab fa-whatsapp',
                'facebook'   => 'fab fa-facebook-messenger',
                'instagram'  => 'fab fa-instagram',
                'email'      => 'far fa-envelope',
                'web'        => 'far fa-comment-dots',
                'sms'        => 'fas fa-mobile-screen-button',
                'prestashop' => 'fas fa-store',
            ];
        @endphp
        <div class="bv-nav-section">
            <div class="bv-nav-label">{{ __('helpdesk::helpdesk.inbox.inboxes') }}</div>
            @foreach($sidebarInboxes as $sbInbox)
                @php
                    $iconClass = $sbInbox->icon ?: ($channelIconMap[$sbInbox->channel_type] ?? 'fas fa-inbox');
                @endphp
                <a href="{{ route('manager.helpdesk.conversations.index', ['inbox' => $sbInbox->id]) }}"
                   class="bv-nav-item {{ $activeInboxId === (int) $sbInbox->id ? 'on' : '' }}"
                   title="{{ $sbInbox->name }}">
                    <i class="{{ $iconClass }}"></i>
                    <span class="bv-nav-item-label">{{ $sbInbox->name }}</span>
                    <span class="c">{{ $sbInbox->conversations_count ?? 0 }}</span>
                </a>
            @endforeach
        </div>
        @endif

        @php
            // Themed per-team icon by `key` — falls back to the generic
            // fa-users for any team without a mapped key (new teams, etc.).
            $groupIconMap = [
                'general_support'   => 'fas fa-headset',
                'technical_support' => 'fas fa-screwdriver-wrench',
                'billing_support'   => 'fas fa-file-invoice-dollar',
                'premium_support'   => 'fas fa-crown',
                'returns_logistics' => 'fas fa-truck-fast',
            ];
        @endphp
        <div class="bv-nav-section">
            <div class="bv-nav-label">{{ __('helpdesk::helpdesk.inbox.teams') }}</div>
            @forelse(($groups ?? collect()) as $group)
                <a href="{{ route('manager.helpdesk.conversations.index', ['group' => $group->id]) }}"
                   class="bv-nav-item {{ request('group') == $group->id ? 'on' : '' }}"
                   data-bv-team-id="{{ $group->id }}"
                   data-bv-droptarget="team"
                   title="{{ $group->name }}">
                    <i class="{{ $groupIconMap[$group->key] ?? 'fas fa-users' }}"></i>
                    <span class="bv-nav-item-label">{{ $group->name }}</span>
                    <span class="c">{{ $group->conversations_count ?? '' }}</span>
                </a>
            @empty
                <span class="bv-nav-empty">{{ __('helpdesk::helpdesk.inbox.no_teams') }}</span>
            @endforelse
        </div>

        <div class="bv-nav-section">
            <div class="bv-nav-label">
                {{ __('helpdesk::helpdesk.inbox.tags') }}
                <a href="#" class="bv-nav-label-add" data-bv-modal="tags" title="{{ __('helpdesk::helpdesk.inbox.manage_tags') }}">
                    <i class="fas fa-plus"></i>
                </a>
            </div>
            @php
                $activeTagId = (int) request('tag');
                // Themed per-tag icon by `slug` — falls back to the generic
                // fa-tag for any tag without a mapped slug (custom tags, the
                // seeded "técnico-30" test tag, etc.).
                $tagIconMap = [
                    'urgente'         => 'fas fa-fire',
                    'bug'             => 'fas fa-bug',
                    'consulta'        => 'fas fa-circle-question',
                    'facturacion'     => 'fas fa-file-invoice-dollar',
                    'onboarding'      => 'fas fa-rocket',
                    'seguimiento'     => 'far fa-eye',
                    'feedback'        => 'fas fa-thumbs-up',
                    'vip'             => 'fas fa-crown',
                    'spam'            => 'fas fa-shield-halved',
                    'feature-request' => 'fas fa-lightbulb',
                ];
            @endphp
            @forelse(($inboxTags ?? collect()) as $tag)
                <a href="{{ route('manager.helpdesk.conversations.index', ['tag' => $tag->id]) }}"
                   class="bv-nav-item bv-nav-item--tag {{ $activeTagId === (int) $tag->id ? 'on' : '' }}"
                   data-bv-tag-id="{{ $tag->id }}"
                   title="{{ $tag->name }}">
                    <i class="{{ $tagIconMap[$tag->slug] ?? 'fas fa-tag' }}"></i>
                    <span class="bv-nav-item-label">{{ $tag->name }}</span>
                    <span class="c">{{ $tag->conversations_count ?? 0 }}</span>
                </a>
            @empty
                <span class="bv-nav-empty">{{ __('helpdesk::helpdesk.inbox.no_tags') }}</span>
            @endforelse
        </div>

        {{-- Vistas guardadas del usuario --}}
        @php
            $savedViews = ($views ?? collect())->filter(fn ($v) => !$v->is_system && !$v->is_default);
        @endphp
        @if ($savedViews->isNotEmpty())
        <div class="bv-nav-section" data-section="saved-views">
            <div class="bv-nav-label">{{ __('helpdesk::helpdesk.inbox.my_views') }}</div>
            @foreach ($savedViews as $sv)
                @php
                    $svFilters = $sv->filters ?? [];
                    $svHref = route('manager.helpdesk.conversations.index', $svFilters);
                @endphp
                <a href="{{ $svHref }}"
                   class="bv-nav-item bv-nav-saved-view bv-x3"
                   data-view-id="{{ $sv->id }}">
                    <i class="fas fa-star bv-x4"></i>
                    <span class="bv-nav-view-name bv-x5">{{ $sv->name }}</span>
                    <button type="button"
                            class="bv-nav-view-del bv-x6"
                            data-view-id="{{ $sv->id }}"
                            title="{{ __('helpdesk::helpdesk.inbox.delete_view') }}"
                            aria-label="{{ __('helpdesk::helpdesk.inbox.delete_view') }}">×</button>
                </a>
            @endforeach
        </div>
        @else
        <div class="bv-nav-section" data-section="saved-views"></div>
        @endif

        </div>{{-- /bv-nav-scroll --}}
    </nav>

    {{-- LIST --}}
    @include('helpdesk::helpdesk.inbox.partials.list')

    {{-- THREAD --}}
    @include('helpdesk::helpdesk.inbox.partials.thread')

    {{-- RIGHT PANEL --}}
    @include('helpdesk::helpdesk.inbox.partials.right-panel')

    {{-- BULK ACTIONS BAR --}}
    @include('helpdesk::helpdesk.inbox.partials.bulk-bar')

    {{-- STATUSBAR --}}
    @php
        $sbm = $statusbarMetrics ?? [];
        $sbChannels = (int) ($sbm['active_channels'] ?? 0);
        $sbAgents   = (int) ($sbm['agents_online'] ?? 0);
        $sbSla      = (int) ($sbm['sla_avg_seconds'] ?? 0);
        $sbResolved = (int) ($sbm['resolved_today'] ?? 0);

        $slaLabel = $sbSla > 0
            ? ($sbSla >= 3600
                ? floor($sbSla / 3600).'h '.floor(($sbSla % 3600) / 60).'m'
                : ($sbSla >= 60
                    ? floor($sbSla / 60).'m '.($sbSla % 60).'s'
                    : $sbSla.'s'))
            : '—';
    @endphp
    <div class="bv-statusbar">
        <span class="sb-item"><span class="dot"></span>Conectado · {{ $sbChannels }} {{ $sbChannels === 1 ? 'canal' : 'canales' }}</span>
        <span class="sep">│</span>
        <span class="sb-item"><i class="fas fa-users"></i>{{ $sbAgents }} {{ $sbAgents === 1 ? 'agente' : 'agentes' }} en línea</span>
        <span class="sep">│</span>
        <span class="sb-item"><i class="fas fa-gauge-high"></i>SLA medio hoy: {{ $slaLabel }}</span>
        <span class="sep">│</span>
        <span class="sb-item"><i class="far fa-circle-check"></i>{{ $sbResolved }} {{ $sbResolved === 1 ? 'resuelta' : 'resueltas' }}</span>
        <span class="spacer"></span>
        <span class="sb-item">v4.0 · Refinamientos</span>
        <span class="sep">│</span>
        {{-- Notificaciones push: toggle per-session --}}
        <button type="button"
                id="bv-notif-toggle"
                class="bv-sb-btn"
                title="{{ __('helpdesk::helpdesk.inbox.desktop_notifications') }}"
                aria-label="{{ __('helpdesk::helpdesk.inbox.desktop_notifications') }}">
            <i class="fas fa-bell"></i>
        </button>
    </div>

    {{-- MOBILE BOTTOM NAVIGATION --}}
    @include('helpdesk::helpdesk.inbox.partials.mobile-tabs')

</div>

{{-- MODALES --}}
@include('helpdesk::helpdesk.inbox.partials.modals')
@endsection

@push('scripts')
    <script src="{{ asset('vendor/helpdesk/conversations.js') }}?v={{ @filemtime(public_path('vendor/helpdesk/conversations.js')) }}" defer></script>

    {{-- Listener global de la bandeja: nuevas conversaciones / nuevos mensajes --}}
    <script>
    (function () {
        // UI-05: live-region oculta para anunciar eventos dinámicos a lectores de pantalla.
        window.bvAnnounce = window.bvAnnounce || function (message) {
            var $live = $('#bv-sr-live');
            if (!$live.length) {
                $live = $('<div id="bv-sr-live" class="visually-hidden" role="status" aria-live="polite" aria-atomic="true"></div>');
                $('body').append($live);
            }
            // Vaciar y re-escribir fuerza el re-anuncio aunque el texto se repita.
            $live.text('');
            window.setTimeout(function () { $live.text(message); }, 50);
        };

        function setupInboxListener() {
            if (typeof window.Echo === 'undefined') {
                console.warn('[Inbox] Echo not ready, retrying in 500ms');
                setTimeout(setupInboxListener, 500);
                return;
            }

            const myId = parseInt(document.querySelector('meta[name="user-id"]')?.content || '0', 10);
            const inboxChannel = window.Echo.private('helpdesk.inbox');
            console.log('[Inbox] Subscribing to private-helpdesk.inbox');

            function refreshConversationList(done) {
                const $list = $('.bv-list').first();
                if (!$list.length) {
                    console.warn('[Inbox] .bv-list not found, cannot refresh');
                    if (typeof done === 'function') done();
                    return;
                }
                const url = "{{ route('manager.helpdesk.conversations.list') }}";
                const currentParams = new URLSearchParams(window.location.search);
                $.get(url, Object.fromEntries(currentParams)).done(function (resp) {
                    if (resp && typeof resp.html === 'string') {
                        $list.replaceWith(resp.html);
                    }
                }).fail(function (xhr) {
                    console.error('[Inbox] List refresh failed:', xhr.status);
                }).always(function () {
                    if (typeof done === 'function') done();
                });
            }

            let refreshTimer = null;
            let refreshInflight = false;
            let pendingRefresh = false;
            function scheduleRefresh() {
                if (refreshInflight) {
                    pendingRefresh = true;
                    return;
                }
                clearTimeout(refreshTimer);
                refreshTimer = setTimeout(function () {
                    refreshInflight = true;
                    pendingRefresh = false;
                    refreshConversationList(function () {
                        refreshInflight = false;
                        if (pendingRefresh) scheduleRefresh();
                    });
                }, 50);
            }

            // Single listener: same event reaches the inbox channel. Sidebar update only.
            // Thread bubble rendering is handled by the per-conversation listener below.
            inboxChannel.listen('.item.created', function (e) {
                const msg = e.message || {};
                // Los mensajes de actividad (etiqueta, asignación, estado…) no deben
                // pisar el preview del último mensaje real en la lista lateral.
                if (msg.type === 'activity') return;
                if (msg.user_id && parseInt(msg.user_id, 10) === myId) return;

                const conv = e.conversation || {};
                const isNew = !!e.is_new_conversation;
                const isViewing = parseInt(new URLSearchParams(window.location.search).get('selected') || '0', 10) === parseInt(conv.id, 10);

                const $existing = $('.bv-conv[data-bv-conv-id="' + conv.id + '"]');
                if (!isNew && $existing.length) {
                    patchConvItem($existing, conv, msg, isViewing);
                } else {
                    scheduleRefresh();
                }

                // Push notification when message is incoming and agent is not viewing it
                const isIncoming = !msg.user_id && (msg.author_id || msg.is_incoming);
                if (isIncoming && (document.visibilityState === 'hidden' || !isViewing)) {
                    const customerName = conv.customer_name || conv.subject || 'Nuevo mensaje';
                    const preview = (msg.body || '').slice(0, 100);
                    const avatar = conv.customer_avatar || null;
                    if (typeof window.showInboxPushNotif === 'function') {
                        window.showInboxPushNotif(conv.id, customerName, preview, avatar);
                    }
                }
            });

            // Patch a conversation item in-place: update last message preview,
            // bump unread badge, and move to top — all without an AJAX call.
            function patchConvItem($item, conv, msg, isViewing) {
                const preview = (msg.body || '').slice(0, 100);
                if (preview) {
                    $item.find('.bv-conv-preview, .bv-conv-last-msg').first().text(preview);
                }

                $item.find('.bv-conv-time').first().text('ahora');

                if (!isViewing && msg.is_incoming) {
                    const $badge = $item.find('.bv-conv-unread');
                    if ($badge.length) {
                        const n = parseInt($badge.text() || '0', 10);
                        $badge.text(n + 1).removeClass('d-none').show();
                    } else {
                        $item.addClass('unread');
                    }
                }

                // Move to top of its group
                const $group = $item.closest('.bv-conv-group, .bv-conv-stack, .bv-conv-list');
                if ($group.length && $item.prev().length) {
                    $item.detach();
                    $group.find('.bv-conv-group-head, .bv-conv-stack-head').first().after($item);
                    if (!$item.parent().is($group)) {
                        $group.prepend($item);
                    }
                }

                $item.addClass('bv-conv-flash');
                setTimeout(() => $item.removeClass('bv-conv-flash'), 1200);
            }

            // Also refresh when an item.created arrives on any open conversation channel,
            // because counters (last_message_at, unread badge) change.
            window.addEventListener('inbox:incoming-message', scheduleRefresh);

            window.__hdInboxListenerReady = true;
            console.log('[Inbox] Listener registered');
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', setupInboxListener);
        } else {
            setupInboxListener();
        }
    })();
    </script>

    {{-- Push notifications (Web Notification API) --}}
    <script>
    (function () {
        'use strict';

        const STORAGE_KEY = 'bv:notif:enabled';
        const SESSION_KEY = 'bv:notif:asked';

        function notifEnabled() {
            return localStorage.getItem(STORAGE_KEY) !== 'false';
        }

        function setNotifEnabled(val) {
            localStorage.setItem(STORAGE_KEY, val ? 'true' : 'false');
        }

        // Update bell button appearance
        function syncBellUI() {
            const $btn = $('#bv-notif-toggle');
            const enabled = notifEnabled() && Notification.permission === 'granted';
            $btn.find('i').toggleClass('fa-bell', enabled).toggleClass('fa-bell-slash', !enabled);
            $btn.attr('title', enabled ? 'Desactivar notificaciones' : 'Activar notificaciones');
        }

        // Ask for permission (once per session, via a friendly toast)
        function requestPermissionOnce() {
            if (!window.Notification) return;
            if (Notification.permission !== 'default') return;
            if (sessionStorage.getItem(SESSION_KEY)) return;
            sessionStorage.setItem(SESSION_KEY, '1');

        }

        $(document).on('click', '#bv-notif-allow', function (e) {
            e.preventDefault();
            Notification.requestPermission().then(syncBellUI);
        });

        // Bell toggle button
        $(document).on('click', '#bv-notif-toggle', function () {
            if (!window.Notification) {
                toastr && toastr.warning('Tu navegador no soporta notificaciones.');
                return;
            }
            if (Notification.permission === 'denied') {
                toastr && toastr.warning('Las notificaciones están bloqueadas. Desbloquéalas en la configuración del navegador.');
                return;
            }
            if (Notification.permission === 'default') {
                Notification.requestPermission().then(function (perm) {
                    if (perm === 'granted') {
                        setNotifEnabled(true);
                    }
                    syncBellUI();
                });
                return;
            }
            // Toggle enabled state
            setNotifEnabled(!notifEnabled());
            syncBellUI();
            const msg = notifEnabled() ? 'Notificaciones activadas.' : 'Notificaciones desactivadas.';
        });

        // Show a native push notification for an incoming message
        window.showInboxPushNotif = function (convId, customerName, messagePreview, avatarUrl) {
            if (!notifEnabled()) return;
            if (!window.Notification || Notification.permission !== 'granted') return;

            const opts = {
                body: messagePreview || 'Nuevo mensaje recibido',
                tag: 'helpdesk-' + convId,    // replace existing notif for same conv
                requireInteraction: false,
            };
            if (avatarUrl) opts.icon = avatarUrl;

            const notif = new Notification(customerName || 'Nuevo mensaje', opts);
            notif.onclick = function () {
                window.focus();
                const url = '/panel/helpdesk/conversations?selected=' + convId;
                window.location.href = url;
                notif.close();
            };
        };

        // Initialize bell UI on load
        $(function () {
            syncBellUI();
            // Ask only after first user interaction (1s delay avoids spamming on load)
            setTimeout(function () {
                $(document).one('click keydown', requestPermissionOnce);
            }, 1000);
        });
    })();
    </script>

    {{-- Suscripción Reverb + typing + borrador por conversación. Antes vivía
         acoplada a la conversación inicial; ahora se expone como
         window.bvBindConversation(convId) para que conversations.js la vuelva a
         enlazar tras cada cambio de conversación SPA (sin recargar la página).
         Los handlers de document van namespaced con .bvconv y se reenganchan en
         cada bind para no duplicarse; los canales Echo anteriores se abandonan. --}}
    <script>
    (function () {
        var currentConvId = null;
        var convChannel = null;
        var typingTimeout = null;
        var typingHideTimer = null;
        var lastTypingPing = 0;
        var lastTypingState = false;

        function csrf() {
            return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        }
        function myId() {
            return parseInt(document.querySelector('meta[name="user-id"]')?.content || '0', 10);
        }

        function showTypingIndicator() {
            var $ind = $('#bv-typing-ind');
            if (!$ind.length) {
                // UI-05: role=status + aria-live anuncian "escribiendo…" a lectores de pantalla.
                $ind = $('<div id="bv-typing-ind" class="bv-typing-ind" role="status" aria-live="polite"><span class="bv-typing-dots" aria-hidden="true"><span></span><span></span><span></span></span><span class="bv-typing-text">Escribiendo…</span></div>');
                $('.bv-th-body').append($ind);
            }
            $ind.show();
            clearTimeout(typingHideTimer);
            typingHideTimer = setTimeout(function () { $ind.hide(); }, 4000);
        }

        function postTypingState(isTyping) {
            if (!currentConvId || lastTypingState === isTyping) return;
            lastTypingState = isTyping;
            $.ajax({
                url: '/panel/helpdesk/conversations/' + currentConvId + '/typing',
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf() },
                data: { is_typing: isTyping ? 1 : 0 },
            });
        }

        // Abandona los canales y handlers de la conversación previa.
        window.bvUnbindConversation = function () {
            if (typeof window.Echo !== 'undefined' && window.Echo && currentConvId) {
                try { window.Echo.leave('helpdesk.conversation.' + currentConvId); } catch (_) {}
                try { window.Echo.leave('helpdesk.conversation.' + currentConvId + '.typing'); } catch (_) {}
            }
            $(document).off('.bvconv');
            clearTimeout(typingTimeout);
            clearTimeout(typingHideTimer);
            $('#bv-typing-ind').hide();
            lastTypingState = false;
            lastTypingPing = 0;
            convChannel = null;
            currentConvId = null;
        };

        // ─── Sugerencia "Detectar idioma" (modal detect-lang.blade.php) ──
        // El idioma del contacto (helpdesk_customers.language) y el idioma de
        // trabajo del agente (select #bv-tp-to del panel Traducir, precargado
        // server-side desde helpdesktranslate.default_target) vivían sin
        // conectar entre sí: el agente nunca se enteraba de que estaba
        // respondiendo en un idioma distinto al del cliente salvo que se
        // fijara él mismo. No depende de Echo/Reverb (no hay tiempo real
        // fiable en este entorno) — se revisa con lo que ya llegó en el pane.
        var HD_LANG_LABELS = { es: 'Español', en: 'Inglés', fr: 'Francés', de: 'Alemán', pt: 'Portugués', it: 'Italiano' };

        function maybeSuggestLanguageMismatch(convId) {
            var seenKey = 'bv_lang_prompt_seen_' + convId;
            if (sessionStorage.getItem(seenKey)) return;

            var settings = {};
            try { settings = JSON.parse(sessionStorage.getItem('inbox_translation_settings') || '{}'); } catch (_e) {}
            if (settings.mode && settings.mode !== 'off') return; // ya hay traducción activa en esta pestaña

            var customerLang = ($('.bv-right').data('customer-language') || '').toString().trim().toLowerCase();
            var workingLang = ($('#bv-tp-to').val() || '').toString().trim().toLowerCase();
            if (!customerLang || !workingLang || customerLang === workingLang) return;
            if (!HD_LANG_LABELS[customerLang] || !HD_LANG_LABELS[workingLang]) return;

            var sample = ($('.bv-msg.in .bv-bubble').last().data('bv-body') || '').toString().trim();
            if (!sample) return; // sin mensajes entrantes todavía, nada que mostrar como ejemplo

            var $modal = $('[data-bv-modal-name="detect-lang"]');
            if (!$modal.length) return;

            // No se repite en esta conversación aunque el agente cierre el
            // modal sin elegir nada — evita que reaparezca en cada mensaje.
            sessionStorage.setItem(seenKey, '1');

            $modal.addClass('on');
            $('body').css('overflow', 'hidden');
            $(document).trigger('bv:modal:open', ['detect-lang', {
                detected: HD_LANG_LABELS[customerLang],
                working: HD_LANG_LABELS[workingLang],
                sample: sample.length > 140 ? sample.slice(0, 140) + '…' : sample,
                fromCode: customerLang,
                toCode: workingLang,
            }]);
        }

        window.bvBindConversation = function (convId) {
            convId = parseInt(convId, 10);
            if (!convId) return;

            maybeSuggestLanguageMismatch(convId);

            window.bvUnbindConversation();
            currentConvId = convId;

            if (typeof window.Echo === 'undefined' || !window.Echo) return;

            // Marcar como leída + limpiar el badge en la lista.
            $.ajax({
                url: '/panel/helpdesk/conversations/' + convId + '/mark-read',
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf() },
            }).done(function () {
                var $item = $('.bv-conv[data-bv-conv-id="' + convId + '"]');
                $item.removeClass('unread');
                $item.find('.bv-conv-unread').remove();
            }).fail(function (xhr) {
                console.warn('[Inbox] mark-read failed:', xhr.status);
            });

            convChannel = window.Echo.private('helpdesk.conversation.' + convId);

            convChannel.listen('.item.created', function (e) {
                // El payload viene envuelto en { message: {...} } desde broadcastWith()
                const msg = e.message || e;

                // Mensajes de actividad (etiqueta añadida, cambio de estado, asignación,
                // etc.): se pintan como píldora centrada, no como burbuja de chat.
                if (msg.type === 'activity') {
                    if (typeof window.appendActivityPillToThread === 'function') {
                        window.appendActivityPillToThread(msg.body);
                    }
                    return;
                }

                // Si el mensaje lo envió el propio agente, ya está pintado por la UI optimista
                if (msg.user_id && parseInt(msg.user_id, 10) === myId()) return;

                const isCustomerMessage = !msg.user_id && msg.author_id;
                const custId = (e.conversation && e.conversation.customer && e.conversation.customer.id) || convId;
                const item = {
                    id: msg.id,
                    body: msg.body,
                    attachment_urls: msg.attachment_urls || [],
                    attachments: msg.attachments || [],
                    metadata: msg.metadata || {},
                    is_internal: !!msg.is_internal,
                    is_incoming: !!isCustomerMessage,
                    author: msg.sender_name || (isCustomerMessage ? 'Cliente' : 'Tú'),
                    time: new Date(msg.created_at || Date.now()).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
                    avatar: msg.sender_avatar,
                    // Matches the server-side `(customer->id ?? conversation->id ?? 1) - 1) % 8 + 1`
                    // so the avatar colour is identical whether the bubble came from the
                    // initial page render or a live WebSocket append.
                    colorIdx: ((custId - 1) % 8 + 8) % 8 + 1,
                };

                if (typeof window.appendBubbleToThread === 'function') {
                    window.appendBubbleToThread(item, !!msg.is_internal);
                }

                // UI-05: anunciar el mensaje entrante en la live-region oculta.
                if (typeof window.bvAnnounce === 'function') {
                    window.bvAnnounce('Nuevo mensaje de ' + (item.author || 'cliente'));
                }

                // Same broadcast also carries delivery/read updates for outbound
                // messages: when the customer reads our reply, we get an updated
                // item with metadata.customer_read_at filled.
                const meta = msg.metadata || {};
                if (msg.user_id && (meta.customer_read_at || meta.customer_delivered_at)) {
                    const $bubble = $('.bv-bubble[data-bv-item-id="' + msg.id + '"]');
                    if ($bubble.length) {
                        const $chk = $bubble.find('.bv-chk-read, .chk');
                        if (meta.customer_read_at) {
                            $chk.removeClass('chk-delivered').addClass('chk-read').addClass('text-primary');
                        } else if (meta.customer_delivered_at) {
                            $chk.addClass('chk-delivered');
                        }
                    }
                }

                // Render customer reactions (e.g. ❤️) on agent-sent bubbles.
                if (msg.user_id && Array.isArray(meta.customer_reactions) && meta.customer_reactions.length) {
                    const $bubble = $('.bv-bubble[data-bv-item-id="' + msg.id + '"]');
                    if ($bubble.length) {
                        const emoji = meta.customer_reactions[0].emoji || '❤️';
                        let $r = $bubble.find('.bv-bubble-reaction');
                        if (!$r.length) {
                            $r = $('<span class="bv-bubble-reaction"></span>');
                            $bubble.append($r);
                        }
                        $r.text(emoji);
                    }
                }

                window.dispatchEvent(new CustomEvent('inbox:incoming-message', { detail: msg }));

                // Push notification for per-conversation listener (agent on page, tab hidden)
                if (isCustomerMessage && document.visibilityState === 'hidden') {
                    const conv = e.conversation || {};
                    const customerName = conv.customer_name || 'Nuevo mensaje';
                    const preview = (msg.body || '').slice(0, 100);
                    if (typeof window.showInboxPushNotif === 'function') {
                        window.showInboxPushNotif(convId, customerName, preview, msg.sender_avatar || null);
                    }
                }
            });

            // ─── Typing indicator: peer (Echo whisper) + customer (Meta API) ────
            $(document).on('input.bvconv', '.bv-composer-input', function () {
                var now = Date.now();
                // Throttle network/whisper traffic: max 1 ping every 2s while typing.
                if (now - lastTypingPing >= 2000) {
                    lastTypingPing = now;
                    if (convChannel && convChannel.whisper) {
                        convChannel.whisper('typing', { user_id: myId(), is_typing: true });
                    }
                    postTypingState(true);
                }

                clearTimeout(typingTimeout);
                typingTimeout = setTimeout(function () {
                    if (convChannel && convChannel.whisper) {
                        convChannel.whisper('typing', { user_id: myId(), is_typing: false });
                    }
                    postTypingState(false);
                }, 3000);
            });

            // Stop typing the moment the message is sent.
            $(document).on('bv:message:sent.bvconv', function () {
                clearTimeout(typingTimeout);
                postTypingState(false);
            });

            // Typing entre agentes (whisper)
            convChannel.listenForWhisper('typing', function (e) {
                if (!e || parseInt(e.user_id, 10) === myId()) return;
                if (e.is_typing) {
                    showTypingIndicator();
                } else {
                    clearTimeout(typingHideTimer);
                    $('#bv-typing-ind').hide();
                }
            });

            // Typing del cliente desde el widget
            window.Echo.private('helpdesk.conversation.' + convId + '.typing')
                .listen('.typing', function () {
                    showTypingIndicator();
                });

            // ─── Autosave borrador del composer en localStorage ──────────
            var draftKey = 'bv:draft:' + convId;
            var $composer = $('.bv-composer-input');
            var saved = localStorage.getItem(draftKey);
            if (saved && !$composer.val()) {
                $composer.val(saved);
                $composer[0]?.dispatchEvent(new Event('input', { bubbles: true }));
            }
            $(document).on('input.bvconv', '.bv-composer-input', function () {
                var val = $(this).val();
                if (val && val.trim()) localStorage.setItem(draftKey, val);
                else localStorage.removeItem(draftKey);
            });
            // Limpiar borrador tras envío exitoso
            $(document).on('bv:message:sent.bvconv', function () {
                localStorage.removeItem(draftKey);
                $('.bv-composer-input').val('');
            });
        };

        $(document).ready(function () {
            @if($selectedConversationId ?? false)
            window.bvBindConversation({{ (int) $selectedConversationId }});
            @endif
        });
    })();
    </script>

    {{-- Supervisor toma el control de una conversación que atiende el bot --}}
    <script>
    $(document).on('click', '#bv-btn-takeover', function () {
        var $btn = $(this);
        var url = $btn.data('takeover-url');
        if (!url) return;
        $btn.prop('disabled', true);
        $.ajax({
            url: url,
            method: 'POST',
            dataType: 'json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), 'Accept': 'application/json' },
        }).done(function (resp) {
            if (window.toastr) toastr.success((resp && resp.message) || 'Has tomado el control de la conversación.');
            $btn.remove();
            var $list = $('.bv-list').first();
            if ($list.length) {
                var params = new URLSearchParams(window.location.search);
                $.get("{{ route('manager.helpdesk.conversations.list') }}", Object.fromEntries(params)).done(function (r) {
                    if (r && typeof r.html === 'string') { $list.replaceWith(r.html); }
                });
            }
        }).fail(function (xhr) {
            if (window.toastr) toastr.error((xhr.responseJSON && xhr.responseJSON.message) || 'No se pudo tomar el control.');
        }).always(function () {
            $btn.prop('disabled', false);
        });
    });
    </script>

    {{-- Empuje activo: fuera de la ventana de 24h de WhatsApp, guiar al agente
         al panel de plantillas (HSM) y bloquear el envío de texto libre. --}}
    <script>
    (function () {
        function steerWhatsAppWindow() {
            var $composer = $('.bv-composer');
            if (!$composer.length) return;
            var closed = $composer.attr('data-bv-wa-window-closed') === '1';
            var $replyTab = $composer.find('.bv-composer-tab[data-bv-tab="reply"]');
            if (closed) {
                // Abrir el panel de plantillas (dispara el handler delegado) y
                // bloquear la pestaña de respuesta libre; las notas internas siguen.
                var hsmTab = $composer.find('.bv-composer-tab[data-bv-tab="hsm"]')[0];
                if (hsmTab && !$composer.hasClass('bv-hsm-mode')) { hsmTab.click(); }
                $composer.addClass('bv-hsm-mode');
                $replyTab.prop('disabled', true).addClass('disabled');
            } else {
                $composer.removeClass('bv-hsm-mode');
                $replyTab.prop('disabled', false).removeClass('disabled');
            }
        }
        document.addEventListener('pane:loaded', steerWhatsAppWindow);
        $(function () { steerWhatsAppWindow(); });
    })();
    </script>

    {{-- Reintentar envío de un mensaje saliente marcado como "no entregado" --}}
    <script>
    $(document).on('click', '.bv-retry-send', function () {
        var $btn = $(this);
        var url = $btn.data('bv-retry-url');
        if (!url) return;
        $btn.prop('disabled', true);
        $.ajax({
            url: url,
            method: 'POST',
            dataType: 'json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), 'Accept': 'application/json' },
        }).done(function () {
            if (window.toastr) toastr.info(@json(__('helpdesk::helpdesk.inbox.thread.retry_send_ok')));
            // Optimista: sustituir el indicador de fallo por el check de enviado.
            $btn.closest('.bv-send-failed').replaceWith('<span class="chk read bv-chk-read">✓✓</span>');
        }).fail(function (xhr) {
            if (window.toastr) toastr.error((xhr.responseJSON && xhr.responseJSON.message) || @json(__('helpdesk::helpdesk.inbox.thread.retry_send_error')));
            $btn.prop('disabled', false);
        });
    });
    </script>
@endpush
