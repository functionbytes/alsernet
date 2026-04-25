<!DOCTYPE html>
<html lang="<?php
use Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys;
use Livewire\Mechanisms\ExtendBlade\ExtendBlade;
use Modules\Ecommerce\Models\ProductCategory;
use Modules\Ecommerce\Services\CartService;

echo e(str_replace('_', '-', app()->getLocale())); ?>">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">

    <?php echo BaseHelper::googleFonts('https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap'); ?>


    <style>
        :root {
            --font-text: <?php echo e(theme_option('font_text', 'Poppins')); ?>, sans-serif;
            --color-brand: <?php echo e(theme_option('color_brand', '#5897fb')); ?>;
            --primary-color: <?php echo e(theme_option('color_brand', '#5897fb')); ?>;
            --color-brand-2: <?php echo e(theme_option('color_brand_2', '#3256e0')); ?>;
            --color-primary: <?php echo e(theme_option('color_primary', '#3f81eb')); ?>;
            --color-secondary: <?php echo e(theme_option('color_secondary', '#41506b')); ?>;
            --color-warning: <?php echo e(theme_option('color_warning', '#ffb300')); ?>;
            --color-danger: <?php echo e(theme_option('color_danger', '#ff3551')); ?>;
            --color-success: <?php echo e(theme_option('color_success', '#3ed092')); ?>;
            --color-info: <?php echo e(theme_option('color_info', '#18a1b7')); ?>;
            --color-text: <?php echo e(theme_option('color_text', '#4f5d77')); ?>;
            --color-heading: <?php echo e(theme_option('color_heading', '#222222')); ?>;
            --color-grey-1: <?php echo e(theme_option('color_grey_1', '#111111')); ?>;
            --color-grey-2: <?php echo e(theme_option('color_grey_2', '#242424')); ?>;
            --color-grey-4: <?php echo e(theme_option('color_grey_4', '#90908e')); ?>;
            --color-grey-9: <?php echo e(theme_option('color_grey_9', '#f8f9fa')); ?>;
            --color-muted: <?php echo e(theme_option('color_muted', '#8e8e90')); ?>;
            --color-body: <?php echo e(theme_option('color_body', '#4f5d77')); ?>;
        }
    </style>

    <?php echo Theme::header(); ?>


    <?php
        $cartCount = app(CartService::class)->count();
$headerStyle = theme_option('header_style') ?: '';
$page = Theme::get('page');
if ($page && method_exists($page, 'getMetaData')) {
    $headerStyle = $page->getMetaData('header_style', true) ?: $headerStyle;
}
$headerStyle = ($headerStyle && in_array($headerStyle, array_keys(get_layout_header_styles()))) ? $headerStyle : '';
?>
</head>
<body <?php echo Theme::bodyAttributes(); ?> class="<?php if (BaseHelper::isRtlEnabled()) { ?> rtl <?php } ?> header_full_true wowy-template css_scrollbar template-index wrapper_full_width header_full_true header_sticky_true des_header_3 top_bar_true">
<?php echo apply_filters(THEME_FRONT_BODY, null); ?>

<div id="alert-container"></div>

<?php echo Theme::partial('preloader'); ?>


<header class="header-area header-height-2 <?php echo e($headerStyle); ?>">
    <div class="header-top header-top-ptb-1 d-none d-lg-block">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-xl-2 col-lg-2">
                    <div class="header-info">
                        <ul>
                            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($hotline = theme_option('hotline')) { ?>
                                <li><i class="fa fa-phone-alt mr-5"></i><a href="tel:<?php echo e($hotline); ?>"><?php echo e($hotline); ?></a></li>
                            <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                        </ul>
                    </div>
                </div>

                <div class="col-xl-8 col-lg-8 text-center">
                    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (theme_option('header_messages') && $headerMessages = json_decode(theme_option('header_messages'), true)) { ?>
                        <div id="news-flash" class="d-inline-block">
                            <ul>
                                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__currentLoopData = $headerMessages;
                        $__env->addLoop($__currentLoopData);
                        foreach ($__currentLoopData as $headerMessage) {
                            $__env->incrementLoopIndices();
                            $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
                                    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (count($headerMessage) == 4) { ?>
                                        <li>
                                            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($headerMessage[0]['value']) { ?>
                                                <?php echo BaseHelper::renderIcon($headerMessage[0]['value'], null, ['class' => 'd-inline-block mr-5']); ?>

                                            <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                                            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($headerMessage[1]['value']) { ?>
                                                <span class="d-inline-block"><?php echo clean($headerMessage[1]['value']); ?></span>
                                            <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                                            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($headerMessage[2]['value'] && $headerMessage[3]['value']) { ?>
                                                &nbsp;<a class="active d-inline-block" href="<?php echo e(url($headerMessage[2]['value'])); ?>"><?php echo clean($headerMessage[3]['value']); ?></a>
                                            <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                                        </li>
                                    <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } $__env->popLoop();
                        $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>
                            </ul>
                        </div>
                    <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                </div>

                <div class="col-xl-2 col-lg-2">
                    <div class="header-info header-info-right">
                        <ul>
                            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (auth()->guard('ecommerce')->guest()) { ?>
                                <li><a href="<?php echo e(route('ecommerce.login')); ?>"><?php echo e(__('Iniciar sesion / Registrarse')); ?></a></li>
                            <?php } else { ?>
                                <li><a href="#"><?php echo e(auth('ecommerce')->user()->name); ?></a></li>
                            <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="header-middle header-middle-ptb-1 d-none d-lg-block">
        <div class="container">
            <div class="header-wrap header-space-between">
                <div class="logo logo-width-1">
                    <a href="<?php echo e(url('/')); ?>">
                        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (theme_option('logo')) { ?>
                            <img src="<?php echo e(RvMedia::getImageUrl(theme_option('logo'))); ?>" alt="<?php echo e(theme_option('site_title')); ?>">
                        <?php } else { ?>
                            <span style="font-size:1.6rem;font-weight:700;color:var(--color-heading);font-family:var(--font-text);"><?php echo e(config('app.name')); ?></span>
                        <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                    </a>
                </div>

                <div class="search-style-2">
                    <form action="<?php echo e(route('shop.index')); ?>" method="get">
                        <input type="text" name="q" class="input-search-product" placeholder="<?php echo e(__('Buscar productos...')); ?>" value="<?php echo e(request('q')); ?>" autocomplete="off">
                        <button type="submit" title="search"><i class="fa fa-search"></i></button>
                    </form>
                </div>

                <div class="header-action-right">
                    <div class="header-action-2">
                        <div class="header-action-icon-2">
                            <a href="<?php echo e(route('ecommerce.wishlist.index')); ?>" class="wishlist-count">
                                <img class="svgInject" alt="<?php echo e(__('Favoritos')); ?>" src="<?php echo e(theme_asset('images/icons/icon-heart.svg')); ?>">
                            </a>
                        </div>
                        <div class="header-action-icon-2">
                            <a class="mini-cart-icon" href="<?php echo e(route('cart.index')); ?>">
                                <img alt="<?php echo e(__('Carrito')); ?>" src="<?php echo e(theme_asset('images/icons/icon-cart.svg')); ?>">
                                <span class="pro-count blue"><?php echo e($cartCount); ?></span>
                            </a>
                            <div class="cart-dropdown-wrap cart-dropdown-hm2">
                                <?php echo Theme::partial('cart-panel'); ?>

                            </div>
                        </div>
                        <div class="header-action-icon-2">
                            <a href="<?php echo e(route('ecommerce.login')); ?>">
                                <img alt="<?php echo e(__('Usuario')); ?>" src="<?php echo e(theme_asset('images/icons/icon-user.svg')); ?>">
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="header-bottom gray-bg sticky-bar">
        <div class="container">
            <div class="header-wrap header-space-between position-relative main-nav">
                <div class="logo logo-width-1 d-block d-lg-none">
                    <a href="<?php echo e(url('/')); ?>">
                        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (theme_option('logo')) { ?>
                            <img src="<?php echo e(RvMedia::getImageUrl(theme_option('logo'))); ?>" alt="<?php echo e(theme_option('site_title')); ?>">
                        <?php } else { ?>
                            <span style="font-size:1.4rem;font-weight:700;color:var(--color-heading);font-family:var(--font-text);"><?php echo e(config('app.name')); ?></span>
                        <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                    </a>
                </div>

                <div class="main-categories-wrap d-none d-lg-block">
                    <a class="categories-button-active open" href="#">
                        <span class="fa fa-list"></span> <?php echo e(__('Categorias')); ?> <i class="down far fa-chevron-down"></i> <i class="up far fa-chevron-up"></i>
                    </a>
                    <div class="categories-dropdown-wrap categories-dropdown-active-large default-open open">
                        <ul>
                            <?php
                        $categories = ProductCategory::query()
                            ->where('parent_id', 0)
                            ->where('status', 'published')
                            ->orderBy('order')
                            ->with('children')
                            ->get();
?>
                            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__currentLoopData = $categories;
$__env->addLoop($__currentLoopData);
foreach ($__currentLoopData as $category) {
    $__env->incrementLoopIndices();
    $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
                                <li>
                                    <a href="<?php echo e(route('shop.category', $category->slug)); ?>">
                                        <?php echo e($category->name); ?>

                                        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($category->children->count()) { ?>
                                            <span class="menu-expand"><i class="down far fa-chevron-down"></i></span>
                                        <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                                    </a>
                                    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($category->children->count()) { ?>
                                        <ul class="dropdown" style="display:none">
                                            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__currentLoopData = $category->children;
                                        $__env->addLoop($__currentLoopData);
                                        foreach ($__currentLoopData as $child) {
                                            $__env->incrementLoopIndices();
                                            $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
                                                <li><a href="<?php echo e(route('shop.category', $child->slug)); ?>"><?php echo e($child->name); ?></a></li>
                                            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } $__env->popLoop();
                                        $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>
                                        </ul>
                                    <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                                </li>
                            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } $__env->popLoop();
$loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>
                        </ul>
                    </div>
                </div>

                <div class="main-menu main-menu-padding-1 main-menu-lh-2 d-none d-lg-block main-menu-light hover-boder">
                    <nav>
                        <?php echo Menu::renderMenuLocation('main-menu', ['view' => 'main-menu']); ?>

                    </nav>
                </div>

                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($hotline = theme_option('hotline')) { ?>
                    <div class="hotline d-none d-lg-block">
                        <p><i class="fa fa-phone-alt"></i><span><?php echo e(__('Hotline')); ?></span> <a href="tel:<?php echo e($hotline); ?>" class="text"><?php echo e($hotline); ?></a></p>
                    </div>
                <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>

                <div class="header-action-right d-block d-lg-none">
                    <div class="header-action-2">
                        <div class="header-action-icon-2">
                            <a href="<?php echo e(route('ecommerce.wishlist.index')); ?>">
                                <img alt="wowy" src="<?php echo e(theme_asset('images/icons/icon-heart.svg')); ?>">
                            </a>
                        </div>
                        <div class="header-action-icon-2">
                            <a class="mini-cart-icon" href="<?php echo e(route('cart.index')); ?>">
                                <img alt="cart" src="<?php echo e(theme_asset('images/icons/icon-cart.svg')); ?>">
                                <span class="pro-count white"><?php echo e($cartCount); ?></span>
                            </a>
                        </div>
                        <div class="header-action-icon-2">
                            <a href="<?php echo e(route('ecommerce.login')); ?>">
                                <img alt="wowy" src="<?php echo e(theme_asset('images/icons/icon-user.svg')); ?>">
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
<?php /**PATH /Users/developerts/Herd/system/platform/themes/wowy/partials/header.blade.php ENDPATH**/ ?>