@extends('layouts.theme')

@section('title', __('helpdeskemailactivity::emaillog.trash.title'))

{{-- Sin page_header y con content_full_width, igual que el listado: el
     título de la franja del tema repetía el que ya lleva el breadcrumb de
     .evx-toolbar de dentro, y dejaba estas pantallas con un ancho distinto
     al del listado del que cuelgan. --}}
@section('content_full_width', true)

{{-- Mismo CSS que emails/index.blade.php (.evx-*, ver emaillog.css/
     emaillog-extras.css) — esta pantalla es una vista más del mismo módulo,
     nunca se toca esa hoja de estilos aquí (ver nota de HelpdeskEmailActivityServiceProvider
     sobre ?v=filemtime). --}}
@php
    $emaillogCss = public_path('modules/helpdeskemailactivity/css/emaillog.css');
    $emaillogExtrasCss = public_path('modules/helpdeskemailactivity/css/emaillog-extras.css');
    $emaillogCssV = is_file($emaillogCss) ? filemtime($emaillogCss) : time();
    $emaillogExtrasCssV = is_file($emaillogExtrasCss) ? filemtime($emaillogExtrasCss) : time();

    // Mismo mapa de iconos que emails/index.blade.php (ver EmailLogController::
    // STATUS_ICONS) — duplicado a propósito, ya era así en la vista principal.
    $statusIcons = ['sent' => 'fa-check', 'failed' => 'fa-xmark', 'queued' => 'fa-clock', 'bounced' => 'fa-triangle-exclamation', 'complained' => 'fa-flag', 'suppressed' => 'fa-ban'];
@endphp

@push('css')
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="{{ asset('modules/helpdeskemailactivity/css/emaillog.css') }}?v={{ $emaillogCssV }}">
    <link rel="stylesheet" href="{{ asset('modules/helpdeskemailactivity/css/emaillog-extras.css') }}?v={{ $emaillogExtrasCssV }}">
@endpush

@section('content')
    <div class="emaillog-index">
        <div class="evx-shell">
            <div class="evx-toolbar">
                <nav class="evx-crumbs" aria-label="breadcrumb">
                    <i class="fa-solid fa-headset" aria-hidden="true"></i>
                    <span>{{ __('helpdeskemailactivity::emaillog.crumbs.panel') }}</span>
                    <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                    <span>{{ __('helpdeskemailactivity::emaillog.title') }}</span>
                    <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                    <span class="is-current">{{ __('helpdeskemailactivity::emaillog.trash.title') }}</span>
                </nav>

                <form action="{{ route('helpdeskemailactivity.trash.index') }}" method="GET" class="evx-toolbar-search">
                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                    <input type="search" name="search" value="{{ request('search') }}"
                           aria-label="{{ __('helpdeskemailactivity::emaillog.filters.search') }}"
                           placeholder="{{ __('helpdeskemailactivity::emaillog.trash.search_placeholder') }}">
                </form>

                {{-- Sin esto la papelera era un callejón sin salida: no tiene
                     subnav (su barra la ocupa el buscador) y no había ningún
                     enlace de vuelta. --}}
                <div class="evx-toolbar-actions">
                    <a href="{{ route('helpdeskemailactivity.index') }}" class="evx-header-btn">
                        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                        {{ __('helpdeskemailactivity::emaillog.actions.back_to_list') }}
                    </a>
                </div>
            </div>

            <div class="evx-filterbar">
                <span class="evx-muted">{{ __('helpdeskemailactivity::emaillog.trash.retention_note', ['days' => $retentionDays]) }}</span>
                <div class="evx-filterbar-end">
                    <span class="evx-filter-count">
                        {{ trans_choice('helpdeskemailactivity::emaillog.filters.results_count', $logs->total(), ['count' => number_format($logs->total())]) }}
                    </span>
                </div>
            </div>

            <div id="evx-list-view">
                <div class="evx-list-head">
                    <input type="checkbox" id="trash-select-all" class="evx-list-check"
                           aria-label="{{ __('helpdeskemailactivity::emaillog.table.select_all') }}">
                    <label for="trash-select-all" class="evx-list-head-label">{{ __('helpdeskemailactivity::emaillog.table.select_all') }}</label>
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
                               aria-label="{{ $row->subject ?: __('helpdeskemailactivity::emaillog.table.subject') }}">
                        <span class="evx-row-dot {{ $statusVal }}" aria-hidden="true"></span>
                        <div class="evx-row-body">
                            <div class="evx-row-top">
                                {{-- Enlace al detalle: un registro en papelera
                                     se puede seguir inspeccionando (en solo
                                     lectura) antes de restaurarlo o purgarlo. --}}
                                <a href="{{ route('helpdeskemailactivity.show', $row->uid) }}" class="evx-row-subject evx-subject-link">{{ Str::limit($row->subject, 60) ?: '—' }}</a>
                            </div>
                            <div class="evx-recipient">
                                {{ __('helpdeskemailactivity::emaillog.preview.field.to') }}: {{ $recipientLine }}
                            </div>
                            <div class="evx-row-meta">
                                <span class="evx-status {{ $statusVal }}">
                                    <i class="fa-solid {{ $statusIcons[$statusVal] ?? 'fa-circle' }}" aria-hidden="true"></i>{{ $row->status_label }}
                                </span>
                                @if($row->module)
                                    <span class="evx-tag mono">{{ $row->module }}</span>
                                @endif
                                <span class="evx-row-date" title="{{ __('helpdeskemailactivity::emaillog.trash.expires_at', ['date' => $expiresAt?->format('d/m/Y')]) }}">
                                    {{ __('helpdeskemailactivity::emaillog.trash.deleted_at') }}: {{ $row->deleted_at?->format('d/m/Y H:i') }}
                                </span>
                            </div>
                        </div>
                        <div class="evx-ph-actions">
                            <button type="button" class="evx-icon-btn js-trash-restore"
                                    data-url="{{ route('helpdeskemailactivity.trash.restore', $row->uid) }}"
                                    aria-label="{{ __('helpdeskemailactivity::emaillog.trash.restore') }}"
                                    title="{{ __('helpdeskemailactivity::emaillog.trash.restore') }}">
                                <i class="fa-solid fa-trash-arrow-up" aria-hidden="true"></i>
                            </button>
                            <button type="button" class="evx-icon-btn is-danger js-trash-force-delete"
                                    data-url="{{ route('helpdeskemailactivity.trash.force-destroy', $row->uid) }}"
                                    aria-label="{{ __('helpdeskemailactivity::emaillog.trash.force_delete') }}"
                                    title="{{ __('helpdeskemailactivity::emaillog.trash.force_delete') }}">
                                <i class="fa-solid fa-trash-can" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="evx-empty-row">
                        <i class="fas fa-trash" aria-hidden="true"></i>
                        <p>{{ __('helpdeskemailactivity::emaillog.trash.empty') }}</p>
                    </div>
                @endforelse
            </div>

            @if($logs->hasPages())
                <div class="evx-pagination">
                    <span class="evx-muted">
                        {{ __('helpdeskemailactivity::emaillog.pagination.showing', ['first' => $logs->firstItem(), 'last' => $logs->lastItem(), 'total' => $logs->total()]) }}
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
        <span class="evx-bulk-count"><span data-bulk-count>0</span> {{ __('helpdeskemailactivity::emaillog.bulk.label') }}</span>
        <button type="button" class="evx-btn evx-btn-primary evx-btn-inline" id="trash-bulk-restore"
                data-url="{{ route('helpdeskemailactivity.trash.bulk-restore') }}">
            {{ __('helpdeskemailactivity::emaillog.trash.bulk_restore') }}
        </button>
    </div>

    {{-- Modal de confirmación (mismo patrón que emaillog-confirm-modal de
         emails/index.blade.php: modal-dialog-centered + footer apilado). --}}
    <div class="modal fade evx-dialog" id="trash-confirm-modal" tabindex="-1"
         aria-labelledby="trash-confirm-title" aria-describedby="trash-confirm-message" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                @include('helpdeskemailactivity::emails.partials.modal-head', [
                    'icon' => 'fa-trash-can',
                    'eyebrow' => __('helpdeskemailactivity::emaillog.modal.eyebrow.trash'),
                    'title' => __('helpdeskemailactivity::emaillog.confirm.title'),
                    'titleId' => 'trash-confirm-title',
                ])
                <div class="modal-body">
                    <p id="trash-confirm-message">—</p>

                    <div class="evx-dialog-keys">
                        <kbd>&crarr;</kbd> {{ __('helpdeskemailactivity::emaillog.modal.kbd_confirm') }}
                        <kbd>esc</kbd> {{ __('helpdeskemailactivity::emaillog.modal.kbd_cancel') }}
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" id="trash-confirm-accept">
                        {{ __('helpdeskemailactivity::emaillog.confirm.accept') }}
                    </button>
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                        {{ __('helpdeskemailactivity::emaillog.confirm.cancel') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script src="{{ asset('core/js/bulk.js') }}"></script>
{{-- Bootstrap minimo de datos (sesion flash + cadenas traducidas) que
     trash.js no puede resolver por su cuenta — toda la logica vive ahi. --}}
@php
    $trashFlash = ['success' => session('success'), 'error' => session('error')];
    $trashI18n = [
        'restoreConfirmTitle' => __('helpdeskemailactivity::emaillog.trash.restore_confirm_title'),
        'restoreConfirm' => __('helpdeskemailactivity::emaillog.trash.restore_confirm'),
        'forceDeleteConfirmTitle' => __('helpdeskemailactivity::emaillog.trash.force_delete_confirm_title'),
        'forceDeleteConfirm' => __('helpdeskemailactivity::emaillog.trash.force_delete_confirm'),
        'bulkRestoreConfirmTitle' => __('helpdeskemailactivity::emaillog.trash.bulk_restore_confirm_title'),
        'bulkRestoreConfirm' => __('helpdeskemailactivity::emaillog.trash.bulk_restore_confirm'),
        'noneSelected' => __('helpdeskemailactivity::emaillog.bulk.none_selected'),
    ];
@endphp
<script>window.EmailActivityTrash = { flash: @json($trashFlash), i18n: @json($trashI18n) };</script>
<script src="{{ asset('modules/helpdeskemailactivity/js/trash.js') }}?v={{ filemtime(public_path('modules/helpdeskemailactivity/js/trash.js')) }}"></script>
@endpush
