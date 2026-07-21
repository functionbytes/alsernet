@extends('layouts.theme')

@section('title', __('helpdeskemaillog::emaillog.title'))

@section('page_header')
    @include('core::components.card', ['title' => __('helpdeskemaillog::emaillog.title')])
@endsection

@php
    $hasFilters = request()->hasAny(['search', 'module', 'status', 'date_from', 'date_to']);
    $canManage = auth()->user()?->can('helpdeskemaillog.manage') ?? false;
    $columnCount = $canManage ? 7 : 6;

    $deliveryRate = $stats['total'] > 0 ? round($stats['sent'] / $stats['total'] * 100) : 0;
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
                @endphp
                <canvas id="emaillog-trend" height="80" role="img"
                        aria-label="{{ __('helpdeskemaillog::emaillog.trend.title') }}: {{ $trendSent }} {{ __('helpdeskemaillog::emaillog.trend.sent') }}, {{ $trendFailed }} {{ __('helpdeskemaillog::emaillog.trend.failed') }}, {{ $trendQueued }} {{ __('helpdeskemaillog::emaillog.trend.queued') }}."></canvas>
            </div>
        </div>

        {{-- Filtros --}}
        <div class="evx-card evx-block">
            <div class="evx-block-head">
                <div>
                    <span class="t">{{ __('helpdeskemaillog::emaillog.filters.heading') }}</span>
                    <span class="s">{{ __('helpdeskemaillog::emaillog.filters.description') }}</span>
                </div>
                <a href="{{ route('helpdeskemaillog.export', request()->query()) }}" class="evx-btn evx-btn-outline evx-btn-inline">
                    {{ __('helpdeskemaillog::emaillog.actions.export') }}
                </a>
            </div>
            <div class="evx-block-body">
                <form action="{{ route('helpdeskemaillog.index') }}" method="GET">
                    <div class="evx-filters">
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
        <div class="evx-card evx-block">
            <div class="evx-table-wrap">
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
                                        <i class="fa-solid {{ ['sent' => 'fa-check', 'failed' => 'fa-xmark', 'queued' => 'fa-clock'][$statusVal] ?? 'fa-circle' }}" aria-hidden="true"></i>{{ $log->status_label }}
                                    </span>
                                </td>
                                <td class="evx-date" title="{{ $log->display_date->diffForHumans() }}">
                                    {{ $log->display_date->format('d/m/Y H:i') }}
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
                                <td colspan="{{ $columnCount }}" class="evx-empty-row">
                                    <i class="fas fa-inbox" aria-hidden="true"></i>
                                    <p>{{ __('helpdeskemaillog::emaillog.table.empty') }}</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

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

    // Gráfico de tendencia
    const trendEl = document.getElementById('emaillog-trend');
    if (trendEl && window.Chart) {
        const trend = @json($trend);
        new Chart(trendEl, {
            type: 'bar',
            data: {
                labels: trend.labels,
                datasets: [
                    { label: @json(__('helpdeskemaillog::emaillog.trend.sent')), data: trend.sent, backgroundColor: '#90bb13', stack: 's', borderRadius: 3 },
                    { label: @json(__('helpdeskemaillog::emaillog.trend.failed')), data: trend.failed, backgroundColor: '#dc2626', stack: 's', borderRadius: 3 },
                    { label: @json(__('helpdeskemaillog::emaillog.trend.queued')), data: trend.queued, backgroundColor: '#d97706', stack: 's', borderRadius: 3 },
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
