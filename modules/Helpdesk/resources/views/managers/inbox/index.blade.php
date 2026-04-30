@extends('layouts.full')

@section('title', 'Bandeja · Línea 2025')

@push('css')
    <link rel="preconnect" href="https://fonts.googleapis.com"/>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet"/>
    {{-- Identidad visual (tokens: colores, fonts, radius, shadows) --}}
    <link rel="stylesheet" href="{{ asset('vendor/helpdesk/inbox-v4-identity.css') }}"/>
    {{-- Componentes y layout --}}
    <link rel="stylesheet" href="{{ asset('vendor/helpdesk/inbox-v4.css') }}?v={{ @filemtime(public_path('vendor/helpdesk/inbox-v4.css')) }}"/>
@endpush

@section('content')
<div class="inbox-v4" data-theme="light" data-right="on" data-bv-mobile-tab="list">

 

    {{-- NAV --}}
    <nav class="bv-nav">
        @php
            $isUnread   = request()->boolean('unread');
            $isMine     = request()->boolean('mine');
            $isUrgent   = request()->boolean('urgent');
            $isPending  = request()->input('status') === 'pending';
            $isArchived = request()->boolean('archived') || request()->input('archived') === '1';
            $activeChannel = request()->input('channel');
            $isAll = !$isUnread && !$isMine && !$isUrgent && !$isPending && !$isArchived && !$activeChannel;

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
                <a href="#" class="bv-nav-label-add">
                    <i class="fas fa-plus"></i>
                </a>
            </div>
            <a href="{{ route('manager.helpdesk.conversations.index', ['unread' => 1]) }}"
               class="bv-nav-item {{ $isUnread ? 'on' : '' }}">
                <i class="far fa-inbox"></i> Sin leer
                <span class="c">{{ $sidebarCounters['unread'] ?? 0 }}</span>
            </a>
            <a href="{{ route('manager.helpdesk.conversations.index') }}"
               class="bv-nav-item {{ $isAll ? 'on' : '' }}">
                <i class="fas fa-asterisk"></i> Todas
                <span class="c">{{ $totalConversations ?? 0 }}</span>
            </a>
            <a href="{{ route('manager.helpdesk.conversations.index', ['mine' => 1]) }}"
               class="bv-nav-item {{ $isMine ? 'on' : '' }}">
                <i class="far fa-user"></i> Mías
                <span class="c">{{ $sidebarCounters['mine'] ?? 0 }}</span>
            </a>
            <a href="{{ route('manager.helpdesk.conversations.index', ['urgent' => 1]) }}"
               class="bv-nav-item {{ $isUrgent ? 'on' : '' }}">
                <i class="fas fa-fire"></i> Urgentes
                <span class="c">{{ $sidebarCounters['urgent'] ?? 0 }}</span>
            </a>
            <a href="{{ route('manager.helpdesk.conversations.index', ['status' => 'pending']) }}"
               class="bv-nav-item {{ $isPending ? 'on' : '' }}">
                <i class="far fa-clock"></i> En espera
                <span class="c">{{ $sidebarCounters['pending'] ?? 0 }}</span>
            </a>
            <a href="{{ route('manager.helpdesk.conversations.index', ['archived' => 1]) }}"
               class="bv-nav-item {{ $isArchived ? 'on' : '' }}">
                <i class="fas fa-check"></i> Cerradas
                <span class="c">{{ $sidebarCounters['archived'] ?? 0 }}</span>
            </a>
        </div>

        <div class="bv-nav-section">
            <div class="bv-nav-label">Canales</div>
            <a href="{{ route('manager.helpdesk.conversations.index', ['channel' => 'whatsapp']) }}"
               class="bv-nav-item {{ $activeChannel === 'whatsapp' ? 'on' : '' }}">
                <span class="dot bv-dot-wa"></span> WhatsApp
                <span class="c">{{ $sidebarCounters['whatsapp'] ?? 0 }}</span>
            </a>
            <a href="{{ route('manager.helpdesk.conversations.index', ['channel' => 'facebook']) }}"
               class="bv-nav-item {{ $activeChannel === 'facebook' ? 'on' : '' }}">
                <span class="dot bv-dot-fb"></span> Facebook
                <span class="c">{{ $sidebarCounters['facebook'] ?? 0 }}</span>
            </a>
            <a href="{{ route('manager.helpdesk.conversations.index', ['channel' => 'instagram']) }}"
               class="bv-nav-item {{ $activeChannel === 'instagram' ? 'on' : '' }}">
                <span class="dot bv-dot-ig"></span> Instagram
                <span class="c">{{ $sidebarCounters['instagram'] ?? 0 }}</span>
            </a>
            <a href="{{ route('manager.helpdesk.conversations.index', ['channel' => 'email']) }}"
               class="bv-nav-item {{ $activeChannel === 'email' ? 'on' : '' }}">
                <i class="far fa-envelope"></i> Email
                <span class="c">{{ $sidebarCounters['email'] ?? 0 }}</span>
            </a>
            <a href="{{ route('manager.helpdesk.conversations.index', ['channel' => 'web']) }}"
               class="bv-nav-item {{ $activeChannel === 'web' ? 'on' : '' }}">
                <i class="far fa-comment-dots"></i> Widget
                <span class="c">{{ $sidebarCounters['widget'] ?? 0 }}</span>
            </a>
        </div>

        <div class="bv-nav-section">
            <div class="bv-nav-label">
                Etiquetas
                <a href="#" class="bv-nav-label-add">
                    <i class="fas fa-plus"></i>
                </a>
            </div>
            <a href="#" class="bv-nav-item">
                <span class="dot bv-dot-urgente"></span> Urgente
                <span class="c">2</span>
            </a>
            <a href="#" class="bv-nav-item">
                <span class="dot bv-dot-pendiente"></span> Pendiente
                <span class="c">5</span>
            </a>
            <a href="#" class="bv-nav-item">
                <span class="dot bv-dot-resuelto"></span> Resuelto
                <span class="c">14</span>
            </a>
            <a href="#" class="bv-nav-item">
                <span class="dot bv-dot-seguimiento"></span> Seguimiento
                <span class="c">3</span>
            </a>
        </div>

        <div class="bv-nav-section">
            <div class="bv-nav-label">Equipos</div>
            @forelse(($groups ?? collect()) as $group)
                <a href="{{ route('manager.helpdesk.conversations.index', ['group' => $group->id]) }}"
                   class="bv-nav-item {{ request('group') == $group->id ? 'on' : '' }}"
                   data-bv-team-id="{{ $group->id }}"
                   data-bv-droptarget="team">
                    <i class="far fa-users"></i> {{ $group->name }}
                    <span class="c">{{ $group->conversations_count ?? '' }}</span>
                </a>
            @empty
                <span class="bv-nav-empty">Sin equipos</span>
            @endforelse
        </div>

        </div>{{-- /bv-nav-scroll --}}
    </nav>

    {{-- LIST --}}
    @include('helpdesk::managers.inbox.partials.list')

    {{-- THREAD --}}
    @include('helpdesk::managers.inbox.partials.thread')

    {{-- RIGHT PANEL --}}
    @include('helpdesk::managers.inbox.partials.right-panel')

    {{-- BULK ACTIONS BAR --}}
    @include('helpdesk::managers.inbox.partials.bulk-bar')

    {{-- STATUSBAR --}}
    <div class="bv-statusbar">
        <span class="sb-item"><span class="dot"></span>Conectado · 5 canales</span>
        <span class="sep">│</span>
        <span class="sb-item"><i class="fas fa-users"></i>3 agentes en línea</span>
        <span class="sep">│</span>
        <span class="sb-item"><i class="fas fa-gauge-high"></i>SLA medio hoy: 2m 14s</span>
        <span class="sep">│</span>
        <span class="sb-item"><i class="far fa-circle-check"></i>24 resueltas</span>
        <span class="spacer"></span>
        <span class="sb-item">v4.0 · Refinamientos</span>
    </div>

    {{-- MOBILE BOTTOM NAVIGATION --}}
    @include('helpdesk::managers.inbox.partials.mobile-tabs')

</div>

{{-- MODALES --}}
@include('helpdesk::managers.inbox.partials.modals')
@endsection

@push('scripts')
    <script src="{{ asset('vendor/helpdesk/inbox-v4.js') }}?v={{ @filemtime(public_path('vendor/helpdesk/inbox-v4.js')) }}"></script>
    @if($selectedConversationId)
    <script>
    (function () {
        if (typeof window.Echo === 'undefined') {
            return;
        }

        var convId = {{ (int) $selectedConversationId }};
        var myId = parseInt(document.querySelector('meta[name="user-id"]')?.content || '0', 10);
        var channel = window.Echo.private('helpdesk.conversation.' + convId);

        channel.listen('.item.created', function (e) {
            // Si el mensaje lo envió el propio agente, ya está pintado por la UI optimista
            if (e.user_id && parseInt(e.user_id, 10) === myId) return;
            if (typeof window.appendBubbleToThread === 'function') {
                window.appendBubbleToThread(e, !!e.is_internal);
            }
            if (window.toastr && !e.is_outgoing) {
                toastr.info((e.body || 'Nuevo mensaje').slice(0, 80), e.author || 'Mensaje entrante', { timeOut: 4000 });
            }
            window.dispatchEvent(new CustomEvent('inbox:incoming-message', { detail: e }));
        });

        // ─── Typing indicator (peer-to-peer via Echo whisper) ────────
        var typingTimeout = null;
        var lastWhisper = 0;
        $(document).on('input', '.bv-composer-input', function () {
            var now = Date.now();
            // Throttle a 1 whisper cada 1.5s
            if (now - lastWhisper < 1500) return;
            lastWhisper = now;
            if (channel && channel.whisper) {
                channel.whisper('typing', { user_id: myId, is_typing: true });
            }
            clearTimeout(typingTimeout);
            typingTimeout = setTimeout(function () {
                if (channel && channel.whisper) channel.whisper('typing', { user_id: myId, is_typing: false });
            }, 2000);
        });

        var typingHideTimer = null;
        channel.listenForWhisper('typing', function (e) {
            if (!e || parseInt(e.user_id, 10) === myId) return;
            var $ind = $('#bv-typing-ind');
            if (!$ind.length) {
                $ind = $('<div id="bv-typing-ind" class="bv-typing-ind"><span class="bv-typing-dots"><span></span><span></span><span></span></span><span class="bv-typing-text">Escribiendo…</span></div>');
                $('.bv-th-body').append($ind);
            }
            if (e.is_typing) {
                $ind.show();
                clearTimeout(typingHideTimer);
                typingHideTimer = setTimeout(function () { $ind.hide(); }, 4000);
            } else {
                $ind.hide();
            }
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
    })();
    </script>
    @endif
@endpush
