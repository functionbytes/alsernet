@extends('layouts.theme')

@section('title', __('helpdeskemaillog::emaillog.title'))

@section('page_header')
    @include('core::components.card', [
        'title' => __('helpdeskemaillog::emaillog.title'),
        'subtitle' => __('helpdeskemaillog::emaillog.subtitle'),
        'actions' => view('helpdeskemaillog::emails.partials.header-actions')->render(),
    ])
@endsection

@php
    $hasFilters = request()->hasAny(['search', 'module', 'status', 'date_from', 'date_to', 'entity_type', 'entity_id', 'engagement', 'causer_id', 'from_address', 'has_attachments']);

    // Filtro por entidad relacionada (p.ej. "ver todos los emails de este
    // ticket" desde la ficha del ticket) — llega por query string, se
    // preserva como campos ocultos del form (mismo patrón que sort_by/
    // sort_dir abajo) para no perderse al usar los demás filtros.
    $entityType = is_string(request('entity_type')) ? request('entity_type') : '';
    $entityId = is_string(request('entity_id')) || is_numeric(request('entity_id')) ? request('entity_id') : '';
    $entityFilterLabel = ($entityType && $entityId)
        ? (config('helpdeskemaillog.entity_labels')[$entityType] ?? \Illuminate\Support\Str::headline(class_basename($entityType))).' #'.$entityId
        : null;
    $canManage = auth()->user()?->can('helpdeskemaillog.manage') ?? false;

    $deliveryRate = $stats['total'] > 0 ? round($stats['sent'] / $stats['total'] * 100) : 0;
    // null cuando nunca hubo un envío con seguimiento — "0%" insinuaría un
    // dato real que no existe (mismo criterio que hasOpenTracking()).
    $openRate = $stats['open_tracked'] > 0 ? round($stats['opened'] / $stats['open_tracked'] * 100) : null;
    $clickRate = $stats['click_tracked'] > 0 ? round($stats['clicked'] / $stats['click_tracked'] * 100) : null;
    $today = now()->toDateString();

    // Selector de orden único (evx-sort-select) — cada opción es una
    // combinación fija clave+dirección (no alterna con clics repetidos como
    // el antiguo esquema de <th> ordenables).
    $sortOptionUrl = function (string $key, string $dir) : string {
        return request()->fullUrlWithQuery(['sort_by' => $key, 'sort_dir' => $dir, 'page' => 1]);
    };

    $currentSortOption = match (true) {
        $sortBy === 'subject' => 'subject_asc',
        $sortBy === 'status' => 'status',
        $sortBy === 'date' && $sortDir === 'asc' => 'date_asc',
        default => 'date_desc',
    };

    // Active status filter (to highlight the matching stat card).
    $activeStatus = request('status');
    $activeEngagement = request('engagement');

    // Los filtros de fecha vienen del usuario: solo se reutilizan si son
    // strings (?date_from[]=x llegaría como array y rompería el render).
    $dateFrom = is_string(request('date_from')) ? request('date_from') : '';
    $dateTo = is_string(request('date_to')) ? request('date_to') : '';
    $isToday = $dateFrom === $today && $dateTo === $today;
@endphp

{{-- ?v=filemtime: sin esto el navegador sirve la hoja cacheada tras cada
     cambio de CSS (mismo patrón que conversations.css/tickets-app.css). El
     fallback a time() evita romper la vista si el asset publicado aún no
     existe en este entorno. --}}
@php
    $emaillogCss = public_path('modules/helpdeskemaillog/css/emaillog.css');
    $emaillogExtrasCss = public_path('modules/helpdeskemaillog/css/emaillog-extras.css');
    $emaillogCssV = is_file($emaillogCss) ? filemtime($emaillogCss) : time();
    $emaillogExtrasCssV = is_file($emaillogExtrasCss) ? filemtime($emaillogExtrasCss) : time();
@endphp

@push('css')
    {{-- Tipografía de la plantilla Alvarez: el tema base sirve Manrope, pero
         este módulo se diseñó en Inter + JetBrains Mono (los valores mono de
         las tarjetas, IDs y fechas dependen de la segunda). Se aplica solo
         dentro de .emaillog-index/.emaillog-settings, no globalmente. --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="{{ asset('modules/helpdeskemaillog/css/emaillog.css') }}?v={{ $emaillogCssV }}">
    <link rel="stylesheet" href="{{ asset('modules/helpdeskemaillog/css/emaillog-extras.css') }}?v={{ $emaillogExtrasCssV }}">
@endpush

@section('content')

    <div class="emaillog-index">
        {{-- Contenedor único (mockup Alvarez): KPIs + gráfica + aviso de cola +
             barra de filtros (con vistas guardadas inline) + workspace de 3
             columnas viven todos dentro de la MISMA tarjeta continua, separados
             solo por border-bottom — nunca tarjetas .evx-card/.evx-block sueltas
             con huecos entre ellas. --}}
        <div class="evx-shell">

        {{-- Barra de herramientas: primera fila DENTRO de la tarjeta, como el
             mockup — breadcrumb propio del módulo + buscador + acciones. El
             buscador vive aquí (no entre los filtros) para que la barra de
             filtros quepa en una sola línea; envía el mismo formulario GET de
             filtros de más abajo vía atributo form. --}}
        <div class="evx-toolbar">
            <nav class="evx-crumbs" aria-label="breadcrumb">
                <i class="fa-solid fa-headset" aria-hidden="true"></i>
                <span>{{ __('helpdeskemaillog::emaillog.crumbs.panel') }}</span>
                <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                <span>{{ __('helpdeskemaillog::emaillog.crumbs.helpdesk') }}</span>
                <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                <span class="is-current">{{ __('helpdeskemaillog::emaillog.title') }}</span>
            </nav>

            <div class="evx-toolbar-search">
                <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                <input type="search" name="search" form="evx-filters-form" value="{{ request('search') }}"
                       aria-label="{{ __('helpdeskemaillog::emaillog.filters.search') }}"
                       placeholder="{{ __('helpdeskemaillog::emaillog.filters.search_placeholder') }}">
            </div>

            <div class="evx-toolbar-actions">
                <a href="{{ request()->fullUrl() }}" class="evx-header-btn">
                    <i class="fa-solid fa-rotate" aria-hidden="true"></i>
                    {{ __('helpdeskemaillog::emaillog.actions.refresh') }}
                </a>
                <a href="{{ route('helpdeskemaillog.reputation.index') }}" class="evx-header-btn">
                    <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
                    {{ __('helpdeskemaillog::emaillog.actions.reputation') }}
                </a>
                <a href="{{ route('helpdeskemaillog.export', request()->query()) }}" class="evx-btn evx-btn-primary evx-btn-inline">
                    <i class="fa-solid fa-download" aria-hidden="true"></i>
                    {{ __('helpdeskemaillog::emaillog.actions.export') }}
                </a>
            </div>
        </div>

        {{-- Tarjetas de estadísticas (clicables → filtran) --}}
        <div class="evx-stats">
            <a href="{{ route('helpdeskemaillog.index') }}"
               class="evx-stat {{ ! $hasFilters ? 'is-active' : '' }}">
                <span class="evx-stat-label">{{ __('helpdeskemaillog::emaillog.stats.total') }}</span>
                <span class="evx-stat-value-row">
                    <span class="evx-stat-value">{{ number_format($stats['total']) }}</span>
                    @include('helpdeskemaillog::emails.partials.stat-delta', ['delta' => $statsDelta['total'] ?? null])
                </span>
                <span class="evx-stat-hint">{{ __('helpdeskemaillog::emaillog.stats.total_hint') }}</span>
            </a>
            <a href="{{ route('helpdeskemaillog.index', ['status' => 'sent']) }}"
               class="evx-stat accent-success {{ $activeStatus === 'sent' ? 'is-active' : '' }}">
                <span class="evx-stat-label">{{ __('helpdeskemaillog::emaillog.stats.sent') }}</span>
                <span class="evx-stat-value-row">
                    <span class="evx-stat-value">{{ number_format($stats['sent']) }}</span>
                    @include('helpdeskemaillog::emails.partials.stat-delta', ['delta' => $statsDelta['sent'] ?? null])
                </span>
                <span class="evx-stat-hint">{{ __('helpdeskemaillog::emaillog.stats.sent_hint') }}</span>
            </a>
            {{-- "Entrega confirmada" (delivered_at, ver ProviderWebhookAdapter) es un
                 dato de ventana (misma que la gráfica de tendencia / el delta de
                 arriba), no un acumulado histórico como el resto de tarjetas de esta
                 fila — computeStats() todavía no expone un total histórico propio,
                 así que aquí se usa directamente el "current" del delta. --}}
            <div class="evx-stat is-static" title="{{ __('helpdeskemaillog::emaillog.stats.delivered_tooltip') }}">
                <span class="evx-stat-label">{{ __('helpdeskemaillog::emaillog.stats.delivered') }}</span>
                <span class="evx-stat-value-row">
                    <span class="evx-stat-value">{{ number_format($statsDelta['delivered']['current'] ?? 0) }}</span>
                    @include('helpdeskemaillog::emails.partials.stat-delta', ['delta' => $statsDelta['delivered'] ?? null])
                </span>
                <span class="evx-stat-hint">{{ __('helpdeskemaillog::emaillog.stats.delivered_hint') }}</span>
            </div>
            <a href="{{ route('helpdeskemaillog.index', ['status' => 'failed']) }}"
               class="evx-stat accent-danger {{ $activeStatus === 'failed' ? 'is-active' : '' }}">
                <span class="evx-stat-label">{{ __('helpdeskemaillog::emaillog.stats.failed') }}</span>
                <span class="evx-stat-value-row">
                    <span class="evx-stat-value">{{ number_format($stats['failed']) }}</span>
                    @include('helpdeskemaillog::emails.partials.stat-delta', ['delta' => $statsDelta['failed'] ?? null])
                </span>
                <span class="evx-stat-hint">{{ __('helpdeskemaillog::emaillog.stats.failed_hint') }}</span>
            </a>
            <a href="{{ route('helpdeskemaillog.index', ['status' => 'bounced']) }}"
               class="evx-stat accent-danger {{ $activeStatus === 'bounced' ? 'is-active' : '' }}">
                <span class="evx-stat-label">{{ __('helpdeskemaillog::emaillog.stats.bounced') }}</span>
                <span class="evx-stat-value-row">
                    <span class="evx-stat-value">{{ number_format($stats['bounced']) }}</span>
                    @include('helpdeskemaillog::emails.partials.stat-delta', ['delta' => $statsDelta['bounced'] ?? null])
                </span>
                <span class="evx-stat-hint">{{ __('helpdeskemaillog::emaillog.stats.bounced_hint') }}</span>
            </a>
            <a href="{{ route('helpdeskemaillog.index', ['status' => 'complained']) }}"
               class="evx-stat accent-danger {{ $activeStatus === 'complained' ? 'is-active' : '' }}">
                <span class="evx-stat-label">{{ __('helpdeskemaillog::emaillog.stats.complained') }}</span>
                <span class="evx-stat-value-row">
                    <span class="evx-stat-value">{{ number_format($stats['complained']) }}</span>
                    @include('helpdeskemaillog::emails.partials.stat-delta', ['delta' => $statsDelta['complained'] ?? null])
                </span>
                <span class="evx-stat-hint">{{ __('helpdeskemaillog::emaillog.stats.complained_hint') }}</span>
            </a>
            <a href="{{ route('helpdeskemaillog.index', ['status' => 'queued']) }}"
               class="evx-stat accent-warning {{ $activeStatus === 'queued' ? 'is-active' : '' }}">
                <span class="evx-stat-label">{{ __('helpdeskemaillog::emaillog.stats.queued') }}</span>
                <span class="evx-stat-value-row">
                    <span class="evx-stat-value">{{ number_format($stats['queued']) }}</span>
                    @include('helpdeskemaillog::emails.partials.stat-delta', ['delta' => $statsDelta['queued'] ?? null])
                </span>
                {{-- Como el mockup: si hay encolados que llevan demasiado tiempo
                     sin confirmarse, el hint lo dice en vez del texto genérico. --}}
                <span class="evx-stat-hint">
                    @if($staleCount > 0)
                        <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
                        {{ __('helpdeskemaillog::emaillog.stats.queued_hint_stale', ['count' => number_format($staleCount), 'hours' => $staleHours]) }}
                    @else
                        {{ __('helpdeskemaillog::emaillog.stats.queued_hint') }}
                    @endif
                </span>
            </a>
            <a href="{{ route('helpdeskemaillog.index', ['date_from' => $today, 'date_to' => $today]) }}"
               class="evx-stat {{ $isToday ? 'is-active' : '' }}">
                <span class="evx-stat-label">{{ __('helpdeskemaillog::emaillog.stats.today') }}</span>
                <span class="evx-stat-value-row">
                    <span class="evx-stat-value">{{ number_format($stats['today']) }}</span>
                    @include('helpdeskemaillog::emails.partials.stat-delta', ['delta' => $statsDelta['today'] ?? null])
                </span>
                <span class="evx-stat-hint">{{ __('helpdeskemaillog::emaillog.stats.today_hint') }}</span>
            </a>
            <div class="evx-stat is-static">
                <span class="evx-stat-label">{{ __('helpdeskemaillog::emaillog.stats.delivery_rate') }}</span>
                <span class="evx-stat-value-row">
                    <span class="evx-stat-value">{{ $deliveryRate }}%</span>
                    @include('helpdeskemaillog::emails.partials.stat-delta', ['delta' => $statsDelta['delivery_rate'] ?? null, 'isRate' => true])
                </span>
                <span class="evx-stat-hint">{{ __('helpdeskemaillog::emaillog.stats.delivery_rate_hint') }}</span>
            </div>
            {{-- Tasa de apertura/clic — clicables solo cuando hay algo que
                 filtrar (al menos un envío con seguimiento); si nunca hubo
                 seguimiento se muestran como "Sin datos", sin enlace, para no
                 llevar a un filtro que devolvería la lista vacía. --}}
            @if($openRate !== null)
                <a href="{{ route('helpdeskemaillog.index', ['engagement' => 'opened']) }}"
                   class="evx-stat {{ $activeEngagement === 'opened' ? 'is-active' : '' }}">
                    <span class="evx-stat-label">{{ __('helpdeskemaillog::emaillog.stats.open_rate') }}</span>
                    <span class="evx-stat-value-row">
                        <span class="evx-stat-value">{{ $openRate }}%</span>
                        @include('helpdeskemaillog::emails.partials.stat-delta', ['delta' => $statsDelta['open_rate'] ?? null, 'isRate' => true])
                    </span>
                    <span class="evx-stat-hint">{{ __('helpdeskemaillog::emaillog.stats.open_rate_hint', ['count' => $stats['open_tracked']]) }}</span>
                </a>
            @else
                <div class="evx-stat is-static">
                    <span class="evx-stat-label">{{ __('helpdeskemaillog::emaillog.stats.open_rate') }}</span>
                    <span class="evx-stat-value">{{ __('helpdeskemaillog::emaillog.stats.open_rate_no_data') }}</span>
                    <span class="evx-stat-hint">{{ __('helpdeskemaillog::emaillog.stats.open_rate_no_data_hint') }}</span>
                </div>
            @endif
            @if($clickRate !== null)
                <a href="{{ route('helpdeskemaillog.index', ['engagement' => 'clicked']) }}"
                   class="evx-stat {{ $activeEngagement === 'clicked' ? 'is-active' : '' }}">
                    <span class="evx-stat-label">{{ __('helpdeskemaillog::emaillog.stats.click_rate') }}</span>
                    <span class="evx-stat-value-row">
                        <span class="evx-stat-value">{{ $clickRate }}%</span>
                        @include('helpdeskemaillog::emails.partials.stat-delta', ['delta' => $statsDelta['click_rate'] ?? null, 'isRate' => true])
                    </span>
                    <span class="evx-stat-hint">{{ __('helpdeskemaillog::emaillog.stats.click_rate_hint', ['count' => $stats['click_tracked']]) }}</span>
                </a>
            @else
                <div class="evx-stat is-static">
                    <span class="evx-stat-label">{{ __('helpdeskemaillog::emaillog.stats.click_rate') }}</span>
                    <span class="evx-stat-value">{{ __('helpdeskemaillog::emaillog.stats.click_rate_no_data') }}</span>
                    <span class="evx-stat-hint">{{ __('helpdeskemaillog::emaillog.stats.click_rate_no_data_hint') }}</span>
                </div>
            @endif
        </div>

        {{-- Gráfica de tendencia --}}
        <div class="evx-trend">
            <div class="evx-block-head">
                <div>
                    <span class="t">{{ __('helpdeskemaillog::emaillog.trend.title') }}</span>
                    <span class="s">{{ __('helpdeskemaillog::emaillog.trend.hint') }}</span>
                </div>
            </div>
            <div class="evx-block-body">
                @php
                    $trendSent = array_sum($trend['sent']);
                    $trendFailed = array_sum($trend['failed']);
                    $trendQueued = array_sum($trend['queued']);
                    $trendBounced = array_sum($trend['bounced']);
                    $trendComplained = array_sum($trend['complained']);
                @endphp
                <canvas id="emaillog-trend" height="80" role="img"
                        aria-label="{{ __('helpdeskemaillog::emaillog.trend.title') }}: {{ $trendSent }} {{ __('helpdeskemaillog::emaillog.trend.sent') }}, {{ $trendFailed }} {{ __('helpdeskemaillog::emaillog.trend.failed') }}, {{ $trendBounced }} {{ __('helpdeskemaillog::emaillog.trend.bounced') }}, {{ $trendComplained }} {{ __('helpdeskemaillog::emaillog.trend.complained') }}, {{ $trendQueued }} {{ __('helpdeskemaillog::emaillog.trend.queued') }}."></canvas>
            </div>
        </div>

        @if($staleCount > 0)
            <a href="{{ route('helpdeskemaillog.index', ['status' => 'queued']) }}" class="evx-stale-banner" role="alert">
                <i class="fas fa-triangle-exclamation" aria-hidden="true"></i>
                <span>{{ __('helpdeskemaillog::emaillog.stale.warning', ['count' => $staleCount, 'hours' => $staleHours]) }}</span>
                <span class="evx-stale-cta">{{ __('helpdeskemaillog::emaillog.stale.view') }}</span>
            </a>
        @endif

        {{-- Barra de filtros única: selects + rango de fechas + vistas
             guardadas inline + modo de vista + exportar + contador de
             resultados, todo en una sola fila (mockup Alvarez). Las vistas
             guardadas conservan el mismo #evx-saved-views/data-*/IDs que
             consume el JS de @push('scripts'); solo cambian de posición en
             el DOM, nunca de lógica. --}}
        <form action="{{ route('helpdeskemaillog.index') }}" method="GET" class="evx-filterbar" id="evx-filters-form">
            @if($entityType && $entityId)
                <input type="hidden" name="entity_type" value="{{ $entityType }}">
                <input type="hidden" name="entity_id" value="{{ $entityId }}">
            @endif
            @if(request('sort_by'))
                <input type="hidden" name="sort_by" value="{{ request('sort_by') }}">
                <input type="hidden" name="sort_dir" value="{{ request('sort_dir', 'desc') }}">
            @endif
            <input type="hidden" name="date_from" id="date_from" value="{{ $dateFrom }}">
            <input type="hidden" name="date_to" id="date_to" value="{{ $dateTo }}">

            @if($entityFilterLabel)
                <span class="evx-tag">
                    {{ __('helpdeskemaillog::emaillog.filters.entity_active', ['entity' => $entityFilterLabel]) }}
                    <a href="{{ route('helpdeskemaillog.index', request()->except(['entity_type', 'entity_id', 'page'])) }}"
                       aria-label="{{ __('helpdeskemaillog::emaillog.filters.clear') }}">
                        <i class="fas fa-xmark" aria-hidden="true"></i>
                    </a>
                </span>
            @endif

            {{-- El buscador vive en la barra de herramientas de arriba (envía
                 este mismo formulario vía atributo form), como en el mockup. --}}

            <span class="evx-select-icon">
                <i class="fas fa-cube" aria-hidden="true"></i>
                <select name="module" class="evx-select">
                    <option value="">{{ __('helpdeskemaillog::emaillog.filters.all_modules') }}</option>
                    @foreach($modules as $mod)
                        <option value="{{ $mod }}" @selected(request('module') === $mod)>{{ $mod }}</option>
                    @endforeach
                </select>
            </span>

            <span class="evx-select-icon">
                <i class="fas fa-circle-half-stroke" aria-hidden="true"></i>
                <select name="status" class="evx-select">
                    <option value="">{{ __('helpdeskemaillog::emaillog.filters.all_statuses') }}</option>
                    @foreach($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </span>

            {{-- Filtros secundarios plegados: el mockup solo lleva módulo,
                 estado, fechas y por-página en la barra, y con los 4 nuestros
                 añadidos la fila se partía en dos (92px en vez de 49px). Se
                 despliegan solos si alguno viene activo, para que nunca haya
                 un filtro aplicado que no se vea. --}}
            @php
                $advancedActive = collect(['engagement', 'causer_id', 'from_address', 'has_attachments'])
                    ->filter(fn ($f) => filled(request($f)))
                    ->count();
            @endphp
            <button type="button" class="evx-filter-more-toggle" id="evx-filters-more-toggle"
                    aria-expanded="{{ $advancedActive ? 'true' : 'false' }}"
                    aria-controls="evx-filters-more">
                <i class="fas fa-sliders" aria-hidden="true"></i>
                {{ __('helpdeskemaillog::emaillog.filters.more') }}
                @if($advancedActive)
                    <span class="evx-filter-more-count">{{ $advancedActive }}</span>
                @endif
            </button>

            <div class="evx-filters-more" id="evx-filters-more" @if(! $advancedActive) hidden @endif>

            {{-- "Sin abrir"/"Sin clic" solo tiene sentido sobre envíos CON
                 seguimiento — ver EmailLogController::applyFilters(). --}}
            <select name="engagement" class="evx-select">
                <option value="">{{ __('helpdeskemaillog::emaillog.filters.all_engagement') }}</option>
                <option value="opened" @selected($activeEngagement === 'opened')>{{ __('helpdeskemaillog::emaillog.filters.engagement_opened') }}</option>
                <option value="not_opened" @selected($activeEngagement === 'not_opened')>{{ __('helpdeskemaillog::emaillog.filters.engagement_not_opened') }}</option>
                <option value="clicked" @selected($activeEngagement === 'clicked')>{{ __('helpdeskemaillog::emaillog.filters.engagement_clicked') }}</option>
                <option value="not_clicked" @selected($activeEngagement === 'not_clicked')>{{ __('helpdeskemaillog::emaillog.filters.engagement_not_clicked') }}</option>
            </select>

            {{-- Agente (quién lo envió) — solo usuarios que REALMENTE aparecen
                 como causer en el log (ver EmailLogController::computeAgentOptions()),
                 no todos los usuarios del sistema. --}}
            @if($agents->isNotEmpty())
                <span class="evx-select-icon">
                    <i class="fas fa-user" aria-hidden="true"></i>
                    <select name="causer_id" class="evx-select">
                        <option value="">{{ __('helpdeskemaillog::emaillog.filters.all_agents') }}</option>
                        @foreach($agents as $agent)
                            <option value="{{ $agent->id }}" @selected((string) request('causer_id') === (string) $agent->id)>{{ $agent->name ?? $agent->email }}</option>
                        @endforeach
                    </select>
                </span>
            @endif

            {{-- Buzón remitente — remitentes distintos ya existentes en el log
                 (ver 'fromAddresses' en EmailLogController::buildListData()).
                 Array plano (mismo ->pluck(...)->all() que 'modules'), no
                 Collection — de ahí count() en vez de isNotEmpty(). --}}
            @if(count($fromAddresses))
                <span class="evx-select-icon">
                    <i class="fas fa-at" aria-hidden="true"></i>
                    <select name="from_address" class="evx-select">
                        <option value="">{{ __('helpdeskemaillog::emaillog.filters.all_from_addresses') }}</option>
                        @foreach($fromAddresses as $address)
                            <option value="{{ $address }}" @selected(request('from_address') === $address)>{{ $address }}</option>
                        @endforeach
                    </select>
                </span>
            @endif

            {{-- Solo con adjuntos — ver EmailLog::scopeHasAttachments(). Select
                 binario (mismo lenguaje visual que 'engagement' de arriba) en
                 vez de checkbox suelto, para no romper la consistencia del resto
                 de la barra de filtros. --}}
            <select name="has_attachments" class="evx-select">
                <option value="" @selected(! request()->boolean('has_attachments'))>{{ __('helpdeskemaillog::emaillog.filters.attachments_only') }}</option>
                <option value="1" @selected(request()->boolean('has_attachments'))>{{ __('helpdeskemaillog::emaillog.filters.attachments_only_yes') }}</option>
            </select>

            </div>{{-- /.evx-filters-more --}}

            <span class="evx-select-icon">
                <i class="fas fa-calendar" aria-hidden="true"></i>
                <input type="text" class="evx-input daterange" autocomplete="off"
                       placeholder="{{ __('helpdeskemaillog::emaillog.filters.date_range') }}"
                       value="{{ ($dateFrom && $dateTo) ? $dateFrom . ' - ' . $dateTo : '' }}">
            </span>

            <select name="per_page" id="per-page" class="evx-select evx-select-sm"
                    title="{{ __('helpdeskemaillog::emaillog.filters.per_page') }}">
                @foreach($perPageOptions as $opt)
                    <option value="{{ $opt }}" @selected((int) $perPage === (int) $opt)>{{ __('helpdeskemaillog::emaillog.filters.per_page_option', ['n' => $opt]) }}</option>
                @endforeach
            </select>

            <button type="submit" class="evx-btn evx-btn-primary evx-btn-inline"
                    aria-label="{{ __('helpdeskemaillog::emaillog.filters.search') }}"
                    title="{{ __('helpdeskemaillog::emaillog.filters.search') }}">
                <i class="fas fa-magnifying-glass" aria-hidden="true"></i>
            </button>

            <span class="evx-filter-divider" aria-hidden="true"></span>

            {{-- Vistas guardadas — mismo #evx-saved-views + data-*/JS de antes. --}}
            <div class="evx-saved-views-inline" id="evx-saved-views"
                 data-index-url="{{ route('helpdeskemaillog.index') }}"
                 data-views-url="{{ route('helpdeskemaillog.views.index') }}"
                 data-views-store-url="{{ route('helpdeskemaillog.views.store') }}"
                 data-can-manage="{{ $canManage ? '1' : '0' }}">
                <span class="evx-filter-views-label">{{ __('helpdeskemaillog::emaillog.views.heading') }}</span>
                <div class="evx-saved-views-list" id="evx-saved-views-list">
                    {{-- Vistas predefinidas del mockup (Todos/Fallidos/Rebotes) — filtran
                         de verdad vía query string, no son vistas guardadas en BD. "En cola"
                         sustituye a "Newsletter": ese módulo no existe aquí como filtro fijo. --}}
                    @php
                        $quickViews = [
                            ['label' => __('helpdeskemaillog::emaillog.quick_views.all'), 'params' => [], 'active' => ! $hasFilters],
                            ['label' => __('helpdeskemaillog::emaillog.quick_views.failed'), 'params' => ['status' => 'failed'], 'active' => $activeStatus === 'failed'],
                            ['label' => __('helpdeskemaillog::emaillog.quick_views.bounced'), 'params' => ['status' => 'bounced'], 'active' => $activeStatus === 'bounced'],
                            ['label' => __('helpdeskemaillog::emaillog.quick_views.queued'), 'params' => ['status' => 'queued'], 'active' => $activeStatus === 'queued'],
                        ];
                    @endphp
                    @foreach($quickViews as $quickView)
                        <span class="evx-view-chip {{ $quickView['active'] ? 'is-active' : '' }}">
                            <a href="{{ route('helpdeskemaillog.index', $quickView['params']) }}">{{ $quickView['label'] }}</a>
                        </span>
                    @endforeach
                    <span class="text-muted small" id="evx-saved-views-empty">{{ __('helpdeskemaillog::emaillog.views.no_views') }}</span>
                </div>
                <button type="button" class="evx-save-view-btn" id="evx-save-view-btn">
                    <i class="fas fa-bookmark" aria-hidden="true"></i>
                    {{ __('helpdeskemaillog::emaillog.views.save_current') }}
                </button>
            </div>

            {{-- Modo de vista y exportar: funcionalidad real que el mockup no
                 cubre — se conservan en esta misma barra en vez de eliminarse. --}}
            <div class="evx-mode-switch" id="evx-mode-switch" role="group"
                 aria-label="{{ __('helpdeskemaillog::emaillog.view_modes.heading') }}">
                <button type="button" class="evx-mode-btn on" data-evx-mode="list">
                    <i class="fa-solid fa-list" aria-hidden="true"></i> {{ __('helpdeskemaillog::emaillog.view_modes.list') }}
                </button>
                <button type="button" class="evx-mode-btn" data-evx-mode="thread">
                    <i class="fa-solid fa-comments" aria-hidden="true"></i> {{ __('helpdeskemaillog::emaillog.view_modes.thread') }}
                </button>
                <button type="button" class="evx-mode-btn" data-evx-mode="compact">
                    <i class="fa-solid fa-bars" aria-hidden="true"></i> {{ __('helpdeskemaillog::emaillog.view_modes.compact') }}
                </button>
                <button type="button" class="evx-mode-btn" data-evx-mode="kanban">
                    <i class="fa-solid fa-table-columns" aria-hidden="true"></i> {{ __('helpdeskemaillog::emaillog.view_modes.kanban') }}
                </button>
            </div>

            {{-- "Exportar CSV" vive en la barra de herramientas de arriba
                 (botón primario verde), como en el mockup. --}}

            <div class="evx-filterbar-end">
                <span class="evx-filter-count">
                    {{ trans_choice('helpdeskemaillog::emaillog.filters.results_count', $logs->total(), ['count' => number_format($logs->total())]) }}
                </span>
                @if($hasFilters)
                    <a href="{{ route('helpdeskemaillog.index') }}" class="evx-filter-clear">
                        {{ __('helpdeskemaillog::emaillog.filters.clear') }}
                    </a>
                @endif
                {{-- Enlace discreto a la papelera (30 días de recuperación, ver
                     destroy()/bulkDestroy() con SoftDeletes) — mismo permiso
                     que borrar: quien no puede eliminar tampoco necesita ver
                     lo ya eliminado. --}}
                @if($canManage)
                    <a href="{{ route('helpdeskemaillog.trash.index') }}" class="evx-filter-clear">
                        <i class="fa-regular fa-trash-can" aria-hidden="true"></i>
                        {{ __('helpdeskemaillog::emaillog.trash.link') }}
                    </a>
                @endif
            </div>
        </form>

        {{-- Tabla --}}
        @php
            // Datos de la página ACTUAL para los modos de vista Hilos/Kanban
            // (evx-mode-switch) — se aplican solo sobre lo ya cargado, sin
            // re-pedir nada por AJAX al cambiar de modo. Mismos campos que
            // consume modules/HelpdeskEmailLog/resources/js (ver @push('scripts')
            // más abajo).
            $emailRowsForViewModes = $logs->map(function ($row) {
                return [
                    'uid' => $row->uid,
                    'subject' => $row->subject,
                    'to_addresses' => $row->to_addresses ?? [],
                    'module' => $row->module,
                    'status' => $row->status?->value,
                    'status_label' => $row->status_label,
                    'entity_type' => $row->entity_type,
                    'entity_id' => $row->entity_id,
                    'entity_label' => $row->entity_label,
                    'created_at' => optional($row->created_at)->toIso8601String(),
                    'date_human' => $row->display_date->format('d/m/Y H:i'),
                    'url_show' => route('helpdeskemaillog.show', $row->uid),
                    'has_attachments' => $row->has_attachments,
                    'has_open_tracking' => $row->hasOpenTracking(),
                    'has_click_tracking' => $row->hasClickTracking(),
                    'opens_count' => (int) ($row->opens_count ?? 0),
                    'clicks_count' => (int) ($row->clicks_count ?? 0),
                ];
            })->values();
        @endphp
        {{-- Workspace de 3 columnas: lista angosta + detalle (main + sidebar,
             ver .evx-body-cols dentro del partial incluido más abajo) — la
             columna de lista y el detalle son 2 tracks reales del grid
             .evx-workspace; el 3er "track" visual (main/sidebar del detalle)
             lo aporta el propio .evx-body-cols anidado dentro de
             #evx-detail-cols, sin duplicar esa definición aquí. --}}
        <div class="evx-workspace">
            <div class="evx-workspace-list">
            <div id="evx-list-view">
                <div class="evx-list-head">
                    @if($canManage)
                        <input type="checkbox" id="select-all" class="evx-list-check"
                               aria-label="{{ __('helpdeskemaillog::emaillog.table.select_all') }}">
                        <label for="select-all" class="evx-list-head-label">{{ __('helpdeskemaillog::emaillog.table.select_all') }}</label>
                    @endif
                    <label for="evx-sort-select" class="visually-hidden">{{ __('helpdeskemaillog::emaillog.sort.label') }}</label>
                    <select id="evx-sort-select" class="evx-select evx-select-sm evx-list-sort">
                        <option value="{{ $sortOptionUrl('date', 'desc') }}" @selected($currentSortOption === 'date_desc')>{{ __('helpdeskemaillog::emaillog.sort.date_desc') }}</option>
                        <option value="{{ $sortOptionUrl('date', 'asc') }}" @selected($currentSortOption === 'date_asc')>{{ __('helpdeskemaillog::emaillog.sort.date_asc') }}</option>
                        <option value="{{ $sortOptionUrl('subject', 'asc') }}" @selected($currentSortOption === 'subject_asc')>{{ __('helpdeskemaillog::emaillog.sort.subject_asc') }}</option>
                        <option value="{{ $sortOptionUrl('status', 'desc') }}" @selected($currentSortOption === 'status')>{{ __('helpdeskemaillog::emaillog.sort.status') }}</option>
                    </select>

                    {{-- Accesos directos a la selección masiva (mismo checkbox
                         .bulk-checkbox que la toolbar flotante #bulk-toolbar,
                         ver @push('scripts')) — no duplican lógica: cada icono
                         solo re-dispara el clic del botón real de la toolbar. --}}
                    @if($canManage)
                        <div class="evx-list-head-icons">
                            <button type="button" id="list-head-bulk-resend"
                                    title="{{ __('helpdeskemaillog::emaillog.actions.bulk_resend') }}"
                                    aria-label="{{ __('helpdeskemaillog::emaillog.actions.bulk_resend') }}">
                                <i class="fa-solid fa-rotate-right" aria-hidden="true"></i>
                            </button>
                            <button type="button" id="list-head-bulk-export"
                                    title="{{ __('helpdeskemaillog::emaillog.actions.bulk_export') }}"
                                    aria-label="{{ __('helpdeskemaillog::emaillog.actions.bulk_export') }}">
                                <i class="fa-solid fa-download" aria-hidden="true"></i>
                            </button>
                            <button type="button" id="list-head-bulk-delete"
                                    title="{{ __('helpdeskemaillog::emaillog.actions.bulk_delete') }}"
                                    aria-label="{{ __('helpdeskemaillog::emaillog.actions.bulk_delete') }}">
                                <i class="fa-regular fa-trash-can" aria-hidden="true"></i>
                            </button>
                        </div>
                    @endif
                </div>

                @forelse($logs as $row)
                    @php
                        $statusVal = $row->status?->value;
                        $toAddresses = $row->to_addresses ?? [];
                        $recipientLine = $toAddresses[0] ?? '—';
                        if (count($toAddresses) > 1) {
                            $recipientLine .= ' +'.(count($toAddresses) - 1);
                        }
                        $rowUrl = route('helpdeskemaillog.show', $row->uid);
                    @endphp
                    <div class="evx-row {{ $log && $log->uid === $row->uid ? 'is-active' : '' }}" data-href="{{ $rowUrl }}">
                        @if($canManage)
                            <input type="checkbox" class="bulk-checkbox evx-row-check" value="{{ $row->uid }}"
                                   aria-label="{{ $row->subject ?: __('helpdeskemaillog::emaillog.table.subject') }}">
                        @endif
                        {{-- El mockup distingue dos verdes en el punto: "Enviado"
                             (aceptado por el SMTP) en verde claro y "Entregado"
                             (confirmado por el webhook del proveedor) en verde
                             pleno. No hay un estado 'delivered' en EmailStatus:
                             la confirmación es delivered_at, así que se marca
                             aquí con una clase extra. --}}
                        <span class="evx-row-dot {{ $statusVal }} {{ $row->delivered_at ? 'is-delivered' : '' }}" aria-hidden="true"></span>
                        <div class="evx-row-body">
                            <div class="evx-row-top">
                                <a href="{{ $rowUrl }}" class="evx-subject-link evx-row-subject">
                                    {{ Str::limit($row->subject, 60) ?: '—' }}
                                </a>
                                @if($row->has_attachments)
                                    <i class="fas fa-paperclip evx-clip" role="img"
                                       aria-label="{{ __('helpdeskemaillog::emaillog.table.has_attachments') }}"
                                       title="{{ __('helpdeskemaillog::emaillog.table.has_attachments') }}"></i>
                                @endif
                            </div>
                            {{-- El mailable NO se pinta aquí: el mockup tiene 3
                                 líneas por fila (asunto / destinatario+extracto /
                                 badge+módulo+fecha) y una cuarta línea rompe esa
                                 altura. Sigue visible en la pestaña Detalle. --}}
                            {{-- "Para: destinatario · extracto del cuerpo", como el
                                 mockup. El extracto (accessor body_snippet, ver
                                 EmailLog::BODY_SNIPPET_LENGTH) llega recortado desde
                                 SQL y queda vacío si el cuerpo fue purgado o nunca se
                                 guardó — en ese caso solo se muestra el destinatario. --}}
                            <div class="evx-recipient">
                                {{ __('helpdeskemaillog::emaillog.preview.field.to') }}: {{ $recipientLine }}@if($row->body_snippet)<span class="evx-row-snippet"> · {{ $row->body_snippet }}</span>@endif
                            </div>
                            <div class="evx-row-meta">
                                {{-- Sin icono dentro del badge: en el mockup la fila
                                     solo lleva el texto del estado (el icono queda
                                     para el badge de la cabecera del detalle). El
                                     punto de color de la izquierda ya da la lectura
                                     rápida del estado sin repetir el símbolo. --}}
                                <span class="evx-status {{ $statusVal }}"
                                      @if($row->error_message) title="{{ Str::limit($row->error_message, 120) }}" @endif>{{ $row->status_label }}</span>
                                @if($row->module)
                                    <span class="evx-tag mono">{{ $row->module }}</span>
                                @endif
                                {{-- Aperturas/clics NO van en la fila (el mockup solo
                                     lleva badge + módulo + fecha): el dato sigue en la
                                     pestaña Aperturas del detalle y en los KPIs de
                                     arriba. Tampoco se pinta un "—" cuando no hay
                                     módulo: es ruido que el mockup no tiene. --}}
                                {{-- Formato corto del mockup ("01 sep 11:20"): la
                                     columna es estrecha y el año sobra con 90 días
                                     de retención. La fecha completa sigue en el
                                     title, junto al "hace X" del original. --}}
                                <span class="evx-row-date"
                                      title="{{ $row->display_date->format('d/m/Y H:i') }} · {{ $row->display_date->diffForHumans() }}">
                                    {{ str_replace('.', '', $row->display_date->translatedFormat('d M H:i')) }}
                                </span>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="evx-empty-row">
                        <i class="fas fa-inbox" aria-hidden="true"></i>
                        <p>{{ __('helpdeskemaillog::emaillog.table.empty') }}</p>
                    </div>
                @endforelse
            </div>

            {{-- Vistas alternas (Hilos/Kanban) — las rellena el JS a partir de
                 los datos ya cargados en esta página, ver @push('scripts'). --}}
            <div id="evx-thread-view" class="evx-mode-hidden"></div>
            {{-- Aviso de alcance del Kanban: estático porque $logs->count()/
                 total() ya están disponibles aquí sin pasar por el JSON que
                 consume el JS de los modos de vista. El JS solo alterna
                 evx-mode-hidden junto con #evx-kanban-view, nunca reescribe
                 este texto. --}}
            <p class="text-muted small evx-kanban-scope-note evx-mode-hidden" id="evx-kanban-scope-note">
                {{ __('helpdeskemaillog::emaillog.view_modes.kanban_scope', ['count' => $logs->count(), 'total' => $logs->total()]) }}
            </p>
            <div id="evx-kanban-view" class="evx-mode-hidden"></div>

            {{-- Pie compacto del mockup: contador a la izquierda y "‹ pág. N ›"
                 a la derecha, en mono. El paginador numérico de Laravel ocupaba
                 varias líneas dentro de una columna de 390px y rompía el ritmo
                 de la lista. Se muestra siempre (no solo con varias páginas)
                 para que el contador de registros no desaparezca. --}}
            <div class="evx-list-foot">
                <span>{{ __('helpdeskemaillog::emaillog.pagination.showing', ['first' => $logs->firstItem() ?? 0, 'last' => $logs->lastItem() ?? 0, 'total' => number_format($logs->total())]) }}</span>
                <span class="evx-list-foot-nav">
                    @if($logs->onFirstPage())
                        <span class="evx-page-btn is-disabled" aria-hidden="true"><i class="fa-solid fa-chevron-left"></i></span>
                    @else
                        <a href="{{ $logs->previousPageUrl() }}" class="evx-page-btn"
                           aria-label="{{ __('pagination.previous') }}"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></a>
                    @endif
                    <span>{{ __('helpdeskemaillog::emaillog.pagination.page', ['page' => $logs->currentPage(), 'last' => $logs->lastPage()]) }}</span>
                    @if($logs->hasMorePages())
                        <a href="{{ $logs->nextPageUrl() }}" class="evx-page-btn"
                           aria-label="{{ __('pagination.next') }}"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a>
                    @else
                        <span class="evx-page-btn is-disabled" aria-hidden="true"><i class="fa-solid fa-chevron-right"></i></span>
                    @endif
                </span>
            </div>
            </div>

            {{-- Columnas 2+3: cargadas por el servidor en la primera visita
                 (autoselección de la primera fila, ver renderWorkspace()) y
                 reemplazadas por AJAX en cada clic de fila (ver loadDetail()
                 en @push('scripts')) sin recargar el resto de la página. --}}
            <div id="evx-detail-cols" class="evx-workspace-detail">
                @include('helpdeskemaillog::emails.partials.detail-panel', [
                    'log' => $log,
                    'opensSummary' => $opensSummary,
                    'clicksSummary' => $clicksSummary,
                    'related' => $related,
                    'entityPanel' => $entityPanel,
                    'canManage' => $canManage,
                    'staleHours' => $staleHours,
                    'isStaleQueued' => $isStaleQueued,
                    'statusIcon' => $statusIcon,
                ])
            </div>
        </div>
        </div>
    </div>

    @can('helpdeskemaillog.manage')
        {{-- Toolbar flotante de acciones masivas --}}
        <div id="bulk-toolbar" class="evx-bulk-toolbar d-none">
            <span class="evx-bulk-count"><span data-bulk-count>0</span> {{ __('helpdeskemaillog::emaillog.bulk.label') }}</span>
            <button type="button" class="evx-btn evx-btn-primary evx-btn-inline" id="bulk-resend"
                    data-url="{{ route('helpdeskemaillog.bulk-resend') }}">
                {{ __('helpdeskemaillog::emaillog.actions.bulk_resend') }}
            </button>
            <button type="button" class="evx-btn evx-btn-outline evx-btn-inline" id="bulk-export"
                    data-url="{{ route('helpdeskemaillog.export-selected') }}">
                {{ __('helpdeskemaillog::emaillog.actions.bulk_export') }}
            </button>
            <button type="button" class="evx-btn evx-btn-danger evx-btn-inline" id="bulk-delete"
                    data-url="{{ route('helpdeskemaillog.bulk-destroy') }}">
                {{ __('helpdeskemaillog::emaillog.actions.bulk_delete') }}
            </button>
        </div>
    @endcan

    {{-- Modal de confirmación reutilizable. El icono, el título, el texto de
         confirmación y la etiqueta del botón los fija evxConfirm() según la
         acción; la tarjeta de contexto se rellena con el email sobre el que
         se actúa y se oculta cuando la acción es masiva (varios registros). --}}
    <div class="modal fade evx-dialog" id="emaillog-confirm-modal" tabindex="-1"
         aria-labelledby="emaillog-confirm-title" aria-describedby="emaillog-confirm-message" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                @include('helpdeskemaillog::emails.partials.modal-head', [
                    'icon' => 'fa-circle-question',
                    'iconId' => 'emaillog-confirm-icon',
                    'eyebrow' => __('helpdeskemaillog::emaillog.modal.eyebrow.confirm'),
                    'title' => __('helpdeskemaillog::emaillog.confirm.title'),
                    'titleId' => 'emaillog-confirm-title',
                ])
                <div class="modal-body">
                    <p id="emaillog-confirm-message">—</p>

                    <div class="evx-dialog-context" id="emaillog-confirm-context" hidden>
                        <span class="evx-dialog-context-title" id="emaillog-confirm-context-title"></span>
                        <span class="evx-dialog-context-sub" id="emaillog-confirm-context-sub"></span>
                    </div>

                    <div class="evx-dialog-keys">
                        <kbd>&crarr;</kbd> {{ __('helpdeskemaillog::emaillog.modal.kbd_confirm') }}
                        <kbd>esc</kbd> {{ __('helpdeskemaillog::emaillog.modal.kbd_cancel') }}
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" id="emaillog-confirm-accept">
                        {{ __('helpdeskemaillog::emaillog.confirm.accept') }}
                    </button>
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                        {{ __('helpdeskemaillog::emaillog.confirm.cancel') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    @can('helpdeskemaillog.manage')
        {{-- Modal: reenviar a otra dirección — markup FIJO fuera de
             #evx-detail-cols (igual que #emaillog-confirm-modal arriba): la
             instancia bootstrap.Modal se crea una sola vez y sobrevive a
             cualquier reemplazo AJAX del detalle. Los botones que la abren
             (dentro del fragmento reemplazable) solo escriben la URL del
             email actual en #resendto-email antes de mostrarla, ver
             @push('scripts'). --}}
        <div class="modal fade evx-dialog" id="emaillog-resendto-modal" tabindex="-1"
             aria-labelledby="emaillog-resendto-title" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    @include('helpdeskemaillog::emails.partials.modal-head', [
                        'icon' => 'fa-share',
                        'eyebrow' => __('helpdeskemaillog::emaillog.modal.eyebrow.resend'),
                        'title' => __('helpdeskemaillog::emaillog.resend.to_title'),
                        'titleId' => 'emaillog-resendto-title',
                    ])
                    <div class="modal-body">
                        <div class="evx-dialog-context">
                            <span class="evx-dialog-context-title" id="resendto-context-title"></span>
                            <span class="evx-dialog-context-sub" id="resendto-context-sub"></span>
                        </div>

                        <div>
                            <label for="resendto-email" class="form-label">
                                {{ __('helpdeskemaillog::emaillog.resend.to_label') }}
                            </label>
                            <input type="email" class="form-control" id="resendto-email"
                                   placeholder="{{ __('helpdeskemaillog::emaillog.resend.to_placeholder') }}">
                            <div class="form-text">{{ __('helpdeskemaillog::emaillog.resend.to_hint') }}</div>
                        </div>

                        {{-- Direcciones ya usadas en reenvíos anteriores desde este
                             navegador (localStorage, ver @push('scripts')): el
                             mockup las ofrece como atajo. El bloque queda oculto
                             mientras no haya ninguna. --}}
                        <div id="resendto-recent-wrap" hidden>
                            <span class="evx-dialog-eyebrow">{{ __('helpdeskemaillog::emaillog.resend.to_recent') }}</span>
                            <div class="evx-dialog-chips" id="resendto-recent"></div>
                        </div>

                        <div class="evx-dialog-note">
                            <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                            <span>{{ __('helpdeskemaillog::emaillog.resend.to_note') }}</span>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" id="resendto-send">
                            {{ __('helpdeskemaillog::emaillog.resend.to_send') }}
                        </button>
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                            {{ __('helpdeskemaillog::emaillog.confirm.cancel') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Modal: triaje de rebotes en un solo paso (mockup) — markup FIJO
             fuera de #evx-detail-cols, mismo motivo que #emaillog-resendto-modal
             de arriba. #btnBounceTriage (dentro del fragmento reemplazable)
             copia aquí la dirección/error/tipo de rebote del email actual antes
             de abrir el modal, ver @push('scripts'). --}}
        <div class="modal fade evx-dialog" id="emaillog-bounce-triage-modal" tabindex="-1"
             aria-labelledby="emaillog-bounce-triage-title" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    @include('helpdeskemaillog::emails.partials.modal-head', [
                        'icon' => 'fa-triangle-exclamation',
                        'eyebrow' => __('helpdeskemaillog::emaillog.modal.eyebrow.bounce'),
                        'title' => __('helpdeskemaillog::emaillog.bounce_triage.title'),
                        'titleId' => 'emaillog-bounce-triage-title',
                    ])
                    <div class="modal-body">
                        <p>{{ __('helpdeskemaillog::emaillog.bounce_triage.hint') }}</p>

                        {{-- Dirección que rebotó + motivo del proveedor, en la
                             tarjeta de contexto del mockup. --}}
                        <div class="evx-dialog-context">
                            <span class="evx-dialog-context-title" id="bounce-old-address">—</span>
                            <span class="evx-dialog-context-sub" id="bounce-error-message"></span>
                        </div>

                        <div>
                            <label for="bounce-corrected-email" class="form-label">
                                {{ __('helpdeskemaillog::emaillog.bounce_triage.corrected_label') }}
                            </label>
                            <input type="email" class="form-control" id="bounce-corrected-email"
                                   placeholder="{{ __('helpdeskemaillog::emaillog.bounce_triage.corrected_placeholder') }}">
                        </div>

                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="bounce-suppress-old">
                            <label class="form-check-label small" for="bounce-suppress-old">
                                {{ __('helpdeskemaillog::emaillog.bounce_triage.suppress_label') }}
                            </label>
                            <div class="form-text" id="bounce-suppress-hint"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" id="bounce-triage-send">
                            {{ __('helpdeskemaillog::emaillog.bounce_triage.send') }}
                        </button>
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                            {{ __('helpdeskemaillog::emaillog.confirm.cancel') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Modal: vincular a un ticket (mockup) — markup FIJO fuera de
             #evx-detail-cols, mismo motivo que los modales de arriba.
             #btnLinkEntity copia aquí la URL de vinculación/búsqueda del email
             actual, ver @push('scripts'). Solo tickets por ahora — ver
             LinkEmailLogEntityRequest::ALLOWED_ENTITY_TYPES. --}}
        <div class="modal fade evx-dialog" id="emaillog-link-entity-modal" tabindex="-1"
             aria-labelledby="emaillog-link-entity-title" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    @include('helpdeskemaillog::emails.partials.modal-head', [
                        'icon' => 'fa-ticket',
                        'eyebrow' => __('helpdeskemaillog::emaillog.modal.eyebrow.link'),
                        'title' => __('helpdeskemaillog::emaillog.link_entity.title'),
                        'titleId' => 'emaillog-link-entity-title',
                    ])
                    <div class="modal-body">
                        <p>{{ __('helpdeskemaillog::emaillog.link_entity.hint') }}</p>

                        <input type="search" class="form-control" id="link-entity-search" autocomplete="off"
                               placeholder="{{ __('helpdeskemaillog::emaillog.link_entity.search_placeholder') }}">

                        <div id="link-entity-results" class="list-group"></div>

                        <div class="evx-dialog-context d-none" id="link-entity-selected">
                            <span class="evx-dialog-eyebrow">{{ __('helpdeskemaillog::emaillog.link_entity.selected_label') }}</span>
                            <span class="evx-dialog-context-title" id="link-entity-selected-label"></span>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" id="link-entity-send" disabled>
                            {{ __('helpdeskemaillog::emaillog.link_entity.send') }}
                        </button>
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                            {{ __('helpdeskemaillog::emaillog.confirm.cancel') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endcan

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="{{ asset('core/js/bulk.js') }}"></script>
<script>
$(function () {
    @if(session('success')) toastr.success(@json(session('success'))); @endif
    @if(session('error')) toastr.error(@json(session('error'))); @endif

    // Icono cuadrado a la izquierda del título (mockup Alvarez) — el título lo
    // pinta core::components.card, un componente compartido con el resto de la
    // app (fuera del scope de este módulo), así que se inserta por JS en vez
    // de tocarlo. Envuelve el título+subtítulo existentes para no duplicarlos.
    (function () {
        const $titleBlock = $('.mc-content-header-inner > :first-child');
        if (!$titleBlock.length || $titleBlock.hasClass('evx-header-title-row')) return;

        const $titleText = $('<div>').append($titleBlock.children());
        $titleBlock.addClass('evx-header-title-row').empty().append(
            '<span class="evx-header-icon" aria-hidden="true"><i class="fa-regular fa-envelope"></i></span>',
            $titleText
        );
    })();

    const csrf = $('meta[name="csrf-token"]').attr('content');
    const $confirmModal = $('#emaillog-confirm-modal');
    const confirmModal = new bootstrap.Modal($confirmModal[0]);
    let pendingAccept = null;

    // Asunto + destinatario del email abierto, para la tarjeta de contexto del
    // modal (mockup): confirmar una acción destructiva sin ver sobre qué
    // registro se aplica es justo lo que el mockup evita. Devuelve null si no
    // hay detalle abierto, y entonces el modal esconde la tarjeta.
    function currentEmailContext() {
        const $panel = $('.evx-detail-panel');
        const subject = $panel.find('.evx-subject-lg').first().text().trim();
        if (! subject) return null;

        return { title: subject, sub: $panel.find('.evx-ph-meta .evx-mono').first().text().trim() };
    }

    const confirmAcceptDefault = $('#emaillog-confirm-accept').text().trim();

    // `icon` y `accept` adaptan el modal a la acción concreta (el mockup usa un
    // icono y una etiqueta propios en cada confirmación, no un "Aceptar"
    // genérico). `context: null` fuerza a ocultar la tarjeta: en las acciones
    // masivas no hay un único email al que referirse.
    function askConfirm({ title, message, onAccept, icon, accept, context }) {
        $('#emaillog-confirm-title').text(title);
        $('#emaillog-confirm-message').text(message);
        $('#emaillog-confirm-icon').find('i').attr('class', 'fa-solid ' + (icon || 'fa-circle-question'));
        $('#emaillog-confirm-accept').text(accept || confirmAcceptDefault);

        const ctx = context === undefined ? currentEmailContext() : context;
        $('#emaillog-confirm-context').prop('hidden', ! ctx);
        if (ctx) {
            $('#emaillog-confirm-context-title').text(ctx.title);
            $('#emaillog-confirm-context-sub').text(ctx.sub || '');
        }

        pendingAccept = onAccept;
        confirmModal.show();
    }

    function acceptConfirm() {
        const fn = pendingAccept;
        pendingAccept = null;
        confirmModal.hide();
        if (typeof fn === 'function') fn();
    }

    $('#emaillog-confirm-accept').on('click', acceptConfirm);

    // Enter confirma (Esc lo cierra ya Bootstrap): son los dos atajos que el
    // modal anuncia en su pie.
    $confirmModal.on('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            acceptConfirm();
        }
    });

    // El botón primario recibe el foco al abrir, para que Enter funcione sin
    // tener que pulsar antes dentro del modal.
    $confirmModal.on('shown.bs.modal', function () {
        $('#emaillog-confirm-accept').trigger('focus');
    });

    // Vistas guardadas — mismo alcance mínimo que la bandeja de tickets
    // (guardar/aplicar/borrar el conjunto de filtros actual, sin reorder ni
    // edición de is_public): ver EmailLogViewsController.
    (function () {
        const $panel = $('#evx-saved-views');
        if (!$panel.length) return;

        const indexUrl = $panel.data('index-url');
        const viewsUrl = $panel.data('views-url');
        const storeUrl = $panel.data('views-store-url');
        // data-can-manage viaja como '1'/'0' (ver $canManage arriba en este
        // mismo Blade) — comparación por string para no depender de cómo
        // jQuery.data() interprete el tipo del atributo.
        const canManage = String($panel.data('can-manage')) === '1';
        const $list = $('#evx-saved-views-list');
        const $empty = $('#evx-saved-views-empty');

        function render(views) {
            // Solo borra los chips DINÁMICOS (los de esta función, con su botón
            // de borrar data-view-id) — nunca los chips fijos del mockup
            // (Todos/Fallidos/Rebotes/En cola, ver index.blade.php), que no
            // llevan ese botón y comparten la misma clase .evx-view-chip.
            $list.find('.evx-view-chip').has('button[data-view-id]').remove();
            $empty.toggle(views.length === 0);

            views.forEach(function (v) {
                const params = new URLSearchParams(v.filters || {}).toString();
                const $chip = $('<span class="evx-view-chip"></span>');
                $('<a></a>').attr('href', indexUrl + (params ? '?' + params : '')).text(v.name).appendTo($chip);
                if (v.is_public) {
                    $('<i class="fas fa-users evx-chip-public" aria-hidden="true" title="{{ __('helpdeskemaillog::emaillog.views.public_indicator') }}"></i>')
                        .appendTo($chip);
                }
                $('<button type="button" title="{{ __('helpdeskemaillog::emaillog.filters.clear') }}"><i class="fas fa-xmark" aria-hidden="true"></i></button>')
                    .attr('data-view-id', v.id)
                    .appendTo($chip);
                $chip.insertBefore($empty);
            });
        }

        $.getJSON(viewsUrl).done(function (res) {
            if (res.success) render(res.views);
        });

        $('#evx-save-view-btn').on('click', function () {
            const name = window.prompt(@json(__('helpdeskemaillog::emaillog.views.name_placeholder')) + ':');
            if (!name) return;

            const filters = Object.fromEntries(new URLSearchParams(window.location.search).entries());
            delete filters.page;

            // Solo a quien puede gestionar (helpdeskemaillog.manage) se le
            // pregunta si quiere publicarla; un viewer normal ni ve el
            // confirm y la vista se crea privada, igual que antes.
            const isPublic = canManage && window.confirm(@json(__('helpdeskemaillog::emaillog.views.make_public_confirm')));

            $.ajax({
                url: storeUrl,
                method: 'POST',
                data: { name, filters, is_public: isPublic },
                headers: { 'X-CSRF-TOKEN': csrf },
            }).done(function (res) {
                if (res.success) {
                    toastr.success(@json(__('helpdeskemaillog::emaillog.views.heading')));
                    $.getJSON(viewsUrl).done(r => r.success && render(r.views));
                }
            }).fail(xhr => toastr.error(xhr.responseJSON?.message || 'Error'));
        });

        $list.on('click', 'button[data-view-id]', function () {
            const id = $(this).data('view-id');
            askConfirm({
                title: @json(__('helpdeskemaillog::emaillog.confirm.delete_title')),
                message: @json(__('helpdeskemaillog::emaillog.views.deleted')),
                icon: 'fa-trash-can',
                context: null,
                onAccept: () => {
                    $.ajax({
                        url: viewsUrl + '/' + id,
                        method: 'DELETE',
                        headers: { 'X-CSRF-TOKEN': csrf },
                    }).done(function (res) {
                        if (res.success) {
                            toastr.success(@json(__('helpdeskemaillog::emaillog.views.deleted')));
                            $.getJSON(viewsUrl).done(r => r.success && render(r.views));
                        } else {
                            toastr.error(res.message || 'Error');
                        }
                    }).fail(xhr => toastr.error(xhr.responseJSON?.message || 'Error'));
                },
            });
        });
    })();

    // Selector de modo de vista (Lista/Hilos/Compacta/Kanban) — opera SOLO
    // sobre los datos ya cargados en esta página del servidor, sin volver a
    // pedir nada por AJAX al cambiar de modo. Persiste en localStorage.
    (function () {
        const $modeSwitch = $('#evx-mode-switch');
        if (!$modeSwitch.length) return;

        const rows = @json($emailRowsForViewModes);
        const threadCountLabel = @json(__('helpdeskemaillog::emaillog.view_modes.thread_count'));
        const storageKey = 'helpdeskemaillog:view-mode';
        const validModes = ['list', 'thread', 'compact', 'kanban'];

        const $listView = $('#evx-list-view');
        const $threadView = $('#evx-thread-view');
        const $kanbanView = $('#evx-kanban-view');
        const $kanbanScopeNote = $('#evx-kanban-scope-note');

        function escapeHtml(value) {
            return String(value === null || value === undefined ? '' : value).replace(/[&<>"']/g, function (c) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
            });
        }

        // Clave de agrupación de "hilo": entity_type+entity_id cuando la fila
        // tiene entidad relacionada (p.ej. un ticket, un customer...), o el
        // propio uid del registro (único) cuando no la tiene — así un email
        // sin entidad nunca se agrupa con otro sin relación real entre ellos.
        function threadKey(row) {
            if (row.entity_type && row.entity_id) {
                return row.entity_type + '|' + row.entity_id;
            }
            return 'no-entity-' + row.uid;
        }

        // Mismo badge de Apertura/Clic que .evx-engagement-inline en la Lista
        // (ver evx-row-meta arriba en este mismo Blade) pero en línea, para
        // Hilos/Kanban — reusa los flags/conteos ya calculados en
        // $emailRowsForViewModes, sin ninguna petición nueva.
        function renderEngagement(row) {
            if (!row.has_open_tracking && !row.has_click_tracking) return '';

            let html = '';
            if (row.has_open_tracking) {
                html += '<i class="fa-solid fa-eye" aria-hidden="true"></i>' + row.opens_count;
            }
            if (row.has_click_tracking) {
                html += (html ? ' ' : '') + '<i class="fa-solid fa-arrow-pointer" aria-hidden="true"></i>' + row.clicks_count;
            }
            return '<span class="evx-engagement-inline">' + html + '</span>';
        }

        function renderThreadRow(row, count) {
            const statusVal = row.status || '';
            const addresses = row.to_addresses || [];
            const recipient = addresses[0] || '—';
            const extra = addresses.length > 1 ? ' +' + (addresses.length - 1) : '';
            const badge = count > 1
                ? '<span class="evx-thread-count">' + escapeHtml(threadCountLabel.replace(':count', count)) + '</span>'
                : '';
            const clip = row.has_attachments
                ? '<i class="fas fa-paperclip evx-clip" aria-hidden="true"></i>'
                : '';
            const moduleTag = row.module
                ? '<span class="evx-tag">' + escapeHtml(row.module) + '</span>'
                : '<span class="evx-muted">—</span>';

            return '<a class="evx-thread-row" href="' + row.url_show + '">'
                + '<span class="evx-subject-link">' + escapeHtml(row.subject || '—') + '</span>'
                + badge + clip
                + '<span class="evx-recipient">' + escapeHtml(recipient + extra) + '</span>'
                + moduleTag
                + '<span class="evx-status ' + statusVal + '">' + escapeHtml(row.status_label || '') + '</span>'
                + renderEngagement(row)
                + '<span class="evx-date">' + escapeHtml(row.date_human || '') + '</span>'
                + '</a>';
        }

        // Agrupa por threadKey() y muestra solo el mensaje más reciente de
        // cada grupo (con badge "N en el hilo" si hay más de 1) — grupos
        // ordenados por fecha del más reciente, igual que la Lista.
        function renderThreadGroups() {
            const byKey = new Map();
            rows.forEach(function (row) {
                const key = threadKey(row);
                if (!byKey.has(key)) byKey.set(key, []);
                byKey.get(key).push(row);
            });

            const groups = Array.from(byKey.values()).map(function (list) {
                list.sort(function (a, b) { return (b.created_at || '').localeCompare(a.created_at || ''); });
                return list;
            });
            groups.sort(function (a, b) { return (b[0].created_at || '').localeCompare(a[0].created_at || ''); });

            return groups.map(function (list) { return renderThreadRow(list[0], list.length); }).join('');
        }

        // Orden fijo de columnas y paleta verdes/grises (nunca rojo/amarillo
        // de alarma) — sent en verde de marca, queued en gris claro (estado
        // neutro/pendiente), el resto de estados "problema" repartidos entre
        // oliva oscuro y grises oscuros.
        const KANBAN_ORDER = ['queued', 'sent', 'bounced', 'complained', 'failed', 'suppressed'];
        const KANBAN_COLORS = {
            queued: '#d4d4d8',
            sent: '#90bb13',
            bounced: '#4f6b0a',
            complained: '#3f3f46',
            failed: '#52525b',
            suppressed: '#71717a',
        };

        function renderKanbanCard(row) {
            const recipient = (row.to_addresses && row.to_addresses[0]) || '—';
            const engagement = renderEngagement(row);
            return '<a class="evx-kcard" href="' + row.url_show + '">'
                + '<div class="evx-kcard-subject">' + escapeHtml(row.subject || '—') + '</div>'
                + '<div class="evx-kcard-recipient">' + escapeHtml(recipient) + '</div>'
                + '<div class="evx-kcard-meta">' + escapeHtml(row.module || '') + ' · ' + escapeHtml(row.date_human || '') + (engagement ? ' · ' + engagement : '') + '</div>'
                + '</a>';
        }

        // Agrupa SOLO las filas ya cargadas en esta página en columnas por
        // estado — sin paginación propia ni drag&drop (son estados de hecho
        // consumado, no asignables a mano). Columnas vacías no se pintan.
        function renderKanban() {
            const byStatus = new Map();
            rows.forEach(function (row) {
                const key = row.status || 'queued';
                if (!byStatus.has(key)) byStatus.set(key, []);
                byStatus.get(key).push(row);
            });

            let html = '';
            KANBAN_ORDER.forEach(function (status) {
                const list = byStatus.get(status);
                if (!list || !list.length) return;

                const label = list[0].status_label || status;
                const color = KANBAN_COLORS[status] || '#a1a1aa';
                const cards = list.map(renderKanbanCard).join('');

                html += '<div class="evx-kcol">'
                    + '<div class="evx-kcol-head">'
                    + '<span class="evx-kcol-dot" style="background-color:' + color + '"></span>'
                    + '<span>' + escapeHtml(label) + '</span>'
                    + '<span class="evx-kcol-count">' + list.length + '</span>'
                    + '</div>'
                    + '<div class="evx-kcol-body">' + cards + '</div>'
                    + '</div>';
            });
            return html;
        }

        function applyMode(mode) {
            $modeSwitch.find('.evx-mode-btn').removeClass('on')
                .filter('[data-evx-mode="' + mode + '"]').addClass('on');

            $listView.removeClass('evx-mode-hidden evx-compact');
            $threadView.addClass('evx-mode-hidden').empty();
            $kanbanView.addClass('evx-mode-hidden').empty();
            $kanbanScopeNote.addClass('evx-mode-hidden');

            if (mode === 'thread') {
                $listView.addClass('evx-mode-hidden');
                $threadView.removeClass('evx-mode-hidden').html(renderThreadGroups());
            } else if (mode === 'kanban') {
                $listView.addClass('evx-mode-hidden');
                $kanbanView.removeClass('evx-mode-hidden').html(renderKanban());
                $kanbanScopeNote.removeClass('evx-mode-hidden');
            } else if (mode === 'compact') {
                $listView.addClass('evx-compact');
            }

            try { window.localStorage.setItem(storageKey, mode); } catch (e) { /* localStorage bloqueado: se ignora, el modo simplemente no persiste */ }
        }

        $modeSwitch.on('click', '.evx-mode-btn', function () {
            applyMode($(this).data('evx-mode'));
        });

        let savedMode = 'list';
        try {
            const stored = window.localStorage.getItem(storageKey);
            if (stored && validModes.indexOf(stored) !== -1) savedMode = stored;
        } catch (e) { /* localStorage bloqueado: se queda en 'list' */ }

        applyMode(savedMode);
    })();

    // Gráfico de tendencia
    const trendEl = document.getElementById('emaillog-trend');
    if (trendEl && window.Chart) {
        const trend = @json($trend);
        new Chart(trendEl, {
            type: 'bar',
            data: {
                labels: trend.labels,
                datasets: [
                    {{-- Paleta verdes/grises (nunca rojo/ámbar de alarma) — misma
                         que usa el Kanban de más abajo (#evx-kanban-view), por
                         consistencia dentro de la misma página. --}}
                    { label: @json(__('helpdeskemaillog::emaillog.trend.sent')), data: trend.sent, backgroundColor: '#90bb13', stack: 's', borderRadius: 3 },
                    { label: @json(__('helpdeskemaillog::emaillog.trend.failed')), data: trend.failed, backgroundColor: '#52525b', stack: 's', borderRadius: 3 },
                    { label: @json(__('helpdeskemaillog::emaillog.trend.bounced')), data: trend.bounced, backgroundColor: '#4f6b0a', stack: 's', borderRadius: 3 },
                    { label: @json(__('helpdeskemaillog::emaillog.trend.complained')), data: trend.complained, backgroundColor: '#3f3f46', stack: 's', borderRadius: 3 },
                    { label: @json(__('helpdeskemaillog::emaillog.trend.queued')), data: trend.queued, backgroundColor: '#d4d4d8', stack: 's', borderRadius: 3 },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                {{-- Barras anchas pegadas entre sí (solo ~6px de aire), como las
                     del mockup: por defecto Chart.js deja casi la mitad del
                     hueco vacío y las barras salen finas. --}}
                barPercentage: 0.94,
                categoryPercentage: 0.94,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { boxWidth: 10, boxHeight: 10, font: { size: 10.5, family: 'Inter' }, color: '#52525b', padding: 16 },
                    },
                },
                scales: {
                    x: { stacked: true, grid: { display: false }, border: { display: false }, ticks: { font: { size: 9.5, family: 'JetBrains Mono' }, color: '#a1a1aa' } },
                    y: { stacked: true, beginAtZero: true, grid: { display: false }, border: { display: false }, ticks: { precision: 0, maxTicksLimit: 2, font: { size: 9, family: 'JetBrains Mono' }, color: '#a1a1aa' } },
                },
            },
        });
    }

    // Cambiar registros por página → reenviar el formulario de filtros
    $('#per-page').on('change', function () { this.form.submit(); });

    // Rango de fechas
    $('.daterange').daterangepicker({
        autoUpdateInput: false,
        locale: {
            cancelLabel: 'Limpiar', applyLabel: 'Aplicar', format: 'DD/MM/YYYY', separator: ' - ',
            daysOfWeek: ['Do', 'Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sa'],
            monthNames: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'],
            firstDay: 1
        },
        ranges: {
            'Hoy': [moment(), moment()],
            'Ayer': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
            'Últimos 7 días': [moment().subtract(6, 'days'), moment()],
            'Últimos 30 días': [moment().subtract(29, 'days'), moment()],
            'Este mes': [moment().startOf('month'), moment().endOf('month')],
            'Mes pasado': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
        }
    });
    $('.daterange').on('apply.daterangepicker', function (ev, picker) {
        $(this).val(picker.startDate.format('DD/MM/YYYY') + ' - ' + picker.endDate.format('DD/MM/YYYY'));
        $('#date_from').val(picker.startDate.format('YYYY-MM-DD'));
        $('#date_to').val(picker.endDate.format('YYYY-MM-DD'));
    });
    $('.daterange').on('cancel.daterangepicker', function () {
        $(this).val(''); $('#date_from').val(''); $('#date_to').val('');
    });

    // Orden único (evx-sort-select) → cada opción ya lleva la URL completa
    // calculada por $sortOptionUrl() en el Blade.
    $('#evx-sort-select').on('change', function () {
        window.location.href = this.value;
    });

    // Fila-tarjeta completa clicable hacia el detalle — el enlace real del
    // asunto (accesible por teclado/lector de pantalla) sigue ahí; esto es
    // solo una mejora para el clic con ratón en cualquier punto de la fila.
    // Cualquier control interactivo propio (checkbox, enlaces) hace su propia
    // acción y no debe además disparar la navegación de la fila.
    //
    // Ambos casos cargan el detalle por AJAX (ver loadDetail() más abajo) en
    // vez de navegar de página: solo se reemplazan las columnas 2+3
    // (#evx-detail-cols), la lista de la columna 1 sigue montada tal cual.
    const $detailCols = $('#evx-detail-cols');

    function markActiveRow(url) {
        $('#evx-list-view .evx-row').each(function () {
            $(this).toggleClass('is-active', $(this).data('href') === url);
        });
    }

    function loadDetail(url, pushState = true) {
        $.get(url)
            .done(function (html) {
                $detailCols.html(html);
                markActiveRow(url);
                if (pushState) history.pushState({ evxDetailUrl: url }, '', url);
            })
            .fail(function () {
                // El fragmento AJAX falló (p. ej. 419/500) — se cae a una
                // navegación normal en vez de dejar la columna de detalle rota.
                window.location.href = url;
            });
    }

    $(document).on('click', '#evx-list-view .evx-subject-link', function (e) {
        // Clic modificado (nueva pestaña/ventana) → se deja la navegación
        // nativa del enlace real, sin interceptarla.
        if (e.metaKey || e.ctrlKey || e.shiftKey || e.which === 2) return;
        e.preventDefault();
        loadDetail($(this).attr('href'));
    });

    $(document).on('click', '#evx-list-view .evx-row', function (e) {
        if ($(e.target).closest('a, input, button').length) return;
        loadDetail($(this).data('href'));
    });

    // Despliegue de los filtros secundarios (la barra debe caber en una
    // línea; ver .evx-filters-more). No recarga nada: solo muestra/oculta.
    $(document).on('click', '#evx-filters-more-toggle', function () {
        const $more = $('#evx-filters-more');
        const visible = !$more.prop('hidden');
        $more.prop('hidden', visible);
        $(this).attr('aria-expanded', String(!visible));
    });

    // Navegación anterior/siguiente del detalle: botones ▲▼ de la cabecera y
    // atajos J/K (mismos que anuncia el title de cada botón). prevUid/nextUid
    // los resuelve el backend respetando filtro y orden activos, así que basta
    // con seguir el data-href que trae el fragmento ya renderizado.
    $(document).on('click', '.js-detail-prev, .js-detail-next', function () {
        const url = $(this).data('href');
        if (url) loadDetail(url);
    });

    $(document).on('keydown', function (e) {
        // Nunca robar la tecla mientras se escribe en un campo o en un modal.
        if (e.metaKey || e.ctrlKey || e.altKey) return;
        if ($(e.target).is('input, textarea, select') || $(e.target).closest('.modal.show').length) return;
        const key = e.key.toLowerCase();
        if (key !== 'j' && key !== 'k') return;
        const $btn = $(key === 'j' ? '.js-detail-next' : '.js-detail-prev');
        const url = $btn.data('href');
        if (url && !$btn.prop('disabled')) {
            e.preventDefault();
            loadDetail(url);
        }
    });

    // Botón atrás/adelante del navegador — vuelve a pedir el fragmento de la
    // URL destino sin volver a empujar el mismo estado al historial.
    window.addEventListener('popstate', function (e) {
        const url = (e.state && e.state.evxDetailUrl) || window.location.href;
        loadDetail(url, false);
    });

    // ── Handlers del panel de detalle (columnas 2+3) ──
    // Delegados en document (nunca en #evx-detail-cols directamente): ese
    // contenedor se reemplaza por completo en cada loadDetail(), así que
    // cualquier listener atado al elemento concreto se perdería tras el
    // primer clic de fila. Delegar en document los hace sobrevivir a
    // cualquier número de reemplazos sin necesidad de "re-bindear" nada.

    // Cambiar de pestaña (Detalle / Traza / Aperturas / Original)
    $(document).on('click', '.evx-tab', function () {
        const target = $(this).data('evx-tab');
        $('.evx-tab').removeClass('on');
        $(this).addClass('on');
        $('.evx-tabpanel').attr('hidden', true);
        $('.evx-tabpanel[data-evx-panel="' + target + '"]').removeAttr('hidden');
    });

    // Alternar vista escritorio / móvil del contenido del email
    $(document).on('click', '#btnDesktopView', function () {
        $('#previewFrame').removeClass('is-mobile');
        $('.evx-device-toggle button').removeClass('on').attr('aria-pressed', 'false');
        $(this).addClass('on').attr('aria-pressed', 'true');
    });
    $(document).on('click', '#btnMobileView', function () {
        $('#previewFrame').addClass('is-mobile');
        $('.evx-device-toggle button').removeClass('on').attr('aria-pressed', 'false');
        $(this).addClass('on').attr('aria-pressed', 'true');
    });
    $(document).on('click', '#btnPrint', () => window.print());

    // Copiar Message-ID
    $(document).on('click', '.evx-copy-btn', function () {
        const text = $(this).data('copy');
        const done = () => toastr.success(@json(__('helpdeskemaillog::emaillog.copy.message_id_copied')));
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(done);
        } else {
            const $t = $('<textarea>').val(text).css({ position: 'fixed', opacity: 0 }).appendTo('body');
            $t[0].select();
            document.execCommand('copy');
            $t.remove();
            done();
        }
    });

    // Reenviar
    $(document).on('click', '.js-resend', function () {
        const url = $(this).data('url');
        askConfirm({
            title: @json(__('helpdeskemaillog::emaillog.resend.confirm_title')),
            message: @json(__('helpdeskemaillog::emaillog.resend.confirm')),
            icon: 'fa-rotate-right',
            accept: @json(__('helpdeskemaillog::emaillog.confirm.accept_resend')),
            onAccept: () => {
                $.ajax({ url, method: 'POST', headers: { 'X-CSRF-TOKEN': csrf } })
                    .done(() => location.reload())
                    .fail(xhr => toastr.error(xhr.responseJSON?.message || 'Error'));
            },
        });
    });

    // Enviar copia de prueba (sidebar) — mismo endpoint de reenvío, con
    // 'to' = correo del usuario autenticado y 'test' = 1 (prefijo [TEST]
    // en el asunto, ver ResendEmailLogJob).
    $(document).on('click', '.js-resend-test', function () {
        const url = $(this).data('url');
        const to = $(this).data('to');
        askConfirm({
            title: @json(__('helpdeskemaillog::emaillog.resend.test_confirm_title')),
            message: @json(__('helpdeskemaillog::emaillog.resend.test_confirm')).replace(':email', to),
            icon: 'fa-vial',
            accept: @json(__('helpdeskemaillog::emaillog.confirm.accept_test')),
            onAccept: () => {
                $.ajax({ url, method: 'POST', data: { to, test: 1 }, headers: { 'X-CSRF-TOKEN': csrf } })
                    .done(() => location.reload())
                    .fail(xhr => toastr.error(xhr.responseJSON?.errors?.to?.[0] || xhr.responseJSON?.message || 'Error'));
            },
        });
    });

    // Eliminar
    $(document).on('click', '.js-delete', function () {
        const url = $(this).data('url');
        askConfirm({
            title: @json(__('helpdeskemaillog::emaillog.confirm.delete_title')),
            message: @json(__('helpdeskemaillog::emaillog.confirm.delete_one')),
            icon: 'fa-trash-can',
            accept: @json(__('helpdeskemaillog::emaillog.confirm.accept_delete')),
            onAccept: () => {
                $.ajax({ url, method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrf } })
                    .done(() => window.location = @json(route('helpdeskemaillog.index')))
                    .fail(xhr => toastr.error(xhr.responseJSON?.message || 'Error'));
            },
        });
    });

    // Purgar contenido (GDPR)
    $(document).on('click', '.js-purge', function () {
        const url = $(this).data('url');
        askConfirm({
            title: @json(__('helpdeskemaillog::emaillog.purge.confirm_title')),
            message: @json(__('helpdeskemaillog::emaillog.purge.confirm')),
            icon: 'fa-eraser',
            accept: @json(__('helpdeskemaillog::emaillog.confirm.accept_purge')),
            onAccept: () => {
                $.ajax({ url, method: 'POST', headers: { 'X-CSRF-TOKEN': csrf } })
                    .done(() => location.reload())
                    .fail(xhr => toastr.error(xhr.responseJSON?.message || 'Error'));
            },
        });
    });

    @can('helpdeskemaillog.manage')
    // Reenviar a otra dirección — el modal vive fuera de #evx-detail-cols
    // (markup fijo en este mismo Blade), así que la instancia de
    // bootstrap.Modal se crea una única vez aquí y sobrevive a cualquier
    // reemplazo AJAX del detalle. #btnResendTo sí vive dentro del fragmento
    // reemplazable: cada clic vuelve a leer su data-url (la del email
    // actualmente cargado) y la copia al input antes de abrir el modal.
    const $resendToModal = $('#emaillog-resendto-modal');
    const resendToModal = new bootstrap.Modal($resendToModal[0]);

    // Direcciones ya usadas en reenvíos anteriores, para ofrecerlas como atajo
    // (bloque "Recientes" del mockup). Viven en localStorage y no en el
    // servidor a propósito: es una comodidad del operador en su navegador, no
    // un dato del registro de emails — y así no se comparten entre usuarios ni
    // hacen falta permisos ni una tabla nueva.
    const RESEND_RECENT_KEY = 'helpdeskemaillog.resend_recent';
    const RESEND_RECENT_MAX = 4;

    function readResendRecent() {
        try {
            const raw = JSON.parse(localStorage.getItem(RESEND_RECENT_KEY) || '[]');
            return Array.isArray(raw) ? raw.filter(v => typeof v === 'string').slice(0, RESEND_RECENT_MAX) : [];
        } catch (e) {
            return [];
        }
    }

    function rememberResendRecent(email) {
        try {
            const lista = [email].concat(readResendRecent().filter(v => v !== email)).slice(0, RESEND_RECENT_MAX);
            localStorage.setItem(RESEND_RECENT_KEY, JSON.stringify(lista));
        } catch (e) {
            // Modo privado o almacenamiento lleno: el atajo es prescindible.
        }
    }

    function renderResendRecent() {
        const lista = readResendRecent();
        $('#resendto-recent-wrap').prop('hidden', ! lista.length);
        $('#resendto-recent').empty().append(lista.map(email =>
            $('<button>', { type: 'button', class: 'evx-dialog-chip', text: email })
        ));
    }

    $(document).on('click', '#resendto-recent .evx-dialog-chip', function () {
        $('#resendto-email').val($(this).text()).removeClass('is-invalid').trigger('focus');
    });

    $(document).on('click', '#btnResendTo', function () {
        $('#resendto-email').val('').removeClass('is-invalid').data('url', $(this).data('url'));

        const ctx = currentEmailContext();
        $('#resendto-context-title').text(ctx ? ctx.title : '');
        $('#resendto-context-sub').text(ctx ? ctx.sub : '');

        renderResendRecent();
        resendToModal.show();
    });

    $('#resendto-send').on('click', function () {
        const $input = $('#resendto-email');
        const to = ($input.val() || '').trim();
        const url = $input.data('url');
        if (!to) { $input.addClass('is-invalid'); return; }
        $input.removeClass('is-invalid');
        $.ajax({ url, method: 'POST', data: { to }, headers: { 'X-CSRF-TOKEN': csrf } })
            .done(() => {
                rememberResendRecent(to);
                location.reload();
            })
            .fail(xhr => {
                const msg = xhr.responseJSON?.errors?.to?.[0] || xhr.responseJSON?.message || 'Error';
                toastr.error(msg);
            });
    });

    // Triaje de rebotes en un solo paso — #btnBounceTriage vive DENTRO del
    // fragmento reemplazable (delegado en document); el modal y su botón de
    // envío viven fuera de #evx-detail-cols (mismo patrón que resendto-*),
    // así que la instancia de bootstrap.Modal se crea una única vez aquí.
    const $bounceModal = $('#emaillog-bounce-triage-modal');
    const bounceModal = new bootstrap.Modal($bounceModal[0]);
    const bounceSuppressHardHint = @json(__('helpdeskemaillog::emaillog.bounce_triage.suppress_hint_hard'));
    const bounceSuppressSoftHint = @json(__('helpdeskemaillog::emaillog.bounce_triage.suppress_hint_soft'));

    $(document).on('click', '#btnBounceTriage', function () {
        const $btn = $(this);
        // jQuery .data() convierte "1"/"0" a número: se acepta cualquiera de
        // las dos formas para no depender de ese detalle de parseo.
        const isHard = $btn.data('hard') === 1 || $btn.data('hard') === '1';
        const error = $btn.data('error') || '';

        // Dirección y motivo comparten ahora la tarjeta de contexto del modal:
        // el motivo es la segunda línea y se deja vacía si el proveedor no
        // devolvió ninguno (antes era un bloque aparte que se ocultaba).
        $('#bounce-old-address').text($btn.data('old-address') || '—');
        $('#bounce-error-message').text(error);
        // Nunca se precarga con la dirección vieja: el campo debe quedar
        // vacío para forzar a escribir la dirección YA corregida.
        $('#bounce-corrected-email').val('').removeClass('is-invalid').data('url', $btn.data('url'));
        // Marcada por defecto solo si el rebote fue permanente (ver
        // EmailLog::bounceType()) — un rebote temporal no debe sugerir
        // bloquear una dirección que podría volver a funcionar sola.
        $('#bounce-suppress-old').prop('checked', isHard);
        $('#bounce-suppress-hint').text(isHard ? bounceSuppressHardHint : bounceSuppressSoftHint);

        bounceModal.show();
    });

    $('#bounce-triage-send').on('click', function () {
        const $input = $('#bounce-corrected-email');
        const to = ($input.val() || '').trim();
        const url = $input.data('url');
        const suppressOld = $('#bounce-suppress-old').is(':checked');

        if (!to) { $input.addClass('is-invalid'); return; }
        $input.removeClass('is-invalid');

        $.ajax({
            url,
            method: 'POST',
            data: { to, suppress_old: suppressOld ? 1 : 0 },
            headers: { 'X-CSRF-TOKEN': csrf },
        })
            .done(() => location.reload())
            .fail(xhr => {
                const msg = xhr.responseJSON?.errors?.to?.[0] || xhr.responseJSON?.message || 'Error';
                toastr.error(msg);
            });
    });

    // Vincular a un ticket — #btnLinkEntity vive dentro del fragmento
    // reemplazable (delegado en document); busca con debounce sobre
    // EmailLogController::searchTickets() y guarda el ticket elegido en
    // data() del botón de envío hasta el submit.
    const $linkEntityModal = $('#emaillog-link-entity-modal');
    const linkEntityModal = new bootstrap.Modal($linkEntityModal[0]);
    const ENTITY_TYPE_TICKET = 'Modules\\HelpdeskTickets\\Models\\Ticket';
    let linkEntitySearchTimer = null;

    function renderTicketResults(tickets) {
        const $results = $('#link-entity-results').empty();

        if (!tickets.length) {
            $('<div class="list-group-item text-muted small"></div>')
                .text(@json(__('helpdeskemaillog::emaillog.link_entity.no_results')))
                .appendTo($results);
            return;
        }

        tickets.forEach(function (ticket) {
            const label = '#' + ticket.ticket_number + ' — ' + (ticket.subject || '')
                + (ticket.customer_name ? ' (' + ticket.customer_name + ')' : '');

            $('<button type="button" class="list-group-item list-group-item-action"></button>')
                .text(label)
                .data({ id: ticket.id, label: label })
                .appendTo($results);
        });
    }

    $(document).on('click', '#btnLinkEntity', function () {
        $('#link-entity-search').val('').data('search-url', $(this).data('search-url'));
        $('#link-entity-results').empty();
        $('#link-entity-selected').addClass('d-none');
        $('#link-entity-selected-label').text('');
        $('#link-entity-send').prop('disabled', true).data('url', $(this).data('url')).removeData('entity-id');

        linkEntityModal.show();
    });

    $('#link-entity-search').on('input', function () {
        const $input = $(this);
        const q = ($input.val() || '').trim();
        window.clearTimeout(linkEntitySearchTimer);

        if (q.length < 2) {
            $('#link-entity-results').empty();
            return;
        }

        linkEntitySearchTimer = window.setTimeout(function () {
            $.getJSON($input.data('search-url'), { q }).done(function (res) {
                renderTicketResults(res.tickets || []);
            });
        }, 300);
    });

    $(document).on('click', '#link-entity-results button', function () {
        $('#link-entity-results button').removeClass('active');
        $(this).addClass('active');
        $('#link-entity-selected').removeClass('d-none');
        $('#link-entity-selected-label').text($(this).data('label'));
        $('#link-entity-send').prop('disabled', false).data('entity-id', $(this).data('id'));
    });

    $('#link-entity-send').on('click', function () {
        const url = $(this).data('url');
        const entityId = $(this).data('entity-id');
        if (!entityId) return;

        $.ajax({
            url,
            method: 'POST',
            data: { entity_type: ENTITY_TYPE_TICKET, entity_id: entityId },
            headers: { 'X-CSRF-TOKEN': csrf },
        })
            .done(() => location.reload())
            .fail(xhr => toastr.error(xhr.responseJSON?.message || 'Error'));
    });
    @endcan

    @can('helpdeskemaillog.manage')
    // Acciones masivas
    window.BulkActions.init({ checkbox: '.bulk-checkbox', selectAll: '#select-all', toolbar: '#bulk-toolbar' });

    // Iconos de la cabecera de la lista (#list-head-bulk-*, mockup Alvarez):
    // acceso directo a la MISMA selección/lógica de la toolbar flotante, sin
    // duplicarla — solo re-disparan el clic del botón real (#bulk-*, ver más
    // abajo), que ya valida la selección, pide confirmación y llama al AJAX.
    $('#list-head-bulk-resend').on('click', () => $('#bulk-resend').trigger('click'));
    $('#list-head-bulk-export').on('click', () => $('#bulk-export').trigger('click'));
    $('#list-head-bulk-delete').on('click', () => $('#bulk-delete').trigger('click'));

    function selectedUids() {
        return $('.bulk-checkbox:checked').map(function () { return this.value; }).get();
    }

    $('#bulk-resend').on('click', function () {
        const uids = selectedUids();
        if (!uids.length) { toastr.warning(@json(__('helpdeskemaillog::emaillog.bulk.none_selected'))); return; }
        const url = $(this).data('url');
        askConfirm({
            title: @json(__('helpdeskemaillog::emaillog.bulk.resend_title')),
            message: @json(__('helpdeskemaillog::emaillog.bulk.resend_confirm')).replace(':count', uids.length),
            icon: 'fa-rotate-right',
            accept: @json(__('helpdeskemaillog::emaillog.confirm.accept_resend')),
            context: null,
            onAccept: () => {
                $.ajax({ url, method: 'POST', data: { uids }, headers: { 'X-CSRF-TOKEN': csrf } })
                    .done(() => location.reload())
                    .fail(xhr => toastr.error(xhr.responseJSON?.message || 'Error'));
            },
        });
    });

    // Exportar seleccionados — descarga de archivo real (no JSON), así que se
    // envía como un form POST normal en vez de $.ajax (igual que cualquier
    // descarga de Laravel con streamDownload/Content-Disposition: attachment).
    $('#bulk-export').on('click', function () {
        const uids = selectedUids();
        if (!uids.length) { toastr.warning(@json(__('helpdeskemaillog::emaillog.bulk.none_selected'))); return; }

        const $form = $('<form>', { method: 'POST', action: $(this).data('url') });
        $form.append($('<input>', { type: 'hidden', name: '_token', value: csrf }));
        uids.forEach(uid => $form.append($('<input>', { type: 'hidden', name: 'uids[]', value: uid })));
        $form.appendTo('body').trigger('submit').remove();
    });

    $('#bulk-delete').on('click', function () {
        const uids = selectedUids();
        if (!uids.length) { toastr.warning(@json(__('helpdeskemaillog::emaillog.bulk.none_selected'))); return; }
        const url = $(this).data('url');
        askConfirm({
            title: @json(__('helpdeskemaillog::emaillog.confirm.delete_title')),
            message: @json(__('helpdeskemaillog::emaillog.bulk.confirm')).replace(':count', uids.length),
            icon: 'fa-trash-can',
            accept: @json(__('helpdeskemaillog::emaillog.confirm.accept_delete')),
            context: null,
            onAccept: () => {
                $.ajax({ url, method: 'DELETE', data: { uids }, headers: { 'X-CSRF-TOKEN': csrf } })
                    .done(() => location.reload())
                    .fail(xhr => toastr.error(xhr.responseJSON?.message || 'Error'));
            },
        });
    });
    @endcan
});
</script>
@endpush
