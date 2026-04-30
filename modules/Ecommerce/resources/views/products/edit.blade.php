@extends('layouts.theme')

@section('title', 'Editar producto')

@section('page_header')
    @include('core::components.card', ['title' => 'Editar producto'])
@endsection

@section('content')
    @include('core::components.alerts')

    <form action="{{ route('ecommerce.products.update', $product) }}" method="POST">
        @csrf @method('PUT')

        <div class="row g-4 align-items-start">

            {{-- Columna principal --}}
            <div class="col-lg-8">

                {{-- 1. Información básica --}}
                <div class="card mb-4">
                    <div class="card-header p-3 border-bottom">
                        <h6 class="mb-0 fw-bold">Información básica</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Nombre <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                value="{{ old('name', $product->name) }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Slug <span class="text-danger">*</span></label>
                            <input type="text" name="slug" class="form-control @error('slug') is-invalid @enderror"
                                value="{{ old('slug', $product->slug) }}" required>
                            @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        {{-- Enlace permanente --}}
                        <div class="mb-3 p-3 bg-light rounded border">
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted fw-semibold text-uppercase" style="letter-spacing:.4px">Enlace permanente</small>
                                <a href="{{ url('products/' . $product->slug) }}" target="_blank" class="small text-decoration-none">
                                    Vista <i class="fas fa-external-link-alt ms-1"></i>
                                </a>
                            </div>
                            <div class="mt-1 small text-break">
                                <span class="text-muted">{{ rtrim(url('/'), '/') }}/products/</span><strong>{{ $product->slug }}</strong>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Descripción corta</label>
                            <textarea name="description" class="form-control" rows="3">{{ old('description', $product->description) }}</textarea>
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Contenido</label>
                            <textarea name="content" class="form-control wysiwyg-editor" rows="6">{{ old('content', $product->content) }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- 2. Imágenes --}}
                <div class="card mb-4">
                    <div class="card-header p-3 border-bottom">
                        <h6 class="mb-0 fw-bold">Imágenes</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Imagen destacada (URL)</label>
                            <input type="text" name="featured_image" class="form-control"
                                value="{{ old('featured_image', $product->featured_image) }}">
                            @if($product->featured_image)
                                <div class="mt-2">
                                    <img src="{{ $product->featured_image }}" class="rounded border object-fit-cover" width="120" height="90" alt="Imagen destacada">
                                </div>
                            @endif
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Galería de imágenes (URLs separadas por coma)</label>
                            <input type="text" name="images" class="form-control"
                                value="{{ old('images', $product->images) }}">
                        </div>
                    </div>
                </div>

                {{-- 3. Descripción general (pricing + inventory) --}}
                <div class="card mb-4">
                    <div class="card-header p-3 border-bottom">
                        <h6 class="mb-0 fw-bold">Descripción general</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">SKU</label>
                                <input type="text" name="sku" class="form-control @error('sku') is-invalid @enderror"
                                    value="{{ old('sku', $product->sku) }}">
                                @error('sku')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Precio <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" name="price"
                                    class="form-control @error('price') is-invalid @enderror"
                                    value="{{ old('price', $product->price) }}" required>
                                @error('price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Precio de oferta</label>
                                <input type="number" step="0.01" name="sale_price" class="form-control"
                                    value="{{ old('sale_price', $product->sale_price) }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Tipo de oferta</label>
                                <select name="sale_type" class="form-select">
                                    <option value="default" {{ old('sale_type', $product->sale_type) === 'default' ? 'selected' : '' }}>Por defecto</option>
                                    <option value="percentage" {{ old('sale_type', $product->sale_type) === 'percentage' ? 'selected' : '' }}>Porcentaje</option>
                                    <option value="fixed" {{ old('sale_type', $product->sale_type) === 'fixed' ? 'selected' : '' }}>Fijo</option>
                                </select>
                            </div>
                        </div>
                        <div class="row g-3 mt-2">
                            <div class="col-md-3">
                                <label class="form-label">Costo por artículo</label>
                                <input type="number" step="0.01" name="cost_per_item" class="form-control"
                                    value="{{ old('cost_per_item', $product->cost_per_item) }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Código de barras</label>
                                <input type="text" name="barcode" class="form-control"
                                    value="{{ old('barcode', $product->barcode) }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Cantidad</label>
                                <input type="number" name="quantity" class="form-control"
                                    value="{{ old('quantity', $product->quantity) }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Estado de stock</label>
                                <select name="stock_status" class="form-select">
                                    <option value="in_stock" {{ old('stock_status', $product->stock_status) === 'in_stock' ? 'selected' : '' }}>En stock</option>
                                    <option value="out_of_stock" {{ old('stock_status', $product->stock_status) === 'out_of_stock' ? 'selected' : '' }}>Sin stock</option>
                                    <option value="backorder" {{ old('stock_status', $product->stock_status) === 'backorder' ? 'selected' : '' }}>Pedido pendiente</option>
                                </select>
                            </div>
                        </div>
                        <div class="row g-3 mt-2">
                            <div class="col-md-6">
                                <label class="form-label">Fecha inicio oferta</label>
                                <input type="datetime-local" name="start_date" class="form-control"
                                    value="{{ old('start_date', $product->start_date?->format('Y-m-d\TH:i')) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Fecha fin oferta</label>
                                <input type="datetime-local" name="end_date" class="form-control"
                                    value="{{ old('end_date', $product->end_date?->format('Y-m-d\TH:i')) }}">
                            </div>
                        </div>
                        <div class="d-flex gap-3 mt-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="with_storehouse_management"
                                    value="1" id="with_storehouse_management"
                                    {{ old('with_storehouse_management', $product->with_storehouse_management) ? 'checked' : '' }}>
                                <label class="form-check-label" for="with_storehouse_management">Gestionar almacén</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="allow_checkout_when_out_of_stock"
                                    value="1" id="allow_checkout_when_out_of_stock"
                                    {{ old('allow_checkout_when_out_of_stock', $product->allow_checkout_when_out_of_stock) ? 'checked' : '' }}>
                                <label class="form-check-label" for="allow_checkout_when_out_of_stock">Permitir checkout sin stock</label>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 4. Transporte --}}
                <div class="card mb-4">
                    <div class="card-header p-3 border-bottom">
                        <h6 class="mb-0 fw-bold">Transporte</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Peso (kg)</label>
                                <input type="number" step="0.01" name="weight" class="form-control"
                                    value="{{ old('weight', $product->weight) }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Largo (cm)</label>
                                <input type="number" step="0.01" name="length" class="form-control"
                                    value="{{ old('length', $product->length) }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Ancho (cm)</label>
                                <input type="number" step="0.01" name="wide" class="form-control"
                                    value="{{ old('wide', $product->wide) }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Alto (cm)</label>
                                <input type="number" step="0.01" name="height" class="form-control"
                                    value="{{ old('height', $product->height) }}">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 5. Especificaciones --}}
                <div class="card mb-4">
                    <div class="card-header p-3 border-bottom">
                        <h6 class="mb-0 fw-bold">Tablas de especificaciones</h6>
                    </div>
                    <div class="card-body">
                        @if($specificationTables->count() > 0)
                            <p class="text-muted small mb-3">Selecciona las tablas de especificaciones que aplican a este producto.</p>
                            <div class="row g-2">
                                @foreach($specificationTables as $table)
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                name="specification_tables[]" value="{{ $table->id }}"
                                                id="spec_table_{{ $table->id }}"
                                                {{ in_array($table->id, old('specification_tables', $product->specificationTables->pluck('id')->toArray())) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="spec_table_{{ $table->id }}">{{ $table->name }}</label>
                                            @if($table->description)
                                                <small class="d-block text-muted">{{ $table->description }}</small>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-muted mb-0">No hay tablas de especificaciones. <a href="{{ route('ecommerce.specification-tables.create') }}">Crear una</a></p>
                        @endif
                    </div>
                </div>

                {{-- 6. Atributos --}}
                <div class="card mb-4">
                    <div class="card-header p-3 border-bottom">
                        <h6 class="mb-0 fw-bold">Atributos</h6>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-3">Define los conjuntos de atributos para las variantes de este producto (talla, color, material, etc.)</p>
                        @if($attributeSets->count() > 0)
                            <div class="row g-2">
                                @foreach($attributeSets as $set)
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                name="attribute_sets[]" value="{{ $set->id }}"
                                                id="attr_set_{{ $set->id }}"
                                                {{ in_array($set->id, old('attribute_sets', $product->attributeSets->pluck('id')->toArray())) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="attr_set_{{ $set->id }}">{{ $set->title }}</label>
                                            <small class="d-block text-muted">{{ $set->slug }}</small>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-muted mb-0">No hay conjuntos de atributos. <a href="{{ route('ecommerce.product-attribute-sets.create') }}">Crear uno</a></p>
                        @endif
                    </div>
                </div>

                {{-- 6b. Atributos del producto (clave=valor) --}}
                <div class="card mb-4">
                    <div class="card-header p-3 border-bottom">
                        <h6 class="mb-0 fw-bold">Atributos del producto</h6>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-3">Especificaciones del producto (ej: RAM = 8GB, Color = Rojo).</p>

                        <div id="spec-attrs-body">
                            @foreach($product->specificationAttributes as $attr)
                                <div class="input-group input-group-sm mb-2 spec-attr-row">
                                    <span class="input-group-text" style="min-width:140px">{{ $attr->title }}</span>
                                    <input type="text" name="spec_attributes[{{ $attr->id }}]"
                                        class="form-control" value="{{ $attr->pivot->value ?? '' }}"
                                        placeholder="Valor">
                                    <button type="button" class="btn btn-outline-secondary btn-remove-spec-attr">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            @endforeach
                        </div>

                        @if($attributeSets->count() > 0)
                            <div class="d-flex gap-2 mt-2">
                                <select id="spec-attr-select" class="form-select form-select-sm">
                                    <option value="">Seleccionar atributo...</option>
                                    @foreach($attributeSets as $set)
                                        @foreach($set->attributes as $attr)
                                            <option value="{{ $attr->id }}" data-title="{{ $attr->title }}">{{ $set->title }}: {{ $attr->title }}</option>
                                        @endforeach
                                    @endforeach
                                </select>
                                <input type="text" id="spec-attr-value" class="form-control form-control-sm"
                                    placeholder="Valor" style="min-width:120px">
                                <button type="button" id="btn-add-spec-attr" class="btn btn-sm btn-outline-primary text-nowrap">
                                    Agregar
                                </button>
                            </div>
                        @else
                            <p class="text-muted small mb-0">No hay atributos definidos. <a href="{{ route('ecommerce.product-attribute-sets.create') }}">Crear conjunto</a></p>
                        @endif
                    </div>
                </div>

                {{-- 7. Opciones de producto --}}
                <div class="card mb-4">
                    <div class="card-header p-3 border-bottom">
                        <h6 class="mb-0 fw-bold">Opciones de producto</h6>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-3">Define opciones personalizables para este producto (texto personalizado, grabado, color especial, etc.)</p>

                        <div id="options-container">
                            @foreach($product->options as $optionIdx => $option)
                                <div class="card mb-3 option-block" data-index="{{ $optionIdx }}">
                                    <div class="card-header d-flex justify-content-between align-items-center py-2">
                                        <span class="fw-semibold small">Opcion #<span class="option-num">{{ $optionIdx + 1 }}</span></span>
                                        <button type="button" class="btn btn-sm btn-outline-danger btn-remove-option">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                    <div class="card-body">
                                        <div class="row g-3">
                                            <div class="col-md-5">
                                                <label class="form-label">Nombre <span class="text-danger">*</span></label>
                                                <input type="text" name="options[{{ $optionIdx }}][name]" class="form-control"
                                                    value="{{ $option->name }}" placeholder="Ej: ¿Agregar grabado?">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Tipo</label>
                                                <select name="options[{{ $optionIdx }}][option_type]" class="form-select option-type-select">
                                                    <option value="text" {{ $option->option_type === 'text' ? 'selected' : '' }}>Texto</option>
                                                    <option value="textarea" {{ $option->option_type === 'textarea' ? 'selected' : '' }}>Texto largo</option>
                                                    <option value="select" {{ $option->option_type === 'select' ? 'selected' : '' }}>Desplegable</option>
                                                    <option value="checkbox" {{ $option->option_type === 'checkbox' ? 'selected' : '' }}>Casillas</option>
                                                    <option value="radio" {{ $option->option_type === 'radio' ? 'selected' : '' }}>Radio</option>
                                                    <option value="file" {{ $option->option_type === 'file' ? 'selected' : '' }}>Archivo</option>
                                                </select>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label">Orden</label>
                                                <input type="number" name="options[{{ $optionIdx }}][order]" class="form-control"
                                                    value="{{ $option->order }}" min="0">
                                            </div>
                                            <div class="col-md-2 d-flex align-items-end pb-1">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox"
                                                        name="options[{{ $optionIdx }}][required]" value="1"
                                                        id="req_{{ $optionIdx }}" {{ $option->required ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="req_{{ $optionIdx }}">Requerido</label>
                                                </div>
                                            </div>
                                            <div class="col-12 option-values-section" @style(['display:none' => !in_array($option->option_type, ['select', 'checkbox', 'radio'])])>
                                                <label class="form-label">Valores de la opcion</label>
                                                <div class="option-values-list">
                                                    @foreach($option->values as $valIdx => $value)
                                                        <div class="input-group mb-2 option-value-row">
                                                            <input type="text" name="options[{{ $optionIdx }}][values][{{ $valIdx }}][option_value]"
                                                                class="form-control" value="{{ $value->option_value }}" placeholder="Valor">
                                                            <input type="number" step="0.01" name="options[{{ $optionIdx }}][values][{{ $valIdx }}][affect_price]"
                                                                class="form-control" value="{{ $value->affect_price }}" placeholder="Precio" style="max-width:100px">
                                                            <select name="options[{{ $optionIdx }}][values][{{ $valIdx }}][affect_type]" class="form-select" style="max-width:110px">
                                                                <option value="plus" {{ $value->affect_type === 'plus' ? 'selected' : '' }}>+ Suma</option>
                                                                <option value="minus" {{ $value->affect_type === 'minus' ? 'selected' : '' }}>- Resta</option>
                                                                <option value="percent" {{ $value->affect_type === 'percent' ? 'selected' : '' }}>% Porcent</option>
                                                            </select>
                                                            <button type="button" class="btn btn-outline-secondary btn-remove-value">
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                        </div>
                                                    @endforeach
                                                </div>
                                                <button type="button" class="btn btn-outline-secondary btn-sm btn-add-value mt-1">
                                                    <i class="fas fa-plus me-1"></i> Valor
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <button type="button" class="btn btn-outline-primary btn-sm" id="btn-add-option">
                            <i class="fas fa-plus me-1"></i> Agregar opcion
                        </button>

                        <template id="option-template">
                            <div class="card mb-3 option-block" data-index="__IDX__">
                                <div class="card-header d-flex justify-content-between align-items-center py-2">
                                    <span class="fw-semibold small">Opcion #<span class="option-num">1</span></span>
                                    <button type="button" class="btn btn-sm btn-outline-danger btn-remove-option">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-5">
                                            <label class="form-label">Nombre <span class="text-danger">*</span></label>
                                            <input type="text" name="options[__IDX__][name]" class="form-control" placeholder="Ej: ¿Agregar grabado?">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Tipo</label>
                                            <select name="options[__IDX__][option_type]" class="form-select option-type-select">
                                                <option value="text">Texto</option>
                                                <option value="textarea">Texto largo</option>
                                                <option value="select">Desplegable</option>
                                                <option value="checkbox">Casillas</option>
                                                <option value="radio">Radio</option>
                                                <option value="file">Archivo</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Orden</label>
                                            <input type="number" name="options[__IDX__][order]" class="form-control" value="0" min="0">
                                        </div>
                                        <div class="col-md-2 d-flex align-items-end pb-1">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="options[__IDX__][required]" value="1" id="req___IDX__">
                                                <label class="form-check-label" for="req___IDX__">Requerido</label>
                                            </div>
                                        </div>
                                        <div class="col-12 option-values-section" style="display:none;">
                                            <label class="form-label">Valores de la opcion</label>
                                            <div class="option-values-list"></div>
                                            <button type="button" class="btn btn-outline-secondary btn-sm btn-add-value mt-1">
                                                <i class="fas fa-plus me-1"></i> Valor
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <template id="option-value-template">
                            <div class="input-group mb-2 option-value-row">
                                <input type="text" name="options[__IDX__][values][__VIDX__][option_value]" class="form-control" placeholder="Valor">
                                <input type="number" step="0.01" name="options[__IDX__][values][__VIDX__][affect_price]" class="form-control" placeholder="Precio" style="max-width:100px">
                                <select name="options[__IDX__][values][__VIDX__][affect_type]" class="form-select" style="max-width:110px">
                                    <option value="plus">+ Suma</option>
                                    <option value="minus">- Resta</option>
                                    <option value="percent">% Porcent</option>
                                </select>
                                <button type="button" class="btn btn-outline-secondary btn-remove-value"><i class="fas fa-times"></i></button>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- 8. Variaciones --}}
                <div class="card mb-4">
                    <div class="card-header p-3 border-bottom">
                        <h6 class="mb-0 fw-bold">Variaciones</h6>
                    </div>
                    <div class="card-body">
                        @if($product->variations->count() > 0)
                            <div class="table-responsive mb-3">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Nombre</th>
                                            <th>SKU</th>
                                            <th>Precio</th>
                                            <th>Stock</th>
                                            <th>Atributos</th>
                                            <th>Por defecto</th>
                                            <th class="text-center">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($product->variations as $variation)
                                            <tr>
                                                <td class="fw-semibold">{{ $variation->product->name ?? '—' }}</td>
                                                <td><small class="text-muted">{{ $variation->product->sku ?? '—' }}</small></td>
                                                <td>${{ number_format($variation->product->price ?? 0, 2) }}</td>
                                                <td>
                                                    <span class="badge bg-{{ ($variation->product->quantity ?? 0) > 0 ? 'success' : 'warning' }}">
                                                        {{ $variation->product->quantity ?? 0 }}
                                                    </span>
                                                </td>
                                                <td>
                                                    @foreach($variation->variationItems as $item)
                                                        @if($item->attribute)
                                                            <span class="badge bg-light text-dark border me-1">{{ $item->attribute->title }}</span>
                                                        @endif
                                                    @endforeach
                                                </td>
                                                <td>
                                                    @if($variation->is_default)
                                                        <span class="badge bg-success">Si</span>
                                                    @else
                                                        <form action="{{ route('ecommerce.products.variations.default', [$product, $variation]) }}" method="POST" class="d-inline">
                                                            @csrf
                                                            <button type="submit" class="btn btn-sm btn-outline-secondary">Establecer</button>
                                                        </form>
                                                    @endif
                                                </td>
                                                <td class="text-center">
                                                    <div class="dropdown">
                                                        <button type="button" class="btn btn-sm btn-light" data-bs-toggle="dropdown">
                                                            <i class="fas fa-ellipsis-vertical"></i>
                                                        </button>
                                                        <ul class="dropdown-menu dropdown-menu-end">
                                                            @if($variation->product_id)
                                                                <li><a class="dropdown-item" href="{{ route('ecommerce.products.edit', $variation->product_id) }}">Editar producto variacion</a></li>
                                                            @endif
                                                            <li>
                                                                <form action="{{ route('ecommerce.products.variations.destroy', [$product, $variation]) }}" method="POST">
                                                                    @csrf @method('DELETE')
                                                                    <button type="submit" class="dropdown-item"
                                                                        onclick="return confirm('¿Eliminar esta variacion y su producto asociado?')">Eliminar</button>
                                                                </form>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-4 bg-light rounded mb-3">
                                <i class="fas fa-layer-group fa-2x text-muted opacity-50 mb-2"></i>
                                <p class="text-muted mb-0 small">No hay variaciones definidas</p>
                            </div>
                        @endif

                        <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addVariationModal">
                            <i class="fas fa-plus me-1"></i> Agregar variacion
                        </button>
                    </div>
                </div>

                {{-- 9. Venta cruzada --}}
                <div class="card mb-4">
                    <div class="card-header p-3 border-bottom">
                        <h6 class="mb-0 fw-bold">Venta cruzada</h6>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-3">Productos sugeridos al cliente al agregar este al carrito.</p>
                        <div id="cross-sales-list" class="mb-3">
                            @foreach($product->crossSales as $cs)
                                <div class="d-flex align-items-center gap-2 mb-2 related-item">
                                    <input type="hidden" name="cross_sales[]" value="{{ $cs->id }}">
                                    @if($cs->featured_image)
                                        <img src="{{ $cs->featured_image }}" width="36" height="36"
                                            class="rounded border object-fit-cover flex-shrink-0">
                                    @else
                                        <div class="rounded border bg-light d-flex align-items-center justify-content-center flex-shrink-0"
                                            style="width:36px;height:36px">
                                            <i class="fas fa-box text-muted small"></i>
                                        </div>
                                    @endif
                                    <div class="flex-grow-1 small">
                                        <div class="fw-semibold">{{ $cs->name }}</div>
                                        @if($cs->sku)<small class="text-muted">{{ $cs->sku }}</small>@endif
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-secondary btn-remove-related">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                        <div class="position-relative">
                            <input type="text" id="cross-sale-search" class="form-control form-control-sm"
                                placeholder="Buscar producto para agregar...">
                            <div id="cross-sale-results" class="list-group position-absolute w-100 shadow-sm z-1" style="display:none;top:100%;left:0"></div>
                        </div>
                    </div>
                </div>

                {{-- 10. Productos relacionados --}}
                <div class="card mb-4">
                    <div class="card-header p-3 border-bottom">
                        <h6 class="mb-0 fw-bold">Productos relacionados</h6>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-3">Productos sugeridos en la página de detalle.</p>
                        <div id="related-products-list" class="mb-3">
                            @foreach($product->relatedProducts as $rp)
                                <div class="d-flex align-items-center gap-2 mb-2 related-item">
                                    <input type="hidden" name="related_products[]" value="{{ $rp->id }}">
                                    @if($rp->featured_image)
                                        <img src="{{ $rp->featured_image }}" width="36" height="36"
                                            class="rounded border object-fit-cover flex-shrink-0">
                                    @else
                                        <div class="rounded border bg-light d-flex align-items-center justify-content-center flex-shrink-0"
                                            style="width:36px;height:36px">
                                            <i class="fas fa-box text-muted small"></i>
                                        </div>
                                    @endif
                                    <div class="flex-grow-1 small">
                                        <div class="fw-semibold">{{ $rp->name }}</div>
                                        @if($rp->sku)<small class="text-muted">{{ $rp->sku }}</small>@endif
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-secondary btn-remove-related">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                        <div class="position-relative">
                            <input type="text" id="related-product-search" class="form-control form-control-sm"
                                placeholder="Buscar producto para agregar...">
                            <div id="related-product-results" class="list-group position-absolute w-100 shadow-sm z-1" style="display:none;top:100%;left:0"></div>
                        </div>
                    </div>
                </div>

                {{-- 11. SEO --}}
                <div class="card mb-4">
                    <div class="card-header p-3 border-bottom">
                        <h6 class="mb-0 fw-bold">Optimizar para motores de búsqueda</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Título SEO</label>
                            <input type="text" name="meta_title" class="form-control"
                                value="{{ old('meta_title', $product->meta_title) }}"
                                placeholder="{{ $product->name }}" maxlength="255">
                            <div class="form-text">Recomendado: 50–60 caracteres.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Descripción SEO</label>
                            <textarea name="meta_description" class="form-control" rows="3"
                                placeholder="Descripción para motores de búsqueda...">{{ old('meta_description', $product->meta_description) }}</textarea>
                            <div class="form-text">Recomendado: 150–160 caracteres.</div>
                        </div>
                        @if($product->meta_title || $product->meta_description)
                            <div class="border rounded p-3 bg-light">
                                <div class="small text-muted mb-2 fw-semibold">Vista previa en buscador</div>
                                <div class="text-primary fw-semibold" style="font-size:.95rem">
                                    {{ $product->meta_title ?: $product->name }}
                                </div>
                                <div class="small text-success">{{ url('products/' . $product->slug) }}</div>
                                <div class="small text-muted mt-1">
                                    {{ $product->meta_description ?: Str::limit($product->description, 160) }}
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

            </div>{{-- /col-lg-8 --}}

            {{-- Sidebar --}}
            <div class="col-lg-4">
                <div class="sticky-top" style="top:80px">

                    {{-- Publicar --}}
                    <div class="card mb-4">
                        <div class="card-body">
                            <button type="submit" class="btn btn-primary w-100 mb-2">Guardar cambios</button>
                            <a href="{{ route('ecommerce.products.index') }}" class="btn btn-outline-secondary w-100">Cancelar</a>
                        </div>
                    </div>

                    {{-- Estado --}}
                    <div class="card mb-4">
                        <div class="card-header p-3 border-bottom">
                            <h6 class="mb-0 fw-bold">Estado</h6>
                        </div>
                        <div class="card-body">
                            <label class="form-label">Estado</label>
                            <select name="status" class="form-select">
                                <option value="published" {{ old('status', $product->status->value) === 'published' ? 'selected' : '' }}>Publicado</option>
                                <option value="draft" {{ old('status', $product->status->value) === 'draft' ? 'selected' : '' }}>Borrador</option>
                                <option value="pending" {{ old('status', $product->status->value) === 'pending' ? 'selected' : '' }}>Pendiente</option>
                            </select>
                            <div class="form-check mt-3">
                                <input class="form-check-input" type="checkbox" name="is_featured" value="1"
                                    id="is_featured" {{ old('is_featured', $product->is_featured) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_featured">Producto destacado</label>
                            </div>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="is_variation" value="1"
                                    id="is_variation" {{ old('is_variation', $product->is_variation) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_variation">Es variacion</label>
                            </div>
                        </div>
                    </div>

                    {{-- Categorías --}}
                    <div class="card mb-4">
                        <div class="card-header p-3 border-bottom">
                            <h6 class="mb-0 fw-bold">Categorías</h6>
                        </div>
                        <div class="card-body p-0">
                            <ul class="list-group list-group-flush">
                                @foreach($categories as $category)
                                    <li class="list-group-item px-3 py-2">
                                        <div class="form-check mb-0">
                                            <input class="form-check-input" type="checkbox"
                                                name="categories[]" value="{{ $category->id }}"
                                                id="cat_{{ $category->id }}"
                                                {{ in_array($category->id, old('categories', $product->categories->pluck('id')->toArray())) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="cat_{{ $category->id }}">{{ $category->name }}</label>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>

                    {{-- Marca --}}
                    <div class="card mb-4">
                        <div class="card-header p-3 border-bottom">
                            <h6 class="mb-0 fw-bold">Marca</h6>
                        </div>
                        <div class="card-body">
                            <select name="brand_id" class="form-select">
                                <option value="">Sin marca</option>
                                @foreach($brands as $id => $name)
                                    <option value="{{ $id }}" {{ old('brand_id', $product->brand_id) == $id ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Colecciones --}}
                    <div class="card mb-4">
                        <div class="card-header p-3 border-bottom">
                            <h6 class="mb-0 fw-bold">Colecciones</h6>
                        </div>
                        <div class="card-body">
                            <select name="collections[]" class="form-select" multiple size="4">
                                @foreach($collections as $id => $name)
                                    <option value="{{ $id }}" {{ in_array($id, old('collections', $product->collections->pluck('id')->toArray())) ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Etiqueta --}}
                    <div class="card mb-4">
                        <div class="card-header p-3 border-bottom">
                            <h6 class="mb-0 fw-bold">Etiqueta</h6>
                        </div>
                        <div class="card-body">
                            <select name="label_id" class="form-select">
                                <option value="">Sin etiqueta</option>
                                @foreach($labels as $label)
                                    <option value="{{ $label->id }}"
                                        {{ old('label_id', $product->label_id) == $label->id ? 'selected' : '' }}
                                        style="background-color: {{ $label->color }}; color: {{ $label->text_color }}">
                                        {{ $label->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Tags --}}
                    <div class="card mb-4">
                        <div class="card-header p-3 border-bottom">
                            <h6 class="mb-0 fw-bold">Tags</h6>
                        </div>
                        <div class="card-body">
                            <select name="tags[]" class="form-select" multiple size="4">
                                @foreach($tags as $id => $name)
                                    <option value="{{ $id }}" {{ in_array($id, old('tags', $product->tags->pluck('id')->toArray())) ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Configuración de pedido --}}
                    <div class="card mb-4">
                        <div class="card-header p-3 border-bottom">
                            <h6 class="mb-0 fw-bold">Configuración de pedido</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-6">
                                    <label class="form-label">Cantidad mínima</label>
                                    <input type="number" name="minimum_order_quantity" class="form-control"
                                        value="{{ old('minimum_order_quantity', $product->minimum_order_quantity) }}">
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Cantidad máxima</label>
                                    <input type="number" name="maximum_order_quantity" class="form-control"
                                        value="{{ old('maximum_order_quantity', $product->maximum_order_quantity) }}">
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Orden</label>
                                    <input type="number" name="order" class="form-control"
                                        value="{{ old('order', $product->order) }}">
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>{{-- /col-lg-4 --}}

        </div>{{-- /row --}}
    </form>
@endsection

{{-- Modal agregar variacion (fuera del form principal) --}}
<div class="modal fade" id="addVariationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form action="{{ route('ecommerce.products.variations.store', $product) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Agregar variacion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nombre <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required placeholder="Ej: Talla M - Azul">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">SKU</label>
                            <input type="text" name="sku" class="form-control" placeholder="Codigo unico">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Precio <span class="text-danger">*</span></label>
                            <input type="number" name="price" class="form-control" step="0.01" min="0" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Stock</label>
                            <input type="number" name="quantity" class="form-control" min="0" value="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Por defecto</label>
                            <div class="form-check mt-2">
                                <input type="checkbox" name="is_default" value="1" class="form-check-input" id="var_is_default">
                                <label class="form-check-label" for="var_is_default">Si</label>
                            </div>
                        </div>
                        @if($attributeSets->count() > 0)
                            <div class="col-12">
                                <label class="form-label">Atributos de variacion</label>
                                @foreach($attributeSets as $set)
                                    @if($set->attributes->count() > 0)
                                        <div class="mb-2">
                                            <small class="text-muted fw-semibold">{{ $set->title }}</small>
                                            <div class="d-flex flex-wrap gap-2 mt-1">
                                                @foreach($set->attributes as $attribute)
                                                    <div class="form-check">
                                                        <input type="checkbox" name="attribute_ids[]" value="{{ $attribute->id }}"
                                                            class="form-check-input" id="attr_{{ $attribute->id }}">
                                                        <label class="form-check-label" for="attr_{{ $attribute->id }}">
                                                            @if($attribute->color)
                                                                <span class="d-inline-block rounded-circle border me-1"
                                                                    style="width:12px;height:12px;background:{{ $attribute->color }};vertical-align:middle"></span>
                                                            @endif
                                                            {{ $attribute->title }}
                                                        </label>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
                <div class="modal-footer flex-column">
                    <button type="submit" class="btn btn-primary w-100 mb-2">Guardar variacion</button>
                    <button type="button" class="btn btn-outline-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
@include('ecommerce::partials._wysiwyg-script')
<script>
(function () {
    // ── Spec attributes ──────────────────────────────────────────
    var existingSpecIds = [{{ $product->specificationAttributes->pluck('id')->implode(',') }}];

    document.getElementById('spec-attrs-body').addEventListener('click', function (e) {
        if (e.target.closest('.btn-remove-spec-attr')) {
            var row = e.target.closest('.spec-attr-row');
            if (row) { row.remove(); }
        }
    });

    var btnAddSpec = document.getElementById('btn-add-spec-attr');
    if (btnAddSpec) {
        btnAddSpec.addEventListener('click', function () {
            var sel = document.getElementById('spec-attr-select');
            var valInput = document.getElementById('spec-attr-value');
            var attrId = sel.value;
            var attrTitle = sel.options[sel.selectedIndex] ? sel.options[sel.selectedIndex].dataset.title : '';
            var attrValue = valInput.value.trim();

            if (!attrId) { return; }

            var row = document.createElement('div');
            row.className = 'input-group input-group-sm mb-2 spec-attr-row';
            row.innerHTML = '<span class="input-group-text" style="min-width:140px">' + attrTitle + '</span>' +
                '<input type="text" name="spec_attributes[' + attrId + ']" class="form-control" value="' + attrValue + '" placeholder="Valor">' +
                '<button type="button" class="btn btn-outline-secondary btn-remove-spec-attr"><i class="fas fa-times"></i></button>';
            document.getElementById('spec-attrs-body').appendChild(row);

            sel.value = '';
            valInput.value = '';
        });
    }

    // ── Product search (cross-sales & related) ────────────────────
    var searchUrl = '{{ route('ecommerce.products.search') }}';
    var currentProductId = {{ $product->id }};

    function initProductSearch(inputId, resultsId, listId, inputName) {
        var $input = $('#' + inputId);
        var $results = $('#' + resultsId);
        var $list = $('#' + listId);
        var timer;

        $input.on('input', function () {
            clearTimeout(timer);
            var q = $(this).val().trim();
            if (q.length < 2) { $results.hide().empty(); return; }

            var excluded = [currentProductId];
            $list.find('input[name="' + inputName + '[]"]').each(function () {
                excluded.push(parseInt($(this).val()));
            });

            timer = setTimeout(function () {
                $.get(searchUrl, { q: q, exclude: excluded }, function (data) {
                    $results.empty();
                    if (!data.length) {
                        $results.append('<div class="list-group-item text-muted small">Sin resultados</div>').show();
                        return;
                    }
                    $.each(data, function (_, p) {
                        var img = p.featured_image
                            ? '<img src="' + p.featured_image + '" width="30" height="30" class="rounded border object-fit-cover flex-shrink-0 me-2">'
                            : '<div class="rounded border bg-light d-flex align-items-center justify-content-center flex-shrink-0 me-2" style="width:30px;height:30px"><i class="fas fa-box text-muted"></i></div>';
                        var $item = $('<button type="button" class="list-group-item list-group-item-action d-flex align-items-center gap-1 py-2">' +
                            img + '<div><div class="small fw-semibold">' + p.name + '</div>' +
                            (p.sku ? '<small class="text-muted">' + p.sku + '</small>' : '') + '</div></button>');
                        $item.on('click', function () {
                            addProductItem($list, p, inputName);
                            $results.hide().empty();
                            $input.val('');
                        });
                        $results.append($item);
                    });
                    $results.show();
                });
            }, 300);
        });

        $(document).on('click', function (e) {
            if (!$(e.target).closest('#' + inputId + ', #' + resultsId).length) {
                $results.hide();
            }
        });

        $list.on('click', '.btn-remove-related', function () {
            $(this).closest('.related-item').remove();
        });
    }

    function addProductItem($list, p, inputName) {
        var img = p.featured_image
            ? '<img src="' + p.featured_image + '" width="36" height="36" class="rounded border object-fit-cover flex-shrink-0">'
            : '<div class="rounded border bg-light d-flex align-items-center justify-content-center flex-shrink-0" style="width:36px;height:36px"><i class="fas fa-box text-muted small"></i></div>';
        var $row = $('<div class="d-flex align-items-center gap-2 mb-2 related-item">' +
            '<input type="hidden" name="' + inputName + '[]" value="' + p.id + '">' +
            img +
            '<div class="flex-grow-1 small"><div class="fw-semibold">' + p.name + '</div>' +
            (p.sku ? '<small class="text-muted">' + p.sku + '</small>' : '') + '</div>' +
            '<button type="button" class="btn btn-sm btn-outline-secondary btn-remove-related"><i class="fas fa-times"></i></button>' +
            '</div>');
        $list.append($row);
    }

    initProductSearch('cross-sale-search', 'cross-sale-results', 'cross-sales-list', 'cross_sales');
    initProductSearch('related-product-search', 'related-product-results', 'related-products-list', 'related_products');

    // ── Options ───────────────────────────────────────────────────
    var optIdx = {{ $product->options->count() }};

    document.getElementById('btn-add-option').addEventListener('click', function () {
        addOption();
    });

    function addOption() {
        var tmpl = document.getElementById('option-template').innerHTML.replace(/__IDX__/g, optIdx);
        var div = document.createElement('div');
        div.innerHTML = tmpl;
        document.getElementById('options-container').appendChild(div.firstElementChild);
        updateOptionNumbers();
        optIdx++;
    }

    document.getElementById('options-container').addEventListener('click', function (e) {
        if (e.target.closest('.btn-remove-option')) {
            e.target.closest('.option-block').remove();
            updateOptionNumbers();
        }
        if (e.target.closest('.btn-add-value')) {
            var block = e.target.closest('.option-block');
            addValue(block, block.dataset.index);
        }
        if (e.target.closest('.btn-remove-value')) {
            e.target.closest('.option-value-row').remove();
        }
    });

    document.getElementById('options-container').addEventListener('change', function (e) {
        if (e.target.classList.contains('option-type-select')) {
            var section = e.target.closest('.option-block').querySelector('.option-values-section');
            section.style.display = ['select', 'checkbox', 'radio'].includes(e.target.value) ? '' : 'none';
        }
    });

    function addValue(block, idx) {
        var list = block.querySelector('.option-values-list');
        var vIdx = list.children.length;
        var tmpl = document.getElementById('option-value-template').innerHTML
            .replace(/__IDX__/g, idx).replace(/__VIDX__/g, vIdx);
        var div = document.createElement('div');
        div.innerHTML = tmpl;
        list.appendChild(div.firstElementChild);
    }

    function updateOptionNumbers() {
        document.querySelectorAll('#options-container .option-block').forEach(function (block, i) {
            block.querySelector('.option-num').textContent = i + 1;
        });
    }
}());
</script>
@endpush
