<?php
use Livewire\Mechanisms\ExtendBlade\ExtendBlade;

$clarityId = (string) setting('microsoft_clarity_id', ''); ?>
<?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($clarityId !== '') { ?>
<!-- Microsoft Clarity -->
<script type="text/javascript">
(function(c,l,a,r,i,t,y){c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};
t=l.createElement(r);t.async=1;t.src="https://www.clarity.ms/tag/"+i;
y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);
})(window,document,"clarity","script","<?php echo e($clarityId); ?>");
</script>
<!-- End Microsoft Clarity -->
<?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
<?php /**PATH /Users/developerts/Herd/system/modules/Analytics/app/Providers/../../resources/views/partials/_microsoft_clarity.blade.php ENDPATH**/ ?>