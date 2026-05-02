<?php
use Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys;
use Livewire\Mechanisms\ExtendBlade\ExtendBlade;

$__env->startSection('title', 'Campañas'); ?>

<?php $__env->startSection('page_header'); ?>
    <?php echo $__env->make('core::components.card', ['title' => 'Campañas'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

    <?php echo $__env->make('core::components.alerts', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <div class="widget-content searchable-container list">

        <div class="card">

            <div class="card-header p-4 border-bottom">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Campañas de email</h5>
                        <p class="small mb-0 text-muted">Envíos masivos y campañas programadas</p>
                    </div>
                    <a href="<?php echo e(route('remarketing.campaigns.create')); ?>" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> Nueva campaña
                    </a>
                </div>
            </div>

            <div class="card-body p-0">
                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($campaigns->isEmpty()) { ?>
                    <div class="text-center py-5">
                        <i class="fas fa-bullhorn fa-3x text-muted mb-3"></i>
                        <p class="text-muted mb-3">No hay campañas creadas todavía</p>
                        <a href="<?php echo e(route('remarketing.campaigns.create')); ?>" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> Crear primera campaña
                        </a>
                    </div>
                <?php } else { ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Nombre</th>
                                    <th>Asunto</th>
                                    <th>Estado</th>
                                    <th>Programado</th>
                                    <th class="text-center">Enviados</th>
                                    <th class="text-center">Open rate</th>
                                    <th class="text-center">CTR</th>
                                    <th>Revenue</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__currentLoopData = $campaigns;
                    $__env->addLoop($__currentLoopData);
                    foreach ($__currentLoopData as $campaign) {
                        $__env->incrementLoopIndices();
                        $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold small"><?php echo e($campaign->name); ?></div>
                                        </td>
                                        <td class="small text-truncate" style="max-width:200px"><?php echo e($campaign->subject); ?></td>
                                        <td>
                                            <?php
                                        $statusColors = [
                                            'draft' => ['secondary', 'Borrador'],
                                            'scheduled' => ['info',      'Programado'],
                                            'sending' => ['warning',   'Enviando'],
                                            'sent' => ['success',   'Enviado'],
                                            'cancelled' => ['danger',    'Cancelado'],
                                            'paused' => ['secondary', 'Pausado'],
                                        ];
                        [$color, $label] = $statusColors[$campaign->status] ?? ['secondary', $campaign->status];
                        ?>
                                            <span class="badge bg-<?php echo e($color); ?>-subtle text-<?php echo e($color); ?>"><?php echo e($label); ?></span>
                                        </td>
                                        <td class="small text-muted">
                                            <?php echo e($campaign->scheduled_at?->format('d/m/Y H:i') ?? '—'); ?>

                                        </td>
                                        <td class="text-center small"><?php echo e(number_format($campaign->sent)); ?></td>
                                        <td class="text-center small">
                                            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($campaign->sent > 0) { ?>
                                                <?php echo e(number_format(($campaign->opened / $campaign->sent) * 100, 1)); ?>%
                                            <?php } else { ?>
                                                —
                                            <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                                        </td>
                                        <td class="text-center small">
                                            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($campaign->sent > 0) { ?>
                                                <?php echo e(number_format(($campaign->clicked / $campaign->sent) * 100, 1)); ?>%
                                            <?php } else { ?>
                                                —
                                            <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                                        </td>
                                        <td class="small fw-semibold">
                                            <?php echo e($campaign->revenue > 0 ? number_format($campaign->revenue, 2).' €' : '—'); ?>

                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-light" data-bs-toggle="dropdown">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (in_array($campaign->status, ['draft', 'scheduled'])) { ?>
                                                        <li>
                                                            <a class="dropdown-item" href="<?php echo e(route('remarketing.campaigns.edit', $campaign)); ?>">Editar</a>
                                                        </li>
                                                    <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                                                    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($campaign->status === 'draft') { ?>
                                                        <li>
                                                            <button class="dropdown-item btn-schedule" data-id="<?php echo e($campaign->id); ?>">Programar</button>
                                                        </li>
                                                    <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                                                    <li>
                                                        <button class="dropdown-item btn-send-test" data-id="<?php echo e($campaign->id); ?>">Enviar prueba</button>
                                                    </li>
                                                    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (in_array($campaign->status, ['scheduled', 'sending'])) { ?>
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <button class="dropdown-item btn-cancel-campaign" data-id="<?php echo e($campaign->id); ?>" data-name="<?php echo e($campaign->name); ?>">Cancelar envío</button>
                                                        </li>
                                                    <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                                                    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($campaign->status === 'draft') { ?>
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <button class="dropdown-item btn-delete"
                                                                    data-id="<?php echo e($campaign->id); ?>"
                                                                    data-name="<?php echo e($campaign->name); ?>"
                                                                    data-url="<?php echo e(route('remarketing.campaigns.destroy', $campaign)); ?>">
                                                                Eliminar
                                                            </button>
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
                <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
            </div>

            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($campaigns->hasPages()) { ?>
                <div class="card-footer bg-white border-top">
                    <?php echo e($campaigns->links()); ?>

                </div>
            <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>

        </div>

    </div>

    
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title">Eliminar campaña</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">¿Eliminar la campaña <strong id="delete-name"></strong>? Esta acción no se puede deshacer.</p>
                </div>
                <div class="modal-footer flex-column">
                    <form id="deleteForm" method="POST" class="w-100">
                        <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                        <button type="submit" class="btn btn-danger w-100 mb-2">Eliminar campaña</button>
                    </form>
                    <button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    
    <div class="modal fade" id="sendTestModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title">Enviar email de prueba</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label">Email de destino</label>
                    <input type="email" id="test-email" class="form-control" placeholder="test@ejemplo.com">
                </div>
                <div class="modal-footer flex-column">
                    <button type="button" id="btn-confirm-test" class="btn btn-primary w-100 mb-2">Enviar prueba</button>
                    <button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
$(document).ready(function () {
    var currentCampaignId = null;

    $(document).on('click', '.btn-delete', function () {
        $('#delete-name').text($(this).data('name'));
        $('#deleteForm').attr('action', $(this).data('url'));
        $('#deleteModal').modal('show');
    });

    $(document).on('click', '.btn-send-test', function () {
        currentCampaignId = $(this).data('id');
        $('#test-email').val('');
        $('#sendTestModal').modal('show');
    });

    $('#btn-confirm-test').on('click', function () {
        var email = $('#test-email').val();
        if (!email) { toastr.warning('Introduce un email válido'); return; }
        $.ajax({
            url: '/api/remarketing/campaigns/' + currentCampaignId + '/send-test',
            method: 'POST',
            data: { email: email },
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                toastr.success(res.message || 'Email de prueba enviado');
                $('#sendTestModal').modal('hide');
            },
            error: function () { toastr.error('Error al enviar el email de prueba'); }
        });
    });

    $(document).on('click', '.btn-cancel-campaign', function () {
        if (!confirm('¿Cancelar el envío de la campaña "' + $(this).data('name') + '"?')) return;
        $.ajax({
            url: '/api/remarketing/campaigns/' + $(this).data('id') + '/cancel',
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) { toastr.success(res.message || 'Campaña cancelada'); location.reload(); },
            error: function () { toastr.error('Error al cancelar la campaña'); }
        });
    });
});
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.theme', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/developerts/Herd/system/modules/Remarketing/resources/views/campaigns/index.blade.php ENDPATH**/ ?>