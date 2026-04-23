<?php
use Livewire\Mechanisms\ExtendBlade\ExtendBlade;

$linkedinPartnerId = (string) setting('linkedin_insight_tag_id', ''); ?>
<?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($linkedinPartnerId !== '') { ?>
<!-- LinkedIn Insight Tag -->
<script type="text/javascript">
_linkedin_partner_id = "<?php echo e($linkedinPartnerId); ?>";
window._linkedin_data_partner_ids = window._linkedin_data_partner_ids || [];
window._linkedin_data_partner_ids.push(_linkedin_partner_id);
(function(l){if(!l){window.lintrk=function(a,b){window.lintrk.q.push([a,b])};window.lintrk.q=[]}
var s=document.getElementsByTagName("script")[0];var b=document.createElement("script");
b.type="text/javascript";b.async=true;b.src="https://snap.licdn.com/li.lms-analytics/insight.min.js";
s.parentNode.insertBefore(b,s);})(window.lintrk);
</script>
<noscript><img height="1" width="1" style="display:none;" alt=""
src="https://px.ads.linkedin.com/collect/?pid=<?php echo e($linkedinPartnerId); ?>&fmt=gif"/></noscript>
<!-- End LinkedIn Insight Tag -->
<?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
<?php /**PATH /Users/developerts/Herd/system/modules/Analytics/app/Providers/../../resources/views/partials/_linkedin_insight.blade.php ENDPATH**/ ?>