
<div class="modal fade" id="uiBlocksModal" tabindex="-1" aria-labelledby="uiBlocksModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-fullscreen-md-down modal-dialog-scrollable">
        <div class="modal-content h-100">

            
            <div class="uib-topbar d-flex align-items-center">
                <div class="d-flex align-items-center gap-2 flex-shrink-0">
                    <i class="fas fa-cubes uib-brand-icon"></i>
                    <span class="uib-brand-text">Shortcode Manager</span>
                </div>
                <div class="flex-grow-1 mx-4 position-relative">
                    <span class="position-absolute uib-search-icon">
                        <i class="fas fa-search"></i>
                    </span>
                    <input type="text" id="uiBlocksSearch" autocomplete="off"
                           class="form-control uib-search-input"
                           placeholder="Buscar componente...">
                    <button id="uiSearchClear" type="button" title="Limpiar">
                        <i class="fas fa-times-circle"></i>
                    </button>
                </div>
                <button type="button" class="uib-close-btn" data-bs-dismiss="modal" aria-label="Cerrar">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="uiSearchMsg" class="ui-blocks-search-msg px-4"></div>

            <?php
use Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys;
            use Livewire\Mechanisms\ExtendBlade\ExtendBlade;

            if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (empty($shortcodes)) { ?>
                <div class="modal-body d-flex align-items-center justify-content-center">
                    <div class="text-center py-5" style="color: #abb3b7;">
                        <i class="fas fa-cubes fa-3x mb-3 opacity-25"></i>
                        <p class="mb-0">No hay bloques disponibles.</p>
                    </div>
                </div>
            <?php } else { ?>
                
                <div class="d-lg-none uib-mobile-cats">
                    <div class="d-flex overflow-auto gap-2 px-3 py-2">
                        <button type="button" class="ui-cat-pill active" data-cat="__all__">
                            Todos <span class="ui-cat-pill-count"><?php echo e($uiTotalBlocks ?? ''); ?></span>
                        </button>
                        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__currentLoopData = $orderedCats;
                $__env->addLoop($__currentLoopData);
                foreach ($__currentLoopData as $catSlug) {
                    $__env->incrementLoopIndices();
                    $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
                            <?php
                            $catLabel = $uiCategoryLabels->get($catSlug, ucfirst($catSlug));
                    $catCount = $uiGrouped->get($catSlug, collect())->count();
                    ?>
                            <button type="button" class="ui-cat-pill" data-cat="<?php echo e($catSlug); ?>">
                                <?php echo e($catLabel); ?> <span class="ui-cat-pill-count"><?php echo e($catCount); ?></span>
                            </button>
                        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } $__env->popLoop();
                $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>
                    </div>
                </div>

                <div class="d-flex uib-body">

                    
                    <div class="d-none d-lg-flex flex-column uib-sidebar">
                        <p class="uib-sidebar-label">Categorías</p>
                        <nav class="overflow-auto uib-sidebar-nav" id="mp-sidebar-tabs">
                            <button type="button" class="uib-nav-btn ui-cat-btn active" data-cat="__all__">
                                <i class="fas fa-border-all uib-nav-icon"></i>
                                <span>Todos</span>
                            </button>
                            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__currentLoopData = $orderedCats;
                $__env->addLoop($__currentLoopData);
                foreach ($__currentLoopData as $catSlug) {
                    $__env->incrementLoopIndices();
                    $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
                                <?php
                            $catIcon = $uiCatIconMap[$catSlug] ?? 'fa-circle';
                    $catLabel = $uiCategoryLabels->get($catSlug, ucfirst($catSlug ?: 'General'));
                    ?>
                                <button type="button" class="uib-nav-btn ui-cat-btn" data-cat="<?php echo e($catSlug); ?>">
                                    <i class="fas <?php echo e($catIcon); ?> uib-nav-icon ui-cat-icon-<?php echo e($catSlug); ?>"></i>
                                    <span><?php echo e($catLabel); ?></span>
                                </button>
                            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } $__env->popLoop();
                $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>
                        </nav>
                    </div>

                    
                    <div class="flex-grow-1 overflow-auto" id="uiBlocksContent">

                        
                        <div class="uib-content-header">
                            <div class="d-flex align-items-start justify-content-between">
                                <div>
                                    <h3 class="uib-content-title mb-1">Bloques de interfaz</h3>
                                    <p class="uib-content-subtitle mb-0">
                                        Explora y gestiona tu colección de componentes. Selecciona uno para insertarlo directamente en el editor.
                                    </p>
                                </div>
                            </div>
                        </div>

                        
                        <div class="uib-grid-area">
                            <div class="ui-blocks-pane" data-pane="__all__">
                                <div class="row g-3">
                                    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__currentLoopData = collect($shortcodes);
                $__env->addLoop($__currentLoopData);
                foreach ($__currentLoopData as $sc) {
                    $__env->incrementLoopIndices();
                    $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
                                        <?php
                            $scCat = $sc['category'] ?? 'otros';
                    $scIcon = $uiIconMap[$sc['name']] ?? 'fa-cube';
                    $scDesc = $sc['description'] ?? '';
                    ?>
                                        <div class="col-6 col-lg-4 ui-block-item" data-name="<?php echo e(strtolower($sc['name'])); ?>" data-category="<?php echo e($scCat); ?>">
                                            <div class="card uib-card ui-block-card h-100" data-block-key="<?php echo e($sc['name']); ?>" data-block-name="<?php echo e($sc['name']); ?>">
                                                <div class="uib-card-icon ui-cat-bg-<?php echo e($scCat); ?>">
                                                    <i class="fas <?php echo e($scIcon); ?> ui-cat-icon-<?php echo e($scCat); ?>"></i>
                                                </div>
                                                <div class="card-body">
                                                    <div class="d-flex align-items-start justify-content-between">
                                                        <div class="uib-card-name" title="[<?php echo e($sc['name']); ?>]">[<?php echo e($sc['name']); ?>]</div>
                                                        <button type="button" class="uib-card-copy" title="Copiar shortcode">
                                                            <i class="far fa-copy"></i>
                                                        </button>
                                                    </div>
                                                    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($scDesc) { ?><div class="uib-card-desc mt-1"><?php echo e(Str::limit($scDesc, 80)); ?></div><?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                                                    <span class="uib-card-tag ui-cat-tag-<?php echo e($scCat); ?>"><?php echo e($uiCategoryLabels->get($scCat, ucfirst($scCat))); ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } $__env->popLoop();
                $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>
                                </div>
                            </div>

                            
                            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__currentLoopData = $orderedCats;
                $__env->addLoop($__currentLoopData);
                foreach ($__currentLoopData as $catSlug) {
                    $__env->incrementLoopIndices();
                    $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
                                <?php $catItems = $uiGrouped->get($catSlug, collect()); ?>
                                <div class="ui-blocks-pane d-none" data-pane="<?php echo e($catSlug); ?>">
                                    <div class="row g-3">
                                        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__currentLoopData = $catItems;
                    $__env->addLoop($__currentLoopData);
                    foreach ($__currentLoopData as $sc) {
                        $__env->incrementLoopIndices();
                        $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
                                            <?php
                                $scIcon = $uiIconMap[$sc['name']] ?? 'fa-cube';
                        $scDesc = $sc['description'] ?? '';
                        ?>
                                            <div class="col-6 col-lg-4 ui-block-item" data-name="<?php echo e(strtolower($sc['name'])); ?>" data-category="<?php echo e($catSlug); ?>">
                                                <div class="card uib-card ui-block-card h-100" data-block-key="<?php echo e($sc['name']); ?>" data-block-name="<?php echo e($sc['name']); ?>">
                                                    <div class="uib-card-icon ui-cat-bg-<?php echo e($catSlug); ?>">
                                                        <i class="fas <?php echo e($scIcon); ?> ui-cat-icon-<?php echo e($catSlug); ?>"></i>
                                                    </div>
                                                    <div class="card-body">
                                                        <div class="d-flex align-items-start justify-content-between">
                                                            <div class="uib-card-name" title="[<?php echo e($sc['name']); ?>]">[<?php echo e($sc['name']); ?>]</div>
                                                            <button type="button" class="uib-card-copy" title="Copiar shortcode">
                                                                <i class="far fa-copy"></i>
                                                            </button>
                                                        </div>
                                                        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($scDesc) { ?><div class="uib-card-desc mt-1"><?php echo e(Str::limit($scDesc, 80)); ?></div><?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                                                        <span class="uib-card-tag ui-cat-tag-<?php echo e($catSlug); ?>"><?php echo e($uiCategoryLabels->get($catSlug, ucfirst($catSlug))); ?></span>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } $__env->popLoop();
                    $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>
                                    </div>
                                </div>
                            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } $__env->popLoop();
                $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>

                            
                            <div id="uiEmptySearch" class="ui-empty-search">
                                <i class="fas fa-search fa-2x mb-3 opacity-25"></i>
                                <p class="fw-semibold mb-1">Sin resultados</p>
                                <p class="mb-0" style="font-size:12px;">Intenta con otro término</p>
                            </div>
                        </div>

                    </div>
                </div>
            <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>

            
            <div class="uib-footer d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <span class="uib-footer-text">Shortcode Engine</span>
                    <span class="uib-footer-version">v<?php echo e(config('shortcode.version', '1.0')); ?></span>
                    <span class="uib-footer-dot"></span>
                </div>
                <button type="button" class="btn btn-sm uib-close-footer-btn" data-bs-dismiss="modal">Cerrar</button>
            </div>

        </div>
    </div>
</div>
<?php /**PATH /Users/developerts/Herd/system/modules/Theme/app/Providers/../../resources/views/partials/ui-blocks-modal.blade.php ENDPATH**/ ?>