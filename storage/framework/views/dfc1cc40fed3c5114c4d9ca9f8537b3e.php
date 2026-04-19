<?php
use Illuminate\View\AnonymousComponent;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\ComponentAttributeBag;
use Illuminate\View\Factory;
use Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys;
use Livewire\Mechanisms\ExtendBlade\ExtendBlade;

if (isset($component)) {
    $__componentOriginal3287929725b3f878740bf3f25881b9ff = $component;
} ?>
<?php if (isset($attributes)) {
    $__attributesOriginal3287929725b3f878740bf3f25881b9ff = $attributes;
} ?>
<?php $component = AnonymousComponent::resolve(['view' => $__env->getContainer()->make(Factory::class)->make('mail::layout'), 'data' => []] + (isset($attributes) && $attributes instanceof ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('mail::layout'); ?>
<?php if ($component->shouldRender()) { ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof ComponentAttributeBag) { ?>
<?php $attributes = $attributes->except(AnonymousComponent::ignoredParameterNames()); ?>
<?php } ?>
<?php $component->withAttributes([]); ?>
<?php SupportCompiledWireKeys::processComponentKey($component); ?>

    
     <?php $__env->slot('header', null, []); ?> 
        <?php if (isset($component)) {
            $__componentOriginal4b27c9cf0646a011e45f5c0081cff2ae = $component;
        } ?>
<?php if (isset($attributes)) {
            $__attributesOriginal4b27c9cf0646a011e45f5c0081cff2ae = $attributes;
        } ?>
<?php $component = AnonymousComponent::resolve(['view' => $__env->getContainer()->make(Factory::class)->make('mail::header'), 'data' => ['url' => config('app.url')]] + (isset($attributes) && $attributes instanceof ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('mail::header'); ?>
<?php if ($component->shouldRender()) { ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof ComponentAttributeBag) { ?>
<?php $attributes = $attributes->except(AnonymousComponent::ignoredParameterNames()); ?>
<?php } ?>
<?php $component->withAttributes(['url' => BladeCompiler::sanitizeComponentAttribute(config('app.url'))]); ?>
<?php SupportCompiledWireKeys::processComponentKey($component); ?>

            <?php echo e(config('app.name')); ?>

         <?php echo $__env->renderComponent(); ?>
<?php } ?>
<?php if (isset($__attributesOriginal4b27c9cf0646a011e45f5c0081cff2ae)) { ?>
<?php $attributes = $__attributesOriginal4b27c9cf0646a011e45f5c0081cff2ae; ?>
<?php unset($__attributesOriginal4b27c9cf0646a011e45f5c0081cff2ae); ?>
<?php } ?>
<?php if (isset($__componentOriginal4b27c9cf0646a011e45f5c0081cff2ae)) { ?>
<?php $component = $__componentOriginal4b27c9cf0646a011e45f5c0081cff2ae; ?>
<?php unset($__componentOriginal4b27c9cf0646a011e45f5c0081cff2ae); ?>
<?php } ?>
     <?php $__env->endSlot(); ?>

    
    <?php echo e($slot); ?>


    
    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (isset($subcopy)) { ?>
         <?php $__env->slot('subcopy', null, []); ?> 
            <?php if (isset($component)) {
                $__componentOriginala95a089fc4dac0df2b807f0c4d49e8b5 = $component;
            } ?>
<?php if (isset($attributes)) {
                $__attributesOriginala95a089fc4dac0df2b807f0c4d49e8b5 = $attributes;
            } ?>
<?php $component = AnonymousComponent::resolve(['view' => $__env->getContainer()->make(Factory::class)->make('mail::subcopy'), 'data' => []] + (isset($attributes) && $attributes instanceof ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('mail::subcopy'); ?>
<?php if ($component->shouldRender()) { ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof ComponentAttributeBag) { ?>
<?php $attributes = $attributes->except(AnonymousComponent::ignoredParameterNames()); ?>
<?php } ?>
<?php $component->withAttributes([]); ?>
<?php SupportCompiledWireKeys::processComponentKey($component); ?>

                <?php echo e($subcopy); ?>

             <?php echo $__env->renderComponent(); ?>
<?php } ?>
<?php if (isset($__attributesOriginala95a089fc4dac0df2b807f0c4d49e8b5)) { ?>
<?php $attributes = $__attributesOriginala95a089fc4dac0df2b807f0c4d49e8b5; ?>
<?php unset($__attributesOriginala95a089fc4dac0df2b807f0c4d49e8b5); ?>
<?php } ?>
<?php if (isset($__componentOriginala95a089fc4dac0df2b807f0c4d49e8b5)) { ?>
<?php $component = $__componentOriginala95a089fc4dac0df2b807f0c4d49e8b5; ?>
<?php unset($__componentOriginala95a089fc4dac0df2b807f0c4d49e8b5); ?>
<?php } ?>
         <?php $__env->endSlot(); ?>
    <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>

    
     <?php $__env->slot('footer', null, []); ?> 
        <?php if (isset($component)) {
            $__componentOriginalef4bd4280c5be2fca1cb0cfd4325d122 = $component;
        } ?>
<?php if (isset($attributes)) {
            $__attributesOriginalef4bd4280c5be2fca1cb0cfd4325d122 = $attributes;
        } ?>
<?php $component = AnonymousComponent::resolve(['view' => $__env->getContainer()->make(Factory::class)->make('mail::footer'), 'data' => []] + (isset($attributes) && $attributes instanceof ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('mail::footer'); ?>
<?php if ($component->shouldRender()) { ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof ComponentAttributeBag) { ?>
<?php $attributes = $attributes->except(AnonymousComponent::ignoredParameterNames()); ?>
<?php } ?>
<?php $component->withAttributes([]); ?>
<?php SupportCompiledWireKeys::processComponentKey($component); ?>

            © <?php echo e(date('Y')); ?> <?php echo e(config('app.name')); ?>. <?php echo app('translator')->get('All rights reserved.'); ?>
         <?php echo $__env->renderComponent(); ?>
<?php } ?>
<?php if (isset($__attributesOriginalef4bd4280c5be2fca1cb0cfd4325d122)) { ?>
<?php $attributes = $__attributesOriginalef4bd4280c5be2fca1cb0cfd4325d122; ?>
<?php unset($__attributesOriginalef4bd4280c5be2fca1cb0cfd4325d122); ?>
<?php } ?>
<?php if (isset($__componentOriginalef4bd4280c5be2fca1cb0cfd4325d122)) { ?>
<?php $component = $__componentOriginalef4bd4280c5be2fca1cb0cfd4325d122; ?>
<?php unset($__componentOriginalef4bd4280c5be2fca1cb0cfd4325d122); ?>
<?php } ?>
     <?php $__env->endSlot(); ?>
 <?php echo $__env->renderComponent(); ?>
<?php } ?>
<?php if (isset($__attributesOriginal3287929725b3f878740bf3f25881b9ff)) { ?>
<?php $attributes = $__attributesOriginal3287929725b3f878740bf3f25881b9ff; ?>
<?php unset($__attributesOriginal3287929725b3f878740bf3f25881b9ff); ?>
<?php } ?>
<?php if (isset($__componentOriginal3287929725b3f878740bf3f25881b9ff)) { ?>
<?php $component = $__componentOriginal3287929725b3f878740bf3f25881b9ff; ?>
<?php unset($__componentOriginal3287929725b3f878740bf3f25881b9ff); ?>
<?php } ?>
<?php /**PATH /Users/developerts/Herd/system/resources/views/vendor/mail/text/message.blade.php ENDPATH**/ ?>