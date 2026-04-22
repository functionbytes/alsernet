<?php
use Illuminate\Support\EncodedHtmlString;
use Illuminate\View\ComponentAttributeBag;

$attributes ??= new ComponentAttributeBag;

$__newAttributes = [];
$__propNames = ComponentAttributeBag::extractPropNames(([
    'url',
    'color' => 'primary',
    'align' => 'center',
]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter(([
    'url',
    'color' => 'primary',
    'align' => 'center',
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) {
        unset($$__key);
    }
}

unset($__defined_vars, $__key, $__value); ?>
<table class="action" align="<?php echo new EncodedHtmlString($align); ?>" width="100%" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td align="<?php echo new EncodedHtmlString($align); ?>">
<table width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td align="<?php echo new EncodedHtmlString($align); ?>">
<table border="0" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td>
<a href="<?php echo new EncodedHtmlString($url); ?>" class="button button-<?php echo new EncodedHtmlString($color); ?>" target="_blank" rel="noopener"><?php echo $slot; ?></a>
</td>
</tr>
</table>
</td>
</tr>
</table>
</td>
</tr>
</table>
<?php /**PATH /Users/developerts/Herd/system/resources/views/vendor/mail/html/button.blade.php ENDPATH**/ ?>