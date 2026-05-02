<?php
use Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys;
use Livewire\Mechanisms\ExtendBlade\ExtendBlade;

$__env->startSection('title', 'Carritos abandonados'); ?>

<?php $__env->startSection('page_header'); ?>
    <?php echo $__env->make('core::components.card', ['title' => 'Carritos abandonados'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

    <?php echo $__env->make('core::components.alerts', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <div class="widget-content searchable-container list">

        <div class="card">

            <div class="card-header p-4 border-bottom">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Carritos abandonados</h5>
                        <p class="small mb-0 text-muted">Seguimiento de carritos activos y recuperados</p>
                    </div>
                    <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#filterModal">
                        <i class="fas fa-filter me-1"></i> Filtros
                        <?php
                            $activeFilters = collect([request('store_id'), request('status'), request('date_from'), request('date_to')])->filter()->count();
?>
                        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($activeFilters > 0) { ?>
                            <span class="badge bg-primary ms-1"><?php echo e($activeFilters); ?></span>
                        <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                    </button>
                </div>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Tienda</th>
                                <th>Email</th>
                                <th>Total</th>
                                <th>Items</th>
                                <th>Estado</th>
                                <th>Abandonado</th>
                                <th>Recuperado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__empty_1 = true;
$__currentLoopData = $carts;
$__env->addLoop($__currentLoopData);
foreach ($__currentLoopData as $cart) {
    $__env->incrementLoopIndices();
    $loop = $__env->getLastLoop();
    $__empty_1 = false; ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
                                <tr>
                                    <td class="small"><?php echo e($cart->store?->name ?? '—'); ?></td>
                                    <td class="small"><?php echo e($cart->email ?? '(anónimo)'); ?></td>
                                    <td class="small fw-semibold"><?php echo e(number_format($cart->total, 2)); ?> <?php echo e($cart->currency); ?></td>
                                    <td class="small text-center">
                                        <?php echo e(is_array($cart->items) ? count($cart->items) : 0); ?>

                                    </td>
                                    <td>
                                        <?php
                    $cartColors = [
                        'active' => ['warning', 'Activo'],
                        'abandoned' => ['danger',  'Abandonado'],
                        'recovered' => ['success', 'Recuperado'],
                        'converted' => ['primary', 'Convertido'],
                    ];
    [$color, $label] = $cartColors[$cart->status] ?? ['secondary', $cart->status];
    ?>
                                        <span class="badge bg-<?php echo e($color); ?>-subtle text-<?php echo e($color); ?>"><?php echo e($label); ?></span>
                                    </td>
                                    <td class="small text-muted"><?php echo e($cart->abandoned_at?->format('d/m/Y H:i') ?? '—'); ?></td>
                                    <td class="small text-muted"><?php echo e($cart->recovered_at?->format('d/m/Y H:i') ?? '—'); ?></td>
                                </tr>
                            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } $__env->popLoop();
$loop = $__env->getLastLoop();
if ($__empty_1) { ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">No se encontraron carritos</td>
                                </tr>
                            <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($carts->hasPages()) { ?>
                <div class="card-footer bg-white border-top">
                    <?php echo e($carts->appends(request()->input())->links()); ?>

                </div>
            <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>

        </div>

    </div>

    
    <div class="modal fade" id="filterModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title">Filtros de carritos</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="GET" action="<?php echo e(route('remarketing.carts.index')); ?>">
                    <div class="modal-body">
                        <div class="row g-3">

                            <div class="col-12">
                                <label class="form-label">Tienda</label>
                                <select name="store_id" class="form-select">
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
                            </div>

                            <div class="col-12">
                                <label class="form-label">Estado</label>
                                <select name="status" class="form-select">
                                    <option value="">Todos</option>
                                    <option value="active"    <?php echo e(request('status') === 'active' ? 'selected' : ''); ?>>Activo</option>
                                    <option value="abandoned" <?php echo e(request('status') === 'abandoned' ? 'selected' : ''); ?>>Abandonado</option>
                                    <option value="recovered" <?php echo e(request('status') === 'recovered' ? 'selected' : ''); ?>>Recuperado</option>
                                    <option value="converted" <?php echo e(request('status') === 'converted' ? 'selected' : ''); ?>>Convertido</option>
                                </select>
                            </div>

                            <div class="col-6">
                                <label class="form-label">Fecha desde</label>
                                <input type="date" name="date_from" class="form-control" value="<?php echo e(request('date_from')); ?>">
                            </div>
                            <div class="col-6">
                                <label class="form-label">Fecha hasta</label>
                                <input type="date" name="date_to" class="form-control" value="<?php echo e(request('date_to')); ?>">
                            </div>

                        </div>
                    </div>
                    <div class="modal-footer flex-column">
                        <button type="submit" class="btn btn-primary w-100 mb-2">Aplicar filtros</button>
                        <a href="<?php echo e(route('remarketing.carts.index')); ?>" class="btn btn-light w-100">Limpiar filtros</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.theme', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/developerts/Herd/system/modules/Remarketing/resources/views/carts/index.blade.php ENDPATH**/ ?>