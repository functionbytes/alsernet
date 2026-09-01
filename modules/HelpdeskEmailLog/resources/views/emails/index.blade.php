@extends('layouts.theme')

@section('title', __('helpdeskemaillog::emaillog.title'))

@section('page_header')
    @include('core::components.card', ['title' => __('helpdeskemaillog::emaillog.title')])
@endsection

@php
    $hasFilters = request()->hasAny(['search', 'module', 'status', 'date_from', 'date_to', 'entity_type', 'entity_id', 'engagement']);

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
    $columnCount = ($canManage ? 7 : 6) + 1; // +1 por la columna Interacción

    $deliveryRate = $stats['total'] > 0 ? round($stats['sent'] / $stats['total'] * 100) : 0;
    // null cuando nunca hubo un envío con seguimiento — "0%" insinuaría un
    // dato real que no existe (mismo criterio que hasOpenTracking()).
    $openRate = $stats['open_tracked'] > 0 ? round($stats['opened'] / $stats['open_tracked'] * 100) : null;
    $clickRate = $stats['click_tracked'] > 0 ? round($stats['clicked'] / $stats['click_tracked'] * 100) : null;
    $today = now()->toDateString();

    // Helper to generate a sort URL for a column key, toggling direction if already active.
    $sortUrl = function (string $key) use ($sortBy, $sortDir): string {
        $nextDir = ($sortBy === $key && $sortDir === 'desc') ? 'asc' : 'desc';
        return request()->fullUrlWithQuery(['sort_by' => $key, 'sort_dir' => $nextDir, 'page' => 1]);
    };

    // Icon class for a sortable header: active column gets an up/down arrow, others get a neutral icon.
    $sortIcon = function (string $key) use ($sortBy, $sortDir): string {
        if ($sortBy !== $key) {
            return 'fas fa-sort evx-sort-idle';
        }
        return ($sortDir === 'asc' ? 'fas fa-sort-up' : 'fas fa-sort-down').' evx-sort-active';
    };

    // aria-sort value for a sortable column header (accessibility).
    $ariaSort = function (string $key) use ($sortBy, $sortDir): string {
        if ($sortBy !== $key) {
            return 'none';
        }
        return $sortDir === 'asc' ? 'ascending' : 'descending';
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

@push('css')
    <link rel="stylesheet" href="{{ asset('modules/helpdeskemaillog/css/emaillog.css') }}">
@endpush

@section('content')

    <div class="emaillog-index">

        @if($staleCount > 0)
            <a href="{{ route('helpdeskemaillog.index', ['status' => 'queued']) }}" class="evx-stale-banner" role="alert">
                <i class="fas fa-triangle-exclamation" aria-hidden="true"></i>
                <span>{{ __('helpdeskemaillog::emaillog.stale.warning', ['count' => $staleCount, 'hours' => $staleHours]) }}</span>
                <span class="evx-stale-cta">{{ __('helpdeskemaillog::emaillog.stale.view') }}</span>
            </a>
        @endif

        {{-- Tarjetas de estadísticas (clicables → filtran) --}}
        <div class="evx-stats">
            <a href="{{ route('helpdeskemaillog.index') }}"
               class="evx-stat {{ ! $hasFilters ? 'is-active' : '' }}">
                <span class="evx-stat-label">{{ __('helpdeskemaillog::emaillog.stats.total') }}</span>
                <span class="evx-stat-value">{{ number_format($stats['total']) }}</span>
                <span class="evx-stat-hint">{{ __('helpdeskemaillog::emaillog.stats.total_hint') }}</span>
            </a>
            <a href="{{ route('helpdeskemaillog.index', ['status' => 'sent']) }}"
               class="evx-stat accent-success {{ $activeStatus === 'sent' ? 'is-active' : '' }}">
                <span class="evx-stat-label">{{ __('helpdeskemaillog::emaillog.stats.sent') }}</span>
                <span class="evx-stat-value">{{ number_format($stats['sent']) }}</span>
                <span class="evx-stat-hint">{{ __('helpdeskemaillog::emaillog.stats.sent_hint') }}</span>
            </a>
            <a href="{{ route('helpdeskemaillog.index', ['status' => 'failed']) }}"
               class="evx-stat accent-danger {{ $activeStatus === 'failed' ? 'is-active' : '' }}">
                <span class="evx-stat-label">{{ __('helpdeskemaillog::emaillog.stats.failed') }}</span>
                <span class="evx-stat-value">{{ number_format($stats['failed']) }}</span>
                <span class="evx-stat-hint">{{ __('helpdeskemaillog::emaillog.stats.failed_hint') }}</span>
            </a>
            <a href="{{ route('helpdeskemaillog.index', ['status' => 'bounced']) }}"
               class="evx-stat accent-danger {{ $activeStatus === 'bounced' ? 'is-active' : '' }}">
                <span class="evx-stat-label">{{ __('helpdeskemaillog::emaillog.stats.bounced') }}</span>
                <span class="evx-stat-value">{{ number_format($stats['bounced']) }}</span>
                <span class="evx-stat-hint">{{ __('helpdeskemaillog::emaillog.stats.bounced_hint') }}</span>
            </a>
            <a href="{{ route('helpdeskemaillog.index', ['status' => 'complained']) }}"
               class="evx-stat accent-danger {{ $activeStatus === 'complained' ? 'is-active' : '' }}">
                <span class="evx-stat-label">{{ __('helpdeskemaillog::emaillog.stats.complained') }}</span>
                <span class="evx-stat-value">{{ number_format($stats['complained']) }}</span>
                <span class="evx-stat-hint">{{ __('helpdeskemaillog::emaillog.stats.complained_hint') }}</span>
            </a>
            <a href="{{ route('helpdeskemaillog.index', ['status' => 'queued']) }}"
               class="evx-stat accent-warning {{ $activeStatus === 'queued' ? 'is-active' : '' }}">
                <span class="evx-stat-label">{{ __('helpdeskemaillog::emaillog.stats.queued') }}</span>
                <span class="evx-stat-value">{{ number_format($stats['queued']) }}</span>
                <span class="evx-stat-hint">{{ __('helpdeskemaillog::emaillog.stats.queued_hint') }}</span>
            </a>
            <a href="{{ route('helpdeskemaillog.index', ['date_from' => $today, 'date_to' => $today]) }}"
               class="evx-stat {{ $isToday ? 'is-active' : '' }}">
                <span class="evx-stat-label">{{ __('helpdeskemaillog::emaillog.stats.today') }}</span>
                <span class="evx-stat-value">{{ number_format($stats['today']) }}</span>
                <span class="evx-stat-hint">{{ __('helpdeskemaillog::emaillog.stats.today_hint') }}</span>
            </a>
            <div class="evx-stat is-static">
                <span class="evx-stat-label">{{ __('helpdeskemaillog::emaillog.stats.delivery_rate') }}</span>
                <span class="evx-stat-value">{{ $deliveryRate }}%</span>
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
                    <span class="evx-stat-value">{{ $openRate }}%</span>
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
                    <span class="evx-stat-value">{{ $clickRate }}%</span>
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

        {{-- Gráfico de tendencia --}}
        <div class="evx-card evx-block">
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

        {{-- Vistas guardadas --}}
        <div class="evx-card evx-block" id="evx-saved-views"
             data-index-url="{{ route('helpdeskemaillog.index') }}"
             data-views-url="{{ route('helpdeskemaillog.views.index') }}"
             data-views-store-url="{{ route('helpdeskemaillog.views.store') }}"
             data-can-manage="{{ $canManage ? '1' : '0' }}">
            <div class="evx-block-body evx-saved-views-body">
                <span class="s evx-saved-views-label">{{ __('helpdeskemaillog::emaillog.views.heading') }}</span>
                <div class="evx-saved-views-list" id="evx-saved-views-list">
                    <span class="text-muted small" id="evx-saved-views-empty">{{ __('helpdeskemaillog::emaillog.views.no_views') }}</span>
                </div>
                <button type="button" class="evx-btn evx-btn-outline evx-btn-inline" id="evx-save-view-btn">
                    <i class="fas fa-bookmark" aria-hidden="true"></i>
                    {{ __('helpdeskemaillog::emaillog.views.save_current') }}
                </button>
            </div>
        </div>

        {{-- Filtros --}}
        <div class="evx-card evx-block">
            <div class="evx-block-head">
                <div>
                    <span class="t">{{ __('helpdeskemaillog::emaillog.filters.heading') }}</span>
                    <span class="s">{{ __('helpdeskemaillog::emaillog.filters.description') }}</span>
                    @if($entityFilterLabel)
                        <span class="evx-tag">
                            {{ __('helpdeskemaillog::emaillog.filters.entity_active', ['entity' => $entityFilterLabel]) }}
                            <a href="{{ route('helpdeskemaillog.index', request()->except(['entity_type', 'entity_id', 'page'])) }}"
                               aria-label="{{ __('helpdeskemaillog::emaillog.filters.clear') }}">
                                <i class="fas fa-xmark" aria-hidden="true"></i>
                            </a>
                        </span>
                    @endif
                </div>
                <div class="d-flex align-items-center gap-2">
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
                    <a href="{{ route('helpdeskemaillog.export', request()->query()) }}" class="evx-btn evx-btn-outline evx-btn-inline">
                        {{ __('helpdeskemaillog::emaillog.actions.export') }}
                    </a>
                </div>
            </div>
            <div class="evx-block-body">
                <form action="{{ route('helpdeskemaillog.index') }}" method="GET">
                    <div class="evx-filters">
                        @if($entityType && $entityId)
                            <input type="hidden" name="entity_type" value="{{ $entityType }}">
                            <input type="hidden" name="entity_id" value="{{ $entityId }}">
                        @endif
                        <div class="evx-search">
                            <i class="fas fa-magnifying-glass" aria-hidden="true"></i>
                            <input type="search" name="search" value="{{ request('search') }}"
                                   aria-label="{{ __('helpdeskemaillog::emaillog.filters.search') }}"
                                   placeholder="{{ __('helpdeskemaillog::emaillog.filters.search_placeholder') }}">
                        </div>

                        <select name="module" class="evx-select">
                            <option value="">{{ __('helpdeskemaillog::emaillog.filters.all_modules') }}</option>
                            @foreach($modules as $mod)
                                <option value="{{ $mod }}" @selected(request('module') === $mod)>{{ $mod }}</option>
                            @endforeach
                        </select>

                        <select name="status" class="evx-select">
                            <option value="">{{ __('helpdeskemaillog::emaillog.filters.all_statuses') }}</option>
                            @foreach($statuses as $value => $label)
                                <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>

                        {{-- "Sin abrir"/"Sin clic" solo tiene sentido sobre envíos
                             CON seguimiento — ver EmailLogController::applyFilters(). --}}
                        <select name="engagement" class="evx-select">
                            <option value="">{{ __('helpdeskemaillog::emaillog.filters.all_engagement') }}</option>
                            <option value="opened" @selected($activeEngagement === 'opened')>{{ __('helpdeskemaillog::emaillog.filters.engagement_opened') }}</option>
                            <option value="not_opened" @selected($activeEngagement === 'not_opened')>{{ __('helpdeskemaillog::emaillog.filters.engagement_not_opened') }}</option>
                            <option value="clicked" @selected($activeEngagement === 'clicked')>{{ __('helpdeskemaillog::emaillog.filters.engagement_clicked') }}</option>
                            <option value="not_clicked" @selected($activeEngagement === 'not_clicked')>{{ __('helpdeskemaillog::emaillog.filters.engagement_not_clicked') }}</option>
                        </select>

                        <input type="text" class="evx-input daterange" autocomplete="off"
                               placeholder="{{ __('helpdeskemaillog::emaillog.filters.date_range') }}"
                               value="{{ ($dateFrom && $dateTo) ? $dateFrom . ' - ' . $dateTo : '' }}">
                        <input type="hidden" name="date_from" id="date_from" value="{{ $dateFrom }}">
                        <input type="hidden" name="date_to" id="date_to" value="{{ $dateTo }}">
                        @if(request('sort_by'))
                            <input type="hidden" name="sort_by" value="{{ request('sort_by') }}">
                            <input type="hidden" name="sort_dir" value="{{ request('sort_dir', 'desc') }}">
                        @endif

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
                        @if($hasFilters)
                            <a href="{{ route('helpdeskemaillog.index') }}" class="evx-btn evx-btn-outline evx-btn-inline"
                               aria-label="{{ __('helpdeskemaillog::emaillog.filters.clear') }}"
                               title="{{ __('helpdeskemaillog::emaillog.filters.clear') }}">
                                <i class="fas fa-xmark" aria-hidden="true"></i>
                            </a>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        {{-- Tabla --}}
        @php
            // Datos de la página ACTUAL para los modos de vista Hilos/Kanban
            // (evx-mode-switch) — se aplican solo sobre lo ya cargado, sin
            // re-pedir nada por AJAX al cambiar de modo. Mismos campos que
            // consume modules/HelpdeskEmailLog/resources/js (ver @push('scripts')
            // más abajo).
            $emailRowsForViewModes = $logs->map(function ($log) {
                return [
                    'uid' => $log->uid,
                    'subject' => $log->subject,
                    'to_addresses' => $log->to_addresses ?? [],
                    'module' => $log->module,
                    'status' => $log->status?->value,
                    'status_label' => $log->status_label,
                    'entity_type' => $log->entity_type,
                    'entity_id' => $log->entity_id,
                    'entity_label' => $log->entity_label,
                    'created_at' => optional($log->created_at)->toIso8601String(),
                    'date_human' => $log->display_date->format('d/m/Y H:i'),
                    'url_show' => route('helpdeskemaillog.show', $log->uid),
                    'has_attachments' => $log->has_attachments,
                    'has_open_tracking' => $log->hasOpenTracking(),
                    'has_click_tracking' => $log->hasClickTracking(),
                    'opens_count' => (int) ($log->opens_count ?? 0),
                    'clicks_count' => (int) ($log->clicks_count ?? 0),
                ];
            })->values();
        @endphp
        <div class="evx-card evx-block">
            <div class="evx-table-wrap" id="evx-list-view">
                <table class="evx-table">
                    <thead>
                        <tr>
                            @if($canManage)
                                <th class="evx-th-check">
                                    <input type="checkbox" id="select-all"
                                           aria-label="{{ __('helpdeskemaillog::emaillog.table.select_all') }}"
                                           title="{{ __('helpdeskemaillog::emaillog.table.select_all') }}">
                                </th>
                            @endif
                            <th aria-sort="{{ $ariaSort('subject') }}">
                                <a href="{{ $sortUrl('subject') }}" class="evx-sort">
                                    {{ __('helpdeskemaillog::emaillog.table.subject') }}
                                    <i class="{{ $sortIcon('subject') }}" aria-hidden="true"></i>
                                </a>
                            </th>
                            <th>{{ __('helpdeskemaillog::emaillog.table.recipient') }}</th>
                            <th aria-sort="{{ $ariaSort('module') }}">
                                <a href="{{ $sortUrl('module') }}" class="evx-sort">
                                    {{ __('helpdeskemaillog::emaillog.table.module') }}
                                    <i class="{{ $sortIcon('module') }}" aria-hidden="true"></i>
                                </a>
                            </th>
                            <th aria-sort="{{ $ariaSort('status') }}">
                                <a href="{{ $sortUrl('status') }}" class="evx-sort">
                                    {{ __('helpdeskemaillog::emaillog.table.status') }}
                                    <i class="{{ $sortIcon('status') }}" aria-hidden="true"></i>
                                </a>
                            </th>
                            <th aria-sort="{{ $ariaSort('date') }}">
                                <a href="{{ $sortUrl('date') }}" class="evx-sort">
                                    {{ __('helpdeskemaillog::emaillog.table.date') }}
                                    <i class="{{ $sortIcon('date') }}" aria-hidden="true"></i>
                                </a>
                            </th>
                            {{-- No es ordenable: es un agregado (subquery de opens/clicks
                                 vía withCount), no una columna propia de email_logs. --}}
                            <th>{{ __('helpdeskemaillog::emaillog.table.engagement') }}</th>
                            <th class="evx-th-actions">{{ __('helpdeskemaillog::emaillog.table.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                            @php $statusVal = $log->status?->value; @endphp
                            <tr>
                                @if($canManage)
                                    <td>
                                        <input type="checkbox" class="bulk-checkbox" value="{{ $log->uid }}"
                                               aria-label="{{ $log->subject ?: __('helpdeskemaillog::emaillog.table.subject') }}">
                                    </td>
                                @endif
                                <td>
                                    <a href="{{ route('helpdeskemaillog.show', $log->uid) }}" class="evx-subject-link">
                                        {{ Str::limit($log->subject, 60) ?: '—' }}
                                    </a>
                                    @if($log->has_attachments)
                                        <i class="fas fa-paperclip evx-clip" role="img"
                                           aria-label="{{ __('helpdeskemaillog::emaillog.table.has_attachments') }}"
                                           title="{{ __('helpdeskemaillog::emaillog.table.has_attachments') }}"></i>
                                    @endif
                                    @if($log->mailable_class)
                                        <div class="evx-subject-sub">{{ class_basename($log->mailable_class) }}</div>
                                    @endif
                                </td>
                                <td>
                                    @foreach($log->to_addresses ?? [] as $addr)
                                        <div class="evx-recipient">{{ $addr }}</div>
                                    @endforeach
                                </td>
                                <td>
                                    @if($log->module)
                                        <span class="evx-tag">{{ $log->module }}</span>
                                    @else
                                        <span class="evx-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="evx-status {{ $statusVal }}"
                                          @if($log->error_message) title="{{ Str::limit($log->error_message, 120) }}" @endif>
                                        <i class="fa-solid {{ ['sent' => 'fa-check', 'failed' => 'fa-xmark', 'queued' => 'fa-clock', 'bounced' => 'fa-triangle-exclamation', 'complained' => 'fa-flag', 'suppressed' => 'fa-ban'][$statusVal] ?? 'fa-circle' }}" aria-hidden="true"></i>{{ $log->status_label }}
                                    </span>
                                </td>
                                <td class="evx-date" title="{{ $log->display_date->diffForHumans() }}">
                                    {{ $log->display_date->format('d/m/Y H:i') }}
                                </td>
                                <td class="evx-td-engagement">
                                    {{-- "0" solo es un dato real cuando el flag de tracking es
                                         true (ver EmailLog::hasOpenTracking()/hasClickTracking());
                                         para el resto de correos nunca hubo píxel ni enlaces
                                         reescritos, así que se muestra un guion neutro en vez de
                                         un "0" que insinuaría un dato que no existe. --}}
                                    @if(!$log->hasOpenTracking() && !$log->hasClickTracking())
                                        <span class="evx-muted" title="{{ __('helpdeskemaillog::emaillog.table.not_tracked') }}">—</span>
                                    @else
                                        @if($log->hasOpenTracking())
                                            <span class="evx-engagement-badge {{ $log->opens_count > 0 ? 'is-active' : '' }}"
                                                  title="{{ trans_choice('helpdeskemaillog::emaillog.table.opens_count', $log->opens_count, ['count' => $log->opens_count]) }}">
                                                <i class="fa-solid fa-eye" aria-hidden="true"></i>{{ $log->opens_count }}
                                            </span>
                                        @endif
                                        @if($log->hasClickTracking())
                                            <span class="evx-engagement-badge {{ $log->clicks_count > 0 ? 'is-active' : '' }}"
                                                  title="{{ trans_choice('helpdeskemaillog::emaillog.table.clicks_count', $log->clicks_count, ['count' => $log->clicks_count]) }}">
                                                <i class="fa-solid fa-arrow-pointer" aria-hidden="true"></i>{{ $log->clicks_count }}
                                            </span>
                                        @endif
                                    @endif
                                </td>
                                <td class="evx-td-actions">
                                    <div class="dropdown">
                                        <a href="#" class="evx-actions-toggle" data-bs-toggle="dropdown"
                                           role="button" aria-expanded="false"
                                           aria-label="{{ __('helpdeskemaillog::emaillog.table.actions') }}">
                                            <i class="fas fa-ellipsis-vertical" aria-hidden="true"></i>
                                        </a>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <a class="dropdown-item" href="{{ route('helpdeskemaillog.show', $log->uid) }}">
                                                    {{ __('helpdeskemaillog::emaillog.actions.view') }}
                                                </a>
                                            </li>
                                            @can('helpdeskemaillog.manage')
                                                <li>
                                                    <button type="button" class="dropdown-item js-resend" data-url="{{ route('helpdeskemaillog.resend', $log->uid) }}">
                                                        {{ __('helpdeskemaillog::emaillog.actions.resend') }}
                                                    </button>
                                                </li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <button type="button" class="dropdown-item js-delete"
                                                            data-url="{{ route('helpdeskemaillog.destroy', $log->uid) }}">
                                                        {{ __('helpdeskemaillog::emaillog.actions.delete') }}
                                                    </button>
                                                </li>
                                            @endcan
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                {{-- $columnCount ya incluye la columna Interacción — ver @php arriba. --}}
                                <td colspan="{{ $columnCount }}" class="evx-empty-row">
                                    <i class="fas fa-inbox" aria-hidden="true"></i>
                                    <p>{{ __('helpdeskemaillog::emaillog.table.empty') }}</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
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

            @if($logs->hasPages())
                <div class="evx-pagination">
                    <span class="evx-muted">
                        {{ __('helpdeskemaillog::emaillog.pagination.showing', ['first' => $logs->firstItem(), 'last' => $logs->lastItem(), 'total' => $logs->total()]) }}
                    </span>
                    {{ $logs->links() }}
                </div>
            @endif
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
            <button type="button" class="evx-btn evx-btn-danger evx-btn-inline" id="bulk-delete"
                    data-url="{{ route('helpdeskemaillog.bulk-destroy') }}">
                {{ __('helpdeskemaillog::emaillog.actions.bulk_delete') }}
            </button>
        </div>
    @endcan

    {{-- Modal de confirmación reutilizable --}}
    <div class="modal fade" id="emaillog-confirm-modal" tabindex="-1"
         aria-labelledby="emaillog-confirm-title" aria-describedby="emaillog-confirm-message" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="emaillog-confirm-title">{{ __('helpdeskemaillog::emaillog.confirm.title') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0" id="emaillog-confirm-message">—</p>
                </div>
                <div class="modal-footer flex-column">
                    <button type="button" class="btn btn-primary w-100 mb-2" id="emaillog-confirm-accept">
                        {{ __('helpdeskemaillog::emaillog.confirm.accept') }}
                    </button>
                    <button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">
                        {{ __('helpdeskemaillog::emaillog.confirm.cancel') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="{{ asset('core/js/bulk.js') }}"></script>
<script>
$(function () {
    @if(session('success')) toastr.success(@json(session('success'))); @endif
    @if(session('error')) toastr.error(@json(session('error'))); @endif

    const csrf = $('meta[name="csrf-token"]').attr('content');
    const $confirmModal = $('#emaillog-confirm-modal');
    const confirmModal = new bootstrap.Modal($confirmModal[0]);
    let pendingAccept = null;

    function askConfirm({ title, message, onAccept }) {
        $('#emaillog-confirm-title').text(title);
        $('#emaillog-confirm-message').text(message);
        pendingAccept = onAccept;
        confirmModal.show();
    }

    $('#emaillog-confirm-accept').on('click', function () {
        const fn = pendingAccept;
        pendingAccept = null;
        confirmModal.hide();
        if (typeof fn === 'function') fn();
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
            $list.find('.evx-view-chip').remove();
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

        // Mismo badge de Apertura/Clic que la columna "Interacción" de la
        // tabla (evx-td-engagement) pero en línea, para Hilos/Kanban — reusa
        // los flags/conteos ya calculados en $emailRowsForViewModes arriba,
        // sin ninguna petición nueva.
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
                plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } },
                scales: {
                    x: { stacked: true, grid: { display: false }, ticks: { font: { size: 10 } } },
                    y: { stacked: true, beginAtZero: true, ticks: { precision: 0, font: { size: 10 } } },
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

    // Reenviar individual
    $(document).on('click', '.js-resend', function () {
        const url = $(this).data('url');
        askConfirm({
            title: @json(__('helpdeskemaillog::emaillog.resend.confirm_title')),
            message: @json(__('helpdeskemaillog::emaillog.resend.confirm')),
            onAccept: () => {
                $.ajax({ url, method: 'POST', headers: { 'X-CSRF-TOKEN': csrf } })
                    .done(() => location.reload())
                    .fail(xhr => toastr.error(xhr.responseJSON?.message || 'Error'));
            },
        });
    });

    // Eliminar individual
    $(document).on('click', '.js-delete', function () {
        const url = $(this).data('url');
        askConfirm({
            title: @json(__('helpdeskemaillog::emaillog.confirm.delete_title')),
            message: @json(__('helpdeskemaillog::emaillog.confirm.delete_one')),
            onAccept: () => {
                $.ajax({ url, method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrf } })
                    .done(() => location.reload())
                    .fail(xhr => toastr.error(xhr.responseJSON?.message || 'Error'));
            },
        });
    });

    @can('helpdeskemaillog.manage')
    // Acciones masivas
    window.BulkActions.init({ checkbox: '.bulk-checkbox', selectAll: '#select-all', toolbar: '#bulk-toolbar' });

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
            onAccept: () => {
                $.ajax({ url, method: 'POST', data: { uids }, headers: { 'X-CSRF-TOKEN': csrf } })
                    .done(() => location.reload())
                    .fail(xhr => toastr.error(xhr.responseJSON?.message || 'Error'));
            },
        });
    });

    $('#bulk-delete').on('click', function () {
        const uids = selectedUids();
        if (!uids.length) { toastr.warning(@json(__('helpdeskemaillog::emaillog.bulk.none_selected'))); return; }
        const url = $(this).data('url');
        askConfirm({
            title: @json(__('helpdeskemaillog::emaillog.confirm.delete_title')),
            message: @json(__('helpdeskemaillog::emaillog.bulk.confirm')).replace(':count', uids.length),
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
