<?php

use Livewire\Mechanisms\ExtendBlade\ExtendBlade;

    $validLevels = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'];
$validSizes = ['sm', 'md', 'lg'];
$validAligns = ['left', 'center', 'right'];
$validDecorations = ['none', 'cross-left', 'cross-center', 'cross-right', 'underline-simple', 'underline-active', 'underline-custom'];
$validColors = ['default', 'primary', 'white', 'dark'];

$level = in_array($attrs['level'] ?? 'h2', $validLevels) ? ($attrs['level'] ?? 'h2') : 'h2';
$size = in_array($attrs['size'] ?? 'md', $validSizes) ? ($attrs['size'] ?? 'md') : 'md';
$align = in_array($attrs['align'] ?? 'center', $validAligns) ? ($attrs['align'] ?? 'center') : 'center';
$decoration = in_array($attrs['decoration'] ?? 'none', $validDecorations) ? ($attrs['decoration'] ?? 'none') : 'none';
$color = in_array($attrs['color'] ?? 'default', $validColors) ? ($attrs['color'] ?? 'default') : 'default';
$uppercase = filter_var($attrs['uppercase'] ?? false, FILTER_VALIDATE_BOOLEAN);
$description = $attrs['description'] ?? null;

$classes = collect([
    'title',
    'title-'.$align,
    $size === 'sm' ? 'title-sm' : null,
    $size === 'lg' ? 'title-lg' : null,
    $decoration === 'cross-left' ? 'title-line' : null,
    $decoration === 'cross-center' ? 'title-line title-right-line' : null,
    $decoration === 'cross-right' ? 'title-right-line' : null,
    $decoration === 'underline-simple' ? 'title-line title-underline title-underline-simple' : null,
    $decoration === 'underline-active' ? 'title-line title-underline' : null,
    $decoration === 'underline-custom' ? 'title-custom-underline' : null,
    $color !== 'default' ? 'text-'.$color : null,
    $uppercase ? 'text-uppercase' : null,
    ! empty($description) ? 'title-descri' : null,
    ! empty($attrs['class']) ? htmlspecialchars($attrs['class']) : null,
])->filter()->implode(' ');

$needsSpan = in_array($decoration, ['underline-simple', 'underline-active']);
?>

<<?php echo e($level); ?> class="<?php echo e($classes); ?>">
    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($needsSpan) { ?>
        <span><?php echo $content; ?></span>
    <?php } else { ?>
        <?php echo $content; ?>

    <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
</<?php echo e($level); ?>>

<?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (! empty($description)) { ?>
    <p class="title-descri text-<?php echo e($align); ?> text-body"><?php echo e($description); ?></p>
<?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
<?php /**PATH /Users/developerts/Herd/system/modules/Template/Templates/Riode/Resources/views/shortcodes/title.blade.php ENDPATH**/ ?>