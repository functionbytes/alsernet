<?php
use Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys;
use Livewire\Mechanisms\ExtendBlade\ExtendBlade;

$__env->startSection('title', 'Automatizaciones'); ?>

<?php $__env->startSection('page_header'); ?>
    <?php echo $__env->make('core::components.card', ['title' => 'Automatizaciones'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

    <?php echo $__env->make('core::components.alerts', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <p class="text-muted mb-0 small">Flujos automáticos de email activados por eventos de tus clientes</p>
        </div>
        <a href="<?php echo e(route('remarketing.automations.create')); ?>" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i> Nueva automatización
        </a>
    </div>

    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($automations->isEmpty()) { ?>
        <div class="card text-center py-5">
            <div class="card-body">
                <i class="fas fa-bolt fa-3x text-muted mb-3"></i>
                <p class="text-muted mb-3">No hay automatizaciones configuradas todavía</p>
                <a href="<?php echo e(route('remarketing.automations.create')); ?>" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i> Crear primera automatización
                </a>
            </div>
        </div>
    <?php } else { ?>
        <div class="row g-3">
            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__currentLoopData = $automations;
        $__env->addLoop($__currentLoopData);
        foreach ($__currentLoopData as $automation) {
            $__env->incrementLoopIndices();
            $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="flex-grow-1 me-2">
                                    <h6 class="fw-bold mb-1"><?php echo e($automation->name); ?></h6>
                                    <?php
                                        $triggerLabels = [
                                            'welcome' => ['primary',   'Bienvenida'],
                                            'cart_abandoned' => ['warning',   'Carrito abandonado'],
                                            'post_purchase' => ['success',   'Post-compra'],
                                            'win_back' => ['danger',    'Recuperar cliente'],
                                        ];
            [$tColor, $tLabel] = $triggerLabels[$automation->trigger] ?? ['secondary', $automation->trigger];
            ?>
                                    <span class="badge bg-<?php echo e($tColor); ?>-subtle text-<?php echo e($tColor); ?>"><?php echo e($tLabel); ?></span>
                                </div>
                                <div class="form-check form-switch ms-2">
                                    <input class="form-check-input toggle-automation"
                                           type="checkbox"
                                           data-id="<?php echo e($automation->id); ?>"
                                           <?php echo e($automation->status === 'active' ? 'checked' : ''); ?>>
                                </div>
                            </div>

                            
                            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($automation->steps && $automation->steps->count() > 0) { ?>
                                <div class="mb-3">
                                    <small class="text-muted fw-semibold">Pasos:</small>
                                    <div class="d-flex flex-wrap gap-1 mt-1">
                                        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__currentLoopData = $automation->steps->take(3);
                                $__env->addLoop($__currentLoopData);
                                foreach ($__currentLoopData as $step) {
                                    $__env->incrementLoopIndices();
                                    $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
                                            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($step->type === 'wait') { ?>
                                                <span class="badge bg-light text-muted border">
                                                    <i class="fas fa-clock me-1"></i>
                                                    Esperar <?php echo e($step->config['hours'] ?? '?'); ?>h
                                                </span>
                                            <?php } elseif ($step->type === 'send_email') { ?>
                                                <span class="badge bg-primary-subtle text-primary">
                                                    <i class="fas fa-envelope me-1"></i>
                                                    Email
                                                </span>
                                            <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                                        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } $__env->popLoop();
                                $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>
                                        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($automation->steps->count() > 3) { ?>
                                            <span class="badge bg-light text-muted border">+<?php echo e($automation->steps->count() - 3); ?></span>
                                        <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                                    </div>
                                </div>
                            <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>

                            <div class="d-flex justify-content-between align-items-center text-muted small">
                                <span><i class="fas fa-play me-1"></i><?php echo e(number_format($automation->runs_total)); ?> ejecuciones</span>
                                <span class="<?php echo e($automation->status === 'active' ? 'text-success' : 'text-secondary'); ?>">
                                    <?php echo e($automation->status === 'active' ? 'Activa' : ucfirst($automation->status)); ?>

                                </span>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent border-top d-flex gap-2">
                            <a href="<?php echo e(route('remarketing.automations.edit', $automation)); ?>" class="btn btn-sm btn-outline-primary flex-grow-1">
                                <i class="fas fa-edit me-1"></i> Editar
                            </a>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-light" data-bs-toggle="dropdown">
                                    <i class="fas fa-ellipsis-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <button class="dropdown-item btn-delete-automation"
                                                data-id="<?php echo e($automation->id); ?>"
                                                data-name="<?php echo e($automation->name); ?>"
                                                data-url="<?php echo e(route('remarketing.automations.destroy', $automation)); ?>">
                                            Eliminar
                                        </button>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } $__env->popLoop();
        $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>
        </div>
    <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>

    
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title">Eliminar automatización</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">¿Eliminar la automatización <strong id="delete-name"></strong>? Se detendrán todas las ejecuciones activas.</p>
                </div>
                <div class="modal-footer flex-column">
                    <form id="deleteForm" method="POST" class="w-100">
                        <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                        <button type="submit" class="btn btn-danger w-100 mb-2">Eliminar automatización</button>
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

    $(document).on('change', '.toggle-automation', function () {
        var id = $(this).data('id');
        var active = $(this).is(':checked');
        $.ajax({
            url: '/api/remarketing/automations/' + id + '/toggle',
            method: 'POST',
            data: { active: active ? 1 : 0 },
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                toastr.success(res.message || (active ? 'Automatización activada' : 'Automatización pausada'));
            },
            error: function () {
                toastr.error('No se pudo cambiar el estado');
                // revert toggle
                $('[data-id="' + id + '"]').prop('checked', !active);
            }
        });
    });

    $(document).on('click', '.btn-delete-automation', function () {
        $('#delete-name').text($(this).data('name'));
        $('#deleteForm').attr('action', $(this).data('url'));
        $('#deleteModal').modal('show');
    });

});
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.theme', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/developerts/Herd/system/modules/Remarketing/resources/views/automations/index.blade.php ENDPATH**/ ?>