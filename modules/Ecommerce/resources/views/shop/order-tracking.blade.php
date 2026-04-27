@extends('ecommerce::layouts.shop-wowy')

@section('title', 'Seguimiento de orden')

@section('breadcrumb')
<div class="page-header breadcrumb-wrap">
    <div class="container">
        <div class="breadcrumb">
            <a href="{{ route('shop.index') }}" rel="nofollow"><i class="fa fa-home mr-5"></i>{{ __('Inicio') }}</a>
            <span></span> {{ __('Seguimiento de orden') }}
        </div>
    </div>
</div>
@endsection

@section('content')
<section class="mt-60 mb-60">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-8">

                <div class="card mb-4">
                    <div class="card-body p-4">
                        <h4 class="mb-1 fw-bold">{{ __('Seguimiento de orden') }}</h4>
                        <p class="text-muted mb-4">{{ __('Ingresa el codigo de tu orden para consultar su estado.') }}</p>

                        @if(session('error') ?? isset($error))
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                {{ session('error') ?? $error }}
                            </div>
                        @endif

                        <form action="{{ route('ecommerce.tracking.search') }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label for="code" class="form-label fw-semibold">{{ __('Codigo de orden') }}</label>
                                <input type="text" id="code" name="code"
                                       class="form-control form-control-lg @error('code') is-invalid @enderror"
                                       placeholder="{{ __('Ej. ORD-ABC123') }}"
                                       value="{{ old('code', isset($order) ? $order->code : '') }}"
                                       required>
                                @error('code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search me-2"></i>{{ __('Buscar') }}
                            </button>
                        </form>
                    </div>
                </div>

                @isset($order)
                    <div class="card">
                        <div class="card-header p-3 border-bottom">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0 fw-bold">{{ __('Orden') }} {{ $order->code }}</h5>
                                <span class="badge bg-{{ match($order->status->value ?? $order->status) {
                                    'pending' => 'warning',
                                    'processing' => 'info',
                                    'shipped' => 'primary',
                                    'delivered', 'completed' => 'success',
                                    'cancelled', 'refunded' => 'danger',
                                    default => 'secondary',
                                } }}">
                                    {{ $order->status->label() ?? ucfirst($order->status) }}
                                </span>
                            </div>
                        </div>
                        <div class="card-body">
                            <dl class="row mb-0">
                                <dt class="col-5 text-muted fw-normal">{{ __('Fecha') }}</dt>
                                <dd class="col-7">{{ $order->created_at->format('d/m/Y') }}</dd>

                                <dt class="col-5 text-muted fw-normal">{{ __('Pago') }}</dt>
                                <dd class="col-7">
                                    <span class="badge bg-{{ $order->payment_status === 'completed' ? 'success' : ($order->payment_status === 'failed' ? 'danger' : 'warning') }}">
                                        {{ ucfirst($order->payment_status ?? '—') }}
                                    </span>
                                </dd>

                                <dt class="col-5 text-muted fw-normal">{{ __('Total') }}</dt>
                                <dd class="col-7 fw-semibold">{{ number_format($order->total, 2) }}</dd>
                            </dl>
                        </div>

                        @if($order->shipments->count())
                            <div class="card-footer border-top bg-white">
                                <h6 class="fw-bold mb-3">{{ __('Envios') }}</h6>
                                @foreach($order->shipments as $shipment)
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div>
                                            <small class="text-muted d-block">{{ __('Transportista') }}: {{ $shipment->carrier ?? '—' }}</small>
                                            @if($shipment->tracking_number)
                                                <small class="text-muted d-block">{{ __('Numero de seguimiento') }}: <strong>{{ $shipment->tracking_number }}</strong></small>
                                            @endif
                                        </div>
                                        <span class="badge bg-{{ $shipment->status === 'delivered' ? 'success' : 'info' }}">
                                            {{ ucfirst($shipment->status ?? '—') }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endisset

            </div>
        </div>
    </div>
</section>
@endsection
