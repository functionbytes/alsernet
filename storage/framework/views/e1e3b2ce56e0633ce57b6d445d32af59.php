<?php
use Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys;
use Livewire\Mechanisms\ExtendBlade\ExtendBlade;

$__env->startSection('title', 'Pagos'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold"><?php echo e(__('Pagos')); ?></h4>
        <a href="<?php echo e(route('ecommerce-payment.payments.export', request()->only(['status', 'search', 'date_from', 'date_to']))); ?>" class="btn btn-success">
            <i class="fas fa-file-excel me-2"></i><?php echo e(__('Exportar')); ?>

        </a>
    </div>

    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (session('success')) { ?>
        <div class="alert alert-success"><?php echo e(session('success')); ?></div>
    <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>

    <div class="card">
        <div class="card-body">
            <form method="GET" class="row g-3 mb-4">
                <div class="col-md-3">
                    <input type="text" name="search" class="form-control" placeholder="Buscar por referencia o orden..." value="<?php echo e(request('search')); ?>">
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">Todos los estados</option>
                        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__currentLoopData = $statuses;
$__env->addLoop($__currentLoopData);
foreach ($__currentLoopData as $status) {
    $__env->incrementLoopIndices();
    $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
                            <option value="<?php echo e($status->value); ?>" <?php echo e(request('status') == $status->value ? 'selected' : ''); ?>>
                                <?php echo e($status->label()); ?>

                            </option>
                        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } $__env->popLoop();
$loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_from" class="form-control" placeholder="Desde" value="<?php echo e(request('date_from')); ?>">
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_to" class="form-control" placeholder="Hasta" value="<?php echo e(request('date_to')); ?>">
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (request()->hasAny(['search', 'status', 'date_from', 'date_to'])) { ?>
                <div class="col-md-2">
                    <a href="<?php echo e(route('ecommerce-payment.payments.index')); ?>" class="btn btn-outline-secondary w-100">
                        <i class="fas fa-times me-2"></i><?php echo e(__('Limpiar')); ?>

                    </a>
                </div>
                <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
            </form>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th><?php echo e(__('ID')); ?></th>
                            <th><?php echo e(__('Referencia')); ?></th>
                            <th><?php echo e(__('Orden')); ?></th>
                            <th><?php echo e(__('Monto')); ?></th>
                            <th><?php echo e(__('Canal')); ?></th>
                            <th><?php echo e(__('Estado')); ?></th>
                            <th><?php echo e(__('Fecha')); ?></th>
                            <th class="text-end"><?php echo e(__('Acciones')); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__empty_1 = true;
$__currentLoopData = $payments;
$__env->addLoop($__currentLoopData);
foreach ($__currentLoopData as $payment) {
    $__env->incrementLoopIndices();
    $loop = $__env->getLastLoop();
    $__empty_1 = false; ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
                            <tr>
                                <td>#<?php echo e($payment->id); ?></td>
                                <td><code><?php echo e($payment->charge_id); ?></code></td>
                                <td>
                                    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($payment->order) { ?>
                                        <a href="<?php echo e(route('ecommerce.orders.show', $payment->order)); ?>">
                                            <?php echo e($payment->order->code); ?>

                                        </a>
                                    <?php } else { ?>
                                        <span class="text-muted">-</span>
                                    <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                                </td>
                                <td>
                                    <strong><?php echo e(number_format($payment->amount, 2)); ?></strong>
                                    <small class="text-muted"><?php echo e($payment->currency); ?></small>
                                </td>
                                <td><span class="badge bg-info"><?php echo e($payment->payment_channel); ?></span></td>
                                <td>
                                    <span class="<?php echo e($payment->status->badgeClass()); ?>">
                                        <?php echo e($payment->status->label()); ?>

                                    </span>
                                </td>
                                <td><?php echo e($payment->created_at->format('d/m/Y H:i')); ?></td>
                                <td class="text-end">
                                    <a href="<?php echo e(route('ecommerce-payment.payments.show', $payment)); ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } $__env->popLoop();
$loop = $__env->getLastLoop();
if ($__empty_1) { ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-2x mb-2"></i>
                                    <p class="mb-0"><?php echo e(__('No hay pagos registrados.')); ?></p>
                                </td>
                            </tr>
                        <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-end">
                <?php echo e($payments->withQueryString()->links()); ?>

            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('template::layouts.default', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/developerts/Herd/system/modules/EcommercePayment/resources/views/admin/payments/index.blade.php ENDPATH**/ ?>