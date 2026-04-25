@extends('ecommerce::layouts.shop-wowy')

@section('title', 'Checkout')

@section('breadcrumb')
<div class="page-header breadcrumb-wrap">
    <div class="container">
        <div class="breadcrumb">
            <a href="{{ route('shop.index') }}" rel="nofollow"><i class="fa fa-home mr-5"></i>{{ __('Inicio') }}</a>
            <span></span> {{ __('Checkout') }}
        </div>
    </div>
</div>
@endsection

@section('content')
<section class="mt-60 mb-60">
    <div class="container">
        <div class="row">
            <div class="col-md-7">
                <form action="{{ route('checkout.store') }}" method="POST">
                    @csrf
                    <div class="mb-4">
                        <h4 class="mb-3">{{ __('Informacion de envio') }}</h4>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">{{ __('Nombre completo') }} <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" required value="{{ old('name', auth('ecommerce')->user()->name ?? '') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('Email') }} <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control" required value="{{ old('email', auth('ecommerce')->user()->email ?? '') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('Telefono') }}</label>
                                <input type="text" name="phone" class="form-control" value="{{ old('phone') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('Pais') }} <span class="text-danger">*</span></label>
                                <input type="text" name="country" class="form-control" required value="{{ old('country') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('Ciudad') }} <span class="text-danger">*</span></label>
                                <input type="text" name="city" class="form-control" required value="{{ old('city') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('Departamento / Region') }} <span class="text-danger">*</span></label>
                                <input type="text" name="region" class="form-control" required value="{{ old('region') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('Codigo postal') }}</label>
                                <input type="text" name="zip_code" class="form-control" value="{{ old('zip_code') }}">
                            </div>
                            <div class="col-12">
                                <label class="form-label">{{ __('Direccion') }} <span class="text-danger">*</span></label>
                                <textarea name="address" class="form-control" rows="2" required>{{ old('address') }}</textarea>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <h4 class="mb-3">{{ __('Metodo de pago') }} <span class="text-danger">*</span></h4>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="payment_method" value="cash" id="pay_cash" checked>
                            <label class="form-check-label" for="pay_cash">{{ __('Efectivo / Contra entrega') }}</label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="payment_method" value="transfer" id="pay_transfer">
                            <label class="form-check-label" for="pay_transfer">{{ __('Transferencia bancaria') }}</label>
                        </div>
                        @if(!empty($paymentMethods) && isset($paymentMethods['wompi']))
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="payment_method" value="card" id="pay_card">
                            <label class="form-check-label" for="pay_card">
                                <i class="fas fa-credit-card text-primary me-1"></i>{{ __('Tarjeta de credito / debito') }}
                                <small class="text-muted d-block">{{ __('Paga con Wompi: Tarjeta, PSE, Nequi y mas') }}</small>
                            </label>
                        </div>
                        @else
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="payment_method" value="card" id="pay_card">
                            <label class="form-check-label" for="pay_card">{{ __('Tarjeta de credito / debito') }}</label>
                        </div>
                        @endif
                    </div>

                    <div class="mb-4">
                        <h4 class="mb-3">{{ __('Notas') }}</h4>
                        <textarea name="note" class="form-control" rows="2" placeholder="{{ __('Notas adicionales sobre tu orden...') }}">{{ old('note') }}</textarea>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">{{ __('Confirmar orden') }}</button>
                </form>
            </div>

            <div class="col-md-5">
                <div class="border p-md-4 p-30 border-radius-10 cart-totals">
                    <div class="heading_s1 mb-3">
                        <h4>{{ __('Resumen de orden') }}</h4>
                    </div>
                    <div class="table-responsive">
                        <table class="table">
                            <tbody>
                                @foreach($cartItems as $item)
                                    <tr>
                                        <td class="cart_total_label">{{ $item->product->name }} x{{ $item->qty }}</td>
                                        <td class="cart_total_amount">
                                            <span class="font-lg fw-900 text-brand">${{ number_format($item->product->final_price * $item->qty, 2) }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                                <tr>
                                    <td class="cart_total_label">{{ __('Subtotal') }}</td>
                                    <td class="cart_total_amount"><span class="font-lg fw-900 text-brand">${{ number_format($subtotal, 2) }}</span></td>
                                </tr>
                                <tr>
                                    <td class="cart_total_label">{{ __('Envio') }}</td>
                                    <td class="cart_total_amount"><span class="font-lg fw-900 text-brand">${{ number_format($shipping, 2) }}</span></td>
                                </tr>
                                <tr>
                                    <td class="cart_total_label">{{ __('Total') }}</td>
                                    <td class="cart_total_amount">
                                        <strong><span class="font-xl fw-900 text-brand">${{ number_format($total, 2) }}</span></strong>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
