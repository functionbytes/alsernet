<?php
use Livewire\Mechanisms\ExtendBlade\ExtendBlade;

$__env->startSection('title', 'Editar tienda'); ?>

<?php $__env->startSection('page_header'); ?>
    <?php echo $__env->make('core::components.card', ['title' => 'Editar tienda'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

    <?php echo $__env->make('core::components.alerts', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <div class="row g-3">

        <div class="col-12 col-lg-8">
            <div class="card">
                <form action="<?php echo e(route('remarketing.stores.update', $store)); ?>" method="POST">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('PUT'); ?>
                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold"><?php echo e($store->name); ?></h5>
                        <small class="text-muted"><?php echo e(ucfirst($store->platform)); ?> — <?php echo e($store->domain); ?></small>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">

                            <div class="col-12">
                                <label class="form-label">Nombre de la tienda <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) {
    if (isset($message)) {
        $__messageOriginal = $message;
    }
    $message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
    if (isset($__messageOriginal)) {
        $message = $__messageOriginal;
    }
}
unset($__errorArgs, $__bag); ?>"
                                       value="<?php echo e(old('name', $store->name)); ?>" required>
                                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) {
    if (isset($message)) {
        $__messageOriginal = $message;
    }
    $message = $__bag->first($__errorArgs[0]); ?>
                                    <div class="invalid-feedback"><?php echo e($message); ?></div>
                                <?php unset($message);
    if (isset($__messageOriginal)) {
        $message = $__messageOriginal;
    }
}
unset($__errorArgs, $__bag); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Configuración avanzada (JSON)</label>
                                <textarea name="settings_raw" rows="8"
                                          class="form-control font-monospace <?php $__errorArgs = ['settings_raw'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) {
    if (isset($message)) {
        $__messageOriginal = $message;
    }
    $message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
    if (isset($__messageOriginal)) {
        $message = $__messageOriginal;
    }
}
unset($__errorArgs, $__bag); ?>"
                                          placeholder='{"webhook_secret": "...", "sync_interval_minutes": 30}'><?php echo e(old('settings_raw', json_encode($store->settings, JSON_PRETTY_PRINT))); ?></textarea>
                                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php $__errorArgs = ['settings_raw'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) {
    if (isset($message)) {
        $__messageOriginal = $message;
    }
    $message = $__bag->first($__errorArgs[0]); ?>
                                    <div class="invalid-feedback"><?php echo e($message); ?></div>
                                <?php unset($message);
    if (isset($__messageOriginal)) {
        $message = $__messageOriginal;
    }
}
unset($__errorArgs, $__bag); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                                <div class="form-text">Edita la configuración en formato JSON. Los cambios se aplican en la siguiente sincronización.</div>
                            </div>

                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-2">
                            <i class="fas fa-save me-1"></i> Guardar cambios
                        </button>
                        <a href="<?php echo e(route('remarketing.stores.index')); ?>" class="btn btn-light w-100">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">Información de la tienda</h6>
                    <dl class="row small mb-0">
                        <dt class="col-5 text-muted">Plataforma</dt>
                        <dd class="col-7"><?php echo e(ucfirst($store->platform)); ?></dd>
                        <dt class="col-5 text-muted">Dominio</dt>
                        <dd class="col-7"><?php echo e($store->domain); ?></dd>
                        <dt class="col-5 text-muted">Estado</dt>
                        <dd class="col-7">
                            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($store->status === 'active') { ?>
                                <span class="badge bg-success-subtle text-success">Activa</span>
                            <?php } else { ?>
                                <span class="badge bg-secondary-subtle text-secondary"><?php echo e($store->status); ?></span>
                            <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                        </dd>
                        <dt class="col-5 text-muted">Última sync</dt>
                        <dd class="col-7"><?php echo e($store->last_synced_at?->diffForHumans() ?? 'Nunca'); ?></dd>
                        <dt class="col-5 text-muted">Creada</dt>
                        <dd class="col-7"><?php echo e($store->created_at->format('d/m/Y')); ?></dd>
                    </dl>
                </div>
            </div>
        </div>

    </div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.theme', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/developerts/Herd/system/modules/Remarketing/resources/views/stores/edit.blade.php ENDPATH**/ ?>