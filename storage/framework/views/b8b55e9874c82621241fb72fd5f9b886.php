<?php
Theme::layout('homepage');
Theme::set('page', $page);
?>

<?php $__env->startSection('seo_head'); ?>
    <?php echo $__env->make(Theme::getThemeNamespace().'::partials.seo-head', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

<?php echo $transContent ?? ''; ?>


<?php $__env->stopSection(); ?>

<?php echo $__env->make('template::layouts.homepage', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/developerts/Herd/system/platform/themes/caixilhariablanco/views/index.blade.php ENDPATH**/ ?>