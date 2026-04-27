@extends('layouts.theme')

@section('title', 'Productos')

@section('content')
    @include('core::components.card', ['title' => 'Ecommerce - Productos'])
    @include('core::components.alerts')

    @php
        $variationType = old('product_variation_images_type', $settings['product_variation_images_type'] ?? 'per_variation');
        $autoSku = old('auto_sku_enabled', $settings['auto_sku_enabled'] ?? '') == '1';
    @endphp

    <form action="{{ route('settings.ecommerce.products.update') }}" method="POST">
        @csrf
        @method('PATCH')

        <div class="row g-4 align-items-start">
            <div class="col-lg-8">

                <div class="card">
                    <div class="card-header p-3 border-bottom">
                        <h6 class="mb-0 fw-bold">Configuracion del producto</h6>
                    </div>
                    <div class="card-body">

                        {{-- Variación de imágenes --}}
                        <div class="mb-4">
                            <div class="fw-semibold mb-1">Cómo mostrar imágenes de variaciones de productos</div>
                            <div class="d-flex gap-4 mb-1">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="product_variation_images_type" id="variation_per" value="per_variation"
                                        {{ $variationType == 'per_variation' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="variation_per">Sólo imágenes de variación.</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="product_variation_images_type" id="variation_all" value="all"
                                        {{ $variationType == 'all' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="variation_all">Imágenes de variación e imágenes principales del producto.</label>
                                </div>
                            </div>
                            <div class="form-text">Elija si desea mostrar solo imágenes específicas de la variación o incluir imágenes de la variación y del producto principal.</div>
                        </div>

                        <hr class="my-3">

                        {{-- Mostrar número de stock --}}
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="show_stock_count" value="1" id="show_stock_count"
                                    {{ old('show_stock_count', $settings['show_stock_count'] ?? '') == '1' ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="show_stock_count">Mostrar número de productos en el producto único</label>
                            </div>
                            <div class="form-text">Muestra el número total de productos en la página de detalles del producto.</div>
                        </div>

                        {{-- Mostrar productos agotados --}}
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="show_out_of_stock_products" value="1" id="show_out_of_stock_products"
                                    {{ old('show_out_of_stock_products', $settings['show_out_of_stock_products'] ?? '') == '1' ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="show_out_of_stock_products">Mostrar productos agotados</label>
                            </div>
                            <div class="form-text">Si está habilitado, los productos fuera de stock se mostrarán en la página de listado de productos.</div>
                        </div>

                        {{-- Opciones de producto --}}
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="product_options_enabled" value="1" id="product_options_enabled"
                                    {{ old('product_options_enabled', $settings['product_options_enabled'] ?? '') == '1' ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="product_options_enabled">Habilitar opciones de producto</label>
                            </div>
                            <div class="form-text">Permitir que los productos tengan opciones personalizables como tamaño, color, etc.</div>
                        </div>

                        {{-- Venta cruzada --}}
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="cross_sell_enabled" value="1" id="cross_sell_enabled"
                                    {{ old('cross_sell_enabled', $settings['cross_sell_enabled'] ?? '') == '1' ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="cross_sell_enabled">Habilitar productos de venta cruzada</label>
                            </div>
                            <div class="form-text">Muestre sugerencias de productos de venta cruzada para fomentar compras adicionales.</div>
                        </div>

                        {{-- Productos relacionados --}}
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="related_products_enabled" value="1" id="related_products_enabled"
                                    {{ old('related_products_enabled', $settings['related_products_enabled'] ?? '') == '1' ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="related_products_enabled">Habilitar productos relacionados</label>
                            </div>
                            <div class="form-text">Mostrar productos relacionados según la categoría o seleccionados por el administrador en el formulario de producto.</div>
                        </div>

                        {{-- Especificaciones --}}
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="specification_enabled" value="1" id="specification_enabled"
                                    {{ old('specification_enabled', $settings['specification_enabled'] ?? '') == '1' ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="specification_enabled">Habilitar la especificación del producto</label>
                            </div>
                            <div class="form-text">Si está habilitado, la tabla de especificaciones del producto se mostrará en la página de detalles del producto.</div>
                        </div>

                        {{-- SKU automático --}}
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="auto_sku_enabled" value="1" id="auto_sku_enabled"
                                    {{ $autoSku ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="auto_sku_enabled">Generar SKU automáticamente al crear el producto</label>
                            </div>
                            <div class="form-text">Genere automáticamente SKU únicos para nuevos productos según el formato que se muestra a continuación.</div>

                            <div id="auto-sku-format-block" class="border rounded p-3 mt-2" style="{{ $autoSku ? '' : 'display:none' }}">
                                <label for="auto_sku_format" class="form-label fw-semibold">Formato SKU</label>
                                <input type="text" name="auto_sku_format" id="auto_sku_format"
                                    class="form-control @error('auto_sku_format') is-invalid @enderror"
                                    placeholder="SKU-%s%s%s%s"
                                    value="{{ old('auto_sku_format', $settings['auto_sku_format'] ?? '') }}">
                                <div class="form-text">Puede utilizar %s (1 carácter de cadena) o %d (1 dígito) en el formato para generar una cadena aleatoria. Ej: SKU-%s%s%s%s-HN-%d%d%d%d</div>
                                @error('auto_sku_format')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- Código de barras requerido --}}
                        <div class="mb-0">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="barcode_required" value="1" id="barcode_required"
                                    {{ old('barcode_required', $settings['barcode_required'] ?? '') == '1' ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="barcode_required">Hacer que el código de barras del producto sea obligatorio</label>
                            </div>
                            <div class="form-text">Si está habilitado, se requerirá el código de barras del producto al crear un producto.</div>
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
    $('#auto_sku_enabled').on('change', function () {
        $('#auto-sku-format-block').slideToggle(150);
    });
});
</script>
@endpush
