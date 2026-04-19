<?php
use Livewire\Mechanisms\ExtendBlade\ExtendBlade;

echo strip_tags($header ?? ''); ?>


<?php echo strip_tags($slot); ?>

<?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (isset($subcopy)) { ?>

<?php echo strip_tags($subcopy); ?>

<?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>

<?php echo strip_tags($footer ?? ''); ?>

<?php /**PATH /Users/developerts/Herd/system/resources/views/vendor/mail/text/layout.blade.php ENDPATH**/ ?>