<table class="subcopy" width="100%" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td>
<?php
use Illuminate\Mail\Markdown;
use Illuminate\Support\EncodedHtmlString;

echo new EncodedHtmlString(Markdown::parse($slot)); ?>

</td>
</tr>
</table>
<?php /**PATH /Users/developerts/Herd/system/resources/views/vendor/mail/html/subcopy.blade.php ENDPATH**/ ?>