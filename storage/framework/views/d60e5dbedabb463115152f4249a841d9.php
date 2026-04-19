<?php

use Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys;
use Livewire\Mechanisms\ExtendBlade\ExtendBlade;

    $categories = config('Cookie.general.cookie_categories', []);
$saveText = cookie_option('save_btn_text', 'Guardar preferencias');
$acceptText = cookie_option('accept_btn_text', 'Aceptar todo');
$btnColor = cookie_option('btn_color', '#90bb13');
?>

<div class="modal fade" id="cookie-preferences-modal" tabindex="-1"
     aria-labelledby="cookiePreferencesTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold" id="cookiePreferencesTitle">
                    <i class="fas fa-shield me-2 text-muted"></i>Configuración de cookies
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <div class="modal-body p-4">
                <p class="text-muted mb-4">
                    Puedes elegir qué tipos de cookies deseas permitir. Las cookies necesarias siempre están activas ya que son imprescindibles para el correcto funcionamiento del sitio.
                </p>

                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__currentLoopData = $categories;
$__env->addLoop($__currentLoopData);
foreach ($__currentLoopData as $key => $category) {
    $__env->incrementLoopIndices();
    $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
                    <div class="d-flex align-items-start justify-content-between py-3 <?php echo e(! $loop->last ? 'border-bottom' : ''); ?>">
                        <div class="me-3">
                            <div class="fw-semibold mb-1"><?php echo e($category['name'] ?? $key); ?></div>
                            <p class="text-muted mb-0"><?php echo e($category['description'] ?? ''); ?></p>
                        </div>
                        <div class="form-check form-switch flex-shrink-0 ms-3 mt-1">
                            <input class="form-check-input cookie-category-toggle"
                                   type="checkbox"
                                   id="category-<?php echo e($key); ?>"
                                   data-category="<?php echo e($key); ?>"
                                   <?php if (! empty($category['required'])) { ?> checked disabled <?php } else { ?> checked <?php } ?>
                                   style="cursor: <?php echo e(! empty($category['required']) ? 'not-allowed' : 'pointer'); ?>;">
                            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (! empty($category['required'])) { ?>
                                <span class="badge bg-secondary ms-1 small">Requerida</span>
                            <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                        </div>
                    </div>
                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } $__env->popLoop();
$loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>
            </div>

            <div class="modal-footer border-top d-flex justify-content-between">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    Cancelar
                </button>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary js-cookie-accept-modal">
                        <?php echo e($acceptText); ?>

                    </button>
                    <button type="button" class="btn btn-sm js-cookie-save-preferences"
                            style="background-color:<?php echo e($btnColor); ?>;border-color:<?php echo e($btnColor); ?>;color:#fff;">
                        <i class="fas fa-save me-1"></i><?php echo e($saveText); ?>

                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
<?php /**PATH /Users/developerts/Herd/system/modules/Cookie/resources/views/components/preferences-modal.blade.php ENDPATH**/ ?>