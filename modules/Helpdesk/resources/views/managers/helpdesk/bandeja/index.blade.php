@extends('layouts.theme')

@section('title', 'Bandeja · Línea 2025')

@push('styles')
    <link rel="preconnect" href="https://fonts.googleapis.com"/>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet"/>
    {{-- Identidad visual (tokens: colores, fonts, radius, shadows) --}}
    <link rel="stylesheet" href="{{ asset('vendor/helpdesk/bandeja-v4-identity.css') }}"/>
    {{-- Componentes y layout --}}
    <link rel="stylesheet" href="{{ asset('vendor/helpdesk/bandeja-v4.css') }}"/>
@endpush

@section('content')
<div class="bandeja-v4" data-theme="light" data-right="on">

    {{-- TOPBAR --}}
    <div class="bv-topbar">
        <div class="bv-brand">
            <div class="bv-brand-mark">F</div>
            <span>{{ config('app.name', 'Functionbytes') }}</span>
        </div>
        <div class="bv-crumb">
            <i class="far fa-inbox" style="color:var(--bv-text-muted);font-size:11px"></i>
            <span>Bandeja</span>
            <span><i class="fas fa-chevron-right" style="font-size:9px;color:var(--bv-text-muted)"></i></span>
            <span class="current">{{ $currentView->name ?? 'Todas las conversaciones' }}</span>
        </div>
        <div class="bv-search-global" data-bv-modal="search-customer">
            <i class="fas fa-magnifying-glass"></i>
            <span>Buscar conversaciones, contactos, pedidos…</span>
            <span class="kbd">⌘K</span>
        </div>
        <button class="bv-topbtn" title="Novedades">
            <i class="far fa-bell"></i>
            <span class="bad">3</span>
        </button>
        <button class="bv-topbtn" title="Análisis">
            <i class="fas fa-chart-line"></i>
        </button>
        <button class="bv-topbtn" data-bv-modal="shortcuts" title="Atajos (?)">
            <i class="fas fa-keyboard"></i>
        </button>
        <button class="bv-topbtn" title="Ajustes">
            <i class="fas fa-gear"></i>
        </button>
        <button class="bv-avbtn">
            <span class="av">{{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 2)) }}</span>
            <span class="nm">{{ auth()->user()->name ?? 'Usuario' }}</span>
            <i class="fas fa-chevron-down" style="font-size:10px"></i>
        </button>
    </div>

    {{-- LEFT RAIL --}}
    <div class="bv-rail">
        <button class="bv-rail-btn on" data-bv-view="inbox" title="Bandeja">
            <i class="far fa-inbox"></i>
        </button>
        <button class="bv-rail-btn" data-bv-view="mentions" title="Menciones">
            <i class="fas fa-at"></i>
            <span class="bad">3</span>
        </button>
        <button class="bv-rail-btn" data-bv-view="pending" title="En espera">
            <i class="far fa-clock"></i>
        </button>
        <button class="bv-rail-btn" data-bv-view="closed" title="Cerradas">
            <i class="fas fa-check"></i>
        </button>
        <button class="bv-rail-btn" data-bv-view="assigned" title="Asignadas a mí">
            <i class="far fa-user"></i>
        </button>
        <div class="bv-rail-spacer"></div>
        <button class="bv-rail-btn" data-bv-view="contacts" title="Contactos">
            <i class="far fa-address-book"></i>
        </button>
        <button class="bv-rail-btn" data-bv-view="campaigns" title="Campañas">
            <i class="far fa-bullhorn"></i>
        </button>
        <button class="bv-rail-btn" data-bv-view="ai" title="Agente IA">
            <i class="fas fa-robot"></i>
        </button>
        <button class="bv-rail-btn" data-bv-view="tickets" title="Tickets">
            <i class="far fa-ticket"></i>
        </button>
        <button class="bv-rail-btn" data-bv-view="reports" title="Informes">
            <i class="fas fa-chart-line"></i>
        </button>
    </div>

    {{-- NAV --}}
    <nav class="bv-nav">
        <div class="bv-nav-section">
            <div class="bv-nav-label">
                Vistas
                <a href="#" style="color:var(--bv-text-muted)">
                    <i class="fas fa-plus" style="font-size:10px"></i>
                </a>
            </div>
            <a href="#" class="bv-nav-item on">
                <i class="far fa-inbox"></i> Sin leer
                <span class="c">12</span>
            </a>
            <a href="#" class="bv-nav-item">
                <i class="fas fa-asterisk"></i> Todas
                <span class="c">{{ $totalConversations ?? 0 }}</span>
            </a>
            <a href="#" class="bv-nav-item">
                <i class="far fa-user"></i> Mías
                <span class="c">8</span>
            </a>
            <a href="#" class="bv-nav-item">
                <i class="fas fa-fire"></i> Urgentes
                <span class="c">2</span>
            </a>
            <a href="#" class="bv-nav-item">
                <i class="far fa-clock"></i> En espera
                <span class="c">5</span>
            </a>
            <a href="#" class="bv-nav-item">
                <i class="fas fa-check"></i> Cerradas
                <span class="c">142</span>
            </a>
        </div>

        <div class="bv-nav-section">
            <div class="bv-nav-label">Canales</div>
            <a href="#" class="bv-nav-item">
                <span class="dot" style="background:#25d366"></span> WhatsApp
                <span class="c">42</span>
            </a>
            <a href="#" class="bv-nav-item">
                <span class="dot" style="background:#1877f2"></span> Facebook
                <span class="c">18</span>
            </a>
            <a href="#" class="bv-nav-item">
                <span class="dot" style="background:linear-gradient(135deg,#f58529,#dd2a7b,#8134af)"></span> Instagram
                <span class="c">9</span>
            </a>
            <a href="#" class="bv-nav-item">
                <i class="far fa-envelope"></i> Email
                <span class="c">7</span>
            </a>
            <a href="#" class="bv-nav-item">
                <i class="far fa-comment-dots"></i> Widget
                <span class="c">3</span>
            </a>
        </div>

        <div class="bv-nav-section">
            <div class="bv-nav-label">
                Etiquetas
                <a href="#" style="color:var(--bv-text-muted)">
                    <i class="fas fa-plus" style="font-size:10px"></i>
                </a>
            </div>
            <a href="#" class="bv-nav-item">
                <span class="dot" style="background:#dc2626"></span> Urgente
                <span class="c">2</span>
            </a>
            <a href="#" class="bv-nav-item">
                <span class="dot" style="background:#d97706"></span> Pendiente
                <span class="c">5</span>
            </a>
            <a href="#" class="bv-nav-item">
                <span class="dot" style="background:#059669"></span> Resuelto
                <span class="c">14</span>
            </a>
            <a href="#" class="bv-nav-item">
                <span class="dot" style="background:#6366f1"></span> Seguimiento
                <span class="c">3</span>
            </a>
        </div>

        <div class="bv-nav-section">
            <div class="bv-nav-label">Equipos</div>
            <a href="#" class="bv-nav-item">
                <i class="far fa-shop"></i> Ventas
                <span class="c">23</span>
            </a>
            <a href="#" class="bv-nav-item">
                <i class="far fa-headset"></i> Soporte
                <span class="c">31</span>
            </a>
            <a href="#" class="bv-nav-item">
                <i class="far fa-truck"></i> Logística
                <span class="c">8</span>
            </a>
        </div>
    </nav>

    {{-- LIST --}}
    @include('helpdesk::managers.helpdesk.bandeja.partials.list')

    {{-- THREAD --}}
    @include('helpdesk::managers.helpdesk.bandeja.partials.thread')

    {{-- RIGHT PANEL --}}
    @include('helpdesk::managers.helpdesk.bandeja.partials.right-panel')

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

</div>

{{-- MODALES --}}
@include('helpdesk::managers.helpdesk.bandeja.partials.modals')
@endsection

@push('scripts')
    <script src="{{ asset('vendor/helpdesk/bandeja-v4.js') }}"></script>
@endpush
