<?php
use Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys;
use Livewire\Mechanisms\ExtendBlade\ExtendBlade;

$__env->startSection('title', 'Remarketing — Dashboard'); ?>

<?php $__env->startSection('page_header'); ?>
    <?php echo $__env->make('core::components.card', ['title' => 'Dashboard Remarketing'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

    <?php echo $__env->make('core::components.alerts', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    
    <div class="row g-3 mb-4">

        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-primary-subtle d-flex align-items-center justify-content-center" style="width:48px;height:48px">
                            <i class="fas fa-store text-primary fs-5"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-1 small">Tiendas conectadas</p>
                            <h4 class="fw-bold mb-0"><?php echo e(number_format($stats['stores_count'] ?? 0)); ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-info-subtle d-flex align-items-center justify-content-center" style="width:48px;height:48px">
                            <i class="fas fa-users text-info fs-5"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-1 small">Clientes totales</p>
                            <h4 class="fw-bold mb-0"><?php echo e(number_format($stats['customers_count'] ?? 0)); ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-success-subtle d-flex align-items-center justify-content-center" style="width:48px;height:48px">
                            <i class="fas fa-check-circle text-success fs-5"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-1 small">Suscritos activos</p>
                            <h4 class="fw-bold mb-0"><?php echo e(number_format($stats['subscribed_count'] ?? 0)); ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-warning-subtle d-flex align-items-center justify-content-center" style="width:48px;height:48px">
                            <i class="fas fa-paper-plane text-warning fs-5"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-1 small">Enviados (30 días)</p>
                            <h4 class="fw-bold mb-0"><?php echo e(number_format($stats['campaigns_sent_30d'] ?? 0)); ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-success-subtle d-flex align-items-center justify-content-center" style="width:48px;height:48px">
                            <i class="fas fa-euro-sign text-success fs-5"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-1 small">Revenue (30 días)</p>
                            <h4 class="fw-bold mb-0"><?php echo e(number_format($stats['revenue_30d'] ?? 0, 2)); ?> €</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-danger-subtle d-flex align-items-center justify-content-center" style="width:48px;height:48px">
                            <i class="fas fa-ban text-danger fs-5"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-1 small">Bounce rate</p>
                            <h4 class="fw-bold mb-0"><?php echo e(number_format($stats['bounce_rate'] ?? 0, 1)); ?>%</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-primary-subtle d-flex align-items-center justify-content-center" style="width:48px;height:48px">
                            <i class="fas fa-envelope-open-text text-primary fs-5"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-1 small">Open rate</p>
                            <h4 class="fw-bold mb-0"><?php echo e(number_format($stats['open_rate'] ?? 0, 1)); ?>%</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    
    <div class="row g-3 mb-4">

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0">Revenue mensual</h6>
                    <select id="chart-range" class="form-select form-select-sm w-auto">
                        <option value="6">Últimos 6 meses</option>
                        <option value="12" selected>Últimos 12 meses</option>
                    </select>
                </div>
                <div class="card-body">
                    <div id="revenue-chart" style="height:300px"></div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header border-bottom">
                    <h6 class="fw-bold mb-0">Distribución por estado</h6>
                </div>
                <div class="card-body">
                    <div id="status-chart" style="height:300px"></div>
                </div>
            </div>
        </div>

    </div>

    
    <div class="row g-3">

        <div class="col-lg-6">
            <div class="card">
                <div class="card-header border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0">Mensajes recientes</h6>
                    <a href="<?php echo e(route('remarketing.campaigns.index')); ?>" class="btn btn-sm btn-outline-secondary">Ver todos</a>
                </div>
                <div class="card-body p-0">
                    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($recentMessages->isEmpty()) { ?>
                        <p class="text-muted text-center py-4 mb-0">Sin mensajes enviados recientemente</p>
                    <?php } else { ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Email</th>
                                        <th>Asunto</th>
                                        <th>Estado</th>
                                        <th>Enviado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__currentLoopData = $recentMessages;
                        $__env->addLoop($__currentLoopData);
                        foreach ($__currentLoopData as $msg) {
                            $__env->incrementLoopIndices();
                            $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
                                        <tr>
                                            <td class="small"><?php echo e($msg->email); ?></td>
                                            <td class="small text-truncate" style="max-width:140px"><?php echo e($msg->subject); ?></td>
                                            <td>
                                                <?php
                                            $statusColors = ['sent' => 'success', 'delivered' => 'primary', 'opened' => 'info', 'clicked' => 'warning', 'bounced' => 'danger', 'failed' => 'danger'];
                            $color = $statusColors[$msg->status] ?? 'secondary';
                            ?>
                                                <span class="badge bg-<?php echo e($color); ?>-subtle text-<?php echo e($color); ?>"><?php echo e($msg->status); ?></span>
                                            </td>
                                            <td class="small text-muted"><?php echo e($msg->sent_at?->diffForHumans() ?? '—'); ?></td>
                                        </tr>
                                    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } $__env->popLoop();
                        $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>
                                </tbody>
                            </table>
                        </div>
                    <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card">
                <div class="card-header border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0">Carritos abandonados recientes</h6>
                    <a href="<?php echo e(route('remarketing.carts.index')); ?>" class="btn btn-sm btn-outline-secondary">Ver todos</a>
                </div>
                <div class="card-body p-0">
                    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($recentCarts->isEmpty()) { ?>
                        <p class="text-muted text-center py-4 mb-0">Sin carritos abandonados recientes</p>
                    <?php } else { ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Email</th>
                                        <th>Total</th>
                                        <th>Estado</th>
                                        <th>Abandonado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__currentLoopData = $recentCarts;
                        $__env->addLoop($__currentLoopData);
                        foreach ($__currentLoopData as $cart) {
                            $__env->incrementLoopIndices();
                            $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
                                        <tr>
                                            <td class="small"><?php echo e($cart->email ?? '(anónimo)'); ?></td>
                                            <td class="small fw-semibold"><?php echo e(number_format($cart->total, 2)); ?> <?php echo e($cart->currency); ?></td>
                                            <td>
                                                <?php
                                            $cartColors = ['active' => 'warning', 'abandoned' => 'danger', 'recovered' => 'success', 'converted' => 'primary'];
                            $cColor = $cartColors[$cart->status] ?? 'secondary';
                            ?>
                                                <span class="badge bg-<?php echo e($cColor); ?>-subtle text-<?php echo e($cColor); ?>"><?php echo e($cart->status); ?></span>
                                            </td>
                                            <td class="small text-muted"><?php echo e($cart->abandoned_at?->diffForHumans() ?? '—'); ?></td>
                                        </tr>
                                    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } $__env->popLoop();
                        $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>
                                </tbody>
                            </table>
                        </div>
                    <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                </div>
            </div>
        </div>

    </div>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
$(document).ready(function () {
    let revenueChart = null;

    function loadRevenueChart(months) {
        $.getJSON('<?php echo e(route('remarketing.dashboard.chart-data')); ?>', { months: months }, function (res) {
            if (revenueChart) revenueChart.dispose();
            revenueChart = $('#revenue-chart').dxChart({
                dataSource: (res.labels || []).map(function (label, i) {
                    return { month: label, revenue: (res.datasets && res.datasets[0]) ? res.datasets[0].data[i] : 0 };
                }),
                series: [{
                    valueField: 'revenue',
                    argumentField: 'month',
                    name: 'Revenue',
                    type: 'bar',
                    color: '#b10100'
                }],
                argumentAxis: { argumentType: 'string' },
                valueAxis: { label: { format: { type: 'fixedPoint', precision: 0 } } },
                tooltip: { enabled: true, format: { type: 'fixedPoint', precision: 2 } },
                legend: { visible: false }
            }).dxChart('instance');
        }).fail(function () {
            // Placeholder when no data endpoint available yet
            $('#revenue-chart').dxChart({
                dataSource: [
                    { month: 'Ene', revenue: 0 }, { month: 'Feb', revenue: 0 },
                    { month: 'Mar', revenue: 0 }, { month: 'Abr', revenue: 0 }
                ],
                series: [{ valueField: 'revenue', argumentField: 'month', type: 'bar', color: '#b10100' }],
                legend: { visible: false }
            });
        });
    }

    loadRevenueChart(12);

    $('#chart-range').on('change', function () {
        loadRevenueChart($(this).val());
    });
});
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.theme', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/developerts/Herd/system/modules/Remarketing/resources/views/dashboard/index.blade.php ENDPATH**/ ?>