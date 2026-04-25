@extends('layouts.theme')

@section('title', 'Nuevo producto')

@section('content')
    @include('core::components.card', ['title' => 'Nuevo producto'])

    <div class="widget-content searchable-container list">
        @include('core::components.alerts')

        <form action="{{ route('ecommerce.products.store') }}" method="POST">
            @csrf
            <div class="card">
                <div class="card-body">
                    <ul class="nav nav-tabs mb-4" role="tablist">
                        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-basic" type="button">Basico</button></li>
                        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-pricing" type="button">Precios</button></li>
                        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-inventory" type="button">Inventario</button></li>
                        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-shipping" type="button">Envio</button></li>
                        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-images" type="button">Imagenes</button></li>
                    </ul>

                    <div class="tab-content">
                        {{-- Tab Basico --}}
                        <div class="tab-pane fade show active" id="tab-basic">
                            <div class="row g-3">
                                <div class="col-md-8">
                                    <div class="mb-3">
                                        <label class="form-label">Nombre <span class="text-danger">*</span></label>
                                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Slug <span class="text-danger">*</span></label>
                                        <input type="text" name="slug" class="form-control @error('slug') is-invalid @enderror" value="{{ old('slug') }}" required>
                                        @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Descripcion corta</label>
                                        <textarea name="description" class="form-control" rows="3">{{ old('description') }}</textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Contenido</label>
                                        <textarea name="content" class="form-control" rows="5">{{ old('content') }}</textarea>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Estado</label>
                                        <select name="status" class="form-select">
                                            <option value="published" {{ old('status') === 'published' ? 'selected' : '' }}>Publicado</option>
                                            <option value="draft" {{ old('status') === 'draft' ? 'selected' : '' }}>Borrador</option>
                                            <option value="pending" {{ old('status') === 'pending' ? 'selected' : '' }}>Pendiente</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Marca</label>
                                        <select name="brand_id" class="form-select">
                                            <option value="">Sin marca</option>
                                            @foreach($brands as $id => $name)
                                                <option value="{{ $id }}" {{ old('brand_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Categorias</label>
                                        <select name="categories[]" class="form-select" multiple size="5">
                                            @foreach($categories as $category)
                                                <option value="{{ $category->id }}" {{ in_array($category->id, old('categories', [])) ? 'selected' : '' }}>{{ $category->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Etiquetas</label>
                                        <select name="tags[]" class="form-select" multiple size="5">
                                            @foreach($tags as $id => $name)
                                                <option value="{{ $id }}" {{ in_array($id, old('tags', [])) ? 'selected' : '' }}>{{ $name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Colecciones</label>
                                        <select name="collections[]" class="form-select" multiple size="5">
                                            @foreach($collections as $id => $name)
                                                <option value="{{ $id }}" {{ in_array($id, old('collections', [])) ? 'selected' : '' }}>{{ $name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Orden</label>
                                        <input type="number" name="order" class="form-control" value="{{ old('order', 0) }}">
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="is_featured" value="1" id="is_featured" {{ old('is_featured') ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_featured">Producto destacado</label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="is_variation" value="1" id="is_variation" {{ old('is_variation') ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_variation">Es variacion</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Tab Precios --}}
                        <div class="tab-pane fade" id="tab-pricing">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Precio <span class="text-danger">*</span></label>
                                        <input type="number" step="0.01" name="price" class="form-control @error('price') is-invalid @enderror" value="{{ old('price') }}" required>
                                        @error('price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Precio de oferta</label>
                                        <input type="number" step="0.01" name="sale_price" class="form-control" value="{{ old('sale_price') }}">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Tipo de oferta</label>
                                        <select name="sale_type" class="form-select">
                                            <option value="default" {{ old('sale_type') === 'default' ? 'selected' : '' }}>Por defecto</option>
                                            <option value="percentage" {{ old('sale_type') === 'percentage' ? 'selected' : '' }}>Porcentaje</option>
                                            <option value="fixed" {{ old('sale_type') === 'fixed' ? 'selected' : '' }}>Fijo</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Fecha inicio oferta</label>
                                        <input type="datetime-local" name="start_date" class="form-control" value="{{ old('start_date') }}">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Fecha fin oferta</label>
                                        <input type="datetime-local" name="end_date" class="form-control" value="{{ old('end_date') }}">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Costo por item</label>
                                        <input type="number" step="0.01" name="cost_per_item" class="form-control" value="{{ old('cost_per_item') }}">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Codigo de barras</label>
                                        <input type="text" name="barcode" class="form-control" value="{{ old('barcode') }}">
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Tab Inventario --}}
                        <div class="tab-pane fade" id="tab-inventory">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">SKU</label>
                                        <input type="text" name="sku" class="form-control @error('sku') is-invalid @enderror" value="{{ old('sku') }}">
                                        @error('sku')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Cantidad</label>
                                        <input type="number" name="quantity" class="form-control" value="{{ old('quantity') }}">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Estado de stock</label>
                                        <select name="stock_status" class="form-select">
                                            <option value="in_stock" {{ old('stock_status') === 'in_stock' ? 'selected' : '' }}>En stock</option>
                                            <option value="out_of_stock" {{ old('stock_status') === 'out_of_stock' ? 'selected' : '' }}>Sin stock</option>
                                            <option value="backorder" {{ old('stock_status') === 'backorder' ? 'selected' : '' }}>Pedido pendiente</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Cantidad minima por orden</label>
                                        <input type="number" name="minimum_order_quantity" class="form-control" value="{{ old('minimum_order_quantity', 1) }}">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Cantidad maxima por orden</label>
                                        <input type="number" name="maximum_order_quantity" class="form-control" value="{{ old('maximum_order_quantity') }}">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="with_storehouse_management" value="1" id="with_storehouse_management" {{ old('with_storehouse_management') ? 'checked' : '' }}>
                                        <label class="form-check-label" for="with_storehouse_management">Gestionar almacen</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="allow_checkout_when_out_of_stock" value="1" id="allow_checkout_when_out_of_stock" {{ old('allow_checkout_when_out_of_stock') ? 'checked' : '' }}>
                                        <label class="form-check-label" for="allow_checkout_when_out_of_stock">Permitir checkout sin stock</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Tab Envio --}}
                        <div class="tab-pane fade" id="tab-shipping">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label">Peso (kg)</label>
                                        <input type="number" step="0.01" name="weight" class="form-control" value="{{ old('weight') }}">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label">Largo (cm)</label>
                                        <input type="number" step="0.01" name="length" class="form-control" value="{{ old('length') }}">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label">Ancho (cm)</label>
                                        <input type="number" step="0.01" name="wide" class="form-control" value="{{ old('wide') }}">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label">Alto (cm)</label>
                                        <input type="number" step="0.01" name="height" class="form-control" value="{{ old('height') }}">
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Tab Imagenes --}}
                        <div class="tab-pane fade" id="tab-images">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Imagen destacada (URL)</label>
                                        <input type="text" name="featured_image" class="form-control" value="{{ old('featured_image') }}">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Galeria de imagenes (URLs separadas por coma)</label>
                                        <input type="text" name="images" class="form-control" value="{{ old('images') }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer text-end">
                    <a href="{{ route('ecommerce.products.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Guardar producto</button>
                </div>
            </div>
        </form>
    </div>
@endsection
