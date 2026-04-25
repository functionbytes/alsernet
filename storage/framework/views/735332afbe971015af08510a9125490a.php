    <?php
use Livewire\Mechanisms\ExtendBlade\ExtendBlade;

echo dynamic_sidebar('top_footer_sidebar'); ?>

    <footer class="main">
        <section class="section-padding-60">
            <div class="container">
                <div class="row">
                    <?php echo dynamic_sidebar('footer_sidebar'); ?>

                </div>
            </div>
        </section>
        <div class="container pb-20 wow fadeIn animated">
            <div class="row">
                <div class="col-12 mb-20">
                    <div class="footer-bottom"></div>
                </div>
                <div class="col-lg-6">
                    <p class="float-md-left font-sm text-muted mb-0"><?php echo Theme::getSiteCopyright(); ?></p>
                </div>
                <div class="col-lg-6">
                    <p class="text-lg-end text-center font-sm text-muted mb-0">
                        <?php echo e(__('Todos los derechos reservados.')); ?>

                    </p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Quick view -->
    <div class="modal fade custom-modal quick-view-modal" id="quick-view-modal" tabindex="-1" aria-labelledby="quick-view-modal-label" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                <div class="modal-body">
                    <div class="half-circle-spinner loading-spinner">
                        <div class="circle circle-1"></div>
                        <div class="circle circle-2"></div>
                    </div>
                    <div class="quick-view-content"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        window.currencies = <?php echo json_encode(get_currencies_json()); ?>;
    </script>

    <?php echo Theme::footer(); ?>


    <script>
        window.trans = {
            "Views": "<?php echo e(__('Views')); ?>",
            "Read more": "<?php echo e(__('Read more')); ?>",
            "days": "<?php echo e(__('days')); ?>",
            "hours": "<?php echo e(__('hours')); ?>",
            "mins": "<?php echo e(__('mins')); ?>",
            "sec": "<?php echo e(__('sec')); ?>",
            "No reviews!": "<?php echo e(__('No reviews!')); ?>"
        };

        window.trackedStartCheckout = '<?php echo e(session('tracked_start_checkout')); ?>';
        window.siteUrl = '<?php echo e(url('/')); ?>';
    </script>

    <?php echo Theme::place('footer'); ?>


    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (session()->has('success_msg') || session()->has('error_msg') || (isset($errors) && $errors->count() > 0) || isset($error_msg)) { ?>
        <script type="text/javascript">
            window.onload = function () {
                <?php if (session()->has('success_msg')) { ?>
                    window.showAlert('alert-success', '<?php echo e(session('success_msg')); ?>');
                <?php } ?>

                <?php if (session()->has('error_msg')) { ?>
                    window.showAlert('alert-danger', '<?php echo e(session('error_msg')); ?>');
                <?php } ?>

                <?php if (isset($error_msg)) { ?>
                    window.showAlert('alert-danger', '<?php echo e($error_msg); ?>');
                <?php } ?>

                <?php if (isset($errors)) { ?>
                    <?php $__currentLoopData = $errors->all();
                    $__env->addLoop($__currentLoopData);
                    foreach ($__currentLoopData as $error) {
                        $__env->incrementLoopIndices();
                        $loop = $__env->getLastLoop(); ?>
                        window.showAlert('alert-danger', '<?php echo clean($error); ?>');
                    <?php } $__env->popLoop();
                    $loop = $__env->getLastLoop(); ?>
                <?php } ?>
            };
        </script>
    <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>

    <div id="scrollUp"><i class="fal fa-long-arrow-up"></i></div>
</body>
</html>
<?php /**PATH /Users/developerts/Herd/system/platform/themes/wowy/partials/footer.blade.php ENDPATH**/ ?>