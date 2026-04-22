<!DOCTYPE html>
<html <?php
use Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys;
use Livewire\Mechanisms\ExtendBlade\ExtendBlade;

echo Theme::htmlAttributes(); ?>>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <link rel="icon" type="image/png" sizes="16x16" href="<?php echo e(asset('favicon-16x16.png')); ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo e(asset('favicon-32x32.png')); ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo e(asset('apple-touch-icon.png')); ?>">
    <link rel="icon" type="image/png" sizes="192x192" href="<?php echo e(asset('android-chrome-192x192.png')); ?>">
    <link rel="icon" type="image/png" sizes="512x512" href="<?php echo e(asset('android-chrome-512x512.png')); ?>">
    <link rel="manifest" href="<?php echo e(asset('manifest.json')); ?>">
    <meta name="theme-color" content="#b10100">
    <meta name="msapplication-TileColor" content="#b10100">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Caixilharia Blanco">
    <meta name="format-detection" content="telephone=yes">

    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">

    <?php if (! empty(trim($__env->yieldContent('seo_head')))) { ?>
        <?php echo $__env->yieldContent('seo_head'); ?>
    <?php } else { ?>
        <title><?php echo $__env->yieldContent('title', theme_option('site_title', config('app.name'))); ?></title>
        <?php if (! empty(trim($__env->yieldContent('description')))) { ?>
            <meta name="description" content="<?php echo $__env->yieldContent('description'); ?>">
        <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
        <?php if (! empty(trim($__env->yieldContent('keywords')))) { ?>
            <meta name="keywords" content="<?php echo $__env->yieldContent('keywords'); ?>">
        <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
    <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>

    <?php echo $__env->yieldPushContent('canonical'); ?>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <?php echo BaseHelper::googleFonts('https://fonts.googleapis.com/css2?family='.urlencode(theme_option('font_text', 'Poppins')).':ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap'); ?>


    <?php echo Theme::asset()->container('default')->styles(); ?>


    <?php echo $__env->yieldPushContent('head'); ?>
    <?php echo Theme::place('head'); ?>

    <?php echo app('seo')->renderSchema(); ?>
    <?php echo $__env->yieldPushContent('schema_org'); ?>

    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Organization",
        "name": "<?php echo e(theme_option('site_title') ?: config('app.name')); ?>",
        "url": "<?php echo e(url('/')); ?>",
        "logo": "<?php echo e(theme_option('logo') ?: asset('images/logo.png')); ?>"
        <?php if (theme_option('phone')) { ?>,"telephone": "<?php echo e(theme_option('phone')); ?>"<?php } ?>
        <?php if (theme_option('email')) { ?>,"email": "<?php echo e(theme_option('email')); ?>"<?php } ?>
        <?php if (theme_option('address')) { ?>,"address": {"@type": "PostalAddress", "streetAddress": "<?php echo e(theme_option('address')); ?>"}<?php } ?>
    }
    </script>

    <?php
        $headerStyle = theme_option('header_style') ?: '';
$page = Theme::get('page');
?>

    <?php echo view('Seo::partials.web-vitals-beacon')->render(); ?>
</head>
<body <?php echo Theme::bodyAttributes(); ?> class="<?php if (BaseHelper::isRtlEnabled()) { ?> rtl <?php } ?>  <?php if (Theme::get('bodyClass')) { ?> <?php echo e(Theme::get('bodyClass')); ?> <?php } ?>">
<?php echo apply_filters(THEME_FRONT_BODY, null); ?>

<div id="alert-container"></div>

<?php echo Theme::partial('preloader'); ?>



<div class="topbar">
    <div class="container">
        <?php
        $activeLang = $currentPageLocale ?? app()->getLocale();
$hasLangLinks = collect($pageLangLinks ?? [])
    ->filter(fn ($info, $lang) => $lang !== $activeLang && ! empty($info['url']) && $info['published'])
    ->isNotEmpty();
?>

        
        <div class="d-flex flex-column flex-lg-row align-items-center justify-content-lg-between gap-1 gap-lg-3">

            
            <div class="topbar-contact-info">
                <ul class="justify-content-center justify-content-lg-start">
                    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (theme_option('address')) { ?>
                        <li>
                            <a href="#">
                                <img src="<?php echo e(asset('themes/caixilhariablanco/images/icon-location.svg')); ?>" alt="Dirección" loading="lazy">
                                <?php echo e(theme_option('address')); ?>

                            </a>
                        </li>
                    <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (theme_option('email')) { ?>
                        <li>
                            <a href="mailto:<?php echo e(theme_option('email')); ?>">
                                <img src="<?php echo e(asset('themes/caixilhariablanco/images/icon-mail.svg')); ?>" alt="Correo electrónico" loading="lazy">
                                <?php echo e(theme_option('email')); ?>

                            </a>
                        </li>
                    <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                </ul>
            </div>

            
            <div class="d-flex align-items-center justify-content-center justify-content-lg-end gap-3">
                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (theme_option('phone') || theme_option('opening_hours')) { ?>
                    <div class="topbar-time">
                        <ul>
                            <li>
                                <a href="<?php echo e(theme_option('phone') ? 'tel:'.theme_option('phone') : '#'); ?>">
                                    <img src="<?php echo e(asset('themes/caixilhariablanco/images/icon-clock.svg')); ?>" alt="Horario de atención" loading="lazy">
                                    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (theme_option('opening_hours')) { ?>
                                        <?php echo e(theme_option('opening_hours')); ?>

                                    <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                                </a>
                            </li>
                        </ul>
                    </div>
                <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>

                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($hasLangLinks) { ?>
                    <ul class="navbar-nav language-switcher-nav">
                        <?php echo Theme::partial('language-switcher'); ?>

                    </ul>
                <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
            </div>

        </div>
    </div>
</div>


<header class="main-header">
    <div class="header-sticky">
        <nav class="navbar navbar-expand-lg">
            <div class="container">
                <!-- Logo Start -->
                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (theme_option('logo')) { ?>
                    <a class="navbar-brand" href="<?php echo e(BaseHelper::getHomepageUrl()); ?>"><img src="<?php echo e(RvMedia::getImageUrl(theme_option('logo'))); ?>" alt="<?php echo e(theme_option('site_title')); ?>"></a>
                <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                <!-- Logo End -->

                <!-- Main Menu Start -->
                <div class="collapse navbar-collapse main-menu">

                            <?php echo Menu::renderMenuLocation('main-menu', [
                        'view' => 'main-menu',
                    ]); ?>


                            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (($socialLinks = theme_option('social_links')) && $socialLinks = json_decode($socialLinks, true)) { ?>
                                <div class="header-social-icons">
                                    <ul>
                                    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__currentLoopData = $socialLinks;
                                $__env->addLoop($__currentLoopData);
                                foreach ($__currentLoopData as $socialLink) {
                                    $__env->incrementLoopIndices();
                                    $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
                                        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (! empty($socialLink['url']) && ! empty($socialLink['icon'])) { ?>
                                            <li><a href="<?php echo e($socialLink['url']); ?>"
                                                   title="<?php echo e($socialLink['name'] ?? ''); ?>"
                                                   target="_blank"
                                                   rel="noopener noreferrer">
                                                <?php echo BaseHelper::renderIcon($socialLink['icon']); ?>

                                            </a></li>
                                        <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                                    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } $__env->popLoop();
                                $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>
                                    </ul>
                                </div>
                            <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>



                </div>
                <!-- Main Menu End -->
                <div class="navbar-toggle"></div>
            </div>
        </nav>
        <div class="responsive-menu"></div>
    </div>
</header>
<!-- Header End -->

<?php /**PATH /Users/developerts/Herd/system/platform/themes/caixilhariablanco/partials/header.blade.php ENDPATH**/ ?>