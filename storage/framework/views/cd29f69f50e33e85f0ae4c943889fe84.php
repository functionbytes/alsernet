<?php

use Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys;
use Livewire\Mechanisms\ExtendBlade\ExtendBlade;
use Modules\Ecommerce\Services\CartService;

    $cartService = app(CartService::class);
$cartItems = $cartService->getCartItems();
?>

<?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($cartItems->count() > 0) { ?>
    <ul>
        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__currentLoopData = $cartItems;
    $__env->addLoop($__currentLoopData);
    foreach ($__currentLoopData as $item) {
        $__env->incrementLoopIndices();
        $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
            <li>
                <div class="shopping-cart-img">
                    <a href="<?php echo e(route('shop.product', $item->product->slug)); ?>">
                        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($item->product->featured_image) { ?>
                            <img alt="<?php echo e($item->product->name); ?>" src="<?php echo e($item->product->featured_image); ?>">
                        <?php } else { ?>
                            <img alt="<?php echo e($item->product->name); ?>" src="<?php echo e(asset('modules/ecommerce/images/404.png')); ?>">
                        <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                    </a>
                </div>
                <div class="shopping-cart-title">
                    <h4><a href="<?php echo e(route('shop.product', $item->product->slug)); ?>"><?php echo e($item->product->name); ?></a></h4>
                    <h4><span><?php echo e($item->qty); ?> × </span>$<?php echo e(number_format($item->product->final_price, 2)); ?></h4>
                </div>
                <div class="shopping-cart-delete">
                    <form action="<?php echo e(route('cart.remove', $item->id)); ?>" method="POST">
                        <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                        <button type="submit" class="border-0 bg-transparent"><i class="fa fa-times"></i></button>
                    </form>
                </div>
            </li>
        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } $__env->popLoop();
    $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>
    </ul>
    <div class="shopping-cart-footer">
        <div class="shopping-cart-total">
            <h4><?php echo e(__('Total')); ?> <span>$<?php echo e(number_format($cartItems->sum(fn ($i) => $i->product->final_price * $i->qty), 2)); ?></span></h4>
        </div>
        <div class="shopping-cart-button">
            <a href="<?php echo e(route('cart.index')); ?>"><?php echo e(__('Ver carrito')); ?></a>
            <a href="<?php echo e(route('checkout.index')); ?>"><?php echo e(__('Checkout')); ?></a>
        </div>
    </div>
<?php } else { ?>
    <div class="text-center py-3">
        <p class="text-muted"><?php echo e(__('Tu carrito esta vacio')); ?></p>
        <a href="<?php echo e(route('shop.index')); ?>" class="btn btn-sm btn-primary"><?php echo e(__('Seguir comprando')); ?></a>
    </div>
<?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
<?php /**PATH /Users/developerts/Herd/system/platform/themes/wowy/partials/cart-panel.blade.php ENDPATH**/ ?>