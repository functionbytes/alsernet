<?php

use Livewire\Mechanisms\ExtendBlade\ExtendBlade;

Theme::set('page', $page);
$template = $page->template ?? 'default';
?>

<?php $__env->startSection('seo_head'); ?>
    <?php echo $__env->make(Theme::getThemeNamespace().'::partials.seo-head', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>


    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($template === 'default') { ?>
        <section class="mt-60 mb-60">
            <div class="ck-content">
                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($transContent) { ?>
                    <?php echo $transContent; ?>

                <?php } else { ?>
                    <p class="text-muted text-center"><?php echo e(trans('page::messages.no_content')); ?></p>
                <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
            </div>
            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (config('page.social_share.enabled', true) && view()->exists('template::partials.social-share')) { ?>
                <?php echo $__env->make('template::partials.social-share', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
        </section>
    <?php } else { ?>
        <div class="ck-content">
            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($transContent) { ?>
                <?php echo $transContent; ?>

            <?php } else { ?>
                <p class="text-muted text-center"><?php echo e(trans('page::messages.no_content')); ?></p>
            <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
        </div>
        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (config('page.social_share.enabled', true) && view()->exists('template::partials.social-share')) { ?>
            <?php echo $__env->make('template::partials.social-share', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
    <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
<?php $__env->stopSection(); ?>


<?php $__env->startPush('scripts'); ?>
<script>
// Time-on-page tracking
(function () {
    var pageId = <?php echo e($page->id ?? 'null'); ?>;
    var ipHash = '<?php echo e(hash('sha256', request()->ip())); ?>';
    var trackUrl = '<?php echo e(url('/api/v1/pages/track-time')); ?>';

    if (!pageId) { return; }

    var startTime = Date.now();

    function sendTime(isUnload) {
        var seconds = Math.round((Date.now() - startTime) / 1000);
        if (seconds < 1) { return; }

        var data = JSON.stringify({ page_id: pageId, time_seconds: seconds, ip_hash: ipHash });
        var csrfToken = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

        if (isUnload && navigator.sendBeacon) {
            navigator.sendBeacon(trackUrl, new Blob([data], { type: 'application/json' }));
            return;
        }

        fetch(trackUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: data,
            keepalive: true,
        }).catch(function () {});
    }

    window.addEventListener('beforeunload', function () { sendTime(true); });

    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'hidden') { sendTime(false); }
    });
}());
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('template::layouts.default', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/developerts/Herd/system/platform/themes/caixilhariablanco/views/page.blade.php ENDPATH**/ ?>