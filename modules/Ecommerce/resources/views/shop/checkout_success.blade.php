@extends('ecommerce::layouts.shop-wowy')

@section('title', 'Orden confirmada')

@section('breadcrumb')
<div class="page-header breadcrumb-wrap">
    <div class="container">
        <div class="breadcrumb">
            <a href="{{ route('shop.index') }}" rel="nofollow"><i class="fa fa-home mr-5"></i>{{ __('Inicio') }}</a>
            <span></span> {{ __('Orden confirmada') }}
        </div>
    </div>
</div>
@endsection

@section('content')
<section class="mt-60 mb-60">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 m-auto text-center">
                @if($order->payment_status === 'completed')
                    <div class="mb-4">
                        <i class="fas fa-check-circle fa-5x text-success"></i>
                    </div>
                    <h2>{{ __('Orden confirmada') }}</h2>
                    <p class="text-muted">{{ __('Gracias por tu compra. Tu orden') }} <strong>{{ $order->code ?? $order->id }}</strong> {{ __('ha sido recibida y pagada.') }}</p>
                @elseif($order->payment_status === 'pending' && in_array($order->payment_method, ['wompi', 'card']))
                    <div class="mb-4">
                        <i class="fas fa-clock fa-5x text-warning"></i>
                    </div>
                    <h2>{{ __('Pago pendiente') }}</h2>
                    <p class="text-muted">{{ __('Tu orden') }} <strong>{{ $order->code ?? $order->id }}</strong> {{ __('esta pendiente de pago.') }}</p>
                    <a href="{{ route('checkout.retry', $order) }}" class="btn btn-primary mt-3 me-2">
                        <i class="fas fa-redo me-2"></i>{{ __('Reintentar pago') }}
                    </a>
                @elseif($order->payment_status === 'failed')
                    <div class="mb-4">
                        <i class="fas fa-times-circle fa-5x text-danger"></i>
                    </div>
                    <h2>{{ __('Pago fallido') }}</h2>
                    <p class="text-muted">{{ __('El pago de tu orden') }} <strong>{{ $order->code ?? $order->id }}</strong> {{ __('no pudo ser procesado.') }}</p>
                    <a href="{{ route('checkout.retry', $order) }}" class="btn btn-primary mt-3 me-2">
                        <i class="fas fa-redo me-2"></i>{{ __('Reintentar pago') }}
                    </a>
                @else
                    <div class="mb-4">
                        <i class="fas fa-check-circle fa-5x text-success"></i>
                    </div>
                    <h2>{{ __('Orden confirmada') }}</h2>
                    <p class="text-muted">{{ __('Gracias por tu compra. Tu orden') }} <strong>{{ $order->code ?? $order->id }}</strong> {{ __('ha sido recibida.') }}</p>
                @endif

                <p class="text-muted mt-2">{{ __('Te enviaremos un correo de confirmacion con los detalles.') }}</p>
                <a href="{{ route('shop.index') }}" class="btn btn-outline-primary mt-3">{{ __('Seguir comprando') }}</a>
            </div>
        </div>
    </div>
</section>
@endsection
