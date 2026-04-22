<?php

use Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys;
use Livewire\Mechanisms\ExtendBlade\ExtendBlade;
use Modules\Cookie\Models\CookieInventory;

$categories = config('Cookie.general.cookie_categories', []);
$saveText = __('cookie::messages.modal.save');
$acceptText = __('cookie::messages.modal.accept_all');
$btnColor = '#b10100';
$inventory = CookieInventory::query()
    ->active()
    ->ordered()
    ->get()
    ->groupBy('category');

$categoryIcons = [
    'essential' => 'fa-shield-halved',
    'analytics' => 'fa-chart-line',
    'marketing' => 'fa-bullhorn',
    'functional' => 'fa-sliders',
    'performance' => 'fa-gauge-high',
];
?>

<div class="modal fade cookiex-modal" id="cookie-preferences-modal" tabindex="-1"
     aria-labelledby="cookiePreferencesTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg" style="--cookiex-brand: <?php echo e($btnColor); ?>;">
        <div class="modal-content cookiex-content">

            <div class="cookiex-header">
                <div class="cookiex-header-body">
                    <h5 class="cookiex-title" id="cookiePreferencesTitle">
                        <?php echo e(__('cookie::messages.modal.title')); ?>

                    </h5>
                    <p class="cookiex-subtitle"><?php echo e(__('cookie::messages.modal.description')); ?></p>
                </div>
                <button type="button" class="cookiex-close" data-bs-dismiss="modal" aria-label="<?php echo e(__('cookie::messages.modal.cancel')); ?>">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="cookiex-body">
                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__currentLoopData = $categories;
$__env->addLoop($__currentLoopData);
foreach ($__currentLoopData as $key => $category) {
    $__env->incrementLoopIndices();
    $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
                    <?php
            $required = ! empty($category['required']);
    $icon = $categoryIcons[$key] ?? 'fa-circle-info';
    $hasList = $inventory->has($key) && $inventory[$key]->count() > 0;
    ?>

                    <div class="cookiex-cat <?php echo e($required ? 'cookiex-cat--required' : ''); ?>">
                        <div class="cookiex-cat-main">
                            <div class="cookiex-cat-ico">
                                <i class="fa-solid <?php echo e($icon); ?>"></i>
                            </div>
                            <div class="cookiex-cat-text">
                                <div class="cookiex-cat-head">
                                    <h6 class="cookiex-cat-name"><?php echo e(__($category['name'] ?? $key)); ?></h6>
                                    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($required) { ?>
                                        <span class="cookiex-chip cookiex-chip--required">
                                            <i class="fa-solid fa-lock"></i><?php echo e(__('cookie::messages.modal.required')); ?>

                                        </span>
                                    <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                                </div>
                                <p class="cookiex-cat-desc"><?php echo e(__($category['description'] ?? '')); ?></p>

                                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($hasList) { ?>
                                    <a class="cookiex-cat-toggle-list" data-bs-toggle="collapse"
                                       href="#cookies-<?php echo e($key); ?>" role="button" aria-expanded="false">
                                        <i class="fa-solid fa-chevron-down cookiex-cat-caret"></i>
                                        <span>Ver cookies utilizadas</span>
                                        <span class="cookiex-count"><?php echo e($inventory[$key]->count()); ?></span>
                                    </a>
                                <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                            </div>
                            <label class="cookiex-switch <?php echo e($required ? 'cookiex-switch--locked' : ''); ?>" for="category-<?php echo e($key); ?>">
                                <input class="cookiex-switch-input cookie-category-toggle <?php echo e($required ? 'cookie-toggle--required' : 'cookie-toggle--optional'); ?>"
                                       type="checkbox"
                                       id="category-<?php echo e($key); ?>"
                                       data-category="<?php echo e($key); ?>"
                                       <?php if (true) {
                                           echo 'checked';
                                       } ?> <?php if ($required) {
                                           echo 'disabled';
                                       } ?>>
                                <span class="cookiex-switch-slider"></span>
                            </label>
                        </div>

                        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($hasList) { ?>
                            <div class="collapse cookiex-cat-list" id="cookies-<?php echo e($key); ?>">
                                <div class="cookiex-cat-list-inner">
                                    <div class="cookiex-table-wrap">
                                        <table class="cookiex-table">
                                            <thead>
                                                <tr>
                                                    <th>Cookie</th>
                                                    <th>Proveedor</th>
                                                    <th>Duración</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__currentLoopData = $inventory[$key];
                            $__env->addLoop($__currentLoopData);
                            foreach ($__currentLoopData as $cookie) {
                                $__env->incrementLoopIndices();
                                $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
                                                    <tr>
                                                        <td class="cookiex-table-name"><?php echo e($cookie->name); ?></td>
                                                        <td><?php echo e($cookie->provider); ?></td>
                                                        <td><?php echo e($cookie->duration); ?></td>
                                                    </tr>
                                                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } $__env->popLoop();
                            $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                    </div>
                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } $__env->popLoop();
$loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>
            </div>

            <div class="cookiex-footer">
                <button type="button" class="cookiex-btn cookiex-btn--primary js-cookie-accept-modal">
                    <?php echo e($acceptText); ?>

                </button>
                <button type="button" class="cookiex-btn cookiex-btn--outline js-cookie-save-preferences">
                    <?php echo e($saveText); ?>

                </button>
                <button type="button" class="cookiex-btn cookiex-btn--ghost" data-bs-dismiss="modal">
                    <?php echo e(__('cookie::messages.modal.cancel')); ?>

                </button>
            </div>
        </div>
    </div>
</div>

<?php /**PATH /Users/developerts/Herd/system/modules/Cookie/resources/views/components/preferences-modal.blade.php ENDPATH**/ ?>