<?php
use Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys;
use Livewire\Mechanisms\ExtendBlade\ExtendBlade;
use Modules\Page\Enums\PageStatus;

$__env->startSection('title', 'Páginas'); ?>

<?php $__env->startSection('content'); ?>
    <?php echo $__env->make('core::components.card', ['title' => 'Páginas'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <div class="widget-content searchable-container list">
        <?php echo $__env->make('core::components.alerts', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

        <div class="card">
            
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Páginas del sitio</h5>
                        <p class="small mb-0 text-muted">Administra las páginas estáticas del sitio web</p>
                    </div>
                    <?php
                        $hasActiveFilters = collect($filters ?? [])->filter(fn ($v, $k) => ! empty($v) && ! in_array($k, ['sort_by', 'sort_order', 'per_page']))->isNotEmpty();
?>
                    <div class="ms-auto">
                        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (! $trashed) { ?>
                        <div class="btn-group">
                            <button type="button" class="btn bg-primary-subtle text-primary dropdown-toggle"
                                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                Acciones
                            </button>
                            <div class="dropdown-menu dropdown-menu-end">
                                <a class="dropdown-item" href="<?php echo e(route('pages.create')); ?>">Nueva página</a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="<?php echo e(route('pages.export.download', ['format' => 'csv'])); ?>">Exportar CSV</a>
                                <a class="dropdown-item" href="<?php echo e(route('pages.export.download', ['format' => 'json'])); ?>">Exportar JSON</a>
                                <a class="dropdown-item" href="<?php echo e(route('pages.import')); ?>">Importar páginas</a>
                            </div>
                        </div>
                        <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                    </div>
                </div>
            </div>

            
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title mb-2">Total páginas</h6>
                                        <h4 class="mb-1 fw-bold"><?php echo e(number_format($stats['total'])); ?></h4>
                                        <small class="text-muted">Configuradas en el sistema</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title mb-2">Publicadas</h6>
                                        <h4 class="mb-1 fw-bold"><?php echo e(number_format($stats['published'])); ?></h4>
                                        <small class="text-muted">Visibles en el sitio</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title mb-2">Borradores</h6>
                                        <h4 class="mb-1 fw-bold"><?php echo e(number_format($stats['draft'])); ?></h4>
                                        <small class="text-muted">Sin publicar</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title mb-2">Pendientes</h6>
                                        <h4 class="mb-1 fw-bold"><?php echo e(number_format($stats['pending'])); ?></h4>
                                        <small class="text-muted">En revisión</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (! $trashed) { ?>
            
            <div class="card-body border-bottom">
                <form method="GET" action="<?php echo e(route('pages.index')); ?>" id="filter-form">
                    <div class="d-flex flex-column flex-lg-row gap-3 align-items-stretch">
                        <div class="flex-fill">
                            <div class="input-group h-100">
                                <span class="input-group-text bg-white border-end-1">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="search" name="search" class="form-control border-start-0 ps-0"
                                       placeholder="Buscar por título o slug..."
                                       value="<?php echo e($filters['search'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="flex-shrink-0" style="min-width: 170px;">
                            <select class="form-select select2 h-100" name="status">
                                <option value="">Todos los estados</option>
                                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__currentLoopData = ['draft' => 'Borrador', 'published' => 'Publicado', 'pending' => 'Pendiente'];
                $__env->addLoop($__currentLoopData);
                foreach ($__currentLoopData as $key => $label) {
                    $__env->incrementLoopIndices();
                    $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
                                    <option value="<?php echo e($key); ?>" <?php echo e(($filters['status'] ?? '') === $key ? 'selected' : ''); ?>>
                                        <?php echo e($label); ?>

                                    </option>
                                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } $__env->popLoop();
                $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>
                            </select>
                        </div>
                        <div class="flex-shrink-0" style="min-width: 150px;">
                            <input type="date" name="date_from" class="form-control h-100" value="<?php echo e($filters['date_from'] ?? ''); ?>">
                        </div>
                        <div class="flex-shrink-0" style="min-width: 150px;">
                            <input type="date" name="date_to" class="form-control h-100" value="<?php echo e($filters['date_to'] ?? ''); ?>">
                        </div>
                        <div class="d-flex gap-2 flex-shrink-0">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i>
                            </button>
                            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($hasActiveFilters) { ?>
                                <a href="<?php echo e(route('pages.index')); ?>" class="btn btn-outline-secondary" title="Limpiar filtros">
                                    <i class="fas fa-times"></i>
                                </a>
                            <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                        </div>
                    </div>
                </form>
            </div>
            <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>

            
            <ul class="nav nav-tabs border-0 user-profile-tab" role="tablist">
                <li class="nav-item" role="presentation">
                    <a class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3 <?php echo e(! $trashed && ($filters['status'] ?? '') === '' ? 'active' : ''); ?>"
                       href="<?php echo e(route('pages.index')); ?>" role="tab">
                        <span class="d-none d-md-block">Todas</span>
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3 <?php echo e(! $trashed && ($filters['status'] ?? '') === 'published' ? 'active' : ''); ?>"
                       href="<?php echo e(route('pages.index', ['status' => 'published'])); ?>" role="tab">
                        <span class="d-none d-md-block">Publicadas</span>
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3 <?php echo e(! $trashed && ($filters['status'] ?? '') === 'draft' ? 'active' : ''); ?>"
                       href="<?php echo e(route('pages.index', ['status' => 'draft'])); ?>" role="tab">
                        <span class="d-none d-md-block">Borradores</span>
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3 <?php echo e($trashed ? 'active' : ''); ?>"
                       href="<?php echo e(route('pages.index', ['trashed' => 1])); ?>" role="tab">
                        <span class="d-none d-md-block">Papelera</span>
                    </a>
                </li>
            </ul>

            
            <div class="card-body">
                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($trashed) { ?>
                    
                    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($pages->count() > 0) { ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="3%"><input type="checkbox" id="select-all" class="form-check-input"></th>
                                        <th>Título</th>
                                        <th class="text-center">Estado</th>
                                        <th>Eliminada</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__currentLoopData = $pages;
                        $__env->addLoop($__currentLoopData);
                        foreach ($__currentLoopData as $page) {
                            $__env->incrementLoopIndices();
                            $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
                                        <tr>
                                            <td>
                                                <input type="checkbox" class="form-check-input bulk-checkbox" value="<?php echo e($page->id); ?>">
                                            </td>
                                            <td>
                                                <span class="fw-semibold text-muted"><?php echo e($page->title); ?></span>
                                                <small class="text-muted d-block">/<?php echo e($page->slug); ?></small>
                                            </td>
                                            <td class="text-center">
                                                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($page->isPublished()) { ?>
                                                    <span class="badge bg-success-subtle text-success">Publicado</span>
                                                <?php } elseif ($page->isDraft()) { ?>
                                                    <span class="badge bg-secondary-subtle text-secondary">Borrador</span>
                                                <?php } else { ?>
                                                    <span class="badge bg-warning-subtle text-warning">Pendiente</span>
                                                <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                                            </td>
                                            <td>
                                                <small class="text-muted"><?php echo e($page->deleted_at->format('d/m/Y H:i')); ?></small>
                                            </td>
                                            <td class="text-center">
                                                <div class="d-flex gap-2 justify-content-center">
                                                    <form action="<?php echo e(route('pages.restore', $page->id)); ?>" method="POST" class="d-inline">
                                                        <?php echo csrf_field(); ?>
                                                        <button type="submit" class="btn btn-sm btn-outline-success" title="Restaurar página">
                                                            <i class="fas fa-rotate-left me-1"></i>Restaurar
                                                        </button>
                                                    </form>
                                                    <button type="button"
                                                            class="btn btn-sm btn-outline-danger force-delete-btn"
                                                            data-url="<?php echo e(route('pages.force-delete', $page->id)); ?>"
                                                            data-title="<?php echo e($page->title); ?>">
                                                        <i class="fas fa-trash me-1"></i>Eliminar definitivamente
                                                    </button>
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
                            <div class="mb-4">
                                <i class="fas fa-trash fa-4x text-muted opacity-50"></i>
                            </div>
                            <h5 class="text-muted mb-2">La papelera está vacía</h5>
                            <p class="text-muted mb-0">No hay páginas eliminadas en este momento</p>
                        </div>
                    <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>

                <?php } else { ?>
                    
                    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($pages->count() > 0) { ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="3%"><input type="checkbox" id="select-all" class="form-check-input"></th>
                                        <th>Título</th>
                                        <th class="text-center">Idiomas</th>
                                        <th class="text-center">Estado</th>
                                        <th>Fecha</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__currentLoopData = $pages;
                        $__env->addLoop($__currentLoopData);
                        foreach ($__currentLoopData as $page) {
                            $__env->incrementLoopIndices();
                            $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
                                        <tr>
                                            <td>
                                                <input type="checkbox" class="form-check-input bulk-checkbox" value="<?php echo e($page->id); ?>">
                                            </td>
                                            <td>
                                                <a href="<?php echo e(route('pages.edit', $page->id)); ?>" class="text-decoration-none fw-semibold">
                                                    <?php echo e($page->title); ?>

                                                </a>
                                                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($page->categories->isNotEmpty()) { ?>
                                                    <div class="mt-1">
                                                        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__currentLoopData = $page->categories;
                                                    $__env->addLoop($__currentLoopData);
                                                    foreach ($__currentLoopData as $cat) {
                                                        $__env->incrementLoopIndices();
                                                        $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
                                                            <a href="<?php echo e(route('pages.index', ['category' => $cat->slug])); ?>"
                                                               class="badge text-decoration-none me-1"
                                                               style="background-color: <?php echo e($cat->color); ?>">
                                                                <?php echo e($cat->name); ?>

                                                            </a>
                                                        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } $__env->popLoop();
                                                    $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>
                                                    </div>
                                                <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                                                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($page->tags->isNotEmpty()) { ?>
                                                    <div class="mt-1">
                                                        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__currentLoopData = $page->tags;
                                                    $__env->addLoop($__currentLoopData);
                                                    foreach ($__currentLoopData as $tag) {
                                                        $__env->incrementLoopIndices();
                                                        $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
                                                            <span class="badge bg-light text-secondary border me-1"><?php echo e($tag->name); ?></span>
                                                        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } $__env->popLoop();
                                                    $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>
                                                    </div>
                                                <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                                                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($page->featured_image) { ?>
                                                    <small class="text-muted d-block mt-1"><i class="fas fa-image me-1"></i>Con imagen destacada</small>
                                                <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                                            </td>
                                            <td class="text-center">
                                                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__currentLoopData = $page->translations->sortBy('locale');
                            $__env->addLoop($__currentLoopData);
                            foreach ($__currentLoopData as $trans) {
                                $__env->incrementLoopIndices();
                                $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
                                                    <?php $isPublished = ($trans->status instanceof PageStatus ? $trans->status->value : $trans->status) === 'published'; ?>
                                                    <span class="badge <?php echo e($isPublished ? 'bg-success' : 'bg-secondary'); ?> me-1"
                                                          data-bs-toggle="tooltip"
                                                          data-bs-placement="top"
                                                          title="<?php echo e($isPublished ? '✓ Publicado' : '○ Borrador'); ?>: /<?php echo e($trans->slug); ?>">
                                                        <?php echo e(strtoupper($trans->locale)); ?> <?php echo e($isPublished ? '●' : '○'); ?>

                                                    </span>
                                                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } $__env->popLoop();
                            $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>
                                                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($page->translations->isEmpty()) { ?>
                                                    <span class="badge bg-light text-muted border">Sin traducción</span>
                                                <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                                            </td>
                                            <td class="text-center">
                                                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($page->isPublished()) { ?>
                                                    <span class="badge bg-success-subtle text-success">Publicado</span>
                                                    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($page->willBeUnpublished()) { ?>
                                                        <br><small class="text-muted"><i class="fas fa-clock"></i> <?php echo e($page->unpublish_at->format('d/m/Y H:i')); ?></small>
                                                    <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                                                <?php } elseif ($page->isDraft()) { ?>
                                                    <span class="badge bg-secondary-subtle text-secondary">Borrador</span>
                                                    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($page->willBePublished()) { ?>
                                                        <br><small class="text-muted"><i class="fas fa-clock"></i> <?php echo e($page->publish_at->format('d/m/Y H:i')); ?></small>
                                                    <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                                                <?php } else { ?>
                                                    <span class="badge bg-warning-subtle text-warning">Pendiente</span>
                                                <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                                            </td>
                                            <td>
                                                <span class="text-muted"><?php echo e($page->created_at->format('d/m/Y')); ?></span>
                                            </td>
                                            <td class="text-center">
                                                <div class="dropdown">
                                                    <a href="#"
                                                       class="text-muted"
                                                       data-bs-toggle="dropdown"
                                                       data-bs-auto-close="true"
                                                       data-bs-boundary="viewport">
                                                        <i class="fa fa-ellipsis-vertical"></i>
                                                    </a>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li>
                                                            <a href="<?php echo e($page->url); ?>" class="dropdown-item" target="_blank">
                                                                Ver
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a href="<?php echo e(route('pages.edit', $page->id)); ?>" class="dropdown-item">
                                                                Editar
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a href="<?php echo e(route('pages.versions.index', $page->id)); ?>" class="dropdown-item">
                                                                Versiones
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a href="<?php echo e(route('pages.analytics.view', $page->id)); ?>" class="dropdown-item">
                                                                Analytics
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a href="<?php echo e(route('pages.edit', $page->id)); ?>#performance"
                                                               class="dropdown-item">
                                                                Analizar PageSpeed
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <form method="POST" action="<?php echo e(route('pages.duplicate', $page->id)); ?>" class="d-inline">
                                                                <?php echo csrf_field(); ?>
                                                                <button type="submit" class="dropdown-item">
                                                                    Duplicar
                                                                </button>
                                                            </form>
                                                        </li>
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <button type="button"
                                                                    class="dropdown-item delete-btn"
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#delete-modal"
                                                                    data-url="<?php echo e(route('pages.destroy', $page->id)); ?>"
                                                                    data-title="Eliminar página: <?php echo e($page->title); ?>">
                                                                Eliminar
                                                            </button>
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
                    <?php } else { ?>
                        <div class="text-center py-5">
                            <div class="mb-4">
                                <i class="fas fa-file-alt fa-4x text-muted opacity-50"></i>
                            </div>
                            <h5 class="text-muted mb-2">No hay páginas para mostrar</h5>
                            <p class="text-muted mb-4">
                                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($hasActiveFilters) { ?>
                                    No se encontraron resultados con los filtros aplicados
                                <?php } else { ?>
                                    Crea tu primera página para el sitio web
                                <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                            </p>
                            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (! $hasActiveFilters) { ?>
                                <a href="<?php echo e(route('pages.create')); ?>" class="btn btn-primary">
                                    <i class="fas fa-plus me-1"></i> Crear primera página
                                </a>
                            <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                        </div>
                    <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
            </div>

            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($pages->hasPages()) { ?>
                <div class="card-footer"><?php echo e($pages->withQueryString()->links()); ?></div>
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
                    <p class="text-muted mb-3">Se aplicará la acción sobre <strong><span data-bulk-count>0</span> página(s)</strong>.</p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Acción</label>
                        <select id="bulk-action-select" class="form-select">
                            <option value="">Seleccionar acción...</option>
                            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($trashed) { ?>
                                <option value="restore">Restaurar</option>
                            <?php } else { ?>
                                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (($filters['status'] ?? '') !== 'published') { ?>
                                    <option value="publish">Publicar</option>
                                <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (($filters['status'] ?? '') !== 'draft') { ?>
                                    <option value="unpublish">Despublicar</option>
                                <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                                <option value="delete">Eliminar</option>
                            <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button id="bulk-apply-btn" type="button" class="btn btn-primary w-100 mb-1">Aplicar</button>
                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    
    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($trashed) { ?>
    <div class="modal fade" id="force-delete-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title text-danger">
                        <i class="fas fa-triangle-exclamation me-2"></i>Eliminar permanentemente
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-1">¿Estás seguro que deseas eliminar permanentemente la página:</p>
                    <p class="fw-semibold" id="force-delete-title"></p>
                    <p class="text-danger small mb-0">Esta acción no se puede deshacer.</p>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form id="force-delete-form" method="POST">
                        <?php echo csrf_field(); ?>
                        <?php echo method_field('DELETE'); ?>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash me-1"></i>Eliminar definitivamente
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php } else { ?>
        <?php echo $__env->make('core::components.delete', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
$(document).ready(function () {
    <?php if (session('success')) { ?>
    toastr.success('<?php echo e(session('success')); ?>', 'Éxito');
    <?php } ?>
    <?php if (session('error')) { ?>
    toastr.error('<?php echo e(session('error')); ?>', 'Error');
    <?php } ?>

    // Bulk actions (toolbar flotante + modal)
    const bulk = window.BulkActions.init({ checkbox: '.bulk-checkbox' });

    $('#bulk-action-select').select2({ dropdownParent: $('#bulk-modal'), width: '100%' });

    $('#bulk-modal').on('hide.bs.modal', function () {
        $('#bulk-action-select').val('').trigger('change');
        $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
        bulk.reset();
    });

    $('#bulk-apply-btn').on('click', function () {
        var action = $('#bulk-action-select').val();
        var ids    = bulk.getIds();

        if (!action) { toastr.warning('Selecciona una acción.'); return; }
        if (!ids.length) { toastr.warning('Selecciona al menos una página.'); return; }
        if (action === 'delete' && !confirm('¿Eliminar las ' + ids.length + ' página(s) seleccionadas?')) { return; }

        $('#bulk-apply-btn').prop('disabled', true).text('Procesando...');

        $.ajax({
            url: '<?php echo e(route('pages.bulk-action')); ?>',
            method: 'POST',
            data: JSON.stringify({ action: action, ids: ids, _token: $('meta[name="csrf-token"]').attr('content') }),
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                $('#bulk-modal').modal('hide');
                toastr.success(res.message || ids.length + ' página(s) actualizadas.');
                setTimeout(function () { location.reload(); }, 800);
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message ?? 'Error al procesar.');
                $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
            },
        });
    });

    <?php if ($trashed) { ?>
    // Force-delete modal
    $(document).on('click', '.force-delete-btn', function () {
        $('#force-delete-form').attr('action', $(this).data('url'));
        $('#force-delete-title').text($(this).data('title'));
        new bootstrap.Modal(document.getElementById('force-delete-modal')).show();
    });
    <?php } else { ?>
    // Delete modal
    $('.delete-btn').on('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        $('#delete-form').attr('action', $(this).data('url'));
        new bootstrap.Modal(document.getElementById('delete-modal')).show();
    });

    // Tooltips
    $('[data-bs-toggle="tooltip"]').each(function () {
        new bootstrap.Tooltip(this);
    });
    <?php } ?>
});
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.theme', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/developerts/Herd/system/modules/Page/resources/views/pages/pages/index.blade.php ENDPATH**/ ?>