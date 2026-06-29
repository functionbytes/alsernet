@extends('layouts.theme')

@push('css')
<style>
.notif-row {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    border-bottom: 1px solid #f0f0f0;
}
.notif-row:last-child { border-bottom: none; }
.notif-row.unread { background: #f5f6f8; }
.notif-row-check { flex-shrink: 0; padding: 0 0.25rem 0 0.5rem; }
.notif-row-actions { flex-shrink: 0; padding: 0 0.5rem; }
.notif-item {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    flex: 1;
    padding: 0.875rem 0.5rem;
    background: none;
    border: none;
    text-align: left;
    cursor: pointer;
    transition: background 0.15s;
    border-radius: 6px;
    min-width: 0;
}
.notif-item:hover { background: #f5f6f8; }
.notif-item .ico {
    flex-shrink: 0;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #90bb122b;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #90bb13;
    font-size: 1rem;
}
.notif-row.unread .notif-item .ico i{
    color: #fff;
}

.notif-row.unread .notif-item .ico {
    background: #90bb13;
}
.notif-item .body { flex: 1; min-width: 0; }
.notif-item .head {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    margin-bottom: 0.2rem;
    flex-wrap: wrap;
}
.notif-item .head .title { font-weight: 600; font-size: 0.875rem; color: #333; }
.notif-item .badge-new {
    font-size: 0.62rem;
    font-weight: 700;
    padding: 2px 6px;
    border-radius: 999px;
    background: #90bb13;
    color: #fff;
    letter-spacing: 0.03em;
    text-transform: uppercase;
}
.notif-item .desc {
    font-size: 0.8125rem;
    color: #6c757d;
    margin-bottom: 0.2rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.notif-item .meta { font-size: 0.72rem; color: #aaa; }
.notif-item .meta i { font-size: 0.68rem; }
.notif-filter-select { min-width: 200px; }
.notif-empty-icon { width: 80px; height: 80px; }
.notif-bulk-toolbar { z-index: 1050; }
</style>
@endpush

@section('page_title', 'Notificaciones')


@section('page_header')
    @include('core::components.card', ['title' => 'Notificaciones'])
@endsection

@section('content')

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">

            {{-- Header --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Mis notificaciones</h5>
                        <p class="small mb-0 text-muted">Administra y revisa todas tus notificaciones del sistema</p>
                    </div>
                    <div class="dropdown">
                        <a href="#" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown">
                            Acciones
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <button type="button" class="dropdown-item mark-all-read-btn" @if($unreadCount === 0) disabled @endif>
                                    Marcar todas como leídas
                                </button>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <button type="button" class="dropdown-item delete-all-btn" @if($notifications->total() === 0) disabled @endif>
                                    Eliminar todas
                                </button>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a href="{{ route('notifications.preferences.index') }}" class="dropdown-item">
                                    Preferencias
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            {{-- Stats --}}
            <div class="card-body border-bottom">
                @php
                    $totalCount = $notifications->total();
                    $readCount  = $totalCount - $unreadCount;
                    $todayCount = \App\Models\User::find(auth()->id())->notifications()->whereDate('created_at', today())->count();
                @endphp
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title text-primary mb-2">Total</h6>
                                <h4 class="mb-1 fw-bold">{{ $totalCount }}</h4>
                                <small class="text-muted">Todas las notificaciones</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title text-warning mb-2">No leídas</h6>
                                <h4 class="mb-1 fw-bold">{{ $unreadCount }}</h4>
                                <small class="text-muted">Pendientes de revisar</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Leídas</h6>
                                <h4 class="mb-1 fw-bold">{{ $readCount }}</h4>
                                <small class="text-muted">Ya revisadas</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title text-info mb-2">Hoy</h6>
                                <h4 class="mb-1 fw-bold">{{ $todayCount }}</h4>
                                <small class="text-muted">Recibidas hoy</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Filter --}}
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('notifications.index') }}">
                    <div class="d-flex flex-column flex-lg-row gap-3 align-items-stretch">
                        <div class="flex-shrink-0 notif-filter-select">
                            <select name="filter" class="form-select select2 h-100">
                                <option value="all"    {{ $filter === 'all'    ? 'selected' : '' }}>Todas</option>
                                <option value="unread" {{ $filter === 'unread' ? 'selected' : '' }}>No leídas</option>
                                <option value="read"   {{ $filter === 'read'   ? 'selected' : '' }}>Leídas</option>
                            </select>
                        </div>
                        <div class="flex-fill">
                            <div class="input-group h-100">
                                <span class="input-group-text bg-white border-end-1">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="search" name="search" class="form-control -0 ps-0"
                                       placeholder="Buscar notificaciones..."
                                       value="{{ request('search') }}">
                            </div>
                        </div>
                        <div class="d-flex gap-2 flex-shrink-0">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i>
                            </button>
                            @if(request('search') || (request('filter') && request('filter') !== 'all'))
                                <a href="{{ route('notifications.index') }}" class="btn btn-outline-secondary" title="Limpiar filtros">
                                    <i class="fas fa-times"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>

            {{-- Notifications list --}}
            <div class="card-body">
                @if($notifications->count() > 0)
                    <div class="notifications-list">
                        @foreach($notifications as $notification)
                            @php
                                $data      = $notification->data;
                                $isUnread  = $notification->read_at === null;
                                $iconClass = $data['icon'] ?? 'fas fa-bell';
                                $actionUrl = $data['action_url'] ?? null;
                            @endphp

                            <div class="notif-row {{ $isUnread ? 'unread' : '' }}" data-notification-id="{{ $notification->id }}">

                                {{-- Checkbox --}}
                                <div class="notif-row-check">
                                    <input type="checkbox" class="form-check-input bulk-checkbox" value="{{ $notification->id }}">
                                </div>

                                {{-- Main button --}}
                                <button class="notif-item" type="button"
                                        data-notification-id="{{ $notification->id }}"
                                        data-action-url="{{ $actionUrl }}">
                                    <div class="ico">
                                        <i class="{{ $iconClass }}"></i>
                                    </div>
                                    <div class="body">
                                        <div class="head">
                                            <span class="title">{{ $data['title'] ?? 'Notificación' }}</span>
                                            @if($isUnread)
                                                <span class="badge-new">Nuevo</span>
                                            @endif
                                            @if(isset($data['priority']) && $data['priority'] === 'high')
                                                <i class="fas fa-exclamation-circle text-danger" title="Prioridad alta"></i>
                                            @endif
                                        </div>
                                        @if(isset($data['message']) && $data['message'])
                                            <div class="desc">{{ $data['message'] }}</div>
                                        @endif
                                        <div class="meta">
                                            <i class="far fa-clock"></i> {{ $notification->created_at->diffForHumans() }}
                                        </div>
                                    </div>
                                </button>

                                {{-- Per-row actions --}}
                                <div class="notif-row-actions">
                                    <div class="dropdown">
                                        <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="fas fa-ellipsis-vertical"></i>
                                        </a>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            @if($isUnread)
                                                <li>
                                                    <button type="button" class="dropdown-item mark-as-read-btn">
                                                        Marcar como leída
                                                    </button>
                                                </li>
                                                <li><hr class="dropdown-divider"></li>
                                            @endif
                                            @if($actionUrl)
                                                <li>
                                                    <a class="dropdown-item" href="{{ $actionUrl }}" target="_blank">
                                                        Ver detalle
                                                    </a>
                                                </li>
                                                <li><hr class="dropdown-divider"></li>
                                            @endif
                                            <li>
                                                <button type="button" class="dropdown-item delete-notification-btn">
                                                    Eliminar
                                                </button>
                                            </li>
                                        </ul>
                                    </div>
                                </div>

                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-5">
                        <div class="d-flex flex-column align-items-center">
                            <div class="notif-empty-icon rounded-circle bg-light-subtle text-muted mb-3 d-flex align-items-center justify-content-center">
                                <i class="fas fa-bell-slash fs-4"></i>
                            </div>
                            <h6 class="mb-1">No hay notificaciones</h6>
                            <p class="text-muted mb-0">
                                @if($filter === 'unread')
                                    No tienes notificaciones sin leer en este momento
                                @elseif($filter === 'read')
                                    No tienes notificaciones leídas
                                @else
                                    No tienes ninguna notificación
                                @endif
                            </p>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Pagination --}}
            @if($notifications->hasPages())
                <div class="card-footer bg-white border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted">
                            Mostrando <strong>{{ $notifications->firstItem() }}</strong> a <strong>{{ $notifications->lastItem() }}</strong>
                            de <strong>{{ $notifications->total() }}</strong> notificaciones
                        </div>
                        <nav>{{ $notifications->links() }}</nav>
                    </div>
                </div>
            @endif

        </div>
    </div>

    {{-- Bulk toolbar flotante --}}
    <div id="bulk-toolbar" class="position-fixed bottom-0 start-50 translate-middle-x mb-4 d-none notif-bulk-toolbar">
        <button type="button" class="btn btn-primary shadow-lg px-4" data-bs-toggle="modal" data-bs-target="#bulk-modal">
            <span data-bulk-count>0</span> seleccionada(s) &mdash; Aplicar acción
        </button>
    </div>

    {{-- Bulk modal --}}
    <div class="modal fade" id="bulk-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Acción masiva</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">Se aplicará la acción sobre <strong><span data-bulk-count>0</span> notificación(es)</strong>.</p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Acción</label>
                        <select id="bulk-action-select" class="form-select">
                            <option value="">Seleccionar acción...</option>
                            <option value="mark_read">Marcar como leídas</option>
                            <option value="delete">Eliminar</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button id="bulk-apply-btn" type="button" class="btn btn-primary w-100 mb-1">Aplicar</button>
                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Confirm: mark all as read --}}
    <div class="modal fade" id="markAllAsReadModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Marcar todas como leídas</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">¿Deseas marcar <strong>todas las notificaciones como leídas</strong>? Esta acción no se puede deshacer.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary w-100 mb-1" id="confirmMarkAllReadBtn">
                        Sí, marcar todas
                    </button>
                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Confirm: delete single --}}
    <div class="modal fade" id="deleteNotificationModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Eliminar notificación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">¿Deseas eliminar esta notificación? Esta acción no se puede deshacer.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary w-100 mb-1" id="confirmDeleteBtn">Sí, eliminar</button>
                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Confirm: delete all --}}
    <div class="modal fade" id="deleteAllModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Eliminar todas las notificaciones</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">¿Deseas eliminar <strong>todas las notificaciones</strong>? Esta acción no se puede deshacer.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary w-100 mb-1" id="confirmDeleteAllBtn">Sí, eliminar todas</button>
                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    const bulk = window.BulkActions.init({ checkbox: '.bulk-checkbox' });

    $('#bulk-action-select').select2({ dropdownParent: $('#bulk-modal'), width: '100%' });

    const markAllModal   = new bootstrap.Modal(document.getElementById('markAllAsReadModal'));
    const deleteModal    = new bootstrap.Modal(document.getElementById('deleteNotificationModal'));
    const deleteAllModal = new bootstrap.Modal(document.getElementById('deleteAllModal'));

    let currentDeleteItem = null;

    // -----------------------------------------------------------------------
    // Bulk action
    // -----------------------------------------------------------------------

    $('#bulk-modal').on('hide.bs.modal', function () {
        $('#bulk-action-select').val('').trigger('change');
        $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
        bulk.reset();
    });

    $('#bulk-apply-btn').on('click', function () {
        const action = $('#bulk-action-select').val();
        const ids    = bulk.getIds();

        if (!action) { toastr.warning('Selecciona una acción.'); return; }
        if (!ids.length) { toastr.warning('Selecciona al menos una notificación.'); return; }
        if (action === 'delete' && !confirm('¿Eliminar las ' + ids.length + ' notificación(es) seleccionadas?')) { return; }

        $('#bulk-apply-btn').prop('disabled', true).text('Procesando...');

        $.ajax({
            url: '{{ route('notifications.bulk-action') }}',
            method: 'POST',
            data: JSON.stringify({ action, ids, _token: $('meta[name="csrf-token"]').attr('content') }),
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                $('#bulk-modal').modal('hide');
                toastr.success(res.count + ' notificación(es) procesadas.');
                setTimeout(() => location.reload(), 800);
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message ?? 'Error al procesar.');
                $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
            },
        });
    });

    // -----------------------------------------------------------------------
    // Click on notif-item → navigate to action url
    // -----------------------------------------------------------------------

    $(document).on('click', '.notif-item', function () {
        const url = $(this).data('action-url');
        if (url) { window.location.href = url; }
    });

    // -----------------------------------------------------------------------
    // Per-row: mark as read
    // -----------------------------------------------------------------------

    $(document).on('click', '.mark-as-read-btn', function () {
        const $row = $(this).closest('.notif-row');
        const id   = $row.data('notification-id');
        const url  = '{{ url('/api/notifications') }}/' + id + '/read';

        $.ajax({
            url,
            type: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function () {
                toastr.success('Notificación marcada como leída');
                setTimeout(() => location.reload(), 600);
            },
            error: function () {
                toastr.error('Error al marcar la notificación como leída');
            },
        });
    });

    // -----------------------------------------------------------------------
    // Per-row: delete
    // -----------------------------------------------------------------------

    $(document).on('click', '.delete-notification-btn', function (e) {
        e.stopPropagation();
        currentDeleteItem = $(this).closest('.notif-row');
        deleteModal.show();
    });

    $('#confirmDeleteBtn').on('click', function () {
        if (!currentDeleteItem) { return; }

        const id    = currentDeleteItem.data('notification-id');
        const url   = '{{ url('/api/notifications') }}/' + id;
        const $item = currentDeleteItem;

        deleteModal.hide();

        $.ajax({
            url,
            type: 'DELETE',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function () {
                toastr.success('Notificación eliminada');
                $item.fadeOut(300, function () {
                    $(this).remove();
                    if ($('.notif-row').length === 0) { setTimeout(() => location.reload(), 400); }
                });
            },
            error: function () {
                toastr.error('Error al eliminar la notificación');
            },
        });
    });

    // -----------------------------------------------------------------------
    // Mark all as read
    // -----------------------------------------------------------------------

    $('.mark-all-read-btn').on('click', function () {
        if ($(this).prop('disabled')) { return; }
        markAllModal.show();
    });

    $('#confirmMarkAllReadBtn').on('click', function () {
        markAllModal.hide();
        $.ajax({
            url: '{{ route('api.notifications.mark-all-read') }}',
            type: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function () {
                toastr.success('Todas las notificaciones han sido marcadas como leídas');
                setTimeout(() => location.reload(), 800);
            },
            error: function () {
                toastr.error('Error al marcar todas las notificaciones como leídas');
            },
        });
    });

    // -----------------------------------------------------------------------
    // Delete all
    // -----------------------------------------------------------------------

    $('.delete-all-btn').on('click', function () {
        if ($(this).prop('disabled')) { return; }
        deleteAllModal.show();
    });

    $('#confirmDeleteAllBtn').on('click', function () {
        deleteAllModal.hide();
        $.ajax({
            url: '{{ route('notifications.destroy-all') }}',
            type: 'DELETE',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function () {
                toastr.success('Todas las notificaciones han sido eliminadas');
                setTimeout(() => location.reload(), 800);
            },
            error: function () {
                toastr.error('Error al eliminar todas las notificaciones');
            },
        });
    });

    // -----------------------------------------------------------------------
    // Flash messages
    // -----------------------------------------------------------------------

    @if (session('success'))
        toastr.success(@json(session('success')));
    @endif
    @if (session('error'))
        toastr.error(@json(session('error')));
    @endif
});
</script>
@endpush
