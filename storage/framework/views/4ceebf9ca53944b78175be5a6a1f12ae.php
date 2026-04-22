<?php
use Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys;
use Livewire\Mechanisms\ExtendBlade\ExtendBlade;

$__env->startSection('title', 'Categorías de formularios'); ?>

<?php $__env->startSection('content'); ?>
    <?php echo $__env->make('core::components.card', ['title' => 'Categorías de formularios'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <div class="widget-content searchable-container list">

        <?php echo $__env->make('core::components.alerts', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

        <div class="card">

            
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Categorías de formularios</h5>
                        <p class="small mb-0 text-muted">Organiza y gestiona las categorías para clasificar tus formularios</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="<?php echo e(route('settings.forms.categories.create')); ?>" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> Nueva categoría
                        </a>
                    </div>
                </div>
            </div>

            
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Total</h6>
                                <h4 class="mb-1 fw-bold"><?php echo e($stats['total']); ?></h4>
                                <small class="text-muted">Categorías configuradas</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Activas</h6>
                                <h4 class="mb-1 fw-bold"><?php echo e($stats['active']); ?></h4>
                                <small class="text-muted">Visibles en los formularios</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Inactivas</h6>
                                <h4 class="mb-1 fw-bold"><?php echo e($stats['inactive']); ?></h4>
                                <small class="text-muted">Deshabilitadas temporalmente</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Con formularios</h6>
                                <h4 class="mb-1 fw-bold"><?php echo e($stats['with_forms']); ?></h4>
                                <small class="text-muted">Tienen formularios asignados</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            
            <div class="card-body border-bottom">
                <form method="GET" action="<?php echo e(route('settings.forms.categories.index')); ?>">
                    <div class="d-flex flex-column flex-lg-row gap-3 align-items-stretch">
                        <div class="flex-fill">
                            <div class="input-group h-100">
                                <span class="input-group-text bg-white border-end-1">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="search" name="search" class="form-control border-start-0 ps-0"
                                       placeholder="Buscar por nombre..."
                                       value="<?php echo e(request('search')); ?>">
                            </div>
                        </div>
                        <div class="flex-shrink-0" >
                            <select name="status" class="form-select select2 h-100">
                                <option value="">Todos los estados</option>
                                <option value="active"   <?php echo e(request('status') === 'active' ? 'selected' : ''); ?>>Activas</option>
                                <option value="inactive" <?php echo e(request('status') === 'inactive' ? 'selected' : ''); ?>>Inactivas</option>
                            </select>
                        </div>
                        <div class="d-flex gap-2 flex-shrink-0">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-1"></i>
                            </button>
                            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (request('search') || request('status')) { ?>
                                <a href="<?php echo e(route('settings.forms.categories.index')); ?>" class="btn btn-outline-secondary" title="Limpiar filtros">
                                    <i class="fas fa-times"></i>
                                </a>
                            <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                        </div>
                    </div>
                </form>
            </div>

            
            <div class="card-body">
                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($categories->isEmpty()) { ?>
                    <div class="text-center py-5">
                        <div class="d-flex flex-column align-items-center">
                            <div class="round-48 rounded-circle bg-light-subtle text-muted mb-3 d-flex align-items-center justify-content-center">
                                <i class="fas fa-folder-open fs-7"></i>
                            </div>
                            <h6 class="mb-1">
                                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (request('search') || request('status')) { ?>
                                    No se encontraron categorías
                                <?php } else { ?>
                                    No hay categorías configuradas
                                <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                            </h6>
                            <p class="text-muted mb-3">
                                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (request('search') || request('status')) { ?>
                                    No hay resultados para los criterios de búsqueda
                                <?php } else { ?>
                                    Crea la primera categoría para organizar tus formularios
                                <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                            </p>
                            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (! request('search') && ! request('status')) { ?>
                                <a href="<?php echo e(route('settings.forms.categories.create')); ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-plus me-1"></i> Nueva categoría
                                </a>
                            <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                        </div>
                    </div>
                <?php } else { ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="3%"><input type="checkbox" id="select-all" class="form-check-input"></th>
                                    <th>Nombre</th>
                                    <th>Slug</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__currentLoopData = $categories;
                    $__env->addLoop($__currentLoopData);
                    foreach ($__currentLoopData as $category) {
                        $__env->incrementLoopIndices();
                        $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
                                    <tr>
                                        <td><input type="checkbox" class="form-check-input bulk-checkbox" value="<?php echo e($category->id); ?>"></td>
                                        <td><strong><?php echo e($category->name); ?></strong></td>
                                        <td><small class="text-muted"><?php echo e($category->slug); ?></small></td>
                                        <td class="text-center">
                                            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($category->is_active) { ?>
                                                <span class="badge bg-success-subtle text-success">Activa</span>
                                            <?php } else { ?>
                                                <span class="badge bg-light text-dark">Inactiva</span>
                                            <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item" href="<?php echo e(route('settings.forms.categories.edit', $category)); ?>">Editar</a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item toggle-category" href="javascript:void(0)"
                                                           data-url="<?php echo e(route('settings.forms.categories.toggle', $category)); ?>">
                                                            <?php echo e($category->is_active ? 'Desactivar' : 'Activar'); ?>

                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <a class="dropdown-item delete-btn"
                                                           data-bs-toggle="modal" data-bs-target="#delete-modal"
                                                           data-url="<?php echo e(route('settings.forms.categories.destroy', $category)); ?>"
                                                           data-title="Eliminar: <?php echo e($category->name); ?>">
                                                            Eliminar
                                                        </a>
                                                    </li>
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

            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($categories->hasPages()) { ?>
                <div class="card-footer">
                    <?php echo e($categories->links()); ?>

                </div>
            <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>

        </div>
    </div>

    
    <div class="modal fade" id="bulk-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Acción masiva</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">Se aplicará la acción sobre <strong><span data-bulk-count>0</span> categoría(s)</strong>.</p>
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
                <div class="modal-footer flex-column">
                    <button id="bulk-apply-btn" type="button" class="btn btn-primary w-100 mb-2">Aplicar</button>
                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    
    <div id="bulk-toolbar" class="position-fixed bottom-0 start-50 translate-middle-x mb-4 d-none" >
        <button type="button" class="btn btn-primary shadow-lg px-4" data-bs-toggle="modal" data-bs-target="#bulk-modal">
            <span data-bulk-count>0</span> seleccionada(s) &mdash; Aplicar acción
        </button>
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
        if (!ids.length) { toastr.warning('Selecciona al menos una categoría.'); return; }
        if (action === 'delete' && !confirm('¿Eliminar las ' + ids.length + ' categoría(s) seleccionadas?')) { return; }

        $('#bulk-apply-btn').prop('disabled', true).text('Procesando...');

        $.ajax({
            url: '<?php echo e(route('settings.forms.categories.bulk-action')); ?>',
            method: 'POST',
            data: JSON.stringify({ action, ids, _token: $('meta[name="csrf-token"]').attr('content') }),
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                $('#bulk-modal').modal('hide');
                toastr.success(res.count + ' categoría(s) actualizadas.');
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
    });

    $(document).on('click', '.toggle-category', function (e) {
        e.preventDefault();
        const url = $(this).data('url');
        $.ajax({
            url: url,
            method: 'POST',
            data: { _method: 'PATCH', _token: $('meta[name="csrf-token"]').attr('content') },
        })
        .done(function (data) {
            toastr.success(data.message);
            location.reload();
        })
        .fail(function () {
            toastr.error('Error al cambiar el estado de la categoría');
        });
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

<?php echo $__env->make('layouts.theme', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/developerts/Herd/system/modules/Forms/resources/views/settings/categories/index.blade.php ENDPATH**/ ?>