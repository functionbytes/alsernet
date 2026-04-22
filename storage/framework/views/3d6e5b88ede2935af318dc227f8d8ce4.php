<?php echo $__env->make('template::partials.header', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

<main class="main" id="main-section">
    <?php echo $__env->yieldContent('content'); ?>
</main>

<?php echo $__env->make('template::partials.footer', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php /**PATH /Users/developerts/Herd/system/platform/themes/caixilhariablanco/layouts/homepage.blade.php ENDPATH**/ ?>