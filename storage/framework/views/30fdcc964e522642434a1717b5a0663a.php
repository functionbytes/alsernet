<?php
use Livewire\Mechanisms\ExtendBlade\ExtendBlade;

$gtmId = (string) seo_setting('gtm.container_id', config('Seo.gtm.container_id', '')); ?>
<?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($gtmId !== '') { ?>
<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?php echo e($gtmId); ?>" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->
<?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
<?php /**PATH /Users/developerts/Herd/system/modules/Seo/app/Providers/../../resources/views/partials/gtm-body.blade.php ENDPATH**/ ?>