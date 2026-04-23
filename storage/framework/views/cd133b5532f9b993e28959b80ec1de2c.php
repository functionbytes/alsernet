<?php
use Livewire\Mechanisms\ExtendBlade\ExtendBlade;

$gtmId = (string) seo_setting('gtm.container_id', config('Seo.gtm.container_id', '')); ?>
<?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($gtmId !== '') { ?>
<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','<?php echo e($gtmId); ?>');</script>
<!-- End Google Tag Manager -->
<?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
<?php /**PATH /Users/developerts/Herd/system/modules/Seo/app/Providers/../../resources/views/partials/gtm-head.blade.php ENDPATH**/ ?>