<?php
use Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys;
use Livewire\Mechanisms\ExtendBlade\ExtendBlade;
use Modules\Theme\Services\NavService;

// Obtener todos los datos de navegación procesados desde el backend
// Esto incluye: miniItems filtrados, sidebars filtrados, y el ID del sidebar activo
['miniItems' => $miniItems, 'sidebars' => $allSidebars, 'activeSidebarId' => $activeSidebarId, 'activeMiniId' => $activeMiniId, 'activeItemRoute' => $activeItemRoute] = NavService::getNavDataForUser();
$currentRoute = request()->route()?->getName() ?? '';
?>

<aside class="side-mini-panel <?php echo e($activeSidebarId ? 'with-vertical' : ''); ?>">
    <!-- ---------------------------------- -->
    <!-- Start Vertical Layout Sidebar -->
    <!-- ---------------------------------- -->
    <div class="iconbar">
        <div>
            <!-- ---------------------------------- -->
            <!-- Mini Navigation Icons -->
            <!-- ---------------------------------- -->
            <div class="mini-nav">
                <div class="brand-logo d-flex align-items-center justify-content-center">
                    <a class="nav-link sidebartoggler" id="headerCollapse" href="javascript:void(0)">
                        <i class="fa-duotone fa-bars fs-6"></i>
                    </a>
                </div>
                <ul class="mini-nav-ul" data-simplebar="">
                    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__empty_1 = true;
$__currentLoopData = $miniItems;
$__env->addLoop($__currentLoopData);
foreach ($__currentLoopData as $miniItem) {
    $__env->incrementLoopIndices();
    $loop = $__env->getLastLoop();
    $__empty_1 = false; ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
                        <?php
            $isActive = $activeMiniId === $miniItem['sidebar_id'];
    ?>
                        <!-- --------------------------------------------------------------------------------------------------------- -->
                        <!-- <?php echo e($miniItem['tooltip']); ?> -->
                        <!-- --------------------------------------------------------------------------------------------------------- -->
                        <li class="mini-nav-item <?php echo e($isActive ? 'selected' : ''); ?>"
                            id="mini-<?php echo e($miniItem['id']); ?>"
                            data-sidebar-id="<?php echo e($miniItem['sidebar_id']); ?>"
                            <?php if (! empty($miniItem['url'])) { ?> data-direct-url="<?php echo e(route($miniItem['url'])); ?>" <?php } ?>>
                            <a href="<?php echo e(! empty($miniItem['url']) ? route($miniItem['url']) : 'javascript:void(0)'); ?>"
                               data-bs-toggle="tooltip"
                               data-bs-custom-class="custom-tooltip"
                               data-bs-placement="right"
                               data-bs-title="<?php echo e($miniItem['tooltip']); ?>">
                                <i class="fa <?php echo e($miniItem['icon']); ?> fs-5"></i>
                            </a>
                        </li>

                        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (! empty($miniItem['divider_after']) && ! $loop->last) { ?>
                            <li>
                                <span class="sidebar-divider lg"></span>
                            </li>
                        <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } $__env->popLoop();
$loop = $__env->getLastLoop();
if ($__empty_1) { ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>
                        <li class="text-muted text-center p-3">
                            <small>No hay menús disponibles</small>
                        </li>
                    <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                </ul>

            </div>
            <!-- ---------------------------------- -->
            <!-- Sidebar Menus -->
            <!-- ---------------------------------- -->
            <div class="sidebarmenu <?php echo e($activeSidebarId ? '' : 'd-none'); ?>">
                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__empty_1 = true;
$__currentLoopData = $allSidebars;
$__env->addLoop($__currentLoopData);
foreach ($__currentLoopData as $sidebarId => $sidebar) {
    $__env->incrementLoopIndices();
    $loop = $__env->getLastLoop();
    $__empty_1 = false; ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
                    <?php
            $sidebarIsActive = $activeSidebarId === $sidebarId;
    ?>
                    <!-- ---------------------------------- -->
                    <!-- Sidebar: <?php echo e($sidebar['sections'][0]['title'] ?? $sidebarId); ?> -->
                    <!-- ---------------------------------- -->
                    <nav class="sidebar-nav scroll-sidebar <?php echo e($sidebarIsActive ? 'd-block' : 'd-none'); ?>"
                         id="menu-right-<?php echo e($sidebarId); ?>"
                         data-simplebar="">
                        <ul class="sidebar-menu" id="sidebarnav-<?php echo e($sidebarId); ?>">
                            <?php
                // Si el sidebar tiene secciones, usarlas; sino crear una sección única
                $sections = isset($sidebar['sections']) && is_array($sidebar['sections'])
                    ? $sidebar['sections']
                    : [['title' => $sidebar['title'] ?? 'Menu', 'items' => $sidebar['items'] ?? []]];
    ?>

                            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__empty_2 = true;
    $__currentLoopData = $sections;
    $__env->addLoop($__currentLoopData);
    foreach ($__currentLoopData as $section) {
        $__env->incrementLoopIndices();
        $loop = $__env->getLastLoop();
        $__empty_2 = false; ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
                                <!-- ---------------------------------- -->
                                <!-- <?php echo e($section['title']); ?> Section -->
                                <!-- ---------------------------------- -->
                                <li class="nav-small-cap">
                                    <span class="hide-menu"><?php echo e($section['title']); ?></span>
                                </li>

                                <!-- Section Items -->
                                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__empty_3 = true;
        $__currentLoopData = $section['items'];
        $__env->addLoop($__currentLoopData);
        foreach ($__currentLoopData as $item) {
            $__env->incrementLoopIndices();
            $loop = $__env->getLastLoop();
            $__empty_3 = false; ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
                                    <?php
                    $itemRoute = $item['route'] ?? '';
            $isItemActive = $itemRoute && (request()->routeIs($itemRoute.'*') || $itemRoute === $activeItemRoute);
            $canAccessItem = NavService::userCanAccessItem($item, auth()->user());
            $hasSubItems = ! empty($item['children']) && is_array($item['children']);
            ?>

                                    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($canAccessItem) { ?>
                                        <!-- ---------------------------------- -->
                                        <!-- <?php echo e($item['label']); ?> -->
                                        <!-- ---------------------------------- -->
                                        <li class="sidebar-item <?php echo e($isItemActive ? 'selected' : ''); ?>">
                                            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($hasSubItems) { ?>
                                                <!-- Item with dropdown -->
                                                <a href="javascript:void(0)"
                                                   class="sidebar-link has-arrow <?php echo e($isItemActive ? 'active' : ''); ?>"
                                                   aria-expanded="<?php echo e($isItemActive ? 'true' : 'false'); ?>">
                                                    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (! empty($item['icon'])) { ?>
                                                        <i class="fa <?php echo e($item['icon']); ?>"></i>
                                                    <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                                                    <span class="hide-menu"><?php echo e($item['label']); ?></span>
                                                </a>

                                                <!-- Sub-items -->
                                                <ul aria-expanded="<?php echo e($isItemActive ? 'true' : 'false'); ?>"
                                                    class="collapse first-level <?php echo e($isItemActive ? 'in' : ''); ?>">
                                                    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__currentLoopData = $item['children'];
                                                $__env->addLoop($__currentLoopData);
                                                foreach ($__currentLoopData as $child) {
                                                    $__env->incrementLoopIndices();
                                                    $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
                                                        <?php
                                                            $childRoute = $child['route'] ?? '';
                                                    $childIsActive = $childRoute && request()->routeIs($childRoute.'*');
                                                    $canAccessChild = NavService::userCanAccessItem($child, auth()->user());
                                                    ?>

                                                        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($canAccessChild) { ?>
                                                            <li class="sidebar-item <?php echo e($childIsActive ? 'active' : ''); ?>">
                                                                <a href="<?php echo e(route($childRoute)); ?>"
                                                                   class="sidebar-link <?php echo e($childIsActive ? 'active' : ''); ?>">
                                                                    <span class="icon-small"></span>
                                                                    <span class="hide-menu"><?php echo e($child['label']); ?></span>
                                                                </a>
                                                            </li>
                                                        <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                                                    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } $__env->popLoop();
                                                $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>
                                                </ul>
                                            <?php } else { ?>
                                                <!-- Simple link without dropdown -->
                                                <a href="<?php echo e($itemRoute ? route($itemRoute) : 'javascript:void(0)'); ?>"
                                                   class="sidebar-link <?php echo e($isItemActive ? 'active' : ''); ?>"
                                                   aria-expanded="false"
                                                   data-current-route="<?php echo e($currentRoute); ?>"
                                                   data-item-route="<?php echo e($itemRoute); ?>"
                                                   data-is-active="<?php echo e($isItemActive ? 'true' : 'false'); ?>">
                                                    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (! empty($item['icon'])) { ?>
                                                        <i class="fa <?php echo e($item['icon']); ?>"></i>
                                                    <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                                                    <span class="hide-menu"><?php echo e($item['label']); ?></span>

                                                    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (! empty($item['badge'])) { ?>
                                                        <span class="badge bg-<?php echo e($item['badge']['color'] ?? 'primary'); ?> rounded ms-auto">
                                                            <?php echo e($item['badge']['text']); ?>

                                                        </span>
                                                    <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                                                </a>
                                            <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                                        </li>
                                    <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } $__env->popLoop();
        $loop = $__env->getLastLoop();
        if ($__empty_3) { ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>
                                    <li class="sidebar-item">
                                        <span class="hide-menu text-muted ps-3">Sin opciones disponibles</span>
                                    </li>
                                <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>

                                <!-- Optional divider after section -->
                                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (! $loop->last) { ?>
                                    <li>
                                        <span class="sidebar-divider"></span>
                                    </li>
                                <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } $__env->popLoop();
    $loop = $__env->getLastLoop();
    if ($__empty_2) { ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>
                                <li class="sidebar-item">
                                    <span class="hide-menu text-muted ps-3">Sin secciones configuradas</span>
                                </li>
                            <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                        </ul>
                    </nav>
                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } $__env->popLoop();
$loop = $__env->getLastLoop();
if ($__empty_1) { ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>
                    <!-- No sidebars available -->
                    <div class="p-3 text-center text-muted">
                        <small>No hay menús configurados</small>
                    </div>
                <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
            </div>
        </div>
    </div>
</aside>

<?php $__env->startPush('scripts'); ?>
<script>
    /**
     * Toggle sidebar visibility when clicking on mini nav items
     * Updates the active state and shows the corresponding sidebar
     */
    document.addEventListener('DOMContentLoaded', function() {
        'use strict';

        // Reemplazar cada mini-nav-item con un clon para eliminar listeners previos (sidebarmenu.js)
        document.querySelectorAll('.mini-nav-item').forEach(item => {
            const clone = item.cloneNode(true);
            item.parentNode.replaceChild(clone, item);
        });

        // Add click handlers to mini nav items
        document.querySelectorAll('.mini-nav-item').forEach(item => {
            item.addEventListener('click', function(e) {
                // Check if this item has a direct URL
                const directUrl = this.dataset.directUrl;
                if (directUrl) {
                    // Let the link handle navigation naturally
                    return;
                }

                // Prevent default for sidebar toggles
                e.preventDefault();

                // Get the sidebar ID from data attribute
                const sidebarId = this.dataset.sidebarId;
                if (!sidebarId) {
                    console.warn('No sidebar ID found for mini item:', this.id);
                    return;
                }

                // Remove 'selected' class from all mini items
                document.querySelectorAll('.mini-nav-item').forEach(navItem => {
                    navItem.classList.remove('selected');
                });

                // Add 'selected' class to clicked mini item
                this.classList.add('selected');

                // Hide all sidebars
                document.querySelectorAll('.sidebar-nav').forEach(nav => {
                    nav.classList.remove('d-block');
                    nav.classList.add('d-none');
                });

                // Show the corresponding sidebar
                const targetSidebar = document.querySelector(`#menu-right-${sidebarId}`);
                if (targetSidebar) {
                    targetSidebar.classList.remove('d-none');
                    targetSidebar.classList.add('d-block');

                    // Asegurar que el contenedor y el aside estén visibles
                    const sidebarmenu = document.querySelector('.sidebarmenu');
                    if (sidebarmenu) sidebarmenu.classList.remove('d-none');

                    const miniPanel = document.querySelector('aside.side-mini-panel');
                    if (miniPanel) miniPanel.classList.add('with-vertical');
                } else {
                    console.warn(`Sidebar not found: menu-right-${sidebarId}`);
                }

                // Set body attribute to full sidebar mode
                document.body.setAttribute('data-sidebartype', 'full');
            });
        });

        // Handle multilevel menu clicks
        document.querySelectorAll('.sidebar-link.has-arrow').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();

                const isActive = this.classList.contains('active');
                const parentUl = this.closest('ul');
                const submenu = this.nextElementSibling;

                if (!isActive) {
                    // Close any open menus and remove all other classes
                    parentUl.querySelectorAll('ul').forEach(function(ul) {
                        ul.classList.remove('in');
                    });
                    parentUl.querySelectorAll('a').forEach(function(navLink) {
                        navLink.classList.remove('active');
                    });

                    // Open our new menu and add the open class
                    if (submenu) {
                        submenu.classList.add('in');
                    }
                    this.classList.add('active');
                } else {
                    this.classList.remove('active');
                    if (submenu) {
                        submenu.classList.remove('in');
                    }
                }
            });
        });
    });
</script>
<?php $__env->stopPush(); ?>
<?php /**PATH /Users/developerts/Herd/system/modules/Theme/resources/views/theme/includes/nav.blade.php ENDPATH**/ ?>