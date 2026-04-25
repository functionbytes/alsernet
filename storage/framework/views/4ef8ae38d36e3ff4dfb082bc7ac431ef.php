<!DOCTYPE html>
<html lang="<?php
use Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys;
use Livewire\Mechanisms\ExtendBlade\ExtendBlade;
use Modules\Ecommerce\Models\ProductCategory;
use Modules\Ecommerce\Services\CartService;

echo e(str_replace('_', '-', app()->getLocale())); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">

    <title><?php echo $__env->yieldContent('title', 'Tienda'); ?> - <?php echo e(config('app.name', 'Alsernet')); ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="<?php echo e(asset('modules/ecommerce/plugins/bootstrap/css/bootstrap.min.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(asset('modules/ecommerce/plugins/fontawesome/css/all.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(asset('modules/ecommerce/css/style.css')); ?>">

    <style>
        :root {
            --font-text: 'Poppins', sans-serif;
            --color-brand: #5897fb;
            --primary-color: #5897fb;
            --color-brand-2: #3256e0;
            --color-primary: #3f81eb;
            --color-secondary: #41506b;
            --color-warning: #ffb300;
            --color-danger: #ff3551;
            --color-success: #3ed092;
            --color-info: #18a1b7;
            --color-text: #4f5d77;
            --color-heading: #222222;
            --color-grey-1: #111111;
            --color-grey-2: #242424;
            --color-grey-4: #90908e;
            --color-grey-9: #f8f9fa;
            --color-muted: #8e8e90;
            --color-body: #4f5d77;
        }
    </style>

    <?php echo $__env->yieldPushContent('styles'); ?>
</head>
<body class="wowy-template css_scrollbar template-index wrapper_full_width header_full_true header_sticky_true des_header_3 top_bar_true">

    <header class="header-area header-height-2">
        <div class="header-top header-top-ptb-1 d-none d-lg-block">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-xl-4 col-lg-4">
                        <div class="header-info">
                            <ul>
                                <li><i class="fa fa-phone-alt mr-5"></i><a href="tel:+1234567890">+1 234 567 890</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-xl-4 col-lg-4 text-center">
                        <div id="news-flash" class="d-inline-block">
                            <ul>
                                <li>Bienvenido a nuestra tienda en linea</li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-xl-4 col-lg-4">
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
                        <a href="<?php echo e(route('shop.index')); ?>">
                            <span style="font-size:1.6rem;font-weight:700;color:var(--color-heading);font-family:var(--font-text);">
                                <?php echo e(config('app.name', 'Tienda')); ?>

                            </span>
                        </a>
                    </div>
                    <div class="search-style-2">
                        <form action="<?php echo e(route('shop.index')); ?>" method="get">
                            <input type="text" name="q" class="input-search-product" placeholder="Buscar productos..." value="<?php echo e(request('q')); ?>" autocomplete="off">
                            <button type="submit" title="search"><i class="fa fa-search"></i></button>
                        </form>
                    </div>
                    <div class="header-action-right">
                        <div class="header-action-2">
                            <div class="header-action-icon-2">
                                <a href="<?php echo e(route('ecommerce.wishlist.index')); ?>" class="wishlist-count">
                                    <img class="svgInject" alt="<?php echo e(__('Favoritos')); ?>" src="<?php echo e(asset('modules/ecommerce/images/icons/icon-heart.svg')); ?>">
                                </a>
                            </div>
                            <div class="header-action-icon-2">
                                <a class="mini-cart-icon" href="<?php echo e(route('cart.index')); ?>">
                                    <img alt="<?php echo e(__('Carrito')); ?>" src="<?php echo e(asset('modules/ecommerce/images/icons/icon-cart.svg')); ?>">
                                    <span class="pro-count blue"><?php echo e(app(CartService::class)->count()); ?></span>
                                </a>
                            </div>
                            <div class="header-action-icon-2">
                                <a href="<?php echo e(route('ecommerce.login')); ?>">
                                    <img alt="<?php echo e(__('Usuario')); ?>" src="<?php echo e(asset('modules/ecommerce/images/icons/icon-user.svg')); ?>">
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
                        <a href="<?php echo e(route('shop.index')); ?>">
                            <span style="font-size:1.4rem;font-weight:700;color:var(--color-heading);font-family:var(--font-text);">
                                <?php echo e(config('app.name', 'Tienda')); ?>

                            </span>
                        </a>
                    </div>

                    <div class="main-categories-wrap d-none d-lg-block">
                        <a class="categories-button-active open" href="#">
                            <span class="fa fa-list"></span> <?php echo e(__('Categorias')); ?> <i class="down far fa-chevron-down"></i> <i class="up far fa-chevron-up"></i>
                        </a>
                        <div class="categories-dropdown-wrap categories-dropdown-active-large default-open open">
                            <ul>
                                <?php
                                    $headerCategories = ProductCategory::query()
                                        ->where('parent_id', 0)
                                        ->where('status', 'published')
                                        ->orderBy('order')
                                        ->with('children')
                                        ->get();
?>
                                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__currentLoopData = $headerCategories;
$__env->addLoop($__currentLoopData);
foreach ($__currentLoopData as $headerCategory) {
    $__env->incrementLoopIndices();
    $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
                                    <li>
                                        <a href="<?php echo e(route('shop.category', $headerCategory->slug)); ?>">
                                            <?php echo e($headerCategory->name); ?>

                                            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($headerCategory->children->count()) { ?>
                                                <span class="menu-expand"><i class="down far fa-chevron-down"></i></span>
                                            <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                                        </a>
                                        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($headerCategory->children->count()) { ?>
                                            <ul class="dropdown" style="display:none">
                                                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__currentLoopData = $headerCategory->children;
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
                            <ul class="navbar-nav flex-row">
                                <li class="nav-item"><a class="nav-link" href="<?php echo e(route('shop.index')); ?>"><?php echo e(__('Inicio')); ?></a></li>
                                <li class="nav-item"><a class="nav-link" href="<?php echo e(route('cart.index')); ?>"><?php echo e(__('Carrito')); ?></a></li>
                                <li class="nav-item"><a class="nav-link" href="<?php echo e(route('checkout.index')); ?>"><?php echo e(__('Checkout')); ?></a></li>
                            </ul>
                        </nav>
                    </div>

                    <div class="header-action-right d-block d-lg-none">
                        <div class="header-action-2">
                            <div class="header-action-icon-2">
                                <a href="<?php echo e(route('ecommerce.wishlist.index')); ?>">
                                    <img alt="wowy" src="<?php echo e(asset('modules/ecommerce/images/icons/icon-heart.svg')); ?>">
                                </a>
                            </div>
                            <div class="header-action-icon-2">
                                <a class="mini-cart-icon" href="<?php echo e(route('cart.index')); ?>">
                                    <img alt="cart" src="<?php echo e(asset('modules/ecommerce/images/icons/icon-cart.svg')); ?>">
                                    <span class="pro-count white"><?php echo e(app(CartService::class)->count()); ?></span>
                                </a>
                            </div>
                            <div class="header-action-icon-2">
                                <a href="<?php echo e(route('ecommerce.login')); ?>">
                                    <img alt="wowy" src="<?php echo e(asset('modules/ecommerce/images/icons/icon-user.svg')); ?>">
                                </a>
                            </div>
                            <div class="header-action-icon-2 d-block d-lg-none">
                                <div class="burger-icon burger-icon">
                                    <span class="burger-icon-top"></span>
                                    <span class="burger-icon-mid"></span>
                                    <span class="burger-icon-bottom"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="main" id="main-section">
        <?php echo $__env->yieldContent('breadcrumb'); ?>
        <?php echo $__env->yieldContent('content'); ?>
    </main>

    <footer class="main">
        <section class="section-padding-60">
            <div class="container">
                <div class="row">
                    <div class="col-lg-4 col-md-6 mb-4">
                        <h5 class="widget-title"><?php echo e(config('app.name', 'Tienda')); ?></h5>
                        <p class="font-sm text-muted">Tu tienda en linea de confianza.</p>
                    </div>
                    <div class="col-lg-2 col-md-6 mb-4">
                        <h5 class="widget-title">Enlaces</h5>
                        <ul class="footer-list">
                            <li><a href="<?php echo e(route('shop.index')); ?>">Inicio</a></li>
                            <li><a href="<?php echo e(route('cart.index')); ?>">Carrito</a></li>
                            <li><a href="<?php echo e(route('checkout.index')); ?>">Checkout</a></li>
                        </ul>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-4">
                        <h5 class="widget-title">Cuenta</h5>
                        <ul class="footer-list">
                            <li><a href="<?php echo e(route('ecommerce.login')); ?>">Iniciar sesion</a></li>
                            <li><a href="<?php echo e(route('ecommerce.register')); ?>">Registrarse</a></li>
                        </ul>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-4">
                        <h5 class="widget-title">Contacto</h5>
                        <ul class="contact-info">
                            <li><i class="fa fa-phone-alt mr-5"></i> +1 234 567 890</li>
                            <li><i class="fa fa-envelope mr-5"></i> info@example.com</li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>
        <div class="container pb-20">
            <div class="row">
                <div class="col-12 mb-20">
                    <div class="footer-bottom"></div>
                </div>
                <div class="col-lg-6">
                    <p class="float-md-left font-sm text-muted mb-0">&copy; <?php echo e(date('Y')); ?> <?php echo e(config('app.name', 'Alsernet')); ?>. Todos los derechos reservados.</p>
                </div>
                <div class="col-lg-6">
                    <p class="text-lg-end text-center font-sm text-muted mb-0"><?php echo e(__('Todos los derechos reservados.')); ?></p>
                </div>
            </div>
        </div>
    </footer>

    <div id="scrollUp"><i class="fal fa-long-arrow-up"></i></div>

    <script src="<?php echo e(asset('modules/ecommerce/js/vendor/jquery.min.js')); ?>"></script>
    <script src="<?php echo e(asset('modules/ecommerce/plugins/bootstrap/js/bootstrap.bundle.min.js')); ?>"></script>
    <script src="<?php echo e(asset('modules/ecommerce/js/main.js')); ?>"></script>

    <script>
        window.siteUrl = '<?php echo e(url('/')); ?>';
    </script>

    <?php echo $__env->yieldPushContent('scripts'); ?>
</body>
</html>
<?php /**PATH /Users/developerts/Herd/system/modules/Ecommerce/resources/views/layouts/shop-wowy.blade.php ENDPATH**/ ?>