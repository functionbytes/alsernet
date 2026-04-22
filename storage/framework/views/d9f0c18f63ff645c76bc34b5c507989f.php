<?php
use App\Models\User;
use Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys;
use Livewire\Mechanisms\ExtendBlade\ExtendBlade;

$__env->startSection('page_title', 'Notificaciones'); ?>

<?php $__env->startSection('content'); ?>

    <?php echo $__env->make('core::components.card', ['title' => 'Notificaciones'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <div class="widget-content searchable-container list">

        <?php echo $__env->make('core::components.alerts', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

        <div class="card">

            
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Mis notificaciones</h5>
                        <p class="small mb-0 text-muted">Administra y revisa todas tus notificaciones del sistema</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="<?php echo e(route('notifications.preferences.index')); ?>" class="btn btn-outline-secondary">
                            Preferencias
                        </a>
                        <div class="dropdown">
                            <a href="#" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown">
                                Acciones
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <button type="button" class="dropdown-item mark-all-read-btn" <?php if ($unreadCount === 0) { ?> disabled <?php } ?>>
                                        Marcar todas como leídas
                                    </button>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <button type="button" class="dropdown-item delete-all-btn" <?php if ($notifications->total() === 0) { ?> disabled <?php } ?>>
                                        Eliminar todas
                                    </button>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            
            <div class="card-body border-bottom">
                <?php
                    $totalCount = $notifications->total();
$readCount = $totalCount - $unreadCount;
$todayCount = User::find(auth()->id())->notifications()->whereDate('created_at', today())->count();
?>
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title text-primary mb-2">Total</h6>
                                <h4 class="mb-1 fw-bold"><?php echo e($totalCount); ?></h4>
                                <small class="text-muted">Todas las notificaciones</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title text-warning mb-2">No leídas</h6>
                                <h4 class="mb-1 fw-bold"><?php echo e($unreadCount); ?></h4>
                                <small class="text-muted">Pendientes de revisar</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Leídas</h6>
                                <h4 class="mb-1 fw-bold"><?php echo e($readCount); ?></h4>
                                <small class="text-muted">Ya revisadas</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title text-info mb-2">Hoy</h6>
                                <h4 class="mb-1 fw-bold"><?php echo e($todayCount); ?></h4>
                                <small class="text-muted">Recibidas hoy</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            
            <div class="card-body border-bottom">
                <form method="GET" action="<?php echo e(route('notifications.index')); ?>">
                    <div class="d-flex flex-column flex-lg-row gap-3 align-items-stretch">
                        <div class="flex-shrink-0" style="min-width: 200px;">
                            <select name="filter" class="form-select select2 h-100">
                                <option value="all"    <?php echo e($filter === 'all' ? 'selected' : ''); ?>>Todas</option>
                                <option value="unread" <?php echo e($filter === 'unread' ? 'selected' : ''); ?>>No leídas</option>
                                <option value="read"   <?php echo e($filter === 'read' ? 'selected' : ''); ?>>Leídas</option>
                            </select>
                        </div>
                        <div class="flex-fill">
                            <div class="input-group h-100">
                                <span class="input-group-text bg-white border-end-1">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="search" name="search" class="form-control border-start-0 ps-0"
                                       placeholder="Buscar notificaciones..."
                                       value="<?php echo e(request('search')); ?>">
                            </div>
                        </div>
                        <div class="d-flex gap-2 flex-shrink-0">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i>
                            </button>
                            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (request('search') || (request('filter') && request('filter') !== 'all')) { ?>
                                <a href="<?php echo e(route('notifications.index')); ?>" class="btn btn-outline-secondary" title="Limpiar filtros">
                                    <i class="fas fa-times"></i>
                                </a>
                            <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                        </div>
                    </div>
                </form>
            </div>

            
            <div class="card-body">
                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($notifications->count() > 0) { ?>
                    <div class="notifications-list">
                        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__currentLoopData = $notifications;
                    $__env->addLoop($__currentLoopData);
                    foreach ($__currentLoopData as $notification) {
                        $__env->incrementLoopIndices();
                        $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
                            <?php
                                $data = $notification->data;
                        $isUnread = $notification->read_at === null;
                        $colorClass = $data['color'] ?? 'primary';
                        $iconClass = $data['icon'] ?? 'fas fa-bell';
                        $actionUrl = $data['action_url'] ?? null;
                        ?>

                            <div class="notification-item d-flex align-items-start gap-3 p-3 mb-2 rounded border <?php echo e($isUnread ? 'border-start border-'.$colorClass.' border-3 bg-light-subtle' : 'border-light'); ?>"
                                 data-notification-id="<?php echo e($notification->id); ?>"
                                 data-action-url="<?php echo e($actionUrl); ?>">

                                
                                <div class="flex-shrink-0 pt-1">
                                    <input type="checkbox" class="form-check-input bulk-checkbox" value="<?php echo e($notification->id); ?>">
                                </div>

                                
                                <div class="flex-shrink-0">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center bg-<?php echo e($colorClass); ?>-subtle"
                                         style="width: 48px; height: 48px;">
                                        <i class="<?php echo e($iconClass); ?> fs-5 text-<?php echo e($colorClass); ?>"></i>
                                    </div>
                                </div>

                                
                                <div class="flex-grow-1" style="min-width: 0;">
                                    <div class="d-flex align-items-start justify-content-between gap-2">
                                        <div>
                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                <h6 class="mb-0 fw-semibold"><?php echo e($data['title'] ?? 'Notificación'); ?></h6>
                                                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($isUnread) { ?>
                                                    <span class="badge rounded-pill bg-success" style="font-size: 9px; padding: 3px 8px;">NUEVO</span>
                                                <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                                                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (isset($data['priority']) && $data['priority'] === 'high') { ?>
                                                    <i class="fas fa-exclamation-circle text-danger" title="Prioridad alta"></i>
                                                <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                                            </div>
                                            <small class="text-muted">
                                                <i class="fas fa-clock me-1"></i><?php echo e($notification->created_at->diffForHumans()); ?>

                                            </small>
                                        </div>
                                        
                                        <div class="dropdown flex-shrink-0">
                                            <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="fas fa-ellipsis-vertical"></i>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($isUnread) { ?>
                                                    <li>
                                                        <button type="button" class="dropdown-item mark-as-read-btn">
                                                            Marcar como leída
                                                        </button>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                                                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($actionUrl) { ?>
                                                    <li>
                                                        <a class="dropdown-item" href="<?php echo e($actionUrl); ?>" target="_blank">
                                                            Ver detalle
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                                                <li>
                                                    <button type="button" class="dropdown-item delete-notification-btn">
                                                        Eliminar
                                                    </button>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>

                                    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (isset($data['message']) && $data['message']) { ?>
                                        <p class="mb-0 mt-2 text-muted" style="font-size: 13px; line-height: 1.6;">
                                            <?php echo e($data['message']); ?>

                                        </p>
                                    <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                                </div>
                            </div>
                        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } $__env->popLoop();
                    $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>
                    </div>
                <?php } else { ?>
                    <div class="text-center py-5">
                        <div class="d-flex flex-column align-items-center">
                            <div class="round-48 rounded-circle bg-light-subtle text-muted mb-3 d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                                <i class="fas fa-bell-slash fs-4"></i>
                            </div>
                            <h6 class="mb-1">No hay notificaciones</h6>
                            <p class="text-muted mb-0">
                                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($filter === 'unread') { ?>
                                    No tienes notificaciones sin leer en este momento
                                <?php } elseif ($filter === 'read') { ?>
                                    No tienes notificaciones leídas
                                <?php } else { ?>
                                    No tienes ninguna notificación
                                <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                            </p>
                        </div>
                    </div>
                <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
            </div>

            
            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($notifications->hasPages()) { ?>
                <div class="card-footer bg-white border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted">
                            Mostrando <strong><?php echo e($notifications->firstItem()); ?></strong> a <strong><?php echo e($notifications->lastItem()); ?></strong>
                            de <strong><?php echo e($notifications->total()); ?></strong> notificaciones
                        </div>
                        <nav><?php echo e($notifications->links()); ?></nav>
                    </div>
                </div>
            <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>

        </div>
    </div>

    
    <div id="bulk-toolbar" class="position-fixed bottom-0 start-50 translate-middle-x mb-4 d-none" style="z-index:1050;">
        <button type="button" class="btn btn-primary shadow-lg px-4" data-bs-toggle="modal" data-bs-target="#bulk-modal">
            <span data-bulk-count>0</span> seleccionada(s) &mdash; Aplicar acción
        </button>
    </div>

    
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

<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
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
            url: '<?php echo e(route('notifications.bulk-action')); ?>',
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
    // Per-row: mark as read
    // -----------------------------------------------------------------------

    $(document).on('click', '.mark-as-read-btn', function () {
        const $item = $(this).closest('.notification-item');
        const id    = $item.data('notification-id');
        const url   = '<?php echo e(url('/api/notifications')); ?>/' + id + '/read';

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
        currentDeleteItem = $(this).closest('.notification-item');
        deleteModal.show();
    });

    $('#confirmDeleteBtn').on('click', function () {
        if (!currentDeleteItem) { return; }

        const id  = currentDeleteItem.data('notification-id');
        const url = '<?php echo e(url('/api/notifications')); ?>/' + id;
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
                    if ($('.notification-item').length === 0) { setTimeout(() => location.reload(), 400); }
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
            url: '<?php echo e(route('api.notifications.mark-all-read')); ?>',
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
            url: '<?php echo e(route('notifications.destroy-all')); ?>',
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

    <?php if (session('success')) { ?>
        toastr.success(<?php echo json_encode(session('success'), 15, 512) ?>);
    <?php } ?>
    <?php if (session('error')) { ?>
        toastr.error(<?php echo json_encode(session('error'), 15, 512) ?>);
    <?php } ?>
});
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.theme', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/developerts/Herd/system/modules/Notification/app/Providers/../../resources/views/managers/notifications/index.blade.php ENDPATH**/ ?>