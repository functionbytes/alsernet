<div class="mc-content-header">
    <div class="mc-content-header-inner">
        <div>
            <h1 class="mc-page-title"><?php
use Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys;
            use Livewire\Mechanisms\ExtendBlade\ExtendBlade;

            echo e($title); ?></h1>
            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (isset($subtitle)) { ?>
                <p class="mc-page-subtitle"><?php echo e($subtitle); ?></p>
            <?php } elseif (isset($description)) { ?>
                <p class="mc-section-desc"><?php echo e($description); ?></p>
            <?php } else { ?>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (isset($breadcrumbs) && is_array($breadcrumbs)) { ?>
                            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__currentLoopData = $breadcrumbs;
                            $__env->addLoop($__currentLoopData);
                            foreach ($__currentLoopData as $crumb) {
                                $__env->incrementLoopIndices();
                                $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
                                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($loop->last) { ?>
                                    <li class="breadcrumb-item active" aria-current="page"><?php echo e($crumb['label']); ?></li>
                                <?php } else { ?>
                                    <li class="breadcrumb-item">
                                        <a class="text-muted text-decoration-none" href="<?php echo e($crumb['url'] ?? '#'); ?>"><?php echo e($crumb['label']); ?></a>
                                    </li>
                                <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } $__env->popLoop();
                            $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>
                        <?php } else { ?>
                            <li class="breadcrumb-item">
                                <a class="text-muted text-decoration-none" href="<?php echo e(url('panel/dashboard')); ?>">Dashboard</a>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page"><?php echo e($title); ?></li>
                        <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                    </ol>
                </nav>
            <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
        </div>

        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (isset($actions)) { ?>
            <div class="mc-banner-actions">
                <?php echo $actions; ?>

            </div>
        <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
    </div>
</div>
<?php /**PATH /Users/developerts/Herd/system/modules/Core/resources/views/components/card.blade.php ENDPATH**/ ?>