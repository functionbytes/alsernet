@php
    $cartService = app(\Modules\Ecommerce\Services\CartService::class);
    $cartItems = $cartService->getCartItems();
@endphp

@if ($cartItems->count() > 0)
    <ul>
        @foreach($cartItems as $item)
            <li>
                <div class="shopping-cart-img">
                    <a href="{{ route('shop.product', $item->product->slug) }}">
                        @if($item->product->featured_image)
                            <img alt="{{ $item->product->name }}" src="{{ $item->product->featured_image }}">
                        @else
                            <img alt="{{ $item->product->name }}" src="{{ asset('modules/ecommerce/images/404.png') }}">
                        @endif
                    </a>
                </div>
                <div class="shopping-cart-title">
                    <h4><a href="{{ route('shop.product', $item->product->slug) }}">{{ $item->product->name }}</a></h4>
                    <h4><span>{{ $item->qty }} × </span>${{ number_format($item->product->final_price, 2) }}</h4>
                </div>
                <div class="shopping-cart-delete">
                    <form action="{{ route('cart.remove', $item->id) }}" method="POST">
                        @csrf @method('DELETE')
                        <button type="submit" class="border-0 bg-transparent"><i class="fa fa-times"></i></button>
                    </form>
                </div>
            </li>
        @endforeach
    </ul>
    <div class="shopping-cart-footer">
        <div class="shopping-cart-total">
            <h4>{{ __('Total') }} <span>${{ number_format($cartItems->sum(fn($i) => $i->product->final_price * $i->qty), 2) }}</span></h4>
        </div>
        <div class="shopping-cart-button">
            <a href="{{ route('cart.index') }}">{{ __('Ver carrito') }}</a>
            <a href="{{ route('checkout.index') }}">{{ __('Checkout') }}</a>
        </div>
    </div>
@else
    <div class="text-center py-3">
        <p class="text-muted">{{ __('Tu carrito esta vacio') }}</p>
        <a href="{{ route('shop.index') }}" class="btn btn-sm btn-primary">{{ __('Seguir comprando') }}</a>
    </div>
@endif
