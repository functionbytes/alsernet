<div class="nav-menu-wrapper">
    <ul class="navbar-nav mr-auto" id="menu">
        <?php
use Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys;
        use Livewire\Mechanisms\ExtendBlade\ExtendBlade;

        if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__currentLoopData = $menu_nodes;
        $__env->addLoop($__currentLoopData);
        foreach ($__currentLoopData as $row) {
            $__env->incrementLoopIndices();
            $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
            <li class="nav-item<?php echo e($row->children->isNotEmpty() ? ' submenu' : ''); ?><?php echo e($row->isActive() ? ' active' : ''); ?><?php echo e($row->css_class ? ' '.$row->css_class : ''); ?>">
                <a class="nav-link<?php echo e($row->isActive() ? ' active' : ''); ?>" href="<?php echo e($row->full_url); ?>" target="<?php echo e($row->target ?? '_self'); ?>">
                    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($row->icon) { ?>
                        <i class="<?php echo e($row->icon); ?>"></i>
                    <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                    <?php echo e($row->display_title); ?>

                </a>

                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($row->children->isNotEmpty()) { ?>
                    <ul class="mega-menu-service">
                        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__currentLoopData = $row->children;
                    $__env->addLoop($__currentLoopData);
                    foreach ($__currentLoopData as $child) {
                        $__env->incrementLoopIndices();
                        $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
                            <li class="nav-item<?php echo e($child->isActive() ? ' active' : ''); ?>">
                                <a class="mega-menu-service-single" href="<?php echo e($child->full_url); ?>" target="<?php echo e($child->target ?? '_self'); ?>">
                                    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($child->icon) { ?>
                                        <span class="mega-menu-service-icon"><i class="<?php echo e($child->icon); ?>"></i></span>
                                    <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                                    <span class="mega-menu-service-title"><?php echo e($child->display_title); ?></span>
                                    <span class="mega-menu-service-nav">
                                        <i class="fa-solid fa-chevron-right"></i><i class="fa-solid fa-chevron-right"></i>
                                    </span>
                                </a>
                            </li>
                        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } $__env->popLoop();
                    $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>
                    </ul>
                <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
            </li>
        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } $__env->popLoop();
        $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>
    </ul>
</div>
<?php /**PATH /Users/developerts/Herd/system/platform/themes/caixilhariablanco/partials/main-menu.blade.php ENDPATH**/ ?>