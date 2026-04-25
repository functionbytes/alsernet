@extends('layouts.theme')

@section('title', 'Configuracion Ecommerce')

@section('content')
    @include('core::components.card', ['title' => 'Configuracion Ecommerce'])

    <div class="widget-content searchable-container list">
        @include('core::components.alerts')

        <form action="{{ route('ecommerce.settings.update') }}" method="POST">
            @csrf @method('PUT')
            <div class="card">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Moneda</label>
                                <input type="text" name="currency" class="form-control" value="{{ $settings['currency'] }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Simbolo de moneda</label>
                                <input type="text" name="currency_symbol" class="form-control" value="{{ $settings['currency_symbol'] }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Productos por pagina</label>
                                <input type="number" name="products_per_page" class="form-control" value="{{ $settings['products_per_page'] }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check mt-4">
                                <input class="form-check-input" type="checkbox" name="allow_guest_checkout" value="1" id="allow_guest_checkout" {{ $settings['allow_guest_checkout'] ? 'checked' : '' }}>
                                <label class="form-check-label" for="allow_guest_checkout">Permitir checkout como invitado</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer text-end">
                    <button type="submit" class="btn btn-primary">Guardar configuracion</button>
                </div>
            </div>
        </form>
    </div>
@endsection
