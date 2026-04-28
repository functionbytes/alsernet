<?php

use Livewire\Mechanisms\ExtendBlade\ExtendBlade;

    $icon = $attrs['icon'] ?? null;
$title = $attrs['title'] ?? null;
$description = $attrs['description'] ?? null;
$layout = in_array($attrs['layout'] ?? '', ['top', 'side']) ? $attrs['layout'] : 'top';
$style = in_array($attrs['style'] ?? '', ['default', 'border', 'inversed', 'solid', 'tiny']) ? $attrs['style'] : 'default';
$color = in_array($attrs['color'] ?? '', ['default', 'primary', 'secondary', 'dark']) ? $attrs['color'] : 'default';
$href = $attrs['href'] ?? null;
$align = in_array($attrs['align'] ?? '', ['left', 'center', 'right']) ? $attrs['align'] : 'center';
$extraClass = $attrs['class'] ?? null;

if (! $icon || ! $title) {
    return;
}

$classes = collect([
    'icon-box',
    $layout === 'side' ? 'icon-box-side' : null,
    $style === 'border' ? 'icon-border' : null,
    $style === 'inversed' ? 'icon-inversed' : null,
    $style === 'solid' ? 'icon-solid' : null,
    $style === 'tiny' ? 'icon-tiny' : null,
    $color !== 'default' ? 'icon-color-'.$color : null,
    'text-'.$align,
    $extraClass,
])->filter()->implode(' ');

$tag = $href ? 'a' : 'div';
?>

<<?php echo e($tag); ?> class="<?php echo e($classes); ?>"<?php if ($href) { ?> href="<?php echo e($href); ?>"<?php } ?>>
    <span class="icon-box-icon">
        <i class="<?php echo e($icon); ?>" aria-hidden="true"></i>
    </span>
    <div class="icon-box-content">
        <h4 class="icon-box-title"><?php echo e($title); ?></h4>
        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($description) { ?>
            <p><?php echo e($description); ?></p>
        <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
    </div>
</<?php echo e($tag); ?>>
<?php /**PATH /Users/developerts/Herd/system/modules/Template/Templates/Riode/Resources/views/shortcodes/icon-box.blade.php ENDPATH**/ ?>