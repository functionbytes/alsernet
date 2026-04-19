<?php
use Illuminate\Support\EncodedHtmlString;
use Illuminate\View\AnonymousComponent;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\ComponentAttributeBag;
use Illuminate\View\Factory;
use Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys;
use Livewire\Mechanisms\ExtendBlade\ExtendBlade;

if (isset($component)) {
    $__componentOriginalaa758e6a82983efcbf593f765e026bd9 = $component;
} ?>
<?php if (isset($attributes)) {
    $__attributesOriginalaa758e6a82983efcbf593f765e026bd9 = $attributes;
} ?>
<?php $component = AnonymousComponent::resolve(['view' => $__env->getContainer()->make(Factory::class)->make('mail::message'), 'data' => []] + (isset($attributes) && $attributes instanceof ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('mail::message'); ?>
<?php if ($component->shouldRender()) { ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof ComponentAttributeBag) { ?>
<?php $attributes = $attributes->except(AnonymousComponent::ignoredParameterNames()); ?>
<?php } ?>
<?php $component->withAttributes([]); ?>
<?php SupportCompiledWireKeys::processComponentKey($component); ?>


<?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (! empty($greeting)) { ?>
# <?php echo new EncodedHtmlString($greeting); ?>

<?php } else { ?>
<?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($level === 'error') { ?>
# <?php echo app('translator')->get('Whoops!'); ?>
<?php } else { ?>
# <?php echo app('translator')->get('Hello!'); ?>
<?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
<?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>


<?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__currentLoopData = $introLines;
    $__env->addLoop($__currentLoopData);
    foreach ($__currentLoopData as $line) {
        $__env->incrementLoopIndices();
        $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
<?php echo new EncodedHtmlString($line); ?>


<?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } $__env->popLoop();
    $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>


<?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (isset($actionText)) { ?>
<?php
    $color = match ($level) {
        'success', 'error' => $level,
        default => 'primary',
    };
    ?>
<?php if (isset($component)) {
        $__componentOriginal15a5e11357468b3880ae1300c3be6c4f = $component;
    } ?>
<?php if (isset($attributes)) {
        $__attributesOriginal15a5e11357468b3880ae1300c3be6c4f = $attributes;
    } ?>
<?php $component = AnonymousComponent::resolve(['view' => $__env->getContainer()->make(Factory::class)->make('mail::button'), 'data' => ['url' => $actionUrl, 'color' => $color]] + (isset($attributes) && $attributes instanceof ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('mail::button'); ?>
<?php if ($component->shouldRender()) { ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof ComponentAttributeBag) { ?>
<?php $attributes = $attributes->except(AnonymousComponent::ignoredParameterNames()); ?>
<?php } ?>
<?php $component->withAttributes(['url' => BladeCompiler::sanitizeComponentAttribute($actionUrl), 'color' => BladeCompiler::sanitizeComponentAttribute($color)]); ?>
<?php SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo new EncodedHtmlString($actionText); ?>

 <?php echo $__env->renderComponent(); ?>
<?php } ?>
<?php if (isset($__attributesOriginal15a5e11357468b3880ae1300c3be6c4f)) { ?>
<?php $attributes = $__attributesOriginal15a5e11357468b3880ae1300c3be6c4f; ?>
<?php unset($__attributesOriginal15a5e11357468b3880ae1300c3be6c4f); ?>
<?php } ?>
<?php if (isset($__componentOriginal15a5e11357468b3880ae1300c3be6c4f)) { ?>
<?php $component = $__componentOriginal15a5e11357468b3880ae1300c3be6c4f; ?>
<?php unset($__componentOriginal15a5e11357468b3880ae1300c3be6c4f); ?>
<?php } ?>
<?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>


<?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__currentLoopData = $outroLines;
    $__env->addLoop($__currentLoopData);
    foreach ($__currentLoopData as $line) {
        $__env->incrementLoopIndices();
        $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
<?php echo new EncodedHtmlString($line); ?>


<?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } $__env->popLoop();
    $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>


<?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (! empty($salutation)) { ?>
<?php echo new EncodedHtmlString($salutation); ?>

<?php } else { ?>
<?php echo app('translator')->get('Regards,'); ?><br>
<?php echo new EncodedHtmlString(config('app.name')); ?>

<?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>


<?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (isset($actionText)) { ?>
 <?php $__env->slot('subcopy', null, []); ?> 
<?php echo app('translator')->get(
    "If you're having trouble clicking the \":actionText\" button, copy and paste the URL below\n".
    'into your web browser:',
    [
        'actionText' => $actionText,
    ]
); ?> <span class="break-all">[<?php echo new EncodedHtmlString($displayableActionUrl); ?>](<?php echo new EncodedHtmlString($actionUrl); ?>)</span>
 <?php $__env->endSlot(); ?>
<?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
 <?php echo $__env->renderComponent(); ?>
<?php } ?>
<?php if (isset($__attributesOriginalaa758e6a82983efcbf593f765e026bd9)) { ?>
<?php $attributes = $__attributesOriginalaa758e6a82983efcbf593f765e026bd9; ?>
<?php unset($__attributesOriginalaa758e6a82983efcbf593f765e026bd9); ?>
<?php } ?>
<?php if (isset($__componentOriginalaa758e6a82983efcbf593f765e026bd9)) { ?>
<?php $component = $__componentOriginalaa758e6a82983efcbf593f765e026bd9; ?>
<?php unset($__componentOriginalaa758e6a82983efcbf593f765e026bd9); ?>
<?php } ?>
<?php /**PATH /Users/developerts/Herd/system/vendor/laravel/framework/src/Illuminate/Notifications/resources/views/email.blade.php ENDPATH**/ ?>