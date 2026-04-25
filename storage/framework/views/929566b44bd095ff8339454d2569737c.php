<!DOCTYPE html>
<html lang="<?php
use Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys;
use Livewire\Mechanisms\ExtendBlade\ExtendBlade;

echo e(str_replace('_', '-', app()->getLocale())); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo e(__('Error de pago')); ?> - <?php echo e(config('app.name')); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #fbfbfb; min-height: 100vh; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
        .error-container { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .error-card { background: white; border-radius: 20px; box-shadow: 0 20px 60px rgba(0,0,0,0.1); overflow: hidden; max-width: 500px; width: 100%; text-align: center; }
        .error-header { background: #dc3545; color: white; padding: 2rem; }
        .error-icon { font-size: 4rem; margin-bottom: 1rem; }
        .error-body { padding: 2rem; }
        .error-details { background: #f8f9fa; border-radius: 10px; padding: 1.5rem; margin: 1.5rem 0; text-align: left; }
        .btn-primary { background: #90bb13; border-color: #90bb13; }
        .btn-primary:hover { background: #7aa30f; border-color: #7aa30f; }
        .btn-outline-primary { color: #90bb13; border-color: #90bb13; }
        .btn-outline-primary:hover { background: #90bb13; border-color: #90bb13; color: white; }
    </style>
</head>
<body>
<div class="error-container">
    <div class="error-card">
        <div class="error-header">
            <div class="error-icon"><i class="fas fa-exclamation-circle"></i></div>
            <h2 class="mb-0"><?php echo e(__('No se puede procesar el pago')); ?></h2>
        </div>
        <div class="error-body">
            <p class="text-muted"><?php echo e($message ?? __('El metodo de pago no esta disponible en este momento.')); ?></p>

            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (! empty($errors) && app()->environment('local')) { ?>
                <div class="error-details">
                    <strong><i class="fas fa-bug me-2"></i><?php echo e(__('Detalles tecnicos')); ?>:</strong>
                    <ul class="mb-0 mt-2">
                        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__currentLoopData = $errors;
                $__env->addLoop($__currentLoopData);
                foreach ($__currentLoopData as $error) {
                    $__env->incrementLoopIndices();
                    $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
                            <li><?php echo e($error); ?></li>
                        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } $__env->popLoop();
                $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>
                    </ul>
                </div>
            <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>

            <a href="<?php echo e(route('cart.index')); ?>" class="btn btn-primary">
                <i class="fas fa-shopping-cart me-2"></i><?php echo e(__('Volver al carrito')); ?>

            </a>
            <a href="<?php echo e(route('shop.index')); ?>" class="btn btn-outline-primary mt-2">
                <i class="fas fa-store me-2"></i><?php echo e(__('Seguir comprando')); ?>

            </a>
        </div>
    </div>
</div>

<script>
    let countdown = 30;
    const redirectTimer = setInterval(() => {
        countdown--;
        if (countdown <= 0) {
            window.location.href = '<?php echo e(route('cart.index')); ?>';
            clearInterval(redirectTimer);
        }
    }, 1000);

    document.addEventListener('click', () => clearInterval(redirectTimer));
    document.addEventListener('keydown', () => clearInterval(redirectTimer));
</script>
</body>
</html>
<?php /**PATH /Users/developerts/Herd/system/modules/EcommercePayment/resources/views/widget-error.blade.php ENDPATH**/ ?>