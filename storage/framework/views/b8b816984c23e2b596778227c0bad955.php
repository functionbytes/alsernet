<?php
use Livewire\Mechanisms\ExtendBlade\ExtendBlade;

echo $__env->make('template::partials.header', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

<main class="main" id="main-section">
    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (Theme::get('hasBreadcrumb', true)) { ?>
        <?php echo $__env->make('template::partials.breadcrumb', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>

    <div class="container">
        <?php echo $__env->yieldContent('content'); ?>
    </div>
</main>

<?php echo $__env->make('template::partials.footer', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php /**PATH /Users/developerts/Herd/system/platform/themes/wowy/layouts/default.blade.php ENDPATH**/ ?>