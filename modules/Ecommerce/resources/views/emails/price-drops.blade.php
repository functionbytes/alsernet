@extends('ecommerce::emails.layouts.base', ['subject' => 'Productos en oferta de tu wishlist'])
@section('content')
@endsection

@php
    $title = 'Productos de tu wishlist en oferta';
@endphp

@component('ecommerce::emails.layouts.base', ['slot' => null])
<h2 style="margin:0 0 12px;color:#1a2030;">Hola {{ $customer->name }},</h2>
<p>Algunos productos de tu lista de deseos bajaron de precio. ¡Aprovéchalos antes de que se acaben!</p>

<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:16px 0;">
    @foreach($products as $product)
    <tr>
        <td style="padding:12px;border-bottom:1px solid #eee;">
            <table cellpadding="0" cellspacing="0" border="0" width="100%">
                <tr>
                    <td style="vertical-align:top;">
                        <h4 style="margin:0 0 4px;color:#1a2030;">{{ $product->name }}</h4>
                        <p style="margin:0;">
                            <span style="color:#90bb13;font-weight:700;font-size:18px;">${{ number_format($product->sale_price, 2) }}</span>
                            <span style="color:#999;text-decoration:line-through;margin-left:8px;">${{ number_format($product->price, 2) }}</span>
                        </p>
                    </td>
                    <td style="vertical-align:top;text-align:right;">
                        <a href="{{ route('shop.product', $product->slug) }}" style="background:#90bb13;color:#fff;padding:8px 16px;border-radius:4px;text-decoration:none;font-weight:600;">Ver oferta</a>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    @endforeach
</table>

<p style="text-align:center;margin-top:24px;">
    <a href="{{ url('/tienda') }}" style="color:#90bb13;font-weight:600;">Ver toda la tienda &rarr;</a>
</p>
@endcomponent
