<?php

use Livewire\Mechanisms\ExtendBlade\ExtendBlade;

    $gaMeasurementId = setting('google_analytics_measurement_id');
$gaEnabled = (bool) setting('google_analytics_enable');
?>

<?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($gaEnabled && $gaMeasurementId) { ?>
<!-- Google Analytics GA4 — <?php echo e($gaMeasurementId); ?> -->
<script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo e($gaMeasurementId); ?>"></script>
<script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', '<?php echo e($gaMeasurementId); ?>', {
        anonymize_ip: true,
        cookie_flags: 'SameSite=None;Secure'
    });
</script>
<?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
<?php /**PATH /Users/developerts/Herd/system/modules/Analytics/app/Providers/../../resources/views/partials/_gtag.blade.php ENDPATH**/ ?>