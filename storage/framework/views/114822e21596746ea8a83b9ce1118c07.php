<?php

use Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys;
use Livewire\Mechanisms\ExtendBlade\ExtendBlade;

    // bg-color: validar hex — inline style is the ONLY viable option here per the doc analysis.
// The project's no-inline-style rule has a documented exception for this shortcode:
// the color value is runtime/dynamic and comes from the shortcode attribute, so
// it cannot be pre-compiled into a CSS class. The value is sanitized via regex.
$bgColor = preg_match('/^#[0-9A-Fa-f]{6}$/', $attrs['bg-color'] ?? '') ? $attrs['bg-color'] : '#4b5577';
$textClass = ($attrs['text-color'] ?? 'light') === 'dark' ? ' text-dark' : '';
$icon = preg_match('/^(fas|far|fab) fa-[a-z0-9-]+$/', $attrs['icon'] ?? '') ? $attrs['icon'] : 'fas fa-folder';
$name = $attrs['name'] ?? '';
$href = $attrs['href'] ?? '#';
?>

<div class="category category-group-icon<?php echo e($textClass); ?>" data-bg-color="<?php echo e($bgColor); ?>" style="background-color: <?php echo e($bgColor); ?>;">
    <a href="<?php echo e($href); ?>">
        <figure class="category-media">
            <i class="<?php echo e($icon); ?>" aria-hidden="true"></i>
        </figure>
        <h4 class="category-name"><?php echo e($name); ?></h4>
    </a>

    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (! empty($subcategories)) { ?>
        <div class="category-content">
            <ul class="category-list">
                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__currentLoopData = $subcategories;
        $__env->addLoop($__currentLoopData);
        foreach ($__currentLoopData as $sub) {
            $__env->incrementLoopIndices();
            $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
                    <li>
                        <a href="<?php echo e($sub['url'] ?? '#'); ?>"><?php echo e($sub['label'] ?? ''); ?></a>
                    </li>
                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } $__env->popLoop();
        $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>
            </ul>
        </div>
    <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
</div>
<?php /**PATH /Users/developerts/Herd/system/modules/Template/Templates/Riode/Resources/views/shortcodes/subcategory-card.blade.php ENDPATH**/ ?>