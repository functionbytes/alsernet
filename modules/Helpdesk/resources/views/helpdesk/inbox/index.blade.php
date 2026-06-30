@extends('layouts.theme')

@section('title', 'Bandeja · Línea 2025')

@push('scripts-head')
    {{-- Dark mode: detectar preferencia antes del paint para evitar flash blanco --}}
    <script>
    (function () {
        if (localStorage.getItem('bv:theme:dark') === '1') {
            // Aplicar al documento como atributo; .conversations lo hereda via [data-theme="dark"] .conversations
            document.documentElement.setAttribute('data-bv-dark', '1');
        }
    })();
    </script>
    <style>
    /* FOUC prevention: mientras bv-dark está activo y .conversations aún no tiene data-theme */
    html[data-bv-dark="1"] .conversations:not([data-theme="dark"]) {
        background: #09090b;
    }
    </style>
@endpush

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
    {{-- Dark mode overrides --}}
    <link rel="stylesheet" href="{{ asset('vendor/helpdesk/conversations-dark.css') }}?v={{ @filemtime(public_path('vendor/helpdesk/conversations-dark.css')) }}"/>
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
            $isArchived = request()->boolean('archived') || request()->input('archived') === '1';
            $activeChannel = request()->input('channel');
            $activeInboxFilter = request()->input('inbox');
            $activeTagFilter = request()->input('tag');
            $activeGroupFilter = request()->input('group');
            $isAll = ! $isUnread && ! $isMine && ! $isUrgent && ! $isBot && ! $isPending && ! $isArchived
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
                <div class="bv-nav-head-title">Conversaciones</div>
                <div class="bv-nav-head-sub">Bandeja de equipo</div>
            </div>
            <button type="button"
                    class="bv-nav-head-btn"
                    data-bv-modal="newconv"
                    title="Nueva conversación"
                    aria-label="Nueva conversación">
                <i class="fas fa-plus"></i>
            </button>
        </div>

        {{-- Tarjeta del usuario activo --}}
        <div class="bv-nav-user-card">
            <div class="bv-nav-user-av">{{ $userInitials ?: 'U' }}</div>
            <div class="bv-nav-user-body">
                <div class="bv-nav-user-name">{{ $userName }}</div>
                <div class="bv-nav-user-status">
                    <span class="bv-nav-user-dot"></span>En línea · {{ $activeCount }} activas
                </div>
            </div>
            <i class="fas fa-chevron-down bv-nav-user-chevron"></i>
        </div>

        <div class="bv-nav-scroll">
        <div class="bv-nav-section">
            <div class="bv-nav-label">
                Vistas
                <span style="display:flex;gap:4px;align-items:center">
                    <a href="{{ route('manager.helpdesk.conversations.kanban') }}"
                       title="Vista kanban"
                       style="color:inherit;opacity:.6;font-size:11px;text-decoration:none"
                       aria-label="Vista kanban">
                        <i class="fas fa-table-columns"></i>
                    </a>
                    <a href="#" class="bv-nav-label-add" id="bv-save-view-btn" title="Guardar vista actual">
                        <i class="fas fa-plus"></i>
                    </a>
                </span>
            </div>
            <a href="{{ route('manager.helpdesk.conversations.index', ['unread' => 1]) }}"
               class="bv-nav-item {{ $isUnread ? 'on' : '' }}">
                <i class="far fa-envelope bv-vi-unread"></i> Sin leer
                <span class="c">{{ $sidebarCounters['unread'] ?? 0 }}</span>
            </a>
            <a href="{{ route('manager.helpdesk.conversations.index') }}"
               class="bv-nav-item {{ $isAll ? 'on' : '' }}">
                <i class="fas fa-inbox bv-vi-all"></i> Todas
                <span class="c">{{ $totalConversations ?? 0 }}</span>
            </a>
            <a href="{{ route('manager.helpdesk.conversations.index', ['mine' => 1]) }}"
               class="bv-nav-item {{ $isMine ? 'on' : '' }}">
                <i class="fas fa-user bv-vi-mine"></i> Mías
                <span class="c">{{ $sidebarCounters['mine'] ?? 0 }}</span>
            </a>
            <a href="{{ route('manager.helpdesk.conversations.index', ['urgent' => 1]) }}"
               class="bv-nav-item {{ $isUrgent ? 'on' : '' }}">
                <i class="fas fa-fire bv-vi-urgent"></i> Urgentes
                <span class="c">{{ $sidebarCounters['urgent'] ?? 0 }}</span>
            </a>
            @if($isBot || ($sidebarCounters['bot'] ?? 0) > 0)
            <a href="{{ route('manager.helpdesk.conversations.index', ['bot' => 1]) }}"
               class="bv-nav-item {{ $isBot ? 'on' : '' }}" title="Conversaciones que el chatbot está atendiendo">
                <i class="fas fa-robot bv-vi-bot"></i> En bot
                <span class="c">{{ $sidebarCounters['bot'] ?? 0 }}</span>
            </a>
            @endif
            <a href="{{ route('manager.helpdesk.conversations.index', ['status' => 'pending']) }}"
               class="bv-nav-item {{ $isPending ? 'on' : '' }}">
                <i class="far fa-clock bv-vi-pending"></i> En espera
                <span class="c">{{ $sidebarCounters['pending'] ?? 0 }}</span>
            </a>
            <a href="{{ route('manager.helpdesk.conversations.index', ['archived' => 1]) }}"
               class="bv-nav-item {{ $isArchived ? 'on' : '' }}">
                <i class="fas fa-circle-check bv-vi-closed"></i> Cerradas
                <span class="c">{{ $sidebarCounters['archived'] ?? 0 }}</span>
            </a>
            @php
                $isBlocked = request('view') === 'blocked';
                $isSpam    = request('view') === 'spam';
                $isDeleted = request('view') === 'deleted';
            @endphp
            <a href="{{ route('manager.helpdesk.conversations.index', ['view' => 'blocked']) }}"
               class="bv-nav-item {{ $isBlocked ? 'on' : '' }}" title="Contactos bloqueados">
                <i class="fas fa-ban bv-vi-blocked"></i> Bloqueados
                <span class="c">{{ $sidebarCounters['blocked'] ?? 0 }}</span>
            </a>
            <a href="{{ route('manager.helpdesk.conversations.index', ['view' => 'spam']) }}"
               class="bv-nav-item {{ $isSpam ? 'on' : '' }}" title="Marcadas como spam">
                <i class="fas fa-shield-halved bv-vi-spam"></i> Spam
                <span class="c">{{ $sidebarCounters['spam'] ?? 0 }}</span>
            </a>
            <a href="{{ route('manager.helpdesk.conversations.index', ['view' => 'deleted']) }}"
               class="bv-nav-item {{ $isDeleted ? 'on' : '' }}" title="Conversaciones eliminadas (papelera)">
                <i class="fas fa-trash-can bv-vi-deleted"></i> Eliminadas
                <span class="c">{{ $sidebarCounters['deleted'] ?? 0 }}</span>
            </a>
        </div>

        @if(! empty($sidebarInboxes) && $sidebarInboxes->count() > 0)
        @php
            $activeInboxId = (int) request('inbox');
            $channelIconMap = [
                'whatsapp'   => 'fab fa-whatsapp',
                'facebook'   => 'fab fa-facebook-messenger',
                'instagram'  => 'fab fa-instagram',
                'email'      => 'fas fa-envelope',
                'web'        => 'fas fa-comment-dots',
                'sms'        => 'fas fa-mobile-screen-button',
                'prestashop' => 'fas fa-cart-shopping',
            ];
            $channelColorMap = [
                'whatsapp'   => '#25d366',
                'facebook'   => '#1877f2',
                'instagram'  => '#e4405f',
                'email'      => '#6b7280',
                'web'        => '#14b8a6',
                'sms'        => '#64748b',
                'prestashop' => '#df1d1d',
            ];
        @endphp
        <div class="bv-nav-section">
            <div class="bv-nav-label">Bandejas</div>
            @foreach($sidebarInboxes as $sbInbox)
                @php
                    $iconClass  = $sbInbox->icon ?: ($channelIconMap[$sbInbox->channel_type] ?? 'fas fa-inbox');
                    $iconColor  = $sbInbox->color ?: ($channelColorMap[$sbInbox->channel_type] ?? null);
                    $iconStyle  = $iconColor ? 'color:'.e($iconColor).';' : '';
                @endphp
                <a href="{{ route('manager.helpdesk.conversations.index', ['inbox' => $sbInbox->id]) }}"
                   class="bv-nav-item {{ $activeInboxId === (int) $sbInbox->id ? 'on' : '' }}"
                   title="{{ $sbInbox->name }}">
                    <i class="{{ $iconClass }}"@if($iconStyle) style="{{ $iconStyle }}"@endif></i>
                    {{ $sbInbox->name }}
                    <span class="c">{{ $sbInbox->conversations_count ?? 0 }}</span>
                </a>
            @endforeach
        </div>
        @endif

        <div class="bv-nav-section">
            <div class="bv-nav-label">Equipos</div>
            @forelse(($groups ?? collect()) as $group)
                <a href="{{ route('manager.helpdesk.conversations.index', ['group' => $group->id]) }}"
                   class="bv-nav-item {{ request('group') == $group->id ? 'on' : '' }}"
                   data-bv-team-id="{{ $group->id }}"
                   data-bv-droptarget="team">
                    <i class="fas fa-users"></i> {{ $group->name }}
                    <span class="c">{{ $group->conversations_count ?? '' }}</span>
                </a>
            @empty
                <span class="bv-nav-empty">Sin equipos</span>
            @endforelse
        </div>

        <div class="bv-nav-section">
            <div class="bv-nav-label">
                Etiquetas
                <a href="#" class="bv-nav-label-add" data-bv-modal="tags" title="Gestionar etiquetas">
                    <i class="fas fa-plus"></i>
                </a>
            </div>
            @php $activeTagId = (int) request('tag'); @endphp
            @forelse(($inboxTags ?? collect()) as $tag)
                <a href="{{ route('manager.helpdesk.conversations.index', ['tag' => $tag->id]) }}"
                   class="bv-nav-item bv-nav-item--tag {{ $activeTagId === (int) $tag->id ? 'on' : '' }}"
                   data-bv-tag-id="{{ $tag->id }}">
                    <i class="fas fa-tag"></i>
                    {{ $tag->name }}
                    <span class="c">{{ $tag->conversations_count ?? 0 }}</span>
                </a>
            @empty
                <span class="bv-nav-empty">Sin etiquetas</span>
            @endforelse
        </div>

        {{-- Vistas guardadas del usuario --}}
        @php
            $savedViews = ($views ?? collect())->filter(fn ($v) => !$v->is_system && !$v->is_default);
        @endphp
        @if ($savedViews->isNotEmpty())
        <div class="bv-nav-section" data-section="saved-views">
            <div class="bv-nav-label">Mis vistas</div>
            @foreach ($savedViews as $sv)
                @php
                    $svFilters = $sv->filters ?? [];
                    $svHref = route('manager.helpdesk.conversations.index', $svFilters);
                @endphp
                <a href="{{ $svHref }}"
                   class="bv-nav-item bv-nav-saved-view"
                   data-view-id="{{ $sv->id }}"
                   style="display:flex;align-items:center;gap:4px">
                    <i class="fas fa-star" style="font-size:9px;opacity:.65"></i>
                    <span class="bv-nav-view-name" style="flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $sv->name }}</span>
                    <button type="button"
                            class="bv-nav-view-del"
                            data-view-id="{{ $sv->id }}"
                            title="Eliminar vista"
                            aria-label="Eliminar vista"
                            style="background:none;border:none;cursor:pointer;color:var(--bv-text-muted);padding:0 2px;line-height:1;font-size:13px">×</button>
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
                title="Notificaciones de escritorio"
                aria-label="Notificaciones de escritorio">
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
    <script src="{{ asset('vendor/helpdesk/conversations.js') }}?v={{ @filemtime(public_path('vendor/helpdesk/conversations.js')) }}"></script>

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

    @if($selectedConversationId ?? false)
    <script>
    $(document).ready(function () {
        if (typeof window.Echo === 'undefined') {
            return;
        }

        var convId = {{ (int) $selectedConversationId }};
        var myId = parseInt(document.querySelector('meta[name="user-id"]')?.content || '0', 10);
        var channel = window.Echo.private('helpdesk.conversation.' + convId);

        // Mark conversation as read on the server (creates read records, sends
        // "seen" receipt to FB/IG, broadcasts message_read to other agents) and
        // clear the unread badge in the sidebar locally.
        (function markConversationReadOnOpen() {
            $.ajax({
                url: "{{ url('/panel/helpdesk/conversations') }}/" + convId + "/mark-read",
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                },
            }).done(function () {
                // Clear unread badge in sidebar for this conversation
                const $item = $('.bv-conv[data-bv-conv-id="' + convId + '"]');
                $item.removeClass('unread');
                $item.find('.bv-conv-unread').remove();
            }).fail(function (xhr) {
                console.warn('[Inbox] mark-read failed:', xhr.status);
            });
        })();

        channel.listen('.item.created', function (e) {
            // El payload viene envuelto en { message: {...} } desde broadcastWith()
            const msg = e.message || e;

            // Si el mensaje lo envió el propio agente, ya está pintado por la UI optimista
            if (msg.user_id && parseInt(msg.user_id, 10) === myId) return;

            const isCustomerMessage = !msg.user_id && msg.author_id;
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
        var typingTimeout = null;
        var lastTypingPing = 0;
        var lastTypingState = false;

        function postTypingState(isTyping) {
            if (lastTypingState === isTyping) return;
            lastTypingState = isTyping;
            $.ajax({
                url: "{{ url('/panel/helpdesk/conversations') }}/" + convId + "/typing",
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content },
                data: { is_typing: isTyping ? 1 : 0 },
            });
        }

        $(document).on('input', '.bv-composer-input', function () {
            var now = Date.now();
            // Throttle network/whisper traffic: max 1 ping every 2s while typing.
            if (now - lastTypingPing >= 2000) {
                lastTypingPing = now;
                if (channel && channel.whisper) {
                    channel.whisper('typing', { user_id: myId, is_typing: true });
                }
                postTypingState(true);
            }

            clearTimeout(typingTimeout);
            typingTimeout = setTimeout(function () {
                if (channel && channel.whisper) {
                    channel.whisper('typing', { user_id: myId, is_typing: false });
                }
                postTypingState(false);
            }, 3000);
        });

        // Stop typing the moment the message is sent.
        $(document).on('bv:message:sent', function () {
            clearTimeout(typingTimeout);
            postTypingState(false);
        });

        var typingHideTimer = null;

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

        // Typing entre agentes (whisper)
        channel.listenForWhisper('typing', function (e) {
            if (!e || parseInt(e.user_id, 10) === myId) return;
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
        $(document).on('input', '.bv-composer-input', function () {
            var val = $(this).val();
            if (val && val.trim()) localStorage.setItem(draftKey, val);
            else localStorage.removeItem(draftKey);
        });
        // Limpiar borrador tras envío exitoso
        $(document).on('bv:message:sent', function () {
            localStorage.removeItem(draftKey);
            $composer.val('');
        });
    });
    </script>
    @endif

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
@endpush
