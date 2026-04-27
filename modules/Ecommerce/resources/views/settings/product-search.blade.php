@extends('layouts.theme')

@section('title', 'Búsqueda de productos')

@section('content')
    @include('core::components.card', ['title' => 'Ecommerce - Búsqueda de productos'])
    @include('core::components.alerts')

    @php $priceFilter = old('search_filter_price', $settings['search_filter_price'] ?? '') == '1'; @endphp

    <form action="{{ route('settings.ecommerce.product-search.update') }}" method="POST">
        @csrf
        @method('PATCH')

        <div class="row g-4 align-items-start">
            <div class="col-lg-8">

                <div class="card mb-4">
                    <div class="card-header p-3 border-bottom">
                        <h6 class="mb-0 fw-bold">Opciones de búsqueda</h6>
                    </div>
                    <div class="card-body">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="search_exact_phrase" value="1" id="search_exact_phrase"
                                {{ old('search_exact_phrase', $settings['search_exact_phrase'] ?? '') == '1' ? 'checked' : '' }}>
                            <label class="form-check-label" for="search_exact_phrase">Busca una frase exacta</label>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header p-3 border-bottom">
                        <h6 class="mb-0 fw-bold">Buscar productos por</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="search_field_name" value="1" id="search_field_name"
                                        {{ old('search_field_name', $settings['search_field_name'] ?? '') == '1' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="search_field_name">Nombre</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="search_field_sku" value="1" id="search_field_sku"
                                        {{ old('search_field_sku', $settings['search_field_sku'] ?? '') == '1' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="search_field_sku">SKU</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="search_field_variation_sku" value="1" id="search_field_variation_sku"
                                        {{ old('search_field_variation_sku', $settings['search_field_variation_sku'] ?? '') == '1' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="search_field_variation_sku">SKU de variación</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="search_field_description" value="1" id="search_field_description"
                                        {{ old('search_field_description', $settings['search_field_description'] ?? '') == '1' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="search_field_description">Descripción</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="search_field_brand" value="1" id="search_field_brand"
                                        {{ old('search_field_brand', $settings['search_field_brand'] ?? '') == '1' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="search_field_brand">Marca</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="search_field_tags" value="1" id="search_field_tags"
                                        {{ old('search_field_tags', $settings['search_field_tags'] ?? '') == '1' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="search_field_tags">Etiquetas</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header p-3 border-bottom">
                        <h6 class="mb-0 fw-bold">Filtros disponibles</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="search_filter_category" value="1" id="search_filter_category"
                                        {{ old('search_filter_category', $settings['search_filter_category'] ?? '') == '1' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="search_filter_category">Habilitar el filtro de productos por categorías</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="search_filter_brand" value="1" id="search_filter_brand"
                                        {{ old('search_filter_brand', $settings['search_filter_brand'] ?? '') == '1' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="search_filter_brand">Habilitar filtrar productos por marcas</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="search_filter_tags" value="1" id="search_filter_tags"
                                        {{ old('search_filter_tags', $settings['search_filter_tags'] ?? '') == '1' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="search_filter_tags">Habilitar filtrar productos por etiquetas</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="search_filter_attributes" value="1" id="search_filter_attributes"
                                        {{ old('search_filter_attributes', $settings['search_filter_attributes'] ?? '') == '1' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="search_filter_attributes">Habilitar filtrar productos por atributos</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="search_filter_price" value="1" id="search_filter_price"
                                        {{ $priceFilter ? 'checked' : '' }}>
                                    <label class="form-check-label" for="search_filter_price">Habilitar el filtro de productos por precio</label>
                                </div>
                            </div>
                            <div class="col-12" id="price-max-block" style="{{ $priceFilter ? '' : 'display:none' }}">
                                <div class="border rounded p-3 mt-1">
                                    <label for="search_max_price" class="form-label fw-semibold">Precio máximo del producto para el filtro</label>
                                    <input type="number" name="search_max_price" id="search_max_price" min="0"
                                        class="form-control @error('search_max_price') is-invalid @enderror"
                                        value="{{ old('search_max_price', $settings['search_max_price'] ?? '') }}"
                                        placeholder="Si está vacío o es cero, obtendrá el precio máximo del producto de sus productos existentes.">
                                    <div class="form-text">Puedes establecer un precio fijo o se obtendrá dinámicamente el precio máximo de tus productos existentes.</div>
                                    @error('search_max_price')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
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
    $('#search_filter_price').on('change', function () {
        $('#price-max-block').slideToggle(150);
    });
});
</script>
@endpush
