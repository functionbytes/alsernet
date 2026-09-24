@extends('layouts.theme')

@section('title', 'Registro del puente')

@section('page_header')
    @include('core::components.card', [
        'title' => 'Registro del puente',
        'description' => 'Llamadas del helpdesk al puente de PrestaShop: cuántas, cuánto tardan y cuáles fallan.',
    ])
@endsection

@push('css')
    <link rel="stylesheet" href="{{ asset('modules/helpdeskprestashop/css/prestashop-chat.css') }}?v={{ @filemtime(public_path('modules/helpdeskprestashop/css/prestashop-chat.css')) }}">
    <link rel="stylesheet" href="{{ asset('modules/helpdeskprestashop/css/ext/opslog.css') }}?v={{ @filemtime(public_path('modules/helpdeskprestashop/css/ext/opslog.css')) }}">
@endpush

@section('content')
<div class="psc-opslog" id="pscOpslogBridge"
     data-url="{{ route('manager.helpdesk.ps.ext.opslog.bridge-log.data') }}"
     data-requeue-url="{{ route('manager.helpdesk.ps.ext.opslog.bridge-log.requeue') }}"
     data-warm-url="{{ route('manager.helpdesk.ps.ext.opslog.bridge-log.warm-cache') }}"
     data-can-retry="{{ $canRetry ? 1 : 0 }}"
     data-can-warm="{{ $canWarm ? 1 : 0 }}">

    <div class="psc-opslog-toolbar">
        <div class="psc-seg psc-opslog-window" role="group" aria-label="Ventana de tiempo">
            @foreach ($windows as $hours)
                <button type="button" data-hours="{{ $hours }}" class="{{ $hours === 24 ? 'is-on' : '' }}">
                    {{ $hours === 1 ? '1 h' : ($hours === 24 ? '24 h' : '7 días') }}
                </button>
            @endforeach
        </div>
        <div class="psc-chips psc-opslog-filter" role="group" aria-label="Resultado">
            <button type="button" class="psc-chip is-on" data-result="all">Todas</button>
            <button type="button" class="psc-chip" data-result="failures">Fallos</button>
            <button type="button" class="psc-chip" data-result="timeout">Timeout</button>
            <button type="button" class="psc-chip" data-result="error">Error</button>
            <button type="button" class="psc-chip" data-result="rejected">Rechazadas</button>
            <button type="button" class="psc-chip" data-result="cache" id="pscOpslogCacheChip" hidden>Caché</button>
        </div>
        <button type="button" class="psc-opslog-iconbtn" id="pscOpslogReload" title="Actualizar" aria-label="Actualizar">
            <i class="fa fa-rotate-right"></i>
        </button>
    </div>

    <div class="psc-opslog-grid">
        <div class="psc-opslog-main">
            <div class="psc-kpis psc-opslog-kpis" id="pscOpslogKpis">
                <div class="psc-kpi"><span class="l" data-kpi-label="calls">Llamadas 24 h</span><span class="n" data-kpi="calls">—</span><span class="d" data-kpi="calls-note">al puente</span></div>
                <div class="psc-kpi"><span class="l">Media</span><span class="n is-good" data-kpi="avg">—</span><span class="d" data-kpi="avg-note">llamadas reales</span></div>
                <div class="psc-kpi"><span class="l">Fallos</span><span class="n" data-kpi="failures">—</span><span class="d" data-kpi="failures-note">errores y timeouts</span></div>
                <div class="psc-kpi"><span class="l">Rechazadas</span><span class="n" data-kpi="rejected">—</span><span class="d">validación o no encontrado</span></div>
            </div>

            <div class="psc-logtable psc-opslog-table">
                <div class="psc-logtable-hd">
                    <span class="act">Acción</span>
                    <span class="when">Cuándo</span>
                    <span class="ms">ms</span>
                    <span class="res">Resultado</span>
                </div>
                <div id="pscOpslogRows">
                    <div class="psc-opslog-skel"><div class="psc-skel"></div><div class="psc-skel"></div><div class="psc-skel"></div></div>
                </div>
            </div>
        </div>

        <aside class="psc-opslog-side">
            <div class="psc-card">
                <div class="psc-card-head">
                    <span class="psc-card-head-tt">Cola de webhooks
                        <span class="s">Eventos de PrestaShop pendientes de entregar</span>
                    </span>
                </div>
                <div class="psc-card-body" id="pscOpslogQueue">
                    <div class="psc-loading">Cargando…</div>
                </div>
                <div class="psc-card-body psc-opslog-actions">
                    @if ($canRetry)
                        <button type="button" class="psc-btn psc-btn--outline" id="pscOpslogRequeue" hidden>Reintentar fallidas</button>
                    @endif
                    @if ($canWarm)
                        <button type="button" class="psc-btn psc-btn--outline" id="pscOpslogWarm">Calentar caché</button>
                        <span class="psc-opslog-hint" id="pscOpslogWarmHint">
                            @if ($lastWarmAt)
                                Último calentado lanzado {{ \Illuminate\Support\Carbon::parse($lastWarmAt)->locale('es')->diffForHumans() }}.
                            @else
                                Precarga en segundo plano los clientes de las conversaciones y tickets abiertos.
                            @endif
                        </span>
                    @endif
                    <div class="psc-opslog-flash" id="pscOpslogFlash" hidden role="status"></div>
                </div>
            </div>

            <div class="psc-card">
                <div class="psc-card-head">
                    <span class="psc-card-head-tt">Por acción
                        <span class="s">Las 15 más llamadas en la ventana</span>
                    </span>
                </div>
                <div class="psc-card-body" id="pscOpslogByAction">
                    <div class="psc-loading">Cargando…</div>
                </div>
            </div>

            <div class="psc-note psc-note--info" id="pscOpslogCacheNote" hidden>
                <span class="psc-note-txt">
                    Esta tienda aún no marca qué respuestas salen de la caché del puente: falta
                    actualizar alsernetbridge a la versión 1.2.5. Hasta entonces una respuesta cacheada
                    cuenta como llamada real y la media incluye respuestas de caché.
                </span>
            </div>
        </aside>
    </div>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('modules/helpdeskprestashop/js/ext/opslog.js') }}?v={{ @filemtime(public_path('modules/helpdeskprestashop/js/ext/opslog.js')) }}" defer></script>
@endpush
