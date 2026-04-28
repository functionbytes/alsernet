<?php

use Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys;
use Livewire\Mechanisms\ExtendBlade\ExtendBlade;

    $validLayouts = ['centered', 'inline', 'start'];
$validBackgrounds = ['dark', 'grey', 'image', 'none'];
$validTextColors = ['light', 'dark'];
$validMinHeights = ['sm', 'md', 'lg'];

$title = $attrs['title'] ?? '';
$subtitle = $attrs['subtitle'] ?? null;
$layout = in_array($attrs['layout'] ?? 'centered', $validLayouts) ? ($attrs['layout'] ?? 'centered') : 'centered';
$background = in_array($attrs['background'] ?? 'dark', $validBackgrounds) ? ($attrs['background'] ?? 'dark') : 'dark';
$bgImage = $attrs['background-image'] ?? null;
$textColor = in_array($attrs['text-color'] ?? 'light', $validTextColors) ? ($attrs['text-color'] ?? 'light') : 'light';
$minHeight = in_array($attrs['min-height'] ?? 'md', $validMinHeights) ? ($attrs['min-height'] ?? 'md') : 'md';
$extraClass = $attrs['class'] ?? null;

// Degradar background=image si no hay imagen
if ($background === 'image' && empty($bgImage)) {
    $background = 'dark';
}

$crumbsRaw = $attrs['breadcrumb'] ?? null;
$crumbs = [];
if ($crumbsRaw) {
    $crumbs = is_string($crumbsRaw) ? (json_decode($crumbsRaw, true) ?? []) : (array) $crumbsRaw;
    $crumbs = is_array($crumbs) ? $crumbs : [];
}

$classes = collect([
    'page-header',
    $background === 'dark' ? 'dark-section' : null,
    $background === 'grey' ? 'grey-section' : null,
    $layout === 'inline' ? 'bread-inline breadcrumb-resize-padding' : null,
    $layout === 'start' ? 'align-items-start breadcrumb-resize-padding' : null,
    $textColor === 'light' ? 'text-white' : null,
    'page-header-'.$minHeight,
    $extraClass,
])->filter()->implode(' ');
?>

<div class="<?php echo e($classes); ?>"
     <?php if ($background === 'image' && $bgImage) { ?> data-bg-image="<?php echo e(htmlspecialchars($bgImage, ENT_QUOTES)); ?>" <?php } ?>>

    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($background === 'image' && $bgImage) { ?>
        <figure class="page-header-bg">
            <img src="<?php echo e(htmlspecialchars($bgImage, ENT_QUOTES)); ?>"
                 alt="<?php echo e(htmlspecialchars($title, ENT_QUOTES)); ?>"
                 loading="eager">
        </figure>
    <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>

    <h2 class="page-title"><?php echo e($title); ?></h2>

    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($subtitle) { ?>
        <p class="page-subtitle"><?php echo e($subtitle); ?></p>
    <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>

    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (! empty($crumbs)) { ?>
        <ul class="breadcrumb">
            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__currentLoopData = array_values($crumbs);
        $__env->addLoop($__currentLoopData);
        foreach ($__currentLoopData as $index => $item) {
            $__env->incrementLoopIndices();
            $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
                <?php $isLast = $index === count($crumbs) - 1; ?>
                <li <?php if ($isLast) { ?> class="active" aria-current="page" <?php } ?>>
                    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (! $isLast && ! empty($item['url'])) { ?>
                        <a href="<?php echo e(htmlspecialchars($item['url'], ENT_QUOTES)); ?>"><?php echo e($item['label']); ?></a>
                    <?php } else { ?>
                        <?php echo e($item['label']); ?>

                    <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                </li>
                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (! $isLast) { ?>
                    <li class="delimiter" aria-hidden="true">/</li>
                <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } $__env->popLoop();
        $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>
        </ul>
    <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>

</div>

<?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($background === 'image' && $bgImage) { ?>
    <?php $__env->startPush('scripts'); ?>
    <script>
    $(function () {
        $('[data-bg-image]').each(function () {
            var url = $(this).data('bg-image');
            if (url) {
                this.style.backgroundImage = "url('" + url + "')";
            }
        });
    });
    </script>
    <?php $__env->stopPush(); ?>
<?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
<?php /**PATH /Users/developerts/Herd/system/modules/Template/Templates/Riode/Resources/views/shortcodes/page-header.blade.php ENDPATH**/ ?>