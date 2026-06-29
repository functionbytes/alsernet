@extends('layouts.theme')

@section('title', __('helpdeskemaillog::emaillog.title'))

@section('page_header')
    @include('core::components.card', ['title' => __('helpdeskemaillog::emaillog.title')])
@endsection

@php
    $hasFilters = request()->hasAny(['search', 'module', 'status', 'date_from', 'date_to']);
    $canManage = auth()->user()?->can('helpdeskemaillog.manage') ?? false;
    $columnCount = $canManage ? 7 : 6;

    // Helper to generate a sort URL for a column key, toggling direction if already active.
    $sortUrl = function (string $key) use ($sortBy, $sortDir): string {
        $nextDir = ($sortBy === $key && $sortDir === 'desc') ? 'asc' : 'desc';
        return request()->fullUrlWithQuery(['sort_by' => $key, 'sort_dir' => $nextDir, 'page' => 1]);
    };

    // Icon class for a sortable header: active column gets an up/down arrow, others get a neutral icon.
    $sortIcon = function (string $key) use ($sortBy, $sortDir): string {
        if ($sortBy !== $key) {
            return 'fas fa-sort text-muted ms-1 small';
        }
        return ($sortDir === 'asc' ? 'fas fa-sort-up' : 'fas fa-sort-down').' ms-1 small';
    };
@endphp

@push('styles')
    <link rel="stylesheet" href="{{ asset('modules/helpdeskemaillog/css/emaillog.css') }}">
@endpush

@section('content')

    {{-- Stats --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="card-title mb-2">{{ __('helpdeskemaillog::emaillog.stats.total') }}</h6>
                    <h2 class="mb-0">{{ number_format($stats['total']) }}</h2>
                    <small class="text-muted">{{ __('helpdeskemaillog::emaillog.stats.total_hint') }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="card-title mb-2 text-success">{{ __('helpdeskemaillog::emaillog.stats.sent') }}</h6>
                    <h2 class="mb-0">{{ number_format($stats['sent']) }}</h2>
                    <small class="text-muted">{{ __('helpdeskemaillog::emaillog.stats.sent_hint') }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="card-title mb-2 text-danger">{{ __('helpdeskemaillog::emaillog.stats.failed') }}</h6>
                    <h2 class="mb-0">{{ number_format($stats['failed']) }}</h2>
                    <small class="text-muted">{{ __('helpdeskemaillog::emaillog.stats.failed_hint') }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="card-title mb-2">{{ __('helpdeskemaillog::emaillog.stats.today') }}</h6>
                    <h2 class="mb-0">{{ number_format($stats['today']) }}</h2>
                    <small class="text-muted">{{ __('helpdeskemaillog::emaillog.stats.today_hint') }}</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Filtros --}}
    <div class="card">
        <div class="card-header p-4 border-bottom border-light d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h6 class="mb-1 fw-bold">{{ __('helpdeskemaillog::emaillog.filters.heading') }}</h6>
                <p class="small mb-0 text-muted">{{ __('helpdeskemaillog::emaillog.filters.description') }}</p>
            </div>
            <a href="{{ route('helpdeskemaillog.export', request()->query()) }}" class="btn btn-outline-secondary">
                <i class="fas fa-file-csv me-1"></i> {{ __('helpdeskemaillog::emaillog.actions.export') }}
            </a>
        </div>
        <div class="card-body">
            <form action="{{ route('helpdeskemaillog.index') }}" method="GET">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <input type="search" name="search" class="form-control flex-grow-1 email-log-filter-search"
                           value="{{ request('search') }}"
                           placeholder="{{ __('helpdeskemaillog::emaillog.filters.search_placeholder') }}">

                    <select name="module" class="form-select email-log-filter-module">
                        <option value="">{{ __('helpdeskemaillog::emaillog.filters.all_modules') }}</option>
                        @foreach($modules as $mod)
                            <option value="{{ $mod }}" @selected(request('module') === $mod)>{{ $mod }}</option>
                        @endforeach
                    </select>

                    <select name="status" class="form-select email-log-filter-status">
                        <option value="">{{ __('helpdeskemaillog::emaillog.filters.all_statuses') }}</option>
                        @foreach($statuses as $value => $label)
                            <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>

                    <input type="text" class="form-control daterange email-log-filter-daterange" autocomplete="off"
                           placeholder="{{ __('helpdeskemaillog::emaillog.filters.date_range') }}"
                           value="{{ (request('date_from') && request('date_to')) ? request('date_from') . ' - ' . request('date_to') : '' }}">
                    <input type="hidden" name="date_from" id="date_from" value="{{ request('date_from') }}">
                    <input type="hidden" name="date_to" id="date_to" value="{{ request('date_to') }}">
                    @if(request('sort_by'))
                        <input type="hidden" name="sort_by" value="{{ request('sort_by') }}">
                        <input type="hidden" name="sort_dir" value="{{ request('sort_dir', 'desc') }}">
                    @endif

                    <select name="per_page" id="per-page" class="form-select email-log-filter-perpage"
                            title="{{ __('helpdeskemaillog::emaillog.filters.per_page') }}">
                        @foreach($perPageOptions as $opt)
                            <option value="{{ $opt }}" @selected((int) $perPage === (int) $opt)>{{ __('helpdeskemaillog::emaillog.filters.per_page_option', ['n' => $opt]) }}</option>
                        @endforeach
                    </select>

                    <div class="d-flex gap-1 flex-shrink-0">
                        <button type="submit" class="btn btn-primary" title="{{ __('helpdeskemaillog::emaillog.filters.search') }}">
                            <i class="fas fa-magnifying-glass"></i>
                        </button>
                        @if($hasFilters)
                            <a href="{{ route('helpdeskemaillog.index') }}" class="btn btn-outline-secondary" title="{{ __('helpdeskemaillog::emaillog.filters.clear') }}">
                                <i class="fas fa-xmark"></i>
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Tabla --}}
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle text-nowrap">
                    <thead class="table-light">
                        <tr>
                            @can('helpdeskemaillog.manage')
                                <th width="3%">
                                    <input type="checkbox" id="select-all" class="form-check-input" title="{{ __('helpdeskemaillog::emaillog.table.select_all') }}">
                                </th>
                            @endcan
                            <th>
                                <a href="{{ $sortUrl('subject') }}" class="text-body text-decoration-none">
                                    {{ __('helpdeskemaillog::emaillog.table.subject') }}
                                    <i class="{{ $sortIcon('subject') }}"></i>
                                </a>
                            </th>
                            <th>{{ __('helpdeskemaillog::emaillog.table.recipient') }}</th>
                            <th>
                                <a href="{{ $sortUrl('module') }}" class="text-body text-decoration-none">
                                    {{ __('helpdeskemaillog::emaillog.table.module') }}
                                    <i class="{{ $sortIcon('module') }}"></i>
                                </a>
                            </th>
                            <th>
                                <a href="{{ $sortUrl('status') }}" class="text-body text-decoration-none">
                                    {{ __('helpdeskemaillog::emaillog.table.status') }}
                                    <i class="{{ $sortIcon('status') }}"></i>
                                </a>
                            </th>
                            <th>
                                <a href="{{ $sortUrl('date') }}" class="text-body text-decoration-none">
                                    {{ __('helpdeskemaillog::emaillog.table.date') }}
                                    <i class="{{ $sortIcon('date') }}"></i>
                                </a>
                            </th>
                            <th class="text-center">{{ __('helpdeskemaillog::emaillog.table.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                            <tr>
                                @can('helpdeskemaillog.manage')
                                    <td>
                                        <input type="checkbox" class="form-check-input bulk-checkbox" value="{{ $log->uid }}">
                                    </td>
                                @endcan
                                <td>
                                    <a href="{{ route('helpdeskemaillog.show', $log->uid) }}" class="fw-semibold text-body">
                                        {{ Str::limit($log->subject, 60) ?: '—' }}
                                    </a>
                                    @if($log->mailable_class)
                                        <br><small class="text-muted">{{ class_basename($log->mailable_class) }}</small>
                                    @endif
                                </td>
                                <td>
                                    @foreach($log->to_addresses ?? [] as $addr)
                                        <div class="small">{{ $addr }}</div>
                                    @endforeach
                                </td>
                                <td>
                                    @if($log->module)
                                        <span class="badge bg-info-subtle text-info">{{ $log->module }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-{{ $log->status_color }}-subtle text-{{ $log->status_color }}"
                                          @if($log->error_message) title="{{ Str::limit($log->error_message, 120) }}" @endif>
                                        {{ $log->status_label }}
                                    </span>
                                </td>
                                <td title="{{ $log->display_date->diffForHumans() }}">
                                    {{ $log->display_date->format('d/m/Y H:i') }}
                                </td>
                                <td class="text-center">
                                    <div class="dropdown">
                                        <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="fas fa-ellipsis-vertical"></i>
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
                                <td colspan="{{ $columnCount }}" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="fas fa-inbox fa-3x mb-3"></i>
                                        <p class="mb-0">{{ __('helpdeskemaillog::emaillog.table.empty') }}</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if($logs->hasPages())
        <div class="card card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <small class="text-muted">
                    {{ __('helpdeskemaillog::emaillog.pagination.showing', ['first' => $logs->firstItem(), 'last' => $logs->lastItem(), 'total' => $logs->total()]) }}
                </small>
                {{ $logs->links() }}
            </div>
        </div>
    @endif

    @can('helpdeskemaillog.manage')
        {{-- Toolbar flotante de acciones masivas --}}
        <div id="bulk-toolbar" class="position-fixed bottom-0 start-50 translate-middle-x mb-4 d-none email-log-bulk-toolbar">
            <div class="card shadow-lg">
                <div class="card-body py-2 px-3 d-flex align-items-center gap-3">
                    <span class="fw-semibold"><span data-bulk-count>0</span> {{ __('helpdeskemaillog::emaillog.bulk.label') }}</span>
                    <button type="button" class="btn btn-sm btn-danger" id="bulk-delete"
                            data-url="{{ route('helpdeskemaillog.bulk-destroy') }}">
                        <i class="fas fa-trash me-1"></i>{{ __('helpdeskemaillog::emaillog.actions.bulk_delete') }}
                    </button>
                </div>
            </div>
        </div>
    @endcan

    {{-- Modal de confirmación reutilizable --}}
    <div class="modal fade" id="emaillog-confirm-modal" tabindex="-1" aria-hidden="true">
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

    // Reenviar
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
    const bulk = window.BulkActions.init({ checkbox: '.bulk-checkbox', selectAll: '#select-all', toolbar: '#bulk-toolbar' });

    $('#bulk-delete').on('click', function () {
        const uids = $('.bulk-checkbox:checked').map(function () { return this.value; }).get();
        if (!uids.length) { toastr.warning(@json(__('helpdeskemaillog::emaillog.bulk.none_selected'))); return; }
        const url = $(this).data('url');
        askConfirm({
            title: @json(__('helpdeskemaillog::emaillog.confirm.delete_title')),
            message: @json(__('helpdeskemaillog::emaillog.bulk.confirm')).replace(':count', uids.length),
            onAccept: () => {
                $.ajax({ url, method: 'DELETE', data: { uids: uids }, headers: { 'X-CSRF-TOKEN': csrf } })
                    .done(() => location.reload())
                    .fail(xhr => toastr.error(xhr.responseJSON?.message || 'Error'));
            },
        });
    });
    @endcan
});
</script>
@endpush
