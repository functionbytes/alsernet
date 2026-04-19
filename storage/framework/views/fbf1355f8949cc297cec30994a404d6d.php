<?php
use Illuminate\Contracts\Auth\Access\Gate;
use Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys;
use Livewire\Mechanisms\ExtendBlade\ExtendBlade;

$__env->startSection('page_title', 'Usuarios'); ?>

<?php $__env->startSection('content'); ?>

    
    <?php echo $__env->make('core::components.card', [
        'title' => 'Gestión de usuarios',
        'breadcrumbs' => [
            ['label' => 'Dashboard', 'url' => url('/home')],
            ['label' => 'Configuración', 'url' => ''],
            ['label' => 'Usuarios', 'active' => true],
        ],
    ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>


    <?php echo $__env->make('core::components.alerts', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>


    <div class="widget-content searchable-container list">

        
        <div class="card">
            
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Usuarios del sistema</h5>
                        <p class="small mb-0 text-muted">Gestiona usuarios, roles y permisos de acceso</p>
                    </div>
                    <div class="ms-auto">
                        <div class="btn-group">
                            <button type="button" class="btn bg-primary-subtle text-primary dropdown-toggle" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                Acciones
                            </button>
                            <div class="dropdown-menu dropdown-menu-end">
                                <a class="dropdown-item" href="<?php echo e(route('settings.users.create')); ?>">Nuevo usuario</a>
                                <a class="dropdown-item" href="<?php echo e(route('settings.roles.index')); ?>">Ver roles</a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="<?php echo e(route('settings.users.export')); ?>">Exportar CSV (todos)</a>
                                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($roleFilter) { ?>
                                    <a class="dropdown-item" href="<?php echo e(route('settings.users.export', ['role' => $roleFilter])); ?>">Exportar CSV (filtrado)</a>
                                <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Total</h6>
                                <h4 class="mb-1 fw-bold"><?php echo e($stats['total']); ?></h4>
                                <small class="text-muted">Usuarios registrados</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Activos</h6>
                                <h4 class="mb-1 fw-bold"><?php echo e($stats['active']); ?></h4>
                                <small class="text-muted">Con acceso habilitado</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Inactivos</h6>
                                <h4 class="mb-1 fw-bold"><?php echo e($stats['inactive']); ?></h4>
                                <small class="text-muted">Sin acceso al sistema</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Nuevos este mes</h6>
                                <h4 class="mb-1 fw-bold"><?php echo e($stats['new']); ?></h4>
                                <small class="text-muted">Creados en <?php echo e(now()->translatedFormat('F Y')); ?></small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            
            <div class="card-body border-bottom">
                <form method="GET" action="<?php echo e(route('settings.users.index')); ?>">
                    <div class="d-flex flex-column flex-lg-row gap-3 align-items-stretch">
                        <div class="flex-fill">
                            <div class="input-group h-100">
                                <span class="input-group-text bg-white border-end-1">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="search" name="search" class="form-control border-start-0 ps-0"
                                       placeholder="Buscar por nombre, email o identificación..."
                                       value="<?php echo e($searchKey ?? ''); ?>">
                            </div>
                        </div>
                        <div class="flex-shrink-0" style="min-width: 180px;">
                            <select name="role" class="form-select select2">
                                <option value="">Todos los roles</option>
                                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__empty_1 = true;
$__currentLoopData = $availableRoles;
$__env->addLoop($__currentLoopData);
foreach ($__currentLoopData as $roleName => $roleLabel) {
    $__env->incrementLoopIndices();
    $loop = $__env->getLastLoop();
    $__empty_1 = false; ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
                                    <option value="<?php echo e($roleName); ?>" <?php echo e(($roleFilter ?? '') === $roleName ? 'selected' : ''); ?>>
                                        <?php echo e(ucwords(str_replace('-', ' ', $roleName))); ?>

                                    </option>
                                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } $__env->popLoop();
$loop = $__env->getLastLoop();
if ($__empty_1) { ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>
                                    <option disabled>No hay roles disponibles</option>
                                <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                            </select>
                        </div>
                        <div class="d-flex gap-2 flex-shrink-0">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-1"></i>
                            </button>
                            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($searchKey || $roleFilter) { ?>
                                <a href="<?php echo e(route('settings.users.index')); ?>" class="btn btn-outline-secondary" title="Limpiar filtros">
                                    <i class="fas fa-times"></i>
                                </a>
                            <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                        </div>
                    </div>
                </form>
            </div>

            
            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($users->count() > 0) { ?>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="users-table">
                        <thead class="table-light">
                            <tr>
                                <th width="3%">
                                    <input type="checkbox" id="select-all" class="form-check-input">
                                </th>
                                <th width="24%">Usuario</th>
                                <th width="20%">Email</th>
                                <th width="13%">Rol</th>
                                <th width="10%">Estado</th>
                                <th width="15%">Actualización</th>
                                <th width="15%" class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__currentLoopData = $users;
                $__env->addLoop($__currentLoopData);
                foreach ($__currentLoopData as $user) {
                    $__env->incrementLoopIndices();
                    $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
                                <tr>
                                    <td>
                                        <input type="checkbox"
                                               class="form-check-input user-checkbox"
                                               value="<?php echo e($user->id); ?>">
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                                <?php echo e(Str::title($user->firstname.' '.$user->lastname)); ?>

                                                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($user->document_id) { ?>
                                                    <small class="text-muted d-block">ID: <?php echo e($user->document_id); ?></small>
                                                <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="text-muted"><?php echo e($user->email); ?></span>
                                    </td>
                                    <td>
                                        <?php
                                    $roleName = $user->getRoleNames()->first() ?? 'Sin rol';
                    ?>
                                        <span class="badge bg-light text-black">
                                            <?php echo e(Str::title(str_replace('-', ' ', $roleName))); ?>

                                        </span>
                                    </td>
                                    <td>
                                        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($user->available) { ?>
                                            <span class="badge bg-success-subtle text-success">Activo</span>
                                        <?php } else { ?>
                                            <span class="badge bg-danger-subtle text-danger">Inactivo</span>
                                        <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                                    </td>
                                    <td>
                                        <small class="text-muted"><?php echo e($user->updated_at->format('d/m/Y H:i')); ?></small>
                                    </td>
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="fa-duotone fa-solid fa-ellipsis"></i>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($user->uid) { ?>
                                                    <li>
                                                        <a class="dropdown-item" href="<?php echo e(route('settings.users.show', $user->uid)); ?>">
                                                            Ver perfil
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="<?php echo e(route('settings.users.edit', $user->uid)); ?>">
                                                            Editar
                                                        </a>
                                                    </li>
                                                    <?php if (app(Gate::class)->check('auth.impersonate')) { ?>
                                                        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($user->id !== auth()->id()) { ?>
                                                            <li>
                                                                <form action="<?php echo e(route('auth.impersonation.start', $user->id)); ?>" method="POST" class="d-inline">
                                                                    <?php echo csrf_field(); ?>
                                                                    <button type="submit" class="dropdown-item impersonate-btn"
                                                                            data-name="<?php echo e($user->firstname); ?> <?php echo e($user->lastname); ?>">
                                                                        Impersonar
                                                                    </button>
                                                                </form>
                                                            </li>
                                                        <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                                                    <?php } ?>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <button type="button" class="dropdown-item delete-btn"
                                                                data-url="<?php echo e(route('settings.users.destroy', $user->uid)); ?>"
                                                                data-title="¿Eliminar usuario <?php echo e($user->firstname); ?> <?php echo e($user->lastname); ?>?">
                                                            Eliminar
                                                        </button>
                                                    </li>
                                                <?php } else { ?>
                                                    <li>
                                                        <span class="dropdown-item text-muted">
                                                            Usuario sin UID
                                                        </span>
                                                    </li>
                                                <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } $__env->popLoop();
                $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php } else { ?>
            <div class="card-body">
                <div class="text-center py-5">
                    <i class="fa fa-inbox fa-3x mb-3 text-muted opacity-50"></i>
                    <h5 class="fw-bold mb-2">No hay usuarios</h5>
                    <p class="text-muted mb-4">
                        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($searchKey || $roleFilter) { ?>
                            No se encontraron resultados con los filtros aplicados.
                        <?php } else { ?>
                            Comienza creando tu primer usuario.
                        <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                    </p>
                    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($searchKey || $roleFilter) { ?>
                        <a href="<?php echo e(route('settings.users.index')); ?>" class="btn btn-secondary">
                            Ver todos
                        </a>
                    <?php } else { ?>
                        <a href="<?php echo e(route('settings.users.create')); ?>" class="btn btn-primary">
                            + Crear ahora
                        </a>
                    <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                </div>
            </div>
            <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>

            
            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($users->hasPages()) { ?>
                <div class="card-footer bg-white border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted">
                            Mostrando <?php echo e($users->firstItem()); ?> - <?php echo e($users->lastItem()); ?> de <?php echo e($users->total()); ?> usuarios
                        </div>
                        <div>
                            <?php echo e($users->appends(request()->input())->links()); ?>

                        </div>
                    </div>
                </div>
            <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>

        </div>

    </div>

    
    <div id="bulk-toolbar"
         class="position-fixed bottom-0 start-50 translate-middle-x mb-4 d-none"
         style="z-index: 1050;">
        <button type="button" class="btn btn-primary shadow-lg px-4" data-bs-toggle="modal" data-bs-target="#bulk-modal">
            <span data-bulk-count>0</span> seleccionado(s) &mdash; Aplicar acción
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
                    <p class="text-muted mb-3">
                        Se aplicará la acción sobre <strong><span class="bulk-count-label">0</span> usuario(s)</strong> seleccionados.
                    </p>
                    <div class="mb-3">
                        <label for="bulk-action-select" class="form-label fw-semibold">Acción</label>
                        <select id="bulk-action-select" class="form-select select2">
                            <option value="">Seleccionar acción...</option>
                            <option value="activate">Activar</option>
                            <option value="deactivate">Desactivar</option>
                            <option value="assign_role">Asignar rol</option>
                            <option value="delete">Eliminar</option>
                        </select>
                    </div>
                    <div id="bulk-role-wrapper" class="d-none">
                        <label for="bulk-role-select" class="form-label fw-semibold">Rol</label>
                        <select id="bulk-role-select" class="form-select select2">
                            <option value="">Elegir rol...</option>
                            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__currentLoopData = $availableRoles;
$__env->addLoop($__currentLoopData);
foreach ($__currentLoopData as $roleName => $roleLabel) {
    $__env->incrementLoopIndices();
    $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
                                <option value="<?php echo e($roleName); ?>">
                                    <?php echo e(Str::title(str_replace('-', ' ', $roleName))); ?>

                                </option>
                            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } $__env->popLoop();
$loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button id="bulk-apply-btn" type="button" class="btn btn-primary w-100 mb-1">Aplicar</button>
                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal" id="bulk-cancel-btn">Cancelar</button>
                </div>
            </div>
        </div>
    </div>


<?php echo $__env->make('core::components.delete', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
$(document).ready(function () {

    // ── Inicializar bulk actions ───────────────────────────────────────────
    const bulk = window.BulkActions.init({ checkbox: '.user-checkbox' });

    // ── Select2 en el modal ────────────────────────────────────────────────
    $('#bulk-action-select').select2({ dropdownParent: $('#bulk-modal'), width: '100%' });
    $('#bulk-role-select').select2({ dropdownParent: $('#bulk-modal'), width: '100%' });

    // ── Show/hide role selector ────────────────────────────────────────────
    $('#bulk-action-select').on('change', function () {
        $('#bulk-role-wrapper').toggleClass('d-none', this.value !== 'assign_role');
    });

    // ── Reset on modal close (covers Cancelar, X, Escape, backdrop) ───────
    $('#bulk-modal').on('hide.bs.modal', function () {
        $('#bulk-action-select').val('').trigger('change');
        $('#bulk-role-select').val('').trigger('change');
        $('#bulk-role-wrapper').addClass('d-none');
        $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
        bulk.reset();
    });

    // ── Apply bulk action ──────────────────────────────────────────────────
    $('#bulk-apply-btn').on('click', function () {
        const action = $('#bulk-action-select').val();
        const ids    = bulk.getIds();
        const value  = $('#bulk-role-select').val();

        if (!action) { toastr.warning('Selecciona una acción antes de continuar.'); return; }
        if (!ids.length) { toastr.warning('Selecciona al menos un usuario.'); return; }
        if (action === 'assign_role' && !value) { toastr.warning('Selecciona un rol para asignar.'); return; }
        if (action === 'delete' && !confirm('¿Eliminar los ' + ids.length + ' usuarios seleccionados? Esta acción no se puede deshacer.')) { return; }

        $('#bulk-apply-btn').prop('disabled', true).text('Procesando...');

        $.ajax({
            url: '<?php echo e(route('settings.users.bulk-action')); ?>',
            method: 'POST',
            data: JSON.stringify({ action, ids, value, _token: $('meta[name="csrf-token"]').attr('content') }),
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                $('#bulk-modal').modal('hide');
                toastr.success(res.count + ' usuario(s) actualizados correctamente.');
                setTimeout(() => location.reload(), 1000);
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message ?? 'Error al procesar la acción.');
                $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
            },
        });
    });

    // ── Delete single user ─────────────────────────────────────────────────
    $('.delete-btn').on('click', function (e) {
        e.preventDefault();
        $('#delete-form').attr('action', $(this).data('url'));
        $('#delete-modal').modal('show');
    });

    // ── Confirm impersonation ──────────────────────────────────────────────
    $(document).on('click', '.impersonate-btn', function (e) {
        const name = $(this).data('name');
        if (!confirm('¿Impersonar a ' + name + '?\n\nPodrás volver a tu cuenta desde el banner superior.')) {
            e.preventDefault();
        }
    });
});
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.theme', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/developerts/Herd/system/modules/User/app/Providers/../../resources/views/users/index.blade.php ENDPATH**/ ?>