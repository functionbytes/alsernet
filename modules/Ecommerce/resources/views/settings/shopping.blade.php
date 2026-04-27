@extends('layouts.theme')

@section('title', 'Compras')

@section('content')
    @include('core::components.card', ['title' => 'Ecommerce - Compras'])
    @include('core::components.alerts')

    @php
        $cartEnabled  = old('cart_enabled', $settings['cart_enabled'] ?? '') == '1';
        $quickBuy     = old('quick_buy_enabled', $settings['quick_buy_enabled'] ?? '') == '1';
        $wishlist     = old('wishlist_enabled', $settings['wishlist_enabled'] ?? '') == '1';
        $quickDest    = old('quick_buy_destination', $settings['quick_buy_destination'] ?? 'checkout');
    @endphp

    <form action="{{ route('settings.ecommerce.shopping.update') }}" method="POST">
        @csrf
        @method('PATCH')

        <div class="row g-4 align-items-start">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header p-3 border-bottom">
                        <h6 class="mb-0 fw-bold">Compras</h6>
                    </div>
                    <div class="card-body">

                        {{-- Carrito --}}
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="cart_enabled" value="1" id="cart_enabled"
                                    {{ $cartEnabled ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="cart_enabled">Habilitar carrito de compras</label>
                            </div>
                            <div class="form-text">Si está deshabilitado, se eliminará el botón del carrito y su sitio pasará a ser solo una pantalla de catálogo.</div>

                            <div id="cart-extra-block" class="border rounded p-3 mt-2" style="{{ $cartEnabled ? '' : 'display:none' }}">

                                {{-- Seguimiento de pedidos --}}
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="order_tracking_enabled" value="1" id="order_tracking_enabled"
                                            {{ old('order_tracking_enabled', $settings['order_tracking_enabled'] ?? '') == '1' ? 'checked' : '' }}>
                                        <label class="form-check-label fw-semibold" for="order_tracking_enabled">Habilitar seguimiento de pedidos</label>
                                    </div>
                                    <div class="form-text">Los clientes pueden rastrear sus pedidos ingresando el código de pedido y el correo electrónico</div>
                                </div>

                                {{-- Comprobante de pago --}}
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="payment_proof_enabled" value="1" id="payment_proof_enabled"
                                            {{ old('payment_proof_enabled', $settings['payment_proof_enabled'] ?? '') == '1' ? 'checked' : '' }}>
                                        <label class="form-check-label fw-semibold" for="payment_proof_enabled">Habilitar la carga de comprobante de pago</label>
                                    </div>
                                    <div class="form-text">Permite que los clientes carguen el comprobante de pago de sus pedidos. Esto es útil para métodos de pago manuales.</div>
                                </div>

                                {{-- Compra rápida --}}
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="quick_buy_enabled" value="1" id="quick_buy_enabled"
                                            {{ $quickBuy ? 'checked' : '' }}>
                                        <label class="form-check-label fw-semibold" for="quick_buy_enabled">Habilitar botón de compra rápida</label>
                                    </div>
                                    <div class="form-text">Se mostrará un botón "Comprar ahora" en la ficha del producto y la página de detalles. Al hacer clic en este botón, el producto se añadirá al carrito y será redirigido a la página de compra/pago.</div>

                                    <div id="quick-buy-destination-block" class="mt-2" style="{{ $quickBuy ? '' : 'display:none' }}">
                                        <div class="fw-semibold small mb-1">Página de destino de compra rápida</div>
                                        <div class="d-flex gap-4">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="quick_buy_destination" id="quick_buy_checkout" value="checkout"
                                                    {{ $quickDest == 'checkout' ? 'checked' : '' }}>
                                                <label class="form-check-label" for="quick_buy_checkout">Página de pago</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="quick_buy_destination" id="quick_buy_cart" value="cart"
                                                    {{ $quickDest == 'cart' ? 'checked' : '' }}>
                                                <label class="form-check-label" for="quick_buy_cart">Página del carrito</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Auto-confirmar --}}
                                <div class="mb-0">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="auto_confirm_order" value="1" id="auto_confirm_order"
                                            {{ old('auto_confirm_order', $settings['auto_confirm_order'] ?? '') == '1' ? 'checked' : '' }}>
                                        <label class="form-check-label fw-semibold" for="auto_confirm_order">Orden de confirmación automática</label>
                                    </div>
                                    <div class="form-text">Si está habilitado, el pedido se confirmará automáticamente después de que el cliente realice un pedido.</div>
                                </div>

                            </div>
                        </div>

                        <hr class="my-3">

                        {{-- Ocultar precio --}}
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="hide_price" value="1" id="hide_price"
                                    {{ old('hide_price', $settings['hide_price'] ?? '') == '1' ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="hide_price">Ocultar el precio del producto</label>
                            </div>
                            <div class="form-text">Esta opción solo funciona si desactivas el carrito de compras, lo que convierte tu sitio web en una página solo de catálogo. Si la activas, el precio del producto se ocultará. Es útil cuando quieres ocultar el precio y que los clientes se pongan en contacto contigo para consultarlo.</div>
                        </div>

                        <hr class="my-3">

                        {{-- Lista de deseos --}}
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="wishlist_enabled" value="1" id="wishlist_enabled"
                                    {{ $wishlist ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="wishlist_enabled">Habilitar lista de deseos</label>
                            </div>
                            <div class="form-text">Si está deshabilitado, el botón de lista de deseos se eliminará del sitio.</div>

                            <div id="wishlist-extra-block" class="border rounded p-3 mt-2" style="{{ $wishlist ? '' : 'display:none' }}">
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" name="wishlist_share" value="1" id="wishlist_share"
                                        {{ old('wishlist_share', $settings['wishlist_share'] ?? '') == '1' ? 'checked' : '' }}>
                                    <label class="form-check-label fw-semibold" for="wishlist_share">Habilitar compartir lista de deseos</label>
                                </div>
                                <div>
                                    <label for="wishlist_duration" class="form-label fw-semibold">Duración de la lista de deseos compartida (días)</label>
                                    <input type="number" name="wishlist_duration" id="wishlist_duration" min="1"
                                        class="form-control @error('wishlist_duration') is-invalid @enderror"
                                        value="{{ old('wishlist_duration', $settings['wishlist_duration'] ?? '7') }}">
                                    <div class="form-text">La vida útil de la lista de deseos compartida en días. Pasado este tiempo, la lista de deseos compartida se eliminará.</div>
                                    @error('wishlist_duration')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr class="my-3">

                        {{-- Comparación --}}
                        <div class="mb-0">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="compare_enabled" value="1" id="compare_enabled"
                                    {{ old('compare_enabled', $settings['compare_enabled'] ?? '') == '1' ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="compare_enabled">Habilitar comparación</label>
                            </div>
                            <div class="form-text">Si está deshabilitado, el botón comparar se eliminará del sitio.</div>
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
    $('#cart_enabled').on('change', function () {
        $('#cart-extra-block').slideToggle(150);
    });

    $('#quick_buy_enabled').on('change', function () {
        $('#quick-buy-destination-block').slideToggle(150);
    });

    $('#wishlist_enabled').on('change', function () {
        $('#wishlist-extra-block').slideToggle(150);
    });
});
</script>
@endpush
