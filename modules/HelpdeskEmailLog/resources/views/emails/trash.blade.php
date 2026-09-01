@extends('layouts.theme')

@section('title', __('helpdeskemaillog::emaillog.trash.title'))

@section('page_header')
    @include('core::components.card', [
        'title' => __('helpdeskemaillog::emaillog.trash.title'),
        'subtitle' => __('helpdeskemaillog::emaillog.trash.subtitle', ['days' => $retentionDays]),
        'actions' => '<a href="'.route('helpdeskemaillog.index').'" class="evx-header-btn"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i>'.__('helpdeskemaillog::emaillog.trash.back_to_list').'</a>',
    ])
@endsection

{{-- Mismo CSS que emails/index.blade.php (.evx-*, ver emaillog.css/
     emaillog-extras.css) — esta pantalla es una vista más del mismo módulo,
     nunca se toca esa hoja de estilos aquí (ver nota de HelpdeskEmailLogServiceProvider
     sobre ?v=filemtime). --}}
@php
    $emaillogCss = public_path('modules/helpdeskemaillog/css/emaillog.css');
    $emaillogExtrasCss = public_path('modules/helpdeskemaillog/css/emaillog-extras.css');
    $emaillogCssV = is_file($emaillogCss) ? filemtime($emaillogCss) : time();
    $emaillogExtrasCssV = is_file($emaillogExtrasCss) ? filemtime($emaillogExtrasCss) : time();

    // Mismo mapa de iconos que emails/index.blade.php (ver EmailLogController::
    // STATUS_ICONS) — duplicado a propósito, ya era así en la vista principal.
    $statusIcons = ['sent' => 'fa-check', 'failed' => 'fa-xmark', 'queued' => 'fa-clock', 'bounced' => 'fa-triangle-exclamation', 'complained' => 'fa-flag', 'suppressed' => 'fa-ban'];
@endphp

@push('css')
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="{{ asset('modules/helpdeskemaillog/css/emaillog.css') }}?v={{ $emaillogCssV }}">
    <link rel="stylesheet" href="{{ asset('modules/helpdeskemaillog/css/emaillog-extras.css') }}?v={{ $emaillogExtrasCssV }}">
@endpush

@section('content')
    <div class="emaillog-index">
        <div class="evx-shell">
            <div class="evx-toolbar">
                <nav class="evx-crumbs" aria-label="breadcrumb">
                    <i class="fa-solid fa-headset" aria-hidden="true"></i>
                    <span>{{ __('helpdeskemaillog::emaillog.crumbs.panel') }}</span>
                    <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                    <span>{{ __('helpdeskemaillog::emaillog.title') }}</span>
                    <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                    <span class="is-current">{{ __('helpdeskemaillog::emaillog.trash.title') }}</span>
                </nav>

                <form action="{{ route('helpdeskemaillog.trash.index') }}" method="GET" class="evx-toolbar-search">
                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                    <input type="search" name="search" value="{{ request('search') }}"
                           aria-label="{{ __('helpdeskemaillog::emaillog.filters.search') }}"
                           placeholder="{{ __('helpdeskemaillog::emaillog.trash.search_placeholder') }}">
                </form>
            </div>

            <div class="evx-filterbar">
                <span class="evx-muted">{{ __('helpdeskemaillog::emaillog.trash.retention_note', ['days' => $retentionDays]) }}</span>
                <div class="evx-filterbar-end">
                    <span class="evx-filter-count">
                        {{ trans_choice('helpdeskemaillog::emaillog.filters.results_count', $logs->total(), ['count' => number_format($logs->total())]) }}
                    </span>
                </div>
            </div>

            <div id="evx-list-view">
                <div class="evx-list-head">
                    <input type="checkbox" id="trash-select-all" class="evx-list-check"
                           aria-label="{{ __('helpdeskemaillog::emaillog.table.select_all') }}">
                    <label for="trash-select-all" class="evx-list-head-label">{{ __('helpdeskemaillog::emaillog.table.select_all') }}</label>
                </div>

                @forelse($logs as $row)
                    @php
                        $statusVal = $row->status?->value;
                        $toAddresses = $row->to_addresses ?? [];
                        $recipientLine = $toAddresses[0] ?? '—';
                        if (count($toAddresses) > 1) {
                            $recipientLine .= ' +'.(count($toAddresses) - 1);
                        }
                        $expiresAt = $row->deleted_at?->copy()->addDays($retentionDays);
                    @endphp
                    <div class="evx-row">
                        <input type="checkbox" class="trash-bulk-checkbox evx-row-check" value="{{ $row->uid }}"
                               aria-label="{{ $row->subject ?: __('helpdeskemaillog::emaillog.table.subject') }}">
                        <span class="evx-row-dot {{ $statusVal }}" aria-hidden="true"></span>
                        <div class="evx-row-body">
                            <div class="evx-row-top">
                                <span class="evx-row-subject">{{ Str::limit($row->subject, 60) ?: '—' }}</span>
                            </div>
                            <div class="evx-recipient">
                                {{ __('helpdeskemaillog::emaillog.preview.field.to') }}: {{ $recipientLine }}
                            </div>
                            <div class="evx-row-meta">
                                <span class="evx-status {{ $statusVal }}">
                                    <i class="fa-solid {{ $statusIcons[$statusVal] ?? 'fa-circle' }}" aria-hidden="true"></i>{{ $row->status_label }}
                                </span>
                                @if($row->module)
                                    <span class="evx-tag mono">{{ $row->module }}</span>
                                @endif
                                <span class="evx-row-date" title="{{ __('helpdeskemaillog::emaillog.trash.expires_at', ['date' => $expiresAt?->format('d/m/Y')]) }}">
                                    {{ __('helpdeskemaillog::emaillog.trash.deleted_at') }}: {{ $row->deleted_at?->format('d/m/Y H:i') }}
                                </span>
                            </div>
                        </div>
                        <div class="evx-ph-actions">
                            <button type="button" class="evx-icon-btn js-trash-restore"
                                    data-url="{{ route('helpdeskemaillog.trash.restore', $row->uid) }}"
                                    aria-label="{{ __('helpdeskemaillog::emaillog.trash.restore') }}"
                                    title="{{ __('helpdeskemaillog::emaillog.trash.restore') }}">
                                <i class="fa-solid fa-trash-arrow-up" aria-hidden="true"></i>
                            </button>
                            <button type="button" class="evx-icon-btn is-danger js-trash-force-delete"
                                    data-url="{{ route('helpdeskemaillog.trash.force-destroy', $row->uid) }}"
                                    aria-label="{{ __('helpdeskemaillog::emaillog.trash.force_delete') }}"
                                    title="{{ __('helpdeskemaillog::emaillog.trash.force_delete') }}">
                                <i class="fa-solid fa-trash-can" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="evx-empty-row">
                        <i class="fas fa-trash" aria-hidden="true"></i>
                        <p>{{ __('helpdeskemaillog::emaillog.trash.empty') }}</p>
                    </div>
                @endforelse
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

    {{-- Toolbar flotante de restauración masiva — mismo patrón que #bulk-toolbar
         de emails/index.blade.php (BulkActions.init de core/js/bulk.js), sin
         exportar/reenviar/eliminar: la papelera solo restaura o borra
         definitivamente uno a uno (ver botones .js-trash-force-delete). --}}
    <div id="trash-bulk-toolbar" class="evx-bulk-toolbar d-none">
        <span class="evx-bulk-count"><span data-bulk-count>0</span> {{ __('helpdeskemaillog::emaillog.bulk.label') }}</span>
        <button type="button" class="evx-btn evx-btn-primary evx-btn-inline" id="trash-bulk-restore"
                data-url="{{ route('helpdeskemaillog.trash.bulk-restore') }}">
            {{ __('helpdeskemaillog::emaillog.trash.bulk_restore') }}
        </button>
    </div>

    {{-- Modal de confirmación (mismo patrón que emaillog-confirm-modal de
         emails/index.blade.php: modal-dialog-centered + footer apilado). --}}
    <div class="modal fade" id="trash-confirm-modal" tabindex="-1"
         aria-labelledby="trash-confirm-title" aria-describedby="trash-confirm-message" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="trash-confirm-title">{{ __('helpdeskemaillog::emaillog.confirm.title') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0" id="trash-confirm-message">—</p>
                </div>
                <div class="modal-footer flex-column">
                    <button type="button" class="btn btn-primary w-100 mb-2" id="trash-confirm-accept">
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
    const $confirmModal = $('#trash-confirm-modal');
    const confirmModal = new bootstrap.Modal($confirmModal[0]);
    let pendingAccept = null;

    function askConfirm({ title, message, onAccept }) {
        $('#trash-confirm-title').text(title);
        $('#trash-confirm-message').text(message);
        pendingAccept = onAccept;
        confirmModal.show();
    }

    $('#trash-confirm-accept').on('click', function () {
        const fn = pendingAccept;
        pendingAccept = null;
        confirmModal.hide();
        if (typeof fn === 'function') fn();
    });

    // Restaurar (fila individual)
    $(document).on('click', '.js-trash-restore', function () {
        const url = $(this).data('url');
        askConfirm({
            title: @json(__('helpdeskemaillog::emaillog.trash.restore_confirm_title')),
            message: @json(__('helpdeskemaillog::emaillog.trash.restore_confirm')),
            onAccept: () => {
                $.ajax({ url, method: 'POST', headers: { 'X-CSRF-TOKEN': csrf } })
                    .done(() => location.reload())
                    .fail(xhr => toastr.error(xhr.responseJSON?.message || 'Error'));
            },
        });
    });

    // Eliminar definitivamente (fila individual) — irreversible, ver
    // EmailLogController::forceDestroy().
    $(document).on('click', '.js-trash-force-delete', function () {
        const url = $(this).data('url');
        askConfirm({
            title: @json(__('helpdeskemaillog::emaillog.trash.force_delete_confirm_title')),
            message: @json(__('helpdeskemaillog::emaillog.trash.force_delete_confirm')),
            onAccept: () => {
                $.ajax({ url, method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrf } })
                    .done(() => location.reload())
                    .fail(xhr => toastr.error(xhr.responseJSON?.message || 'Error'));
            },
        });
    });

    // Restauración masiva
    window.BulkActions.init({ checkbox: '.trash-bulk-checkbox', selectAll: '#trash-select-all', toolbar: '#trash-bulk-toolbar' });

    $('#trash-bulk-restore').on('click', function () {
        const uids = $('.trash-bulk-checkbox:checked').map(function () { return this.value; }).get();
        if (!uids.length) { toastr.warning(@json(__('helpdeskemaillog::emaillog.bulk.none_selected'))); return; }
        const url = $(this).data('url');
        askConfirm({
            title: @json(__('helpdeskemaillog::emaillog.trash.bulk_restore_confirm_title')),
            message: @json(__('helpdeskemaillog::emaillog.trash.bulk_restore_confirm')).replace(':count', uids.length),
            onAccept: () => {
                $.ajax({ url, method: 'POST', data: { uids }, headers: { 'X-CSRF-TOKEN': csrf } })
                    .done(() => location.reload())
                    .fail(xhr => toastr.error(xhr.responseJSON?.message || 'Error'));
            },
        });
    });
});
</script>
@endpush
