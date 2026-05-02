<?php
use Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys;
use Livewire\Mechanisms\ExtendBlade\ExtendBlade;

$__env->startSection('title', 'Catálogo de productos'); ?>

<?php $__env->startSection('page_header'); ?>
    <?php echo $__env->make('core::components.card', ['title' => 'Catálogo de productos'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

    <?php echo $__env->make('core::components.alerts', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <div class="widget-content searchable-container list">

        <div class="card">

            <div class="card-header p-4 border-bottom">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Catálogo de productos</h5>
                        <p class="small mb-0 text-muted">Productos sincronizados desde tus tiendas conectadas</p>
                    </div>
                </div>
            </div>

            <div class="card-body border-bottom py-3">
                <form method="GET" action="<?php echo e(route('remarketing.products.index')); ?>">
                    <div class="d-flex gap-2">
                        <select name="store_id" class="form-select w-auto">
                            <option value="">Todas las tiendas</option>
                            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__currentLoopData = $stores ?? [];
$__env->addLoop($__currentLoopData);
foreach ($__currentLoopData as $store) {
    $__env->incrementLoopIndices();
    $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
                                <option value="<?php echo e($store->id); ?>" <?php echo e(request('store_id') == $store->id ? 'selected' : ''); ?>>
                                    <?php echo e($store->name); ?>

                                </option>
                            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } $__env->popLoop();
$loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>
                        </select>
                        <select name="status" class="form-select w-auto">
                            <option value="">Todos los estados</option>
                            <option value="active" <?php echo e(request('status') === 'active' ? 'selected' : ''); ?>>Activo</option>
                            <option value="draft" <?php echo e(request('status') === 'draft' ? 'selected' : ''); ?>>Borrador</option>
                            <option value="archived" <?php echo e(request('status') === 'archived' ? 'selected' : ''); ?>>Archivado</option>
                        </select>
                        <input type="text" name="search" class="form-control" placeholder="Buscar por título o SKU..."
                               value="<?php echo e(request('search')); ?>">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i>
                        </button>
                        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (request()->hasAny(['search', 'store_id', 'status'])) { ?>
                            <a href="<?php echo e(route('remarketing.products.index')); ?>" class="btn btn-light">
                                <i class="fas fa-times"></i>
                            </a>
                        <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                    </div>
                </form>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:60px">Imagen</th>
                                <th>Título</th>
                                <th>SKU</th>
                                <th>Precio</th>
                                <th>Stock</th>
                                <th>Estado</th>
                                <th>Tienda</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__empty_1 = true;
$__currentLoopData = $products;
$__env->addLoop($__currentLoopData);
foreach ($__currentLoopData as $product) {
    $__env->incrementLoopIndices();
    $loop = $__env->getLastLoop();
    $__empty_1 = false; ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
                                <tr>
                                    <td>
                                        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($product->image_url) { ?>
                                            <img src="<?php echo e($product->image_url); ?>" alt="<?php echo e($product->title); ?>"
                                                 width="48" height="48" class="rounded" loading="lazy"
                                                 style="object-fit:cover">
                                        <?php } else { ?>
                                            <div class="rounded bg-light d-flex align-items-center justify-content-center" style="width:48px;height:48px">
                                                <i class="fas fa-image text-muted"></i>
                                            </div>
                                        <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                                    </td>
                                    <td>
                                        <div class="fw-semibold small"><?php echo e(Str::limit($product->title, 60)); ?></div>
                                        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($product->vendor) { ?>
                                            <small class="text-muted"><?php echo e($product->vendor); ?></small>
                                        <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                                    </td>
                                    <td class="small text-muted"><?php echo e($product->sku ?: '—'); ?></td>
                                    <td class="small fw-semibold"><?php echo e(number_format($product->price, 2)); ?> <?php echo e($product->currency); ?></td>
                                    <td class="small">
                                        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($product->inventory <= 0) { ?>
                                            <span class="text-danger">Sin stock</span>
                                        <?php } elseif ($product->inventory < 5) { ?>
                                            <span class="text-warning"><?php echo e($product->inventory); ?></span>
                                        <?php } else { ?>
                                            <?php echo e($product->inventory); ?>

                                        <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                                    </td>
                                    <td>
                                        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($product->status === 'active') { ?>
                                            <span class="badge bg-success-subtle text-success">Activo</span>
                                        <?php } elseif ($product->status === 'draft') { ?>
                                            <span class="badge bg-secondary-subtle text-secondary">Borrador</span>
                                        <?php } else { ?>
                                            <span class="badge bg-light text-muted"><?php echo e($product->status); ?></span>
                                        <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                                    </td>
                                    <td class="small text-muted"><?php echo e($product->store?->name ?? '—'); ?></td>
                                </tr>
                            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } $__env->popLoop();
$loop = $__env->getLastLoop();
if ($__empty_1) { ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">No se encontraron productos</td>
                                </tr>
                            <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($products->hasPages()) { ?>
                <div class="card-footer bg-white border-top">
                    <?php echo e($products->appends(request()->input())->links()); ?>

                </div>
            <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>

        </div>

    </div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.theme', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/developerts/Herd/system/modules/Remarketing/resources/views/products/index.blade.php ENDPATH**/ ?>