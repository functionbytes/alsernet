<?php
use Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys;
use Livewire\Mechanisms\ExtendBlade\ExtendBlade;

$__env->startSection('title', 'Plantillas de email'); ?>

<?php $__env->startSection('page_header'); ?>
    <?php echo $__env->make('core::components.card', ['title' => 'Plantillas de email'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

    <?php echo $__env->make('core::components.alerts', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <p class="text-muted mb-0 small">Diseños de email reutilizables para campañas y automatizaciones</p>
        <a href="<?php echo e(route('remarketing.templates.create')); ?>" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i> Nueva plantilla
        </a>
    </div>

    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($templates->isEmpty()) { ?>
        <div class="card text-center py-5">
            <div class="card-body">
                <i class="fas fa-file-code fa-3x text-muted mb-3"></i>
                <p class="text-muted mb-3">No hay plantillas creadas todavía</p>
                <a href="<?php echo e(route('remarketing.templates.create')); ?>" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i> Crear primera plantilla
                </a>
            </div>
        </div>
    <?php } else { ?>
        <div class="row g-3">
            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__currentLoopData = $templates;
        $__env->addLoop($__currentLoopData);
        foreach ($__currentLoopData as $template) {
            $__env->incrementLoopIndices();
            $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
                <div class="col-12 col-sm-6 col-xl-4">
                    <div class="card h-100">

                        
                        <div class="bg-light d-flex align-items-center justify-content-center border-bottom"
                             style="height:160px;overflow:hidden">
                            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($template->thumbnail_url) { ?>
                                <img src="<?php echo e($template->thumbnail_url); ?>" alt="<?php echo e($template->name); ?>"
                                     width="100%" loading="lazy"
                                     style="object-fit:cover;height:160px">
                            <?php } else { ?>
                                <i class="fas fa-image fa-3x text-muted"></i>
                            <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                        </div>

                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <h6 class="fw-bold mb-1 me-2"><?php echo e($template->name); ?></h6>
                                <?php
                                    $typeColors = ['campaign' => 'primary', 'automation' => 'success', 'transactional' => 'info'];
            $tColor = $typeColors[$template->type] ?? 'secondary';
            ?>
                                <span class="badge bg-<?php echo e($tColor); ?>-subtle text-<?php echo e($tColor); ?> flex-shrink-0"><?php echo e(ucfirst($template->type)); ?></span>
                            </div>
                            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($template->subject) { ?>
                                <p class="small text-muted mb-0 mt-1"><?php echo e(Str::limit($template->subject, 60)); ?></p>
                            <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                            <p class="small text-muted mb-0 mt-1"><?php echo e($template->created_at->format('d/m/Y')); ?></p>
                        </div>

                        <div class="card-footer bg-transparent border-top d-flex gap-2">
                            <a href="<?php echo e(route('remarketing.templates.edit', $template)); ?>"
                               class="btn btn-sm btn-outline-primary flex-grow-1">
                                <i class="fas fa-edit me-1"></i> Editar
                            </a>
                            <a href="<?php echo e(route('remarketing.templates.preview', $template)); ?>"
                               target="_blank" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-eye"></i>
                            </a>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-light" data-bs-toggle="dropdown">
                                    <i class="fas fa-ellipsis-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <button class="dropdown-item btn-duplicate" data-id="<?php echo e($template->id); ?>">Duplicar</button>
                                    </li>
                                    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (! $template->is_global) { ?>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <button class="dropdown-item btn-delete"
                                                    data-id="<?php echo e($template->id); ?>"
                                                    data-name="<?php echo e($template->name); ?>"
                                                    data-url="<?php echo e(route('remarketing.templates.destroy', $template)); ?>">
                                                Eliminar
                                            </button>
                                        </li>
                                    <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                                </ul>
                            </div>
                        </div>

                    </div>
                </div>
            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } $__env->popLoop();
        $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>
        </div>

        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($templates->hasPages()) { ?>
            <div class="mt-3">
                <?php echo e($templates->links()); ?>

            </div>
        <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
    <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>

    
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title">Eliminar plantilla</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">¿Eliminar la plantilla <strong id="delete-name"></strong>? Las campañas que la usen perderán su plantilla.</p>
                </div>
                <div class="modal-footer flex-column">
                    <form id="deleteForm" method="POST" class="w-100">
                        <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                        <button type="submit" class="btn btn-danger w-100 mb-2">Eliminar plantilla</button>
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
        $('#delete-name').text($(this).data('name'));
        $('#deleteForm').attr('action', $(this).data('url'));
        $('#deleteModal').modal('show');
    });

    $(document).on('click', '.btn-duplicate', function () {
        var id = $(this).data('id');
        $.ajax({
            url: '/api/remarketing/templates/' + id + '/duplicate',
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                toastr.success(res.message || 'Plantilla duplicada');
                location.reload();
            },
            error: function () { toastr.error('Error al duplicar la plantilla'); }
        });
    });

});
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.theme', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/developerts/Herd/system/modules/Remarketing/resources/views/templates/index.blade.php ENDPATH**/ ?>