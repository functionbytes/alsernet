<tr>
<td>
<table class="footer" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td class="content-cell" align="center">
<?php
use Illuminate\Mail\Markdown;
use Illuminate\Support\EncodedHtmlString;

echo new EncodedHtmlString(Markdown::parse($slot)); ?>

</td>
</tr>
</table>
</td>
</tr>
<?php /**PATH /Users/developerts/Herd/system/resources/views/vendor/mail/html/footer.blade.php ENDPATH**/ ?>