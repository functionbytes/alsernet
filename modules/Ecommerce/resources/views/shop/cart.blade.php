@extends('ecommerce::layouts.shop-wowy')

@section('title', 'Carrito')

@section('breadcrumb')
<div class="page-header breadcrumb-wrap">
    <div class="container">
        <div class="breadcrumb">
            <a href="{{ route('shop.index') }}" rel="nofollow"><i class="fa fa-home mr-5"></i>{{ __('Inicio') }}</a>
            <span></span> {{ __('Carrito') }}
        </div>
    </div>
</div>
@endsection

@section('content')
<section class="mt-60 mb-60">
    <div class="container">
        <div class="row">
            <div class="col-12 section--shopping-cart">
                @if($cartItems->count() > 0)
                    <div class="table-responsive">
                        <table class="table shopping-summery text-center clean table--cart">
                            <thead>
                                <tr class="main-heading">
                                    <th scope="col">{{ __('Imagen') }}</th>
                                    <th scope="col">{{ __('Producto') }}</th>
                                    <th scope="col">{{ __('Precio') }}</th>
                                    <th scope="col">{{ __('Cantidad') }}</th>
                                    <th scope="col">{{ __('Subtotal') }}</th>
                                    <th scope="col">{{ __('Eliminar') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($cartItems as $item)
                                    <tr>
                                        <td class="image product-thumbnail">
                                            <a href="{{ route('shop.product', $item->product->slug) }}">
                                                @if($item->product->featured_image)
                                                    <img src="{{ $item->product->featured_image }}" alt="{{ $item->product->name }}" width="80" />
                                                @else
                                                    <img src="{{ asset('modules/ecommerce/images/404.png') }}" alt="{{ $item->product->name }}" width="80" />
                                                @endif
                                            </a>
                                        </td>
                                        <td class="product-des product-name">
                                            <p class="product-name">
                                                <a href="{{ route('shop.product', $item->product->slug) }}">{{ $item->product->name }}</a>
                                            </p>
                                        </td>
                                        <td class="price" data-title="{{ __('Precio') }}">
                                            <span>${{ number_format($item->product->final_price, 2) }}</span>
                                            @if($item->product->is_on_sale)
                                                <small><del>${{ number_format($item->product->price, 2) }}</del></small>
                                            @endif
                                        </td>
                                        <td class="text-center" data-title="{{ __('Cantidad') }}">
                                            <div class="detail-qty border radius m-auto">
                                                <form action="{{ route('cart.update', $item->id) }}" method="POST" class="d-flex align-items-center">
                                                    @csrf @method('PUT')
                                                    <a href="#" class="qty-down" onclick="event.preventDefault(); var input=this.nextElementSibling; if(input.value>1){input.value--; this.closest('form').submit();}"><i class="fa fa-caret-down"></i></a>
                                                    <input type="number" min="1" value="{{ $item->qty }}" name="qty" class="qty-val qty-input" onchange="this.closest('form').submit();" />
                                                    <a href="#" class="qty-up" onclick="event.preventDefault(); var input=this.previousElementSibling; input.value++; this.closest('form').submit();"><i class="fa fa-caret-up"></i></a>
                                                </form>
                                            </div>
                                        </td>
                                        <td class="text-right" data-title="{{ __('Subtotal') }}">
                                            <span>${{ number_format($item->product->final_price * $item->qty, 2) }}</span>
                                        </td>
                                        <td class="action" data-title="{{ __('Eliminar') }}">
                                            <form action="{{ route('cart.remove', $item->id) }}" method="POST" class="d-inline">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="text-muted border-0 bg-transparent"><i class="fa fa-trash-alt"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="divider center_icon mt-50 mb-50"><i class="fa fa-gem"></i></div>
                    <div class="row mb-50">
                        <div class="col-lg-6 col-md-12">
                            <div class="border p-md-4 p-30 border-radius-10 cart-totals">
                                <div class="heading_s1 mb-3">
                                    <h4>{{ __('Acciones') }}</h4>
                                </div>
                                <form action="{{ route('cart.clear') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-danger w-100"><i class="fa fa-trash mr-5"></i>{{ __('Vaciar carrito') }}</button>
                                </form>
                            </div>
                        </div>
                        <div class="col-lg-6 col-md-12">
                            <div class="border p-md-4 p-30 border-radius-10 cart-totals">
                                <div class="heading_s1 mb-3">
                                    <h4>{{ __('Total del carrito') }}</h4>
                                </div>
                                <div class="table-responsive">
                                    <table class="table">
                                        <tbody>
                                            <tr>
                                                <td class="cart_total_label">{{ __('Total') }}</td>
                                                <td class="cart_total_amount">
                                                    <strong><span class="font-xl fw-900 text-brand">${{ number_format($total, 2) }}</span></strong>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <a href="{{ route('checkout.index') }}" class="btn w-100"><i class="fa fa-share-square mr-5"></i> {{ __('Proceder al checkout') }}</a>
                                <div class="mt-10">
                                    <a class="btn btn-primary w-100" href="{{ route('shop.index') }}"><i class="fa fa-arrow-left me-2"></i>{{ __('Seguir comprando') }}</a>
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="cart-container">
                        <div class="checkout-empty-container text-center py-5">
                            <i class="fa-solid fa-cart-xmark fa-4x text-muted mb-3"></i>
                            <h2>{{ __('Tu carrito esta vacio!') }}</h2>
                            <p>{{ __('Agrega productos para comenzar tu compra.') }}</p>
                            <a href="{{ route('shop.index') }}" class="btn btn-primary mt-3">{{ __('Seguir comprando') }}</a>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>
@endsection
