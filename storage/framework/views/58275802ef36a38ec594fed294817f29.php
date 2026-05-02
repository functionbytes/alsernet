<?php
use Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys;
use Livewire\Mechanisms\ExtendBlade\ExtendBlade;

$__env->startSection('title', 'Clientes'); ?>

<?php $__env->startSection('page_header'); ?>
    <?php echo $__env->make('core::components.card', ['title' => 'Clientes'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

    <?php echo $__env->make('core::components.alerts', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <div class="widget-content searchable-container list">

        <div class="card">

            <div class="card-header p-4 border-bottom">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Clientes</h5>
                        <p class="small mb-0 text-muted">Perfiles unificados sincronizados desde tus tiendas</p>
                    </div>
                    <button type="button" class="btn btn-outline-secondary btn-filter" data-bs-toggle="modal" data-bs-target="#filterModal">
                        <i class="fas fa-filter me-1"></i> Filtros
                        <?php
                            $activeFilters = collect([request('store_id'), request('status'), request('country'), request('search')])->filter()->count();
?>
                        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($activeFilters > 0) { ?>
                            <span class="badge bg-primary ms-1"><?php echo e($activeFilters); ?></span>
                        <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                    </button>
                </div>
            </div>

            
            <div class="card-body border-bottom py-3">
                <form method="GET" action="<?php echo e(route('remarketing.customers.index')); ?>" id="searchForm">
                    <div class="d-flex gap-2">
                        <input type="text" name="search" class="form-control" placeholder="Buscar por email o nombre..."
                               value="<?php echo e(request('search')); ?>">
                        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__currentLoopData = request()->except(['search', 'page']);
$__env->addLoop($__currentLoopData);
foreach ($__currentLoopData as $key => $val) {
    $__env->incrementLoopIndices();
    $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
                            <input type="hidden" name="<?php echo e($key); ?>" value="<?php echo e($val); ?>">
                        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } $__env->popLoop();
$loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i>
                        </button>
                        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (request()->hasAny(['search', 'store_id', 'status', 'country'])) { ?>
                            <a href="<?php echo e(route('remarketing.customers.index')); ?>" class="btn btn-light">
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
                                <th>Email</th>
                                <th>Nombre</th>
                                <th>País</th>
                                <th>Estado</th>
                                <th>RFM</th>
                                <th>Última compra</th>
                                <th>Total gastado</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__empty_1 = true;
$__currentLoopData = $customers;
$__env->addLoop($__currentLoopData);
foreach ($__currentLoopData as $customer) {
    $__env->incrementLoopIndices();
    $loop = $__env->getLastLoop();
    $__empty_1 = false; ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
                                <tr>
                                    <td>
                                        <a href="<?php echo e(route('remarketing.customers.show', $customer)); ?>" class="text-decoration-none fw-semibold">
                                            <?php echo e($customer->email); ?>

                                        </a>
                                    </td>
                                    <td class="small"><?php echo e(trim($customer->first_name.' '.$customer->last_name) ?: '—'); ?></td>
                                    <td class="small"><?php echo e($customer->country ?: '—'); ?></td>
                                    <td>
                                        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($customer->status === 'subscribed') { ?>
                                            <span class="badge bg-success-subtle text-success">Suscrito</span>
                                        <?php } elseif ($customer->status === 'unsubscribed') { ?>
                                            <span class="badge bg-secondary-subtle text-secondary">Dado de baja</span>
                                        <?php } elseif ($customer->status === 'bounced') { ?>
                                            <span class="badge bg-danger-subtle text-danger">Bounce</span>
                                        <?php } else { ?>
                                            <span class="badge bg-light text-muted"><?php echo e($customer->status); ?></span>
                                        <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                                    </td>
                                    <td>
                                        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($customer->rfm_score) { ?>
                                            <div class="d-flex gap-1">
                                                <?php
                            $rfm = $customer->rfm_score;
                                            $segments = [
                                                'champion' => ['Champions', 'success'],
                                                'loyal' => ['Fiel', 'primary'],
                                                'at_risk' => ['En riesgo', 'warning'],
                                                'lost' => ['Perdido', 'danger'],
                                            ];
                                            [$label, $color] = $segments[$rfm] ?? ['—', 'secondary'];
                                            ?>
                                                <span class="badge bg-<?php echo e($color); ?>-subtle text-<?php echo e($color); ?>"><?php echo e($label); ?></span>
                                            </div>
                                        <?php } else { ?>
                                            <span class="text-muted small">—</span>
                                        <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                                    </td>
                                    <td class="small text-muted"><?php echo e($customer->last_order_at?->format('d/m/Y') ?? '—'); ?></td>
                                    <td class="small fw-semibold"><?php echo e($customer->total_spent ? number_format($customer->total_spent, 2).' €' : '—'); ?></td>
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-light" data-bs-toggle="dropdown">
                                                <i class="fas fa-ellipsis-vertical"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <a class="dropdown-item" href="<?php echo e(route('remarketing.customers.show', $customer)); ?>">Ver perfil</a>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } $__env->popLoop();
$loop = $__env->getLastLoop();
if ($__empty_1) { ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">No se encontraron clientes</td>
                                </tr>
                            <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($customers->hasPages()) { ?>
                <div class="card-footer bg-white border-top">
                    <?php echo e($customers->appends(request()->input())->links()); ?>

                </div>
            <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>

        </div>

    </div>

    
    <div class="modal fade" id="filterModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title">Filtros de clientes</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="GET" action="<?php echo e(route('remarketing.customers.index')); ?>">
                    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (request('search')) { ?>
                        <input type="hidden" name="search" value="<?php echo e(request('search')); ?>">
                    <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                    <div class="modal-body">
                        <div class="row g-3">

                            <div class="col-12">
                                <label class="form-label">Tienda</label>
                                <select name="store_id" class="form-select">
                                    <option value="">Todas las tiendas</option>
                                    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__currentLoopData = $stores;
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
                                    <option value="subscribed" <?php echo e(request('status') === 'subscribed' ? 'selected' : ''); ?>>Suscrito</option>
                                    <option value="unsubscribed" <?php echo e(request('status') === 'unsubscribed' ? 'selected' : ''); ?>>Dado de baja</option>
                                    <option value="bounced" <?php echo e(request('status') === 'bounced' ? 'selected' : ''); ?>>Bounce</option>
                                </select>
                            </div>

                            <div class="col-12">
                                <label class="form-label">País</label>
                                <input type="text" name="country" class="form-control" placeholder="ES, MX, US..."
                                       value="<?php echo e(request('country')); ?>">
                            </div>

                            <div class="col-12">
                                <label class="form-label">Segmento RFM</label>
                                <select name="rfm_score" class="form-select">
                                    <option value="">Todos</option>
                                    <option value="champion" <?php echo e(request('rfm_score') === 'champion' ? 'selected' : ''); ?>>Champions</option>
                                    <option value="loyal" <?php echo e(request('rfm_score') === 'loyal' ? 'selected' : ''); ?>>Fieles</option>
                                    <option value="at_risk" <?php echo e(request('rfm_score') === 'at_risk' ? 'selected' : ''); ?>>En riesgo</option>
                                    <option value="lost" <?php echo e(request('rfm_score') === 'lost' ? 'selected' : ''); ?>>Perdidos</option>
                                </select>
                            </div>

                        </div>
                    </div>
                    <div class="modal-footer flex-column">
                        <button type="submit" class="btn btn-primary w-100 mb-2">Aplicar filtros</button>
                        <a href="<?php echo e(route('remarketing.customers.index')); ?>" class="btn btn-light w-100">Limpiar filtros</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.theme', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/developerts/Herd/system/modules/Remarketing/resources/views/customers/index.blade.php ENDPATH**/ ?>