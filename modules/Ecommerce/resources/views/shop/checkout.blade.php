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
        <div class="mb-4">
            <div class="checkout-stepper d-flex align-items-center justify-content-center gap-2">
                <div class="stepper-item active">
                    <div class="stepper-circle"><i class="fas fa-shopping-cart"></i></div>
                    <span>{{ __('Carrito') }}</span>
                </div>
                <div class="stepper-line"></div>
                <div class="stepper-item active">
                    <div class="stepper-circle"><i class="fas fa-truck"></i></div>
                    <span>{{ __('Envio y pago') }}</span>
                </div>
                <div class="stepper-line"></div>
                <div class="stepper-item">
                    <div class="stepper-circle"><i class="fas fa-check"></i></div>
                    <span>{{ __('Confirmacion') }}</span>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-8 col-md-12">
                <form id="checkout-form" action="{{ route('checkout.store') }}" method="POST">
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
                                <select name="country_id" id="country_id" class="form-select" required>
                                    <option value="">Selecciona un país...</option>
                                </select>
                                <input type="hidden" name="country" id="country_text">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('Departamento / Region') }} <span class="text-danger">*</span></label>
                                <select name="state_id" id="state_id" class="form-select" required disabled>
                                    <option value="">Selecciona primero el país</option>
                                </select>
                                <input type="hidden" name="region" id="region_text">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('Ciudad') }} <span class="text-danger">*</span></label>
                                <select name="city_id" id="city_id" class="form-select" required disabled>
                                    <option value="">Selecciona primero el departamento</option>
                                </select>
                                <input type="hidden" name="city" id="city_text">
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
                        @forelse($paymentMethods as $index => $method)
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="payment_method"
                                       value="{{ $method['key'] }}"
                                       id="pay_{{ $method['key'] }}"
                                       {{ $index === 0 ? 'checked' : '' }}>
                                <label class="form-check-label" for="pay_{{ $method['key'] }}">
                                    @if($method['key'] === 'wompi')
                                        <i class="fas fa-credit-card me-1"></i>
                                    @elseif($method['key'] === 'cod')
                                        <i class="fas fa-motorcycle me-1"></i>
                                    @elseif($method['key'] === 'bank_transfer')
                                        <i class="fas fa-university me-1"></i>
                                    @endif
                                    {{ $method['name'] }}
                                    @if(!empty($method['description']))
                                        <small class="text-muted d-block">{{ $method['description'] }}</small>
                                    @endif
                                </label>
                            </div>
                        @empty
                            <div class="alert alert-warning mb-0">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                {{ __('No hay métodos de pago disponibles.') }}
                            </div>
                        @endforelse
                    </div>

                    <div class="mb-4">
                        <h4 class="mb-3">{{ __('Cupon de descuento') }}</h4>
                        <div class="input-group">
                            <input type="text" name="coupon_code" class="form-control" placeholder="{{ __('Codigo de cupon (opcional)') }}" value="{{ old('coupon_code') }}">
                            <span class="input-group-text"><i class="fas fa-tag"></i></span>
                        </div>
                        @error('coupon_code')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <h4 class="mb-3">{{ __('Notas') }}</h4>
                        <textarea name="note" class="form-control" rows="2" placeholder="{{ __('Notas adicionales sobre tu orden...') }}">{{ old('note') }}</textarea>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">{{ __('Confirmar orden') }}</button>
                </form>
            </div>

            <div class="col-lg-4 col-md-12">
                <div class="checkout-summary-sticky">
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
                                @if($tax > 0)
                                <tr>
                                    <td class="cart_total_label">{{ __('Impuesto') }}</td>
                                    <td class="cart_total_amount"><span class="font-lg fw-900 text-brand">${{ number_format($tax, 2) }}</span></td>
                                </tr>
                                @endif
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
    </div>
</section>
@endsection

@push('scripts')
<script>
$(function () {
    // Load countries on page load
    $.getJSON('/api/locations/countries', function (data) {
        var $country = $('#country_id');
        $.each(data, function (i, c) {
            $country.append($('<option>').val(c.id).text(c.name));
        });
    });

    // Country → States
    $('#country_id').on('change', function () {
        var id = $(this).val();
        var text = $(this).find('option:selected').text();
        $('#country_text').val(text);
        $('#state_id').prop('disabled', true).html('<option value="">Cargando...</option>');
        $('#city_id').prop('disabled', true).html('<option value="">Selecciona primero el departamento</option>');
        $('#region_text, #city_text').val('');

        if (!id) { return; }

        $.getJSON('/api/locations/states', { country_id: id }, function (data) {
            var $state = $('#state_id').empty().append('<option value="">Selecciona un departamento...</option>');
            $.each(data, function (i, s) {
                $state.append($('<option>').val(s.id).text(s.name));
            });
            $state.prop('disabled', false);
        });
    });

    // State → Cities
    $('#state_id').on('change', function () {
        var id = $(this).val();
        var text = $(this).find('option:selected').text();
        $('#region_text').val(text);
        $('#city_id').prop('disabled', true).html('<option value="">Cargando...</option>');
        $('#city_text').val('');

        if (!id) { return; }

        $.getJSON('/api/locations/cities', { state_id: id }, function (data) {
            var $city = $('#city_id').empty().append('<option value="">Selecciona una ciudad...</option>');
            $.each(data, function (i, c) {
                $city.append($('<option>').val(c.id).text(c.name));
            });
            $city.prop('disabled', false);
        });
    });

    $('#city_id').on('change', function () {
        $('#city_text').val($(this).find('option:selected').text());
    });
});
</script>
@include('ecommerce::shop.partials._checkout-validation-script')
@endpush
