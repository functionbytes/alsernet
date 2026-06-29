@extends('layouts.theme')

@section('title', 'Checkout')

@section('page_header')
    @include('core::components.card', ['title' => 'Ecommerce - Checkout'])
@endsection

@section('content')
    @include('core::components.alerts')

    @php
        $termsEnabled        = old('checkout_terms_enabled', $settings['checkout_terms_enabled'] ?? '') == '1';
        $recentlyViewed      = old('checkout_recently_viewed_enabled', $settings['checkout_recently_viewed_enabled'] ?? '') == '1';
        $loadFromPlugin      = old('checkout_load_from_plugin', $settings['checkout_load_from_plugin'] ?? '0');
    @endphp

    <form action="{{ route('settings.ecommerce.checkout.update') }}" method="POST">
        @csrf
        @method('PATCH')

        <div class="row g-4 align-items-start">
            <div class="col-lg-8">

                {{-- General --}}
                <div class="card mb-4">
                    <div class="card-header p-3 border-bottom">
                        <h6 class="mb-0 fw-bold">Configuracion general</h6>
                    </div>
                    <div class="card-body">

                        <div class="form-check mb-1">
                            <input class="form-check-input" type="checkbox" name="allow_guest_checkout" value="1" id="allow_guest_checkout"
                                {{ old('allow_guest_checkout', $settings['allow_guest_checkout'] ?? '') == '1' ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="allow_guest_checkout">Habilitar el pago de invitados</label>
                        </div>
                        <div class="form-text mb-3">Si está habilitado, los clientes pueden realizar un pedido sin crear una cuenta.</div>

                        <hr class="my-3">

                        <div class="mb-3">
                            <label for="order_minimum_amount" class="form-label fw-semibold">Monto mínimo de pedido para realizar un pedido</label>
                            <input type="number" name="order_minimum_amount" id="order_minimum_amount" step="0.01" min="0"
                                class="form-control @error('order_minimum_amount') is-invalid @enderror"
                                value="{{ old('order_minimum_amount', $settings['order_minimum_amount'] ?? '') }}">
                            <div class="form-text">Establezca el importe total mínimo requerido para procesar un pedido. No se aceptarán pedidos inferiores a este importe.</div>
                            @error('order_minimum_amount')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="order_minimum_quantity" class="form-label fw-semibold">Cantidad mínima de pedido</label>
                            <input type="number" name="order_minimum_quantity" id="order_minimum_quantity" min="0"
                                class="form-control @error('order_minimum_quantity') is-invalid @enderror"
                                value="{{ old('order_minimum_quantity', $settings['order_minimum_quantity'] ?? '') }}">
                            <div class="form-text">Cantidad mínima para realizar un pedido. Déjelo en 0 si no desea configurarlo.</div>
                            @error('order_minimum_quantity')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-0">
                            <label for="order_maximum_quantity" class="form-label fw-semibold">Cantidad máxima de pedido</label>
                            <input type="number" name="order_maximum_quantity" id="order_maximum_quantity" min="0"
                                class="form-control @error('order_maximum_quantity') is-invalid @enderror"
                                value="{{ old('order_maximum_quantity', $settings['order_maximum_quantity'] ?? '') }}">
                            <div class="form-text">Cantidad máxima para realizar un pedido. Déjelo en 0 si no desea configurarlo.</div>
                            @error('order_maximum_quantity')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                    </div>
                </div>

                {{-- Campos del formulario --}}
                <div class="card mb-4">
                    <div class="card-header p-3 border-bottom">
                        <h6 class="mb-0 fw-bold">Campos del formulario de pago</h6>
                    </div>
                    <div class="card-body">

                        {{-- Campos obligatorios --}}
                        <div class="fw-semibold mb-2">Campos obligatorios en la página de pago:</div>
                        <div class="row g-2 mb-1">
                            @foreach([
                                'phone'   => 'Teléfono',
                                'email'   => 'Correo electrónico',
                                'country' => 'País',
                                'state'   => 'Estado o Provincia',
                                'city'    => 'Ciudad',
                                'address' => 'Dirección',
                            ] as $field => $label)
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="checkout_required_{{ $field }}" value="1" id="checkout_required_{{ $field }}"
                                        {{ old('checkout_required_'.$field, $settings['checkout_required_'.$field] ?? '') == '1' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="checkout_required_{{ $field }}">{{ $label }}</label>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        <div class="form-text mb-4">Seleccione los campos obligatorios durante el proceso de compra. Los clientes deben completarlos para completar su pedido.</div>

                        {{-- Campos ocultos --}}
                        <div class="fw-semibold mb-2">Ocultar campos de cliente en la página de pago:</div>
                        <div class="row g-2 mb-1">
                            @foreach([
                                'phone'   => 'Teléfono',
                                'email'   => 'Correo electrónico',
                                'country' => 'País',
                                'state'   => 'Estado o Provincia',
                                'city'    => 'Ciudad',
                                'address' => 'Dirección',
                            ] as $field => $label)
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="checkout_hidden_{{ $field }}" value="1" id="checkout_hidden_{{ $field }}"
                                        {{ old('checkout_hidden_'.$field, $settings['checkout_hidden_'.$field] ?? '') == '1' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="checkout_hidden_{{ $field }}">{{ $label }}</label>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        <div class="form-text mb-4">Seleccione los campos que deben ocultarse en la página de pago. Estos campos no serán visibles para los clientes.</div>

                        <hr class="my-3">

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="checkout_zip_code_enabled" value="1" id="checkout_zip_code_enabled"
                                    {{ old('checkout_zip_code_enabled', $settings['checkout_zip_code_enabled'] ?? '') == '1' ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="checkout_zip_code_enabled">Habilitar código postal</label>
                            </div>
                            <div class="form-text">Habilite o deshabilite el campo de código postal en el formulario de dirección de envío.</div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="checkout_billing_address" value="1" id="checkout_billing_address"
                                    {{ old('checkout_billing_address', $settings['checkout_billing_address'] ?? '') == '1' ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="checkout_billing_address">Habilitar dirección de facturación</label>
                            </div>
                            <div class="form-text">Permitir que los clientes ingresen una dirección de facturación separada de su dirección de envío.</div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="checkout_fiscal_info" value="1" id="checkout_fiscal_info"
                                    {{ old('checkout_fiscal_info', $settings['checkout_fiscal_info'] ?? '') == '1' ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="checkout_fiscal_info">Mostrar campos de información fiscal en la página de pago</label>
                            </div>
                            <div class="form-text">Si esta opción está habilitada, se mostrarán los campos de información fiscal para ingresar los detalles de impuestos de la empresa para recibir una factura.</div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="checkout_terms_enabled" value="1" id="checkout_terms_enabled"
                                    {{ $termsEnabled ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="checkout_terms_enabled">Mostrar la casilla de verificación de términos y políticas</label>
                            </div>
                            <div class="form-text">Si está habilitado, los clientes deben aceptar los términos y la política antes de realizar un pedido.</div>
                        </div>

                        <div id="terms-prechecked-block" style="{{ $termsEnabled ? '' : 'display:none' }}">
                            <div class="form-check mb-0">
                                <input class="form-check-input" type="checkbox" name="checkout_terms_prechecked" value="1" id="checkout_terms_prechecked"
                                    {{ old('checkout_terms_prechecked', $settings['checkout_terms_prechecked'] ?? '') == '1' ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="checkout_terms_prechecked">Casilla de verificación de términos y políticas marcada de forma predeterminada</label>
                            </div>
                            <div class="form-text">Si está habilitada, la casilla de verificación de términos y políticas se marcará previamente cuando se cargue el formulario de pago.</div>
                        </div>

                    </div>
                </div>

                {{-- Países y ciudades --}}
                <div class="card mb-4">
                    <div class="card-header p-3 border-bottom">
                        <h6 class="mb-0 fw-bold">Países y ciudades</h6>
                    </div>
                    <div class="card-body">

                        <div class="mb-3">
                            <div class="fw-semibold mb-1">Cargar países, estados y ciudades desde la ubicación del complemento</div>
                            <div class="d-flex gap-4 mb-1">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="checkout_load_from_plugin" id="checkout_load_plugin_no" value="0"
                                        {{ $loadFromPlugin != '1' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="checkout_load_plugin_no">No</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="checkout_load_from_plugin" id="checkout_load_plugin_yes" value="1"
                                        {{ $loadFromPlugin == '1' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="checkout_load_plugin_yes">Sí</label>
                                </div>
                            </div>
                            <div class="form-text">Después de cambiar esta opción, deberá actualizar todas las direcciones nuevamente.</div>
                        </div>

                        <hr class="my-3">

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="checkout_city_free_text" value="1" id="checkout_city_free_text"
                                    {{ old('checkout_city_free_text', $settings['checkout_city_free_text'] ?? '') == '1' ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="checkout_city_free_text">Usar el campo ciudad como campo de texto libre</label>
                            </div>
                        </div>

                        <hr class="my-3">

                        <div class="mb-3">
                            <label for="checkout_default_country" class="form-label fw-semibold">País predeterminado en la página de pago</label>
                            <input type="text" name="checkout_default_country" id="checkout_default_country"
                                class="form-control @error('checkout_default_country') is-invalid @enderror"
                                placeholder="CO"
                                value="{{ old('checkout_default_country', $settings['checkout_default_country'] ?? '') }}">
                            <div class="form-text">Si selecciona un país, este se seleccionará de forma predeterminada en la página de pago. Use el código ISO de 2 letras (ej: CO, MX, US).</div>
                            @error('checkout_default_country')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <hr class="my-3">

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="checkout_filter_cities_by_state" value="1" id="checkout_filter_cities_by_state"
                                    {{ old('checkout_filter_cities_by_state', $settings['checkout_filter_cities_by_state'] ?? '') == '1' ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="checkout_filter_cities_by_state">Filtrar ciudades por estado</label>
                            </div>
                            <div class="form-text">Cuando está habilitado, solo se mostrarán las ciudades del estado seleccionado en el desplegable de ciudades del checkout.</div>
                        </div>

                    </div>
                </div>

                {{-- Productos vistos y cantidad --}}
                <div class="card">
                    <div class="card-header p-3 border-bottom">
                        <h6 class="mb-0 fw-bold">Opciones adicionales</h6>
                    </div>
                    <div class="card-body">

                        <div class="mb-1">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="checkout_recently_viewed_enabled" value="1" id="checkout_recently_viewed_enabled"
                                    {{ $recentlyViewed ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="checkout_recently_viewed_enabled">Habilitar productos vistos recientemente por el cliente</label>
                            </div>
                            <div class="form-text">Rastrea y muestra los productos que los clientes han visto recientemente.</div>
                        </div>

                        <div id="recently-viewed-block" style="{{ $recentlyViewed ? '' : 'display:none' }}">
                            <div class="border rounded p-3 mt-2 mb-3">
                                <label for="checkout_recently_viewed_max" class="form-label fw-semibold">Número máximo de productos vistos recientemente por clientes</label>
                                <input type="number" name="checkout_recently_viewed_max" id="checkout_recently_viewed_max" min="0"
                                    class="form-control @error('checkout_recently_viewed_max') is-invalid @enderror"
                                    value="{{ old('checkout_recently_viewed_max', $settings['checkout_recently_viewed_max'] ?? '24') }}">
                                <div class="form-text">Si se establece en 0, no habrá límite.</div>
                                @error('checkout_recently_viewed_max')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <hr class="my-3">

                        <div class="mb-0">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="checkout_quantity_editable" value="1" id="checkout_quantity_editable"
                                    {{ old('checkout_quantity_editable', $settings['checkout_quantity_editable'] ?? '') == '1' ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="checkout_quantity_editable">Permitir que los clientes cambien la cantidad del producto en la página de pago</label>
                            </div>
                            <div class="form-text">Cuando está habilitado, los clientes pueden modificar la cantidad de productos directamente en la página de pago sin regresar al carrito.</div>
                        </div>

                    </div>
                </div>

            </div>

            <div class="col-lg-4">
                <div class="sticky-top" style="top:80px">
                    <div class="card">
                        <div class="card-header p-3 border-bottom">
                            <h6 class="mb-0 fw-bold">Guardar</h6>
                        </div>
                        <div class="card-body">
                            <button type="submit" class="btn btn-primary w-100">Guardar cambios</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
<script>
$(function () {
    $('#checkout_terms_enabled').on('change', function () {
        $('#terms-prechecked-block').slideToggle(150);
    });

    $('#checkout_recently_viewed_enabled').on('change', function () {
        $('#recently-viewed-block').slideToggle(150);
    });
});
</script>
@endpush
