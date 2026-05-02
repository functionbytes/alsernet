<?php
use Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys;
use Livewire\Mechanisms\ExtendBlade\ExtendBlade;

$__env->startSection('title', 'Tiendas conectadas'); ?>

<?php $__env->startSection('page_header'); ?>
    <?php echo $__env->make('core::components.card', ['title' => 'Tiendas conectadas'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

    <?php echo $__env->make('core::components.alerts', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <div class="widget-content searchable-container list">

        <div class="card">

            <div class="card-header p-4 border-bottom">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Tiendas conectadas</h5>
                        <p class="small mb-0 text-muted">Gestiona las integraciones con plataformas ecommerce</p>
                    </div>
                    <a href="<?php echo e(route('remarketing.stores.create')); ?>" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> Conectar tienda
                    </a>
                </div>
            </div>

            <div class="card-body p-0">
                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($stores->isEmpty()) { ?>
                    <div class="text-center py-5">
                        <i class="fas fa-store fa-3x text-muted mb-3"></i>
                        <p class="text-muted mb-3">No hay tiendas conectadas todavía</p>
                        <a href="<?php echo e(route('remarketing.stores.create')); ?>" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> Conectar primera tienda
                        </a>
                    </div>
                <?php } else { ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Tienda</th>
                                    <th>Plataforma</th>
                                    <th>Estado</th>
                                    <th>Última sincronización</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__currentLoopData = $stores;
                    $__env->addLoop($__currentLoopData);
                    foreach ($__currentLoopData as $store) {
                        $__env->incrementLoopIndices();
                        $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold"><?php echo e($store->name); ?></div>
                                            <small class="text-muted"><?php echo e($store->domain); ?></small>
                                        </td>
                                        <td>
                                            <?php
                                        $platformIcons = [
                                            'shopify' => 'fab fa-shopify',
                                            'woocommerce' => 'fab fa-wordpress',
                                            'prestashop' => 'fas fa-shopping-bag',
                                            'magento' => 'fas fa-store',
                                            'bigcommerce' => 'fas fa-cart-plus',
                                        ];
                        $icon = $platformIcons[$store->platform] ?? 'fas fa-store';
                        ?>
                                            <i class="<?php echo e($icon); ?> me-1"></i>
                                            <?php echo e(ucfirst($store->platform)); ?>

                                        </td>
                                        <td>
                                            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($store->status === 'active') { ?>
                                                <span class="badge bg-success-subtle text-success">Activa</span>
                                            <?php } elseif ($store->status === 'syncing') { ?>
                                                <span class="badge bg-info-subtle text-info">Sincronizando</span>
                                            <?php } elseif ($store->status === 'error') { ?>
                                                <span class="badge bg-danger-subtle text-danger">Error</span>
                                            <?php } else { ?>
                                                <span class="badge bg-secondary-subtle text-secondary"><?php echo e($store->status); ?></span>
                                            <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                                        </td>
                                        <td class="small text-muted">
                                            <?php echo e($store->last_synced_at?->diffForHumans() ?? 'Nunca'); ?>

                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-light" data-bs-toggle="dropdown">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <button class="dropdown-item btn-sync" data-id="<?php echo e($store->id); ?>">Sincronizar</button>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="<?php echo e(route('remarketing.stores.edit', $store)); ?>">Editar</a>
                                                    </li>
                                                    <li>
                                                        <button class="dropdown-item btn-health" data-id="<?php echo e($store->id); ?>">Health check</button>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <button class="dropdown-item btn-delete"
                                                                data-id="<?php echo e($store->id); ?>"
                                                                data-name="<?php echo e($store->name); ?>"
                                                                data-url="<?php echo e(route('remarketing.stores.destroy', $store)); ?>">
                                                            Eliminar
                                                        </button>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } $__env->popLoop();
                    $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>
                            </tbody>
                        </table>
                    </div>
                <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
            </div>

            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($stores->hasPages()) { ?>
                <div class="card-footer bg-white border-top">
                    <?php echo e($stores->links()); ?>

                </div>
            <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>

        </div>

    </div>

    
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title">Eliminar tienda</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">¿Estás seguro de eliminar la tienda <strong id="delete-name"></strong>? Se eliminarán todos los datos asociados.</p>
                </div>
                <div class="modal-footer flex-column">
                    <form id="deleteForm" method="POST" class="w-100">
                        <?php echo csrf_field(); ?>
                        <?php echo method_field('DELETE'); ?>
                        <button type="submit" class="btn btn-danger w-100 mb-2">Eliminar tienda</button>
                    </form>
                    <button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
$(document).ready(function () {

    $(document).on('click', '.btn-delete', function () {
        var name = $(this).data('name');
        var url  = $(this).data('url');
        $('#delete-name').text(name);
        $('#deleteForm').attr('action', url);
        $('#deleteModal').modal('show');
    });

    $(document).on('click', '.btn-sync', function () {
        var id = $(this).data('id');
        $.ajax({
            url: '/api/remarketing/stores/' + id + '/sync',
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) { toastr.success(res.message || 'Sincronización iniciada'); },
            error: function ()    { toastr.error('Error al iniciar la sincronización'); }
        });
    });

    $(document).on('click', '.btn-health', function () {
        var id = $(this).data('id');
        $.ajax({
            url: '/api/remarketing/stores/' + id + '/health',
            method: 'GET',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) { toastr.info(res.message || 'Health check completado'); },
            error: function ()    { toastr.error('Error al ejecutar health check'); }
        });
    });

});
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.theme', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/developerts/Herd/system/modules/Remarketing/resources/views/stores/index.blade.php ENDPATH**/ ?>