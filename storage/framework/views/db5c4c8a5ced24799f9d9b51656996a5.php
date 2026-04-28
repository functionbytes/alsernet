<!DOCTYPE html>

<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow,noarchive,nosnippet">
    <meta name="googlebot" content="noindex,nofollow,noarchive,nosnippet">
    <title><?php echo $__env->yieldContent('title', config('app.name')); ?></title>
    <meta name="author" content="">
    <meta name="description" content="">
    <meta name="twitter:card" content="summary">
    <meta name="twitter:site" content="@publisher_handle">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">

    <link rel="stylesheet" href="<?php echo e(themeAsset('libs/fontawesome/fontawesome.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(themeAsset('css/bootstrap.min.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(themeAsset('css/auth.css')); ?>">

    <?php echo $__env->yieldPushContent('css'); ?>

</head>

<body>

    <div id="page" class="page font--jakarta">

        <?php echo $__env->yieldContent('content'); ?>

    </div>

    <script data-pagespeed-no-defer src="<?php echo e(themeAsset('libs/jquery/dist/jquery.min.js')); ?>"></script>
    <script data-pagespeed-no-defer src="<?php echo e(themeAsset('libs/select2/dist/js/select2.min.js')); ?>"></script>
    <script data-pagespeed-no-defer src="<?php echo e(themeAsset('libs/jquery-validation/dist/jquery.validate.min.js')); ?>"></script>

    <?php echo $__env->yieldPushContent('scripts'); ?>

</body>

</html>
<?php /**PATH /Users/developerts/Herd/system/modules/Theme/resources/views/layouts/auth.blade.php ENDPATH**/ ?>