<?php
use Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys;
use Livewire\Mechanisms\ExtendBlade\ExtendBlade;

$__env->startSection('title', 'Plantillas de email'); ?>

<?php $__env->startSection('content'); ?>

    <?php echo $__env->make('core::components.card', ['title' => 'Plantillas de email'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <div class="widget-content searchable-container list">

        <?php echo $__env->make('core::components.alerts', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

        <div class="card">

            
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Plantillas de email</h5>
                        <p class="small mb-0 text-muted">Gestiona plantillas de email para documentos, órdenes y notificaciones</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="<?php echo e(route('mailers.components.index')); ?>" class="btn btn-outline-secondary">
                            Ver componentes
                        </a>
                        <a href="<?php echo e(route('mailers.templates.create')); ?>" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> Nuevo template
                        </a>
                    </div>
                </div>
            </div>

            
            <div class="card-body border-bottom">
                <div class="alert alert-info border-0 mb-0">
                    <div class="d-flex align-items-center justify-content-between gap-3">
                        <div class="d-flex align-items-start">
                            <i class="fas fa-circle-info fs-5 me-3 mt-1"></i>
                            <div>
                                <h6 class="fw-bold mb-1">¿Necesitas editar el Header o Footer?</h6>
                                <p class="mb-0 small">Los componentes como header, footer y otros elementos reutilizables se gestionan por separado. Edítalos una vez y se aplicarán automáticamente a todas las plantillas.</p>
                            </div>
                        </div>
                        <a href="<?php echo e(route('mailers.components.index')); ?>" class="btn btn-info btn-sm flex-shrink-0">
                            <i class="fas fa-arrow-right me-1"></i> Ver
                        </a>
                    </div>
                </div>
            </div>

            
            <div class="card-body border-bottom">
                <form method="GET" action="<?php echo e(route('mailers.templates.index')); ?>">
                    <div class="d-flex flex-column flex-lg-row gap-3 align-items-stretch">
                        <div class="flex-fill">
                            <div class="input-group h-100">
                                <span class="input-group-text bg-white border-end-1">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="search" name="search" class="form-control border-start-0 ps-0"
                                       placeholder="Buscar por nombre, key o descripción..."
                                       value="<?php echo e($search); ?>">
                            </div>
                        </div>
                        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (! empty($modules)) { ?>
                            <div class="flex-shrink-0" style="min-width: 180px;">
                                <select name="module" class="form-select select2 h-100">
                                    <option value="">Todos los módulos</option>
                                    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__currentLoopData = $modules;
                            $__env->addLoop($__currentLoopData);
                            foreach ($__currentLoopData as $mod) {
                                $__env->incrementLoopIndices();
                                $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
                                        <option value="<?php echo e($mod); ?>" <?php echo e($module === $mod ? 'selected' : ''); ?>><?php echo e(ucfirst($mod)); ?></option>
                                    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } $__env->popLoop();
                            $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>
                                </select>
                            </div>
                        <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                        <div class="d-flex gap-2 flex-shrink-0">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-1"></i>
                            </button>
                            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($search || $module) { ?>
                                <a href="<?php echo e(route('mailers.templates.index')); ?>" class="btn btn-outline-secondary" title="Limpiar filtros">
                                    <i class="fas fa-times"></i>
                                </a>
                            <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                        </div>
                    </div>
                </form>
            </div>

            
            <div class="card-body">
                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($templates->count() > 0) { ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="3%"><input type="checkbox" id="select-all" class="form-check-input"></th>
                                    <th>Nombre</th>
                                    <th>Clave (Key)</th>
                                    <th>Modulo</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__currentLoopData = $templates;
                    $__env->addLoop($__currentLoopData);
                    foreach ($__currentLoopData as $template) {
                        $__env->incrementLoopIndices();
                        $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
                                    <tr>
                                        <td><input type="checkbox" class="form-check-input bulk-checkbox" value="<?php echo e($template->id); ?>"></td>
                                        <td>
                                            <strong class="d-block"><?php echo e($template->name); ?></strong>
                                            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($template->description) { ?>
                                                <small class="text-muted"><?php echo e(Str::limit($template->description, 50)); ?></small>
                                            <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                                        </td>
                                        <td><code class="text-muted"><?php echo e($template->key); ?></code></td>
                                        <td>
                                            <span class="badge bg-info-subtle text-info"><?php echo e(ucfirst($template->module)); ?></span>
                                        </td>
                                        <td class="text-center">
                                            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($template->is_enabled) { ?>
                                                <span class="badge bg-success-subtle text-success">Activo</span>
                                            <?php } else { ?>
                                                <span class="badge bg-light text-black">Inactivo</span>
                                            <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item" href="<?php echo e(route('mailers.templates.edit', $template->uid)); ?>">
                                                            Editar
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="<?php echo e(route('mailers.templates.preview', $template->uid)); ?>" target="_blank">
                                                            Vista previa
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <button type="button" class="dropdown-item btn-send-test"
                                                                data-template-uid="<?php echo e($template->uid); ?>"
                                                                data-template-name="<?php echo e($template->name); ?>"
                                                                data-template-subject="<?php echo e($template->subject); ?>">
                                                            Enviar prueba
                                                        </button>
                                                    </li>
                                                    <li>
                                                        <form method="POST" action="<?php echo e(route('mailers.templates.toggle-status', $template->uid)); ?>">
                                                            <?php echo csrf_field(); ?>
                                                            <button type="submit" class="dropdown-item">
                                                                <?php echo e($template->is_enabled ? 'Desactivar' : 'Activar'); ?>

                                                            </button>
                                                        </form>
                                                    </li>
                                                    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (! $template->is_protected) { ?>
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <a class="dropdown-item delete-btn" href="javascript:void(0)"
                                                               data-bs-toggle="modal" data-bs-target="#delete-modal"
                                                               data-url="<?php echo e(route('mailers.templates.destroy', $template->uid)); ?>"
                                                               data-title="Eliminar: <?php echo e($template->name); ?>"
                                                               data-method="DELETE">
                                                                Eliminar
                                                            </a>
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
                <?php } else { ?>
                    <div class="text-center py-5">
                        <div class="d-flex flex-column align-items-center">
                            <div class="round-48 rounded-circle bg-light-subtle text-muted mb-3 d-flex align-items-center justify-content-center">
                                <i class="fas fa-envelope-open fs-7"></i>
                            </div>
                            <h6 class="mb-1">
                                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($search || $module) { ?>
                                    No se encontraron plantillas
                                <?php } else { ?>
                                    No hay plantillas configuradas
                                <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                            </h6>
                            <p class="text-muted mb-3">
                                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($search || $module) { ?>
                                    No hay resultados para los criterios de búsqueda
                                <?php } else { ?>
                                    Crea la primera plantilla de email
                                <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                            </p>
                            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (! $search && ! $module) { ?>
                                <a href="<?php echo e(route('mailers.templates.create')); ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-plus me-1"></i> Nuevo template
                                </a>
                            <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                        </div>
                    </div>
                <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
            </div>

            
            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($templates->hasPages()) { ?>
                <div class="card-footer bg-white border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted">
                            Mostrando <strong><?php echo e($templates->firstItem()); ?></strong> a <strong><?php echo e($templates->lastItem()); ?></strong>
                            de <strong><?php echo e($templates->total()); ?></strong> plantillas
                        </div>
                        <?php echo e($templates->links()); ?>

                    </div>
                </div>
            <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>

        </div>
    </div>

    
    <div id="bulk-toolbar" class="position-fixed bottom-0 start-50 translate-middle-x mb-4 d-none" style="z-index:1050;">
        <button type="button" class="btn btn-primary shadow-lg px-4" data-bs-toggle="modal" data-bs-target="#bulk-modal">
            <span data-bulk-count>0</span> seleccionado(s) &mdash; Aplicar acción
        </button>
    </div>

    
    <div class="modal fade" id="bulk-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Acción masiva</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">Se aplicará la acción sobre <strong><span data-bulk-count>0</span> plantilla(s)</strong>.</p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Acción</label>
                        <select id="bulk-action-select" class="form-select">
                            <option value="">Seleccionar acción...</option>
                            <option value="activate">Activar</option>
                            <option value="deactivate">Desactivar</option>
                            <option value="delete">Eliminar</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button id="bulk-apply-btn" type="button" class="btn btn-primary w-100 mb-1">Aplicar</button>
                    <button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    
    <div class="modal fade" id="modalSendTest" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" id="sendTestForm" action="">
                <?php echo csrf_field(); ?>
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">Enviar email de prueba</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info border-0">
                            <strong>Plantilla:</strong> <span id="sendTestTemplateName"></span><br>
                            <strong>Asunto:</strong> <span id="sendTestTemplateSubject"></span>
                        </div>
                        <div class="mb-3">
                            <label for="send_test_email" class="form-label fw-semibold">Email de destino</label>
                            <input type="email" class="form-control" id="send_test_email"
                                   name="test_email" placeholder="tu@email.com" required>
                            <small class="form-text text-muted">Se enviará un email con variables de ejemplo</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-1" id="sendTestSubmitBtn">
                            <i class="fas fa-paper-plane me-1"></i> Enviar ahora
                        </button>
                        <button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">Cancelar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <?php echo $__env->make('core::components.delete', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
$(document).ready(function () {
    const bulk = window.BulkActions.init({ checkbox: '.bulk-checkbox' });

    $('#bulk-action-select').select2({ dropdownParent: $('#bulk-modal'), width: '100%' });

    $('#bulk-modal').on('hide.bs.modal', function () {
        $('#bulk-action-select').val('').trigger('change');
        $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
        bulk.reset();
    });

    $('#bulk-apply-btn').on('click', function () {
        const action = $('#bulk-action-select').val();
        const ids    = bulk.getIds();

        if (!action) { toastr.warning('Selecciona una acción.'); return; }
        if (!ids.length) { toastr.warning('Selecciona al menos una plantilla.'); return; }
        if (action === 'delete' && !confirm('¿Eliminar las ' + ids.length + ' plantilla(s) seleccionadas? Las protegidas serán omitidas.')) { return; }

        $('#bulk-apply-btn').prop('disabled', true).text('Procesando...');

        $.ajax({
            url: '<?php echo e(route('mailers.templates.bulk-action')); ?>',
            method: 'POST',
            data: JSON.stringify({ action, ids, _token: $('meta[name="csrf-token"]').attr('content') }),
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                $('#bulk-modal').modal('hide');
                toastr.success(res.message);
                setTimeout(() => location.reload(), 800);
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message ?? 'Error al procesar.');
                $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
            },
        });
    });

    $('.delete-btn').on('click', function () {
        $('#delete-modal .modal-title').text($(this).data('title'));
        $('#delete-form').attr('action', $(this).data('url'));
        $('#delete-form input[name="_method"]').val('DELETE');
    });

    $(document).on('click', '.btn-send-test', function () {
        const uid     = $(this).data('template-uid');
        const name    = $(this).data('template-name');
        const subject = $(this).data('template-subject');

        $('#sendTestTemplateName').text(name);
        $('#sendTestTemplateSubject').text(subject);
        $('#sendTestForm').attr('action', '<?php echo e(url('settings/mailers/templates')); ?>/' + uid + '/send-test');
        $('#send_test_email').val('');

        new bootstrap.Modal(document.getElementById('modalSendTest')).show();
    });

    <?php if (session('success')) { ?>
        toastr.success('<?php echo e(session('success')); ?>', 'Éxito');
    <?php } ?>
    <?php if (session('error')) { ?>
        toastr.error('<?php echo e(session('error')); ?>', 'Error');
    <?php } ?>
});
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.theme', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/developerts/Herd/system/modules/Mailer/app/Providers/../../resources/views/templates/index.blade.php ENDPATH**/ ?>