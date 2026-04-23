<?php

use Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys;
use Livewire\Mechanisms\ExtendBlade\ExtendBlade;

    $activeLang = $currentPageLocale ?? app()->getLocale();
$localeNames = ['es' => 'Español', 'pt' => 'Português', 'en' => 'English', 'fr' => 'Français'];
$availableLinks = collect($pageLangLinks ?? [])
    ->filter(fn ($info, $lang) => $lang !== $activeLang && ! empty($info['url']) && $info['published'])
    ->all();
?>

<?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (count($availableLinks) > 0) { ?>
    <li>
        <a class="language-dropdown-active" href="#">
            <i class="fa fa-globe"></i> <?php echo e(strtoupper($activeLang)); ?> <i class="fa fa-chevron-down"></i>
        </a>
        <ul class="language-dropdown">
            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__currentLoopData = $availableLinks;
    $__env->addLoop($__currentLoopData);
    foreach ($__currentLoopData as $lang => $info) {
        $__env->incrementLoopIndices();
        $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
                <li>
                    <a rel="alternate" hreflang="<?php echo e($lang); ?>" href="<?php echo e($info['url']); ?>">
                        <?php echo e($localeNames[$lang] ?? strtoupper($lang)); ?>

                    </a>
                </li>
            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } $__env->popLoop();
    $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>
        </ul>
    </li>
<?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
<?php /**PATH /Users/developerts/Herd/system/platform/themes/caixilhariablanco/partials/language-switcher.blade.php ENDPATH**/ ?>