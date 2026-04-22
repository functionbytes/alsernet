<div class="forms-wrapper forms-theme-<?php
use Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys;
use Livewire\Mechanisms\ExtendBlade\ExtendBlade;
use Modules\Captcha\Facades\Captcha;

echo e($theme); ?>" id="<?php echo e($formId); ?>-wrapper">
    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($showTitle) { ?>
    <h3 class="forms-title mb-3"><?php echo e($form->name); ?></h3>
    <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>

    
    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($isMultiStep) { ?>
    <div class="forms-progress mb-4" data-style="<?php echo e($form->progress_bar_style ?? 'bar'); ?>">
        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (($form->progress_bar_style ?? 'bar') === 'bar') { ?>
        <div class="progress" style="height:6px">
            <div class="progress-bar bg-success" style="width: <?php echo e((1 / $totalSteps) * 100); ?>%" id="<?php echo e($formId); ?>-progress-bar"></div>
        </div>
        <small class="text-muted mt-1 d-block">Paso <span id="<?php echo e($formId); ?>-current-step">1</span> de <?php echo e($totalSteps); ?></small>
        <?php } elseif (($form->progress_bar_style ?? 'bar') === 'dots') { ?>
        <div class="d-flex gap-2 align-items-center">
            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__currentLoopData = $steps;
            $__env->addLoop($__currentLoopData);
            foreach ($__currentLoopData as $s) {
                $__env->incrementLoopIndices();
                $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
            <div class="forms-step-dot <?php echo e($loop->first ? 'active' : ''); ?>" data-step="<?php echo e($s); ?>"></div>
            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } $__env->popLoop();
            $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>
        </div>
        <?php } elseif (($form->progress_bar_style ?? 'bar') === 'steps') { ?>
        <div class="d-flex gap-3 align-items-center flex-wrap">
            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__currentLoopData = $steps;
            $__env->addLoop($__currentLoopData);
            foreach ($__currentLoopData as $s) {
                $__env->incrementLoopIndices();
                $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
            <?php $stepNames = $form->steps_config[$s - 1]['name'] ?? "Paso {$s}"; ?>
            <div class="forms-step-label <?php echo e($loop->first ? 'active' : ''); ?>" data-step="<?php echo e($s); ?>">
                <span class="forms-step-number"><?php echo e($s); ?></span> <?php echo e($stepNames); ?>

            </div>
            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } $__env->popLoop();
            $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>
        </div>
        <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
    </div>
    <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>

    
    <div class="forms-success-message d-none" id="<?php echo e($formId); ?>-success">
        <div class="alert alert-success text-center py-4">
            <i class="fas fa-check-circle fa-3x mb-3 d-block text-success"></i>
            <p class="mb-0"><?php echo e($form->success_message ?? config('forms.default_success_message')); ?></p>
        </div>
    </div>

    
    <form id="<?php echo e($formId); ?>" class="forms-form" method="post" novalidate data-config="<?php echo e($formId); ?>">
        <?php echo csrf_field(); ?>
        
        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($form->honeypot_enabled) { ?>
        <div style="display:none !important" aria-hidden="true">
            <input type="text" name="_hp" value="" tabindex="-1" autocomplete="off">
        </div>
        <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
        
        <input type="hidden" name="_time_to_complete" id="<?php echo e($formId); ?>-time">
        <input type="hidden" name="_start_time" value="<?php echo e(time()); ?>">

        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__currentLoopData = $steps;
$__env->addLoop($__currentLoopData);
foreach ($__currentLoopData as $step) {
    $__env->incrementLoopIndices();
    $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
        <div class="forms-step <?php echo e($loop->first ? 'forms-step-active' : 'd-none'); ?>" data-step="<?php echo e($step); ?>">
            <?php
            $stepFields = $form->fields->where('step_number', $step)
                ->where('is_visible', true)
                ->sortBy('sort_order');
    ?>
            <div class="row g-3">
                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__currentLoopData = $stepFields;
    $__env->addLoop($__currentLoopData);
    foreach ($__currentLoopData as $field) {
        $__env->incrementLoopIndices();
        $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
                    <?php echo $__env->make('forms::public.partials.field', compact('field', 'formId', 'floatingLabel'), array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } $__env->popLoop();
    $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>
            </div>

            
            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($isMultiStep) { ?>
                <div class="forms-step-nav mt-4">
                    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($loop->first && ! $loop->last) { ?>
                        
                        <button type="button" class="btn btn-primary forms-next-btn w-100" data-step="<?php echo e($step); ?>">
                            Siguiente <i class="fas fa-arrow-right ms-1"></i>
                        </button>
                    <?php } elseif (! $loop->first && ! $loop->last) { ?>
                        
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-secondary forms-prev-btn w-50" data-step="<?php echo e($step); ?>">
                                <i class="fas fa-arrow-left me-1"></i> Anterior
                            </button>
                            <button type="button" class="btn btn-primary forms-next-btn w-50" data-step="<?php echo e($step); ?>">
                                Siguiente <i class="fas fa-arrow-right ms-1"></i>
                            </button>
                        </div>
                    <?php } else { ?>
                        
                        <div class="d-flex justify-content-between align-items-center gap-2">
                            <button type="button" class="btn btn-outline-secondary forms-prev-btn" data-step="<?php echo e($step); ?>">
                                <i class="fas fa-arrow-left me-1"></i> Anterior
                            </button>
                            <div>
                                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (! empty($captchaEnabled) && $captchaEnabled && class_exists('\Modules\Captcha\Facades\Captcha')) { ?>
                                <div class="mb-3 w-100">
                                    <?php echo Captcha::display(); ?>

                                </div>
                                <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                                <?php echo $__env->make('forms::public.partials.submit-button', compact('form', 'formId', 'buttonText', 'buttonColor'), array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                            </div>
                        </div>
                    <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                </div>
            <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
        </div>
        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } $__env->popLoop();
$loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>

        
        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (! $isMultiStep) { ?>
        <div class="mt-4 text-<?php echo e($form->button_position ?? 'start'); ?>">
            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (! empty($captchaEnabled) && $captchaEnabled && class_exists('\Modules\Captcha\Facades\Captcha')) { ?>
            <div class="mb-3">
                <?php echo Captcha::display(); ?>

            </div>
            <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
            <?php echo $__env->make('forms::public.partials.submit-button', compact('form', 'formId', 'buttonText', 'buttonColor'), array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        </div>
        <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
    </form>
</div>
<?php /**PATH /Users/developerts/Herd/system/modules/Forms/resources/views/public/partials/form-body.blade.php ENDPATH**/ ?>