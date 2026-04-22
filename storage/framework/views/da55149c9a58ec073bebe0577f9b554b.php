<ul>
    <?php
use Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys;
    use Livewire\Mechanisms\ExtendBlade\ExtendBlade;

    if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__currentLoopData = $menu_nodes;
    $__env->addLoop($__currentLoopData);
    foreach ($__currentLoopData as $row) {
        $__env->incrementLoopIndices();
        $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
        <li>
            <a href="<?php echo e($row->full_url); ?>" target="<?php echo e($row->target ?? '_self'); ?>">
                <?php echo e($row->display_title); ?>

            </a>
        </li>
    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } $__env->popLoop();
    $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>
</ul>
<?php /**PATH /Users/developerts/Herd/system/platform/themes/caixilhariablanco/partials/footer-menu.blade.php ENDPATH**/ ?>