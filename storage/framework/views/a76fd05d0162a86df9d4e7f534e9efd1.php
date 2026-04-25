<?php

use Livewire\Mechanisms\ExtendBlade\ExtendBlade;
use Modules\Page\Models\Page;

    try {
    $errorPageId = setting('error-page-403');
    $errorPage = $errorPageId
        ? Page::find($errorPageId)
        : Page::findByPageType('error_403');
} catch (Throwable) {
    $errorPage = null;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($errorPage?->title ?? '403 - Acceso denegado'); ?></title>
    <link rel="stylesheet" href="<?php echo e(asset('modules/Theme/theme/css/style.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(asset('modules/Theme/theme/libs/fontawesome/fontawesome.css')); ?>">
    <style>
        body { background: #f5f6f8; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; font-family: 'Nunito Sans', sans-serif; }
        .error-card { background: #fff; border-radius: 12px; padding: 3rem 2.5rem; text-align: center; max-width: 480px; width: 90%; box-shadow: 0 4px 24px rgba(0,0,0,.08); }
        .error-code { font-size: 5rem; font-weight: 800; color: #FEC90F; line-height: 1; }
        .error-icon { font-size: 4rem; color: #FEC90F; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <div class="error-card">
        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($errorPage?->content) { ?>
            <?php echo $errorPage->content; ?>

        <?php } else { ?>
            <div class="error-icon"><i class="fas fa-shield-alt"></i></div>
            <div class="error-code">403</div>
            <h4 class="mt-3 mb-2 fw-bold">Acceso denegado</h4>
            <p class="text-muted mb-4">No tienes permisos para acceder a esta sección.</p>
            <a href="<?php echo e(url('/')); ?>" class="btn btn-primary me-2">
                <i class="fas fa-home me-1"></i> Inicio
            </a>
            <button onclick="history.back()" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Volver
            </button>
        <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
    </div>
</body>
</html>
<?php /**PATH /Users/developerts/Herd/system/resources/views/errors/403.blade.php ENDPATH**/ ?>