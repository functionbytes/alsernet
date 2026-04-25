<?php
use Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys;
use Livewire\Mechanisms\ExtendBlade\ExtendBlade;

$__env->startSection('title', 'Carrito'); ?>

<?php $__env->startSection('breadcrumb'); ?>
<div class="page-header breadcrumb-wrap">
    <div class="container">
        <div class="breadcrumb">
            <a href="<?php echo e(route('shop.index')); ?>" rel="nofollow"><i class="fa fa-home mr-5"></i><?php echo e(__('Inicio')); ?></a>
            <span></span> <?php echo e(__('Carrito')); ?>

        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<section class="mt-60 mb-60">
    <div class="container">
        <div class="row">
            <div class="col-12 section--shopping-cart">
                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($cartItems->count() > 0) { ?>
                    <div class="table-responsive">
                        <table class="table shopping-summery text-center clean table--cart">
                            <thead>
                                <tr class="main-heading">
                                    <th scope="col"><?php echo e(__('Imagen')); ?></th>
                                    <th scope="col"><?php echo e(__('Producto')); ?></th>
                                    <th scope="col"><?php echo e(__('Precio')); ?></th>
                                    <th scope="col"><?php echo e(__('Cantidad')); ?></th>
                                    <th scope="col"><?php echo e(__('Subtotal')); ?></th>
                                    <th scope="col"><?php echo e(__('Eliminar')); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__currentLoopData = $cartItems;
                    $__env->addLoop($__currentLoopData);
                    foreach ($__currentLoopData as $item) {
                        $__env->incrementLoopIndices();
                        $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
                                    <tr>
                                        <td class="image product-thumbnail">
                                            <a href="<?php echo e(route('shop.product', $item->product->slug)); ?>">
                                                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($item->product->featured_image) { ?>
                                                    <img src="<?php echo e($item->product->featured_image); ?>" alt="<?php echo e($item->product->name); ?>" width="80" />
                                                <?php } else { ?>
                                                    <img src="<?php echo e(asset('modules/ecommerce/images/404.png')); ?>" alt="<?php echo e($item->product->name); ?>" width="80" />
                                                <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                                            </a>
                                        </td>
                                        <td class="product-des product-name">
                                            <p class="product-name">
                                                <a href="<?php echo e(route('shop.product', $item->product->slug)); ?>"><?php echo e($item->product->name); ?></a>
                                            </p>
                                        </td>
                                        <td class="price" data-title="<?php echo e(__('Precio')); ?>">
                                            <span>$<?php echo e(number_format($item->product->final_price, 2)); ?></span>
                                            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($item->product->is_on_sale) { ?>
                                                <small><del>$<?php echo e(number_format($item->product->price, 2)); ?></del></small>
                                            <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                                        </td>
                                        <td class="text-center" data-title="<?php echo e(__('Cantidad')); ?>">
                                            <div class="detail-qty border radius m-auto">
                                                <form action="<?php echo e(route('cart.update', $item->id)); ?>" method="POST" class="d-flex align-items-center">
                                                    <?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?>
                                                    <a href="#" class="qty-down" onclick="event.preventDefault(); var input=this.nextElementSibling; if(input.value>1){input.value--; this.closest('form').submit();}"><i class="fa fa-caret-down"></i></a>
                                                    <input type="number" min="1" value="<?php echo e($item->qty); ?>" name="qty" class="qty-val qty-input" onchange="this.closest('form').submit();" />
                                                    <a href="#" class="qty-up" onclick="event.preventDefault(); var input=this.previousElementSibling; input.value++; this.closest('form').submit();"><i class="fa fa-caret-up"></i></a>
                                                </form>
                                            </div>
                                        </td>
                                        <td class="text-right" data-title="<?php echo e(__('Subtotal')); ?>">
                                            <span>$<?php echo e(number_format($item->product->final_price * $item->qty, 2)); ?></span>
                                        </td>
                                        <td class="action" data-title="<?php echo e(__('Eliminar')); ?>">
                                            <form action="<?php echo e(route('cart.remove', $item->id)); ?>" method="POST" class="d-inline">
                                                <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                                                <button type="submit" class="text-muted border-0 bg-transparent"><i class="fa fa-trash-alt"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } $__env->popLoop();
                    $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="divider center_icon mt-50 mb-50"><i class="fa fa-gem"></i></div>
                    <div class="row mb-50">
                        <div class="col-lg-6 col-md-12">
                            <div class="border p-md-4 p-30 border-radius-10 cart-totals">
                                <div class="heading_s1 mb-3">
                                    <h4><?php echo e(__('Acciones')); ?></h4>
                                </div>
                                <form action="<?php echo e(route('cart.clear')); ?>" method="POST">
                                    <?php echo csrf_field(); ?>
                                    <button type="submit" class="btn btn-outline-danger w-100"><i class="fa fa-trash mr-5"></i><?php echo e(__('Vaciar carrito')); ?></button>
                                </form>
                            </div>
                        </div>
                        <div class="col-lg-6 col-md-12">
                            <div class="border p-md-4 p-30 border-radius-10 cart-totals">
                                <div class="heading_s1 mb-3">
                                    <h4><?php echo e(__('Total del carrito')); ?></h4>
                                </div>
                                <div class="table-responsive">
                                    <table class="table">
                                        <tbody>
                                            <tr>
                                                <td class="cart_total_label"><?php echo e(__('Total')); ?></td>
                                                <td class="cart_total_amount">
                                                    <strong><span class="font-xl fw-900 text-brand">$<?php echo e(number_format($total, 2)); ?></span></strong>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <a href="<?php echo e(route('checkout.index')); ?>" class="btn w-100"><i class="fa fa-share-square mr-5"></i> <?php echo e(__('Proceder al checkout')); ?></a>
                                <div class="mt-10">
                                    <a class="btn btn-primary w-100" href="<?php echo e(route('shop.index')); ?>"><i class="fa fa-arrow-left me-2"></i><?php echo e(__('Seguir comprando')); ?></a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php } else { ?>
                    <div class="cart-container">
                        <div class="checkout-empty-container text-center py-5">
                            <i class="fa-solid fa-cart-xmark fa-4x text-muted mb-3"></i>
                            <h2><?php echo e(__('Tu carrito esta vacio!')); ?></h2>
                            <p><?php echo e(__('Agrega productos para comenzar tu compra.')); ?></p>
                            <a href="<?php echo e(route('shop.index')); ?>" class="btn btn-primary mt-3"><?php echo e(__('Seguir comprando')); ?></a>
                        </div>
                    </div>
                <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
            </div>
        </div>
    </div>
</section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('ecommerce::layouts.shop-wowy', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/developerts/Herd/system/modules/Ecommerce/resources/views/shop/cart.blade.php ENDPATH**/ ?>