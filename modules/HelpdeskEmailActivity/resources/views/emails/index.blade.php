@extends('layouts.theme')

@section('title', __('helpdeskemailactivity::emaillog.title'))

{{-- Sin page_header y con content_full_width: mismo criterio que el inbox de
     Conversaciones y la bandeja de Tickets. La franja de título repetía
     "Actividad de correo" (ya está en el breadcrumb de .evx-toolbar) y le restaba
     ~110px de alto útil a un workspace de 3 columnas que necesita el
     viewport entero. Las acciones que vivían ahí (Configuración y el tag del
     módulo) pasan a la barra de herramientas de dentro. --}}
@section('content_full_width', true)

@php
    $hasFilters = request()->hasAny(['search', 'module', 'status', 'date_from', 'date_to', 'entity_type', 'entity_id', 'engagement', 'causer_id', 'from_address', 'has_attachments']);

    // Filtro por entidad relacionada (p.ej. "ver todos los emails de este
    // ticket" desde la ficha del ticket) — llega por query string, se
    // preserva como campos ocultos del form (mismo patrón que sort_by/
    // sort_dir abajo) para no perderse al usar los demás filtros.
    $entityType = is_string(request('entity_type')) ? request('entity_type') : '';
    $entityId = is_string(request('entity_id')) || is_numeric(request('entity_id')) ? request('entity_id') : '';
    $entityFilterLabel = ($entityType && $entityId)
        ? (config('helpdeskemailactivity.entity_labels')[$entityType] ?? \Illuminate\Support\Str::headline(class_basename($entityType))).' #'.$entityId
        : null;
    // OJO: variable propia y no $canManage. El controlador manda un
    // $canManage que además tiene en cuenta el registro seleccionado (en la
    // papelera es false aunque el usuario tenga el permiso, ver
    // resolveDetailData()); recalcularlo aquí lo pisaba y el detalle de un
    // registro borrado volvía a ofrecer reenviarlo.
    // Esta de aquí es la del LISTADO, donde solo cuenta el permiso.
    $canManageList = auth()->user()?->can('helpdeskemailactivity.manage') ?? false;

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
    $emaillogCss = public_path('modules/helpdeskemailactivity/css/emaillog.css');
    $emaillogExtrasCss = public_path('modules/helpdeskemailactivity/css/emaillog-extras.css');
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
    <link rel="stylesheet" href="{{ asset('modules/helpdeskemailactivity/css/emaillog.css') }}?v={{ $emaillogCssV }}">
    <link rel="stylesheet" href="{{ asset('modules/helpdeskemailactivity/css/emaillog-extras.css') }}?v={{ $emaillogExtrasCssV }}">
@endpush

@section('content')

    <div class="emaillog-index">
        {{-- Contenedor único (mockup Alvarez): KPIs + gráfica + aviso de cola +
             barra de filtros (con vistas guardadas inline) + workspace de 3
             columnas viven todos dentro de la MISMA tarjeta continua, separados
             solo por border-bottom — nunca tarjetas .evx-card/.evx-block sueltas
             con huecos entre ellas. --}}
        <div class="evx-shell">

        {{-- Aviso de base de seguimiento inalcanzable. Va lo primero y a la
             vista de todos porque el síntoma es invisible desde el panel: aquí
             los enlaces funcionan, y el que se queda sin poder abrirlos es el
             destinatario. Solo aparece si el seguimiento está activo, que es
             cuando el correo sale con enlaces reescritos. --}}
        @if($trackingUnreachable)
            <div class="evx-tracking-warning">
                <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
                <div>
                    <strong>{{ __('helpdeskemailactivity::emaillog.tracking_warning.title') }}</strong>
                    <p>{{ __('helpdeskemailactivity::emaillog.tracking_warning.body', ['url' => $trackingBase]) }}</p>
                </div>
                @can('helpdeskemailactivity.manage')
                    <a href="{{ route('settings.helpdeskemailactivity.index') }}" class="evx-header-btn">
                        {{ __('helpdeskemailactivity::emaillog.tracking_warning.cta') }}
                    </a>
                @endcan
            </div>
        @endif

        {{-- Barra de herramientas: primera fila DENTRO de la tarjeta, como el
             mockup — breadcrumb propio del módulo + buscador + acciones. El
             buscador vive aquí (no entre los filtros) para que la barra de
             filtros quepa en una sola línea; envía el mismo formulario GET de
             filtros de más abajo vía atributo form. --}}
        <div class="evx-toolbar">
            <nav class="evx-crumbs" aria-label="breadcrumb">
                <i class="fa-solid fa-headset" aria-hidden="true"></i>
                <span>{{ __('helpdeskemailactivity::emaillog.crumbs.panel') }}</span>
                <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                <span>{{ __('helpdeskemailactivity::emaillog.crumbs.helpdesk') }}</span>
                <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                <span class="is-current">{{ __('helpdeskemailactivity::emaillog.title') }}</span>
            </nav>

            <div class="evx-toolbar-search">
                <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                <input type="search" name="search" form="evx-filters-form" value="{{ request('search') }}"
                       aria-label="{{ __('helpdeskemailactivity::emaillog.filters.search') }}"
                       placeholder="{{ __('helpdeskemailactivity::emaillog.filters.search_placeholder') }}">
            </div>

            <div class="evx-toolbar-actions">
                <a href="{{ request()->fullUrl() }}" class="evx-header-btn">
                    <i class="fa-solid fa-rotate" aria-hidden="true"></i>
                    {{ __('helpdeskemailactivity::emaillog.actions.refresh') }}
                </a>
                {{-- Analítica: la pantalla existía (rendimiento por plantilla,
                     latencia de entrega, entregabilidad por dominio) pero solo
                     se enlazaba desde la subnavegación de Ajustes, así que
                     desde aquí no había forma de llegar a ella. --}}
                @can('helpdeskemailactivity.manage')
                    <button type="button" class="evx-header-btn" id="evx-gdpr-trigger">
                        <i class="fa-solid fa-user-shield" aria-hidden="true"></i>
                        {{ __('helpdeskemailactivity::emaillog.gdpr.title') }}
                    </button>
                    <button type="button" class="evx-header-btn" id="evx-queue-trigger">
                        <i class="fa-solid fa-layer-group" aria-hidden="true"></i>
                        {{ __('helpdeskemailactivity::emaillog.queue.title') }}
                    </button>
                @endcan
                <a href="{{ route('helpdeskemailactivity.analytics.index') }}" class="evx-header-btn">
                    <i class="fa-solid fa-chart-simple" aria-hidden="true"></i>
                    {{ __('helpdeskemailactivity::emaillog.actions.analytics') }}
                </a>
                <a href="{{ route('helpdeskemailactivity.reputation.index') }}" class="evx-header-btn">
                    <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
                    {{ __('helpdeskemailactivity::emaillog.actions.reputation') }}
                </a>
                {{-- Baja aquí desde la cabecera de página, que ya no existe. --}}
                <a href="{{ route('settings.helpdeskemailactivity.index') }}" class="evx-header-btn">
                    <i class="fa-solid fa-gear" aria-hidden="true"></i>
                    {{ __('helpdeskemailactivity::emaillog.actions.settings') }}
                </a>
                <a href="{{ route('helpdeskemailactivity.export', request()->query()) }}" class="evx-btn evx-btn-primary evx-btn-inline">
                    <i class="fa-solid fa-download" aria-hidden="true"></i>
                    {{ __('helpdeskemailactivity::emaillog.actions.export') }}
                </a>
            </div>
        </div>

        {{-- Tarjetas de estadísticas (clicables → filtran) --}}
        <div class="evx-stats">
            <a href="{{ route('helpdeskemailactivity.index') }}"
               class="evx-stat {{ ! $hasFilters ? 'is-active' : '' }}">
                <span class="evx-stat-label">{{ __('helpdeskemailactivity::emaillog.stats.total') }}</span>
                <span class="evx-stat-value-row">
                    <span class="evx-stat-value">{{ number_format($stats['total']) }}</span>
                    @include('helpdeskemailactivity::emails.partials.stat-delta', ['delta' => $statsDelta['total'] ?? null])
                </span>
                <span class="evx-stat-hint">{{ __('helpdeskemailactivity::emaillog.stats.total_hint') }}</span>
            </a>
            <a href="{{ route('helpdeskemailactivity.index', ['status' => 'sent']) }}"
               class="evx-stat accent-success {{ $activeStatus === 'sent' ? 'is-active' : '' }}">
                <span class="evx-stat-label">{{ __('helpdeskemailactivity::emaillog.stats.sent') }}</span>
                <span class="evx-stat-value-row">
                    <span class="evx-stat-value">{{ number_format($stats['sent']) }}</span>
                    @include('helpdeskemailactivity::emails.partials.stat-delta', ['delta' => $statsDelta['sent'] ?? null])
                </span>
                <span class="evx-stat-hint">{{ __('helpdeskemailactivity::emaillog.stats.sent_hint') }}</span>
            </a>
            {{-- "Entrega confirmada" (delivered_at, ver ProviderWebhookAdapter) es un
                 dato de ventana (misma que la gráfica de tendencia / el delta de
                 arriba), no un acumulado histórico como el resto de tarjetas de esta
                 fila — computeStats() todavía no expone un total histórico propio,
                 así que aquí se usa directamente el "current" del delta. --}}
            <div class="evx-stat is-static" title="{{ __('helpdeskemailactivity::emaillog.stats.delivered_tooltip') }}">
                <span class="evx-stat-label">{{ __('helpdeskemailactivity::emaillog.stats.delivered') }}</span>
                <span class="evx-stat-value-row">
                    <span class="evx-stat-value">{{ number_format($statsDelta['delivered']['current'] ?? 0) }}</span>
                    @include('helpdeskemailactivity::emails.partials.stat-delta', ['delta' => $statsDelta['delivered'] ?? null])
                </span>
                <span class="evx-stat-hint">{{ __('helpdeskemailactivity::emaillog.stats.delivered_hint') }}</span>
            </div>
            <a href="{{ route('helpdeskemailactivity.index', ['status' => 'failed']) }}"
               class="evx-stat accent-danger {{ $activeStatus === 'failed' ? 'is-active' : '' }}">
                <span class="evx-stat-label">{{ __('helpdeskemailactivity::emaillog.stats.failed') }}</span>
                <span class="evx-stat-value-row">
                    <span class="evx-stat-value">{{ number_format($stats['failed']) }}</span>
                    @include('helpdeskemailactivity::emails.partials.stat-delta', ['delta' => $statsDelta['failed'] ?? null])
                </span>
                <span class="evx-stat-hint">{{ __('helpdeskemailactivity::emaillog.stats.failed_hint') }}</span>
            </a>
            <a href="{{ route('helpdeskemailactivity.index', ['status' => 'bounced']) }}"
               class="evx-stat accent-danger {{ $activeStatus === 'bounced' ? 'is-active' : '' }}">
                <span class="evx-stat-label">{{ __('helpdeskemailactivity::emaillog.stats.bounced') }}</span>
                <span class="evx-stat-value-row">
                    <span class="evx-stat-value">{{ number_format($stats['bounced']) }}</span>
                    @include('helpdeskemailactivity::emails.partials.stat-delta', ['delta' => $statsDelta['bounced'] ?? null])
                </span>
                <span class="evx-stat-hint">{{ __('helpdeskemailactivity::emaillog.stats.bounced_hint') }}</span>
            </a>
            <a href="{{ route('helpdeskemailactivity.index', ['status' => 'complained']) }}"
               class="evx-stat accent-danger {{ $activeStatus === 'complained' ? 'is-active' : '' }}">
                <span class="evx-stat-label">{{ __('helpdeskemailactivity::emaillog.stats.complained') }}</span>
                <span class="evx-stat-value-row">
                    <span class="evx-stat-value">{{ number_format($stats['complained']) }}</span>
                    @include('helpdeskemailactivity::emails.partials.stat-delta', ['delta' => $statsDelta['complained'] ?? null])
                </span>
                <span class="evx-stat-hint">{{ __('helpdeskemailactivity::emaillog.stats.complained_hint') }}</span>
            </a>
            <a href="{{ route('helpdeskemailactivity.index', ['status' => 'queued']) }}"
               class="evx-stat accent-warning {{ $activeStatus === 'queued' ? 'is-active' : '' }}">
                <span class="evx-stat-label">{{ __('helpdeskemailactivity::emaillog.stats.queued') }}</span>
                <span class="evx-stat-value-row">
                    <span class="evx-stat-value">{{ number_format($stats['queued']) }}</span>
                    @include('helpdeskemailactivity::emails.partials.stat-delta', ['delta' => $statsDelta['queued'] ?? null])
                </span>
                {{-- Como el mockup: si hay encolados que llevan demasiado tiempo
                     sin confirmarse, el hint lo dice en vez del texto genérico. --}}
                <span class="evx-stat-hint">
                    @if($staleCount > 0)
                        <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
                        {{ __('helpdeskemailactivity::emaillog.stats.queued_hint_stale', ['count' => number_format($staleCount), 'hours' => $staleHours]) }}
                    @else
                        {{ __('helpdeskemailactivity::emaillog.stats.queued_hint') }}
                    @endif
                </span>
            </a>
            <a href="{{ route('helpdeskemailactivity.index', ['date_from' => $today, 'date_to' => $today]) }}"
               class="evx-stat {{ $isToday ? 'is-active' : '' }}">
                <span class="evx-stat-label">{{ __('helpdeskemailactivity::emaillog.stats.today') }}</span>
                <span class="evx-stat-value-row">
                    <span class="evx-stat-value">{{ number_format($stats['today']) }}</span>
                    @include('helpdeskemailactivity::emails.partials.stat-delta', ['delta' => $statsDelta['today'] ?? null])
                </span>
                <span class="evx-stat-hint">{{ __('helpdeskemailactivity::emaillog.stats.today_hint') }}</span>
            </a>
            <div class="evx-stat is-static">
                <span class="evx-stat-label">{{ __('helpdeskemailactivity::emaillog.stats.delivery_rate') }}</span>
                <span class="evx-stat-value-row">
                    <span class="evx-stat-value">{{ $deliveryRate }}%</span>
                    @include('helpdeskemailactivity::emails.partials.stat-delta', ['delta' => $statsDelta['delivery_rate'] ?? null, 'isRate' => true])
                </span>
                <span class="evx-stat-hint">{{ __('helpdeskemailactivity::emaillog.stats.delivery_rate_hint') }}</span>
            </div>
            {{-- Tasa de apertura/clic — clicables solo cuando hay algo que
                 filtrar (al menos un envío con seguimiento); si nunca hubo
                 seguimiento se muestran como "Sin datos", sin enlace, para no
                 llevar a un filtro que devolvería la lista vacía. --}}
            @if($openRate !== null)
                <a href="{{ route('helpdeskemailactivity.index', ['engagement' => 'opened']) }}"
                   class="evx-stat {{ $activeEngagement === 'opened' ? 'is-active' : '' }}">
                    <span class="evx-stat-label">{{ __('helpdeskemailactivity::emaillog.stats.open_rate') }}</span>
                    <span class="evx-stat-value-row">
                        <span class="evx-stat-value">{{ $openRate }}%</span>
                        @include('helpdeskemailactivity::emails.partials.stat-delta', ['delta' => $statsDelta['open_rate'] ?? null, 'isRate' => true])
                    </span>
                    <span class="evx-stat-hint">{{ __('helpdeskemailactivity::emaillog.stats.open_rate_hint', ['count' => $stats['open_tracked']]) }}</span>
                </a>
            @else
                <div class="evx-stat is-static">
                    <span class="evx-stat-label">{{ __('helpdeskemailactivity::emaillog.stats.open_rate') }}</span>
                    <span class="evx-stat-value">{{ __('helpdeskemailactivity::emaillog.stats.open_rate_no_data') }}</span>
                    <span class="evx-stat-hint">{{ __('helpdeskemailactivity::emaillog.stats.open_rate_no_data_hint') }}</span>
                </div>
            @endif
            @if($clickRate !== null)
                <a href="{{ route('helpdeskemailactivity.index', ['engagement' => 'clicked']) }}"
                   class="evx-stat {{ $activeEngagement === 'clicked' ? 'is-active' : '' }}">
                    <span class="evx-stat-label">{{ __('helpdeskemailactivity::emaillog.stats.click_rate') }}</span>
                    <span class="evx-stat-value-row">
                        <span class="evx-stat-value">{{ $clickRate }}%</span>
                        @include('helpdeskemailactivity::emails.partials.stat-delta', ['delta' => $statsDelta['click_rate'] ?? null, 'isRate' => true])
                    </span>
                    <span class="evx-stat-hint">{{ __('helpdeskemailactivity::emaillog.stats.click_rate_hint', ['count' => $stats['click_tracked']]) }}</span>
                </a>
            @else
                <div class="evx-stat is-static">
                    <span class="evx-stat-label">{{ __('helpdeskemailactivity::emaillog.stats.click_rate') }}</span>
                    <span class="evx-stat-value">{{ __('helpdeskemailactivity::emaillog.stats.click_rate_no_data') }}</span>
                    <span class="evx-stat-hint">{{ __('helpdeskemailactivity::emaillog.stats.click_rate_no_data_hint') }}</span>
                </div>
            @endif
        </div>

        {{-- Gráfica de tendencia --}}
        <div class="evx-trend">
            <div class="evx-block-head">
                <div>
                    <span class="t">{{ __('helpdeskemailactivity::emaillog.trend.title') }}</span>
                    <span class="s">{{ __('helpdeskemailactivity::emaillog.trend.hint') }}</span>
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
                {{-- La altura real la fija .evx-trend .evx-block-body por CSS (el
                     canvas es responsive y la hereda del contenedor); este
                     atributo solo evita el salto de layout antes de que
                     Chart.js monte. --}}
                <canvas id="emaillog-trend" height="160" role="img"
                        aria-label="{{ __('helpdeskemailactivity::emaillog.trend.title') }}: {{ $trendSent }} {{ __('helpdeskemailactivity::emaillog.trend.sent') }}, {{ $trendFailed }} {{ __('helpdeskemailactivity::emaillog.trend.failed') }}, {{ $trendBounced }} {{ __('helpdeskemailactivity::emaillog.trend.bounced') }}, {{ $trendComplained }} {{ __('helpdeskemailactivity::emaillog.trend.complained') }}, {{ $trendQueued }} {{ __('helpdeskemailactivity::emaillog.trend.queued') }}."></canvas>
            </div>
        </div>

        @if($staleCount > 0)
            <a href="{{ route('helpdeskemailactivity.index', ['status' => 'queued']) }}" class="evx-stale-banner" role="alert">
                <i class="fas fa-triangle-exclamation" aria-hidden="true"></i>
                <span>{{ __('helpdeskemailactivity::emaillog.stale.warning', ['count' => $staleCount, 'hours' => $staleHours]) }}</span>
                <span class="evx-stale-cta">{{ __('helpdeskemailactivity::emaillog.stale.view') }}</span>
            </a>
        @endif

        {{-- Barra de filtros única: selects + rango de fechas + vistas
             guardadas inline + modo de vista + exportar + contador de
             resultados, todo en una sola fila (mockup Alvarez). Las vistas
             guardadas conservan el mismo #evx-saved-views/data-*/IDs que
             consume el JS de @push('scripts'); solo cambian de posición en
             el DOM, nunca de lógica. --}}
        <form action="{{ route('helpdeskemailactivity.index') }}" method="GET" class="evx-filterbar" id="evx-filters-form">
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
                    {{ __('helpdeskemailactivity::emaillog.filters.entity_active', ['entity' => $entityFilterLabel]) }}
                    <a href="{{ route('helpdeskemailactivity.index', request()->except(['entity_type', 'entity_id', 'page'])) }}"
                       aria-label="{{ __('helpdeskemailactivity::emaillog.filters.clear') }}">
                        <i class="fas fa-xmark" aria-hidden="true"></i>
                    </a>
                </span>
            @endif

            {{-- El buscador vive en la barra de herramientas de arriba (envía
                 este mismo formulario vía atributo form), como en el mockup. --}}

            <span class="evx-select-icon">
                <i class="fas fa-cube" aria-hidden="true"></i>
                <select name="module" class="evx-select">
                    <option value="">{{ __('helpdeskemailactivity::emaillog.filters.all_modules') }}</option>
                    @foreach($modules as $mod)
                        <option value="{{ $mod }}" @selected(request('module') === $mod)>{{ $mod }}</option>
                    @endforeach
                </select>
            </span>

            <span class="evx-select-icon">
                <i class="fas fa-circle-half-stroke" aria-hidden="true"></i>
                <select name="status" class="evx-select">
                    <option value="">{{ __('helpdeskemailactivity::emaillog.filters.all_statuses') }}</option>
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
                // Filtros que viven dentro del modal: el contador del botón dice
                // cuántos están puestos, para que nunca haya un filtro aplicado
                // que no se vea desde la barra.
                $advancedFilters = ['engagement', 'causer_id', 'from_address', 'has_attachments',
                    'mailable_class', 'recipient_domain', 'has_error', 'linked', 'tracked', 'stale'];
                $advancedActive = collect($advancedFilters)
                    ->filter(fn ($f) => filled(request($f)))
                    ->count();
            @endphp
            <button type="button" class="evx-filter-more-toggle" id="evx-filters-more-toggle">
                <i class="fas fa-sliders" aria-hidden="true"></i>
                {{ __('helpdeskemailactivity::emaillog.filters.more') }}
                @if($advancedActive)
                    <span class="evx-filter-more-count">{{ $advancedActive }}</span>
                @endif
            </button>

            <span class="evx-select-icon">
                <i class="fas fa-calendar" aria-hidden="true"></i>
                {{-- Abre #emaillog-dates-modal (presets + rango manual), en vez
                     del daterangepicker genérico: el mockup ofrece los cuatro
                     rangos que de verdad se usan a un clic. El texto del botón
                     es el rango activo, o el placeholder si no hay ninguno. --}}
                <button type="button" class="evx-input evx-date-trigger" id="evx-dates-trigger">
                    {{ ($dateFrom && $dateTo)
                        ? $dateFrom.' – '.$dateTo
                        : __('helpdeskemailactivity::emaillog.filters.date_range') }}
                </button>
            </span>

            <select name="per_page" id="per-page" class="evx-select evx-select-sm"
                    title="{{ __('helpdeskemailactivity::emaillog.filters.per_page') }}">
                @foreach($perPageOptions as $opt)
                    <option value="{{ $opt }}" @selected((int) $perPage === (int) $opt)>{{ __('helpdeskemailactivity::emaillog.filters.per_page_option', ['n' => $opt]) }}</option>
                @endforeach
            </select>

            <button type="submit" class="evx-btn evx-btn-primary evx-btn-inline"
                    aria-label="{{ __('helpdeskemailactivity::emaillog.filters.search') }}"
                    title="{{ __('helpdeskemailactivity::emaillog.filters.search') }}">
                <i class="fas fa-magnifying-glass" aria-hidden="true"></i>
            </button>

            <span class="evx-filter-divider" aria-hidden="true"></span>

            {{-- Vistas guardadas — mismo #evx-saved-views + data-*/JS de antes. --}}
            <div class="evx-saved-views-inline" id="evx-saved-views"
                 data-index-url="{{ route('helpdeskemailactivity.index') }}"
                 data-views-url="{{ route('helpdeskemailactivity.views.index') }}"
                 data-views-store-url="{{ route('helpdeskemailactivity.views.store') }}"
                 data-can-manage="{{ $canManageList ? '1' : '0' }}">
                <span class="evx-filter-views-label">{{ __('helpdeskemailactivity::emaillog.views.heading') }}</span>
                <div class="evx-saved-views-list" id="evx-saved-views-list">
                    {{-- Vistas predefinidas del mockup (Todos/Fallidos/Rebotes) — filtran
                         de verdad vía query string, no son vistas guardadas en BD. "En cola"
                         sustituye a "Newsletter": ese módulo no existe aquí como filtro fijo. --}}
                    @php
                        $quickViews = [
                            ['label' => __('helpdeskemailactivity::emaillog.quick_views.all'), 'params' => [], 'active' => ! $hasFilters],
                            ['label' => __('helpdeskemailactivity::emaillog.quick_views.failed'), 'params' => ['status' => 'failed'], 'active' => $activeStatus === 'failed'],
                            ['label' => __('helpdeskemailactivity::emaillog.quick_views.bounced'), 'params' => ['status' => 'bounced'], 'active' => $activeStatus === 'bounced'],
                            ['label' => __('helpdeskemailactivity::emaillog.quick_views.queued'), 'params' => ['status' => 'queued'], 'active' => $activeStatus === 'queued'],
                        ];
                    @endphp
                    @foreach($quickViews as $quickView)
                        <span class="evx-view-chip {{ $quickView['active'] ? 'is-active' : '' }}">
                            <a href="{{ route('helpdeskemailactivity.index', $quickView['params']) }}">{{ $quickView['label'] }}</a>
                        </span>
                    @endforeach
                    <span class="text-muted small" id="evx-saved-views-empty">{{ __('helpdeskemailactivity::emaillog.views.no_views') }}</span>
                </div>
                {{-- "+" en vez del icono de marcador y en minúscula, como el
                     mockup: es la única pastilla que crea algo, y el signo la
                     distingue de las vistas ya guardadas mejor que un icono. --}}
                <button type="button" class="evx-save-view-btn" id="evx-save-view-btn">
                    + {{ __('helpdeskemailactivity::emaillog.views.save_current') }}
                </button>
            </div>


            {{-- "Exportar CSV" vive en la barra de herramientas de arriba
                 (botón primario verde), como en el mockup. --}}

            <div class="evx-filterbar-end">
                <span class="evx-filter-count">
                    {{ trans_choice('helpdeskemailactivity::emaillog.filters.results_count', $logs->total(), ['count' => number_format($logs->total())]) }}
                </span>
                @if($hasFilters)
                    <a href="{{ route('helpdeskemailactivity.index') }}" class="evx-filter-clear">
                        {{ __('helpdeskemailactivity::emaillog.filters.clear') }}
                    </a>
                @endif
            </div>
        </form>

        {{-- Tabla --}}
        @php
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
                    @if($canManageList)
                        <input type="checkbox" id="select-all" class="evx-list-check"
                               aria-label="{{ __('helpdeskemailactivity::emaillog.table.select_all') }}">
                        <label for="select-all" class="evx-list-head-label">{{ __('helpdeskemailactivity::emaillog.table.select_all') }}</label>
                    @endif
                    <label for="evx-sort-select" class="visually-hidden">{{ __('helpdeskemailactivity::emaillog.sort.label') }}</label>
                    <select id="evx-sort-select" class="evx-select evx-select-sm evx-list-sort">
                        <option value="{{ $sortOptionUrl('date', 'desc') }}" @selected($currentSortOption === 'date_desc')>{{ __('helpdeskemailactivity::emaillog.sort.date_desc') }}</option>
                        <option value="{{ $sortOptionUrl('date', 'asc') }}" @selected($currentSortOption === 'date_asc')>{{ __('helpdeskemailactivity::emaillog.sort.date_asc') }}</option>
                        <option value="{{ $sortOptionUrl('subject', 'asc') }}" @selected($currentSortOption === 'subject_asc')>{{ __('helpdeskemailactivity::emaillog.sort.subject_asc') }}</option>
                        <option value="{{ $sortOptionUrl('status', 'desc') }}" @selected($currentSortOption === 'status')>{{ __('helpdeskemailactivity::emaillog.sort.status') }}</option>
                    </select>

                    {{-- Accesos directos a la selección masiva (mismo checkbox
                         .bulk-checkbox que la toolbar flotante #bulk-toolbar,
                         ver @push('scripts')) — no duplican lógica: cada icono
                         solo re-dispara el clic del botón real de la toolbar. --}}
                    @if($canManageList)
                        <div class="evx-list-head-icons">
                            <button type="button" id="list-head-bulk-resend"
                                    title="{{ __('helpdeskemailactivity::emaillog.actions.bulk_resend') }}"
                                    aria-label="{{ __('helpdeskemailactivity::emaillog.actions.bulk_resend') }}">
                                <i class="fa-solid fa-rotate-right" aria-hidden="true"></i>
                            </button>
                            <button type="button" id="list-head-bulk-export"
                                    title="{{ __('helpdeskemailactivity::emaillog.actions.bulk_export') }}"
                                    aria-label="{{ __('helpdeskemailactivity::emaillog.actions.bulk_export') }}">
                                <i class="fa-solid fa-download" aria-hidden="true"></i>
                            </button>
                            <button type="button" id="list-head-bulk-delete"
                                    title="{{ __('helpdeskemailactivity::emaillog.actions.bulk_delete') }}"
                                    aria-label="{{ __('helpdeskemailactivity::emaillog.actions.bulk_delete') }}">
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
                        $rowUrl = route('helpdeskemailactivity.show', $row->uid);
                    @endphp
                    <div class="evx-row {{ $log && $log->uid === $row->uid ? 'is-active' : '' }}" data-href="{{ $rowUrl }}">
                        @if($canManageList)
                            <input type="checkbox" class="bulk-checkbox evx-row-check" value="{{ $row->uid }}"
                                   aria-label="{{ $row->subject ?: __('helpdeskemailactivity::emaillog.table.subject') }}">
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
                                       aria-label="{{ __('helpdeskemailactivity::emaillog.table.has_attachments') }}"
                                       title="{{ __('helpdeskemailactivity::emaillog.table.has_attachments') }}"></i>
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
                                {{ __('helpdeskemailactivity::emaillog.preview.field.to') }}: {{ $recipientLine }}@if($row->body_snippet)<span class="evx-row-snippet"> · {{ $row->body_snippet }}</span>@endif
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
                        <p>{{ __('helpdeskemailactivity::emaillog.table.empty') }}</p>
                    </div>
                @endforelse
            </div>


            {{-- Pie compacto del mockup: contador a la izquierda y "‹ pág. N ›"
                 a la derecha, en mono. El paginador numérico de Laravel ocupaba
                 varias líneas dentro de una columna de 390px y rompía el ritmo
                 de la lista. Se muestra siempre (no solo con varias páginas)
                 para que el contador de registros no desaparezca. --}}
            <div class="evx-list-foot">
                <span>{{ __('helpdeskemailactivity::emaillog.pagination.showing', ['first' => $logs->firstItem() ?? 0, 'last' => $logs->lastItem() ?? 0, 'total' => number_format($logs->total())]) }}</span>
                <span class="evx-list-foot-nav">
                    @if($logs->onFirstPage())
                        <span class="evx-page-btn is-disabled" aria-hidden="true"><i class="fa-solid fa-chevron-left"></i></span>
                    @else
                        <a href="{{ $logs->previousPageUrl() }}" class="evx-page-btn"
                           aria-label="{{ __('pagination.previous') }}"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></a>
                    @endif
                    <span>{{ __('helpdeskemailactivity::emaillog.pagination.page', ['page' => $logs->currentPage(), 'last' => $logs->lastPage()]) }}</span>
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
                @include('helpdeskemailactivity::emails.partials.detail-panel', [
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

    @can('helpdeskemailactivity.manage')
        {{-- Toolbar flotante de acciones masivas --}}
        <div id="bulk-toolbar" class="evx-bulk-toolbar d-none">
            <span class="evx-bulk-count"><span data-bulk-count>0</span> {{ __('helpdeskemailactivity::emaillog.bulk.label') }}</span>
            <button type="button" class="evx-btn evx-btn-primary evx-btn-inline" id="bulk-resend"
                    data-url="{{ route('helpdeskemailactivity.bulk-resend') }}">
                {{ __('helpdeskemailactivity::emaillog.actions.bulk_resend') }}
            </button>
            <button type="button" class="evx-btn evx-btn-outline evx-btn-inline" id="bulk-export"
                    data-url="{{ route('helpdeskemailactivity.export-selected') }}">
                {{ __('helpdeskemailactivity::emaillog.actions.bulk_export') }}
            </button>
            <button type="button" class="evx-btn evx-btn-danger evx-btn-inline" id="bulk-delete"
                    data-url="{{ route('helpdeskemailactivity.bulk-destroy') }}">
                {{ __('helpdeskemailactivity::emaillog.actions.bulk_delete') }}
            </button>
        </div>
    @endcan

    {{-- Modal "Más filtros": los filtros que no caben en la barra. Vive DENTRO
         del formulario de filtros (#evx-filters-form, ver arriba) para que sus
         campos viajen en el mismo submit que los de la barra — de ahí que no
         esté junto a los otros modales del final del archivo. --}}
    <div class="modal fade evx-dialog" id="emaillog-filters-modal" tabindex="-1"
         aria-labelledby="emaillog-filters-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                @include('helpdeskemailactivity::emails.partials.modal-head', [
                    'icon' => 'fa-sliders',
                    'eyebrow' => __('helpdeskemailactivity::emaillog.modal.eyebrow.filters'),
                    'title' => __('helpdeskemailactivity::emaillog.filters.heading'),
                    'titleId' => 'emaillog-filters-title',
                ])
                <div class="modal-body">
                    <p>{{ __('helpdeskemailactivity::emaillog.filters.description') }}</p>

                    <div class="evx-filter-grid">
                        {{-- "Sin abrir"/"Sin clic" solo tiene sentido sobre envíos
                             CON seguimiento — ver applyFilters(). --}}
                        <label class="evx-filter-field">
                            <span class="k">{{ __('helpdeskemailactivity::emaillog.filters.field.engagement') }}</span>
                            <select form="evx-filters-form" name="engagement" class="evx-select">
                                <option value="">{{ __('helpdeskemailactivity::emaillog.filters.all_engagement') }}</option>
                                <option value="opened" @selected($activeEngagement === 'opened')>{{ __('helpdeskemailactivity::emaillog.filters.engagement_opened') }}</option>
                                <option value="not_opened" @selected($activeEngagement === 'not_opened')>{{ __('helpdeskemailactivity::emaillog.filters.engagement_not_opened') }}</option>
                                <option value="clicked" @selected($activeEngagement === 'clicked')>{{ __('helpdeskemailactivity::emaillog.filters.engagement_clicked') }}</option>
                                <option value="not_clicked" @selected($activeEngagement === 'not_clicked')>{{ __('helpdeskemailactivity::emaillog.filters.engagement_not_clicked') }}</option>
                            </select>
                        </label>

                        {{-- Con o sin píxel de seguimiento. Distinto de
                             "engagement": aquí no se pregunta si lo abrieron,
                             sino si el envío llegó a poder medirse. --}}
                        <label class="evx-filter-field">
                            <span class="k">{{ __('helpdeskemailactivity::emaillog.filters.field.tracked') }}</span>
                            <select form="evx-filters-form" name="tracked" class="evx-select">
                                <option value="">{{ __('helpdeskemailactivity::emaillog.filters.tracked_any') }}</option>
                                <option value="1" @selected(request('tracked') === '1')>{{ __('helpdeskemailactivity::emaillog.filters.tracked_yes') }}</option>
                                <option value="0" @selected(request('tracked') === '0')>{{ __('helpdeskemailactivity::emaillog.filters.tracked_no') }}</option>
                            </select>
                        </label>

                        {{-- Agente: solo usuarios que REALMENTE aparecen como
                             causer en el log (ver computeAgentOptions()), no
                             todos los del sistema. --}}
                        @if($agents->isNotEmpty())
                            <label class="evx-filter-field">
                                <span class="k">{{ __('helpdeskemailactivity::emaillog.filters.field.agent') }}</span>
                                <select form="evx-filters-form" name="causer_id" class="evx-select">
                                    <option value="">{{ __('helpdeskemailactivity::emaillog.filters.all_agents') }}</option>
                                    @foreach($agents as $agent)
                                        <option value="{{ $agent->id }}" @selected((string) request('causer_id') === (string) $agent->id)>{{ $agent->name ?? $agent->email }}</option>
                                    @endforeach
                                </select>
                            </label>
                        @endif

                        @if(count($fromAddresses))
                            <label class="evx-filter-field">
                                <span class="k">{{ __('helpdeskemailactivity::emaillog.filters.field.from') }}</span>
                                <select form="evx-filters-form" name="from_address" class="evx-select">
                                    <option value="">{{ __('helpdeskemailactivity::emaillog.filters.all_from_addresses') }}</option>
                                    @foreach($fromAddresses as $address)
                                        <option value="{{ $address }}" @selected(request('from_address') === $address)>{{ $address }}</option>
                                    @endforeach
                                </select>
                            </label>
                        @endif

                        {{-- Dominio del destinatario: dice de un vistazo si el
                             problema es de un cliente o de todo un dominio. --}}
                        @if(count($recipientDomains))
                            <label class="evx-filter-field">
                                <span class="k">{{ __('helpdeskemailactivity::emaillog.filters.field.recipient_domain') }}</span>
                                <select form="evx-filters-form" name="recipient_domain" class="evx-select">
                                    <option value="">{{ __('helpdeskemailactivity::emaillog.filters.all_domains') }}</option>
                                    @foreach($recipientDomains as $domain)
                                        <option value="{{ $domain }}" @selected(request('recipient_domain') === $domain)>&#64;{{ $domain }}</option>
                                    @endforeach
                                </select>
                            </label>
                        @endif

                        {{-- Tipo de email: se muestra el nombre corto de la clase
                             (el FQCN completo no cabe y no aporta al leerlo). --}}
                        @if(count($mailableClasses))
                            <label class="evx-filter-field">
                                <span class="k">{{ __('helpdeskemailactivity::emaillog.filters.field.mailable') }}</span>
                                <select form="evx-filters-form" name="mailable_class" class="evx-select">
                                    <option value="">{{ __('helpdeskemailactivity::emaillog.filters.all_mailables') }}</option>
                                    @foreach($mailableClasses as $mailable)
                                        <option value="{{ $mailable }}" @selected(request('mailable_class') === $mailable)>{{ Str::headline(class_basename($mailable)) }}</option>
                                    @endforeach
                                </select>
                            </label>
                        @endif

                        <label class="evx-filter-field">
                            <span class="k">{{ __('helpdeskemailactivity::emaillog.filters.field.linked') }}</span>
                            <select form="evx-filters-form" name="linked" class="evx-select">
                                <option value="">{{ __('helpdeskemailactivity::emaillog.filters.linked_any') }}</option>
                                <option value="1" @selected(request('linked') === '1')>{{ __('helpdeskemailactivity::emaillog.filters.linked_yes') }}</option>
                                <option value="0" @selected(request('linked') === '0')>{{ __('helpdeskemailactivity::emaillog.filters.linked_no') }}</option>
                            </select>
                        </label>

                        <label class="evx-filter-field">
                            <span class="k">{{ __('helpdeskemailactivity::emaillog.filters.field.attachments') }}</span>
                            <select form="evx-filters-form" name="has_attachments" class="evx-select">
                                <option value="" @selected(! request()->boolean('has_attachments'))>{{ __('helpdeskemailactivity::emaillog.filters.attachments_only') }}</option>
                                <option value="1" @selected(request()->boolean('has_attachments'))>{{ __('helpdeskemailactivity::emaillog.filters.attachments_only_yes') }}</option>
                            </select>
                        </label>

                        {{-- Diagnóstico: "con error" no es lo mismo que
                             status=failed (un rebote también trae error, y un
                             fallido antiguo puede no tenerlo). --}}
                        <label class="evx-filter-field">
                            <span class="k">{{ __('helpdeskemailactivity::emaillog.filters.field.error') }}</span>
                            <select form="evx-filters-form" name="has_error" class="evx-select">
                                <option value="" @selected(! request()->boolean('has_error'))>{{ __('helpdeskemailactivity::emaillog.filters.error_any') }}</option>
                                <option value="1" @selected(request()->boolean('has_error'))>{{ __('helpdeskemailactivity::emaillog.filters.error_only') }}</option>
                            </select>
                        </label>

                        {{-- Estancados: mismo criterio (y mismas horas) que el
                             banner de aviso y el KPI "En cola". --}}
                        <label class="evx-filter-field">
                            <span class="k">{{ __('helpdeskemailactivity::emaillog.filters.field.stale') }}</span>
                            <select form="evx-filters-form" name="stale" class="evx-select">
                                <option value="" @selected(! request()->boolean('stale'))>{{ __('helpdeskemailactivity::emaillog.filters.stale_any') }}</option>
                                <option value="1" @selected(request()->boolean('stale'))>{{ __('helpdeskemailactivity::emaillog.filters.stale_only', ['hours' => $staleHours]) }}</option>
                            </select>
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" form="evx-filters-form" class="btn btn-primary">
                        {{ __('helpdeskemailactivity::emaillog.filters.apply') }}
                    </button>
                    <a href="{{ route('helpdeskemailactivity.index') }}" class="btn btn-light">
                        {{ __('helpdeskemailactivity::emaillog.filters.clear') }}
                    </a>

                    {{-- Reenviar TODO el resultado del filtro, no solo lo
                         marcado a mano. Vive aquí porque el criterio es el
                         filtro que se acaba de ajustar arriba. Solo con
                         permiso de gestión y solo si hay resultados. --}}
                    @can('helpdeskemailactivity.manage')
                        @if($logs->total() > 0)
                            <button type="button" class="btn btn-light w-100 mt-2" id="evx-bulk-resend-filtered"
                                    data-count="{{ $logs->total() }}">
                                {{ __('helpdeskemailactivity::emaillog.resend.bulk_filtered', ['count' => number_format($logs->total())]) }}
                            </button>
                        @endif
                    @endcan
                </div>
            </div>
        </div>
    </div>

    {{-- Modal "Rango de fechas" (mockup mDates): cuatro presets de un clic más
         el rango manual. Escribe en los hidden #date_from/#date_to del
         formulario de filtros, así que no necesita campos propios: el submit
         viaja por el mismo camino que el resto de la barra. --}}
    @php
        // Los rangos se calculan aquí, no en JS, para que el subtítulo de cada
        // preset enseñe las fechas reales y coincidan con lo que se enviará.
        $datePresets = [
            'today' => [
                'icon' => 'fa-sun',
                'from' => now()->startOfDay(),
                'to' => now(),
                'hint' => now()->translatedFormat('d M').' · '.__('helpdeskemailactivity::emaillog.filters.preset_since_midnight'),
            ],
            'last7' => [
                'icon' => 'fa-business-time',
                'from' => now()->subDays(6)->startOfDay(),
                'to' => now(),
                'hint' => now()->subDays(6)->translatedFormat('d M').' – '.now()->translatedFormat('d M'),
            ],
            'last14' => [
                'icon' => 'fa-calendar-days',
                'from' => now()->subDays(13)->startOfDay(),
                'to' => now(),
                'hint' => now()->subDays(13)->translatedFormat('d M').' – '.now()->translatedFormat('d M'),
            ],
            'month' => [
                'icon' => 'fa-calendar-week',
                'from' => now()->startOfMonth(),
                'to' => now()->endOfMonth(),
                'hint' => now()->translatedFormat('F Y'),
            ],
        ];
    @endphp
    <div class="modal fade evx-dialog" id="emaillog-dates-modal" tabindex="-1"
         aria-labelledby="emaillog-dates-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                @include('helpdeskemailactivity::emails.partials.modal-head', [
                    'icon' => 'fa-calendar',
                    'eyebrow' => __('helpdeskemailactivity::emaillog.modal.eyebrow.dates'),
                    'title' => __('helpdeskemailactivity::emaillog.filters.date_range'),
                    'titleId' => 'emaillog-dates-title',
                ])
                <div class="modal-body">
                    <div class="evx-preset-list">
                        @foreach($datePresets as $key => $preset)
                            @php
                                $presetFrom = $preset['from']->format('Y-m-d');
                                $presetTo = $preset['to']->format('Y-m-d');
                                $isActive = $dateFrom === $presetFrom && $dateTo === $presetTo;
                            @endphp
                            <button type="button" class="evx-preset {{ $isActive ? 'is-active' : '' }}"
                                    data-from="{{ $presetFrom }}" data-to="{{ $presetTo }}">
                                <i class="fa-solid {{ $preset['icon'] }}" aria-hidden="true"></i>
                                <span class="evx-preset-main">
                                    <span class="t">{{ __('helpdeskemailactivity::emaillog.filters.preset.'.$key) }}</span>
                                    <span class="s">{{ $preset['hint'] }}</span>
                                </span>
                                @if($isActive)
                                    <i class="fa-solid fa-check evx-preset-check" aria-hidden="true"></i>
                                @endif
                            </button>
                        @endforeach
                    </div>

                    <div class="evx-filter-grid">
                        <label class="evx-filter-field">
                            <span class="k">{{ __('helpdeskemailactivity::emaillog.filters.date_from') }}</span>
                            <input type="date" class="evx-input" id="evx-dates-from" value="{{ $dateFrom }}">
                        </label>
                        <label class="evx-filter-field">
                            <span class="k">{{ __('helpdeskemailactivity::emaillog.filters.date_to') }}</span>
                            <input type="date" class="evx-input" id="evx-dates-to" value="{{ $dateTo }}">
                        </label>
                    </div>

                    {{-- Cuántos registros hay ahora mismo con los filtros
                         activos: da la medida antes de cambiar el rango. --}}
                    <div class="evx-note-card">
                        <i class="fa-solid fa-chart-simple" aria-hidden="true"></i>
                        <span>{!! __('helpdeskemailactivity::emaillog.filters.date_scope', ['count' => '<strong>'.number_format($logs->total()).'</strong>']) !!}</span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" id="evx-dates-apply">
                        {{ __('helpdeskemailactivity::emaillog.filters.date_apply') }}
                    </button>
                    <button type="button" class="btn btn-light" id="evx-dates-clear">
                        {{ __('helpdeskemailactivity::emaillog.filters.date_clear') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal "Guardar vista actual" (mockup mView) — sustituye al par
         window.prompt() + window.confirm() que había: dos diálogos nativos
         seguidos, sin forma de ver qué filtros se estaban guardando ni de
         volver atrás del primero. Aquí se ve el nombre, el resumen de filtros
         y la visibilidad de una vez. --}}
    <div class="modal fade evx-dialog" id="emaillog-view-modal" tabindex="-1"
         aria-labelledby="emaillog-view-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                @include('helpdeskemailactivity::emails.partials.modal-head', [
                    'icon' => 'fa-bookmark',
                    'eyebrow' => __('helpdeskemailactivity::emaillog.modal.eyebrow.views'),
                    'title' => __('helpdeskemailactivity::emaillog.views.save_title'),
                    'titleId' => 'emaillog-view-title',
                ])
                <div class="modal-body">
                    <div>
                        <label for="evx-view-name" class="form-label">
                            {{ __('helpdeskemailactivity::emaillog.views.name_label') }}
                        </label>
                        <input type="text" class="form-control" id="evx-view-name" maxlength="120"
                               placeholder="{{ __('helpdeskemailactivity::emaillog.views.name_placeholder_example') }}">
                    </div>

                    {{-- Qué filtros se guardan: lo rellena el JS leyendo la
                         query actual, que es exactamente lo que se envía. --}}
                    <div class="evx-dialog-context">
                        <span class="evx-dialog-eyebrow">{{ __('helpdeskemailactivity::emaillog.views.filters_saved') }}</span>
                        <span id="evx-view-summary"></span>
                    </div>

                    {{-- Solo quien puede gestionar el módulo decide publicar una
                         vista; a un lector normal no se le ofrece y la vista se
                         crea privada, igual que antes con el confirm(). --}}
                    @can('helpdeskemailactivity.manage')
                        <div>
                            <span class="form-label d-block">{{ __('helpdeskemailactivity::emaillog.views.visibility') }}</span>
                            <div class="evx-dialog-chips" role="group">
                                <button type="button" class="evx-dialog-chip is-active" data-view-public="0">
                                    {{ __('helpdeskemailactivity::emaillog.views.visibility_mine') }}
                                </button>
                                <button type="button" class="evx-dialog-chip" data-view-public="1">
                                    {{ __('helpdeskemailactivity::emaillog.views.visibility_team') }}
                                </button>
                            </div>
                        </div>
                    @endcan

                    <div class="evx-dialog-note">
                        <i class="fa-solid fa-list-check" aria-hidden="true"></i>
                        <span>{!! __('helpdeskemailactivity::emaillog.views.save_hint', ['count' => '<strong>'.number_format($logs->total()).'</strong>']) !!}</span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" id="evx-view-save">
                        {{ __('helpdeskemailactivity::emaillog.views.save') }}
                    </button>
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                        {{ __('helpdeskemailactivity::emaillog.confirm.cancel') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal "Jobs fallidos" (mockup mQueue) — solo los de la cola de correo:
         failed_jobs es una tabla compartida por toda la app y este módulo no
         reintenta ni purga trabajo ajeno (ver EmailQueueController). Se rellena
         por AJAX al abrirlo, no en el render de la página: son datos de
         operación que caducan en segundos. --}}
    @can('helpdeskemailactivity.manage')
        <div class="modal fade evx-dialog" id="emaillog-queue-modal" tabindex="-1"
             aria-labelledby="emaillog-queue-title" aria-hidden="true"
             data-index-url="{{ route('helpdeskemailactivity.queue.index') }}"
             data-retry-all-url="{{ route('helpdeskemailactivity.queue.retry-all') }}"
             data-flush-url="{{ route('helpdeskemailactivity.queue.flush') }}"
             data-retry-url="{{ route('helpdeskemailactivity.queue.retry', ['uuid' => '__UUID__']) }}">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    @include('helpdeskemailactivity::emails.partials.modal-head', [
                        'icon' => 'fa-layer-group',
                        'eyebrow' => __('helpdeskemailactivity::emaillog.modal.eyebrow.queue'),
                        'title' => __('helpdeskemailactivity::emaillog.queue.title'),
                        'titleId' => 'emaillog-queue-title',
                    ])
                    <div class="modal-body">
                        <div class="evx-queue-stats">
                            <span class="evx-queue-stat">
                                <span class="v" id="evx-queue-pending">—</span>
                                <span class="k">{{ __('helpdeskemailactivity::emaillog.queue.pending') }}</span>
                            </span>
                            <span class="evx-queue-stat">
                                <span class="v" id="evx-queue-failed">—</span>
                                <span class="k">{{ __('helpdeskemailactivity::emaillog.queue.failed') }}</span>
                            </span>
                        </div>

                        <div class="evx-event-list" id="evx-queue-list"></div>

                        <div class="evx-note-card">
                            <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                            <span>{{ __('helpdeskemailactivity::emaillog.queue.hint') }}</span>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" id="evx-queue-retry-all">
                            {{ __('helpdeskemailactivity::emaillog.queue.retry_all') }}
                        </button>
                        <button type="button" class="btn btn-light" id="evx-queue-flush">
                            {{ __('helpdeskemailactivity::emaillog.queue.flush') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endcan

    {{-- Modal "Anonimizar destinatario" (mockup mGdpr) — derecho de supresión
         del RGPD. Acción IRREVERSIBLE: exige teclear la dirección dos veces
         (ver AnonymizeRecipientRequest) y enseña el alcance antes de ejecutar.
         Qué se borra y qué se conserva, en RecipientAnonymizerService. --}}
    @can('helpdeskemailactivity.manage')
        <div class="modal fade evx-dialog" id="emaillog-gdpr-modal" tabindex="-1"
             aria-labelledby="emaillog-gdpr-title" aria-hidden="true"
             data-preview-url="{{ route('helpdeskemailactivity.gdpr.preview') }}"
             data-run-url="{{ route('helpdeskemailactivity.gdpr.anonymize') }}">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    @include('helpdeskemailactivity::emails.partials.modal-head', [
                        'icon' => 'fa-user-shield',
                        'eyebrow' => __('helpdeskemailactivity::emaillog.modal.eyebrow.gdpr'),
                        'title' => __('helpdeskemailactivity::emaillog.gdpr.title'),
                        'titleId' => 'emaillog-gdpr-title',
                    ])
                    <div class="modal-body">
                        <div>
                            <label for="evx-gdpr-email" class="form-label">
                                {{ __('helpdeskemailactivity::emaillog.gdpr.email_label') }}
                            </label>
                            <input type="email" class="form-control" id="evx-gdpr-email"
                                   placeholder="{{ __('helpdeskemailactivity::emaillog.resend.to_placeholder') }}">
                        </div>

                        {{-- Alcance real, consultado al servidor: enseña cuántos
                             envíos se van a tocar antes de tocarlos. --}}
                        <div class="evx-dialog-context" id="evx-gdpr-scope" hidden>
                            <span class="evx-dialog-eyebrow">{{ __('helpdeskemailactivity::emaillog.gdpr.scope_emails') }}</span>
                            <span id="evx-gdpr-scope-text"></span>
                        </div>

                        <div class="evx-gdpr-options">
                            <label class="form-check">
                                <input class="form-check-input" type="checkbox" id="evx-gdpr-purge" checked>
                                <span class="form-check-label">{{ __('helpdeskemailactivity::emaillog.gdpr.purge_body') }}</span>
                            </label>
                            <label class="form-check">
                                <input class="form-check-input" type="checkbox" id="evx-gdpr-replace" checked>
                                <span class="form-check-label">{{ __('helpdeskemailactivity::emaillog.gdpr.replace_address') }}</span>
                            </label>
                            <label class="form-check">
                                <input class="form-check-input" type="checkbox" id="evx-gdpr-suppress" checked>
                                <span class="form-check-label">{{ __('helpdeskemailactivity::emaillog.gdpr.suppress') }}</span>
                            </label>
                        </div>

                        <div class="evx-alert">
                            <strong>{{ __('helpdeskemailactivity::emaillog.gdpr.warning_title') }}</strong>
                            {{ __('helpdeskemailactivity::emaillog.gdpr.warning') }}
                        </div>

                        {{-- La confirmación escrita va la última y sin valor
                             previo: es el paso que impide el clic accidental. --}}
                        <div>
                            <label for="evx-gdpr-confirm" class="form-label">
                                {{ __('helpdeskemailactivity::emaillog.gdpr.confirmation_label') }}
                            </label>
                            <input type="email" class="form-control" id="evx-gdpr-confirm" autocomplete="off">
                        </div>

                        <div class="evx-note-card">
                            <i class="fa-solid fa-clipboard-list" aria-hidden="true"></i>
                            <span>{{ __('helpdeskemailactivity::emaillog.gdpr.audit_note') }}</span>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" id="evx-gdpr-run">
                            {{ __('helpdeskemailactivity::emaillog.gdpr.run') }}
                        </button>
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                            {{ __('helpdeskemailactivity::emaillog.confirm.cancel') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endcan

    {{-- Envío del reenvío por filtro: POST con los filtros activos copiados de
         la query actual más el total esperado, que el controlador vuelve a
         comprobar antes de encolar nada (ver bulkResendFiltered()). --}}
    @can('helpdeskemailactivity.manage')
        <form method="POST" action="{{ route('helpdeskemailactivity.bulk-resend-filtered') }}" id="evx-bulk-resend-form" hidden>
            @csrf
            <input type="hidden" name="expected" value="{{ $logs->total() }}">
            @foreach(request()->except(['page', '_token', 'expected']) as $key => $value)
                @if(is_scalar($value))
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endif
            @endforeach
        </form>
    @endcan

    {{-- Toast del mockup: una sola línea negra centrada abajo, con el icono en
         el verde de la marca. El componente .evx-toast ya vivía en
         emaillog-extras.css pero nada lo usaba — todo el módulo llamaba a
         toastr, que pinta el estilo genérico del tema. Markup fijo aquí (fuera
         de #evx-detail-cols) para que sobreviva al reemplazo AJAX del detalle,
         igual que los modales. --}}
    <div class="evx-toast" id="emaillog-toast" role="status" aria-live="polite" hidden>
        <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
        <span id="emaillog-toast-msg"></span>
    </div>

    {{-- Modal de confirmación reutilizable. El icono, el título, el texto de
         confirmación y la etiqueta del botón los fija evxConfirm() según la
         acción; la tarjeta de contexto se rellena con el email sobre el que
         se actúa y se oculta cuando la acción es masiva (varios registros). --}}
    <div class="modal fade evx-dialog" id="emaillog-confirm-modal" tabindex="-1"
         aria-labelledby="emaillog-confirm-title" aria-describedby="emaillog-confirm-message" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                @include('helpdeskemailactivity::emails.partials.modal-head', [
                    'icon' => 'fa-circle-question',
                    'iconId' => 'emaillog-confirm-icon',
                    'eyebrow' => __('helpdeskemailactivity::emaillog.modal.eyebrow.confirm'),
                    'title' => __('helpdeskemailactivity::emaillog.confirm.title'),
                    'titleId' => 'emaillog-confirm-title',
                ])
                <div class="modal-body">
                    <p id="emaillog-confirm-message">—</p>

                    <div class="evx-dialog-context" id="emaillog-confirm-context" hidden>
                        <span class="evx-dialog-context-title" id="emaillog-confirm-context-title"></span>
                        <span class="evx-dialog-context-sub" id="emaillog-confirm-context-sub"></span>
                    </div>

                    <div class="evx-dialog-keys">
                        <kbd>&crarr;</kbd> {{ __('helpdeskemailactivity::emaillog.modal.kbd_confirm') }}
                        <kbd>esc</kbd> {{ __('helpdeskemailactivity::emaillog.modal.kbd_cancel') }}
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" id="emaillog-confirm-accept">
                        {{ __('helpdeskemailactivity::emaillog.confirm.accept') }}
                    </button>
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                        {{ __('helpdeskemailactivity::emaillog.confirm.cancel') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    @can('helpdeskemailactivity.manage')
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
                    @include('helpdeskemailactivity::emails.partials.modal-head', [
                        'icon' => 'fa-share',
                        'eyebrow' => __('helpdeskemailactivity::emaillog.modal.eyebrow.resend'),
                        'title' => __('helpdeskemailactivity::emaillog.resend.to_title'),
                        'titleId' => 'emaillog-resendto-title',
                    ])
                    <div class="modal-body">
                        <div class="evx-dialog-context">
                            <span class="evx-dialog-context-title" id="resendto-context-title"></span>
                            <span class="evx-dialog-context-sub" id="resendto-context-sub"></span>
                        </div>

                        <div>
                            <label for="resendto-email" class="form-label">
                                {{ __('helpdeskemailactivity::emaillog.resend.to_label') }}
                            </label>
                            <input type="email" class="form-control" id="resendto-email"
                                   placeholder="{{ __('helpdeskemailactivity::emaillog.resend.to_placeholder') }}">
                            <div class="form-text">{{ __('helpdeskemailactivity::emaillog.resend.to_hint') }}</div>
                        </div>

                        {{-- Direcciones ya usadas en reenvíos anteriores desde este
                             navegador (localStorage, ver @push('scripts')): el
                             mockup las ofrece como atajo. El bloque queda oculto
                             mientras no haya ninguna. --}}
                        <div id="resendto-recent-wrap" hidden>
                            <span class="evx-dialog-eyebrow">{{ __('helpdeskemailactivity::emaillog.resend.to_recent') }}</span>
                            <div class="evx-dialog-chips" id="resendto-recent"></div>
                        </div>

                        <div class="evx-dialog-note">
                            <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                            <span>{{ __('helpdeskemailactivity::emaillog.resend.to_note') }}</span>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" id="resendto-send">
                            {{ __('helpdeskemailactivity::emaillog.resend.to_send') }}
                        </button>
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                            {{ __('helpdeskemailactivity::emaillog.confirm.cancel') }}
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
                    @include('helpdeskemailactivity::emails.partials.modal-head', [
                        'icon' => 'fa-triangle-exclamation',
                        'eyebrow' => __('helpdeskemailactivity::emaillog.modal.eyebrow.bounce'),
                        'title' => __('helpdeskemailactivity::emaillog.bounce_triage.title'),
                        'titleId' => 'emaillog-bounce-triage-title',
                    ])
                    <div class="modal-body">
                        <p>{{ __('helpdeskemailactivity::emaillog.bounce_triage.hint') }}</p>

                        {{-- Dirección que rebotó + motivo del proveedor, en la
                             tarjeta de contexto del mockup. --}}
                        <div class="evx-dialog-context">
                            <span class="evx-dialog-context-title" id="bounce-old-address">—</span>
                            <span class="evx-dialog-context-sub" id="bounce-error-message"></span>
                        </div>

                        <div>
                            <label for="bounce-corrected-email" class="form-label">
                                {{ __('helpdeskemailactivity::emaillog.bounce_triage.corrected_label') }}
                            </label>
                            <input type="email" class="form-control" id="bounce-corrected-email"
                                   placeholder="{{ __('helpdeskemailactivity::emaillog.bounce_triage.corrected_placeholder') }}">
                        </div>

                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="bounce-suppress-old">
                            <label class="form-check-label small" for="bounce-suppress-old">
                                {{ __('helpdeskemailactivity::emaillog.bounce_triage.suppress_label') }}
                            </label>
                            <div class="form-text" id="bounce-suppress-hint"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" id="bounce-triage-send">
                            {{ __('helpdeskemailactivity::emaillog.bounce_triage.send') }}
                        </button>
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                            {{ __('helpdeskemailactivity::emaillog.confirm.cancel') }}
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
                    @include('helpdeskemailactivity::emails.partials.modal-head', [
                        'icon' => 'fa-ticket',
                        'eyebrow' => __('helpdeskemailactivity::emaillog.modal.eyebrow.link'),
                        'title' => __('helpdeskemailactivity::emaillog.link_entity.title'),
                        'titleId' => 'emaillog-link-entity-title',
                    ])
                    <div class="modal-body">
                        <p>{{ __('helpdeskemailactivity::emaillog.link_entity.hint') }}</p>

                        <input type="search" class="form-control" id="link-entity-search" autocomplete="off"
                               placeholder="{{ __('helpdeskemailactivity::emaillog.link_entity.search_placeholder') }}">

                        <div id="link-entity-results" class="list-group"></div>

                        <div class="evx-dialog-context d-none" id="link-entity-selected">
                            <span class="evx-dialog-eyebrow">{{ __('helpdeskemailactivity::emaillog.link_entity.selected_label') }}</span>
                            <span class="evx-dialog-context-title" id="link-entity-selected-label"></span>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" id="link-entity-send" disabled>
                            {{ __('helpdeskemailactivity::emaillog.link_entity.send') }}
                        </button>
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                            {{ __('helpdeskemailactivity::emaillog.confirm.cancel') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endcan

    @include('helpdeskemailactivity::partials.select2')
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="{{ asset('core/js/bulk.js') }}"></script>
<script>
$(function () {
    // Toast propio del módulo (ver #emaillog-toast). Se expone con la misma
    // forma que toastr para no reescribir las ~20 llamadas que ya existen:
    // 'error' solo cambia el color del icono, porque el mockup tiene un único
    // toast y el módulo no usa rojos.
    const toastr = (function () {
        const $toast = $('#emaillog-toast');
        const $msg = $('#emaillog-toast-msg');
        let timer = null;

        function show(message, isError) {
            if (! message) return;
            $msg.text(message);
            $toast.toggleClass('is-error', !! isError).prop('hidden', false);
            clearTimeout(timer);
            // 4 s: da tiempo a leer una línea sin quedarse tapando la vista.
            timer = setTimeout(() => $toast.prop('hidden', true), 4000);
        }

        return {
            success: msg => show(msg, false),
            error: msg => show(msg, true),
            info: msg => show(msg, false),
            warning: msg => show(msg, true),
        };
    })();

    @if(session('success')) toastr.success(@json(session('success'))); @endif

    // Primera pintura: el detalle llega ya renderizado con la página, sin pasar
    // por loadDetail().
    $(function () {
        applyHighlighting(document);
        prefetchHtmlCheck(document);
    });
    @if(session('error')) toastr.error(@json(session('error'))); @endif

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
                    $('<i class="fas fa-users evx-chip-public" aria-hidden="true" title="{{ __('helpdeskemailactivity::emaillog.views.public_indicator') }}"></i>')
                        .appendTo($chip);
                }
                {{-- La clase evx-view-del la usa el CSS para dar a la pastilla
                     menos aire por la derecha solo cuando lleva este botón. --}}
                $('<button type="button" class="evx-view-del" title="{{ __('helpdeskemailactivity::emaillog.views.delete') }}" aria-label="{{ __('helpdeskemailactivity::emaillog.views.delete') }}"><i class="fas fa-xmark" aria-hidden="true"></i></button>')
                    .attr('data-view-id', v.id)
                    .appendTo($chip);
                $chip.insertBefore($empty);
            });
        }

        $.getJSON(viewsUrl).done(function (res) {
            if (res.success) render(res.views);
        });

        // Guardar vista — modal en vez de prompt()+confirm() nativos.
        const $viewModal = $('#emaillog-view-modal');
        const viewModal = new bootstrap.Modal($viewModal[0]);

        // Etiquetas legibles de los filtros, para el resumen del modal: sin
        // esto el usuario guardaría "lo que haya" sin ver qué es.
        const filterLabels = @json(__('helpdeskemailactivity::emaillog.views.filter_labels'));

        function currentFilters() {
            const filters = Object.fromEntries(new URLSearchParams(window.location.search).entries());
            delete filters.page;
            // Los vacíos viajan en la query (el form envía todos los campos)
            // pero no son filtros: guardarlos ensuciaría la vista.
            Object.keys(filters).forEach(k => { if (filters[k] === '') delete filters[k]; });
            return filters;
        }

        $('#evx-save-view-btn').on('click', function () {
            const filters = currentFilters();
            const summary = Object.entries(filters)
                .map(([k, v]) => (filterLabels[k] || k) + ': ' + v)
                .join(' · ');

            $('#evx-view-name').val('').removeClass('is-invalid');
            $('#evx-view-summary').text(summary || @json(__('helpdeskemailactivity::emaillog.views.no_filters')));
            $viewModal.find('[data-view-public]').removeClass('is-active')
                .filter('[data-view-public="0"]').addClass('is-active');
            viewModal.show();
        });

        $viewModal.on('click', '[data-view-public]', function () {
            $viewModal.find('[data-view-public]').removeClass('is-active');
            $(this).addClass('is-active');
        });

        $('#evx-view-save').on('click', function () {
            const $name = $('#evx-view-name');
            const name = ($name.val() || '').trim();
            if (! name) { $name.addClass('is-invalid').trigger('focus'); return; }
            $name.removeClass('is-invalid');

            // Sin permiso de gestión no hay chips de visibilidad y la vista se
            // crea privada, igual que antes.
            const isPublic = canManage && $viewModal.find('[data-view-public].is-active').data('view-public') === 1;

            $.ajax({
                url: storeUrl,
                method: 'POST',
                // 1/0, no true/false: jQuery serializa el booleano como la
                // cadena "false", que la regla boolean de Laravel rechaza.
                data: { name, filters: currentFilters(), is_public: isPublic ? 1 : 0 },
                headers: { 'X-CSRF-TOKEN': csrf },
            }).done(function (res) {
                if (res.success) {
                    viewModal.hide();
                    toastr.success(@json(__('helpdeskemailactivity::emaillog.views.saved')));
                    $.getJSON(viewsUrl).done(r => r.success && render(r.views));
                }
            }).fail(xhr => toastr.error(xhr.responseJSON?.message || 'Error'));
        });

        $list.on('click', 'button[data-view-id]', function () {
            const id = $(this).data('view-id');
            askConfirm({
                title: @json(__('helpdeskemailactivity::emaillog.confirm.delete_title')),
                message: @json(__('helpdeskemailactivity::emaillog.views.deleted')),
                icon: 'fa-trash-can',
                context: null,
                onAccept: () => {
                    $.ajax({
                        url: viewsUrl + '/' + id,
                        method: 'DELETE',
                        headers: { 'X-CSRF-TOKEN': csrf },
                    }).done(function (res) {
                        if (res.success) {
                            toastr.success(@json(__('helpdeskemailactivity::emaillog.views.deleted')));
                            $.getJSON(viewsUrl).done(r => r.success && render(r.views));
                        } else {
                            toastr.error(res.message || 'Error');
                        }
                    }).fail(xhr => toastr.error(xhr.responseJSON?.message || 'Error'));
                },
            });
        });
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
                    {{-- Paleta verdes/grises (nunca rojo/ámbar de alarma), la
                         misma que los puntos de estado del listado. --}}
                    { label: @json(__('helpdeskemailactivity::emaillog.trend.sent')), data: trend.sent, backgroundColor: '#90bb13', stack: 's', borderRadius: 2 },
                    { label: @json(__('helpdeskemailactivity::emaillog.trend.failed')), data: trend.failed, backgroundColor: '#52525b', stack: 's', borderRadius: 2 },
                    { label: @json(__('helpdeskemailactivity::emaillog.trend.bounced')), data: trend.bounced, backgroundColor: '#6d8f10', stack: 's', borderRadius: 2 },
                    { label: @json(__('helpdeskemailactivity::emaillog.trend.complained')), data: trend.complained, backgroundColor: '#27272a', stack: 's', borderRadius: 2 },
                    { label: @json(__('helpdeskemailactivity::emaillog.trend.queued')), data: trend.queued, backgroundColor: '#e4e4e7', stack: 's', borderRadius: 2 },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                {{-- Barras anchas pegadas entre sí (~6px de aire), como las del
                     mockup: por defecto Chart.js deja casi la mitad del hueco
                     vacío y las barras salen finas. Los dos factores se
                     multiplican, así que 0.94×0.94 dejaba 13px de hueco, el
                     doble de lo que debía. --}}
                barPercentage: 0.95,
                categoryPercentage: 1,
                {{-- Un día con 70 veces la mediana (una ráfaga puntual basta)
                     deja al resto por debajo de un píxel y el gráfico parece
                     vacío. minBarLength garantiza 2px a todo día CON envíos,
                     sin tocar la escala: las barras grandes siguen siendo
                     comparables entre sí y un día a cero sigue sin pintarse. --}}
                minBarLength: 2,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { boxWidth: 10, boxHeight: 10, font: { size: 10.5, family: 'Inter' }, color: '#52525b', padding: 10 },
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

    // Reenviar todo el resultado del filtro. Confirmación obligatoria con el
    // número exacto: son correos reales y no hay forma de retirarlos una vez
    // encolados.
    $(document).on('click', '#evx-bulk-resend-filtered', function () {
        const count = $(this).data('count');

        askConfirm({
            title: @json(__('helpdeskemailactivity::emaillog.resend.bulk_filtered_confirm_title')),
            message: @json(__('helpdeskemailactivity::emaillog.resend.bulk_filtered_confirm')).replace(':count', count),
            icon: 'fa-rotate-right',
            accept: @json(__('helpdeskemailactivity::emaillog.confirm.accept_resend')),
            context: null,
            onAccept: () => $('#evx-bulk-resend-form')[0].submit(),
        });
    });

    // Anonimizar destinatario (#emaillog-gdpr-modal) — RGPD, irreversible.
    (function () {
        const $modal = $('#emaillog-gdpr-modal');
        if (! $modal.length) return;

        const gdprModal = new bootstrap.Modal($modal[0]);
        const $email = $('#evx-gdpr-email');
        const $scope = $('#evx-gdpr-scope');
        const scopeTpl = @json(__('helpdeskemailactivity::emaillog.gdpr.scope_summary'));

        $(document).on('click', '#evx-gdpr-trigger', function () {
            $modal.find('input[type="email"]').val('');
            $modal.find('input[type="checkbox"]').prop('checked', true);
            $scope.prop('hidden', true);
            gdprModal.show();
        });

        // El alcance se consulta al dejar el campo, no al teclear: es una
        // consulta por dirección y no hace falta a cada pulsación.
        $email.on('blur', function () {
            const email = ($(this).val() || '').trim();
            if (! email) { $scope.prop('hidden', true); return; }

            $.ajax({
                url: $modal.data('preview-url'),
                method: 'POST',
                data: { email },
                headers: { 'X-CSRF-TOKEN': csrf },
            }).done(function (res) {
                if (! res.success) return;
                $('#evx-gdpr-scope-text').text(
                    scopeTpl.replace(':emails', res.preview.emails)
                        .replace(':body', res.preview.with_body)
                        .replace(':attachments', res.preview.attachments)
                );
                $scope.prop('hidden', false);
            }).fail(() => $scope.prop('hidden', true));
        });

        $('#evx-gdpr-run').on('click', function () {
            $.ajax({
                url: $modal.data('run-url'),
                method: 'POST',
                data: {
                    email: ($email.val() || '').trim(),
                    confirmation: ($('#evx-gdpr-confirm').val() || '').trim(),
                    purge_body: $('#evx-gdpr-purge').is(':checked') ? 1 : 0,
                    replace_address: $('#evx-gdpr-replace').is(':checked') ? 1 : 0,
                    suppress: $('#evx-gdpr-suppress').is(':checked') ? 1 : 0,
                },
                headers: { 'X-CSRF-TOKEN': csrf },
            }).done(function (res) {
                gdprModal.hide();
                toastr.success(res.message);
                // Recarga: las filas anonimizadas cambian en el listado y en
                // los KPIs, y dejarlas a la vista con el dato viejo confundiría.
                setTimeout(() => window.location.reload(), 1200);
            }).fail(function (xhr) {
                const errors = xhr.responseJSON?.errors;
                toastr.error(errors ? Object.values(errors)[0][0] : (xhr.responseJSON?.message || 'Error'));
            });
        });
    })();

    // Jobs fallidos de la cola de correo (#emaillog-queue-modal). Se piden al
    // abrir el modal y tras cada acción: son datos de operación que cambian
    // solos según trabajan los workers, así que renderizarlos con la página
    // los dejaría obsoletos al segundo.
    (function () {
        const $modal = $('#emaillog-queue-modal');
        if (! $modal.length) return;

        const queueModal = new bootstrap.Modal($modal[0]);
        const indexUrl = $modal.data('index-url');
        const retryUrlTemplate = $modal.data('retry-url');
        const $list = $('#evx-queue-list');
        const emptyLabel = @json(__('helpdeskemailactivity::emaillog.queue.empty'));
        const retryLabel = @json(__('helpdeskemailactivity::emaillog.queue.retry'));

        function escapeHtml(value) {
            return String(value ?? '').replace(/[&<>"']/g, c => (
                { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]
            ));
        }

        function render(data) {
            $('#evx-queue-pending').text(data.pending === null ? '—' : data.pending);
            $('#evx-queue-failed').text(data.failed);

            if (! data.jobs.length) {
                $list.html('<div class="evx-event-empty">' + escapeHtml(emptyLabel) + '</div>');
                return;
            }

            $list.html(data.jobs.map(job => ''
                + '<div class="evx-event-row is-stacked">'
                + '<span class="evx-event-icon"><i class="fa-solid fa-xmark"></i></span>'
                + '<span class="evx-event-main">'
                + '<span class="evx-event-url">' + escapeHtml(job.name) + '</span>'
                + '<span class="evx-event-sub">' + escapeHtml(job.error) + '</span>'
                + '<span class="evx-event-sub">' + escapeHtml(job.failed_at ?? '') + '</span>'
                + '</span>'
                + '<button type="button" class="evx-dialog-chip js-queue-retry" data-uuid="' + escapeHtml(job.uuid) + '">'
                + escapeHtml(retryLabel) + '</button>'
                + '</div>'
            ).join(''));
        }

        function load() {
            $.getJSON(indexUrl).done(res => res.success && render(res));
        }

        $(document).on('click', '#evx-queue-trigger', function () {
            $list.empty();
            queueModal.show();
            load();
        });

        // Reintentar uno: el uuid se sustituye en la plantilla de ruta para no
        // construir la URL a mano en JS.
        $modal.on('click', '.js-queue-retry', function () {
            const url = retryUrlTemplate.replace('__UUID__', encodeURIComponent($(this).data('uuid')));
            $.ajax({ url, method: 'POST', headers: { 'X-CSRF-TOKEN': csrf } })
                .done(res => { toastr.success(res.message); load(); })
                .fail(xhr => toastr.error(xhr.responseJSON?.message || 'Error'));
        });

        $('#evx-queue-retry-all').on('click', function () {
            $.ajax({ url: $modal.data('retry-all-url'), method: 'POST', headers: { 'X-CSRF-TOKEN': csrf } })
                .done(res => { toastr.success(res.message); load(); })
                .fail(xhr => toastr.error(xhr.responseJSON?.message || 'Error'));
        });

        // Purgar sí pide confirmación: descarta los jobs sin reintentarlos y no
        // hay vuelta atrás.
        $('#evx-queue-flush').on('click', function () {
            askConfirm({
                title: @json(__('helpdeskemailactivity::emaillog.queue.flush_confirm_title')),
                message: @json(__('helpdeskemailactivity::emaillog.queue.flush_confirm')),
                icon: 'fa-layer-group',
                accept: @json(__('helpdeskemailactivity::emaillog.queue.flush')),
                context: null,
                onAccept: () => {
                    $.ajax({ url: $modal.data('flush-url'), method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrf } })
                        .done(res => { toastr.success(res.message); load(); })
                        .fail(xhr => toastr.error(xhr.responseJSON?.message || 'Error'));
                },
            });
        });
    })();

    // Rango de fechas — modal propio (#emaillog-dates-modal) en vez del
    // daterangepicker genérico: cuatro presets a un clic más el rango manual,
    // como el mockup. Escribe en los hidden del formulario de filtros y envía.
    (function () {
        const $modal = $('#emaillog-dates-modal');
        if (! $modal.length) return;

        const datesModal = new bootstrap.Modal($modal[0]);
        const $from = $('#evx-dates-from');
        const $to = $('#evx-dates-to');

        $(document).on('click', '#evx-dates-trigger', () => datesModal.show());

        // Un preset solo rellena los dos campos: aplicar sigue siendo un paso
        // aparte, para poder ajustar el rango a mano antes de enviar.
        $modal.on('click', '.evx-preset', function () {
            $modal.find('.evx-preset').removeClass('is-active');
            $(this).addClass('is-active');
            $from.val($(this).data('from'));
            $to.val($(this).data('to'));
        });

        function submitWith(from, to) {
            $('#date_from').val(from);
            $('#date_to').val(to);
            $('#evx-filters-form')[0].submit();
        }

        $('#evx-dates-apply').on('click', () => submitWith($from.val() || '', $to.val() || ''));
        $('#evx-dates-clear').on('click', () => submitWith('', ''));
    })();

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
                applyHighlighting($detailCols);
                // El % de compatibilidad se pinta en su pestaña sin esperar a
                // que se abra, como en Mailpit: la petición sale en segundo
                // plano y no bloquea nada de lo que ya está en pantalla.
                prefetchHtmlCheck($detailCols);
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

    // "Más filtros": los filtros que no caben en la barra viven en un modal
    // (#emaillog-filters-modal). Sus campos van enganchados al formulario de
    // filtros con form="evx-filters-form", así que el botón Aplicar del modal
    // envía la barra entera de una vez.
    const filtersModal = new bootstrap.Modal(document.getElementById('emaillog-filters-modal'));
    $(document).on('click', '#evx-filters-more-toggle', () => filtersModal.show());

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

    // Cadenas del inspector, resueltas de una vez en PHP. Se agrupan aquí en
    // vez de interpolar @json(__(...)) en cada punto de uso porque el parser
    // de directivas de Blade corta la expresión en la primera coma de un array
    // asociativo con más de una clave (warnings_heading tiene dos).
    @php
        $inspectorI18n = [
            'support' => __('helpdeskemailactivity::emaillog.preview.inspector.html_check.support'),
            'supported' => __('helpdeskemailactivity::emaillog.preview.inspector.html_check.supported', ['pct' => ':pct']),
            'partiallySupported' => __('helpdeskemailactivity::emaillog.preview.inspector.html_check.partially_supported', ['pct' => ':pct']),
            'unsupported' => __('helpdeskemailactivity::emaillog.preview.inspector.html_check.unsupported', ['pct' => ':pct']),
            'calculatedFrom' => __('helpdeskemailactivity::emaillog.preview.inspector.html_check.calculated_from', ['count' => ':count']),
            'datasetNote' => __('helpdeskemailactivity::emaillog.preview.inspector.html_check.dataset_note', ['date' => ':date']),
            'platforms' => __('helpdeskemailactivity::emaillog.preview.inspector.html_check.platforms'),
            'warningsHeading' => trans_choice('helpdeskemailactivity::emaillog.preview.inspector.html_check.warnings_heading', 2, ['count' => ':count', 'nodes' => ':nodes']),
            'noWarnings' => __('helpdeskemailactivity::emaillog.preview.inspector.html_check.no_warnings'),
        'help' => __('helpdeskemailactivity::emaillog.preview.inspector.html_check.help'),
        'helpText' => __('helpdeskemailactivity::emaillog.preview.inspector.html_check.help_text'),
            'occurrences' => __('helpdeskemailactivity::emaillog.preview.inspector.html_check.occurrences', ['count' => ':count']),
            'clientsHeading' => __('helpdeskemailactivity::emaillog.preview.inspector.html_check.clients_heading'),
            'notesHeading' => __('helpdeskemailactivity::emaillog.preview.inspector.html_check.notes_heading'),
            'reference' => __('helpdeskemailactivity::emaillog.preview.inspector.html_check.reference'),
            'htmlCheckError' => __('helpdeskemailactivity::emaillog.preview.inspector.html_check.error'),
            'linkLoading' => __('helpdeskemailactivity::emaillog.preview.inspector.link_check.loading'),
            'linkRerun' => __('helpdeskemailactivity::emaillog.preview.inspector.link_check.rerun'),
            'linkError' => __('helpdeskemailactivity::emaillog.preview.inspector.link_check.error'),
            'linkEmpty' => __('helpdeskemailactivity::emaillog.preview.inspector.link_check.empty'),
            'linkScanned' => trans_choice('helpdeskemailactivity::emaillog.preview.inspector.link_check.scanned', 2, ['count' => ':count']),
            'linkStatusLabel' => __('helpdeskemailactivity::emaillog.preview.inspector.link_check.status_label', ['code' => ':code']),
        'linkLastChecked' => __('helpdeskemailactivity::emaillog.preview.inspector.link_check.last_checked', ['date' => ':date']),
        ];
    @endphp
    const I18N = @json($inspectorI18n);


    // ── Resaltado de sintaxis del inspector ──
    // Propio y sin librería a propósito: son ~50 líneas frente a los ~100 KB de
    // un highlight.js por CDN, y así el panel sigue resaltando igual sin salida
    // a internet. Trabaja SOBRE EL TEXTO YA ESCAPADO por Blade (se lee con
    // .text() y se vuelca con .html() envuelto en <span>), así que el HTML del
    // correo nunca llega al DOM del panel como marcado.
    function escapeHtml(text) {
        return text
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    function highlightHtml(source) {
        let out = escapeHtml(source);

        // Los comentarios se apartan antes de tocar las etiquetas: dentro de uno
        // no se resalta nada. El marcador lleva un prefijo largo y no un número
        // suelto — un placeholder numérico chocaría con cualquier cifra del
        // propio correo al reinsertarlos.
        const comments = [];
        out = out.replace(/&lt;!--[\s\S]*?--&gt;/g, function (match) {
            comments.push(match);
            return '@@EVX-COMMENT-' + (comments.length - 1) + '@@';
        });

        // Etiquetas completas: dentro se colorean nombre, atributos y valores.
        out = out.replace(/&lt;(\/?)([a-zA-Z][\w:-]*)((?:[^&]|&(?!gt;))*?)(\/?)&gt;/g, function (all, slash, name, attrs, selfClose) {
            const painted = attrs.replace(
                /([\w:-]+)(\s*=\s*)("[^"]*"|'[^']*')/g,
                '<span class="tok-attr">$1</span>$2<span class="tok-value">$3</span>'
            );

            return '<span class="tok-punct">&lt;' + slash + '</span>'
                + '<span class="tok-tag">' + name + '</span>'
                + painted
                + '<span class="tok-punct">' + selfClose + '&gt;</span>';
        });

        return out.replace(
            /@@EVX-COMMENT-(\d+)@@/g,
            (match, index) => '<span class="tok-comment">' + comments[index] + '</span>'
        );
    }

    // Mensaje completo: basta con separar el bloque de cabeceras del cuerpo y,
    // dentro de él, marcar el nombre de cada cabecera.
    function highlightMime(source) {
        const split = source.search(/\r?\n\r?\n/);
        const headers = split === -1 ? source : source.slice(0, split);
        const body = split === -1 ? '' : source.slice(split);

        const painted = escapeHtml(headers).replace(
            /^([!-9;-~]+)(:)/gm,
            '<span class="tok-attr">$1</span><span class="tok-punct">$2</span>'
        );

        return painted + '<span class="tok-body">' + escapeHtml(body) + '</span>';
    }

    // Se aplica al abrir el panel y tras cada carga AJAX de detalle. El guard
    // data-highlighted evita repintar (y volver a escapar) un bloque ya hecho.
    // Tope de resaltado. El cuerpo guardado puede llegar a max_body_bytes
    // (512 KB por defecto) y pasar medio megabyte por varias expresiones
    // regulares bloquea la pestaña del navegador. Por encima de esto se muestra
    // el código sin colorear, que es lo único que se pierde.
    const HIGHLIGHT_MAX_CHARS = 300000;

    function applyHighlighting(root) {
        $(root).find('.js-highlight-html:not([data-highlighted])').each(function () {
            const source = $(this).text();

            if (source.length > HIGHLIGHT_MAX_CHARS) {
                $(this).attr('data-highlighted', 'skipped');

                return;
            }

            $(this).html(highlightHtml(source)).attr('data-highlighted', '1');
        });

        $(root).find('.js-highlight-mime:not([data-highlighted])').each(function () {
            const source = $(this).text();

            if (source.length > HIGHLIGHT_MAX_CHARS) {
                $(this).attr('data-highlighted', 'skipped');

                return;
            }

            $(this).html(highlightMime(source)).attr('data-highlighted', '1');
        });
    }

    // Borrado definitivo desde el detalle de un registro en papelera. Es
    // irreversible: mismo texto de confirmación que en la papelera, y al
    // terminar se vuelve al listado porque el registro ya no existe.
    $(document).on('click', '.js-trash-force-delete', function () {
        const url = $(this).data('url');
        askConfirm({
            title: @json(__('helpdeskemailactivity::emaillog.trash.force_delete_confirm_title')),
            message: @json(__('helpdeskemailactivity::emaillog.trash.force_delete_confirm')),
            onAccept: () => {
                $.ajax({ url, method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrf } })
                    .done(() => { window.location.href = @json(route('helpdeskemailactivity.trash.index')); })
                    .fail(xhr => toastr.error(xhr.responseJSON?.message || 'Error'));
            },
        });
    });

    // Restaurar desde el detalle de un registro en papelera (misma clase y
    // mismo flujo que la papelera; aquí el detalle se abre en solo lectura).
    $(document).on('click', '.js-trash-restore', function () {
        const url = $(this).data('url');
        askConfirm({
            title: @json(__('helpdeskemailactivity::emaillog.trash.restore_confirm_title')),
            message: @json(__('helpdeskemailactivity::emaillog.trash.restore_confirm')),
            onAccept: () => {
                $.ajax({ url, method: 'POST', headers: { 'X-CSRF-TOKEN': csrf } })
                    .done(() => location.reload())
                    .fail(xhr => toastr.error(xhr.responseJSON?.message || 'Error'));
            },
        });
    });

    // ── Inspector del mensaje (pestaña "Mensaje") ──
    // Todo delegado en document por el mismo motivo que el resto del panel: el
    // fragmento de detalle se reemplaza entero en cada clic de fila.

    // Sub-pestañas del inspector. Acotadas al .evx-inspector que las contiene
    // para no chocar con las pestañas principales (.evx-tab), que son otras.
    $(document).on('click', '.evx-subtab', function () {
        const $inspector = $(this).closest('.evx-inspector');
        const target = $(this).data('evx-subtab');

        $inspector.find('.evx-subtab').removeClass('on').attr('aria-selected', 'false');
        $(this).addClass('on').attr('aria-selected', 'true');
        $inspector.find('.evx-subpanel').attr('hidden', true);
        $inspector.find('.evx-subpanel[data-evx-subpanel="' + target + '"]').removeAttr('hidden');

        if (target === 'html-check') {
            loadHtmlCheck($inspector);
        }

        if (target === 'link-check') {
            loadLinkCheckHistory($inspector);
        }
    });

    // Última comprobación guardada. Es una lectura de base de datos, no sale a
    // la red: enseña lo que ya se sabe en vez de una pestaña en blanco que
    // obliga a volver a llamar a todos los servidores.
    function loadLinkCheckHistory($inspector) {
        const $body = $inspector.find('.js-link-check-body');

        if ($body.length === 0 || $body.data('history-loaded')) return;

        $body.data('history-loaded', true);

        $.getJSON($body.data('history-url')).done(function (data) {
            if (! data.CheckedAt || ! data.Links || data.Links.length === 0) return;

            renderLinkCheck($inspector, data);
        });
    }

    // Ancho del iframe del inspector (escritorio / tableta / móvil) — acotado a
    // su propio grupo: el panel Detalle tiene otro conmutador igual y antes se
    // apagaban el botón activo el uno al otro.
    $(document).on('click', '.js-inspector-device', function () {
        const width = $(this).data('width');

        $(this).closest('.evx-inspector').find('#inspectorFrame')
            .removeClass('is-mobile is-tablet')
            .addClass(width ? 'is-' + width : '');

        $(this).closest('.evx-device-toggle').find('button')
            .removeClass('on').attr('aria-pressed', 'false');
        $(this).addClass('on').attr('aria-pressed', 'true');
    });

    // Copiar el contenido de un <pre> del inspector
    $(document).on('click', '.js-inspector-copy', function () {
        const text = $($(this).data('target')).text();
        const done = () => toastr.success($(this).data('toast'));

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

    // Filtro de cabeceras: sobre el nombre Y el valor, porque buscar un
    // dominio o un Message-ID concreto es tan habitual como buscar la cabecera.
    $(document).on('input', '.js-headers-filter', function () {
        const term = $(this).val().trim().toLowerCase();
        const $rows = $(this).closest('.evx-subpanel').find('.evx-header-row');
        let visible = 0;

        $rows.each(function () {
            const match = term === '' || $(this).text().toLowerCase().indexOf(term) !== -1;
            $(this).toggle(match);
            if (match) visible++;
        });

        $(this).closest('.evx-subpanel').find('.js-headers-empty').prop('hidden', visible > 0);
    });

    // ── Compatibilidad (caniemail) ──

    // Pide el análisis en segundo plano solo para pintar el % en la pestaña.
    // Se separa de loadHtmlCheck() porque aquí NO hay que dibujar el panel: si
    // el usuario nunca abre "Compatibilidad", el trabajo del navegador se queda
    // en poner un badge.
    // Petición de análisis en vuelo. Navegar rápido con J/K abriría una por
    // correo y todas seguirían corriendo aunque su panel ya no esté en pantalla:
    // se aborta la anterior antes de lanzar la siguiente.
    let htmlCheckRequest = null;

    function prefetchHtmlCheck(root) {
        const $inspector = $(root).find('.evx-inspector').first();

        if ($inspector.length === 0 || $inspector.data('html-check-state')) return;

        if (htmlCheckRequest) htmlCheckRequest.abort();

        $inspector.data('html-check-state', 'loading');

        htmlCheckRequest = $.getJSON($inspector.data('html-check-url'));

        htmlCheckRequest
            .done(function (data) {
                $inspector.data('html-check-state', 'done');
                $inspector.data('html-check-data', data);

                if (! data.available) {
                    $inspector.find('.js-html-check-body').html(
                        $('<div class="evx-empty-card">').text(data.reason || '')
                    );

                    return;
                }

                $inspector.data('html-check-platforms', Object.keys(data.Platforms || {}));
                renderHtmlCheck($inspector);
            })
            .fail(function (xhr, status) {
                // Sin marcar como fallido: al abrir la pestaña se reintenta.
                // Un abort() es una cancelación nuestra, no un error del
                // servidor — no debe pintar nada.
                $inspector.data('html-check-state', null);
                if (status !== 'abort') htmlCheckRequest = null;
            });
    }

    function loadHtmlCheck($inspector) {
        if ($inspector.data('html-check-state')) return;
        $inspector.data('html-check-state', 'loading');

        $.getJSON($inspector.data('html-check-url'))
            .done(function (data) {
                $inspector.data('html-check-state', 'done');
                $inspector.data('html-check-data', data);

                if (! data.available) {
                    $inspector.find('.js-html-check-body').html(
                        $('<div class="evx-empty-card">').text(data.reason || '')
                    );
                    return;
                }

                // Todas las plataformas activas de salida, como Mailpit.
                $inspector.data('html-check-platforms', Object.keys(data.Platforms || {}));
                renderHtmlCheck($inspector);
            })
            .fail(function () {
                $inspector.data('html-check-state', null);
                $inspector.find('.js-html-check-body').html(
                    $('<div class="evx-empty-card">').text(I18N.htmlCheckError)
                );
            });
    }

    // Recalcula la puntuación con las plataformas activas: mismo algoritmo que
    // EmailHtmlCheckService (peor caso ponderado por nodos afectados), aquí en
    // cliente para que mover un conmutador sea instantáneo y no otra petición.
    function scoreHtmlCheck(data, platforms) {
        const nodes = Math.max(1, data.Total.Nodes);
        const warnings = [];
        let partial = 0;
        let unsupported = 0;

        (data.Warnings || []).forEach(function (warning) {
            let yes = 0, no = 0, part = 0;
            let visibleResults = warning.Results || [];

            if (! warning.Results || warning.Results.length === 0) {
                // html-script y similares: no salen del dataset, su puntuación
                // es fija y no depende de qué plataformas estén activas.
                yes = warning.Score.Supported;
                part = warning.Score.Partial;
                no = warning.Score.Unsupported;
            } else {
                const results = warning.Results.filter(r => platforms.indexOf(r.Platform) !== -1);
                if (results.length === 0) return;

                results.forEach(function (r) {
                    if (r.Support === 'yes') yes++;
                    else if (r.Support === 'no') no++;
                    else part++;
                });

                const total = yes + no + part;
                yes = yes / total * 100;
                part = part / total * 100;
                no = no / total * 100;
                visibleResults = results;
            }

            const weight = warning.Score.Found / nodes;
            partial = Math.max(partial, part * weight);
            unsupported = Math.max(unsupported, no * weight);

            warnings.push($.extend({}, warning, {
                // Los clientes listados también respetan los conmutadores: si
                // solo se evalúa Windows, ver ahí un fallo de Gmail Android
                // contradice el propio filtro.
                Results: visibleResults,
                Score: {
                    Found: warning.Score.Found,
                    Supported: yes,
                    Partial: part,
                    Unsupported: no,
                },
            }));
        });

        warnings.sort(function (a, b) {
            const w = s => (s.Score.Unsupported + s.Score.Partial) * s.Score.Found / nodes;
            return w(b) - w(a);
        });

        return {
            warnings: warnings,
            nodes: nodes,
            supported: 100 - partial - unsupported,
            partial: partial,
            unsupported: unsupported,
        };
    }

    function pct(value) {
        return (Math.round(value * 100) / 100).toFixed(2);
    }

    function renderHtmlCheck($inspector) {
        const data = $inspector.data('html-check-data');
        const platforms = $inspector.data('html-check-platforms') || [];
        const score = scoreHtmlCheck(data, platforms);

        const $body = $('<div class="evx-htmlcheck">');

        // Cabecera: anillo con el porcentaje + leyenda + conmutadores.
        const $head = $('<div class="evx-htmlcheck-head">').appendTo($body);
        const $scoreBox = $('<div class="evx-htmlcheck-score">').appendTo($head);

        const circumference = 2 * Math.PI * 54;
        const dash = v => (v / 100 * circumference).toFixed(2) + ' ' + circumference;
        const offset = v => (-v / 100 * circumference).toFixed(2);

        $scoreBox.append(
            '<svg class="evx-donut" viewBox="0 0 120 120" role="img" aria-label="' + pct(score.supported) + '%">' +
            '<circle class="evx-donut-track" cx="60" cy="60" r="54"></circle>' +
            '<circle class="evx-donut-seg is-supported" cx="60" cy="60" r="54" stroke-dasharray="' + dash(score.supported) + '" stroke-dashoffset="0"></circle>' +
            '<circle class="evx-donut-seg is-partial" cx="60" cy="60" r="54" stroke-dasharray="' + dash(score.partial) + '" stroke-dashoffset="' + offset(score.supported) + '"></circle>' +
            '<circle class="evx-donut-seg is-unsupported" cx="60" cy="60" r="54" stroke-dasharray="' + dash(score.unsupported) + '" stroke-dashoffset="' + offset(score.supported + score.partial) + '"></circle>' +
            '</svg>'
        );

        $scoreBox.append(
            $('<div class="evx-donut-label">')
                .append($('<strong>').text(pct(score.supported) + '%'))
                .append($('<span>').text(I18N.support))
        );

        const $legend = $('<div class="evx-htmlcheck-legend">').appendTo($head);
        [
            ['is-supported', I18N.supported, score.supported],
            ['is-partial', I18N.partiallySupported, score.partial],
            ['is-unsupported', I18N.unsupported, score.unsupported],
        ].forEach(function (row) {
            $legend.append(
                $('<span class="evx-legend-item">')
                    .append($('<i class="evx-legend-dot ' + row[0] + '">'))
                    .append(document.createTextNode(row[1].replace(':pct', pct(row[2]))))
            );
        });

        $legend.append(
            $('<span class="evx-legend-meta">').text(
                I18N.calculatedFrom.replace(':count', data.Total.Tests)
            )
        );

        if (data.dataset_updated_at) {
            $legend.append(
                $('<span class="evx-legend-meta">').text(
                    I18N.datasetNote
                        .replace(':date', String(data.dataset_updated_at).substring(0, 10))
                )
            );
        }

        // El cálculo no es intuitivo (peor caso ponderado, no media): se explica
        // donde se lee el número, plegado para no robarle sitio.
        $legend.append(
            $('<details class="evx-htmlcheck-help">')
                .append($('<summary>').text(I18N.help))
                .append($('<p>').text(I18N.helpText))
        );

        // Conmutadores de plataforma: al cambiar uno se repinta el resto del
        // panel con el mismo dataset ya descargado, sin volver al servidor.
        //
        // El bloque se REUTILIZA entre repintados en vez de recrearse: si se
        // rehiciera, el checkbox recién pulsado sería otro nodo, perdería el
        // foco y desmarcar varias plataformas seguidas dejaría de funcionar
        // (los clics siguientes caerían sobre nodos ya desechados).
        let $platforms = $inspector.find('.evx-htmlcheck-platforms');

        if ($platforms.length === 0) {
            $platforms = $('<div class="evx-htmlcheck-platforms">');
            $platforms.append($('<h4>').text(I18N.platforms));

            Object.keys(data.Platforms || {}).sort().forEach(function (platform) {
                const id = 'evx-plat-' + platform;
                const $label = $('<label class="evx-check">').attr('for', id);
                $('<input type="checkbox">')
                    .attr('id', id)
                    .attr('data-platform', platform)
                    .prop('checked', platforms.indexOf(platform) !== -1)
                    .addClass('js-html-check-platform')
                    .appendTo($label);
                $('<span>').text(platformLabel(platform, data)).attr('title', (data.Platforms[platform] || []).join(', ')).appendTo($label);
                $platforms.append($label);
            });
        }

        $head.append($platforms);

        // Avisos, peores primero.
        const $list = $('<div class="evx-htmlcheck-warnings">').appendTo($body);

        $list.append(
            $('<h4>').text(
                I18N.warningsHeading
                    .replace(':count', score.warnings.length)
                    .replace(':nodes', score.nodes)
            )
        );

        if (score.warnings.length === 0) {
            $list.append($('<div class="evx-empty-card">').text(I18N.noWarnings));
        }

        score.warnings.forEach(function (warning) {
            $list.append(renderWarning(warning));
        });

        // detach() y no empty(): el bloque de plataformas que se acaba de
        // reinsertar en $body sigue vivo, hay que sacarlo del DOM viejo sin
        // destruirlo.
        $inspector.find('.js-html-check-body').children().detach();
        $inspector.find('.js-html-check-body').append($body);
        $inspector.find('[data-evx-score]').text(Math.round(score.supported) + '%').prop('hidden', false);
    }

    // Nombre legible de la plataforma: lo publica el propio dataset
    // (PlatformNames). El slug solo se usa si faltara, que no debería.
    function platformLabel(platform, data) {
        return (data.PlatformNames && data.PlatformNames[platform])
            || platform.replace(/-/g, ' ').replace(/^./, c => c.toUpperCase());
    }

    function renderWarning(warning) {
        const $row = $('<details class="evx-warning">');
        const $summary = $('<summary class="evx-warning-head">').appendTo($row);

        $summary.append($('<span class="evx-warning-title">').text(warning.Title));
        $summary.append($('<span class="evx-tag">').text(warning.Category));
        $summary.append($('<span class="evx-warning-count">').text(
            I18N.occurrences.replace(':count', warning.Score.Found)
        ));

        // Barra apilada: de un vistazo, cuánto del aviso es soporte pleno.
        const $bar = $('<span class="evx-warning-bar">').appendTo($summary);
        [['is-supported', warning.Score.Supported], ['is-partial', warning.Score.Partial], ['is-unsupported', warning.Score.Unsupported]]
            .forEach(function (seg) {
                if (seg[1] <= 0) return;
                $('<span class="evx-warning-seg ' + seg[0] + '">')
                    .css('width', seg[1] + '%')
                    .attr('title', pct(seg[1]) + '%')
                    .appendTo($bar);
            });

        const $detail = $('<div class="evx-warning-body">').appendTo($row);

        // Description y las notas vienen ya saneadas del servidor (solo <code>
        // y enlaces, ver EmailHtmlCheckService::markdownish).
        if (warning.Description) {
            $detail.append($('<p class="evx-warning-desc">').html(warning.Description));
        }

        const failing = (warning.Results || []).filter(r => r.Support !== 'yes');

        if (failing.length > 0) {
            $detail.append($('<h5>').text(I18N.clientsHeading));
            const $clients = $('<div class="evx-warning-clients">').appendTo($detail);

            failing.forEach(function (result) {
                const $client = $('<span class="evx-warning-client">')
                    .append($('<i class="evx-legend-dot ' + (result.Support === 'no' ? 'is-unsupported' : 'is-partial') + '">'))
                    .append(document.createTextNode(result.Name));

                if (result.NoteNumber) {
                    $client.append($('<sup class="evx-warning-note-ref">').text(result.NoteNumber));
                }

                $clients.append($client);
            });
        }

        const notes = warning.NotesByNumber || {};
        const noteKeys = Object.keys(notes).sort();

        if (noteKeys.length > 0) {
            $detail.append($('<h5>').text(I18N.notesHeading));
            const $notes = $('<ol class="evx-warning-notes">').appendTo($detail);
            noteKeys.forEach(function (key) {
                $notes.append($('<li>').attr('value', key).html(notes[key]));
            });
        }

        if (warning.URL) {
            $detail.append(
                $('<a class="evx-warning-link" target="_blank" rel="noopener noreferrer">')
                    .attr('href', warning.URL)
                    .text(I18N.reference)
            );
        }

        return $row;
    }

    $(document).on('change', '.js-html-check-platform', function () {
        const $inspector = $(this).closest('.evx-inspector');
        const platforms = [];

        $inspector.find('.js-html-check-platform:checked').each(function () {
            platforms.push($(this).data('platform'));
        });

        $inspector.data('html-check-platforms', platforms);
        renderHtmlCheck($inspector);
    });

    // ── Enlaces ──

    $(document).on('click', '.js-link-check-run', function () {
        const $inspector = $(this).closest('.evx-inspector');
        const $btn = $(this);
        const $body = $inspector.find('.js-link-check-body');

        $btn.prop('disabled', true);
        $body.html(
            $('<div class="evx-inspector-loading">')
                .append('<i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i>')
                .append(document.createTextNode(I18N.linkLoading))
        );

        $.ajax({
            url: $inspector.data('link-check-url'),
            method: 'POST',
            data: {
                _token: csrf,
                follow: $inspector.find('.js-link-check-follow').is(':checked') ? 1 : 0,
            },
        }).done(function (data) {
            renderLinkCheck($inspector, data);
            $btn.html('<i class="fa-solid fa-rotate" aria-hidden="true"></i> ' + I18N.linkRerun);
        }).fail(function () {
            $body.html($('<div class="evx-empty-card">').text(I18N.linkError));
        }).always(function () {
            $btn.prop('disabled', false);
        });
    });

    function renderLinkCheck($inspector, data) {
        const $body = $inspector.find('.js-link-check-body').empty();
        const links = data.Links || [];

        if (links.length === 0) {
            $body.append($('<div class="evx-empty-card">').text(I18N.linkEmpty));
            $inspector.find('[data-evx-link-errors]').prop('hidden', true);
            return;
        }

        const scanned = I18N.linkScanned
            .replace(':count', links.length);

        const $title = $('<h4 class="evx-linkcheck-title">').text(scanned);

        if (data.CheckedAt) {
            $title.append(
                $('<span class="evx-linkcheck-when">').text(
                    I18N.linkLastChecked.replace(':date', new Date(data.CheckedAt).toLocaleString())
                )
            );
        }

        $body.append($title);

        // Agrupado por código de respuesta, igual que Mailpit: lo que importa
        // es "qué respondió", no en qué orden aparecían en el correo.
        const groups = {};
        links.forEach(function (link) {
            const key = link.StatusCode + '|' + link.Status;
            (groups[key] = groups[key] || []).push(link);
        });

        Object.keys(groups).forEach(function (key) {
            const group = groups[key];
            const first = group[0];
            const isError = first.StatusCode >= 400 || first.StatusCode === -1;
            const $card = $('<div class="evx-linkcheck-group">').toggleClass('is-error', isError).appendTo($body);

            const $head = $('<div class="evx-linkcheck-group-head">').appendTo($card);
            $head.append($('<span class="evx-linkcheck-status">').text(
                first.StatusCode > 0
                    ? I18N.linkStatusLabel.replace(':code', first.StatusCode)
                    : ''
            ));
            $head.append($('<span class="evx-linkcheck-reason">').text(first.Status));

            group.forEach(function (link) {
                $('<a class="evx-linkcheck-url" target="_blank" rel="noopener noreferrer">')
                    .attr('href', link.URL)
                    .text(link.URL)
                    .appendTo($card);
            });
        });

        const $badge = $inspector.find('[data-evx-link-errors]');
        if (data.Errors > 0) {
            $badge.text(data.Errors).prop('hidden', false);
        } else {
            $badge.prop('hidden', true);
        }
    }

    // Alternar vista escritorio / móvil del contenido del email. El grupo se
    // acota con closest(): el inspector tiene su propio conmutador idéntico y
    // un selector global apagaría el botón activo del otro panel.
    $(document).on('click', '#btnDesktopView', function () {
        $('#previewFrame').removeClass('is-mobile');
        $(this).closest('.evx-device-toggle').find('button').removeClass('on').attr('aria-pressed', 'false');
        $(this).addClass('on').attr('aria-pressed', 'true');
    });
    $(document).on('click', '#btnMobileView', function () {
        $('#previewFrame').addClass('is-mobile');
        $(this).closest('.evx-device-toggle').find('button').removeClass('on').attr('aria-pressed', 'false');
        $(this).addClass('on').attr('aria-pressed', 'true');
    });
    $(document).on('click', '#btnPrint', () => window.print());

    // Copiar Message-ID
    $(document).on('click', '.evx-copy-btn', function () {
        const text = $(this).data('copy');
        const done = () => toastr.success(@json(__('helpdeskemailactivity::emaillog.copy.message_id_copied')));
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
            title: @json(__('helpdeskemailactivity::emaillog.resend.confirm_title')),
            message: @json(__('helpdeskemailactivity::emaillog.resend.confirm')),
            icon: 'fa-rotate-right',
            accept: @json(__('helpdeskemailactivity::emaillog.confirm.accept_resend')),
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
            title: @json(__('helpdeskemailactivity::emaillog.resend.test_confirm_title')),
            message: @json(__('helpdeskemailactivity::emaillog.resend.test_confirm')).replace(':email', to),
            icon: 'fa-vial',
            accept: @json(__('helpdeskemailactivity::emaillog.confirm.accept_test')),
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
            title: @json(__('helpdeskemailactivity::emaillog.confirm.delete_title')),
            message: @json(__('helpdeskemailactivity::emaillog.confirm.delete_one')),
            icon: 'fa-trash-can',
            accept: @json(__('helpdeskemailactivity::emaillog.confirm.accept_delete')),
            onAccept: () => {
                $.ajax({ url, method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrf } })
                    .done(() => window.location = @json(route('helpdeskemailactivity.index')))
                    .fail(xhr => toastr.error(xhr.responseJSON?.message || 'Error'));
            },
        });
    });

    // Purgar contenido (GDPR)
    $(document).on('click', '.js-purge', function () {
        const url = $(this).data('url');
        askConfirm({
            title: @json(__('helpdeskemailactivity::emaillog.purge.confirm_title')),
            message: @json(__('helpdeskemailactivity::emaillog.purge.confirm')),
            icon: 'fa-eraser',
            accept: @json(__('helpdeskemailactivity::emaillog.confirm.accept_purge')),
            onAccept: () => {
                $.ajax({ url, method: 'POST', headers: { 'X-CSRF-TOKEN': csrf } })
                    .done(() => location.reload())
                    .fail(xhr => toastr.error(xhr.responseJSON?.message || 'Error'));
            },
        });
    });

    @can('helpdeskemailactivity.manage')
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
    const RESEND_RECENT_KEY = 'helpdeskemailactivity.resend_recent';
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
    const bounceSuppressHardHint = @json(__('helpdeskemailactivity::emaillog.bounce_triage.suppress_hint_hard'));
    const bounceSuppressSoftHint = @json(__('helpdeskemailactivity::emaillog.bounce_triage.suppress_hint_soft'));

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
                .text(@json(__('helpdeskemailactivity::emaillog.link_entity.no_results')))
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

    @can('helpdeskemailactivity.manage')
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
        if (!uids.length) { toastr.warning(@json(__('helpdeskemailactivity::emaillog.bulk.none_selected'))); return; }
        const url = $(this).data('url');
        askConfirm({
            title: @json(__('helpdeskemailactivity::emaillog.bulk.resend_title')),
            message: @json(__('helpdeskemailactivity::emaillog.bulk.resend_confirm')).replace(':count', uids.length),
            icon: 'fa-rotate-right',
            accept: @json(__('helpdeskemailactivity::emaillog.confirm.accept_resend')),
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
        if (!uids.length) { toastr.warning(@json(__('helpdeskemailactivity::emaillog.bulk.none_selected'))); return; }

        const $form = $('<form>', { method: 'POST', action: $(this).data('url') });
        $form.append($('<input>', { type: 'hidden', name: '_token', value: csrf }));
        uids.forEach(uid => $form.append($('<input>', { type: 'hidden', name: 'uids[]', value: uid })));
        $form.appendTo('body').trigger('submit').remove();
    });

    $('#bulk-delete').on('click', function () {
        const uids = selectedUids();
        if (!uids.length) { toastr.warning(@json(__('helpdeskemailactivity::emaillog.bulk.none_selected'))); return; }
        const url = $(this).data('url');
        askConfirm({
            title: @json(__('helpdeskemailactivity::emaillog.confirm.delete_title')),
            message: @json(__('helpdeskemailactivity::emaillog.bulk.confirm')).replace(':count', uids.length),
            icon: 'fa-trash-can',
            accept: @json(__('helpdeskemailactivity::emaillog.confirm.accept_delete')),
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
