<?php

use Livewire\Mechanisms\ExtendBlade\ExtendBlade;

    $validGutters = ['none', 'sm', 'md', 'lg'];

$gutter = in_array($attrs['gutter'] ?? 'md', $validGutters) ? ($attrs['gutter'] ?? 'md') : 'md';
$extraClass = $attrs['class'] ?? null;

$classes = collect([
    'row',
    'grid',
    $gutter !== 'none' ? 'gutter-'.$gutter : 'g-0',
    $extraClass,
])->filter()->implode(' ');
?>

<?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (trim($content) !== '') { ?>
    <div class="<?php echo e($classes); ?>">
        <?php echo $content; ?>

        
        <div class="grid-space col-1" aria-hidden="true"></div>
    </div>
<?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
<?php /**PATH /Users/developerts/Herd/system/modules/Template/Templates/Riode/Resources/views/shortcodes/creative-grid.blade.php ENDPATH**/ ?>