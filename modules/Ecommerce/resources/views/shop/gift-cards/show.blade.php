@extends('ecommerce::layouts.shop-wowy')

@section('title', __('Gift Cards'))

@section('breadcrumb')
<div class="page-header breadcrumb-wrap">
    <div class="container">
        <div class="breadcrumb">
            <a href="{{ route('shop.index') }}" rel="nofollow"><i class="fa fa-home mr-5"></i>{{ __('Inicio') }}</a>
            <span></span> {{ __('Gift Cards') }}
        </div>
    </div>
</div>
@endsection

@section('content')
<section class="mt-60 mb-60">
    <div class="container">

        <div class="row justify-content-center g-5">

            <div class="col-md-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-header p-4" style="background: linear-gradient(135deg, #b10100 0%, #7da210 100%);">
                        <div class="text-white text-center">
                            <i class="fas fa-gift fa-3x mb-3"></i>
                            <h3 class="fw-bold mb-1">{{ __('Gift Card') }}</h3>
                            <p class="mb-0 opacity-75">{{ __('El regalo perfecto para quien más quieres') }}</p>
                        </div>
                    </div>
                    <div class="card-body p-4">

                        @if(session('success'))
                            <div class="alert alert-success">{{ session('success') }}</div>
                        @endif
                        @if(session('error'))
                            <div class="alert alert-danger">{{ session('error') }}</div>
                        @endif

                        <form action="{{ route('shop.gift-cards.purchase') }}" method="POST">
                            @csrf
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label fw-semibold">{{ __('Monto') }} <span class="text-danger">*</span></label>
                                    <div class="d-flex gap-2 flex-wrap mb-2">
                                        @foreach([25, 50, 100, 200, 500] as $preset)
                                            <button type="button" class="btn btn-outline-secondary btn-sm amount-preset"
                                                data-amount="{{ $preset }}">
                                                ${{ $preset }}
                                            </button>
                                        @endforeach
                                    </div>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" name="amount" id="amountInput"
                                            step="0.01" min="1"
                                            class="form-control @error('amount') is-invalid @enderror"
                                            value="{{ old('amount') }}"
                                            placeholder="0.00" required>
                                    </div>
                                    @error('amount')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>

                                <div class="col-12">
                                    <label class="form-label fw-semibold">{{ __('Tu nombre') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="buyer_name"
                                        class="form-control @error('buyer_name') is-invalid @enderror"
                                        value="{{ old('buyer_name', auth('ecommerce')->user()?->name) }}"
                                        placeholder="{{ __('¿De parte de quién?') }}" required>
                                    @error('buyer_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>

                                <div class="col-12">
                                    <label class="form-label fw-semibold">{{ __('Nombre del destinatario') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="recipient_name"
                                        class="form-control @error('recipient_name') is-invalid @enderror"
                                        value="{{ old('recipient_name') }}"
                                        placeholder="{{ __('¿Para quién es el regalo?') }}" required>
                                    @error('recipient_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>

                                <div class="col-12">
                                    <label class="form-label fw-semibold">{{ __('Email del destinatario') }} <span class="text-danger">*</span></label>
                                    <input type="email" name="recipient_email"
                                        class="form-control @error('recipient_email') is-invalid @enderror"
                                        value="{{ old('recipient_email') }}"
                                        placeholder="correo@ejemplo.com" required>
                                    @error('recipient_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>

                                <div class="col-12">
                                    <label class="form-label fw-semibold">{{ __('Mensaje personalizado') }}</label>
                                    <textarea name="message" rows="3"
                                        class="form-control @error('message') is-invalid @enderror"
                                        placeholder="{{ __('Escribe un mensaje especial para el destinatario...') }}"
                                        maxlength="500">{{ old('message') }}</textarea>
                                    @error('message')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>

                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary w-100 btn-lg">
                                        <i class="fas fa-gift me-2"></i> {{ __('Comprar gift card') }}
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-3">{{ __('¿Cómo funciona?') }}</h5>
                        <div class="d-flex gap-3 mb-3">
                            <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0"
                                style="width:40px;height:40px;">
                                <span class="fw-bold text-primary">1</span>
                            </div>
                            <div>
                                <div class="fw-semibold">{{ __('Elige el monto') }}</div>
                                <small class="text-muted">{{ __('Selecciona el valor de la gift card') }}</small>
                            </div>
                        </div>
                        <div class="d-flex gap-3 mb-3">
                            <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0"
                                style="width:40px;height:40px;">
                                <span class="fw-bold text-primary">2</span>
                            </div>
                            <div>
                                <div class="fw-semibold">{{ __('Ingresa los datos') }}</div>
                                <small class="text-muted">{{ __('Nombre y email del destinatario') }}</small>
                            </div>
                        </div>
                        <div class="d-flex gap-3">
                            <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0"
                                style="width:40px;height:40px;">
                                <span class="fw-bold text-primary">3</span>
                            </div>
                            <div>
                                <div class="fw-semibold">{{ __('Listo para usar') }}</div>
                                <small class="text-muted">{{ __('El código se aplica en el checkout') }}</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h6 class="fw-bold mb-2">{{ __('¿Tienes una gift card?') }}</h6>
                        <p class="text-muted small mb-3">{{ __('Aplica tu código durante el proceso de pago en el checkout.') }}</p>
                        <a href="{{ route('checkout.index') }}" class="btn btn-outline-primary w-100">
                            {{ __('Ir al checkout') }}
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
$(document).ready(function () {
    $('.amount-preset').on('click', function () {
        const amount = $(this).data('amount');
        $('#amountInput').val(amount);
        $('.amount-preset').removeClass('btn-primary').addClass('btn-outline-secondary');
        $(this).removeClass('btn-outline-secondary').addClass('btn-primary');
    });
});
</script>
@endpush
